<?php
// app/views/pages/docs/sections/sidebar.php
?>
<aside class="docs-side" id="docsSide">
  <div class="docs-side-header">
    <div class="docs-side-label">Documentation</div>
    <input
      type="text"
      class="docs-search"
      id="docsSearch"
      placeholder="Cari dokumentasi..."
      autocomplete="off"
    >
  </div>

  <div class="docs-side-title">Getting Started</div>
  <a class="docs-link active" data-doc="intro"> Introduction
  </a>
  <a class="docs-link" data-doc="quickstart"> Quick Start
  </a>

  <div class="docs-side-title">Reference</div>
  <a class="docs-link" data-doc="html"> HTML & CSS
  </a>
  <a class="docs-link" data-doc="actions"> Actions & Toggle
  </a>
  <a class="docs-link" data-doc="logic"> Game Logic
    <span class="docs-link-badge">Baru</span>
  </a>
  <a class="docs-link" data-doc="output"> Output Files
  </a>

  <div class="docs-side-title">Advanced</div>
  <a class="docs-link" data-doc="plugin"> Plugin & Install Pack
  </a>
  <a class="docs-link" data-doc="api"> API Reference
  </a>
  <a class="docs-link" data-doc="faq"> FAQ
  </a>

  <div class="docs-no-results" id="docsNoResults">
    Tidak ada hasil untuk pencarian ini.
  </div>
</aside>