<?php
declare(strict_types=1);
require __DIR__ . '/inc/contenu.php';

$icones_valeurs = [
  '<path d="M12 3 4 6v5.5c0 4.6 3.2 8.4 8 9.5 4.8-1.1 8-4.9 8-9.5V6l-8-3Z"/>',
  '<path d="M8 13.5 4 10m12 3.5 4-3.5M6.5 17.5 12 21l5.5-3.5M3 7l4-3 5 2 5-2 4 3"/>',
  '<path d="m3 19 6-9 4 5 2-3 6 7H3Z"/>',
  '<path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 20a6 6 0 0 1 12 0M13 20a6 6 0 0 1 9-5.2"/>',
];
$icones_piliers = [
  '<path d="M21 12a8 8 0 1 1-3.2-6.4M21 4v5h-5"/>',
  '<path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 20a6 6 0 0 1 12 0M13 20a6 6 0 0 1 9-5.2"/>',
  '<path d="M12 3 4 6v5.5c0 4.6 3.2 8.4 8 9.5 4.8-1.1 8-4.9 8-9.5V6l-8-3Z"/>',
  '<path d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Zm10 1h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
];
$POUCE = '<path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3m0 11V11m0 11h9.3a3 3 0 0 0 2.95-2.46l1.4-7.6A2 2 0 0 0 19.7 9.5H15V5.6A2.6 2.6 0 0 0 12.4 3c-.5 0-.95.3-1.14.77L7 11"/>';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(c('meta.titre')) ?></title>
<meta name="description" content="<?= e(c('meta.description')) ?>">
<meta name="theme-color" content="#0A0A0A">
<meta property="og:title" content="<?= e(c('meta.titre')) ?>">
<meta property="og:description" content="<?= e(c('meta.description')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=3">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230A0A0A'/><text x='50' y='72' font-size='58' font-family='Arial Black,Arial' font-weight='900' text-anchor='middle' fill='%23FFC220'>C</text></svg>">
<script>window.CHAR_OBJECTIF = <?= (int)(cl('compteur')['objectif'] ?? 500) ?>;</script>
</head>
<body>

<header class="nav" id="nav">
  <a class="nav__brand" href="#top" aria-label="<?= e(c('marque.sigle')) ?> — accueil">
    <span class="shield shield--sm" aria-hidden="true"><span class="shield__puck"></span><b><?= e(c('marque.sigle')) ?></b></span>
    <span class="nav__brandtxt"><?= c('marque.nom') ?></span>
  </a>
  <nav class="nav__links" aria-label="Navigation principale">
    <a href="#engagement">Engagement</a>
    <a href="#valeurs">Valeurs</a>
    <a href="#projet">Le projet</a>
    <a href="#equipe">L'équipe</a>
  </nav>
  <a href="#soutien" class="btn btn--nav" data-suivi="cta-nav">
    <svg class="ico-thumb" viewBox="0 0 24 24" aria-hidden="true"><?= $POUCE ?></svg>
    Je soutiens
  </a>
  <button class="nav__burger" id="burger" aria-label="Ouvrir le menu" aria-expanded="false"><span></span><span></span></button>
</header>

<div class="mobile-menu" id="mobileMenu" hidden>
  <a href="#engagement">Engagement</a>
  <a href="#valeurs">Valeurs</a>
  <a href="#projet">Le projet</a>
  <a href="#equipe">L'équipe</a>
  <a href="#soutien" class="mobile-menu__cta" data-suivi="cta-menu">👍 Je soutiens</a>
</div>

<main id="top">

