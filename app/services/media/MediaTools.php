<?php
// app/services/media/MediaTools.php — lokasi, status & installer otomatis yt-dlp + ffmpeg
//
// Urutan cari binary:
//   1. .env YTDLP_BIN / FFMPEG_BIN (path file)
//   2. storage/bin/ (hasil "Install otomatis" — diblok .htaccess, tidak ikut git)
//   3. PATH sistem ("yt-dlp", "ffmpeg")
//
// Installer otomatis mendukung Windows (yt-dlp.exe + ffmpeg.exe/ffprobe.exe) dan
// yt-dlp untuk Linux / macOS. ffmpeg di Linux / macOS pakai package manager.

class MediaTools
{
    private const YTDLP_URL = [
        'Windows' => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe',
        'Linux'   => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux',
        'Darwin'  => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_macos',
    ];
    private const FFMPEG_URL = [
        'Windows' => 'https://github.com/yt-dlp/FFmpeg-Builds/releases/download/latest/ffmpeg-master-latest-win64-gpl.zip',
    ];

    public static function binDir(): string
    {
        return STORAGE_PATH . '/bin';
    }

    private static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    private static function exe(string $name): string
    {
        return $name . (self::isWindows() ? '.exe' : '');
    }

    // ============================================================
    // LOKASI BINARY
    // ============================================================

    public static function ytdlp(): string
    {
        $env = env('YTDLP_BIN');
        if ($env) {
            return (string)$env;
        }
        $local = self::binDir() . '/' . self::exe('yt-dlp');
        return is_file($local) ? $local : 'yt-dlp';
    }

    public static function ffmpeg(): string
    {
        $env = env('FFMPEG_BIN');
        if ($env) {
            return (string)$env;
        }
        $local = self::binDir() . '/' . self::exe('ffmpeg');
        return is_file($local) ? $local : 'ffmpeg';
    }

    /** Nilai --ffmpeg-location untuk yt-dlp (null = biar yt-dlp cari di PATH) */
    public static function ffmpegLocation(): ?string
    {
        $bin = self::ffmpeg();
        return $bin === 'ffmpeg' ? null : $bin;
    }

    // ============================================================
    // STATUS
    // ============================================================

    /**
     * @return array{os:string, ytdlp:array, ffmpeg:array, autoInstall:array{ytdlp:bool, ffmpeg:bool}}
     */
    public static function status(): array
    {
        return [
            'os'     => PHP_OS_FAMILY,
            'ytdlp'  => self::probe([self::ytdlp(), '--version'], '/^(\S+)/'),
            'ffmpeg' => self::probe([self::ffmpeg(), '-version'], '/ffmpeg version (\S+)/'),
            'autoInstall' => [
                'ytdlp'  => isset(self::YTDLP_URL[PHP_OS_FAMILY]),
                'ffmpeg' => isset(self::FFMPEG_URL[PHP_OS_FAMILY]),
            ],
        ];
    }

    private static function probe(array $cmd, string $versionRe): array
    {
        try {
            $r = ProcessRunner::run($cmd, 20, basename($cmd[0]));
        } catch (Throwable) {
            return ['ok' => false, 'version' => null];
        }
        $ok = $r['code'] === 0 && preg_match($versionRe, trim($r['out']), $m);
        return ['ok' => (bool)$ok, 'version' => $ok ? $m[1] : null];
    }

    // ============================================================
    // INSTALL
    // ============================================================

    /** Download yt-dlp (juga dipakai untuk update ke versi terbaru) */
    public static function installYtdlp(): void
    {
        $url = self::YTDLP_URL[PHP_OS_FAMILY] ?? null;
        if ($url === null) {
            throw new RuntimeException('Install otomatis tidak didukung di OS ' . PHP_OS_FAMILY);
        }
        self::ensureBinDir();

        $target = self::binDir() . '/' . self::exe('yt-dlp');
        $tmp    = $target . '.download';
        self::download($url, $tmp);
        if (filesize($tmp) < 1_000_000) {
            @unlink($tmp);
            throw new RuntimeException('File yt-dlp yang ter-download tidak valid');
        }
        self::replace($tmp, $target);
        if (!self::isWindows()) {
            chmod($target, 0755);
        }
    }

