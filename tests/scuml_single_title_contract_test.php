<?php
declare(strict_types=1);
/** One public service title, clear Apply button and no decorative arrow prompts. */
$root=dirname(__DIR__);
$detail=(string)file_get_contents($root.'/views/public-service-detail.php');
$css=(string)file_get_contents($root.'/assets/css/scuml-service.css');
$assert=static function(bool $yes,string $why):void {if(!$yes)throw new RuntimeException($why);};
$start=strpos($detail,'<?php if ($isScumlSupport): ?>');
$end=strpos($detail,'<?php else: ?>', $start);
$assert($start!==false && $end!==false && $end>$start,'SCUML product section not found.');
$block=substr($detail,$start,$end-$start);
$assert(str_contains($detail,'<h1><?= e($serviceName) ?></h1>') &&
  str_contains($detail,'<?php if (!$isScumlSupport && $shortDescription') &&
  str_contains($detail,'<?php if (!$isScumlSupport): ?>') &&
  !str_contains($block,'<h2>') &&
  !str_contains($block,'iw-scuml-eyebrow'),
  'SCUML should show only the service name as the main title, without a second marketing headline or breadcrumb.');
$assert(str_contains($block,'Apply for support') &&
  str_contains($block,'href="<?= e($actionUrl) ?>"') &&
  !str_contains($block,'bi-arrow-up-right') &&
  !str_contains($block,'bi-arrow-right'),
  'Use one direct customer onboarding button without arrow decorations.');
$assert(!str_contains($block,'Check official requirements') &&
  !str_contains($block,'scuml.efcc.gov.ng/registration-guideline/') &&
  str_contains($block,'<h3>Documents for review</h3>'),
  'Remove unnecessary official-guideline link from the document-review checklist.');
$assert(str_contains($block,'<h3>Special business sectors</h3>') &&
  str_contains($block,'class="iw-scuml-sector-button"') &&
  str_contains($block,'Discuss your requirements') &&
  str_contains($css,'.iw-scuml-sector-button') &&
  !str_contains($block,'Discuss a special-sector case <i'),
  'Keep the special-sector section but replace text link with a compact no-arrow button.');
$assert(str_contains($detail,'<?php if (!$isScumlSupport && $relatedServices !== []): ?>'),
  'No unrelated service cards and extra arrow links after the SCUML product.');
echo "scuml_single_title_contract_test passed\n";
