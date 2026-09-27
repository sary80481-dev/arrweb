<?php
// app/services/generators/FullScriptGenerator.php — full LocalScript (build UI + behavior)
class FullScriptGenerator {
    /**
     * $ui: ['name' => nama container, 'gui' => nama ScreenGui, 'singleRoot' => bool]
     */
    public static function generateFullScript($nodes, $W, $H, array $ui = []) {
        $ui += ['name' => 'Canvas', 'gui' => 'GeneratedUI', 'singleRoot' => false];
        $gui = LuaHelper::q($ui['gui']);
        $L = [];
        $L[] = '-- ============================================================';
        $L[] = '--  FULL SCRIPT — bikin UI dari nol di runtime';
        $L[] = '--  ' . count($nodes) . " instances  |  Canvas {$W}x{$H}";
        $L[] = '--  Drop ke: StarterPlayer > StarterPlayerScripts (LocalScript)';
        $L[] = '-- ============================================================';
        $L[] = '';
        $L[] = 'local Players = game:GetService("Players")';
        $L[] = 'local TweenService = game:GetService("TweenService")';
        $L[] = 'local RunService = game:GetService("RunService")';
        $L[] = '';
        $L[] = '-- Guard: Script ini butuh LocalPlayer (client runtime)';
        $L[] = 'local player = Players.LocalPlayer';
        $L[] = 'if not player then';
        $L[] = '    warn("[ArrStudio] Script ini harus dijalankan sebagai LocalScript di client")';
        $L[] = '    return';
        $L[] = 'end';
        $L[] = '';
        $L[] = 'local playerGui = player:WaitForChild("PlayerGui")';
        $L[] = '';
        $L[] = "local old = playerGui:FindFirstChild({$gui})";
        $L[] = 'if old then old:Destroy() end';
        $L[] = '';
        $L[] = 'local screenGui = Instance.new("ScreenGui")';
        $L[] = "screenGui.Name = {$gui}";
        $L[] = 'screenGui.ResetOnSpawn = false';
        $L[] = 'screenGui.IgnoreGuiInset = true';
        $L[] = 'screenGui.ZIndexBehavior = Enum.ZIndexBehavior.Sibling';
        $L[] = 'screenGui.Parent = playerGui';
        $L[] = '';
        if ($ui['singleRoot']) {
            $L[] = '-- Root UI sendiri yang jadi container (di-center + di-scale)';
            $L[] = 'local canvas';
        } else {
            $L[] = 'local canvas = Instance.new("Frame")';
            $L[] = 'canvas.Name = ' . LuaHelper::q($ui['name']);
            $L[] = 'canvas.BackgroundTransparency = 1';
            $L[] = "canvas.Size = UDim2.new(0, {$W}, 0, {$H})";
            $L[] = 'canvas.Position = UDim2.new(0.5, 0, 0.5, 0)';
            $L[] = 'canvas.AnchorPoint = Vector2.new(0.5, 0.5)';
            $L[] = 'canvas.ClipsDescendants = false';
            $L[] = 'canvas.Active = false';
            $L[] = 'canvas.Parent = screenGui';
            $L[] = '';
        }
        $L[] = '';
        $L[] = 'local function findByNormName(root, targetName)';
        $L[] = '    local targetNorm = targetName:lower():gsub("[^%a%d]", "")';
        $L[] = '    for _, g in ipairs(root:GetDescendants()) do';
        $L[] = '        if g:IsA("GuiObject") then';
        $L[] = '            local n = g.Name:lower():gsub("[^%a%d]", "")';
        $L[] = '            if n == targetNorm then return g end';
        $L[] = '        end';
        $L[] = '    end';
        $L[] = '    return nil';
        $L[] = 'end';
        $L[] = '';

        $sorted = self::sortByDepth($nodes);
        $vmap = [];
        foreach ($sorted as $n) $vmap[$n['id']] = 'n' . $n['id'];

        foreach ($sorted as $n) {
            $v = $vmap[$n['id']];
            $L[] = "local {$v} = Instance.new(\"" . $n['robloxClass'] . "\")";
            $L[] = "{$v}.Name = " . LuaHelper::q($n['name']);

            // Posisi persis hasil render browser (relatif ke parent)
            $L[] = "{$v}.Position = UDim2.new(0, " . (int)$n['x'] . ", 0, " . (int)$n['y'] . ")";
            $L[] = "{$v}.Size = UDim2.new(0, {$n['w']}, 0, {$n['h']})";
            if (abs($n['rotation'] ?? 0) > 0.01) $L[] = "{$v}.Rotation = " . round($n['rotation'], 1);
            $st = NodeStyle::resolve($n);
            $L[] = "{$v}.BackgroundColor3 = Color3.new(" . LuaHelper::c3($st['bgColor']) . ')';
            $L[] = "{$v}.BackgroundTransparency = " . $st['bgTransparency'];
            $L[] = "{$v}.BorderSizePixel = 0";
            if ($st['clips']) $L[] = "{$v}.ClipsDescendants = true";
            if (!empty($n['selfHidden'])) $L[] = "{$v}.Visible = false";

            // Setup ScrollingFrame
            if ($n['robloxClass'] === 'ScrollingFrame') {
                // Hitung tinggi konten dari descendants
                $maxBottom = 0;
                foreach ($sorted as $child) {
                    if ($child['parentId'] === $n['id'] || self::isDescendantOf($child, $n['id'], $sorted)) {
                        $childBottom = ($child['y'] ?? 0) + ($child['h'] ?? 0);
                        if ($childBottom > $maxBottom) $maxBottom = $childBottom;
                    }
                }
                $canvasH = max($n['h'], $maxBottom + 40);
                $L[] = "{$v}.CanvasSize = UDim2.new(0, 0, 0, {$canvasH})";
                $L[] = "{$v}.ScrollBarThickness = 6";
                $L[] = "{$v}.ScrollBarImageColor3 = Color3.fromRGB(58, 58, 68)";
                $L[] = "{$v}.ScrollingDirection = Enum.ScrollingDirection.Y";
                $L[] = "{$v}.ElasticBehavior = Enum.ElasticBehavior.Never";
                $L[] = "{$v}.ScrollingEnabled = true";
                $L[] = "{$v}.BorderSizePixel = 0";
                $L[] = "{$v}.AutomaticCanvasSize = Enum.AutomaticSize.None";
            }

            if ($st['gradient']) {
                $L[] = 'do';
                $L[] = "    local _g = Instance.new(\"UIGradient\")";
                $L[] = "    _g.Rotation = " . round($st['gradient']['rotation'], 1);
                $L[] = "    _g.Color = " . LuaHelper::colorSeq($st['gradient']['keypoints']);
                $L[] = "    _g.Transparency = " . LuaHelper::numSeq($st['gradient']['keypoints']);
                $L[] = "    _g.Parent = {$v}";
                $L[] = 'end';
            }

            if ($t = $st['text']) {
                $f = $t['font'];
                $L[] = "{$v}.Text = " . LuaHelper::q($t['value']);
                if ($t['rich']) $L[] = "{$v}.RichText = true";
                $L[] = "{$v}.TextColor3 = Color3.new(" . LuaHelper::c3($t['color']) . ')';
                $L[] = "{$v}.TextTransparency = " . $t['transparency'];
                $L[] = "{$v}.TextSize = {$t['size']}";
                $L[] = "{$v}.FontFace = Font.new(" . LuaHelper::q($f['url']) . ", Enum.FontWeight.{$f['weight']}, Enum.FontStyle.{$f['style']})";
                $L[] = "{$v}.TextXAlignment = Enum.TextXAlignment.{$t['alignX']}";
                $L[] = "{$v}.TextYAlignment = Enum.TextYAlignment.{$t['alignY']}";
                $L[] = "{$v}.TextWrapped = " . ($t['wrapped'] ? 'true' : 'false');
                if ($n['robloxClass'] === 'TextBox') {
                    $L[] = "{$v}.PlaceholderText = " . LuaHelper::q($t['placeholder']);
                    $L[] = "{$v}.ClearTextOnFocus = false";
                }
                if ($n['robloxClass'] === 'TextButton') $L[] = "{$v}.AutoButtonColor = false";
            }

            if (!empty($n['layoutOrder'])) $L[] = "{$v}.LayoutOrder = {$n['layoutOrder']}";
            if (!empty($n['layout'])) {
                foreach (self::layoutLines($v, $n['layout']) as $line) $L[] = $line;
            }

            if ($p = $st['padding']) {
                $L[] = 'do';
                $L[] = "    local _p = Instance.new(\"UIPadding\")";
                $L[] = "    _p.PaddingLeft = UDim.new(0, {$p['L']})";
                $L[] = "    _p.PaddingRight = UDim.new(0, {$p['R']})";
                $L[] = "    _p.PaddingTop = UDim.new(0, {$p['T']})";
                $L[] = "    _p.PaddingBottom = UDim.new(0, {$p['B']})";
                $L[] = "    _p.Parent = {$v}";
                $L[] = 'end';
            }

            if ($n['robloxClass'] === 'ImageLabel') {
                $L[] = "{$v}.Image = " . LuaHelper::q($n['src'] ?? '');
                $L[] = "{$v}.ScaleType = Enum.ScaleType.Crop";
            }

            // Border CSS → UIStroke (ikut lengkungan UICorner, beda dengan BorderSizePixel)
            if ($s = $st['stroke']) {
                $L[] = 'do';
                $L[] = "    local _s = Instance.new(\"UIStroke\")";
                $L[] = "    _s.ApplyStrokeMode = Enum.ApplyStrokeMode.Border";
                $L[] = "    _s.Color = Color3.new(" . LuaHelper::c3($s['color']) . ')';
                $L[] = "    _s.Thickness = {$s['thickness']}";
                $L[] = "    _s.Transparency = {$s['transparency']}";
                $L[] = "    _s.Parent = {$v}";
                $L[] = 'end';
            }

            if ($st['cornerRadius'] > 0) {
                $L[] = 'do';
                $L[] = "    local _c = Instance.new(\"UICorner\")";
                $L[] = "    _c.CornerRadius = UDim.new(0, {$st['cornerRadius']})";
                $L[] = "    _c.Parent = {$v}";
                $L[] = 'end';
            }

            if (!$n['parentId'] && $ui['singleRoot']) {
                $L[] = "{$v}.AnchorPoint = Vector2.new(0.5, 0.5)";
                $L[] = "{$v}.Position = UDim2.new(0.5, 0, 0.5, 0)";
                $L[] = "{$v}.Parent = screenGui";
                $L[] = "canvas = {$v}";
            } else {
                $L[] = "{$v}.Parent = " . ($n['parentId'] ? $vmap[$n['parentId']] : 'canvas');
            }
            $L[] = '';
        }

        foreach (self::autoScaleLines($W, $H) as $line) $L[] = $line;

        $L[] = '-- BEHAVIOR';
        $L[] = self::generateInlineBehavior($sorted, $vmap, $W, $H);
        $L[] = '';
        $L[] = '-- Guard: initial tab visibility';
        $L[] = 'for _, g in ipairs(canvas:GetDescendants()) do';
        $L[] = '    if g:IsA("GuiObject") and g.Name:match("^ContentLibrary$") then';
        $L[] = '        g.Visible = true';
        $L[] = '    end';
        $L[] = 'end';
        $L[] = '';
        $L[] = 'print("[ArrStudio] GeneratedUI loaded — ' . count($nodes) . ' instances")';
        return implode("\n", $L);
    }