<section class="hero" id="hero">
  <div class="hero__sticky">
    <div class="hero__bg" aria-hidden="true">
      <div class="hero__glow hero__glow--1"></div>
      <div class="hero__glow hero__glow--2"></div>
      <div class="hero__ice"></div>
      <div class="hero__grain"></div>
    </div>

    <div class="hero__inner">
      <div class="hero__left">
        <p class="hero__kicker reveal" data-reveal>
          <span class="dot"></span>
          <span class="k-short"><?= c('hero.bandeau_court') ?></span><span class="k-long"><?= c('hero.bandeau_long') ?></span>
          — <b><?= c('hero.bandeau_date') ?></b>
        </p>

        <h1 class="hero__title">
          <span class="line"><span><?= c('hero.titre_ligne1') ?></span></span>
          <span class="line"><span class="y"><?= c('hero.titre_ligne2') ?></span></span>
          <span class="line"><span class="y"><?= c('hero.titre_ligne3') ?></span></span>
        </h1>

        <p class="hero__lead reveal" data-reveal data-delay="120"><?= c('hero.chapo') ?></p>

        <div class="hero__actions reveal" data-reveal data-delay="220">
          <a href="#soutien" class="btn btn--primary btn--big" data-suivi="cta-hero">
            <svg class="ico-thumb" viewBox="0 0 24 24" aria-hidden="true"><?= $POUCE ?></svg>
            <?= c('hero.cta_principal') ?>
          </a>
          <a href="#projet" class="btn btn--ghost btn--big hero__second" data-suivi="cta-projet"><?= c('hero.cta_secondaire') ?></a>
        </div>
      </div>

      <div class="hero__right">
        <div class="mascotte" id="mascotte" aria-hidden="true">
          <div class="mascotte__img"></div>
          <div class="mascotte__fallback">
            <span class="shield shield--xl"><span class="shield__puck"></span><b><?= e(c('marque.sigle')) ?></b><i>CLUB DE HOCKEY<br>AMATEUR ROUEN</i></span>
          </div>
          <div class="mascotte__halo"></div>
        </div>

        <div class="board reveal" data-reveal data-delay="260" id="board">
          <div class="board__frame">
            <div class="board__top">
              <span class="board__live"><i></i><?= c('compteur.direct') ?></span>
              <span class="board__label"><?= c('compteur.libelle') ?></span>
              <span class="board__per"><?= c('compteur.periode') ?></span>
            </div>

            <div class="board__main">
              <div class="board__digits" id="boardDigits" role="status" aria-live="polite" aria-label="Nombre de soutiens">
                <span class="digit"><b>0</b></span><span class="digit"><b>0</b></span><span class="digit"><b>0</b></span>
              </div>
              <button class="board__thumb" id="thumbBtn" aria-label="Ajouter mon pouce de soutien" data-suivi="pouce">
                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $POUCE ?></svg>
                <span class="board__thumb-ring"></span>
              </button>
            </div>

            <div class="rink" aria-hidden="true">
              <div class="rink__ice">
                <span class="rink__blue rink__blue--l"></span>
                <span class="rink__red"></span>
                <span class="rink__blue rink__blue--r"></span>
                <span class="rink__circle"></span>
                <div class="rink__fill" id="rinkFill"></div>
                <div class="rink__puck" id="rinkPuck"><i></i></div>
                <div class="rink__goal"><svg viewBox="0 0 24 24"><path d="M4 20V9a8 8 0 0 1 16 0v11M4 20h16M8 20v-8m8 8v-8M8 14h8"/></svg></div>
              </div>
            </div>

            <div class="board__bottom">
              <span id="boardHint"><?= c('compteur.ligne_basse') ?></span>
              <span class="board__faces" id="counterFaces"></span>
            </div>
          </div>
          <div class="board__glow" aria-hidden="true"></div>
        </div>
      </div>
    </div>

    <a class="hero__scroll" href="#engagement" aria-label="Descendre"><span></span></a>
  </div>
</section>

<div class="marquee" aria-hidden="true">
  <div class="marquee__track" id="marqueeTrack">
    <?php $mots = cl('bandeau_defilant'); for ($i = 0; $i < 3; $i++): foreach ($mots as $mot): ?>
      <span><?= e((string)$mot) ?></span><i>◆</i>
    <?php endforeach; endfor; ?>
  </div>
</div>

