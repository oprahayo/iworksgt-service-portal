<?php
/** @var string $title */
$user = null;
try {
    $user = current_user();
} catch (Throwable $e) {
    $user = null;
}
$currentPage = $_GET['page'] ?? 'dashboard';
$logo = logo_path();
$favicon = favicon_path();
$seoDescriptionText = seo_description();
$seoTagsText = seo_tags();
$unreadCount = 0;
$latestNotificationId = 0;
$pageHeading = trim(str_replace(' - ' . app_name(), '', $title ?: ''));
if ($pageHeading === '') {
    $pageHeading = app_name();
}
$portalHint = null;
$pageBodyClass = 'page-' . preg_replace('/[^a-z0-9_-]+/i', '-', strtolower((string) $currentPage));
$bodyClass = trim(($user ? 'portal-authenticated' : 'portal-guest') . ' ' . $pageBodyClass);

if ($user) {
    try {
        $notificationState = notification_realtime_state((int) $user['id']);
        $unreadCount = (int) ($notificationState['unread_count'] ?? 0);
        $latestNotificationId = (int) ($notificationState['latest_id'] ?? 0);
    } catch (Throwable $e) {
        $unreadCount = 0;
        $latestNotificationId = 0;
    }

    try {
        $portalHint = portal_next_step_hint($user, (string) $currentPage);
    } catch (Throwable $e) {
        $portalHint = null;
    }
}

// Customer accounts use the dedicated application workspace. The operational
// admin/finance/agent layout below remains untouched.
if ($user && (string) ($user['role'] ?? '') === 'client') {
    require __DIR__ . '/client-header.php';
    return;
}

