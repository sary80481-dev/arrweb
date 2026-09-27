<?php
// app/views/pages/docs/sections/actions.php
?>
<section class="docs-section" data-doc="actions">
  <h1 data-anchor="actions">Actions &amp; Toggle <a class="anchor" href="#actions">#</a></h1>
  <p>Interaksi UI (buka/tutup panel, tab, switch) dibuat otomatis di <strong>Behavior Script</strong>.</p>

  <h2 data-anchor="act-toggle">Toggle Panel</h2>
  <div class="code-block">
    <div class="code-header">
      <div class="code-lang"><span class="code-dot"></span> HTML</div>
      <button class="code-copy" data-copy>Copy</button>
    </div>
    <pre><code>&lt;button id="shop-toggle"&gt;Shop&lt;/button&gt;
&lt;div id="shop-panel" class="panel" style="display:none"&gt;
  &lt;button id="shop-close"&gt;×&lt;/button&gt;
  ...
&lt;/div&gt;</code></pre>
  </div>
  <p>
    Pola <code>xxx-toggle</code> / <code>xxx-close</code> ↔ <code>xxx-panel</code> otomatis dipasangkan.
    Bisa juga eksplisit: <code>data-action="toggle" data-target="shop-panel"</code>.
    Panel boleh di-hide lewat class CSS (<code>.panel { display:none }</code>).
  </p>

  <h2 data-anchor="act-list">Aksi yang Dikenali</h2>
  <table class="docs-table">
    <thead><tr><th>Aksi</th><th>Dideteksi dari</th><th>Hasil</th></tr></thead>
    <tbody>
      <tr><td><code>toggle</code></td><td>id/class <code>toggle</code>, <code>menu</code>, <code>-btn</code></td><td>Panel slide in/out</td></tr>
      <tr><td><code>close</code></td><td><code>close</code>, <code>dismiss</code>, <code>backdrop</code></td><td>Tutup panel target</td></tr>
      <tr><td><code>tab</code></td><td>class <code>tab</code></td><td>Ganti konten tab</td></tr>
      <tr><td><code>switch</code></td><td>Toggle switch (pill + knob)</td><td>Animasi knob + setting per pemain</td></tr>
      <tr><td>Buy / Claim / Equip / …</td><td>Teks atau id tombol</td><td>Remote ke server — lihat <a data-doc-nav="logic">Game Logic</a></td></tr>
    </tbody>
  </table>
</section>
