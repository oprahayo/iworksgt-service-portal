<?php
/**
 * iWorks public homepage — deliberately minimal.
 *
 * Uses the existing published CMS and service catalog. This view does not
 * create or alter orders, payments, customer accounts or service records.
 */
require_once __DIR__ . '/../app/public-photo-config.php';

$sections = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (!is_array($block)) {
        continue;
    }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if ($type !== '' && !array_key_exists($type, $sections)) {
        $sections[$type] = is_array($block['data'] ?? null) ? $block['data'] : [];
    }
}

$hero = $sections['hero'] ?? [];
$featured = $sections['featured_services'] ?? [];
$how = $sections['how_it_works'] ?? [];
$faq = $sections['faq'] ?? [];
$banner = $sections['cta_banner'] ?? [];

$defaultHeadlines = [
    'Launch and grow your business remotely with iWorks Global.',
    'Launch and grow your business remotely with iWorks Global',
];
$oldHeadline = trim((string) ($hero['headline'] ?? ''));
$headline = $oldHeadline !== '' && !in_array($oldHeadline, $defaultHeadlines, true)
    ? $oldHeadline
    : 'Start, protect and grow your business.';

$defaultIntroductions = [
    'CAC registration, trademark filing, NAFDAC-related support, branding, growth services, and structured business operations handled in one portal.',
];
$oldIntroduction = trim((string) ($hero['subheadline'] ?? ''));
$introduction = $oldIntroduction !== '' && !in_array($oldIntroduction, $defaultIntroductions, true)
    ? $oldIntroduction
    : 'Business registration, trademark and digital services. Simple steps, helpful support and updates in one place.';

$heroAsset = !empty($hero['imageId']) ? get_media_asset((int) $hero['imageId']) : null;
$heroImage = trim((string) ($heroAsset['url'] ?? '')) ?: iworks_stock_photo_url('consultation', 1100);

$heroPrimary = is_array($hero['primaryCta'] ?? null) ? $hero['primaryCta'] : [];
$primaryLabel = trim((string) ($heroPrimary['label'] ?? ''));
$primaryLabel = in_array($primaryLabel, ['', 'Explore our services', 'Get Started Now'], true) ? 'Explore services' : $primaryLabel;
$primaryHref = normalize_public_href(
    trim((string) ($heroPrimary['href'] ?? public_url('services'))),
    public_url('services')
);

$catalog = array_values(array_filter(is_array($publicServices ?? null) ? $publicServices : [], static function ($service): bool {
    if (!is_array($service)) {
        return false;
    }
    $name = strtolower(trim((string) ($service['name'] ?? '')));
    $slug = trim((string) ($service['slug'] ?? ''));
    if ($name === '' || $slug === '') {
        return false;
    }
    if ($name === 'custom service' && (float) ($service['price'] ?? 0) <= 0) {
        return false;
    }
    foreach (['banking', 'bank account', 'loan', 'payment gateway', 'wallet'] as $excluded) {
        if (str_contains($name, $excluded)) {
            return false;
        }
    }
    return true;
}));

$findService = static function (array $terms, string $category) use ($catalog): string {
    foreach ($terms as $term) {
        foreach ($catalog as $service) {
            if (str_contains(strtolower((string) ($service['name'] ?? '')), $term)) {
                return public_url('service', ['slug' => (string) $service['slug']]);
            }
        }
    }
    return public_url('services', ['category' => $category]);
};

$businessUrl = $findService(['business name registration', 'company registration'], 'business-registration');
$trademarkUrl = $findService(['trademark'], 'trademark');
$websiteUrl = $findService(['website design', 'domain & web hosting'], 'growth');

$serviceHeading = trim((string) ($featured['title'] ?? ''));
if ($serviceHeading === '' || $serviceHeading === 'Services entrepreneurs use most') {
    $serviceHeading = 'What do you need help with?';
}

$steps = array_values(array_filter(is_array($how['steps'] ?? null) ? $how['steps'] : [], 'is_array'));
if ($steps === []) {
    $steps = [
        ['title' => 'Choose a service', 'text' => 'Pick the service that suits your business.'],
        ['title' => 'Submit your details', 'text' => 'Complete checkout and upload the required information.'],
        ['title' => 'Follow progress', 'text' => 'Get updates and final files in your portal.'],
    ];
}
$faqs = array_slice(array_values(array_filter(is_array($faq['items'] ?? null) ? $faq['items'] : [], 'is_array')), 0, 3);

