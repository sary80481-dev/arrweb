<?php
// app/bootstrap.php — bootstrap untuk semua entry point (index.php & api/*.php)

define('BASE_PATH',    dirname(__DIR__));
define('APP_PATH',     BASE_PATH . '/app');
define('VIEW_PATH',    APP_PATH . '/views');
define('CONFIG_PATH',  APP_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Deteksi base URL (/ArrStudioWeb atau '') — selalu relatif ke root project,
// jadi tetap benar walau dipanggil dari api/*.php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (basename($scriptDir) === 'api') {
    $scriptDir = dirname($scriptDir);
}
define('BASE_URL', rtrim($scriptDir, '/'));

// ============================================================
// AUTOLOAD — cari class di folder-folder app/ (termasuk subfolder)
// ============================================================
spl_autoload_register(function (string $class): void {
    static $map = null;

    if ($map === null) {
        $map  = [];
        $dirs = ['core', 'helpers', 'middleware', 'services', 'controllers'];
        foreach ($dirs as $dir) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(APP_PATH . '/' . $dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->getExtension() === 'php') {
                    $map[$file->getBasename('.php')] ??= $file->getPathname();
                }
            }
        }
    }

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

// Helper global (url, asset, e, ...)
require_once APP_PATH . '/helpers/functions.php';

// .env (opsional) → getenv()/env()
Env::load(BASE_PATH . '/.env');

// ============================================================
// SESSION
// ============================================================
Auth::startSession();
