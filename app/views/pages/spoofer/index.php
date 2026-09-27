<?php
// app/views/pages/spoofer/index.php — Auto Spoof massal (logic: assets/js/pages/spoofer.js)
$isLoggedIn = Auth::check();
?>
<main class="page-wrap">
  <div class="page-inner sp-inner">

    <header class="page-hero">
      <p class="page-hero-eyebrow">Auto Spoof</p>
      <h1 class="page-title">Re-upload aset massal ke akunmu.</h1>
      <p class="page-sub">
        Tempel banyak <b>asset ID</b> gambar / audio (atau pilih file), masukkan <b>API key Open Cloud</b> kamu,
        lalu semua aset di-upload ulang ke akun / grup kamu. Hasilnya daftar asset ID baru yang tinggal di-copy.
      </p>
    </header>

    <?php if (!$isLoggedIn): ?>
      <div class="sp-notice">
        Login dulu untuk memakai Auto Spoof. <a href="<?= url('login') ?>" data-no-spa>Masuk</a>
      </div>
    <?php endif; ?>

    <div class="sp-grid">

      <!-- ===== 1. AKUN ROBLOX ===== -->
      <section class="sp-card">
        <h2 class="sp-card-title"><span>01</span> Akun Roblox</h2>

        <label class="sp-label" for="spApiKey">API key Open Cloud</label>
        <div class="sp-row">
          <input type="password" id="spApiKey" class="sp-input" placeholder="Tempel API key" autocomplete="off" spellcheck="false">
          <button type="button" class="sp-btn-ghost" id="spToggleKey">Lihat</button>
        </div>
        <label class="sp-check">
          <input type="checkbox" id="spRemember"> Ingat API key di browser ini
        </label>

        <div class="sp-row sp-check-row">
          <input type="text" id="spTestId" class="sp-input" placeholder="Asset ID untuk tes (opsional)" inputmode="numeric">
          <button type="button" class="sp-btn-ghost" id="spCheck" <?= $isLoggedIn ? "" : "disabled" ?>>Cek koneksi</button>
        </div>
        <div class="sp-check-result" id="spCheckResult" hidden></div>

        <label class="sp-label">Upload ke</label>
        <div class="sp-row">
          <select id="spCreatorType" class="sp-input sp-select">
            <option value="user">User (akun saya)</option>
            <option value="group">Group</option>
          </select>
          <input type="text" id="spCreatorId" class="sp-input" placeholder="User ID / Group ID" inputmode="numeric">
        </div>

        <label class="sp-label" for="spUniverseId">Izinkan di game (opsional)</label>
        <input type="text" id="spUniverseId" class="sp-input" placeholder="Universe ID game" inputmode="numeric">
        <label class="sp-check">
          <input type="checkbox" id="spAutoGrant"> Otomatis izinkan semua aset baru ke game ini
        </label>

        <details class="sp-help">
          <summary>Cara bikin API key</summary>
          <ol>
            <li>Buka <a href="https://create.roblox.com/dashboard/credentials" target="_blank" rel="noopener">create.roblox.com → Credentials</a> → <b>Create API Key</b>.</li>
            <li>Access Permissions: tambah <b>Assets</b> (<code>asset:read</code>, <code>asset:write</code>) dan <b>Legacy Assets</b> (<code>legacy-asset:manage</code>) untuk download dari asset ID.
              Untuk "Izinkan ke game" tambah juga <b>asset-permissions</b> (<code>asset-permissions:write</code>).</li>
            <li>Accepted IP: isi IP server ini, atau <code>0.0.0.0/0</code> untuk testing.</li>
            <li>Untuk upload ke grup, API key harus dibuat dari grup tersebut.</li>
            <li>User ID ada di URL profil: roblox.com/users/<b>123456</b>/profile.</li>
          </ol>
          <p>API key hanya diteruskan ke Roblox, tidak disimpan di server.</p>
        </details>
      </section>

      <!-- ===== 2. SUMBER ===== -->
      <section class="sp-card">
        <h2 class="sp-card-title"><span>02</span> Sumber aset</h2>

        <div class="sp-tabs" role="tablist">
          <button type="button" class="sp-tab active" data-sp-tab="ids">Dari Asset ID</button>
          <button type="button" class="sp-tab" data-sp-tab="files">Dari File</button>
        </div>

        <div class="sp-pane active" data-sp-pane="ids">
          <textarea id="spIds" class="sp-input sp-textarea" spellcheck="false"
            placeholder="Satu per baris / pisah koma. Bisa juga link atau format Nama{ID}:&#10;123456789&#10;rbxassetid://987654321&#10;https://www.roblox.com/library/111222333/Nama&#10;Nama Lagu{123456789}"></textarea>
          <p class="sp-hint" id="spIdCount">0 ID terdeteksi</p>
        </div>

        <div class="sp-pane" data-sp-pane="files">
          <label class="sp-drop" id="spDrop">
            <input type="file" id="spFiles" multiple accept="image/png,image/jpeg,image/bmp,audio/mpeg,audio/ogg,audio/wav,audio/flac,.mp3,.ogg,.wav,.flac">
            <span class="sp-drop-title">Pilih / drop file</span>
            <span class="sp-drop-sub">PNG · JPG · BMP · MP3 · OGG · WAV · FLAC — maks 20MB per file</span>
          </label>
          <p class="sp-hint" id="spFileCount">0 file dipilih</p>
        </div>

        <label class="sp-label" for="spName">Nama aset (opsional)</label>
        <input type="text" id="spName" class="sp-input" placeholder="Kosong = pakai ID lama / nama file" maxlength="40">

        <div class="sp-actions">
          <button type="button" class="sp-start" id="spStart" <?= $isLoggedIn ? "" : "disabled" ?>>
            Mulai Spoof
          </button>
          <button type="button" class="sp-btn-ghost" id="spStop" disabled>Stop</button>
        </div>
      </section>
    </div>

    <!-- ===== 3. HASIL ===== -->
    <section class="sp-card sp-results">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>03</span> Hasil</h2>
        <p class="sp-progress-text" id="spProgressText">Belum ada proses</p>
      </div>
      <div class="sp-progress"><div class="sp-progress-bar" id="spProgressBar"></div></div>

      <div class="sp-table-wrap">
        <table class="sp-table">
          <thead><tr><th>#</th><th>Sumber</th><th>Status</th><th>Asset ID baru</th></tr></thead>
          <tbody id="spRows"><tr class="sp-empty"><td colspan="4">Hasil muncul di sini.</td></tr></tbody>
        </table>
      </div>

      <div class="sp-output-head">
        <label class="sp-label" for="spFormat">Format output</label>
        <select id="spFormat" class="sp-input sp-select sp-format">
          <option value="list">Sesuai input (Nama{ID})</option>
          <option value="ids">ID saja (per baris)</option>
          <option value="rbx">rbxassetid://ID</option>
          <option value="comma">ID dipisah koma</option>
          <option value="map">ID lama → ID baru</option>
          <option value="lua">Tabel Lua { [lama] = baru }</option>
        </select>
        <button type="button" class="sp-btn-ghost" id="spCopy">Copy</button>
        <button type="button" class="sp-btn-ghost" id="spDownload">Download .txt</button>
      </div>
      <textarea id="spOutput" class="sp-input sp-textarea sp-output" readonly spellcheck="false"></textarea>
    </section>

    <!-- ===== 4. IZIN GAME MASSAL ===== -->
    <section class="sp-card sp-grant">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>04</span> Izin game &amp; publik</h2>
        <p class="sp-progress-text" id="spGrantStatus"></p>
      </div>
      <p class="sp-grant-desc">
        <b>Izinkan ke game</b>: aset bisa dipakai di satu game (Universe ID). <b>Jadikan publik</b>: bisa dipakai
        siapa saja — hanya untuk gambar / decal / mesh. Audio tidak bisa dijadikan publik lewat API; untuk audio pakai
        <i>Distribute on Creator Store</i> di Creator Dashboard (butuh verifikasi ID).
        Isi <b>Universe ID</b> di card 01 (Creator Dashboard → Creations → ⋯ di game → <i>Copy Universe ID</i> — bukan
        angka di link game). Aset &amp; game harus milik akun / grup yang sama dengan API key, dan API key butuh scope
        <code>asset-permissions:write</code>.
      </p>
      <textarea id="spGrantIds" class="sp-input sp-textarea sp-grant-ids" spellcheck="false"
        placeholder="Asset ID (satu per baris / pisah koma) — atau klik &quot;Pakai hasil di atas&quot;"></textarea>
      <div class="sp-output-head">
        <button type="button" class="sp-start sp-grant-btn" id="spGrant" <?= $isLoggedIn ? "" : "disabled" ?>>Izinkan semua ke game</button>
        <button type="button" class="sp-btn-ghost" id="spPublic" <?= $isLoggedIn ? "" : "disabled" ?>
                title="Siapa saja bisa pakai. Hanya gambar / decal / mesh — audio tidak bisa lewat API">Jadikan publik (gambar)</button>
        <button type="button" class="sp-btn-ghost" id="spGrantFill">Pakai hasil di atas</button>
      </div>
      <div class="sp-check-result" id="spGrantResult" hidden></div>
    </section>

    <!-- ===== 5. ANIMATION → .rbxm ===== -->
    <section class="sp-card sp-anim">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>05</span> Animation → .rbxm</h2>
        <p class="sp-progress-text" id="spAnimStatus"></p>
      </div>
      <p class="sp-grant-desc">
        Tempel banyak <b>Animation ID</b> → tiap animasi diunduh jadi file <code>.rbxm</code> (bisa langsung banyak).
        Upload-nya <b>manual di Studio</b>: drag file → klik kanan → <i>Save to Roblox</i> → copy ID baru.
        (Roblox tidak menyediakan API untuk upload animation, jadi langkah ini tidak bisa otomatis.)
      </p>
      <textarea id="spAnimIds" class="sp-input sp-textarea sp-grant-ids" spellcheck="false"
        placeholder="Satu Animation ID per baris / pisah koma. Bisa juga link rbxassetid:// atau roblox.com/...&#10;106898427663940&#10;rbxassetid://106898427663940"></textarea>
      <p class="sp-hint" id="spAnimCount">0 ID terdeteksi</p>

      <div class="sp-actions">
        <button type="button" class="sp-start" id="spAnimStart" <?= $isLoggedIn ? "" : "disabled" ?>>Download semua</button>
        <button type="button" class="sp-btn-ghost" id="spAnimZip" disabled>Download semua (.zip)</button>
        <button type="button" class="sp-btn-ghost" id="spAnimClear">Bersihkan</button>
      </div>

      <div class="sp-table-wrap" id="spAnimTableWrap" hidden>
        <table class="sp-table">
          <thead><tr><th>#</th><th>Animation ID</th><th>Nama</th><th>Status</th><th>File</th></tr></thead>
          <tbody id="spAnimRows"></tbody>
        </table>
      </div>
    </section>

  </div>
</main>
