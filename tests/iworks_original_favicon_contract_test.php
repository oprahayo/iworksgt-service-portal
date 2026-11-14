<?php
declare(strict_types=1);
/** Approved orange iWORKS favicon asset and site-wide contract. */
function ensure_favicon(bool $valid, string $message): void {
    if (!$valid) throw new RuntimeException($message);
}
$root = dirname(__DIR__);
$compact = (string)file_get_contents($root.'/assets/branding/iworks-compact.svg');
$favicon = (string)file_get_contents($root.'/assets/branding/iworks-favicon.svg');
preg_match_all('~<path\b[^>]+/>~', $compact, $approvedPaths);
preg_match_all('~<path\b[^>]+/>~', $favicon, $faviconPaths);
ensure_favicon(count($approvedPaths[0]) === 4
    && $approvedPaths[0] === $faviconPaths[0],
    'Favicon SVG must preserve the four original, approved iWORKS vector paths.');
ensure_favicon(str_contains($favicon, 'viewBox="19 -74 310 310"')
    && str_contains($favicon, '<title>iWORKS GLOBAL</title>')
    && !str_contains($favicon, '<rect')
    && substr_count($favicon,'fill="#FF6600"') === 4,
    'Favicon SVG must remain a transparent, scalable orange mark.');
foreach ([32, 180] as $size) {
    $path = $root.'/assets/branding/iworks-favicon-'.$size.'.png';
    $info = @getimagesize($path);
    ensure_favicon(is_array($info) && $info[0] === $size && $info[1] === $size
        && $info[2] === IMAGETYPE_PNG,
        'Favicon PNG fallback must be a valid '.$size.'x'.$size.' transparent-mark image.');
}
$helpers = (string)file_get_contents($root.'/app/helpers.php');
$settings = (string)file_get_contents($root.'/views/settings.php');
$router = (string)file_get_contents($root.'/index.php');
$partial = (string)file_get_contents($root.'/views/partials/favicon-head.php');
ensure_favicon(str_contains($helpers,"setting('favicon_source_mode', 'official')")
    && str_contains($helpers, "setting('favicon_path', '')")
    && str_contains($helpers, 'iworks_official_favicon_url()')
    && str_contains($helpers, "['iworks-favicon.svg','iworks-favicon-32.png','iworks-favicon-180.png']"),
    'New brand favicon must be the default with an allowlisted override fallback.');
ensure_favicon(str_contains($router, "set_setting('favicon_source_mode', 'uploaded')")
    && str_contains($router, "set_setting('favicon_source_mode', 'official')")
    && str_contains($settings, 'name="form_action" value="use_official_favicon"')
    && str_contains($settings, 'image/png,image/jpeg,image/webp,image/gif'),
    'Existing safe admin upload/reset controls must remain operational.');
ensure_favicon(str_contains($partial, 'rel="icon" type="image/svg+xml"')
    && str_contains($partial, 'rel="icon" type="image/png" sizes="32x32"')
    && str_contains($partial, 'rel="apple-touch-icon" sizes="180x180"')
    && str_contains($partial, 'iworks_official_favicon_active()'),
    'Shared partial must publish SVG, PNG and Apple touch links.');
foreach (['public-header.php','header.php','client-header.php'] as $file) {
    $head = (string)file_get_contents($root.'/views/partials/'.$file);
    ensure_favicon(str_contains($head, "require __DIR__ . '/favicon-head.php'"),
        'Every public, portal and client shell must share the favicon partial: '.$file);
}
$installer = (string)file_get_contents($root.'/scripts/deploy_admin_mobile_refresh.sh');
ensure_favicon(str_contains($installer,'assets/branding/*'),
    'Source installer must mirror brand favicon assets to both document roots.');
echo "iworks_original_favicon_contract_test passed\n";