    /** Download ffmpeg + ffprobe (Windows) */
    public static function installFfmpeg(): void
    {
        $url = self::FFMPEG_URL[PHP_OS_FAMILY] ?? null;
        if ($url === null) {
            throw new RuntimeException(PHP_OS_FAMILY === 'Darwin'
                ? 'Install ffmpeg lewat Terminal: brew install ffmpeg'
                : 'Install ffmpeg lewat package manager, mis. sudo apt install ffmpeg');
        }
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Aktifkan extension=zip di php.ini dulu, lalu restart Apache');
        }
        self::ensureBinDir();

        $zipPath = self::binDir() . '/ffmpeg.zip.download';
        self::download($url, $zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            @unlink($zipPath);
            throw new RuntimeException('File ZIP ffmpeg rusak, coba lagi');
        }
        try {
            foreach (['ffmpeg.exe', 'ffprobe.exe'] as $name) {
                self::extractEntry($zip, '/bin/' . $name, self::binDir() . '/' . $name);
            }
        } finally {
            $zip->close();
            @unlink($zipPath);
        }
    }

    private static function extractEntry(ZipArchive $zip, string $suffix, string $target): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string)$zip->getNameIndex($i);
            if (!str_ends_with($entry, $suffix)) {
                continue;
            }
            $in  = $zip->getStream($entry);
            $tmp = $target . '.download';
            $out = fopen($tmp, 'wb');
            if (!$in || !$out) {
                throw new RuntimeException('Gagal extract ' . basename($target));
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            self::replace($tmp, $target);
            return;
        }
        throw new RuntimeException(basename($target) . ' tidak ada di ZIP ffmpeg');
    }

    private static function ensureBinDir(): void
    {
        $dir = self::binDir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException('Gagal membuat folder storage/bin — cek izin tulis folder storage');
        }
    }

    private static function replace(string $tmp, string $target): void
    {
        // Windows: rename tidak bisa menimpa file yang sedang dipakai / sudah ada
        if (is_file($target) && !@unlink($target)) {
            @unlink($tmp);
            throw new RuntimeException(basename($target) . ' sedang dipakai, tunggu proses convert selesai lalu coba lagi');
        }
        if (!rename($tmp, $target)) {
            throw new RuntimeException('Gagal menyimpan ' . basename($target));
        }
    }

    /** Download file besar (streaming ke disk, TLS tetap diverifikasi) */
    private static function download(string $url, string $target): void
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Aktifkan extension=curl di php.ini dulu, lalu restart Apache');
        }
        $fp = fopen($target, 'wb');
        if (!$fp) {
            throw new RuntimeException('Tidak bisa menulis ke storage/bin');
        }

        $ch   = curl_init($url);
        $opts = [
            CURLOPT_FILE            => $fp,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => 20,
            CURLOPT_TIMEOUT         => 1800,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_USERAGENT       => 'ARRR-Studio',
        ];
        $ca = self::caBundle();
        if ($ca !== null) {
            $opts[CURLOPT_CAINFO] = $ca;
        }
        curl_setopt_array($ch, $opts);

        $ok    = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok) {
            @unlink($target);
            if (in_array($errno, [60, 77], true)) {
                throw new RuntimeException('Sertifikat SSL tidak ditemukan. Di php.ini isi curl.cainfo = "C:\\xampp\\apache\\bin\\curl-ca-bundle.crt" (sesuaikan lokasi XAMPP), restart Apache, lalu coba lagi');
            }
            throw new RuntimeException('Download gagal: ' . $error);
        }
    }

    /**
     * XAMPP sering tidak mengisi curl.cainfo → cari CA bundle bawaan XAMPP / PHP.
     * (Verifikasi TLS tetap aktif — ini hanya menunjukkan lokasi daftar sertifikat.)
     */
    private static function caBundle(): ?string
    {
        if (ini_get('curl.cainfo') || ini_get('openssl.cafile') || !self::isWindows()) {
            return null;
        }
        // Di Apache (mod_php) PHP_BINARY = httpd.exe → pakai extension_dir (…\xampp\php\ext)
        $phpDir  = dirname((string)ini_get('extension_dir'));
        $rootDir = dirname($phpDir);
        $candidates = [
            $phpDir . '/extras/ssl/cacert.pem',
            $rootDir . '/apache/bin/curl-ca-bundle.crt',
            dirname((string)PHP_BINARY) . '/curl-ca-bundle.crt',
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
            'C:/xampp/php/extras/ssl/cacert.pem',
        ];
        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return null;
    }
}
