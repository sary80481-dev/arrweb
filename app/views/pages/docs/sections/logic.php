<?php
// app/views/pages/docs/sections/logic.php
?>
<section class="docs-section" data-doc="logic">
  <h1 data-anchor="logic">Game Logic Otomatis <a class="anchor" href="#logic">#</a></h1>
  <p>
    Setelah Convert, ARRR Studio membaca GUI kamu dan membuatkan script game yang sesuai —
    tab <strong>🧠 Game Logic</strong> di panel Output, dan otomatis ikut di file <code>.rbxmx</code>.
  </p>

  <h2 data-anchor="logic-files">3 File yang Dibuat</h2>
  <table class="docs-table">
    <thead><tr><th>File</th><th>Lokasi</th><th>Isi</th></tr></thead>
    <tbody>
      <tr><td><strong>GameConfig</strong> (ModuleScript)</td><td><code>ReplicatedStorage › ArrUI</code></td><td>Mata uang + saldo awal, katalog item (nama, harga, rarity), remote, hadiah, kode redeem</td></tr>
      <tr><td><strong>ArrUIServer</strong> (Script)</td><td><code>ServerScriptService</code></td><td>RemoteFunction, leaderstats, Inventory, DataStore, validasi harga/saldo, rate limit</td></tr>
      <tr><td><strong>ArrUIClient</strong> (LocalScript)</td><td><code>StarterPlayerScripts</code></td><td>Sambungkan tombol GUI ke server, notifikasi, label saldo live, animasi switch</td></tr>
    </tbody>
  </table>

  <h2 data-anchor="logic-detect">Yang Dideteksi</h2>
  <table class="docs-table">
    <thead><tr><th>Di HTML</th><th>Jadi</th></tr></thead>
    <tbody>
      <tr><td>Card berisi nama + harga (<code>2,500 💎</code>, <code>$0.99</code>) + rarity</td><td>Item di katalog; card / tombol Buy-nya → <code>Purchase</code></td></tr>
      <tr><td>Label saldo di luar card (<code>💎 12,450</code>, <code>1,240 Gold</code>)</td><td>leaderstats dengan saldo awal = angka di GUI, label ter-update otomatis</td></tr>
      <tr><td>Tombol Buy / Sell / Equip / Unequip / Upgrade / Craft / Use</td><td>Aksi item (harga dari teks tombol, mis. <code>Upgrade · 🪙 900</code>)</td></tr>
      <tr><td>Claim / Daily / Reward</td><td><code>Claim</code> dengan cooldown 24 jam</td></tr>
      <tr><td>Input kode + tombol Redeem</td><td><code>Redeem</code> — kode di <code>GameConfig.Codes</code>, sekali pakai</td></tr>
      <tr><td>Spin / Roll / Gacha (+ label biaya)</td><td><code>Spin</code> dengan hadiah berbobot, diacak di server</td></tr>
      <tr><td>Accept / Decline quest, pilihan dialog</td><td><code>AcceptQuest</code> / <code>DeclineQuest</code> / <code>DialogueChoice</code></td></tr>
      <tr><td>Revive · 💎 20 / Respawn</td><td><code>Revive</code> (bayar) / <code>Respawn</code></td></tr>
      <tr><td>Toggle switch + Save Settings</td><td><code>SetSetting</code> per pemain + <code>SaveSettings</code></td></tr>
    </tbody>
  </table>

  <div class="callout warn">
    <div>
      <strong>Aman dari exploit:</strong> client hanya mengirim <em>id item</em>. Harga, saldo, cooldown,
      dan hadiah selalu dibaca dari <code>GameConfig</code> di server.
    </div>
  </div>

  <h2 data-anchor="logic-custom">Kustomisasi</h2>
  <ul>
    <li>Ubah harga, saldo awal, hadiah, kode redeem di <strong>GameConfig</strong></li>
    <li>Tambah efek item di <code>Handlers.UseItem</code> / hadiah dialog di <code>GameConfig.Dialogues</code></li>
    <li>Matikan penyimpanan dengan <code>UseDataStore = false</code></li>
  </ul>
</section>
