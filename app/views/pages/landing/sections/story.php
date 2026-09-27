<?php
// app/views/pages/landing/sections/story.php — alur kerja: visual sticky + langkah yang aktif saat scroll
$steps = [
    ['Tulis', 'HTML apa pun', 'Class, <code>&lt;style&gt;</code>, flex, grid, gradient, rotate — pakai cara yang sudah kamu kuasai, atau minta AI lewat halaman Prompt.'],
    ['Convert', 'Satu klik', 'Browser menghitung layout &amp; style asli. Preview Roblox langsung tampil identik dengan hasil di Studio.'],
    ['Logic', 'Script otomatis', 'Tombol Buy, Claim, Equip, Redeem, Spin dikenali — lengkap dengan remote, leaderstats, dan validasi server.'],
    ['Install', 'Satu paket', 'Export .rbxmx, Insert from File, klik Install Pack. GUI + GameConfig + Server + Client terpasang.'],
];
?>
<section class="lx-story" id="lxStory">
  <div class="lx-story-visual" aria-hidden="true">
    <div class="lx-story-frame">
      <div class="lx-story-scene is-active" data-scene="0">
        <div class="lx-scene-code">
          <p><b>&lt;div</b> class="card"<b>&gt;</b></p>
          <p class="i1"><b>&lt;h3&gt;</b>Dragon Sword<b>&lt;/h3&gt;</b></p>
          <p class="i1"><b>&lt;button&gt;</b>Buy<b>&lt;/button&gt;</b></p>
          <p><b>&lt;/div&gt;</b></p>
          <span class="lx-caret"></span>
        </div>
      </div>
      <div class="lx-story-scene" data-scene="1">
        <div class="lx-scene-convert">
          <div class="lx-scene-card"><b>Dragon Sword</b><span>2,500 💎</span><em>Buy</em></div>
          <div class="lx-scene-arrow">→</div>
          <div class="lx-scene-card lx-scene-card--rbx"><b>Dragon Sword</b><span>2,500 💎</span><em>Buy</em></div>
        </div>
      </div>
      <div class="lx-story-scene" data-scene="2">
        <div class="lx-scene-logic">
          <div><i>M</i>GameConfig<small>ReplicatedStorage</small></div>
          <div><i>S</i>ArrUIServer<small>ServerScriptService</small></div>
          <div><i>C</i>ArrUIClient<small>StarterPlayerScripts</small></div>
        </div>
      </div>
      <div class="lx-story-scene" data-scene="3">
        <div class="lx-scene-install">
          <div class="lx-install-btn">Install Pack</div>
          <div class="lx-install-ok">✓ Terpasang 4 item</div>
        </div>
      </div>
    </div>
  </div>

  <ol class="lx-story-steps">
    <?php foreach ($steps as $i => [$kicker, $title, $desc]): ?>
      <li class="lx-step <?= $i === 0 ? 'is-active' : '' ?>" data-step="<?= $i ?>">
        <span class="lx-step-num"><?= sprintf('%02d', $i + 1) ?></span>
        <p class="lx-eyebrow"><?= e($kicker) ?></p>
        <h3><?= e($title) ?></h3>
        <p><?= $desc ?></p>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
