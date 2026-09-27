<?php
// app/config/app.php — konfigurasi umum aplikasi

return [
    'name' => 'ARRR Studio',

    // Ukuran canvas default converter (px)
    'canvas' => [
        'width'  => 800,
        'height' => 600,
    ],

    // Batas pemakaian converter untuk guest (belum login)
    'guest' => [
        'max_uses' => 3,
        'cookie'   => 'arrr_guest_uses_c',
    ],

    // Batas ukuran HTML yang boleh dikonversi (byte)
    'max_html_size' => 500_000,

    // YT → MP3 (butuh yt-dlp + ffmpeg di server, lihat README)
    'ytmp3' => [
        'dir'          => STORAGE_PATH . '/ytmp3', // hasil sementara
        'ttl'          => 3600,  // detik sebelum file hasil dihapus
        'max_duration' => 1800,  // durasi video maks (detik)
        'max_playlist' => 50,    // video maks yang diambil dari 1 playlist
        'max_batch'    => 50,    // link maks sekali proses (dicek di JS)
        'timeout'      => 240,   // detik maks per video
        // Tombol "Install otomatis" (download yt-dlp / ffmpeg ke storage/bin).
        // Matikan kalau server publik & tools sudah terpasang.
        'allow_install' => true,
    ],

    // Animation spoof: TTL file .rbxm sementara (detik)
    'anim' => ['ttl' => 3600],

    // Lokasi penyimpanan user
    'users_file' => STORAGE_PATH . '/users.json',
];
