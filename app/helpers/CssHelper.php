<?php
// app/helpers/CssHelper.php — parser nilai CSS (style inline, warna, gradient, transition)

class CssHelper
{
    public static function parseInlineStyle($css) {
        $out = [];
        if (!$css) return $out;
        foreach (explode(';', $css) as $rule) {
            if (strpos($rule, ':') === false) continue;
            $parts = explode(':', $rule, 2);
            $k = trim(strtolower($parts[0]));
            $v = trim($parts[1]);
            if ($k === '') continue;
            $out[$k] = $v;
        }
        return $out;
    }

    public static function parsePx($val) {
        if ($val === null || $val === '') return null;
        if (is_int($val) || is_float($val)) return (int)$val;
        if (preg_match('/^(-?[\d.]+)\s*(px|pt)?$/i', trim($val), $m)) return (int)round((float)$m[1]);
        if (preg_match('/^(-?[\d.]+)\s*em$/i', trim($val), $m)) return (int)round((float)$m[1] * 16);
        return null;
    }

    public static function firstPad($val) {
        if (!$val) return null;
        $parts = preg_split('/\s+/', trim($val));
        return $parts[0] ?? null;
    }

    public static function parseColor($css) {
        if (!$css || $css === 'transparent' || $css === 'none') {
            return ['r'=>0,'g'=>0,'b'=>0,'a'=>0];
        }
        $css = trim($css);

        if (preg_match('/rgba?\(([^)]+)\)/i', $css, $m)) {
            $p = array_map('trim', explode(',', $m[1]));
            if (count($p) === 1 && strpos($p[0], ' ') !== false) $p = preg_split('/\s+/', trim($p[0]));
            return [
                'r' => ((float)($p[0] ?? 0)) / 255,
                'g' => ((float)($p[1] ?? 0)) / 255,
                'b' => ((float)($p[2] ?? 0)) / 255,
                'a' => isset($p[3]) ? (float)$p[3] : 1,
            ];
        }

        if (preg_match('/^#([0-9a-f]{3,8})$/i', $css, $m)) {
            $h = $m[1];
            if (strlen($h) === 3) $h = $h[0].$h[0] . $h[1].$h[1] . $h[2].$h[2];
            if (strlen($h) === 6) {
                return [
                    'r' => hexdec(substr($h, 0, 2)) / 255,
                    'g' => hexdec(substr($h, 2, 2)) / 255,
                    'b' => hexdec(substr($h, 4, 2)) / 255,
                    'a' => 1,
                ];
            }
            if (strlen($h) === 8) {
                return [
                    'r' => hexdec(substr($h, 0, 2)) / 255,
                    'g' => hexdec(substr($h, 2, 2)) / 255,
                    'b' => hexdec(substr($h, 4, 2)) / 255,
                    'a' => hexdec(substr($h, 6, 2)) / 255,
                ];
            }
        }

        if (preg_match('/hsla?\(([^)]+)\)/i', $css, $m)) {
            $p = array_map('trim', explode(',', $m[1]));
            $h = ((float)$p[0]) / 360;
            $s = ((float)($p[1] ?? 0)) / 100;
            $l = ((float)($p[2] ?? 0)) / 100;
            $a = isset($p[3]) ? (float)$p[3] : 1;
            return self::hslToRgb($h, $s, $l, $a);
        }

        $named = [
            'white' => [1,1,1], 'black' => [0,0,0], 'red' => [1,0,0],
            'green' => [0,0.5,0], 'blue' => [0,0,1], 'yellow' => [1,1,0],
            'gray' => [0.5,0.5,0.5], 'grey' => [0.5,0.5,0.5],
        ];
        $lower = strtolower($css);
        if (isset($named[$lower])) {
            [$r, $g, $b] = $named[$lower];
            return ['r'=>$r, 'g'=>$g, 'b'=>$b, 'a'=>1];
        }

        return ['r'=>0, 'g'=>0, 'b'=>0, 'a'=>1];
    }