$sidebarItems = [];
if ($user && (string)($user['role'] ?? '') === 'author') {
    $sidebarItems = [
        ['page'=>'dashboard','label'=>'Dashboard','icon'=>'bi-speedometer2','url'=>url('dashboard')],
        ['page'=>'blog','label'=>'Insights','icon'=>'bi-journal-richtext','url'=>url('blog')],
    ];
} elseif ($user) {
    $sidebarItems[] = [
        'page' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => url('dashboard'),
    ];
    $sidebarItems[] = [
        'page' => 'jobs',
        'label' => 'Jobs',
        'icon' => 'bi-briefcase',
        'url' => url('jobs'),
    ];
    if (has_role(['client', 'admin', 'finance'])) {
        $sidebarItems[] = [
            'page' => 'invoices',
            'label' => 'Invoices',
            'icon' => 'bi-receipt',
            'url' => url('invoices'),
        ];
    }

    if (has_role(['client', 'admin', 'finance'])) {
        $sidebarItems[] = [
            'page' => 'support',
            'label' => 'Support',
            'icon' => 'bi-life-preserver',
            'url' => url('support'),
        ];
    }

    if (has_role(['agent', 'admin', 'finance'])) {
        $sidebarItems[] = [
            'page' => 'withdrawals',
            'label' => 'Withdrawals',
            'icon' => 'bi-wallet2',
            'url' => url('withdrawals'),
        ];
    }

    if (has_role(['admin', 'agent'])) {
        $sidebarItems[] = [
            'page' => 'community',
            'label' => 'Community',
            'icon' => 'bi-people-fill',
            'url' => url('community'),
        ];
    }

    if (has_role(['admin', 'agent'])) {
        $sidebarItems[] = [
            'page' => 'live_inbox',
            'label' => 'Website Live Chat',
            'icon' => 'bi-chat-dots-fill',
            'url' => url('live_inbox'),
        ];
        $sidebarItems[] = [
            'page' => 'messages',
            'label' => 'Messages',
            'icon' => 'bi-chat-dots',
            'url' => url('messages'),
        ];
        $sidebarItems[] = [
            'page' => 'blog',
            'label' => 'Insights',
            'icon' => 'bi-journal-richtext',
            'url' => url('blog'),
        ];
    }

    if (has_role(['admin', 'finance'])) {
        $sidebarItems[] = [
            'page' => 'services',
            'label' => 'Services',
            'icon' => 'bi-grid',
            'url' => url('services'),
        ];
        $sidebarItems[] = [
            'page' => 'payments',
            'label' => 'Payments',
            'icon' => 'bi-credit-card',
            'url' => url('payments'),
        ];
        $sidebarItems[] = [
            'page' => 'payouts',
            'label' => 'Payouts',
            'icon' => 'bi-cash-coin',
            'url' => url('payouts'),
        ];
        $sidebarItems[] = [
            'page' => 'exports',
            'label' => 'Export',
            'icon' => 'bi-download',
            'url' => url('exports'),
        ];
    }

    if (has_role(['admin'])) {
        $sidebarItems[] = [
            'page' => 'forms',
            'label' => 'Forms',
            'icon' => 'bi-ui-checks-grid',
            'url' => url('forms'),
        ];
        $sidebarItems[] = [
            'page' => 'users',
            'label' => 'Users',
            'icon' => 'bi-person-lines-fill',
            'url' => url('users'),
        ];
        $sidebarItems[] = [
            'page' => 'media',
            'label' => 'Media',
            'icon' => 'bi-images',
            'url' => url('media'),
        ];
        $sidebarItems[] = [
            'page' => 'editorial_showcase',
            'label' => 'Stories & Feedback',
            'icon' => 'bi-collection',
            'url' => url('editorial_showcase'),
        ];
        $sidebarItems[] = [
            'page' => 'frontend',
            'label' => 'Website',
            'icon' => 'bi-window-stack',
            'url' => url('frontend'),
        ];
        $sidebarItems[] = [
            'page' => 'settings',
            'label' => 'Settings',
            'icon' => 'bi-gear',
            'url' => url('settings'),
        ];
    }
}
if ($user && (string)($user['role'] ?? '') === 'admin') {
    $sidebarItems[] = ['page'=>'system_health','label'=>'System health','icon'=>'bi-heart-pulse','url'=>url('system_health')];
}
$sidebarPriority = [
    'dashboard'=>0, 'jobs'=>10, 'support'=>20, 'live_inbox'=>25, 'messages'=>30, 'community'=>40,
    'services'=>50, 'forms'=>60,
    'blog'=>70, 'editorial_showcase'=>80, 'frontend'=>90, 'media'=>100,
    'invoices'=>110, 'payments'=>120, 'payouts'=>130, 'withdrawals'=>140,
    'users'=>150, 'settings'=>160, 'system_health'=>170, 'exports'=>180,
];
usort($sidebarItems, static function(array $a, array $b) use ($sidebarPriority):int {
    return ($sidebarPriority[$a['page']] ?? 999) <=> ($sidebarPriority[$b['page']] ?? 999);
});
$publicPreviewRoutes = ['blog'=>'blog','services'=>'services','frontend'=>'home','editorial_showcase'=>'case-studies'];
$publicPreviewPage = $publicPreviewRoutes[$currentPage] ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?: app_name()) ?></title>
  <meta name="description" content="<?= e($seoDescriptionText) ?>">
  <?php if ($seoTagsText !== ''): ?>
    <meta name="keywords" content="<?= e($seoTagsText) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="<?= e(brand_primary_color()) ?>">
  <meta property="og:title" content="<?= e($title ?: app_name()) ?>">
  <meta property="og:description" content="<?= e($seoDescriptionText) ?>">
  <?php if ($favicon !== ''): ?>
    <link rel="icon" type="<?= e(favicon_mime_type($favicon)) ?>" href="<?= e($favicon) ?>" sizes="any">
    <?php if (favicon_mime_type($favicon) !== 'image/svg+xml'): ?><link rel="apple-touch-icon" href="<?= e($favicon) ?>"><?php endif; ?>
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php if (!$user && in_array($currentPage, ['login', 'register', 'forgot_password'], true)): ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.6.0/build/css/intlTelInput.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="stylesheet" href="assets/css/reviews.css">
  <link rel="stylesheet" href="assets/css/emoji-rating.css?v=20261113-live1">
  <link rel="stylesheet" href="assets/css/cac-registration.css?v=20261113-cac1">
  <link rel="stylesheet" href="assets/css/brand-color-pass.css?v=20261113-live1">
  <link rel="stylesheet" href="assets/css/site-launch-polish.css?v=20261113-launch2">
  <?php if ($currentPage === 'live_inbox'): ?><link rel="stylesheet" href="assets/css/live-inbox.css?v=20261113-live1"><?php endif; ?>
  <link rel="stylesheet" href="assets/css/admin-editorial.css?v=20261112a">
  <?php if ($user && (string)($user['role'] ?? '') !== 'client'): ?><link rel="stylesheet" href="assets/css/admin-workspace-refresh.css?v=20261113a"><?php endif; ?>
  <?php if ($user && $currentPage === 'job'): ?><link rel="stylesheet" href="assets/css/job-workspace.css?v=20261113b"><?php endif; ?>
  <?php if (!$user && in_array($currentPage, ['login', 'register', 'forgot_password'], true)): ?>
    <link rel="stylesheet" href="assets/css/auth-clean.css">
    <link rel="stylesheet" href="assets/css/auth-compact.css?v=20261112c">
  <?php endif; ?>
  <style>
    :root {
      --brand-primary: <?= e(brand_primary_color()) ?>;
      --brand-primary-dark: <?= e(brand_primary_dark_color()) ?>;
      --brand-accent: <?= e(brand_accent_color()) ?>;
    }
    body { font-family: <?= !$user && in_array($currentPage, ['login', 'register', 'forgot_password'], true) ? "'Inter'" : "'Outfit'" ?>, sans-serif; }
  </style>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($user): ?>
