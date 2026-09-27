/* ============================================================
   assets/js/core/layout.js — UI global: plugin modal, toast, navbar
   ============================================================ */
/* ============================================================
   PLUGIN MODAL — global
   ============================================================ */
function openPluginModal() {
  const modal = document.getElementById('pluginModal');
  if (modal) modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closePluginModal() {
  const modal = document.getElementById('pluginModal');
  if (modal) modal.classList.remove('show');
  document.body.style.overflow = '';
}

function copyPluginPath(elementId) {
  const el = document.getElementById(elementId);
  if (!el) return;
  const text = el.textContent;
  const btn = el.nextElementSibling;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      if (btn) {
        const original = btn.textContent;
        btn.textContent = '✓';
        btn.classList.add('copied');
        setTimeout(() => {
          btn.textContent = original;
          btn.classList.remove('copied');
        }, 1200);
      }
    });
  }
}

/* ============================================================
   TOAST — global
   ============================================================ */
function showToast(message, type = 'success', duration = 2200) {
  const toast = document.getElementById('toast');
  const icon = document.getElementById('toastIcon');
  const text = document.getElementById('toastText');
  if (!toast) return;

  toast.className = 'toast ' + type;
  icon.textContent = type === 'error' ? '✕' : (type === 'warning' ? '⚠' : '✓');
  text.textContent = message;
  toast.classList.add('show');

  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

/* ============================================================
   NAVBAR — interactive
   ============================================================ */
(function initNavbar() {
  function setup() {
    const header      = document.getElementById('appHeader');
    const toggle      = document.getElementById('navToggle');
    const nav         = document.getElementById('topNav');
    const overlay     = document.getElementById('navOverlay');
    const profileChip = document.getElementById('profileChip');

    if (!header) return;
    if (header.dataset.navbarInit === '1') return;
    header.dataset.navbarInit = '1';

    if (toggle && nav) {
      toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle.classList.toggle('open');
        nav.classList.toggle('open');
        overlay?.classList.toggle('show');
      });
    }

    overlay?.addEventListener('click', () => {
      toggle?.classList.remove('open');
      nav?.classList.remove('open');
      overlay?.classList.remove('show');
    });

    // Dropdown Tools (klik — hover ditangani CSS di desktop)
    const tools = document.getElementById('navTools');
    const toolsToggle = tools?.querySelector('.nav-group-toggle');
    toolsToggle?.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = tools.classList.toggle('open');
      toolsToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    if (profileChip) {
      profileChip.addEventListener('click', (e) => {
        e.stopPropagation();
        profileChip.classList.toggle('open');
      });
    }
  }

  // Auto-hide navbar saat scroll ke bawah — listener dipasang SEKALI,
  // header dicari ulang tiap event (header bisa diganti oleh SPA)
  let lastScroll = 0;
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const header  = document.getElementById('appHeader');
      const current = window.scrollY;
      if (header) header.classList.toggle('hidden', current > lastScroll && current > 100);
      lastScroll = current;
      ticking = false;
    });
  }, { passive: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
  window.addEventListener('spa:navigated', setup);

  document.addEventListener('click', (e) => {
    const profileChip = document.getElementById('profileChip');
    const nav         = document.getElementById('topNav');
    const toggle      = document.getElementById('navToggle');
    const overlay     = document.getElementById('navOverlay');

    if (profileChip && !profileChip.contains(e.target)) {
      profileChip.classList.remove('open');
    }
    const tools = document.getElementById('navTools');
    if (tools && !tools.contains(e.target)) {
      tools.classList.remove('open');
      tools.querySelector('.nav-group-toggle')?.setAttribute('aria-expanded', 'false');
    }
    if (nav && !nav.contains(e.target) && !toggle?.contains(e.target)) {
      toggle?.classList.remove('open');
      nav.classList.remove('open');
      overlay?.classList.remove('show');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.getElementById('profileChip')?.classList.remove('open');
    document.getElementById('navTools')?.classList.remove('open');
    document.getElementById('navToggle')?.classList.remove('open');
    document.getElementById('topNav')?.classList.remove('open');
    document.getElementById('navOverlay')?.classList.remove('show');
    if (typeof closePluginModal === 'function') closePluginModal();
    if (typeof window.__closeGuestGate === 'function') window.__closeGuestGate();
  });
})();

