<?php
// app/services/auth/OAuthService.php — alur OAuth2 (Google & Discord)

class OAuthService
{
    private string $provider;
    private array  $cfg;

    public function __construct(string $provider)
    {
        $cfg = config('oauth.' . $provider);
        if (!is_array($cfg)) {
            throw new InvalidArgumentException("Provider OAuth tidak dikenal: {$provider}");
        }
        $this->provider = $provider;
        $this->cfg      = $cfg;
    }

    /**
     * URL authorize provider (+ simpan state anti-CSRF di session)
     */
    /** Kredensial sudah diisi di .env? */
    public function isConfigured(): bool
    {
        return trim((string)$this->cfg['client_id']) !== '' && trim((string)$this->cfg['client_secret']) !== '';
    }

    public function authorizeUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $this->cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'],
            'state'         => $state,
        ];

        if ($this->provider === 'google') {
            $params['access_type'] = 'online';
            $params['prompt']      = 'select_account';
        }

        return $this->cfg['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Proses callback → profil user ['id','email','name','avatar_url'].
     * Lempar RuntimeException dengan pesan yang bisa ditampilkan ke user.
     */
    public function handleCallback(array $query): array
    {
        $expected = $_SESSION['oauth_state'] ?? '';
        unset($_SESSION['oauth_state']);

        if (empty($query['state']) || !hash_equals((string)$expected, (string)$query['state'])) {
            throw new RuntimeException('Invalid state parameter (CSRF).');
        }
        if (empty($query['code'])) {
            throw new RuntimeException('Authorization dibatalkan.');
        }

        $token = $this->exchangeCode((string)$query['code']);
        if (empty($token['access_token'])) {
            throw new RuntimeException('Gagal ambil access token ' . ucfirst($this->provider) . '.');
        }

        return $this->provider === 'google'
            ? $this->googleProfile($token['access_token'])
            : $this->discordProfile($token['access_token']);
    }

    private function exchangeCode(string $code): array
    {
        $cfg = $this->cfg;

        if ($this->provider === 'discord') {
            // Discord: client credential via Basic Auth
            return HttpClient::postForm($cfg['token_url'], [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $cfg['redirect_uri'],
            ], [
                'Authorization: Basic ' . base64_encode($cfg['client_id'] . ':' . $cfg['client_secret']),
            ]);
        }

        return HttpClient::postForm($cfg['token_url'], [
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);
    }

    private function googleProfile(string $accessToken): array
    {
        $user = HttpClient::getJson($this->cfg['user_url'], ['Authorization: Bearer ' . $accessToken]);
        if (empty($user['id'])) {
            throw new RuntimeException('Gagal ambil profil Google.');
        }

        return [
            'id'         => $user['id'],
            'email'      => $user['email'] ?? '',
            'name'       => $user['name'] ?? ($user['email'] ?? 'User'),
            'avatar_url' => $user['picture'] ?? '',
        ];
    }

    private function discordProfile(string $accessToken): array
    {
        $user = HttpClient::getJson($this->cfg['user_url'], ['Authorization: Bearer ' . $accessToken]);
        if (empty($user['id'])) {
            throw new RuntimeException('Gagal ambil profil Discord.');
        }

        $avatar = empty($user['avatar'])
            ? ''
            : 'https://cdn.discordapp.com/avatars/' . $user['id'] . '/' . $user['avatar'] . '.png';

        return [
            'id'         => $user['id'],
            'email'      => $user['email'] ?? '',
            'name'       => $user['global_name'] ?? $user['username'] ?? 'User',
            'avatar_url' => $avatar,
        ];
    }
}
