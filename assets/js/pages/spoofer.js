/* ============================================================
 *  assets/js/pages/spoofer.js — Auto Spoof massal (SPA-ready)
 *  Tiap aset = 1 request ke api/spoof.php, dijalankan paralel (CONCURRENCY).
 * ============================================================ */
(function () {
  'use strict';

  const CONCURRENCY = 2;
  const POLL_MS     = 2000;
  const POLL_MAX    = 60;
  const STORE_KEY   = 'arrr_spoof_settings';

  const esc = (t) => String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const $ = (id) => document.getElementById(id);
  const sleep = (ms) => new Promise(r => setTimeout(r, ms));

  let items   = [];     // { source, label, file?, status, newId, error }
  let running = false;
  let stopped = false;
  let files   = [];
  let entries = [];     // hasil parseEntries saat Mulai — dipakai format "Sesuai input"
  let modStop = null;   // stop() pengecek status review Roblox

  /* ============================================================
     STORAGE (per browser) — API key hanya kalau "Ingat" dicentang
     ============================================================ */
  function loadSettings() {
    try {
      const s = JSON.parse(localStorage.getItem(STORE_KEY) || '{}');
      if (s.creatorType) $('spCreatorType').value = s.creatorType;
      if (s.creatorId)   $('spCreatorId').value   = s.creatorId;
      if (s.universeId)  $('spUniverseId').value  = s.universeId;
      $('spAutoGrant').checked = !!s.autoGrant;
      if (s.apiKey) {
        $('spApiKey').value = s.apiKey;
        $('spRemember').checked = true;
      }
    } catch (e) { /* storage diblok → abaikan */ }
  }

  function saveSettings() {
    try {
      const s = {
        creatorType: $('spCreatorType').value,
        creatorId:   $('spCreatorId').value.trim(),
        universeId:  $('spUniverseId').value.trim(),
        autoGrant:   $('spAutoGrant').checked,
      };
      if ($('spRemember').checked) s.apiKey = $('spApiKey').value.trim();
      localStorage.setItem(STORE_KEY, JSON.stringify(s));
    } catch (e) { /* abaikan */ }
  }

  /* ============================================================
     PARSE INPUT
     ============================================================ */
  // Baris "Nama Lagu{123}" / "Nama{123,456}" → entry bernama (format dipertahankan di output).
  // Selain itu: angka pertama tiap token — "123", "rbxassetid://123", ".../library/123/Nama"
  function parseEntries(text) {
    const entries = [];
    text.split(/\r?\n/).forEach(line => {
      const m = line.match(/^(.*?)\s*\{\s*([\d\s,]+)\}\s*$/);
      if (m) {
        const ids = (m[2].match(/\d{3,20}/g) || []);
        if (ids.length) entries.push({ name: m[1].trim(), ids });
        return;
      }
      line.split(/[\s,;]+/).forEach(tok => {
        const n = tok.match(/\d{3,20}/);
        if (n) entries.push({ name: '', ids: [n[0]] });
      });
    });
    return entries;
  }

  // ID unik (urut sesuai input) + nama pertama yang dipakai ID itu
  function parseIds(text) {
    const seen = new Map();
    parseEntries(text).forEach(e => e.ids.forEach(id => {
      if (!seen.has(id)) seen.set(id, e.name);
    }));
    return Array.from(seen, ([id, name]) => ({ id, name }));
  }

  function updateCounts() {
    const ids = parseIds($('spIds').value);
    $('spIdCount').textContent = ids.length + ' ID terdeteksi';
    $('spFileCount').textContent = files.length
      ? files.length + ' file: ' + files.slice(0, 5).map(f => f.name).join(', ') + (files.length > 5 ? ', …' : '')
      : '0 file dipilih';
  }

  function activeTab() {
    const t = document.querySelector('.sp-tab.active');
    return t ? t.dataset.spTab : 'ids';
  }

  /* ============================================================
     RENDER
     ============================================================ */
  const STATUS_LABEL = { wait: 'Antri', run: 'Proses…', ok: 'Berhasil', err: 'Gagal' };

  function renderRows() {
    const body = $('spRows');
    if (!body) return;   // pindah halaman (SPA) saat proses jalan
    if (!items.length) {
      body.innerHTML = '<tr class="sp-empty"><td colspan="4">Hasil muncul di sini.</td></tr>';
      return;
    }
    body.innerHTML = items.map((it, i) => `
      <tr>
        <td>${i + 1}</td>
        <td class="sp-mono">${esc(it.label)}</td>
        <td><span class="sp-status ${it.status}">${esc(it.status === 'err' ? it.error : (it.note || STATUS_LABEL[it.status]))}</span></td>
        <td class="sp-mono">${it.newId
          ? esc(it.newId) + ' ' + copyIcon(it.newId) + `<span class="sp-mod">${it.mod || it.modErr ? moderationBadge(it.mod, it.modErr) : '<span class="sp-status run">Menunggu review…</span>'}</span>`
          : '—'}</td>
      </tr>`).join('');
  }

  function renderProgress() {
    if (!$('spProgressBar')) return;
    const done = items.filter(i => i.status === 'ok' || i.status === 'err').length;
    const ok   = items.filter(i => i.status === 'ok').length;
    const err  = items.filter(i => i.status === 'err').length;
    $('spProgressBar').style.width = items.length ? (done / items.length * 100) + '%' : '0';
    $('spProgressText').textContent = items.length
      ? `${done}/${items.length} selesai · ${ok} berhasil · ${err} gagal${running ? (stopped ? ' · berhenti…' : ' · jalan') : ''}`
      : 'Belum ada proses';
  }

  function renderOutput() {
    if (!$('spOutput')) return;
    const ok  = items.filter(i => i.status === 'ok');
    const fmt = $('spFormat').value;
    let text  = '';
    switch (fmt) {
      case 'rbx':   text = ok.map(i => 'rbxassetid://' + i.newId).join('\n'); break;
      case 'comma': text = ok.map(i => i.newId).join(', '); break;
      case 'map':   text = ok.map(i => i.label + ' → ' + i.newId).join('\n'); break;
      case 'list': {
        // Sama persis dengan input, ID lama diganti ID baru. Baris yang semua ID-nya gagal dilewati.
        const byId = new Map(ok.map(i => [i.source, i.newId]));
        text = entries.map(e => {
          const ids = e.ids.map(id => byId.get(id)).filter(Boolean);
          if (!ids.length) return null;
          return e.name ? `${e.name}{${ids.join(',')}}` : ids.join('\n');
        }).filter(Boolean).join('\n');
        break;
      }
      case 'lua':
        text = 'return {\n' + ok.map(i => /^\d+$/.test(i.label)
          ? `\t[${i.label}] = ${i.newId},`
          : `\t[${JSON.stringify(i.label)}] = ${i.newId},`).join('\n') + '\n}';
        break;
      default:      text = ok.map(i => i.newId).join('\n');
    }
    $('spOutput').value = ok.length ? text : '';
  }

  function renderAll() {
    renderRows();
    renderProgress();
    renderOutput();
  }

  /* ============================================================
     API
     ============================================================ */
  async function callApi(body, isForm) {
    const res = await fetch(window.__apiUrls.spoof, isForm
      ? { method: 'POST', body }
      : { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('Respons server tidak valid (HTTP ' + res.status + ')');
    }
    if (data.error) throw new Error(data.error);
    return data;
  }

  async function processItem(it, cfg) {
    it.status = 'run';
    renderAll();

    let data;
    if (it.file) {
      const fd = new FormData();
      fd.append('action', 'upload');
      fd.append('apiKey', cfg.apiKey);
      fd.append('creatorType', cfg.creatorType);
      fd.append('creatorId', cfg.creatorId);
      if (cfg.name) fd.append('name', cfg.name);
      fd.append('file', it.file);
      data = await callApi(fd, true);
    } else {
      data = await callApi({
        action: 'reupload',
        apiKey: cfg.apiKey,
        creatorType: cfg.creatorType,
        creatorId: cfg.creatorId,
        assetId: it.source,
        name: cfg.name ? cfg.name + ' ' + it.source : (it.name || ''),
      });
    }

    // Roblox masih memproses → polling status operasi
    for (let n = 0; !data.assetId && data.operationId && n < POLL_MAX; n++) {
      it.note = 'Menunggu Roblox…';
      renderRows();
      await sleep(POLL_MS);
      data = await callApi({ action: 'status', apiKey: cfg.apiKey, operationId: data.operationId });
    }
    if (!data.assetId) throw new Error('Timeout menunggu Roblox — cek Creator Dashboard nanti');

    it.newId  = String(data.assetId);
    it.note   = '';
    it.status = 'ok';
    startModWatch(cfg.apiKey);
  }

  async function worker(queue, cfg) {
    while (queue.length && !stopped) {
      const it = queue.shift();
      try {
        await processItem(it, cfg);
      } catch (e) {
        it.status = 'err';
        it.error  = e.message || 'Gagal';
      }
      renderAll();
    }
  }

  /* ============================================================
     START / STOP
     ============================================================ */
  async function start() {
    if (running) return;

    const cfg = {
      apiKey:      $('spApiKey').value.trim(),
      creatorType: $('spCreatorType').value,
      creatorId:   $('spCreatorId').value.trim(),
      name:        $('spName').value.trim(),
    };
    if (!cfg.apiKey) return showToast('Masukkan API key Roblox dulu', 'error');
    if (!/^\d+$/.test(cfg.creatorId)) return showToast('Isi User ID / Group ID kamu (angka)', 'error');

    if (activeTab() === 'files') {
      if (!files.length) return showToast('Pilih file dulu', 'error');
      entries = [];
      items = files.map(f => ({ source: f.name, label: f.name, file: f, status: 'wait' }));
    } else {
      const ids = parseIds($('spIds').value);
      if (!ids.length) return showToast('Tempel minimal 1 asset ID', 'error');
      entries = parseEntries($('spIds').value);
      items = ids.map(({ id, name }) => ({ source: id, name, label: name ? `${name} (${id})` : id, status: 'wait' }));
      if (ids.some(i => i.name)) $('spFormat').value = 'list';
    }

    saveSettings();
    askNotify();
    running = true;
    stopped = false;
    $('spStart').disabled = true;
    $('spStop').disabled  = false;
    renderAll();

    const queue = items.slice();
    await Promise.all(Array.from({ length: Math.min(CONCURRENCY, queue.length) }, () => worker(queue, cfg)));

    // Item yang belum sempat jalan karena di-stop
    items.forEach(it => {
      if (it.status === 'wait') { it.status = 'err'; it.error = 'Dibatalkan'; }
    });

    running = false;
    if ($('spStart')) {
      $('spStart').disabled = false;
      $('spStop').disabled  = true;
    }
    renderAll();

    const ok = items.filter(i => i.status === 'ok').length;
    showToast(`${ok}/${items.length} aset berhasil di-upload`, ok === items.length ? 'success' : 'warning', 3500);

    // Otomatis izinkan semua aset baru ke game
    const universeId = $('spUniverseId') ? $('spUniverseId').value.trim() : '';
    if (ok && $('spAutoGrant') && $('spAutoGrant').checked && /^\d+$/.test(universeId)) {
      $('spGrantIds').value = items.filter(i => i.status === 'ok').map(i => i.newId).join('\n');
      await grantFromBox();
    }
  }

  /** Cek status review aset hasil upload sampai semua Approved / Rejected */
  function startModWatch(apiKey) {
    if (modStop || !apiKey) return;
    modStop = watchModeration({
      apiKey,
      getIds: () => items.filter(i => i.status === 'ok' && !MOD_FINAL[i.mod]).map(i => i.newId),
      onUpdate: (r) => {
        items.forEach(i => {
          if (i.status !== 'ok') return;
          if (r.states[i.newId]) { i.mod = r.states[i.newId]; i.modErr = ''; }
          else if (r.errors[i.newId]) i.modErr = r.errors[i.newId];
        });
        renderRows();
      },
      onDone: (allFinal) => {
        modStop = null;
        const up = items.filter(i => i.status === 'ok');
        if (allFinal && up.length) {
          const ok = up.filter(i => i.mod === 'Approved').length;
          showToast(`Review selesai: ${ok}/${up.length} aset siap dipakai`, ok === up.length ? 'success' : 'warning', 5000);
          notify('ARRR Studio — review selesai', `${ok}/${up.length} aset siap dipakai di Roblox`);
        }
      },
    });
  }

  /* ============================================================
     IZIN GAME MASSAL — helper dipakai juga oleh ytmp3.js
     ============================================================ */
  // → {granted:[...], failed:{id: alasan}, universeId, placeId?}
  // placeId terisi kalau ID yang diisi ternyata Place ID (server mengonversi ke Universe ID)
  async function grantAssets(apiKey, universeId, ids, onProgress) {
    const granted = [];
    const failed  = {};
    let placeId = null;
    // Maks 200 per request (batas server)
    for (let i = 0; i < ids.length; i += 200) {
      const part = ids.slice(i, i + 200);
      if (onProgress) onProgress(i, ids.length);
      try {
        const r = await callApi({ action: 'grant', apiKey, universeId, assetIds: part });
        granted.push(...r.granted);
        Object.assign(failed, r.failed || {});
        if (r.placeId && r.universeId) {
          placeId = r.placeId;
          universeId = r.universeId;   // batch berikutnya langsung pakai Universe ID
          saveUniverseId(universeId);
        }
      } catch (e) {
        part.forEach(id => { failed[id] = e.message; });
      }
    }
    return { granted, failed, universeId, placeId };
  }
  window.ArrrGrant = grantAssets;

  /** Simpan Universe ID hasil konversi ke setting bersama (dipakai semua halaman) */
  function saveUniverseId(id) {
    try {
      const s = JSON.parse(localStorage.getItem(STORE_KEY) || '{}');
      s.universeId = id;
      localStorage.setItem(STORE_KEY, JSON.stringify(s));
    } catch (e) { /* abaikan */ }
    ['spUniverseId', 'ytUniverseId', 'hsUniverseId'].forEach(f => { if ($(f)) $(f).value = id; });
  }

  /** Ringkasan hasil izin game / publik. mode: 'game' | 'public' */
  function grantSummaryHtml(r, mode) {
    const isPublic  = mode === 'public';
    const failedIds = Object.keys(r.failed);
    // Kelompokkan alasan yang sama biar tidak panjang
    const byReason = {};
    failedIds.forEach(id => { (byReason[r.failed[id]] = byReason[r.failed[id]] || []).push(id); });
    const doneText = isPublic
      ? `${r.granted.length} aset dijadikan publik`
      : `${r.granted.length} aset diizinkan${r.universeId ? ' ke game ' + esc(r.universeId) : ''}`;
    const rows = (r.placeId
      ? [`<li class="warn">${esc(r.placeId)} itu Place ID → dipakai Universe ID ${esc(r.universeId)} (sudah disimpan)</li>`]
      : [])
      .concat([`<li class="${r.granted.length ? 'ok' : 'warn'}">${doneText}</li>`])
      .concat(Object.entries(byReason).map(([reason, list]) =>
        `<li class="err">${list.length} gagal: ${esc(reason)} <span class="sp-mono">(${esc(list.slice(0, 5).join(', '))}${list.length > 5 ? ', …' : ''})</span></li>`));
    const title = failedIds.length
      ? (r.granted.length ? 'Sebagian gagal' : 'Gagal')
      : (isPublic ? 'Semua aset sudah publik' : 'Semua aset sudah diizinkan ke game');
    const buttons = [
      r.granted.length ? copyButton(r.granted.join('\n'), `Copy ${r.granted.length} ID berhasil`) : '',
      failedIds.length ? copyButton(failedIds.join('\n'), `Copy ${failedIds.length} ID gagal`) : '',
      failedIds.length ? copyButton(failedIds.map(id => `${id}: ${r.failed[id]}`).join('\n'), 'Copy pesan error') : '',
    ].filter(Boolean).join('');
    return `<p><b>${title}</b></p><ul>${rows.join('')}</ul>${buttons ? `<div class="sp-copy-row">${buttons}</div>` : ''}`;
  }
  window.ArrrGrantSummary = grantSummaryHtml;

  /* ============================================================
     JADIKAN PUBLIK — gambar / decal / mesh (audio tidak bisa lewat API)
     ============================================================ */
  // → {granted:[...], failed:{id: alasan}}
  async function makePublic(apiKey, ids) {
    const granted = [];
    const failed  = {};
    for (let i = 0; i < ids.length; i += 200) {
      const part = ids.slice(i, i + 200);
      try {
        const r = await callApi({ action: 'public', apiKey, assetIds: part });
        granted.push(...r.granted);
        Object.assign(failed, r.failed || {});
      } catch (e) {
        part.forEach(id => { failed[id] = e.message; });
      }
    }
    return { granted, failed };
  }
  window.ArrrPublic = makePublic;

  /* ============================================================
     TOMBOL COPY — <button data-copy="teks"> di halaman mana pun
     ============================================================ */
  function copyButton(text, label, cls) {
    return `<button type="button" class="${cls || 'sp-btn-ghost sp-copy-btn'}" data-copy="${esc(text)}">${esc(label)}</button>`;
  }
  /** Tombol kecil ⧉ di sebelah asset ID */
  function copyIcon(id) {
    return `<button type="button" class="sp-copy" data-copy="${esc(id)}" title="Copy ${esc(id)}" aria-label="Copy ${esc(id)}">⧉</button>`;
  }
  window.ArrrCopyButton = copyButton;
  window.ArrrCopyIcon   = copyIcon;

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
    // http:// (XAMPP tanpa HTTPS) → clipboard API tidak ada, pakai textarea
    return new Promise((resolve, reject) => {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand('copy');
      ta.remove();
      ok ? resolve() : reject(new Error('copy gagal'));
    });
  }
  window.ArrrCopyText = copyText;

  if (!window.__arrrCopyBound) {
    window.__arrrCopyBound = true;
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-copy]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      const text  = btn.getAttribute('data-copy');
      const lines = text.split('\n').length;
      copyText(text)
        .then(() => {
          showToast(lines > 1 ? `${lines} baris di-copy` : `${text.length > 40 ? 'Teks' : text} di-copy`);
          btn.classList.add('copied');
          setTimeout(() => btn.classList.remove('copied'), 1200);
        })
        .catch(() => showToast('Gagal copy', 'error'));
    });
  }

  /* ============================================================
     STATUS REVIEW ROBLOX — helper dipakai spoofer, ytmp3, history
     ============================================================ */
  const MOD_FINAL = { Approved: true, Rejected: true };
  const MOD_LABEL = { Reviewing: 'Direview…', Approved: 'Siap dipakai', Rejected: 'Ditolak moderasi' };
  const MOD_CLASS = { Reviewing: 'run', Approved: 'ok', Rejected: 'err' };

  /** → {states:{id: state}, errors:{id: pesan}} (dipecah 25 per request) */
  async function checkModeration(apiKey, ids) {
    const states = {};
    const errors = {};
    for (let i = 0; i < ids.length; i += 25) {
      try {
        const r = await callApi({ action: 'moderation', apiKey, assetIds: ids.slice(i, i + 25) });
        Object.assign(states, r.states || {});
        Object.assign(errors, r.errors || {});
      } catch (e) {
        ids.slice(i, i + 25).forEach(id => { errors[id] = e.message; });
      }
    }
    return { states, errors };
  }

  /**
   * Cek berkala sampai semua final (Approved/Rejected) atau lewat maxMinutes.
   * getIds() → asset ID yang masih perlu dicek; onUpdate(result) tiap putaran; onDone() di akhir.
   * Balas fungsi stop().
   */
  function watchModeration({ apiKey, getIds, onUpdate, onDone, intervalMs = window.__arrrModInterval || 30000, maxMinutes = 60 }) {
    let stopped = false;
    let timer   = null;
    const until = Date.now() + maxMinutes * 60000;
    const tick = async () => {
      if (stopped) return;
      const ids = getIds();
      if (!ids.length || Date.now() > until) {
        stopped = true;
        if (onDone) onDone(!ids.length);
        return;
      }
      const r = await checkModeration(apiKey, ids);
      if (stopped) return;
      onUpdate(r);
      timer = setTimeout(tick, intervalMs);
    };
    tick();
    return () => { stopped = true; clearTimeout(timer); };
  }

  function moderationBadge(state, error) {
    if (!state && !error) return '';
    if (!state) return `<span class="sp-status warn" title="${esc(error)}">Status belum bisa dicek</span>`;
    return `<span class="sp-status ${MOD_CLASS[state] || 'run'}">${esc(MOD_LABEL[state] || state)}</span>`;
  }

  /** Minta izin notifikasi browser (panggil dari klik user) */
  function askNotify() {
    try {
      if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
    } catch (e) { /* abaikan */ }
  }

  /** Notifikasi browser kalau tab sedang tidak dilihat (review bisa lama) */
  function notify(title, body) {
    try {
      if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
        new Notification(title, { body, icon: (window.__logoUrl || undefined) });
      }
    } catch (e) { /* abaikan */ }
  }

  window.ArrrModeration = {
    check: checkModeration, watch: watchModeration, badge: moderationBadge,
    isFinal: (s) => !!MOD_FINAL[s], askNotify, notify,
  };

  async function grantFromBox() {
    const apiKey     = $('spApiKey').value.trim();
    const universeId = $('spUniverseId').value.trim();
    const ids        = parseIds($('spGrantIds').value).map(x => x.id);
    if (!apiKey) return showToast('Masukkan API key Roblox dulu', 'error');
    if (!/^\d+$/.test(universeId)) {
      $('spUniverseId').focus();
      return showToast('Isi Universe ID game di card 01', 'error');
    }
    if (!ids.length) return showToast('Tempel minimal 1 asset ID', 'error');
    saveSettings();

    const btn = $('spGrant');
    btn.disabled = true;
    $('spGrantStatus').textContent = `Mengizinkan ${ids.length} aset…`;
    const r = await grantAssets(apiKey, universeId, ids);
    if (!$('spGrant')) return;   // pindah halaman (SPA)
    btn.disabled = false;
    $('spGrantStatus').textContent = `${r.granted.length}/${ids.length} diizinkan`;
    const box = $('spGrantResult');
    box.hidden = false;
    box.className = 'sp-check-result ' + (Object.keys(r.failed).length ? 'err' : 'ok');
    box.innerHTML = grantSummaryHtml(r, 'game');
    showToast(`${r.granted.length}/${ids.length} aset diizinkan ke game`, r.granted.length === ids.length ? 'success' : 'warning', 3500);
  }

  async function publicFromBox() {
    const apiKey = $('spApiKey').value.trim();
    const ids    = parseIds($('spGrantIds').value).map(x => x.id);
    if (!apiKey) return showToast('Masukkan API key Roblox dulu', 'error');
    if (!ids.length) return showToast('Tempel minimal 1 asset ID', 'error');
    if (!confirm(`Jadikan ${ids.length} aset PUBLIK? Siapa saja bisa memakai aset ini di game mereka.`)) return;

    const btn = $('spPublic');
    btn.disabled = true;
    $('spGrantStatus').textContent = `Menjadikan ${ids.length} aset publik…`;
    const r = await makePublic(apiKey, ids);
    if (!$('spPublic')) return;   // pindah halaman (SPA)
    btn.disabled = false;
    $('spGrantStatus').textContent = `${r.granted.length}/${ids.length} publik`;
    const box = $('spGrantResult');
    box.hidden = false;
    box.className = 'sp-check-result ' + (Object.keys(r.failed).length ? 'err' : 'ok');
    box.innerHTML = grantSummaryHtml(r, 'public');
    showToast(`${r.granted.length}/${ids.length} aset jadi publik`, r.granted.length === ids.length ? 'success' : 'warning', 3500);
  }

  /* ============================================================
     CEK KONEKSI API KEY
     ============================================================ */
  async function checkConnection() {
    const apiKey = $('spApiKey').value.trim();
    if (!apiKey) return showToast('Masukkan API key Roblox dulu', 'error');
    const testId = (parseIds($('spTestId').value)[0] || {}).id || '';

    const box = $('spCheckResult');
    const btn = $('spCheck');
    btn.disabled = true;
    btn.textContent = 'Mengecek…';
    box.hidden = false;
    box.className = 'sp-check-result';
    box.innerHTML = '<p>Menghubungi Roblox…</p>';

    const line = (ok, text) => `<li class="${ok === null ? 'warn' : (ok ? 'ok' : 'err')}">${esc(text)}</li>`;
    try {
      const r = await callApi({ action: 'check', apiKey, assetId: testId });
      const rows = [];
      if (r.introspected) {
        rows.push(line(r.enabled !== false && r.expired !== true,
          'API key ' + (r.expired ? 'kedaluwarsa' : (r.enabled === false ? 'nonaktif' : 'valid & aktif'))
          + (r.name ? ' — "' + r.name + '"' : '')));
        if (r.userId) rows.push(line(true, 'Pemilik key: User ID ' + r.userId));
        if (r.scopes.length) {
          ['asset:read', 'asset:write', 'legacy-asset:manage'].forEach(op =>
            rows.push(line(!r.missing.includes(op), 'Scope ' + op + (r.missing.includes(op) ? ' — belum ada' : ''))));
        } else {
          rows.push(line(null, 'Scope tidak bisa dibaca dari Roblox — lihat hasil tes download'));
        }
        if (r.scopes.length) {
          const miss = (r.missingOptional || []).includes('asset-permissions:write');
          rows.push(line(miss ? null : true, 'Scope asset-permissions:write' + (miss
            ? ' — belum ada (dibutuhkan untuk "Izinkan ke game": API key → Select API System → asset-permissions → write)'
            : ' (untuk izin game)')));
        }
        if (r.rawScopes) rows.push(line(null, 'Data scope dari Roblox: ' + JSON.stringify(r.rawScopes).slice(0, 300)));
      } else {
        rows.push(line(null, 'Detail API key tidak bisa dibaca (introspect tidak tersedia)'));
      }
      if (r.asset && r.userId && r.asset.id === String(r.userId)) {
        rows.push(line(false, `${r.asset.id} itu User ID kamu, bukan Asset ID. Pakai ID aset (gambar/audio) milikmu — lihat di create.roblox.com → Creations, atau angka di link roblox.com/library/<ID>/...`));
      } else if (r.asset) {
        rows.push(line(r.asset.ok, r.asset.ok
          ? `Tes download ${r.asset.id}: berhasil (${r.asset.kind}, ${(r.asset.bytes / 1024).toFixed(0)} KB)`
          : `Tes download ${r.asset.id}: ${r.asset.error}`));
      } else {
        rows.push(line(null, 'Isi Asset ID tes untuk mengecek akses download'));
      }
      box.classList.add(r.ok ? 'ok' : 'err');
      box.innerHTML = `<p><b>${r.ok ? 'Terhubung ke Roblox Open Cloud' : 'Ada yang perlu dibenahi'}</b></p><ul>${rows.join('')}</ul>`;

      // Isi otomatis User ID kalau masih kosong
      if (r.userId && $('spCreatorType').value === 'user' && !$('spCreatorId').value.trim()) {
        $('spCreatorId').value = r.userId;
        saveSettings();
      }
    } catch (e) {
      box.classList.add('err');
      box.innerHTML = `<p><b>Tidak terhubung</b></p><ul>${line(false, e.message)}</ul>`;
    } finally {
      if ($('spCheck')) {
        btn.disabled = false;
        btn.textContent = 'Cek koneksi';
      }
    }
  }

  function copyOutput() {
    const text = $('spOutput').value;
    if (!text) return showToast('Belum ada hasil', 'warning');
    navigator.clipboard.writeText(text)
      .then(() => showToast('Asset ID di-copy'))
      .catch(() => { $('spOutput').select(); document.execCommand('copy'); showToast('Asset ID di-copy'); });
  }

  function downloadOutput() {
    const text = $('spOutput').value;
    if (!text) return showToast('Belum ada hasil', 'warning');
    const isLua = $('spFormat').value === 'lua';
    const blob = new Blob([text], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = isLua ? 'SpoofedAssets.lua' : 'spoofed-asset-ids.txt';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  /* ============================================================
     INIT
     ============================================================ */
  function initSpoofer() {
    const root = $('spStart');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';

    loadSettings();

    document.querySelectorAll('.sp-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.sp-tab').forEach(t => t.classList.toggle('active', t === tab));
        document.querySelectorAll('.sp-pane').forEach(p => p.classList.toggle('active', p.dataset.spPane === tab.dataset.spTab));
      });
    });

    $('spToggleKey').addEventListener('click', () => {
      const input = $('spApiKey');
      input.type = input.type === 'password' ? 'text' : 'password';
      $('spToggleKey').textContent = input.type === 'password' ? 'Lihat' : 'Tutup';
    });

    $('spRemember').addEventListener('change', saveSettings);
    $('spIds').addEventListener('input', updateCounts);
    $('spFiles').addEventListener('change', (e) => {
      files = Array.from(e.target.files || []);
      updateCounts();
    });

    const drop = $('spDrop');
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, () => drop.classList.add('drag')));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, () => drop.classList.remove('drag')));

    root.addEventListener('click', start);
    $('spCheck').addEventListener('click', checkConnection);
    $('spGrant').addEventListener('click', grantFromBox);
    $('spPublic').addEventListener('click', publicFromBox);
    $('spGrantFill').addEventListener('click', () => {
      const ids = items.filter(i => i.status === 'ok').map(i => i.newId);
      if (!ids.length) return showToast('Belum ada hasil upload', 'warning');
      $('spGrantIds').value = ids.join('\n');
    });
    $('spUniverseId').addEventListener('change', saveSettings);
    $('spAutoGrant').addEventListener('change', saveSettings);
    $('spStop').addEventListener('click', () => { stopped = true; renderProgress(); });
    $('spFormat').addEventListener('change', renderOutput);
    $('spCopy').addEventListener('click', copyOutput);
    $('spDownload').addEventListener('click', downloadOutput);

    if ($('spAnimStart')) {
      $('spAnimStart').addEventListener('click', animStart);
      $('spAnimZip').addEventListener('click', animZip);
      $('spAnimClear').addEventListener('click', () => { if (!animRunning) { animItems = []; $('spAnimStatus').textContent = ''; renderAnim(); } });
      $('spAnimIds').addEventListener('input', updateAnimCount);
      updateAnimCount();
    }

    files = [];
    updateCounts();
    renderAll();
  }

  /* ============================================================
     ANIMATION → .rbxm (download massal; upload manual di Studio)
     ============================================================ */
  let animItems = [];   // { animId, status:'wait'|'run'|'ok'|'err', token?, filename?, size?, kind?, error? }
  let animRunning = false;

  function animDownloadUrl(token) {
    return window.__apiUrls.spoof + '?action=anim_download&token=' + encodeURIComponent(token);
  }

  function renderAnim() {
    const wrap = $('spAnimTableWrap'), body = $('spAnimRows');
    if (!body) return;
    wrap.hidden = animItems.length === 0;
    body.innerHTML = animItems.map((it, i) => {
      const st = it.status === 'err' ? esc(it.error || 'Gagal')
        : it.status === 'ok' ? (it.kind === 'animation' ? 'Selesai' : 'Selesai (bukan animasi?)')
        : it.status === 'run' ? 'Unduh…' : 'Antri';
      const cls = it.status === 'ok' ? (it.kind === 'animation' ? 'ok' : 'run') : it.status;
      return `<tr>
        <td>${i + 1}</td>
        <td class="sp-mono">${esc(it.animId)}</td>
        <td>${it.filename ? esc(it.filename) : '—'}</td>
        <td><span class="sp-status ${cls}">${st}${it.size ? ' · ' + (it.size / 1024).toFixed(1) + ' KB' : ''}</span></td>
        <td>${it.token ? `<a class="sp-btn-ghost" style="height:30px;padding:0 12px;font-size:12px;text-decoration:none;display:inline-flex;align-items:center" href="${esc(animDownloadUrl(it.token))}" data-no-spa download>.rbxm</a>` : '—'}</td>
      </tr>`;
    }).join('');
    const ok = animItems.filter(i => i.status === 'ok').length;
    $('spAnimZip').disabled = animRunning || ok === 0;
    $('spAnimZip').textContent = ok ? `Download semua (${ok}, .zip)` : 'Download semua (.zip)';
    $('spAnimClear').disabled = animRunning;
    if ($('spAnimStart')) $('spAnimStart').disabled = animRunning || !window.__isLoggedIn;
  }

  function animTriggerDownload(url) {
    const a = document.createElement('a');
    a.href = url; a.setAttribute('download', ''); a.setAttribute('data-no-spa', '');
    document.body.appendChild(a); a.click(); a.remove();
  }

  async function animWorker(queue, apiKey) {
    while (queue.length) {
      const it = queue.shift();
      it.status = 'run'; renderAnim();
      try {
        const data = await callApi({ action: 'anim', apiKey, animId: it.animId, name: it.name || '' });
        Object.assign(it, { status: 'ok', token: data.token, filename: data.filename, size: data.size, kind: data.kind });
      } catch (e) {
        it.status = 'err'; it.error = e.message || 'Gagal';
      }
      renderAnim();
    }
  }

  async function animStart() {
    if (animRunning) return;
    const apiKey = $('spApiKey').value.trim();
    if (!apiKey) { $('spApiKey').focus(); return showToast('Isi API key Roblox dulu (card 01)', 'error'); }
    const parsed = parseIds($('spAnimIds').value);
    if (!parsed.length) return showToast('Tempel minimal 1 Animation ID', 'error');
    saveSettings();

    animItems = parsed.map(x => ({ animId: x.id, name: x.name || '', status: 'wait' }));
    animRunning = true;
    $('spAnimStatus').textContent = 'Mengunduh…';
    renderAnim();
    const queue = animItems.slice();
    await Promise.all(Array.from({ length: Math.min(CONCURRENCY, queue.length) }, () => animWorker(queue, apiKey)));
    animRunning = false;
    const ok = animItems.filter(i => i.status === 'ok').length;
    $('spAnimStatus').textContent = `${ok}/${animItems.length} animasi terunduh`;
    renderAnim();
    showToast(`${ok}/${animItems.length} animasi jadi .rbxm`, ok === animItems.length ? 'success' : 'warning', 3500);
  }

  function animZip() {
    const tokens = animItems.filter(i => i.status === 'ok' && i.token).map(i => i.token);
    if (!tokens.length) return showToast('Belum ada file', 'warning');
    animTriggerDownload(window.__apiUrls.spoof + '?action=anim_zip&tokens=' + tokens.join(','));
    showToast('Menyiapkan ZIP…');
  }

  function updateAnimCount() {
    if (!$('spAnimIds')) return;
    $('spAnimCount').textContent = parseIds($('spAnimIds').value).length + ' ID terdeteksi';
  }

  window.initSpoofer = initSpoofer;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSpoofer);
  } else {
    initSpoofer();
  }
  window.addEventListener('spa:navigated', initSpoofer);
})();
