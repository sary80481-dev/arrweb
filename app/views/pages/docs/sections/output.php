<?php
// app/views/pages/docs/sections/output.php
?>
<section class="docs-section" data-doc="output">
  <h1 data-anchor="output">Output Files <a class="anchor" href="#output">#</a></h1>
  <p>Semua tab di panel Output setelah klik ⚡ Convert.</p>

  <h2 data-anchor="out-rbxmx">⬇ Export .rbxmx — paket lengkap</h2>
  <div class="code-block">
    <div class="code-header"><div class="code-lang"><span class="code-dot"></span> ArrUIPack</div></div>
    <pre><code>ArrUIPack
├─ BacaDulu (StringValue)
├─ StarterGui
│  └─ ItemShopGui (ScreenGui)
│     ├─ GeneratedUI_Behavior (Script, RunContext Client)
│     └─ ItemShop (Frame, di tengah + UIScale)
├─ ReplicatedStorage › ArrUI › GameConfig (ModuleScript)
├─ ServerScriptService › ArrUIServer (Script)
└─ StarterPlayerScripts › ArrUIClient (LocalScript)</code></pre>
  </div>
  <p>Insert from File, lalu klik <strong>📦 Install Pack</strong> di plugin — semuanya pindah ke service yang benar.</p>

  <h2 data-anchor="out-tabs">Tab Output</h2>
  <table class="docs-table">
    <thead><tr><th>Tab</th><th>Isi</th></tr></thead>
    <tbody>
      <tr><td>Behavior Script</td><td>Toggle/close panel, tab, hover — sudah ada di dalam ScreenGui paket</td></tr>
      <tr><td>Full Lua</td><td>LocalScript yang membangun seluruh UI di runtime (tanpa .rbxmx)</td></tr>
      <tr><td>Tree</td><td>Hierarki Instance (debug)</td></tr>
      <tr><td>RBXMX</td><td>Isi file paket</td></tr>
      <tr><td>Plugin</td><td>Kode plugin ARRR Studio v5</td></tr>
      <tr><td>👑 Billboard</td><td>BillboardGui nametag di atas kepala pemain</td></tr>
      <tr><td>🧠 Game Logic</td><td>GameConfig / ArrUIServer / ArrUIClient + ringkasan deteksi</td></tr>
      <tr><td>Report</td><td>CSS yang tidak didukung Roblox</td></tr>
    </tbody>
  </table>

  <div class="callout info">
    <div>
      UI dengan satu root: root itu sendiri jadi container di tengah layar (tanpa Frame "Canvas" tambahan),
      di-scale otomatis sesuai layar. ScreenGui dinamai sesuai UI, mis. <code>ItemShopGui</code>.
    </div>
  </div>
</section>
