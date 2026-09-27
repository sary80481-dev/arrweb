<?php
// app/views/pages/ytmp3/index.php — YT → MP3 massal (logic: assets/js/pages/ytmp3.js)
// Pakai komponen .sp-* dari spoofer.css + tambahan di ytmp3.css
$isLoggedIn = Auth::check();
$maxBatch   = (int)config('app.ytmp3.max_batch', 50);
$maxMinutes = intdiv((int)config('app.ytmp3.max_duration', 1800), 60);
?>
<main class="page-wrap">
  <div class="page-inner sp-inner">

    <header class="page-hero">
      <p class="page-hero-eyebrow">YT → MP3</p>
      <h1 class="page-title">Convert banyak link YouTube ke MP3 sekaligus.</h1>
      <p class="page-sub">
        Tempel link video (atau playlist), pilih kualitas, lalu semua dikonversi paralel.
        Download satu-satu atau <b>semuanya dalam satu ZIP</b>.
      </p>
    </header>

    <?php if (!$isLoggedIn): ?>
      <div class="sp-notice">
        Login dulu untuk memakai YT → MP3. <a href="<?= url('login') ?>" data-no-spa>Masuk</a>
      </div>
    <?php endif; ?>

    <!-- ===== TOOLS (yt-dlp + ffmpeg) ===== -->
    <section class="yt-tools" id="ytTools" data-state="loading">
      <div class="yt-tools-status">
        <span class="yt-tools-title">Tools</span>
        <span class="yt-tool" id="ytToolYtdlp"><i></i> yt-dlp <b>cek…</b></span>
        <span class="yt-tool" id="ytToolFfmpeg"><i></i> ffmpeg <b>cek…</b></span>
      </div>
      <div class="yt-tools-actions">
        <button type="button" class="sp-start yt-tools-btn" id="ytToolsInstall" hidden>Install otomatis</button>
        <button type="button" class="sp-btn-ghost yt-tools-btn" id="ytToolsUpdate" hidden>Update yt-dlp</button>
        <button type="button" class="sp-btn-ghost yt-tools-btn" id="ytToolsRefresh">Cek ulang</button>
      </div>
      <p class="yt-tools-msg" id="ytToolsMsg"></p>
      <details class="sp-help yt-tools-help" id="ytToolsHelp" hidden>
        <summary>Cara install manual (Windows / XAMPP)</summary>
        <ol>
          <li>Download <a href="https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe" target="_blank" rel="noopener">yt-dlp.exe</a>.</li>
          <li>Download <a href="https://github.com/yt-dlp/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-win64-gpl.zip" target="_blank" rel="noopener">ffmpeg (win64 zip)</a>, extract, ambil <code>ffmpeg.exe</code> &amp; <code>ffprobe.exe</code> dari folder <code>bin</code>.</li>
          <li>Taruh ketiga file di folder project <code>storage\bin\</code> (buat foldernya kalau belum ada).</li>
          <li>Klik <b>Cek ulang</b>. Tidak perlu setting PATH / restart.</li>
        </ol>
        <p>Linux: <code>sudo apt install ffmpeg</code> · macOS: <code>brew install ffmpeg yt-dlp</code>.</p>
      </details>
    </section>

    <div class="sp-grid yt-grid">

      <!-- ===== 1. LINK ===== -->
      <section class="sp-card">
        <h2 class="sp-card-title"><span>01</span> Link YouTube</h2>

        <textarea id="ytLinks" class="sp-input sp-textarea yt-links" spellcheck="false"
          placeholder="Satu link per baris (atau pisah spasi / koma):&#10;https://www.youtube.com/watch?v=dQw4w9WgXcQ&#10;https://youtu.be/9bZkp7q19f0&#10;https://youtube.com/shorts/xxxxxxxxxxx&#10;https://www.youtube.com/playlist?list=PL..."></textarea>
        <p class="sp-hint" id="ytCount">0 video · 0 playlist terdeteksi</p>
      </section>

      <!-- ===== 2. PENGATURAN ===== -->
      <section class="sp-card">
        <h2 class="sp-card-title"><span>02</span> Pengaturan</h2>

        <label class="sp-label" for="ytBitrate">Kualitas MP3</label>
        <select id="ytBitrate" class="sp-input sp-select">
          <option value="128">128 kbps — hemat</option>
          <option value="192" selected>192 kbps — standar</option>
          <option value="256">256 kbps — tinggi</option>
          <option value="320">320 kbps — maksimal</option>
        </select>

        <label class="sp-label" for="ytParallel">Proses paralel</label>
        <select id="ytParallel" class="sp-input sp-select">
          <option value="1">1 sekaligus</option>
          <option value="2" selected>2 sekaligus</option>
          <option value="3">3 sekaligus</option>
        </select>

        <label class="sp-check">
          <input type="checkbox" id="ytAutoDownload"> Auto download tiap file selesai
        </label>

        <details class="sp-help">
          <summary>Batasan</summary>
          <ol>
            <li>Maks <b><?= $maxBatch ?></b> video sekali proses (playlist ikut dihitung).</li>
            <li>Durasi video maks <b><?= $maxMinutes ?> menit</b>; live stream dilewati.</li>
            <li>Video private / member-only / dibatasi umur tidak bisa diambil.</li>
            <li>File hasil disimpan sementara (±1 jam) lalu dihapus otomatis.</li>
            <li>Gunakan hanya untuk konten yang kamu punya hak-nya.</li>
          </ol>
        </details>

        <div class="sp-actions">
          <button type="button" class="sp-start" id="ytStart" <?= $isLoggedIn ? '' : 'disabled' ?>>
            Convert Semua
          </button>
          <button type="button" class="sp-btn-ghost" id="ytStop" disabled>Stop</button>
        </div>
      </section>
    </div>

    <!-- ===== 3. AUDIO ENHANCEMENT ===== -->
    <section class="sp-card yt-enhance">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>03</span> Audio Enhancement</h2>
        <button type="button" class="sp-btn-ghost yt-reset" id="ytFxReset">Reset</button>
      </div>
      <p class="yt-desc">
        Sesuaikan speed dan pitch audio sesuai kebutuhan. Script kompensasi otomatis di-generate
        buat dipasang di game agar audio tetap terdengar normal.
      </p>

      <div class="yt-fx-grid">
        <div class="yt-fx">
          <div class="yt-fx-head">
            <label class="sp-label" for="ytSpeed">Speed</label>
            <output class="yt-fx-val" id="ytSpeedVal">x1.00</output>
          </div>
          <input type="range" id="ytSpeed" class="yt-range" min="1" max="2" step="0.05" value="1">
          <div class="yt-fx-scale"><span>x1.00</span><span>x2.00</span></div>
        </div>

        <div class="yt-fx">
          <div class="yt-fx-head">
            <label class="sp-label" for="ytPitch">Pitch Shift</label>
            <output class="yt-fx-val" id="ytPitchVal">0 semitone</output>
          </div>
          <input type="range" id="ytPitch" class="yt-range" min="-6" max="6" step="1" value="0">
          <div class="yt-fx-scale"><span>-6</span><span>+6</span></div>
        </div>
      </div>

      <div class="yt-script" id="ytScriptWrap" hidden>
        <div class="sp-output-head">
          <label class="sp-label" for="ytScript">Script kompensasi (ModuleScript Luau)</label>
          <button type="button" class="sp-btn-ghost" id="ytScriptCopy">Copy</button>
          <button type="button" class="sp-btn-ghost" id="ytScriptDownload">Download .lua</button>
        </div>
        <textarea id="ytScript" class="sp-input sp-textarea yt-script-code" readonly spellcheck="false"></textarea>
      </div>
    </section>

    <!-- ===== 4. UPLOAD KE ROBLOX (pakai Open Cloud yang sama dengan Auto Spoof) ===== -->
    <section class="sp-card yt-roblox">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>04</span> Upload ke Roblox</h2>
        <label class="sp-check yt-roblox-toggle">
          <input type="checkbox" id="ytUpload"> Langsung upload setelah convert
        </label>
      </div>
      <p class="yt-desc">
        MP3 hasil convert dikirim langsung dari server ke akun / grup Roblox kamu — tanpa download &amp; upload ulang.
        Pengaturan dipakai bersama dengan <a href="<?= url('spoofer') ?>" data-spa>Auto Spoof</a>
        (cek koneksi API key di sana).
      </p>

      <div class="yt-roblox-grid">
        <div>
          <label class="sp-label" for="ytApiKey">API key Open Cloud</label>
          <div class="sp-row">
            <input type="password" id="ytApiKey" class="sp-input" placeholder="Tempel API key" autocomplete="off" spellcheck="false">
            <button type="button" class="sp-btn-ghost" id="ytToggleKey">Lihat</button>
          </div>
          <label class="sp-check">
            <input type="checkbox" id="ytRemember"> Ingat API key di browser ini
          </label>
        </div>
        <div>
          <label class="sp-label" for="ytCreatorId">Upload ke</label>
          <div class="sp-row">
            <select id="ytCreatorType" class="sp-input sp-select">
              <option value="user">User (akun saya)</option>
              <option value="group">Group</option>
            </select>
            <input type="text" id="ytCreatorId" class="sp-input" placeholder="User ID / Group ID" inputmode="numeric">
          </div>
          <label class="sp-label" for="ytNameMax">Nama aset</label>
          <select id="ytNameMax" class="sp-input sp-select">
            <option value="20">Judul dipendekkan — maks 20 huruf</option>
            <option value="30" selected>Judul dipendekkan — maks 30 huruf</option>
            <option value="40">Judul dipendekkan — maks 40 huruf</option>
            <option value="50">Judul dipendekkan — maks 50 huruf</option>
          </select>
          <p class="sp-hint">"(Official Video)", "[HD]", "| Channel", hashtag dll. dibuang otomatis. Upload audio kena kuota bulanan &amp; review moderasi Roblox.</p>
        </div>
        <div>
          <label class="sp-label" for="ytUniverseId">Izinkan di game (opsional)</label>
          <input type="text" id="ytUniverseId" class="sp-input" placeholder="Universe ID game" inputmode="numeric">
        </div>
        <div class="yt-grant-auto">
          <label class="sp-check">
            <input type="checkbox" id="ytAutoGrant"> Otomatis izinkan semua audio baru ke game ini
          </label>
        </div>
      </div>
    </section>

    <!-- ===== 5. HASIL ===== -->
    <section class="sp-card sp-results">
      <div class="sp-results-head">
        <h2 class="sp-card-title"><span>05</span> Hasil</h2>
        <p class="sp-progress-text" id="ytProgressText">Belum ada proses</p>
      </div>
      <div class="sp-progress"><div class="sp-progress-bar" id="ytProgressBar"></div></div>

      <div class="sp-table-wrap yt-table-wrap">
        <table class="sp-table yt-table">
          <thead><tr><th>#</th><th>Video</th><th>Status</th><th>File</th><th>Roblox</th></tr></thead>
          <tbody id="ytRows"><tr class="sp-empty"><td colspan="5">Hasil muncul di sini.</td></tr></tbody>
        </table>
      </div>

      <div class="sp-output-head">
        <button type="button" class="sp-start yt-zip" id="ytZip" disabled>Download Semua (.zip)</button>
        <button type="button" class="sp-btn-ghost" id="ytRetry" disabled>Ulangi yang gagal</button>
        <button type="button" class="sp-btn-ghost" id="ytUploadAll" disabled>Upload ke Roblox</button>
        <button type="button" class="sp-btn-ghost" id="ytClear">Bersihkan</button>
      </div>

      <div class="yt-ids" id="ytIdsWrap" hidden>
        <div class="sp-output-head">
          <label class="sp-label" for="ytIdFormat">Asset ID Roblox</label>
          <select id="ytIdFormat" class="sp-input sp-select sp-format">
            <option value="rbx">rbxassetid://ID -- judul</option>
            <option value="ids">ID saja (per baris)</option>
            <option value="lua">Tabel Lua { ["judul"] = "rbxassetid://ID" }</option>
          </select>
          <button type="button" class="sp-btn-ghost" id="ytIdCopy">Copy</button>
          <button type="button" class="sp-btn-ghost" id="ytGrant">Izinkan semua ke game</button>
        </div>
        <p class="sp-hint" id="ytGrantStatus"></p>
        <p class="sp-hint" id="ytModStatus"></p>
        <textarea id="ytIds" class="sp-input sp-textarea sp-output" readonly spellcheck="false"></textarea>
      </div>
    </section>

  </div>
</main>
