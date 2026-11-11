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
check(str_contains($html, 'NGN 26,000.00'), 'Catalog pricing should reflect the service source.');
check(substr_count($html, 'data-isc-card data-search=') === 3, 'Only three real services should render.');

$selectedPublicCategory = 'growth';
ob_start();
require __DIR__ . '/../views/public-services-refined.php';
$growthHtml = (string) ob_get_clean();
check(str_contains($growthHtml, 'Website Design &amp; Development'), 'Selected category should render its service.');
check(!str_contains($growthHtml, 'Launch a Company Package'), 'Selected category should not show services from another category.');
check(substr_count($growthHtml, 'data-isc-card data-search=') === 1, 'Category filter should render one matching service.');

echo "public_services_template_test passed\n";
