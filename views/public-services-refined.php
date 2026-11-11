<?php
/**
 * Public service catalogue — catalog data, prices, and routing remain
 * sourced from the existing portal; this file does not create an order system.
 */
// Standalone icon helpers: this template is loaded before legacy block helpers.
if (!function_exists('iworks_catalog_service_icon')) {
    function iworks_catalog_service_icon(array $service): string
    {
        $text = strtolower((string) ($service['name'] ?? '') . ' ' . (string) ($service['category'] ?? ''));
        foreach ([
            'trademark' => 'bi-patch-check',
            'registration' => 'bi-building-add',
            'company' => 'bi-buildings',
            'website' => 'bi-window-stack',
            'hosting' => 'bi-globe',
            'domain' => 'bi-globe2',
            'graphic' => 'bi-palette',
            'marketing' => 'bi-graph-up-arrow',
            'seo' => 'bi-graph-up-arrow',
            'training' => 'bi-mortarboard',
            'tracking' => 'bi-geo-alt',
        ] as $keyword => $icon) {
            if (str_contains($text, $keyword)) { return $icon; }
        }
        return 'bi-briefcase';
    }
}
$serviceGridData = [];
foreach ($layout as $block) {
    if (is_array($block) && normalize_page_block_type((string) ($block['type'] ?? '')) === 'service_grid') {
        $serviceGridData = is_array($block['data'] ?? null) ? $block['data'] : [];
        break;
    }
}
$allServices = array_values(array_filter($publicServices, static function (array $service): bool {
    $name = strtolower(trim((string) ($service['name'] ?? '')));
    $description = strtolower(trim((string) ($service['short_description'] ?? '')));
    // An internal zero-price placeholder is not an actual bookable offer.
    return !($name === 'custom service' && ((float) ($service['price'] ?? 0)) <= 0 && str_contains($description, 'placeholder'));
}));
$categories = [];
foreach ($allServices as $service) {
    $slug = trim((string) ($service['category'] ?? 'general')) ?: 'general';
    if (!isset($categories[$slug])) {
        $categories[$slug] = [
            'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
            'count' => 0,
            'icon' => 'bi-grid',
        ];
    }
    $categories[$slug]['count']++;
}
$chosenCategory = trim((string) ($selectedPublicCategory ?? ''));
if (!isset($categories[$chosenCategory])) { $chosenCategory = ''; }

