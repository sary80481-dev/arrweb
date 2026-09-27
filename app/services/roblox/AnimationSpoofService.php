<?php
// app/services/roblox/AnimationSpoofService.php — download animation (KeyframeSequence) → file .rbxm
//
// Roblox TIDAK punya API upload animation, jadi tool ini hanya MEN-DOWNLOAD animasi
// jadi file .rbxm (bisa banyak sekaligus / di-ZIP). Upload tetap manual di Studio:
// drag .rbxm → klik kanan → Save to Roblox → copy ID baru.
//
// Hasil disimpan sementara di storage/anim/<token>/ (diblok .htaccess), per user,
// dihapus otomatis setelah TTL.

class AnimationSpoofService
{
    private string $dir;
    private int $ttl;

    public function __construct()
    {
        $this->dir = STORAGE_PATH . '/anim';
        $this->ttl = (int)config('app.anim.ttl', 3600);
    }

    /**
     * Download 1 animation → simpan .rbxm. $service sudah dibuat dgn API key user.
     * @return array{token:string, animId:string, name:string, filename:string, size:int, kind:string}
     */
    public function download(RobloxAssetService $service, string $animId, string $name, string $owner): array
    {
        if (!preg_match('/^\d{1,20}$/', $animId)) {
            throw new RuntimeException('Animation ID tidak valid: ' . $animId);
        }
        $this->cleanup();

        $file = $service->fetchRaw($animId);
        if ($file['kind'] !== 'animation') {
            // Tetap simpan, tapi beri tahu: ini bukan KeyframeSequence
            // (bisa jadi ID itu bukan animasi, mis. gambar/model)
        }

        $token = bin2hex(random_bytes(16));
        $dir   = $this->dir . '/' . $token;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException('Gagal membuat folder sementara di storage/');
        }

        file_put_contents($dir . '/anim.rbxm', $file['bytes']);
        $clean = self::safeName($name) ?: ('animation_' . $animId);
        $meta = [
            'owner'    => $owner,
            'animId'   => $animId,
            'name'     => $clean,
            'filename' => $clean . '.rbxm',
            'size'     => strlen($file['bytes']),
            'kind'     => $file['kind'],
            'created'  => time(),
        ];
        file_put_contents($dir . '/meta.json', json_encode($meta));

        return ['token' => $token] + array_intersect_key($meta, array_flip(['animId', 'name', 'filename', 'size', 'kind']));
    }

    /** @return array{path:string, filename:string} */
    public function find(string $token, string $owner): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new RuntimeException('Token tidak valid');
        }
        $dir  = $this->dir . '/' . $token;
        $meta = is_file($dir . '/meta.json') ? json_decode((string)file_get_contents($dir . '/meta.json'), true) : null;
        if (!is_array($meta) || !is_file($dir . '/anim.rbxm') || ($meta['owner'] ?? '') !== $owner) {
            throw new RuntimeException('File tidak ditemukan / sudah kedaluwarsa, download ulang');
        }
        return ['path' => $dir . '/anim.rbxm', 'filename' => (string)$meta['filename']];
    }

    /** Gabungkan beberapa .rbxm jadi satu ZIP (file sementara — hapus setelah dikirim) */
    public function zip(array $tokens, string $owner): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif di server');
        }
        $zipPath = tempnam(sys_get_temp_dir(), 'anim');
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
            $name = $f['filename'];
            for ($n = 2; isset($used[strtolower($name)]); $n++) {
                $name = pathinfo($f['filename'], PATHINFO_FILENAME) . " ($n).rbxm";
            }
            $used[strtolower($name)] = true;
            $zip->addFile($f['path'], $name);
        }
        $count = $zip->numFiles;
        $zip->close();
        if ($count === 0) {
            @unlink($zipPath);
            throw new RuntimeException('Tidak ada file yang bisa di-ZIP (sudah kedaluwarsa?)');
        }
        return $zipPath;
    }

    public function cleanup(): void
    {
        foreach (glob($this->dir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - $this->ttl) {
                foreach (glob($dir . '/*') ?: [] as $f) {
                    @unlink($f);
                }
                @rmdir($dir);
            }
        }
    }

    public static function safeName(string $name): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $name);
        $name = trim(preg_replace('/\s+/u', ' ', (string)$name), ' .');
        return mb_substr($name, 0, 60);
    }
}
