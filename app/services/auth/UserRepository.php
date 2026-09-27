<?php
// app/services/auth/UserRepository.php — penyimpanan user di file JSON

class UserRepository
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? config('app.users_file');
    }

    public function all(): array
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $raw = @file_get_contents($this->file);
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            // Backup file corrupt, biar ga ilang total
            @copy($this->file, $this->file . '.corrupt.' . time());
            return [];
        }

        return $data;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $u) {
            if (($u['id'] ?? '') === $id) {
                return $u;
            }
        }
        return null;
    }

    /**
     * Cari index user: prioritas provider + provider_id, fallback email
     */
    public function findIndex(array $users, string $provider, string $providerId, string $email): int
    {
        foreach ($users as $i => $u) {
            if (($u['provider'] ?? '') === $provider
                && (string)($u['provider_id'] ?? '') === $providerId) {
                return $i;
            }
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            foreach ($users as $i => $u) {
                if (!empty($u['email']) && strtolower($u['email']) === strtolower($email)) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /**
     * Simpan semua user (atomic write: temp file → rename)
     */
    public function save(array $users): bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tmp = $this->file . '.tmp.' . getmypid();
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }

        if (!@rename($tmp, $this->file)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }

    /**
     * Buat username unik dari nama (alfanumerik + _, max 20 char)
     */
    public function uniqueUsername(string $base, array $users): string
    {
        $base = substr(preg_replace('/[^a-zA-Z0-9_]/', '', $base), 0, 20) ?: 'user';

        $taken = array_map(fn($u) => strtolower($u['username'] ?? ''), $users);
        $username = $base;
        for ($i = 1; in_array(strtolower($username), $taken, true); $i++) {
            $username = $base . $i;
        }
        return $username;
    }
}
