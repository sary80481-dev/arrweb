<?php
// app/views/pages/landing/sections/marquee.php — ticker fitur yang didukung
$items = ['Flexbox', 'CSS Grid', 'Linear Gradient', 'Rotate', 'RichText', 'UIStroke', 'UICorner',
          'UIListLayout', 'UIGridLayout', 'FontFace', 'Game Logic', 'DataStore', 'Install Pack',
          'YT → MP3', 'Speed & Pitch', 'Auto Spoof', 'Izin Game', 'Status Review', 'Riwayat Upload'];
?>
<section class="lx-marquee" aria-label="Fitur yang didukung">
  <div class="lx-marquee-track">
    <?php for ($r = 0; $r < 2; $r++): ?>
      <div class="lx-marquee-row" <?= $r ? 'aria-hidden="true"' : '' ?>>
        <?php foreach ($items as $item): ?>
          <span><?= e($item) ?></span><i>✦</i>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
</section>
