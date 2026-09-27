<?php
// app/core/Env.php — loader file .env sederhana (KEY=VALUE per baris)

class Env
{
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        // Buang BOM (Notepad Windows) + dukung CRLF
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($file));

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'export ')) $line = substr($line, 7);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$values[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
