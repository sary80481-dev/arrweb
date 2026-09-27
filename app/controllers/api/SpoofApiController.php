<?php
// app/controllers/api/SpoofApiController.php — Auto Spoof: re-upload aset ke akun/grup sendiri
//
// POST JSON  {action:"reupload", apiKey, creatorType, creatorId, assetId, name?}
// POST JSON  {action:"status",   apiKey, operationId}
// POST JSON  {action:"check",    apiKey, assetId?}   → cek koneksi API key (+ tes download 1 aset)
// POST form  action=upload, apiKey, creatorType, creatorId, name?, file (multipart)
// POST JSON  {action:"grant", apiKey, universeId, assetIds:[...]} → izinkan aset dipakai di game (maks 200)
//            → {granted:[...], failed:{id: alasan}, universeId, placeId?}  (placeId = input ternyata Place ID)
// POST JSON  {action:"ytmp3", apiKey, creatorType, creatorId, token, name?, nameMax?}
//            nama dipendekkan otomatis (RobloxAssetService::shortName, default 30 karakter)
//            → upload langsung hasil YT → MP3 (file sudah di server, tanpa download/upload ulang)
//
// POST JSON  {action:"public", apiKey, assetIds:[...]} → jadikan publik (gambar/decal/mesh saja, bukan audio)
//            → {granted:[...], failed:{id: alasan}}
// POST JSON  {action:"moderation", apiKey, assetIds:[...]} → {states:{id: Reviewing|Approved|Rejected}, errors:{id: pesan}}
//            status review Roblox (maks 25 aset per request), ikut disimpan ke riwayat
// POST JSON  {action:"history"}                     → {items:[...]} riwayat upload (UploadHistory)
// POST JSON  {action:"history_delete", ids:[...]|all:true} → hapus dari riwayat (aset di Roblox tetap ada)
// POST JSON  {action:"anim", apiKey, animId, name?}  → download animation (KeyframeSequence) jadi .rbxm
//            → {token, animId, name, filename, size, kind}
// GET        ?action=anim_download&token=...          → kirim file .rbxm
// GET        ?action=anim_zip&tokens=a,b,c            → kirim .zip berisi banyak .rbxm
//
// Balasan sukses: {assetId} atau {operationId} (belum selesai → JS panggil "status")
// Tiap upload yang berhasil dicatat otomatis di riwayat user.

