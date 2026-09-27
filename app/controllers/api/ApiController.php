<?php
// app/controllers/api/ApiController.php — base untuk endpoint JSON

abstract class ApiController extends Controller
{
    public function __construct()
    {
        // Error PHP jangan bocor jadi HTML — selalu balas JSON
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
        ob_start();

        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode([
                    'error' => 'PHP Fatal: ' . $err['message'],
                    'file'  => $err['file'],
                    'line'  => $err['line'],
                ]);
            }
        });
    }

    abstract public function handle(): void;

    /**
     * Balas error (status 200 biar JS bisa baca field "error")
     */
    protected function error(string $message, array $extra = [], int $status = 200): void
    {
        $this->json(['error' => $message] + $extra, $status);
    }

    protected function exceptionResponse(Throwable $e): void
    {
        $this->json([
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }
}
