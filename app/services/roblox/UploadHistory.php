<?php
// app/services/roblox/UploadHistory.php — riwayat upload aset ke Roblox, per user
//
// Disimpan di storage/history/<userId>.json (diblok .htaccess). Dicatat otomatis oleh
// SpoofApiController tiap upload (Auto Spoof & YT → MP3). Hapus di sini TIDAK menghapus
// aset di Roblox — hanya dari daftar riwayat.
//
// Entry: {id, assetId|null, operationId|null, name, source: ytmp3|reupload|file, ref,
//         kind, creatorType, creatorId, games: [universeId], moderation?: Reviewing|Approved|Rejected,
//         moderationCheckedAt?, createdAt}

class UploadHistory
{
    /** Batas entry per user (yang paling lama dibuang) */
    private const MAX_ENTRIES = 5000;

    private string $file;

    public function __construct(string $owner)
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $owner);
        if ($safe === '' || $safe === null) {
            throw new RuntimeException('User tidak valid');
        }
        $this->file = STORAGE_PATH . '/history/' . $safe . '.json';
    }

    /** @return list<array> terbaru dulu */
    public function all(): array
    {
        return $this->locked(fn(array $list) => [$list, false])[0];
    }

    /**
     * Catat hasil upload. $result = balasan uploadAndWait (assetId dan/atau operationId).
     */
    public function add(array $result, array $meta): void
    {
        $assetId = isset($result['assetId']) ? (string)$result['assetId'] : null;
        $opId    = isset($result['operationId']) ? (string)$result['operationId'] : null;
        if ($assetId === null && $opId === null) {
            return;
        }

        $entry = [
            'id'          => bin2hex(random_bytes(6)),
            'assetId'     => $assetId,
            'operationId' => $assetId === null ? $opId : null,
            'name'        => mb_substr((string)($meta['name'] ?? ''), 0, 120),
            'source'      => (string)($meta['source'] ?? ''),
            'ref'         => mb_substr((string)($meta['ref'] ?? ''), 0, 200),
            'kind'        => (string)($result['kind'] ?? $meta['kind'] ?? ''),
            'creatorType' => (string)($meta['creatorType'] ?? ''),
            'creatorId'   => (string)($meta['creatorId'] ?? ''),
            'games'       => [],
            'createdAt'   => date('c'),
        ];

        $this->locked(function (array $list) use ($entry) {
            array_unshift($list, $entry);
            return [array_slice($list, 0, self::MAX_ENTRIES), true];
        });
    }

    /** Operasi upload selesai → isi asset ID di entry yang masih pending */
    public function resolveOperation(string $operationId, string $assetId): void
    {
        $this->locked(function (array $list) use ($operationId, $assetId) {
            $changed = false;
            foreach ($list as &$e) {
                if (($e['operationId'] ?? null) === $operationId && empty($e['assetId'])) {
                    $e['assetId']     = $assetId;
                    $e['operationId'] = null;
                    $changed = true;
                }
            }
            unset($e);
            return [$list, $changed];
        });
    }

    /** Tandai aset sudah diizinkan di game (universe) */
    public function markGranted(string $universeId, array $assetIds): void
    {
        if (!$assetIds) {
            return;
        }
        $set = array_flip(array_map('strval', $assetIds));
        $this->locked(function (array $list) use ($universeId, $set) {
            $changed = false;
            foreach ($list as &$e) {
                if (!empty($e['assetId']) && isset($set[$e['assetId']]) && !in_array($universeId, $e['games'] ?? [], true)) {
                    $e['games'][] = $universeId;
                    $changed = true;
                }
            }
            unset($e);
            return [$list, $changed];
        });
    }

    /** Tandai aset sudah dijadikan publik */
    public function markPublic(array $assetIds): void
    {
        if (!$assetIds) {
            return;
        }
        $set = array_flip(array_map('strval', $assetIds));
        $this->locked(function (array $list) use ($set) {
            $changed = false;
            foreach ($list as &$e) {
                if (!empty($e['assetId']) && isset($set[$e['assetId']]) && empty($e['public'])) {
                    $e['public'] = true;
                    $changed = true;
                }
            }
            unset($e);
            return [$list, $changed];
        });
    }

    /** Simpan status review Roblox. $states = [assetId => Reviewing|Approved|Rejected] */
    public function setModeration(array $states): void
    {
        if (!$states) {
            return;
        }
        $now = date('c');
        $this->locked(function (array $list) use ($states, $now) {
            $changed = false;
            foreach ($list as &$e) {
                $id = $e['assetId'] ?? null;
                if ($id !== null && isset($states[$id])) {
                    $e['moderation']          = $states[$id];
                    $e['moderationCheckedAt'] = $now;
                    $changed = true;
                }
            }
            unset($e);
            return [$list, $changed];
        });
    }

    /** Hapus entry dari riwayat ($ids kosong = hapus semua). Balas jumlah yang dihapus. */
    public function delete(array $ids): int
    {
        return $this->locked(function (array $list) use ($ids) {
            $before = count($list);
            if ($ids) {
                $drop = array_flip(array_map('strval', $ids));
                $list = array_values(array_filter($list, fn($e) => !isset($drop[$e['id'] ?? ''])));
            } else {
                $list = [];
            }
            return [$list, $before !== count($list), $before - count($list)];
        })[2];
    }

    /**
     * Baca-ubah-tulis dengan flock (upload paralel menulis bersamaan).
     * $fn(list) → [list baru, perlu disimpan?, ...nilai balik tambahan]
     */
    private function locked(callable $fn): array
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Gagal membuat folder storage/history');
        }
        $fp = fopen($this->file, 'c+');
        if (!$fp) {
            throw new RuntimeException('Gagal membuka riwayat upload');
        }
        try {
            flock($fp, LOCK_EX);
            $raw  = stream_get_contents($fp);
            $list = $raw ? json_decode($raw, true) : [];
            if (!is_array($list)) {
                @copy($this->file, $this->file . '.corrupt.' . time());
                $list = [];
            }

            $out = $fn($list);
            if (!empty($out[1])) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode($out[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                fflush($fp);
            }
            return $out;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
