<?php
declare(strict_types=1);

// Standalone render test: no database, API keys, or active hosting environment.
function normalize_page_block_type(string $type): string { return $type; }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function default_currency(): string { return 'NGN'; }
function money(float $amount, string $currency): string { return $currency . ' ' . number_format($amount, 2); }
function public_url(string $page, array $params = []): string
{
    if ($page === 'home') { return '/'; }
    if ($page === 'service') { return '/services/' . rawurlencode((string) ($params['slug'] ?? '')); }
    $path = '/' . $page;
    return $params === [] ? $path : $path . '?' . http_build_query($params);
}
function check(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$layout = [
    ['type' => 'service_grid', 'data' => ['title' => 'Public Service Catalog', 'subtitle' => 'Browse what you need.']],
];
$publicServices = [
    ['id' => 1, 'name' => 'Business Name Registration', 'slug' => 'business-name-registration', 'category' => 'business-registration', 'currency' => 'NGN', 'price' => 26000, 'short_description' => 'Register your business name with the CAC.'],
    ['id' => 2, 'name' => 'Custom Service', 'slug' => 'custom-service', 'category' => 'general', 'currency' => 'NGN', 'price' => 0, 'short_description' => 'System placeholder for admin-created custom service orders.'],
    ['id' => 3, 'name' => 'Launch a Company Package', 'slug' => 'launch-a-company-package', 'category' => 'business-registration', 'currency' => 'NGN', 'price' => 350000, 'short_description' => 'End-to-end launch package with CAC, TIN, banking, branding and go-live support.'],
    ['id' => 4, 'name' => 'Website Design & Development', 'slug' => 'website-design-development', 'category' => 'growth', 'currency' => 'NGN', 'price' => 250000, 'short_description' => 'Build a fast business website.'],
    ['id' => 5, 'name' => 'Consultation', 'slug' => 'consultation', 'category' => 'general', 'currency' => 'NGN', 'price' => 0, 'short_description' => 'Request a tailored service estimate.'],
    ['id' => 6, 'name' => 'Bank Account Opening', 'slug' => 'bank-account-opening', 'category' => 'general', 'currency' => 'NGN', 'price' => 100000, 'short_description' => 'Banking services.'],
    ['id' => 7, 'name' => 'U.S. LLC Formation', 'slug' => 'us-llc-formation', 'category' => 'general', 'currency' => 'USD', 'price' => 800, 'short_description' => 'Start a US business.'],
];
$selectedPublicCategory = '';
ob_start();
require __DIR__ . '/../views/public-services-refined.php';
$html = (string) ob_get_clean();

check(str_contains($html, 'Services for your business'), 'Generic catalogue heading should be upgraded.');
check(str_contains($html, '/services/business-name-registration'), 'CAC service should point to existing detail route.');
check(str_contains($html, '/services/website-design-development'), 'Digital service should use its existing detail route.');
check(!str_contains($html, 'System placeholder'), 'Internal placeholder should be hidden.');
check(!str_contains($html, 'banking'), 'Banking must not be advertised in package preview.');
check(str_contains($html, 'NGN 26,000'), 'Catalog pricing must use the published amount without unnecessary decimals.');
check(str_contains($html, 'USD 800'), 'Mixed currencies must keep their own ISO currency code.');
check(str_contains($html, 'Request a quote'), 'Zero-priced custom requests must not say free.');
check(!str_contains($html, 'Bank Account Opening'), 'Banking services must remain excluded.');
check(substr_count($html, 'data-service-row data-search=') === 5, 'Each published service should render one flat linked row.');
check(str_contains($html, '<a class="iw-service-index-row"'), 'Each directory row should be one tap target.');
check(str_contains($html, '<h3 class="iw-service-index-name"'), 'Service titles must be keyboard and screen-reader friendly headings.');
$directoryCss = (string) file_get_contents(__DIR__ . '/../assets/css/services-list.css');
check(str_contains($directoryCss, '@media(max-width:767px)'), 'Mobile row layout must be explicitly defined.');
check(str_contains($directoryCss, '.iw-service-index-row[hidden]'), 'Search must be able to hide rows despite grid display styling.');
check(!str_contains($directoryCss, 'grid-template-columns:repeat(3,'), 'Directory must not revert to a multi-card desktop grid.');
check(str_contains($html, 'id="iwServiceCategory" name="category"'), 'Native category dropdown must be available on mobile.');
check(str_contains($html, 'type="search"'), 'Directory search must be available.');
check(!str_contains($html, 'isc-grid') && !str_contains($html, 'isc-card'), 'Old card grid and layout switch must be removed.');
check(substr_count($html, '<h1') === 1, 'Services must have a single top-level heading.');

$selectedPublicCategory = 'growth';
ob_start();
require __DIR__ . '/../views/public-services-refined.php';
$growthHtml = (string) ob_get_clean();
check(str_contains($growthHtml, 'Website Design &amp; Development'), 'Selected category should render its service.');
check(!str_contains($growthHtml, 'Launch a Company Package'), 'Selected category should not show services from another category.');
check(substr_count($growthHtml, 'data-service-row data-search=') === 1, 'Category route should render only matching service rows.');
check(str_contains($growthHtml, '<option value="growth" selected'), 'Category dropdown should reflect the URL-selected category.');
check(!str_contains($growthHtml, 'Bank Account Opening'), 'A category must not leak another category.');

echo "public_services_template_test passed\n";
