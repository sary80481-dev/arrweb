/* ============================================================
   assets/js/core/spa.js — SPA navigation (React/Next-like)
   No reload, fetch + swap DOM + History API
   ============================================================ */
(function () {
  'use strict';

  // ============================================================
  // Config
  // ============================================================
  const CONFIG = {
    containers: [
      'header.app-header',
      'main',
    ],
    timeout: 10000,
    safetyTimeout: 15000,
    prefetchOnHover: true,
    maxRetries: 1,
    debug: false,
  };

  // Path yang JANGAN di-handle SPA
  const SKIP_PATTERNS = [
    /\/auth_/,
    /\/auth\//,
    /\/logout/,
    /\/login/,
    /accounts\.google\.com/,
    /discord\.com/,
    /github\.com/,
    /facebook\.com/,
  ];

  // Style yang PERMANENT — nggak boleh di-swap
  const PERMANENT_STYLES = new Set(['base', 'nav', 'loader']);

  // ============================================================
  // State
  // ============================================================
  let currentUrl = location.href;
  let isNavigating = false;
  let safetyTimer = null;

  // ============================================================
  // Utils
  // ============================================================
  function log(...args) {
    if (CONFIG.debug) console.log('[SPA]', ...args);
  }
  function warn(...args) {
    console.warn('[SPA]', ...args);
  }
  function err(...args) {
    console.error('[SPA]', ...args);
  }

  function sameOrigin(url) {
    try {
      const u = new URL(url, location.href);
      return u.origin === location.origin;
    } catch {
      return false;
    }
  }

  function shouldSkipSpa(href) {
    if (!href) return true;
    if (href.startsWith('#')) return true;
    if (href.startsWith('mailto:')) return true;
    if (href.startsWith('tel:')) return true;
    if (href.startsWith('javascript:')) return true;
    if (href.startsWith('blob:')) return true;
    if (href.startsWith('data:')) return true;
    if (!sameOrigin(href)) return true;

    for (const pattern of SKIP_PATTERNS) {
      if (pattern.test(href)) return true;
    }
    return false;
  }

  // ============================================================
  // Loading indicator
  // ============================================================
  function showLoading() {
    document.documentElement.classList.add('spa-loading');
  }
  function hideLoading() {
    document.documentElement.classList.remove('spa-loading');
  }

  function resetNavState() {
    isNavigating = false;
    if (safetyTimer) {
      clearTimeout(safetyTimer);
      safetyTimer = null;
    }
    hideLoading();
  }

  // ============================================================
  // Execute scripts setelah swap
  // ============================================================
  function runScripts(container) {
    const scripts = container.querySelectorAll('script');
    scripts.forEach(oldScript => {
      const newScript = document.createElement('script');
      for (const attr of oldScript.attributes) {
        newScript.setAttribute(attr.name, attr.value);
      }
      if (!oldScript.src && oldScript.textContent) {
        newScript.textContent = oldScript.textContent;
      }
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  // ============================================================
  // Sync styles — swap HANYA style page-specific
  // Style permanent (base, nav, loader) nggak disentuh
  // ============================================================
  function syncStyles(newDoc) {
    // 1. Sync <link rel="stylesheet">
    const currentLinks = new Set(
      Array.from(document.querySelectorAll('head > link[rel="stylesheet"]'))
        .map(l => l.getAttribute('href'))
    );
    newDoc.querySelectorAll('head > link[rel="stylesheet"]').forEach(link => {
      const href = link.getAttribute('href');
      if (href && !currentLinks.has(href)) {
        document.head.appendChild(link.cloneNode(true));
        log('Added link:', href);
      }
    });

    // 2. Kumpulkan style dari halaman baru
    const newStyles = Array.from(newDoc.querySelectorAll('head style[data-spa-style]'));
    const newIds = new Set(newStyles.map(s => s.getAttribute('data-spa-style')));

    // 3. Hapus style lama yang nggak dipakai di halaman baru (kecuali permanent)
    let removed = 0;
    document.querySelectorAll('head style[data-spa-style]').forEach(style => {
      const id = style.getAttribute('data-spa-style');
      if (PERMANENT_STYLES.has(id)) return;
      if (newIds.has(id)) return;
      style.remove();
      removed++;
      log('Removed:', id);
    });

    // 4. Tambah style baru yang belum ada
    const currentIds = new Set(
      Array.from(document.querySelectorAll('head style[data-spa-style]'))
        .map(s => s.getAttribute('data-spa-style'))
    );
    let added = 0;
    newStyles.forEach(style => {
      const id = style.getAttribute('data-spa-style');
      if (currentIds.has(id)) return;
      const clone = document.createElement('style');
      clone.setAttribute('data-spa-style', id);
      clone.textContent = style.textContent;
      document.head.appendChild(clone);
      added++;
      log('Added:', id);
    });

    // 5. Force reflow
    if (removed || added) {
      void document.body.offsetHeight;
      log(`Styles synced: -${removed} +${added}`);
    }
  }

  // ============================================================
  // Swap 1 container
  // - ada di dua halaman  → elemen lama DIGANTI utuh (atribut & listener lama ikut hilang)
  // - cuma di halaman baru → DISISIPKAN (mis. navbar saat landing → converter)
  // - cuma di halaman lama → DIHAPUS    (mis. navbar saat converter → landing)
  // ============================================================
  function swapOne(newDoc, selector) {
    const newEl = newDoc.querySelector(selector);
    const oldEl = document.querySelector(selector);

    if (!newEl && !oldEl) return false;

    if (!newEl) {
      oldEl.remove();
      log('Removed container:', selector);
      return true;
    }

    const fresh = document.importNode(newEl, true);

    if (oldEl) {
      oldEl.replaceWith(fresh);
    } else {
      insertContainer(fresh, selector);
      log('Inserted container:', selector);
    }

    runScripts(fresh);
    return true;
  }

  // Sisipkan container baru sesuai urutan di CONFIG.containers
  // (sebelum container berikutnya yang sudah ada di halaman)
  function insertContainer(el, selector) {
    const idx = CONFIG.containers.indexOf(selector);
    for (let i = idx + 1; i < CONFIG.containers.length; i++) {
      const next = document.querySelector(CONFIG.containers[i]);
      if (next) {
        next.before(el);
        return;
      }
    }
    const firstScript = document.body.querySelector(':scope > script');
    if (firstScript) firstScript.before(el);
    else document.body.appendChild(el);
  }

  // ============================================================
  // Swap semua container
  // PENTING: syncStyles DULU, baru swap DOM
  // ============================================================
  function swapContent(newDoc) {
    // 1. CSS DULU
    syncStyles(newDoc);

    // 2. Baru swap DOM
    let anySwapped = false;
    CONFIG.containers.forEach(selector => {
      const ok = swapOne(newDoc, selector);
      if (ok) anySwapped = true;
    });

    if (!anySwapped) {
      warn('No container matched, falling back to full body swap');
      document.body.innerHTML = newDoc.body.innerHTML;
      runScripts(document.body);
    }

    // 3. Title + body class
    const newTitle = newDoc.querySelector('title');
    if (newTitle) document.title = newTitle.textContent;

    const newBodyClass = newDoc.body.getAttribute('class');
    if (newBodyClass !== null) {
      document.body.setAttribute('class', newBodyClass);
    }
  }

  // ============================================================
  // Fetch HTML dari URL
  // ============================================================
  async function fetchPage(url, attempt = 0) {
    try {
      const res = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html',
        },
        signal: AbortSignal.timeout(CONFIG.timeout),
        credentials: 'same-origin',
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const html = await res.text();
      const parser = new DOMParser();
      return parser.parseFromString(html, 'text/html');

    } catch (e) {
      if (attempt < CONFIG.maxRetries) {
        warn(`Fetch failed, retrying (${attempt + 1}/${CONFIG.maxRetries}):`, e.message);
        return fetchPage(url, attempt + 1);
      }
      throw e;
    }
  }

  // ============================================================
  // Navigate
  // ============================================================
  async function navigate(url, options = {}) {
    if (isNavigating) {
      warn('Navigation already in progress, skipping:', url);
      return;
    }
    if (url === currentUrl && !options.force) {
      log('Same URL, skipping:', url);
      return;
    }

    if (shouldSkipSpa(url) && !options.force) {
      log('Skip SPA (pattern match), full redirect:', url);
      location.href = url;
      return;
    }

    isNavigating = true;
    showLoading();

    // Hide body selama swap — biar user nggak lihat flash unstyled
    const prevVis = document.body.style.visibility;
    document.body.style.visibility = 'hidden';

    safetyTimer = setTimeout(() => {
      warn('Safety timeout — resetting nav state');
      resetNavState();
      document.body.style.visibility = prevVis || '';
    }, CONFIG.safetyTimeout);

    try {
      const newDoc = await fetchPage(url);

      swapContent(newDoc);

      if (!options.replace) {
        history.pushState({ url }, '', url);
      } else {
        history.replaceState({ url }, '', url);
      }

      currentUrl = url;

      if (options.scroll !== false) {
        window.scrollTo({ top: 0, behavior: 'instant' });
      }

      // Wait 2 frame biar browser sempat re-render dengan CSS baru
      await new Promise(resolve => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
      });

      document.body.style.visibility = prevVis || '';
      resetNavState();

      window.dispatchEvent(new CustomEvent('spa:navigated', {
        detail: { url }
      }));

    } catch (e) {
      err('Navigation failed:', e);
      document.body.style.visibility = prevVis || '';
      resetNavState();
      location.href = url;
    }
  }

  // ============================================================
  // Intercept clicks (BUBBLE phase)
  // ============================================================
  document.addEventListener('click', (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (e.button !== 0) return;

    const link = e.target.closest('a, button');
    if (!link) return;
    if (link.hasAttribute('data-no-spa')) return;

    if (link.tagName === 'A') {
      if (link.getAttribute('target') === '_blank') return;
      if (link.hasAttribute('download')) return;

      const href = link.getAttribute('href');
      if (shouldSkipSpa(href)) return;

      e.preventDefault();
      navigate(link.href);
      return;
    }

    if (link.tagName === 'BUTTON') {
      const dataHref = link.getAttribute('data-href');
      if (dataHref) {
        if (shouldSkipSpa(dataHref)) return;
        e.preventDefault();
        navigate(new URL(dataHref, location.href).href);
        return;
      }

      const onclick = link.getAttribute('onclick') || '';
      const match = onclick.match(/location\.href\s*=\s*['"]([^'"]+)['"]/);
      if (match) {
        const href = match[1];
        if (shouldSkipSpa(href)) return;
        e.preventDefault();
        navigate(new URL(href, location.href).href);
      }
    }
  }, false);

  // ============================================================
  // Prefetch saat hover
  // ============================================================
  if (CONFIG.prefetchOnHover) {
    const prefetched = new Set();

    const prefetch = (href) => {
      if (prefetched.has(href)) return;
      if (href === currentUrl) return;
      if (shouldSkipSpa(href)) return;

      prefetched.add(href);
      fetch(href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      }).catch(() => {});
    };

    document.addEventListener('mouseover', (e) => {
      const link = e.target.closest('a[href]');
      if (!link) return;
      if (link.hasAttribute('data-no-spa')) return;
      if (link.getAttribute('target') === '_blank') return;

      const href = link.href;
      if ('requestIdleCallback' in window) {
        requestIdleCallback(() => prefetch(href), { timeout: 2000 });
      } else {
        setTimeout(() => prefetch(href), 200);
      }
    }, { passive: true });
  }

  // ============================================================
  // Back/Forward button
  // ============================================================
  // Klik link "#anchor" juga memicu popstate — kalau yang beda cuma hash,
  // biarkan browser scroll ke anchor (jangan fetch ulang + scroll ke atas)
  const withoutHash = (u) => u.split('#')[0];
  window.addEventListener('popstate', (e) => {
    const url = e.state?.url || location.href;
    if (withoutHash(url) === withoutHash(currentUrl)) {
      currentUrl = url;
      return;
    }
    navigate(url, { replace: true });
  });

  // ============================================================
  // Expose
  // ============================================================
  window.__spaNavigate = function (url, options) {
    return navigate(url, options);
  };

  window.__spaConfig = CONFIG;
  window.__spaSyncStyles = syncStyles;

  // ============================================================
  // Init
  // ============================================================
  history.replaceState({ url: currentUrl }, null, currentUrl);
  document.documentElement.classList.add('spa-ready');

  log('Navigation active — containers:', CONFIG.containers.join(', '));
  log('Debug mode:', CONFIG.debug);
})();