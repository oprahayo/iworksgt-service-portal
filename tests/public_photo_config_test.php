<?php
declare(strict_types=1);
require __DIR__ . '/../app/public-photo-config.php';

$subjects = ['consultation', 'registration', 'digital', 'tracking', 'meeting', 'strategy', 'developer'];
foreach ($subjects as $subject) {
    $url = iworks_stock_photo_url($subject, 640);
    if (!str_starts_with($url, 'https://images.unsplash.com/photo-')) {
        throw new RuntimeException("Invalid Unsplash image domain: $subject");
    }
    if (!str_contains($url, 'w=640') || !str_contains($url, 'q=75')) {
        throw new RuntimeException("Image optimization missing: $subject");
    }
    if (str_contains($url, 'source.unsplash.com') || str_contains($url, '/random')) {
        throw new RuntimeException("Unstable image endpoint: $subject");
    }
}
if (!str_contains(iworks_stock_photo_url('digital', 999999), 'w=1440')) {
    throw new RuntimeException('Image width must be bounded.');
}
if (!str_contains(iworks_stock_photo_url('digital', 1), 'w=320')) {
    throw new RuntimeException('Small image width must be bounded.');
}
echo "public_photo_config_test passed\n";
