<?php
declare(strict_types=1);
$page = 'soutiens'; $titre = 'Soutiens';
require __DIR__ . '/entete.php';

$fichier = DATA_DIR . '/supporters.jsonl';
$message = ''; $classe = '';

/* ---------- Suppression (droit à l'effacement, RGPD) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    if (!csrf_verifier()) {
        $message = "Jeton de sécurité invalide, rien n'a été supprimé."; $classe = 'err';
    } else {
        $cible = (string)($_POST['empreinte'] ?? '');
        $lignes = is_readable($fichier) ? file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $gardees = []; $supprime = 0;
        foreach ($lignes as $l) {
            $r = json_decode($l, true);
            if (is_array($r) && ($r['h'] ?? '') === $cible) { $supprime++; continue; }
            $gardees[] = $l;
        }
        if ($supprime > 0) {
            $tmp = $fichier . '.tmp';
            file_put_contents($tmp, $gardees ? implode("\n", $gardees) . "\n" : '', LOCK_EX);
            rename($tmp, $fichier);
            $message = "Soutien supprimé définitivement."; $classe = 'ok';
        } else {
            $message = "Soutien introuvable."; $classe = 'err';
        }
    }
}

/* ---------- Lecture ---------- */
$soutiens = [];
if (is_readable($fichier)) {
    foreach (file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $r = json_decode($l, true);
        if (is_array($r) && !empty($r['e'])) $soutiens[] = $r;
    }
}
$soutiens = array_reverse($soutiens);

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $qq = mb_strtolower($q);
    $soutiens = array_values(array_filter($soutiens, static function ($r) use ($qq) {
        return str_contains(mb_strtolower(($r['n'] ?? '') . ' ' . ($r['e'] ?? '') . ' ' . ($r['c'] ?? '') . ' ' . ($r['m'] ?? '')), $qq);
    }));
}

$parProfil = [];
foreach ($soutiens as $r) { $p = $r['c'] ?: '—'; $parProfil[$p] = ($parProfil[$p] ?? 0) + 1; }
arsort($parProfil);
$tousLesMails = implode(', ', array_column($soutiens, 'e'));
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<h1 class="titre">Soutiens</h1>
<p class="sous"><?= count($soutiens) ?> personne<?= count($soutiens) > 1 ? 's' : '' ?><?= $q !== '' ? ' trouvée' . (count($soutiens) > 1 ? 's' : '') . ' pour « ' . h($q) . ' »' : '' ?></p>

<?php if ($message !== ''): ?><div class="msg msg--<?= $classe ?>"><?= h($message) ?></div><?php endif; ?>

<div class="carte">
  <div class="actions" style="margin-bottom:14px">
    <form method="get" style="flex:1;min-width:200px"><input type="search" name="q" value="<?= h($q) ?>" placeholder="Rechercher un nom, un e-mail, un profil…"></form>
    <a class="btn" href="exporter.php">⬇ Exporter en CSV</a>
    <button class="btn btn--fin" type="button" onclick="copierMails()">Copier tous les e-mails</button>
  </div>

  <?php if ($parProfil): ?>
    <p style="margin:0 0 14px">
      <?php foreach ($parProfil as $p => $n): ?><span class="etiq" style="margin-right:6px"><?= h($p) ?> : <b style="color:var(--jaune)"><?= $n ?></b></span><?php endforeach; ?>
    </p>
  <?php endif; ?>

  <?php if (!$soutiens): ?>
    <p class="sous" style="margin:0">Aucun soutien enregistré pour l'instant.</p>
  <?php else: ?>
  <div class="tableau-enveloppe">
    <table>
      <thead><tr><th>Date</th><th>Prénom</th><th>E-mail</th><th>Profil</th><th>Message</th><th>Public</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($soutiens as $r): ?>
        <tr>
          <td style="white-space:nowrap;color:var(--gris)"><?= h(date('d/m/Y H:i', strtotime($r['d'] ?? 'now'))) ?></td>
          <td><?= h($r['n'] ?? '') ?: '<span style="color:var(--gris)">—</span>' ?></td>
          <td><a href="mailto:<?= h($r['e']) ?>"><?= h($r['e']) ?></a></td>
          <td><span class="etiq"><?= h($r['c'] ?? '—') ?></span></td>
          <td style="max-width:280px;color:var(--gris-2)"><?= h($r['m'] ?? '') ?></td>
          <td><?= !empty($r['p']) ? 'oui' : 'non' ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Supprimer définitivement ce soutien ? Cette action est irréversible.')">
              <?= csrf_champ() ?>
              <input type="hidden" name="action" value="supprimer">
              <input type="hidden" name="empreinte" value="<?= h($r['h'] ?? '') ?>">
              <button class="btn btn--danger" type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<textarea id="tousLesMails" style="position:absolute;left:-9999px" readonly><?= h($tousLesMails) ?></textarea>
<script>
function copierMails(){
  var t=document.getElementById('tousLesMails');
  t.select(); t.setSelectionRange(0,999999);
  try{ document.execCommand('copy'); alert('<?= count($soutiens) ?> adresse(s) copiée(s) dans le presse-papiers.'); }
  catch(e){ alert('Copie impossible : sélectionnez le texte manuellement.'); }
}
</script>
<?php require __DIR__ . '/pied.php'; ?>
