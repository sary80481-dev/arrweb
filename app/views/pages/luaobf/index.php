<?php
// app/views/pages/luaobf/index.php — Lua Obfuscator / Deobfuscator (logic: assets/js/pages/luaobf.js)
// Semua proses di browser — kode tidak dikirim ke server.
$isLoggedIn = Auth::check();
?>
<main class="page-wrap">
  <div class="page-inner sp-inner" id="loRoot">

    <header class="page-hero">
      <p class="page-hero-eyebrow">Lua Obfuscator</p>
      <h1 class="page-title">Obfuscate &amp; deobfuscate script Lua.</h1>
      <p class="page-sub">
        Lindungi script buatanmu, lalu kembalikan kapan saja. Format ARRR <b>reversibel penuh</b> —
        seribet apa pun, deobfuscator mengupasnya balik jadi kode Lua semula. Semua diproses
        <b>di browser</b>, kode tidak dikirim ke server.
      </p>
    </header>

    <div class="lo-grid">
      <section class="sp-card lo-pane">
        <div class="lo-pane-head">
          <h2 class="sp-card-title"><span>01</span> Input</h2>
          <span class="sp-hint" id="loInBytes">0 byte · 1 baris</span>
        </div>
        <textarea id="loInput" class="sp-input sp-textarea lo-code" spellcheck="false"
          placeholder="Tempel kode Lua (untuk obfuscate) atau hasil ter-obfuscate (untuk deobfuscate)…"></textarea>

        <div class="lo-controls">
          <label class="sp-label" for="loLayers">Layer obfuscate</label>
          <select id="loLayers" class="sp-input sp-select lo-layers">
            <option value="1">1 layer</option>
            <option value="2">2 layer</option>
            <option value="3">3 layer</option>
            <option value="4">4 layer</option>
            <option value="5">5 layer — paling ribet</option>
          </select>
        </div>

        <div class="sp-actions lo-actions">
          <button type="button" class="sp-start" id="loObfuscate" <?= $isLoggedIn ? '' : 'disabled' ?>>Obfuscate →</button>
          <button type="button" class="sp-btn-ghost" id="loDeobfuscate" <?= $isLoggedIn ? '' : 'disabled' ?>>← Deobfuscate</button>
          <button type="button" class="sp-btn-ghost" id="loClear">Bersihkan</button>
        </div>
        <?php if (!$isLoggedIn): ?>
          <div class="sp-notice">Login dulu untuk memakai Lua Obfuscator. <a href="<?= url('login') ?>" data-no-spa>Masuk</a></div>
        <?php endif; ?>
      </section>

      <section class="sp-card lo-pane">
        <div class="lo-pane-head">
          <h2 class="sp-card-title"><span>02</span> Hasil</h2>
          <span class="sp-hint" id="loOutBytes">0 byte · 1 baris</span>
        </div>
        <textarea id="loOutput" class="sp-input sp-textarea lo-code lo-out" readonly spellcheck="false"
          placeholder="Hasil muncul di sini."></textarea>
        <p class="sp-hint lo-note" id="loNote"></p>
        <div class="sp-actions lo-actions">
          <button type="button" class="sp-btn-ghost" id="loCopy">Copy</button>
          <button type="button" class="sp-btn-ghost" id="loDownload">Download .lua</button>
          <button type="button" class="sp-btn-ghost" id="loSwap">Pakai hasil sebagai input ↺</button>
        </div>
      </section>
    </div>

    <section class="sp-card lo-info">
      <h2 class="sp-card-title"><span>03</span> Cara kerja &amp; batasan</h2>
      <ul class="lo-facts">
        <li><b>Reversibel penuh</b> — hasil obfuscator ini <b>selalu</b> bisa dikembalikan ke kode Lua asli, berapa pun layer-nya.</li>
        <li><b>Tetap jalan di Roblox</b> — output berupa loader yang mendekode dirinya sendiri lalu <code>load()</code>. Perilaku script tidak berubah.</li>
        <li><b>Privat</b> — proses 100% di browser, kode kamu tidak pernah dikirim ke server.</li>
        <li><b>Obfuscator string-table</b> (gaya <code>XI/dI/II</code>) — semua string didecode statis lalu disisipkan &amp; dirapikan jadi Lua yang bisa dijalankan.</li>
        <li><b>Obfuscator bytecode-VM</b> (Luraph / MoonSec / IronBrew dsb.) — konstanta string-nya didecode &amp; disisipkan supaya terbaca, tapi <b>logikanya</b> sudah dikompilasi jadi bytecode (angka) yang dijalankan VM di dalamnya, sehingga <b>tidak bisa</b> dikembalikan ke source asli secara otomatis (butuh devirtualisasi khusus per-VM). Jaminan “pasti jadi source bersih” hanya untuk hasil obfuscator ARRR ini sendiri.</li>
        <li><b>Gunakan untuk kode milikmu sendiri</b> atau yang kamu punya izinnya.</li>
      </ul>
    </section>

  </div>
</main>
