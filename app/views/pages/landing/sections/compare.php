<?php
// app/views/pages/landing/sections/compare.php — slider HTML ⇄ Roblox Studio (drag / keyboard)
?>
<section class="lx-compare">
  <div class="lx-section-head lx-section-head--center">
    <p class="lx-eyebrow lx-reveal">Pixel-perfect</p>
    <h2 class="lx-h2 lx-reveal">Yang kamu tulis,<br>itu yang ada di Studio.</h2>
    <p class="lx-lead lx-reveal">Geser untuk melihat HTML berubah jadi hierarki Instance Roblox — nama rapi, layout asli, script tersambung.</p>
  </div>

  <div class="lx-slider lx-reveal" id="lxSlider" style="--pos:50%">
    <div class="lx-slider-pane lx-slider-pane--html">
      <span class="lx-slider-label">HTML</span>
<pre><span class="t">&lt;div</span> <span class="a">class</span>=<span class="s">"shop"</span><span class="t">&gt;</span>
  <span class="t">&lt;header&gt;</span>
    <span class="t">&lt;h2&gt;</span>🏪 Item Shop<span class="t">&lt;/h2&gt;</span>
    <span class="t">&lt;span</span> <span class="a">class</span>=<span class="s">"balance"</span><span class="t">&gt;</span>💎 12,450<span class="t">&lt;/span&gt;</span>
  <span class="t">&lt;/header&gt;</span>
  <span class="t">&lt;div</span> <span class="a">class</span>=<span class="s">"grid"</span><span class="t">&gt;</span>
    <span class="t">&lt;div</span> <span class="a">class</span>=<span class="s">"card"</span><span class="t">&gt;</span>
      <span class="t">&lt;b&gt;</span>Dragon Sword<span class="t">&lt;/b&gt;</span>
      <span class="t">&lt;span&gt;</span>2,500 💎<span class="t">&lt;/span&gt;</span>
      <span class="t">&lt;button&gt;</span>Buy<span class="t">&lt;/button&gt;</span>
    <span class="t">&lt;/div&gt;</span>
    <span class="c">&lt;!-- … --&gt;</span>
  <span class="t">&lt;/div&gt;</span>
<span class="t">&lt;/div&gt;</span></pre>
    </div>

    <div class="lx-slider-pane lx-slider-pane--rbx">
      <span class="lx-slider-label lx-slider-label--right">Roblox Studio</span>
      <ul class="lx-tree">
        <li><i class="ic-gui"></i>ItemShopGui</li>
        <li class="d1"><i class="ic-frame"></i>ItemShop <small>UIScale · UICorner · UIGradient</small></li>
        <li class="d2"><i class="ic-frame"></i>ItemShopHeader</li>
        <li class="d3"><i class="ic-text"></i>ItemShopTitle</li>
        <li class="d3"><i class="ic-text"></i>Price <small>↔ leaderstats.Gems</small></li>
        <li class="d2"><i class="ic-frame"></i>Grid <small>UIGridLayout</small></li>
        <li class="d3"><i class="ic-btn"></i>DragonSwordCard</li>
        <li class="d4"><i class="ic-text"></i>DragonSwordLabel</li>
        <li class="d4"><i class="ic-text"></i>DragonSwordPrice</li>
        <li class="d4"><i class="ic-btn"></i>BuyButton <small>→ Purchase</small></li>
        <li><i class="ic-script"></i>ServerScriptService › ArrUIServer</li>
      </ul>
    </div>

    <div class="lx-slider-handle" role="slider" tabindex="0" aria-label="Geser perbandingan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
      <span>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M9 6l-6 6 6 6M15 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
    </div>
  </div>
</section>
