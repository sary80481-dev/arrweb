<?php
// app/helpers/LuaHelper.php — formatter nilai Lua (string, Color3, sequence, easing)

class LuaHelper
{
    public static function mapEasing($css) {
        $css = strtolower(trim($css));
        if (str_contains($css, 'cubic-bezier')) return 'Enum.EasingStyle.Back, Enum.EasingDirection.Out';
        return [
            'linear'      => 'Enum.EasingStyle.Linear',
            'ease'        => 'Enum.EasingStyle.Quad, Enum.EasingDirection.Out',
            'ease-in'     => 'Enum.EasingStyle.Quad, Enum.EasingDirection.In',
            'ease-out'    => 'Enum.EasingStyle.Quad, Enum.EasingDirection.Out',
            'ease-in-out' => 'Enum.EasingStyle.Quad, Enum.EasingDirection.InOut',
        ][$css] ?? 'Enum.EasingStyle.Quad, Enum.EasingDirection.Out';
    }

    public static function colorSeq($kps) {
        $parts = [];
        foreach ($kps as $kp) {
            $t = max(0, min(1, $kp['pos']));
            $parts[] = sprintf('ColorSequenceKeypoint.new(%.3f, Color3.fromRGB(%d, %d, %d))',
                $t,
                round($kp['color']['r'] * 255),
                round($kp['color']['g'] * 255),
                round($kp['color']['b'] * 255));
        }
        return 'ColorSequence.new({' . implode(', ', $parts) . '})';
    }

    public static function numSeq($kps) {
        $parts = [];
        foreach ($kps as $kp) {
            $t = max(0, min(1, $kp['pos']));
            $a = 1 - $kp['color']['a'];
            $parts[] = sprintf('NumberSequenceKeypoint.new(%.3f, %.3f)', $t, $a);
        }
        return 'NumberSequence.new({' . implode(', ', $parts) . '})';
    }

    public static function q($s) { return '"' . addcslashes($s, "\\\"\n") . '"'; }
    public static function c3($c) { return sprintf('%.3f, %.3f, %.3f', $c['r'], $c['g'], $c['b']); }

    /**
     * Nilai PHP → literal Lua (array list / map / string / number / bool / nil)
     */
    public static function value($v, int $indent = 0): string {
        if ($v === null) return 'nil';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_int($v) || is_float($v)) return (string)$v;
        if (!is_array($v)) return self::q((string)$v);
        if ($v === []) return '{}';

        $pad  = str_repeat('    ', $indent + 1);
        $end  = str_repeat('    ', $indent);
        $list = array_is_list($v);
        $rows = [];
        foreach ($v as $k => $item) {
            $key = match (true) {
                $list                                            => '',
                is_int($k)                                       => "[{$k}] = ",
                (bool)preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k) => "{$k} = ",
                default                                          => '[' . self::q($k) . '] = ',
            };
            $rows[] = $pad . $key . self::value($item, $indent + 1) . ',';
        }
        return "{\n" . implode("\n", $rows) . "\n{$end}}";
    }

    /** "Dragon Sword!" → "DragonSword" (aman untuk key / nama Instance) */
    public static function ident(string $s, string $fallback = 'Item'): string {
        $s = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $s);
        $s = str_replace(' ', '', ucwords(strtolower(trim($s))));
        if ($s === '' || ctype_digit($s[0])) $s = $fallback . $s;
        return $s;
    }
}

