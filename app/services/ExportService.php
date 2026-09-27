<?php
// app/services/ExportService.php — gabungkan semua output generator untuk 1 set node

class ExportService
{
    /** Default config BillboardGui kalau frontend nggak kirim */
    private const DEFAULT_BILLBOARD = [
        'name'        => 'Player',
        'role'        => 'Member',
        'level'       => 1,
        'offsetY'     => 3.5,
        'size'        => [220, 70],
        'maxDistance' => 120,
        'theme'       => 'gold',
    ];

    public function build(array $nodes, int $width, int $height, ?array $billboard = null): array
    {
        // 1. Baca logic GUI dulu (tombol, card, toggle, saldo)
        $spec = (new GuiAnalyzer())->analyze($nodes);

        // 2. Elemen yang diklik (card item, label aksi, toggle) → TextButton di Studio
        $nodes = $this->promoteClickables($nodes, $spec);

        // 3. Bentuk CSS tanpa padanan langsung (border di dalam kotak, border sebagian, radius sebagian)
        $nodes = ShapeFixer::apply($nodes);
        $previewNodes = $nodes;

        // 4. Flex/grid yang terverifikasi → UIListLayout / UIGridLayout
        $nodes = $this->applyLayouts($nodes);   // posisi asli di canvas desain (untuk preview Roblox di web)

        // 5. Bingkai UI: root dirapatkan ke (0,0), ScreenGui & container diberi nama sesuai UI
        [$nodes, $width, $height, $ui] = $this->frameUi($nodes, $width, $height);

        $logic = GameLogicGenerator::generate($nodes, $spec, $ui);

        return [
            'script'       => LuaGenerator::generateBehaviorScript($nodes, $ui),
            'fullscript'   => FullScriptGenerator::generateFullScript($nodes, $width, $height, $ui),
            'tree'         => LuaGenerator::renderTreeText($nodes),
            'rbxmx'        => RbxmxGenerator::generate($nodes, $width, $height, $ui, $logic),   // paket selalu lengkap
            'plugin'       => PluginGenerator::generate(),
            'billboard'    => BillboardGenerator::generate($billboard ?? self::DEFAULT_BILLBOARD),
            'report'       => LuaGenerator::renderReport($nodes),
            'module'       => $logic['module'],
            'server'       => $logic['server'],
            'client'       => $logic['client'],
            'logicSummary' => $this->logicSummary($spec),
            'ui'           => $ui,
            'nodes'        => $previewNodes,
        ];
    }

    private function applyLayouts(array $nodes): array
    {
        $children = [];
        foreach ($nodes as $i => $n) $children[$n['parentId'] ?? 0][] = $i;

        foreach ($nodes as $i => $n) {
            $kidIdx = $children[$n['id']] ?? [];
            if (!$kidIdx) continue;
            $layout = LayoutDetector::detect($n, array_map(fn($k) => $nodes[$k], $kidIdx));
            if ($layout === null) continue;
            $nodes[$i]['layout'] = $layout;
            foreach ($kidIdx as $order => $k) $nodes[$k]['layoutOrder'] = $order + 1;
        }
        return $nodes;
    }

    private function promoteClickables(array $nodes, array $spec): array
    {
        $targets = [];
        foreach ($spec['bindings'] as $b) $targets[$b['target']] = true;

        foreach ($nodes as &$n) {
            if (!isset($targets[$n['name']]) || in_array($n['robloxClass'], ['TextButton', 'ImageButton', 'TextBox'], true)) continue;
            // Frame (card/toggle) → TextButton kosong; TextLabel ("Save Changes") → TextButton berteks
            if ($n['robloxClass'] === 'Frame') $n['text'] = '';
            $n['robloxClass']  = 'TextButton';
            $n['isButtonLike'] = true;
        }
        unset($n);
        return $nodes;
    }

    /**
     * Rapatkan root ke konten & tentukan nama:
     *  - 1 root  → root itu sendiri jadi container (tanpa Frame "Canvas" kosong)
     *  - >1 root → container transparan "Main" seukuran konten
     */
    private function frameUi(array $nodes, int $width, int $height): array
    {
        $roots = array_keys(array_filter($nodes, fn($n) => ($n['parentId'] ?? null) === null));
        if (!$roots) {
            return [$nodes, $width, $height, ['name' => 'Main', 'gui' => 'MainGui', 'singleRoot' => false]];
        }

        $minX = min(array_map(fn($i) => $nodes[$i]['x'], $roots));
        $minY = min(array_map(fn($i) => $nodes[$i]['y'], $roots));
        $maxX = max(array_map(fn($i) => $nodes[$i]['x'] + $nodes[$i]['w'], $roots));
        $maxY = max(array_map(fn($i) => $nodes[$i]['y'] + $nodes[$i]['h'], $roots));
        foreach ($roots as $i) {
            $nodes[$i]['x'] -= $minX;
            $nodes[$i]['y'] -= $minY;
        }

        $single = count($roots) === 1;
        $name   = $single ? $nodes[$roots[0]]['name'] : 'Main';
        $base   = preg_replace('/(Frame|Container|Panel)$/', '', $name) ?: 'Main';

        return [$nodes, max(1, $maxX - $minX), max(1, $maxY - $minY), [
            'name'       => $name,
            'gui'        => $base . 'Gui',
            'singleRoot' => $single,
        ]];
    }

    /**
     * Ringkasan singkat hasil analisis GUI (ditampilkan di tab Game Logic)
     */
    private function logicSummary(array $spec): string
    {
        $parts = [];
        if ($spec['items'])      $parts[] = count($spec['items']) . ' item';
        if ($spec['currencies']) $parts[] = implode('/', array_keys($spec['currencies']));
        if ($spec['settings'])   $parts[] = count($spec['settings']) . ' setting';
        if ($spec['actions'])    $parts[] = implode(', ', $spec['actions']);
        return $parts ? implode(' · ', $parts) : 'Tidak ada aksi server terdeteksi — template dasar';
    }
}
