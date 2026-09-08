<?php
/* =========================================================
   Modèle du fichier de secrets.

   À placer HORS de la racine web, par défaut :
       /var/www/char2026-secrets/config.php
   Droits conseillés : dossier 750, fichier 640, propriétaire
   l'utilisateur de PHP-FPM (www-data).

   Ne jamais committer ce fichier.

   Pourquoi un fichier PHP et non des fastcgi_param nginx :
   une empreinte bcrypt commence par $2y$… et nginx interprète
   le $ comme une variable, ce qui casse sa configuration.

   Générer l'empreinte du mot de passe sur le serveur :
       php -r 'echo password_hash("VOTRE_MOT_DE_PASSE", PASSWORD_BCRYPT);'
   ========================================================= */
return [
    'export_key' => 'chaine-aleatoire-longue',   // export CSV par URL
    'admin_user' => 'PierreD',                   // identifiant de l'espace admin
    'admin_hash' => '$2y$10$...',                // password_hash() du mot de passe
    'sel_stats'  => 'chaine-aleatoire-32-car',   // sel des empreintes de visiteurs
];
