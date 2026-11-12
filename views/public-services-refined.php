<?php
/**
 * iWorks public service directory.
 *
 * One consistent, accessible list on every viewport. All service records,
 * prices and detail destinations remain sourced from the published catalog.
 */
if (!function_exists('iworks_catalog_service_icon')) {
    function iworks_catalog_service_icon(array $service): string
    {
        $search = strtolower((string) ($service['name'] ?? '') . ' ' . (string) ($service['category'] ?? ''));
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
            if (str_contains($search, $term)) { return $icon; }
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
$allServices = array_values(array_filter(is_array($publicServices ?? null) ? $publicServices : [], static function ($item): bool {
    if (!is_array($item)) { return false; }
    $name = strtolower(trim((string) ($item['name'] ?? '')));
    if ($name === '' || trim((string) ($item['slug'] ?? '')) === '') { return false; }
    if ($name === 'custom service' && (float) ($item['price'] ?? 0) <= 0) { return false; }
    foreach (['banking', 'bank account', 'loan', 'payment gateway', 'wallet'] as $excluded) {
        if (str_contains($name, $excluded)) { return false; }
    }
    return true;
}));
$priority = ['business name registration', 'company registration', 'trademark', 'website design', 'domain', 'graphics design', 'seo'];
usort($allServices, static function (array $left, array $right) use ($priority): int {
    $rank = static function (array $item) use ($priority): int {
        $name = strtolower((string) ($item['name'] ?? ''));
        foreach ($priority as $index => $term) {
            if (str_contains($name, $term)) { return $index; }
        }
        return 99;
    };
    return ($rank($left) <=> $rank($right)) ?: strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});
$categories = [];
foreach ($allServices as $service) {
    $slug = trim((string) ($service['category'] ?? 'general')) ?: 'general';
    $categories[$slug] = ($categories[$slug] ?? 0) + 1;
}
$selectedCategory = trim((string) ($selectedPublicCategory ?? ''));
if (!isset($categories[$selectedCategory])) { $selectedCategory = ''; }
$filteredServices = $selectedCategory === '' ? $allServices : array_values(array_filter(
    $allServices, static fn(array $service): bool => (string) ($service['category'] ?? 'general') === $selectedCategory
));
$categoryLabel = static fn(string $category): string => ucwords(str_replace(['-', '_'], ' ', $category));
$heading = trim((string) ($catalogSettings['title'] ?? ''));
if ($heading === '' || in_array(strtolower($heading), ['our services', 'all services', 'public service catalog'], true)) {
    $heading = 'Services for your business';
}
$description = trim((string) ($catalogSettings['subtitle'] ?? ''));
if ($description === '' || $description === 'Browse what you need.') {
    $description = 'Find the right service, review the details and get started online.';
}
?>
<main class="public-main iw-service-index" id="main-content">
  <div class="container public-container iw-service-index-shell">
    <nav class="iw-service-index-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(public_url('home')) ?>">Home</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span aria-current="page">Services</span>
    </nav>

    <header class="iw-service-index-heading">
      <h1><?= e($selectedCategory !== '' ? $categoryLabel($selectedCategory) : $heading) ?></h1>
      <p><?= e($selectedCategory !== '' ? 'Browse services in this category.' : $description) ?></p>
    </header>

    <div class="iw-service-index-tools">
      <label class="iw-service-index-search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <span class="visually-hidden">Search services</span>
        <input id="iwServiceSearch" type="search" placeholder="Search services" autocomplete="off" aria-controls="iwServiceList">
      </label>
      <form class="iw-service-index-filter" id="iwServiceFilter" method="get" action="<?= e(public_url('services')) ?>">
        <label for="iwServiceCategory">Category</label>
        <select id="iwServiceCategory" name="category">
          <option value=""<?= $selectedCategory === '' ? ' selected' : '' ?>>All categories (<?= count($allServices) ?>)</option>
          <?php foreach ($categories as $slug => $total): ?>
            <option value="<?= e($slug) ?>"<?= $selectedCategory === $slug ? ' selected' : '' ?>><?= e($categoryLabel($slug)) ?> (<?= (int) $total ?>)</option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Apply category</button></noscript>
      </form>
    </div>

    <div class="iw-service-index-list-head">
      <h2>Available services</h2>
      <span id="iwServiceCount" aria-live="polite"><?= count($filteredServices) ?> <?= count($filteredServices) === 1 ? 'service' : 'services' ?></span>
    </div>

    <div class="iw-service-index-list" id="iwServiceList">
      <?php foreach ($filteredServices as $service): ?>
        <?php
        $name = trim((string) ($service['name'] ?? 'Service'));
        $category = trim((string) ($service['category'] ?? 'general')) ?: 'general';
        $short = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($service['short_description'] ?? ''))));
        if (stripos($name, 'launch a company package') !== false) {
            $short = 'Company formation, compliance documentation, branding and launch support.';
        } else {
            $short = trim((string) preg_replace('/\b(?:banking|bank account opening)\b\s*,?\s*/i', '', $short));
        }
        if ($short === '') { $short = 'View the full requirements and service details.'; }
        $price = (float) ($service['price'] ?? 0);
        $currency = strtoupper(trim((string) ($service['currency'] ?? default_currency())));
        $amount = $currency . ' ' . number_format($price, (abs($price - round($price)) < 0.001 ? 0 : 2));
        $href = public_url('service', ['slug' => (string) ($service['slug'] ?? '')]);
        $searchText = strtolower($name . ' ' . $short . ' ' . $categoryLabel($category));
        ?>
        <a class="iw-service-index-row" href="<?= e($href) ?>" data-service-row data-search="<?= e($searchText) ?>" aria-label="View <?= e($name) ?> details">
          <span class="iw-service-index-icon"><i class="bi <?= e(iworks_catalog_service_icon($service)) ?>" aria-hidden="true"></i></span>
          <span class="iw-service-index-main">
            <span class="iw-service-index-category"><?= e($categoryLabel($category)) ?></span>
            <strong class="iw-service-index-name"><?= e($name) ?></strong>
            <span class="iw-service-index-description"><?= e($short) ?></span>
          </span>
          <span class="iw-service-index-end">
            <span class="iw-service-index-pricing">
              <small><?= $price > 0 ? 'From' : 'Pricing' ?></small>
              <strong><?= e($price > 0 ? $amount : 'Request a quote') ?></strong>
            </span>
            <i class="bi bi-arrow-up-right iw-service-index-arrow" aria-hidden="true"></i>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="iw-service-index-empty" id="iwServiceEmpty"<?= count($filteredServices) > 0 ? ' hidden' : '' ?>>
      <p>No matching services.</p>
      <a href="<?= e(public_url('services')) ?>">Show all services</a>
    </div>

    <p class="iw-service-index-assist">
      Not sure what you need? <a href="<?= e(public_url('contact')) ?>">Contact iWorks <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </p>
  </div>
</main>
<script>
(function () {
  'use strict';
  var input = document.getElementById('iwServiceSearch');
  var list = document.getElementById('iwServiceList');
  var count = document.getElementById('iwServiceCount');
  var empty = document.getElementById('iwServiceEmpty');
  var form = document.getElementById('iwServiceFilter');
  var select = document.getElementById('iwServiceCategory');
  if (form && select) {
    select.addEventListener('change', function () { form.submit(); });
  }
  if (!input || !list || !count || !empty) return;
  var rows = Array.prototype.slice.call(list.querySelectorAll('[data-service-row]'));
  function update() {
    var query = String(input.value || '').trim().toLocaleLowerCase();
    var visible = 0;
    rows.forEach(function (row) {
      var matches = !query || (row.getAttribute('data-search') || '').indexOf(query) !== -1;
      row.hidden = !matches;
      if (matches) visible++;
    });
    count.textContent = visible + (visible === 1 ? ' service' : ' services');
    empty.hidden = visible !== 0;
  }
  input.addEventListener('input', update);
  update();
})();
</script>