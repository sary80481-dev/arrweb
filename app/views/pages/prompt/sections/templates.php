<?php
// app/views/pages/prompt/sections/templates.php — Tab 4: Templates
?>
<div class="pg-panel" data-panel="templates">
  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">01</span>
      Template Prompt Siap Pakai
    </div>
    <div class="pg-section-desc">
      Klik untuk expand, copy, lalu ganti bagian [dalam kurung].
    </div>

    <div class="pg-template open">
      <div class="pg-template-head">
        <div class="pg-template-left">
          <div class="pg-template-icon">01</div>
          <div>
            <div class="pg-template-name">HUD In-Game</div>
            <div class="pg-template-sub">Health bar, mana, skill cooldown</div>
          </div>
        </div>
        <span class="pg-template-arrow" aria-hidden="true"></span>
      </div>
      <div class="pg-template-body">
        <div class="pg-template-inner">
          <div class="pg-code">
            <div class="pg-code-head">
              <div class="lang"><span class="dot"></span> prompt.txt</div>
              <button type="button" class="pg-copy" data-copy-target="tpl1">Copy</button>
            </div>
            <div class="pg-code-body" id="tpl1">Buatkan HTML untuk HUD in-game Roblox yang akan di-convert pakai ARRR Studio Converter.

ATURAN CONVERTER ARRR STUDIO v5:
- Boleh pakai &lt;style&gt; + class, flex, grid, margin, padding (dibaca dari hasil render browser)
- Satu elemen root pembungkus, ukuran muat di 800×600 px
- Warna solid / linear-gradient; border & border-radius boleh; transform: rotate() boleh
- JANGAN box-shadow, text-shadow, filter, backdrop-filter, radial-gradient, gambar URL (pakai emoji)
- Tombol pakai &lt;button&gt; dengan teks jelas (Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Accept, Decline, Save)
- Harga ditulis sebagai teks + ikon mata uang: "💎 2,500", "🪙 900", "$0.99"; saldo pemain di header "💎 12,450"
- Card item: satu container berisi ikon emoji, nama, rarity (Common/Rare/Epic/Legendary), harga
- Panel tersembunyi: display:none + id "xxx-panel", tombol id "xxx-toggle" / "xxx-close"

