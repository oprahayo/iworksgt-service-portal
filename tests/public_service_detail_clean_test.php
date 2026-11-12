<?php
declare(strict_types=1);

// Public service details use existing service pricing and the portal login entry.
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string { return '/' . $page . ($params === [] ? '' : '?' . http_build_query($params)); }
function portal_url(string $page): string { return '/portal/' . $page; }
function default_currency(): string { return 'NGN'; }
function money(float $price, string $currency): string { return $currency . ' ' . number_format($price, 2); }
function get_media_asset(int $id): ?array { return null; }
function sanitize_limited_html(string $value): string { return strip_tags($value, '<h3><h4><p><ul><li><strong>'); }
function assert_detail(bool $valid, string $reason): void { if (!$valid) { throw new RuntimeException($reason); } }

$service = [
  'name' => 'Launch a Company Package',
  'slug' => 'launch-a-company-package',
  'short_description' => 'Company setup, banking, brand identity.',
  'description' => '<h3>What you get</h3><p>CAC, banking, hosting and branding support.</p>',
  'category' => 'business-registration',
  'price' => 350000,
  'currency' => 'NGN',
  'tax_percent' => 2.5,
  'featured_bullets' => ['CAC documentation', 'Design support'],
];
ob_start();
require __DIR__ . '/../views/public-service-detail.php';
$html = (string) ob_get_clean();

assert_detail(str_contains($html,'Launch a Company Package'), 'Service name should render.');
assert_detail(str_contains($html,'NGN 350,000.00'), 'Actual backend price should render.');
assert_detail(str_contains($html,'2.50%'), 'Tax rate should remain transparent.');
assert_detail(str_contains($html,'/portal/login'), 'Order CTA must use portal login.');
assert_detail(!str_contains(strtolower($html),'banking support'), 'Banking must not be advertised as an included service.');
assert_detail(str_contains($html,'CAC documentation'), 'Configured inclusions should render.');
assert_detail(substr_count($html,'<section') === 3, 'Detail page should contain intro, content and substantive buying guide.');
assert_detail(str_contains($html,'Launch your company in Nigeria'), 'Company launch guide should be included.');
assert_detail(!str_contains($html,'What happens next'), 'Remove redundant repeated process cards.');
assert_detail(str_contains($html,'/contact'), 'Support route should work.');

echo "public_service_detail_clean_test passed\n";