$bannerTitle = trim((string) ($banner['headline'] ?? ''));
if ($bannerTitle === '' || $bannerTitle === 'Launch faster with the right support stack.') {
    $bannerTitle = 'Ready to get started?';
}
?>
<main class="public-main iw-clean-home" id="main-content">
  <?php if ($message = flash('success')): ?>
    <div class="container public-container pt-3"><div class="alert alert-success"><?= e($message) ?></div></div>
  <?php endif; ?>
  <?php if ($message = flash('error')): ?>
    <div class="container public-container pt-3"><div class="alert alert-danger"><?= e($message) ?></div></div>
  <?php endif; ?>

  <section class="iw-clean-hero" aria-labelledby="iw-clean-title">
    <div class="container public-container iw-clean-hero-inner">
      <div class="iw-clean-hero-copy">
        <span class="iw-clean-overline">iWORKS GLOBAL</span>
        <h1 id="iw-clean-title"><?= e($headline) ?></h1>
        <p><?= e($introduction) ?></p>
        <div class="iw-clean-actions">
          <a class="iw-clean-btn iw-clean-btn-primary" href="<?= e($primaryHref) ?>"><?= e($primaryLabel) ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          <a class="iw-clean-text-link" href="<?= e($businessUrl) ?>">Register a business <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <p class="iw-clean-detail">Order online <span aria-hidden="true">·</span> Submit documents <span aria-hidden="true">·</span> Track your request</p>
      </div>
      <figure class="iw-clean-hero-media">
        <img src="<?= e($heroImage) ?>" alt="Professionals reviewing business documents" width="640" height="510" fetchpriority="high" decoding="async">
      </figure>
    </div>
  </section>

  <section class="iw-clean-section iw-clean-services" aria-labelledby="iw-clean-services-title">
    <div class="container public-container">
      <div class="iw-clean-section-head">
        <h2 id="iw-clean-services-title"><?= e($serviceHeading) ?></h2>
        <p>Choose a starting point. Each service connects to your iWorks account.</p>
      </div>
      <div class="iw-clean-service-grid">
        <a class="iw-clean-service" href="<?= e($businessUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
          <h3>Business registration</h3>
          <p>Business names, companies and CAC filing support.</p>
          <span>Explore registration <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="iw-clean-service" href="<?= e($trademarkUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
          <h3>Trademark &amp; compliance</h3>
          <p>Protect your brand and manage essential filings.</p>
          <span>Explore services <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="iw-clean-service" href="<?= e($websiteUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-window" aria-hidden="true"></i></span>
          <h3>Websites &amp; digital</h3>
          <p>Web development, hosting, design and marketing.</p>
          <span>Explore digital <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
      </div>
      <a class="iw-clean-all-services" href="<?= e(public_url('services')) ?>">View all services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="iw-clean-section iw-clean-process" aria-labelledby="iw-clean-process-title">
    <div class="container public-container">
      <div class="iw-clean-section-head">
        <h2 id="iw-clean-process-title">How it works</h2>
        <p>Three straightforward steps, with no unnecessary paperwork upfront.</p>
      </div>
      <div class="iw-clean-step-grid">
        <?php foreach (array_slice($steps, 0, 3) as $index => $step): ?>
          <div class="iw-clean-step">
            <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e((string) ($step['title'] ?? '')) ?></h3>
            <p><?= e((string) ($step['text'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="iw-clean-section iw-clean-support" aria-labelledby="iw-clean-support-title">
    <div class="container public-container iw-clean-support-inner">
      <div class="iw-clean-support-photo">
        <img src="<?= e(iworks_stock_photo_url('strategy', 1000)) ?>" alt="Business professionals discussing documents" loading="lazy" decoding="async" width="560" height="420">
      </div>
      <div class="iw-clean-support-copy">
        <span class="iw-clean-overline">WHY iWORKS</span>
        <h2 id="iw-clean-support-title">Support that stays with you.</h2>
        <p>Find what you need, send your details and follow your job without juggling messages and files across different platforms.</p>
        <a class="iw-clean-text-link" href="<?= e(public_url('track')) ?>">Track an existing order <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <?php if ($faqs !== []): ?>
    <section class="iw-clean-section iw-clean-faq" aria-labelledby="iw-clean-faq-title">
      <div class="container public-container iw-clean-faq-inner">
        <div>
          <h2 id="iw-clean-faq-title">Common questions</h2>
          <p>Need something else? <a href="<?= e(public_url('contact')) ?>">Talk to our team.</a></p>
        </div>
        <div class="iw-clean-faq-items">
          <?php foreach ($faqs as $item): ?>
            <?php
            $question = trim((string) ($item['q'] ?? $item['question'] ?? ''));
            $answer = trim((string) ($item['a'] ?? $item['answer'] ?? ''));
            if ($question === '') { continue; }
            ?>
            <details>
              <summary><?= e($question) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
              <p><?= e($answer) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="iw-clean-last" aria-labelledby="iw-clean-last-title">
    <div class="container public-container iw-clean-last-inner">
      <div>
        <h2 id="iw-clean-last-title"><?= e($bannerTitle) ?></h2>
        <p>Browse a service or speak with our team.</p>
      </div>
      <a class="iw-clean-btn iw-clean-btn-primary" href="<?= e(public_url('services')) ?>">Explore services <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>