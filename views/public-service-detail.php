<?php
/**
 * Clean public service details. Uses the existing published service record,
 * sanitized description, currency, tax and portal login/order entry.
 */
require_once __DIR__ . '/../app/public-photo-config.php';
if (!function_exists('public_service_bullets')) {
    function public_service_bullets(array $service): array
    {
        return array_values(array_filter(array_map('trim',
            is_array($service['featured_bullets'] ?? null) ? $service['featured_bullets'] : []
        )));
    }
}
$service = is_array($service ?? null) ? $service : [];
$serviceName = trim((string) ($service['name'] ?? 'Service'));
$categoryName = ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')));
$description = (string) ($service['description'] ?? '');
$shortDescription = trim((string) ($service['short_description'] ?? ''));
if (strcasecmp($serviceName, 'Launch a Company Package') === 0) {
    $description = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $description);
    $shortDescription = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $shortDescription);
}
$safeDescription = sanitize_limited_html($description);
$bullets = public_service_bullets($service);
$price = (float) ($service['price'] ?? 0);
$priceText = $price > 0 ? money($price, (string) ($service['currency'] ?? default_currency())) : 'Request a quote';
$tax = (float) ($service['tax_percent'] ?? 0);
$featured = !empty($service['featured_image_id']) ? get_media_asset((int) $service['featured_image_id']) : null;
$imageUrl = trim((string) ($featured['url'] ?? ''));
if ($imageUrl === '') {
    $name = strtolower($serviceName . ' ' . (string) ($service['category'] ?? ''));
    $topic = 'consultation';
    if (str_contains($name, 'company') || str_contains($name, 'registration') || str_contains($name, 'cac')) { $topic = 'registration'; }
    elseif (str_contains($name, 'trademark') || str_contains($name, 'compliance')) { $topic = 'strategy'; }
    elseif (str_contains($name, 'web') || str_contains($name, 'hosting') || str_contains($name, 'domain') || str_contains($name, 'digital') || str_contains($name, 'graphic')) { $topic = 'digital'; }
    $imageUrl = iworks_stock_photo_url($topic, 960);
}
?>
<main class="public-main iw-detail" id="main-content">
  <section class="iw-detail-intro">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(public_url('services')) ?>">Services</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page"><?= e($serviceName) ?></span>
      </nav>
      <div class="iw-detail-hero">
        <div class="iw-detail-copy">
          <span class="iw-detail-category"><?= e($categoryName) ?></span>
          <h1><?= e($serviceName) ?></h1>
          <?php if ($shortDescription !== ''): ?><p class="iw-detail-lead"><?= e($shortDescription) ?></p><?php endif; ?>
          <div class="iw-detail-price">
            <span><?= $price > 0 ? 'Starting from' : 'Pricing' ?></span>
            <strong><?= e($priceText) ?></strong>
            <?php if ($tax > 0): ?><small>Tax rate: <?= e(number_format($tax, 2)) ?>%. Final total shown at checkout.</small><?php endif; ?>
          </div>
          <div class="iw-detail-actions">
            <a class="iw-detail-primary" href="<?= e(portal_url('login')) ?>">Continue to order <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <a class="iw-detail-secondary" href="<?= e(public_url('contact')) ?>">Ask a question</a>
          </div>
          <p class="iw-detail-note">Submit requirements and follow updates through your iWorks account.</p>
        </div>
        <div class="iw-detail-photo"><img src="<?= e($imageUrl) ?>" alt="" width="560" height="430" decoding="async" fetchpriority="high"></div>
      </div>
    </div>
  </section>

  <section class="iw-detail-body">
    <div class="container public-container iw-detail-columns">
      <article class="iw-detail-description">
        <h2>About this service</h2>
        <div class="rich-text">
          <?php if ($safeDescription !== ''): ?>
            <?= $safeDescription ?>
          <?php else: ?>
            <p>Review the service scope and complete the required information during checkout. Processing times depend on the selected service and any external regulatory approvals.</p>
          <?php endif; ?>
        </div>
      </article>
      <aside class="iw-detail-aside">
        <?php if ($bullets !== []): ?>
          <div class="iw-detail-includes">
            <h2>What's included</h2>
            <ul>
              <?php foreach (array_slice($bullets, 0, 6) as $bullet): ?>
                <li><i class="bi bi-check2" aria-hidden="true"></i><?= e($bullet) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <div class="iw-detail-help">
          <h2>Need help?</h2>
          <p>We can help you choose the right service before ordering.</p>
          <a href="<?= e(public_url('contact')) ?>">Contact iWorks <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </aside>
    </div>
  </section>
</main>