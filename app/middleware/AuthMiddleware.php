<?php
// app/middleware/AuthMiddleware.php — hanya untuk user yang sudah login

class AuthMiddleware implements Middleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            return true;
        }

        // Simpan URL yang diminta, balik ke sana setelah login
        $_SESSION['intended_url'] = url($_GET['page'] ?? 'converter');
        Response::redirect(url('login'));
        return false;
    }
}
