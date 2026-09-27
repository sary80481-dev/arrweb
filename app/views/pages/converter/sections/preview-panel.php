<?php
// app/views/pages/converter/sections/preview-panel.php — preview canvas (tengah)
?>
<!-- ============================================================
     PANEL PREVIEW
============================================================ -->
<section class="panel" id="panelPreview">
  <div class="panel-head">
    <span class="dot green"></span>
    <span class="label">Live Preview</span>
    <div class="head-actions">
      <div class="view-toggle">
        <button id="viewHTML" class="active" onclick="setViewMode('html')">HTML</button>
        <button id="viewRBX" onclick="setViewMode('roblox')">Roblox</button>
      </div>
      <button onclick="zoomOut()" class="icon-btn">−</button>
      <span id="zoomLabel" class="mono zoom-label">100%</span>
      <button onclick="zoomIn()" class="icon-btn">+</button>
      <button onclick="fitToScreen()" class="icon-btn">⛶</button>
    </div>
  </div>
  <div id="previewStage">
    <div id="previewFrameWrap">
      <iframe id="previewFrame" sandbox="allow-same-origin allow-scripts" scrolling="no"></iframe>
    </div>
    <div id="robloxFrameWrap" class="hidden">
      <div id="robloxCanvas"></div>
    </div>
  </div>
  <div class="panel-foot">
    <span>Output StarterGui</span>
    <span class="mono">800 × 600</span>
  </div>
</section>
