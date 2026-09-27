<?php
// app/services/generators/ShapeFixer.php — samakan bentuk CSS yang tidak ada padanannya langsung di Roblox
//
//  1. Border CSS ada DI DALAM kotak, UIStroke Roblox digambar DI LUAR
//     → frame ber-stroke di-inset setebal border (ukuran visual tetap sama)
//  2. Border sebagian (border-top saja, border-bottom:none, warna beda per sisi)
//     → garis Frame per sisi, tanpa UIStroke
//  3. Radius sebagian ("70px 70px 0 0" = lengkung atas saja)
//     → container ClipsDescendants + shape yang diperpanjang ke sisi yang lurus

class ShapeFixer
{
    private array $out = [];
    private int $nextId = 0;

    public static function apply(array $nodes): array
    {
        return (new self())->run($nodes);
    }

    private function run(array $nodes): array
    {
        $this->nextId = max(array_column($nodes, 'id') ?: [0]) + 1;

        // Offset yang harus dikurangkan dari posisi anak (karena parent di-inset)
        $childShift = [];

        foreach ($nodes as $n) {
            $pid = $n['parentId'] ?? null;
            if ($pid !== null && isset($childShift[$pid])) {
                $n['x'] -= $childShift[$pid][0];
                $n['y'] -= $childShift[$pid][1];
            }

            $sides   = $n['borderSides'] ?? null;
            $radii   = $n['radiusCorners'] ?? ['TL' => $n['radius'] ?? 0, 'TR' => $n['radius'] ?? 0, 'BR' => $n['radius'] ?? 0, 'BL' => $n['radius'] ?? 0];
            $rotated = abs($n['rotation'] ?? 0) > 0.01;
            $partialRadius = !$rotated ? $this->partialRadius($radii) : null;

            if ($partialRadius !== null && $this->isVisibleBox($n)) {
                $this->clipShape($n, $partialRadius, $childShift);
                continue;
            }

            if ($sides && $this->isPartialBorder($sides)) {
                $this->sideLines($n, $sides);
                continue;
            }

            $this->insetStroke($n, $childShift);
        }

        return $this->out;
    }

    // ============================================================
    // 1. Stroke di dalam kotak
    // ============================================================
    private function insetStroke(array $n, array &$childShift): void
    {
        $bw = (int)round($n['borderW'] ?? 0);
        $hasStroke = $bw > 0 && (($n['borderColor']['a'] ?? 0) > 0.001);
        if (!$hasStroke || $n['w'] <= $bw * 2 || $n['h'] <= $bw * 2) {
            $this->out[] = $n;
            return;
        }

        $n['x'] += $bw;
        $n['y'] += $bw;
        $n['w'] -= $bw * 2;
        $n['h'] -= $bw * 2;
        $n['radius'] = max(0, (int)round(($n['radius'] ?? 0) - $bw));
        // Padding teks diukur dari tepi border-box → kurangi border
        foreach (['padL', 'padR', 'padT', 'padB'] as $p) {
            if (isset($n[$p])) $n[$p] = max(0, $n[$p] - $bw);
        }
        $childShift[$n['id']] = [$bw, $bw];
        $this->out[] = $n;
    }

    // ============================================================
    // 2. Border sebagian → garis per sisi
    // ============================================================
    private function isPartialBorder(array $sides): bool
    {
        $ws = array_map(fn($s) => (int)round($s['w']), $sides);
        if (max($ws) <= 0) return false;
        if (count(array_unique($ws)) > 1) return true;
        $cs = array_map(fn($s) => implode(',', array_map(fn($v) => round($v, 3), $s['c'])), $sides);
        return count(array_unique($cs)) > 1;
    }

    private function sideLines(array $n, array $sides): void
    {
        $n['borderW'] = 0;
        $this->out[] = $n;

        $w = $n['w'];
        $h = $n['h'];
        $geo = [
            'top'    => fn($b) => [0, 0, $w, $b],
            'bottom' => fn($b) => [0, $h - $b, $w, $b],
            'left'   => fn($b) => [0, 0, $b, $h],
            'right'  => fn($b) => [$w - $b, 0, $b, $h],
        ];
        foreach ($sides as $side => $s) {
            $b = (int)round($s['w']);
            if ($b <= 0 || ($s['c']['a'] ?? 0) <= 0.001) continue;
            [$x, $y, $lw, $lh] = $geo[$side]($b);
            $this->out[] = $this->decor($n, 'Border' . ucfirst($side), $x, $y, $lw, $lh, [
                'bg' => $s['c'],
            ]);
        }
    }

