<?php
/**
 * SIWES Tracker — shared page layout
 *
 * Usage:
 *   $page_title = 'Dashboard';
 *   $active     = 'dashboard';
 *   require __DIR__ . '/partials/layout-top.php';
 *   ... page content ...
 *   require __DIR__ . '/partials/layout-bottom.php';
 */

require_once __DIR__ . '/../helpers.php';

$page_title = $page_title ?? APP_NAME;
$active     = $active     ?? '';
$student    = current_student();
$flashError = flash('error');
$flashSuccess = flash('success');

$navItems = [
    'dashboard' => ['Dashboard',  icon('home')],
    'logs'      => ['My Log Book', icon('book')],
    'profile'   => ['Profile',     icon('user')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-theme="light">

<!-- ===== Sidebar ===== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        <span class="sidebar__logo"><?= icon('cap') ?></span>
        <span class="sidebar__name"><?= e(APP_NAME) ?></span>
    </div>

    <nav class="sidebar__nav" aria-label="Main navigation">
        <?php foreach ($navItems as $key => [$label, $svg]): ?>
            <a class="sidebar__link <?= $active === $key ? 'is-active' : '' ?>"
               href="<?= e($key) ?>.php" <?= $active === $key ? 'aria-current="page"' : '' ?>>
                <?= $svg ?><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__user">
            <span class="avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($student['full_name'] ?? 'S', 0, 1))) ?></span>
            <div class="sidebar__user-meta">
                <strong><?= e($student['full_name'] ?? '') ?></strong>
                <small><?= e($student['matric_no'] ?? '') ?></small>
            </div>
        </div>
        <a class="btn btn--ghost btn--sm btn--block" href="logout.php">
            <?= icon('logout') ?><span>Log out</span>
        </a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

<!-- ===== Main ===== -->
<div class="main">
    <header class="topbar">
        <button class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false">
            <?= icon('menu') ?>
        </button>
        <div class="topbar__title">
            <h1><?= e($page_title) ?></h1>
        </div>
        <button class="icon-btn" id="themeToggle" aria-label="Toggle dark mode" title="Toggle theme">
            <?= icon('moon') ?>
        </button>
    </header>

    <main class="content">
        <?php if ($flashSuccess): ?>
            <div class="alert alert--success" role="status"><?= icon('check') ?> <?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert--error" role="alert"><?= icon('alert') ?> <?= e($flashError) ?></div>
        <?php endif; ?>
