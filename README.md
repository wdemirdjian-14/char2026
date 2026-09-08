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

## Ce qui n'est jamais écrasé

`data/` contient les soutiens collectés (`supporters.jsonl`). Ce dossier est
**exclu du dépôt** (`.gitignore`) et **protégé pendant le rsync** : une mise à jour
du site ne perd jamais les adresses déjà recueillies.

## À personnaliser

- `api/config.php` → `EXPORT_KEY` (clé d'export CSV), `NOTIFY_EMAIL` (alerte à chaque soutien)
- `assets/js/main.js` → `var GOAL = 500;` (objectif affiché sur la patinoire)

## Récupérer les adresses e-mail

```
https://char2026.walautao.fr/api/export.php?key=VOTRE_CLE
```

Voir `LISEZMOI.txt` pour le détail complet (images, animations, RGPD).
