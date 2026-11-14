<?php
/**
 * One editorial product detail layout for every published service. The
 * routed $service comes from iworks_customer_published_service(), including
 * real admin-managed CAC price variants and the existing checkout policy.
 *
 * No duplicate pricing, product IDs, or client-side order calculations.
 */
require_once __DIR__ . '/../app/public_marketing.php';
require_once __DIR__ . '/../app/public-photo-config.php';
$service = is_array($service ?? null) ? $service : [];
$serviceName = trim((string) ($service['name'] ?? 'Service'));
$serviceSlug = trim((string) ($service['slug'] ?? ''));
$categoryName = ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')));
$shortDescription = trim((string) ($service['short_description'] ?? ''));
$description = (string) ($service['description'] ?? '');
if (strcasecmp($serviceName, 'Launch a Company Package') === 0) {
    // Older CMS fixture text must not present banking as a current service.
    $description = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $description);
    $shortDescription = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $shortDescription);
}
$safeDescription = sanitize_limited_html($description);
$included = array_values(array_filter(array_map(
    static fn($value): string => trim(is_string($value) ? $value : ''),
    is_array($service['featured_bullets'] ?? null) ? $service['featured_bullets'] : []
)));
$guide = iworks_search_service_guide($service);
$steps = is_array($guide['steps'] ?? null) ? $guide['steps'] : [];
$questions = is_array($guide['questions'] ?? null) ? $guide['questions'] : [];
$pricing = iworks_customer_product_price($service);
$isEnquiry = (bool) $pricing['is_enquiry'];
$registrationType = (string) ($service['_iworks_registration_type'] ?? '');
$actionText = $isEnquiry
    ? 'Request a specialist quote'
    : ($registrationType === 'business'
        ? 'Start Business Name registration'
        : ($registrationType === 'company'
            ? 'Start Limited Company registration'
            : ($serviceSlug === 'trademark' ? 'Start trademark application' : 'Start this service')));
$actionUrl = $isEnquiry
    ? public_url('contact')
    : portal_url('login', ['service' => $serviceSlug]);
$actionHelp = $isEnquiry;
$featured = !empty($service['featured_image_id'])
    ? get_media_asset((int) $service['featured_image_id'])
    : null;
