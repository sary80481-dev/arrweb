<?php
// app/services/media/ProcessRunner.php — jalankan binary (yt-dlp / ffmpeg) tanpa shell, dengan timeout
//
// stdout/stderr ditulis ke file sementara (bukan pipe): pipe non-blocking tidak didukung
// di Windows dan bisa macet kalau output-nya besar. Jalan sama di Windows, Linux, macOS.

class ProcessRunner
{
    /**
     * @return array{code:int, out:string, err:string}
     * @throws RuntimeException kalau binary tidak bisa dijalankan / timeout
     */
    public static function run(array $cmd, int $timeout, string $label): array
    {
        $outFile = tempnam(sys_get_temp_dir(), 'arrr');
        $errFile = tempnam(sys_get_temp_dir(), 'arrr');

        $proc = @proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['file', $outFile, 'w'],
            2 => ['file', $errFile, 'w'],
        ], $pipes, null, null, ['bypass_shell' => true]);

        if (!is_resource($proc)) {
            @unlink($outFile);
            @unlink($errFile);
            throw new RuntimeException(self::missingMessage($label));
        }
        fclose($pipes[0]);

        $deadline = microtime(true) + $timeout;
        $code     = -1;
        while (true) {
            $status = proc_get_status($proc);
            if (!$status['running']) {
                $code = (int)$status['exitcode'];
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($proc, 9);
                proc_close($proc);
                @unlink($outFile);
                @unlink($errFile);
                throw new RuntimeException('Timeout — ' . $label . ' terlalu lama');
            }
            usleep(100_000);
        }
        proc_close($proc);

        $out = (string)@file_get_contents($outFile);
        $err = (string)@file_get_contents($errFile);
        @unlink($outFile);
        @unlink($errFile);

        // 127 = "command not found" (Linux/macOS), 9009 = cmd.exe versi Windows
        if ($code === 127 || $code === 9009) {
            throw new RuntimeException(self::missingMessage($label));
        }
        return ['code' => $code, 'out' => $out, 'err' => $err];
    }

    private static function missingMessage(string $label): string
    {
        return $label . ' belum terpasang — klik "Install otomatis" di panel Tools halaman YT → MP3';
    }
}
