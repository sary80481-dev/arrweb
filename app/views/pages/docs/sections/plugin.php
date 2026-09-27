<?php
// app/views/pages/docs/sections/plugin.php
?>
<section class="docs-section" data-doc="plugin">
  <h1 data-anchor="plugin">Plugin &amp; Install Pack <a class="anchor" href="#plugin">#</a></h1>
  <p>Plugin ARRR Studio v5 memasang paket .rbxmx ke tempat yang benar dalam 1 klik.</p>

  <h2 data-anchor="plg-install">Pasang Plugin (sekali saja)</h2>
  <ol>
    <li>Klik <strong>🔌 Plugin</strong> di navbar converter → <strong>Download Plugin</strong> (<code>ArrStudioImporter.lua</code>)</li>
    <li>Studio → tab <strong>Plugins</strong> → <strong>Plugins Folder</strong> → taruh file di sana</li>
    <li>Restart Roblox Studio — toolbar <strong>ARRR Studio</strong> muncul</li>
  </ol>

  <h2 data-anchor="plg-pack">📦 Install Pack</h2>
  <ol>
    <li>Converter → <strong>⬇ Export .rbxmx</strong></li>
    <li>Studio → klik kanan <strong>Workspace → Insert from File</strong> → pilih file</li>
    <li>Buka widget plugin → <strong>📦 Install Pack</strong></li>
  </ol>
  <p>
    Isi <code>ArrUIPack</code> dipindah ke <code>StarterGui</code>, <code>ReplicatedStorage</code>,
    <code>ServerScriptService</code>, dan <code>StarterPlayer › StarterPlayerScripts</code>.
    Instance dengan nama sama diganti, folder <code>ArrUI</code> di-merge. Bisa di-undo (<kbd>Ctrl+Z</kbd>).
  </p>

  <h2 data-anchor="plg-build">🔨 Build dari Lua</h2>
  <p>Alternatif: copy tab <strong>Full Lua</strong> atau <strong>Billboard</strong>, paste di widget, klik <strong>Build</strong>.</p>

  <div class="callout warn">
    <div>
      Aktifkan <code>Game Settings → Security → Enable Studio Access to API Services</code>
      (untuk <code>loadstring</code> di Build dan DataStore di Game Logic).
    </div>
  </div>
</section>
