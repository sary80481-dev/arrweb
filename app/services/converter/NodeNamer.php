<?php
// app/services/converter/NodeNamer.php — nama Instance yang mudah dibaca di Explorer Roblox Studio
//
// Urutan sumber nama:
//   1. data-name / aria-label / id / name        → "ClaimBtn" → "ClaimButton"
//   2. teks (tombol, judul, label pendek)         → "Buy Now" → "BuyNowButton", "Item Shop" → "ItemShopTitle"
//   3. class CSS yang bermakna (tanpa prefix BEM) → "shop-card" → "ShopCard", "dr-claim" → "Claim"
//   4. konteks isi (container dengan judul)       → card berisi "Dragon Sword" → "DragonSwordCard"
//   5. peran (tag / class Roblox)                 → "Header", "Icon", "Price", "Container"
// Nama dibuat unik; kalau bentrok, diberi konteks parent dulu ("DragonSwordPrice") baru angka.

class NodeNamer {
    /** Kata di class yang sudah menjelaskan peran container */
    private const CONTAINER_WORDS = 'card|panel|row|col|column|grid|list|header|head|footer|foot|bar|slot|box|wrap|wrapper|container|section|menu|modal|popup|dialog|window|tab|tabs|nav|sidebar|body|content|group|stack|track|item|tile|badge|chip|pill';

    /** Class utilitas (Tailwind dsb.) yang tidak bermakna sebagai nama */
    private const UTILITY_CLASS = '/^(flex|grid|block|inline|hidden|relative|absolute|fixed|sticky|w|h|min|max|p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|gap|space|text|font|bg|border|rounded|shadow|items|justify|self|place|overflow|z|opacity|transition|duration|ease|cursor|select|leading|tracking|uppercase|lowercase|capitalize|truncate|active|selected|disabled|open|show|on|off|is|has|js|sm|md|lg|xl|dark|light|primary|secondary|ghost|done|today|equipped|unlocked|premium|ok|miss|me|gold|silver|bronze|danger|success|warning|info|new|hot)(-|$|:)/i';

    /** Kata class yang sudah cukup jelas tanpa suffix Label/Frame */
    private const SELF_DESCRIBING = '/(icon|avatar|logo|title|subtitle|label|price|amount|value|name|text|desc|description|count|counter|badge|tag|rarity|level|lv|note|hint|stat|num|number|balance|timer|score|rank|image|img|art|thumb|thumbnail|arrow|divider|fill|progress|knob)$/i';

    private const ROLE_BY_TAG = [
        'nav' => 'Nav', 'header' => 'Header', 'footer' => 'Footer', 'main' => 'Main', 'aside' => 'Sidebar',
        'section' => 'Section', 'article' => 'Article', 'form' => 'Form', 'ul' => 'List', 'ol' => 'List',
        'li' => 'ListItem', 'table' => 'Table', 'thead' => 'TableHead', 'tbody' => 'TableBody', 'tr' => 'Row',
        'td' => 'Cell', 'th' => 'HeaderCell', 'img' => 'Image', 'svg' => 'Icon', 'hr' => 'Divider',
        'input' => 'Input', 'textarea' => 'Input', 'select' => 'Dropdown', 'label' => 'Label',
        'progress' => 'ProgressBar', 'video' => 'Video', 'canvas' => 'Canvas',
    ];

    public static function assignProfessionalNames(&$nodes) {
        $byId = [];
        $children = [];
        foreach ($nodes as $i => $n) {
            $byId[$n['id']] = $i;
            $children[$n['parentId'] ?? 0][] = $n['id'];
        }

        $used = [];
        foreach ($nodes as $i => &$n) {
            $base = self::baseName($n, $nodes, $byId, $children) ?: 'Frame';

            $name = $base;
            if (isset($used[$name])) {
                // Bentrok → tambah konteks parent ("Price" di card "DragonSword" → "DragonSwordPrice")
                $ctx = self::parentContext($n, $nodes, $byId);
                if ($ctx !== '' && !str_starts_with($base, $ctx)) $name = $ctx . $base;
                for ($k = 2; isset($used[$name]); $k++) $name = ($ctx !== '' && !str_starts_with($base, $ctx) ? $ctx . $base : $base) . $k;
            }
            $used[$name] = true;
            $n['name'] = $name;
        }
        unset($n);
    }

