<?php
/**
 * Public service catalogue: a calm search-first page using published services.
 * All ordering and billing continue through the existing portal.
 */
if (!function_exists('iworks_catalog_service_icon')) {
    function iworks_catalog_service_icon(array $service): string
    {
        $name = strtolower((string) ($service['name'] ?? '') . ' ' . (string) ($service['category'] ?? ''));
        foreach ([
            'trademark' => 'bi-patch-check',
            'registration' => 'bi-building',
            'company' => 'bi-buildings',
            'website' => 'bi-window-stack',
            'hosting' => 'bi-globe',
            'domain' => 'bi-globe',
            'design' => 'bi-palette',
            'marketing' => 'bi-graph-up-arrow',
            'seo' => 'bi-graph-up-arrow',
            'training' => 'bi-mortarboard',
            'tracking' => 'bi-geo-alt',
        ] as $term => $icon) {
            if (str_contains($name, $term)) { return $icon; }
        }
        return 'bi-briefcase';
    }
}
$catalogSettings = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (is_array($block) && normalize_page_block_type((string) ($block['type'] ?? '')) === 'service_grid') {
        $catalogSettings = is_array($block['data'] ?? null) ? $block['data'] : [];
        break;
    }
}

$allServices = array_values(array_filter(is_array($publicServices ?? null) ? $publicServices : [], static function ($service): bool {
    if (!is_array($service)) { return false; }
    $name = strtolower(trim((string) ($service['name'] ?? '')));
    if ($name === '' || trim((string) ($service['slug'] ?? '')) === '') { return false; }
    if ($name === 'custom service' && (float) ($service['price'] ?? 0) <= 0) { return false; }
    foreach (['banking', 'bank account', 'loan', 'payment gateway', 'wallet'] as $excluded) {
        if (str_contains($name, $excluded)) { return false; }
    }
    return true;
}));
$sortPriority = ['business name registration', 'company registration', 'trademark', 'website design', 'domain', 'graphics design', 'seo'];
usort($allServices, static function (array $left, array $right) use ($sortPriority): int {
    $rank = static function (array $service) use ($sortPriority): int {
        $name = strtolower((string) ($service['name'] ?? ''));
        foreach ($sortPriority as $i => $term) {
            if (str_contains($name, $term)) { return $i; }
        }
        return 99;
    };
    return ($rank($left) <=> $rank($right))
        ?: strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});
$categories = [];
foreach ($allServices as $service) {
    $key = trim((string) ($service['category'] ?? 'general')) ?: 'general';
    $categories[$key] = ($categories[$key] ?? 0) + 1;
}
$selectedCategory = trim((string) ($selectedPublicCategory ?? ''));
if (!isset($categories[$selectedCategory])) { $selectedCategory = ''; }
$filteredServices = $selectedCategory === '' ? $allServices : array_values(array_filter(
    $allServices, static fn(array $service): bool => (string) ($service['category'] ?? 'general') === $selectedCategory
));

