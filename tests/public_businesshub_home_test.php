<?php
declare(strict_types=1);

// Standalone test of the homepage: no database, network, credentials, or writes.
function normalize_page_block_type(string $type): string { return $type; }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string {
    if ($page === 'home') { return '/'; }
    if ($page === 'service') { return '/services/' . rawurlencode((string) ($params['slug'] ?? '')); }
    $base = '/' . $page;
    return $params ? $base . '?' . http_build_query($params) : $base;
}
function normalize_public_href(string $href, string $fallback): string { return trim($href) ?: $fallback; }
function get_media_asset(int $id): ?array {
    return $id === 15 ? ['url' => '/uploads/iworks-real-hero.webp'] : null;
}
function flash(string $type): ?string { return null; }
function assert_home(bool $value, string $msg): void {
    if (!$value) { throw new RuntimeException($msg); }
}
$layout = [
    ['type' => 'hero', 'data' => [
        'headline' => 'Business services that work for you',
        'subheadline' => 'Register, protect, and grow your business remotely.',
        'kicker' => 'Business support',
        'imageId' => 15,
        'primaryCta' => ['label' => 'Explore our services', 'href' => '/services'],
    ]],
    ['type' => 'featured_services', 'data' => ['title' => 'Selected iWorks services', 'subtitle' => 'Support for each stage of business.', 'serviceIds' => [4]]],
    ['type' => 'how_it_works', 'data' => ['steps' => [
        ['title' => 'Choose', 'text' => 'Select the service you need.'],
        ['title' => 'Submit', 'text' => 'Provide details and checkout.'],
        ['title' => 'Track', 'text' => 'Follow your order.'],
    ]]],
    ['type' => 'faq', 'data' => ['items' => [['q' => 'Can I order online?', 'a' => 'Yes.']]]],
];
$publicServices = [
    ['id' => 1, 'name' => 'Business Name Registration (CAC Nigeria)', 'slug' => 'business-name-cac', 'category' => 'business-registration', 'short_description' => 'Register your business name.', 'price' => 26000],
    ['id' => 2, 'name' => 'Business Bank Account Opening', 'slug' => 'banking', 'category' => 'general', 'short_description' => 'Banking services.', 'price' => 120000],
    ['id' => 3, 'name' => 'Custom Service', 'slug' => 'custom', 'category' => 'general', 'short_description' => 'System placeholder for admins.', 'price' => 0],
    ['id' => 4, 'name' => 'Website Design & Development', 'slug' => 'web-design', 'category' => 'growth', 'short_description' => 'Build an online presence.', 'price' => 250000],
    ['id' => 5, 'name' => 'Launch a Company Package', 'slug' => 'launch-company', 'category' => 'business-registration', 'short_description' => 'CAC, TIN, banking, branding and support.', 'price' => 350000],
    ['id' => 6, 'name' => 'Trademark', 'slug' => 'trademark', 'category' => 'trademark', 'short_description' => 'Protect your business brand.', 'price' => 50000],
];
ob_start();
require __DIR__ . '/../views/public-home-businesshub.php';
$html = (string) ob_get_clean();
assert_home(str_contains($html, 'Business services that work for you'), 'CMS hero copy should render.');
assert_home(str_contains($html, '/uploads/iworks-real-hero.webp'), 'Admin-selected hero image should take precedence.');
assert_home(str_contains($html, 'Selected iWorks services'), 'CMS featured services title should render.');
assert_home(str_contains($html, '/services/business-name-cac'), 'CAC CTA should use real service route.');
assert_home(str_contains($html, '/services/web-design'), 'Digital service card should use real route.');
assert_home(str_contains($html, 'Launch a Company Package'), 'Legitimate package should not be hidden due to stale banking description.');
assert_home(!str_contains($html, 'Bank Account Opening'), 'Banking offer should be excluded.');
assert_home(!str_contains($html, 'System placeholder'), 'Internal placeholder should not be promoted.');
assert_home(!str_contains($html, 'CAC, TIN, banking'), 'Stale banking promise should not be rendered.');
assert_home(str_contains($html, 'Can I order online?'), 'CMS FAQ should be rendered.');
assert_home(str_contains($html, 'Business support, made less complicated.'), 'Service reassurance section must render.');
assert_home(str_contains($html, 'get to know iWorks') || str_contains($html, 'Get to know iWorks'), 'About page link should be present.');
assert_home(!str_contains($html, '372 reviews'), 'Invented reviews should not appear.');
echo "public_businesshub_home_test passed\n";
