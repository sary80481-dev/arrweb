<?php
// app/services/media/YoutubeMp3Service.php — konversi link YouTube → MP3 lewat yt-dlp + ffmpeg
//
// Dipakai fitur YT → MP3 (konversi massal). Butuh yt-dlp + ffmpeg — lokasinya diatur
// MediaTools (.env → storage/bin hasil "Install otomatis" → PATH). Jalan di Windows & Linux.
//
// Keamanan:
//   - URL TIDAK pernah diteruskan mentah ke yt-dlp — hanya video ID / playlist ID hasil regex,
//     lalu dibangun ulang jadi URL youtube.com (cegah SSRF & argumen nyasar).
//   - Proses dijalankan lewat ProcessRunner (proc_open array) → tanpa shell.
//   - Hasil disimpan di storage/ytmp3/<token>/ (diblok .htaccess), dihapus otomatis setelah TTL.

class YoutubeMp3Service
{
    public const BITRATES = ['128', '192', '256', '320'];

    /** Batas Audio Enhancement */
    public const SPEED_MIN = 1.0;
    public const SPEED_MAX = 2.0;
    public const PITCH_MIN = -6;
    public const PITCH_MAX = 6;

    private const ID_RE       = '[A-Za-z0-9_-]{11}';
    private const PLAYLIST_RE = '[A-Za-z0-9_-]{10,64}';

    private string $ytdlp;
    private string $ffmpeg;
    private ?string $ffmpegLocation;
    private string $dir;
    private int $maxDuration;
    private int $maxPlaylist;
    private int $timeout;
    private int $ttl;

    public function __construct()
    {
        $cfg = config('app.ytmp3', []);

        $this->ytdlp          = MediaTools::ytdlp();
        $this->ffmpeg         = MediaTools::ffmpeg();
        $this->ffmpegLocation = MediaTools::ffmpegLocation();
        $this->dir         = (string)($cfg['dir'] ?? STORAGE_PATH . '/ytmp3');
        $this->maxDuration = (int)($cfg['max_duration'] ?? 1800);
        $this->maxPlaylist = (int)($cfg['max_playlist'] ?? 50);
        $this->timeout     = (int)($cfg['timeout'] ?? 240);
        $this->ttl         = (int)($cfg['ttl'] ?? 3600);
    }

    // ============================================================
    // PARSE URL
    // ============================================================

