<?php
declare(strict_types=1);
$page = 'stats'; $titre = 'Audience';
require __DIR__ . '/entete.php';
require_once __DIR__ . '/../inc/stats.php';

$jours = (int)($_GET['jours'] ?? 30);
if (!in_array($jours, [7, 30, 90], true)) $jours = 30;
$s = stats_lire($jours);

$fichierSoutiens = DATA_DIR . '/supporters.jsonl';
$totalSoutiens = 0;
if (is_readable($fichierSoutiens)) {
    $fh = fopen($fichierSoutiens, 'rb');
    while (($l = fgets($fh)) !== false) { if (trim($l) !== '') $totalSoutiens++; }
    fclose($fh);
}

$maxSerie = max(1, max(array_column($s['series'], 'vues')));
$conversion = $s['total']['visiteurs'] > 0
    ? round($s['total']['soutiens'] / $s['total']['visiteurs'] * 100, 1) : 0.0;

function pct(int $v, int $total): float { return $total > 0 ? round($v / $total * 100, 1) : 0.0; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$libellesClics = [
  'cta-hero' => "Bouton principal de l'accueil", 'cta-nav' => 'Bouton du menu haut',
  'cta-menu' => 'Bouton du menu mobile', 'cta-projet' => 'Voir les 10 priorités',
  'cta-finale' => 'Bouton du bas de page', 'pouce' => 'Pouce du tableau d\'affichage',
  'envoi-formulaire' => 'Envoi du formulaire', 'clic-email' => 'Adresse e-mail du pied de page',
  'clic-programme' => 'Lien vers le programme',
];
?>
<h1 class="titre">Audience</h1>
<p class="sous">
  Sur les <?= $jours ?> derniers jours ·
  <a href="?jours=7">7 j</a> · <a href="?jours=30">30 j</a> · <a href="?jours=90">90 j</a>
</p>

<div class="grille grille--4" style="margin-bottom:14px">
  <div class="kpi"><b><?= number_format($s['total']['vues'], 0, ',', ' ') ?></b><span>Pages vues</span></div>
  <div class="kpi"><b><?= number_format($s['total']['visiteurs'], 0, ',', ' ') ?></b><span>Visiteurs</span></div>
  <div class="kpi"><b><?= number_format($totalSoutiens, 0, ',', ' ') ?></b><span>Soutiens au total</span></div>
  <div class="kpi"><b><?= str_replace('.', ',', (string)$conversion) ?> %</b><span>Taux de soutien</span></div>
</div>

<div class="carte">
  <h2>Fréquentation quotidienne</h2>
  <div class="histo">
    <?php foreach ($s['series'] as $j): ?>
      <div style="height:<?= max(2, (int)round($j['vues'] / $maxSerie * 100)) ?>%"
           data-t="<?= h(date('d/m', strtotime($j['date']))) ?> — <?= $j['vues'] ?> vues, <?= $j['visiteurs'] ?> visiteurs, <?= $j['soutiens'] ?> soutiens"></div>
    <?php endforeach; ?>
  </div>
  <div class="axe">
    <span><?= h(date('d/m/Y', strtotime($s['series'][0]['date']))) ?></span>
    <span><?= h(date('d/m/Y', strtotime(end($s['series'])['date']))) ?></span>
  </div>
</div>

<div class="grille grille--2">
  <div class="carte">
    <h2>Clics sur les boutons</h2>
    <?php if (!$s['clics']): ?>
      <p class="sous" style="margin:0">Aucun clic enregistré pour l'instant.</p>
    <?php else: $maxC = max($s['clics']); foreach ($s['clics'] as $id => $n): ?>
      <div class="barres" style="margin-bottom:9px">
        <div class="barre">
          <span class="barre__n"><?= h($libellesClics[$id] ?? $id) ?></span>
          <span class="barre__v"><?= $n ?></span>
          <span class="barre__p"><i style="width:<?= (int)round($n / $maxC * 100) ?>%"></i></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="carte">
    <h2>Provenance</h2>
    <?php if (!$s['referents']): ?>
      <p class="sous" style="margin:0">Aucune donnée pour l'instant.</p>
    <?php else: $maxR = max($s['referents']); $i = 0; foreach ($s['referents'] as $r => $n): if ($i++ >= 8) break; ?>
      <div class="barres" style="margin-bottom:9px">
        <div class="barre">
          <span class="barre__n"><?= h($r === 'direct' ? 'Accès direct' : $r) ?></span>
          <span class="barre__v"><?= $n ?></span>
          <span class="barre__p"><i style="width:<?= (int)round($n / $maxR * 100) ?>%"></i></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="carte">
    <h2>Appareils</h2>
    <?php $totApp = array_sum($s['appareils']); ?>
    <?php if (!$totApp): ?>
      <p class="sous" style="margin:0">Aucune donnée pour l'instant.</p>
    <?php else: foreach ($s['appareils'] as $a => $n): ?>
      <div class="barres" style="margin-bottom:9px">
        <div class="barre">
          <span class="barre__n"><?= h(ucfirst($a)) ?></span>
          <span class="barre__v"><?= str_replace('.', ',', (string)pct($n, $totApp)) ?> % (<?= $n ?>)</span>
          <span class="barre__p"><i style="width:<?= pct($n, $totApp) ?>%"></i></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="carte">
    <h2>Jusqu'où descendent-ils ?</h2>
    <?php $base = max(1, $s['profondeur']['25'] ?? 1); ?>
    <?php foreach ($s['profondeur'] as $seuil => $n): ?>
      <div class="barres" style="margin-bottom:9px">
        <div class="barre">
          <span class="barre__n"><?= $seuil ?> % de la page</span>
          <span class="barre__v"><?= $n ?></span>
          <span class="barre__p"><i style="width:<?= (int)round(min(100, $n / $base * 100)) ?>%"></i></span>
        </div>
      </div>
    <?php endforeach; ?>
    <p class="aide">Proportion de visiteurs ayant atteint chaque niveau de la page.</p>
  </div>
</div>

<p class="aide" style="margin-top:16px">
  Mesure interne, sans cookie ni service tiers. Les visiteurs sont comptés via une empreinte
  anonyme renouvelée chaque jour : aucune adresse IP n'est conservée.
</p>
<?php require __DIR__ . '/pied.php'; ?>
