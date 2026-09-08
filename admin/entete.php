<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
auth_exiger();
$page = $page ?? '';
$titre = $titre ?? 'Administration';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?> — CHAR 2026</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=1">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230A0A0A'/><text x='50' y='72' font-size='58' font-family='Arial Black,Arial' font-weight='900' text-anchor='middle' fill='%23FFC220'>C</text></svg>">
</head>
<body>
<header class="topbar">
  <div class="topbar__in">
    <span class="marque"><i></i>CHAR 2026</span>
    <span class="topbar__sp"></span>
    <a href="../" target="_blank" rel="noopener" style="font-size:13.5px">Voir le site ↗</a>
    <a href="deconnexion.php" style="font-size:13.5px;color:var(--gris)">Déconnexion</a>
  </div>
  <nav class="onglets">
    <a href="tableau-de-bord.php" class="<?= $page === 'stats' ? 'actif' : '' ?>">Audience</a>
    <a href="soutiens.php" class="<?= $page === 'soutiens' ? 'actif' : '' ?>">Soutiens</a>
    <a href="contenu.php" class="<?= $page === 'contenu' ? 'actif' : '' ?>">Textes du site</a>
    <a href="programme.php" class="<?= $page === 'programme' ? 'actif' : '' ?>">Programme &amp; QR</a>
  </nav>
</header>
<main class="page"><div class="wrap">
