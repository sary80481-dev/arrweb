<?php
// app/services/roblox/RobloxAssetService.php — download & upload aset Roblox lewat Open Cloud
//
// Dipakai fitur Auto Spoof (re-upload massal gambar / audio ke akun atau grup sendiri).
// API key TIDAK pernah disimpan / di-log — hanya diteruskan ke apis.roblox.com.
//
// Scope API key yang dibutuhkan:
//   - asset:read + asset:write      → upload & cek status operasi
//   - legacy-asset:manage           → download aset dari asset ID (asset-delivery-api)

class RobloxAssetService
{
    private const UPLOAD_URL    = 'https://apis.roblox.com/assets/v1/assets';
    private const OPERATION_URL = 'https://apis.roblox.com/assets/v1/operations/';
    private const DELIVERY_URL  = 'https://apis.roblox.com/asset-delivery-api/v1/assetId/';
    private const LEGACY_URL    = 'https://assetdelivery.roblox.com/v2/assetId/';
    private const INTROSPECT_URL = 'https://apis.roblox.com/api-keys/v1/introspect';
    private const PERMISSIONS_URL = 'https://apis.roblox.com/asset-permissions-api/v1/assets/permissions';
    private const PLACE_UNIVERSE_URL = 'https://apis.roblox.com/universes/v1/places/';
    private const ASSET_URL = 'https://apis.roblox.com/assets/v1/assets/';

    /** Aset per request izin (dipecah biar aman dari batas batch Roblox) */
    private const GRANT_CHUNK = 10;

    /** Operasi yang dibutuhkan Auto Spoof */
    private const REQUIRED_OPS = ['asset:read', 'asset:write', 'legacy-asset:manage'];

    /** Scope opsional — hanya untuk fitur "Izinkan ke game" */
    private const OPTIONAL_OPS = ['asset-permissions:write'];

    /** Batas ukuran file yang di-download / di-upload (byte) */
    public const MAX_BYTES = 20 * 1024 * 1024;

    /** Host yang boleh di-download (cegah SSRF lewat field "location") */
    private const ALLOWED_HOSTS = ['roblox.com', 'rbxcdn.com'];

    /** Signature file → [kind, assetType Open Cloud, content-type, ekstensi] */
    private const SIGNATURES = [
        ["\x89PNG",  'image', 'Decal', 'image/png',  'png'],
        ["\xFF\xD8\xFF", 'image', 'Decal', 'image/jpeg', 'jpg'],
        ['BM',       'image', 'Decal', 'image/bmp',  'bmp'],
        ['OggS',     'audio', 'Audio', 'audio/ogg',  'ogg'],
        ['fLaC',     'audio', 'Audio', 'audio/flac', 'flac'],
        ['RIFF',     'audio', 'Audio', 'audio/wav',  'wav'],
        ['ID3',      'audio', 'Audio', 'audio/mpeg', 'mp3'],
        ["\xFF\xFB", 'audio', 'Audio', 'audio/mpeg', 'mp3'],
        ["\xFF\xF3", 'audio', 'Audio', 'audio/mpeg', 'mp3'],
        ["\xFF\xF2", 'audio', 'Audio', 'audio/mpeg', 'mp3'],
    ];

    public function __construct(private string $apiKey)
    {
    }

    // ============================================================
    // DOWNLOAD
    // ============================================================

    /**
     * Download isi aset dari asset ID. Decal otomatis di-resolve ke gambar aslinya.
     * @return array{bytes:string, sourceId:string}
     */
    public function download(string $assetId, int $depth = 0): array
    {
        $location = $this->resolveLocation($assetId);
        $bytes    = $this->fetchLocation($location);

        // Decal / model XML → ambil ID gambar / audio di dalamnya lalu download itu
        if ($depth === 0 && self::detect($bytes) === null) {
            $innerId = self::extractInnerAssetId($bytes);
            if ($innerId !== null && $innerId !== $assetId) {
                return $this->download($innerId, 1);
            }
        }

        return ['bytes' => $bytes, 'sourceId' => $assetId];
    }

    /**
     * Ambil isi aset MENTAH apa adanya (tanpa deteksi gambar/audio, tanpa ikuti inner-id).
     * Dipakai untuk Animation (KeyframeSequence) → langsung jadi file .rbxm yang bisa
     * di-drag ke Studio. @return array{bytes:string, kind:string}
     */
    public function fetchRaw(string $assetId): array
    {
        $bytes = $this->fetchLocation($this->resolveLocation($assetId));
        return ['bytes' => $bytes, 'kind' => self::rbxKind($bytes)];
    }

