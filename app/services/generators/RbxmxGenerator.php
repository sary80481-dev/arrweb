<?php
// app/services/generators/RbxmxGenerator.php — model .rbxmx siap import
class RbxmxGenerator {

    /** Enum.TextXAlignment / Enum.TextYAlignment → nilai token */
    private const TEXT_X = ['Left' => 0, 'Right' => 1, 'Center' => 2];
    private const TEXT_Y = ['Top' => 0, 'Center' => 1, 'Bottom' => 2];

    /**
     * $ui: ['name' => nama container, 'gui' => nama ScreenGui, 'singleRoot' => bool]
     * singleRoot = root UI sendiri jadi container (tanpa Frame "Canvas" tambahan)
     */
    public static function generate($nodes, $W = 800, $H = 600, array $ui = [], array $logic = []) {
        $ui += ['name' => 'Canvas', 'gui' => 'GeneratedUI', 'singleRoot' => false];
        $refCounter = 0;
        $newRef = function() use (&$refCounter) {
            return 'RBX' . strtoupper(base_convert(++$refCounter, 10, 36));
        };

        $behavior = LuaGenerator::generateBehaviorScript($nodes, $ui);

        $out = [];
        $out[] = '<?xml version="1.0" encoding="utf-8"?>';
        $out[] = '<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" version="4">';

        // ArrUIPack: folder per service → plugin "📦 Install Pack" memindahkan otomatis
        $out[] = '  <Item class="Folder" referent="' . $newRef() . '">';
        $out[] = '    <Properties>';
        $out[] = '      <string name="Name">ArrUIPack</string>';
        $out[] = '    </Properties>';
        $out[] = self::stringValue($newRef, 'BacaDulu',
            "Pakai plugin ARRR Studio → klik 📦 Install Pack. Manual: pindahkan isi tiap folder ke service dengan nama yang sama " .
            "(StarterGui, ReplicatedStorage, ServerScriptService, StarterPlayer > StarterPlayerScripts).");
        $out[] = '  <Item class="Folder" referent="' . $newRef() . '">';
        $out[] = '    <Properties><string name="Name">StarterGui</string></Properties>';

        $out[] = '    <Item class="ScreenGui" referent="' . $newRef() . '">';
        $out[] = '      <Properties>';
        $out[] = '        <string name="Name">' . self::x($ui['gui']) . '</string>';
        $out[] = '        <bool name="ResetOnSpawn">false</bool>';
        $out[] = '        <bool name="IgnoreGuiInset">true</bool>';
        $out[] = '        <token name="ZIndexBehavior">0</token>';
        $out[] = '      </Properties>';

        $out[] = '      <Item class="Script" referent="' . $newRef() . '">';
        $out[] = '        <Properties>';
        $out[] = '          <string name="Name">GeneratedUI_Behavior</string>';
        $out[] = '          <bool name="Disabled">false</bool>';
        $out[] = '          <token name="RunContext">2</token>';
        $out[] = '          <ProtectedString name="Source"><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $behavior) . ']]></ProtectedString>';
        $out[] = '        </Properties>';
        $out[] = '      </Item>';

        // Container utama hanya dibuat kalau root UI lebih dari satu
        if (!$ui['singleRoot']) {
            $out[] = '      <Item class="Frame" referent="' . $newRef() . '">';
            $out[] = '        <Properties>';
            $out[] = '          <string name="Name">' . self::x($ui['name']) . '</string>';
            $out[] = '          <float name="BackgroundTransparency">1</float>';
            $out[] = "          <UDim2 name=\"Size\"><XS>0</XS><XO>{$W}</XO><YS>0</YS><YO>{$H}</YO></UDim2>";
            $out[] = "          <UDim2 name=\"Position\"><XS>0.5</XS><XO>-" . ($W/2) . "</XO><YS>0.5</YS><YO>-" . ($H/2) . "</YO></UDim2>";
            $out[] = '          <int name="BorderSizePixel">0</int>';
            $out[] = '          <bool name="Active">false</bool>';
            $out[] = '          <bool name="ClipsDescendants">false</bool>';
            $out[] = '        </Properties>';

            $out[] = '        <Item class="UIScale" referent="' . $newRef() . '">';
            $out[] = '          <Properties>';
            $out[] = '            <string name="Name">UIScale</string>';
            $out[] = '            <float name="Scale">1</float>';
            $out[] = '          </Properties>';
            $out[] = '        </Item>';
        }

        $byParent = [];
        foreach ($nodes as $n) {
            $key = $n['parentId'] === null ? 'root' : (string)$n['parentId'];
            $byParent[$key][] = $n;
        }

        $writeNode = function($n, $indent, bool $isContainer = false) use (&$writeNode, &$byParent, &$out, &$newRef) {
            $pad = str_repeat('  ', $indent);
            $st  = NodeStyle::resolve($n);
            // Closure biasa (bukan arrow fn): arrow fn menangkap $out by value → baris hilang
            $p   = function (string $line) use (&$out, $pad) { $out[] = "{$pad}    {$line}"; };

            $out[] = "{$pad}<Item class=\"{$n['robloxClass']}\" referent=\"" . $newRef() . '">';
            $out[] = "{$pad}  <Properties>";
            $p('<string name="Name">' . self::x($n['name']) . '</string>');
            if ($isContainer) {
                // Root = container: di tengah layar
                $p('<Vector2 name="AnchorPoint"><X>0.5</X><Y>0.5</Y></Vector2>');
                $p('<UDim2 name="Position"><XS>0.5</XS><XO>0</XO><YS>0.5</YS><YO>0</YO></UDim2>');
            } else {
                $p("<UDim2 name=\"Position\"><XS>0</XS><XO>{$n['x']}</XO><YS>0</YS><YO>{$n['y']}</YO></UDim2>");
            }
            $p("<UDim2 name=\"Size\"><XS>0</XS><XO>{$n['w']}</XO><YS>0</YS><YO>{$n['h']}</YO></UDim2>");
            if (abs($n['rotation'] ?? 0) > 0.01) $p('<float name="Rotation">' . round($n['rotation'], 1) . '</float>');
            $p(self::color3('BackgroundColor3', $st['bgColor']));
            $p('<float name="BackgroundTransparency">' . $st['bgTransparency'] . '</float>');
            $p('<int name="BorderSizePixel">0</int>');
            if ($st['clips'])          $p('<bool name="ClipsDescendants">true</bool>');
            if (!empty($n['selfHidden'])) $p('<bool name="Visible">false</bool>');
            if (!empty($n['layoutOrder'])) $p('<int name="LayoutOrder">' . (int)$n['layoutOrder'] . '</int>');

            if ($t = $st['text']) {
                $f = $t['font'];
                $p('<string name="Text">' . self::x($t['value']) . '</string>');
                $p('<bool name="RichText">' . ($t['rich'] ? 'true' : 'false') . '</bool>');
                $p(self::color3('TextColor3', $t['color']));
                $p('<float name="TextTransparency">' . $t['transparency'] . '</float>');
                $p("<float name=\"TextSize\">{$t['size']}</float>");
                $p("<Font name=\"FontFace\"><Family><url>{$f['url']}</url></Family><Weight>{$f['weightNum']}</Weight><Style>{$f['style']}</Style></Font>");
                $p('<token name="TextXAlignment">' . self::TEXT_X[$t['alignX']] . '</token>');
                $p('<token name="TextYAlignment">' . self::TEXT_Y[$t['alignY']] . '</token>');
                $p('<bool name="TextWrapped">' . ($t['wrapped'] ? 'true' : 'false') . '</bool>');
                $p('<bool name="TextScaled">false</bool>');
                if ($n['robloxClass'] === 'TextButton') $p('<bool name="AutoButtonColor">false</bool>');
                if ($n['robloxClass'] === 'TextBox') {
                    $p('<string name="PlaceholderText">' . self::x($t['placeholder']) . '</string>');
                    $p('<bool name="ClearTextOnFocus">false</bool>');
                }
            }
            if ($n['robloxClass'] === 'ImageLabel') {
                $p('<Content name="Image"><url>' . self::x($n['src'] ?? '') . '</url></Content>');
                $p('<token name="ScaleType">4</token>'); // Crop ≈ object-fit: cover
            }
            $out[] = "{$pad}  </Properties>";

            // ==== Child modifier (UICorner, UIStroke, UIGradient, UIPadding) ====
            $modifier = function (string $class, array $props) use (&$out, &$newRef, $pad) {
                $out[] = "{$pad}  <Item class=\"{$class}\" referent=\"" . $newRef() . '">';
                $out[] = "{$pad}    <Properties>";
                $out[] = "{$pad}      <string name=\"Name\">{$class}</string>";
                foreach ($props as $line) $out[] = "{$pad}      {$line}";
                $out[] = "{$pad}    </Properties>";
                $out[] = "{$pad}  </Item>";
            };

            if ($isContainer) {
                $modifier('UIScale', ['<float name="Scale">1</float>']);
            }

            if ($lay = $n['layout'] ?? null) {
                if ($lay['type'] === 'grid') {
                    $modifier('UIGridLayout', [
                        "<UDim2 name=\"CellSize\"><XS>0</XS><XO>{$lay['cell'][0]}</XO><YS>0</YS><YO>{$lay['cell'][1]}</YO></UDim2>",
                        "<UDim2 name=\"CellPadding\"><XS>0</XS><XO>{$lay['gap'][0]}</XO><YS>0</YS><YO>{$lay['gap'][1]}</YO></UDim2>",
                        "<int name=\"FillDirectionMaxCells\">{$lay['maxCols']}</int>",
                        '<token name="SortOrder">2</token>',
                    ]);
                } else {
                    $modifier('UIListLayout', [
                        '<token name="FillDirection">' . ($lay['direction'] === 'Vertical' ? 1 : 0) . '</token>',
                        "<UDim name=\"Padding\"><S>0</S><O>{$lay['gap']}</O></UDim>",
                        '<token name="HorizontalAlignment">' . ['Center' => 0, 'Left' => 1, 'Right' => 2][$lay['hAlign']] . '</token>',
                        '<token name="VerticalAlignment">' . ['Center' => 0, 'Top' => 1, 'Bottom' => 2][$lay['vAlign']] . '</token>',
                        '<token name="SortOrder">2</token>',
                    ]);
                }
                $lp = $lay['padding'];
                if (array_sum($lp) > 0) {
                    $modifier('UIPadding', [
                        "<UDim name=\"PaddingLeft\"><S>0</S><O>{$lp['L']}</O></UDim>",
                        "<UDim name=\"PaddingTop\"><S>0</S><O>{$lp['T']}</O></UDim>",
                        "<UDim name=\"PaddingRight\"><S>0</S><O>{$lp['R']}</O></UDim>",
                        "<UDim name=\"PaddingBottom\"><S>0</S><O>{$lp['B']}</O></UDim>",
                    ]);
                }
            }

            if ($st['cornerRadius'] > 0) {
                $modifier('UICorner', ["<UDim name=\"CornerRadius\"><S>0</S><O>{$st['cornerRadius']}</O></UDim>"]);
            }

            if ($s = $st['stroke']) {
                $modifier('UIStroke', [
                    '<token name="ApplyStrokeMode">1</token>', // Border
                    self::color3('Color', $s['color']),
                    "<float name=\"Thickness\">{$s['thickness']}</float>",
                    "<float name=\"Transparency\">{$s['transparency']}</float>",
                ]);
            }

            if ($g = $st['gradient']) {
                // Format rbxmx: "time r g b 0" per keypoint / "time value envelope"
                $colors = $alphas = '';
                foreach ($g['keypoints'] as $kp) {
                    $t = round(max(0, min(1, $kp['pos'])), 4);
                    $colors .= sprintf('%s %s %s %s 0 ', $t, round($kp['color']['r'], 4), round($kp['color']['g'], 4), round($kp['color']['b'], 4));
                    $alphas .= sprintf('%s %s 0 ', $t, round(1 - $kp['color']['a'], 4));
                }
                $modifier('UIGradient', [
                    '<float name="Rotation">' . round($g['rotation'], 1) . '</float>',
                    "<ColorSequence name=\"Color\">{$colors}</ColorSequence>",
                    "<NumberSequence name=\"Transparency\">{$alphas}</NumberSequence>",
                ]);
            }

            if ($pd = $st['padding']) {
                $modifier('UIPadding', [
                    "<UDim name=\"PaddingLeft\"><S>0</S><O>{$pd['L']}</O></UDim>",
                    "<UDim name=\"PaddingRight\"><S>0</S><O>{$pd['R']}</O></UDim>",
                    "<UDim name=\"PaddingTop\"><S>0</S><O>{$pd['T']}</O></UDim>",
                    "<UDim name=\"PaddingBottom\"><S>0</S><O>{$pd['B']}</O></UDim>",
                ]);
            }

            foreach ($byParent[(string)$n['id']] ?? [] as $c) $writeNode($c, $indent + 1);

            $out[] = "{$pad}</Item>";
        };

        $rootChildren = $byParent['root'] ?? [];
        if ($ui['singleRoot']) {
            foreach ($rootChildren as $c) $writeNode($c, 3, true);
        } else {
            foreach ($rootChildren as $c) $writeNode($c, 6);
            $out[] = '      </Item>';
        }

        $out[] = '    </Item>';
        $out[] = '  </Item>';   // /StarterGui

        // Game Logic (kalau ada)
        if (!empty($logic['module'])) {
            $out[] = self::serviceFolder($newRef, 'ReplicatedStorage',
                '<Item class="Folder" referent="' . $newRef() . '"><Properties><string name="Name">ArrUI</string></Properties>'
                . self::script($newRef, 'ModuleScript', 'GameConfig', $logic['module']) . '</Item>');
            $out[] = self::serviceFolder($newRef, 'ServerScriptService',
                self::script($newRef, 'Script', 'ArrUIServer', $logic['server']));
            $out[] = self::serviceFolder($newRef, 'StarterPlayerScripts',
                self::script($newRef, 'LocalScript', 'ArrUIClient', $logic['client']));
        }

        $out[] = '  </Item>';   // /ArrUIPack
        $out[] = '</roblox>';

        return implode("\n", $out);
    }

    private static function color3(string $name, array $c): string {
        return "<Color3 name=\"{$name}\"><R>" . round($c['r'], 4) . '</R><G>' . round($c['g'], 4) . '</G><B>' . round($c['b'], 4) . '</B></Color3>';
    }

    private static function x($s): string {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function script(callable $newRef, string $class, string $name, string $source): string {
        return '<Item class="' . $class . '" referent="' . $newRef() . '"><Properties>'
            . '<string name="Name">' . self::x($name) . '</string>'
            . '<ProtectedString name="Source"><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $source) . ']]></ProtectedString>'
            . '</Properties></Item>';
    }

    private static function serviceFolder(callable $newRef, string $name, string $inner): string {
        return '  <Item class="Folder" referent="' . $newRef() . '"><Properties><string name="Name">' . $name . '</string></Properties>'
            . "\n    " . $inner . "\n  </Item>";
    }

    private static function stringValue(callable $newRef, string $name, string $value): string {
        return '  <Item class="StringValue" referent="' . $newRef() . '"><Properties>'
            . '<string name="Name">' . self::x($name) . '</string><string name="Value">' . self::x($value) . '</string>'
            . '</Properties></Item>';
    }
}
