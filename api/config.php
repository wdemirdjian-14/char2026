<?php
/* =========================================================
   CHAR 2026 — configuration
   Modifiez uniquement ce fichier.
   ========================================================= */

/* Clé secrète pour télécharger la liste des soutiens
   (api/export.php?key=...).

   Elle N'EST PAS dans le code : ce dépôt est versionné, une clé
   écrite ici serait lisible par tous. Elle est fournie par le
   serveur via la variable d'environnement CHAR2026_EXPORT_KEY
   (voir fastcgi_param dans le bloc nginx).

   Tant qu'elle vaut la valeur par défaut, export.php refuse de
   servir quoi que ce soit. */
const EXPORT_KEY_DEFAUT = 'cle-non-configuree';
define('EXPORT_KEY', getenv('CHAR2026_EXPORT_KEY') ?: EXPORT_KEY_DEFAUT);

// Ajoute un décalage au compteur public (0 = compteur réel uniquement).
const COUNT_OFFSET = 0;

// Nombre maximum d'enregistrements par IP et par heure (anti-spam).
const RATE_LIMIT = 6;

// Recevoir un e-mail à chaque nouveau soutien ('' = désactivé).
const NOTIFY_EMAIL = '';

/* ---------------------------------------------------------
   Emplacement des données collectées.

   IMPORTANT — le serveur tourne sous nginx, qui IGNORE les
   fichiers .htaccess. Un dossier /data placé dans la racine
   web serait donc téléchargeable publiquement.
   On le place par défaut EN DEHORS de la racine web.

   Ordre de résolution :
     1. variable d'environnement CHAR2026_DATA_DIR
     2. <parent de la racine du site>/char2026-data   (recommandé)
     3. repli : <racine du site>/data
        — dans ce cas, ajoutez impérativement la règle nginx
          « location ^~ /data/ { deny all; } »
          (voir deploiement/nginx-char2026.conf)
   --------------------------------------------------------- */
function char2026_dossier_donnees(): string
{
    $explicite = getenv('CHAR2026_DATA_DIR');
    if (is_string($explicite) && $explicite !== '') {
        return rtrim($explicite, '/');
    }

    $hors_racine = dirname(__DIR__, 2) . '/char2026-data';
    if (is_dir($hors_racine) && is_writable($hors_racine)) {
        return $hors_racine;
    }
    if (!is_dir($hors_racine) && @mkdir($hors_racine, 0775, true)) {
        return $hors_racine;
    }

    return dirname(__DIR__) . '/data';
}

define('DATA_DIR', char2026_dossier_donnees());