class SpoofApiController extends ApiController
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $this->error('Login dulu untuk memakai Auto Spoof.', ['login_url' => url('login')], 401);
        }
        $owner = (string)Auth::id();

        // Lepas lock session — JS kirim beberapa upload paralel
        session_write_close();

        $isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
        $input       = $isMultipart ? $_POST : (Request::json() ?? []);
        $action      = (string)($input['action'] ?? $_GET['action'] ?? '');
        $history     = new UploadHistory($owner);

        // Riwayat tidak butuh API key
        try {
            if ($action === 'history') {
                $this->json(['items' => $history->all()]);
            }
            if ($action === 'history_delete') {
                $ids = array_values(array_filter(array_map('strval', (array)($input['ids'] ?? [])), fn($id) => preg_match('/^[a-f0-9]{12}$/', $id)));
                if (!$ids && empty($input['all'])) {
                    $this->error('Pilih riwayat yang mau dihapus');
                }
                $this->json(['deleted' => $history->delete($ids)]);
            }
            if ($action === 'anim_download') {
                $anim = new AnimationSpoofService();
                $f = $anim->find((string)($_GET['token'] ?? ''), $owner);
                $this->sendFile($f['path'], $f['filename'], 'model/x-rbxm');
            }
            if ($action === 'anim_zip') {
                $anim   = new AnimationSpoofService();
                $tokens = array_slice(array_filter(explode(',', (string)($_GET['tokens'] ?? ''))), 0, 200);
                set_time_limit(120);
                $zip = $anim->zip($tokens, $owner);
                $this->sendFile($zip, 'animations-' . date('Ymd-His') . '.zip', 'application/zip', true);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $apiKey = trim((string)($input['apiKey'] ?? ''));
        if ($apiKey === '' || strlen($apiKey) > 2000) {
            $this->error('API key Roblox wajib diisi.');
        }

        set_time_limit(90);
        $service = new RobloxAssetService($apiKey);

        try {
            switch ($action) {
                case 'anim':
                    $animId = trim((string)($input['animId'] ?? ''));
                    if (!preg_match('/^\d{1,20}$/', $animId)) {
                        $this->error('Animation ID tidak valid: ' . $animId);
                    }
                    set_time_limit(60);
                    $name   = trim((string)($input['name'] ?? '')) ?: ('animation_' . $animId);
                    $this->json((new AnimationSpoofService())->download($service, $animId, $name, $owner));

                case 'status':
                    $opId = (string)($input['operationId'] ?? '');
                    if (!preg_match('/^[A-Za-z0-9_-]{1,128}$/', $opId)) {
                        $this->error('operationId tidak valid');
                    }
                    $result = $service->operation($opId);
                    if (!empty($result['assetId'])) {
                        $this->record(fn() => $history->resolveOperation($opId, (string)$result['assetId']));
                    }
                    $this->json($result);

                case 'check':
                    $testId = trim((string)($input['assetId'] ?? ''));
                    if ($testId !== '' && !preg_match('/^\d{1,20}$/', $testId)) {
                        $this->error('Asset ID tes tidak valid');
                    }
                    $this->json($service->check($testId !== '' ? $testId : null));

                case 'reupload':
                    [$creatorType, $creatorId] = $this->creator($input);
                    $assetId = (string)($input['assetId'] ?? '');
                    if (!preg_match('/^\d{1,20}$/', $assetId)) {
                        $this->error('Asset ID tidak valid: ' . $assetId);
                    }
                    $file   = $service->download($assetId);
                    $name   = trim((string)($input['name'] ?? '')) ?: 'Asset ' . $assetId;
                    $result = $service->uploadAndWait($file['bytes'], $name, $creatorType, $creatorId);
                    $this->record(fn() => $history->add($result, [
                        'name' => $name, 'source' => 'reupload', 'ref' => $assetId,
                        'creatorType' => $creatorType, 'creatorId' => $creatorId,
                    ]));
                    $this->json($result + ['sourceId' => $file['sourceId']]);

                case 'grant':
                    $universeId = trim((string)($input['universeId'] ?? ''));
                    if (!preg_match('/^\d{1,20}$/', $universeId)) {
                        $this->error('Universe ID game wajib diisi (angka)');
                    }
                    $ids = array_values(array_unique(array_filter(
                        array_map('strval', (array)($input['assetIds'] ?? [])),
                        fn($id) => preg_match('/^\d{1,20}$/', $id)
                    )));
                    if (!$ids) {
                        $this->error('Tidak ada asset ID yang valid');
                    }
                    if (count($ids) > 200) {
                        $this->error('Maks 200 aset sekali proses');
                    }
                    $granted = $service->grantUniverse($universeId, $ids);
                    $this->record(fn() => $history->markGranted($granted['universeId'], $granted['granted']));
                    $this->json($granted);

                case 'public':
                    $ids = array_values(array_unique(array_filter(
                        array_map('strval', (array)($input['assetIds'] ?? [])),
                        fn($id) => preg_match('/^\d{1,20}$/', $id)
                    )));
                    if (!$ids) {
                        $this->error('Tidak ada asset ID yang valid');
                    }
                    if (count($ids) > 200) {
                        $this->error('Maks 200 aset sekali proses');
                    }
                    $public = $service->grantPublic($ids);
                    $this->record(fn() => $history->markPublic($public['granted']));
                    $this->json($public);

                case 'moderation':
                    $ids = array_values(array_unique(array_filter(
                        array_map('strval', (array)($input['assetIds'] ?? [])),
                        fn($id) => preg_match('/^\d{1,20}$/', $id)
                    )));
                    if (!$ids) {
                        $this->error('Tidak ada asset ID yang valid');
                    }
                    $result = $service->moderation(array_slice($ids, 0, 25));
                    $this->record(fn() => $history->setModeration($result['states']));
                    $this->json($result);

                case 'ytmp3':
                    [$creatorType, $creatorId] = $this->creator($input);
                    $file = (new YoutubeMp3Service())->find((string)($input['token'] ?? ''), $owner);
                    if (filesize($file['path']) > RobloxAssetService::MAX_BYTES) {
                        $this->error('MP3 terlalu besar untuk Roblox (maks 20MB) — pilih bitrate lebih kecil');
                    }
                    $bytes  = (string)file_get_contents($file['path']);
                    $name   = trim((string)($input['name'] ?? '')) ?: pathinfo($file['filename'], PATHINFO_FILENAME);
                    $name   = RobloxAssetService::shortName($name, (int)($input['nameMax'] ?? 30));
                    $result = $service->uploadAndWait($bytes, $name, $creatorType, $creatorId) + ['name' => $name];
                    $this->record(fn() => $history->add($result, [
                        'name' => $name, 'source' => 'ytmp3', 'ref' => (string)($input['videoId'] ?? ''), 'kind' => 'audio',
                        'creatorType' => $creatorType, 'creatorId' => $creatorId,
                    ]));
                    $this->json($result);

                case 'upload':
                    [$creatorType, $creatorId] = $this->creator($input);
                    $upload = $_FILES['file'] ?? null;
                    if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        $this->error('File gagal diterima server (cek upload_max_filesize di php.ini).');
                    }
                    if ($upload['size'] > RobloxAssetService::MAX_BYTES) {
                        $this->error('File terlalu besar (maks 20MB)');
                    }
                    $bytes  = (string)file_get_contents($upload['tmp_name']);
                    $name   = trim((string)($input['name'] ?? '')) ?: pathinfo((string)$upload['name'], PATHINFO_FILENAME);
                    $result = $service->uploadAndWait($bytes, $name, $creatorType, $creatorId);
                    $this->record(fn() => $history->add($result, [
                        'name' => $name, 'source' => 'file', 'ref' => (string)$upload['name'],
                        'creatorType' => $creatorType, 'creatorId' => $creatorId,
                    ]));
                    $this->json($result);

                default:
                    $this->error('Action tidak dikenal');
            }
        } catch (Throwable $e) {
            // Pesan saja — jangan kirim file/line (dan jangan pernah log API key)
            $this->error($e->getMessage());
        }
    }

    /** Catat ke riwayat — gagal mencatat tidak boleh menggagalkan upload */
    private function record(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            error_log('UploadHistory: ' . $e->getMessage());
        }
    }

    /** Kirim file sebagai attachment lalu exit */
    private function sendFile(string $path, string $filename, string $type, bool $deleteAfter = false): void
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

    /** @return array{0:string,1:string} */
    private function creator(array $input): array
    {
        $type = ($input['creatorType'] ?? 'user') === 'group' ? 'group' : 'user';
        $id   = (string)($input['creatorId'] ?? '');
        if (!preg_match('/^\d{1,20}$/', $id)) {
            $this->error('User ID / Group ID kamu wajib diisi (angka).');
        }
        return [$type, $id];
    }
}
