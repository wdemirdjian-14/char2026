<?php
/* =========================================================
   CHAR 2026 — export CSV des soutiens
   Usage : https://char2026.walautao.fr/api/export.php?key=VOTRE_CLE
   ========================================================= */
declare(strict_types=1);
require __DIR__ . '/config.php';

// Refus total tant qu'aucune clé n'a été configurée sur le serveur.
if (EXPORT_KEY === EXPORT_KEY_DEFAUT) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Export désactivé : la variable CHAR2026_EXPORT_KEY n'est pas définie sur le serveur.";
    exit;
}

$key = (string)($_GET['key'] ?? '');
if (!hash_equals(EXPORT_KEY, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Accès refusé.";
    exit;
}

$file = DATA_DIR . '/supporters.jsonl';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="soutiens-char-2026-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM Excel
fputcsv($out, ['Date', 'Prenom', 'Email', 'Profil', 'Message', 'Affichage public'], ';');

if (is_readable($file)) {
    $fh = fopen($file, 'rb');
    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '') continue;
        $r = json_decode($line, true);
        if (!is_array($r)) continue;
        fputcsv($out, [
            $r['d'] ?? '', $r['n'] ?? '', $r['e'] ?? '',
            $r['c'] ?? '', $r['m'] ?? '', !empty($r['p']) ? 'oui' : 'non',
        ], ';');
    }
    fclose($fh);
}
fclose($out);