    // ============================================================
    // NAMA DASAR PER NODE
    // ============================================================
    private static function baseName(array $n, array $nodes, array $byId, array $children): string {
        $class  = $n['robloxClass'] ?? 'Frame';
        $tag    = $n['tag'] ?? 'div';
        $text   = self::cleanText($n['text'] ?? '');
        $isBtn  = in_array($class, ['TextButton', 'ImageButton'], true);
        $suffix = $isBtn ? 'Button' : '';

        // 1. Nama eksplisit
        foreach ([$n['explicitName'] ?? '', $n['sourceId'] ?? ''] as $explicit) {
            $name = self::pascal(preg_replace('/(^|[-_])(btn|button)$/i', '$1Button', $explicit));
            if ($name !== '') return self::withSuffix($name, $isBtn ? 'Button' : self::roleSuffix($n));
        }

        // 2. Teks
        if ($class === 'TextBox') {
            $ph = self::pascal(preg_replace('/^(contoh|e\.?g\.?|example)\s*:.*$/i', '', $n['placeholder'] ?? ''));
            return self::withSuffix(self::classWord($n) ?: ($ph !== '' && strlen($ph) <= 24 ? $ph : 'Text'), 'Input');
        }
        if ($tag === 'img') {
            return self::withSuffix(self::pascal($n['alt'] ?? '') ?: self::classWord($n) ?: 'Image', 'Image');
        }
        if (self::isIcon($n['text'] ?? '')) {
            $w = self::classWord($n);
            return ($w !== '' && preg_match(self::SELF_DESCRIBING, $w)) ? $w : (self::withSuffix($w, 'Icon') ?: 'Icon');
        }
        if ($text !== '') {
            if (self::isNumeric($text)) return self::withSuffix(self::classWord($n) ?: self::numericRole($n['text']), $isBtn ? 'Button' : '');
            // Angka di tengah teks tidak ikut nama ("Refreshes in 04:32:18" → "RefreshesIn")
            $words = self::pascal(self::stripNumbers($text));
            if ($words === '') $words = self::classWord($n) ?: 'Info';
            if ($isBtn && strlen($words) <= 28) return self::withSuffix($words, 'Button');
            if (in_array($tag, ['h1', 'h2', 'h3'], true) || ($n['fontSize'] ?? 16) >= 20) {
                if (strlen($words) <= 28) return self::withSuffix($words, 'Title');
            }
            if (strlen($words) <= 22 && str_word_count($text) <= 4) return self::withSuffix($words, 'Label');
            if (preg_match('/^\d/', $text)) return self::withSuffix(self::classWord($n) ?: 'Info', 'Label');
            // Kalimat panjang: pakai class kalau ada, kalau tidak "Description"
            $w = self::classWord($n);
            return self::withSuffix($w ?: 'Description', $isBtn ? 'Button' : ($w !== '' ? self::roleSuffix($n, $w) : ''));
        }

        // 3. Class CSS
        $word = self::classWord($n);

        // Switch (pill + knob bulat) → "<Judul baris>Toggle", knob-nya "Knob"
        if (self::isSwitch($n, $nodes, $byId, $children)) {
            $row = ($n['parentId'] ?? null) !== null ? self::childTitle($n['parentId'], $nodes, $byId, $children) : '';
            return $row . 'Toggle';
        }
        if (($n['parentId'] ?? null) !== null && self::isSwitch($nodes[$byId[$n['parentId']]], $nodes, $byId, $children)) {
            return 'Knob';
        }

        // 4. Konteks: peran dari bentuk (Grid/List/Header/Card) + judul di dalamnya
        if (in_array($class, ['Frame', 'ScrollingFrame', 'TextButton', 'ImageButton'], true)) {
            $shape = self::shapeRole($n, $nodes, $byId, $children);
            if ($shape === 'Grid' || $shape === 'List') {
                // Kumpulan item: nama dari class, bukan dari item pertama
                return $word !== '' ? self::withSuffix($word, preg_match('/(' . self::CONTAINER_WORDS . ')$/i', $word) ? '' : $shape) : $shape;
            }

            $title = self::childTitle($n['id'], $nodes, $byId, $children);
            if ($title !== '') {
                if (($n['parentId'] ?? null) === null) return strlen($word) >= 3 ? $word : $title;   // root = nama menu
                if ($isBtn) return self::withSuffix($title, 'Button');
                $role = $word !== '' && preg_match('/(' . self::CONTAINER_WORDS . ')$/i', $word, $m)
                    ? self::normalizeRole($m[1]) : ($shape ?? 'Frame');
                // "Day 1" + class "day" → "Day1Card" (bukan "Day1Day")
                if ($word !== '' && stripos($title, $word) !== false) $role = $shape ?? 'Card';
                return self::withSuffix($title, $role);
            }
            if ($shape !== null && $word === '') return $shape;
        }
        if ($word !== '') return self::withSuffix($word, $suffix ?: self::roleSuffix($n, $word));

        // 5. Peran
        if (isset(self::ROLE_BY_TAG[$tag])) return self::withSuffix(self::ROLE_BY_TAG[$tag], $suffix);
        if ($isBtn) return 'Button';
        if (($n['isToggleSwitch'] ?? false)) return 'Toggle';
        if (($n['w'] ?? 0) <= 4 || ($n['h'] ?? 0) <= 4) return 'Divider';
        return 'Container';
    }

