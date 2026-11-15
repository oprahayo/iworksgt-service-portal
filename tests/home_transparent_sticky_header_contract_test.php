<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/views/partials/public-header.php');
$footer=(string)file_get_contents($root.'/views/partials/public-footer.php');
$css=(string)file_get_contents($root.'/assets/css/site-launch-polish.css');
$deploy=(string)file_get_contents($root.'/scripts/deploy_home_hero_overlay.sh');
$assert=static function(bool $yes,string $why):void {if(!$yes)throw new RuntimeException($why);};
$assert(str_contains($header,"' page-public-home'") &&
        str_contains($header,'class="public-navbar sticky-top"'),
        'Overlay must target the existing homepage and sticky header, not inner pages.');
$assert(str_contains($footer,"navbar.classList.toggle('scrolled', window.scrollY > 10)") &&
        str_contains($footer,"window.addEventListener('scroll', syncScrollState, { passive: true })"),
        'Use the existing scroll state rather than duplicate listeners.');
$start=strpos($css,'/* iw-hero-overlay-header-v1');
$assert($start!==false,'Versioned hero/header patch missing.');
$overlay=substr($css,$start);
$assert(str_contains($overlay,'body.page-public-home .public-navbar {') &&
        str_contains($overlay,'position: sticky !important;') &&
        str_contains($overlay,'margin-bottom: calc(-1 * var(--iw-header-overlap)) !important;') &&
        str_contains($overlay,'background: transparent !important;') &&
        str_contains($overlay,'border-bottom-color: transparent !important;'),
        'Top-of-home header must overlay the hero transparently and preserve sticky positioning.');
$assert(str_contains($overlay,'body.page-public-home .public-navbar.scrolled {') &&
        str_contains($overlay,'background: #fff !important;') &&
        str_contains($overlay,'box-shadow: 0 5px 18px'),
        'After scroll, restore solid white header and subtle shadow.');
$assert(str_contains($overlay,'body.page-public-home .iw-clean-home .iw-clean-hero {') &&
        str_contains($overlay,'min-height: 100svh;') &&
        str_contains($overlay,'--iw-header-overlap: 65px;'),
        'Keep hero screen-height and match the smaller mobile nav height.');
$assert(str_contains($overlay,'body.page-public-home .public-navbar #publicNav,') &&
        str_contains($overlay,'background: #fff;') &&
        str_contains($overlay,'@media (prefers-reduced-motion: reduce)'),
        'Maintain legible opened mobile menu and reduced-motion settings.');
$assert(str_contains($deploy,'check_remediation_release.php') &&
        str_contains($deploy,'portal-css.tgz') &&
        str_contains($deploy,'rollback()') &&
        str_contains($deploy,'--check|--apply FULL_SHA') &&
        !str_contains($deploy,'mysqldump') && !str_contains($deploy,'DROP TABLE'),
        'Deploy through pinned source, mandatory preflight and private rollback, with no SQL writes.');
echo "home_transparent_sticky_header_contract_test passed\n";
