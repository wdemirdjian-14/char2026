<?php
/* =========================================================
   CHAR 2026 — réception des évènements d'audience
   Aucune donnée personnelle : ni cookie, ni IP conservée.
   ========================================================= */
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/../inc/stats.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo '{"ok":false}';
    exit;
}

$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > 2048) { http_response_code(413); echo '{"ok":false}'; exit; }
$ev = json_decode($raw, true);
if (!is_array($ev)) { http_response_code(400); echo '{"ok":false}'; exit; }

// Garde-fou : au plus 300 évènements par heure et par visiteur.
$garde = DATA_DIR . '/track-limite.json';
$now = time();
$emp = stats_empreinte_visiteur();
$table = is_readable($garde) ? (json_decode((string)@file_get_contents($garde), true) ?: []) : [];
foreach ($table as $k => $v) {
    if (!is_array($v) || ($v['t'] ?? 0) < $now - 3600) unset($table[$k]);
}
$mien = $table[$emp] ?? ['t' => $now, 'n' => 0];
if ($mien['t'] < $now - 3600) $mien = ['t' => $now, 'n' => 0];
if ($mien['n'] >= 300) { http_response_code(429); echo '{"ok":false}'; exit; }
$mien['n']++;
$table[$emp] = $mien;
@file_put_contents($garde, json_encode($table), LOCK_EX);

stats_enregistrer($ev);
echo '{"ok":true}';