    private static function isDescendantOf($node, $ancestorId, $allNodes) {
        $cur = $node;
        while (!empty($cur['parentId'])) {
            if ($cur['parentId'] === $ancestorId) return true;
            $found = null;
            foreach ($allNodes as $n) {
                if ($n['id'] === $cur['parentId']) { $found = $n; break; }
            }
            if (!$found) break;
            $cur = $found;
        }
        return false;
    }

    private static function sortByDepth($nodes) {
        $idToNode = [];
        foreach ($nodes as $n) $idToNode[$n['id']] = $n;
        $depth = function($n) use ($idToNode) {
            $d = 0; $cur = $n;
            while (!empty($cur['parentId'])) {
                $cur = $idToNode[$cur['parentId']] ?? null;
                if (!$cur) break;
                $d++;
            }
            return $d;
        };
        usort($nodes, function($a, $b) use ($depth) {
            $da = $depth($a); $db = $depth($b);
            if ($da !== $db) return $da - $db;
            return $a['id'] - $b['id'];
        });
        return $nodes;
    }

    private static function generateInlineBehavior($nodes, $vmap, $W = 800, $H = 600) {
        $L = [];
        ['pairs' => $pairs, 'buttons' => $buttons, 'usedBtn' => $usedBtn] = LuaGenerator::detectPairs($nodes);

        $pairByBtn = [];
        foreach ($pairs as $pair) {
            $bid = $pair['btn']['id'];
            if (!isset($pairByBtn[$bid])) $pairByBtn[$bid] = ['btn' => $pair['btn'], 'panels' => []];
            $pairByBtn[$bid]['panels'][] = $pair['panel'];
        }

        foreach ($pairByBtn as $group) {
            $btn = $group['btn'];
            $bv = $vmap[$btn['id']];
            $isOpenVar = 'isOpen_' . $btn['id'];

            $L[] = "-- Toggle: {$btn['name']}";
            $L[] = "local {$isOpenVar} = false";
            $L[] = '';

            foreach ($group['panels'] as $panel) {
                $pv = $vmap[$panel['id']];
                $dur = $panel['transition']['duration'] ?? 0.35;
                $ease = LuaHelper::mapEasing($panel['transition']['easing'] ?? 'ease-out');
                $openX = min($panel['x'], $W - ($panel['w'] ?? 0));
                if ($openX < 0) $openX = 0;
                $openY = $panel['y'];
                $closedX = $openX + 30;
                if ($closedX + ($panel['w'] ?? 0) > $W) {
                    $closedX = $W - ($panel['w'] ?? 0) + 30;
                }
                $tweenVar = 'tween_' . $panel['id'];

                $L[] = "local {$tweenVar} = TweenInfo.new({$dur}, {$ease})";
                $L[] = "local {$pv}_openPos   = UDim2.new(0, {$openX}, 0, {$openY})";
                $L[] = "local {$pv}_closedPos = UDim2.new(0, {$closedX}, 0, {$openY})";
                $L[] = "{$pv}.Position = {$pv}_closedPos";
                $L[] = "{$pv}.Visible = false";
                $L[] = '';
            }

            $L[] = "{$bv}.MouseButton1Click:Connect(function()";
            $L[] = "    {$isOpenVar} = not {$isOpenVar}";

            foreach ($group['panels'] as $panel) {
                $pv = $vmap[$panel['id']];
                $bgTarget = NodeStyle::resolve($panel)['bgTransparency'];
                $tweenVar = 'tween_' . $panel['id'];

                $L[] = "    if {$isOpenVar} then";
                $L[] = "        {$pv}.Visible = true";
                $L[] = "        TweenService:Create({$pv}, {$tweenVar}, {Position = {$pv}_openPos, BackgroundTransparency = {$bgTarget}}):Play()";
                $L[] = "    else";
                $L[] = "        local t = TweenService:Create({$pv}, {$tweenVar}, {Position = {$pv}_closedPos, BackgroundTransparency = 1})";
                $L[] = "        t:Play()";
                $L[] = "        t.Completed:Wait()";
                $L[] = "        {$pv}.Visible = false";
                $L[] = "    end";
            }
            $L[] = "end)";
            $L[] = '';
        }

        foreach ($nodes as $n) {
            if (empty($n['transition']) || (!in_array($n['robloxClass'], ['TextButton','ImageButton']) && empty($n['isButtonLike']))) continue;
            if (!isset($vmap[$n['id']])) continue;
            $v = $vmap[$n['id']];
            $dur = $n['transition']['duration'];
            $ease = LuaHelper::mapEasing($n['transition']['easing']);
            $baseX = $n['x'];
            $baseY = $n['y'];

            $L[] = "{$v}.MouseEnter:Connect(function()";
            $L[] = "    TweenService:Create({$v}, TweenInfo.new({$dur}, {$ease}), {Position = UDim2.new(0, " . ($baseX - 3) . ", 0, {$baseY})}):Play()";
            $L[] = "end)";
            $L[] = "{$v}.MouseLeave:Connect(function()";
            $L[] = "    TweenService:Create({$v}, TweenInfo.new({$dur}, {$ease}), {Position = UDim2.new(0, {$baseX}, 0, {$baseY})}):Play()";
            $L[] = "end)";
        }

        $remaining = array_filter($buttons, fn($b) => !isset($usedBtn[$b['id']]));
        if (!empty($remaining)) {
            $L[] = '';
            $L[] = '-- ============================================================';
            $L[] = '-- INTERACTIVE BUTTONS';
            $L[] = '-- ============================================================';
            $L[] = '';

            foreach ($remaining as $n) {
                $v = $vmap[$n['id']] ?? null;
                if (!$v) continue;

                $action = $n['action'] ?? '';
                $target = $n['target'] ?? '';

                if ($action === 'toggle' && $target !== '') {
                    $L[] = "-- Toggle fallback: {$n['name']} -> {$target}";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    local tgt = findByNormName(canvas, '{$target}')";
                    $L[] = "    if tgt then tgt.Visible = not tgt.Visible end";
                    $L[] = "end)";

                } elseif ($action === 'close' && $target !== '') {
                    $L[] = "-- Close: {$n['name']} -> {$target}";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    local tgt = findByNormName(canvas, '{$target}')";
                    $L[] = "    if tgt then tgt.Visible = false end";
                    $L[] = "end)";

                } elseif ($action === 'switch' && !empty($n['isToggleSwitch'])) {
                    $isOn = !empty($n['isOn']);
                    $L[] = "-- Toggle Switch: {$n['name']}";
                    $L[] = "local {$v}_on = " . ($isOn ? 'true' : 'false');
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    {$v}_on = not {$v}_on";
                    $L[] = "    if {$v}_on then";
                    $L[] = "        TweenService:Create({$v}, TweenInfo.new(0.2), {BackgroundColor3 = Color3.fromRGB(124,58,237)}):Play()";
                    $L[] = "    else";
                    $L[] = "        TweenService:Create({$v}, TweenInfo.new(0.2), {BackgroundColor3 = Color3.fromRGB(48,49,58)}):Play()";
                    $L[] = "    end";
                    $L[] = "end)";

                } elseif ($action === 'tab' && $target !== '') {
                    $L[] = "-- Tab: {$n['name']} -> {$target}";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    for _, g in ipairs(canvas:GetDescendants()) do";
                    $L[] = "        if g:IsA('GuiObject') and g.Name:match('^Content') then";
                    $L[] = "            g.Visible = false";
                    $L[] = "        end";
                    $L[] = "    end";
                    $L[] = "    local tgt = findByNormName(canvas, '{$target}')";
                    $L[] = "    if tgt then tgt.Visible = true end";
                    $L[] = "end)";

                } elseif ($action === 'select') {
                    $L[] = "-- Select: {$n['name']}";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    print('selected: {$n['name']}')";
                    $L[] = "end)";

                } elseif ($action === 'play') {
                    $L[] = "-- Play/Pause: {$n['name']}";
                    $L[] = "local {$v}_playing = false";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    {$v}_playing = not {$v}_playing";
                    $L[] = "    {$v}.Text = {$v}_playing and '⏸' or '▶'";
                    $L[] = "end)";

                } else {
                    $L[] = "-- Button: {$n['name']}";
                    $L[] = "{$v}.MouseButton1Click:Connect(function()";
                    $L[] = "    print('clicked: {$n['name']}')";
                    $L[] = "end)";
                }
                $L[] = '';
            }
        }

        // Handle toggle switch (standalone)
        $switches = array_filter($nodes, fn($n) => ($n['action'] ?? '') === 'switch' && !empty($n['isToggleSwitch']));
        if (!empty($switches)) {
            $L[] = '-- ============================================================';
            $L[] = '-- TOGGLE SWITCHES';
            $L[] = '-- ============================================================';
            foreach ($switches as $sw) {
                $sv = $vmap[$sw['id']] ?? null;
                if (!$sv) continue;
                if (isset($usedBtn[$sw['id']])) continue;
                $isOn = !empty($sw['isOn']);
                $L[] = "-- Switch: {$sw['name']}";
                $L[] = "local {$sv}_on = " . ($isOn ? 'true' : 'false');
                $L[] = "{$sv}.MouseButton1Click:Connect(function()";
                $L[] = "    {$sv}_on = not {$sv}_on";
                $L[] = "    if {$sv}_on then";
                $L[] = "        TweenService:Create({$sv}, TweenInfo.new(0.2), {BackgroundColor3 = Color3.fromRGB(124,58,237)}):Play()";
                $L[] = "    else";
                $L[] = "        TweenService:Create({$sv}, TweenInfo.new(0.2), {BackgroundColor3 = Color3.fromRGB(48,49,58)}):Play()";
                $L[] = "    end";
                $L[] = "end)";
                $L[] = '';
            }
        }

        // Backdrop close
        $backdropClose = array_filter($nodes, fn($n) => ($n['action'] ?? '') === 'close' && !empty($n['selfHidden']));
        if (!empty($backdropClose)) {
            $L[] = '-- ============================================================';
            $L[] = '-- BACKDROP CLOSE';
            $L[] = '-- ============================================================';
            foreach ($backdropClose as $bn) {
                $bv = $vmap[$bn['id']] ?? null;
                if (!$bv) continue;
                $tgt = $bn['target'] ?? '';
                if ($tgt === '') continue;
                $L[] = "-- Backdrop: {$bn['name']} -> {$tgt}";
                $L[] = "{$bv}.MouseButton1Click:Connect(function()";
                $L[] = "    local tgt = findByNormName(canvas, '{$tgt}')";
                $L[] = "    if tgt then tgt.Visible = false end";
                $L[] = "end)";
                $L[] = '';
            }
        }

        return implode("\n", $L);
    }

