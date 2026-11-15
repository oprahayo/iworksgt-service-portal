<?php
declare(strict_types=1);
/** The case-studies presentation must follow the iWORKS site, not a copied
 * design from the user's visual reference. Preserve existing client records. */
require dirname(__DIR__).'/app/editorial.php';
$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$check=static function(bool $ok,string $message):void {
    if (!$ok) throw new RuntimeException($message);
};
require_once $root.'/app/launch_case_study_catalog.php';
$cases=iworks_launch_case_studies();
$check(count($cases)===5,'All five original iWORKS client case studies must remain.');
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
    $check(isset($expected[$slug]),'Unrecognised case slug '.$slug);
    $check(iworks_editorial_case_tags($case)===$expected[$slug],'Wrong tags for '.$slug);
    $check(iworks_editorial_case_site($slug)===$case['website'],'Reviewed site link missing: '.$slug);
    $check(isset($manifest[$slug]) && (int)$manifest[$slug]['http_status']===200,'Real screenshot not verified: '.$slug);
    $image=$root.'/assets/branding/case-studies/'.$slug.'.webp';
    $check(is_file($image) && filesize($image)>10000,'Real WebP website preview missing: '.$slug);
}
$check(iworks_editorial_case_site('unknown-client')==='','Must not invent links for unidentified clients.');
$work=$read('views/public-case-studies-refined.php');
foreach(['iworks_editorial_cases(true','>Case studies</h1>','data-iw-work-filters',
    'data-iw-work-project','data-iw-work-categories','aria-pressed="false"',
    'aria-live="polite"','iw-work-card-preview','Read case study','Visit website',
    'data-iw-reveal','rel="noopener noreferrer"'] as $marker){
    $check(str_contains($work,$marker),'Missing case study behavior or link: '.$marker);
}
foreach(['iw-work-browser','iw-work-preview-overlay','SELECTED CLIENT PROJECTS',
    'Our Work<span','bi-arrow-up-right','iw-work-cta','YOUR PROJECT COULD BE NEXT'] as $marker){
    $check(!str_contains($work,$marker),'Borrowed or generic UI remains on listing: '.$marker);
}
$detail=$read('views/public-case-study-detail.php');
foreach(['Case studies','iw-work-detail-cover','iworks_editorial_case_site(',
    'Visit ','data-iw-reveal','rel="noopener noreferrer"'] as $marker){
    $check(str_contains($detail,$marker),'Case study detail missing: '.$marker);
}
foreach(['iw-work-browser','iw-work-detail-visit','CLIENT CASE STUDY','bi-arrow-up-right'] as $marker){
    $check(!str_contains($detail,$marker),'Copied visual language remains on case detail: '.$marker);
}
$home=$read('views/public-home-clean.php');
$check(str_contains($home,'<h2 id="iw-home-cases-title">Case studies</h2>'),'Homepage must have real Case studies heading.');
$check(!str_contains($home,'SELECTED WORK')
    && !str_contains($home,'Real projects. Real progress.')
    && !str_contains($home,'iw-work-browser'),'Keep the restored browser-window visuals scoped to homepage only.');
foreach(['iw-home-case-grid','View case study','Visit website','data-iw-showcase-track',
         'iworks_editorial_case_site(','iw-home-case-window'] as $marker){
    $check(str_contains($home,$marker),'Home case study feature missing: '.$marker);
}
$css=$read('assets/css/portfolio-work.css');
foreach(['--iw-cases-paper:#fffaf6','font-family:Inter','display:flex;',
    'scroll-snap-type:x mandatory',
    'object-position:center top','@media(max-width:650px)',
    '@media(prefers-reduced-motion:reduce)','.iw-cases-motion-ready'] as $marker){
    $check(str_contains($css,$marker),'Native brand typography or responsive motion missing: '.$marker);
}
$check(!str_contains($css,'#15181c') && !str_contains($css,'iw-work-browser'),
    'Dark copied portfolio or mock browser frames remain in CSS.');
$head=$read('views/partials/public-header.php');
$footer=$read('views/partials/public-footer.php');
$js=$read('assets/js/portfolio-work.js');
$check(str_contains($head,'portfolio-work.css') && str_contains($head,'$iwWorkCss'),
    'Styles must load safely on split cPanel roots.');
$check(str_contains($footer,'portfolio-work.js') && str_contains($footer,"'case_study'"),
    'Motion controller must also load for homepage and detail.');
$check(str_contains($js,'IntersectionObserver') && str_contains($js,'data-iw-reveal')
    && str_contains($js,'prefers-reduced-motion') && str_contains($js,'aria-pressed'),
    'Accessible motion and category filtering required.');
echo "portfolio_work_layout_contract_test passed\n";
