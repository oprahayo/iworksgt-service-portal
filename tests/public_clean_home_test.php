<?php
declare(strict_types=1);

// A fast, dependency-free render check for the public homepage.
function normalize_page_block_type(string $type): string { return $type; }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string
{
    if ($page === 'home') { return '/'; }
    if ($page === 'service') { return '/services/' . rawurlencode((string) ($params['slug'] ?? '')); }
    return '/' . $page . ($params === [] ? '' : '?' . http_build_query($params));
}
function normalize_public_href(string $href, string $fallback): string { return trim($href) ?: $fallback; }
function get_media_asset(int $id): ?array { return $id === 55 ? ['url' => '/uploads/real-client-hero.webp'] : null; }
function flash(string $type): ?string { return null; }
function csrf_token(): string { return 'testcsrf'; }
function portal_url(string $page): string { return '/portal/' . $page; }
function check_home(bool $passes, string $message): void
{
    if (!$passes) { throw new RuntimeException($message); }
}

$layout = [
    ['type' => 'hero', 'data' => [
        'headline' => 'Launch and grow your business remotely with iWorks Global.',
        'subheadline' => 'CAC registration, trademark filing, NAFDAC-related support, branding, growth services, and structured business operations handled in one portal.',
        'imageId' => 55,
        'primaryCta' => ['label' => 'Explore our services', 'href' => '/services'],
    ]],
    ['type' => 'featured_services', 'data' => [
        'title' => 'Services entrepreneurs use most',
        'serviceIds' => [7],
    ]],
    ['type' => 'how_it_works', 'data' => [
        'steps' => [
            ['title' => 'Choose the service you need', 'text' => 'Select a service from our public catalogue.'],
            ['title' => 'Submit payment and requirements', 'text' => 'Complete the requirements online.'],
            ['title' => 'Track delivery', 'text' => 'Follow status and completion.'],
        ],
    ]],
    ['type' => 'faq', 'data' => [
        'items' => [
            ['q' => 'Can I order remotely?', 'a' => 'Yes, through the portal.'],
            ['q' => 'How do I track?', 'a' => 'Use Track Order.'],
            ['q' => 'Which documents?', 'a' => 'The service page explains.'],
            ['q' => 'Extra lengthy question?', 'a' => 'Not on the home page.'],
        ],
    ]],
];
$publicServices = [
    ['id' => 1, 'name' => 'Business Name Registration (CAC Nigeria)', 'slug' => 'business-name', 'category' => 'business-registration', 'price' => 26000],
    ['id' => 2, 'name' => 'Trademark', 'slug' => 'trademark', 'category' => 'trademark', 'price' => 50000],
    ['id' => 3, 'name' => 'Website Design & Development', 'slug' => 'website', 'category' => 'growth', 'price' => 250000],
    ['id' => 4, 'name' => 'Banking Account Setup', 'slug' => 'banking', 'category' => 'general', 'price' => 75000],
    ['id' => 5, 'name' => 'Custom Service', 'slug' => 'custom-service', 'category' => 'general', 'price' => 0],
    ['id' => 7, 'name' => 'Domain & Web Hosting', 'slug' => 'hosting', 'category' => 'growth', 'price' => 45000],
];
ob_start();
require __DIR__ . '/../views/public-home-clean.php';
$html = (string) ob_get_clean();

check_home(str_contains($html, 'Business registration and website design in Nigeria.'), 'Homepage H1 must describe Nigerian business services.');
check_home(!str_contains($html, 'NAFDAC-related support, branding, growth services'), 'Legacy default intro should not clutter the hero.');
check_home(str_contains($html, '/uploads/real-client-hero.webp'), 'CMS-selected hero image must take precedence.');
check_home(str_contains($html, 'What do you need help with?'), 'Default services heading should be short.');
check_home(str_contains($html, '/services/business-name'), 'CAC category should route to a published service.');
check_home(str_contains($html, '/services/trademark'), 'Trademark should route to a published service.');
check_home(str_contains($html, '/services/hosting'), 'Admin-selected growth service should control category destination.');
check_home(!str_contains($html, '/services/banking'), 'Banking offers must never be promoted.');
check_home(!str_contains($html, '/services/custom-service'), 'Internal placeholder must never be promoted.');
check_home(substr_count($html, 'class="iw-clean-service"') === 3, 'Home should have only three service categories.');
check_home(substr_count($html, 'class="iw-clean-step"') === 3, 'Home should have only three steps.');
check_home(substr_count($html, '<details>') >= 6, 'Home should offer expanded collapsible FAQs.');
check_home(str_contains($html, 'Can I use iWorks if I live outside Nigeria?'), 'Remote/diaspora service FAQ missing.');
check_home(str_contains($html, 'Can I pay in naira or US dollars?'), 'Currency FAQ missing.');
check_home(substr_count($html, '<section') <= 8, 'Keep marketing sections concise, including the requested illustrated benefits.');
check_home(substr_count($html, 'class="iw-home-benefits"') === 1, 'Illustrated benefit section must appear once.');
check_home(str_contains($html, 'Step 1') && str_contains($html, 'Step 3'), 'Connected steps need accessible labels.');
check_home(str_contains($html, 'name="form_action" value="public_track"'), 'Home must submit to the existing track handler.');
check_home(str_contains($html, 'name="job_code"') && str_contains($html, 'name="identifier"'), 'Tracking fields must match the handler.');
check_home(str_contains($html, 'name="csrf_token"'), 'Track form requires CSRF protection.');
check_home(str_contains($html, 'data-iw-help-open'), 'Human support dialog entry missing.');
check_home(str_contains($html, 'On Google'), 'Centered Google rating link is missing.');
check_home(str_contains($html, 'Customer reviews'), 'Order-linked customer reviews area missing.');
check_home(!str_contains($html, '120 reviews'), 'Old unverified review count must not appear.');
check_home(str_contains($html, '/track'), 'Existing portal tracking link must work.');
check_home(str_contains($html, '/services'), 'Existing service catalogue link must work.');

// Fully custom text must still be editable from the admin CMS.
$layout[0]['data']['headline'] = 'A tailored headline from the Website Editor';
$layout[0]['data']['subheadline'] = 'Short custom copy entered by the administrator.';
ob_start();
require __DIR__ . '/../views/public-home-clean.php';
$customHtml = (string) ob_get_clean();
check_home(str_contains($customHtml, 'A tailored headline from the Website Editor'), 'Custom hero headline should not be overwritten.');
check_home(str_contains($customHtml, 'Short custom copy entered by the administrator.'), 'Custom hero introduction should not be overwritten.');


// Regress a formerly broken CMS destination that sent public visitors to
// portal /services instead of the apex public service catalogue.
$layout[0]['data']['primaryCta'] = ['label'=>'Explore services',
    'href'=>'https://portal.iworksgt.com/services'];
$publicServices[0] = [
    'id'=>1,'name'=>'Business Registration','slug'=>'company-registration',
    'category'=>'business-registration','price'=>26000,
];
ob_start();
require __DIR__ . '/../views/public-home-clean.php';
$fixedLinksHtml=(string)ob_get_clean();
check_home(!str_contains($fixedLinksHtml,'href="https://portal.iworksgt.com/services"'),
    'Old CMS portal services CTA must not send visitors to a management route.');
check_home(str_contains($fixedLinksHtml,'href="/services"'),
    'Explore Services must use the public catalogue URL.');
check_home(str_contains($fixedLinksHtml,'href="/services/company-registration"'),
    'Homepage Business Registration must use the unified legal pathway product.');

echo "public_clean_home_test passed\n";
