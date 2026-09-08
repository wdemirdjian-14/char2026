<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
auth_demarrer();
auth_deconnecter();
header('Location: index.php');
