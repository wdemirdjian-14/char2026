<?php
/* =========================================================
   CHAR 2026 — API soutiens
   GET  ?action=stats  -> { count, recent[] }
   POST (JSON)         -> { ok, count, already, recent[] }
   ========================================================= */
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$file      = DATA_DIR . '/supporters.jsonl';
$rateFile  = DATA_DIR . '/ratelimit.json';

function fail(string $m, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $m], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_storage(string $file): void {
    if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0775, true) && !is_dir(DATA_DIR)) {
        fail("Stockage indisponible (dossier /data).", 500);
    }
    $ht = DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
    }
    if (!file_exists($file)) { @touch($file); }
}

/** Retourne [count, recent[]] */
function read_all(string $file): array {
    if (!is_readable($file)) return [0, []];
    $count = 0; $recent = [];
    $fh = @fopen($file, 'rb');
    if (!$fh) return [0, []];
    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '') continue;
        $row = json_decode($line, true);
        if (!is_array($row) || empty($row['h'])) continue;
        $count++;
        if (!empty($row['p']) && !empty($row['n'])) {
            $recent[] = ['name' => $row['n'], 'category' => $row['c'] ?? ''];
        }
    }
    fclose($fh);
    $recent = array_slice(array_reverse($recent), 0, 40);
    return [$count, $recent];
}

function email_exists(string $file, string $hash): bool {
    if (!is_readable($file)) return false;
    $fh = @fopen($file, 'rb');
    if (!$fh) return false;
    $found = false;
    while (($line = fgets($fh)) !== false) {
        if (strpos($line, '"' . $hash . '"') !== false) { $found = true; break; }
    }
    fclose($fh);
    return $found;
}

function client_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function rate_ok(string $rateFile): bool {
    $now = time();
    $key = hash('sha256', client_ip());
    $data = [];
    if (is_readable($rateFile)) {
        $data = json_decode((string)@file_get_contents($rateFile), true) ?: [];
    }
    foreach ($data as $k => $stamps) {
        $data[$k] = array_values(array_filter($stamps, static fn($t) => $t > $now - 3600));
        if (!$data[$k]) unset($data[$k]);
    }
    $mine = $data[$key] ?? [];
    if (count($mine) >= RATE_LIMIT) return false;
    $mine[] = $now;
    $data[$key] = $mine;
    @file_put_contents($rateFile, json_encode($data), LOCK_EX);
    return true;
}

function clean(string $s, int $max): string {
    $s = strip_tags($s);
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
    return mb_substr($s, 0, $max);
}

ensure_storage($file);

/* ---------------- GET : statistiques ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    [$count, $recent] = read_all($file);
    echo json_encode([
        'ok'     => true,
        'count'  => $count + COUNT_OFFSET,
        'recent' => $recent,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Méthode non autorisée.', 405);
}

/* ---------------- POST : nouveau soutien ---------------- */
$raw  = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) { $body = $_POST; }

// Pot de miel
if (!empty($body['website'])) {
    echo json_encode(['ok' => true, 'count' => read_all($file)[0] + COUNT_OFFSET, 'already' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$email = mb_strtolower(trim((string)($body['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    fail('Adresse e-mail invalide.');
}
$consent = !empty($body['consent']);
if (!$consent) fail('Le consentement est requis.');

if (!rate_ok($rateFile)) {
    fail('Trop de demandes depuis cette connexion. Réessayez plus tard.', 429);
}

$hash = hash('sha256', $email);
if (email_exists($file, $hash)) {
    [$count, $recent] = read_all($file);
    echo json_encode([
        'ok' => true, 'already' => true,
        'count' => $count + COUNT_OFFSET, 'recent' => $recent,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = [
    'h' => $hash,
    'e' => $email,
    'n' => clean((string)($body['name'] ?? ''), 60),
    'c' => clean((string)($body['category'] ?? ''), 40),
    'm' => clean((string)($body['message'] ?? ''), 400),
    'p' => !empty($body['public']),
    'd' => gmdate('c'),
];

$fh = @fopen($file, 'ab');
if (!$fh) fail("Impossible d'enregistrer votre soutien (droits sur /data).", 500);
flock($fh, LOCK_EX);
fwrite($fh, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
fflush($fh);
flock($fh, LOCK_UN);
fclose($fh);

if (NOTIFY_EMAIL !== '') {
    @mail(
        NOTIFY_EMAIL,
        'Nouveau soutien CHAR 2026',
        "Nouveau soutien :\n\nPrenom : {$row['n']}\nEmail : {$row['e']}\nProfil : {$row['c']}\nMessage : {$row['m']}\n",
        "Content-Type: text/plain; charset=utf-8\r\nFrom: no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    );
}

// Comptabilise la conversion dans les statistiques du jour.
// Fait côté serveur uniquement : plus fiable que le navigateur, et
// évite le double comptage avec la mesure d'audience du JavaScript.
$statsPhp = __DIR__ . '/../inc/stats.php';
if (is_readable($statsPhp)) {
    require_once $statsPhp;
    stats_enregistrer(['t' => 'soutien']);
}

[$count, $recent] = read_all($file);
echo json_encode([
    'ok' => true, 'already' => false,
    'count' => $count + COUNT_OFFSET, 'recent' => $recent,
], JSON_UNESCAPED_UNICODE);
