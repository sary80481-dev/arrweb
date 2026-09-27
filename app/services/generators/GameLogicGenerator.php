<?php
// app/services/generators/GameLogicGenerator.php — spesifikasi GuiAnalyzer → 3 file Roblox:
//   1. ReplicatedStorage/ArrUI/GameConfig   (ModuleScript) — data & remote bersama
//   2. ServerScriptService/ArrUIServer      (Script)       — remote, leaderstats, handler aman
//   3. StarterPlayerScripts/ArrUIClient     (LocalScript)  — sambungkan tombol GUI ke server

class GameLogicGenerator
{
    public static function generate(array $nodes, ?array $spec = null, array $ui = []): array
    {
        $spec ??= (new GuiAnalyzer())->analyze($nodes);
        $spec['gui'] = $ui['gui'] ?? 'GeneratedUI';

        return [
            'module' => self::module($spec),
            'server' => self::server($spec),
            'client' => self::client($spec),
            'spec'   => $spec,
        ];
    }

    // ============================================================
    // 1. MODULE — ReplicatedStorage/ArrUI/GameConfig
    // ============================================================
    private static function module(array $spec): string
    {
        $a = array_flip($spec['actions']);
        $price = fn(string $action) => current(array_filter(array_column(
            array_filter($spec['bindings'], fn($b) => $b['action'] === $action), 'price'))) ?: null;

        $cfg = [
            'ScreenGuiName' => $spec['gui'] ?? 'GeneratedUI',
            'Cooldown'      => 0.4,
            'UseDataStore'  => true,
            'DataStoreName' => 'ArrUI_PlayerData_v1',
            'Currencies'    => $spec['currencies'] ?: new stdClass(),
            'Items'         => $spec['items'] ?: new stdClass(),
            'Remotes'       => $spec['actions'],
        ];
        if (isset($a['Sell']))    $cfg['SellRatio'] = 0.5;
        if (isset($a['Claim']))   $cfg['Claim'] = ['Currency' => self::mainCurrency($spec), 'Amount' => 100, 'CooldownSeconds' => 86400];
        if (isset($a['Upgrade'])) {
            $p = $price('Upgrade');
            $cfg['Upgrade'] = ['Currency' => $p['currency'] ?? self::mainCurrency($spec), 'BaseCost' => $p['amount'] ?? 100, 'CostMultiplier' => 1.5, 'MaxLevel' => 50];
        }
        if (isset($a['Revive'])) {
            $p = $price('Revive');
            $cfg['Revive'] = ['Currency' => $p['currency'] ?? self::mainCurrency($spec), 'Cost' => $p['amount'] ?? 20];
        }
        if (isset($a['Redeem']))  $cfg['Codes'] = ['WELCOME' => ['Currency' => self::mainCurrency($spec), 'Amount' => 250]];
        if (isset($a['Spin']))    $cfg['Spin'] = ['Currency' => $price('Spin')['currency'] ?? self::mainCurrency($spec), 'Cost' => $price('Spin')['amount'] ?? 100, 'Rewards' => [
            ['Weight' => 60, 'Amount' => 50], ['Weight' => 30, 'Amount' => 150], ['Weight' => 10, 'Amount' => 500],
        ]];
        if (isset($a['Craft']))   $cfg['CraftCost'] = 200;
        if ($spec['settings'])    $cfg['Settings'] = $spec['settings'];
        if (isset($a['DialogueChoice'])) {
            $dialogues = [];
            foreach ($spec['bindings'] as $b) {
                if ($b['action'] !== 'DialogueChoice') continue;
                $dialogues[$b['dialogueId']][$b['choice']] = ['Text' => $b['label'], 'Reward' => 0];
            }
            $cfg['Dialogues'] = $dialogues;
        }

        $L = self::header('GameConfig (ModuleScript)', 'ReplicatedStorage > ArrUI > GameConfig', [
            'Data bersama server & client. Ubah harga, hadiah, dan saldo awal di sini.',
            'Server SELALU pakai nilai dari file ini (client tidak bisa curang ubah harga).',
        ]);
        $L[] = 'local GameConfig = ' . self::luaValue($cfg);
        $L[] = '';
        $L[] = '-- Ambil remote (dibuat oleh ArrUIServer di ReplicatedStorage.ArrUI.Remotes)';
        $L[] = 'function GameConfig.getRemote(name)';
        $L[] = '    local root = script.Parent';
        $L[] = '    local folder = root:WaitForChild("Remotes", 15)';
        $L[] = '    return folder and folder:WaitForChild(name, 15)';
        $L[] = 'end';
        $L[] = '';
        $L[] = '-- 12450 → "12,450"';
        $L[] = 'function GameConfig.formatNumber(n)';
        $L[] = '    local s = tostring(math.floor(tonumber(n) or 0))';
        $L[] = '    local formatted = s:reverse():gsub("(%d%d%d)", "%1,"):reverse()';
        $L[] = '    return (formatted:gsub("^,", ""))';
        $L[] = 'end';
        $L[] = '';
        $L[] = 'return GameConfig';
        return implode("\n", $L);
    }

