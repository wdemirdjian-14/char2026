# CHAR 2026 — char2026.walautao.fr

Site de campagne pour les élections du **Club de Hockey Amateur de Rouen** (juin 2026).
Liste « Ensemble, dans le respect et l'exigence », portée par Pierre Dehaen.

Site statique + une petite API PHP pour le compteur de soutiens et la collecte d'e-mails.
Conçu **mobile first**.

## Domaines

Le site répond sur trois adresses, servies par le même vhost et couvertes par
un seul certificat Let's Encrypt (renouvellement automatique, expire le
7 décembre 2026) :

- **https://char2026.fr** — adresse officielle
- https://www.char2026.fr
- https://char2026.walautao.fr — adresse d'origine, toujours active

Aucun domaine n'est codé en dur dans le site : tout se déduit de `HTTP_HOST`.
Seule exception voulue, le **QR code** de l'admin, qui encode toujours
`site.domaine` (modifiable dans les textes) plutôt que l'adresse par laquelle
on consulte l'admin — sinon un QR imprimé pourrait pointer vers l'ancienne.

Pour faire de `char2026.fr` la seule adresse et rediriger les deux autres,
ajouter dans le bloc 443 de `deploiement/nginx-char2026.conf` :

```nginx
if ($host != char2026.fr) { return 301 https://char2026.fr$request_uri; }
```

## Espace d'administration

`https://char2026.walautao.fr/admin/` — trois onglets :

- **Audience** — pages vues, visiteurs, taux de soutien, fréquentation quotidienne,
  clics par bouton, provenance, appareils, profondeur de lecture. Mesure interne,
  **sans cookie ni service tiers** : les visiteurs sont comptés par une empreinte
  anonyme (SHA-256 salé, renouvelée chaque jour), aucune adresse IP n'est conservée.