    // ============================================================
    // HELPER
    // ============================================================

    /** Class pertama yang bermakna, tanpa prefix BEM pendek: "dr-claim" → "Claim", "shop-card" → "ShopCard" */
    private static function classWord(array $n): string {
        foreach (preg_split('/\s+/', trim($n['className'] ?? '')) as $token) {
            if ($token === '' || preg_match(self::UTILITY_CLASS, $token)) continue;
            $token = preg_replace('/__|--/', '-', $token);
            $parts = explode('-', $token);
            // Prefix singkatan 1-3 huruf (dr-, rc-, lo-, pc-) dibuang
            if (count($parts) > 1 && strlen($parts[0]) <= 3) array_shift($parts);
            $word = self::pascal(implode(' ', $parts));
            if (strlen($word) >= 2 && !preg_match(self::UTILITY_CLASS, $word)) return $word;
        }
        return '';
    }

    /** Judul di dalam container: teks pendek terbesar/terberat (heading diutamakan, makin dalam makin kecil skornya) */
    private static function childTitle(int $id, array $nodes, array $byId, array $children): string {
        $best = null;
        $queue = array_map(fn($c) => [$c, 1], $children[$id] ?? []);
        while ($queue) {
            [$cid, $depth] = array_shift($queue);
            $c = $nodes[$byId[$cid]];
            $t = self::cleanText($c['text'] ?? '');
            // "Quest: Dragon of Ember Peak" → "Dragon of Ember Peak"
            $t = preg_replace('/^(quest|misi|shop|toko)\s*:\s*/i', '', $t);
            if (strlen($t) >= 3 && !self::isIcon($c['text']) && !self::isNumeric($t) && str_word_count($t) <= 5
                && !preg_match('/^\d/', $t) && !in_array($c['robloxClass'], ['TextButton', 'TextBox'], true)) {
                $score = ($c['fontSize'] ?? 16)
                    + ((int)($c['fontWeight'] ?? 400) >= 600 ? 4 : 0)
                    + (in_array($c['tag'] ?? '', ['h1', 'h2', 'h3', 'h4'], true) ? 10 : 0)
                    - $depth;
                if ($best === null || $score > $best[0]) $best = [$score, $t];
            }
            if ($depth < 4) foreach ($children[$cid] ?? [] as $g) $queue[] = [$g, $depth + 1];
        }
        if ($best === null) return '';
        $p = self::pascal(self::stripNumbers($best[1]) ?: $best[1]);
        return strlen($p) <= 24 ? $p : '';
    }

    /** Peran dari bentuk: Grid/List (anak seragam), Header/Footer (strip atas/bawah), Card (judul + harga/tombol) */
    private static function shapeRole(array $n, array $nodes, array $byId, array $children): ?string {
        $kids = array_map(fn($id) => $nodes[$byId[$id]], $children[$n['id']] ?? []);
        if (count($kids) >= 3) {
            $sizes = array_map(fn($k) => round($k['w'] / 4) . 'x' . round($k['h'] / 4), $kids);
            $common = max(array_count_values($sizes));
            if ($common >= 3 && $common >= count($kids) * 0.75) {
                $rows = count(array_unique(array_map(fn($k) => (int)round($k['y'] / 4), $kids)));
                $cols = count(array_unique(array_map(fn($k) => (int)round($k['x'] / 4), $kids)));
                return ($rows > 1 && $cols > 1) ? 'Grid' : 'List';
            }
        }

        $pid = $n['parentId'] ?? null;
        if ($pid !== null) {
            $parent = $nodes[$byId[$pid]];
            $siblings = $children[$pid] ?? [];
            // Baris dalam list seragam bukan header/footer
            $same = count(array_filter($siblings, fn($id) => abs($nodes[$byId[$id]]['w'] - $n['w']) <= 4 && abs($nodes[$byId[$id]]['h'] - $n['h']) <= 8));
            if ($same >= 3) $siblings = [];
            $wide = $n['w'] >= $parent['w'] * 0.8;
            if ($wide && $siblings && $siblings[0] === $n['id'] && count($siblings) > 1 && $n['h'] < $parent['h'] * 0.35) return 'Header';
            if ($wide && $siblings && end($siblings) === $n['id'] && count($siblings) > 1 && $n['h'] < $parent['h'] * 0.35) return 'Footer';
        }

        $hasPrice = false;
        foreach ($children[$n['id']] ?? [] as $cid) {
            $stack = [$cid];
            while ($stack) {
                $c = $nodes[$byId[array_pop($stack)]];
                if (preg_match('/\d/', $c['text'] ?? '') && preg_match('/💎|💰|🪙|\$|coin|gem|gold/iu', $c['text'])) $hasPrice = true;
                foreach ($children[$c['id']] ?? [] as $g) $stack[] = $g;
            }
        }
        return $hasPrice ? 'Card' : null;
    }