    // ============================================================
    // 3. Radius sebagian → clip container + shape diperpanjang
    // ============================================================
    /** Sisi yang lurus: 'bottom' untuk "R R 0 0", dst. Null kalau radius seragam / tidak ada */
    private function partialRadius(array $r): ?array
    {
        $vals = array_map('intval', [$r['TL'] ?? 0, $r['TR'] ?? 0, $r['BR'] ?? 0, $r['BL'] ?? 0]);
        [$tl, $tr, $br, $bl] = $vals;
        if (max($vals) <= 0 || count(array_unique($vals)) === 1) return null;

        if ($tl === $tr && $bl === $br && $tl > 0 && $bl === 0) return ['flat' => 'bottom', 'r' => $tl];
        if ($tl === $tr && $bl === $br && $bl > 0 && $tl === 0) return ['flat' => 'top',    'r' => $bl];
        if ($tl === $bl && $tr === $br && $tl > 0 && $tr === 0) return ['flat' => 'right',  'r' => $tl];
        if ($tl === $bl && $tr === $br && $tr > 0 && $tl === 0) return ['flat' => 'left',   'r' => $tr];
        return null;   // kombinasi lain: pakai radius maksimum (perilaku lama)
    }

    private function isVisibleBox(array $n): bool
    {
        return ($n['bg']['a'] ?? 0) > 0.001 || !empty($n['gradient']) || ($n['borderW'] ?? 0) > 0;
    }

    private function clipShape(array $n, array $pr, array &$childShift): void
    {
        $flat = $pr['flat'];
        $sides = $n['borderSides'] ?? [];
        // Stroke memakai sisi terlebar yang ADA (sisi datar boleh tanpa border — ikut terpotong)
        $bw = 0;
        $bc = $n['borderColor'] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
        foreach ($sides as $side => $s) {
            if ($side !== $flat && $s['w'] > $bw) { $bw = (int)round($s['w']); $bc = $s['c']; }
        }
        if (!$sides) $bw = (int)round($n['borderW'] ?? 0);

        $r = $pr['r'];
        $ext = $r + $bw + 2;   // cukup panjang supaya sudut di sisi datar keluar dari area clip

        // Container: posisi & ukuran asli, transparan, memotong isinya
        $container = $n;
        $container['bg'] = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
        $container['gradient'] = null;
        $container['borderW'] = 0;
        $container['radius'] = 0;
        $container['clips'] = true;
        $this->out[] = $container;

        // Shape: di-inset setebal stroke (stroke Roblox di luar), diperpanjang ke sisi datar
        [$x, $y, $w, $h] = [$bw, $bw, $n['w'] - 2 * $bw, $n['h'] - 2 * $bw];
        match ($flat) {
            'bottom' => $h += $ext,
            'top'    => [$y, $h] = [$y - $ext, $h + $ext],
            'right'  => $w += $ext,
            'left'   => [$x, $w] = [$x - $ext, $w + $ext],
        };
        // UICorner Roblox dibatasi setengah sisi terpendek → pastikan sisi yang diperpanjang ≥ 2×radius
        $innerR = max(0, $r - $bw);
        if (in_array($flat, ['bottom', 'top'], true) && $h < 2 * $innerR + 2) {
            $extra = 2 * $innerR + 2 - $h;
            $h += $extra;
            if ($flat === 'top') $y -= $extra;
        }
        if (in_array($flat, ['left', 'right'], true) && $w < 2 * $innerR + 2) {
            $extra = 2 * $innerR + 2 - $w;
            $w += $extra;
            if ($flat === 'left') $x -= $extra;
        }
        $this->out[] = $this->decor($n, 'Shape', $x, $y, $w, $h, [
            'bg'          => $n['bg'],
            'gradient'    => $n['gradient'] ?? null,
            'borderW'     => $bw,
            'borderColor' => $bc,
            'radius'      => max(0, $r - $bw),
        ]);
    }

    // ============================================================
    // Node dekorasi (Frame tanpa teks) sebagai anak pertama
    // ============================================================
    private function decor(array $parent, string $suffix, $x, $y, $w, $h, array $props): array
    {
        return array_merge([
            'id'           => $this->nextId++,
            'parentId'     => $parent['id'],
            'tag'          => 'div',
            'robloxClass'  => 'Frame',
            'name'         => $parent['name'] . $suffix,
            'explicitName' => '',
            'sourceId'     => '',
            'className'    => '',
            'x' => (int)round($x), 'y' => (int)round($y), 'w' => (int)round($w), 'h' => (int)round($h),
            'visible'      => true,
            'selfHidden'   => false,
            'parentHidden' => false,
            'bg'           => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0],
            'gradient'     => null,
            'fg'           => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 1],
            'borderColor'  => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0],
            'borderW'      => 0,
            'radius'       => 0,
            'opacity'      => $parent['opacity'] ?? 1,
            'rotation'     => 0,
            'text'         => '',
            'richText'     => '',
            'decor'        => true,
            'transition'   => null,
            'isButtonLike' => false,
            'isToggleSwitch' => false,
            'action'       => '',
            'target'       => '',
            'unsupported'  => [],
            'padL' => 0, 'padR' => 0, 'padT' => 0, 'padB' => 0,
            'children'     => [],
        ], $props);
    }
}
