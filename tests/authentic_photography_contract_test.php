<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/public-photo-config.php';
function authentic_photo_check(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
$expected = [
    'hero-desktop' => 'hero-professional-desktop.jpg',
    'hero-mobile' => 'hero-professional-mobile.jpg',
    'registration' => 'registration-documents.jpg',
    'trademark' => 'trademark-branding.jpg',
    'operations' => 'operations-team.jpg',
    'digital' => 'digital-designer.jpg',
];
foreach ($expected as $name => $file) {
    authentic_photo_check(
        str_contains(iworks_authentic_photo_asset($name), '/assets/photography/' . $file),
        'Missing local photograph path: ' . $name
    );
}
authentic_photo_check(iworks_authentic_photo_asset('unexpected') === '', 'Unexpected photo should not resolve.');
authentic_photo_check(
    iworks_is_retired_paperless_photo('https://iworksgt.com/assets/uploads/frontend/1772909461_e4c73dc4.png'),
    'Known Paperless placeholder must be replaceable.'
);
authentic_photo_check(
    !iworks_is_retired_paperless_photo('https://example.test/approved-photo.jpg'),
    'Approved content must not be treated as placeholder.'
);
$custom = 'https://example.test/approved-editorial-photo.jpg';
authentic_photo_check(
    iworks_select_authentic_photo('hero-desktop', $custom) === $custom,
    'CMS-selected hero must be preserved.'
);
authentic_photo_check(
    iworks_blog_photo_url(['title' => 'How to protect your trademark', 'featured_image' => ['url' => $custom]]) === $custom,
    'CMS blog cover must be preserved.'
);
authentic_photo_check(
    iworks_service_photo_url(['slug' => 'business-name-registration'], $custom) === $custom,
    'CMS service cover must be preserved.'
);
$root = dirname(__DIR__);
$home = file_get_contents($root . '/views/public-home-clean.php');
$archive = file_get_contents($root . '/views/public-insights.php');
$detail = file_get_contents($root . '/views/public-blog-detail.php');
$service = file_get_contents($root . '/views/public-service-detail.php');
$installer = file_get_contents($root . '/scripts/deploy_authentic_photography.sh');
authentic_photo_check(str_contains($home, 'iworks_select_authentic_photo') && str_contains($home, '<picture>'), 'Responsive hero integration missing.');
authentic_photo_check(str_contains($home, 'iworks_blog_photo_url($insight)'), 'Home Insights mapping missing.');
authentic_photo_check(str_contains($archive, 'iworks_blog_photo_url($featured)') && str_contains($archive, 'iworks_blog_photo_url($post)'), 'Journal covers mapping missing.');
authentic_photo_check(str_contains($detail, 'iworks_blog_photo_url($blogPost)') && str_contains($detail, 'iworks_blog_photo_url($post)'), 'Article and related covers missing.');
authentic_photo_check(str_contains($service, 'iworks_service_photo_url('), 'Service photograph integration missing.');
authentic_photo_check(str_contains($installer, 'check_remediation_release.php') && str_contains($installer, 'portal.tgz') && str_contains($installer, 'rollback()'), 'Rollback or release checks missing.');
authentic_photo_check(!str_contains($installer, 'mysqldump') && !str_contains($installer, 'DROP DATABASE'), 'Photo-only installer must not modify SQL.');
echo "authentic_photography_contract_test passed\n";
