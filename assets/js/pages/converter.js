/* ============================================================
 *  assets/js/pages/converter.js — HTML → Roblox Converter (frontend v3.6, SPA-ready)
 * ============================================================ */
(function () {
'use strict';

const CANVAS_W = 800;
const CANVAS_H = 600;
const $ = id => document.getElementById(id);

let currentTab = 'script';
let cache = {
  script: '', fullscript: '', tree: '', rbxmx: '',
  plugin: '', billboard: '', report: '',
  module: '', server: '', client: '',   // Game Logic
};
let logicSummary = '';

// File Game Logic: lokasi di Roblox Studio + nama file download
const LOGIC_FILES = {
  module: { path: 'ReplicatedStorage › ArrUI › GameConfig (ModuleScript)', file: 'GameConfig.lua' },
  server: { path: 'ServerScriptService › ArrUIServer (Script)',            file: 'ArrUIServer.server.lua' },
  client: { path: 'StarterPlayerScripts › ArrUIClient (LocalScript)',       file: 'ArrUIClient.client.lua' },
};
let lastNodes = [];
let previewNodes = null;
let lastUi = null;         // nama ScreenGui/container dari server   // node final dari server (setelah ShapeFixer) → preview Roblox
let viewMode = 'html';
let zoom = 1;
let autoFit = true;
let convertToken = 0;
let isInitialized = false;

/* ============================================================
   LAZY REFS — ambil fresh setiap dipakai
   ============================================================ */
const ref = {
  get htmlIn()            { return $('htmlIn'); },
  get frame()             { return $('previewFrame'); },
  get frameWrap()         { return $('previewFrameWrap'); },
  get robloxWrap()        { return $('robloxFrameWrap'); },
  get robloxCanvas()      { return $('robloxCanvas'); },
  get previewStage()      { return $('previewStage'); },
  get outputBody()        { return $('outputBody'); },
  get badgeNodes()        { return $('badgeNodes'); },
  get badgeWarn()         { return $('badgeWarn'); },
  get luaLines()          { return $('luaLines'); },
  get luaStatus()         { return $('luaStatus'); },
  get zoomLabel()         { return $('zoomLabel'); },
  get vscodeHighlight()   { return $('vscodeHighlight'); },
  get vscodeGutter()      { return $('vscodeGutter'); },
  get vscodeMinimapContent() { return $('vscodeMinimapContent'); },
  get vscodeMinimapViewport() { return $('vscodeMinimapViewport'); },
  get vscodeMinimap()     { return $('vscodeMinimap'); },
  get vscodeCursor()      { return $('vscodeCursor'); },
  get vscodeErrors()      { return $('vscodeErrors'); },
  get vscodeWarnings()    { return $('vscodeWarnings'); },
  get btnToggleMinimap()  { return $('btnToggleMinimap'); },
};

function isConverterPage() {
  return !!document.getElementById('htmlIn');
}

/* ============================================================
   API CALLS
   ============================================================ */
async function apiConvert(html, rectMap) {
  const res = await fetch(window.__apiUrls?.convert || 'api/convert.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ html, rectMap }),
  });
  if (res.status === 429) {
    const info = await res.json().catch(() => ({}));
    window.GuestLimit?.sync({ uses: info.uses, max: info.max });
    const err = new Error('Kuota gratis habis. Login untuk lanjut.');
    err.guestLimit = true;
    throw err;
  }
  if (!res.ok) throw new Error('convert.php: ' + res.status);
  return res.json();
}

async function apiGenerate(nodes, billboardConfig = null) {
  const payload = { nodes, canvasW: CANVAS_W, canvasH: CANVAS_H };
  if (billboardConfig) payload.billboard = billboardConfig;

  const res = await fetch(window.__apiUrls?.generate || 'api/generate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error('generate.php: ' + res.status);
  return res.json();
}

/* ============================================================
   VIEW MODE
   ============================================================ */
function setViewMode(mode) {
  viewMode = mode;
  const isHtml = mode === 'html';
  const fw = ref.frameWrap;
  const rw = ref.robloxWrap;
  if (fw) fw.classList.toggle('hidden', !isHtml);
  if (rw) rw.classList.toggle('hidden', isHtml);
  const vh = $('viewHTML'); if (vh) vh.classList.toggle('active', isHtml);
  const vr = $('viewRBX'); if (vr) vr.classList.toggle('active', !isHtml);
  if (!isHtml) renderRobloxPreview(previewNodes || lastNodes);
  applyZoom();
}

function applyZoom() {
  const wrap = viewMode === 'html' ? ref.frameWrap : ref.robloxWrap;
  if (!wrap) return;
  wrap.style.transform = `scale(${zoom})`;
  wrap.style.width = CANVAS_W + 'px';
  wrap.style.height = CANVAS_H + 'px';
  wrap.style.marginBottom = ((CANVAS_H * zoom) - CANVAS_H) + 'px';
  wrap.style.marginRight = ((CANVAS_W * zoom) - CANVAS_W) + 'px';
  const zl = ref.zoomLabel;
  if (zl) zl.textContent = Math.round(zoom * 100) + '%';
}

function computeFitZoom() {
  const stage = ref.previewStage;
  if (!stage) return 1;
  const pad = 48;
  const availW = stage.clientWidth - pad;
  const availH = stage.clientHeight - pad;
  return Math.min(availW / CANVAS_W, availH / CANVAS_H, 1);
}

function fitToScreen() { autoFit = true; zoom = computeFitZoom(); applyZoom(); }
function zoomIn()  { autoFit = false; zoom = Math.min(zoom + 0.1, 3); applyZoom(); }
function zoomOut() { autoFit = false; zoom = Math.max(zoom - 0.1, 0.1); applyZoom(); }

window.addEventListener('resize', () => {
  if (autoFit) { zoom = computeFitZoom(); applyZoom(); }
});

/* ============================================================
   CONVERT FLOW
   ============================================================ */
/* Default: generate Lua hanya lewat tombol Convert (preview HTML tetap live).
   User login bisa nyalakan "Auto" (disimpan di localStorage). */
const AUTO_KEY = 'arrr_auto_convert';
function isAutoConvert() {
  if (!window.__isLoggedIn) return false;
  try { return localStorage.getItem(AUTO_KEY) === '1'; } catch { return false; }
}
const isManualMode = () => !isAutoConvert();

function setAutoConvert(on) {
  try { localStorage.setItem(AUTO_KEY, on ? '1' : '0'); } catch {}
  if (on) scheduleConvert(50);
}

// Dipanggil saat isi editor berubah
function scheduleConvert(delay = 350) {
  clearTimeout(window.__debounceT);
  window.__debounceT = setTimeout(() => convert({ previewOnly: isManualMode() }), delay);
}

// Tombol ⚡ Convert / Ctrl+Enter
async function runConvert() {
  clearTimeout(window.__debounceT);
  if (!window.__isLoggedIn && window.GuestLimit?.isExceeded()) {
    window.__showLoginGate?.();
    return;
  }
  const btn = $('btnConvert');
  btn?.classList.add('is-loading');
  try {
    await convert({ previewOnly: false });
  } finally {
    btn?.classList.remove('is-loading');
  }
}

// Output belum sesuai dengan isi editor → minta user klik Convert
function markOutputStale() {
  const luaStatus = ref.luaStatus;
  if (luaStatus) luaStatus.textContent = 'Belum di-convert — klik Convert';
  $('btnConvert')?.classList.add('is-stale');

  const outputBody = ref.outputBody;
  const hasOutput = Object.values(cache).some(Boolean);
  if (outputBody && !hasOutput) {
    const left = window.GuestLimit ? window.GuestLimit.getRemaining() : 0;
    outputBody.innerHTML = `<span class="tok-cmt">-- Preview HTML sudah tampil.\n-- Klik Convert (atau Ctrl+Enter) untuk generate Lua.\n-- Sisa kuota gratis: ${left}/${window.GuestLimit?.MAX ?? 3}</span>`;
  }
}

// Label tombol Convert: tampilkan sisa kuota untuk guest
function updateConvertButton() {
  const auto = $('autoConvert');
  if (auto) {
    auto.hidden = !window.__isLoggedIn;
    const box = auto.querySelector('input');
    if (box) box.checked = isAutoConvert();
  }
  const quota = $('convertQuota');
  if (!quota) return;
  if (window.__isLoggedIn || !window.GuestLimit) {
    quota.textContent = '';
    quota.hidden = true;
    return;
  }
  const left = window.GuestLimit.getRemaining();
  quota.hidden = false;
  quota.textContent = `${left}/${window.GuestLimit.MAX}`;
  quota.classList.toggle('is-empty', left <= 0);
}

async function convert(options = {}) {
  const previewOnly = !!options.previewOnly;
  const htmlIn = ref.htmlIn;
  const frame  = ref.frame;
  const outputBody = ref.outputBody;
  const badgeNodes = ref.badgeNodes;
  const badgeWarn  = ref.badgeWarn;
  const luaLines   = ref.luaLines;
  const robloxCanvas = ref.robloxCanvas;

  // Guard: kalau elemen nggak ada (bukan di converter), skip
  if (!htmlIn || !frame || !outputBody) return;

  const myToken = ++convertToken;

  const html = htmlIn.value;
  if (!html.trim()) {
    outputBody.innerHTML = '<span class="tok-cmt">-- input kosong --</span>';
    if (badgeNodes) badgeNodes.textContent = '0 nodes';
    if (badgeWarn) badgeWarn.classList.add('hidden');
    if (luaLines) luaLines.textContent = '0 lines';
    if (robloxCanvas) robloxCanvas.innerHTML = '';
    lastNodes = [];
    return;
  }

  if (myToken !== convertToken) return;

  frame.style.width = CANVAS_W + 'px';
  frame.style.height = CANVAS_H + 'px';
  const fd = frame.contentDocument;
  if (!fd) {
    console.error('frame.contentDocument null — iframe belum siap');
    return;
  }

  await new Promise((resolve) => {
    let resolved = false;
    const done = () => { if (!resolved) { resolved = true; resolve(); } };
    frame.onload = done;

    fd.open();
    fd.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>
      html,body{margin:0;padding:0;width:${CANVAS_W}px;height:${CANVAS_H}px;overflow:hidden;background:#fff;font-family:Arial,sans-serif;position:relative;}
      *{box-sizing:border-box;}
    </style></head><body>${html}</body></html>`);
    fd.close();
    setTimeout(done, 300);
  });

  if (myToken !== convertToken) return;
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
  if (myToken !== convertToken) return;

  // Mode guest: cukup preview HTML, generate Lua nunggu tombol Convert
  if (previewOnly) {
    markOutputStale();
    return;
  }

  // HTML yang dikirim = hasil render browser (sudah ditandai data-arrr-idx)
  let measured = { rectMap: [], html };
  try {
    measured = await measureRectMap(fd.body, fd);
  } catch (e) {
    console.error('Measure error:', e);
  }

  if (myToken !== convertToken) return;

  try {
    const parsed = await apiConvert(measured.html, measured.rectMap);
    if (parsed.guest) {
      window.GuestLimit?.sync(parsed.guest);
      updateConvertButton();
      if (parsed.guest.remaining === 1) showToast('Tersisa 1 kuota gratis. Login untuk unlimited.', 'warning');
      else if (parsed.guest.remaining === 0) showToast('Kuota gratis habis. Login untuk lanjut.', 'warning');
    }
    if (parsed.error) throw new Error(parsed.error);
    if (myToken !== convertToken) return;
    lastNodes = parsed.nodes;

    const bbConfig = (typeof getBillboardConfig === 'function') ? getBillboardConfig() : null;
    const gen = await apiGenerate(parsed.nodes, bbConfig);
    if (gen.error) throw new Error(gen.error);
    if (myToken !== convertToken) return;

    cache.script     = gen.script     || '';
    cache.fullscript = gen.fullscript || '';
    cache.tree       = gen.tree       || '';
    cache.rbxmx      = gen.rbxmx      || '';
    cache.plugin     = gen.plugin     || '';
    cache.billboard  = gen.billboard  || '';
    cache.report     = gen.report     || '';
    cache.module     = gen.module     || '';
    cache.server     = gen.server     || '';
    cache.client     = gen.client     || '';
    logicSummary     = gen.logicSummary || '';

    renderTab();
    previewNodes = gen.nodes || null;
    lastUi = gen.ui || null;
    renderRobloxPreview(previewNodes || lastNodes);
    if (badgeNodes) badgeNodes.textContent = lastNodes.length + ' nodes';

    const totalIssues = lastNodes.reduce((s,n) => s + (n.unsupported ? n.unsupported.length : 0), 0);
    if (badgeWarn) {
      if (totalIssues > 0) {
        badgeWarn.textContent = totalIssues + ' warn';
        badgeWarn.classList.remove('hidden');
      } else {
        badgeWarn.classList.add('hidden');
      }
    }

    const luaStatus = ref.luaStatus;
    if (luaStatus) luaStatus.textContent = 'Generated';
    $('btnConvert')?.classList.remove('is-stale');
    if (autoFit) { zoom = computeFitZoom(); applyZoom(); }
  } catch (e) {
    if (e.guestLimit) {
      updateConvertButton();
      window.__showLoginGate?.();
      return;
    }
    console.error(e);
    if (myToken === convertToken && outputBody) {
      outputBody.innerHTML = `<span class="tok-cmt">-- error: ${escapeHtml(e.message)} --</span>`;
      const luaStatus = ref.luaStatus;
      if (luaStatus) luaStatus.textContent = 'Error: ' + e.message;
    }
  }
}

/* ============================================================
   MEASURE — browser yang menghitung layout & CSS final
   (stylesheet, class, flex/grid, %, em, inheritance), lalu
   hasilnya dikirim ke server.

   Return:
   - html    : isi <body> yang tiap elemennya sudah diberi data-arrr-idx
   - rectMap : [{ idx, parentIdx, x, y, w, h, selfHidden, merged,
                  text, rich, style: {computed css} }]
   ============================================================ */

// Tidak pernah jadi node & tidak ditelusuri
const MEASURE_SKIP = new Set([
  'script', 'style', 'meta', 'link', 'title', 'head', 'template', 'noscript',
  'option', 'optgroup', 'datalist', 'param', 'source', 'track', 'base', 'wbr',
]);
// Jadi node, tapi isinya tidak ditelusuri
const MEASURE_LEAF = new Set([
  'svg', 'canvas', 'video', 'audio', 'iframe', 'object', 'embed', 'img', 'input',
  'textarea', 'select', 'math', 'progress', 'meter', 'hr',
]);
// Properti computed style yang dipakai parser
const MEASURE_PROPS = [
  'display', 'position', 'visibility', 'opacity', 'z-index', 'cursor',
  'color', 'background-color', 'background-image',
  'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
  'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color', 'border-top-style',
  'border-top-left-radius', 'border-top-right-radius',
  'border-bottom-right-radius', 'border-bottom-left-radius',
  'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
  'font-family', 'font-size', 'font-weight', 'font-style', 'line-height',
  'text-align', 'text-transform', 'text-decoration-line', 'white-space',
  'overflow-x', 'overflow-y', 'object-fit',
  'flex-direction', 'justify-content', 'align-items',
  'transition', 'transform', 'box-shadow', 'text-shadow', 'filter',
  'backdrop-filter', 'clip-path', 'mix-blend-mode',
];
// Nilai default — tidak perlu dikirim (hemat payload)
const MEASURE_DEFAULTS = new Set([
  'none', 'normal', 'auto', '0px', 'rgba(0, 0, 0, 0)', 'static', 'visible',
  'start', 'all', 'all 0s ease 0s', 'fill',
]);

async function measureRectMap(rootEl, iframeDoc) {
  const win = iframeDoc.defaultView;
  const cs  = (el) => win.getComputedStyle(el);

  // Semua elemen yang relevan (urutan dokumen), di luar SKIP & isi LEAF
  const elements = [];
  (function collect(parent) {
    for (const el of parent.children) {
      const tag = el.tagName.toLowerCase();
      if (MEASURE_SKIP.has(tag)) continue;
      elements.push(el);
      if (!MEASURE_LEAF.has(tag)) collect(el);
    }
  })(rootEl);

  // Simpan style attribute asli → dipulihkan setelah ukur
  const savedStyle = new Map();
  const force = (el, prop, value) => {
    if (!savedStyle.has(el)) savedStyle.set(el, el.getAttribute('style'));
    el.style.setProperty(prop, value, 'important');
  };

  // ==== 1. Elemen hidden (inline ATAU lewat CSS) → tampilkan sementara ====
  const hiddenRoots = new Set();
  for (const el of elements) {
    const s = cs(el);
    const parentVis = el.parentElement ? cs(el.parentElement).visibility : 'visible';
    const hiddenByDisplay = s.display === 'none';
    const hiddenByVis     = s.visibility === 'hidden' && parentVis !== 'hidden';
    const hiddenByOpacity = parseFloat(s.opacity) === 0;
    if (!hiddenByDisplay && !hiddenByVis && !hiddenByOpacity) continue;

    // Hanya root-nya yang ditandai (anak ikut tersembunyi lewat parent)
    let p = el.parentElement, insideHidden = false;
    while (p && p !== rootEl) {
      if (hiddenRoots.has(p)) { insideHidden = true; break; }
      p = p.parentElement;
    }
    if (!insideHidden) hiddenRoots.add(el);

    if (hiddenByDisplay) force(el, 'display', 'block');
    if (hiddenByVis)     force(el, 'visibility', 'visible');
    if (hiddenByOpacity) force(el, 'opacity', '1');
    // Panel slide-in biasanya digeser pakai transform — ukur di posisi aslinya
    if (s.transform && s.transform !== 'none') force(el, 'transform', 'none');
  }

  // ==== 2. Baca computed style (sebelum overflow diubah) ====
  const styles = new Map();
  for (const el of elements) {
    const s = cs(el);
    const parentCursor = el.parentElement ? cs(el.parentElement).cursor : 'auto';
    const out = {};
    for (const prop of MEASURE_PROPS) {
      let v = s.getPropertyValue(prop);
      // cursor diwariskan — kirim hanya kalau di-set di elemen ini
      if (prop === 'cursor' && v === parentCursor) continue;
      if (prop === 'opacity' && v === '1') continue;
      if (v === '' || (MEASURE_DEFAULTS.has(v) && prop !== 'display' && prop !== 'color')) continue;
      out[prop] = v;
    }
    styles.set(el, out);
  }

  // ==== 3. Konten scroll → tampilkan penuh supaya anak terukur utuh ====
  for (const el of elements) {
    const s = styles.get(el);
    if (/(auto|scroll)/.test((s['overflow-y'] || '') + (s['overflow-x'] || ''))) {
      force(el, 'overflow', 'visible');
    }
  }

  void rootEl.offsetHeight;
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(() => setTimeout(r, 30))));

  // ==== 4. Tentukan node vs teks inline yang digabung (RichText) ====
  const idxOf = new Map();
  elements.forEach((el, i) => idxOf.set(el, i));
  const merged = new Set();

  const hasDirectText = (el) => Array.from(el.childNodes)
    .some(n => n.nodeType === 3 && n.textContent.trim() !== '');

  const noBox = (s) => {
    const zero = (v) => !v || parseFloat(v) === 0;
    return (!s['background-color'] || s['background-color'] === 'rgba(0, 0, 0, 0)')
      && !s['background-image']
      && zero(s['border-top-width']) && zero(s['border-right-width'])
      && zero(s['border-bottom-width']) && zero(s['border-left-width'])
      && zero(s['padding-left']) && zero(s['padding-right']);
  };

  // Elemen inline murni (b, strong, em, a, span berwarna, br, ...) → teks parent
  function canMerge(el) {
    const tag = el.tagName.toLowerCase();
    if (tag === 'br') return true;
    if (MEASURE_SKIP.has(tag) || MEASURE_LEAF.has(tag) || tag === 'button') return false;
    if (hiddenRoots.has(el)) return false;
    if (el.id || el.hasAttribute('data-action') || el.hasAttribute('data-target')
        || el.hasAttribute('onclick') || el.hasAttribute('data-name')) return false;
    const s = styles.get(el) || {};
    if (s.display !== 'inline') return false;
    if (!noBox(s)) return false;
    // Link/tombol inline hanya digabung kalau memang bagian dari kalimat
    if (tag === 'a' && !hasDirectText(el.parentElement)) return false;
    return Array.from(el.children).every(c => MEASURE_SKIP.has(c.tagName.toLowerCase()) || canMerge(c));
  }

  const escRich = (t) => t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&apos;');

  const transformText = (t, el) => {
    const tt = (styles.get(el) || {})['text-transform'];
    if (tt === 'uppercase') return t.toUpperCase();
    if (tt === 'lowercase') return t.toLowerCase();
    if (tt === 'capitalize') return t.replace(/\b\p{L}/gu, c => c.toUpperCase());
    return t;
  };

  const toHex = (rgb) => {
    const m = rgb && rgb.match(/rgba?\(([^)]+)\)/);
    if (!m) return null;
    const [r, g, b] = m[1].split(',').map(v => parseFloat(v));
    return '#' + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
  };

  // Format RichText untuk elemen inline, relatif ke style parent-nya
  function wrapRich(inner, el, parent) {
    const s = cs(el), p = cs(parent);
    let open = '', close = '';
    const add = (o, c) => { open += o; close = c + close; };

    const fontAttrs = [];
    if (s.color !== p.color) {
      const hex = toHex(s.color);
      if (hex) fontAttrs.push(`color="${hex}"`);
    }
    if (s.fontSize !== p.fontSize) fontAttrs.push(`size="${Math.round(parseFloat(s.fontSize))}"`);
    if (fontAttrs.length) add(`<font ${fontAttrs.join(' ')}>`, '</font>');

    if (parseInt(s.fontWeight, 10) >= 600 && parseInt(p.fontWeight, 10) < 600) add('<b>', '</b>');
    if (s.fontStyle === 'italic' && p.fontStyle !== 'italic') add('<i>', '</i>');
    const deco = s.textDecorationLine || '';
    const pdeco = p.textDecorationLine || '';
    if (deco.includes('underline') && !pdeco.includes('underline')) add('<u>', '</u>');
    if (deco.includes('line-through') && !pdeco.includes('line-through')) add('<s>', '</s>');

    return { rich: open + inner + close, formatted: open !== '' };
  }

  // Teks + RichText dari isi elemen (child element yang digabung ikut masuk)
  function inlineContent(el) {
    let text = '', rich = '', formatted = false;
    for (const n of el.childNodes) {
      if (n.nodeType === 3) {
        // Collapse whitespace seperti browser (&nbsp; dipertahankan, white-space:pre* tidak di-collapse)
        const ws = (styles.get(el) || {})['white-space'] || '';
        const raw = ws.startsWith('pre') || ws === 'break-spaces'
          ? n.textContent
          : n.textContent.replace(/[ \t\n\r\f]+/g, ' ');
        const t = transformText(raw, el);
        text += t; rich += escRich(t);
      } else if (n.nodeType === 1 && merged.has(n)) {
        if (n.tagName.toLowerCase() === 'br') {
          text += '\n'; rich += '<br />'; formatted = true;
          continue;
        }
        const sub = inlineContent(n);
        const w = wrapRich(sub.rich, n, el);
        text += sub.text; rich += w.rich;
        formatted = formatted || sub.formatted || w.formatted;
      }
    }
    return { text, rich, formatted };
  }

  const markMerged = (el) => {
    merged.add(el);
    for (const c of el.children) if (!MEASURE_SKIP.has(c.tagName.toLowerCase())) markMerged(c);
  };
  for (const el of elements) {
    if (merged.has(el) || MEASURE_LEAF.has(el.tagName.toLowerCase())) continue;
    for (const c of el.children) {
      if (!merged.has(c) && canMerge(c)) markMerged(c);
    }
  }

  // ==== 5. Ukur & susun rectMap ====
  const root = rootEl.getBoundingClientRect();
  const map = [];
  const byIdx = new Map();

  for (const el of elements) {
    const idx = idxOf.get(el);
    el.setAttribute('data-arrr-idx', String(idx));

    if (merged.has(el)) {
      map.push({ idx, merged: true });
      continue;
    }

    // Parent = ancestor terdekat yang juga jadi node
    let parentIdx = -1;
    for (let p = el.parentElement; p && p !== rootEl; p = p.parentElement) {
      if (idxOf.has(p) && !merged.has(p)) { parentIdx = idxOf.get(p); break; }
    }

    const r = el.getBoundingClientRect();
    const style = styles.get(el);

    // transform: rotate/scale → ukuran asli (sebelum rotasi) + Rotation Roblox.
    // Roblox memutar GuiObject di titik tengahnya → pakai titik tengah visual dari browser.
    let w = Math.round(r.width), h = Math.round(r.height);
    let x = r.left - root.left, y = r.top - root.top;
    let rotation = 0;
    const tm = (style.transform || '').match(/^matrix\(([^)]+)\)/);
    if (tm && !hiddenRoots.has(el)) {
      const [a, b, c, d] = tm[1].split(',').map(parseFloat);
      const sx = Math.hypot(a, b) || 1;
      const sy = Math.abs(a * d - b * c) / sx || 1;
      rotation = Math.round(Math.atan2(b, a) * 180 / Math.PI * 10) / 10;
      const baseW = el.offsetWidth ?? r.width, baseH = el.offsetHeight ?? r.height;
      const cx = x + r.width / 2, cy = y + r.height / 2;
      w = Math.round(baseW * sx);
      h = Math.round(baseH * sy);
      x = cx - w / 2;
      y = cy - h / 2;
    }

    // Radius → px dengan aturan CSS: kalau jumlah radius > panjang sisi,
    // SEMUA radius diskalakan dengan faktor yang sama (bukan dipotong per sudut)
    const RK = ['border-top-left-radius', 'border-top-right-radius',
                'border-bottom-right-radius', 'border-bottom-left-radius'];
    if (RK.some(k => style[k])) {
      const px = RK.map(k => {
        const first = (style[k] || '0').split(' ')[0];
        return first.endsWith('%') ? parseFloat(first) / 100 * Math.min(w, h) : (parseFloat(first) || 0);
      });
      const [tl, tr, br, bl] = px;
      const f = Math.min(1,
        w / (tl + tr), w / (bl + br),   // x/0 = Infinity → sisi tanpa radius tidak membatasi
        h / (tl + bl), h / (tr + br));
      RK.forEach((k, i) => { style[k] = Math.round(px[i] * f) + 'px'; });
    }

    const tag = el.tagName.toLowerCase();
    let content = { text: '', rich: '', formatted: false };
    if (tag === 'select') {
      const opt = el.options && el.options[el.selectedIndex];
      content.text = opt ? opt.text : '';
    } else if (tag === 'textarea') {
      content.text = el.value || '';
    } else if (!MEASURE_LEAF.has(tag)) {
      content = inlineContent(el);
    }
    const text = content.text.replace(/ *\n */g, '\n').replace(/^[ \t\n\r\f]+|[ \t\n\r\f]+$/g, '');

    // Anak dari elemen yang diputar: Roblox ikut memutar descendant,
    // jadi posisinya dihitung di ruang parent yang BELUM diputar (offsetLeft/Top)
    const parentRect = parentIdx >= 0 ? byIdx.get(parentIdx) : null;
    if (parentRect && (parentRect.rotation || parentRect.inRotated) && el.offsetParent === elements[parentIdx]) {
      x = parentRect.x + el.offsetLeft;
      y = parentRect.y + el.offsetTop;
      w = el.offsetWidth;
      h = el.offsetHeight;
    }

    map.push({
      idx, parentIdx,
      inRotated: !!(parentRect && (parentRect.rotation || parentRect.inRotated)),
      x: Math.round(x),
      y: Math.round(y),
      w, h,
      rotation,
      selfHidden: hiddenRoots.has(el),
      id: el.id || '',
      className: (typeof el.className === 'string') ? el.className : '',
      text,
      rich: content.formatted
        ? content.rich.replace(/ *<br \/> */g, '<br />').replace(/^[ \t\n\r\f]+|[ \t\n\r\f]+$/g, '')
        : '',
      style,
    });
    byIdx.set(idx, map[map.length - 1]);
  }

  // ==== 6. Pulihkan style asli ====
  for (const [el, attr] of savedStyle) {
    if (attr === null) el.removeAttribute('style');
    else el.setAttribute('style', attr);
  }

  return { rectMap: map, html: rootEl.innerHTML };
}

/* ============================================================
   ROBLOX PREVIEW RENDERER
   ============================================================ */
const rgbaCss = (c) => c
  ? `rgba(${Math.round(c.r * 255)},${Math.round(c.g * 255)},${Math.round(c.b * 255)},${c.a})`
  : 'transparent';

// Font preview ≈ FontFace Roblox (lihat app/helpers/FontHelper.php)
function previewFontFamily(css) {
  const f = String(css || '').toLowerCase();
  if (/mono|consolas|courier|menlo/.test(f)) return "'Roboto Mono', monospace";
  if (/(^|,|\s)(serif|georgia|times|merriweather)/.test(f) && !/sans-serif/.test(f.split(',')[0])) return "Merriweather, Georgia, serif";
  return '';
}

// RichText Roblox (b, i, u, s, br, font color/size) → HTML aman untuk preview
function robloxRichToHtml(rich) {
  return String(rich).replace(/<(\/?)(b|i|u|s|br|font)\b([^>]*)>/g, (m, close, tag, attrs) => {
    if (tag === 'br') return '<br>';
    if (tag !== 'font') return `<${close}${tag}>`;
    if (close) return '</span>';
    const color = (attrs.match(/color="(#[0-9a-fA-F]{6})"/) || [])[1];
    const size = (attrs.match(/size="(\d+)"/) || [])[1];
    const style = [color ? `color:${color}` : '', size ? `font-size:${size}px` : ''].filter(Boolean).join(';');
    return `<span style="${style}">`;
  }).replace(/<(?!\/?(b|i|u|s|br|span)\b)/g, '&lt;');
}

function renderRobloxPreview(nodes) {
  const robloxCanvas = ref.robloxCanvas;
  if (!robloxCanvas) return;
  robloxCanvas.innerHTML = '';
  if (!nodes || !nodes.length) return;
  const FONT = `'Source Sans 3','Segoe UI',Roboto,sans-serif`;

  const idToNode = {};
  nodes.forEach(n => idToNode[n.id] = n);

  const absPos = (n) => {
    let x = n.x, y = n.y;
    let cur = n;
    while (cur.parentId) {
      cur = idToNode[cur.parentId];
      if (!cur) break;
      x += cur.x; y += cur.y;
    }
    return { x, y };
  };

  const depthOf = (n) => {
    let d = 0, cur = n;
    while (cur && cur.parentId) {
      cur = idToNode[cur.parentId];
      d++;
    }
    return d;
  };
  const sorted = [...nodes].sort((a, b) => {
    const da = depthOf(a), db = depthOf(b);
    if (da !== db) return da - db;
    return a.id - b.id;
  });

  const domMap = {};
  for (const n of sorted) {
    const pos = absPos(n);
    const el = document.createElement('div');
    el.style.position = 'absolute';
    el.style.left = pos.x + 'px';
    el.style.top = pos.y + 'px';
    el.style.width = n.w + 'px';
    el.style.height = n.h + 'px';
    el.style.boxSizing = 'border-box';
    el.style.overflow = 'visible';
    el.style.pointerEvents = 'auto';
    el.style.zIndex = String(n.id);
    el.style.transition = 'opacity .35s ease, transform .35s cubic-bezier(.22,1,.36,1)';

    if (n.gradient && n.gradient.keypoints) {
      const stops = n.gradient.keypoints.map(kp => {
        const c = `rgba(${Math.round(kp.color.r*255)},${Math.round(kp.color.g*255)},${Math.round(kp.color.b*255)},${kp.color.a})`;
        return `${c} ${(kp.pos*100).toFixed(1)}%`;
      }).join(', ');
      el.style.background = `linear-gradient(${n.gradient.rotationCss}deg, ${stops})`;
    } else {
      el.style.background = rgbaCss(n.bg);
    }

    el.style.borderRadius = n.radius + 'px';

    // Rotation Roblox ikut memutar descendant (di sekitar titik tengah ancestor)
    let rot = n.rotation ? ` rotate(${n.rotation}deg)` : '';
    for (let p = idToNode[n.parentId]; p; p = idToNode[p.parentId]) {
      if (!p.rotation) continue;
      const pp = absPos(p);
      const dx = (pp.x + p.w / 2) - (pos.x + n.w / 2);
      const dy = (pp.y + p.h / 2) - (pos.y + n.h / 2);
      rot = ` translate(${dx}px, ${dy}px) rotate(${p.rotation}deg) translate(${-dx}px, ${-dy}px)` + rot;
    }
    if (rot) el.style.transform = rot.trim();
    if (n.selfHidden) {
      el.style.opacity = '0';
      el.style.pointerEvents = 'none';
      el.style.transform = 'translateX(30px)' + rot;
    }

    // UIStroke (Border mode) digambar di luar kotak → pakai outline, bukan border
    if (n.borderW > 0 && n.borderColor.a > 0.001) {
      const bs = Math.max(1, Math.round(n.borderW));
      el.style.boxShadow = `0 0 0 ${bs}px ${rgbaCss(n.borderColor)}`;
    }
    if (n.clips) el.style.overflow = 'hidden';
    if (!n.selfHidden && n.opacity !== undefined && n.opacity < 1) el.style.opacity = String(n.opacity);

    const isText = ['TextLabel','TextButton','TextBox'].includes(n.robloxClass);
    if (isText) {
      const txt = n.text || n.value || n.placeholder || '';
      const alignX = n.textAlign === 'center' ? 'center' : (n.textAlign === 'right' ? 'right' : 'left');
      el.style.display = 'flex';
      el.style.flexDirection = 'column';
      el.style.justifyContent = n.textAlignY === 'top' ? 'flex-start' : (n.textAlignY === 'bottom' ? 'flex-end' : 'center');
      el.style.alignItems = alignX === 'center' ? 'center' : (alignX === 'right' ? 'flex-end' : 'flex-start');
      el.style.padding = `${n.padT}px ${n.padR}px ${n.padB}px ${n.padL}px`;
      const span = document.createElement('span');
      if (n.richText) span.innerHTML = robloxRichToHtml(n.richText);
      else span.textContent = txt;
      span.style.color = rgbaCss(n.fg);
      span.style.fontFamily = previewFontFamily(n.fontFamily) || FONT;
      span.style.fontWeight = String(parseInt(n.fontWeight, 10) || 400);
      span.style.fontStyle = n.fontStyle === 'italic' ? 'italic' : 'normal';
      span.style.fontSize = Math.max(1, Math.round(n.fontSize)) + 'px';
      span.style.lineHeight = '1.2';
      span.style.whiteSpace = n.textWrapped === false ? 'pre' : 'pre-wrap';
      span.style.wordBreak = 'break-word';
      span.style.textAlign = alignX;
      span.style.width = '100%';
      el.appendChild(span);
      if (n.robloxClass === 'TextButton') el.style.cursor = 'pointer';
      if (n.robloxClass === 'TextBox') el.style.cursor = 'text';
    }

    if (n.robloxClass === 'ImageLabel' && n.src) {
      const img = document.createElement('img');
      img.src = n.src;
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'fill';
      el.appendChild(img);
    }

    // ClipsDescendants: potong elemen di luar kotak ancestor yang clip (seperti di Roblox)
    for (let p = idToNode[n.parentId]; p; p = idToNode[p.parentId]) {
      if (!p.clips || p.rotation) continue;
      const a = absPos(p);
      // Nilai negatif = area clip lebih besar dari elemen (stroke/outline di luar kotak tetap terlihat)
      const top = a.y - pos.y, left = a.x - pos.x;
      const right = (pos.x + n.w) - (a.x + p.w), bottom = (pos.y + n.h) - (a.y + p.h);
      if (top > 0 || left > 0 || right > 0 || bottom > 0) el.style.clipPath = `inset(${top}px ${right}px ${bottom}px ${left}px)`;
      break;
    }

    el.title = `${n.robloxClass} "${n.name}"${n.selfHidden ? ' [hidden]' : ''}`;
    robloxCanvas.appendChild(el);
    domMap[n.id] = el;

    if (n.transition && (n.robloxClass === 'TextButton' || n.isButtonLike)) {
      const baseX = pos.x;
      el.addEventListener('mouseenter', () => { el.style.left = (baseX - 3) + 'px'; });
      el.addEventListener('mouseleave', () => { el.style.left = baseX + 'px'; });
    }
  }

  const buttons = sorted.filter(n => n.robloxClass === 'TextButton' || n.robloxClass === 'ImageButton');
  const hiddenPanels = sorted.filter(n =>
    n.selfHidden &&
    ['Frame','ScrollingFrame'].includes(n.robloxClass) &&
    (n.action !== 'close')
  );

  const usedBtn = new Set();
  const usedPanel = new Set();
  const pairs = [];
  const norm = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9]/g, '');

  hiddenPanels.forEach(panel => {
    if (usedPanel.has(panel.id)) return;
    const panelKeys = [norm(panel.sourceId), norm(panel.id), norm(panel.name)].filter(Boolean);
    let btn = null;

    const sid = norm(panel.sourceId);
    if (sid) {
      btn = buttons.find(b =>
        !usedBtn.has(b.id) && b.action === 'toggle' && norm(b.target) === sid
      );
    }
    if (!btn) {
      btn = buttons.find(b => {
        if (usedBtn.has(b.id)) return false;
        if (b.action !== 'toggle') return false;
        const tgt = norm(b.target);
        if (!tgt) return false;
        return panelKeys.some(pk => pk.includes(tgt) || tgt.includes(pk));
      });
    }
    if (!btn) {
      const panelRoot = (panel.name || '').replace(/Panel\d*$|Popup$|Menu$|Container$|Frame$|Backdrop$/i, '');
      btn = buttons.find(b => {
        if (usedBtn.has(b.id)) return false;
        if (b.action !== 'toggle') return false;
        const btnRoot = (b.name || '').replace(/Button$|Toggle$|Btn$|Icon$/i, '');
        return btnRoot && panelRoot && btnRoot.toLowerCase() === panelRoot.toLowerCase();
      });
    }
    if (btn) {
      usedBtn.add(btn.id);
      usedPanel.add(panel.id);
      pairs.push({ btn, panel });
    }
  });

  const pairByBtn = {};
  pairs.forEach(({ btn, panel }) => {
    if (!pairByBtn[btn.id]) pairByBtn[btn.id] = { btn, panels: [] };
    pairByBtn[btn.id].panels.push(panel);
  });

  Object.values(pairByBtn).forEach(({ btn, panels }) => {
    const btnEl = domMap[btn.id];
    if (!btnEl) return;
    let open = false;
    panels.forEach(p => {
      const el = domMap[p.id];
      if (!el) return;
      const pos = absPos(p);
      el.style.left = (pos.x + 30) + 'px';
    });
    btnEl.addEventListener('click', (e) => {
      e.stopPropagation();
      open = !open;
      panels.forEach(p => {
        const el = domMap[p.id];
        if (!el) return;
        const pos = absPos(p);
        if (open) {
          el.style.opacity = '1';
          el.style.pointerEvents = 'auto';
          el.style.transform = 'translateX(0)';
          requestAnimationFrame(() => { el.style.left = pos.x + 'px'; });
        } else {
          el.style.opacity = '0';
          el.style.pointerEvents = 'none';
          el.style.transform = 'translateX(30px)';
          el.style.left = (pos.x + 30) + 'px';
        }
      });
    });
  });

  sorted.forEach(n => {
    if (n.action !== 'close') return;
    if (!n.selfHidden) return;
    const el = domMap[n.id];
    if (!el) return;
    const tgtNorm = norm(n.target);
    if (!tgtNorm) return;
    const panel = hiddenPanels.find(p => norm(p.sourceId) === tgtNorm);
    if (!panel) return;
    const panelEl = domMap[panel.id];
    if (!panelEl) return;
    el.addEventListener('click', (e) => {
      e.stopPropagation();
      panelEl.style.opacity = '0';
      panelEl.style.pointerEvents = 'none';
      panelEl.style.transform = 'translateX(30px)';
    });
  });
}

/* ============================================================
   OUTPUT TABS
   ============================================================ */
function renderTab() {
  const outputBody = ref.outputBody;
  if (!outputBody) return;
  const content = cache[currentTab] || '';
  if (currentTab === 'rbxmx' || currentTab === 'plugin' || currentTab === 'billboard') {
    outputBody.innerHTML = highlightLua(content);
  } else if (['script', 'fullscript', 'module', 'server', 'client'].includes(currentTab)) {
    outputBody.innerHTML = renderCodeWithLines(content, highlightLua);
  } else {
    outputBody.innerHTML = escapeHtml(content);
  }
  const luaLines = ref.luaLines;
  if (luaLines) luaLines.textContent = content.split('\n').length + ' lines';
  const isLogic = !!LOGIC_FILES[currentTab];
  document.querySelectorAll('.tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === currentTab || (isLogic && btn.dataset.group === 'logic'));
  });

  const logicBar = $('logicBar');
  if (logicBar) {
    logicBar.classList.toggle('hidden', !isLogic);
    logicBar.querySelectorAll('.logic-file[data-logic]').forEach(b =>
      b.classList.toggle('active', b.dataset.logic === currentTab));
    const path = $('logicPath');
    if (path) path.textContent = isLogic ? '📍 ' + LOGIC_FILES[currentTab].path : '';
    const summary = $('logicSummary');
    if (summary) summary.textContent = logicSummary ? '🔎 Terdeteksi: ' + logicSummary : '';
  }
}

function currentLogicFile() {
  return LOGIC_FILES[currentTab] ? currentTab : 'server';
}

function switchTab(tab) {
  currentTab = tab;
  renderTab();

  const bbConfig = document.getElementById('billboardConfig');
  if (bbConfig) {
    bbConfig.classList.toggle('hidden', tab !== 'billboard');
    if (tab === 'billboard' && lastNodes.length && !cache.billboard) {
      setTimeout(() => regenerateBillboard(), 200);
    }
  }
  const pluginBar = document.getElementById('pluginActionBar');
  if (pluginBar) pluginBar.classList.toggle('hidden', tab !== 'plugin');

  const copyBtn = document.getElementById('copyBtn');
  if (copyBtn) copyBtn.classList.toggle('btn-copy-active', !!cache[tab]);
}

function copyCurrent() {
  const txt = cache[currentTab] || '';
  if (!txt) {
    if (typeof showToast === 'function') showToast('Tab kosong — belum ada konten', 'error');
    return;
  }
  navigator.clipboard.writeText(txt).then(() => {
    const luaStatus = ref.luaStatus;
    if (luaStatus) luaStatus.textContent = '✓ Copied!';
    if (typeof showToast === 'function') showToast(`${currentTab} copied!`, 'success');
    setTimeout(() => { if (luaStatus) luaStatus.textContent = 'Ready'; }, 1200);
  });
}

function downloadFile(kind) {
  if (!lastNodes.length) {
    showToast('Klik Convert dulu, baru bisa download', 'warning');
    $('btnConvert')?.classList.add('is-stale');
    return;
  }
  let content = '', filename = '', mime = 'text/plain';
  if (kind === 'lua')       { content = cache.fullscript; filename = 'GeneratedUI_Full.lua'; }
  if (kind === 'rbxmx')     { content = cache.rbxmx;      filename = (lastUi?.name || 'ArrUI') + '_ArrUIPack.rbxmx'; mime = 'application/xml'; }
  if (kind === 'plugin')    { content = cache.plugin;     filename = 'ArrStudioImporter.lua'; }
  if (kind === 'billboard') { content = cache.billboard;  filename = 'BillboardNametag.lua'; }
  if (LOGIC_FILES[kind])    { content = cache[kind];      filename = LOGIC_FILES[kind].file; }
  if (!content) return;

  const blob = new Blob([content], { type: mime + ';charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);

  if (kind === 'rbxmx') {
    showToast('Studio: klik kanan Workspace → Insert from File → lalu plugin → Install Pack', 'success', 6000);
  }

  const luaStatus = ref.luaStatus;
  if (luaStatus) {
    luaStatus.textContent = `✓ ${filename} (${(content.length/1024).toFixed(1)} KB)`;
    setTimeout(() => luaStatus.textContent = 'Ready', 2500);
  }
}

/* ============================================================
   SAMPLES
   ============================================================ */
async function loadSample(which) {
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;
  const res = await fetch((window.__samplesUrl || 'samples/') + which + '.html');
  htmlIn.value = await res.text();
  syncEditorFromTextarea();
  convert({ previewOnly: isManualMode() });
}
function clearInput() {
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;
  htmlIn.value = '';
  syncEditorFromTextarea();
  convert({ previewOnly: true });
}

/* ============================================================
   BILLBOARD
   ============================================================ */
function getBillboardConfig() {
  const g = (id, fallback) => {
    const el = document.getElementById(id);
    return el ? el.value : fallback;
  };
  return {
    name:        (g('bbName', 'Player') || 'Player').trim(),
    role:        (g('bbRole', 'Member') || 'Member').trim(),
    level:       parseInt(g('bbLevel', '1')) || 1,
    theme:       g('bbTheme', 'gold') || 'gold',
    offsetY:     parseFloat(g('bbOffsetY', '3.5')) || 3.5,
    maxDistance: parseInt(g('bbMaxDistance', '120')) || 120,
    size:        [220, 70],
  };
}

async function regenerateBillboard() {
  const luaStatus = ref.luaStatus;
  if (!lastNodes || !lastNodes.length) {
    if (luaStatus) luaStatus.textContent = '⚠️ Convert HTML dulu';
    setTimeout(() => { if (luaStatus) luaStatus.textContent = 'Ready'; }, 1800);
    return;
  }
  if (luaStatus) luaStatus.textContent = 'Generating billboard...';
  try {
    const gen = await apiGenerate(lastNodes, getBillboardConfig());
    if (gen.error) throw new Error(gen.error);
    cache.billboard = gen.billboard || '';
    if (currentTab === 'billboard') renderTab();
    if (luaStatus) luaStatus.textContent = '✓ Billboard updated';
    setTimeout(() => { if (luaStatus) luaStatus.textContent = 'Ready'; }, 1500);
  } catch (e) {
    console.error(e);
    if (luaStatus) luaStatus.textContent = 'Error: ' + e.message;
  }
}

/* ============================================================
   HIGHLIGHT & CODE VIEW
   ============================================================ */
const LUA_KEYWORDS = new Set(['and','break','do','else','elseif','end','false','for','function','goto','if','in','local','nil','not','or','repeat','return','then','true','until','while']);
const LUA_BUILTINS = new Set(['print','warn','error','assert','pcall','xpcall','type','typeof','tostring','tonumber','ipairs','pairs','next','select','unpack','setmetatable','getmetatable','rawget','rawset','require','tick','wait','spawn','delay','math','string','table','os','coroutine','task']);
const LUA_GLOBALS = new Set(['game','workspace','script','Instance','Enum','Color3','UDim2','UDim','Vector2','Vector3','CFrame','TweenInfo','TweenService','Players','RunService','UserInputService','ReplicatedStorage','ServerScriptService','StarterGui','StarterPlayer']);

function highlightLua(code) {
  const out = []; let i = 0;
  while (i < code.length) {
    const ch = code[i];
    if (ch === '-' && code[i+1] === '-') {
      if (code[i+2] === '[' && code[i+3] === '[') {
        let j = code.indexOf(']]', i+4);
        if (j === -1) j = code.length; else j += 2;
        out.push(`<span class="tok-cmt">${escapeHtml(code.slice(i,j))}</span>`);
        i = j; continue;
      }
      let j = code.indexOf('\n', i);
      if (j === -1) j = code.length;
      out.push(`<span class="tok-cmt">${escapeHtml(code.slice(i,j))}</span>`);
      i = j; continue;
    }
    if (ch === '"' || ch === "'") {
      let j = i+1;
      while (j < code.length && code[j] !== ch) { if (code[j] === '\\') j++; j++; }
      j++;
      out.push(`<span class="tok-str">${escapeHtml(code.slice(i,j))}</span>`);
      i = j; continue;
    }
    if (/[0-9]/.test(ch) && (i === 0 || !/[a-zA-Z_]/.test(code[i-1]))) {
      let j = i;
      while (j < code.length && /[0-9.]/.test(code[j])) j++;
      out.push(`<span class="tok-num">${escapeHtml(code.slice(i,j))}</span>`);
      i = j; continue;
    }
    if (/[a-zA-Z_]/.test(ch)) {
      let j = i;
      while (j < code.length && /[a-zA-Z0-9_]/.test(code[j])) j++;
      const word = code.slice(i, j);
      const prev = code[i-1];
      const nextNonSpace = code.slice(j).match(/^\s*[:(]/);
      if (LUA_KEYWORDS.has(word))        out.push(`<span class="tok-kw">${word}</span>`);
      else if (LUA_GLOBALS.has(word))    out.push(`<span class="tok-cls">${word}</span>`);
      else if ((prev === '.' || prev === ':') && nextNonSpace) out.push(`<span class="tok-mth">${word}</span>`);
      else if (LUA_BUILTINS.has(word))   out.push(`<span class="tok-blt">${word}</span>`);
      else if (nextNonSpace && prev !== '.' && prev !== ':') out.push(`<span class="tok-fn">${word}</span>`);
      else                                out.push(escapeHtml(word));
      i = j; continue;
    }
    if (/[+\-*/%^#=<>~]/.test(ch)) {
      out.push(`<span class="tok-op">${escapeHtml(ch)}</span>`);
      i++; continue;
    }
    out.push(escapeHtml(ch));
    i++;
  }
  return out.join('');
}

function renderCodeWithLines(code, highlighter) {
  const lines = code.split('\n');
  const gutterLines = lines.map((_, i) => i + 1).join('\n');
  const contentHtml = lines.map(line => {
    const highlighted = highlighter(line);
    return `<span class="code-line">${highlighted || '&nbsp;'}</span>`;
  }).join('\n');
  return `<div class="code-wrap"><div class="code-gutter">${gutterLines}</div><div class="code-content">${contentHtml}</div></div>`;
}

function escapeHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/* ============================================================
   VSCODE EDITOR
   ============================================================ */
function highlightHTML(code) {
  if (!code) return '';
  const span = (cls, text) => `<span class="${cls}">${escapeHtml(text)}</span>`;

  // Satu kali scan: komentar | tag (dengan atribut) | teks biasa.
  // Markup hasil highlight tidak pernah di-regex ulang (dulu bikin "class=tok-punct" bocor).
  return code.replace(/(<!--[\s\S]*?(?:-->|$))|(<\/?)([\w:-]+)([^<>]*?)(\/?>)|([^<]+|<)/g,
    (m, comment, open, tag, attrs, close, text) => {
      if (comment) return span('tok-comment', comment);
      if (text !== undefined) return escapeHtml(text);

      const attrHtml = attrs.replace(/([\w:@.-]+)(?:(\s*=\s*)("[^"]*"|'[^']*'|[^\s"'>]+))?|([^\w:@.-]+)/g,
        (am, name, eq, value, other) => {
          if (other !== undefined) return escapeHtml(other);
          if (!eq) return span('tok-attr', name);
          return span('tok-attr', name) + escapeHtml(eq) + (value ? span('tok-str', value) : '');
        });

      return span('tok-punct', open) + span('tok-tag', tag) + attrHtml + span('tok-punct', close);
    }) + '\n';
}

function syncEditorFromTextarea() {
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;
  const text = htmlIn.value;
  const lines = text.split('\n');

  const vscodeHighlight = ref.vscodeHighlight;
  if (vscodeHighlight) vscodeHighlight.innerHTML = highlightHTML(text);

  const vscodeGutter = ref.vscodeGutter;
  if (vscodeGutter) {
    vscodeGutter.innerHTML = lines.map((_, i) => `<div class="vscode-line-num">${i + 1}</div>`).join('');
  }
  const vscodeMinimapContent = ref.vscodeMinimapContent;
  if (vscodeMinimapContent) vscodeMinimapContent.textContent = text || '';

  const vscodeErrors = ref.vscodeErrors;
  if (vscodeErrors) {
    const openTags = (text.match(/<([a-z][\w-]*)(\s[^>]*)?>/gi) || [])
      .filter(t => !/\/>$/.test(t) && !/^<(br|hr|img|input|meta|link)/i.test(t));
    const closeTags = (text.match(/<\/[a-z][\w-]*>/gi) || []);
    const diff = Math.max(0, openTags.length - closeTags.length);
    vscodeErrors.textContent = diff;
  }
  const vscodeWarnings = ref.vscodeWarnings;
  if (vscodeWarnings) vscodeWarnings.textContent = 0;

  syncEditorScroll();
}

function syncEditorScroll() {
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;
  const top = htmlIn.scrollTop;
  const left = htmlIn.scrollLeft;

  const vscodeHighlight = ref.vscodeHighlight;
  if (vscodeHighlight) {
    vscodeHighlight.style.transform = `translate(${-left}px, ${-top}px)`;
    const contentW = Math.max(htmlIn.scrollWidth, htmlIn.clientWidth);
    const contentH = Math.max(htmlIn.scrollHeight, htmlIn.clientHeight);
    vscodeHighlight.style.width = contentW + 'px';
    vscodeHighlight.style.height = contentH + 'px';
  }
  const vscodeGutter = ref.vscodeGutter;
  if (vscodeGutter) vscodeGutter.style.transform = `translateY(${-top}px)`;

  updateMinimapViewport();
}

function updateMinimapViewport() {
  const vscodeMinimapViewport = ref.vscodeMinimapViewport;
  const vscodeMinimap = ref.vscodeMinimap;
  const htmlIn = ref.htmlIn;
  if (!vscodeMinimapViewport || !vscodeMinimap || !htmlIn) return;
  if (vscodeMinimap.classList.contains('collapsed')) return;

  const totalH = htmlIn.scrollHeight;
  const viewH  = htmlIn.clientHeight;
  const minimapH = vscodeMinimap.clientHeight - 16;
  if (minimapH <= 0) return;

  if (totalH <= viewH) {
    vscodeMinimapViewport.style.height = minimapH + 'px';
    vscodeMinimapViewport.style.top = '8px';
    return;
  }
  const ratio = minimapH / totalH;
  const vpTop = 8 + (htmlIn.scrollTop * ratio);
  const vpHeight = Math.max(20, viewH * ratio);
  vscodeMinimapViewport.style.top = vpTop + 'px';
  vscodeMinimapViewport.style.height = vpHeight + 'px';
}

function updateCursorPos() {
  const vscodeCursor = ref.vscodeCursor;
  const htmlIn = ref.htmlIn;
  if (!vscodeCursor || !htmlIn) return;
  const pos = htmlIn.selectionStart;
  const before = htmlIn.value.substring(0, pos);
  const line = before.split('\n').length;
  const col  = pos - before.lastIndexOf('\n');
  vscodeCursor.textContent = `Ln ${line}, Col ${col}`;

  const vscodeGutter = ref.vscodeGutter;
  if (vscodeGutter) {
    vscodeGutter.querySelectorAll('.vscode-line-num').forEach((el, i) =>
      el.classList.toggle('active', i === line - 1)
    );
  }
}

/* ============================================================
   RESIZERS
   ============================================================ */
function initResizers() {
  const main = document.querySelector('.app-main');
  const pInput = $('panelInput');
  const pOutput = $('panelOutput');
  if (!main || !pInput || !pOutput) return;
  if (main.dataset.resizersInit === '1') return;
  main.dataset.resizersInit = '1';

  let dragInfo = null;

  document.querySelectorAll('.resizer').forEach(rs => {
    rs.addEventListener('mousedown', startDrag);
    rs.addEventListener('touchstart', startDrag, { passive: false });
  });

  function startDrag(e) {
    e.preventDefault();
    const rs = e.currentTarget;
    dragInfo = {
      rs, which: rs.dataset.target,
      startX: e.touches ? e.touches[0].clientX : e.clientX,
      startInputW: pInput.offsetWidth,
      startOutputW: pOutput.offsetWidth,
      totalW: main.offsetWidth,
    };
    rs.classList.add('active');
    document.body.classList.add('dragging');
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
  }
  function onDrag(e) {
    if (!dragInfo) return;
    e.preventDefault();
    const x = e.touches ? e.touches[0].clientX : e.clientX;
    const dx = x - dragInfo.startX;
    if (dragInfo.which === 'input') {
      const newW = Math.max(200, Math.min(dragInfo.startInputW + dx, dragInfo.totalW - 500));
      pInput.style.flex = `0 0 ${newW}px`;
    } else if (dragInfo.which === 'output') {
      const newW = Math.max(220, Math.min(dragInfo.startOutputW - dx, dragInfo.totalW - 500));
      pOutput.style.flex = `0 0 ${newW}px`;
    }
    if (autoFit) { zoom = computeFitZoom(); applyZoom(); }
  }
  function endDrag() {
    if (dragInfo) dragInfo.rs.classList.remove('active');
    document.body.classList.remove('dragging');
    dragInfo = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchend', endDrag);
  }
}

/* ============================================================
   BIND LISTENERS — rebind setiap init
   ============================================================ */
function bindEditorListeners() {
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;
  if (htmlIn.dataset.bound === '1') return;
  htmlIn.dataset.bound = '1';

htmlIn.addEventListener('input', () => {
  syncEditorFromTextarea();
  updateCursorPos();
  scheduleConvert();
});

// Ctrl/Cmd + Enter → Convert
htmlIn.addEventListener('keydown', (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    e.preventDefault();
    runConvert();
  }
});
  htmlIn.addEventListener('scroll', syncEditorScroll, { passive: true });
  htmlIn.addEventListener('keyup', updateCursorPos);
  htmlIn.addEventListener('click', updateCursorPos);
  htmlIn.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      e.preventDefault();
      const start = htmlIn.selectionStart;
      const end = htmlIn.selectionEnd;
      htmlIn.value = htmlIn.value.substring(0, start) + '  ' + htmlIn.value.substring(end);
      htmlIn.selectionStart = htmlIn.selectionEnd = start + 2;
      syncEditorFromTextarea();
      updateCursorPos();
    }
  });

  const vscodeMinimap = ref.vscodeMinimap;
  if (vscodeMinimap) {
    vscodeMinimap.addEventListener('click', (e) => {
      const rect = vscodeMinimap.getBoundingClientRect();
      const ratio = (e.clientY - rect.top) / rect.height;
      const targetScroll = ratio * htmlIn.scrollHeight - htmlIn.clientHeight / 2;
      htmlIn.scrollTop = Math.max(0, Math.min(targetScroll, htmlIn.scrollHeight - htmlIn.clientHeight));
      syncEditorScroll();
    });
  }

  const btnToggleMinimap = ref.btnToggleMinimap;
  if (btnToggleMinimap && vscodeMinimap) {
    const saved = localStorage.getItem('arrr_minimap_collapsed');
    if (saved === '1') {
      vscodeMinimap.classList.add('collapsed');
      btnToggleMinimap.classList.remove('active');
    } else {
      btnToggleMinimap.classList.add('active');
    }
    btnToggleMinimap.addEventListener('click', () => {
      const collapsed = vscodeMinimap.classList.toggle('collapsed');
      btnToggleMinimap.classList.toggle('active', !collapsed);
      localStorage.setItem('arrr_minimap_collapsed', collapsed ? '1' : '0');
      updateMinimapViewport();
    });
  }

  // Billboard auto-regen
  const ids = ['bbName', 'bbRole', 'bbLevel', 'bbTheme', 'bbOffsetY', 'bbMaxDistance'];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (!el || el.dataset.bound === '1') return;
    el.dataset.bound = '1';
    let t = null;
    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
    el.addEventListener(evt, () => {
      clearTimeout(t);
      t = setTimeout(() => {
        if (currentTab === 'billboard' && lastNodes.length) regenerateBillboard();
      }, 500);
    });
  });
}

/* ============================================================
   INIT CONVERTER APP (dipanggil tiap SPA navigation)
   ============================================================ */
function initConverterApp() {
  // Kalau bukan di converter page, skip
  if (!isConverterPage()) return;

  bindEditorListeners();
  updateConvertButton();
  initResizers();

  syncEditorFromTextarea();
  updateCursorPos();
  updateMinimapViewport();

  // Load sample (kalau perlu)
  const htmlIn = ref.htmlIn;
  if (!htmlIn) return;

  const pendingSample = localStorage.getItem('arrr_load_sample');
  if (pendingSample) {
    localStorage.removeItem('arrr_load_sample');
    loadSample(pendingSample).catch((e) => {
      console.error('Failed to load sample:', pendingSample, e);
      loadSample('sidebar');
    });
  } else if (!htmlIn.value.trim()) {
    loadSample('sidebar');
  }

  setTimeout(() => { zoom = computeFitZoom(); applyZoom(); }, 300);
}

/* ============================================================
   EXPOSE GLOBAL
   ============================================================ */
window.setViewMode   = setViewMode;
window.zoomIn        = zoomIn;
window.zoomOut       = zoomOut;
window.fitToScreen   = fitToScreen;
window.loadSample    = loadSample;
window.clearInput    = clearInput;
window.runConvert    = runConvert;
window.setAutoConvert = setAutoConvert;
window.switchTab     = switchTab;
window.currentLogicFile = currentLogicFile;
window.copyCurrent   = copyCurrent;
window.downloadFile  = downloadFile;
window.regenerateBillboard = regenerateBillboard;
window.getBillboardConfig  = getBillboardConfig;
window.initConverterApp    = initConverterApp;

/* ============================================================
   AUTO-INIT
   ============================================================ */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initConverterApp);
} else {
  initConverterApp();
}
window.addEventListener('spa:navigated', initConverterApp);

})();