<?php
// app/core/View.php — render page (views/pages/) di dalam layout + komponen

class View
{
    /** Data yang di-share ke semua view/komponen dalam 1 request */
    private static array $shared = [];

    /**
     * Render halaman views/pages/{$page}.php di dalam layouts/master
     */
    public static function render(string $page, array $data = []): void
    {
        self::$shared = $data;

        $content = self::capture('pages/' . $page, $data);
        self::partial('layouts/master', ['content' => $content]);
    }

    /**
     * Render file view ke string
     */
    public static function capture(string $view, array $data = []): string
    {
        ob_start();
        self::partial($view, $data);
        return ob_get_clean();
    }

    /**
     * Render file views/{$view}.php — mewarisi data shared, data lokal menang
     */
    public static function partial(string $view, array $data = []): void
    {
        extract(array_merge(self::$shared, $data), EXTR_OVERWRITE);
        require VIEW_PATH . "/{$view}.php";
    }

    /**
     * Render komponen reusable: views/components/{$name}.php
     */
    public static function component(string $name, array $data = []): void
    {
        self::partial('components/' . $name, $data);
    }

    /**
     * CSS inline dengan penanda data-spa-style (dipakai spa.js untuk swap
     * style per halaman tanpa flash). File: assets/css/{$file}.css
     */
    public static function style(string $id, string $file): void
    {
        $path = BASE_PATH . '/assets/css/' . $file . '.css';
        if (!is_file($path)) {
            return;
        }
        echo '<style data-spa-style="' . e($id) . '">' . "\n" . file_get_contents($path) . "</style>\n";
    }
}
