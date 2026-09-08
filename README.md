# CHAR 2026 — char2026.walautao.fr

Site de campagne pour les élections du **Club de Hockey Amateur de Rouen** (juin 2026).
Liste « Ensemble, dans le respect et l'exigence », portée par Pierre Dehaen.

Site statique + une petite API PHP pour le compteur de soutiens et la collecte d'e-mails.
Conçu **mobile first**.

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
