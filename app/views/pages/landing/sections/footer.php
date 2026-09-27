<?php
// app/views/pages/landing/sections/footer.php — footer landing
?>
<footer class="lx-footer">
  <div class="lx-footer-top">
    <div class="lx-footer-brand">
      <a href="<?= url('landing') ?>" class="lx-logo">
        <img src="<?= asset('img/logo.png') ?>" alt="">
        <span>ARRR <b>STUDIO</b></span>
      </a>
      <p>HTML → Roblox GUI converter dengan Game Logic otomatis — plus tools audio &amp; aset untuk creator.</p>
    </div>
    <div class="lx-footer-col">
      <h4>Produk</h4>
      <a href="<?= url('converter') ?>">Converter</a>
      <a href="<?= url('library') ?>">Library</a>
      <a href="<?= url('prompt') ?>">Prompt</a>
    </div>
    <?php if (Auth::check()): ?>
    <div class="lx-footer-col">
      <h4>Tools</h4>
      <a href="<?= url('ytmp3') ?>">YT → MP3</a>
      <a href="<?= url('spoofer') ?>">Auto Spoof</a>
      <a href="<?= url('history') ?>">Riwayat Upload</a>
      <a href="<?= url('luaobf') ?>">Lua Obfuscator</a>
    </div>
    <?php endif; ?>
    <div class="lx-footer-col">
      <h4>Belajar</h4>
      <a href="<?= url('docs') ?>">Dokumentasi</a>
      <a href="<?= url('docs') ?>">Plugin &amp; Install Pack</a>
      <a href="<?= url('docs') ?>">API</a>
    </div>
    <div class="lx-footer-col">
      <h4>Tampilan</h4>
      <button type="button" class="lx-link" data-theme-open>Ganti tema <span>→</span></button>
    </div>
  </div>
  <div class="lx-footer-bottom">
    <span>© <?= date('Y') ?> ARRR Studio</span>
    <span>Dibuat untuk developer Roblox</span>
  </div>
</footer>
