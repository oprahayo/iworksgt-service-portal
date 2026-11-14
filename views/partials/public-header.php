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
$seoPageType = (string) ($publicPageSlug ?? $page ?? 'home');
if ($seoPageType === 'service' && is_array($service ?? null)) {
    $metaDescription = iworks_seo_excerpt((string) ($service['short_description'] ?? $service['description'] ?? ''));
} elseif ($seoPageType === 'blog_post' && is_array($blogPost ?? null)) {
    $metaDescription = iworks_seo_excerpt((string) ($blogPost['excerpt'] ?? ''));
} else {
    $metaDescription = iworks_seo_excerpt($metaDescription);
}
if ($metaDescription === '') {
    $metaDescription = 'Business registration, licensing, compliance, web development and digital services from iWORKS GLOBAL LTD.';
}
// Prefer focused, truthful search snippets over generated titles and short
// placeholders. Respect explicit CMS-authored SEO fields on ordinary pages.
$searchProfile = $seoPageType === 'service' && is_array($service ?? null)
    ? iworks_search_service_meta($service)
    : iworks_search_page_meta($seoPageType);
if ($searchProfile !== []) {
    if ($seoPageType === 'service') {
        $generatedTitle = trim((string) ($service['name'] ?? '')) . ' - ' . brand_name();
        if ($metaTitle === '' || $metaTitle === $generatedTitle) {
            $publicTitle = (string) $searchProfile['title'];
        }
        $metaDescription = (string) $searchProfile['description'];
    } else {
        if ($metaTitle === '') { $publicTitle = (string) $searchProfile['title']; }
        if (trim((string) ($pageSeo['metaDescription'] ?? '')) === '') {
            $metaDescription = (string) $searchProfile['description'];
        }
    }
}
$seoIndexable = http_response_code() !== 404 && !in_array($seoPageType, ['track'], true);
$seoCanonicalUrl = $seoIndexable ? iworks_seo_canonical($seoPageType, $_GET) : '';
$seoRecord = $seoPageType === 'service'
    ? (is_array($service ?? null) ? $service : [])
    : ($seoPageType === 'blog_post' ? (is_array($blogPost ?? null) ? $blogPost : [])
      : ($seoPageType === 'case_study' ? (is_array($caseRecord ?? null) ? $caseRecord : []) : []));
$seoJsonLd = $seoIndexable
    ? iworks_seo_jsonld($seoPageType, $seoRecord, $seoCanonicalUrl, $metaDescription)
    : '';
