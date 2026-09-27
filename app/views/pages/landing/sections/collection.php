<?php
// app/views/pages/landing/sections/collection.php — carousel koleksi template (drag / tombol / keyboard)
$featured = [
    ['shop',         'Item Shop',     'Commerce', 'Katalog + pembelian aman di server'],
    ['hud',          'Game HUD',      'HUD',      'HP/XP bar, saldo live, hotbar'],
    ['daily-reward', 'Daily Reward',  'Reward',   'Streak 7 hari, klaim 24 jam'],
    ['gacha',        'Lucky Spin',    'Commerce', 'Roda gacha, hasil diacak di server'],
    ['quest-board',  'Quest Board',   'RPG',      'Accept / Decline quest'],
    ['battle-pass',  'Battle Pass',   'Season',   'Tier, premium, claim all'],
    ['loadout',      'Loadout',       'RPG',      'Equip / Unequip per item'],
    ['settings',     'Settings',      'Menu',     'Toggle tersimpan per pemain'],
];
?>
<section class="lx-collection" id="lxCollection">
  <div class="lx-section-head">
    <div>
      <p class="lx-eyebrow lx-reveal">The Collection</p>
      <h2 class="lx-h2 lx-reveal">Template yang langsung<br>jadi game.</h2>
    </div>
    <div class="lx-carousel-ctrl lx-reveal">
      <button class="lx-round-btn" data-carousel="prev" aria-label="Sebelumnya">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <button class="lx-round-btn" data-carousel="next" aria-label="Berikutnya">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>

  <div class="lx-carousel" id="lxCarousel" tabindex="0" aria-label="Koleksi template">
    <?php foreach ($featured as $i => [$id, $name, $cat, $desc]): ?>
      <article class="lx-card" data-sample="<?= e($id) ?>">
        <div class="lx-card-media">
          <iframe src="<?= e(BASE_URL . '/samples/' . $id . '.html') ?>" loading="lazy" tabindex="-1" title="<?= e($name) ?>"></iframe>
          <span class="lx-card-index"><?= sprintf('%02d', $i + 1) ?></span>
        </div>
        <div class="lx-card-body">
          <span class="lx-card-cat"><?= e($cat) ?></span>
          <h3><?= e($name) ?></h3>
          <p><?= e($desc) ?></p>
          <button type="button" class="lx-link" data-open-sample="<?= e($id) ?>">
            Buka di Converter <span>→</span>
          </button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="lx-progress"><i id="lxCarouselBar"></i></div>
</section>
