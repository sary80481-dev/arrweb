<?php
// app/views/components/plugin-modal.php — modal cara pakai plugin (buka: openPluginModal())
?>
<!-- ============================================================
     PLUGIN MODAL
============================================================ -->
<div class="plugin-modal-overlay" id="pluginModal">
  <div class="plugin-modal">
    <div class="plugin-modal-head">
      <div class="plugin-modal-title">
        
        <div>
          <div class="plugin-modal-title-main">Cara Pakai Plugin ARRR Studio</div>
          <div class="plugin-modal-title-sub">Pasang plugin sekali, lalu install GUI + script dalam 1 klik</div>
        </div>
      </div>
      <button class="plugin-modal-close" onclick="closePluginModal()">✕</button>
    </div>

    <div class="plugin-modal-body">

      <div class="plugin-step">
        <div class="plugin-step-num">1</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Download file plugin</div>
          <div class="plugin-step-desc">
            Klik tombol <strong>Download Plugin</strong> di bawah, atau copy kode plugin
            dari tab <code>Plugin</code> di panel Output. Simpan sebagai
            <code>ArrStudioImporter.lua</code>.
          </div>
        </div>
      </div>

      <div class="plugin-step">
        <div class="plugin-step-num">2</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Buka folder Plugins Roblox Studio</div>
          <div class="plugin-step-desc">
            Di Roblox Studio: tab <strong>Plugins</strong> → <strong>Plugins Folder</strong>.
            Atau buka manual:
          </div>
          <div class="plugin-path-box">
            <span class="path-text" id="pluginPathWin">%LOCALAPPDATA%\Roblox\Plugins</span>
            <button class="plugin-path-copy" onclick="copyPluginPath('pluginPathWin')">Copy</button>
          </div>
          <div class="plugin-path-box" style="margin-top:6px;">
            <span class="path-text" id="pluginPathMac">~/Documents/Roblox/Plugins</span>
            <button class="plugin-path-copy" onclick="copyPluginPath('pluginPathMac')">Copy</button>
          </div>
        </div>
      </div>

      <div class="plugin-step">
        <div class="plugin-step-num">3</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Copy file ke folder Plugins</div>
          <div class="plugin-step-desc">
            Paste file <code>ArrStudioImporter.lua</code> ke folder yang terbuka.
            Pastikan ekstensi file <code>.lua</code> (bukan <code>.txt</code>).
          </div>
        </div>
      </div>

      <div class="plugin-step">
        <div class="plugin-step-num">4</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Restart Roblox Studio</div>
          <div class="plugin-step-desc">
            <strong>Tutup total</strong> Studio (bukan minimize), lalu buka lagi.
            Plugin akan auto-load saat Studio start.
          </div>
        </div>
      </div>

      <div class="plugin-step">
        <div class="plugin-step-num">5</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Install GUI + Game Logic (cara utama)</div>
          <div class="plugin-step-desc">
            Di converter klik <strong>Export .rbxmx</strong>. Di Studio:
            klik kanan <strong>Workspace → Insert from File</strong> → pilih file <code>…_ArrUIPack.rbxmx</code>.
            Buka widget plugin → klik <strong>Install Pack</strong>. Otomatis dipindah ke:
            <ul class="plugin-install-list">
              <li><code>StarterGui</code> — ScreenGui + behavior script</li>
              <li><code>ReplicatedStorage › ArrUI › GameConfig</code> — harga, item, hadiah</li>
              <li><code>ServerScriptService › ArrUIServer</code> — remote, leaderstats, validasi</li>
              <li><code>StarterPlayerScripts › ArrUIClient</code> — tombol GUI → server</li>
            </ul>
            Bisa di-undo dengan <kbd>Ctrl+Z</kbd>.
          </div>
        </div>
      </div>

      <div class="plugin-step">
        <div class="plugin-step-num">6</div>
        <div class="plugin-step-content">
          <div class="plugin-step-title">Alternatif: Build dari Lua</div>
          <div class="plugin-step-desc">
            Copy tab <strong>Full Lua</strong> (atau <strong>Billboard</strong>), paste di widget plugin,
            lalu klik <strong>Build</strong> — UI langsung dibuat di StarterGui.
          </div>
        </div>
      </div>

      <div class="plugin-callout">
        
        <div>
          <strong>Aktifkan API Services:</strong> <code>Home → Game Settings → Security</code> →
          centang <strong>Enable Studio Access to API Services</strong>. Dibutuhkan untuk
          <code>loadstring</code> (Build) dan DataStore (simpan saldo/inventory pemain).
        </div>
      </div>

      <div class="plugin-callout" style="background: linear-gradient(90deg, rgba(96, 165, 250, 0.08), rgba(96, 165, 250, 0.02)); border-left-color: #60a5fa;">
        
        <div>
          <strong>Tanpa plugin:</strong> setelah Insert from File, pindahkan manual isi tiap folder di
          <code>ArrUIPack</code> ke service dengan nama yang sama. Petunjuknya ada di
          <code>ArrUIPack › BacaDulu</code>.
        </div>
      </div>

    </div>

    <div class="plugin-modal-foot">
      <button class="plugin-modal-btn primary" onclick="downloadFile('plugin'); closePluginModal();">
        Download Plugin
      </button>
      <button class="plugin-modal-btn ghost" onclick="closePluginModal()">
        Tutup
      </button>
    </div>
  </div>
</div>