/* ============================================================
   MODAL BACKDROP — click outside to close
   ============================================================ */
document.addEventListener('click', (e) => {
  const modal = document.getElementById('pluginModal');
  if (modal && e.target === modal) closePluginModal();
});

/* ============================================================
   THEME — aksen situs (Gold / Rosso / Azure / Emerald)
   Disimpan di localStorage 'arrr_theme', diterapkan di <head> sebelum render.
   ============================================================ */
(function initTheme() {
  const THEMES = [
    { id: 'gold',    label: 'Gold',    color: '#d4af37' },
    { id: 'rosso',   label: 'Rosso',   color: '#da291c' },
    { id: 'azure',   label: 'Azure',   color: '#3b82f6' },
    { id: 'emerald', label: 'Emerald', color: '#10b981' },
  ];

  function current() {
    return document.documentElement.getAttribute('data-theme') || 'gold';
  }

  function setTheme(id) {
    if (!THEMES.some(t => t.id === id)) return;
    const root = document.documentElement;
    root.classList.add('theme-switching');
    root.setAttribute('data-theme', id);
    try { localStorage.setItem('arrr_theme', id); } catch {}
    setTimeout(() => root.classList.remove('theme-switching'), 400);
    document.querySelectorAll('.theme-swatch').forEach(b => b.classList.toggle('active', b.dataset.theme === id));
    window.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme: id } }));
  }

  function closePicker() {
    const el = document.getElementById('themePicker');
    if (!el) return;
    el.classList.remove('show');
    setTimeout(() => el.remove(), 200);
    document.removeEventListener('click', outside, true);
  }

  function outside(e) {
    const el = document.getElementById('themePicker');
    if (el && !el.contains(e.target) && !e.target.closest('[data-theme-open]')) closePicker();
  }

  // anchor: elemen pemicu (popover muncul di bawahnya)
  function openThemePicker(anchor) {
    if (document.getElementById('themePicker')) { closePicker(); return; }
    const el = document.createElement('div');
    el.id = 'themePicker';
    el.className = 'theme-picker';
    el.setAttribute('role', 'dialog');
    el.innerHTML = `<div class="theme-picker-title">Theme</div>
      <div class="theme-picker-grid">${THEMES.map(t => `
        <button type="button" class="theme-swatch ${t.id === current() ? 'active' : ''}" data-theme="${t.id}" style="--swatch:${t.color}">
          <span class="theme-swatch-dot"></span>${t.label}
        </button>`).join('')}
      </div>`;
    document.body.appendChild(el);

    const r = (anchor || document.body).getBoundingClientRect();
    const left = Math.min(Math.max(12, r.right - el.offsetWidth), innerWidth - el.offsetWidth - 12);
    el.style.left = left + 'px';
    el.style.top = Math.min(r.bottom + 10, innerHeight - el.offsetHeight - 12) + 'px';
    requestAnimationFrame(() => el.classList.add('show'));

    el.addEventListener('click', (e) => {
      const b = e.target.closest('.theme-swatch');
      if (b) setTheme(b.dataset.theme);
    });
    setTimeout(() => document.addEventListener('click', outside, true), 0);
  }

  // Semua elemen [data-theme-open] membuka picker (navbar, menu profil, landing)
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-theme-open]');
    if (!trigger) return;
    e.preventDefault();
    e.stopPropagation();
    openThemePicker(trigger);
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePicker(); });
  window.addEventListener('spa:navigated', closePicker);

  window.setTheme = setTheme;
  window.openThemePicker = openThemePicker;
  window.ARRR_THEMES = THEMES;
})();
