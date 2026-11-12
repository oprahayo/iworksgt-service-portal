<?php
/** Minimal customer-only shell; administrative portal keeps its existing UI. */
$clientRoute = (string) ($_GET['page'] ?? 'dashboard');
$clientLogo = logo_path();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($title ?: 'iWorks — Client Workspace') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="stylesheet" href="assets/css/client-portal.css?v=20261112c">
</head>
<body class="portal-authenticated iw-client-page <?= e($pageBodyClass ?? '') ?>">
<div class="portal-layout iw-client-layout">
  <aside class="portal-sidebar iw-client-sidebar" id="portalSidebar">
    <div class="iw-client-side-brand">
      <a href="<?= e(url('dashboard')) ?>" aria-label="iWorks customer home">
        <?php if ($clientLogo !== ''): ?>
        <img class="iw-client-logo" src="<?= e($clientLogo) ?>" alt="<?= e(brand_name()) ?>">
        <?php else: ?>
        <span class="iw-client-brand"><span class="iw-client-mark">i</span>works<span class="iw-client-brand-dot">.</span></span>
        <?php endif; ?>
      </a>
      <button class="iw-client-nav-close" id="sidebarCloseBtn" aria-label="Close menu" type="button"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav class="iw-client-menu" aria-label="Customer navigation">
      <a class="<?= $clientRoute === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('dashboard')) ?>"><i class="bi bi-plus-square"></i> New application</a>
      <a class="<?= in_array($clientRoute, ['applications','trademark','job','jobs'], true) ? 'active' : '' ?>" href="<?= e(url('applications')) ?>"><i class="bi bi-folder2-open"></i> My services</a>
      <a class="<?= $clientRoute === 'invoices' ? 'active' : '' ?>" href="<?= e(url('invoices')) ?>"><i class="bi bi-receipt"></i> Billing</a>
      <a class="<?= $clientRoute === 'card_vault' ? 'active' : '' ?>" href="<?= e(url('card_vault')) ?>"><i class="bi bi-credit-card-2-front"></i> Saved cards</a>
      <a class="<?= $clientRoute === 'profile' ? 'active' : '' ?>" href="<?= e(url('profile')) ?>"><i class="bi bi-person"></i> Profile</a>
    </nav>
    <div class="iw-client-side-foot">
      <a href="<?= e(url('support')) ?>"><i class="bi bi-question-circle"></i> Help & support</a>
      <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="portal-main iw-client-main">
    <header class="portal-topbar iw-client-topbar">
      <div class="iw-client-topbar-left">
        <button type="button" id="sidebarOpenBtn" aria-label="Open customer menu" class="iw-client-menu-btn"><i class="bi bi-list"></i></button>
        <a class="iw-client-top-home" href="<?= e(public_url('home')) ?>"><i class="bi bi-arrow-up-left"></i> Main website</a>
      </div>
      <div class="iw-client-topbar-right">
        <span class="iw-client-avatar" aria-hidden="true"><?= e(strtoupper(substr(trim((string) ($user['full_name'] ?? 'C')), 0, 1))) ?></span>
        <span class="iw-client-account-name"><?= e((string) ($user['full_name'] ?? 'Customer')) ?></span>
        <a href="logout.php" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </header>
    <main class="portal-content iw-client-content" id="main-content">
      <?php if ($msg = flash('success')): ?><div class="alert alert-success" role="status"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($msg = flash('error')): ?><div class="alert alert-danger" role="alert"><?= e($msg) ?></div><?php endif; ?>
