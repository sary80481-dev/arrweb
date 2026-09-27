<?php
// app/views/pages/history/index.php — Riwayat Upload (logic: assets/js/pages/history.js)
// Data dari api/spoof.php action "history" — dicatat otomatis tiap upload Auto Spoof & YT → MP3
$isLoggedIn = Auth::check();
?>
<main class="page-wrap">
  <div class="page-inner sp-inner">

    <header class="page-hero">
      <p class="page-hero-eyebrow">Riwayat Upload</p>
      <h1 class="page-title">Semua aset yang sudah kamu upload.</h1>
      <p class="page-sub">
        Tercatat otomatis dari <a href="<?= url('spoofer') ?>" data-spa>Auto Spoof</a> dan
        <a href="<?= url('ytmp3') ?>" data-spa>YT → MP3</a>. Cari, copy asset ID, atau izinkan ke game sekaligus.
      </p>
    </header>

    <?php if (!$isLoggedIn): ?>
      <div class="sp-notice">
        Login dulu untuk melihat riwayat upload. <a href="<?= url('login') ?>" data-no-spa>Masuk</a>
      </div>
    <?php endif; ?>

    <section class="sp-card hs-card" id="hsRoot" data-logged-in="<?= $isLoggedIn ? '1' : '0' ?>">
      <div class="hs-toolbar">
        <input type="search" id="hsSearch" class="sp-input hs-search" placeholder="Cari nama / asset ID / video…">
        <select id="hsSource" class="sp-input sp-select hs-filter">
          <option value="">Semua sumber</option>
          <option value="ytmp3">YT → MP3</option>
          <option value="reupload">Auto Spoof (Asset ID)</option>
          <option value="file">Auto Spoof (File)</option>
        </select>
        <select id="hsGame" class="sp-input sp-select hs-filter">
          <option value="">Semua status game</option>
          <option value="no">Belum diizinkan ke game</option>
          <option value="yes">Sudah diizinkan ke game</option>
        </select>
        <select id="hsMod" class="sp-input sp-select hs-filter">
          <option value="">Semua status review</option>
          <option value="Approved">Siap dipakai</option>
          <option value="Reviewing">Masih direview</option>
          <option value="Rejected">Ditolak</option>
        </select>
        <button type="button" class="sp-btn-ghost" id="hsModCheck">Cek status review</button>
        <button type="button" class="sp-btn-ghost" id="hsRefresh">Muat ulang</button>
      </div>
      <p class="sp-hint" id="hsCount">Memuat…</p>

      <div class="sp-table-wrap hs-table-wrap">
        <table class="sp-table hs-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="hsAll" aria-label="Pilih semua"></th>
              <th>Waktu</th><th>Nama</th><th>Sumber</th><th>Asset ID</th><th>Review</th><th>Game</th>
            </tr>
          </thead>
          <tbody id="hsRows"><tr class="sp-empty"><td colspan="7">Memuat…</td></tr></tbody>
        </table>
      </div>
      <button type="button" class="sp-btn-ghost hs-more" id="hsMore" hidden>Tampilkan lebih banyak</button>
    </section>

    <section class="sp-card hs-actions">
      <h2 class="sp-card-title">Aksi <span id="hsTarget">Semua yang tampil</span></h2>

      <div class="hs-action-grid">
        <div>
          <label class="sp-label" for="hsFormat">Copy asset ID</label>
          <div class="sp-row">
            <select id="hsFormat" class="sp-input sp-select">
              <option value="rbx">rbxassetid://ID -- nama</option>
              <option value="ids">ID saja (per baris)</option>
              <option value="comma">ID dipisah koma</option>
              <option value="lua">Tabel Lua { ["nama"] = "rbxassetid://ID" }</option>
            </select>
            <button type="button" class="sp-btn-ghost" id="hsCopy">Copy</button>
            <button type="button" class="sp-btn-ghost" id="hsDownload">.txt</button>
          </div>
        </div>

        <div>
          <label class="sp-label" for="hsUniverseId">Izinkan ke game</label>
          <div class="sp-row">
            <input type="password" id="hsApiKey" class="sp-input" placeholder="API key Open Cloud" autocomplete="off" spellcheck="false">
            <input type="text" id="hsUniverseId" class="sp-input hs-universe" placeholder="Universe ID" inputmode="numeric">
            <button type="button" class="sp-start hs-grant" id="hsGrant">Izinkan</button>
          </div>
          <div class="sp-row">
            <button type="button" class="sp-btn-ghost" id="hsPublic"
                    title="Siapa saja bisa pakai. Hanya gambar / decal / mesh — audio tidak bisa lewat API">Jadikan publik (gambar)</button>
          </div>
          <p class="sp-hint">API key &amp; Universe ID diambil dari setting Auto Spoof kalau sudah disimpan.
            Universe ID: Creator Dashboard → Creations → ⋯ di game → Copy Universe ID. Audio tidak bisa dijadikan publik lewat API.</p>
        </div>
      </div>

      <div class="sp-check-result" id="hsResult" hidden></div>

      <div class="hs-danger">
        <button type="button" class="sp-btn-ghost hs-delete" id="hsDelete">Hapus dari riwayat</button>
        <span class="sp-hint">Hanya menghapus catatan di sini — aset di Roblox tidak ikut terhapus.</span>
      </div>
    </section>

  </div>
</main>