$priorityTerms = ['business name registration', 'company registration', 'trademark', 'website design', 'hosting', 'graphics design', 'seo'];
usort($allServices, static function (array $a, array $b) use ($priorityTerms): int {
    $sortRank = static function (array $service) use ($priorityTerms): int {
        $name = strtolower((string) ($service['name'] ?? ''));
        foreach ($priorityTerms as $i => $term) {
            if (str_contains($name, $term)) { return $i; }
        }
        return 90;
    };
    return $sortRank($a) <=> $sortRank($b) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$displayServices = $chosenCategory === '' ? $allServices : array_values(array_filter(
    $allServices,
    static fn(array $service): bool => (string) ($service['category'] ?? 'general') === $chosenCategory
));
$heading = trim((string) ($serviceGridData['title'] ?? 'Our services'));
$intro = trim((string) ($serviceGridData['subtitle'] ?? 'Choose a service, review requirements and track delivery online.'));
// Retain administrator-customized headings; improve old generic defaults.
if (in_array(strtolower($heading), ['public service catalog', 'all services', 'our services'], true)) {
    $heading = 'Services for your business';
}
$heading = $chosenCategory === '' ? ($heading ?: 'Services for your business') : (string) ($categories[$chosenCategory]['name'] ?? 'Services');
$intro = $chosenCategory === '' ? ($intro ?: 'Choose a service, review requirements and track delivery online.') : 'Explore available services in this category.';
$businessNameHref = public_url('services', ['category' => 'business-registration']);
foreach ($allServices as $service) {
    if (str_contains(strtolower((string) ($service['name'] ?? '')), 'business name registration')) {
        $businessNameHref = public_url('service', ['slug' => (string) ($service['slug'] ?? '')]);
        break;
    }
}
?>
<main class="public-main iworks-services-page" id="main-content">
  <section class="isc-head" aria-labelledby="isc-heading">
    <div class="container public-container">
      <nav class="isc-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span>Services</span></nav>
      <div class="isc-heading-row">
        <div>
          <span class="isc-eyebrow">HOW CAN WE HELP?</span>
          <h1 id="isc-heading"><?= e($heading) ?></h1>
          <p><?= e($intro) ?></p>
        </div>
        <a class="isc-support-link" href="<?= e(public_url('contact')) ?>"><i class="bi bi-chat-dots"></i><span>Need help choosing?<strong>Talk to our team</strong></span><i class="bi bi-arrow-up-right"></i></a>
      </div>
    </div>
  </section>

  <section class="isc-quick-start" aria-label="Popular starting points">
    <div class="container public-container">
      <a class="isc-start-tile" href="<?= e($businessNameHref) ?>"><span class="isc-start-icon"><i class="bi bi-building-add"></i></span><span><strong>Register a business</strong><small>Start your CAC registration</small></span><i class="bi bi-arrow-up-right"></i></a>
      <a class="isc-start-tile" href="<?= e(public_url('services', ['category' => 'trademark'])) ?>"><span class="isc-start-icon"><i class="bi bi-patch-check"></i></span><span><strong>Protect your brand</strong><small>Explore trademark services</small></span><i class="bi bi-arrow-up-right"></i></a>
      <a class="isc-start-tile" href="<?= e(public_url('services', ['category' => 'growth'])) ?>"><span class="isc-start-icon"><i class="bi bi-window-stack"></i></span><span><strong>Grow online</strong><small>Websites, branding &amp; marketing</small></span><i class="bi bi-arrow-up-right"></i></a>
    </div>
  </section>

  <section class="isc-catalogue" aria-labelledby="isc-catalogue-title">
    <div class="container public-container">
      <div class="isc-catalogue-header">
        <div><span class="isc-eyebrow">BROWSE THE CATALOGUE</span><h2 id="isc-catalogue-title">Find the right service</h2></div>
        <span class="isc-total" aria-label="<?= count($displayServices) ?> services in this category"><?= count($displayServices) ?> <?= count($displayServices) === 1 ? 'service' : 'services' ?></span>
      </div>
      <div class="isc-toolbar">
        <label class="isc-search">
          <i class="bi bi-search" aria-hidden="true"></i>
          <span class="visually-hidden">Search available services</span>
          <input type="search" id="iscSearch" autocomplete="off" placeholder="Search registration, trademark, website..." aria-controls="iscGrid">
        </label>
        <div class="isc-view" role="group" aria-label="Choose display layout">
          <button type="button" class="isc-view-btn is-active" data-view="grid" aria-pressed="true" aria-label="Grid view" title="Grid view"><i class="bi bi-grid-3x3-gap"></i></button>
          <button type="button" class="isc-view-btn" data-view="list" aria-pressed="false" aria-label="List view" title="List view"><i class="bi bi-list-ul"></i></button>
        </div>
      </div>
      <nav class="isc-categories" aria-label="Service categories">
        <a class="isc-category<?= $chosenCategory === '' ? ' is-current' : '' ?>" href="<?= e(public_url('services')) ?>"<?= $chosenCategory === '' ? ' aria-current="page"' : '' ?>>All services <span><?= count($allServices) ?></span></a>
        <?php foreach ($categories as $slug => $category): ?>
          <a class="isc-category<?= $chosenCategory === $slug ? ' is-current' : '' ?>" href="<?= e(public_url('services', ['category' => $slug])) ?>"<?= $chosenCategory === $slug ? ' aria-current="page"' : '' ?>><?= e((string) ($category['name'] ?? 'Services')) ?> <span><?= (int) ($category['count'] ?? 0) ?></span></a>
        <?php endforeach; ?>
      </nav>

      <div id="iscGrid" class="isc-grid" data-view="grid">
        <?php foreach ($displayServices as $service): ?>
          <?php
          $name = trim((string) ($service['name'] ?? 'Service'));
          $slug = trim((string) ($service['slug'] ?? ''));
          if ($slug === '') { continue; }
          $description = trim((string) ($service['short_description'] ?? ''));
          if (str_contains(strtolower($name), 'launch a company package') && stripos($description, 'banking') !== false) {
              $description = 'Company formation, CAC and tax documentation, branding, and go-live support. Review the package details before ordering.';
          }
          if ($description === '') {
              $description = 'Review what is included, the documents required, and the next steps.';
          }
          $description = trim((string) preg_replace('/\\s+/', ' ', strip_tags($description)));
          $currency = trim((string) ($service['currency'] ?? default_currency()));
          $price = (float) ($service['price'] ?? 0);
          $link = public_url('service', ['slug' => $slug]);
          $categorySlug = (string) ($service['category'] ?? 'general');
          $categoryLabel = (string) ($categories[$categorySlug]['name'] ?? ucwords(str_replace(['-', '_'], ' ', $categorySlug)));
          $searchText = strtolower($name . ' ' . $description . ' ' . $categoryLabel);
          ?>
          <article class="isc-card" data-isc-card data-search="<?= e($searchText) ?>">
            <div class="isc-card-top">
              <span class="isc-card-icon"><i class="bi <?= e(iworks_catalog_service_icon($service)) ?>" aria-hidden="true"></i></span>
              <span class="isc-card-category"><?= e($categoryLabel) ?></span>
            </div>
            <div class="isc-card-main">
              <h3><a href="<?= e($link) ?>"><?= e($name) ?></a></h3>
              <p><?= e($description) ?></p>
            </div>
            <div class="isc-card-foot">
              <div class="isc-price">
                <small><?= $price > 0 ? 'Starting at' : 'Pricing' ?></small>
                <strong><?= e($price > 0 ? money($price, $currency) : 'Request a quote') ?></strong>
              </div>
              <a href="<?= e($link) ?>" class="isc-card-cta" aria-label="View <?= e($name) ?> details">View details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div id="iscEmpty" class="isc-empty" hidden>
        <i class="bi bi-search" aria-hidden="true"></i><h3>No matching services</h3><p>Try a different search or browse all categories.</p>
        <a href="<?= e(public_url('services')) ?>">View all services</a>
      </div>
      <p class="isc-count" id="iscCount" aria-live="polite"></p>
    </div>
  </section>

  <section class="isc-assurance" aria-label="What to expect">
    <div class="container public-container">
      <div><i class="bi bi-list-check"></i><span><strong>Clear requirements</strong><small>Know what you need to provide.</small></span></div>
      <div><i class="bi bi-shield-check"></i><span><strong>Secure portal checkout</strong><small>Pay through the existing iWorks system.</small></span></div>
      <div><i class="bi bi-clock-history"></i><span><strong>Track your progress</strong><small>See updates and documents online.</small></span></div>
    </div>
  </section>
  <section class="isc-bottom">
    <div class="container public-container">
      <div><span class="isc-eyebrow">LET'S GET STARTED</span><h2>Not sure which service you need?</h2><p>Tell us what you are trying to achieve. Our team can point you in the right direction before you order.</p></div>
      <a href="<?= e(public_url('contact')) ?>" class="isc-btn">Talk to iWorks <i class="bi bi-arrow-right"></i></a>
    </div>
  </section>
</main>
<script>
(function () {
  'use strict';
  var grid = document.getElementById('iscGrid');
  var search = document.getElementById('iscSearch');
  var empty = document.getElementById('iscEmpty');
  var count = document.getElementById('iscCount');
  if (!grid || !search || !empty || !count) return;
  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-isc-card]'));
  var buttons = document.querySelectorAll('.isc-view-btn');
  var storageKey = 'iworks-service-catalogue-view';
  function applySearch() {
    var query = String(search.value || '').trim().toLowerCase();
    var visible = 0;
    cards.forEach(function (card) {
      var match = !query || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
      card.hidden = !match;
      if (match) visible++;
    });
    empty.hidden = visible !== 0;
    count.textContent = visible + (visible === 1 ? ' service shown' : ' services shown');
  }
  function setView(view) {
    var selected = view === 'list' ? 'list' : 'grid';
    grid.setAttribute('data-view', selected);
    buttons.forEach(function (button) {
      var active = button.getAttribute('data-view') === selected;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    try { window.localStorage.setItem(storageKey, selected); } catch (e) {}
  }
  search.addEventListener('input', applySearch);
  buttons.forEach(function (button) { button.addEventListener('click', function () { setView(button.getAttribute('data-view')); }); });
  var stored = 'grid';
  try { stored = window.localStorage.getItem(storageKey) || 'grid'; } catch (e) {}
  setView(stored);
  applySearch();
})();
</script>
