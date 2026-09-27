<?php
// app/core/Router.php — routing ?page=... ke Controller@method

class Router
{
    /** Alias middleware yang bisa dipakai di routes.php */
    private const MIDDLEWARE = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
    ];

    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[$method][$path] = [
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        // ?page=..., default 'landing'. '/' dinormalisasi jadi '_'
        $page   = preg_replace('/[^a-zA-Z0-9_\/-]/', '', (string)($_GET['page'] ?? 'landing'));
        $lookup = str_replace('/', '_', $page ?: 'landing');
        $route  = $this->routes[Request::method()][$lookup] ?? null;

        if ($route === null) {
            $this->render404();
            return;
        }

        foreach ($route['middleware'] as $alias) {
            $class = self::MIDDLEWARE[$alias] ?? null;
            if ($class !== null && !(new $class())->handle()) {
                return;
            }
        }

        [$controllerName, $methodName] = explode('@', $route['handler']);

        if (!class_exists($controllerName) || !method_exists($controllerName, $methodName)) {
            $this->render404();
            return;
        }

        (new $controllerName())->$methodName();
    }

    private function render404(): void
    {
        Response::status(404);
        View::render('errors/404', [
            'pageTitle'  => '404 — Halaman tidak ditemukan',
            'activePage' => '',
            'navVariant' => 'app',
            'styles'     => ['errors'],
        ]);
    }
}
