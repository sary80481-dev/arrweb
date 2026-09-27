/* ============================================================
 *  assets/js/pages/ytmp3.js — YT → MP3 massal (SPA-ready)
 *  Tiap video = 1 request "convert" ke api/ytmp3.php, dijalankan paralel.
 *  Link playlist di-expand dulu jadi daftar video (action "playlist").
 *  Audio Enhancement (speed + pitch) diproses di server; script kompensasi
 *  Luau di-generate di sini supaya audio terdengar normal lagi di game.
 *  Upload ke Roblox: hasil convert dikirim langsung dari server lewat
 *  api/spoof.php (action "ytmp3") — setting dipakai bersama Auto Spoof.
 * ============================================================ */
(function () {
  'use strict';

  const ID_RE = /^[A-Za-z0-9_-]{11}$/;

  const esc = (t) => String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const $ = (id) => document.getElementById(id);

  let items   = [];     // { videoId, label, status, token?, filename?, size?, error? }
  let running = false;
  let stopped = false;
  let tools   = null;   // status yt-dlp & ffmpeg dari server (action "tools")
  // item.rbx = { status: 'wait'|'run'|'ok'|'err', assetId?, error?, note? } — upload ke Roblox

  const SPOOF_KEY = 'arrr_spoof_settings';   // sama dengan spoofer.js
  const OWN_KEY   = 'arrr_ytmp3_settings';
  const POLL_MS   = 2000;
  const POLL_MAX  = 60;
  const sleep = (ms) => new Promise(r => setTimeout(r, ms));
  let modStop = null;   // stop() pengecek status review Roblox (ArrrModeration.watch)
  let installing = false;

  /* ============================================================
     PARSE INPUT
     ============================================================ */
  function parseLink(raw) {
    const tok = raw.trim();
    if (!tok) return null;
    if (ID_RE.test(tok)) return { type: 'video', id: tok };

    let u;
    try {
      u = new URL(/^https?:\/\//i.test(tok) ? tok : 'https://' + tok);
    } catch (e) {
      return null;
    }
    const host = u.hostname.toLowerCase().replace(/^(www|m|music)\./, '');

    if (host === 'youtu.be') {
      const id = u.pathname.slice(1, 12);
      return ID_RE.test(id) ? { type: 'video', id } : null;
    }
    if (host !== 'youtube.com' && host !== 'youtube-nocookie.com') return null;

    // watch?v=...&list=... → ambil videonya saja
    const v = u.searchParams.get('v');
    if (v && ID_RE.test(v)) return { type: 'video', id: v };

    const m = u.pathname.match(/^\/(?:shorts|embed|live|v|e)\/([A-Za-z0-9_-]{11})/);
    if (m) return { type: 'video', id: m[1] };

    const list = u.searchParams.get('list');
    if (list && /^[A-Za-z0-9_-]{10,64}$/.test(list) && !list.startsWith('RD')) {
      return { type: 'playlist', id: list, url: 'https://www.youtube.com/playlist?list=' + list };
    }
    return null;
  }

  function parseInput(text) {
    const videos = [], playlists = [], invalid = [];
    const seen = new Set();
    text.split(/[\s,;]+/).forEach(tok => {
      if (!tok) return;
      const p = parseLink(tok);
      if (!p) return invalid.push(tok);
      if (seen.has(p.type + p.id)) return;
      seen.add(p.type + p.id);
      (p.type === 'video' ? videos : playlists).push(p);
    });
    return { videos, playlists, invalid };
  }

  function updateCount() {
    if (!$('ytLinks')) return;
    const { videos, playlists, invalid } = parseInput($('ytLinks').value);
    $('ytCount').textContent = `${videos.length} video · ${playlists.length} playlist terdeteksi`
      + (invalid.length ? ` · ${invalid.length} link tidak dikenali` : '');
  }

  /* ============================================================
     TOOLS (yt-dlp + ffmpeg) — cek status & install otomatis
     ============================================================ */
  const TOOL_LABEL = { ytdlp: 'yt-dlp', ffmpeg: 'ffmpeg' };
  const toolsReady = () => !!(tools && tools.ytdlp.ok && tools.ffmpeg.ok);

  function toolsMsg(text, isErr) {
    const el = $('ytToolsMsg');
    if (!el) return;
    el.textContent = text || '';
    el.classList.toggle('err', !!isErr);
  }

  function renderTools() {
    const box = $('ytTools');
    if (!box) return;
    const setChip = (id, t) => {
      const el = $(id);
      el.classList.toggle('ok', !!(t && t.ok));
      el.classList.toggle('miss', !!(t && !t.ok));
      el.querySelector('b').textContent = !t ? 'cek…' : (t.ok ? (t.version || 'OK') : 'belum ada');
    };
    setChip('ytToolYtdlp', tools && tools.ytdlp);
    setChip('ytToolFfmpeg', tools && tools.ffmpeg);

    box.dataset.state = !tools ? 'loading' : (toolsReady() ? 'ok' : 'missing');
    const missing = tools ? ['ytdlp', 'ffmpeg'].filter(k => !tools[k].ok) : [];
    const canAuto = tools && missing.some(k => tools.autoInstall[k]);

    $('ytToolsInstall').hidden   = !canAuto;
    $('ytToolsInstall').disabled = installing;
    $('ytToolsUpdate').hidden    = !(tools && tools.ytdlp.ok && tools.autoInstall.ytdlp);
    $('ytToolsUpdate').disabled  = installing || running;
    $('ytToolsRefresh').disabled = installing;
    $('ytToolsHelp').hidden      = !tools || toolsReady();
  }

  async function checkTools() {
    if (!window.__isLoggedIn || !$('ytTools')) return;
    tools = null;
    renderTools();
    try {
      tools = await callApi({ action: 'tools' });
      if (!installing) {
        toolsMsg(toolsReady() ? '' : 'yt-dlp dan ffmpeg dibutuhkan untuk convert. Klik "Install otomatis" (sekali saja).');
      }
    } catch (e) {
      toolsMsg('Gagal cek tools: ' + e.message, true);
    }
    renderTools();
  }

  async function installTools(list) {
    if (installing) return;
    installing = true;
    renderTools();
    try {
      for (const tool of list) {
        toolsMsg(`Menginstall ${TOOL_LABEL[tool]}… ${tool === 'ffmpeg'
          ? '(download ±190MB, bisa beberapa menit — jangan tutup halaman)' : '(±20MB)'}`);
        tools = await callApi({ action: 'install', tool });
        renderTools();
      }
      toolsMsg(toolsReady() ? 'Tools siap dipakai.' : 'Sebagian tools belum terpasang — lihat cara manual di bawah.', !toolsReady());
      if (toolsReady()) showToast('yt-dlp & ffmpeg siap');
    } catch (e) {
      toolsMsg('Install gagal: ' + e.message, true);
      $('ytToolsHelp').hidden = false;
    } finally {
      installing = false;
      renderTools();
    }
  }

  function installMissing() {
    if (!tools) return;
    installTools(['ytdlp', 'ffmpeg'].filter(k => !tools[k].ok && tools.autoInstall[k]));
  }

  /* ============================================================
     AUDIO ENHANCEMENT
     ============================================================ */
  function fxSettings() {
    return {
      speed: Math.round(parseFloat($('ytSpeed').value) * 100) / 100 || 1,
      pitch: parseInt($('ytPitch').value, 10) || 0,
    };
  }

  const fmtSpeed = (v) => 'x' + v.toFixed(2);
  const fmtPitch = (v) => (v > 0 ? '+' : '') + v + ' semitone';
  const isFx     = (fx) => fx.speed !== 1 || fx.pitch !== 0;

  // Hasil file: tempo x speed, pitch x 2^(pitch/12).
  // Di game: PlaybackSpeed = 1/speed (tempo normal, pitch ikut turun)
  //          + PitchShiftSoundEffect.Octave = speed / 2^(pitch/12) (pitch normal).
  function compensationScript(fx) {
    const octave = fx.speed / Math.pow(2, fx.pitch / 12);
    return `--[[
	AudioCompensation — ModuleScript (generated by ARRR Studio)
	Audio di-export dengan Speed ${fmtSpeed(fx.speed)}, Pitch ${fmtPitch(fx.pitch)}.
	Module ini mengembalikan tempo & pitch ke normal saat diputar di game.

	Pasang: taruh ModuleScript ini di ReplicatedStorage, lalu:
		local AudioCompensation = require(game.ReplicatedStorage.AudioCompensation)
		AudioCompensation.apply(workspace.MySound)   -- satu Sound
		AudioCompensation.applyTagged("Compensated") -- semua Sound dengan tag CollectionService
	Lalu play seperti biasa (sound:Play()).
]]

local AudioCompensation = {}

AudioCompensation.SPEED = ${fx.speed}  -- speed saat export
AudioCompensation.PITCH = ${fx.pitch}  -- pitch shift saat export (semitone)

local FX_NAME = "ArrrCompensation"

-- Octave kompensasi ≈ ${octave.toFixed(4)}
local function octave(): number
	return AudioCompensation.SPEED / 2 ^ (AudioCompensation.PITCH / 12)
end

function AudioCompensation.apply(sound: Sound)
	assert(sound and sound:IsA("Sound"), "AudioCompensation.apply butuh Sound")

	-- Tempo kembali normal (sekaligus pitch ikut turun)
	sound.PlaybackSpeed = 1 / AudioCompensation.SPEED

	-- Bersihkan kompensasi lama
	for _, child in sound:GetChildren() do
		if child.Name == FX_NAME then
			child:Destroy()
		end
	end

	-- Pitch kembali normal. Octave dibatasi 0.5–2 per efek → pecah jadi 2 kalau perlu
	local target = octave()
	if math.abs(target - 1) < 1e-3 then
		return sound
	end
	local count = (target > 2 or target < 0.5) and 2 or 1
	for i = 1, count do
		local fx = Instance.new("PitchShiftSoundEffect")
		fx.Name = FX_NAME
		fx.Octave = target ^ (1 / count)
		fx.Priority = i
		fx.Parent = sound
	end
	return sound
end

function AudioCompensation.applyTagged(tag: string)
	local CollectionService = game:GetService("CollectionService")
	for _, sound in CollectionService:GetTagged(tag) do
		if sound:IsA("Sound") then
			AudioCompensation.apply(sound)
		end
	end
	CollectionService:GetInstanceAddedSignal(tag):Connect(function(sound)
		if sound:IsA("Sound") then
			AudioCompensation.apply(sound)
		end
	end)
end

return AudioCompensation
`;
  }

  function renderFx() {
    if (!$('ytSpeed')) return;
    const fx = fxSettings();
    $('ytSpeedVal').textContent = fmtSpeed(fx.speed);
    $('ytPitchVal').textContent = fmtPitch(fx.pitch);
    $('ytScriptWrap').hidden = !isFx(fx);
    $('ytScript').value = isFx(fx) ? compensationScript(fx) : '';
  }

  function copyScript() {
    const text = $('ytScript').value;
    if (!text) return;
    navigator.clipboard.writeText(text)
      .then(() => showToast('Script di-copy'))
      .catch(() => { $('ytScript').select(); document.execCommand('copy'); showToast('Script di-copy'); });
  }

  function downloadScript() {
    const text = $('ytScript').value;
    if (!text) return;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([text], { type: 'text/plain' }));
    a.download = 'AudioCompensation.lua';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  /* ============================================================
     RENDER
     ============================================================ */
  const STATUS_LABEL = { wait: 'Antri', run: 'Convert…', ok: 'Selesai', err: 'Gagal' };

  function fmtSize(bytes) {
    return bytes ? (bytes / 1048576).toFixed(1) + ' MB' : '';
  }

  function fmtDuration(sec) {
    if (!sec) return '';
    const m = Math.floor(sec / 60), s = sec % 60;
    return m + ':' + String(s).padStart(2, '0');
  }

  function downloadUrl(token) {
    return window.__apiUrls.ytmp3 + '?action=download&token=' + encodeURIComponent(token);
  }

  function renderRows() {
    const body = $('ytRows');
    if (!body) return;   // pindah halaman (SPA) saat proses jalan
    if (!items.length) {
      body.innerHTML = '<tr class="sp-empty"><td colspan="5">Hasil muncul di sini.</td></tr>';
      return;
    }
    body.innerHTML = items.map((it, i) => `
      <tr>
        <td>${i + 1}</td>
        <td class="yt-video">
          <a href="https://www.youtube.com/watch?v=${esc(it.videoId)}" target="_blank" rel="noopener" data-no-spa>${esc(it.label)}</a>
          <span class="yt-meta">${esc([it.videoId, fmtDuration(it.duration), fmtSize(it.size)].filter(Boolean).join(' · '))}${it.fx && isFx(it.fx)
            ? ` <span class="yt-fx-tag">${esc(fmtSpeed(it.fx.speed) + ' · ' + (it.fx.pitch > 0 ? '+' : '') + it.fx.pitch + 'st')}</span>` : ''}</span>
        </td>
        <td><span class="sp-status ${it.status}">${esc(it.status === 'err' ? it.error : STATUS_LABEL[it.status])}</span></td>
        <td>${it.token
          ? `<a class="sp-btn-ghost yt-dl" href="${esc(downloadUrl(it.token))}" data-no-spa download>MP3</a>`
          : '—'}</td>
        <td class="yt-rbx">${rbxCell(it)}</td>
      </tr>`).join('');
  }

  function rbxCell(it) {
    const r = it.rbx;
    if (!r) return '—';
    if (r.status === 'ok') {
      // Aset baru selalu private → link ke Creator Dashboard (bukan Creator Store, yang hanya untuk aset publik)
      return `<a href="https://create.roblox.com/dashboard/creations/store/${esc(r.assetId)}/configure" target="_blank" rel="noopener" data-no-spa title="Buka di Creator Dashboard">${esc(r.assetId)}</a>`
        + (window.ArrrCopyIcon ? window.ArrrCopyIcon(r.assetId) : '')
        + (r.granted ? ' <span class="yt-fx-tag" title="Diizinkan di game">✓ game</span>' : '')
        + (r.name ? `<span class="yt-meta" title="Nama aset di Roblox">${esc(r.name)}</span>` : '')
        + (window.ArrrModeration && (r.mod || r.modErr) ? `<span class="sp-mod">${window.ArrrModeration.badge(r.mod, r.modErr)}</span>` : '')
        + (!r.mod && !r.modErr ? '<span class="sp-mod"><span class="sp-status run">Menunggu review…</span></span>' : '');
    }
    const text = r.status === 'err' ? r.error : (r.note || (r.status === 'run' ? 'Upload…' : 'Antri'));
    return `<span class="sp-status ${r.status}">${esc(text)}</span>`;
  }

  function renderProgress() {
    if (!$('ytProgressBar')) return;
    const done = items.filter(i => i.status === 'ok' || i.status === 'err').length;
    const ok   = items.filter(i => i.status === 'ok').length;
    const err  = items.filter(i => i.status === 'err').length;
    $('ytProgressBar').style.width = items.length ? (done / items.length * 100) + '%' : '0';
    $('ytProgressText').textContent = items.length
      ? `${done}/${items.length} selesai · ${ok} berhasil · ${err} gagal${running ? (stopped ? ' · berhenti…' : ' · jalan') : ''}`
      : (running ? 'Menyiapkan…' : 'Belum ada proses');

    $('ytZip').disabled   = ok === 0;
    $('ytZip').textContent = ok ? `Download Semua (${ok} MP3, .zip)` : 'Download Semua (.zip)';
    $('ytRetry').disabled = running || err === 0;
    $('ytClear').disabled = running;

    const uploadable = items.filter(i => i.status === 'ok' && (!i.rbx || i.rbx.status === 'err')).length;
    $('ytUploadAll').disabled    = running || uploadable === 0;
    $('ytUploadAll').textContent = uploadable ? `Upload ke Roblox (${uploadable})` : 'Upload ke Roblox';
  }

  function renderIds() {
    if (!$('ytIds')) return;
    const ok = items.filter(i => i.rbx && i.rbx.status === 'ok');
    $('ytIdsWrap').hidden = !ok.length;
    const fmt = $('ytIdFormat').value;
    let text = '';
    if (fmt === 'ids') {
      text = ok.map(i => i.rbx.assetId).join('\n');
    } else if (fmt === 'lua') {
      text = 'return {\n' + ok.map(i => `\t[${JSON.stringify(i.rbx.name || i.label)}] = "rbxassetid://${i.rbx.assetId}",`).join('\n') + '\n}';
    } else {
      text = ok.map(i => `rbxassetid://${i.rbx.assetId} -- ${(i.rbx.name || i.label).replace(/[\r\n]+/g, ' ')}`).join('\n');
    }
    $('ytIds').value = text;
  }

  function renderAll() {
    renderRows();
    renderProgress();
    renderIds();
    renderModStatus(!!modStop);
  }

  function setRunning(on) {
    running = on;
    if ($('ytStart')) {
      $('ytStart').disabled = on;
      $('ytStop').disabled  = !on;
    }
    renderProgress();
  }

  /* ============================================================
     API
     ============================================================ */
  async function callApi(body) {
    const res = await fetch(window.__apiUrls.ytmp3, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('Respons server tidak valid (HTTP ' + res.status + ')');
    }
    if (data.error) throw new Error(data.error);
    return data;
  }

  function triggerDownload(url) {
    const a = document.createElement('a');
    a.href = url;
    a.setAttribute('download', '');
    a.setAttribute('data-no-spa', '');
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  /* ============================================================
     UPLOAD KE ROBLOX (lewat api/spoof.php — file tidak lewat browser)
     ============================================================ */
  function loadRobloxSettings() {
    try {
      const s = JSON.parse(localStorage.getItem(SPOOF_KEY) || '{}');
      if (s.creatorType) $('ytCreatorType').value = s.creatorType;
      if (s.creatorId)   $('ytCreatorId').value   = s.creatorId;
      if (s.universeId)  $('ytUniverseId').value  = s.universeId;
      $('ytAutoGrant').checked = !!s.autoGrant;
      if (s.apiKey) {
        $('ytApiKey').value = s.apiKey;
        $('ytRemember').checked = true;
      }
      const own = JSON.parse(localStorage.getItem(OWN_KEY) || '{}');
      $('ytUpload').checked = !!own.upload;
      if (own.nameMax) $('ytNameMax').value = String(own.nameMax);
    } catch (e) { /* storage diblok → abaikan */ }
    renderRobloxToggle();
  }

  function saveRobloxSettings() {
    try {
      const s = JSON.parse(localStorage.getItem(SPOOF_KEY) || '{}');
      s.creatorType = $('ytCreatorType').value;
      s.creatorId   = $('ytCreatorId').value.trim();
      s.universeId  = $('ytUniverseId').value.trim();
      s.autoGrant   = $('ytAutoGrant').checked;
      if ($('ytRemember').checked) s.apiKey = $('ytApiKey').value.trim();
      else delete s.apiKey;
      localStorage.setItem(SPOOF_KEY, JSON.stringify(s));
      localStorage.setItem(OWN_KEY, JSON.stringify({ upload: $('ytUpload').checked, nameMax: $('ytNameMax').value }));
    } catch (e) { /* abaikan */ }
  }

  function renderRobloxToggle() {
    const card = document.querySelector('.yt-roblox');
    if (card) card.toggleAttribute('data-off', !$('ytUpload').checked);
  }

  /** Setting Roblox dari form; null (+ toast) kalau belum lengkap */
  function robloxCfg() {
    const cfg = {
      apiKey:      $('ytApiKey').value.trim(),
      creatorType: $('ytCreatorType').value,
      creatorId:   $('ytCreatorId').value.trim(),
    };
    if (!cfg.apiKey) {
      $('ytApiKey').focus();
      showToast('Isi API key Roblox dulu (card Upload ke Roblox)', 'error', 3500);
      return null;
    }
    if (!/^\d+$/.test(cfg.creatorId)) {
      $('ytCreatorId').focus();
      showToast('Isi User ID / Group ID kamu (angka)', 'error', 3500);
      return null;
    }
    saveRobloxSettings();
    return cfg;
  }

  async function callSpoof(body) {
    const res = await fetch(window.__apiUrls.spoof, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('Respons server tidak valid (HTTP ' + res.status + ')');
    }
    if (data.error) throw new Error(data.error);
    return data;
  }

  async function uploadItem(it, rcfg) {
    it.rbx = { status: 'run' };
    renderAll();
    try {
      let rbxName = '';
      let data = await callSpoof({
        action: 'ytmp3',
        apiKey: rcfg.apiKey,
        creatorType: rcfg.creatorType,
        creatorId: rcfg.creatorId,
        token: it.token,
        videoId: it.videoId,
        name: it.label,
        nameMax: parseInt($('ytNameMax').value, 10) || 30,
      });
      rbxName = data.name || '';
      // Roblox masih memproses → polling status operasi
      for (let n = 0; !data.assetId && data.operationId && n < POLL_MAX; n++) {
        it.rbx.note = 'Menunggu Roblox…';
        renderRows();
        await sleep(POLL_MS);
        data = await callSpoof({ action: 'status', apiKey: rcfg.apiKey, operationId: data.operationId });
      }
      if (!data.assetId) throw new Error('Timeout menunggu Roblox — cek Creator Dashboard nanti');
      it.rbx = { status: 'ok', assetId: String(data.assetId), name: rbxName || '' };
      startModWatch(rcfg.apiKey);
    } catch (e) {
      it.rbx = { status: 'err', error: e.message || 'Upload gagal' };
    }
    renderAll();
  }

  /** Upload semua hasil convert yang belum ter-upload (atau gagal upload) */
  async function uploadAll() {
    if (running) return;
    const rcfg = robloxCfg();
    if (!rcfg) return;
    if (window.ArrrModeration) window.ArrrModeration.askNotify();
    const queue = items.filter(i => i.status === 'ok' && (!i.rbx || i.rbx.status === 'err'));
    if (!queue.length) return;
    queue.forEach(it => { it.rbx = { status: 'wait' }; });

    stopped = false;
    setRunning(true);
    renderAll();
    const next = async () => {
      while (queue.length && !stopped) await uploadItem(queue.shift(), rcfg);
    };
    await Promise.all([next(), next()]);
    queue.forEach(it => { it.rbx = { status: 'err', error: 'Dibatalkan' }; });
    setRunning(false);
    renderAll();
    toastUploads();
    await autoGrant();
  }

  async function autoGrant() {
    if ($('ytAutoGrant') && $('ytAutoGrant').checked && /^\d+$/.test($('ytUniverseId').value.trim())) {
      await grantUploaded(true);
    }
  }

  /** Izinkan audio yang sudah ter-upload ke game (Universe ID). silent = dari auto-grant */
  async function grantUploaded(silent) {
    const status = (t) => { if ($('ytGrantStatus')) $('ytGrantStatus').textContent = t; };
    const apiKey     = $('ytApiKey').value.trim();
    const universeId = $('ytUniverseId').value.trim();
    const done = items.filter(i => i.rbx && i.rbx.status === 'ok' && !i.rbx.granted);
    if (!done.length) {
      if (!silent) showToast('Semua audio sudah diizinkan / belum ada yang ter-upload', 'warning');
      return;
    }
    if (!apiKey) return showToast('Isi API key Roblox dulu', 'error');
    if (!/^\d+$/.test(universeId)) {
      $('ytUniverseId').focus();
      return showToast('Isi Universe ID game (card Upload ke Roblox)', 'error');
    }
    if (typeof window.ArrrGrant !== 'function') return showToast('Modul Auto Spoof belum termuat', 'error');
    saveRobloxSettings();

    status(`Mengizinkan ${done.length} audio ke game ${universeId}…`);
    const r = await window.ArrrGrant(apiKey, universeId, done.map(i => i.rbx.assetId));
    done.forEach(i => { if (r.granted.includes(i.rbx.assetId)) i.rbx.granted = true; });
    const failed = Object.keys(r.failed).length;
    status((r.placeId ? `${r.placeId} itu Place ID → pakai Universe ID ${r.universeId}. ` : '')
      + `${r.granted.length}/${done.length} audio diizinkan ke game ${r.universeId || universeId}`
      + (failed ? ' · gagal: ' + [...new Set(Object.values(r.failed))].join(' | ') : ''));
    if (failed && window.ArrrCopyButton && $('ytGrantStatus')) {
      $('ytGrantStatus').insertAdjacentHTML('beforeend', '<span class="sp-copy-row">'
        + window.ArrrCopyButton(Object.keys(r.failed).join('\n'), `Copy ${failed} ID gagal`)
        + window.ArrrCopyButton(Object.entries(r.failed).map(([id, m]) => `${id}: ${m}`).join('\n'), 'Copy pesan error')
        + '</span>');
    }
    showToast(`${r.granted.length}/${done.length} audio diizinkan ke game`, failed ? 'warning' : 'success', 3500);
    renderRows();
  }

  /* ============================================================
     STATUS REVIEW — cek berkala sampai semua audio Approved / Rejected
     ============================================================ */
  function pendingModIds() {
    const M = window.ArrrModeration;
    return items.filter(i => i.rbx && i.rbx.status === 'ok' && !(M && M.isFinal(i.rbx.mod))).map(i => i.rbx.assetId);
  }

  function renderModStatus(watching) {
    const el = $('ytModStatus');
    if (!el) return;
    const up = items.filter(i => i.rbx && i.rbx.status === 'ok');
    if (!up.length) { el.textContent = ''; return; }
    const ok  = up.filter(i => i.rbx.mod === 'Approved').length;
    const bad = up.filter(i => i.rbx.mod === 'Rejected').length;
    const wait = up.length - ok - bad;
    el.textContent = `Review Roblox: ${ok} siap dipakai · ${wait} masih direview · ${bad} ditolak`
      + (wait ? (watching ? ' — dicek otomatis tiap 30 detik' : ' — buka Riwayat Upload untuk cek lagi nanti') : '');
  }

  function startModWatch(apiKey) {
    if (modStop || !window.ArrrModeration || !apiKey) return;
    modStop = window.ArrrModeration.watch({
      apiKey,
      getIds: pendingModIds,
      onUpdate: (r) => {
        items.forEach(i => {
          if (!i.rbx || i.rbx.status !== 'ok') return;
          const id = i.rbx.assetId;
          if (r.states[id]) { i.rbx.mod = r.states[id]; i.rbx.modErr = ''; }
          else if (r.errors[id]) i.rbx.modErr = r.errors[id];
        });
        renderRows();
        renderModStatus(true);
      },
      onDone: (allFinal) => {
        modStop = null;
        renderModStatus(false);
        const up = items.filter(i => i.rbx && i.rbx.status === 'ok');
        if (allFinal && up.length) {
          const ok = up.filter(i => i.rbx.mod === 'Approved').length;
          showToast(`Review selesai: ${ok}/${up.length} audio siap dipakai`, ok === up.length ? 'success' : 'warning', 5000);
          window.ArrrModeration.notify('ARRR Studio — review selesai', `${ok}/${up.length} audio siap dipakai di Roblox`);
        }
      },
    });
    renderModStatus(true);
  }

  function toastUploads() {
    const tried = items.filter(i => i.rbx);
    if (!tried.length) return false;
    const ok = tried.filter(i => i.rbx.status === 'ok').length;
    showToast(`${ok}/${tried.length} audio ter-upload ke Roblox`, ok === tried.length ? 'success' : 'warning', 3500);
    return true;
  }

  function copyIds() {
    const text = $('ytIds').value;
    if (!text) return;
    navigator.clipboard.writeText(text)
      .then(() => showToast('Asset ID di-copy'))
      .catch(() => { $('ytIds').select(); document.execCommand('copy'); showToast('Asset ID di-copy'); });
  }

  async function processItem(it, cfg) {
    it.status = 'run';
    it.error  = '';
    renderAll();

    const data = await callApi({
      action: 'convert',
      videoId: it.videoId,
      bitrate: cfg.bitrate,
      speed:   cfg.fx.speed,
      pitch:   cfg.fx.pitch,
    });
    Object.assign(it, {
      status:   'ok',
      token:    data.token,
      filename: data.filename,
      size:     data.size,
      duration: data.duration,
      label:    data.title || it.label,
      fx:       { speed: data.speed ?? cfg.fx.speed, pitch: data.pitch ?? cfg.fx.pitch },
    });
    if (cfg.autoDownload) triggerDownload(downloadUrl(it.token));
    if (cfg.roblox) {
      it.rbx = { status: 'wait' };
      await uploadItem(it, cfg.roblox);
    }
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

  async function runQueue(queue, roblox) {
    const cfg = {
      bitrate:      $('ytBitrate').value,
      autoDownload: $('ytAutoDownload').checked,
      fx:           fxSettings(),
      roblox:       roblox || null,
    };
    const parallel = Math.max(1, Math.min(3, parseInt($('ytParallel').value, 10) || 2));

    stopped = false;
    setRunning(true);
    renderAll();

    await Promise.all(Array.from({ length: Math.min(parallel, queue.length) }, () => worker(queue, cfg)));

    // Item yang belum sempat jalan karena di-stop
    items.forEach(it => {
      if (it.status === 'wait') { it.status = 'err'; it.error = 'Dibatalkan'; }
    });

    setRunning(false);
    renderAll();

    if (cfg.roblox && toastUploads()) {
      await autoGrant();
      return;
    }
    const ok = items.filter(i => i.status === 'ok').length;
    showToast(`${ok}/${items.length} video berhasil dikonversi`, ok === items.length ? 'success' : 'warning', 3500);
  }

  /** Setting Roblox kalau "Langsung upload" dicentang; false = batal (belum lengkap) */
  function uploadCfgForRun() {
    if (!$('ytUpload').checked) return null;
    return robloxCfg() || false;
  }

  /* ============================================================
     START / RETRY / STOP
     ============================================================ */
  async function start() {
    if (running) return;

    const { videos, playlists } = parseInput($('ytLinks').value);
    if (!videos.length && !playlists.length) return showToast('Tempel minimal 1 link YouTube', 'error');
    if (tools && !toolsReady()) {
      $('ytTools').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return showToast('Install yt-dlp & ffmpeg dulu (panel Tools di atas)', 'error', 3500);
    }
    const roblox = uploadCfgForRun();
    if (roblox === false) return;
    if (roblox && window.ArrrModeration) window.ArrrModeration.askNotify();

    const max  = window.__ytmp3MaxBatch || 50;
    const seen = new Set();
    const list = [];
    let skipped = 0;
    const add  = (id, label) => {
      if (seen.has(id)) return;
      seen.add(id);
      if (list.length >= max) return skipped++;
      list.push({ videoId: id, label: label || id, status: 'wait' });
    };

    items = [];
    stopped = false;
    setRunning(true);

    // Expand playlist → video (berurutan, biar urutan hasil sama dengan input)
    for (const p of playlists) {
      if (stopped) break;
      $('ytProgressText').textContent = 'Mengambil playlist ' + p.id + '…';
      try {
        const data = await callApi({ action: 'playlist', url: p.url });
        data.items.forEach(v => add(v.id, v.title));
      } catch (e) {
        showToast('Playlist ' + p.id + ': ' + e.message, 'error', 4000);
      }
    }
    videos.forEach(v => add(v.id));

    if (skipped) showToast(`Dibatasi ${max} video pertama (${skipped} dilewati)`, 'warning', 3500);

    if (stopped || !list.length) {
      setRunning(false);
      renderAll();
      if (!list.length && !stopped) showToast('Tidak ada video yang bisa diproses', 'error');
      return;
    }

    items = list;
    await runQueue(items.slice(), roblox);
  }

  function retryFailed() {
    if (running) return;
    const failed = items.filter(i => i.status === 'err');
    if (!failed.length) return;
    const roblox = uploadCfgForRun();
    if (roblox === false) return;
    if (roblox && window.ArrrModeration) window.ArrrModeration.askNotify();
    failed.forEach(it => { it.status = 'wait'; it.error = ''; it.rbx = null; });
    runQueue(failed, roblox);
  }

  function downloadZip() {
    const tokens = items.filter(i => i.status === 'ok' && i.token).map(i => i.token);
    if (!tokens.length) return showToast('Belum ada hasil', 'warning');
    triggerDownload(window.__apiUrls.ytmp3 + '?action=zip&tokens=' + tokens.join(','));
    showToast('Menyiapkan ZIP…');
  }

  function clearAll() {
    if (running) return;
    items = [];
    renderAll();
  }

  /* ============================================================
     INIT
     ============================================================ */
  function initYtMp3() {
    const root = $('ytStart');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';

    $('ytLinks').addEventListener('input', updateCount);
    root.addEventListener('click', start);
    $('ytStop').addEventListener('click', () => { stopped = true; renderProgress(); });
    $('ytRetry').addEventListener('click', retryFailed);
    $('ytZip').addEventListener('click', downloadZip);
    $('ytClear').addEventListener('click', clearAll);

    $('ytSpeed').addEventListener('input', renderFx);
    $('ytPitch').addEventListener('input', renderFx);
    $('ytFxReset').addEventListener('click', () => {
      $('ytSpeed').value = 1;
      $('ytPitch').value = 0;
      renderFx();
    });
    $('ytScriptCopy').addEventListener('click', copyScript);
    $('ytToolsInstall').addEventListener('click', installMissing);

    loadRobloxSettings();
    $('ytUpload').addEventListener('change', () => { renderRobloxToggle(); saveRobloxSettings(); });
    $('ytRemember').addEventListener('change', saveRobloxSettings);
    $('ytCreatorType').addEventListener('change', saveRobloxSettings);
    $('ytCreatorId').addEventListener('change', saveRobloxSettings);
    $('ytToggleKey').addEventListener('click', () => {
      const input = $('ytApiKey');
      input.type = input.type === 'password' ? 'text' : 'password';
      $('ytToggleKey').textContent = input.type === 'password' ? 'Lihat' : 'Tutup';
    });
    $('ytUploadAll').addEventListener('click', uploadAll);
    $('ytIdFormat').addEventListener('change', renderIds);
    $('ytIdCopy').addEventListener('click', copyIds);
    $('ytGrant').addEventListener('click', () => grantUploaded(false));
    $('ytUniverseId').addEventListener('change', saveRobloxSettings);
    $('ytNameMax').addEventListener('change', saveRobloxSettings);
    $('ytAutoGrant').addEventListener('change', saveRobloxSettings);
    $('ytToolsUpdate').addEventListener('click', () => installTools(['ytdlp']));
    $('ytToolsRefresh').addEventListener('click', checkTools);
    $('ytScriptDownload').addEventListener('click', downloadScript);

    // Balik ke halaman ini (SPA) saat proses masih jalan → tampilkan state terakhir
    root.disabled = running || !window.__isLoggedIn;
    $('ytStop').disabled = !running;

    updateCount();
    renderFx();
    renderAll();
    if (window.__isLoggedIn) {
      if (tools) renderTools(); else checkTools();
    } else {
      $('ytTools').hidden = true;
    }
  }

  window.initYtMp3 = initYtMp3;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initYtMp3);
  } else {
    initYtMp3();
  }
  window.addEventListener('spa:navigated', initYtMp3);
})();
