<?php
/* =========================================================
   CHAR 2026 — chargement et enregistrement du contenu
   ========================================================= */
declare(strict_types=1);

const CONTENU_FICHIER = __DIR__ . '/../contenu.json';

/** Contenu du site, en cache mémoire pour la durée de la requête. */
function contenu(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $brut = @file_get_contents(CONTENU_FICHIER);
    $data = $brut ? json_decode($brut, true) : null;
    $cache = is_array($data) ? $data : [];
    return $cache;
}

/** Lecture par chemin pointé : c('hero.titre_ligne1') */
function c(string $chemin, string $defaut = ''): string
{
    $n = contenu();
    foreach (explode('.', $chemin) as $cle) {
        if (!is_array($n) || !array_key_exists($cle, $n)) return $defaut;
        $n = $n[$cle];
    }
    return is_scalar($n) ? (string)$n : $defaut;
}

/** Idem mais pour les listes et objets. */
function cl(string $chemin, array $defaut = []): array
{
    $n = contenu();
    foreach (explode('.', $chemin) as $cle) {
        if (!is_array($n) || !array_key_exists($cle, $n)) return $defaut;
        $n = $n[$cle];
    }
    return is_array($n) ? $n : $defaut;
}

/** Échappement pour les attributs et le texte brut. */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------------------------------------------------
   Nettoyage du HTML autorisé dans les champs de contenu.
   Seule la mise en forme légère est conservée ; tout ce qui
   peut exécuter du code est retiré.
   --------------------------------------------------------- */
function nettoyer_html(string $s): string
{
    $s = preg_replace('#<\s*(script|style|iframe|object|embed|form|link|meta)\b.*?(</\s*\1\s*>|$)#is', '', $s) ?? '';
    $s = strip_tags($s, '<b><strong><em><i><br><sup><sub><span><a>');
    // retire les attributs événementiels et les URL javascript:
    $s = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $s) ?? '';
    $s = preg_replace('#(href|src)\s*=\s*("|\')?\s*javascript:[^"\'>\s]*#i', '', $s) ?? '';
    return trim($s);
}

/** Écriture atomique du contenu (fichier temporaire + rename). */
function enregistrer_contenu(array $data): bool
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) return false;
    $tmp = CONTENU_FICHIER . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return @rename($tmp, CONTENU_FICHIER);
}