    /** Tebak jenis file model Roblox dari header (biar bisa peringatkan kalau bukan animasi) */
    public static function rbxKind(string $bytes): string
    {
        if (str_starts_with($bytes, "<roblox!")) {
            // Binary .rbxm — cari nama ClassName KeyframeSequence di dalamnya
            return str_contains($bytes, 'KeyframeSequence') ? 'animation' : 'model-bin';
        }
        if (str_starts_with($bytes, '<roblox') || str_starts_with($bytes, "\xEF\xBB\xBF<roblox")) {
            return str_contains($bytes, 'KeyframeSequence') ? 'animation' : 'model-xml';
        }
        $info = self::detect($bytes);
        return $info['kind'] ?? 'unknown';
    }

    private function resolveLocation(string $assetId): string
    {
        // 1. Open Cloud (pakai API key → bisa akses aset privat milik kamu / grup kamu)
        //    Balasan bisa {location} (v1) atau {locations:[{location}]} — dua-duanya dicek,
        //    dan HTTP 200 pun bisa berisi {errors:[...]}.
        $res  = $this->request('GET', self::DELIVERY_URL . $assetId, ['x-api-key: ' . $this->apiKey]);
        $json = json_decode($res['body'], true) ?: [];
        $location = self::pickLocation($json);
        if ($res['status'] === 200 && $location !== null) {
            return $location;
        }
        $cloudError = self::deliveryError($res['status'], $json, $res['body']);

        // 2. Fallback asset delivery publik (tanpa login — hanya aset publik)
        try {
            $legacy   = $this->request('GET', self::LEGACY_URL . $assetId, ['Accept: application/json']);
            $location = self::pickLocation(json_decode($legacy['body'], true) ?: []);
            if ($legacy['status'] === 200 && $location !== null) {
                return $location;
            }
        } catch (Throwable) {
            // abaikan — yang dilaporkan error Open Cloud
        }

        throw new RuntimeException('Gagal download aset ' . $assetId . ': ' . $cloudError);
    }

    /**
     * Normalisasi scope introspect ke bentuk "asset:read" / "legacy-asset:manage".
     * Roblox bisa mengirim: ["asset:read"], [{name:"asset", operations:["read"]}],
     * [{name:"assets", operations:["asset:read"]}], [{scopeType:"asset:read"}], dst.
     * @return list<string>
     */
    private static function parseScopes($scopes): array
    {
        $norm = static function (string $name): string {
            $name = strtolower(trim($name));
            return preg_replace('/s$/', '', $name); // "assets" → "asset", "legacy-assets" → "legacy-asset"
        };
        $full = static function (string $name, string $op) use ($norm): string {
            $op = strtolower(trim($op));
            if (str_contains($op, ':')) {
                [$n, $o] = explode(':', $op, 2);
                return $norm($n) . ':' . $o;
            }
            return $norm($name) . ':' . $op;
        };

        $ops = [];
        foreach ((array)$scopes as $scope) {
            if (is_string($scope)) {
                if (str_contains($scope, ':')) {
                    $ops[] = $full('', $scope);
                }
                continue;
            }
            if (!is_array($scope)) {
                continue;
            }
            $name = (string)($scope['name'] ?? $scope['scopeType'] ?? $scope['type'] ?? '');
            $list = (array)($scope['operations'] ?? $scope['permissions'] ?? []);
            if (!$list && str_contains($name, ':')) {
                $ops[] = $full('', $name);
            }
            foreach ($list as $op) {
                if (is_string($op) && $op !== '') {
                    $ops[] = $full($name, $op);
                }
            }
        }
        return array_values(array_unique($ops));
    }

    /** Ambil URL file dari balasan asset delivery (v1: location, v2: locations[]) */
    private static function pickLocation(array $json): ?string
    {
        if (!empty($json['location']) && is_string($json['location'])) {
            return $json['location'];
        }
        foreach ((array)($json['locations'] ?? []) as $loc) {
            if (!empty($loc['location']) && is_string($loc['location'])) {
                return $loc['location'];
            }
        }
        return null;
    }

