<?php
// app/views/pages/landing/sections/navbar.php — navbar landing (transparan → solid saat scroll)
$loggedIn = Auth::check();
?>
<header class="lx-nav" id="lxNav">
  <div class="lx-nav-inner">
    <nav class="lx-nav-links" aria-label="Menu utama">
      <button class="lx-burger" id="lxBurger" aria-label="Buka menu" aria-expanded="false">
        <span></span><span></span>
      </button>
      <a href="<?= url('converter') ?>">Converter</a>
      <a href="<?= url('library') ?>">Library</a>
      <a href="<?= url('docs') ?>">Docs</a>
      <a href="<?= url('prompt') ?>">Prompt</a>
      <?php if ($loggedIn): ?>
        <a href="#lxTools" data-no-spa>Tools <sup class="lx-new">baru</sup></a>
      <?php endif; ?>
    </nav>

    <a href="<?= url('landing') ?>" class="lx-logo" aria-label="ARRR Studio">
      <img src="<?= asset('img/logo.png') ?>" alt="">
      <span>ARRR <b>STUDIO</b></span>
    </a>

    <div class="lx-nav-actions">
      <button type="button" class="lx-icon-btn" data-theme-open aria-label="Ganti tema" title="Tema">
        <span class="lx-theme-dot"></span>
      </button>
      <?php if ($loggedIn): ?>
        <a href="<?= url('converter') ?>" class="lx-btn lx-btn--accent lx-btn--sm">Buka Studio</a>
      <?php else: ?>
        <a href="<?= url('login') ?>" class="lx-nav-login" data-no-spa>Masuk</a>
        <a href="<?= url('converter') ?>" class="lx-btn lx-btn--accent lx-btn--sm">Coba Gratis</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Menu layar penuh (mobile) -->
  <div class="lx-drawer" id="lxDrawer" aria-hidden="true">
    <a href="<?= url('converter') ?>"><span>01</span>Converter</a>
    <a href="<?= url('library') ?>"><span>02</span>Library</a>
    <a href="<?= url('docs') ?>"><span>03</span>Docs</a>
    <a href="<?= url('prompt') ?>"><span>04</span>Prompt</a>
    <?php if ($loggedIn): ?>
      <a href="<?= url('ytmp3') ?>"><span>05</span>YT → MP3</a>
      <a href="<?= url('spoofer') ?>"><span>06</span>Auto Spoof</a>
      <a href="<?= url('history') ?>"><span>07</span>Riwayat</a>
      <a href="<?= url('luaobf') ?>"><span>08</span>Lua Obfuscator</a>
    <?php else: ?>
      <a href="<?= url('login') ?>" data-no-spa><span>05</span>Masuk</a>
    <?php endif; ?>
  </div>
</header>
