<?php
declare(strict_types=1);
/** iWORKS source-only visual and solutions regression guard. */
$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$assert=static function(bool $ok,string $what):void{
  if(!$ok)throw new RuntimeException($what);
};
$home=$read('views/public-home-clean.php');
$header=$read('views/partials/public-header.php');
$routes=$read('index.php');
$helpers=$read('app/helpers.php');
$meta=$read('app/public_marketing.php');
$seo=$read('app/public_seo.php');
$landing=$read('views/public-solutions.php');
$brandCss=$read('assets/css/public-clean.css');
$caseCss=$read('assets/css/portfolio-work.css');
$chromeCss=$read('assets/css/site-launch-polish.css');
$landingCss=$read('assets/css/solutions.css');
$deployer=$read('scripts/deploy_solutions_ui_refresh.sh');

$assert(str_contains($brandCss,'filter:grayscale(1)!important;') &&
        str_contains($brandCss,'filter:grayscale(0)!important;') &&
        substr_count($brandCss,'filter:none!important')===0 &&
        str_contains($brandCss,'iw-home-brand-orangeserver:hover img') &&
        str_contains($brandCss,'iw-home-brand-rilahost:hover img'),
        'All hosting logos must follow grayscale idle/color on hover template.');

$start=strpos($home,'<div class="iw-home-case-grid"');
$end=strpos($home,'<div class="iw-home-case-nav">',$start===false?0:$start);
$assert($start!==false && $end!==false,'Carousel missing.');
$carousel=substr($home,$start,$end-$start);
$assert(str_contains($carousel,'class="iw-home-case-cover"') &&
        str_contains($carousel,'class="iw-home-case-overlay" aria-hidden="true"') &&
        !str_contains($carousel,'iw-home-case-overlay-actions') &&
        !str_contains($carousel,'Visit website') &&
        !str_contains($carousel,'View case study'),
        'Click screenshot for detail, never paint action buttons over mobile preview.');
$assert(str_contains($caseCss,'rgba(237,102,23,.38)') &&
        !str_contains($caseCss,'rgba(12,21,30,.82)') &&
        !str_contains($caseCss,'@media(hover:none)'),
        'Case overlay must use orange on interaction and stay clear on touch idle.');

$assert(str_contains($chromeCss,'background:transparent!important;background-image:none!important;') &&
        str_contains($chromeCss,'@keyframes iw-back-to-top-hop') &&
        str_contains($chromeCss,'animation:iw-back-to-top-hop 2.3s') &&
        str_contains($chromeCss,'filter:drop-shadow(') &&
        str_contains($chromeCss,'prefers-reduced-motion:reduce'),
        'Back-to-top control must have transparent backing and a slow, accessible orange hop.');
$assert(str_contains($chromeCss,'body.page-public .public-footer-brand-row{') &&
        str_contains($chromeCss,'justify-items:center!important;') &&
        str_contains($chromeCss,'body.page-public .public-footer-summary{') &&
        str_contains($chromeCss,'text-align:center!important;'),
        'Mobile footer logo AND its description should be centered.');
$assert(str_contains($chromeCss,'iw-hero-overlay-header-v2') &&
        str_contains($chromeCss,'position:fixed!important;') &&
        str_contains($chromeCss,'-webkit-backdrop-filter:none!important;') &&
        str_contains($chromeCss,'body.page-public-home .public-navbar.scrolled') &&
        str_contains($chromeCss,'background:#fff!important;'),
        'Hero header starts truly transparent and becomes white on scroll.');

$assert(str_contains($header,'iw-public-solutions-dropdown') &&
        str_contains($header,'iw-public-resources-dropdown') &&
        str_contains($header,'Our Solutions <i class="bi bi-chevron-down iw-nav-chevron"') &&
        str_contains($header,'Resources <i class="bi bi-chevron-down iw-nav-chevron"') &&
        str_contains($header,'aria-label="Our Solutions menu"') &&
        str_contains($header,'aria-label="Resources menu"') &&
        str_contains($header,'ms-xl-auto me-xl-3') &&
        !str_contains($header,'Explore all solutions') &&
        str_contains($header,'>Gansell</a>') &&
        str_contains($header,"public_url('solutions')"),
        'Navigation must be grouped with accessible visible dropdown chevrons.');
