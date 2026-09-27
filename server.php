<?php
// server.php — router untuk PHP built-in server (development), meniru .htaccess
// Jalankan: php -S localhost:8000 server.php

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Folder internal tidak boleh diakses langsung
if (preg_match('#^/(app|storage)(/|$)#', $path)) {
    http_response_code(403);
    exit;
}

// File statis / endpoint api/*.php → layani apa adanya
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

// Pretty URL: /docs → index.php?page=docs
if (preg_match('#^/([a-zA-Z0-9_-]+)/?$#', $path, $m)) {
    $_GET['page'] = $m[1];
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
