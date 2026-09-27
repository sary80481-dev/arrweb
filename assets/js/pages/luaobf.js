/* ============================================================
 *  assets/js/pages/luaobf.js — Lua Obfuscator / Deobfuscator (SPA-ready)
 *
 *  Semua proses di browser (tidak dikirim ke server). Format ARRR bersifat
 *  reversibel penuh: apa pun yang di-obfuscate bisa dikembalikan persis ke
 *  kode Lua semula oleh deobfuscator ini.
 *
 *  Format (per layer):
 *    --[[ARRROBF:1]] ... loader Lua ... local _ARRR="<base64>" ...
 *    base64 = payload; payload = seed(4B LE, plain) + XOR(source, keystream(seed))
 *    keystream = LCG(seed). XOR simetris → decode = kebalikan encode.
 *  Layer bertingkat: obfuscate lagi hasil layer sebelumnya. Deobfuscate
 *  mengupas tiap layer sampai tidak ada marker ARRR lagi.
 * ============================================================ */
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const MARKER = '--[[ARRROBF:1]]';
  const B64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

  /* ============================================================
     BYTE <-> STRING (UTF-8) & BASE64
     ============================================================ */
  const enc = new TextEncoder();
  const dec = new TextDecoder();

  function b64encode(bytes) {
    let out = '';
    for (let i = 0; i < bytes.length; i += 3) {
      const a = bytes[i], b = bytes[i + 1], c = bytes[i + 2];
      const n = (a << 16) | ((b || 0) << 8) | (c || 0);
      out += B64[(n >> 18) & 63] + B64[(n >> 12) & 63]
        + (i + 1 < bytes.length ? B64[(n >> 6) & 63] : '=')
        + (i + 2 < bytes.length ? B64[n & 63] : '=');
    }
    return out;
  }

  // Base64 → bytes (akumulator bit; abaikan '=' — sama persis dengan decoder Lua)
  function base64ToBytes(str) {
    const clean = str.replace(/[^A-Za-z0-9+/]/g, '');
    const out = [];
    let val = 0, cnt = 0;
    for (let i = 0; i < clean.length; i++) {
      val = (val << 6) | B64.indexOf(clean[i]);
      cnt += 6;
      if (cnt >= 8) { cnt -= 8; out.push((val >> cnt) & 255); }
    }
    return Uint8Array.from(out);
  }

  /* ============================================================
     KEYSTREAM (LCG) — sama persis dengan loader Lua
     ============================================================ */
  // state = (state * 1103515245 + 12345) mod 2^31 ; byte = floor(state / 65536) mod 256
  function keystreamXor(bytes, seed) {
    let state = seed >>> 0;
    const out = new Uint8Array(bytes.length);
    for (let i = 0; i < bytes.length; i++) {
      state = (Math.imul(state, 1103515245) + 12345) >>> 0;
      state = state % 2147483648;
      const k = Math.floor(state / 65536) % 256;
      out[i] = bytes[i] ^ k;
    }
    return out;
  }

  /* ============================================================
     LOADER LUA — didekode & dijalankan di Roblox/Lua
     ============================================================ */
  function buildLoader(base64, seed) {
    // Decoder base64 + LCG + XOR, lalu load(src)(). Semua aritmatika dibatasi (Lua = float).
    return `${MARKER}
-- ARRR Studio Lua Obfuscator (reversibel) - jangan ubah baris _ARRR / _S
local _ARRR="${base64}"
local _S=${seed >>> 0}
local function _d(data,seed)
	local b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
	local raw,val,cnt={},0,0
	for i=1,#data do
		local c=data:sub(i,i)
		local p=b:find(c,1,true)
		if p then
			val=val*64+(p-1)
			cnt=cnt+6
			if cnt>=8 then
				cnt=cnt-8
				local d=2^cnt
				raw[#raw+1]=math.floor(val/d)%256
				val=val%d
			end
		end
	end
	local st=seed%2147483648
	local res={}
	for i=5,#raw do
		st=(st*1103515245+12345)%2147483648
		local k=math.floor(st/65536)%256
		local x,r,bit=raw[i],0,1
		for _=1,8 do
			local xb,kb=x%2,k%2
			if xb~=kb then r=r+bit end
			x=math.floor(x/2);k=math.floor(k/2);bit=bit*2
		end
		res[#res+1]=string.char(r)
	end
	return table.concat(res)
end
local _src=_d(_ARRR,_S)
local _f=(loadstring or load)(_src)
if _f then return _f() end`;
  }

  /* ============================================================
     OBFUSCATE / DEOBFUSCATE
     ============================================================ */
  function obfuscateOnce(source) {
    const srcBytes = enc.encode(source);
    const seed = (Math.floor(Math.random() * 0x7ffffffe) + 1) >>> 0;
    const xored = keystreamXor(srcBytes, seed);
    const payload = new Uint8Array(4 + xored.length);
    payload[0] = seed & 255;
    payload[1] = (seed >>> 8) & 255;
    payload[2] = (seed >>> 16) & 255;
    payload[3] = (seed >>> 24) & 255;
    payload.set(xored, 4);
    return buildLoader(b64encode(payload), seed);
  }

  function obfuscate(source, layers) {
    let out = source;
    for (let i = 0; i < layers; i++) out = obfuscateOnce(out);
    return out;
  }

  // Kembalikan satu layer, atau null kalau bukan format ARRR
  function peelOnce(text) {
    if (text.indexOf(MARKER) === -1) return null;
    const m = text.match(/_ARRR="([A-Za-z0-9+/=]*)"/);
    if (!m) return null;
    const payload = base64ToBytes(m[1]);
    if (payload.length < 4) return null;
    const seed = (payload[0] | (payload[1] << 8) | (payload[2] << 16) | (payload[3] << 24)) >>> 0;
    const xored = payload.slice(4);
    const srcBytes = keystreamXor(xored, seed);
    return dec.decode(srcBytes);
  }

  function deobfuscate(text) {
    let out = text.trim();
    let peeled = 0;
    while (true) {
      const next = peelOnce(out);
      if (next === null) break;
      out = next.trim();
      peeled++;
      if (peeled > 100) break; // jaga-jaga
    }
    return { code: out, layers: peeled };
  }

  /* ============================================================
     DEOBFUSCATE FAMILI-A (string-table + PRNG name resolver)
     Contoh: output obfuscator gaya "XI/dI/II" (sejenis yang beredar utk
     script Roblox). SEMUA string didecode secara STATIS (tanpa menjalankan
     kode asing) lalu disisipkan balik → hasil Lua yang bisa dijalankan.
     ============================================================ */
  const LUA_KEYWORDS = new Set(['and','break','do','else','elseif','end','false','for','function','goto','if','in','local','nil','not','or','repeat','return','then','true','until','while']);

  function luaQuote(s) {
    let out = '"';
    for (let i = 0; i < s.length; i++) {
      const c = s.charCodeAt(i);
      if (c === 34) out += '\\"';
      else if (c === 92) out += '\\\\';
      else if (c === 10) out += '\\n';
      else if (c === 13) out += '\\r';
      else if (c === 9) out += '\\t';
      else if (c < 32 || c === 127) out += '\\' + c;
      else out += s[i];
    }
    return out + '"';
  }

  // Ambil daftar literal string dari "local <name>={ ... }"
  function parseStringArray(text, startKey) {
    let i = text.indexOf(startKey);
    if (i < 0) return null;
    i = text.indexOf('{', i) + 1;
    const out = [];
    while (i < text.length) {
      const ch = text[i];
      if (ch === '}') break;
      if (ch === '"' || ch === "'") {
        const q = ch; let s = ''; i++;
        while (i < text.length) {
          const c = text[i];
          if (c === '\\') { const n = text[i + 1]; const map = { '"': '"', "'": "'", '\\': '\\', n: '\n', t: '\t', r: '\r' }; s += (n in map) ? map[n] : n; i += 2; continue; }
          if (c === q) { i++; break; }
          s += c; i++;
        }
        out.push(s);
      } else i++;
    }
    return out;
  }

  // Parse map alfabet {["x"]=N; y=N, ...} → {char: value}
  function parseAlphabet(text, name) {
    const re = new RegExp('local\\s+' + name + '\\s*=\\s*\\{');
    const m = re.exec(text);
    if (!m) return null;
    let i = m.index + m[0].length, depth = 1, body = '';
    while (i < text.length && depth > 0) {
      const c = text[i];
      if (c === '{') depth++;
      else if (c === '}') { depth--; if (depth === 0) break; }
      body += c; i++;
    }
    const map = {};
    const pairRe = /(\[(["'])((?:\\.|.)*?)\2\]|(\w))\s*=\s*(\d+)/g;
    let p;
    while ((p = pairRe.exec(body))) {
      let key = p[4] !== undefined ? p[4] : p[3];
      if (key === '\\\\') key = '\\'; else if (key === '\\"') key = '"'; else if (key === "\\'") key = "'";
      map[key] = parseInt(p[5], 10);
    }
    return map;
  }

  function looksLikeFamilyA(text) {
    return /local\s+\w+\s*=\s*\{\s*["']\*/.test(text)   // tabel string diawali "*"
      && /function\s+\w+\s*\(\s*\w+\s*\)\s*return\s+\w+\[\s*\w+\s*\+\s*\d{3,}\s*\]/.test(text) // dI(c) return XI[c+NNN]
      && /setmetatable\(/.test(text)
      && /%\s*256/.test(text);
  }

  /* ============================================================
     BYTECODE VM (Luraph / MoonSec / IronBrew dsb.)
     Logika = opcode angka yang dijalankan interpreter → TIDAK bisa jadi
     source bersih otomatis. Yang bisa & aman: decode KONSTANTA STRING
     (tanpa menjalankan kode) & sisipkan balik agar terbaca.
     ============================================================ */
  function looksLikeVMBytecode(text) {
    const bigTable = /local\s+\w+\s*=\s*\{\s*"\\\d{2,3}/.test(text); // tabel string escape desimal
    const poolFn = /function\s+\w+\s*\(\s*\w+\s*\)\s*return\s+\w+\[\s*\w+\s*\+\s*\(/.test(text); // w(w) return x[w+(...)]
    const dispatch = (text.match(/if\s+\w+\s*<\s*-?\d{3,}/g) || []).length > 20; // while G do if G<NNN ...
    const boot = /getfenv|newproxy/.test(text) && /setmetatable/.test(text);
    return (bigTable && poolFn) && (dispatch || boot);
  }

  // Parser literal string Lua (menangani \ddd desimal, \xHH, \n\t\r, \", dst.)
  function parseLuaStrings(text, startIdx) {
    let i = text.indexOf('{', startIdx) + 1;
    const out = [];
    while (i < text.length) {
      const ch = text[i];
      if (ch === '}') break;
      if (ch === '"' || ch === "'") {
        const q = ch; let s = ''; i++;
        while (i < text.length) {
          const c = text[i];
          if (c === '\\') {
            const n = text[i + 1];
            if (n >= '0' && n <= '9') { // \ddd desimal (1-3 digit)
              let d = n; let k = i + 2;
              while (k < i + 4 && text[k] >= '0' && text[k] <= '9') { d += text[k]; k++; }
              s += String.fromCharCode(parseInt(d, 10) & 255); i = k; continue;
            }
            if (n === 'x') { s += String.fromCharCode(parseInt(text.substr(i + 2, 2), 16) & 255); i += 4; continue; }
            const map = { n: '\n', t: '\t', r: '\r', '"': '"', "'": "'", '\\': '\\', a: '\x07', b: '\b', f: '\f', v: '\v' };
            s += (n in map) ? map[n] : n; i += 2; continue;
          }
          if (c === q) { i++; break; }
          s += c; i++;
        }
        out.push(s);
      } else i++;
    }
    return out;
  }

  // Eval ekspresi aritmetika integer aman: hanya digit, + - ( ) spasi
  function evalIntExpr(expr) {
    if (!/^[-+()\d\s]+$/.test(expr)) return NaN;
    try { return Function('"use strict";return (' + expr + ')')(); } catch (e) { return NaN; }
  }

  // → { ok, code, count, strings } : decode & sisipkan konstanta string; logika tetap VM
  function deobfVMConstants(text) {
    try {
      const arrM = /local\s+(\w+)\s*=\s*\{\s*"\\\d/.exec(text);
      if (!arrM) return { ok: false };
      const XI = parseLuaStrings(text, arrM.index);
      if (!XI || XI.length < 8) return { ok: false };

      const rr = /ipairs\(\{((?:\s*\{[^{}]*\}\s*[;,]?)+)\}\)/.exec(text);
      if (rr) {
        for (const mm of rr[1].matchAll(/\{([^{}]*)\}/g)) {
          const nums = mm[1].split(/[;,]/).map(s => evalIntExpr(s.trim()));
          if (nums.length >= 2 && nums.every(Number.isFinite)) {
            let a = nums[0] - 1, b = nums[1] - 1;
            while (a < b) { const t = XI[a]; XI[a] = XI[b]; XI[b] = t; a++; b--; }
          }
        }
      }

      // alfabet o: {["x"]=EXPR; y=EXPR, ...} nilai berupa aritmetika
      const alM = /local\s+(\w+)\s*=\s*\{\s*(?:\[?["']?[\w\\]+["']?\]?\s*=\s*-?\d)/.exec(text);
      // ambil blok alfabet: cari 'local <o>={' yang isinya banyak '=angka' & ada ["\ddd"] atau huruf
      let alpha = null;
      for (const m of text.matchAll(/local\s+(\w+)\s*=\s*\{/g)) {
        let i = m.index + m[0].length, depth = 1, body = '';
        while (i < text.length && depth > 0) { const c = text[i]; if (c === '{') depth++; else if (c === '}') { depth--; if (!depth) break; } body += c; i++; }
        const pairs = [...body.matchAll(/(\[(["'])((?:\\.|.)*?)\2\]|(\w))\s*=\s*([-+()\d\s]+?)(?=[;,}]|$)/g)];
        if (pairs.length >= 40) { // alfabet base64 ~64 entri
          alpha = {};
          for (const p of pairs) {
            let key = p[4] !== undefined ? p[4] : p[3];
            if (key.startsWith('\\')) key = String.fromCharCode(parseInt(key.slice(1), 10) & 255);
            const val = evalIntExpr(p[5].trim());
            if (Number.isFinite(val)) alpha[key] = val;
          }
          break;
        }
      }
      if (!alpha) return { ok: false };

      const b64 = (s) => {
        const out = []; let I = 0, j = 0;
        for (let p = 0; p < s.length; p++) {
          const c = s[p], T = alpha[c];
          if (T !== undefined) { I += T * Math.pow(64, 3 - j); j++; if (j === 4) { j = 0; out.push((Math.floor(I / 65536)) % 256, (Math.floor((I % 65536) / 256)) % 256, I % 256); I = 0; } }
          else if (c === '=') { out.push(Math.floor(I / 65536) % 256); if (p + 1 >= s.length || s[p + 1] !== '=') out.push(Math.floor((I % 65536) / 256) % 256); break; }
        }
        return out;
      };
      const dec = XI.map(s => b64(s));

      // OFFSET dari: function w(w) return x[w+(EXPR)] end
      const offM = /function\s+\w+\s*\(\s*\w+\s*\)\s*return\s+\w+\[\s*\w+\s*\+\s*\(([-+()\d\s]+)\)\s*\]/.exec(text);
      if (!offM) return { ok: false };
      const OFFSET = evalIntExpr(offM[1]);
      if (!Number.isFinite(OFFSET)) return { ok: false };

      const poolName = offM[0].match(/function\s+(\w+)/)[1];
      const printable = (b) => b.length > 0 && b.every(c => c >= 9 && c < 127);
      const strAt = (idx) => { const b = dec[idx + OFFSET - 1]; return b ? b.map(c => String.fromCharCode(c)).join('') : null; };

      // Sisipkan w(<arith>) → "string" di seluruh VM (w = decoder di region VM)
      const strings = [];
      let count = 0;
      const re = new RegExp('\\b' + poolName + '\\s*\\(\\s*([-+()\\d\\s]+?)\\s*\\)', 'g');
      const code = text.replace(re, (m, expr) => {
        const n = evalIntExpr(expr);
        if (!Number.isFinite(n)) return m;
        const b = dec[n + OFFSET - 1];
        if (!b) return m;
        if (printable(b)) { const s = b.map(c => String.fromCharCode(c)).join(''); strings.push(s); count++; return luaQuote(s); }
        return m; // biner (kemungkinan angka/kunci VM) → biarkan
      });
      if (count === 0) return { ok: false };
      const uniq = [...new Set(strings)];
      return { ok: true, code, count, strings: uniq };
    } catch (e) {
      return { ok: false, error: e.message };
    }
  }

  // Deobfuscate famili-A → { ok, code, count } atau { ok:false }
  function deobfFamilyA(text) {
    try {
      const XI = parseStringArray(text, 'local XI=') || parseStringArray(text, '={"*');
      if (!XI || XI.length < 4) return { ok: false };

      // Reverse ranges: for C,G in ipairs({{a,b},...}) do reverse XI[a..b]
      const rr = /ipairs\(\{((?:\s*\{\s*\d+\s*[;,]\s*\d+\s*\}\s*[;,]?)+)\}\)/.exec(text);
      if (rr) {
        for (const mm of rr[1].matchAll(/\{\s*(\d+)\s*[;,]\s*(\d+)\s*\}/g)) {
          let a = +mm[1] - 1, b = +mm[2] - 1;
          while (a < b) { const t = XI[a]; XI[a] = XI[b]; XI[b] = t; a++; b--; }
        }
      }

      const K = parseAlphabet(text, 'K');
      const t = parseAlphabet(text, 't');
      if (!K || !t) return { ok: false };

      const b64 = (s) => {
        const out = []; let I = 0, j = 0;
        for (let p = 0; p < s.length; p++) {
          const c = s[p], T = K[c];
          if (T !== undefined) { I += T * Math.pow(64, 3 - j); j++; if (j === 4) { j = 0; out.push(Math.floor(I / 65536) % 256, Math.floor((I % 65536) / 256) % 256, I % 256); I = 0; } }
          else if (c === '=') { out.push(Math.floor(I / 65536) % 256); if (p + 1 >= s.length || s[p + 1] !== '=') out.push(Math.floor((I % 65536) / 256) % 256); break; }
        }
        return out;
      };
      const b85 = (s) => {
        const out = []; let p = 0; const n = s.length;
        while (p < n) {
          const rem = n - p, T = rem >= 5 ? 5 : rem; let B = 0, ok = T > 1;
          for (let c = 0; c < 5; c++) { let G; if (c < T) { G = t[s[p + c]]; if (G === undefined) { ok = false; break; } } else G = 84; B = B * 85 + G; }
          if (ok) { const by = [Math.floor(B / 16777216) % 256, Math.floor(B / 65536) % 256, Math.floor(B / 256) % 256, B % 256]; for (let z = 0; z < T - 1; z++) out.push(by[z]); }
          p += T;
        }
        return out;
      };
      const dec = XI.map(L => L[0] === '0' ? b64(L.slice(1)) : (L[0] === '*' ? b85(L.slice(1)) : [...L].map(c => c.charCodeAt(0) & 255)));

      const offM = /return\s+\w+\[\s*\w+\s*\+\s*(\d+)\s*\]/.exec(text);
      if (!offM) return { ok: false };
      const OFFSET = +offM[1];
      const dI = (c) => dec[c + OFFSET - 1];

      // Konstanta PRNG (II / A())
      const num = (re) => { const m = re.exec(text); return m ? +m[1] : null; };
      const A_MUL = num(/w\s*\*\s*(\d+)\s*\+/), A_ADD = num(/w\s*\*\s*\d+\s*\+\s*(\d+)/), A_MOD = num(/\)\s*\)\s*%\s*(\d+)/);
      const C_MUL = num(/C\s*\*\s*(\d+)\s*\)\s*%\s*\d+/), C_MOD = num(/C\s*\*\s*\d+\s*\)\s*%\s*(\d+)/);
      const W_MOD = num(/w\s*=\s*K\s*%\s*(\d+)/), C_KMOD = num(/C\s*=\s*K\s*%\s*(\d+)\s*\+/), C_KADD = num(/C\s*=\s*K\s*%\s*\d+\s*\+\s*(\d+)/);
      const M0 = num(/local\s+\w+\s*=\s*(204)\b/) || 204;
      if ([A_MUL, A_ADD, A_MOD, C_MUL, C_MOD, W_MOD, C_KMOD, C_KADD].some(v => v == null)) return { ok: false };

      function makeII() {
        let w = 0, C = 2, U = [];
        function A() {
          if (U.length === 0) {
            w = (w * A_MUL + A_ADD) % A_MOD;
            do { C = (C * C_MUL) % C_MOD; } while (C === 1);
            const nb = C % 32, Kk = 13 - ((C - nb)) / 32;
            const tt = (Math.floor(w / Math.pow(2, Kk)) % 4294967296) / Math.pow(2, nb);
            const T = Math.floor((tt % 1) * 4294967296) + Math.floor(tt);
            const B = T % 65536, AA = (T - B) / 65536;
            U = [B % 256, Math.floor((B - B % 256) / 256), AA % 256, Math.floor((AA - AA % 256) / 256)];
          }
          return U.pop();
        }
        return function (G, Kkey) {
          w = Kkey % W_MOD; C = Kkey % C_KMOD + C_KADD; U = [];
          let m = M0, L = '';
          for (let i = 0; i < G.length; i++) { m = ((G[i] + A()) + m) % 256; L += String.fromCharCode(m); }
          return L;
        };
      }
      const II = makeII();
      const rawStr = (bytes) => bytes.map(b => String.fromCharCode(b)).join('');

      let count = 0;
      // Ganti jI[II(dI(a),k)] → "string terdekripsi"
      let out = text.replace(/jI\s*\[\s*II\s*\(\s*dI\s*\(\s*(-?\d+)\s*\)\s*,\s*(\d+)\s*\)\s*\]/g, (_, a, k) => {
        const g = dI(+a); if (!g) return '""';
        count++;
        return luaQuote(II(g, Number(k)));
      });
      // Sisa bare dI(a) → literal mentah
      out = out.replace(/\bdI\s*\(\s*(-?\d+)\s*\)/g, (_, a) => { const g = dI(+a); return g ? luaQuote(rawStr(g)) : '""'; });

      if (count === 0) return { ok: false };

      // Buang scaffolding decoder: dari "local XI={" s/d awal payload (game: pertama)
      const pg = out.search(/game\s*[:.]/);
      if (pg >= 0) {
        const ls = out.lastIndexOf('local ', pg);
        if (ls > 0) out = 'return(function(...)' + out.slice(ls);
      }


      return { ok: true, code: out, count };
    } catch (e) {
      return { ok: false, error: e.message };
    }
  }

  /* ============================================================
     BEAUTIFY — tokenizer Lua yang benar → baris + indentasi.
     Antar token SELALU dipisah spasi/newline, jadi tidak pernah menempel
     (aman: hasil tetap kompilasi). Format hanya kosmetik.
     ============================================================ */
  function tokenizeLua(code) {
    const toks = [];
    let i = 0; const n = code.length;
    const isDigit = (c) => c >= '0' && c <= '9';
    const isWord = (c) => /[A-Za-z0-9_]/.test(c);
    while (i < n) {
      const c = code[i];
      if (c === ' ' || c === '\t' || c === '\n' || c === '\r') { i++; continue; }
      // komentar
      if (c === '-' && code[i + 1] === '-') {
        let j = i + 2;
        if (code[j] === '[') {
          let eq = 0, k = j + 1; while (code[k] === '=') { eq++; k++; }
          if (code[k] === '[') { const cl = ']' + '='.repeat(eq) + ']'; const e = code.indexOf(cl, k + 1); const stop = e < 0 ? n : e + cl.length; toks.push({ t: 'comment', v: code.slice(i, stop) }); i = stop; continue; }
        }
        while (j < n && code[j] !== '\n') j++;
        toks.push({ t: 'linecomment', v: code.slice(i, j) }); i = j; continue;
      }
      // long string
      if (c === '[') {
        let eq = 0, k = i + 1; while (code[k] === '=') { eq++; k++; }
        if (code[k] === '[') { const cl = ']' + '='.repeat(eq) + ']'; const e = code.indexOf(cl, k + 1); const stop = e < 0 ? n : e + cl.length; toks.push({ t: 'string', v: code.slice(i, stop) }); i = stop; continue; }
      }
      // string biasa
      if (c === '"' || c === "'") {
        let j = i + 1; while (j < n) { if (code[j] === '\\') { j += 2; continue; } if (code[j] === c) { j++; break; } j++; }
        toks.push({ t: 'string', v: code.slice(i, j) }); i = j; continue;
      }
      // angka
      if (isDigit(c) || (c === '.' && isDigit(code[i + 1]))) {
        let j = i;
        if (c === '0' && (code[i + 1] === 'x' || code[i + 1] === 'X')) { j = i + 2; while (j < n && /[0-9a-fA-F.]/.test(code[j])) j++; }
        else { while (j < n && /[0-9.]/.test(code[j])) j++; if (code[j] === 'e' || code[j] === 'E') { j++; if (code[j] === '+' || code[j] === '-') j++; while (j < n && isDigit(code[j])) j++; } }
        toks.push({ t: 'number', v: code.slice(i, j) }); i = j; continue;
      }
      // nama / keyword
      if (isWord(c)) { let j = i; while (j < n && isWord(code[j])) j++; toks.push({ t: 'word', v: code.slice(i, j) }); i = j; continue; }
      // operator multi-char
      const three = code.substr(i, 3);
      if (three === '...') { toks.push({ t: 'op', v: '...' }); i += 3; continue; }
      const two = code.substr(i, 2);
      if (['..', '==', '~=', '<=', '>=', '::'].includes(two)) { toks.push({ t: 'op', v: two }); i += 2; continue; }
      toks.push({ t: 'op', v: c }); i++;
    }
    return toks;
  }

  function beautifyLua(code) {
    let toks;
    try { toks = tokenizeLua(code); } catch (e) { return code; }
    const KW = (v) => (v === 'and' || v === 'or' || v === 'not');
    const BINOP = new Set(['+', '-', '*', '/', '%', '^', '..', '==', '~=', '<', '>', '<=', '>=', '=']);
    const STARTER = new Set(['local', 'return', 'if', 'for', 'while', 'repeat']);
    const identRe = /^[A-Za-z_]\w*$/;
    let out = '', indent = 0, lineEmpty = true, prev = null;
    let paren = 0, brace = 0; const funcStack = [];
    const nl = () => { out = out.replace(/[ \t]+$/, '') + '\n'; lineEmpty = true; };
    const emit = (s) => { if (lineEmpty) out += '\t'.repeat(Math.max(0, indent)); out += s; lineEmpty = false; };

    function space(pt, ct) {
      if (!pt) return false;
      const p = pt.v, c = ct.v;
      const atom = (x) => x.t === 'word' || x.t === 'number';
      if (atom(pt) && atom(ct)) return true;
      if ((atom(pt) || pt.t === 'string') && ct.t === 'string') return true;
      if (pt.t === 'string' && atom(ct)) return true;
      if (pt.t === 'op' && pt.v === '-' && pt.unary) return false;
      if (ct.t === 'op' && BINOP.has(c)) return true;
      if (pt.t === 'op' && BINOP.has(p)) return true;
      if (KW(p) || KW(c)) return true;
      if (p === ',') return true;
      if (pt.t === 'op' && (p === ')' || p === ']') && (atom(ct) || ct.t === 'string')) return true;
      return false;
    }
    const strContent = (v) => v.length >= 2 ? v.slice(1, -1) : '';

    const CONT = new Set(['(', ')', '[', ']', '{', '}', ',', ';', '.', ':', '..']);
    for (let x = 0; x < toks.length; x++) {
      const tk = toks[x], v = tk.v;
      if (tk.t === 'op' && v === '-') {
        tk.unary = !prev || (prev.t === 'op' && prev.v !== ')' && prev.v !== ']' && prev.v !== '}') || (prev.t === 'word' && KW(prev.v));
      }

      // ["nama"] → .nama saat INDEXING (bukan key tabel, bukan keyword)
      if (tk.t === 'op' && v === '[' && toks[x + 1] && toks[x + 1].t === 'string'
          && toks[x + 2] && toks[x + 2].t === 'op' && toks[x + 2].v === ']') {
        const nm = strContent(toks[x + 1].v);
        const isKey = prev && ((prev.t === 'op' && (prev.v === '{' || prev.v === ',' || prev.v === ';' || prev.v === '(')) || prev === null);
        const canIndex = prev && (prev.t === 'word' || prev.t === 'number' || prev.t === 'string' || (prev.t === 'op' && (prev.v === ')' || prev.v === ']')));
        if (identRe.test(nm) && !LUA_KEYWORDS.has(nm) && !isKey && canIndex) {
          emit('.' + nm);
          prev = { t: 'word', v: nm };
          x += 2;
          continue;
        }
      }

      // penutup blok
      if (tk.t === 'word' && (v === 'end' || v === 'until' || v === 'elseif' || v === 'else')) { indent--; if (!lineEmpty) nl(); }
      else if (tk.t === 'word' && STARTER.has(v) && brace === 0 && !lineEmpty && prev &&
               !(prev.t === 'op' && (prev.v === '(' || prev.v === '=' || prev.v === ',' || prev.v === '{'))) {
        nl();
      }

      if (!lineEmpty && space(prev, tk)) emit(' ');
      if (tk.t === 'linecomment') { emit(v); nl(); prev = tk; continue; }
      emit(v);

      if (tk.t === 'op' && v === '(') paren++;
      else if (tk.t === 'op' && v === ')') { paren--; if (funcStack.length && paren === funcStack[funcStack.length - 1]) { funcStack.pop(); indent++; nl(); } }
      else if (tk.t === 'op' && v === '{') brace++;
      else if (tk.t === 'op' && v === '}') { if (brace > 0) brace--; }

      if (tk.t === 'word' && v === 'function') funcStack.push(paren);
      else if (tk.t === 'word' && (v === 'then' || v === 'do' || v === 'repeat')) { indent++; nl(); }
      else if (tk.t === 'word' && v === 'else') { indent++; nl(); }
      else if (tk.t === 'op' && v === ';' && brace === 0 && paren === 0) nl();
      if (tk.t === 'word' && (v === 'end' || v === 'until')) {
        const nx = toks[x + 1];
        if (nx && !(nx.t === 'op' && CONT.has(nx.v)) && !(nx.t === 'word' && (nx.v === 'end' || nx.v === 'until' || nx.v === 'elseif' || nx.v === 'else'))) nl();
      }
      prev = tk;
    }
    return out.replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
  }

  /* ============================================================
     UI
     ============================================================ */
  function setOutput(text) {
    $('loOutput').value = text;
    $('loOutBytes').textContent = new Blob([text]).size + ' byte · ' + text.split(/\r?\n/).length + ' baris';
  }

  function doObfuscate() {
    const src = $('loInput').value;
    if (!src.trim()) return showToast('Tempel kode Lua dulu', 'error');
    const layers = Math.max(1, Math.min(5, parseInt($('loLayers').value, 10) || 1));
    let out;
    try {
      out = obfuscate(src, layers);
    } catch (e) {
      return showToast('Gagal obfuscate: ' + e.message, 'error');
    }
    setOutput(out);
    showToast(`Ter-obfuscate (${layers} layer)`);
  }

  function doDeobfuscate() {
    const src = $('loInput').value;
    if (!src.trim()) return showToast('Tempel kode ter-obfuscate dulu', 'error');
    let res;
    try {
      res = deobfuscate(src);
    } catch (e) {
      return showToast('Gagal deobfuscate: ' + e.message, 'error');
    }
    if (res.layers === 0) {
      // Bukan format ARRR → coba deobfuscator famili-A (string-table + PRNG)
      if (looksLikeFamilyA(src)) {
        const fa = deobfFamilyA(src);
        if (fa.ok) {
          let pretty = fa.code;
          try { pretty = beautifyLua(fa.code); } catch (e) { pretty = fa.code; }
          setOutput(pretty);
          $('loNote').textContent = `Obfuscator string-table terdeteksi — ${fa.count} string didecode & dirapikan jadi `
            + `${pretty.split('\n').length} baris. Hasil berupa Lua yang bisa dijalankan (nama variabel lokal tetap singkat).`;
          return showToast(`Berhasil deobfuscate (${fa.count} string)`, 'success', 4000);
        }
      }
      // Obfuscator bytecode-VM (Luraph/MoonSec/IronBrew dsb.): decode konstanta string
      if (looksLikeVMBytecode(src)) {
        const vm = deobfVMConstants(src);
        if (vm.ok) {
          const header = '--[[ ARRR Studio — Bytecode-VM obfuscator terdeteksi.\n'
            + '  ' + vm.count + ' konstanta string berhasil didecode & disisipkan (aman, tanpa menjalankan kode).\n'
            + '  CATATAN: logika program ini dikompilasi jadi BYTECODE (angka) yang dijalankan\n'
            + '  interpreter/VM di dalamnya — bukan lagi kode Lua biasa. Mengembalikannya ke\n'
            + '  source asli yang rapi butuh devirtualisasi khusus per-VM dan TIDAK bisa otomatis.\n'
            + '  Yang di bawah = kode VM apa adanya, dengan semua string sudah terbaca. ]]\n\n';
          setOutput(header + vm.code);
          $('loNote').textContent = `Bytecode-VM: ${vm.count} konstanta string didecode & disisipkan. `
            + `Logika tetap berupa bytecode VM (tidak bisa dijadikan source asli otomatis).`;
          return showToast(`Konstanta didecode (${vm.count} string) — logika tetap VM`, 'warning', 6000);
        }
      }
      // Sisanya → best-effort / penjelasan jujur
      const be = bestEffort(src);
      setOutput(be.cannot ? '' : be.code);
      $('loNote').textContent = be.note;
      return showToast(be.cannot ? 'Tidak bisa dibuka otomatis — lihat catatan' : 'Bukan format ARRR — hasil best-effort',
        'warning', 5000);
    }
    setOutput(res.code);
    $('loNote').textContent = `Dikupas ${res.layers} layer → kode Lua asli.`;
    showToast(`Dikembalikan (${res.layers} layer)`);
  }

  // Best-effort untuk obfuscator lain. Jujur soal batasan: obfuscator VM /
  // enkripsi-nama (Luraph/MoonSec/IronBrew dsb.) TIDAK bisa dibuka statis —
  // konstanta baru terbentuk saat script dijalankan, dan tool ini tidak
  // menjalankan kode asing. Yang aman: unescape string sederhana.
  function bestEffort(text) {
    // Deteksi obfuscator VM / enkripsi-nama (string-table + resolver runtime)
    const vmSignals =
      (/\bfunction\s+\w*\s*\([^)]*\)\s*return\s+\w+\[?\w*\s*[+\-]\s*\d{3,}/.test(text) ? 1 : 0) + // dI(c) return XI[c+NNN]
      (/local\s+\w+\s*=\s*\{\s*["'*0]/.test(text) && (text.match(/["'][*0][^"']*["']/g) || []).length > 20 ? 1 : 0) + // tabel string besar
      (/setmetatable\(\{\}\s*,\s*\{/.test(text) ? 1 : 0) +
      (/\bstring\.char\b/.test(text) && /\bmath\.floor\b/.test(text) && /%\s*256/.test(text) ? 1 : 0);
    if (vmSignals >= 2) {
      return {
        code: text,
        note: 'Terdeteksi obfuscator VM / enkripsi-nama (mis. sejenis Luraph / MoonSec / IronBrew). '
          + 'Logika programnya sebenarnya sudah berupa Lua biasa — yang disembunyikan hanya konstanta string & nama, '
          + 'yang baru terbentuk SAAT script dijalankan (lewat PRNG di dalamnya). Untuk membukanya harus MENJALANKAN '
          + 'script itu, dan tool ini sengaja tidak menjalankan kode asing (berbahaya). Jadi ini tidak bisa di-deobfuscate '
          + 'otomatis jadi kode rapi. Jaminan “pasti kembali” hanya berlaku untuk hasil obfuscator ARRR ini sendiri.',
        cannot: true,
      };
    }
    // Loader sederhana: ambil literal string terpanjang berisi escape byte lalu unescape
    const candidates = [...text.matchAll(/(["'])((?:\\.|(?!\1).)*)\1/g)].map(m => m[2]);
    let best = '';
    for (const c of candidates) {
      if (/\\\d{1,3}|\\x[0-9A-Fa-f]{2}/.test(c) && c.length > best.length) best = c;
    }
    if (!best) {
      return { code: text, note: 'Bukan format ARRR Studio, dan tidak ada string ter-escape yang bisa dipulihkan otomatis.' };
    }
    const decoded = best
      .replace(/\\x([0-9A-Fa-f]{2})/g, (_, h) => String.fromCharCode(parseInt(h, 16)))
      .replace(/\\(\d{1,3})/g, (_, d) => String.fromCharCode(parseInt(d, 10)))
      .replace(/\\n/g, '\n').replace(/\\t/g, '\t').replace(/\\r/g, '\r')
      .replace(/\\"/g, '"').replace(/\\'/g, "'").replace(/\\\\/g, '\\');
    return {
      code: decoded,
      note: 'Bukan format ARRR Studio — ini hasil unescape string terbesar (best-effort). Round-trip 100% hanya dijamin untuk hasil obfuscator ini sendiri.',
    };
  }

  /* ============================================================
     AKSI KECIL
     ============================================================ */
  function copyOut() {
    const text = $('loOutput').value;
    if (!text) return showToast('Belum ada hasil', 'warning');
    (window.ArrrCopyText ? window.ArrrCopyText(text) : navigator.clipboard.writeText(text))
      .then(() => showToast('Hasil di-copy'))
      .catch(() => showToast('Gagal copy', 'error'));
  }

  function downloadOut() {
    const text = $('loOutput').value;
    if (!text) return showToast('Belum ada hasil', 'warning');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([text], { type: 'text/plain' }));
    a.download = 'script.lua';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  function swapInOut() {
    const o = $('loOutput').value;
    if (!o) return;
    $('loInput').value = o;
    setOutput('');
    $('loNote').textContent = '';
    updateInBytes();
  }

  function updateInBytes() {
    const t = $('loInput').value;
    $('loInBytes').textContent = new Blob([t]).size + ' byte · ' + t.split(/\r?\n/).length + ' baris';
  }

  /* ============================================================
     INIT
     ============================================================ */
  function initLuaObf() {
    const root = $('loRoot');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';

    $('loInput').addEventListener('input', updateInBytes);
    $('loObfuscate').addEventListener('click', doObfuscate);
    $('loDeobfuscate').addEventListener('click', doDeobfuscate);
    $('loCopy').addEventListener('click', copyOut);
    $('loDownload').addEventListener('click', downloadOut);
    $('loSwap').addEventListener('click', swapInOut);
    $('loClear').addEventListener('click', () => {
      $('loInput').value = ''; setOutput(''); $('loNote').textContent = ''; updateInBytes();
    });

    updateInBytes();
    setOutput('');
  }

  window.initLuaObf = initLuaObf;
  // Ekspos untuk pengetesan
  window.__ArrrLua = { obfuscate, deobfuscate, deobfFamilyA, looksLikeFamilyA, deobfVMConstants, looksLikeVMBytecode };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLuaObf);
  } else {
    initLuaObf();
  }
  window.addEventListener('spa:navigated', initLuaObf);
})();