    // ============================================================
    // 2. SERVER — ServerScriptService/ArrUIServer
    // ============================================================
    private static function server(array $spec): string
    {
        $a = array_flip($spec['actions']);

        $L = self::header('ArrUIServer (Script)', 'ServerScriptService > ArrUIServer', [
            'Membuat RemoteFunction, leaderstats, inventory, dan menangani aksi dari GUI.',
            'Semua validasi (harga, saldo, cooldown) dilakukan di server.',
        ]);
        $L[] = <<<'LUA'
local Players           = game:GetService("Players")
local ReplicatedStorage = game:GetService("ReplicatedStorage")
local DataStoreService  = game:GetService("DataStoreService")

local ArrUI  = ReplicatedStorage:WaitForChild("ArrUI")
local Config = require(ArrUI:WaitForChild("GameConfig"))

-- ============================================================
--  REMOTES
-- ============================================================
local remotes = ArrUI:FindFirstChild("Remotes") or Instance.new("Folder")
remotes.Name = "Remotes"
remotes.Parent = ArrUI

local function getRemote(name)
	local r = remotes:FindFirstChild(name)
	if not r then
		r = Instance.new("RemoteFunction")
		r.Name = name
		r.Parent = remotes
	end
	return r
end

-- ============================================================
--  DATA PEMAIN (leaderstats + Inventory + DataStore)
-- ============================================================
local store = nil
if Config.UseDataStore then
	local ok, result = pcall(function() return DataStoreService:GetDataStore(Config.DataStoreName) end)
	if ok then store = result else warn("[ArrUI] DataStore nonaktif:", result) end
end

local function currency(player, name)
	local ls = player:FindFirstChild("leaderstats")
	return ls and ls:FindFirstChild(name)
end

local function inventory(player)
	return player:FindFirstChild("Inventory")
end

local function serialize(player)
	local data = { Currencies = {}, Inventory = {}, Attributes = player:GetAttributes() }
	for name in pairs(Config.Currencies) do
		local v = currency(player, name)
		data.Currencies[name] = v and v.Value or 0
	end
	for _, item in ipairs(inventory(player):GetChildren()) do
		data.Inventory[item.Name] = item.Value
	end
	return data
end

local function setupPlayer(player)
	local saved = nil
	if store then
		local ok, result = pcall(function() return store:GetAsync("p_" .. player.UserId) end)
		if ok then saved = result else warn("[ArrUI] Gagal load data:", result) end
	end
	saved = saved or {}

	local leaderstats = Instance.new("Folder")
	leaderstats.Name = "leaderstats"
	for name, start in pairs(Config.Currencies) do
		local v = Instance.new("IntValue")
		v.Name = name
		v.Value = (saved.Currencies and saved.Currencies[name]) or start
		v.Parent = leaderstats
	end
	leaderstats.Parent = player

	local inv = Instance.new("Folder")
	inv.Name = "Inventory"
	for itemId, count in pairs(saved.Inventory or {}) do
		local v = Instance.new("IntValue")
		v.Name = itemId
		v.Value = count
		v.Parent = inv
	end
	inv.Parent = player

	for key, value in pairs(saved.Attributes or {}) do
		player:SetAttribute(key, value)
	end
end

local function savePlayer(player)
	if not store or not inventory(player) then return end
	local data = serialize(player)
	local ok, err = pcall(function() store:SetAsync("p_" .. player.UserId, data) end)
	if not ok then warn("[ArrUI] Gagal simpan data:", err) end
end

Players.PlayerAdded:Connect(setupPlayer)
for _, p in ipairs(Players:GetPlayers()) do task.spawn(setupPlayer, p) end
Players.PlayerRemoving:Connect(savePlayer)
game:BindToClose(function()
	for _, p in ipairs(Players:GetPlayers()) do savePlayer(p) end
end)

-- ============================================================
--  HELPER
-- ============================================================
local lastCall = {}
local function rateLimited(player, action)
	local key = player.UserId .. ":" .. action
	local now = os.clock()
	if lastCall[key] and now - lastCall[key] < Config.Cooldown then return true end
	lastCall[key] = now
	return false
end
Players.PlayerRemoving:Connect(function(player)
	local prefix = player.UserId .. ":"
	for key in pairs(lastCall) do
		if key:sub(1, #prefix) == prefix then lastCall[key] = nil end
	end
end)

local function spend(player, currencyName, amount)
	local cur = currency(player, currencyName)
	if not cur then return false, "Mata uang " .. tostring(currencyName) .. " tidak ada" end
	if cur.Value < amount then return false, "Saldo " .. currencyName .. " kurang" end
	cur.Value -= amount
	return true
end

local function give(player, currencyName, amount)
	local cur = currency(player, currencyName)
	if cur then cur.Value += amount end
end

local function owned(player, itemId)
	local v = inventory(player):FindFirstChild(itemId)
	return v and v.Value or 0
end

local function addItem(player, itemId, delta)
	local inv = inventory(player)
	local v = inv:FindFirstChild(itemId)
	if not v then
		v = Instance.new("IntValue")
		v.Name = itemId
		v.Parent = inv
	end
	v.Value += delta
	if v.Value <= 0 then v:Destroy() end
end

local function validItem(itemId)
	return typeof(itemId) == "string" and Config.Items[itemId] ~= nil
end

-- ============================================================
--  HANDLER AKSI (return ok:boolean, message:string)
-- ============================================================
local Handlers = {}
LUA;

        if (isset($a['Purchase'])) $L[] = <<<'LUA'

function Handlers.Purchase(player, itemId)
	if not validItem(itemId) then return false, "Item tidak dikenal" end
	local item = Config.Items[itemId]
	local ok, err = spend(player, item.Currency, item.Price)
	if not ok then return false, err end
	addItem(player, itemId, 1)
	return true, "Berhasil beli " .. item.DisplayName
end
LUA;
        if (isset($a['Sell'])) $L[] = <<<'LUA'

function Handlers.Sell(player, itemId)
	if not validItem(itemId) then return false, "Item tidak dikenal" end
	if owned(player, itemId) <= 0 then return false, "Kamu tidak punya item ini" end
	local item = Config.Items[itemId]
	addItem(player, itemId, -1)
	give(player, item.Currency, math.floor(item.Price * Config.SellRatio))
	return true, item.DisplayName .. " terjual"
end
LUA;
        if (isset($a['Equip'])) $L[] = <<<'LUA'

function Handlers.Equip(player, itemId)
	if typeof(itemId) ~= "string" then return false, "Item tidak valid" end
	if Config.Items[itemId] and owned(player, itemId) <= 0 then return false, "Beli item ini dulu" end
	player:SetAttribute("Equipped", itemId)
	return true, "Dipakai"
end
LUA;
        if (isset($a['Unequip'])) $L[] = <<<'LUA'

function Handlers.Unequip(player)
	player:SetAttribute("Equipped", nil)
	return true, "Dilepas"
end
LUA;
        if (isset($a['Claim'])) $L[] = <<<'LUA'

function Handlers.Claim(player)
	local last = player:GetAttribute("LastClaim") or 0
	local wait = Config.Claim.CooldownSeconds - (os.time() - last)
	if wait > 0 then
		return false, string.format("Bisa klaim lagi dalam %dj %dm", wait // 3600, (wait % 3600) // 60)
	end
	player:SetAttribute("LastClaim", os.time())
	give(player, Config.Claim.Currency, Config.Claim.Amount)
	return true, "+" .. Config.Claim.Amount .. " " .. Config.Claim.Currency
end
LUA;
        if (isset($a['Upgrade'])) $L[] = <<<'LUA'

function Handlers.Upgrade(player, itemId)
	local key = "Level_" .. (typeof(itemId) == "string" and itemId or "Player")
	local level = player:GetAttribute(key) or 1
	if level >= Config.Upgrade.MaxLevel then return false, "Sudah level maksimal" end
	local cost = math.floor(Config.Upgrade.BaseCost * Config.Upgrade.CostMultiplier ^ (level - 1))
	local ok, err = spend(player, Config.Upgrade.Currency, cost)
	if not ok then return false, err end
	player:SetAttribute(key, level + 1)
	return true, "Level " .. (level + 1)
end
LUA;
        if (isset($a['Craft'])) $L[] = <<<'LUA'

function Handlers.Craft(player, itemId)
	if not validItem(itemId) then return false, "Resep tidak dikenal" end
	local ok, err = spend(player, Config.Items[itemId].Currency, Config.CraftCost)
	if not ok then return false, err end
	addItem(player, itemId, 1)
	return true, Config.Items[itemId].DisplayName .. " berhasil dibuat"
end
LUA;
        if (isset($a['UseItem'])) $L[] = <<<'LUA'

function Handlers.UseItem(player, itemId)
	if typeof(itemId) ~= "string" or owned(player, itemId) <= 0 then return false, "Item habis" end
	addItem(player, itemId, -1)
	-- TODO: efek item (heal, buff, dll.)
	return true, "Item dipakai"
end
LUA;
        if (isset($a['Redeem'])) $L[] = <<<'LUA'

function Handlers.Redeem(player, code)
	if typeof(code) ~= "string" then return false, "Kode tidak valid" end
	code = code:upper():gsub("%s", "")
	local reward = Config.Codes[code]
	if not reward then return false, "Kode tidak ditemukan" end
	local key = "Code_" .. code
	if player:GetAttribute(key) then return false, "Kode sudah dipakai" end
	player:SetAttribute(key, true)
	give(player, reward.Currency, reward.Amount)
	return true, "+" .. reward.Amount .. " " .. reward.Currency
end
LUA;
        if (isset($a['Spin'])) $L[] = <<<'LUA'

function Handlers.Spin(player)
	local ok, err = spend(player, Config.Spin.Currency, Config.Spin.Cost)
	if not ok then return false, err end
	local total = 0
	for _, r in ipairs(Config.Spin.Rewards) do total += r.Weight end
	local roll = math.random() * total
	for _, r in ipairs(Config.Spin.Rewards) do
		roll -= r.Weight
		if roll <= 0 then
			give(player, Config.Spin.Currency, r.Amount)
			return true, "Dapat " .. r.Amount .. " " .. Config.Spin.Currency
		end
	end
	return true, "Coba lagi!"
end
LUA;
        if (isset($a['AcceptQuest'])) $L[] = <<<'LUA'

function Handlers.AcceptQuest(player, questId)
	if typeof(questId) ~= "string" then return false, "Quest tidak valid" end
	player:SetAttribute("ActiveQuest", questId)
	return true, "Quest diterima"
end
LUA;
        if (isset($a['DeclineQuest'])) $L[] = <<<'LUA'

function Handlers.DeclineQuest(player, questId)
	if player:GetAttribute("ActiveQuest") == questId then player:SetAttribute("ActiveQuest", nil) end
	return true, "Quest ditolak"
end
LUA;
        if (isset($a['Revive'])) $L[] = <<<'LUA'

function Handlers.Revive(player)
	local ok, err = spend(player, Config.Revive.Currency, Config.Revive.Cost)
	if not ok then return false, err end
	local character = player.Character
	local root = character and character:FindFirstChild("HumanoidRootPart")
	local spot = root and root.CFrame
	player:LoadCharacter()
	if spot then
		local newRoot = player.Character and player.Character:WaitForChild("HumanoidRootPart", 5)
		if newRoot then newRoot.CFrame = spot end
	end
	return true, "Bangkit kembali!"
end
LUA;
        if (isset($a['Respawn'])) $L[] = <<<'LUA'

function Handlers.Respawn(player)
	player:LoadCharacter()
	return true, nil
end
LUA;
        if (isset($a['DialogueChoice'])) $L[] = <<<'LUA'

function Handlers.DialogueChoice(player, dialogueId, choice)
	local dialogue = Config.Dialogues[dialogueId]
	local option = dialogue and dialogue[choice]
	if not option then return false, "Pilihan tidak valid" end
	player:SetAttribute("Dialogue_" .. dialogueId, choice)
	-- Pilihan pertama = terima quest (ubah sesuai cerita)
	if choice == 1 then player:SetAttribute("ActiveQuest", dialogueId) end
	if option.Reward and option.Reward > 0 then
		local cur = next(Config.Currencies)
		if cur then give(player, cur, option.Reward) end
	end
	return true, option.Text
end
LUA;
        if (isset($a['SetSetting']) || isset($a['SaveSettings'])) $L[] = <<<'LUA'

function Handlers.SetSetting(player, key, value)
	if typeof(key) ~= "string" or Config.Settings == nil or Config.Settings[key] == nil then
		return false, "Setting tidak dikenal"
	end
	if typeof(value) ~= typeof(Config.Settings[key]) then return false, "Nilai tidak valid" end
	player:SetAttribute("Setting_" .. key, value)
	return true, key .. " = " .. tostring(value)
end

function Handlers.SaveSettings(player)
	task.spawn(savePlayer, player)
	return true, "Pengaturan disimpan"
end
LUA;

        $L[] = <<<'LUA'

-- ============================================================
--  SAMBUNGKAN HANDLER KE REMOTE
-- ============================================================
for _, action in ipairs(Config.Remotes) do
	local handler = Handlers[action]
	if handler then
		getRemote(action).OnServerInvoke = function(player, ...)
			if rateLimited(player, action) then return false, "Terlalu cepat, tunggu sebentar" end
			local ok, success, message = pcall(handler, player, ...)
			if not ok then
				warn("[ArrUI] Error di " .. action .. ":", success)
				return false, "Terjadi kesalahan di server"
			end
			return success, message
		end
	end
end

print("[ArrUI] Server siap — remotes:", table.concat(Config.Remotes, ", "))
LUA;
        return implode("\n", $L);
    }

    // ============================================================
    // 3. CLIENT — StarterPlayerScripts/ArrUIClient
    // ============================================================
    private static function client(array $spec): string
    {
        $bindings = array_map(fn($b) => array_filter([
            'target'   => $b['target'],
            'action'   => $b['action'],
            'kind'     => $b['kind'],
            'itemId'   => $b['itemId'] ?? null,
            'input'    => $b['input'] ?? null,
            'questId'  => $b['questId'] ?? null,
            'dialogue' => $b['dialogueId'] ?? null,
            'choice'   => $b['choice'] ?? null,
            'setting'  => $b['setting'] ?? null,
        ], fn($v) => $v !== null), $spec['bindings']);

        $balances = array_map(fn($b) => [
            'target' => $b['target'], 'currency' => $b['currency'], 'template' => $b['template'],
        ], $spec['balances']);

        $L = self::header('ArrUIClient (LocalScript)', 'StarterPlayer > StarterPlayerScripts > ArrUIClient', [
            'Menyambungkan tombol di GUI ke server + update label saldo otomatis.',
            'Nama target = nama Instance di GUI hasil ArrStudio (bisa diganti).',
        ]);
        $L[] = 'local Players           = game:GetService("Players")';
        $L[] = 'local ReplicatedStorage = game:GetService("ReplicatedStorage")';
        $L[] = 'local TweenService      = game:GetService("TweenService")';
        $L[] = '';
        $L[] = 'local player = Players.LocalPlayer';
        $L[] = 'local Config = require(ReplicatedStorage:WaitForChild("ArrUI"):WaitForChild("GameConfig"))';
        $L[] = 'local gui    = player:WaitForChild("PlayerGui"):WaitForChild(Config.ScreenGuiName, 30)';
        $L[] = 'if not gui then';
        $L[] = '    warn("[ArrUI] ScreenGui " .. Config.ScreenGuiName .. " tidak ditemukan")';
        $L[] = '    return';
        $L[] = 'end';
        $L[] = '';
        $L[] = '-- Tombol/elemen GUI → aksi server';
        $L[] = 'local Bindings = ' . self::luaValue(array_values($bindings));
        $L[] = '';
        $L[] = '-- Label saldo ({n} diganti angka dari leaderstats)';
        $L[] = 'local Balances = ' . self::luaValue(array_values($balances));
        $L[] = '';
        $L[] = <<<'LUA'
-- ============================================================
--  HELPER
-- ============================================================
local function find(name)
	return gui:FindFirstChild(name, true)
end

-- Notifikasi kecil di bawah layar
local function toast(message, ok)
	local label = Instance.new("TextLabel")
	label.AnchorPoint = Vector2.new(0.5, 1)
	label.Position = UDim2.new(0.5, 0, 1, -40)
	label.Size = UDim2.new(0, 0, 0, 36)
	label.AutomaticSize = Enum.AutomaticSize.X
	label.BackgroundColor3 = ok and Color3.fromRGB(22, 101, 52) or Color3.fromRGB(127, 29, 29)
	label.TextColor3 = Color3.new(1, 1, 1)
	label.FontFace = Font.new("rbxasset://fonts/families/GothamSSm.json", Enum.FontWeight.SemiBold)
	label.TextSize = 15
	label.Text = "  " .. tostring(message) .. "  "
	label.ZIndex = 1000
	Instance.new("UICorner", label).CornerRadius = UDim.new(0, 8)
	local pad = Instance.new("UIPadding", label)
	pad.PaddingLeft = UDim.new(0, 12)
	pad.PaddingRight = UDim.new(0, 12)
	label.Parent = gui
	task.delay(1.8, function()
		TweenService:Create(label, TweenInfo.new(0.3), { BackgroundTransparency = 1, TextTransparency = 1 }):Play()
		task.wait(0.35)
		label:Destroy()
	end)
end

-- Element yang bisa diklik: tombol asli, atau overlay transparan untuk Frame/Label
local function clickable(target)
	if target:IsA("GuiButton") then return target end
	local overlay = Instance.new("TextButton")
	overlay.Name = "ArrUI_Click"
	overlay.Size = UDim2.fromScale(1, 1)
	overlay.BackgroundTransparency = 1
	overlay.Text = ""
	overlay.ZIndex = target.ZIndex + 10
	overlay.Parent = target
	return overlay
end

local busy = {}
local function call(action, ...)
	if busy[action] then return end
	busy[action] = true
	local remote = Config.getRemote(action)
	local ok, success, message = pcall(function(...) return remote:InvokeServer(...) end, ...)
	busy[action] = false
	if not ok then
		toast("Server tidak merespon", false)
		return false
	end
	if message then toast(message, success) end
	return success
end

-- ============================================================
--  SAMBUNGKAN BINDINGS
-- ============================================================
local settingsState = table.clone(Config.Settings or {})

for _, b in ipairs(Bindings) do
	local target = find(b.target)
	if not target then
		warn("[ArrUI] Elemen tidak ditemukan:", b.target)
		continue
	end
	local button = clickable(target)

	button.Activated:Connect(function()
		if b.action == "Redeem" then
			local box = b.input and find(b.input)
			call("Redeem", box and box.Text or "")
		elseif b.action == "SetSetting" then
			local value = not settingsState[b.setting]
			if call("SetSetting", b.setting, value) then
				settingsState[b.setting] = value
				-- Geser knob switch (anak pertama) ke kiri/kanan
				local knob = target:FindFirstChildWhichIsA("GuiObject")
				if knob and knob.Name ~= "ArrUI_Click" then
					local x = value and (target.AbsoluteSize.X - knob.AbsoluteSize.X - 2) or 2
					TweenService:Create(knob, TweenInfo.new(0.2), { Position = UDim2.new(0, x, knob.Position.Y.Scale, knob.Position.Y.Offset) }):Play()
				end
			end
		elseif b.action == "DialogueChoice" then
			call("DialogueChoice", b.dialogue, b.choice)
		elseif b.action == "AcceptQuest" or b.action == "DeclineQuest" then
			call(b.action, b.questId)
		else
			call(b.action, b.itemId)
		end
	end)
end

-- ============================================================
--  LABEL SALDO ↔ leaderstats
-- ============================================================
local leaderstats = player:WaitForChild("leaderstats", 30)
if leaderstats then
	for _, bal in ipairs(Balances) do
		local label = find(bal.target)
		local value = leaderstats:WaitForChild(bal.currency, 10)
		if label and value then
			local function refresh()
				label.Text = (bal.template:gsub("{n}", Config.formatNumber(value.Value)))
			end
			value.Changed:Connect(refresh)
			refresh()
		end
	end
end

print("[ArrUI] Client siap —", #Bindings, "binding")
LUA;
        return implode("\n", $L);
    }

    // ============================================================
    // UTIL
    // ============================================================
    private static function mainCurrency(array $spec): string
    {
        return array_key_first($spec['currencies']) ?? 'Coins';
    }

    private static function luaValue($v): string
    {
        if ($v instanceof stdClass) return '{}';
        if (is_array($v)) {
            $v = array_map(fn($x) => $x instanceof stdClass ? [] : $x, $v);
        }
        return LuaHelper::value($v);
    }

    private static function header(string $title, string $location, array $notes): array
    {
        $L = [];
        $L[] = '-- ============================================================';
        $L[] = "--  {$title}";
        $L[] = "--  Lokasi : {$location}";
        foreach ($notes as $n) $L[] = "--  {$n}";
        $L[] = '--  Generated by ArrStudio';
        $L[] = '-- ============================================================';
        $L[] = '';
        return $L;
    }
}