    /** Pesan error asset delivery Open Cloud + petunjuk sesuai penyebabnya */
    private static function deliveryError(int $status, array $json, string $raw): string
    {
        $msg = (string)($json['message'] ?? $json['errors'][0]['message'] ?? '');
        $hint = match (true) {
            $status === 401                               => 'API key tidak valid / kedaluwarsa, atau IP server belum ada di Accepted IP.',
            $status === 403 && stripos($msg, 'scope') !== false,
            stripos($msg, 'insufficient') !== false       => 'API key belum punya scope legacy-asset:manage.',
            $status === 403                               => 'API key tidak diizinkan (cek Accepted IP & scope legacy-asset:manage).',
            $status === 404                               => 'Asset ID tidak ditemukan.',
            $status === 429                               => 'Kena rate limit Roblox, turunkan kecepatan / coba lagi nanti.',
            stripos($msg, 'not authorized') !== false     => 'Aset ini bukan milik pemilik API key. Open Cloud hanya bisa download aset milikmu sendiri '
                                                           . '(aset grup → API key harus dibuat dari grup itu). Cek juga: yang diisi Asset ID, bukan User ID.',
            stripos($msg, 'not approved') !== false       => 'Aset masih di-review / ditolak moderasi, atau pemilik aset beda dengan pemilik API key '
                                                           . '(aset grup harus pakai API key yang dibuat dari grup itu).',
            default                                       => '',
        };
        $text = 'HTTP ' . $status . ($msg !== '' ? ' — ' . $msg : ($raw !== '' && $status !== 200 ? ' — ' . substr(strip_tags($raw), 0, 120) : ''));
        return $text . ($hint !== '' ? '. ' . $hint : '');
    }

    // ============================================================
    // IZIN PAKAI DI GAME (Asset Permissions API — scope legacy-asset:manage)
    // ============================================================

    /**
     * Izinkan banyak aset dipakai di satu game (universe) sekaligus.
     * Butuh scope asset-permissions:write (lihat creator-docs asset-permissions-api/v1).
     * Kalau game tidak ditemukan / tidak bisa diakses, ID dicoba dibaca sebagai Place ID
     * lalu diulang dengan Universe ID-nya (izin tetap hanya berhasil untuk game milik key).
     * @param list<string> $assetIds
     * @return array{granted:list<string>, failed:array<string,string>, universeId:string, placeId?:string}
     */
    public function grantUniverse(string $universeId, array $assetIds): array
    {
        $ids    = array_values(array_unique(array_map('strval', $assetIds)));
        $result = $this->grantBatch($universeId, $ids);

        if (!$result['granted'] && $result['subjectProblem']) {
            $fromPlace = $this->universeFromPlace($universeId);
            if ($fromPlace !== null && $fromPlace !== $universeId) {
                $retry = $this->grantBatch($fromPlace, $ids);
                unset($retry['subjectProblem']);
                return $retry + ['universeId' => $fromPlace, 'placeId' => $universeId];
            }
        }
        unset($result['subjectProblem']);
        return $result + ['universeId' => $universeId];
    }

    /**
     * Jadikan aset PUBLIK (bisa dipakai siapa saja) — subjectType "All", action "Use".
     * Menurut spec hanya untuk Decal / Image / Mesh. Audio TIDAK bisa (hanya lewat
     * "Distribute on Creator Store" di Creator Dashboard).
     * @return array{granted:list<string>, failed:array<string,string>}
     */
    public function grantPublic(array $assetIds): array
    {
        $ids    = array_values(array_unique(array_map('strval', $assetIds)));
        $result = $this->grantBatch(null, $ids);
        unset($result['subjectProblem']);
        return $result;
    }

