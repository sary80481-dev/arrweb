<?php
// app/middleware/GuestMiddleware.php — hanya untuk user yang BELUM login

class GuestMiddleware implements Middleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            return true;
        }

        Response::redirect(url('converter'));
        return false;
    }
}
