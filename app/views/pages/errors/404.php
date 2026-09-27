<?php
// app/views/pages/errors/404.php — halaman 404 custom
?>
<main class="app-main error-page">
  <div class="error-card">
    <div class="error-code">404</div>
    <div class="error-title">Halaman tidak ditemukan.</div>
    <div class="error-desc">
      URL yang kamu akses tidak ada, atau sudah dipindah.
    </div>
    <div class="error-actions">
      <a href="<?= url('converter') ?>" class="error-btn">Buka Converter</a>
      <a href="<?= url('landing') ?>" class="error-btn error-btn-ghost">Beranda</a>
    </div>
  </div>
</main>
