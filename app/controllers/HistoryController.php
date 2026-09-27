<?php
// app/controllers/HistoryController.php — halaman Riwayat Upload (logic: assets/js/pages/history.js)

class HistoryController extends Controller
{
    public function index(): void
    {
        $this->render('history/index', [
            'pageTitle'  => 'ARRR Studio — Riwayat Upload',
            'activePage' => 'history',
            'styles'     => ['library', 'spoofer', 'history'],
        ]);
    }
}
