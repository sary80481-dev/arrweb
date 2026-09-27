<?php
// app/views/pages/docs/sections/api.php
?>
<section class="docs-section" data-doc="api">
  <h1 data-anchor="api">API Reference <a class="anchor" href="#api">#</a></h1>
  <p>Kalau mau integrasi dengan tool lain, pakai endpoint berikut.</p>

  <h2 data-anchor="api-convert">POST /api/convert.php</h2>
  <p>
    HTML → node tree. <code>html</code> berisi elemen bertanda <code>data-arrr-idx</code>, dan tiap entri
    <code>rectMap</code> membawa posisi + computed style dari browser (lihat <code>measureRectMap()</code> di
    <code>assets/js/pages/converter.js</code>). Tanpa <code>style</code>, parser memakai mode lama (inline style + urutan).
    Guest dibatasi 3 request (HTTP 429 setelahnya).
  </p>
  <div class="code-block">
    <div class="code-header">
      <div class="code-lang"><span class="code-dot"></span> JSON Request</div>
      <button class="code-copy" data-copy>Copy</button>
    </div>
    <pre><code>{
  "html": "&lt;div data-arrr-idx=\"0\" class=\"card\"&gt;...&lt;/div&gt;",
  "rectMap": [
    { "idx": 0, "parentIdx": -1, "x": 220, "y": 24, "w": 360, "h": 244,
      "rotation": 0, "selfHidden": false, "text": "", "rich": "",
      "style": { "display": "block", "background-image": "linear-gradient(...)", "border-top-left-radius": "16px" } }
  ]
}</code></pre>
  </div>

  <h2 data-anchor="api-generate">POST /api/generate.php</h2>
  <p>Node → semua output (GUI, paket, Game Logic).</p>
  <div class="code-block">
    <div class="code-header">
      <div class="code-lang"><span class="code-dot"></span> JSON Request</div>
      <button class="code-copy" data-copy>Copy</button>
    </div>
    <pre><code>{ "nodes": [ ... ], "canvasW": 800, "canvasH": 600, "billboard": { "name": "Player" } }</code></pre>
  </div>

  <h2 data-anchor="api-response">Response</h2>
  <div class="code-block">
    <div class="code-header">
      <div class="code-lang"><span class="code-dot"></span> JSON Response</div>
      <button class="code-copy" data-copy>Copy</button>
    </div>
    <pre><code>{
  "script": "-- behavior", "fullscript": "-- full lua", "tree": "...",
  "rbxmx": "&lt;roblox&gt; ArrUIPack ...", "plugin": "-- plugin v5",
  "billboard": "-- nametag", "report": "...",
  "module": "-- GameConfig", "server": "-- ArrUIServer", "client": "-- ArrUIClient",
  "logicSummary": "7 item · Gems · Purchase",
  "ui": { "name": "ItemShop", "gui": "ItemShopGui", "singleRoot": true },
  "nodes": [ ... ]
}</code></pre>
  </div>
</section>
