<?php
// app/config/oauth.php — kredensial OAuth diambil dari .env (lihat .env.example)

return [
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://localhost/ArrStudioWeb/index.php?page=auth_google_callback'),
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url'     => 'https://oauth2.googleapis.com/token',
        'user_url'      => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scopes'        => 'openid email profile',
    ],
    'discord' => [
        'client_id'     => env('DISCORD_CLIENT_ID', ''),
        'client_secret' => env('DISCORD_CLIENT_SECRET', ''),
        'redirect_uri'  => env('DISCORD_REDIRECT_URI', 'http://localhost/ArrStudioWeb/index.php?page=auth_discord_callback'),
        'auth_url'      => 'https://discord.com/api/oauth2/authorize',
        'token_url'     => 'https://discord.com/api/oauth2/token',
        'user_url'      => 'https://discord.com/api/users/@me',
        'scopes'        => 'identify email',
    ],
];
