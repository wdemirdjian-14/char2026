<?php
/* =========================================================
   CHAR 2026 — authentification de l'espace d'administration
   ========================================================= */
declare(strict_types=1);
require_once __DIR__ . '/../api/config.php';

const AUTH_TENTATIVES_MAX   = 5;      // essais autorisés
const AUTH_FENETRE          = 900;    // sur 15 minutes
const AUTH_INACTIVITE       = 7200;   // déconnexion après 2 h sans activité

function auth_demarrer(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('char2026_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function auth_identifiant_attendu(): string
{
    return secret('admin_user');
}

function auth_empreinte_attendue(): string
{
    return secret('admin_hash');
}

function auth_configure(): bool
{
    return auth_identifiant_attendu() !== '' && auth_empreinte_attendue() !== '';
}

/* ---------- Limitation des tentatives ---------- */
function auth_fichier_tentatives(): string { return DATA_DIR . '/admin-tentatives.json'; }

function auth_tentatives_restantes(): int
{
    $f = auth_fichier_tentatives();
    $t = is_readable($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $cle = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $now = time();
    $mien = array_values(array_filter($t[$cle] ?? [], static fn($ts) => $ts > $now - AUTH_FENETRE));
    return max(0, AUTH_TENTATIVES_MAX - count($mien));
}

function auth_noter_echec(): void
{
    $f = auth_fichier_tentatives();
    $t = is_readable($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $cle = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $now = time();
    foreach ($t as $k => $v) {
        $t[$k] = array_values(array_filter((array)$v, static fn($ts) => $ts > $now - AUTH_FENETRE));
        if (!$t[$k]) unset($t[$k]);
    }
    $t[$cle][] = $now;
    @file_put_contents($f, json_encode($t), LOCK_EX);
}

function auth_effacer_echecs(): void
{
    $f = auth_fichier_tentatives();
    $t = is_readable($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    unset($t[hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''))]);
    @file_put_contents($f, json_encode($t), LOCK_EX);
}

/* ---------- Connexion ---------- */
function auth_tenter(string $identifiant, string $motdepasse): bool
{
    if (!auth_configure()) return false;
    if (auth_tentatives_restantes() <= 0) return false;

    $okUser = hash_equals(auth_identifiant_attendu(), $identifiant);
    $okPass = password_verify($motdepasse, auth_empreinte_attendue());

    if ($okUser && $okPass) {
        session_regenerate_id(true);
        $_SESSION['connecte']  = true;
        $_SESSION['identifiant'] = $identifiant;
        $_SESSION['vu_a']      = time();
        $_SESSION['csrf']      = bin2hex(random_bytes(32));
        auth_effacer_echecs();
        return true;
    }
    auth_noter_echec();
    return false;
}

function auth_connecte(): bool
{
    if (empty($_SESSION['connecte'])) return false;
    if (time() - (int)($_SESSION['vu_a'] ?? 0) > AUTH_INACTIVITE) { auth_deconnecter(); return false; }
    $_SESSION['vu_a'] = time();
    return true;
}

function auth_exiger(): void
{
    auth_demarrer();
    if (!auth_connecte()) { header('Location: index.php'); exit; }
}

function auth_deconnecter(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- CSRF ---------- */
function csrf_jeton(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_verifier(): bool
{
    $envoye = (string)($_POST['csrf'] ?? '');
    return $envoye !== '' && hash_equals((string)($_SESSION['csrf'] ?? ''), $envoye);
}

function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_jeton(), ENT_QUOTES, 'UTF-8') . '">';
}
