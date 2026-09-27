<?php
// app/controllers/AuthController.php — login page, OAuth (Google/Discord), logout

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $error = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);

        $this->render('auth/login', [
            'pageTitle'  => 'Login — ARRR Studio',
            'activePage' => 'login',
            'navVariant' => 'landing',
            'styles'     => ['auth'],
            'error'      => $error,
        ]);
    }

    public function googleRedirect(): void
    {
        $this->redirectToProvider('google');
    }

    public function googleCallback(): void
    {
        $this->handleCallback('google');
    }

    public function discordRedirect(): void
    {
        $this->redirectToProvider('discord');
    }

    public function discordCallback(): void
    {
        $this->handleCallback('discord');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('landing'));
    }

    private function redirectToProvider(string $provider): void
    {
        $oauth = new OAuthService($provider);
        if (!$oauth->isConfigured()) {
            $key = strtoupper($provider);
            $this->failLogin("Login {$provider} belum dikonfigurasi: isi {$key}_CLIENT_ID dan {$key}_CLIENT_SECRET di file .env (root project).");
        }
        $this->redirect($oauth->authorizeUrl());
    }

    /**
     * Callback OAuth → login → balik ke halaman tujuan
     */
    private function handleCallback(string $provider): void
    {
        try {
            $profile = (new OAuthService($provider))->handleCallback($_GET);
        } catch (RuntimeException $e) {
            $this->failLogin($e->getMessage());
        }

        $result = Auth::loginOAuth($provider, $profile);
        if (!$result['ok']) {
            $this->failLogin($result['error']);
        }

        $intended = $_SESSION['intended_url'] ?? url('converter');
        unset($_SESSION['intended_url']);
        $this->redirect($intended);
    }

    private function failLogin(string $message): void
    {
        $_SESSION['auth_error'] = $message;
        $this->redirect(url('login'));
    }
}
