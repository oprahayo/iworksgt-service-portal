<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$layout = normalize_page_layout([
    ['type' => 'hero', 'data' => ['headline' => 'A']],
    ['type' => 'faq', 'data' => ['items' => [['q' => 'Q', 'a' => 'A']]]],
]);

$errors = validate_page_layout($layout);
assert_true($errors === [], 'Expected no layout validation errors for valid blocks.');

$legacy = normalize_page_layout([
    ['type' => 'faqs', 'data' => ['items' => []]],
    ['type' => 'featured_services', 'data' => ['serviceIds' => []]],
    ['type' => 'trust_bar', 'data' => ['headline' => 'Trusted by']],
]);
assert_true((string) ($legacy[0]['type'] ?? '') === 'faq', 'Legacy faqs type should map to faq.');
assert_true((string) ($legacy[1]['type'] ?? '') === 'featured_services', 'featured_services should remain available as a native block.');
assert_true((string) ($legacy[2]['type'] ?? '') === 'trusted_logos', 'Legacy trust_bar type should map to trusted_logos.');

$rich = normalize_page_layout([
    ['type' => 'rich_text', 'data' => ['htmlOrDelta' => '<script>alert(1)</script><p>Hello</p>']],
]);
$html = (string) ($rich[0]['data']['htmlOrDelta'] ?? '');
assert_true(!str_contains($html, '<script'), 'Rich text should sanitize script tags.');
assert_true(str_contains($html, '<p>Hello</p>'), 'Rich text should preserve allowed tags.');

$footer = normalize_page_layout([
    ['type' => 'footer', 'data' => [
        'companyLinksText' => "About|/about\nBlog|/blog",
        'servicesLinksText' => "Services|/services",
        'legalLinksText' => "Privacy|/privacy",
    ]],
]);
assert_true(isset($footer[0]['data']['columns'][0]['links'][0]['label']), 'Footer normalization should create footer columns from textareas.');

echo "public_cms_layout_test passed\n";
