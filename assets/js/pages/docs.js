/* ============================================================
 *  assets/js/pages/docs.js — JS khusus halaman docs (SPA-ready)
 * ============================================================ */
(function () {
  'use strict';

  function initDocs() {
    // Guard: pastikan di halaman docs
    if (!document.querySelector('.docs-wrap')) return;

    // Skip kalau udah pernah init (biar nggak double listener)
    if (window.__docsInitialized) return;
    window.__docsInitialized = true;

    /* ============================================================
       NAVIGASI SECTION
       ============================================================ */
    const links    = document.querySelectorAll('.docs-link');
    const sections = document.querySelectorAll('.docs-section');
    const main     = document.getElementById('docsMain');

    function activateSection(id) {
      links.forEach(l => l.classList.toggle('active', l.dataset.doc === id));
      sections.forEach(s => s.classList.toggle('active', s.dataset.doc === id));
      if (main) main.scrollTop = 0;
      if (history.replaceState) {
        history.replaceState(null, '', '#' + id);
      }
    }

    links.forEach(link => {
      link.addEventListener('click', () => {
        activateSection(link.dataset.doc);
        closeSidebar();
      });
    });

    document.querySelectorAll('[data-doc-nav]').forEach(card => {
      card.addEventListener('click', () => {
        activateSection(card.dataset.docNav);
      });
    });

    /* ============================================================
       SEARCH FILTER
       ============================================================ */
    const searchInput = document.getElementById('docsSearch');
    const noResults   = document.getElementById('docsNoResults');
    const groupTitles = document.querySelectorAll('.docs-side-title');

    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase().trim();
        let visibleCount = 0;

        links.forEach(link => {
          const text = link.textContent.toLowerCase();
          const match = !q || text.includes(q);
          link.style.display = match ? '' : 'none';
          if (match) visibleCount++;
        });

        groupTitles.forEach(title => {
          let sib = title.nextElementSibling;
          let anyVisible = false;
          while (sib && !sib.classList.contains('docs-side-title')) {
            if (sib.classList.contains('docs-link') && sib.style.display !== 'none') {
              anyVisible = true;
              break;
            }
            sib = sib.nextElementSibling;
          }
          title.style.display = anyVisible ? '' : 'none';
        });

        if (noResults) noResults.classList.toggle('show', visibleCount === 0);
      });
    }

    /* ============================================================
       COPY BUTTON
       ============================================================ */
    document.querySelectorAll('[data-copy]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const block = btn.closest('.code-block');
        const code  = block.querySelector('pre code');
        if (!code) return;
        try {
          if (navigator.clipboard) {
            await navigator.clipboard.writeText(code.textContent);
          } else {
            const ta = document.createElement('textarea');
            ta.value = code.textContent;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
          }
          const original = btn.textContent;
          btn.textContent = 'Copied';
          btn.classList.add('copied');
          setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('copied');
          }, 1500);
        } catch (e) {
          console.error('Copy failed:', e);
        }
      });
    });

    /* ============================================================
       FAQ ACCORDION
       ============================================================ */
    document.querySelectorAll('.faq-q').forEach(q => {
      q.addEventListener('click', () => {
        const item = q.parentElement;
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(i => {
          if (i !== item) i.classList.remove('open');
        });
        item.classList.toggle('open', !isOpen);
      });
    });

    /* ============================================================
       READING PROGRESS BAR
       ============================================================ */
    const progress = document.getElementById('readingProgress');
    if (main && progress) {
      main.addEventListener('scroll', () => {
        const max = main.scrollHeight - main.clientHeight;
        const pct = max > 0 ? (main.scrollTop / max) * 100 : 0;
        progress.style.width = pct + '%';
      }, { passive: true });
    }

    /* ============================================================
       MOBILE SIDEBAR
       ============================================================ */
    const hamburger = document.getElementById('docsHamburger');
    const sidebar   = document.getElementById('docsSide');
    const overlay   = document.getElementById('docsOverlay');

    function closeSidebar() {
      sidebar?.classList.remove('open');
      overlay?.classList.remove('show');
    }
    window.closeSidebar = closeSidebar;

    hamburger?.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
    });

    overlay?.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeSidebar();
    });

    /* ============================================================
       INIT: hash URL
       ============================================================ */
    const hash = location.hash.replace('#', '');
    if (hash && document.querySelector(`.docs-link[data-doc="${hash}"]`)) {
      activateSection(hash);
    }
  }

  /* ============================================================
     EXPOSE
     ============================================================ */
  window.initDocs = initDocs;

  /* ============================================================
     AUTO-INIT
     ============================================================ */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDocs);
  } else {
    initDocs();
  }

  // Re-init setiap SPA navigation
  window.addEventListener('spa:navigated', () => {
    // Reset flag biar init jalan lagi di halaman baru
    window.__docsInitialized = false;
    initDocs();
  });

})();