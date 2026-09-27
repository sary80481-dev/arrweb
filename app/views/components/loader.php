<?php
// app/views/components/loader.php — splash loader (logic: assets/js/core/loader.js)
?>
<div id="arrrLoader" class="arrr-loader">
  <div class="arrr-loader-inner">

    <div class="arrr-loader-line top"></div>

    <div class="arrr-loader-logo-wrap">
      <img src="<?= asset('img/logo.png') ?>" alt="ARRR Studio" class="arrr-loader-logo">
    </div>

    <div class="arrr-loader-brand">
      ARRR <span>STUDIO</span>
    </div>

    <div class="arrr-loader-tagline">
      ROBLOX UI CONVERTER
    </div>

    <div class="arrr-loader-progress">
      <div class="arrr-loader-progress-bar" id="arrrLoaderBar"></div>
    </div>

    <div class="arrr-loader-percent" id="arrrLoaderPercent">0%</div>

    <div class="arrr-loader-line bottom"></div>

  </div>
</div>
<script src="<?= asset_v('js/core/loader.js') ?>"></script>
