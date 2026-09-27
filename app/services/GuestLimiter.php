<?php
// app/services/GuestLimiter.php — kuota converter untuk guest (disimpan di cookie)

class GuestLimiter
{
    private int    $max;
    private string $cookie;

    public function __construct()
    {
        $this->max    = (int)config('app.guest.max_uses', 3);
        $this->cookie = (string)config('app.guest.cookie', 'arrr_guest_uses_c');
    }

    public function max(): int
    {
        return $this->max;
    }

    public function uses(): int
    {
        return (int)($_COOKIE[$this->cookie] ?? 0);
    }

    public function remaining(): int
    {
        return max(0, $this->max - $this->uses());
    }

    public function exceeded(): bool
    {
        return $this->uses() >= $this->max;
    }

    public function increment(): int
    {
        $next = $this->uses() + 1;
        // Valid 1 tahun. httponly=false biar JS (guest-limit.js) bisa baca juga
        setcookie($this->cookie, (string)$next, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$this->cookie] = (string)$next;
        return $next;
    }

    public function info(): array
    {
        return [
            'uses'      => $this->uses(),
            'remaining' => $this->remaining(),
            'max'       => $this->max,
        ];
    }
}
