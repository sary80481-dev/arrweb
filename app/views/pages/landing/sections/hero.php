<?php
// app/views/pages/landing/sections/hero.php — hero sinematik layar penuh + mockup parallax
?>
<section class="lx-hero" id="lxHero">
  <div class="lx-hero-bg" aria-hidden="true">
    <div class="lx-aurora"></div>
    <div class="lx-grid-floor"></div>
    <div class="lx-vignette"></div>
  </div>

  <div class="lx-hero-content">
    <a href="#lxTools" class="lx-hero-badge lx-reveal" data-no-spa>
      <b>Baru</b> YT → MP3, Auto Spoof &amp; Riwayat Upload <span>→</span>
    </a>
    <p class="lx-eyebrow lx-reveal">ARRR Studio · HTML → Roblox</p>
    <h1 class="lx-hero-title lx-reveal">
      Desain di web.<br>
      <em>Main di Roblox.</em>
    </h1>
    <p class="lx-hero-sub lx-reveal">
      Tulis HTML &amp; CSS yang sudah kamu kuasai. ARRR Studio mengubahnya jadi GUI Roblox
      yang identik — lengkap dengan script game, siap install dalam satu klik.
    </p>
    <div class="lx-hero-cta lx-reveal">
      <a href="<?= url('converter') ?>" class="lx-btn lx-btn--accent lx-magnetic">
        Mulai Convert
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a href="#lxCollection" class="lx-btn lx-btn--ghost lx-magnetic" data-no-spa>Lihat Koleksi</a>
    </div>
  </div>

  <!-- Mockup: editor HTML → layar game Roblox -->
  <div class="lx-stage lx-reveal" id="lxStage" aria-hidden="true">
    <div class="lx-window lx-window--code">
      <div class="lx-window-bar"><i></i><i></i><i></i><span>shop.html</span></div>
      <pre class="lx-code"><span class="t">&lt;div</span> <span class="a">class</span>=<span class="s">"card"</span><span class="t">&gt;</span>
  <span class="t">&lt;h3&gt;</span>Dragon Sword<span class="t">&lt;/h3&gt;</span>
  <span class="t">&lt;p</span> <span class="a">class</span>=<span class="s">"price"</span><span class="t">&gt;</span>💎 2,500<span class="t">&lt;/p&gt;</span>
  <span class="t">&lt;button&gt;</span>Buy<span class="t">&lt;/button&gt;</span>
<span class="t">&lt;/div&gt;</span></pre>
    </div>

    <div class="lx-window lx-window--game">
      <div class="lx-game-sky"></div>
      <div class="lx-game-shop">
        <div class="lx-game-head"><b>ITEM SHOP</b><span>💎 12,450</span></div>
        <div class="lx-game-cards">
          <div class="lx-game-card"><i>⚔️</i><b>Dragon Sword</b><span>2,500 💎</span><em>Buy</em></div>
          <div class="lx-game-card"><i>🛡️</i><b>Ice Shield</b><span>1,200 💎</span><em>Buy</em></div>
          <div class="lx-game-card"><i>🔮</i><b>Magic Orb</b><span>3,800 💎</span><em>Buy</em></div>
        </div>
      </div>
      <div class="lx-game-toast">✓ Berhasil beli Dragon Sword</div>
    </div>
  </div>

  <a href="#lxNumbers" class="lx-scroll-cue" data-no-spa aria-label="Scroll">
    <span>Scroll</span><i></i>
  </a>
</section>