    /** @return array{granted:list<string>, failed:array<string,string>, subjectProblem:bool} */
    /** $universeId null = publik (subjectType All) */
    private function grantBatch(?string $universeId, array $ids): array
    {
        $granted = [];
        $failed  = [];
        $subjectProblem = false;

        foreach (array_chunk($ids, self::GRANT_CHUNK) as $chunk) {
            $body = json_encode([
                'subjectType' => $universeId === null ? 'All' : 'Universe',
                'subjectId'   => $universeId,   // wajib kosong untuk All
                'action'      => 'Use',
                'requests'    => array_map(fn($id) => ['assetId' => (int)$id, 'grantToDependencies' => true], $chunk),
            ]);
            $res  = $this->request('PATCH', self::PERMISSIONS_URL, [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
            ], $body);
            $json = json_decode($res['body'], true) ?: [];

            if ($res['status'] < 200 || $res['status'] >= 300) {
                // Balasan error: {"error":{"code","message"}}
                $code = (string)($json['error']['code'] ?? '');
                $msg  = self::errorMessage($json, $res['body']);
                if (in_array($code, ['SubjectNotFound', 'CannotManageSubject'], true)
                    || preg_match('/subject|universe/i', $msg)) {
                    $subjectProblem = true;
                }
                $reason = self::grantCodeMessage($code) ?? ('HTTP ' . $res['status'] . ': ' . $msg . self::grantHint($res['status'], $msg));
                foreach ($chunk as $id) {
                    $failed[$id] = $reason;
                }
                continue;
            }

            // Balasan: {successAssetIds:[...], errors:[{assetId, code}]}
            foreach ((array)($json['errors'] ?? []) as $err) {
                $id   = (string)($err['assetId'] ?? '');
                $code = (string)($err['code'] ?? '');
                if ($id === '') {
                    continue;
                }
                if ($code === 'PublicAssetCannotBeGrantedTo') {
                    continue; // aset publik → sudah bisa dipakai di game mana pun
                }
                if (in_array($code, ['SubjectNotFound', 'CannotManageSubject'], true)) {
                    $subjectProblem = true;
                }
                $failed[$id] = self::grantCodeMessage($code) ?? ((string)($err['message'] ?? '') ?: $code ?: 'ditolak');
            }
            foreach ($chunk as $id) {
                if (!isset($failed[$id])) {
                    $granted[] = $id;
                }
            }
        }
        return ['granted' => $granted, 'failed' => $failed, 'subjectProblem' => $subjectProblem];
    }

    /** Pesan untuk kode error Asset Permissions API */
    private static function grantCodeMessage(string $code): ?string
    {
        return match ($code) {
            'CannotManageAsset'      => 'Aset bukan milik pemilik API key (aset grup → pakai API key grup)',
            'CannotManageSubject'    => 'Tidak punya akses ke game ini. Cek: (1) isi Universe ID, bukan Place ID — Creator Dashboard → '
                                        . 'Creations → ⋯ di game → Copy Universe ID; (2) kalau game milik grup, API key harus dibuat dari grup itu',
            'SubjectNotFound'        => 'Game tidak ditemukan — isi Universe ID (bukan Place ID)',
            'AssetNotFound'          => 'Asset ID tidak ditemukan',
            'AssetTypeNotEnabled'    => 'Jenis aset ini tidak didukung (audio tidak bisa dijadikan publik lewat API — pakai "Distribute on Creator Store" di Creator Dashboard)',
            'PermissionLimitReached' => 'Batas jumlah izin untuk aset ini sudah tercapai',
            'DependenciesLimitReached' => 'Terlalu banyak dependensi aset',
            default                  => null,
        };
    }

    private static function grantHint(int $status, string $msg = ''): string
    {
        if (stripos($msg, 'scope') !== false) {
            return ' — API key belum punya scope asset-permissions:write. Di halaman API key: Select API System → '
                . 'asset-permissions → centang write, lalu Save Changes';
        }
        return match ($status) {
            401     => ' — API key tidak valid / IP belum diizinkan',
            403     => ' — butuh scope asset-permissions:write, dan game harus milik akun/grup yang sama dengan API key',
            404     => ' — Universe ID / aset tidak ditemukan',
            429     => ' — kena rate limit (100/menit), coba lagi sebentar lagi',
            default => '',
        };
    }

    /**
     * Place ID → Universe ID (endpoint publik Roblox, tanpa API key). null kalau bukan Place ID.
     */
    public function universeFromPlace(string $placeId): ?string
    {
        try {
            $res = $this->request('GET', self::PLACE_UNIVERSE_URL . rawurlencode($placeId) . '/universe', ['Accept: application/json']);
        } catch (Throwable) {
            return null;
        }
        $json = json_decode($res['body'], true) ?: [];
        $id   = $json['universeId'] ?? null;
        return $res['status'] === 200 && $id !== null && preg_match('/^\d{1,20}$/', (string)$id) ? (string)$id : null;
    }


    // ============================================================
    // STATUS REVIEW (moderasi) — GET assets/v1/assets/{id}, scope asset:read
    // ============================================================

