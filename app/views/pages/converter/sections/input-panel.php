<?php
// app/views/pages/converter/sections/input-panel.php — editor HTML (kiri)
?>
<!-- ============================================================
     PANEL INPUT
============================================================ -->
<section class="panel" id="panelInput">
  <div class="panel-head">
      <span class="dot orange"></span>
      <span class="label">HTML Input</span>
      <div class="head-actions">
      <button onclick="loadSample('sidebar')" class="chip">Sidebar</button>
      <button onclick="loadSample('music')" class="chip">Music</button>
      <button onclick="clearInput()" class="chip">Clear</button>
      <label class="auto-convert" id="autoConvert" title="Convert otomatis tiap mengetik (khusus user login)" hidden>
        <input type="checkbox" onchange="setAutoConvert(this.checked)"> Auto
      </label>
      <button onclick="runConvert()" class="btn-convert" id="btnConvert" title="Convert ke Lua (Ctrl+Enter)">
        <span>Convert</span>
        <span class="btn-convert-quota" id="convertQuota" title="Sisa kuota gratis" hidden></span>
      </button>
      </div>
  </div>

  <div class="vscode-editor">
      <div class="vscode-tabs">
      <div class="vscode-tab active">
          
          <span class="vscode-tab-name">index.html</span>
          <span class="vscode-tab-close">×</span>
      </div>
      <div class="vscode-tab-actions">
          <button class="vscode-tab-action" id="btnToggleMinimap" title="Toggle Minimap">▥</button>
          <button class="vscode-tab-action" title="Split Editor">⬒</button>
          <button class="vscode-tab-action" title="More Actions">⋯</button>
      </div>
      </div>

      <div class="vscode-body">
      <div class="vscode-gutter" id="vscodeGutter">
          <div class="vscode-line-num">1</div>
      </div>

      <div class="vscode-code-wrap">
          <pre class="vscode-highlight" id="vscodeHighlight" aria-hidden="true"></pre>
          <textarea
          id="htmlIn"
          class="vscode-input"
          spellcheck="false"
          autocomplete="off"
          autocorrect="off"
          autocapitalize="off"
          placeholder="Paste HTML di sini..."
          ></textarea>
      </div>

      <div class="vscode-minimap" id="vscodeMinimap">
          <div class="vscode-minimap-content" id="vscodeMinimapContent"></div>
          <div class="vscode-minimap-viewport" id="vscodeMinimapViewport"></div>
      </div>
      </div>
  </div>

  <div class="panel-foot vscode-status">
      <div class="vscode-status-left">
      <span class="vscode-branch">⎇ main</span>
      <span class="vscode-status-item">
          <span id="vscodeErrors">0</span>
          <span class="vscode-icon-err">⊗</span>
      </span>
      <span class="vscode-status-item">
          <span id="vscodeWarnings">0</span>
          <span class="vscode-icon-warn">⚠</span>
      </span>
      </div>
      <div class="vscode-status-right">
      <span class="vscode-status-item" id="vscodeCursor">Ln 1, Col 1</span>
      <span class="vscode-status-item">Spaces: 2</span>
      <span class="vscode-status-item">UTF-8</span>
      <span class="vscode-status-item">HTML</span>
      </div>
  </div>
</section>
