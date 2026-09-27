<?php
// app/helpers/FontHelper.php — CSS font-family / font-weight / font-style → Roblox FontFace

class FontHelper
{
    /** Keyword di font-family CSS → nama family Roblox (rbxasset://fonts/families/<nama>.json) */
    private const FAMILIES = [
        'roboto mono'    => 'RobotoMono',
        'monospace'      => 'RobotoMono',
        'consolas'       => 'RobotoMono',
        'courier'        => 'RobotoMono',
        'menlo'          => 'RobotoMono',
        'montserrat'     => 'Montserrat',
        'roboto'         => 'Roboto',
        'arial'          => 'Arial',
        'helvetica'      => 'Arial',
        'merriweather'   => 'Merriweather',
        'georgia'        => 'Merriweather',
        'times'          => 'Merriweather',
        'serif'          => 'Merriweather',
        'nunito'         => 'Nunito',
        'oswald'         => 'Oswald',
        'ubuntu'         => 'Ubuntu',
        'source sans'    => 'SourceSansPro',
        'press start'    => 'PressStart2P',
        'fredoka'        => 'FredokaOne',
        'bangers'        => 'Bangers',
        'titillium'      => 'TitilliumWeb',
        'josefin'        => 'JosefinSans',
        'gotham'         => 'GothamSSm',
    ];

    /** Default untuk sans-serif modern (Inter, Poppins, Segoe UI, system-ui, ...) */
    private const DEFAULT_FAMILY = 'GothamSSm';

    private const WEIGHTS = [
        100 => 'Thin', 200 => 'ExtraLight', 300 => 'Light', 400 => 'Regular', 500 => 'Medium',
        600 => 'SemiBold', 700 => 'Bold', 800 => 'ExtraBold', 900 => 'Heavy',
    ];

    /**
     * @return array{family:string, url:string, weight:string, weightNum:int, style:string}
     */
    public static function resolve(array $node): array
    {
        $text = $node['text'] ?: ($node['value'] ?? '') ?: ($node['placeholder'] ?? '');

        // Emoji / ikon pendek → Arial (fallback glyph paling aman)
        $isIcon = $text !== '' && preg_match('/[^\x00-\x7F]/u', $text) && mb_strlen($text) <= 3;
        $family = $isIcon ? 'Arial' : self::family((string)($node['fontFamily'] ?? ''));

        $weightNum = self::weight($node['fontWeight'] ?? '400');

        return [
            'family'    => $family,
            'url'       => "rbxasset://fonts/families/{$family}.json",
            'weight'    => self::WEIGHTS[$weightNum],
            'weightNum' => $weightNum,
            'style'     => ($node['fontStyle'] ?? '') === 'italic' ? 'Italic' : 'Normal',
        ];
    }

    private static function family(string $css): string
    {
        $css = strtolower($css);
        // Urut sesuai font-family CSS (font pertama yang dikenal yang dipakai)
        foreach (explode(',', $css) as $name) {
            $name = trim($name, " \"'");
            foreach (self::FAMILIES as $keyword => $family) {
                if ($name !== '' && str_contains($name, $keyword)) {
                    // 'serif' jangan match 'sans-serif'
                    if ($keyword === 'serif' && str_contains($name, 'sans')) continue;
                    return $family;
                }
            }
        }
        return self::DEFAULT_FAMILY;
    }

    private static function weight($css): int
    {
        $css = strtolower(trim((string)$css));
        $n = match ($css) {
            'bold', 'bolder' => 700,
            'lighter'        => 300,
            'normal', ''     => 400,
            default          => (int)$css ?: 400,
        };
        return max(100, min(900, (int)round($n / 100) * 100));
    }
}