    public static function hslToRgb($h, $s, $l, $a = 1) {
        $r = $l; $g = $l; $b = $l;
        if ($s > 0) {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hue2rgb($p, $q, $h + 1/3);
            $g = self::hue2rgb($p, $q, $h);
            $b = self::hue2rgb($p, $q, $h - 1/3);
        }
        return ['r'=>$r, 'g'=>$g, 'b'=>$b, 'a'=>$a];
    }
    private static function hue2rgb($p, $q, $t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Parse linear-gradient (inline maupun computed style browser).
     * Mendukung: tanpa arah, `to <side>`, deg/turn/rad, multi-layer (ambil
     * layer linear pertama), repeating-linear-gradient.
     * radial/conic → null (tidak ada padanan di UIGradient; lihat solidFromGradient()).
     */
    public static function parseGradient($bg) {
        if (!$bg) return null;
        $args = self::gradientArgs($bg, 'linear-gradient');
        if ($args === null) return null;

        $parts = self::splitTopLevel($args);
        if (!$parts) return null;

        // Argumen pertama arah? (kalau bukan, berarti langsung color stop)
        $rotCss = 180;
        $head = strtolower(trim($parts[0]));
        if (preg_match('/^(-?[\d.]+)(deg|turn|rad|grad)$/', $head, $d)) {
            $v = (float)$d[1];
            $rotCss = match ($d[2]) {
                'turn'  => $v * 360,
                'rad'   => rad2deg($v),
                'grad'  => $v * 0.9,
                default => $v,
            };
            array_shift($parts);
        } elseif (str_starts_with($head, 'to ')) {
            $dir = preg_replace('/\s+/', ' ', substr($head, 3));
            $map = [
                'top' => 0, 'right' => 90, 'bottom' => 180, 'left' => 270,
                'top right' => 45, 'right top' => 45, 'bottom right' => 135, 'right bottom' => 135,
                'bottom left' => 225, 'left bottom' => 225, 'top left' => 315, 'left top' => 315,
            ];
            $rotCss = $map[$dir] ?? 180;
            array_shift($parts);
        }

        // CSS: 0deg = ke atas, 90deg = ke kanan. UIGradient: 0 = kiri→kanan, 90 = atas→bawah
        $rotRoblox = fmod(fmod($rotCss - 90, 360) + 360, 360);

        $keypoints = [];
        foreach ($parts as $stop) {
            $stop = trim($stop);
            // Color hint (angka saja) diabaikan
            if (preg_match('/^-?[\d.]+(%|px)?$/', $stop)) continue;

            // Warna + 0..2 posisi (mis. "rgb(0, 0, 0) 10% 40%")
            $positions = [];
            while (preg_match('/\s+(-?[\d.]+)(%|px)?\s*$/', $stop, $pm)) {
                array_unshift($positions, ($pm[2] ?? '') === 'px' ? null : ((float)$pm[1]) / (($pm[2] ?? '') === '%' ? 100 : 1));
                $stop = rtrim(substr($stop, 0, -strlen($pm[0])));
            }
            $color = self::parseColor($stop);
            if (!$positions) $positions = [null];
            foreach ($positions as $pos) {
                $keypoints[] = ['color' => $color, 'pos' => $pos];
            }
        }

        $n = count($keypoints);
        if ($n === 0) return null;
        if ($n === 1) {
            $keypoints[] = ['color' => $keypoints[0]['color'], 'pos' => 1];
            $n = 2;
        }

        // Posisi kosong → awal 0, akhir 1, sisanya dibagi rata di antara yang diketahui
        if ($keypoints[0]['pos'] === null) $keypoints[0]['pos'] = 0;
        if ($keypoints[$n - 1]['pos'] === null) $keypoints[$n - 1]['pos'] = 1;
        for ($i = 1; $i < $n - 1; $i++) {
            if ($keypoints[$i]['pos'] !== null) continue;
            $j = $i;
            while ($keypoints[$j]['pos'] === null) $j++;
            $start = $keypoints[$i - 1]['pos'];
            $step  = ($keypoints[$j]['pos'] - $start) / ($j - $i + 1);
            for ($k = $i; $k < $j; $k++) $keypoints[$k]['pos'] = $start + $step * ($k - $i + 1);
        }

        // Posisi harus naik & dalam 0..1 (syarat ColorSequence)
        $prev = 0.0;
        foreach ($keypoints as &$kp) {
            $kp['pos'] = max($prev, min(1, max(0, (float)$kp['pos'])));
            $prev = $kp['pos'];
        }
        unset($kp);

        // ColorSequence wajib mulai di 0 & berakhir di 1, max 20 keypoint
        if ($keypoints[0]['pos'] > 0) array_unshift($keypoints, ['color' => $keypoints[0]['color'], 'pos' => 0]);
        if (end($keypoints)['pos'] < 1) $keypoints[] = ['color' => end($keypoints)['color'], 'pos' => 1];
        $keypoints = array_slice($keypoints, 0, 20);
        $keypoints[count($keypoints) - 1]['pos'] = 1;

        return [
            'rotation' => $rotRoblox,
            'rotationCss' => $rotCss,
            'keypoints' => array_values($keypoints),
        ];
    }

    /**
     * Warna rata-rata dari radial/conic-gradient (fallback karena UIGradient cuma linear)
     */
    public static function solidFromGradient($bg) {
        foreach (['radial-gradient', 'conic-gradient'] as $fn) {
            $args = self::gradientArgs((string)$bg, $fn);
            if ($args === null) continue;

            $colors = [];
            foreach (self::splitTopLevel($args) as $part) {
                if (preg_match('/(rgba?\([^)]*\)|hsla?\([^)]*\)|#[0-9a-f]{3,8}\b)/i', $part, $m)) {
                    $colors[] = self::parseColor($m[1]);
                }
            }
            if (!$colors) return null;

            $avg = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
            foreach ($colors as $c) foreach ($avg as $k => $_) $avg[$k] += $c[$k] / count($colors);
            return $avg;
        }
        return null;
    }

    /** Isi di dalam `<fn>(...)` pertama (termasuk varian repeating-), null kalau tidak ada */
    private static function gradientArgs(string $bg, string $fn): ?string {
        if (!preg_match('/(?:repeating-)?' . preg_quote($fn, '/') . '\s*\(/i', $bg, $m, PREG_OFFSET_CAPTURE)) return null;
        $start = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        for ($i = $start, $len = strlen($bg); $i < $len; $i++) {
            if ($bg[$i] === '(') $depth++;
            elseif ($bg[$i] === ')' && --$depth === 0) return substr($bg, $start, $i - $start);
        }
        return null;
    }

    /** Split string di koma level teratas (koma di dalam rgb(...) diabaikan) */
    private static function splitTopLevel(string $s): array {
        $parts = [];
        $buf = '';
        $depth = 0;
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $c = $s[$i];
            if ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            if ($c === ',' && $depth === 0) {
                $parts[] = trim($buf);
                $buf = '';
            } else {
                $buf .= $c;
            }
        }
        if (trim($buf) !== '') $parts[] = trim($buf);
        return $parts;
    }