    private static function autoScaleLines($W, $H): array {
        $L = [];
        $L[] = '-- ============================================================';
        $L[] = '--  AUTO-SCALE: bikin canvas pas di layar apapun';
        $L[] = '--  Skala max = 1 (nggak pernah kegedean dari ukuran desain)';
        $L[] = '-- ============================================================';
        $L[] = 'local uiScale = Instance.new("UIScale")';
        $L[] = 'uiScale.Name = "AutoScale"';
        $L[] = 'uiScale.Parent = canvas';
        $L[] = '';
        $L[] = 'local function updateScale()';
        $L[] = '    local camera = workspace.CurrentCamera';
        $L[] = '    if not camera then return end';
        $L[] = '    local vp = camera.ViewportSize';
        $L[] = '    -- Margin 10% biar nggak nempel pinggir';
        $L[] = "    local scaleX = (vp.X * 0.9) / {$W}";
        $L[] = "    local scaleY = (vp.Y * 0.9) / {$H}";
        $L[] = '    local s = math.min(scaleX, scaleY, 1)  -- max 1, nggak kegedean';
        $L[] = '    uiScale.Scale = s';
        $L[] = 'end';
        $L[] = '';
        $L[] = 'updateScale()';
        $L[] = '';
        $L[] = 'local camera = workspace.CurrentCamera';
        $L[] = 'if camera then';
        $L[] = '    camera:GetPropertyChangedSignal("ViewportSize"):Connect(updateScale)';
        $L[] = 'end';
        $L[] = '';
        return $L;
    }

