<?php
// app/services/generators/NodeStyle.php — hitung properti visual Roblox dari 1 node
// (dipakai bersama oleh FullScriptGenerator & RbxmxGenerator supaya hasilnya sama)

class NodeStyle
{
    public const TEXT_CLASSES = ['TextLabel', 'TextButton', 'TextBox'];

    /**
     * @return array{
     *   bgColor: array, bgTransparency: float, gradient: ?array,
     *   stroke: ?array{color: array, thickness: int, transparency: float},
     *   cornerRadius: int, clips: bool, text: ?array, padding: ?array
     * }
     */
    public static function resolve(array $n): array
    {
        $opacity  = max(0.0, min(1.0, (float)($n['opacity'] ?? 1)));
        $bg       = $n['bg'] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
        $gradient = !empty($n['gradient']['keypoints']) ? $n['gradient'] : null;

        // UIGradient MENGALIKAN BackgroundColor3 → base harus putih & opaque,
        // warna + alpha diatur penuh oleh ColorSequence / NumberSequence
        $bgColor        = $gradient ? ['r' => 1, 'g' => 1, 'b' => 1, 'a' => 1] : $bg;
        $bgTransparency = $gradient ? 1 - $opacity : 1 - ($bg['a'] ?? 0) * $opacity;

        $stroke = null;
        $borderW = (int)round($n['borderW'] ?? 0);
        $border  = $n['borderColor'] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
        if ($borderW > 0 && ($border['a'] ?? 0) > 0.001) {
            $stroke = [
                'color'        => $border,
                'thickness'    => $borderW,
                'transparency' => round(1 - $border['a'] * $opacity, 3),
            ];
        }

        $text = null;
        $padding = null;
        if (in_array($n['robloxClass'] ?? '', self::TEXT_CLASSES, true)) {
            $plain = $n['text'] ?: (($n['value'] ?? '') ?: ($n['placeholder'] ?? '') ?: '');
            if (($n['robloxClass'] ?? '') === 'TextBox') {
                $plain = $n['value'] ?? '' ?: '';
            }
            $rich = (string)($n['richText'] ?? '');
            $fg   = $n['fg'] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 1];

            $text = [
                'value'        => $rich !== '' ? $rich : $plain,
                'rich'         => $rich !== '',
                'color'        => $fg,
                'transparency' => round(1 - ($fg['a'] ?? 1) * $opacity, 3),
                'size'         => max(1, (int)round($n['fontSize'] ?? 16)),
                'font'         => FontHelper::resolve($n),
                'alignX'       => self::alignX($n['textAlign'] ?? 'left'),
                'alignY'       => ucfirst($n['textAlignY'] ?? 'center'),
                'wrapped'      => (bool)($n['textWrapped'] ?? (mb_strlen($plain) > 15)),
                'placeholder'  => (string)($n['placeholder'] ?? ''),
            ];

            $pad = [
                'L' => (int)($n['padL'] ?? 0), 'R' => (int)($n['padR'] ?? 0),
                'T' => (int)($n['padT'] ?? 0), 'B' => (int)($n['padB'] ?? 0),
            ];
            // Container ber-layout: padding diatur oleh layout (hindari 2 UIPadding)
            if (array_sum($pad) > 0 && empty($n['layout'])) $padding = $pad;
        }

        return [
            'bgColor'        => $bgColor,
            'bgTransparency' => round(max(0, min(1, $bgTransparency)), 3),
            'gradient'       => $gradient,
            'stroke'         => $stroke,
            'cornerRadius'   => (int)round($n['radius'] ?? 0),
            'clips'          => !empty($n['clips']),
            'text'           => $text,
            'padding'        => $padding,
        ];
    }

    private static function alignX(string $a): string
    {
        if ($a === 'center') return 'Center';
        if ($a === 'right' || $a === 'end') return 'Right';
        return 'Left';
    }
}
