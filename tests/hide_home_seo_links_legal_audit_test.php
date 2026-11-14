<?php
declare(strict_types=1);
// Verify that no SEO-only landing-page paragraph is rendered in the public
// home template, while actual published routes/meta remain functional.
$root=dirname(__DIR__);
$home=(string)file_get_contents($root.'/views/public-home-clean.php');
$marketing=(string)file_get_contents($root.'/app/public_marketing.php');
$seo=(string)file_get_contents($root.'/app/public_seo.php');
$router=(string)file_get_contents($root.'/index.php');
$installer=(string)file_get_contents($root.'/scripts/deploy_hide_home_seo_links.sh');
$audit=(string)file_get_contents($root.'/docs/prelaunch-legal-risk-audit-2026-11-14.md');
function check_home_legal(bool $pass,string $message):void {
    if(!$pass)throw new RuntimeException($message);
}
check_home_legal(!str_contains($home,'Building across borders?') &&
    !str_contains($home,'iw-home-international-links'),
    'SEO landing links should not be a visible homepage services paragraph.');
check_home_legal(str_contains($home,'iw-clean-service-grid') &&
    str_contains($home,'What do you need help with?'),
    'Homepage service grid and heading must be preserved.');
check_home_legal(str_contains($marketing,"'register-company-nigeria-from-abroad'") &&
    str_contains($marketing,"'us-llc-formation-from-nigeria'"),
    'International SEO titles/descriptions must be preserved.');
check_home_legal(str_contains($seo,'iworks_seo_canonical') &&
    str_contains($seo,'iworks_seo_public_sitemap'),
    'Canonical and sitemap functions must be preserved.');
check_home_legal(str_contains($router,"'register-company-nigeria-from-abroad'") &&
    str_contains($router,"'us-llc-formation-from-nigeria'"),
    'Cross-border landing routes must be preserved.');
check_home_legal(str_contains($installer,'check_remediation_release.php') &&
    str_contains($installer,'class=\"iw-home-international-links\"') &&
    str_contains($installer,'public-home-clean.php') &&
    str_contains($installer,'rollback()') &&
    str_contains($installer,'home-seo-line-'),
    'Surgical installer must have exact-match safety gate and rollback.');
check_home_legal(!str_contains($installer,'DROP DATABASE') &&
    !str_contains($installer,'mysqldump') &&
    !str_contains($installer,'assets/uploads'),
    'Homepage metadata correction must not affect database or uploads.');
foreach(['L01','L02','L03','L04','L05','L06','L07','FCCPC','NDPC'] as $item) {
    check_home_legal(str_contains($audit,$item),'Pre-launch legal audit incomplete: '.$item);
}
echo "hide_home_seo_links_legal_audit_test passed\n";