DESAIN HUD:
- Posisi: pojok kiri bawah (left:20, bottom area)
- Health bar: 200x20 px, background merah (#ef4444), border radius 6px, ada label "HP 100/100" di dalam
- Mana bar: 200x12 px di bawah health bar (top +26), background biru (#3b82f6)
- Skill icons: 3 kotak 48x48 px di kanan health bar, background #17171d, border gold #d4af37 2px, isi emoji ⚔️ 🛡️ 🧪
- Text color putih #ffffff, font Arial bold 12px

TOGGLE (kalau perlu):
- Tambah button kecil di pojok untuk toggle HUD pakai data-action="toggle" data-target="hudPanel"

OUTPUT:
- Full HTML siap paste ke ARRR Studio input
- Boleh pakai &lt;style&gt; + class (lebih rapi)
- Jangan jelasin panjang, langsung kode</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pg-template">
      <div class="pg-template-head">
        <div class="pg-template-left">
          <div class="pg-template-icon">02</div>
          <div>
            <div class="pg-template-name">Shop Panel</div>
            <div class="pg-template-sub">Grid item + harga + tombol beli</div>
          </div>
        </div>
        <span class="pg-template-arrow" aria-hidden="true"></span>
      </div>
      <div class="pg-template-body">
        <div class="pg-template-inner">
          <div class="pg-code">
            <div class="pg-code-head">
              <div class="lang"><span class="dot"></span> prompt.txt</div>
              <button type="button" class="pg-copy" data-copy-target="tpl2">Copy</button>
            </div>
            <div class="pg-code-body" id="tpl2">Buatkan HTML untuk Shop Panel Roblox yang akan di-convert pakai ARRR Studio Converter.

ATURAN CONVERTER ARRR STUDIO v5:
- Boleh pakai &lt;style&gt; + class, flex, grid, margin, padding (dibaca dari hasil render browser)
- Satu elemen root pembungkus, ukuran muat di 800×600 px
- Warna solid / linear-gradient; border & border-radius boleh; transform: rotate() boleh
- JANGAN box-shadow, text-shadow, filter, backdrop-filter, radial-gradient, gambar URL (pakai emoji)
- Tombol pakai &lt;button&gt; dengan teks jelas (Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Accept, Decline, Save)
- Harga ditulis sebagai teks + ikon mata uang: "💎 2,500", "🪙 900", "$0.99"; saldo pemain di header "💎 12,450"
- Card item: satu container berisi ikon emoji, nama, rarity (Common/Rare/Epic/Legendary), harga
- Panel tersembunyi: display:none + id "xxx-panel", tombol id "xxx-toggle" / "xxx-close"

DESAIN SHOP:
- Panel utama: 600x500 px di tengah (left:100, top:50)
- Background panel: #17171d, border #d4af37 1px, border-radius 12px
- Header: "🏪 Item Shop" 20px bold gold #d4af37 di (left:20, top:20)
- Coins display: kotak di kanan atas (left:480, top:20), 100x30, bg #121216, isi "💎 12,450" gold bold
- Grid 3x3 item: mulai dari top:70, left:20
  - Tiap item: 180x140, bg #121216, border-radius 8px, margin antar item 10px
  - Isi item: emoji 60px di tengah atas, nama 13px bold putih, harga 11px gold
  - 9 item contoh: ⚔️ Dragon Sword 2500, 🛡️ Ice Shield 1200, 🧪 Potion 150, 🔮 Magic Orb 3800, 👑 Crown 9999, 🔥 Fire Scroll 780, 💎 Gem Pack 500, ⭐ Star Token 300, ❤️ Health Pack 200
- Tiap item ada tombol "Beli" di bawah harga: 100x24, bg gold #d4af37, text #0a0a0d bold

TOGGLE:
- Tambah button "Open Shop" di luar panel, data-action="toggle" data-target="shopPanel"
- Panel shop pakai style="display:none;" biar bisa di-toggle

OUTPUT:
- Full HTML siap paste</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pg-template">
      <div class="pg-template-head">
        <div class="pg-template-left">
          <div class="pg-template-icon">03</div>
          <div>
            <div class="pg-template-name">Inventory Grid</div>
            <div class="pg-template-sub">Slot item + hover effect</div>
          </div>
        </div>
        <span class="pg-template-arrow" aria-hidden="true"></span>
      </div>
      <div class="pg-template-body">
        <div class="pg-template-inner">
          <div class="pg-code">
            <div class="pg-code-head">
              <div class="lang"><span class="dot"></span> prompt.txt</div>
              <button type="button" class="pg-copy" data-copy-target="tpl3">Copy</button>
            </div>
            <div class="pg-code-body" id="tpl3">Buatkan HTML untuk Inventory Grid Roblox yang akan di-convert pakai ARRR Studio Converter.

ATURAN CONVERTER ARRR STUDIO v5:
- Boleh pakai &lt;style&gt; + class, flex, grid, margin, padding (dibaca dari hasil render browser)
- Satu elemen root pembungkus, ukuran muat di 800×600 px
- Warna solid / linear-gradient; border & border-radius boleh; transform: rotate() boleh
- JANGAN box-shadow, text-shadow, filter, backdrop-filter, radial-gradient, gambar URL (pakai emoji)
- Tombol pakai &lt;button&gt; dengan teks jelas (Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Accept, Decline, Save)
- Harga ditulis sebagai teks + ikon mata uang: "💎 2,500", "🪙 900", "$0.99"; saldo pemain di header "💎 12,450"
- Card item: satu container berisi ikon emoji, nama, rarity (Common/Rare/Epic/Legendary), harga
- Panel tersembunyi: display:none + id "xxx-panel", tombol id "xxx-toggle" / "xxx-close"

DESAIN INVENTORY:
- Panel utama: 720x540 px di (left:40, top:30)
- Background: #121216, border #26262e, border-radius 16px, padding 24px
- Header: "🎒 Inventory" 22px bold putih, subtitle "24/40 slots used" 12px #8a8a96
- 6 kolom × 4 baris = 24 slot
  - Tiap slot: 100x100 px, bg #1a1a22, border #26262e 1px, border-radius 10px
  - Spacing antar slot: 10px
  - Posisi slot mulai (left:24, top:100)
- Isi slot (contoh):
  - Slot 1: ⚔️ (border gold #d4af37 2px — legendary)
  - Slot 2: 🛡️ (border blue #60a5fa 2px — rare)
  - Slot 3: 🧪 (border #26262e default)
  - Slot 4: kosong
  - Slot 5: 👑 (border gold)
  - Slot 6: 💰
  - Slot 7: 🔥 (border red #ef4444)
  - sisanya kosong
- Emoji size 44px di tengah slot
- Footer: "💎 1,240 Gold · 🔑 8 Keys" 12px gold di bawah

TOGGLE:
- Panel pakai style="display:none;" + id="inventoryPanel"
- Tambah button "🎒 Bag" di atas panel, data-action="toggle" data-target="inventoryPanel"
- Tambah button close "×" di pojok panel, data-action="close" data-target="inventoryPanel"

OUTPUT:
- Full HTML siap paste</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pg-template">
      <div class="pg-template-head">
        <div class="pg-template-left">
          <div class="pg-template-icon">04</div>
          <div>
            <div class="pg-template-name">Dialogue Box</div>
            <div class="pg-template-sub">NPC dialog RPG + pilihan</div>
          </div>
        </div>
        <span class="pg-template-arrow" aria-hidden="true"></span>
      </div>
      <div class="pg-template-body">
        <div class="pg-template-inner">
          <div class="pg-code">
            <div class="pg-code-head">
              <div class="lang"><span class="dot"></span> prompt.txt</div>
              <button type="button" class="pg-copy" data-copy-target="tpl4">Copy</button>
            </div>
            <div class="pg-code-body" id="tpl4">Buatkan HTML untuk Dialogue Box RPG Roblox yang akan di-convert pakai ARRR Studio Converter.

ATURAN CONVERTER ARRR STUDIO v5:
- Boleh pakai &lt;style&gt; + class, flex, grid, margin, padding (dibaca dari hasil render browser)
- Satu elemen root pembungkus, ukuran muat di 800×600 px
- Warna solid / linear-gradient; border & border-radius boleh; transform: rotate() boleh
- JANGAN box-shadow, text-shadow, filter, backdrop-filter, radial-gradient, gambar URL (pakai emoji)
- Tombol pakai &lt;button&gt; dengan teks jelas (Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Accept, Decline, Save)
- Harga ditulis sebagai teks + ikon mata uang: "💎 2,500", "🪙 900", "$0.99"; saldo pemain di header "💎 12,450"
- Card item: satu container berisi ikon emoji, nama, rarity (Common/Rare/Epic/Legendary), harga
- Panel tersembunyi: display:none + id "xxx-panel", tombol id "xxx-toggle" / "xxx-close"

DESAIN DIALOGUE:
- Panel utama di bawah canvas: 760x220 px, left:20, top:360
- Background: linear-gradient(#17171d, #0f0f14), border gold #d4af37 1px, border-radius 14px
- Avatar: kotak 80x80 px di (left:20, top:20) dalam panel
  - Emoji 🧙 60px di tengah
  - Border gold 2px, border-radius 50% (bulat)
- Speaker name: "Elder Marcus" 16px bold gold di (left:120, top:24)
- Subtitle: "Village Elder · Lv. 60" 11px #8a8a96 di (left:120, top:46)
- Dialog text: 14px putih #e8e8ec di (left:120, top:70), lebar 620px
  - Isi: "Ah, traveler... pedang suci kami, Excalibur, telah dicuri oleh naga di Gunung Berapi."
- 3 pilihan (choices) di bawah dialog, mulai top:130:
  - Tiap choice: 700x32 px, bg #1a1a22, border #26262e, border-radius 8px
  - Pilihan 1: "Aku akan pergi ke Gunung Berapi" — default
  - Pilihan 2: "Berapa bayarannya?" — highlighted (border gold #d4af37, bg rgba(212,175,55,0.1))
  - Pilihan 3: "Aku sedang sibuk" — abu
  - Setiap choice ada nomor (1/2/3) dalam kotak kecil 22x22 px di kiri

TOGGLE:
- Panel pakai style="display:none;" + id="dialogueBox"
- Button "💬 Talk" di atas, data-action="toggle" data-target="dialogueBox"

OUTPUT:
- Full HTML siap paste</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pg-template">
      <div class="pg-template-head">
        <div class="pg-template-left">
          <div class="pg-template-icon">05</div>
          <div>
            <div class="pg-template-name">Billboard Overhead Nametag</div>
            <div class="pg-template-sub">Nama + role + level di atas kepala player</div>
          </div>
        </div>
        <span class="pg-template-arrow" aria-hidden="true"></span>
      </div>
      <div class="pg-template-body">
        <div class="pg-template-inner">
          <div class="pg-code">
            <div class="pg-code-head">
              <div class="lang"><span class="dot"></span> prompt.txt</div>
              <button type="button" class="pg-copy" data-copy-target="tpl5">Copy</button>
            </div>
            <div class="pg-code-body" id="tpl5">Buatkan script Lua untuk BillboardGui overhead nametag di atas kepala player Roblox.

KONTEKS:
Script ini akan dipasang di LocalScript (StarterPlayerScripts) dan bikin BillboardGui otomatis di atas kepala setiap player.

ATURAN:
- Pakai BillboardGui, bukan ScreenGui
- Nempel di Head (bukan HumanoidRootPart)
- StudsOffsetWorldSpace untuk atur tinggi di atas kepala
- AlwaysOnTop = true biar tembus dinding (opsional)
- MaxDistance = 120 biar nggak keliatan dari jauh
- LightInfluence = 0 biar warna konsisten
- Apply ke semua player (Players:GetPlayers + PlayerAdded)
- Skip local player biar nggak lihat nametag sendiri

STRUKTUR BILLBOARD:
- Ukuran: 220 × 70 px
- Layout (dari atas ke bawah):
  1. Row badge icons (3 badge 22x22 px, horizontal):
     - 🎤 (mic) — background rgba(30,30,40,0.8)
     - 💬 (chat) — background rgba(30,50,90,0.8)
     - 👑 (crown) — background rgba(90,30,60,0.8)
     - Border tipis putih rgba(200,200,210,0.5), border-radius 6px
  2. Nama player: 16px bold, warna gold #f4d03f, text-stroke hitam
  3. Role text: 12px regular, warna silver #c0c0c8, text-stroke hitam
  4. Level text: 11px bold, warna hijau #22c55e, format "Level X"
- Semua text pakai Font GothamBold / Gotham
- TextStrokeTransparency 0.3-0.5 biar kebaca di background apapun

SERVICES:
- Players (untuk GetPlayers + PlayerAdded)
- TweenService (opsional, kalau mau animasi fade in)

FITUR TAMBAHAN:
- Auto-update level dari leaderstats.Level kalau ada
- Handle CharacterAdded (respawn)
- Cleanup BillboardGui lama sebelum bikin yang baru
- Guard kalau Head belum ke-load (WaitForChild dengan timeout)

OUTPUT:
- Full Lua script siap paste ke LocalScript di StarterPlayerScripts
- Pakai komentar di tiap fungsi penting
- Modern API (task.wait, :GetService())
- Jangan jelasin panjang, langsung kode</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>