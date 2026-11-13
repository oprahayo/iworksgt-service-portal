<?php
declare(strict_types=1);

/**
 * Self-contained product page regression tests: no database, credentials,
 * network or payment gateway required.
 */
function assert_detail(bool $valid, string $reason): void
{
    if (!$valid) { throw new RuntimeException($reason); }
}
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function public_url(string $page, array $params = []): string
{
    $path = $page === 'service' ? '/services/' . rawurlencode((string) ($params['slug'] ?? '')) : '/' . $page;
    return $path;
}
function portal_url(string $page, array $params = []): string
{
    return '/portal/index.php?' . http_build_query(['page' => $page] + $params);
}
function default_currency(): string { return 'NGN'; }
function money(float $price, string $currency): string
{
    return $currency . ' ' . number_format($price, 2);
}
function trademark_price_usd(): float { return 214.50; }
function get_media_asset(int $id): ?array
{
    return $id === 9 ? ['url' => '/assets/uploads/approved-product.png'] : null;
}
function sanitize_limited_html(string $value): string
{
    return strip_tags($value, '<p><h2><h3><h4><ul><ol><li><strong><em>');
}
require __DIR__ . '/../app/customer_service_policy.php';

function render_product(array $record, array $catalogue = []): string
{
    $service = $record;
    $publicServices = $catalogue;
    ob_start();
    require __DIR__ . '/../views/public-service-detail.php';
    return (string) ob_get_clean();
}

$standard = [
    'id' => 30,
    'name' => 'Website Design & Development',
    'slug' => 'website-design-development',
    'category' => 'digital-services',
    'short_description' => 'Responsive websites for businesses.',
    'description' => '<p>Build and launch your website with iWorks.</p>',
    'featured_bullets' => ['Mobile-ready interface', 'Domain and deployment guidance'],
    'price' => 85000,
    'currency' => 'NGN',
    'tax_percent' => 2.5,
    'featured_image_id' => 9,
    '_iworks_checkout_mode' => 'standard',
];
$standardHtml = render_product($standard, [
    $standard,
    ['slug' => 'domain-web-hosting', 'name' => 'Domain and Hosting', 'category' => 'digital-services'],
    ['slug' => 'private-from-another-category', 'name' => 'Unrelated', 'category' => 'other'],
]);
assert_detail(str_contains($standardHtml, 'Website Design &amp; Development'), 'Real CMS product title must render.');
assert_detail(str_contains($standardHtml, 'NGN 85,000.00'), 'Pricing must come from the backend service record.');
assert_detail(str_contains($standardHtml, 'Tax rate: 2.50%'), 'Configured taxes must be explained before checkout.');
assert_detail(str_contains($standardHtml, '/portal/index.php?page=login&amp;service=website-design-development'),
    'Primary product action must pass the real service slug into the authenticated order-intent flow.');
assert_detail(substr_count($standardHtml, 'class="iw-product-cta"') === 1,
    'Product details must have one clear primary conversion action.');
assert_detail(str_contains($standardHtml, 'Mobile-ready interface'), 'CMS inclusions must remain visible.');
assert_detail(str_contains($standardHtml, 'Build and launch your website'), 'Sanitized CMS description must remain visible.');
assert_detail(str_contains($standardHtml, '/assets/uploads/approved-product.png'), 'Actual uploaded product image must render.');
assert_detail(str_contains($standardHtml, 'Professional website design in Nigeria, delivered remotely'),
    'Existing SEO/service guide must remain available.');
assert_detail(str_contains($standardHtml, '<details>') && str_contains($standardHtml, 'Can a website be designed'),
    'Service guidance FAQs must be accessible without additional JavaScript.');
assert_detail(str_contains($standardHtml, '/services/domain-web-hosting') &&
    !str_contains($standardHtml, '/services/private-from-another-category'),
    'Related links must match the current published category.');
assert_detail(!str_contains($standardHtml, 'iw-search-guide-steps') &&
    !str_contains($standardHtml, 'iw-detail-includes') &&
    !str_contains($standardHtml, 'iw-detail-help'),
    'Old incompatible detail blocks must not return.');

$noImage = $standard;
unset($noImage['featured_image_id']);
$noImageHtml = render_product($noImage);
assert_detail(!str_contains($noImageHtml, 'iw-product-media'),
    'Without uploaded CMS media, do not inject misleading external stock images.');

