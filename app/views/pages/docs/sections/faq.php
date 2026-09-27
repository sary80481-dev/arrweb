<?php
// app/views/pages/docs/sections/faq.php
?>
<section class="docs-section" data-doc="faq">
  <h1 data-anchor="faq">FAQ <a class="anchor" href="#faq">#</a></h1>
  <p>Pertanyaan yang sering ditanyakan.</p>

  <div class="faq-item">
    <div class="faq-q"><span>Kenapa preview Roblox sedikit beda dari HTML?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Hampir semua CSS sudah dikonversi (flex, grid, gradient, rotate, border per sisi, radius sebagian).
      Yang tidak ada di Roblox: shadow, filter, blur, radius elips, skew. Cek tab <code>Report</code>.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-q"><span>Harus pakai position:absolute?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Tidak. Layout apa pun (flow, flex, grid, margin auto) dibaca dari hasil render browser.
      Flex/grid yang rapi bahkan jadi <code>UIListLayout</code> / <code>UIGridLayout</code>.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-q"><span>Posisi di Studio jadi 0,0 semua?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Itu bug export .rbxmx versi lama (properti tidak tertulis). Convert ulang lalu Export .rbxmx lagi.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-q"><span>Tombol di GUI tidak melakukan apa-apa?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Pastikan <code>ArrUIServer</code>, <code>GameConfig</code>, dan <code>ArrUIClient</code> ikut terpasang
      (📦 Install Pack), lalu test dengan <strong>Play</strong>. Output log: <code>[ArrUI] Server siap</code>.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-q"><span>Image tidak muncul di Roblox?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Roblox butuh asset ID (<code>rbxassetid://123456</code>). Upload gambar ke Roblox, lalu ganti properti
      <code>Image</code>.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-q"><span>Kuota gratis habis tiba-tiba?</span><span class="faq-arrow"></span></div>
    <div class="faq-a">
      Sekarang kuota hanya berkurang saat klik <strong>⚡ Convert</strong>. Mengetik, paste, dan load sample gratis.
      Login untuk tanpa batas.
    </div>
  </div>
</section>
