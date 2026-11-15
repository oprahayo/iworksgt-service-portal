<?php
declare(strict_types=1);
/* Approved carousel composition: centered dominant story, side peeks. */
$root=dirname(__DIR__);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$assert=static function(bool $yes,string $why):void{if(!$yes)throw new RuntimeException($why);};
$css=$read('assets/css/portfolio-work.css');
$js=$read('assets/js/portfolio-work.js');
$header=$read('views/partials/public-header.php');
$footer=$read('views/partials/public-footer.php');
$home=$read('views/public-home-clean.php');
$script=$read('scripts/deploy_centered_case_showcase.sh');
foreach([
  '--iw-case-card-width:min(54vw,660px)',
  'padding:14px max(0px,calc((100% - var(--iw-case-card-width)) / 2))',
  'scroll-snap-align:center;',
  'transform:translateY(11px) scale(.89)',
  '.iw-home-case-card.is-active',
  '.iw-home-case-grid:not(.is-ready) .iw-home-case-card:first-child',
  '--iw-case-card-width:min(82vw,415px)',
  'scroll-snap-type:x mandatory',
  '@media(prefers-reduced-motion:reduce)'
] as $marker)$assert(str_contains($css,$marker),'Centered focus styling missing: '.$marker);
foreach([
  'function centeredShowcaseOffset(',
  'function initialShowcaseIndex(',
  'function initShowcase()',
  "centerSlide(initialShowcaseIndex(slides.length), 'auto')",
  "track.classList.add('is-ready')",
  "slide.classList.toggle('is-active', focused)",
  "slide.setAttribute('aria-current', 'true')",
  "centerSlide(activeIndex + direction",
  'getBoundingClientRect()',
  "addEventListener('pointermove'",
  "addEventListener('wheel'",
  "addEventListener('keydown'"
] as $marker)$assert(str_contains($js,$marker),'Centered selection behavior missing: '.$marker);
$assert(str_contains($header,'20261115-center-focus1')
 && str_contains($footer,'20261115-center-focus1'),
 'Split-root CSS and JS must have fresh cache versions.');
$assert(str_contains($home,'data-iw-showcase-track')
 && str_contains($home,'data-iw-showcase-slide')
 && str_contains($home,'iw-home-case-window')
 && str_contains($home,'data-iw-showcase-prev')
 && str_contains($home,'data-iw-showcase-next')
 && !str_contains($home,'iw-home-case-overlay-actions'),
 'Do not remove existing carousel screenshots, navigation or click through.');
$assert(str_contains($script,'check_remediation_release.php')
 && str_contains($script,'portal.tgz')
 && str_contains($script,'rollback()')
 && str_contains($script,'--check|--apply FULL_SHA')
 && !str_contains($script,'mysqldump')
 && !str_contains($script,'DROP DATABASE'),
 'Publisher must verify immutable source and create private rollback backups.');
echo "centered_case_study_carousel_contract_test passed\n";