<div class="portal-layout">
  <aside class="portal-sidebar" id="portalSidebar">
    <div class="sidebar-head">
      <a class="sidebar-brand fw-bold" href="<?= e(url('dashboard')) ?>" aria-label="<?= e(brand_name()) ?> dashboard">
        <span class="sidebar-brand-full">
          <?php if ($logo !== ''): ?>
            <img src="<?= e($logo) ?>" alt="Logo" class="brand-logo">
          <?php else: ?>
            <span class="brand-text"><?= e(brand_name()) ?> <span>Portal</span></span>
          <?php endif; ?>
        </span>
        <span class="sidebar-brand-mini" aria-hidden="true">
          <?php if ($favicon !== ''): ?>
            <img src="<?= e($favicon) ?>" alt="" class="sidebar-favicon-logo">
          <?php elseif ($logo !== ''): ?>
            <img src="<?= e($logo) ?>" alt="" class="sidebar-favicon-logo">
          <?php else: ?>
            <i class="bi bi-grid-1x2-fill"></i>
          <?php endif; ?>
        </span>
      </a>
      <span class="iw-ops-sidebar-role"><?= e(ucfirst((string)($user['role'] ?? 'team'))) ?> workspace</span>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarCloseBtn" aria-label="Close menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <?php
        $portalGroups = [
          'dashboard'=>'OVERVIEW',
          'jobs'=>'SERVICE DELIVERY','support'=>'SERVICE DELIVERY',
          'messages'=>'COMMUNICATION','live_inbox'=>'COMMUNICATION','community'=>'COMMUNICATION',
          'services'=>'SERVICE CATALOGUE','forms'=>'SERVICE CATALOGUE',
          'blog'=>'WEBSITE CONTENT','editorial_showcase'=>'WEBSITE CONTENT','frontend'=>'WEBSITE CONTENT','media'=>'WEBSITE CONTENT',
          'invoices'=>'FINANCE','payments'=>'FINANCE','payouts'=>'FINANCE','withdrawals'=>'FINANCE',
          'users'=>'TEAM & SETTINGS','settings'=>'TEAM & SETTINGS','system_health'=>'TEAM & SETTINGS',
          'exports'=>'REPORTS',
        ];
        $lastGroup='';
      ?>
      <?php foreach ($sidebarItems as $item): ?>
        <?php $group=$portalGroups[$item['page']]??'ADMINISTRATION';
          if ($group!==$lastGroup): ?><span class="iw-admin-nav-label"><?= e($group) ?></span><?php $lastGroup=$group; endif; ?>
        <a class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>" href="<?= e($item['url']) ?>" title="<?= e($item['label']) ?>">
          <i class="bi <?= e($item['icon']) ?>"></i>
          <span class="sidebar-label"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
      <a class="sidebar-link iw-ops-site-link" href="<?= e(public_url('home')) ?>" target="_blank" rel="noopener" title="View public website">
        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
        <span class="sidebar-label">View website</span>
      </a>
      <a class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="<?= e(url('profile')) ?>" title="My Profile">
        <i class="bi bi-person-circle"></i>
        <span class="sidebar-label">My Profile</span>
      </a>
      <a class="sidebar-link" href="logout.php" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
        <span class="sidebar-label">Logout</span>
      </a>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="portal-main">
    <header class="portal-topbar">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarOpenBtn" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary d-none d-lg-inline-flex" type="button" id="sidebarCollapseBtn" aria-label="Toggle sidebar">
          <i class="bi bi-layout-sidebar-inset"></i>
        </button>
        <div class="topbar-title"><?= e($pageHeading) ?></div>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <a class="btn btn-sm btn-outline-secondary iw-ops-preview-link" href="<?= e(public_url($publicPreviewPage)) ?>" target="_blank" rel="noopener" title="Open corresponding public website page"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="d-none d-md-inline">View website</span></a>
        <a class="btn btn-sm btn-outline-secondary position-relative" id="topbarNotificationBtn" href="<?= e(url('notifications')) ?>" title="Notifications">
          <i class="bi bi-bell"></i>
          <span
            id="topbarUnreadBadge"
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger <?= $unreadCount > 0 ? '' : 'd-none' ?>"
            data-unread="<?= (int) $unreadCount ?>"
            data-latest="<?= (int) $latestNotificationId ?>"
          >
            <?= (int) $unreadCount ?>
          </span>
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('profile')) ?>"><?= e($user['full_name']) ?></a>
        <a class="btn btn-sm btn-primary" href="logout.php">Logout</a>
      </div>
    </header>

    <main class="portal-content">
      <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($msg = flash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (is_array($portalHint) && $currentPage === 'dashboard'): ?>
        <div class="alert alert-info d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2" role="alert">
          <div>
            <strong><?= e((string) ($portalHint['title'] ?? 'Guide')) ?>:</strong>
            <?= e((string) ($portalHint['message'] ?? '')) ?>
          </div>
          <?php if (!empty($portalHint['url'])): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e((string) $portalHint['url']) ?>"><?= e((string) ($portalHint['button'] ?? 'Open')) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
<?php else: ?>
<?php if ($currentPage === 'login'): ?>
<main class="auth-main">
  <div class="auth-main-inner container">
<?php else: ?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e(url('login')) ?>">
      <?php if ($logo !== ''): ?>
        <img src="<?= e($logo) ?>" alt="Logo" class="brand-logo">
      <?php else: ?>
        <span class="brand-text"><?= e(brand_name()) ?> <span>Portal</span></span>
      <?php endif; ?>
    </a>

    <div class="d-flex align-items-center gap-2">
      <?php if ($currentPage !== 'login'): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('login')) ?>">Login</a>
      <?php endif; ?>
      <?php if ($currentPage !== 'register'): ?>
        <a class="btn btn-sm btn-primary" href="<?= e(url('register')) ?>">Create account</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php endif; ?>
  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
<?php endif; ?>
