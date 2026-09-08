<?php
/* =========================================================
   CHAR 2026 — configuration
   Modifiez uniquement ce fichier.
   ========================================================= */

/* ---------------------------------------------------------
   SECRETS — jamais dans ce dépôt.

   Ils vivent dans un fichier PHP situé HORS de la racine web,
   par défaut <parent de la racine>/char2026-secrets/config.php,
   qui retourne un tableau :

       <?php return [
           'export_key' => '…',
           'admin_user' => '…',
           'admin_hash' => '…',   // password_hash(), contient des $
           'sel_stats'  => '…',
       ];

   Pourquoi un fichier et non des fastcgi_param nginx : une
   empreinte bcrypt commence par $2y$… et nginx interprète le $
   comme une variable, ce qui casse la configuration.
   Le chemin peut être surchargé par CHAR2026_SECRETS.
   --------------------------------------------------------- */
function char2026_secrets(): array
{
    static $s = null;
    if ($s !== null) return $s;
    $chemin = getenv('CHAR2026_SECRETS') ?: dirname(__DIR__, 2) . '/char2026-secrets/config.php';
    $v = is_readable($chemin) ? @include $chemin : null;
    $s = is_array($v) ? $v : [];
    return $s;
}

function secret(string $cle, string $defaut = ''): string
{
    $s = char2026_secrets();
    if (!empty($s[$cle])) return (string)$s[$cle];
    $env = getenv('CHAR2026_' . strtoupper($cle));   // repli par variable d'environnement
    return is_string($env) && $env !== '' ? $env : $defaut;
}

/* Clé d'export CSV. Tant qu'elle n'est pas configurée,
   export.php refuse de servir quoi que ce soit. */
const EXPORT_KEY_DEFAUT = 'cle-non-configuree';
define('EXPORT_KEY', secret('export_key', EXPORT_KEY_DEFAUT));

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
