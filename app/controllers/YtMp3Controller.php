<?php
// app/controllers/YtMp3Controller.php — halaman YT → MP3 massal (logic: assets/js/pages/ytmp3.js)

class YtMp3Controller extends Controller
{
    public function index(): void
    {
        $this->render('ytmp3/index', [
            'pageTitle'  => 'ARRR Studio — YT → MP3',
            'activePage' => 'ytmp3',
            'styles'     => ['library', 'spoofer', 'ytmp3'],
        ]);
    }
}
