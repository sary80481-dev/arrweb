<?php
// app/services/generators/LayoutDetector.php — flex/grid HTML → UIListLayout / UIGridLayout
//
// Layout HANYA dipasang kalau posisi anak hasil render browser bisa direproduksi
// persis oleh layout Roblox (toleransi 1px). Kalau tidak, posisi absolut dipertahankan
// supaya tampilan tidak bergeser.

class LayoutDetector
{
    private const TOL = 1.5;

    /**
     * @param array $container node container
     * @param array $kids      node anak langsung (urutan DOM)
     * @return array|null ['type' => 'list'|'grid', ...]
     */
    public static function detect(array $container, array $kids): ?array
    {
        if (count($kids) < 2) return null;
        if (!in_array($container['robloxClass'] ?? '', ['Frame', 'ScrollingFrame', 'TextButton', 'ImageButton'], true)) return null;
        // Anak tersembunyi / teks container → layout bisa menggeser, lewati
        foreach ($kids as $k) {
            if (!empty($k['selfHidden'])) return null;
        }
        if (trim($container['text'] ?? '') !== '') return null;

        return self::grid($container, $kids) ?? self::list($container, $kids);
    }

    // ============================================================
    // UIListLayout
    // ============================================================
    private static function list(array $c, array $kids): ?array
    {
        foreach (['Vertical', 'Horizontal'] as $dir) {
            [$main, $cross, $size, $crossSize] = $dir === 'Vertical' ? ['y', 'x', 'h', 'w'] : ['x', 'y', 'w', 'h'];

            // Urutan sepanjang sumbu utama harus = urutan DOM, dan tidak tumpang tindih
            $gaps = [];
            for ($i = 1; $i < count($kids); $i++) {
                $gap = $kids[$i][$main] - ($kids[$i - 1][$main] + $kids[$i - 1][$size]);
                if ($gap < -self::TOL) continue 2;
                $gaps[] = $gap;
            }
            if (max($gaps) - min($gaps) > self::TOL) continue;
            $gap = (int)round(array_sum($gaps) / count($gaps));

            // Perataan sumbu silang: start / center / end
            $crossLen = $c[$crossSize];
            $align = self::crossAlign($kids, $cross, $crossSize, $crossLen);
            if ($align === null) continue;

            $pad = self::padding($c);
            $startMain = $kids[0][$main];
            $mainPadKey = $dir === 'Vertical' ? 'T' : 'L';
            $crossPadKey = $dir === 'Vertical' ? 'L' : 'T';

            $padding = ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0];
            $padding[$mainPadKey] = $startMain;
            if ($align['mode'] === 'start') $padding[$crossPadKey] = $align['offset'];
            if ($align['mode'] === 'end')   $padding[$dir === 'Vertical' ? 'R' : 'B'] = $align['offset'];

            return [
                'type'      => 'list',
                'direction' => $dir,
                'gap'       => max(0, $gap),
                'hAlign'    => $dir === 'Vertical' ? self::alignName($align['mode'], 'h') : 'Left',
                'vAlign'    => $dir === 'Horizontal' ? self::alignName($align['mode'], 'v') : 'Top',
                'padding'   => $padding,
            ];
        }
        return null;
    }

    // ============================================================
    // UIGridLayout (sel seukuran, diisi baris demi baris)
    // ============================================================
    private static function grid(array $c, array $kids): ?array
    {
        if (count($kids) < 4) return null;
        $w = $kids[0]['w'];
        $h = $kids[0]['h'];
        foreach ($kids as $k) {
            if (abs($k['w'] - $w) > self::TOL || abs($k['h'] - $h) > self::TOL) return null;
        }

        $xs = array_values(array_unique(array_map(fn($k) => (int)round($k['x']), $kids)));
        $ys = array_values(array_unique(array_map(fn($k) => (int)round($k['y']), $kids)));
        sort($xs);
        sort($ys);
        if (count($xs) < 2 || count($ys) < 2) return null;   // 1 baris/kolom → list

        $gapX = $xs[1] - $xs[0] - $w;
        $gapY = $ys[1] - $ys[0] - $h;
        $cols = count($xs);

        // Setiap anak harus berada di sel ke-i (row-major) persis
        foreach ($kids as $i => $k) {
            $ex = $xs[0] + ($i % $cols) * ($w + $gapX);
            $ey = $ys[0] + intdiv($i, $cols) * ($h + $gapY);
            if (abs($k['x'] - $ex) > self::TOL || abs($k['y'] - $ey) > self::TOL) return null;
        }

        return [
            'type'    => 'grid',
            'cell'    => [(int)round($w), (int)round($h)],
            'gap'     => [max(0, (int)round($gapX)), max(0, (int)round($gapY))],
            'maxCols' => $cols,
            'padding' => ['L' => $xs[0], 'T' => $ys[0], 'R' => 0, 'B' => 0],
        ];
    }

    private static function crossAlign(array $kids, string $pos, string $size, float $containerLen): ?array
    {
        $starts  = array_map(fn($k) => $k[$pos], $kids);
        $centers = array_map(fn($k) => $k[$pos] + $k[$size] / 2, $kids);
        $ends    = array_map(fn($k) => $containerLen - ($k[$pos] + $k[$size]), $kids);

        if (max($starts) - min($starts) <= self::TOL)   return ['mode' => 'start', 'offset' => (int)round(min($starts))];
        if (max($centers) - min($centers) <= self::TOL && abs($centers[0] - $containerLen / 2) <= self::TOL) {
            return ['mode' => 'center', 'offset' => 0];
        }
        if (max($ends) - min($ends) <= self::TOL)       return ['mode' => 'end', 'offset' => (int)round(min($ends))];
        return null;
    }

    private static function alignName(string $mode, string $axis): string
    {
        return match ($mode) {
            'center' => 'Center',
            'end'    => $axis === 'h' ? 'Right' : 'Bottom',
            default  => $axis === 'h' ? 'Left' : 'Top',
        };
    }

    private static function padding(array $c): array
    {
        return ['L' => (int)($c['padL'] ?? 0), 'T' => (int)($c['padT'] ?? 0)];
    }
}
