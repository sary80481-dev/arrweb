<?php
// app/views/layouts/master.php — kerangka HTML semua halaman
// Variabel: $pageTitle, $activePage, $navVariant, $styles, $content
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'ARRR Studio') ?></title>
<link rel="icon" type="image/png" href="<?= asset('img/logo.png') ?>">

<?php /* Terapkan tema sebelum CSS dirender (tanpa kedip) */ ?>
<script>try{var t=localStorage.getItem('arrr_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Inter+Tight:wght@500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= asset_v('css/theme.css') ?>">

<link rel="stylesheet" href="<?= asset_v('css/app.css') ?>">

<?php /* Style permanen (tidak di-swap SPA) */ ?>
<?php View::style('base', 'base'); ?>
<?php View::style('nav', 'nav'); ?>
<?php View::style('loader', 'loader'); ?>

<?php /* Style khusus halaman (di-swap SPA) */ ?>
<?php foreach ((array)($styles ?? []) as $style): ?>
<?php View::style($style, 'pages/' . $style); ?>
<?php endforeach; ?>
</head>
<body>

<?php View::component('loader'); ?>

<?php View::component('navbar'); ?>

<?= $content ?>

<?php // Di luar <main> → tetap ada saat navigasi SPA (yang hanya swap header & main) ?>
<?php View::component('toast'); ?>

<?php View::component('scripts'); ?>

</body>
</html>
