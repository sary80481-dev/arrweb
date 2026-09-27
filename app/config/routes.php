<?php
// app/config/routes.php — daftar route (?page=... atau /pretty-url)
// Middleware: 'auth' (wajib login), 'guest' (wajib belum login)

// ============================================================
// HALAMAN — bisa diakses guest (kecuali yang pakai 'auth')
// ============================================================
$router->get('landing',   'LandingController@index');
$router->get('converter', 'ConverterController@index');
$router->get('library',   'LibraryController@index');
$router->get('docs',      'DocsController@index');
$router->get('prompt',    'PromptController@index');
$router->get('spoofer',   'SpooferController@index', ['auth']);
$router->get('ytmp3',     'YtMp3Controller@index',   ['auth']);
$router->get('history',   'HistoryController@index', ['auth']);
$router->get('luaobf',    'LuaObfController@index',   ['auth']);

// ============================================================
// AUTH
// ============================================================
$router->get('login',  'AuthController@loginForm', ['guest']);
$router->get('logout', 'AuthController@logout');

$router->get('auth_google',           'AuthController@googleRedirect');
$router->get('auth_google_callback',  'AuthController@googleCallback');
$router->get('auth_discord',          'AuthController@discordRedirect');
$router->get('auth_discord_callback', 'AuthController@discordCallback');

// ============================================================
// API (JSON) — juga bisa lewat api/convert.php & api/generate.php
// ============================================================
$router->post('api_convert',  'ConvertApiController@handle');
$router->post('api_generate', 'GenerateApiController@handle');
$router->post('api_spoof',    'SpoofApiController@handle');
$router->post('api_ytmp3',    'YtMp3ApiController@handle');
