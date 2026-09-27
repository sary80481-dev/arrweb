<?php
// app/controllers/api/YtMp3ApiController.php — YT → MP3 massal
//
// POST JSON  {action:"playlist", url}                 → {items:[{id,title}]}
// POST JSON  {action:"convert",  url|videoId, bitrate, speed?, pitch?}
//                                                     → {token, title, filename, size, duration, speed, pitch}
//            speed = x1.00–x2.00, pitch = semitone -6..+6 (Audio Enhancement)
// GET        ?action=download&token=...               → file .mp3
// GET        ?action=zip&tokens=a,b,c                 → file .zip (semua hasil)
// POST JSON  {action:"tools"}                         → status yt-dlp & ffmpeg (MediaTools::status)
// POST JSON  {action:"install", tool:"ytdlp"|"ffmpeg"} → download otomatis ke storage/bin
//
// Tiap video = 1 request "convert" (JS yang mengatur antrian & paralelnya).

class YtMp3ApiController extends ApiController
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $this->error('Login dulu untuk memakai YT → MP3.', ['login_url' => url('login')], 401);
        }
        $owner = (string)Auth::id();

        // Lepas lock session — konversi bisa lama & JS kirim beberapa request paralel
        session_write_close();

        $input  = Request::isPost() ? (Request::json() ?? $_POST) : $_GET;
        $action = (string)($input['action'] ?? '');
        $service = new YoutubeMp3Service();

        try {
            switch ($action) {
                case 'tools':
                    $this->json(MediaTools::status());

                case 'install':
                    if (!config('app.ytmp3.allow_install', true)) {
                        $this->error('Install otomatis dimatikan (app.ytmp3.allow_install)');
                    }
                    set_time_limit(0); // ffmpeg ±190MB
                    ignore_user_abort(true);
                    match ((string)($input['tool'] ?? '')) {
                        'ytdlp'  => MediaTools::installYtdlp(),
                        'ffmpeg' => MediaTools::installFfmpeg(),
                        default  => $this->error('Tool tidak dikenal'),
                    };
                    $this->json(MediaTools::status());

                case 'playlist':
                    $listId = YoutubeMp3Service::playlistId((string)($input['url'] ?? ''));
                    if ($listId === null) {
                        $this->error('Link playlist tidak valid');
                    }
                    set_time_limit(90);
                    $this->json(['items' => $service->playlist($listId)]);

                case 'convert':
                    $videoId = YoutubeMp3Service::videoId((string)($input['videoId'] ?? $input['url'] ?? ''));
                    if ($videoId === null) {
                        $this->error('Link YouTube tidak valid');
                    }
                    set_time_limit((int)config('app.ytmp3.timeout', 240) * 2 + 30); // yt-dlp + ffmpeg
                    $this->json($service->convert(
                        $videoId,
                        (string)($input['bitrate'] ?? '192'),
                        $owner,
                        (float)($input['speed'] ?? 1),
                        (int)($input['pitch'] ?? 0),
                    ));

                case 'download':
                    $file = $service->find((string)($input['token'] ?? ''), $owner);
                    $this->send($file['path'], $file['filename'], 'audio/mpeg');

                case 'zip':
                    $tokens = array_slice(array_filter(explode(',', (string)($input['tokens'] ?? ''))), 0, 200);
                    set_time_limit(120);
                    $zipPath = $service->zip($tokens, $owner);
                    $this->send($zipPath, 'yt-mp3-' . date('Ymd-His') . '.zip', 'application/zip', true);

                default:
                    $this->error('Action tidak dikenal');
            }
        } catch (Throwable $e) {
            // Pesan saja — jangan kirim file/line server
            $this->error($e->getMessage());
        }
    }

    /** Kirim file sebagai attachment lalu exit */
    private function send(string $path, string $filename, string $type, bool $deleteAfter = false): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $ascii = preg_replace('/[^A-Za-z0-9 ._()-]+/', '_', $filename);
        header('Content-Type: ' . $type);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        if ($deleteAfter) {
            @unlink($path);
        }
        exit;
    }
}
