<?php
// app/views/pages/docs/sections/html.php
?>
<section class="docs-section" data-doc="html">
  <h1 data-anchor="html">HTML &amp; CSS Support <a class="anchor" href="#html">#</a></h1>
  <p>
    Converter membaca <strong>hasil render browser</strong> (computed style), jadi CSS dari
    <code>&lt;style&gt;</code>, class, inheritance, flex, grid, <code>%</code>, <code>em</code>, dan
    elemen yang di-hide lewat class semuanya ikut terbaca. Posisi & ukuran diambil persis dari layout browser.
  </p>

  <h2 data-anchor="html-tags">Tag → Roblox Class</h2>
  <table class="docs-table">
    <thead><tr><th>HTML</th><th>Roblox</th></tr></thead>
    <tbody>
      <tr><td>Container apa pun (<code>div</code>, <code>section</code>, <code>table</code>, <code>form</code>, <code>li</code>, …)</td><td><strong>Frame</strong> (atau <strong>TextLabel</strong> kalau berisi teks)</td></tr>
      <tr><td><code>button</code>, <code>a</code>, elemen <code>cursor:pointer</code> / <code>onclick</code> / class <code>btn</code></td><td><strong>TextButton</strong></td></tr>
      <tr><td><code>input</code>, <code>textarea</code></td><td><strong>TextBox</strong></td></tr>
      <tr><td><code>img</code></td><td><strong>ImageLabel</strong></td></tr>
      <tr><td><code>overflow: auto / scroll</code></td><td><strong>ScrollingFrame</strong></td></tr>
      <tr><td><code>b</code>, <code>strong</code>, <code>i</code>, <code>em</code>, <code>u</code>, <code>s</code>, <code>br</code>, <code>span</code> berwarna di dalam kalimat</td><td>Digabung ke parent sebagai <strong>RichText</strong></td></tr>
      <tr><td><code>svg</code>, <code>canvas</code>, <code>video</code>, <code>iframe</code></td><td>Frame placeholder seukuran aslinya</td></tr>
    </tbody>
  </table>

  <h2 data-anchor="html-css">CSS → Properti Roblox</h2>
  <table class="docs-table">
    <thead><tr><th>CSS</th><th>Hasil di Roblox</th></tr></thead>
    <tbody>
      <tr><td><code>background-color</code>, <code>opacity</code></td><td>BackgroundColor3 / Transparency</td></tr>
      <tr><td><code>linear-gradient</code> (arah, deg, stop %, multi-layer)</td><td><strong>UIGradient</strong> (warna dasar otomatis putih supaya tidak gelap)</td></tr>
      <tr><td><code>radial-gradient</code>, <code>conic-gradient</code></td><td>Warna rata-rata (UIGradient hanya linear)</td></tr>
      <tr><td><code>border</code> (semua sisi sama)</td><td><strong>UIStroke</strong>, frame di-inset supaya ukuran visual sama</td></tr>
      <tr><td><code>border-top</code> saja / warna beda per sisi</td><td>Garis Frame per sisi</td></tr>
      <tr><td><code>border-radius</code> seragam / <code>50%</code></td><td><strong>UICorner</strong></td></tr>
      <tr><td><code>border-radius: 70px 70px 0 0</code> (sebagian)</td><td>Container <code>ClipsDescendants</code> + shape diperpanjang</td></tr>
      <tr><td><code>transform: rotate()</code> / <code>scale()</code></td><td><strong>Rotation</strong> + ukuran asli</td></tr>
      <tr><td><code>color</code>, <code>font-size</code>, <code>font-weight</code>, <code>font-style</code>, <code>font-family</code></td><td>TextColor3, TextSize, <strong>FontFace</strong> (Gotham, Montserrat, Roboto, Merriweather, …)</td></tr>
      <tr><td><code>text-align</code>, <code>justify-content</code> / <code>align-items</code> (flex)</td><td>TextXAlignment / TextYAlignment</td></tr>
      <tr><td><code>text-transform</code>, <code>&amp;nbsp;</code>, <code>white-space: pre</code></td><td>Dipertahankan di teks</td></tr>
      <tr><td><code>padding</code></td><td><strong>UIPadding</strong></td></tr>
      <tr><td><code>overflow: hidden</code></td><td><strong>ClipsDescendants</strong></td></tr>
      <tr><td><code>display: flex</code> / <code>grid</code> dengan jarak seragam</td><td><strong>UIListLayout</strong> / <strong>UIGridLayout</strong> (hanya kalau posisinya identik)</td></tr>
      <tr><td><code>display:none</code>, <code>visibility:hidden</code>, <code>opacity:0</code> (inline maupun class)</td><td><code>Visible = false</code></td></tr>
    </tbody>
  </table>

  <h2 data-anchor="html-limits">Batasan Roblox</h2>
  <ul>
    <li><code>box-shadow</code>, <code>text-shadow</code>, <code>filter</code>, <code>backdrop-filter</code>, <code>clip-path</code> — tidak ada padanannya (dicatat di tab <strong>Report</strong>)</li>
    <li>Radius elips (mis. elemen 48×15 dengan <code>border-radius:50%</code>) jadi bentuk pil — UICorner hanya sudut lingkaran</li>
    <li><code>skew()</code> diabaikan; <code>ClipsDescendants</code> tidak bekerja pada frame yang diputar</li>
    <li>Gambar butuh asset ID Roblox (<code>rbxassetid://…</code>), bukan URL web</li>
  </ul>

  <div class="callout info">
    <div>
      <strong>Nama Instance</strong> diambil dari <code>data-name</code> / <code>id</code>, teks tombol & judul,
      class CSS, lalu konteks — contoh: <code>ItemShop › Grid › DragonSwordCard › DragonSwordPrice</code>.
      Pakai <code>data-name="..."</code> untuk menentukan nama sendiri.
    </div>
  </div>
</section>
