<?php
// app/views/components/navbar.php — navbar aplikasi (tidak tampil di landing/login)
// Variabel: $activePage, $navVariant
$activePage = $activePage ?? 'converter';
$navVariant = $navVariant ?? 'app';

$navItems = [
    ['id' => 'converter', 'label' => 'Converter', 'url' => url('converter')],
    ['id' => 'library',   'label' => 'Library',   'url' => url('library')],
    ['id' => 'docs',      'label' => 'Docs',      'url' => url('docs')],
    ['id' => 'prompt',    'label' => 'Prompt',    'url' => url('prompt')],
];

// Menu "Tools" (dropdown di desktop, section tersendiri di mobile) — hanya untuk user login
$toolItems = [
    ['id' => 'spoofer', 'label' => 'Auto Spoof', 'desc' => 'Re-upload aset massal ke akunmu', 'url' => url('spoofer')],
    ['id' => 'ytmp3',   'label' => 'YT → MP3',   'desc' => 'Convert link YouTube + speed/pitch', 'url' => url('ytmp3')],
    ['id' => 'history', 'label' => 'Riwayat Upload', 'desc' => 'Semua aset yang sudah kamu upload', 'url' => url('history')],
    ['id' => 'luaobf', 'label' => 'Lua Obfuscator', 'desc' => 'Obfuscate & deobfuscate script Lua', 'url' => url('luaobf')],
];
$toolsActive = in_array($activePage, array_column($toolItems, 'id'), true);

// ==== AMBIL DATA USER ====
$currentUser = Auth::check() ? Auth::user() : null;

if ($currentUser) {
    $userName   = $currentUser['name'] ?? $currentUser['username'] ?? 'User';
    $userHandle = '@' . ($currentUser['username'] ?? 'user');
    $userEmail  = $currentUser['email'] ?? '';
    $userAvatar = $currentUser['avatar'] ?? '';
    $userRole   = ucfirst($currentUser['provider'] ?? 'Member'); // Google / Discord
    $isLoggedIn = true;
} else {
    $userName   = 'Guest';
    $userHandle = 'Not signed in';
    $userEmail  = '';
    $userAvatar = '';
    $userRole   = 'Guest';
    $isLoggedIn = false;
}

// Fallback avatar
$avatarSrc = $userAvatar !== '' ? $userAvatar : asset('img/logo.png');
?>
<?php if (($navVariant ?? 'app') === 'landing'): ?>
  <?php /* Landing page: no navbar */ ?>
