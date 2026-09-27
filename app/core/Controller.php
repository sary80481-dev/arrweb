<?php
// app/core/Controller.php — base controller

abstract class Controller
{
    /**
     * Render view di dalam layout master.
     *
     * Data umum layout:
     *  - pageTitle  : <title>
     *  - activePage : id menu navbar yang aktif
     *  - navVariant : 'app' (pakai navbar) | 'landing' (tanpa navbar)
     *  - styles     : CSS khusus halaman, nama file di assets/css/pages/ (tanpa .css)
     */
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data + [
            'pageTitle'  => config('app.name'),
            'activePage' => '',
            'navVariant' => 'app',
            'styles'     => [],
        ]);
    }

    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function input(string $key, $default = null)
    {
        return Request::input($key, $default);
    }

    protected function abort404(): void
    {
        Response::status(404);
        $this->render('errors/404', ['pageTitle' => '404 — Halaman tidak ditemukan', 'styles' => ['errors']]);
        exit;
    }
}
