# ARRR Studio

Converter HTML → Roblox StarterGui (LocalScript Lua, `.rbxmx`, plugin Studio).

## Menjalankan

```bash
cp .env.example .env              # isi kredensial OAuth Google / Discord
php -S localhost:8000 server.php  # dev server (meniru .htaccess)
```

Fitur **YT → MP3** butuh `yt-dlp` + `ffmpeg`:

- **Windows / XAMPP**: buka halaman YT → MP3 → panel **Tools** → klik **Install otomatis**.
  Server men-download `yt-dlp.exe`, `ffmpeg.exe`, `ffprobe.exe` ke `storage/bin/` (tanpa setting PATH).
  Manual: taruh ketiga file itu sendiri di `storage/bin/`. Tombol **Update yt-dlp** untuk versi terbaru.
- **Linux / macOS**: `yt-dlp` bisa lewat Install otomatis; ffmpeg lewat `sudo apt install ffmpeg` / `brew install ffmpeg`.
- Lokasi custom: isi `YTDLP_BIN` / `FFMPEG_BIN` di `.env`.

Batas durasi, jumlah link, TTL file, dan tombol install (`allow_install`) ada di `app/config/app.php` → `ytmp3`.

Di Apache (XAMPP dll.) cukup taruh folder project di `htdocs`; `.htaccess` sudah menangani pretty URL (`/docs`, `/converter`, ...).

## Struktur

```
index.php                  front controller (semua halaman)
server.php                 router untuk `php -S` (development)
api/
  convert.php              POST HTML → node tree     (ConvertApiController)
  generate.php             POST node tree → Lua/rbxmx (GenerateApiController)
  spoof.php                Auto Spoof: re-upload aset via Open Cloud (SpoofApiController)
                           + izin game massal (grant) & riwayat upload (history)
  ytmp3.php                YT → MP3 massal + Audio Enhancement speed/pitch (YtMp3ApiController);
                           upload langsung ke Roblox lewat spoof.php action "ytmp3"
app/
  bootstrap.php            konstanta path, autoload, .env, session
  config/
    app.php                canvas, guest limit, batas ukuran HTML
    oauth.php              provider OAuth (kredensial dari .env)
    routes.php             daftar route halaman + API
  core/                    framework kecil: Router, Controller, View, Request, Response, Env
  middleware/              AuthMiddleware ('auth'), GuestMiddleware ('guest')
  controllers/             controller halaman (Landing, Converter, Library, Docs, Prompt, Auth)
    api/                   ApiController (base JSON), ConvertApiController, GenerateApiController
  services/
    auth/                  Auth (session), UserRepository (storage/users.json), OAuthService
    converter/             HtmlParser (HTML → node), NodeNamer (penamaan node)
    media/                 YoutubeMp3Service (yt-dlp + ffmpeg, speed/pitch), MediaTools (cari/install binary), ProcessRunner
    generators/            LuaGenerator, FullScriptGenerator, RbxmxGenerator, PluginGenerator, BillboardGenerator
    roblox/                RobloxAssetService (Open Cloud), UploadHistory (storage/history/<user>.json)
    ExportService.php      gabungkan semua output generator
    GuestLimiter.php       kuota converter untuk guest
  helpers/
    functions.php          url(), asset(), asset_v(), e(), env(), config()
    CssHelper.php          parse style inline, warna, gradient, transition
    LuaHelper.php          format nilai Lua (string, Color3, ColorSequence, easing)
    HttpClient.php         request HTTP JSON (cURL)
  views/
    layouts/master.php     kerangka HTML
    components/            navbar, loader, scripts, toast, plugin-modal
    pages/<halaman>/       index.php + sections/*.php
assets/
  css/                     app.css, base.css, nav.css, loader.css
    pages/                 CSS per halaman (di-inline + di-swap oleh SPA)
  js/
    core/                  spa.js, layout.js, loader.js, guest-limit.js
    pages/                 converter.js, landing.js, library.js, docs.js, prompt.js
  img/
samples/                   contoh HTML untuk Library
storage/                   data runtime (users.json) — tidak di-commit
```

## Konvensi

- **Halaman baru**: buat controller di `app/controllers`, view di `app/views/pages/<nama>/index.php`,
  daftarkan di `app/config/routes.php`. CSS khusus halaman → `assets/css/pages/<nama>.css`
  lalu isi `'styles' => ['<nama>']` saat `render()`.
- **JS halaman**: `assets/js/pages/<nama>.js`, tambahkan ke `app/views/components/scripts.php`.
  Init lewat `DOMContentLoaded` **dan** event `spa:navigated`, dan guard dengan elemen di dalam `<main>`.
- **SPA**: `spa.js` hanya mengganti `<header class="app-header">` dan `<main>`. Script inline di luar
  `<main>` tidak akan jalan setelah navigasi — taruh logic di file JS halaman.
- Asset dimuat dengan `asset_v()` (cache-busting `?v=filemtime`), jadi perubahan file langsung terpakai.
