/* =========================================================
   CHAR 2026 — moteur de scroll + tableau d'affichage
   ========================================================= */
(function () {
  'use strict';

  var API = 'api/support.php';
  var CONTACT = 'ensemblepourlechar@gmail.com';
  var LS_KEY = 'char2026_supported';
  var GOAL = window.CHAR_OBJECTIF || 500;   // objectif interne : pilote la jauge de la patinoire
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var clamp = function (v, a, b) { return v < a ? a : v > b ? b : v; };
  var lerp = function (a, b, t) { return a + (b - a) * t; };

  /* =======================================================
     1. MOTEUR DE SCROLL — progression 0→1 par scène
     ======================================================= */
  var scenes = [];
  var vh = window.innerHeight;
  var vw = window.innerWidth;

  function addScene(el, mode, cb) {
    if (!el) return;
    scenes.push({ el: el, mode: mode, cb: cb, s: 0, e: 1, p: -1 });
  }

  function measure() {
    vh = window.innerHeight;
    vw = window.innerWidth;
    sizePrios();
    var sy = window.scrollY;
    scenes.forEach(function (sc) {
      var top = sc.el.getBoundingClientRect().top + sy;
      var h = sc.el.offsetHeight;
      if (sc.mode === 'pin') { sc.s = top; sc.e = top + h - vh; }
      else if (sc.mode === 'text') { sc.s = top - vh * 0.85; sc.e = top + h - vh * 0.42; }
      else { sc.s = top - vh; sc.e = top + h; }
      if (sc.e - sc.s < 1) sc.e = sc.s + 1;
    });
  }

  /* Priorités : swipe natif sous 1100 px, rail piloté par le scroll au-dessus */
  var prioSection = $('#projet'), prioTrack = $('#prioTrack'), prioView = $('#prioViewport');
  var prioSticky = $('.pscene__sticky');
  var prioPinned = false;

  function sizePrios() {
    if (!prioSection || !prioTrack || !prioSticky) return;
    prioPinned = !reduce && getComputedStyle(prioSticky).position === 'sticky';
    if (!prioPinned) {
      prioSection.style.height = '';
      prioTrack.style.transform = '';
      updateSwipeMeter();
      return;
    }
    if (prioView) prioView.scrollLeft = 0;
    var run = Math.max(0, prioTrack.scrollWidth - vw);
    prioSection.style.height = (vh + run) + 'px';
  }

  /* =======================================================
     2. SCÈNES
     ======================================================= */

  /* --- Hero : recule, s'estompe et se floute --- */
  var heroSticky = $('.hero__sticky');
  addScene($('#hero'), 'pin', function (p) {
    if (!heroSticky) return;
    var e = p * p;                                   // départ doux
    heroSticky.style.setProperty('--hs', (1 - e * 0.13).toFixed(4));
    heroSticky.style.setProperty('--ho', (1 - clamp((p - 0.25) / 0.7, 0, 1)).toFixed(3));
    heroSticky.style.setProperty('--hb', (e * 7).toFixed(2));
  });

  /* --- Bandeau : défile avec le scroll + dérive continue --- */
  var mTrack = $('#marqueeTrack');
  var mLoop = 0;
  function marquee(now) {
    if (!mTrack) return;
    if (!mLoop) mLoop = mTrack.scrollWidth / 3;
    var x = (now * 0.022 + window.scrollY * 0.28) % mLoop;
    mTrack.style.transform = 'translate3d(' + (-x) + 'px,0,0)';
  }

  /* --- Textes révélés mot à mot --- */
  $$('[data-words]').forEach(function (el) {
    var words = el.textContent.trim().split(/\s+/);
    el.textContent = '';
    words.forEach(function (w, i) {
      var n = document.createElement('w');
      n.textContent = w;
      el.appendChild(n);
      if (i < words.length - 1) el.appendChild(document.createTextNode(' '));
    });
    var spans = $$('w', el), last = -1;
    addScene(el, 'text', function (p) {
      var idx = Math.round(clamp(p * 1.12, 0, 1) * spans.length);
      if (idx === last) return;
      if (idx > last) { for (var i = last + 1; i < idx; i++) spans[i] && spans[i].classList.add('lit'); }
      else { for (var j = last; j >= idx; j--) spans[j] && spans[j].classList.remove('lit'); }
      last = idx;
    });
  });

  /* --- Parallaxe douce (citation, encart ambition) --- */
  $$('[data-scene="tilt"]').forEach(function (el, i) {
    addScene(el, 'through', function (p) {
      el.style.setProperty('--ty', ((0.5 - p) * (i % 2 ? 46 : 26)).toFixed(1) + 'px');
    });
  });

  /* --- Piliers : montée décalée --- */
  $$('[data-scene="rise"]').forEach(function (el) {
    var sp = parseFloat(el.dataset.speed || 0);
    addScene(el, 'through', function (p) {
      el.style.setProperty('--ty', ((0.5 - p) * (34 + sp * 22)).toFixed(1) + 'px');
    });
  });

  /* --- Valeurs : scène épinglée, 4 cartes qui s'enchaînent --- */
  var vCards = $$('.vcard'), vRail = $$('.vscene__rail i'), vLetter = $('#vsLetter');
  var vGlow = $('.vscene__glow');
  var vLast = -1;
  var LETTERS = ['C', 'H', 'A', 'R'];
  addScene($('#valeurs'), 'pin', function (p) {
    var n = vCards.length;
    var f = clamp(p, 0, 0.9999) * n;
    var i = Math.floor(f);
    var sub = f - i;                                  // 0→1 dans la carte courante
    if (i !== vLast) {
      vCards.forEach(function (c, k) {
        c.classList.toggle('is-on', k === i);
        c.classList.toggle('is-out', k < i);
      });
      vRail.forEach(function (r, k) { r.classList.toggle('is-on', k === i); });
      if (vLetter) vLetter.textContent = LETTERS[i] || 'R';
      vLast = i;
    }
    if (vLetter) {
      vLetter.style.setProperty('--ls', lerp(0.86, 1.14, sub).toFixed(3));
      vLetter.style.setProperty('--lr', lerp(-5, 5, sub).toFixed(2) + 'deg');
    }
    if (vGlow) vGlow.style.setProperty('--gs', lerp(0.85, 1.2, Math.sin(p * Math.PI)).toFixed(3));
  });

  /* --- 10 priorités --- */
  var prioBar = $('#prioBar'), prioStep = $('#prioStep');
  var prioCount = $$('.pcard').length || 10;

  function setPrioMeter(p) {
    if (prioBar) prioBar.style.width = (clamp(p, 0, 1) * 100).toFixed(1) + '%';
    if (prioStep) {
      var step = clamp(Math.round(p * (prioCount - 1)) + 1, 1, prioCount);
      var txt = step < 10 ? '0' + step : String(step);
      if (prioStep.textContent !== txt) prioStep.textContent = txt;
    }
  }
  setPrioMeter(0);

  /* Mobile / tablette : le doigt fait défiler, la jauge suit */
  function updateSwipeMeter() {
    if (!prioView || prioPinned) return;
    var run = prioView.scrollWidth - prioView.clientWidth;
    setPrioMeter(run > 0 ? prioView.scrollLeft / run : 0);
  }
  if (prioView) {
    prioView.addEventListener('scroll', updateSwipeMeter, { passive: true });
  }

  /* Desktop : le scroll vertical translate le rail */
  addScene(prioSection, 'pin', function (p) {
    if (!prioTrack || !prioPinned) return;
    var run = Math.max(0, prioTrack.scrollWidth - vw);
    prioTrack.style.transform = 'translate3d(' + (-p * run) + 'px,0,0)';
    setPrioMeter(p);
  });

  /* --- Finale : léger zoom --- */
  var finaleTxt = $('.finale__txt');
  addScene($('[data-scene="finale"]'), 'through', function (p) {
    if (finaleTxt) finaleTxt.style.setProperty('--fs', lerp(0.9, 1.06, p).toFixed(4));
  });

  /* =======================================================
     3. BOUCLE
     ======================================================= */
  var nav = $('#nav');

  /* --- Mascotte patineuse : position = avancement, patine tant qu'on scrolle --- */
  var skater = $('#skater'), skaterFig = $('#skaterFigure');
  var lastSy = window.scrollY, skateT = 0, skating = false, lastDir = 1;

  function updateSkater(sy, p) {
    if (!skater) return;
    skater.style.setProperty('--sp', p.toFixed(4));
    skater.classList.toggle('is-on', sy > 60);

    var d = sy - lastSy;
    lastSy = sy;
    if (Math.abs(d) > 0.5) {
      var dir = d > 0 ? 1 : -1;
      if (dir !== lastDir) { lastDir = dir; skaterFig.style.setProperty('--dir', dir); }
      if (!skating) { skating = true; skaterFig.classList.add('is-skating'); }
      clearTimeout(skateT);
      skateT = setTimeout(function () { skating = false; skaterFig.classList.remove('is-skating'); }, 170);
    }
  }

  function frame(now) {
    var sy = window.scrollY;
    var max = document.documentElement.scrollHeight - vh;
    var prog = max > 0 ? clamp(sy / max, 0, 1) : 0;
    updateSkater(sy, prog);
    if (nav) nav.classList.toggle('is-stuck', sy > 30);
    marquee(now);
    for (var i = 0; i < scenes.length; i++) {
      var sc = scenes[i];
      var p = clamp((sy - sc.s) / (sc.e - sc.s), 0, 1);
      if (Math.abs(p - sc.p) > 0.0005 || (p === 0 && sc.p !== 0) || (p === 1 && sc.p !== 1)) {
        sc.p = p; sc.cb(p);
      }
    }
    requestAnimationFrame(frame);
  }

  if (!reduce) {
    measure();
    requestAnimationFrame(frame);
    var rt;
    var remeasure = function () { clearTimeout(rt); rt = setTimeout(measure, 150); };
    window.addEventListener('resize', remeasure);
    window.addEventListener('orientationchange', remeasure);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) remeasure(); });
    window.addEventListener('load', measure);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);
  } else {
    $$('[data-words] w').forEach(function (w) { w.classList.add('lit'); });
    vCards.forEach(function (c) { c.classList.add('is-on'); });
    sizePrios();
  }

  /* =======================================================
     4. APPARITIONS SIMPLES
     ======================================================= */
  var revealables = $$('[data-reveal]');
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.style.setProperty('--d', (en.target.dataset.delay || 0) + 'ms');
        en.target.classList.add('is-in');
        io.unobserve(en.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    revealables.forEach(function (el) { io.observe(el); });
  } else {
    revealables.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* =======================================================
     5. NAVIGATION
     ======================================================= */
  var burger = $('#burger'), menu = $('#mobileMenu');
  if (burger && menu) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') === 'true';
      burger.setAttribute('aria-expanded', String(!open));
      menu.hidden = open;
    });
    $$('a', menu).forEach(function (a) {
      a.addEventListener('click', function () {
        burger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
      });
    });
  }

  $$('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (!id || id === '#') return;
      var t = document.querySelector(id);
      if (!t) return;
      e.preventDefault();
      var y = t.getBoundingClientRect().top + window.scrollY - 56;
      window.scrollTo({ top: y, behavior: reduce ? 'auto' : 'smooth' });
      history.replaceState(null, '', id);
    });
  });

  /* --- Mascotte : chargée uniquement sur grand écran (fond CSS) --- */
  var masc = $('#mascotte'), hero = $('#hero');
  if (masc && window.matchMedia('(min-width:1100px)').matches) {
    var probe = new Image();
    probe.onerror = function () { masc.classList.add('is-empty'); };
    probe.src = 'assets/img/mascotte.png';
  }

  /* --- Parallaxe souris sur la mascotte --- */
  if (masc && hero && !reduce && window.matchMedia('(pointer:fine)').matches) {
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - 0.5;
      var y = (e.clientY - r.top) / r.height - 0.5;
      masc.style.transform = 'translate3d(' + (x * -20) + 'px,' + (y * -16) + 'px,0)';
    });
    hero.addEventListener('mouseleave', function () { masc.style.transform = ''; });
  }

  /* =======================================================
     6. TABLEAU D'AFFICHAGE
     ======================================================= */
  var boards = [$('#boardDigits'), $('#boardDigits2')].filter(Boolean);
  var rinkFill = $('#rinkFill'), rinkPuck = $('#rinkPuck');
  var boardHint = $('#boardHint'), boardGoal = $('#boardGoal');
  var facesEl = $('#counterFaces');
  var wall = $('#wall'), wallList = $('#wallList');
  var shown = 0, target = 0;

  if (boardGoal) boardGoal.textContent = GOAL;

  function paint(n) {
    var str = String(Math.max(0, n));
    while (str.length < 3) str = '0' + str;
    boards.forEach(function (b) {
      var tiles = $$('.digit', b);
      if (tiles.length !== str.length) {
        b.innerHTML = '';
        for (var i = 0; i < str.length; i++) {
          var d = document.createElement('span');
          d.className = 'digit';
          d.innerHTML = '<b>' + str[i] + '</b>';
          b.appendChild(d);
        }
        return;
      }
      tiles.forEach(function (t, i) {
        var v = $('b', t);
        if (v.textContent !== str[i]) {
          v.textContent = str[i];
          t.classList.remove('is-flip');
          void t.offsetWidth;
          t.classList.add('is-flip');
        }
      });
    });
    var pct = clamp(n / GOAL, 0, 1) * 100;
    if (rinkFill) rinkFill.style.setProperty('--fill', pct.toFixed(1) + '%');
    if (rinkPuck) rinkPuck.style.setProperty('--fill', pct.toFixed(1) + '%');
  }

  function animateTo(n) {
    n = Math.max(0, parseInt(n, 10) || 0);
    var from = shown; target = n;
    if (reduce || from === n) { shown = n; paint(n); return; }
    var t0 = performance.now(), dur = 1200;
    (function step(now) {
      var t = Math.min(1, (now - t0) / dur);
      var e = 1 - Math.pow(1 - t, 4);
      shown = Math.round(from + (n - from) * e);
      paint(shown);
      if (t < 1) requestAnimationFrame(step);
    })(t0);
    if (rinkPuck && from !== n) {
      rinkPuck.classList.remove('is-shot'); void rinkPuck.offsetWidth; rinkPuck.classList.add('is-shot');
    }
  }
  paint(0);

  function renderPeople(list) {
    if (!Array.isArray(list) || !list.length) return;
    if (facesEl) {
      facesEl.innerHTML = '';
      list.slice(0, 4).forEach(function (p, i) {
        var s = document.createElement('span');
        s.style.animationDelay = (i * 70) + 'ms';
        s.textContent = (p.name || '?').charAt(0).toUpperCase();
        facesEl.appendChild(s);
      });
    }
    if (wall && wallList) {
      wallList.innerHTML = '';
      list.slice(0, 40).forEach(function (p, i) {
        var s = document.createElement('span');
        s.style.animationDelay = (i * 35) + 'ms';
        s.textContent = p.name + (p.category ? ' · ' + p.category : '');
        wallList.appendChild(s);
      });
      wall.hidden = false;
    }
  }

  function loadStats() {
    fetch(API + '?action=stats', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (d) { animateTo(d.count || 0); renderPeople(d.recent || []); })
      .catch(function () {
        animateTo(0);
        if (boardHint) boardHint.innerHTML = 'Compteur en cours d’activation';
      });
  }
  loadStats();

  /* =======================================================
     6 bis. MESURE D'AUDIENCE (sans cookie ni traceur tiers)
     ======================================================= */
  function suivre(ev) {
    try {
      var corps = JSON.stringify(ev);
      if (navigator.sendBeacon) {
        navigator.sendBeacon('api/track.php', new Blob([corps], { type: 'application/json' }));
      } else {
        fetch('api/track.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: corps, keepalive: true });
      }
    } catch (e) { /* la mesure ne doit jamais gêner la navigation */ }
  }

  function typeAppareil() {
    var l = Math.min(screen.width, screen.height);
    if (l < 768) return 'mobile';
    if (l < 1024) return 'tablette';
    return 'bureau';
  }

  suivre({ t: 'vue', r: document.referrer || '', d: typeAppareil() });

  document.addEventListener('click', function (e) {
    var cible = e.target.closest('[data-suivi]');
    if (cible) suivre({ t: 'clic', id: cible.dataset.suivi });
  }, { passive: true });

  var paliers = [25, 50, 75, 100], atteints = {};
  window.addEventListener('scroll', function () {
    var h = document.documentElement.scrollHeight - window.innerHeight;
    if (h <= 0) return;
    var p = (window.scrollY / h) * 100;
    for (var i = 0; i < paliers.length; i++) {
      if (p >= paliers[i] && !atteints[paliers[i]]) {
        atteints[paliers[i]] = true;
        suivre({ t: 'profondeur', s: paliers[i] });
      }
    }
  }, { passive: true });

  /* =======================================================
     7. POUCES
     ======================================================= */
  var toastEl = $('#toast'), toastT;
  function toast(m) {
    if (!toastEl) return;
    toastEl.textContent = m;
    toastEl.classList.add('is-on');
    clearTimeout(toastT);
    toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 3600);
  }

  function burst(el, n) {
    if (reduce || !el) return;
    var r = el.getBoundingClientRect();
    for (var i = 0; i < (n || 10); i++) {
      var s = document.createElement('span');
      s.className = 'thumb-particle';
      s.textContent = '👍';
      s.style.left = (r.left + r.width / 2 - 11) + 'px';
      s.style.top = (r.top + r.height / 2 - 11) + 'px';
      s.style.setProperty('--dx', (Math.random() * 200 - 100).toFixed(0) + 'px');
      s.style.setProperty('--dy', (-90 - Math.random() * 140).toFixed(0) + 'px');
      s.style.setProperty('--rot', (Math.random() * 90 - 45).toFixed(0) + 'deg');
      s.style.animationDelay = (i * 40) + 'ms';
      document.body.appendChild(s);
      setTimeout(function (node) { return function () { node.remove(); }; }(s), 1500 + i * 40);
    }
  }

  var thumbBtn = $('#thumbBtn');
  if (thumbBtn) {
    thumbBtn.addEventListener('click', function () {
      thumbBtn.classList.remove('is-pop'); void thumbBtn.offsetWidth; thumbBtn.classList.add('is-pop');
      burst(thumbBtn, 8);
      if (localStorage.getItem(LS_KEY)) { toast('Merci, votre soutien est déjà enregistré ! 💛'); return; }
      var t = $('#soutien');
      window.scrollTo({ top: t.getBoundingClientRect().top + window.scrollY - 56, behavior: reduce ? 'auto' : 'smooth' });
      setTimeout(function () { var f = $('#femail'); if (f) f.focus({ preventScroll: true }); }, reduce ? 0 : 800);
    });
  }
  if (localStorage.getItem(LS_KEY) && thumbBtn) thumbBtn.classList.add('is-done');

  /* =======================================================
     7 bis. SON D'AMBIANCE (corne de but)
     Les navigateurs interdisent la lecture audio tant que le
     visiteur n'a pas interagi : on tente au chargement, puis
     on se rabat sur le premier clic, appui ou défilement.
     ======================================================= */
  var CLE_SON = 'char2026_son';
  var audio = $('#ambiance');
  var btnSon = $('#btnSon');
  var sonCoupe = localStorage.getItem(CLE_SON) === 'coupe';

  function majBoutonSon() {
    if (!btnSon) return;
    btnSon.setAttribute('aria-pressed', sonCoupe ? 'false' : 'true');
    btnSon.setAttribute('aria-label', sonCoupe ? btnSon.dataset.activer : btnSon.dataset.couper);
    if (sonCoupe) btnSon.classList.remove('joue');
  }

  function jouerSon() {
    if (!audio || sonCoupe) return Promise.reject();
    audio.volume = Math.min(1, Math.max(0, (parseInt(audio.dataset.volume, 10) || 35) / 100));
    audio.currentTime = 0;
    var p = audio.play();
    return p && p.then ? p : Promise.resolve();
  }

  if (audio && btnSon) {
    majBoutonSon();

    audio.addEventListener('playing', function () { btnSon.classList.add('joue'); });
    audio.addEventListener('ended',   function () { btnSon.classList.remove('joue'); });
    audio.addEventListener('pause',   function () { btnSon.classList.remove('joue'); });

    // 1. tentative immédiate ; 2. repli sur la première interaction
    var armer = function () {
      var lancer = function () {
        jouerSon().catch(function () {});
        retirer();
      };
      var retirer = function () {
        ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach(function (ev) {
          window.removeEventListener(ev, lancer, true);
        });
      };
      ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach(function (ev) {
        window.addEventListener(ev, lancer, { capture: true, once: false, passive: true });
      });
    };
    if (!sonCoupe) jouerSon().catch(armer);

    btnSon.addEventListener('click', function (e) {
      e.stopPropagation();
      sonCoupe = !sonCoupe;
      localStorage.setItem(CLE_SON, sonCoupe ? 'coupe' : 'actif');
      majBoutonSon();
      if (sonCoupe) { audio.pause(); }
      else { jouerSon().catch(function () {}); }
      suivre({ t: 'clic', id: sonCoupe ? 'son-coupe' : 'son-actif' });
    });
  }

  /* =======================================================
     8. FORMULAIRE
     ======================================================= */
  var form = $('#supportForm'), msg = $('#formMsg'), submitBtn = $('#submitBtn');

  function setMsg(text, cls) {
    if (!msg) return;
    msg.textContent = text;
    msg.className = 'form__msg' + (cls ? ' ' + cls : '');
  }

  function mailtoFallback(d) {
    var body = 'Bonjour,\n\nJe soutiens la liste « Ensemble, dans le respect et l\'exigence » pour les élections du CHAR.\n\n'
      + 'Prénom : ' + (d.name || '-') + '\nE-mail : ' + d.email + '\nProfil : ' + (d.category || '-') + '\n'
      + (d.message ? 'Message : ' + d.message + '\n' : '');
    return 'mailto:' + CONTACT + '?subject=' + encodeURIComponent('Soutien à la liste CHAR 2026')
      + '&body=' + encodeURIComponent(body);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var emailEl = $('#femail'), consentEl = $('#fconsent');
      var data = {
        name: $('#fname').value.trim(),
        email: emailEl.value.trim(),
        category: $('#fcat').value,
        message: $('#fmsg').value.trim(),
        public: $('#fpublic').checked,
        consent: consentEl.checked,
        website: $('#fhp').value
      };
      emailEl.closest('.field').classList.remove('is-error');
      consentEl.closest('.check').classList.remove('is-error');

      if (!/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(data.email)) {
        emailEl.closest('.field').classList.add('is-error');
        setMsg('Merci d’indiquer une adresse e-mail valide.', 'err');
        emailEl.focus(); return;
      }
      if (!data.consent) {
        consentEl.closest('.check').classList.add('is-error');
        setMsg('Merci de cocher la case d’accord pour valider votre soutien.', 'err'); return;
      }

      submitBtn.classList.add('is-loading');
      $('span', submitBtn).textContent = 'Envoi en cours…';
      setMsg('');

      fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          submitBtn.classList.remove('is-loading');
          if (!res.ok || !res.j.ok) {
            $('span', submitBtn).textContent = 'J’apporte mon soutien';
            setMsg(res.j && res.j.error ? res.j.error : 'Une erreur est survenue, réessayez dans un instant.', 'err');
            return;
          }
          success(res.j, data);
        })
        .catch(function () {
          submitBtn.classList.remove('is-loading');
          $('span', submitBtn).textContent = 'J’apporte mon soutien';
          setMsg('Le formulaire n’est pas joignable. Vous pouvez nous écrire directement : ' + CONTACT, 'err');
          window.location.href = mailtoFallback(data);
        });
    });
  }

  function success(resp, data) {
    localStorage.setItem(LS_KEY, '1');
    // la conversion est comptée côté serveur (api/support.php), pas ici
    animateTo(resp.count);
    if (resp.recent) renderPeople(resp.recent);
    var big = $('#thumbBig');
    if (big) { big.classList.remove('is-pop'); void big.offsetWidth; big.classList.add('is-pop'); burst(big, 14); }
    if (!resp.already && typeof jouerSon === 'function') jouerSon().catch(function () {});
    if (thumbBtn) thumbBtn.classList.add('is-done');
    form.reset();
    $('span', submitBtn).textContent = 'Soutien enregistré ✓';
    setMsg(resp.already
      ? 'Cette adresse était déjà enregistrée — merci à nouveau !'
      : 'Merci ' + (data.name ? data.name + ' ' : '') + '! Votre soutien est bien enregistré. 💛', 'ok');
    toast(resp.already ? 'Soutien déjà enregistré 👍' : 'Merci, votre pouce est ajouté ! 👍');
  }

})();
