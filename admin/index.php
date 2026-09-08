<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
auth_demarrer();

if (auth_connecte()) { header('Location: tableau-de-bord.php'); exit; }

$erreur = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!auth_configure()) {
        $erreur = "L'accès n'est pas configuré sur le serveur (CHAR2026_ADMIN_USER / CHAR2026_ADMIN_HASH).";
    } elseif (auth_tentatives_restantes() <= 0) {
        $erreur = "Trop de tentatives. Réessayez dans un quart d'heure.";
    } elseif (auth_tenter((string)($_POST['identifiant'] ?? ''), (string)($_POST['motdepasse'] ?? ''))) {
        header('Location: tableau-de-bord.php');
        exit;
    } else {
        $restantes = auth_tentatives_restantes();
        $erreur = "Identifiant ou mot de passe incorrect."
                . ($restantes > 0 ? " Il vous reste $restantes tentative" . ($restantes > 1 ? 's' : '') . "." : '');
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Connexion — CHAR 2026</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=1">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230A0A0A'/><text x='50' y='72' font-size='58' font-family='Arial Black,Arial' font-weight='900' text-anchor='middle' fill='%23FFC220'>C</text></svg>">
</head>
<body>
<div class="connexion">
  <div class="connexion__boite">
    <span class="marque" style="margin-bottom:16px"><i></i>CHAR 2026</span>
    <h1 class="connexion__titre">Administration</h1>
    <p class="connexion__sous">Suivi de l'audience, soutiens et textes du site.</p>

    <?php if ($erreur !== ''): ?>
      <div class="msg msg--err"><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <label class="champ">
        <span>Identifiant</span>
        <input type="text" name="identifiant" autocomplete="username" required autofocus>
      </label>
      <label class="champ">
        <span>Mot de passe</span>
        <input type="password" name="motdepasse" autocomplete="current-password" required>
      </label>
      <button class="btn btn--plein" type="submit">Se connecter</button>
    </form>
  </div>
</div>
</body>
</html>
