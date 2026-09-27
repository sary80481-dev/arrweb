<?php
// app/services/auth/Auth.php — session login (OAuth) untuk user

class Auth
{
    private const SESSION_KEY = 'user_id';

    /**
     * Start session dengan config aman + kompatibel OAuth redirect (idempotent)
     */
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => false, // true kalau sudah HTTPS
                'httponly' => true,
                'samesite' => 'Lax',  // penting untuk OAuth redirect
            ]);
            ini_set('session.use_only_cookies', '1');
        }
        session_start();
    }

    /**
     * Login / register via OAuth provider.
     * $profile: id, email, name, avatar_url
     */
    public static function loginOAuth(string $provider, array $profile): array
    {
        self::startSession();

        $providerId = trim((string)($profile['id'] ?? ''));
        $email      = trim((string)($profile['email'] ?? ''));
        $name       = trim((string)($profile['name'] ?? $profile['username'] ?? '')) ?: 'User';
        $avatar     = trim((string)($profile['avatar_url'] ?? $profile['picture'] ?? ''));

        if ($providerId === '') {
            return ['ok' => false, 'error' => 'Provider ID kosong'];
        }

        $repo  = new UserRepository();
        $users = $repo->all();
        $index = $repo->findIndex($users, $provider, $providerId, $email);
        $now   = date('c');

        if ($index >= 0) {
            // === UPDATE USER EXISTING ===
            $user = &$users[$index];
            $user['provider']    = $provider;
            $user['provider_id'] = $providerId;
            $user['email']       = $email ?: ($user['email'] ?? '');
            $user['name']        = $name;
            $user['avatar']      = $avatar ?: ($user['avatar'] ?? '');
            $user['last_login']  = $now;
            unset($user);

            $userId   = $users[$index]['id'];
            $username = $users[$index]['username'];
        } else {
            // === REGISTER USER BARU ===
            $userId   = uniqid('u_', true);
            $username = $repo->uniqueUsername($name, $users);

            $users[] = [
                'id'          => $userId,
                'username'    => $username,
                'email'       => $email,
                'provider'    => $provider,
                'provider_id' => $providerId,
                'name'        => $name,
                'avatar'      => $avatar,
                'created_at'  => $now,
                'last_login'  => $now,
            ];
        }

        if (!$repo->save($users)) {
            return ['ok' => false, 'error' => 'Gagal menyimpan data user.'];
        }

        // Anti session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_KEY] = $userId;
        $_SESSION['username']        = $username;
        $_SESSION['provider']        = $provider;
        $_SESSION['logged_in_at']    = time();

        return ['ok' => true, 'user_id' => $userId, 'username' => $username];
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $user = (new UserRepository())->find($_SESSION[self::SESSION_KEY]);
        if ($user === null) {
            // Session ada tapi user udah dihapus dari storage
            self::logout();
        }
        return $user;
    }

    public static function id(): ?string
    {
        self::startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function username(): string
    {
        self::startSession();
        return $_SESSION['username'] ?? 'Guest';
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'] ?: '/',
                'domain'   => $p['domain'] ?: '',
                'secure'   => (bool)($p['secure'] ?? false),
                'httponly' => (bool)($p['httponly'] ?? true),
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
