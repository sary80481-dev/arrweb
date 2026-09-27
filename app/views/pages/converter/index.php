<?php
// app/views/pages/converter/index.php — halaman converter (3 panel: input | preview | output)
?>
<main class="app-main">

  <?php View::partial('pages/converter/sections/input-panel'); ?>

  <div class="resizer" data-target="input"></div>

  <?php View::partial('pages/converter/sections/preview-panel'); ?>

  <div class="resizer" data-target="output"></div>

  <?php View::partial('pages/converter/sections/output-panel'); ?>

  <?php View::component('plugin-modal'); ?>


</main>
