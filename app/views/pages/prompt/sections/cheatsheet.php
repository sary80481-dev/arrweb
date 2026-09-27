<?php
// app/views/pages/prompt/sections/cheatsheet.php — Tab 3: Cheat Sheet
?>
<div class="pg-panel" data-panel="cheatsheet">
  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">01</span>
      Cheat Sheet HTML Converter
    </div>
    <div class="pg-section-desc">
      Klik tag untuk copy ke clipboard. Merah = <strong>tidak ada di Roblox</strong> (hindari).
    </div>

    <div class="pg-cheat-grid">
      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Tag Didukung (semua tag)
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">div / section / article</span>
          <span class="pg-cheat-tag">span / p / h1–h6</span>
          <span class="pg-cheat-tag">button</span>
          <span class="pg-cheat-tag">input / textarea</span>
          <span class="pg-cheat-tag">img</span>
          <span class="pg-cheat-tag">ul / ol / li</span>
          <span class="pg-cheat-tag">table / tr / td</span>
          <span class="pg-cheat-tag">form / label</span>
          <span class="pg-cheat-tag">header / nav / footer</span>
          <span class="pg-cheat-tag">b / strong / i / em</span>
          <span class="pg-cheat-tag">br</span>
          <span class="pg-cheat-tag">data-name="..."</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head" style="color:#ef4444;">
          Tag / Fitur Dilewati
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag bad">svg (jadi Frame kosong)</span>
          <span class="pg-cheat-tag bad">canvas</span>
          <span class="pg-cheat-tag bad">video</span>
          <span class="pg-cheat-tag bad">iframe</span>
          <span class="pg-cheat-tag bad">::before / ::after</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          CSS Didukung
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">&lt;style&gt; + class</span>
          <span class="pg-cheat-tag">display: flex</span>
          <span class="pg-cheat-tag">display: grid</span>
          <span class="pg-cheat-tag">margin / padding</span>
          <span class="pg-cheat-tag">% / em / rem</span>
          <span class="pg-cheat-tag">linear-gradient</span>
          <span class="pg-cheat-tag">border (per sisi)</span>
          <span class="pg-cheat-tag">border-radius (sebagian)</span>
          <span class="pg-cheat-tag">transform: rotate()</span>
          <span class="pg-cheat-tag">opacity</span>
          <span class="pg-cheat-tag">overflow: hidden</span>
          <span class="pg-cheat-tag">overflow: auto → Scrolling</span>
          <span class="pg-cheat-tag">font-family</span>
          <span class="pg-cheat-tag">text-transform</span>
          <span class="pg-cheat-tag">display: none (class)</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head" style="color:#ef4444;">
          CSS Unsupported
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag bad">box-shadow</span>
          <span class="pg-cheat-tag bad">text-shadow</span>
          <span class="pg-cheat-tag bad">filter</span>
          <span class="pg-cheat-tag bad">backdrop-filter</span>
          <span class="pg-cheat-tag bad">clip-path</span>
          <span class="pg-cheat-tag bad">radial-gradient</span>
          <span class="pg-cheat-tag bad">transform: skew()</span>
          <span class="pg-cheat-tag bad">mix-blend-mode</span>
          <span class="pg-cheat-tag bad">background-image: url()</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Interaktif
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">id="shop-toggle"</span>
          <span class="pg-cheat-tag">id="shop-close"</span>
          <span class="pg-cheat-tag">id="shop-panel"</span>
          <span class="pg-cheat-tag">display:none</span>
          <span class="pg-cheat-tag">data-action="toggle"</span>
          <span class="pg-cheat-tag">data-target="shop-panel"</span>
          <span class="pg-cheat-tag">Buy / Sell / Equip</span>
          <span class="pg-cheat-tag">Claim / Redeem / Spin</span>
          <span class="pg-cheat-tag">Upgrade / Craft</span>
          <span class="pg-cheat-tag">Accept / Decline</span>
          <span class="pg-cheat-tag">Save</span>
          <span class="pg-cheat-tag">💎 2,500 (harga)</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Warna Gold Theme
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">#0a0a0d (bg)</span>
          <span class="pg-cheat-tag">#121216 (panel)</span>
          <span class="pg-cheat-tag">#17171d (head)</span>
          <span class="pg-cheat-tag">#d4af37 (gold)</span>
          <span class="pg-cheat-tag">#f4d03f (gold bright)</span>
          <span class="pg-cheat-tag">#c0c0c8 (silver)</span>
          <span class="pg-cheat-tag">#e8e8ec (text)</span>
          <span class="pg-cheat-tag">#8a8a96 (text dim)</span>
          <span class="pg-cheat-tag">#26262e (border)</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Emoji Icon
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">⚔️ sword</span>
          <span class="pg-cheat-tag">🛡️ shield</span>
          <span class="pg-cheat-tag">🧪 potion</span>
          <span class="pg-cheat-tag">💰 money</span>
          <span class="pg-cheat-tag">🔥 fire</span>
          <span class="pg-cheat-tag">💎 gem</span>
          <span class="pg-cheat-tag">👑 crown</span>
          <span class="pg-cheat-tag">🔮 orb</span>
          <span class="pg-cheat-tag">⭐ star</span>
          <span class="pg-cheat-tag">❤️ heart</span>
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Ukuran Desain
        </div>
        <div style="font-size:12.5px;color:var(--text-dim);line-height:1.7;">
          Area desain <code>800 × 600 px</code> (preview).<br>
          UI dengan 1 root otomatis di-center &amp; di-scale di layar pemain,<br>
          jadi cukup desain ukuran menunya saja.
        </div>
      </div>

      <div class="pg-cheat-card">
        <div class="pg-cheat-head">
          Billboard Nametag
        </div>
        <div class="pg-cheat-list">
          <span class="pg-cheat-tag">BillboardGui</span>
          <span class="pg-cheat-tag">Head (attachment)</span>
          <span class="pg-cheat-tag">StudsOffsetWorldSpace</span>
          <span class="pg-cheat-tag">AlwaysOnTop = true</span>
          <span class="pg-cheat-tag">MaxDistance = 120</span>
          <span class="pg-cheat-tag">LightInfluence = 0</span>
          <span class="pg-cheat-tag">UIListLayout</span>
          <span class="pg-cheat-tag">TextStrokeTransparency</span>
          <span class="pg-cheat-tag">Players:GetPlayers()</span>
          <span class="pg-cheat-tag">CharacterAdded</span>
        </div>
      </div>
    </div>
  </div>
</div>