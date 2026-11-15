<?php
declare(strict_types=1);
/** Exact public screenshot contract: no user uploads, no arbitrary file paths. */
$root = dirname(__DIR__);
require_once $root.'/app/public_solutions_media.php';
$assert = static function (bool $ok, string $why): void {
    if (!$ok) { throw new RuntimeException($why); }
};
$expected = [
    'gansell-home-desktop.png',
    'gansell-home-mobile.png',
    'ab-african-store-desktop.png',
    'ab-african-store-mobile.png',
];
$assert(iworks_solutions_media_allowed() === $expected, 'Expected four allowlisted public Gansell images.');
foreach ($expected as $name) {
    $path = iworks_solutions_media_path($name, $root);
    $assert($path !== null && is_file($path), 'Genuine screenshot file missing: '.$name);
    $size = getimagesize($path);
    $assert(is_array($size) && ($size['mime'] ?? '') === 'image/png'
        && ($size[0] ?? 0) >= 390 && ($size[1] ?? 0) >= 800,
        'Invalid desktop/mobile PNG: '.$name);
    $assert(filesize($path) > 10000, 'Unexpected empty image: '.$name);
    $embedded = iworks_solutions_embedded_image($name, $root);
    $assert(str_starts_with($embedded, 'data:image/png;base64,'),
        'Real screenshot must be embedded independently of public asset routes: '.$name);
    $decoded = base64_decode(substr($embedded, strlen('data:image/png;base64,')), true);
    $assert($decoded !== false && hash('sha256', $decoded) === hash_file('sha256', $path),
        'Embedded screenshot is not byte-identical to the genuine PNG: '.$name);
}
foreach (['../app/config.php','../../storage/data.png','unknown.png','gansell-home-desktop.jpg',
    'gansell-home-desktop.png/evil','/etc/passwd',''] as $bad) {
    $assert(iworks_solutions_media_path($bad, $root) === null,
        'Arbitrary file path accepted: '.$bad);
}
$router = (string)file_get_contents($root.'/index.php');
$landing = (string)file_get_contents($root.'/views/public-solutions.php');
$deployer = (string)file_get_contents($root.'/scripts/deploy_solutions_ui_refresh.sh');
$assert(!str_contains($router,"iworks_serve_solutions_media(") &&
    !str_contains($router,"solutions/media/([a-z0-9-]+"),
    'Unreleased product images must not have a public route on iWorks.');
$assert(str_contains($landing,'iworks_solutions_embedded_image(') &&
    str_contains($landing,'iw-gansell-product-gallery') &&
    str_contains($landing,'iw-gansell-free-seal') &&
    substr_count($landing,'<img src="<?= e($screens[') === 4 &&
    !str_contains($landing,"public_absolute_url('solutions/media/'"),
    'Page must render four authentic embedded images with a large desktop gallery and FREE sticker.');
foreach ($expected as $name) {
    $assert(str_contains($deployer, "assets/branding/solutions/".$name),
        'Deployment must require screenshot '.$name);
}
$assert(str_contains($deployer,'src="data:image/png;base64,') &&
    str_contains($deployer,'PASS 4 genuine screenshots embedded in public Gansell HTML') &&
    str_contains($deployer,'iw_release=') &&
    str_contains($deployer,'rollback()') &&
    str_contains($deployer,'check_remediation_release.php'),
    'Deployment must verify all four authentic embedded PNGs in the public page and roll back on failure.');
echo "public_solutions_media_contract_test passed\n";
