<?php
// app/views/pages/prompt/index.php
?>
<main class="pg-wrap">
  <div class="pg-inner">

    <div class="pg-hero">
      <div class="pg-hero-eyebrow">Prompt Guide</div>
      <div class="pg-hero-title">HTML Prompt Guide</div>
      <div class="pg-hero-sub">
        Prompt generator khusus buat bikin <strong>HTML yang optimal</strong> untuk
        ARRR Studio Converter.
      </div>
    </div>

    <div class="pg-tabs" id="pgTabs">
      <button type="button" class="pg-tab active" data-tab="generator"> Prompt Generator
      </button>
      <button type="button" class="pg-tab" data-tab="rules"> Aturan Converter
      </button>
      <button type="button" class="pg-tab" data-tab="cheatsheet"> Cheat Sheet
      </button>
      <button type="button" class="pg-tab" data-tab="templates"> Templates
      </button>
      <button type="button" class="pg-tab" data-tab="tips"> Tips
      </button>
    </div>

    <?php View::partial('pages/prompt/sections/generator'); ?>
    <?php View::partial('pages/prompt/sections/rules'); ?>
    <?php View::partial('pages/prompt/sections/cheatsheet'); ?>
    <?php View::partial('pages/prompt/sections/templates'); ?>
    <?php View::partial('pages/prompt/sections/tips'); ?>

  </div>
</main>