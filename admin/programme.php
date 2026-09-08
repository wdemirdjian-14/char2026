<?php
declare(strict_types=1);
$page = 'programme'; $titre = 'Programme & QR code';
require __DIR__ . '/entete.php';
require_once __DIR__ . '/../inc/contenu.php';

const PROGRAMME_MAX_OCTETS = 20 * 1024 * 1024;   // 20 Mo
$racine    = dirname(__DIR__);
$relatif   = c('programme.fichier', 'assets/programme/programme-char-2026.pdf');
$chemin    = $racine . '/' . ltrim($relatif, '/');
$message   = ''; $classe = '';

/* ---------------- Envoi du PDF ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'envoyer') {
    if (!csrf_verifier()) {
        $message = "Jeton de sécurité invalide, rien n'a été envoyé."; $classe = 'err';
    } else {
        $f = $_FILES['programme'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $message = "Aucun fichier sélectionné."; $classe = 'err';
        } elseif (($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
            $message = "L'envoi a échoué (code " . (int)$f['error'] . "). Le fichier dépasse peut-être la taille autorisée par le serveur."; $classe = 'err';
        } elseif (($f['size'] ?? 0) > PROGRAMME_MAX_OCTETS) {
            $message = "Fichier trop volumineux (" . round($f['size'] / 1048576, 1) . " Mo). Maximum : 20 Mo."; $classe = 'err';
        } else {
            // Vérification du type réel, pas seulement de l'extension
            $entete = (string)@file_get_contents($f['tmp_name'], false, null, 0, 5);
            $mime = function_exists('finfo_open')
                ? (finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) ?: '')
                : 'application/pdf';
            if ($entete !== '%PDF-' || $mime !== 'application/pdf') {
                $message = "Ce fichier n'est pas un PDF valide."; $classe = 'err';
            } else {
                @mkdir(dirname($chemin), 0775, true);
                // La sauvegarde va hors racine web : un ancien programme ne
                // doit pas rester téléchargeable après son remplacement.
                if (is_file($chemin)) {
                    @copy($chemin, DATA_DIR . '/programme-' . date('Ymd-His') . '.pdf');
                }
                if (move_uploaded_file($f['tmp_name'], $chemin)) {
                    @chmod($chemin, 0644);
                    $message = "Programme mis en ligne. Le bouton apparaît immédiatement sur le site.";
                    $classe = 'ok';
                } else {
                    $message = "Impossible d'écrire le fichier : vérifiez les droits sur assets/programme/."; $classe = 'err';
                }
            }
        }
    }
}

/* ---------------- Suppression ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    if (!csrf_verifier()) {
        $message = "Jeton de sécurité invalide."; $classe = 'err';
    } elseif (is_file($chemin) && @unlink($chemin)) {
        $message = "Programme retiré du site."; $classe = 'ok';
    } else {
        $message = "Aucun fichier à retirer."; $classe = 'err';
    }
}

$existe  = is_file($chemin);
$taille  = $existe ? filesize($chemin) : 0;
$modifie = $existe ? filemtime($chemin) : 0;
// Adresse encodée dans le QR : le domaine officiel du site, et non
// celle par laquelle on consulte l'admin — sans quoi un QR imprimé
// pourrait pointer vers l'ancienne adresse.
$urlSite = c('site.domaine');
if ($urlSite === '') {
    $schema  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $urlSite = $schema . '://' . ($_SERVER['HTTP_HOST'] ?? 'char2026.fr') . '/';
}
function hp(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<h1 class="titre">Programme &amp; QR code</h1>
<p class="sous">Le PDF du programme et l'affichette à imprimer pour la patinoire.</p>

<?php if ($message !== ''): ?><div class="msg msg--<?= $classe ?>"><?= hp($message) ?></div><?php endif; ?>

<div class="carte">
  <h2>Le programme en PDF</h2>
  <?php if ($existe): ?>
    <div class="msg msg--ok" style="margin-bottom:14px">
      En ligne — <?= number_format($taille / 1048576, 1, ',', ' ') ?> Mo,
      mis à jour le <?= hp(date('d/m/Y à H:i', $modifie)) ?>.
    </div>
    <div class="actions" style="margin-bottom:16px">
      <a class="btn btn--fin" href="../<?= hp($relatif) ?>?v=<?= $modifie ?>" target="_blank" rel="noopener">Voir le PDF actuel</a>
      <form method="post" onsubmit="return confirm('Retirer le programme du site ? Le bouton disparaîtra de la barre du haut.')">
        <?= csrf_champ() ?><input type="hidden" name="action" value="supprimer">
        <button class="btn btn--danger" type="submit">Retirer du site</button>
      </form>
    </div>
  <?php else: ?>
    <div class="msg msg--info" style="margin-bottom:14px">
      Aucun programme en ligne. Le bouton « <?= hp(c('programme.bouton')) ?> » reste masqué
      dans la barre du haut tant qu'aucun PDF n'est envoyé.
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_champ() ?>
    <input type="hidden" name="action" value="envoyer">
    <label class="champ">
      <span><?= $existe ? 'Remplacer par un nouveau PDF' : 'Choisir le PDF du programme' ?></span>
      <input type="file" name="programme" accept="application/pdf,.pdf" required
             style="padding:11px;background:var(--noir-3)">
      <p class="aide">PDF uniquement, 20 Mo maximum. L'ancien fichier est conservé en sauvegarde.</p>
    </label>
    <button class="btn" type="submit">Mettre en ligne</button>
  </form>
</div>

<div class="carte">
  <h2>QR code du site</h2>
  <p class="sous" style="margin-bottom:16px">
    À imprimer et afficher à la patinoire, sur les maillots, dans les vestiaires.
    Il pointe vers <b style="color:var(--jaune)"><?= hp($urlSite) ?></b>
  </p>

  <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start">
    <div style="background:#fff;padding:16px;border-radius:14px;line-height:0" id="zoneQR">
      <div id="qr"></div>
    </div>
    <div style="flex:1;min-width:220px">
      <label class="champ">
        <span>Adresse encodée</span>
        <input type="text" id="urlQR" value="<?= hp($urlSite) ?>">
        <p class="aide">Modifiez-la pour créer un QR vers une autre page (le formulaire de soutien, par exemple).</p>
      </label>
      <div class="actions">
        <button class="btn" type="button" id="btnPNG">⬇ Télécharger en PNG</button>
        <button class="btn btn--fin" type="button" id="btnAffiche">🖨 Affichette à imprimer</button>
      </div>
      <p class="aide" id="qrEtat" style="margin-top:10px"></p>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function(){
  var zone = document.getElementById('qr');
  var champ = document.getElementById('urlQR');
  var etat = document.getElementById('qrEtat');

  if (typeof qrcode !== 'function') {
    etat.textContent = "Le générateur n'a pas pu être chargé (connexion ou blocage réseau). Réessayez plus tard.";
    etat.style.color = 'var(--rouge)';
    document.getElementById('btnPNG').disabled = true;
    document.getElementById('btnAffiche').disabled = true;
    return;
  }

  function dessiner(){
    var url = champ.value.trim() || <?= json_encode($urlSite) ?>;
    var q = qrcode(0, 'M');          // version auto, correction moyenne
    q.addData(url);
    q.make();
    zone.innerHTML = q.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
    var svg = zone.querySelector('svg');
    if (svg) { svg.setAttribute('width', '190'); svg.setAttribute('height', '190'); }
    etat.textContent = 'Modules : ' + q.getModuleCount() + ' × ' + q.getModuleCount();
    etat.style.color = '';
  }

  function versPNG(taille, callback){
    var url = champ.value.trim() || <?= json_encode($urlSite) ?>;
    var q = qrcode(0, 'M'); q.addData(url); q.make();
    var n = q.getModuleCount(), marge = 4, total = n + marge * 2;
    var px = Math.max(1, Math.floor(taille / total));
    var c = document.createElement('canvas');
    c.width = c.height = total * px;
    var x = c.getContext('2d');
    x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height);
    x.fillStyle = '#000';
    for (var r = 0; r < n; r++) for (var col = 0; col < n; col++)
      if (q.isDark(r, col)) x.fillRect((col + marge) * px, (r + marge) * px, px, px);
    callback(c.toDataURL('image/png'));
  }

  champ.addEventListener('input', dessiner);
  dessiner();

  document.getElementById('btnPNG').addEventListener('click', function(){
    versPNG(1200, function(data){
      var a = document.createElement('a');
      a.href = data; a.download = 'qrcode-char2026.png';
      document.body.appendChild(a); a.click(); a.remove();
    });
  });

  document.getElementById('btnAffiche').addEventListener('click', function(){
    versPNG(1000, function(data){
      var url = champ.value.trim();
      var f = window.open('', '_blank');
      if (!f) { alert("Autorisez les fenêtres surgissantes pour ouvrir l'affichette."); return; }
      f.document.write(
        '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Affichette CHAR 2026</title>'
      + '<style>@page{size:A4;margin:14mm}'
      + 'body{margin:0;font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#0A0A0A;color:#fff;'
      + 'display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:96vh;text-align:center;padding:20px}'
      + 'h1{font-size:44px;margin:0 0 6px;text-transform:uppercase;letter-spacing:-1px;line-height:1}'
      + 'h1 em{font-style:normal;color:#FFC220}'
      + 'p.s{color:#FFC220;font-size:19px;font-weight:700;margin:0 0 26px;text-transform:uppercase;letter-spacing:2px}'
      + 'img{width:290px;height:290px;background:#fff;padding:14px;border-radius:16px}'
      + 'p.u{margin:20px 0 0;font-size:17px;color:#bbb;word-break:break-all}'
      + 'p.d{margin:26px 0 0;font-size:15px;color:#777}'
      + '@media print{body{background:#fff;color:#000}h1 em{color:#B98600}p.s{color:#B98600}p.u{color:#444}}'
      + '</style></head><body>'
      + '<h1>Ensemble,<br><em>dans le respect<br>et l\'exigence</em></h1>'
      + '<p class="s">Club de Hockey Amateur de Rouen</p>'
      + '<img src="' + data + '" alt="QR code">'
      + '<p class="u">' + url.replace(/</g, '&lt;') + '</p>'
      + '<p class="d">Scannez pour découvrir le projet et le soutenir</p>'
      + '</body></html>');
      f.document.close();
      setTimeout(function(){ f.print(); }, 400);
    });
  });
})();
</script>
<?php require __DIR__ . '/pied.php'; ?>
