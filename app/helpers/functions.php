<?php
// app/helpers/functions.php — helper global (dipakai di controller & view)

/**
 * Ambil nilai dari .env / environment
 */
function env(string $key, $default = null)
{
    return Env::get($key, $default);
}

/**
 * Ambil config: config('app.guest.max_uses'), config('oauth.discord')
 */
function config(string $key, $default = null)
{
    static $cache = [];

    $parts = explode('.', $key);
    $file  = array_shift($parts);

    if (!array_key_exists($file, $cache)) {
        $path = CONFIG_PATH . '/' . $file . '.php';
        $cache[$file] = is_file($path) ? require $path : [];
    }

    $value = $cache[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/**
 * Deteksi apakah pretty URL (.htaccess rewrite) aktif —
 * kalau REQUEST_URI nggak mengandung 'index.php', anggap aktif
 */
function pretty_urls_enabled(): bool
{
    static $enabled = null;
    if ($enabled === null) {
        $enabled = strpos($_SERVER['REQUEST_URI'] ?? '', 'index.php') === false;
    }
    return $enabled;
}

/**
 * Build URL halaman — otomatis pakai pretty URL kalau aktif
 */
function url(string $page = 'converter', array $params = []): string
{
    if (pretty_urls_enabled()) {
        $base = BASE_URL . '/' . ltrim($page, '/');
        if (!empty($params)) {
            $base .= '?' . http_build_query($params);
        }
        return $base;
    }
    $params = array_merge(['page' => $page], $params);
    return BASE_URL . '/index.php?' . http_build_query($params);
}

/**
 * URL ke file di assets/
 */
function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * URL asset + cache-busting (?v=filemtime) — perubahan file langsung kepake
 */
function asset_v(string $path): string
{
    $full = BASE_PATH . '/assets/' . ltrim($path, '/');
    $v    = @filemtime($full) ?: time();
    return asset($path) . '?v=' . $v;
}

/**
 * Escape HTML
 */
function e($str): string
{
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Class 'active' untuk menu
 */
function is_active(string $page, string $current): string
{
    return $page === $current ? 'active' : '';
}