    /**
     * Ambil video ID dari link YouTube (watch, youtu.be, shorts, embed, live, music) atau ID polos.
     */
    public static function videoId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^' . self::ID_RE . '$/', $input)) {
            return $input;
        }

        $parts = parse_url(preg_match('#^https?://#i', $input) ? $input : 'https://' . $input);
        $host  = strtolower($parts['host'] ?? '');
        $path  = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            return preg_match('#^/(' . self::ID_RE . ')#', $path, $m) ? $m[1] : null;
        }
        if (!self::isYoutubeHost($host)) {
            return null;
        }
        if (isset($query['v']) && is_string($query['v']) && preg_match('/^' . self::ID_RE . '$/', $query['v'])) {
            return $query['v'];
        }
        if (preg_match('#^/(?:shorts|embed|live|v|e)/(' . self::ID_RE . ')#', $path, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Ambil playlist ID (?list=...) dari link YouTube. Mix otomatis (RD...) tidak didukung.
     */
    public static function playlistId(string $input): ?string
    {
        $input = trim($input);
        $parts = parse_url(preg_match('#^https?://#i', $input) ? $input : 'https://' . $input);
        if (!self::isYoutubeHost(strtolower($parts['host'] ?? ''))) {
            return null;
        }
        parse_str($parts['query'] ?? '', $query);
        $list = $query['list'] ?? null;
        if (!is_string($list) || !preg_match('/^' . self::PLAYLIST_RE . '$/', $list) || str_starts_with($list, 'RD')) {
            return null;
        }
        return $list;
    }

    private static function isYoutubeHost(string $host): bool
    {
        return in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true);
    }

    // ============================================================
    // PLAYLIST
    // ============================================================

    /**
     * Daftar video di playlist (maks max_playlist).
     * @return list<array{id:string, title:string}>
     */
    public function playlist(string $playlistId): array
    {
        // --print-to-file → UTF-8 (stdout di Windows bisa merusak judul non-Latin)
        $listFile = tempnam(sys_get_temp_dir(), 'ytpl');
        try {
            $this->run([
                '--flat-playlist',
                '--skip-download',
                '--playlist-end', (string)$this->maxPlaylist,
                '--print-to-file', "%(id)s\t%(title)s", self::outtmplPath($listFile),
                'https://www.youtube.com/playlist?list=' . $playlistId,
            ], 60);
            $out = (string)file_get_contents($listFile);
        } finally {
            @unlink($listFile);
        }

        $items = [];
        foreach (preg_split('/\R/', trim($out)) as $line) {
            [$id, $title] = array_pad(explode("\t", $line, 2), 2, '');
            if (preg_match('/^' . self::ID_RE . '$/', $id)) {
                $items[] = ['id' => $id, 'title' => $title];
            }
        }
        if (!$items) {
            throw new RuntimeException('Playlist kosong / private');
        }
        return $items;
    }

    // ============================================================
    // CONVERT
    // ============================================================

    /**
     * Download audio video lalu konversi ke MP3.
     * $speed (x1.00–x2.00) & $pitch (semitone -6..+6) = Audio Enhancement, diterapkan lewat ffmpeg.
     * @return array{token:string, title:string, filename:string, size:int, duration:int, speed:float, pitch:int}
     */
    public function convert(string $videoId, string $bitrate, string $owner, float $speed = 1.0, int $pitch = 0): array
    {
        if (!in_array($bitrate, self::BITRATES, true)) {
            $bitrate = '192';
        }
        $speed = round(max(self::SPEED_MIN, min(self::SPEED_MAX, $speed)), 2);
        $pitch = max(self::PITCH_MIN, min(self::PITCH_MAX, $pitch));

        $this->cleanup();

        $token = bin2hex(random_bytes(16));
        $dir   = $this->dir . '/' . $token;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException('Gagal membuat folder sementara di storage/');
        }

        $args = [
            '--no-playlist',
            '--no-progress',
            '--no-warnings',
            '--no-mtime',
            '--restrict-filenames',
            '--match-filter', '!is_live & duration <=? ' . $this->maxDuration,
            '-f', 'bestaudio/best',
            '-x', '--audio-format', 'mp3', '--audio-quality', $bitrate . 'K',
            '--embed-metadata',
            '--print-to-file', "after_move:%(title)s\t%(duration)s", self::outtmplPath($dir . '/info.txt'),
            '-P', $dir,
            '-o', 'audio.%(ext)s',
        ];
        if ($this->ffmpegLocation !== null) {
            array_push($args, '--ffmpeg-location', $this->ffmpegLocation);
        }
        $args[] = 'https://www.youtube.com/watch?v=' . $videoId;

        try {
            $this->run($args, $this->timeout);
        } catch (Throwable $e) {
            self::removeDir($dir);
            throw $e;
        }

        $file = $dir . '/audio.mp3';
        if (!is_file($file)) {
            self::removeDir($dir);
            // match-filter menolak → yt-dlp keluar sukses tanpa file
            throw new RuntimeException('Video dilewati: live / lebih dari ' . intdiv($this->maxDuration, 60) . ' menit');
        }

        $info = is_file($dir . '/info.txt') ? (string)file_get_contents($dir . '/info.txt') : '';
        @unlink($dir . '/info.txt');
        [$title, $duration] = array_pad(explode("\t", trim(strtok($info, "\r\n") ?: ''), 2), 2, '');
        $title = trim($title) !== '' ? trim($title) : $videoId;

        if ($speed != 1.0 || $pitch !== 0) {
            try {
                $this->enhance($file, $bitrate, $speed, $pitch);
            } catch (Throwable $e) {
                self::removeDir($dir);
                throw $e;
            }
            $duration = (int)round((int)$duration / $speed);
        }

        $meta = [
            'owner'    => $owner,
            'videoId'  => $videoId,
            'title'    => $title,
            'filename' => self::safeFilename($title) . '.mp3',
            'size'     => filesize($file),
            'duration' => (int)$duration,
            'speed'    => $speed,
            'pitch'    => $pitch,
            'created'  => time(),
        ];
        file_put_contents($dir . '/meta.json', json_encode($meta));

        return [
            'token'    => $token,
            'title'    => $meta['title'],
            'filename' => $meta['filename'],
            'size'     => $meta['size'],
            'duration' => $meta['duration'],
            'speed'    => $speed,
            'pitch'    => $pitch,
        ];
    }

    // ============================================================
    // AUDIO ENHANCEMENT (speed + pitch)
    // ============================================================

    /**
     * Filter ffmpeg: pitch digeser lewat asetrate (ikut mengubah tempo), lalu atempo
     * mengoreksi tempo supaya hasil akhirnya tepat x$speed dengan pitch +$pitch semitone.
     */
    public static function filterChain(float $speed, int $pitch): string
    {
        $rate    = 44100;
        $p       = 2 ** ($pitch / 12);
        $filters = ['aresample=' . $rate];
        if ($pitch !== 0) {
            $filters[] = 'asetrate=' . round($rate * $p);
            $filters[] = 'aresample=' . $rate;
        }
        // atempo hanya menerima 0.5–2.0 per filter → pecah jadi beberapa
        $tempo = $speed / $p;
        while ($tempo > 2.0) {
            $filters[] = 'atempo=2.0';
            $tempo /= 2.0;
        }
        while ($tempo < 0.5) {
            $filters[] = 'atempo=0.5';
            $tempo /= 0.5;
        }
        if (abs($tempo - 1.0) > 1e-6) {
            $filters[] = 'atempo=' . round($tempo, 6);
        }
        return implode(',', $filters);
    }

    private function enhance(string $file, string $bitrate, float $speed, int $pitch): void
    {
        $tmp = dirname($file) . '/enhanced.mp3';
        $this->exec([
            $this->ffmpeg, '-hide_banner', '-loglevel', 'error', '-y',
            '-i', $file,
            '-map', '0:a', '-map_metadata', '0',
            '-af', self::filterChain($speed, $pitch),
            '-c:a', 'libmp3lame', '-b:a', $bitrate . 'k',
            $tmp,
        ], $this->timeout, 'ffmpeg');

        if (!is_file($tmp) || filesize($tmp) === 0) {
            throw new RuntimeException('Audio Enhancement gagal diproses ffmpeg');
        }
        rename($tmp, $file);
    }

    // ============================================================
    // FILE HASIL
    // ============================================================

    /**
     * Cari file hasil konversi milik $owner.
     * @return array{path:string, filename:string}
     */
    public function find(string $token, string $owner): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new RuntimeException('Token tidak valid');
        }
        $dir  = $this->dir . '/' . $token;
        $meta = is_file($dir . '/meta.json') ? json_decode((string)file_get_contents($dir . '/meta.json'), true) : null;
        if (!is_array($meta) || !is_file($dir . '/audio.mp3') || ($meta['owner'] ?? '') !== $owner) {
            throw new RuntimeException('File tidak ditemukan / sudah kedaluwarsa, konversi ulang');
        }
        return ['path' => $dir . '/audio.mp3', 'filename' => (string)$meta['filename']];
    }

    /**
     * Gabungkan beberapa hasil jadi satu ZIP (file sementara — hapus setelah dikirim).
     */
    public function zip(array $tokens, string $owner): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif di server');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'ytmp3');
        $zip     = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat ZIP');
        }

        $used = [];
        foreach (array_unique($tokens) as $token) {
            try {
                $f = $this->find((string)$token, $owner);
            } catch (Throwable) {
                continue;
            }
            // Nama duplikat → "Judul (2).mp3"
            $name = $f['filename'];
            for ($n = 2; isset($used[strtolower($name)]); $n++) {
                $name = pathinfo($f['filename'], PATHINFO_FILENAME) . " ($n).mp3";
            }
            $used[strtolower($name)] = true;
            $zip->addFile($f['path'], $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE); // MP3 sudah terkompres
        }

        $count = $zip->numFiles;
        $zip->close();
        if ($count === 0) {
            @unlink($zipPath);
            throw new RuntimeException('Tidak ada file yang bisa di-ZIP (sudah kedaluwarsa?)');
        }
        return $zipPath;
    }

    /** Hapus hasil yang lebih tua dari TTL */
    public function cleanup(): void
    {
        foreach (glob($this->dir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - $this->ttl) {
                self::removeDir($dir);
            }
        }
    }

    // ============================================================
    // PROSES yt-dlp
    // ============================================================

    private function run(array $args, int $timeout): string
    {
        return $this->exec(array_merge([$this->ytdlp, '--ignore-config', '--no-cache-dir'], $args), $timeout, 'yt-dlp');
    }

    /** Jalankan proses (lewat ProcessRunner). Balas stdout, lempar pesan ramah kalau gagal. */
    private function exec(array $cmd, int $timeout, string $bin): string
    {
        $r = ProcessRunner::run($cmd, $timeout, $bin);
        if ($r['code'] !== 0) {
            throw new RuntimeException(self::friendlyError($r['err']));
        }
        return $r['out'];
    }

    /** Path untuk --print-to-file (dievaluasi sebagai output template → escape %) */
    private static function outtmplPath(string $path): string
    {
        return str_replace('%', '%%', $path);
    }

    private static function friendlyError(string $stderr): string
    {
        $map = [
            'Private video'                => 'Video private',
            'Video unavailable'            => 'Video tidak tersedia / dihapus',
            'Sign in to confirm your age'  => 'Video dibatasi umur (perlu login)',
            'members-only'                 => 'Video khusus member',
            'not available in your country'=> 'Video diblokir di negara server',
            'ffprobe and ffmpeg not found' => 'ffmpeg belum terpasang — klik "Install otomatis" di panel Tools',
            'ffmpeg not found'             => 'ffmpeg belum terpasang — klik "Install otomatis" di panel Tools',
            'ffprobe not found'            => 'ffprobe belum terpasang — klik "Install otomatis" di panel Tools',
            'Sign in to confirm you'       => 'YouTube minta verifikasi bot — coba lagi nanti / update yt-dlp',
            'HTTP Error 429'               => 'Terlalu banyak request ke YouTube, coba lagi nanti',
        ];
        foreach ($map as $needle => $msg) {
            if (stripos($stderr, $needle) !== false) {
                return $msg;
            }
        }
        // Ambil baris ERROR terakhir saja (tanpa path server)
        if (preg_match_all('/ERROR:\s*(.+)/', $stderr, $m)) {
            $line = end($m[1]);
            $line = preg_replace('/^\[[^\]]+\]\s*[A-Za-z0-9_-]+:\s*/', '', $line);
            return mb_strimwidth(trim($line), 0, 160, '…');
        }
        return 'Konversi gagal';
    }

    // ============================================================
    // UTIL
    // ============================================================

    public static function safeFilename(string $title): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $title);
        $name = trim(preg_replace('/\s+/u', ' ', (string)$name), ' .');
        $name = mb_substr($name, 0, 120);
        return $name !== '' ? $name : 'audio';
    }

    private static function removeDir(string $dir): void
    {
        foreach (glob($dir . '/{,.}[!.,!..]*', GLOB_BRACE) ?: [] as $f) {
            is_dir($f) ? self::removeDir($f) : @unlink($f);
        }
        @rmdir($dir);
    }
}