$seoBreadcrumb = $seoIndexable ? iworks_seo_breadcrumb_jsonld($seoPageType, $seoRecord, $seoCanonicalUrl) : '';
$seoOgImage = $ogImage !== '' ? public_absolute_url($ogImage) : '';
$bodyClass = 'portal-guest page-public' . ((string) ($publicPageSlug ?? $page ?? '') === 'home' ? ' page-public-home' : '') . ((string) ($publicPageSlug ?? $page ?? '') === 'services' ? ' page-public-services' : '') . ((string) ($publicPageSlug ?? $page ?? '') === 'blog_post' ? ' page-public-article' : '');
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
$isGuestOnboardingCta = strcasecmp($ctaLabel, 'Get started') === 0;
$currentPublicSlug = trim((string) ($publicPageSlug ?? ($page ?? 'home')));
$isAboutActive = $currentPublicSlug === 'about';
$isManagementActive = $currentPublicSlug === 'management';
$isUseCasesActive = $currentPublicSlug === 'case-studies';
$isServicesActive = in_array($currentPublicSlug, ['services', 'service'], true);
$isBlogActive = in_array($currentPublicSlug, ['blog', 'blog_post'], true);
$isContactActive = $currentPublicSlug === 'contact';
$isTrackActive = $currentPublicSlug === 'track';
$isLaunchHome = $currentPublicSlug === 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($publicTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if (!$seoIndexable): ?>
    <meta name="robots" content="noindex,follow">
  <?php endif; ?>
  <?php if ($seoCanonicalUrl !== ''): ?>
    <link rel="canonical" href="<?= e($seoCanonicalUrl) ?>">
  <?php endif; ?>
  <?php if ($seoTagsText !== ''): ?>
    <meta name="keywords" content="<?= e($seoTagsText) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="<?= e(brand_primary_color()) ?>">
  <meta property="og:title" content="<?= e($publicTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:type" content="<?= $seoPageType === 'blog_post' ? 'article' : 'website' ?>">
  <?php if ($seoCanonicalUrl !== ''): ?><meta property="og:url" content="<?= e($seoCanonicalUrl) ?>"><?php endif; ?>
  <?php if ($seoOgImage !== ''): ?><meta property="og:image" content="<?= e($seoOgImage) ?>"><?php endif; ?>
  <meta name="twitter:card" content="<?= $seoOgImage !== '' ? 'summary_large_image' : 'summary' ?>">
  <meta name="twitter:title" content="<?= e($publicTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <?php if ($seoJsonLd !== ''): ?><script type="application/ld+json"><?= $seoJsonLd ?></script><?php endif; ?>
  <?php if ($seoBreadcrumb !== ''): ?><script type="application/ld+json"><?= $seoBreadcrumb ?></script><?php endif; ?>
  <?php require __DIR__ . '/favicon-head.php'; ?>
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
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-clean.css')) ?>?v=20261114-refine1">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-polish.css')) ?>">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/reviews.css')) ?>">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/ux-finish.css')) ?>?v=20261112b">
  <!-- Shared auth inputs rendered directly in dialog, no nested page -->
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/auth-clean.css')) ?>?v=20261112b">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-layout-fixes.css')) ?>?v=20261114-refine1">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/auth-compact.css')) ?>?v=20261112c">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-inner-clean.css')) ?>">
  <?php if ($currentPublicSlug === 'services'): ?><link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/services-list.css')) ?>?v=20261112c"><?php endif; ?>
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-mobile-audit.css')) ?>?v=20261112launch1">
  <?php
  // The public site delegates to the portal PHP app, but on some cPanel
  // setups static assets aren't mapped to the same root. Inline this small
  // critical widget stylesheet so chat/popup styling never depends on the
  // apex-domain asset mirroring configuration.
  $iwAssistCssPath = __DIR__ . '/../../assets/css/public-assistance.css';
  if (is_file($iwAssistCssPath) && is_readable($iwAssistCssPath)): ?>
    <style id="iw-assist-critical-css"><?= (string) file_get_contents($iwAssistCssPath) ?></style>
  <?php else: ?>
    <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-assistance.css')) ?>?v=20261114-launcher1">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-navigation-cta.css')) ?>?v=20261113a">
  <script>
  // Public website now uses one consistent light theme; ignore older saved
  // dark-mode choices from visitors' previous sessions.
  document.documentElement.dataset.iwTheme='light';
  document.documentElement.dataset.iwThemeMode='light';
  </script>
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/editorial-brand.css')) ?>?v=20261114-refine1">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/public-editorial-refinement.css')) ?>?v=20261113-live1">
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/brand-color-pass.css')) ?>?v=20261113-live1">
  <?php $iwLaunchPolishCss = __DIR__.'/../../assets/css/site-launch-polish.css'; ?>
  <?php if (is_file($iwLaunchPolishCss) && is_readable($iwLaunchPolishCss)): ?>
    <style id="iw-site-launch-polish"><?= (string)file_get_contents($iwLaunchPolishCss) ?></style>
  <?php else: ?>
    <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/site-launch-polish.css')) ?>?v=20261114-color2">
  <?php endif; ?>
  <?php if (in_array($currentPublicSlug, ['blog', 'blog_post'], true)): ?>
    <?php
      // Serve the journal styling directly when split cPanel document roots
      // cannot expose /portal/assets on the apex domain.
      $iwJournalCss = __DIR__ . '/../../assets/css/blog-journal.css';
    ?>
    <?php if (is_file($iwJournalCss) && is_readable($iwJournalCss)): ?>
      <style id="iw-journal-css"><?= (string) file_get_contents($iwJournalCss) ?></style>
    <?php else: ?>
      <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/blog-journal.css')) ?>?v=20261113-journal1">
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($currentPublicSlug === 'blog_post'): ?>
    <?php
      // Critical article typography is available on the split-root public
      // domain even when /assets/ does not resolve there. Load AFTER journal CSS.
      $iwReadingCss = __DIR__ . '/../../assets/css/blog-reading.css';
    ?>
    <?php if (is_file($iwReadingCss) && is_readable($iwReadingCss)): ?>
      <style id="iw-reading-critical-css"><?= (string) file_get_contents($iwReadingCss) ?></style>
    <?php else: ?>
      <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/blog-reading.css')) ?>?v=20261113-reading2">
    <?php endif; ?>
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/iworks-responsive-logo.css')) ?>?v=20261113-logo1">

  <?php if ($currentPublicSlug === 'service'): ?>
    <?php
      // Split-root cPanel can serve the public site through the portal PHP
      // tree without sharing the apex /assets mapping. Inline this scoped
      // stylesheet AFTER the other public styles, with an asset fallback.
      $iwProductCss = __DIR__ . '/../../assets/css/product-detail.css';
    ?>
    <?php if (is_file($iwProductCss) && is_readable($iwProductCss)): ?>
      <style id="iw-product-detail-css"><?= (string) file_get_contents($iwProductCss) ?></style>
    <?php else: ?>
      <link rel="stylesheet" href="<?= e(public_absolute_url('assets/css/product-detail.css')) ?>?v=20261113-product1">
    <?php endif; ?>
  <?php endif; ?>
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
          <picture class="iw-brand-picture">
            <source media="(max-width:1199.98px)" srcset="<?= e(public_absolute_url('assets/branding/iworks-compact.svg')) ?>?v=20261113-logo1">
            <img src="<?= e(public_absolute_url('assets/branding/iworks-desktop.svg')) ?>?v=20261113-logo1" alt="<?= e(brand_name()) ?>" class="public-logo iw-desktop-logo" width="224" height="21" decoding="async">
          </picture>
        </a>
        <div class="public-nav-actions d-xl-none">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse" id="publicNav">
          <div class="iw-mobile-menu d-xl-none" aria-label="Mobile navigation">
            <details class="iw-mobile-menu-group">
              <summary>What we do <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
              <div class="iw-mobile-menu-sub">
                <a href="<?= e(public_url('services')) ?>">All services</a>
                <a href="<?= e(public_url('services', ['category' => 'business-registration'])) ?>">Business registration &amp; compliance</a>
                <a href="<?= e(public_url('services', ['category' => 'trademark'])) ?>">Trademarks</a>
                <a href="<?= e(public_url('services', ['category' => 'growth'])) ?>">Websites, hosting &amp; digital growth</a>
              </div>
            </details>
            <details class="iw-mobile-menu-group">
              <summary>Explore iWorks <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
              <div class="iw-mobile-menu-sub">
                <a href="<?= e(public_url('services')) ?>">Find a service</a>
                <a href="<?= e(public_url('track')) ?>">Track an order</a>
                <a href="<?= e(public_url('blog')) ?>">Insights &amp; guides</a>
              </div>
            </details>
            <details class="iw-mobile-menu-group">
              <summary>About <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
              <div class="iw-mobile-menu-sub">
                <a href="<?= e(public_url('about')) ?>">Our company</a>
                <a href="<?= e(public_url('case-studies')) ?>">Case studies</a>
                <a href="<?= e(public_url('contact')) ?>">Contact</a>
              </div>
            </details>
            <a class="iw-mobile-menu-link" href="<?= e(public_url('services')) ?>">Pricing &amp; services</a>
            <div class="iw-mobile-menu-bottom">
              <a class="iw-mobile-menu-signin iw-public-auth-status" data-iw-auth="login" data-iw-auth-status href="<?= e(portal_url('login')) ?>">Sign in</a>
              <button type="button" class="iw-mobile-menu-book" data-assist-book-open><i class="bi bi-calendar3" aria-hidden="true"></i> Request a consultation</button>
              <a class="iw-mobile-menu-start" data-iw-guest-cta href="<?= e(public_url('services')) ?>">Get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </div>
          <ul class="navbar-nav mx-auto align-items-xl-center public-nav-center">
            <li class="nav-item"><a class="nav-link<?= $isServicesActive ? ' active' : '' ?>" href="<?= e(public_url('services')) ?>"<?= $isServicesActive ? ' aria-current="page"' : '' ?>>Services</a></li>
            <?php if ($aboutLabel !== ''): ?><li class="nav-item"><a class="nav-link<?= $isAboutActive ? ' active' : '' ?>" href="<?= e($aboutHref) ?>"<?= $isAboutActive ? ' aria-current="page"' : '' ?>><?= e($aboutLabel) ?></a></li><?php endif; ?>
            <li class="nav-item"><a class="nav-link<?= $isTrackActive ? ' active' : '' ?>" href="<?= e(public_url('track')) ?>"<?= $isTrackActive ? ' aria-current="page"' : '' ?>>Track order</a></li>
            <li class="nav-item"><a class="nav-link<?= $isBlogActive ? ' active' : '' ?>" href="<?= e(public_url('blog')) ?>"<?= $isBlogActive ? ' aria-current="page"' : '' ?>>Insights</a></li>
            <?php if ($contactLabel !== ''): ?><li class="nav-item"><a class="nav-link<?= $isContactActive ? ' active' : '' ?>" href="<?= e($contactHref) ?>"<?= $isContactActive ? ' aria-current="page"' : '' ?>><?= e($contactLabel) ?></a></li><?php endif; ?>
          </ul>
          <div class="public-header-cta-group">
            <a class="public-signin-link iw-public-auth-status" href="<?= e(portal_url('login')) ?>" data-iw-auth="login" data-iw-auth-status title="Sign in" aria-label="Sign in to your iWorks account"><span><?= e($signInLabel?:'Sign In') ?></span></a>
            <a class="btn btn-primary btn-sm public-header-main-cta"<?= $isGuestOnboardingCta ? ' data-iw-guest-cta' : '' ?> href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
          </div>
        </div>
      </nav>
    </div>
  </header>