<?php else: ?>
<header class="app-header" id="appHeader">

  <!-- Mobile hamburger -->
  <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <!-- Brand -->
  <!-- Logo → landing (reload penuh: landing tidak punya header aplikasi yang di-swap SPA) -->
  <a href="<?= url('landing') ?>" class="brand" data-no-spa title="Beranda">
    <div class="brand-icon">
      <img src="<?= asset('img/logo.png') ?>" alt="ARRR Studio">
    </div>
    <div class="brand-text">
      <div class="brand-title">
        ARRR <span class="brand-accent">STUDIO</span>
      </div>
      <div class="brand-sub">Roblox UI Converter</div>
    </div>
  </a>

  <!-- Navigation -->
  <nav class="top-nav" id="topNav">
    <?php foreach ($navItems as $item): ?>
      <a class="top-nav-item <?= is_active($item['id'], $activePage) ?>"
         href="<?= $item['url'] ?>"
         data-spa>
        <span class="nav-label"><?= $item['label'] ?></span>
        <span class="nav-indicator"></span>
      </a>
    <?php endforeach; ?>

    <?php if (Auth::check()): /* Tools butuh login — tidak ditampilkan ke guest */ ?>
    <div class="nav-group <?= $toolsActive ? 'active' : '' ?>" id="navTools">
      <button type="button" class="top-nav-item nav-group-toggle <?= $toolsActive ? 'active' : '' ?>"
              aria-haspopup="true" aria-expanded="false" aria-controls="navToolsMenu">
        <span class="nav-label">Tools</span>
        <svg class="nav-group-chevron" viewBox="0 0 12 12" width="10" height="10" fill="none" aria-hidden="true"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        <span class="nav-indicator"></span>
      </button>
      <div class="nav-group-menu" id="navToolsMenu">
        <span class="nav-group-label">Tools</span>
        <?php foreach ($toolItems as $item): ?>
          <a class="nav-group-item <?= is_active($item['id'], $activePage) ?>" href="<?= $item['url'] ?>" data-spa>
            <span class="nav-group-name"><?= e($item['label']) ?></span>
            <span class="nav-group-desc"><?= e($item['desc']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </nav>

  <!-- Right side -->
  <div class="top-right">

    <!-- Status + aksi (hanya di converter) -->
    <?php if ($activePage === 'converter'): ?>
      <div class="status-group">
        <div class="status-pill live" title="Nodes converted">
          <span class="status-dot"></span>
          <span id="badgeNodes">0 nodes</span>
        </div>
        <div class="status-pill warn hidden" id="badgeWarn" title="Warnings">
          <span class="status-dot"></span>
          <span>0 warn</span>
        </div>
      </div>

      <div class="top-divider"></div>

      <!-- Export: tombol berlabel supaya jelas fungsinya -->
      <div class="actions">
        <button onclick="downloadFile('rbxmx')" class="btn btn-action btn-export"
                title="Paket lengkap untuk Roblox Studio: GUI + GameConfig + Server + Client (.rbxmx)">
          <span class="btn-action-label">Export .rbxmx</span>
        </button>
        <button onclick="downloadFile('lua')" class="btn btn-action"
                title="Full LocalScript (.lua) — bikin UI lewat script">
          <span class="btn-action-label">Lua</span>
        </button>
        <button onclick="openPluginModal()" class="btn btn-action"
                title="Download plugin + cara install ke Studio">
          <span class="btn-action-label">Plugin</span>
        </button>
        <button onclick="copyCurrent()" id="copyBtn" class="btn btn-action"
                title="Copy isi tab output yang sedang dibuka">
          <span class="btn-action-label">Copy</span>
        </button>
      </div>

      <div class="top-divider"></div>
    <?php endif; ?>

    <button type="button" class="theme-toggle-btn" data-theme-open title="Ganti tema" aria-label="Ganti tema">
      <span class="theme-toggle-dot"></span>
    </button>

    <?php if ($isLoggedIn): ?>
      <!-- ===== PROFILE (LOGGED IN) ===== -->
      <div class="profile-chip" id="profileChip" tabindex="0">
        <div class="profile-avatar">
          <img src="<?= e($avatarSrc) ?>" alt="<?= e($userName) ?>">
          <span class="profile-online"></span>
        </div>
        <div class="profile-info">
          <div class="profile-name"><?= e($userName) ?></div>
          <div class="profile-role"><?= e($userRole) ?></div>
        </div>
        <svg class="profile-chevron" viewBox="0 0 12 12" width="10" height="10" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>

        <!-- Dropdown -->
        <div class="profile-menu" id="profileMenu">
          <div class="profile-menu-header">
            <div class="profile-menu-avatar">
              <img src="<?= e($avatarSrc) ?>" alt="<?= e($userName) ?>">
            </div>
            <div class="profile-menu-info">
              <div class="profile-menu-name"><?= e($userName) ?></div>
              <div class="profile-menu-email"><?= e($userEmail ?: $userHandle) ?></div>
            </div>
          </div>
          <div class="profile-menu-divider"></div>
          <a href="#" class="profile-menu-item">
            Profile
          </a>
          <a href="#" class="profile-menu-item">
            Settings
          </a>
          <a href="#" class="profile-menu-item" data-theme-open data-no-spa>
            Theme
          </a>
          <div class="profile-menu-divider"></div>
          <a href="<?= url('logout') ?>" class="profile-menu-item danger" data-no-spa>
            Sign out
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- ===== LOGIN BUTTON (GUEST) ===== -->
      <a href="<?= url('login') ?>" class="btn btn-gold" data-no-spa>
        <span class="btn-label">Masuk</span>
      </a>
    <?php endif; ?>

  </div>

  <!-- Mobile overlay -->
  <div class="nav-overlay" id="navOverlay"></div>

</header>
<?php endif; ?>