    /**
     * Status moderasi banyak aset: Reviewing | Approved | Rejected.
     * @param list<string> $assetIds
     * @return array{states:array<string,string>, errors:array<string,string>}
     */
    public function moderation(array $assetIds): array
    {
        $states = [];
        $errors = [];
        foreach (array_values(array_unique(array_map('strval', $assetIds))) as $id) {
            try {
                $res  = $this->request('GET', self::ASSET_URL . rawurlencode($id), ['x-api-key: ' . $this->apiKey]);
                $json = json_decode($res['body'], true) ?: [];
                if ($res['status'] === 200) {
                    $state = (string)($json['moderationResult']['moderationState'] ?? '');
                    // Tanpa moderationResult → belum ada hasil review
                    $states[$id] = in_array($state, ['Reviewing', 'Approved', 'Rejected'], true) ? $state : 'Reviewing';
                    continue;
                }
                if ($res['status'] === 429) {
                    $errors[$id] = 'Rate limit — dicek lagi nanti';
                    break; // sisanya dicek di putaran berikutnya
                }
                $errors[$id] = 'HTTP ' . $res['status'] . ': ' . self::errorMessage($json, $res['body'])
                    . ($res['status'] === 403 ? ' — butuh scope asset:read & aset milik pemilik API key' : '');
            } catch (Throwable $e) {
                $errors[$id] = $e->getMessage();
            }
        }
        return ['states' => $states, 'errors' => $errors];
    }

    // ============================================================
    // CEK KONEKSI API KEY
    // ============================================================

    /**
     * Cek API key: valid / aktif, pemilik, scope. Opsional tes download satu asset ID.
     * @return array{ok:bool, name:?string, userId:?string, enabled:?bool, expired:?bool,
     *               scopes:array, missing:list<string>, asset:?array}
     */
    public function check(?string $testAssetId = null): array
    {
        $res  = $this->request('POST', self::INTROSPECT_URL, ['Content-Type: application/json'], json_encode(['apiKey' => $this->apiKey]));
        $json = json_decode($res['body'], true) ?: [];
        if (in_array($res['status'], [400, 401, 403], true)) {
            throw new RuntimeException('API key ditolak Roblox (HTTP ' . $res['status'] . '): ' . self::errorMessage($json, $res['body'])
                . '. Pastikan key di-copy lengkap & belum di-regenerate.');
        }
        $introspected = $res['status'] === 200;

        $ops     = self::parseScopes($json['scopes'] ?? []);
        // Format scope tidak dikenali → jangan klaim "belum ada", tes download yang menentukan
        $missing = $introspected && $ops ? array_values(array_diff(self::REQUIRED_OPS, $ops)) : [];
        $missingOptional = $introspected && $ops ? array_values(array_diff(self::OPTIONAL_OPS, $ops)) : [];

        $asset = null;
        if ($testAssetId !== null) {
            try {
                $file  = $this->download($testAssetId);
                $info  = self::detect($file['bytes']);
                $asset = ['ok' => true, 'id' => $testAssetId, 'kind' => $info['kind'] ?? 'unknown', 'bytes' => strlen($file['bytes'])];
            } catch (Throwable $e) {
                $asset = ['ok' => false, 'id' => $testAssetId, 'error' => $e->getMessage()];
            }
        }

        $enabled = isset($json['enabled']) ? (bool)$json['enabled'] : null;
        $expired = isset($json['expired']) ? (bool)$json['expired'] : null;

        return [
            'ok'      => $enabled !== false && $expired !== true && !$missing && ($asset['ok'] ?? $introspected),
            'introspected' => $introspected,
            'name'    => isset($json['name']) ? (string)$json['name'] : null,
            'userId'  => isset($json['authorizedUserId']) ? (string)$json['authorizedUserId'] : null,
            'enabled' => $enabled,
            'expired' => $expired,
            'scopes'  => $ops,
            'rawScopes' => ($missing || !$ops) ? ($json['scopes'] ?? null) : null, // bantu debug kalau format beda
            'missing' => $missing,
            'missingOptional' => $missingOptional,
            'asset'   => $asset,
        ];
    }