- **Soutiens** — liste complète, recherche, répartition par profil, export CSV,
  copie de toutes les adresses, suppression unitaire (droit à l'effacement RGPD).
- **Textes du site** — édition de l'intégralité des contenus (122 champs), publiés
  immédiatement. Une sauvegarde horodatée est écrite avant chaque enregistrement.
- **Programme & QR** — envoi du programme en PDF (20 Mo max, type réel vérifié :
  en-tête `%PDF-` *et* type MIME, un fichier déguisé est refusé) et génération du
  QR code du site, téléchargeable en PNG ou imprimable en affichette A4.

Le programme s'ouvre par un **lien direct** vers le PDF, placé à côté du logo
dans la barre du haut (et dans le menu mobile). Pas de fenêtre intermédiaire :
le navigateur ouvre le fichier avec son propre lecteur.

Le PDF est envoyé depuis l'admin, donc **exclu du dépôt et protégé pendant le
rsync** : un déploiement ne l'efface pas. Le bouton « Le programme » n'apparaît
dans la barre du haut que si un PDF est effectivement en ligne.

Sécurité : mot de passe stocké en empreinte bcrypt (jamais en clair), session
`HttpOnly` + `Secure` + `SameSite=Strict` limitée à `/admin/`, jeton CSRF sur tous
les envois, 5 tentatives de connexion par quart d'heure, déconnexion après 2 h
d'inactivité, nettoyage du HTML saisi (les balises exécutables sont retirées).

## Son

La corne de but des Dragons (`assets/son/goal-horn.mp3`, 29 s) se lance à
l'ouverture du site et rejoue à chaque nouveau soutien enregistré — un but.

Deux points à connaître :

- **Les navigateurs interdisent la lecture audio automatique.** Le script tente
  au chargement, puis réessaie à chaque geste « activant » du visiteur jusqu'à
  réussir. Attention : le **défilement et `touchstart` ne comptent pas** comme
  geste activant — seuls `pointerdown/up`, `mousedown/up`, `touchend`, `keydown`
  et `click` débloquent l'audio. Le son démarre donc au premier clic ou appui.
  Chrome autorise parfois la lecture immédiate sur les sites déjà visités.
- Un bouton haut-parleur dans la barre du haut coupe le son. Le choix est
  mémorisé, et **si le son est coupé le MP3 n'est pas téléchargé du tout**
  (`preload="none"`) : aucun coût pour ces visiteurs.

Tout se règle depuis l'admin, onglet « Textes du site », bloc *Son* :
activation, fichier et volume (35 % par défaut).

## Cache

La page HTML est servie en `no-cache, must-revalidate` : le visiteur reçoit
toujours le markup à jour. Les feuilles de style, scripts, images et sons sont
versionnés par `filemtime` et mis en cache 7 jours.

À savoir sur nginx : un `add_header` dans un bloc `location` **annule tous ceux
hérités du serveur**. Les en-têtes de sécurité sont donc répétés dans chaque
bloc qui définit son propre cache.

## Arrivée sur le site

`history.scrollRestoration` est mis à `manual` : le visiteur arrive toujours en
haut, jamais à la position qu'il occupait lors de sa visite précédente — sans
quoi il atterrissait au milieu de l'accueil épinglé. Une adresse avec ancre
(`#soutien` depuis un QR code) reste honorée et descend à la bonne section.

## Contenu éditable

Tous les textes vivent dans `contenu.json`, rendu côté serveur par `index.php`.
Aucun texte n'est en dur dans le gabarit : ajouter une priorité ou changer une
formule se fait depuis l'admin, sans toucher au code.

## Secrets

Identifiant, empreinte du mot de passe, clé d'export et sel des statistiques sont
dans un fichier **hors racine web** (`/var/www/char2026-secrets/config.php`),
jamais dans ce dépôt. Modèle : `deploiement/secrets-exemple.php`.

## Déploiement — par tag

Le déploiement se déclenche **uniquement sur un tag** commençant par `v` :

```bash
git tag v1.0.0
git push origin v1.0.0
```

Le workflow `.github/workflows/deploiement.yml` envoie les fichiers sur le VPS par
rsync/SSH, ajuste les droits de `/data`, puis contrôle que la page et l'API répondent.
Un lancement manuel reste possible depuis l'onglet **Actions**.

### Secrets à définir
`Settings › Secrets and variables › Actions`

| Secret | Rôle | Exemple |
|---|---|---|
| `VPS_HOST` | Hôte du VPS | `93.93.117.124` |
| `VPS_USER` | Utilisateur SSH | `root` |
| `VPS_SSH_KEY` | Clé **privée** de déploiement (contenu complet) | `-----BEGIN OPENSSH PRIVATE KEY-----…` |
| `VPS_PATH` | Racine du sous-domaine sur le VPS | `/var/www/char2026.walautao.fr` |
| `VPS_PORT` | Port SSH (optionnel, 22 par défaut) | `22` |

## En production

Déployé sur le VPS : **https://char2026.walautao.fr** (nginx + PHP-FPM 8.3,
certificat Let's Encrypt, HTTP redirigé en 301 vers HTTPS).

Particularités de ce serveur, à connaître avant d'y toucher :

- **nginx tourne sous l'utilisateur `warren`**, pas `www-data`. Le pool PHP-FPM
  dédié (`deploiement/php-fpm-char2026.conf`) tourne donc en `www-data` mais
  expose son socket en `warren:warren 0660`, sinon nginx reçoit un
  « Permission denied » sur le socket.
- Le vhost `char2026.walautao.fr` était initialement déclaré dans
  `sites-available/default` par certbot, pointé sur wazzzfood. Les blocs ont été
  déplacés dans un vhost dédié, en réutilisant le certificat existant.
- La clé d'export **n'est pas dans le code** : elle arrive par
  `fastcgi_param CHAR2026_EXPORT_KEY` dans le bloc nginx. Sans elle,
  `export.php` renvoie 503 et ne sert rien.

## Serveur : nginx, pas Apache

Le VPS tourne sous **nginx**, qui **ignore les fichiers `.htaccess`**.
Deux conséquences, toutes deux traitées :

1. **Les données collectées ne sont pas dans la racine web.**
   `api/config.php` place `DATA_DIR` dans `<parent de la racine>/char2026-data`,
   donc hors de portée du serveur web. Sans cela, la liste des e-mails serait
   téléchargeable publiquement — problème RGPD.
   Ordre de résolution : `CHAR2026_DATA_DIR` (variable d'environnement) →
   `../char2026-data` → repli `./data`.

2. **Un bloc `server` nginx est fourni** : `deploiement/nginx-char2026.conf`.
   Il gère PHP-FPM, le refus de `/data` (filet de sécurité), les fichiers cachés,
   la compression, le cache et les en-têtes de sécurité.
   À installer une fois sur le VPS, puis `certbot --nginx -d char2026.walautao.fr`
   pour le certificat HTTPS.

## Ce qui n'est jamais écrasé

`char2026-data/supporters.jsonl` contient les soutiens collectés. Ce dossier est
**hors du dépôt** (`.gitignore`), **hors de la racine web**, et **protégé pendant
le rsync** : une mise à jour du site ne perd jamais les adresses déjà recueillies.

## À personnaliser

- clé d'export CSV → `fastcgi_param CHAR2026_EXPORT_KEY` dans le bloc nginx, **jamais dans le dépôt**
- `api/config.php` → `NOTIFY_EMAIL` (alerte à chaque soutien)
- `assets/js/main.js` → `var GOAL = 500;` (objectif affiché sur la patinoire)

## Récupérer les adresses e-mail

```
https://char2026.walautao.fr/api/export.php?key=VOTRE_CLE
```

Voir `LISEZMOI.txt` pour le détail complet (images, animations, RGPD).
