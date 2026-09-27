<?php
// app/views/pages/prompt/sections/rules.php — Tab 2: Aturan Converter
?>
<div class="pg-panel" data-panel="rules">
  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">01</span>
      Aturan Converter ARRR Studio v5
    </div>
    <div class="pg-section-desc">
      Converter membaca <strong>hasil render browser</strong>, jadi HTML/CSS modern (class, flex, grid) boleh dipakai.
      Aturan di bawah bikin hasil di Roblox <strong>identik</strong> dan <strong>Game Logic</strong> terdeteksi otomatis.
    </div>

    <div class="pg-section-title" style="margin-top:24px;">
      <span class="num">02</span>
      Boleh &amp; Disarankan
    </div>

    <div class="pg-cheat-grid">
      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Layout</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          <code>&lt;style&gt;</code> + class, <code>display:flex</code>, <code>grid</code>, margin, padding, <code>%</code>, <code>em</code><br>
          Satu elemen root pembungkus UI<br>
          Muat di area desain <strong>800 × 600</strong> px
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Visual</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          Warna solid &amp; <code>linear-gradient</code><br>
          <code>border</code> (boleh per sisi), <code>border-radius</code> (boleh sebagian, mis. atas saja)<br>
          <code>transform: rotate()</code>, <code>opacity</code>, <code>overflow:hidden</code>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Teks</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          <code>&lt;b&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;span style="color"&gt;</code>, <code>&lt;br&gt;</code> → RichText<br>
          Font: Inter, Poppins, Montserrat, Roboto, Arial, Georgia, monospace<br>
          <code>text-transform</code>, <code>text-align</code>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Game Logic</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          <code>&lt;button&gt;</code> dengan teks jelas: Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Craft, Accept, Decline, Save<br>
          Harga sebagai teks: <code>💎 2,500</code>, <code>🪙 900</code>, <code>$0.99</code><br>
          Saldo pemain di header: <code>💎 12,450</code>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Card Item</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          Satu container per item: ikon, <strong>nama</strong>, rarity (Common/Rare/Epic/Legendary), <strong>harga</strong><br>
          Tombol Buy di dalam card (opsional — card sendiri bisa diklik)
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">Panel &amp; Interaksi</div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          Panel tersembunyi: <code>display:none</code> (inline / class) + <code>id="shop-panel"</code><br>
          Tombol <code>id="shop-toggle"</code> / <code>id="shop-close"</code><br>
          Switch: pill ±44×24 radius penuh + 1 knob bulat<br>
          Kode redeem: <code>&lt;input&gt;</code> di samping tombol Redeem<br>
          <code>data-name="..."</code> = nama Instance di Studio
        </div>
      </div>
    </div>

    <div class="pg-section-title" style="margin-top:32px;">
      <span class="num">03</span>
      Hindari (tidak ada di Roblox)
    </div>

    <div class="pg-cheat-grid">
      <div class="pg-cheat-card">
        <div class="pg-cheat-head" style="color:#ef4444;">
          Efek
        </div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          <code>box-shadow</code>, <code>text-shadow</code>, <code>filter</code>, <code>backdrop-filter</code>,
          <code>clip-path</code>, <code>mix-blend-mode</code>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head" style="color:#ef4444;">
          Bentuk
        </div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          <code>radial-gradient</code> (jadi warna rata-rata), radius elips pada kotak pipih,
          <code>transform: skew()</code>, pseudo-element <code>::before</code>/<code>::after</code>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head" style="color:#ef4444;">
          Aset
        </div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          Gambar URL web (Roblox butuh <code>rbxassetid://</code>) — pakai emoji / bentuk CSS<br>
          SVG, canvas, video (jadi Frame kosong)
        </div>
      </div>
    </div>

    <div class="pg-section" style="margin-top:32px;">
      <div class="pg-section-title">
        <span class="num">04</span>
        Contoh: Kurang vs Bagus
      </div>

      <div class="pg-compare">
        <div class="pg-compare-card bad">
          <div class="pg-compare-head">Kurang (logic tidak terdeteksi, efek hilang)</div>
          <div class="pg-compare-body">&lt;div class="card" style="box-shadow:0 8px 24px #0008"&gt;
  &lt;img src="https://.../sword.png"&gt;
  &lt;div&gt;Sword&lt;/div&gt;
  &lt;div class="icon-btn"&gt;🛒&lt;/div&gt;
&lt;/div&gt;</div>
        </div>
        <div class="pg-compare-card good">
          <div class="pg-compare-head">Bagus (identik + Game Logic otomatis)</div>
          <div class="pg-compare-body">&lt;div class="card"&gt;
  &lt;div class="icon"&gt;⚔️&lt;/div&gt;
  &lt;div class="name"&gt;Dragon Sword&lt;/div&gt;
  &lt;div class="rarity"&gt;Legendary&lt;/div&gt;
  &lt;div class="price"&gt;💎 2,500&lt;/div&gt;
  &lt;button class="buy"&gt;Buy&lt;/button&gt;
&lt;/div&gt;</div>
        </div>
      </div>
    </div>
  </div>
</div>
