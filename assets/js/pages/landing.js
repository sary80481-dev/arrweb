/* ============================================================
   assets/js/pages/landing.js — interaksi landing
   Navbar scroll, menu mobile, reveal, parallax hero, counter,
   carousel koleksi, slider HTML⇄Roblox, story sticky, kode diketik.
   Aman untuk SPA: init ulang saat 'spa:navigated', listener global sekali.
   ============================================================ */
(function () {
  'use strict';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let cleanup = [];   // listener window yang harus dilepas saat pindah halaman

  function on(target, type, fn, opts) {
    target.addEventListener(type, fn, opts);
    cleanup.push(() => target.removeEventListener(type, fn, opts));
  }

  function teardown() {
    cleanup.forEach(fn => fn());
    cleanup = [];
  }

  function initLanding() {
    const root = document.getElementById('landing');
    if (!root || root.dataset.init === '1') return;
    root.dataset.init = '1';
    teardown();

    initNav(root);
    initReveal(root);
    initHero(root);
    initCounters(root);
    initCarousel(root);
    initSlider(root);
    initStory(root);
    initTyped(root);
    initSampleLinks(root);
  }

  /* ---------- Navbar: solid saat scroll, sembunyi saat scroll turun ---------- */
  function initNav(root) {
    const nav = root.querySelector('#lxNav');
    const burger = root.querySelector('#lxBurger');
    if (!nav) return;
    let last = 0;
    const onScroll = () => {
      const y = window.scrollY;
      nav.classList.toggle('is-scrolled', y > 24);
      nav.classList.toggle('is-hidden', y > last && y > 400 && !nav.classList.contains('menu-open'));
      last = y;
    };
    on(window, 'scroll', onScroll, { passive: true });
    onScroll();

    burger?.addEventListener('click', () => {
      const open = nav.classList.toggle('menu-open');
      burger.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
    });
    nav.querySelectorAll('.lx-drawer a').forEach(a => a.addEventListener('click', () => {
      nav.classList.remove('menu-open');
      document.body.style.overflow = '';
    }));
  }

  /* ---------- Reveal saat masuk viewport ---------- */
  function initReveal(root) {
    const items = root.querySelectorAll('.lx-reveal');
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(el => el.classList.add('in'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    items.forEach(el => io.observe(el));
    cleanup.push(() => io.disconnect());
  }

  /* ---------- Hero: parallax mouse + scroll ---------- */
  function initHero(root) {
    const hero = root.querySelector('#lxHero');
    if (!hero || reduceMotion) return;
    let raf = 0;
    const set = (mx, my) => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        hero.style.setProperty('--mx', mx.toFixed(3));
        hero.style.setProperty('--my', my.toFixed(3));
      });
    };
    hero.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      const r = hero.getBoundingClientRect();
      set((e.clientX - r.left) / r.width - 0.5, (e.clientY - r.top) / r.height - 0.5);
    });
    hero.addEventListener('pointerleave', () => set(0, 0));
    on(window, 'scroll', () => {
      const p = Math.min(1, window.scrollY / Math.max(1, hero.offsetHeight));
      hero.style.setProperty('--sy', p.toFixed(3));
    }, { passive: true });
  }

  /* ---------- Angka menghitung naik ---------- */
  function initCounters(root) {
    const els = root.querySelectorAll('[data-count]');
    const run = (el) => {
      const target = +el.dataset.count;
      if (reduceMotion) { el.textContent = target; return; }
      const start = performance.now();
      const dur = 1400;
      const tick = (t) => {
        const k = Math.min(1, (t - start) / dur);
        el.textContent = Math.round(target * (1 - Math.pow(1 - k, 3)));
        if (k < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    if (!('IntersectionObserver' in window)) { els.forEach(run); return; }
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.6 });
    els.forEach(el => io.observe(el));
    cleanup.push(() => io.disconnect());
  }

  /* ---------- Carousel koleksi: tombol, drag, keyboard, progress ---------- */
  function initCarousel(root) {
    const track = root.querySelector('#lxCarousel');
    const bar = root.querySelector('#lxCarouselBar');
    if (!track) return;
    const prev = root.querySelector('[data-carousel="prev"]');
    const next = root.querySelector('[data-carousel="next"]');

    // Skala iframe preview mengikuti lebar kartu
    const fit = () => {
      track.querySelectorAll('.lx-card-media').forEach(m => {
        m.style.setProperty('--card-scale', (m.clientWidth / 800).toFixed(4));
      });
    };
    fit();
    on(window, 'resize', fit);

    const step = () => (track.querySelector('.lx-card')?.offsetWidth || 400) + 20;
    const update = () => {
      const max = track.scrollWidth - track.clientWidth;
      const p = max > 0 ? track.scrollLeft / max : 0;
      if (bar) {
        const w = Math.max(12, (track.clientWidth / track.scrollWidth) * 100);
        bar.style.width = w + '%';
        bar.style.transform = `translateX(${p * (100 / w) * (100 - w)}%)`;
      }
      if (prev) prev.disabled = track.scrollLeft < 4;
      if (next) next.disabled = track.scrollLeft > max - 4;
    };
    track.addEventListener('scroll', update, { passive: true });
    on(window, 'resize', update);
    update();

    prev?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); track.scrollBy({ left: step(), behavior: 'smooth' }); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); track.scrollBy({ left: -step(), behavior: 'smooth' }); }
    });

    // Drag dengan mouse (sentuh sudah native)
    let down = false, startX = 0, startLeft = 0, moved = 0;
    track.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.target.closest('button')) return;
      down = true; moved = 0;
      startX = e.clientX; startLeft = track.scrollLeft;
      track.setPointerCapture(e.pointerId);
    });
    track.addEventListener('pointermove', (e) => {
      if (!down) return;
      const dx = e.clientX - startX;
      moved = Math.max(moved, Math.abs(dx));
      if (moved > 4) track.classList.add('is-dragging');
      track.scrollLeft = startLeft - dx;
    });
    const end = () => {
      if (!down) return;
      down = false;
      track.classList.remove('is-dragging');
    };
    track.addEventListener('pointerup', end);
    track.addEventListener('pointercancel', end);
  }

  /* ---------- Slider HTML ⇄ Roblox ---------- */
  function initSlider(root) {
    const slider = root.querySelector('#lxSlider');
    const handle = slider?.querySelector('.lx-slider-handle');
    if (!slider || !handle) return;
    const set = (pct) => {
      pct = Math.max(4, Math.min(96, pct));
      slider.style.setProperty('--pos', pct + '%');
      handle.setAttribute('aria-valuenow', String(Math.round(pct)));
    };
    const fromEvent = (e) => {
      const r = slider.getBoundingClientRect();
      set(((e.clientX - r.left) / r.width) * 100);
    };
    let dragging = false;
    slider.addEventListener('pointerdown', (e) => {
      dragging = true;
      slider.setPointerCapture(e.pointerId);
      fromEvent(e);
    });
    slider.addEventListener('pointermove', (e) => { if (dragging) fromEvent(e); });
    slider.addEventListener('pointerup', () => { dragging = false; });
    slider.addEventListener('pointercancel', () => { dragging = false; });
    handle.addEventListener('keydown', (e) => {
      const cur = parseFloat(slider.style.getPropertyValue('--pos')) || 50;
      if (e.key === 'ArrowLeft')  { e.preventDefault(); set(cur - 5); }
      if (e.key === 'ArrowRight') { e.preventDefault(); set(cur + 5); }
    });

    // Sekali geser otomatis saat pertama terlihat (petunjuk bisa digeser)
    if (!reduceMotion && 'IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        if (!entries[0].isIntersecting) return;
        io.disconnect();
        const start = performance.now();
        const anim = (t) => {
          const k = Math.min(1, (t - start) / 1600);
          set(50 + Math.sin(k * Math.PI * 2) * 18 * (1 - k));
          if (k < 1 && !dragging) requestAnimationFrame(anim);
        };
        requestAnimationFrame(anim);
      }, { threshold: 0.5 });
      io.observe(slider);
      cleanup.push(() => io.disconnect());
    }
  }

  /* ---------- Story: langkah aktif ↔ visual ---------- */
  function initStory(root) {
    const steps = root.querySelectorAll('.lx-step');
    const scenes = root.querySelectorAll('.lx-story-scene');
    if (!steps.length || !('IntersectionObserver' in window)) return;
    const activate = (i) => {
      steps.forEach(s => s.classList.toggle('is-active', +s.dataset.step === i));
      scenes.forEach(s => s.classList.toggle('is-active', +s.dataset.scene === i));
    };
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) activate(+e.target.dataset.step); });
    }, { rootMargin: '-45% 0px -45% 0px' });
    steps.forEach(s => io.observe(s));
    cleanup.push(() => io.disconnect());
  }

  /* ---------- Kode "diketik" saat terlihat ---------- */
  function initTyped(root) {
    const pre = root.querySelector('#lxTyped');
    if (!pre) return;
    const code = pre.dataset.code || '';
    const highlight = (txt) => txt
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/"[^"\n]*"?/g, m => `<span class="s">${m}</span>`)
      .replace(/\b(function|local|if|then|not|return|end|and|or)\b/g, '<span class="k">$1</span>');
    if (reduceMotion || !('IntersectionObserver' in window)) { pre.innerHTML = highlight(code); return; }
    const io = new IntersectionObserver((entries) => {
      if (!entries[0].isIntersecting) return;
      io.disconnect();
      let i = 0;
      const tick = () => {
        i = Math.min(code.length, i + 3);
        pre.innerHTML = highlight(code.slice(0, i));
        if (i < code.length && document.body.contains(pre)) setTimeout(tick, 16);
      };
      tick();
    }, { threshold: 0.4 });
    io.observe(pre);
    cleanup.push(() => io.disconnect());
  }

  /* ---------- "Buka di Converter" dari kartu koleksi ---------- */
  function initSampleLinks(root) {
    root.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-open-sample]');
      if (!btn) return;
      try { localStorage.setItem('arrr_load_sample', btn.dataset.openSample); } catch {}
      const url = new URL(window.__converterUrl || 'converter', location.href).href;
      if (window.__spaNavigate) window.__spaNavigate(url); else location.href = url;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanding);
  } else {
    initLanding();
  }
  window.addEventListener('spa:navigated', () => {
    if (!document.getElementById('landing')) { teardown(); document.body.style.overflow = ''; }
    initLanding();
  });
})();