    private function fetchLocation(string $url): string
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $ok   = false;
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                $ok = true;
                break;
            }
        }
        if (!$ok || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Lokasi download tidak valid: ' . $host);
        }

        $res = $this->request('GET', $url, [], null, true);
        if ($res['status'] !== 200 || $res['body'] === '') {
            throw new RuntimeException('Gagal download file aset (HTTP ' . $res['status'] . ')');
        }
        return $res['body'];
    }

    /** ID aset di dalam XML decal/model: <url>http://www.roblox.com/asset/?id=123</url> */
    public static function extractInnerAssetId(string $bytes): ?string
    {
        if (preg_match('#(?:asset/?\?id=|rbxassetid://)(\d+)#i', $bytes, $m)) {
            return $m[1];
        }
        return null;
    }

    // ============================================================
    // UPLOAD
    // ============================================================

    /**
     * Upload file ke Roblox. Balikin ['assetId'=>..] kalau langsung selesai,
     * atau ['operationId'=>..] kalau masih diproses (cek lagi pakai operation()).
     */
    public function upload(string $bytes, string $name, string $creatorType, string $creatorId, string $description = ''): array
    {
        $info = self::detect($bytes);
        if ($info === null) {
            throw new RuntimeException('Format file tidak didukung (hanya PNG/JPG/BMP atau MP3/OGG/WAV/FLAC)');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new RuntimeException('File terlalu besar (maks 20MB)');
        }

        $creator = $creatorType === 'group' ? ['groupId' => $creatorId] : ['userId' => $creatorId];
        $request = [
            'assetType'       => $info['assetType'],
            'displayName'     => self::cleanName($name),
            'description'     => $description !== '' ? mb_substr($description, 0, 1000) : 'Uploaded via ARRR Studio',
            'creationContext' => ['creator' => $creator],
        ];

        $boundary = '----arrr' . bin2hex(random_bytes(8));
        $body = "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"request\"\r\n"
            . "Content-Type: application/json\r\n\r\n"
            . json_encode($request) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"fileContent\"; filename=\"asset.{$info['ext']}\"\r\n"
            . "Content-Type: {$info['mime']}\r\n\r\n"
            . $bytes . "\r\n"
            . "--{$boundary}--\r\n";

        $res = $this->request('POST', self::UPLOAD_URL, [
            'x-api-key: ' . $this->apiKey,
            'Content-Type: multipart/form-data; boundary=' . $boundary,
        ], $body);

        $json = json_decode($res['body'], true) ?: [];
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new RuntimeException('Upload ditolak Roblox (HTTP ' . $res['status'] . '): ' . self::errorMessage($json, $res['body']));
        }

        return self::operationResult($json) + ['kind' => $info['kind']];
    }

    /** Cek status operasi upload */
    public function operation(string $operationId): array
    {
        $res  = $this->request('GET', self::OPERATION_URL . rawurlencode($operationId), ['x-api-key: ' . $this->apiKey]);
        $json = json_decode($res['body'], true) ?: [];
        if ($res['status'] !== 200) {
            throw new RuntimeException('Gagal cek status (HTTP ' . $res['status'] . '): ' . self::errorMessage($json, $res['body']));
        }
        return self::operationResult($json);
    }

    /** Upload lalu tunggu sebentar sampai dapat asset ID */
    public function uploadAndWait(string $bytes, string $name, string $creatorType, string $creatorId, int $maxWaitSec = 12): array
    {
        $result   = $this->upload($bytes, $name, $creatorType, $creatorId);
        $deadline = microtime(true) + $maxWaitSec;

        while (empty($result['assetId']) && !empty($result['operationId']) && microtime(true) < $deadline) {
            usleep(1_500_000);
            $result = $this->operation($result['operationId']) + ['kind' => $result['kind'] ?? null];
        }
        return $result;
    }

    private static function operationResult(array $json): array
    {
        if (!empty($json['error'])) {
            throw new RuntimeException('Upload gagal: ' . self::errorMessage($json['error'], ''));
        }
        $assetId = $json['response']['assetId'] ?? null;
        if (!empty($json['done']) && $assetId) {
            return ['assetId' => (string)$assetId];
        }

        $opId = $json['operationId'] ?? null;
        if (!$opId && !empty($json['path'])) {
            $opId = basename((string)$json['path']);
        }
        if (!$opId) {
            throw new RuntimeException('Respons Roblox tidak dikenali: ' . substr(json_encode($json), 0, 200));
        }
        return ['operationId' => (string)$opId];
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /** Deteksi jenis file dari magic bytes */
    public static function detect(string $bytes): ?array
    {
        foreach (self::SIGNATURES as [$sig, $kind, $assetType, $mime, $ext]) {
            if (str_starts_with($bytes, $sig)) {
                if ($sig === 'RIFF' && substr($bytes, 8, 4) !== 'WAVE') {
                    continue;
                }
                return compact('kind', 'assetType', 'mime', 'ext');
            }
        }
        return null;
    }

    /** Kata "embel-embel" judul YouTube yang dibuang dari nama aset */
    private const TITLE_NOISE = 'official|music\\s*video|video\\s*clip|video|audio|lyrics?|lirik|mv|m\\/v|hd|hq|4k|8k|\\d{3,4}p'
        . '|visuali[sz]er|remaster(?:ed)?|explicit|clean|color\\s*coded|eng\\s*sub|sub\\s*indo|full\\s*song|official\\s*audio';

    /**
     * Nama aset pendek dari judul (mis. judul YouTube):
     * "Artist - Song (Official Music Video) [HD] | Channel" → "Artist - Song"
     * Buang tag [..] / 【..】, (..) yang berisi embel-embel, bagian setelah " | ",
     * hashtag & emoji, lalu potong di batas kata maks $max karakter.
     */
    public static function shortName(string $title, int $max = 30): string
    {
        $max  = max(10, min(50, $max));
        $name = $title;

        // Bagian setelah " | " / " // " biasanya nama channel / keterangan
        $parts = preg_split('#\s+(?:\||//)\s+#u', $name);
        if ($parts && mb_strlen(trim($parts[0])) >= 3) {
            $name = $parts[0];
        }

        $name = preg_replace('/\[[^\]]*\]|【[^】]*】/u', ' ', $name);                                             // tag [HD] 【MV】
        $name = preg_replace('/[「『]([^」』]*)[」』]/u', ' $1 ', $name);                                         // 「judul」 → judul
        $name = preg_replace('/\((?:[^()]*\b(?:' . self::TITLE_NOISE . ')\b[^()]*)\)/iu', ' ', $name);          // (Official Video)
        $name = preg_replace('/#\S+/u', ' ', $name);                                                             // #hashtag
        $name = preg_replace('/(?:\s+[-–—:]?\s*\b(?:' . self::TITLE_NOISE . ')\b)+\s*$/iu', '', $name);         // "... Official Video" di akhir

        // Karakter yang boleh di nama Roblox, rapikan spasi & pemisah di ujung
        $name = preg_replace('/[^\p{L}\p{N} _.\-()]/u', ' ', $name);
        $name = preg_replace('/\(\s*\)/u', ' ', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name), ' -._');

        if (mb_strlen($name) > $max) {
            $cut  = mb_substr($name, 0, $max + 1);
            $pos  = mb_strrpos($cut, ' ');
            $name = ($pos !== false && $pos >= (int)($max * 0.5)) ? mb_substr($cut, 0, $pos) : mb_substr($name, 0, $max);
            $name = trim($name, ' -._(');
            // Kurung buka tanpa tutup gara-gara dipotong
            if (substr_count($name, '(') > substr_count($name, ')')) {
                $name = trim(preg_replace('/\s*\([^)]*$/u', '', $name), ' -._');
            }
        }
        return $name !== '' ? $name : 'Audio';
    }

    private static function cleanName(string $name): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N} _.\-()]/u', '', $name) ?? '');
        return mb_substr($name !== '' ? $name : 'Asset', 0, 50);
    }

    private static function errorMessage($json, string $raw): string
    {
        if (is_array($json)) {
            $msg = $json['message'] ?? $json['errors'][0]['message'] ?? $json['error']['message'] ?? $json['error']['code'] ?? null;
            if ($msg) {
                return (string)$msg;
            }
        }
        return $raw !== '' ? substr(strip_tags($raw), 0, 200) : 'tanpa pesan';
    }

    /** @return array{status:int, body:string} */
    protected function request(string $method, string $url, array $headers = [], ?string $body = null, bool $binary = false): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => '',
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        } elseif ($method !== 'GET') {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS]    = $body ?? '';
        }
        if ($binary) {
            // Stop kalau file lebih besar dari batas
            $opts[CURLOPT_NOPROGRESS]       = false;
            $opts[CURLOPT_PROGRESSFUNCTION] = fn($c, $dlTotal, $dlNow) => $dlNow > self::MAX_BYTES ? 1 : 0;
        }
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Koneksi ke Roblox gagal: ' . $error);
        }
        return ['status' => $status, 'body' => (string)$response];
    }
}