    /** UIListLayout / UIGridLayout + UIPadding dari LayoutDetector */
    private static function layoutLines(string $v, array $lay): array {
        $L = ['do'];
        if ($lay['type'] === 'grid') {
            $L[] = '    local _l = Instance.new("UIGridLayout")';
            $L[] = "    _l.CellSize = UDim2.new(0, {$lay['cell'][0]}, 0, {$lay['cell'][1]})";
            $L[] = "    _l.CellPadding = UDim2.new(0, {$lay['gap'][0]}, 0, {$lay['gap'][1]})";
            $L[] = "    _l.FillDirectionMaxCells = {$lay['maxCols']}";
        } else {
            $L[] = '    local _l = Instance.new("UIListLayout")';
            $L[] = "    _l.FillDirection = Enum.FillDirection.{$lay['direction']}";
            $L[] = "    _l.Padding = UDim.new(0, {$lay['gap']})";
            $L[] = "    _l.HorizontalAlignment = Enum.HorizontalAlignment.{$lay['hAlign']}";
            $L[] = "    _l.VerticalAlignment = Enum.VerticalAlignment.{$lay['vAlign']}";
        }
        $L[] = '    _l.SortOrder = Enum.SortOrder.LayoutOrder';
        $L[] = "    _l.Parent = {$v}";
        $p = $lay['padding'];
        if (array_sum($p) > 0) {
            $L[] = '    local _lp = Instance.new("UIPadding")';
            $L[] = "    _lp.PaddingLeft = UDim.new(0, {$p['L']})";
            $L[] = "    _lp.PaddingTop = UDim.new(0, {$p['T']})";
            $L[] = "    _lp.PaddingRight = UDim.new(0, {$p['R']})";
            $L[] = "    _lp.PaddingBottom = UDim.new(0, {$p['B']})";
            $L[] = "    _lp.Parent = {$v}";
        }
        $L[] = 'end';
        return $L;
    }
}
