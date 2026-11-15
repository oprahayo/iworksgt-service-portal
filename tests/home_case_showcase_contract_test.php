<?php
declare(strict_types=1);
// The homepage must remain a genuine, accessible, drag-to-scroll portfolio.
// This test makes no database or network calls.
$root = dirname(__DIR__);
$read = static fn(string $path):string => (string)file_get_contents($root.'/'.$path);
$check = static function(bool $value,string $description):void {
    if (!$value) throw new RuntimeException($description);
};
$home = $read('views/public-home-clean.php');
$start = strpos($home,'<section class="iw-home-case-stories"');
$end = strpos($home,'<section class="iw-clean-section iw-clean-support"',$start);
$check($start!==false && $end!==false,'Home showcase must precede support section.');
$section = substr($home,$start,$end-$start);
foreach([
  'data-iw-showcase', 'data-iw-showcase-track','data-iw-showcase-slide',
  'data-iw-showcase-prev','data-iw-showcase-next','data-iw-showcase-status',
  'iw-home-case-window','iw-home-case-overlay','iw-home-case-overlay-actions',
  'iw-home-case-grid','aria-roledescription="carousel"','tabindex="0"',
  'draggable="false"','View case study','Visit website',
  'aria-live="polite"','foreach ($homeCases as $story)',
  'See all case studies'
] as $marker) {
    $check(str_contains($section,$marker),'Missing carousel feature: '.$marker);
}
$check(!str_contains($section,"$".'story[\'summary\']')
   && !str_contains($section,"$".'story[\'outcome\']'),
    'Homepage must show project category/name, not long descriptions.');
$check(!str_contains($section,'Real projects. Real progress.')
   && !str_contains($section,'SELECTED WORK'),'Previously rejected homepage slogan must stay removed.');
$check(str_contains($section,'<h2 id="iw-home-cases-title">Case studies</h2>'),
    'Section title must be Case studies.');
$check(str_contains($home,"iworks_editorial_cases(true, 6)"),
    'Use genuine published CMS case studies; do not fabricate carousel items.');
$check(str_contains($section,'$caseScreenshotPath')
    && str_contains($section,'is_file(__DIR__')
    && str_contains($section,'loading="eager"')
    && str_contains($section,'$casePreview'),
    'Mobile carousel must use reviewed WebP screenshot assets with eager loading.');
$nav = $read('views/partials/public-header.php');
$check(substr_count($nav,'>>Case Studies</a>')===0 || str_contains($nav,'Case Studies'),
    'Case Studies navigation not present.');
$check(str_contains($nav,'$isUseCasesActive')
    && str_contains($nav,"['case-studies', 'case_study']")
    && substr_count($nav,'href="<?= e($useCasesHref) ?>"')>=2,
    'Desktop About dropdown and mobile About submenu must link to Case Studies.');
$check(!str_contains($nav,'Pricing &amp; services')
    && !str_contains($nav,'>Find a service</a>')
    && substr_count($nav,'>Case Studies</a>')===2,
    'Remove duplicate mobile Pricing and Services and top-level Case Studies, but retain discoverability.');
$check(str_contains($nav,'iw-public-about-dropdown')
    && str_contains($nav,'iw-public-about-menu')
    && str_contains($nav,'<summary>What we do'),
    'Desktop must have a compact About dropdown while mobile keeps What we do.');
$css = $read('assets/css/portfolio-work.css');
$sharedCss = $read('assets/css/editorial-brand.css');
$check(!str_contains($sharedCss,'.iw-home-case-visual{display:none}'),
    'Do not hide the entire graphical case-study image panel on mobile.');
$check(str_contains($css,'display:block!important; /* override legacy mobile'),
    'The case study preview must have a scoped explicit display override.');
foreach([
  'background:#fff6ee!important','scroll-snap-type:x mandatory',
  '.iw-home-case-window-bar','.iw-home-case-overlay',
  'linear-gradient(180deg','cursor:grab','is-dragging',
  '@media(hover:none)','@media(prefers-reduced-motion:reduce)'
] as $marker) {
    $check(str_contains($css,$marker),'Carousel styling missing: '.$marker);
}
$js = $read('assets/js/portfolio-work.js');
foreach([
  'function initShowcase()','pointerdown','pointermove','pointerup',
  'pointercancel','lostpointercapture','data-iw-showcase-track',
  'data-iw-showcase-prev','data-iw-showcase-next',
  "addEventListener('wheel'","addEventListener('keydown'",
  'prefers-reduced-motion: reduce','initShowcase();'
] as $marker){
    $check(str_contains($js,$marker),'Carousel behavior missing: '.$marker);
}
$detail = $read('views/public-case-study-detail.php');
foreach([
 'iw-case-project-layout','iw-case-project-facts',
 'iw-case-project-content','iw-case-project-outcome',
 '<details class="iw-case-project-notes">','Project details',
 'Work delivered','Overview','Outcome',
 'sanitize_limited_html','iworks_editorial_case_site('
] as $marker){
  $check(str_contains($detail,$marker),'Structured digital case-study detail missing: '.$marker);
}
$check(!str_contains($detail,'iw-work-browser'),
    'The browser window mockup should be homepage-only.');
echo "home_case_showcase_contract_test passed\n";
