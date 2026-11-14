<?php
declare(strict_types=1);

/** Portfolio contract: genuine website previews, brand-safe navigation and
 * idempotent public case presentation without changing CMS schema. */
require dirname(__DIR__).'/app/editorial.php';
$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$check=static function(bool $condition,string $failure):void {
    if (!$condition) throw new RuntimeException($failure);
};
$catalog=$read('app/launch_case_study_catalog.php');
require_once $root.'/app/launch_case_study_catalog.php';
$cases=iworks_launch_case_studies();
$check(count($cases)===5,'Expected five owner-approved case-study snapshots.');
$expected=[
    'amandla-institute-formation-hosting'=>['formation','hosting','nonprofit'],
    'triply-travels-company-formation-travel-website-portals'=>['formation','web','travel'],
    'restoration-llc-healthcare-website-design-hosting-management'=>['web','hosting','healthcare'],
    'karis-health-services-digital-healthcare-presence'=>['web','healthcare'],
    'cornerstonetl-senior-care-website-design-hosting'=>['web','hosting','healthcare'],
];
$manifest=json_decode($read('assets/branding/case-studies/capture-manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($cases as $case){
    $slug=(string)$case['slug'];
    $check(isset($expected[$slug]),'Unknown case-study slug '.$slug);
    $check(iworks_editorial_case_tags($case)===$expected[$slug],'Wrong service category '.$slug);
    $check(iworks_editorial_case_site($slug)===$case['website'],'External link does not match reviewed project catalog.');
    $check(isset($manifest[$slug]) && (int)$manifest[$slug]['http_status']===200,
        'Screenshot origin missing verified site response: '.$slug);
    $check(is_file($root.'/assets/branding/case-studies/'.$slug.'.webp')
        && filesize($root.'/assets/branding/case-studies/'.$slug.'.webp')>10000,
        'Real browser preview missing: '.$slug);
}
$check(iworks_editorial_case_site('unrecognized-case')==='','Unknown case must not receive an invented website link.');
$check(iworks_editorial_case_tags(['slug'=>'unknown','category'=>'Online store and Ecommerce Website Development'])===['web'],
    'Generic CMS categories should remain filterable.');
$work=$read('views/public-case-studies-refined.php');
foreach(['data-iw-work-filters','data-iw-work-project','data-iw-work-categories',
         'data-iw-work-count','aria-pressed="false"','aria-live="polite"',
         'iw-work-card-preview','iw-work-browser','Visit website',
         'View case study','rel="noopener noreferrer"'] as $needle) {
    $check(str_contains($work,$needle),'Missing accessible image-first portfolio element: '.$needle);
}
$detail=$read('views/public-case-study-detail.php');
$check(str_contains($detail,'iworks_editorial_case_site(')
    && str_contains($detail,'iw-work-detail-cover')
    && str_contains($detail,'Visit client website')
    && str_contains($detail,'rel="noopener noreferrer"'),
    'Detailed case study must display real capture and reviewed client website link.');
$home=$read('views/public-home-clean.php');
$check(str_contains($home,'iw-home-case-actions')
    && str_contains($home,'iworks_editorial_case_site(')
    && str_contains($home,'View project')
    && str_contains($home,'Visit website'),
    'Homepage Selected Work must feature real website preview and separate visitor links.');
$head=$read('views/partials/public-header.php');
$footer=$read('views/partials/public-footer.php');
$style=$read('assets/css/portfolio-work.css');
$js=$read('assets/js/portfolio-work.js');
$check(str_contains($head,'portfolio-work.css')&&str_contains($head,'$iwWorkCss')
    && str_contains($footer,'portfolio-work.js')&&str_contains($footer,'$iwWorkJs'),
    'Source-backed CSS and JS must load on the split cPanel public domain.');
$check(str_contains($style,'grid-template-columns:repeat(2,minmax(0,1fr))')
    && str_contains($style,'@media(max-width:640px)')
    && str_contains($style,'prefers-reduced-motion:reduce')
    && str_contains($style,'object-position:center top'),
    'Screenshot grid must remain responsive and accessible.');
$check(str_contains($js,'aria-pressed')&&str_contains($js,'data-iw-work-project')
    && str_contains($js,'project.hidden = !visible'),
    'Category buttons must actually filter the visible case studies.');
echo "portfolio_work_layout_contract_test passed\n";