$assert(str_contains($chromeCss,'margin-left:auto!important;') &&
        str_contains($chromeCss,'gap:clamp(9px,1.15vw,18px)!important;'),
        'Desktop menu must be compact and right aligned.');

$assert(str_contains($routes,"'solutions/gansell'") &&
        str_contains($routes,"$".'publicSlug === '."'solutions'") &&
        !str_contains($routes,"['solutions', 'gansell']") &&
        str_contains($routes,"'/views/public-solutions.php'") &&
        str_contains($helpers,"$".'path = '."'solutions#gansell'") &&
        str_contains($meta,"'solutions' =>") &&
        str_contains($seo,"public_url('solutions')") &&
        !str_contains($seo,"$".'add(public_url('."'gansell'".'))'),
        'Gansell must use the single Our Solutions page; old links redirect to #gansell.');
$assert(str_contains($header,'20261115-brand-grayscale1')
    && str_contains($header,'20261115-center-focus1')
    && str_contains($header,'20261115-visual-finish1'),
    'Public CSS URLs must invalidate old client cache after this release.');
$assert(str_contains($deployer,'assets/branding/orangeserver-logo.svg')
    && str_contains($deployer,'assets/branding/rilahost-logo.svg'),
    'Deploy both hosting logo assets if missing from the public docroot.');
$assert(str_contains($landing,'id="iw-solutions"') &&
        str_contains($landing,'id="gansell"') &&
        str_contains($landing,'id="gansell-example"') &&
        str_contains($landing,'https://gansell.com/') &&
        str_contains($landing,'https://gansell.com/@ab-african') &&
        str_contains($landing,'Put your business online with Gansell.') &&
        str_contains($landing,'class="iw-gansell-free-seal"') &&
        str_contains($landing,'class="iw-gansell-product-gallery"') &&
        str_contains($landing,'class="iw-gansell-functions"') &&
        substr_count($landing,'<li>') === 6 &&
        str_contains($landing,'gansell-home-desktop.png') &&
        str_contains($landing,'gansell-home-mobile.png') &&
        str_contains($landing,'ab-african-store-desktop.png') &&
        str_contains($landing,'ab-african-store-mobile.png') &&
        !str_contains($landing,'iw-gansell-steps') &&
        !str_contains($landing,'iw-solutions-future') &&
        !str_contains($landing,'three ways') &&
        !str_contains($landing,'Digital tools for business.') &&
        !str_contains($landing,'Explore all solutions'),
        'Gansell must be an editorial product page: full-width real gallery, compact checks, real vendor example, no generic marketing panels.');
$assert(str_contains($landingCss,'iw-gansell-browser-shot img') &&
        str_contains($landingCss,'iw-gansell-product-gallery') &&
        str_contains($landingCss,'iw-gansell-phone-shot img') &&
        str_contains($landingCss,'iw-gansell-free-seal') &&
        str_contains($landingCss,'iw-gansell-functions li::before') &&
        str_contains($landingCss,'#f56613') &&
        strlen($landingCss) < 14000,
        'Gansell styling must use a prominent real screenshot, smaller mobile overlay, real FREE sticker and iWORKS colors.');
$assert(str_contains($header,'iw-solutions-critical-css') &&
        str_contains($landingCss,'@media(max-width:550px)') &&
        str_contains($landingCss,'@media(prefers-reduced-motion:reduce)'),
        'Solutions pages need split-root compatible responsive styles.');
$assert(str_contains($deployer,'check_remediation_release.php') &&
        str_contains($deployer,'--check|--apply FULL_SHA') &&
        str_contains($deployer,'portal.tgz') &&
        str_contains($deployer,'rollback()') &&
        !str_contains($deployer,'mysqldump') &&
        !str_contains($deployer,'DROP TABLE'),
        'Scoped production publisher must be immutable, fail-closed, and roll back.');
echo "solutions_visual_refinement_contract_test passed\n";
