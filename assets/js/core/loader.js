/* ============================================================
   assets/js/core/loader.js — splash loader (progress palsu sampai window load)
   Dipanggil langsung setelah markup #arrrLoader.
   ============================================================ */
(function () {
  'use strict';
  const loader = document.getElementById('arrrLoader');
  const bar = document.getElementById('arrrLoaderBar');
  const percentEl = document.getElementById('arrrLoaderPercent');
  if (!loader || !bar || !percentEl) return;

  let progress = 0;
  let done = false;

  function tick() {
    if (done) return;
    let increment;
    if (progress < 30)      increment = 4 + Math.random() * 3;
    else if (progress < 70) increment = 1.5 + Math.random() * 2;
    else if (progress < 95) increment = 0.4 + Math.random() * 0.8;
    else                    increment = 0.15;

    progress = Math.min(progress + increment, 99);
    bar.style.width = progress + '%';
    percentEl.textContent = Math.floor(progress) + '%';

    if (progress < 99) setTimeout(tick, 80 + Math.random() * 120);
  }
  tick();

  window.arrrLoaderFinish = function () {
    if (done) return;
    done = true;
    bar.style.width = '100%';
    percentEl.textContent = '100%';
    setTimeout(() => {
      loader.classList.add('hidden');
      setTimeout(() => {
        if (loader.parentNode) loader.parentNode.removeChild(loader);
      }, 900);
    }, 300);
  };

  if (document.readyState === 'complete') {
    setTimeout(window.arrrLoaderFinish, 200);
  } else {
    window.addEventListener('load', () => {
      const minDelay = Math.max(0, 600 - performance.now());
      setTimeout(window.arrrLoaderFinish, minDelay);
    });
  }

  setTimeout(() => { if (!done) window.arrrLoaderFinish(); }, 3000);
})();
