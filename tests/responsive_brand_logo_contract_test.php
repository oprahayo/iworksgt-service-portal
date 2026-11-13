<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function brand_logo_check(bool $ok,string $why):void { if (!$ok) throw new RuntimeException($why); }
$desktop=file_get_contents($root.'/assets/branding/iworks-desktop.svg');
$compact=file_get_contents($root.'/assets/branding/iworks-compact.svg');
foreach ([$desktop,$compact] as $svg) {
    brand_logo_check(str_starts_with(ltrim($svg),'<svg ') && str_contains($svg,'</svg>'),
        'Brand asset must be a complete SVG.');
    brand_logo_check(!preg_match('~<(?:script|image|foreignObject)|onload=|href=|https?://(?!www.w3.org/2000/svg)~i',$svg),
        'User-supplied logos should not load scripts, off-site images or event handlers.');
    brand_logo_check(str_contains($svg,'#FF6600'),'Keep original orange identity.');
}
brand_logo_check(str_contains($desktop,'viewBox="0 0 1338 123"') &&
    str_contains($desktop,'fill="black"'),
    'Desktop must use provided wide black/orange wordmark.');
brand_logo_check(str_contains($compact,'viewBox="45 15 265 135"') &&
    !str_contains($compact,'fill="black"'),
    'Compact icon should crop surrounding whitespace without adding any black wordmark.');
$header=file_get_contents($root.'/views/partials/public-header.php');
$footer=file_get_contents($root.'/views/partials/public-footer.php');
$staff=file_get_contents($root.'/views/partials/header.php');
$client=file_get_contents($root.'/views/partials/client-header.php');
$css=file_get_contents($root.'/assets/css/iworks-responsive-logo.css');
$script=file_get_contents($root.'/scripts/deploy_admin_mobile_refresh.sh');
foreach([$header,$footer] as $view){
    brand_logo_check(str_contains($view,'iworks-desktop.svg') && str_contains($view,'iworks-compact.svg'),
        'Public logo must switch between supplied full and compact SVGs.');
    brand_logo_check(str_contains($view,'<picture') && str_contains($view,'<source media='),
        'Mobile source should switch without JS and retain accessible image alt text.');
}
brand_logo_check(str_contains($staff,'iw-brand-sidebar-lockup') &&
    str_contains($staff,'iw-sidebar-mini-icon') &&
    str_contains($staff,'iworks-compact.svg') &&
    str_contains($staff,'iworks-desktop.svg'),
    'Admin/agent sidebar must use compact asset and signed-out header full logo.');
brand_logo_check(str_contains($client,'iw-client-logo iw-compact-logo') &&
    str_contains($client,'iworks-compact.svg'),
    'Client sidebar must use compact logo.');
foreach([$header,$staff,$client] as $view){
    brand_logo_check(str_contains($view,'iworks-responsive-logo.css?v=20261113-logo1'),
        'Every shell must load the menu-safe responsive logo CSS.');
}
brand_logo_check(str_contains($css,'max-width:224px!important') &&
    str_contains($css,'@media(max-width:1199.98px)') &&
    str_contains($css,'max-width:60px!important') &&
    str_contains($css,'width:49px!important') &&
    str_contains($css,'object-fit:contain'),
    'Logo geometry must fit desktop menu, mobile header and portal sidebars without distortion.');
brand_logo_check(str_contains($script,'assets/branding/*|views/*') &&
    str_contains($script,'assets/illustrations/*|assets/branding/*)'),
    'cPanel installer must copy branding SVGs to both public and portal document roots.');
echo "responsive_brand_logo_contract_test passed\n";