    /**
     * overflow: hidden/clip → ClipsDescendants
     */
    public static function clipsContent(array $style): bool {
        foreach (['overflow', 'overflow-x', 'overflow-y'] as $prop) {
            if (in_array($style[$prop] ?? '', ['hidden', 'clip'], true)) return true;
        }
        return false;
    }

    /**
     * Perataan horizontal teks: text-align, atau justify-content kalau elemen flex (row)
     */
    public static function textAlignX(array $style): string {
        $display = $style['display'] ?? '';
        if (str_contains($display, 'flex') && !str_starts_with($style['flex-direction'] ?? 'row', 'column')) {
            $jc = $style['justify-content'] ?? '';
            if ($jc === 'center') return 'center';
            if (in_array($jc, ['flex-end', 'end', 'right'], true)) return 'right';
        }
        $align = strtolower($style['text-align'] ?? 'left');
        if (str_contains($align, 'center')) return 'center';
        if (in_array($align, ['right', 'end', '-webkit-right'], true)) return 'right';
        return 'left';
    }

    /**
     * Perataan vertikal teks: tengah untuk tombol / flex align-items:center / satu baris,
     * atas untuk paragraf multi-baris
     */
    public static function textAlignY(array $style, string $tag, int $h, int $lineHeight, string $text): string {
        $display = $style['display'] ?? '';
        if (str_contains($display, 'flex')) {
            $ai = str_starts_with($style['flex-direction'] ?? 'row', 'column')
                ? ($style['justify-content'] ?? '')
                : ($style['align-items'] ?? '');
            if ($ai === 'center') return 'center';
            if (in_array($ai, ['flex-end', 'end'], true)) return 'bottom';
        }
        if (in_array($tag, ['button', 'input', 'select'], true)) return 'center';
        return self::isMultiline($style, $h, $lineHeight, $text) ? 'top' : 'center';
    }

    /**
     * Teks lebih dari 1 baris di browser? (tinggi konten > ~1.5 line-height atau ada \n)
     */
    public static function isMultiline(array $style, int $h, int $lineHeight, string $text): bool {
        if ($text === '') return false;
        if (str_contains($text, "\n")) return true;
        $padY = (self::parsePx($style['padding-top'] ?? null) ?? 0) + (self::parsePx($style['padding-bottom'] ?? null) ?? 0);
        return ($h - $padY) > max(1, $lineHeight) * 1.5;
    }

    public static function parseTransition($css) {
        if (!$css || $css === 'none') return null;
        if (preg_match('/([\d.]+)(m?s)/', $css, $m)) {
            $sec = (float)$m[1];
            if ($m[2] === 'ms') $sec /= 1000;
            if ($sec <= 0) return null;
            $ease = 'ease';
            if (preg_match('/(cubic-bezier\([^)]+\)|ease-in-out|ease-in|ease-out|linear|ease)/i', $css, $em)) $ease = $em[1];
            return ['duration' => $sec, 'easing' => $ease];
        }
        return null;
    }
}
