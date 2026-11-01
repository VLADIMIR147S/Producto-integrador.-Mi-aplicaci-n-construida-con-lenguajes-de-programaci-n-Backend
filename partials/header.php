<?php
/** Encabezado compartido de Guacamaya. */
$layout = $layout ?? 'app';
$pageTitle = $pageTitle ?? APP_NAME;
$me = $me ?? current_user();
$flashes = take_flashes();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#111827">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($layout) ?>">
<?php if ($layout !== 'bare'): ?>
<div class="app-shell">
<aside class="sidebar">
<a class="brand" href="<?= $me ? 'gallery.php' : 'index.php' ?>"><?= logo() ?></a>
<nav class="side-nav">
<a href="gallery.php"><?= icon('images') ?> Mi galería</a>
<a href="profile.php"><?= icon('user') ?> Mi cuenta</a>
<?php if ($me && ($me['role'] ?? '') === 'admin'): ?><a href="admin.php"><?= icon('users') ?> Administración</a><?php endif; ?>
</nav>
<div class="sidebar-bottom">
<span class="user-mini"><?= avatar($me['username'] ?? 'Usuario') ?><span><?= e($me['username'] ?? '') ?></span></span>
<form method="post" action="logout.php" class="logout-form"><?= csrf_field() ?><button type="submit" class="logout-button"><?= icon('logout') ?> Cerrar sesión</button></form>
</div>
</aside>
<main class="main-content">
<header class="page-header">
<div><p class="breadcrumb"><?= e($breadcrumb ?? '') ?></p><h1><?= e($pageTitle) ?></h1></div>
<div class="page-actions"><?= $pageActions ?? '' ?></div>
</header>
<?php endif; ?>
<?php foreach ($flashes as $f): ?><div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div><?php endforeach; ?>