    private static function isSwitch(array $n, array $nodes, array $byId, array $children): bool {
        if (!empty($n['isToggleSwitch'])) return true;
        $w = $n['w'] ?? 0; $h = $n['h'] ?? 0;
        if ($w < 28 || $w > 72 || $h < 12 || $h > 36 || $w < $h * 1.4 || ($n['radius'] ?? 0) < $h / 2 - 2) return false;
        $kids = $children[$n['id']] ?? [];
        if (count($kids) !== 1) return false;
        $k = $nodes[$byId[$kids[0]]];
        return abs($k['w'] - $k['h']) <= 3 && $k['h'] <= $h;
    }

    private static function parentContext(array $n, array $nodes, array $byId): string {
        for ($pid = $n['parentId'] ?? null; $pid !== null; $pid = $nodes[$byId[$pid]]['parentId'] ?? null) {
            $name = $nodes[$byId[$pid]]['name'] ?? '';
            $ctx = preg_replace('/(Card|Panel|Frame|Container|Row|Slot|Item|Tile|Box|Button|Section|Wrap|Wrapper)\d*$/', '', $name);
            if ($ctx !== '' && $ctx !== 'Container' && !preg_match('/^(Row|Cell|List|ListItem|Frame)\d*$/', $ctx)) return $ctx;
        }
        return '';
    }

    private static function roleSuffix(array $n, string $word = ''): string {
        if ($word !== '' && (preg_match('/(' . self::CONTAINER_WORDS . ')$/i', $word) || preg_match(self::SELF_DESCRIBING, $word))) return '';
        return match ($n['robloxClass'] ?? '') {
            'TextLabel'      => 'Label',
            'ImageLabel'     => 'Image',
            'ScrollingFrame' => 'Scroll',
            'TextBox'        => 'Input',
            default          => '',
        };
    }

    private static function normalizeRole(string $role): string {
        return match (strtolower($role)) {
            'head' => 'Header', 'foot' => 'Footer', 'col', 'column' => 'Column', 'wrap', 'wrapper' => 'Container',
            default => ucfirst($role),
        };
    }

    private static function numericRole(string $text): string {
        if (preg_match('/💎|💰|🪙|\$|coin|gem|gold|koin/iu', $text)) return 'Price';
        if (str_contains($text, '%')) return 'Percent';
        if (preg_match('/\d+\s*[:\/]\s*\d+/', $text)) return 'Counter';
        return 'Value';
    }

    private static function withSuffix(string $name, string $suffix): string {
        if ($name === '') return '';
        if ($suffix === '' || preg_match('/' . preg_quote($suffix, '/') . '$/i', $name)) return $name;
        // "ClaimBtn" + Button → "ClaimButton"
        if ($suffix === 'Button' && preg_match('/Btn$/', $name)) return substr($name, 0, -3) . 'Button';
        return $name . $suffix;
    }

    /** Buang emoji, harga setelah "·", tanda baca */
    /** Buang angka "panjang" (04:32:18, 1,200, 3/5) tapi pertahankan nomor kecil ("Day 1", "Tier 10") */
    private static function stripNumbers(string $t): string {
        $t = preg_replace('/\d+([:.,\/]\d+)+|\(\s*\)/', ' ', $t);
        return trim(preg_replace('/\b\d{3,}\b/', ' ', $t));
    }

    private static function cleanText(string $t): string {
        $t = preg_replace('/\s*[·|•]\s.*$/u', '', $t);
        $t = preg_replace('/[^\p{L}\p{N}\s%\/:.,$]/u', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private static function isIcon(string $t): bool {
        $t = trim($t);
        return $t !== '' && mb_strlen($t) <= 3 && !preg_match('/[\p{L}\p{N}]/u', $t);
    }

    private static function isNumeric(string $t): bool {
        return (bool)preg_match('/^[\d\s.,:%\/+\-x×$]+(k|m|b|x|xp|hp|mp|lv|gems?|coins?|gold|koin)?$/iu', trim($t)) && preg_match('/\d/', $t);
    }

    private static function pascal(string $str): string {
        $str = preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $out = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_strtolower(mb_substr($w, 1)), array_slice($parts, 0, 5)));
        // Nama Instance: ASCII aman
        $out = preg_replace('/[^A-Za-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $out) ?: $out);
        if ($out !== '' && ctype_digit($out[0])) $out = 'N' . $out;
        return substr($out, 0, 40);
    }
}
