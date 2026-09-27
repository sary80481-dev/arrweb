<?php
// app/controllers/SpooferController.php — halaman Auto Spoof (logic: assets/js/pages/spoofer.js)

class SpooferController extends Controller
{
    public function index(): void
    {
        $this->render('spoofer/index', [
            'pageTitle'  => 'ARRR Studio — Auto Spoof',
            'activePage' => 'spoofer',
            'styles'     => ['library', 'spoofer'],
        ]);
    }
}
