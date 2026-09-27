<?php
// app/views/pages/converter/sections/output-panel.php — hasil Lua / rbxmx / plugin (kanan)
?>
<!-- ============================================================
     PANEL OUTPUT
============================================================ -->
<section class="panel" id="panelOutput">
  <div class="panel-head">
    <span class="dot blue"></span>
    <span class="label">Output</span>
    <span id="luaLines" class="mono">0 lines</span>
  </div>

  <div class="tabs">
    <button data-tab="script"     class="tab active" onclick="switchTab('script')">Behavior Script</button>
    <button data-tab="fullscript" class="tab"        onclick="switchTab('fullscript')">Full Lua</button>
    <button data-tab="tree"       class="tab"        onclick="switchTab('tree')">Tree</button>
    <button data-tab="rbxmx"      class="tab"        onclick="switchTab('rbxmx')">RBXMX</button>
    <button data-tab="plugin"     class="tab"        onclick="switchTab('plugin')">Plugin</button>
    <button data-tab="billboard"  class="tab"        onclick="switchTab('billboard')">Billboard</button>
    <button data-tab="server"     class="tab" data-group="logic" onclick="switchTab('server')">Game Logic</button>
    <button data-tab="report"     class="tab"        onclick="switchTab('report')">Report</button>
  </div>

  <!-- BILLBOARD CONFIG PANEL -->
  <div id="billboardConfig" class="billboard-config hidden">

    <div class="bb-intro">
      
      <div>
        <div><strong>Billboard Nametag</strong> — script untuk nama overhead di atas kepala player.</div>
        <div class="bb-intro-sub">Ubah config di bawah, preview akan update otomatis. Klik Generate lalu Copy ke LocalScript di StarterPlayerScripts.</div>
      </div>
    </div>

    <div class="bb-field">
      <label class="bb-label">Player Name <span class="help" data-tip="Nama yang ditampilkan. Default: 'Player'">?</span></label>
      <input type="text" id="bbName" value="Player" placeholder="Steve">
    </div>

    <div class="bb-field">
      <label class="bb-label">Role / Rank <span class="help" data-tip="Text role di bawah nama. Contoh: '[O] Owners'">?</span></label>
      <input type="text" id="bbRole" value="Member" placeholder="[O] Owners">
    </div>

    <div class="bb-field">
      <label class="bb-label">Level <span class="help" data-tip="Level player. Auto-update dari leaderstats.Level jika ada">?</span></label>
      <input type="number" id="bbLevel" value="1" min="1" max="9999">
    </div>

    <div class="bb-field">
      <label class="bb-label">Offset Y (studs) <span class="help" data-tip="Jarak di atas kepala dalam studs. 3.5 = default">?</span></label>
      <input type="number" id="bbOffsetY" value="3.5" step="0.1" min="1" max="10">
    </div>

    <div class="bb-field full">
      <label class="bb-label">Theme <span class="help" data-tip="Skema warna nametag. Pilih salah satu">?</span></label>
      <div class="bb-theme-swatches" id="bbThemeSwatches">
        <div class="bb-theme-swatch gold active" data-theme="gold">Gold</div>
        <div class="bb-theme-swatch blue" data-theme="blue">Blue</div>
        <div class="bb-theme-swatch purple" data-theme="purple">Purple</div>
      </div>
      <input type="hidden" id="bbTheme" value="gold">
    </div>

    <div class="bb-field">
      <label class="bb-label">Max Distance <span class="help" data-tip="Jarak max nametag kelihatan. 120 = default">?</span></label>
      <input type="number" id="bbMaxDistance" value="120" min="20" max="500">
    </div>

    <div class="bb-field">
      <label class="bb-label">Player <span class="help" data-tip="Local = untuk semua player. All = termasuk kamu">?</span></label>
      <select id="bbScope">
        <option value="others">Others Only (skip local)</option>
        <option value="all">All Players</option>
      </select>
    </div>

    <div class="bb-preview">
      <div class="bb-preview-label">Live Preview</div>
      <div class="bb-visual" id="bbVisual">
        <div class="bb-badges">
          <div class="bb-badge mic">🎤</div>
          <div class="bb-badge chat">💬</div>
          <div class="bb-badge crown">👑</div>
        </div>
        <div class="bb-name" id="bbVisualName" style="color: #f4d03f;">Player</div>
        <div class="bb-role" id="bbVisualRole" style="color: #c0c0c8;">Member</div>
        <div class="bb-level" id="bbVisualLevel" style="color: #22c55e;">Level 1</div>
      </div>
    </div>

    <div class="bb-actions">
      <button type="button" class="bb-btn bb-btn-primary" onclick="regenerateBillboard()">Generate Billboard</button>
      <button type="button" class="bb-btn bb-btn-ghost" onclick="copyBillboard()" title="Copy script">Copy</button>
      <button type="button" class="bb-btn bb-btn-ghost" onclick="downloadFile('billboard')" title="Download .lua">⬇ Download</button>
    </div>

    <div class="bb-status" id="bbStatus">
      <span class="bb-status-dot"></span>
      <span id="bbStatusText">Siap. Konfigurasi akan auto-generate.</span>
    </div>

  </div>

  <div class="plugin-action-bar hidden" id="pluginActionBar">
    <button class="plugin-action-btn" onclick="downloadFile('plugin')">Download Plugin</button>
    <button class="plugin-action-btn" onclick="copyCurrent()">Copy Code</button>
    <button class="plugin-action-btn ghost" onclick="openPluginModal()">Cara Pakai</button>
  </div>

  <!-- Game Logic: 3 file (ModuleScript / Script / LocalScript) -->
  <div class="logic-bar hidden" id="logicBar">
    <div class="logic-files">
      <button class="logic-file" data-logic="module" onclick="switchTab('module')" title="ReplicatedStorage > ArrUI > GameConfig">GameConfig <small>Module</small></button>
      <button class="logic-file" data-logic="server" onclick="switchTab('server')" title="ServerScriptService > ArrUIServer">ArrUIServer <small>Script</small></button>
      <button class="logic-file" data-logic="client" onclick="switchTab('client')" title="StarterPlayerScripts > ArrUIClient">ArrUIClient <small>LocalScript</small></button>
      <button class="logic-file ghost" onclick="downloadFile(currentLogicFile())" title="Download file ini">Download</button>
    </div>
    <div class="logic-meta">
      <span class="logic-path" id="logicPath"></span>
      <span class="logic-summary" id="logicSummary"></span>
    </div>
  </div>

  <div id="outputBody"></div>
  <div class="panel-foot">
    <span id="luaStatus">Ready</span>
    <span class="mono">UTF-8</span>
  </div>
</section>
