<?php
/* Export CSV réservé à l'administrateur connecté (pas de clé dans l'URL). */
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
auth_exiger();

$fichier = DATA_DIR . '/supporters.jsonl';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="soutiens-char-2026-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Date', 'Prenom', 'Email', 'Profil', 'Message', 'Affichage public'], ';');
if (is_readable($fichier)) {
    $fh = fopen($fichier, 'rb');
    while (($l = fgets($fh)) !== false) {
        $l = trim($l);
        if ($l === '') continue;
        $r = json_decode($l, true);
        if (!is_array($r)) continue;
        fputcsv($out, [$r['d'] ?? '', $r['n'] ?? '', $r['e'] ?? '', $r['c'] ?? '', $r['m'] ?? '', !empty($r['p']) ? 'oui' : 'non'], ';');
    }
    fclose($fh);
}
fclose($out);
