<?php

declare(strict_types=1);

// Public About template contract without a database or hosting session.
function normalize_page_block_type(string $type): string { return $type; }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string
{
    $base = $page === 'home' ? '/' : '/' . $page;
    return $params === [] ? $base : $base . '?' . http_build_query($params);
}
function public_absolute_url(string $path): string { return '/' . ltrim($path, '/'); }
function must_contain(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$layout = [
    ['type' => 'hero', 'data' => [
        'headline' => 'About our company',
        'subheadline' => 'We make business services easier to access.',
    ]],
    ['type' => 'founder_story', 'data' => [
        'name' => 'Example Founder',
        'role' => 'Founder',
        'summary' => 'Reliable delivery and clear communication.',
        'quote' => 'Service should be transparent.',
        'sections' => [['title' => 'Our standard', 'text' => 'Show every next step.']],
    ]],
];
ob_start();
require __DIR__ . '/../views/public-about-refined.php';
$html = (string) ob_get_clean();

must_contain(str_contains($html, 'About our company'), 'CMS headline should be rendered.');
must_contain(str_contains($html, 'Home</a>'), 'Compact breadcrumb should link home.');
must_contain(str_contains($html, 'aria-current="page">About'), 'Breadcrumb should identify current page.');
must_contain(str_contains($html, 'photo-1772588627327-1eeddcf73c11'), 'Real registration photo must appear.');
must_contain(str_contains($html, 'photo-1778146476147-5f8d4bd03c79'), 'Real digital workspace photo must appear.');
must_contain(str_contains($html, 'photo-1769740333462-9a63bfa914bc'), 'Real meeting photo must appear.');
must_contain(str_contains($html, 'Example Founder'), 'CMS founder identity should be retained.');
must_contain(str_contains($html, '/services?category=growth'), 'Digital services must link to the live catalog.');
must_contain(!str_contains($html, 'Trusted by millions'), 'Unverified trust claims must not appear.');

foreach (['register', 'digital', 'tracking'] as $visual) {
    $path = __DIR__ . '/../assets/illustrations/' . $visual . '.svg';
    must_contain(is_file($path), 'The ' . $visual . ' illustration must exist.');
    $contents = (string) file_get_contents($path);
    must_contain(str_contains($contents, '<svg') && str_contains($contents, '</svg>'), 'SVG asset must have a valid root wrapper.');
}

echo "public_about_template_test passed\n";