$imageUrl = iworks_service_photo_url($service, trim((string) ($featured['url'] ?? '')));
$relatedServices = [];
foreach (is_array($publicServices ?? null) ? $publicServices : [] as $item) {
    if (!is_array($item)
        || (string) ($item['slug'] ?? '') === $serviceSlug
        || (string) ($item['category'] ?? '') !== (string) ($service['category'] ?? '')
        || empty($item['slug'])
        || empty($item['name'])) {
        continue;
    }
    $relatedServices[] = $item;
    if (count($relatedServices) === 3) {
        break;
    }
}
?>
<main class="public-main iw-detail iw-product" id="main-content">
  <div class="container public-container iw-product-shell">
    <nav class="iw-product-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(public_url('home')) ?>">Home</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="<?= e(public_url('services')) ?>">Services</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span aria-current="page"><?= e($serviceName) ?></span>
    </nav>

    <header class="iw-product-heading">
      <span class="iw-product-category"><?= e($categoryName) ?></span>
      <h1><?= e($serviceName) ?></h1>
      <?php if ($shortDescription !== ''): ?>
        <p class="iw-product-lead"><?= e($shortDescription) ?></p>
      <?php endif; ?>
    </header>

    <div class="iw-product-layout">
      <aside class="iw-product-side" aria-label="Service pricing and next step">
        <div class="iw-product-order">
          <span class="iw-product-price-caption"><?= e((string) $pricing['label']) ?></span>
          <div class="iw-product-price"><?= e((string) $pricing['display']) ?></div>
          <?php if ($pricing['tax_note'] !== ''): ?>
            <p class="iw-product-tax"><?= e((string) $pricing['tax_note']) ?></p>
          <?php endif; ?>
          <?php if ($actionHelp): ?>
            <a class="iw-product-cta" href="<?= e($actionUrl) ?>" data-iw-help-open data-iw-service="<?= e($serviceName) ?>">
              <?= e($actionText) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          <?php else: ?>
            <a class="iw-product-cta" href="<?= e($actionUrl) ?>">
              <?= e($actionText) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          <?php endif; ?>
          <p class="iw-product-order-note"><?= $isEnquiry
              ? 'We will review your requirements and confirm the scope and fees before proceeding.'
              : 'Enter your requirements securely and track each update in your iWorks account.' ?></p>
          <div class="iw-product-order-help">
            <span>Questions before you begin?</span>
            <a href="<?= e(public_url('contact')) ?>" data-iw-help-open data-iw-service="<?= e($serviceName) ?>">Talk to our team <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </div>
        </div>
      </aside>

      <article class="iw-product-content">
        <?php if ($imageUrl !== ''): ?>
          <figure class="iw-product-media iw-photo-feature iw-photo-feature-product">
            <img src="<?= e($imageUrl) ?>" alt="<?= e(str_starts_with($imageUrl, '/assets/photography/') ? 'Illustrative photograph for ' . $serviceName : $serviceName) ?>" width="960" height="540" loading="eager" decoding="async">
          </figure>
        <?php endif; ?>

        <section class="iw-product-section" id="product-overview" aria-labelledby="product-overview-title">
          <h2 id="product-overview-title">Overview</h2>
          <div class="iw-product-description">
            <?php if ($safeDescription !== ''): ?>
              <?= $safeDescription ?>
            <?php else: ?>
              <p>Review the service scope, share your requirements and manage progress from your account. We will confirm any external requirements before processing begins.</p>
            <?php endif; ?>
          </div>
        </section>

        <?php if ($included !== []): ?>
          <section class="iw-product-section" id="product-included" aria-labelledby="product-included-title">
            <h2 id="product-included-title">What's included</h2>
            <ul class="iw-product-included">
              <?php foreach ($included as $feature): ?>
                <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span><?= e($feature) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ($guide !== []): ?>
          <section class="iw-product-section" id="product-process" aria-labelledby="product-process-title">
            <h2 id="product-process-title"><?= e((string) ($guide['heading'] ?? 'How this service works')) ?></h2>
            <?php if (!empty($guide['intro'])): ?>
              <p class="iw-product-process-intro"><?= e((string) $guide['intro']) ?></p>
            <?php endif; ?>
            <?php if ($steps !== []): ?>
              <ol class="iw-product-steps">
                <?php foreach ($steps as $index => $step): ?>
                  <?php if (!is_array($step)) { continue; } ?>
                  <li>
                    <span class="iw-product-step-number" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <div><h3><?= e((string) ($step[0] ?? 'Step ' . ($index + 1))) ?></h3>
                      <p><?= e((string) ($step[1] ?? '')) ?></p></div>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>
          </section>
        <?php endif; ?>

        <?php if ($questions !== []): ?>
          <section class="iw-product-section" id="product-faq" aria-labelledby="product-faq-title">
            <h2 id="product-faq-title">Frequently asked questions</h2>
            <div class="iw-product-faq">
              <?php foreach ($questions as $question): ?>
                <?php if (!is_array($question)) { continue; } ?>
                <details>
                  <summary><?= e((string) ($question[0] ?? 'Question')) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
                  <p><?= e((string) ($question[1] ?? '')) ?></p>
                </details>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if (!empty($guide['official'])): ?>
          <p class="iw-product-source">Official requirements and decisions remain with the relevant regulator.
            <a href="<?= e((string) $guide['official']) ?>" target="_blank" rel="noopener noreferrer">Read the official guidance <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </p>
        <?php endif; ?>
      </article>
    </div>

    <?php if ($relatedServices !== []): ?>
      <nav class="iw-product-related" aria-label="Related services">
        <div class="iw-product-related-heading">
          <h2>Explore related services</h2>
          <a href="<?= e(public_url('services')) ?>">All services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <ul>
          <?php foreach ($relatedServices as $related): ?>
            <li><a href="<?= e(public_url('service', ['slug' => (string) $related['slug']])) ?>">
              <?= e((string) $related['name']) ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</main>
