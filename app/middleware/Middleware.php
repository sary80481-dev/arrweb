<?php
// app/middleware/Middleware.php — kontrak middleware route

interface Middleware
{
    /**
     * Return false (atau redirect + exit) untuk menghentikan request
     */
    public function handle(): bool;
}
