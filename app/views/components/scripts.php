<?php
// app/views/components/scripts.php — config global JS + semua script
//
// Semua script halaman dimuat sekali di sini supaya navigasi SPA
// (yang cuma swap <header>/<main>) tetap punya semua logic-nya.
// Tiap script halaman init sendiri via DOMContentLoaded + event 'spa:navigated'.
//
// Urutan PENTING:
//   1. core/guest-limit.js → API GuestLimit
//   2. core/layout.js      → modal, toast, navbar
//   3. pages/*.js          → logic per halaman (butuh GuestLimit + window.__*)
//   4. core/spa.js         → paling akhir
$scripts = [
    'js/core/guest-limit.js',
    'js/core/layout.js',
    'js/pages/landing.js',
    'js/pages/converter.js',
    'js/pages/library.js',
    'js/pages/docs.js',
    'js/pages/prompt.js',
    'js/pages/spoofer.js',
    'js/pages/ytmp3.js',
    'js/pages/history.js',
    'js/pages/luaobf.js',
    'js/core/spa.js',
];
?>
<script>
window.__isLoggedIn = <?= Auth::check() ? 'true' : 'false' ?>;
window.__guestMax   = <?= (int)config('app.guest.max_uses', 3) ?>;
window.__oauthUrls = {
  google:  <?= json_encode(url('auth_google')) ?>,
  discord: <?= json_encode(url('auth_discord')) ?>,
};
window.__docsUrl    = <?= json_encode(url('docs')) ?>;
window.__libraryUrl = <?= json_encode(url('library')) ?>;
window.__converterUrl = <?= json_encode(url('converter')) ?>;
window.__loginUrl   = <?= json_encode(url('login')) ?>;
window.__logoUrl    = <?= json_encode(asset('img/logo.png')) ?>;
window.__apiUrls = {
  convert:  <?= json_encode(BASE_URL . '/api/convert.php') ?>,
  generate: <?= json_encode(BASE_URL . '/api/generate.php') ?>,
  spoof:    <?= json_encode(BASE_URL . '/api/spoof.php') ?>,
  ytmp3:    <?= json_encode(BASE_URL . '/api/ytmp3.php') ?>,
};
window.__ytmp3MaxBatch = <?= (int)config('app.ytmp3.max_batch', 50) ?>;
window.__samplesUrl = <?= json_encode(BASE_URL . '/samples/') ?>;
</script>

<?php foreach ($scripts as $script): ?>
<script src="<?= asset_v($script) ?>"></script>
<?php endforeach; ?>
