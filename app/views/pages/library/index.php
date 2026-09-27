<?php
// app/views/pages/library/index.php — koleksi template (logic: assets/js/pages/library.js)
?>
<main class="page-wrap">
  <div class="page-inner">

    <header class="page-hero">
      <p class="page-hero-eyebrow">Library</p>
      <h1 class="page-title">Template yang langsung jadi game.</h1>
      <p class="page-sub">
        Klik template untuk membukanya di Converter. Yang bertanda <b>Logic</b> otomatis
        dibuatkan script server &amp; client di tab Game Logic.
      </p>
    </header>

    <div class="lib-toolbar">
      <div class="lib-filters" id="libFilters" role="tablist">
        <button class="lib-filter active" data-filter="all">Semua</button>
        <button class="lib-filter" data-filter="commerce">Shop &amp; Reward</button>
        <button class="lib-filter" data-filter="hud">HUD</button>
        <button class="lib-filter" data-filter="menu">Menu</button>
        <button class="lib-filter" data-filter="rpg">RPG</button>
        <button class="lib-filter" data-filter="ui">UI</button>
        <button class="lib-filter" data-filter="layout">Layout</button>
      </div>
      <div class="lib-search-wrap">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6"/><path d="M16 16l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <input type="text" class="lib-search" id="libSearch" placeholder="Cari template" autocomplete="off">
      </div>
    </div>

    <p class="lib-count" id="libCount"></p>
    <div class="lib-grid" id="libGrid"></div>
  </div>
</main>
