<?php
// app/views/pages/docs/index.php — dokumentasi (sidebar + konten; logic: assets/js/pages/docs.js)
?>
<main class="docs-wrap">
  <div class="reading-progress" id="readingProgress"></div>
  <div class="docs-overlay" id="docsOverlay"></div>

  <?php View::partial('pages/docs/sections/sidebar'); ?>

  <div class="docs-main" id="docsMain">
    <button type="button" class="docs-menu-btn" id="docsHamburger" aria-label="Buka daftar isi">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Daftar isi
    </button>

    <div class="docs-inner">
      <?php View::partial('pages/docs/sections/intro'); ?>
      <?php View::partial('pages/docs/sections/quickstart'); ?>
      <?php View::partial('pages/docs/sections/html'); ?>
      <?php View::partial('pages/docs/sections/actions'); ?>
      <?php View::partial('pages/docs/sections/logic'); ?>
      <?php View::partial('pages/docs/sections/output'); ?>
      <?php View::partial('pages/docs/sections/plugin'); ?>
      <?php View::partial('pages/docs/sections/api'); ?>
      <?php View::partial('pages/docs/sections/faq'); ?>
    </div>
  </div>
</main>
