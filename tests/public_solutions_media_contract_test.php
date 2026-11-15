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
}
foreach (['../app/config.php','../../storage/data.png','unknown.png','gansell-home-desktop.jpg',
    'gansell-home-desktop.png/evil','/etc/passwd',''] as $bad) {
    $assert(iworks_solutions_media_path($bad, $root) === null,
        'Arbitrary file path accepted: '.$bad);
}
$router = (string)file_get_contents($root.'/index.php');
$landing = (string)file_get_contents($root.'/views/public-solutions.php');
$deployer = (string)file_get_contents($root.'/scripts/deploy_solutions_ui_refresh.sh');
$assert(str_contains($router,"solutions/media/([a-z0-9-]+") &&
    str_contains($router,"iworks_serve_solutions_media("),
    'Image URL must work through the shared public PHP entry point.');
$assert(str_contains($landing,"public_absolute_url('solutions/media/'") &&
    str_contains($landing,'iw-gansell-checklist') &&
    substr_count($landing,"$".'gansellImage(') === 4 &&
    !str_contains($landing,'is_file(__DIR__'),
    'Page must use four reliable image routes, not filesystem-dependent public links.');
foreach ($expected as $name) {
    $assert(str_contains($deployer, "assets/branding/solutions/".$name),
        'Deployment must require screenshot '.$name);
}
$assert(str_contains($deployer,"PASS real screenshot: ") &&
    str_contains($deployer,"cmp -s ") &&
    str_contains($deployer,"^content-type:") &&
    str_contains($deployer,'rollback()') &&
    str_contains($deployer,'check_remediation_release.php'),
    'Deployment must verify live PNG content with safety checks and rollback.');
echo "public_solutions_media_contract_test passed\n";