$heading = trim((string) ($catalogSettings['title'] ?? ''));
if ($heading === '' || in_array(strtolower($heading), ['our services', 'all services', 'public service catalog'], true)) {
    $heading = 'Services for your business';
}
$intro = trim((string) ($catalogSettings['subtitle'] ?? ''));
if ($intro === '' || $intro === 'Browse what you need.') {
    $intro = 'Choose a service, check the requirements and get started online.';
}
$categoryLabel = static fn(string $value): string => ucwords(str_replace(['-', '_'], ' ', $value));
?>
<main class="public-main iworks-services-page iw-catalog" id="main-content">
  <section class="iw-catalog-head" aria-labelledby="iw-catalog-title">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Services</span></nav>
      <div class="iw-catalog-intro">
        <div>
          <h1 id="iw-catalog-title"><?= e($selectedCategory !== '' ? $categoryLabel($selectedCategory) : $heading) ?></h1>
          <p><?= e($selectedCategory !== '' ? 'Select a service to see what is included.' : $intro) ?></p>
        </div>
        <a href="<?= e(public_url('contact')) ?>" class="iw-catalog-help">Need advice? Contact us <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <section class="iw-catalog-content" aria-label="Service catalogue">
    <div class="container public-container">
      <div class="iw-catalog-tools">
        <label class="iw-catalog-search">
          <i class="bi bi-search" aria-hidden="true"></i>
          <span class="visually-hidden">Search services</span>
          <input id="iwCatalogSearch" type="search" placeholder="Search services..." autocomplete="off" aria-controls="iwCatalogGrid">
        </label>
        <span class="iw-catalog-count" id="iwCatalogCount" aria-live="polite"><?= count($filteredServices) ?> services</span>
      </div>
      <nav class="iw-catalog-categories" aria-label="Filter services by category">
        <a href="<?= e(public_url('services')) ?>" class="<?= $selectedCategory === '' ? 'is-active' : '' ?>"<?= $selectedCategory === '' ? ' aria-current="page"' : '' ?>>All <span><?= count($allServices) ?></span></a>
        <?php foreach ($categories as $slug => $total): ?>
          <a href="<?= e(public_url('services', ['category' => $slug])) ?>" class="<?= $selectedCategory === $slug ? 'is-active' : '' ?>"<?= $selectedCategory === $slug ? ' aria-current="page"' : '' ?>><?= e($categoryLabel($slug)) ?> <span><?= (int) $total ?></span></a>
        <?php endforeach; ?>
      </nav>

      <div class="iw-catalog-grid" id="iwCatalogGrid">
        <?php foreach ($filteredServices as $service): ?>
          <?php
          $name = trim((string) ($service['name'] ?? 'Service'));
          $slug = trim((string) ($service['slug'] ?? ''));
          $category = trim((string) ($service['category'] ?? 'general')) ?: 'general';
          $description = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($service['short_description'] ?? ''))));
          // Avoid advertising a banking product embedded in an old package description.
          if (str_contains(strtolower($name), 'launch a company package')) {
              $description = 'Company formation, compliance documentation, branding and launch support.';
          } else {
              $description = trim((string) preg_replace('/\b(?:banking|bank account opening)\b\s*,?\s*/i', '', $description));
          }
          if ($description === '') {
              $description = 'Review the service details, requirements and next steps.';
          }
          $price = (float) ($service['price'] ?? 0);
          $currency = (string) ($service['currency'] ?? default_currency());
          $url = public_url('service', ['slug' => $slug]);
          $searchText = strtolower($name . ' ' . $description . ' ' . $categoryLabel($category));
          ?>
          <article class="isc-card iw-catalog-card" data-isc-card data-search="<?= e($searchText) ?>">
            <span class="iw-catalog-icon"><i class="bi <?= e(iworks_catalog_service_icon($service)) ?>" aria-hidden="true"></i></span>
            <div class="iw-catalog-card-copy">
              <span class="iw-catalog-category"><?= e($categoryLabel($category)) ?></span>
              <h2><a href="<?= e($url) ?>"><?= e($name) ?></a></h2>
              <p><?= e($description) ?></p>
            </div>
            <div class="iw-catalog-card-bottom">
              <div><small><?= $price > 0 ? 'From' : 'Pricing' ?></small><strong><?= e($price > 0 ? money($price, $currency) : 'Request a quote') ?></strong></div>
              <a href="<?= e($url) ?>" class="iw-catalog-view" aria-label="View details for <?= e($name) ?>">View details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="iw-catalog-empty" id="iwCatalogEmpty" hidden>
        <h2>No matching services</h2>
        <p>Try a different search or category.</p>
        <a href="<?= e(public_url('services')) ?>">Browse all services</a>
      </div>
      <?php if ($filteredServices === []): ?>
        <p class="iw-catalog-empty-server">No services are published in this category yet. <a href="<?= e(public_url('contact')) ?>">Contact our team</a>.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="iw-catalog-last">
    <div class="container public-container">
      <p>Not sure which service you need?</p>
      <a href="<?= e(public_url('contact')) ?>">Speak with iWorks <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>
<script>
(function () {
  'use strict';
  var input = document.getElementById('iwCatalogSearch');
  var grid = document.getElementById('iwCatalogGrid');
  var counter = document.getElementById('iwCatalogCount');
  var empty = document.getElementById('iwCatalogEmpty');
  if (!input || !grid || !counter || !empty) return;
  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-isc-card]'));
  function update() {
    var query = input.value.trim().toLowerCase();
    var count = 0;
    cards.forEach(function (card) {
      var match = !query || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
      card.hidden = !match;
      if (match) count++;
    });
    counter.textContent = count + (count === 1 ? ' service' : ' services');
    empty.hidden = count > 0;
  }
  input.addEventListener('input', update);
  update();
})();
</script>