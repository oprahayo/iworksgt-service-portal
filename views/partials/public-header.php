<?php
$logo = logo_path();
$favicon = favicon_path();
$seoDescriptionText = seo_description();
$seoTagsText = seo_tags();
$pageSeo = is_array($pageSeo ?? null) ? $pageSeo : [];
$publicHeaderConfig = is_array($publicHeaderConfig ?? null) ? $publicHeaderConfig : get_public_header_config();
$publicServiceCategories = is_array($publicServiceCategories ?? null) ? $publicServiceCategories : list_public_service_categories();
$metaTitle = trim((string) ($pageSeo['metaTitle'] ?? ''));
$metaDescription = trim((string) ($pageSeo['metaDescription'] ?? ''));
$ogImage = normalize_public_asset_path((string) ($pageSeo['ogImage'] ?? ''));
$publicTitle = $title ?: app_name();
if ($metaTitle !== '') {
    $publicTitle = $metaTitle;
}
if ($metaDescription === '') {
    $metaDescription = $seoDescriptionText;
}
if ($ogImage === '') {
    $ogImage = $logo ?: $favicon;
}
$bodyClass = 'portal-guest page-public' . ((string) ($publicPageSlug ?? $page ?? '') === 'home' ? ' page-public-home' : '') . ((string) ($publicPageSlug ?? $page ?? '') === 'services' ? ' page-public-services' : '');
$headerAnnouncement = trim((string) ($publicHeaderConfig['announcement'] ?? ''));
$aboutLabel = trim((string) ($publicHeaderConfig['aboutLabel'] ?? 'About'));
$aboutHref = normalize_public_href(trim((string) ($publicHeaderConfig['aboutHref'] ?? public_url('about'))), public_url('about'));
$managementLabel = trim((string) ($publicHeaderConfig['managementLabel'] ?? 'Management'));
$managementHref = normalize_public_href(trim((string) ($publicHeaderConfig['managementHref'] ?? public_url('management'))), public_url('management'));
$useCasesLabel = trim((string) ($publicHeaderConfig['useCasesLabel'] ?? 'Case Studies'));
$useCasesHref = normalize_public_href(trim((string) ($publicHeaderConfig['useCasesHref'] ?? public_url('case-studies'))), public_url('case-studies'));
$blogLabel = trim((string) ($publicHeaderConfig['blogLabel'] ?? 'Blog'));
$blogHref = normalize_public_href(trim((string) ($publicHeaderConfig['blogHref'] ?? public_url('blog'))), public_url('blog'));
$contactLabel = trim((string) ($publicHeaderConfig['contactLabel'] ?? 'Contact Us'));
$contactHref = normalize_public_href(trim((string) ($publicHeaderConfig['contactHref'] ?? public_url('contact'))), public_url('contact'));
$signInLabel = trim((string) ($publicHeaderConfig['signInLabel'] ?? 'Sign In'));
$ctaLabel = trim((string) ($publicHeaderConfig['ctaLabel'] ?? 'Talk to our experts'));
$ctaHref = normalize_public_href(trim((string) ($publicHeaderConfig['ctaHref'] ?? public_url('services'))), public_url('services'));
// Short, unambiguous default CTA. Customized CMS labels remain respected.
if (strcasecmp($ctaLabel, 'Talk to our experts') === 0) {
    $ctaLabel = 'Get started';
    $ctaHref = public_url('services');
}
$currentPublicSlug = trim((string) ($publicPageSlug ?? ($page ?? 'home')));
$isAboutActive = $currentPublicSlug === 'about';
$isManagementActive = $currentPublicSlug === 'management';
$isUseCasesActive = $currentPublicSlug === 'case-studies';
$isServicesActive = in_array($currentPublicSlug, ['services', 'service'], true);
$isBlogActive = in_array($currentPublicSlug, ['blog', 'blog_post'], true);
$isContactActive = $currentPublicSlug === 'contact';
$isLaunchHome = $currentPublicSlug === 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($publicTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if ($seoTagsText !== ''): ?>
    <meta name="keywords" content="<?= e($seoTagsText) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="<?= e(brand_primary_color()) ?>">
  <meta property="og:title" content="<?= e($publicTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
  <?php if ($favicon !== ''): ?>
    <link rel="icon" type="image/png" href="<?= e($favicon) ?>">
    <link rel="apple-touch-icon" href="<?= e($favicon) ?>">
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/app.css')) ?>">
  <!-- Minimal public inner-page styles are applied after the shared site styles. -->
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="dns-prefetch" href="//images.unsplash.com">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-unified.css')) ?>">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-clean.css')) ?>">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-inner-clean.css')) ?>">
  <style>
    :root {
      --brand-primary: <?= e(brand_primary_color()) ?>;
      --brand-primary-dark: <?= e(brand_primary_dark_color()) ?>;
      --brand-accent: <?= e(brand_accent_color()) ?>;
    }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  </style>
</head>
<body class="<?= e($bodyClass) ?>">
  <?php if ($headerAnnouncement !== '' && !$isLaunchHome): ?>
    <div class="public-announcement-bar">
      <div class="container public-container">
        <span><?= e($headerAnnouncement) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <header class="public-navbar sticky-top">
    <div class="container public-container">
      <nav class="navbar navbar-expand-xl public-nav-shell" aria-label="Main navigation">
        <a class="navbar-brand d-flex align-items-center public-brand" href="<?= e(public_url('home')) ?>" aria-label="<?= e(brand_name()) ?> home">
          <?php if ($logo !== ''): ?>
            <img src="<?= e($logo) ?>" alt="<?= e(brand_name()) ?>" class="public-logo">
          <?php else: ?>
            <span class="public-wordmark"><?= e(brand_name()) ?></span>
          <?php endif; ?>
        </a>
        <div class="public-nav-actions d-xl-none">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse" id="publicNav">
          <ul class="navbar-nav mx-auto align-items-xl-center public-nav-center">
            <li class="nav-item"><a class="nav-link<?= $isServicesActive ? ' active' : '' ?>" href="<?= e(public_url('services')) ?>"<?= $isServicesActive ? ' aria-current="page"' : '' ?>>Services</a></li>
            <?php if ($aboutLabel !== ''): ?><li class="nav-item"><a class="nav-link<?= $isAboutActive ? ' active' : '' ?>" href="<?= e($aboutHref) ?>"<?= $isAboutActive ? ' aria-current="page"' : '' ?>><?= e($aboutLabel) ?></a></li><?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(public_url('track')) ?>">Track order</a></li>
            <?php if ($contactLabel !== ''): ?><li class="nav-item"><a class="nav-link<?= $isContactActive ? ' active' : '' ?>" href="<?= e($contactHref) ?>"<?= $isContactActive ? ' aria-current="page"' : '' ?>><?= e($contactLabel) ?></a></li><?php endif; ?>
          </ul>
          <div class="public-header-cta-group">
            <a class="public-signin-link" href="<?= e(portal_url('login')) ?>"><?= e($signInLabel) ?></a>
            <a class="btn btn-primary btn-sm public-header-main-cta" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
          </div>
        </div>
      </nav>
    </div>
  </header>

