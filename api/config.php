<?php
/* =========================================================
   CHAR 2026 — configuration
   Modifiez uniquement ce fichier.
   ========================================================= */

// Clé secrète pour télécharger la liste des soutiens (api/export.php?key=...)
// >>> CHANGEZ CETTE VALEUR <<<
const EXPORT_KEY = 'char2026-a-changer';

// Ajoute un décalage au compteur public (0 = compteur réel uniquement).
const COUNT_OFFSET = 0;

// Nombre maximum d'enregistrements par IP et par heure (anti-spam).
const RATE_LIMIT = 6;

// Recevoir un e-mail à chaque nouveau soutien ('' = désactivé).
const NOTIFY_EMAIL = '';

// Dossier de stockage des données.
const DATA_DIR = __DIR__ . '/../data';