<section class="section section--light engagement" id="engagement">
  <div class="wrap">
    <p class="eyebrow reveal" data-reveal><?= c('engagement.surtitre') ?></p>
    <h2 class="h2 reveal" data-reveal data-delay="80"><?= c('engagement.titre') ?></h2>
    <p class="bigtext" data-words><?= c('engagement.texte') ?></p>

    <div class="eng-grid">
      <figure class="quote" data-scene="tilt">
        <blockquote><?= c('engagement.citation') ?></blockquote>
        <figcaption><?= c('engagement.citation_source') ?></figcaption>
      </figure>

      <div class="ambition" data-scene="tilt">
        <div class="ambition__badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Zm10 1h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg></div>
        <div>
          <h3><?= c('engagement.ambition_titre') ?></h3>
          <p><?= c('engagement.ambition_texte') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="vscene" id="valeurs">
  <div class="vscene__sticky">
    <div class="vscene__bg" aria-hidden="true"><span class="vscene__glow"></span></div>
    <p class="eyebrow vscene__eyebrow"><?= c('valeurs.surtitre') ?></p>

    <div class="vscene__stage">
      <span class="vscene__letter" id="vsLetter" aria-hidden="true"><?= e((string)(cl('valeurs.items')[0]['lettre'] ?? 'C')) ?></span>
      <?php foreach (cl('valeurs.items') as $i => $v): ?>
      <article class="vcard<?= $i === 0 ? ' is-on' : '' ?>" data-i="<?= $i ?>">
        <svg class="vcard__ico" viewBox="0 0 24 24" aria-hidden="true"><?= $icones_valeurs[$i] ?? $icones_valeurs[0] ?></svg>
        <h2><?= e((string)($v['titre'] ?? '')) ?></h2>
        <p><?= e((string)($v['texte'] ?? '')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="vscene__rail" aria-hidden="true">
      <?php foreach (cl('valeurs.items') as $i => $v): ?><i<?= $i === 0 ? ' class="is-on"' : '' ?>></i><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="pscene" id="projet">
  <div class="pscene__sticky">
    <header class="pscene__head">
      <div>
        <p class="eyebrow"><?= c('projet.surtitre') ?></p>
        <h2 class="h2 h2--light"><?= c('projet.titre') ?></h2>
      </div>
      <div class="pscene__meter" aria-hidden="true">
        <span class="pscene__step"><b id="prioStep">01</b> / <?= count(cl('projet.priorites')) ?></span>
        <span class="pscene__bar"><i id="prioBar"></i></span>
      </div>
    </header>

    <div class="pscene__viewport" id="prioViewport">
    <div class="pscene__track" id="prioTrack">
      <?php foreach (cl('projet.priorites') as $i => $p): ?>
      <article class="pcard">
        <span class="pcard__num"><?= $i + 1 ?></span>
        <h3><?= e((string)($p['titre'] ?? '')) ?></h3>
        <ul>
          <?php foreach (($p['points'] ?? []) as $pt): ?><li><?= e((string)$pt) ?></li><?php endforeach; ?>
        </ul>
      </article>
      <?php endforeach; ?>
    </div>
    </div>
    <p class="pscene__hint" aria-hidden="true"><span>Faites glisser</span> →</p>
  </div>
</section>

<section class="section section--dark equipe" id="equipe">
  <div class="wrap">
    <p class="eyebrow reveal" data-reveal><?= c('equipe.surtitre') ?></p>
    <h2 class="h2 h2--light reveal" data-reveal data-delay="80"><?= c('equipe.titre') ?></h2>
    <p class="bigtext bigtext--light" data-words><?= c('equipe.texte') ?></p>

    <div class="pillars">
      <?php foreach (cl('equipe.piliers') as $i => $pil): ?>
      <div class="pillar" data-scene="rise" data-speed="<?= $i ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icones_piliers[$i] ?? $icones_piliers[0] ?></svg>
        <h3><?= e((string)($pil['titre'] ?? '')) ?></h3><p><?= e((string)($pil['texte'] ?? '')) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--support" id="soutien">
  <div class="wrap wrap--narrow">
    <p class="eyebrow reveal" data-reveal><?= c('soutien.surtitre') ?></p>
    <h2 class="h2 reveal" data-reveal data-delay="80"><?= c('soutien.titre') ?></h2>
    <p class="lead reveal" data-reveal data-delay="140"><?= c('soutien.texte') ?></p>

    <div class="support-card reveal" data-reveal data-delay="200">
      <div class="support-card__count">
        <div class="thumb-big" id="thumbBig" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $POUCE ?></svg></div>
        <div class="board__digits board__digits--sm" id="boardDigits2" aria-hidden="true">
          <span class="digit"><b>0</b></span><span class="digit"><b>0</b></span><span class="digit"><b>0</b></span>
        </div>
        <span class="support-card__lbl">soutiens<br>enregistrés</span>
      </div>

      <form class="form" id="supportForm" novalidate>
        <div class="field">
          <input type="text" id="fname" name="name" autocomplete="given-name" placeholder=" " maxlength="60">
          <label for="fname">Prénom (facultatif)</label>
        </div>
        <div class="field">
          <input type="email" id="femail" name="email" autocomplete="email" placeholder=" " required maxlength="120">
          <label for="femail">Votre adresse e-mail *</label>
        </div>
        <div class="field field--select">
          <select id="fcat" name="category">
            <?php foreach (cl('soutien.profils') as $prof): ?><option value="<?= e((string)$prof) ?>"><?= e((string)$prof) ?></option><?php endforeach; ?>
          </select>
          <label for="fcat">Vous êtes</label>
        </div>
        <div class="field">
          <textarea id="fmsg" name="message" placeholder=" " rows="2" maxlength="400"></textarea>
          <label for="fmsg">Un mot d'encouragement (facultatif)</label>
        </div>

        <label class="check">
          <input type="checkbox" id="fpublic" name="public" checked>
          <span><?= e(c('soutien.affichage_public')) ?></span>
        </label>
        <label class="check">
          <input type="checkbox" id="fconsent" name="consent" required>
          <span><?= e(c('soutien.consentement')) ?></span>
        </label>

        <input type="text" name="website" id="fhp" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

        <button type="submit" class="btn btn--primary btn--big btn--full" id="submitBtn" data-suivi="envoi-formulaire">
          <svg class="ico-thumb" viewBox="0 0 24 24" aria-hidden="true"><?= $POUCE ?></svg>
          <span><?= e(c('soutien.bouton')) ?></span>
        </button>
        <p class="form__msg" id="formMsg" role="status" aria-live="polite"></p>
        <p class="form__rgpd">
          <?= c('soutien.rgpd') ?>
          <a href="mailto:<?= e(c('pied.email')) ?>"><?= e(c('pied.email')) ?></a>.
        </p>
      </form>
    </div>

    <div class="wall" id="wall" hidden>
      <p class="wall__title">Ils soutiennent déjà le projet</p>
      <div class="wall__list" id="wallList"></div>
    </div>
  </div>
</section>

<section class="finale" data-scene="finale">
  <div class="wrap">
    <h2 class="finale__txt"><?= c('finale.texte') ?></h2>
    <a href="#soutien" class="btn btn--dark btn--big reveal" data-reveal data-delay="120" data-suivi="cta-finale"><?= c('finale.bouton') ?></a>
  </div>
</section>
</main>

<footer class="footer">
  <div class="wrap footer__grid">
    <div>
      <span class="shield shield--sm"><span class="shield__puck"></span><b><?= e(c('marque.sigle')) ?></b></span>
      <p class="footer__base"><?= c('pied.base') ?></p>
    </div>
    <div>
      <p class="footer__h">Contact</p>
      <a href="mailto:<?= e(c('pied.email')) ?>" data-suivi="clic-email"><?= e(c('pied.email')) ?></a>
    </div>
    <div>
      <p class="footer__h">Le programme</p>
      <a href="<?= e(c('pied.programme_url')) ?>" target="_blank" rel="noopener" data-suivi="clic-programme"><?= e(c('pied.programme_libelle')) ?></a>
    </div>
    <div>
      <p class="footer__h">Liste</p>
      <p class="footer__base"><?= c('pied.liste') ?></p>
    </div>
  </div>
  <p class="footer__legal"><?= c('pied.mentions') ?></p>
</footer>

<div class="skater" id="skater" aria-hidden="true">
  <div class="skater__rail">
    <i class="skater__fill"></i>
    <div class="skater__figure" id="skaterFigure">
      <span class="skater__spray skater__spray--1"></span>
      <span class="skater__spray skater__spray--2"></span>
      <span class="skater__img"></span>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="assets/js/main.js?v=3" defer></script>
</body>
</html>
