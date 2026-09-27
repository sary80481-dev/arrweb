<?php
// app/views/pages/docs/sections/quickstart.php
?>
<section class="docs-section" data-doc="quickstart">
  <h1 data-anchor="quickstart">Quick Start <a class="anchor" href="#quickstart">#</a></h1>
  <p>Dari HTML ke game Roblox yang jalan dalam 4 langkah.</p>

  <h2 data-anchor="qs-paste">1. Paste HTML</h2>
  <p>Di panel <strong>HTML Input</strong>, paste HTML/CSS apa saja — boleh pakai <code>&lt;style&gt;</code>, class, flex, grid:</p>
  <div class="code-block">
    <div class="code-header">
      <div class="code-lang"><span class="code-dot"></span> index.html</div>
      <button class="code-copy" data-copy>Copy</button>
    </div>
    <pre><code>&lt;style&gt;
  .card { width: 300px; padding: 20px; border-radius: 14px; color: #fff;
          background: linear-gradient(135deg, #7c3aed, #db2777); }
  .price { margin: 8px 0 14px; font-weight: 700; }
  .buy { padding: 10px; border: 0; border-radius: 8px; background: #111827; color: #fbbf24; }
&lt;/style&gt;
&lt;div class="card"&gt;
  &lt;h2&gt;Dragon Sword&lt;/h2&gt;
  &lt;div class="price"&gt;💎 2,500&lt;/div&gt;
  &lt;button class="buy"&gt;Buy&lt;/button&gt;
&lt;/div&gt;</code></pre>
  </div>

  <h2 data-anchor="qs-convert">2. Convert</h2>
  <p>
    Preview <strong>HTML</strong> selalu live. Klik <strong>⚡ Convert</strong> (atau <kbd>Ctrl+Enter</kbd>)
    untuk generate semua output. Mode <strong>Roblox</strong> di preview menampilkan hasil yang sama persis
    dengan di Studio (rotasi, clip, stroke, RichText).
  </p>
  <div class="callout info">
    <div>
      Guest dapat <strong>3× Convert gratis</strong> — sisa kuota tampil di tombol Convert.
      Ketik, paste, atau load sample <em>tidak</em> mengurangi kuota. Login = tanpa batas + opsi <strong>Auto</strong> convert.
    </div>
  </div>

  <h2 data-anchor="qs-export">3. Export</h2>
  <table class="docs-table">
    <thead><tr><th>Tombol</th><th>Isi</th></tr></thead>
    <tbody>
      <tr><td><strong>⬇ Export .rbxmx</strong></td><td>Paket lengkap <code>ArrUIPack</code>: GUI + GameConfig + ArrUIServer + ArrUIClient</td></tr>
      <tr><td><strong>📜 Lua</strong></td><td>Full LocalScript yang membangun UI di runtime</td></tr>
      <tr><td><strong>🔌 Plugin</strong></td><td>Plugin ARRR Studio + panduan install</td></tr>
      <tr><td><strong>📋 Copy</strong></td><td>Copy isi tab output yang sedang dibuka</td></tr>
    </tbody>
  </table>

  <h2 data-anchor="qs-install">4. Install di Studio</h2>
  <ol>
    <li>Klik kanan <strong>Workspace → Insert from File</strong> → pilih <code>…_ArrUIPack.rbxmx</code></li>
    <li>Buka plugin ARRR Studio → klik <strong>📦 Install Pack</strong></li>
    <li>Aktifkan <code>Game Settings → Security → Enable Studio Access to API Services</code> (DataStore)</li>
    <li>Klik <strong>Play</strong> — tombol di GUI langsung terhubung ke server</li>
  </ol>
</section>
