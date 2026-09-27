<?php
// app/services/generators/PluginGenerator.php — generate plugin Lua untuk ARRR Studio (v4.0 - auto billboard)
// Fitur baru:
// - Auto-detect kalau script pakai BillboardGui
// - Auto-attach ke semua player character di Workspace (Play mode)
// - Edit mode: bikin preview part dummy di Workspace

class PluginGenerator {
    public static function generate() {
        return <<<'LUA'
--!strict
-- ============================================================
--  ARRR Studio Importer v5.0
--  • Build: paste Lua (Full Lua / Billboard) → UI langsung jadi
--  • Install Pack: .rbxmx "ArrUIPack" → GUI + GameConfig + Server + Client
--    otomatis dipindah ke service yang benar
-- ============================================================

local PLUGIN_NAME  = "ARRR Studio Importer"
local TOOLBAR_NAME = "ARRR Studio"
local VERSION      = "5.0.0"

if not plugin then
	warn("[ARRR] Harus dijalankan di Roblox Studio.")
	return
end

local Players    = game:GetService("Players")
local RunService = game:GetService("RunService")
local Workspace  = game:GetService("Workspace")
local ChangeHistoryService = game:GetService("ChangeHistoryService")
local Selection  = game:GetService("Selection")

local toolbar = plugin:CreateToolbar(TOOLBAR_NAME)
local mainButton = toolbar:CreateButton(
	"Import UI",
	"Import UI dari ARRR Studio Converter",
	"rbxasset://textures/ui/GuiImagePlaceholder.png"
)
mainButton.ClickableWhenViewportHidden = true

local widgetInfo = DockWidgetPluginGuiInfo.new(
	Enum.InitialDockState.Float,
	false, false,
	680, 640,
	540, 480
)
local widget = plugin:CreateDockWidgetPluginGui("ArrStudioImporterWidget", widgetInfo)
widget.Title = PLUGIN_NAME

-- ============================================================
--  COLORS
-- ============================================================
local GOLD        = Color3.fromRGB(212, 175, 55)
local GOLD_BRIGHT = Color3.fromRGB(244, 208, 63)
local BG          = Color3.fromRGB(23, 23, 29)
local BG_DARK     = Color3.fromRGB(14, 14, 18)
local HEAD        = Color3.fromRGB(15, 15, 20)
local EDGE        = Color3.fromRGB(38, 38, 46)
local TEXT        = Color3.fromRGB(232, 232, 236)
local TEXT_DIM    = Color3.fromRGB(138, 138, 150)
local TEXT_MUTE   = Color3.fromRGB(90, 90, 102)
local GREEN       = Color3.fromRGB(34, 197, 94)
local RED         = Color3.fromRGB(239, 68, 68)
local YELLOW      = Color3.fromRGB(245, 158, 11)
local BLUE        = Color3.fromRGB(96, 165, 250)

-- ============================================================
--  HELPERS
-- ============================================================
local function create(class, props, children)
	local inst = Instance.new(class)
	for k, v in pairs(props or {}) do
		inst[k] = v
	end
	for _, c in ipairs(children or {}) do
		c.Parent = inst
	end
	return inst
end

local function corner(radius)
	return create("UICorner", { CornerRadius = UDim.new(0, radius or 8) })
end

local function padding(t, r, b, l)
	return create("UIPadding", {
		PaddingTop = UDim.new(0, t),
		PaddingRight = UDim.new(0, r),
		PaddingBottom = UDim.new(0, b),
		PaddingLeft = UDim.new(0, l),
	})
end

local function stroke(color, thickness, transparency)
	return create("UIStroke", {
		Color = color or EDGE,
		Thickness = thickness or 1,
		Transparency = transparency or 0,
		ApplyStrokeMode = Enum.ApplyStrokeMode.Border,
	})
end

-- ============================================================
--  ROOT
-- ============================================================
local root = create("Frame", {
	Name = "Root",
	Size = UDim2.fromScale(1, 1),
	BackgroundColor3 = BG,
	BorderSizePixel = 0,
})
root.Parent = widget

local layout = create("UIListLayout", {
	FillDirection = Enum.FillDirection.Vertical,
	SortOrder = Enum.SortOrder.LayoutOrder,
	Padding = UDim.new(0, 10),
	HorizontalAlignment = Enum.HorizontalAlignment.Center,
})
layout.Parent = root

local rootPad = create("UIPadding", {
	PaddingTop = UDim.new(0, 0),
	PaddingBottom = UDim.new(0, 14),
	PaddingLeft = UDim.new(0, 14),
	PaddingRight = UDim.new(0, 14),
})
rootPad.Parent = root

-- ============================================================
--  HEADER
-- ============================================================
local header = create("Frame", {
	Name = "Header",
	LayoutOrder = 1,
	Size = UDim2.new(1, 28, 0, 52),
	Position = UDim2.new(0, -14, 0, 0),
	BackgroundColor3 = HEAD,
	BorderSizePixel = 0,
}, {
	padding(0, 14, 0, 14),
})
header.Parent = root

create("TextLabel", {
	Name = "Title",
	Size = UDim2.fromScale(1, 1),
	BackgroundTransparency = 1,
	Text = "⚡ ARRR Studio Importer  ·  v" .. VERSION,
	TextColor3 = GOLD,
	TextSize = 14,
	Font = Enum.Font.GothamBold,
	TextXAlignment = Enum.TextXAlignment.Left,
	TextYAlignment = Enum.TextYAlignment.Center,
	Parent = header,
})

create("Frame", {
	Name = "GoldLine",
	Size = UDim2.new(1, 0, 0, 1),
	Position = UDim2.new(0, 0, 1, -1),
	BackgroundColor3 = GOLD,
	BackgroundTransparency = 0.5,
	BorderSizePixel = 0,
	Parent = header,
})

-- ============================================================
--  INSTRUCTION
-- ============================================================
local instrFrame = create("Frame", {
	Name = "Instructions",
	LayoutOrder = 2,
	Size = UDim2.new(1, 0, 0, 52),
	BackgroundColor3 = BG_DARK,
	BorderSizePixel = 0,
}, {
	padding(10, 14, 10, 14),
	corner(8),
	stroke(EDGE, 1, 0.3),
})
instrFrame.Parent = root

create("TextLabel", {
	Size = UDim2.fromScale(1, 1),
	BackgroundTransparency = 1,
	Text = "Paste Lua script hasil generate, lalu klik Build. Script billboard otomatis di-attach ke player.",
	TextColor3 = TEXT_DIM,
	TextSize = 12,
	Font = Enum.Font.Gotham,
	TextXAlignment = Enum.TextXAlignment.Left,
	TextYAlignment = Enum.TextYAlignment.Center,
	TextWrapped = true,
	Parent = instrFrame,
})

-- ============================================================
--  MODE ROW
-- ============================================================
local modeWrap = create("Frame", {
	Name = "ModeWrap",
	LayoutOrder = 3,
	Size = UDim2.new(1, 0, 0, 30),
	BackgroundTransparency = 1,
})
modeWrap.Parent = root

create("TextLabel", {
	Name = "ModeLabel",
	Size = UDim2.new(0, 60, 1, 0),
	BackgroundTransparency = 1,
	Text = "Mode:",
	TextColor3 = TEXT_DIM,
	TextSize = 12,
	Font = Enum.Font.Gotham,
	TextXAlignment = Enum.TextXAlignment.Left,
	TextYAlignment = Enum.TextYAlignment.Center,
	Parent = modeWrap,
})

local modeLua = create("TextButton", {
	Name = "ModeLua",
	Size = UDim2.new(0, 100, 1, 0),
	Position = UDim2.new(0, 60, 0, 0),
	BackgroundColor3 = GOLD,
	TextColor3 = Color3.fromRGB(10, 10, 13),
	Text = "Lua Script",
	TextSize = 11,
	Font = Enum.Font.GothamBold,
	AutoButtonColor = false,
}, { corner(6) })
modeLua.Parent = modeWrap

local modeRbxmx = create("TextButton", {
	Name = "ModeRbxmx",
	Size = UDim2.new(0, 100, 1, 0),
	Position = UDim2.new(0, 166, 0, 0),
	BackgroundColor3 = EDGE,
	TextColor3 = TEXT_DIM,
	Text = "RBXMX XML",
	TextSize = 11,
	Font = Enum.Font.Gotham,
	AutoButtonColor = false,
}, { corner(6) })
modeRbxmx.Parent = modeWrap

local currentMode = "lua"

local function setMode(m)
	currentMode = m
	if m == "lua" then
		modeLua.BackgroundColor3 = GOLD
		modeLua.TextColor3 = Color3.fromRGB(10, 10, 13)
		modeLua.Font = Enum.Font.GothamBold
		modeRbxmx.BackgroundColor3 = EDGE
		modeRbxmx.TextColor3 = TEXT_DIM
		modeRbxmx.Font = Enum.Font.Gotham
	else
		modeLua.BackgroundColor3 = EDGE
		modeLua.TextColor3 = TEXT_DIM
		modeLua.Font = Enum.Font.Gotham
		modeRbxmx.BackgroundColor3 = GOLD
		modeRbxmx.TextColor3 = Color3.fromRGB(10, 10, 13)
		modeRbxmx.Font = Enum.Font.GothamBold
	end
end

modeLua.MouseButton1Click:Connect(function() setMode("lua") end)
modeRbxmx.MouseButton1Click:Connect(function() setMode("rbxmx") end)

-- ============================================================
--  CODE EDITOR
-- ============================================================
local editorWrap = create("Frame", {
	Name = "EditorWrap",
	LayoutOrder = 4,
	Size = UDim2.new(1, 0, 1, -280),
	BackgroundColor3 = BG_DARK,
	BorderSizePixel = 0,
	ClipsDescendants = true,
}, {
	corner(8),
	stroke(EDGE, 1, 0),
})
editorWrap.Parent = root

local scroller = create("ScrollingFrame", {
	Name = "Scroller",
	Size = UDim2.fromScale(1, 1),
	BackgroundTransparency = 1,
	BorderSizePixel = 0,
	CanvasSize = UDim2.new(0, 0, 0, 5000),
	ScrollBarThickness = 8,
	ScrollBarImageColor3 = Color3.fromRGB(90, 90, 102),
	ScrollBarImageTransparency = 0,
	ScrollingDirection = Enum.ScrollingDirection.Y,
	ElasticBehavior = Enum.ElasticBehavior.Never,
	VerticalScrollBarInset = Enum.ScrollBarInset.ScrollBar,
	ClipsDescendants = true,
	Active = true,
	AutomaticCanvasSize = Enum.AutomaticSize.None,
})
scroller.Parent = editorWrap

local editorPad = create("UIPadding", {
	PaddingTop = UDim.new(0, 12),
	PaddingBottom = UDim.new(0, 12),
	PaddingLeft = UDim.new(0, 14),
	PaddingRight = UDim.new(0, 14),
})
editorPad.Parent = scroller

local textBox = create("TextBox", {
	Name = "Input",
	Size = UDim2.new(1, 0, 0, 5000),
	Position = UDim2.new(0, 0, 0, 0),
	BackgroundTransparency = 1,
	BorderSizePixel = 0,
	TextColor3 = TEXT,
	PlaceholderText = "-- Paste Lua script di sini...",
	PlaceholderColor3 = TEXT_MUTE,
	TextSize = 12,
	Font = Enum.Font.Code,
	TextXAlignment = Enum.TextXAlignment.Left,
	TextYAlignment = Enum.TextYAlignment.Top,
	TextWrapped = true,
	ClearTextOnFocus = false,
	MultiLine = true,
	Text = "",
	TextEditable = true,
	TextScaled = false,
	AutomaticSize = Enum.AutomaticSize.None,
})
textBox.Parent = scroller

textBox.Focused:Connect(function()
	local s = editorWrap:FindFirstChildOfClass("UIStroke")
	if s then s.Color = GOLD end
end)
textBox.FocusLost:Connect(function()
	local s = editorWrap:FindFirstChildOfClass("UIStroke")
	if s then s.Color = EDGE end
end)

-- ============================================================
--  BOTTOM ROW
-- ============================================================
local bottomRow = create("Frame", {
	Name = "BottomRow",
	LayoutOrder = 5,
	Size = UDim2.new(1, 0, 0, 44),
	BackgroundTransparency = 1,
})
bottomRow.Parent = root

local status = create("TextLabel", {
	Name = "Status",
	Size = UDim2.new(1, -372, 1, 0),
	BackgroundTransparency = 1,
	Text = "Ready.",
	TextColor3 = TEXT_DIM,
	TextSize = 12,
	Font = Enum.Font.Gotham,
	TextXAlignment = Enum.TextXAlignment.Left,
	TextYAlignment = Enum.TextYAlignment.Center,
	TextTruncate = Enum.TextTruncate.AtEnd,
	Parent = bottomRow,
})

local installBtn = create("TextButton", {
	Name = "InstallPack",
	Size = UDim2.new(0, 124, 1, 0),
	Position = UDim2.new(1, -360, 0, 0),
	BackgroundColor3 = Color3.fromRGB(34, 94, 58),
	TextColor3 = Color3.fromRGB(230, 255, 238),
	Text = "📦 Install Pack",
	TextSize = 12,
	Font = Enum.Font.GothamBold,
	AutoButtonColor = false,
}, { corner(8), stroke(Color3.fromRGB(74, 222, 128), 1, 0.4) })
installBtn.Parent = bottomRow

local clearBtn = create("TextButton", {
	Name = "Clear",
	Size = UDim2.new(0, 100, 1, 0),
	Position = UDim2.new(1, -228, 0, 0),
	BackgroundColor3 = EDGE,
	TextColor3 = TEXT_DIM,
	Text = "🗑️ Clear",
	TextSize = 12,
	Font = Enum.Font.Gotham,
	AutoButtonColor = false,
}, { corner(8), stroke(EDGE, 1, 0.5) })
clearBtn.Parent = bottomRow

local buildBtn = create("TextButton", {
	Name = "Build",
	Size = UDim2.new(0, 120, 1, 0),
	Position = UDim2.new(1, -120, 0, 0),
	BackgroundColor3 = GOLD,
	TextColor3 = Color3.fromRGB(10, 10, 13),
	Text = "🔨 Build",
	TextSize = 13,
	Font = Enum.Font.GothamBold,
	AutoButtonColor = false,
}, { corner(8), stroke(GOLD_BRIGHT, 1, 0.4) })
buildBtn.Parent = bottomRow

local function addHover(btn, baseColor, hoverColor)
	local s = btn:FindFirstChildOfClass("UIStroke")
	btn.MouseEnter:Connect(function()
		btn.BackgroundColor3 = hoverColor
		if s then s.Transparency = 0 end
	end)
	btn.MouseLeave:Connect(function()
		btn.BackgroundColor3 = baseColor
		if s then s.Transparency = 0.4 end
	end)
end

addHover(buildBtn, GOLD, GOLD_BRIGHT)
addHover(clearBtn, EDGE, Color3.fromRGB(52, 52, 62))
addHover(installBtn, Color3.fromRGB(34, 94, 58), Color3.fromRGB(40, 120, 70))

-- ============================================================
--  STATUS HELPER
-- ============================================================
local function setStatus(msg, color)
	status.Text = msg
	status.TextColor3 = color or TEXT_DIM
end

-- ============================================================
--  DETECT SCRIPT TYPE
-- ============================================================
local function detectScriptType(source)
	if source:find("BillboardGui") or source:find("billboard") or source:find("Billboard") then
		return "billboard"
	end
	if source:find("ScreenGui") or source:find("screenGui") then
		return "screengui"
	end
	return "unknown"
end

-- ============================================================
--  BILLBOARD HANDLER
--  Attach BillboardGui ke semua player (Play mode) atau dummy (Edit mode)
-- ============================================================
local function applyBillboard(source)
	local isRunning = RunService:IsRunning()

	-- Compile source jadi function
	local fn, err = loadstring(source)
	if not fn then
		return false, "Syntax error: " .. tostring(err)
	end

	-- Coba jalankan source — kalau ada BillboardGui yang dibuat, kita tangkap
	-- Trick: intercept Instance.new("BillboardGui") dengan hook sementara
	local capturedBillboards = {}
	local originalNew = Instance.new

	-- Hook Instance.new
	local hookedNew = function(class, parent)
		local inst = originalNew(class, parent)
		if class == "BillboardGui" then
			table.insert(capturedBillboards, inst)
		end
		return inst
	end

	-- Ganti sementara
	-- (Roblox nggak allow replace global Instance.new, jadi pakai approach lain)

	-- Approach 2: Jalankan script di tempat yang kita kontrol
	-- Kita bikin environment yang bisa intercept
	-- Tapi karena loadstring pakai _ENV global, kita nggak bisa inject mudah

	-- Approach 3 (paling reliable): User harus jalankan script di Play mode
	-- Kalau di Edit mode, kita bikin dummy part di workspace biar bisa preview

	if isRunning then
		-- Play mode: langsung jalanin, script akan attach ke LocalPlayer otomatis
		local ok, result = pcall(fn)
		if not ok then
			return false, "Runtime error: " .. tostring(result)
		end
		return true, "Play mode: Billboard attached to players"
	end

	-- Edit mode: bikin preview
	-- Kita bikin dummy part di Workspace + attach BillboardGui ke situ
	local existingDummy = Workspace:FindFirstChild("ARRR_BillboardPreview")
	if existingDummy then existingDummy:Destroy() end

	local dummyPart = create("Part", {
		Name = "ARRR_BillboardPreview",
		Size = Vector3.new(2, 5, 1),
		Position = Vector3.new(0, 5, 0),
		Anchored = true,
		CanCollide = false,
		Transparency = 0.7,
		Color = Color3.fromRGB(100, 100, 110),
		Material = Enum.Material.SmoothPlastic,
	})
	dummyPart.Parent = Workspace

	-- Bikin dummy head attachment
	local attach = create("Attachment", {
		Name = "BillboardAttach",
		Position = Vector3.new(0, 2.5, 0),
	})
	attach.Parent = dummyPart

	-- Jalankan source script dengan sanitize
	-- Ganti Players.LocalPlayer references supaya ambil dummy
	local sanitized = source
	-- Hapus baris yang reference LocalPlayer / PlayerGui karena nggak perlu buat billboard
	sanitized = sanitized:gsub('local%s+player%s*=%s*Players%.LocalPlayer', 'local player = nil')
	sanitized = sanitized:gsub('local%s+playerGui%s*=%s*player:WaitForChild%("PlayerGui"%)', 'local playerGui = nil')

	-- Coba jalanin, kalau error kita laporin
	local fn2, err2 = loadstring(sanitized)
	if not fn2 then
		dummyPart:Destroy()
		return false, "Syntax error after sanitize: " .. tostring(err2)
	end

	-- Karena di Edit mode, script biasanya gagal karena nggak ada player
	-- Kita coba jalanin, kalau gagal cuma warning
	local ok, result = pcall(fn2)

	-- Hapus semua BillboardGui yang ke-create di luar workspace
	-- (kecuali yang di Workspace)
	local guiInStarterGui = game:GetService("StarterGui"):FindFirstChild("GeneratedUI")
	if guiInStarterGui then
		-- Kalau ada BillboardGui yang dibuat di StarterGui, pindahin ke dummy
		for _, d in ipairs(guiInStarterGui:GetDescendants()) do
			if d:IsA("BillboardGui") then
				d.Parent = attach
			end
		end
		-- Hapus ScreenGui-nya kalau nggak kepake
		guiInStarterGui:Destroy()
	end

	return true, "Edit mode: Preview part created at (0,5,0). Klik Play untuk lihat di player."
end

-- ============================================================
--  SCREEN GUI HANDLER (existing)
-- ============================================================
local function sanitizeForEditMode(source)
	if RunService:IsRunning() then
		return source, "run"
	end

	local s = source
	s = s:gsub('local%s+player%s*=%s*Players%.LocalPlayer', 'local player = { PlayerGui = game:GetService("StarterGui") }')
	s = s:gsub('local%s+playerGui%s*=%s*player:WaitForChild%("PlayerGui"%)', 'local playerGui = game:GetService("StarterGui")')
	s = s:gsub('player:WaitForChild%("PlayerGui"%)', 'game:GetService("StarterGui")')
	s = s:gsub('player%.PlayerGui', 'game:GetService("StarterGui")')
	s = s:gsub('Players%.LocalPlayer', 'game:GetService("StarterGui")')
	return s, "edit"
end

-- ============================================================
--  BUILD LOGIC
-- ============================================================
local function buildFromLua(source)
	if not source or source == "" then
		setStatus("❌ Input kosong.", RED)
		return
	end

	if not loadstring then
		setStatus("❌ loadstring tidak tersedia. Enable API Services.", RED)
		return
	end

	-- Detect script type
	local scriptType = detectScriptType(source)

	if scriptType == "billboard" then
		setStatus("👑 Billboard detected — applying...", GOLD)
		task.wait(0.1)

		local ok, msg = applyBillboard(source)
		if ok then
			setStatus("✓ " .. msg, GREEN)
		else
			setStatus("❌ " .. msg, RED)
			warn("[ARRR] Billboard error:", msg)
		end
		return
	end

	-- ScreenGui / generic script: pakai flow lama
	setStatus("⏳ Sanitizing...", GOLD)
	task.wait(0.05)

	local sanitized, mode = sanitizeForEditMode(source)

	setStatus("⏳ Building (" .. mode .. " mode)...", GOLD)
	task.wait(0.05)

	local fn, err = loadstring(sanitized)
	if not fn then
		setStatus("❌ Syntax error: " .. tostring(err), RED)
		warn("[ARRR] Syntax error:", err)
		return
	end

	local ok, result = pcall(fn)

	if not ok then
		local errMsg = tostring(result)
		if #errMsg > 70 then errMsg = errMsg:sub(1, 70) .. "..." end
		setStatus("❌ " .. errMsg, RED)
		warn("[ARRR] Runtime error:")
		warn(result)
		return
	end

	if mode == "edit" then
		setStatus("✓ Sukses! Cek StarterGui.", GREEN)
	else
		setStatus("✓ Sukses! Cek PlayerGui.", GREEN)
	end
end

local function buildFromRbxmx(source)
	if not source or source == "" then
		setStatus("❌ Input kosong.", RED)
		return
	end
	if not source:match("^%s*<") then
		setStatus("❌ Bukan XML valid.", RED)
		return
	end
	setStatus("ℹ️ RBXMX: klik kanan Workspace → Insert from File → pilih .rbxmx, lalu klik 📦 Install Pack", YELLOW)
end

-- ============================================================
--  INSTALL PACK (.rbxmx "ArrUIPack")
--  Struktur pack: StarterGui / ReplicatedStorage / ServerScriptService /
--  StarterPlayerScripts → dipindah ke service aslinya (replace kalau sudah ada)
-- ============================================================
local PACK_TARGETS = {
	StarterGui           = function() return game:GetService("StarterGui") end,
	ReplicatedStorage    = function() return game:GetService("ReplicatedStorage") end,
	ServerScriptService  = function() return game:GetService("ServerScriptService") end,
	StarterPlayerScripts = function() return game:GetService("StarterPlayer"):WaitForChild("StarterPlayerScripts") end,
}

local function findPack()
	for _, sel in ipairs(Selection:Get()) do
		if sel.Name == "ArrUIPack" then return sel end
		local inside = sel:FindFirstChild("ArrUIPack", true)
		if inside then return inside end
	end
	return Workspace:FindFirstChild("ArrUIPack", true)
		or game:GetService("ServerStorage"):FindFirstChild("ArrUIPack", true)
		or game:GetService("ReplicatedStorage"):FindFirstChild("ArrUIPack", true)
end

-- Folder (mis. ArrUI) di-merge; Instance lain di-replace
local function moveInto(item, target)
	local existing = target:FindFirstChild(item.Name)
	if existing and item:IsA("Folder") and existing:IsA("Folder") then
		for _, child in ipairs(item:GetChildren()) do moveInto(child, existing) end
		item:Destroy()
		return
	end
	if existing then existing:Destroy() end
	item.Parent = target
end

local function installPack()
	local pack = findPack()
	if not pack then
		setStatus("❌ ArrUIPack tidak ditemukan. Klik kanan Workspace → Insert from File → pilih .rbxmx", RED)
		return
	end

	local recording = ChangeHistoryService:TryBeginRecording("ArrUI Install Pack")
	local installed = {}
	for _, group in ipairs(pack:GetChildren()) do
		local getTarget = PACK_TARGETS[group.Name]
		if getTarget then
			local target = getTarget()
			for _, item in ipairs(group:GetChildren()) do
				moveInto(item, target)
				table.insert(installed, target.Name .. "/" .. item.Name)
			end
		end
	end
	pack:Destroy()
	if recording then ChangeHistoryService:FinishRecording(recording, Enum.FinishRecordingOperation.Commit) end

	print("[ARRR] Installed:\n  " .. table.concat(installed, "\n  "))
	setStatus("✓ Terpasang " .. #installed .. " item. DataStore: Game Settings → Security → API Services.", GREEN)
	Selection:Set({ game:GetService("StarterGui") })
end

installBtn.MouseButton1Click:Connect(installPack)

buildBtn.MouseButton1Click:Connect(function()
	if currentMode == "lua" then
		buildFromLua(textBox.Text)
	else
		buildFromRbxmx(textBox.Text)
	end
end)

clearBtn.MouseButton1Click:Connect(function()
	textBox.Text = ""
	setStatus("Ready.")
end)

mainButton.Click:Connect(function()
	widget.Enabled = not widget.Enabled
end)

plugin.Unloading:Connect(function()
	if widget then
		widget.Enabled = false
		widget:Destroy()
	end
	-- Cleanup preview dummy
	local dummy = Workspace:FindFirstChild("ARRR_BillboardPreview")
	if dummy then dummy:Destroy() end
	print("[ARRR] Plugin unloaded.")
end)

print("[ARRR] " .. PLUGIN_NAME .. " v" .. VERSION .. " loaded.")
LUA;
    }
}
?>