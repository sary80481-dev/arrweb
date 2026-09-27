<?php
// app/views/pages/prompt/sections/generator.php — Tab 1: Prompt Generator
?>
<div class="pg-panel active" data-panel="generator">
  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">01</span>
      Detail UI yang Mau Dibuat
    </div>
    <div class="pg-section-desc">
      Isi detail UI yang kamu mau. Semakin detail, semakin presisi hasil konversi.
    </div>

    <div class="pg-form">

      <div class="pg-field">
        <label class="pg-label">Jenis UI</label>
        <select class="pg-select" id="genUiType">
          <option value="HUD in-game">HUD in-game (health, ammo, dll)</option>
          <option value="main menu">Main Menu</option>
          <option value="shop panel">Shop Panel</option>
          <option value="inventory grid">Inventory Grid</option>
          <option value="settings panel">Settings Panel</option>
          <option value="dialogue box">Dialogue Box</option>
          <option value="quest tracker">Quest Tracker</option>
          <option value="notification toast">Notification Toast</option>
          <option value="loading screen">Loading Screen</option>
          <option value="billboard overhead nametag">Billboard Overhead Nametag</option>
          <option value="custom">Custom</option>
        </select>
      </div>

      <div class="pg-field">
        <label class="pg-label">Tema Warna</label>
        <select class="pg-select" id="genTheme">
          <option value="dark + gold accent (#d4af37)">Dark + Gold</option>
          <option value="dark + blue accent (#3b82f6)">Dark + Blue</option>
          <option value="dark + purple accent (#a855f7)">Dark + Purple</option>
          <option value="dark + green accent (#22c55e)">Dark + Green</option>
          <option value="dark + red accent (#ef4444)">Dark + Red</option>
          <option value="light clean minimal">Light Minimal</option>
          <option value="custom hex">Custom hex...</option>
        </select>
      </div>

      <div class="pg-field full">
        <label class="pg-label">Deskripsi UI</label>
        <textarea class="pg-textarea" id="genDesc" placeholder="contoh: HUD yang nampilin health bar, mana bar, dan skill cooldown di pojok kiri bawah"></textarea>
      </div>

      <div class="pg-field full">
        <label class="pg-label">Komponen (satu per baris)</label>
        <textarea class="pg-textarea" id="genComponents" placeholder="satu komponen per baris, contoh:
Health bar: 200x20 px, warna merah, ada label 'HP 100/100'
Mana bar: 200x12 px, warna biru, di bawah health bar
Skill icons: 3 icon 48x48 px di kanan bawah
Border radius 8px di semua panel"></textarea>
      </div>

      <div class="pg-field full">
        <label class="pg-label">Fitur Interaktif (opsional)</label>
        <div class="pg-checkbox-group" id="genFeatures">
          <label class="pg-checkbox" data-feature="Toggle panel pakai data-action=&quot;toggle&quot;"><span class="pg-checkbox-icon">✓</span> Toggle panel</label>
          <label class="pg-checkbox" data-feature="Close panel pakai data-action=&quot;close&quot;"><span class="pg-checkbox-icon">✓</span> Close panel</label>
          <label class="pg-checkbox" data-feature="Hover effect di tombol"><span class="pg-checkbox-icon">✓</span> Hover tombol</label>
          <label class="pg-checkbox" data-feature="Animasi slide saat panel muncul"><span class="pg-checkbox-icon">✓</span> Animasi slide</label>
          <label class="pg-checkbox" data-feature="Gradient background"><span class="pg-checkbox-icon">✓</span> Gradient</label>
          <label class="pg-checkbox" data-feature="Icon pakai emoji atau image"><span class="pg-checkbox-icon">✓</span> Icon</label>
        </div>
      </div>

      <div class="pg-field full">
        <label class="pg-label">Catatan Tambahan (opsional)</label>
        <textarea class="pg-textarea" id="genNotes" placeholder="contoh: pakai emoji untuk icon, tombol Buy di tiap card, saldo gem di header"></textarea>
      </div>

    </div>

    <div class="pg-btn-row">
      <button type="button" class="pg-btn pg-btn-primary" id="genBtn">
        Generate Prompt
      </button>
      <button type="button" class="pg-btn pg-btn-ghost" id="genRandom">Contoh Acak</button>
      <button type="button" class="pg-btn pg-btn-ghost" id="genClear">Reset Form</button>
    </div>
  </div>

  <div class="pg-section">
    <div class="pg-section-title">
      <span class="num">02</span>
      Copy Prompt
    </div>
    <div class="pg-output">
      <div class="pg-output-head">
        <div class="pg-output-title">
          <span class="dot"></span>
          Generated Prompt
        </div>
        <button type="button" class="pg-copy" id="genCopy">Copy Prompt</button>
      </div>
      <div class="pg-output-body" id="genOutput"></div>
    </div>
  </div>
</div>