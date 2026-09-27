<?php
// app/views/pages/docs/sections/intro.php
?>
<section class="docs-section active" data-doc="intro">

  <div class="docs-hero">
    <div class="docs-hero-eyebrow">Version 5 · Stable</div>
    <h1 data-anchor="intro">ARRR Studio Documentation</h1>
    <p>
      Tool konversi <strong>HTML → Roblox StarterGui</strong>. Tulis HTML/CSS biasa
      (class, <code>&lt;style&gt;</code>, flex, grid, gradient, transform), preview live, lalu export
      satu paket <code>.rbxmx</code> berisi GUI <em>plus</em> ModuleScript, ServerScript, dan LocalScript
      yang sudah tersambung.
    </p>
  </div>

  <h2 data-anchor="intro-features">Fitur Utama</h2>
  <div class="docs-cards">
    <div class="docs-card">
      <div class="docs-card-title">Live Preview</div>
      <div class="docs-card-desc">Preview HTML & Roblox berdampingan dengan zoom control.</div>
    </div>
    <div class="docs-card">
      <div class="docs-card-title">VSCode Editor</div>
      <div class="docs-card-desc">Syntax highlight, line numbers, minimap, dan cursor tracking.</div>
    </div>
    <div class="docs-card">
      <div class="docs-card-title">Paket Lengkap</div>
      <div class="docs-card-desc">Satu file .rbxmx: GUI + GameConfig + Server + Client. Install 1 klik lewat plugin.</div>
    </div>
    <div class="docs-card">
      <div class="docs-card-title">Game Logic Otomatis</div>
      <div class="docs-card-desc">Tombol Buy/Claim/Equip/Redeem/Spin dll. otomatis dibuatkan remote & handler server yang aman.</div>
    </div>
  </div>

  <h2 data-anchor="intro-quick">Quick Navigation</h2>
  <div class="quick-nav">
    <a class="quick-nav-item" data-doc-nav="quickstart">
      <div>
        <div class="quick-nav-label">Get Started</div>
        <div class="quick-nav-title">Quick Start Guide</div>
      </div>
    </a>
    <a class="quick-nav-item" data-doc-nav="html">
      <div>
        <div class="quick-nav-label">Reference</div>
        <div class="quick-nav-title">HTML & CSS Support</div>
      </div>
    </a>
    <a class="quick-nav-item" data-doc-nav="actions">
      <div>
        <div class="quick-nav-label">Reference</div>
        <div class="quick-nav-title">Actions & Toggle</div>
      </div>
    </a>
    <a class="quick-nav-item" data-doc-nav="logic">
      <div>
        <div class="quick-nav-label">Fitur</div>
        <div class="quick-nav-title">Game Logic</div>
      </div>
    </a>
    <a class="quick-nav-item" data-doc-nav="plugin">
      <div>
        <div class="quick-nav-label">Advanced</div>
        <div class="quick-nav-title">Plugin &amp; Install Pack</div>
      </div>
    </a>
  </div>

  <div class="callout">
    <div>
      <strong>Tip:</strong> Paste HTML di panel kiri — preview HTML tampil langsung.
      Klik <strong>⚡ Convert</strong> (atau <kbd>Ctrl+Enter</kbd>) untuk generate Lua dan
      lihat mode <strong>Roblox</strong> yang identik dengan hasil di Studio.
    </div>
  </div>
</section>