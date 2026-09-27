<?php
// app/views/pages/prompt/sections/tips.php — Tab 5: Tips
?>
<div class="pg-panel" data-panel="tips">
  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">01</span>
      Tips Biar HTML Sukses Convert
    </div>
    <div class="pg-section-desc">
      Detail kecil yang bikin konversi HTML → Roblox jadi mulus.
    </div>

    <div class="pg-tips">
      <div class="pg-tip">
        <span class="pg-tip-icon">01</span>
        <div class="pg-tip-body">
          <strong>Tulis teks tombol yang jelas</strong><br>
          "Buy", "Claim Reward", "Equip", "Redeem" → otomatis jadi remote + handler server di tab Game Logic.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">02</span>
        <div class="pg-tip-body">
          <strong>Desain muat di 800×600</strong><br>
          Itu area preview. Di game, UI otomatis di-center dan di-scale sesuai layar.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">03</span>
        <div class="pg-tip-body">
          <strong>Harga &amp; saldo sebagai teks</strong><br>
          "💎 2,500" di card = harga item; "💎 12,450" di header = saldo awal pemain (leaderstats).
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">04</span>
        <div class="pg-tip-body">
          <strong>Sebutkan tag yang boleh dipakai</strong><br>
          "Cuma div, span, p, button" biar AI nggak pakai canvas/svg.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">05</span>
        <div class="pg-tip-body">
          <strong>Pakai emoji untuk icon</strong><br>
          ⚔️ 🛡️ 🧪 lebih gampang di-convert daripada img dengan URL.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">06</span>
        <div class="pg-tip-body">
          <strong>Sebutkan toggle pattern</strong><br>
          "Pakai data-action='toggle' data-target='idPanel'" biar interaktif.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">07</span>
        <div class="pg-tip-body">
          <strong>Hindari box-shadow</strong><br>
          Roblox nggak support. Pakai border + gradient biar tetap keren.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">08</span>
        <div class="pg-tip-body">
          <strong>Kasih contoh warna hex</strong><br>
          "#d4af37 untuk gold, #0a0a0d untuk background" biar AI nggak ngasal.
        </div>
      </div>

      <div class="pg-tip">
        <span class="pg-tip-icon">09</span>
        <div class="pg-tip-body">
          <strong>Billboard nametag butuh BillboardGui</strong><br>
          Bukan ScreenGui. Nempel di Head pakai StudsOffsetWorldSpace.
        </div>
      </div>

      <div class="pg-tip bad">
        <span class="pg-tip-icon">10</span>
        <div class="pg-tip-body">
          <strong>JANGAN andalkan box-shadow / blur</strong><br>
          Roblox tidak punya shadow &amp; filter. Pakai border atau warna gelap sebagai gantinya.
        </div>
      </div>

      <div class="pg-tip bad">
        <span class="pg-tip-icon">11</span>
        <div class="pg-tip-body">
          <strong>JANGAN pakai gambar URL</strong><br>
          Roblox butuh rbxassetid. Pakai emoji atau bentuk CSS; ganti Image di Studio setelah upload aset.
        </div>
      </div>

      <div class="pg-tip bad">
        <span class="pg-tip-icon">12</span>
        <div class="pg-tip-body">
          <strong>JANGAN pakai tag unsupported</strong><br>
          canvas, svg, video, iframe, form, table → di-skip converter.
        </div>
      </div>

      <div class="pg-tip bad">
        <span class="pg-tip-icon">13</span>
        <div class="pg-tip-body">
          <strong>JANGAN pakai pseudo-element</strong><br>
          ::before, ::after nggak ada di Roblox. Pakai div terpisah.
        </div>
      </div>
    </div>
  </div>
</div>