$company = $standard;
$company['slug'] = 'company-registration';
$company['name'] = 'Private Limited Company (Ltd)';
$company['_iworks_registration_type'] = 'company';
$company['_iworks_checkout_mode'] = 'cac';
$company['price'] = 310000;
$company['featured_image_id'] = 0;
$companyHtml = render_product($company);
assert_detail(str_contains($companyHtml, 'Start Limited Company registration'), 'Ltd must keep guided filing CTA.');
assert_detail(str_contains($companyHtml, 'service=company-registration'), 'Ltd must pass canonical CAC route.');
assert_detail(str_contains($companyHtml, 'NGN 310,000.00'), 'Ltd must display its own configured quote.');

$business = $company;
$business['slug'] = 'cac-business-name';
$business['name'] = 'Business Name (Enterprise)';
$business['_iworks_registration_type'] = 'business';
$business['price'] = 28000;
$businessHtml = render_product($business);
assert_detail(str_contains($businessHtml, 'Start Business Name registration'), 'Enterprise must keep guided business intake CTA.');
assert_detail(str_contains($businessHtml, 'service=cac-business-name'), 'Enterprise virtual slug must survive login.');
assert_detail(str_contains($businessHtml, 'NGN 28,000.00'), 'Enterprise fee must not inherit Ltd price.');
assert_detail(str_contains($businessHtml, 'How to register a business name in Nigeria'),
    'Current Enterprise slug must inherit the business-name guidance.');

$trademark = $standard;
$trademark['slug'] = 'trademark';
$trademark['name'] = 'Trademark';
$trademark['_iworks_checkout_mode'] = 'trademark';
$trademark['price'] = 999999; // Stale record must not override live per-class fee.
$trademarkHtml = render_product($trademark);
assert_detail(str_contains($trademarkHtml, 'USD 214.50 per class'), 'Trademark price must use live class-aware backend configuration.');
assert_detail(!str_contains($trademarkHtml, '999,999'), 'Stale DB trademark price must not be presented.');
assert_detail(str_contains($trademarkHtml, 'service=trademark'), 'Trademark CTA must keep guided wizard auth intent.');

$specialist = $standard;
$specialist['slug'] = 'incorporated-trustees';
$specialist['_iworks_checkout_mode'] = 'consultation';
$specialist['price'] = 46000;
$specialistHtml = render_product($specialist);
assert_detail(str_contains($specialistHtml, 'Specialist quote'), 'Legal specialist fee must be reviewed first.');
assert_detail(str_contains($specialistHtml, 'Request a specialist quote'), 'Consultation must have explicit enquiry CTA.');
assert_detail(!str_contains($specialistHtml, '/portal/index.php?page=login'), 'Specialist offering must not enter direct checkout.');
assert_detail(str_contains($specialistHtml, 'data-iw-help-open'), 'Existing support modal integration must remain.');

$unpriced = $standard;
$unpriced['price'] = 0;
$unpricedHtml = render_product($unpriced);
assert_detail(str_contains($unpricedHtml, 'Request a quote'), 'Unpriced standard products must not look free.');
assert_detail(!str_contains($unpricedHtml, '/portal/index.php?page=login'), 'Unpriced standard products cannot enter generic invoice checkout.');

$unsafe = $standard;
$unsafe['name'] = '<script>alert(1)</script> Home';
$unsafe['featured_bullets'] = ['<img src=x onerror=alert(1)>'];
$unsafeHtml = render_product($unsafe);
assert_detail(!str_contains($unsafeHtml, '<script>alert(1)</script>'), 'Product names must be escaped.');
assert_detail(str_contains($unsafeHtml, '&lt;img src=x onerror=alert(1)&gt;'), 'CMS list items must be escaped.');

$listing = file_get_contents(__DIR__ . '/../views/public-services-refined.php');
$dashboard = file_get_contents(__DIR__ . '/../views/client-dashboard.php');
$header = file_get_contents(__DIR__ . '/../views/partials/public-header.php');
$styles = file_get_contents(__DIR__ . '/../assets/css/product-detail.css');
assert_detail(str_contains($listing, 'iworks_customer_product_price($service)')
    && str_contains($dashboard, 'iworks_customer_product_price($service)'),
    'Directory and customer catalogue must use one shared price presenter.');
assert_detail(str_contains($header, "currentPublicSlug === 'service'") &&
    str_contains($header, 'iw-product-detail-css') && str_contains($header, 'file_get_contents($iwProductCss)'),
    'Product CSS must load on service pages using the split-root-safe application path.');
assert_detail(str_contains($styles, '@media (max-width:920px)') &&
    str_contains($styles, 'grid-template-areas:"side" "content"'),
    'Customer order panel must appear before content on mobile.');
assert_detail(!str_contains($styles, 'box-shadow:0 10px'), 'Do not bring back stacked elevated cards.');

echo "public_service_detail_clean_test passed\n";
