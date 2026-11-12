<?php
require_once __DIR__ . '/../app/public-photo-config.php';
if (!function_exists('public_service_bullets')) {
    function public_service_bullets(array $service): array
    {
        $bullets = array_values(array_filter(array_map('trim', is_array($service['featured_bullets'] ?? null) ? $service['featured_bullets'] : [])));
        if ($bullets !== []) {
            return $bullets;
        }

        return [
            'Structured service scope and requirements',
            'Remote document submission flow',
            'Portal-based updates and delivery',
        ];
    }
}

$service = $service ?? [];
$currency = (string) ($service['currency'] ?? default_currency());
$serviceName = trim((string) ($service['name'] ?? 'Service'));
$rawDescription = (string) ($service['description'] ?? '');
$shortDescription = trim((string) ($service['short_description'] ?? ''));
// Legacy CMS copy advertised a banking service iWorks does not provide.
// This keeps the public statement accurate until the admin updates the source record.
if (strcasecmp($serviceName, 'Launch a Company Package') === 0) {
    $rawDescription = str_ireplace('banking, ', '', $rawDescription);
    $rawDescription = str_ireplace(', banking', '', $rawDescription);
    $shortDescription = str_ireplace('banking, ', '', $shortDescription);
    $shortDescription = str_ireplace(', banking', '', $shortDescription);
}
$safeHtml = sanitize_limited_html($rawDescription);
$categoryLabel = ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')));
$bullets = public_service_bullets($service);
$priceLabel = money((float) ($service['price'] ?? 0), $currency);
$taxPercent = (float) ($service['tax_percent'] ?? 0);
$featuredImage = !empty($service['featured_image_id']) ? get_media_asset((int) $service['featured_image_id']) : null;
$featuredImageUrl = trim((string) ($featuredImage['url'] ?? ''));
$categoryText = strtolower((string) ($service['category'] ?? '') . ' ' . $serviceName);
$stockVisual = 'consultation';
if (str_contains($categoryText, 'registration') || str_contains($categoryText, 'company') || str_contains($categoryText, 'cac')) {
    $stockVisual = 'registration';
} elseif (str_contains($categoryText, 'trademark') || str_contains($categoryText, 'compliance')) {
    $stockVisual = 'strategy';
} elseif (str_contains($categoryText, 'growth') || str_contains($categoryText, 'web') || str_contains($categoryText, 'domain') || str_contains($categoryText, 'hosting') || str_contains($categoryText, 'digital')) {
    $stockVisual = 'digital';
} elseif (str_contains($categoryText, 'training')) {
    $stockVisual = 'meeting';
} elseif (str_contains($categoryText, 'track')) {
    $stockVisual = 'tracking';
}
$fallback = '<h3>What you get</h3><p>Structured service delivery with clear requirements, invoice flow, and tracked updates inside the portal.</p><h4>Requirements</h4><p>After checkout, complete the intake form and upload the required documents for processing.</p><h4>Processing time</h4><p>Processing timelines depend on service scope and regulatory workload.</p>';
$renderedHtml = $safeHtml !== '' ? $safeHtml : $fallback;
?>
<main class="public-main service-detail-page">
  <section class="public-section public-section-tight">
    <div class="container public-container">
      <div class="service-detail-page-shell">
        <div class="service-detail-hero">
          <div class="service-detail-overview">
            <a class="service-detail-back" href="<?= e(public_url('services')) ?>">
              <i class="bi bi-arrow-left"></i>
              <span>Back to services</span>
            </a>
            <div class="service-detail-kicker-row">
              <span class="hero-chip"><?= e($categoryLabel) ?></span>
              <span class="service-detail-status-chip"><?= (int) ($service['is_public'] ?? 1) === 1 ? 'Available now' : 'Private service' ?></span>
            </div>
            <h1><?= e($serviceName) ?></h1>
            <?php if ($shortDescription !== ''): ?>
              <p class="service-detail-subtitle"><?= e($shortDescription) ?></p>
            <?php endif; ?>
            <div class="service-detail-meta-row">
              <div class="service-detail-price">
                <small>Starting from</small>
                <strong><?= e($priceLabel) ?></strong>
              </div>
              <div class="service-detail-meta-pill">
                <span class="service-detail-meta-label">Tax</span>
                <span class="service-detail-meta-value"><?= e(number_format($taxPercent, 2)) ?>%</span>
              </div>
              <div class="service-detail-meta-pill">
                <span class="service-detail-meta-label">Delivery</span>
                <span class="service-detail-meta-value">Tracked in portal</span>
              </div>
            </div>
            <div class="service-detail-actions">
              <a class="btn btn-primary" href="<?= e(portal_url('login')) ?>">Order this service</a>
              <a class="btn btn-outline-primary" href="<?= e(public_url('track')) ?>">Track order</a>
            </div>
            <ul class="service-detail-inline-bullets">
              <?php foreach (array_slice($bullets, 0, 3) as $bullet): ?>
                <li><i class="bi bi-check-circle-fill"></i><span><?= e($bullet) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <aside class="service-detail-hero-card">
            <div class="service-detail-hero-media">
              <?php if ($featuredImageUrl !== ''): ?>
                <img src="<?= e($featuredImageUrl) ?>" alt="<?= e($serviceName) ?>" loading="lazy">
              <?php else: ?>
                <img src="<?= e(iworks_stock_photo_url($stockVisual, 1000)) ?>" alt="" loading="lazy" width="800" height="560" decoding="async">
              <?php endif; ?>
            </div>
            <div class="service-detail-hero-card-body">
              <span class="service-detail-card-label">What happens next</span>
              <ol class="service-detail-step-list">
                <li>Open the service and continue to portal checkout.</li>
                <li>Make payment and complete any required intake form.</li>
                <li>Track updates, comments, files, and final delivery online.</li>
              </ol>
            </div>
          </aside>
        </div>

        <div class="service-detail-content-grid">
          <article class="service-detail-article-card">
            <div class="service-detail-content rich-text">
              <?= $renderedHtml ?>
            </div>
          </article>

          <aside class="service-detail-sidebar">
            <div class="service-detail-card">
              <h3>Service highlights</h3>
              <ul class="marketing-bullets service-detail-bullets">
                <?php foreach ($bullets as $bullet): ?>
                  <li><?= e($bullet) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="service-detail-card">
              <h3>How delivery works</h3>
              <ul class="marketing-bullets service-detail-bullets">
                <li>Choose the service and continue to portal checkout.</li>
                <li>Submit requirements, files, and intake details remotely.</li>
                <li>Receive updates from admin or assigned agents as work progresses.</li>
                <li>Get final delivery in your dashboard with comments and files.</li>
              </ul>
            </div>

            <div class="service-detail-card service-detail-support-card">
              <h3>Need help before ordering?</h3>
              <p>Use the contact page to speak with iWorks support if you need help choosing the right service.</p>
              <a class="btn btn-outline-primary btn-sm" href="<?= e(public_url('contact')) ?>">Talk to support</a>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </section>
</main>
