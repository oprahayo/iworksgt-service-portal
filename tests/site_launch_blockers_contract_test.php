<?php
declare(strict_types=1);
/** Static regression gates for the iWorks launch-blocker repair. */
$root=dirname(__DIR__);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$ok=static function(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException($message);
};
$auth=$read('app/auth.php');
$support=$read('views/support.php');
$css=$read('assets/css/site-launch-polish.css');
$widget=$read('views/partials/public-assistance.php');
$bookJs=$read('assets/js/public-assistance.js');
$publicHeader=$read('views/partials/public-header.php');
$portalHeader=$read('views/partials/header.php');
$jobsRouter=$read('index.php');
$footer=$read('views/partials/public-footer.php');
$ok(str_contains($support,'role_has_admin_access(') && str_contains($auth,'function role_has_admin_access('),
  'Support must not call an undefined role classification helper.');
$ok(str_contains($auth,'function role_has_admin_or_finance_access('),
  'Finance/Admin legacy role helper must exist for older templates.');
$ok(str_contains($widget,'iwAssistDateOptions') && str_contains($widget,'iwAssistTimeOptions'),
  'Consultation must use digital day/time controls.');
$ok(!str_contains($widget,'type="date"') && !str_contains($widget,'type="time"'),
  'Consultation must not fall back to browser native calendar inputs.');
$ok(str_contains($bookJs,'rebuildRequestDays()') && str_contains($bookJs,'renderRequestTimes()')
  && str_contains($bookJs,'requestTimes.includes(time)'),
  'Suggested request days and start times need real click handlers and validation.');
$ok(str_contains($bookJs,'West Africa Time') || str_contains($widget,'West Africa Time'),
  'Appointment times must identify their timezone.');
$ok(str_contains($widget,'not live availability') && str_contains($bookJs,'not live availability'),
  'Consultation must not imply appointments are already reserved.');
$ok(str_contains($widget,'data-assist-add-guest') && str_contains($bookJs,'new Set(guests)'),
  'Additional guest emails must remain available and validated.');
$ok(str_contains($css,'body.page-public .iw-back-to-top') &&
  str_contains($css,'left:clamp(') && str_contains($css,'right:auto!important'),
  'Back-to-top must be separated from the right-floating support launcher.');
$ok(str_contains($bookJs,"classList.add('iw-assist-chat-open')") &&
  str_contains($bookJs,"classList.add('iw-assist-book-open')"),
  'Expanded chat and booking states must control floating elements.');
$ok(str_contains($css,'body.page-public .iw-sitewide-cta-assurances i') &&
  str_contains($css,'color:#ff8c48') && str_contains($css,'body.page-public .iw-sitewide-cta-primary'),
  'Homepage ready-to-launch CTA and its icons need orange accents.');
$ok(str_contains($css,'body.portal-authenticated .service-card-actions') &&
  str_contains($css,'grid-template-columns:minmax(0,1fr)!important'),
  'Legacy four-column cart buttons must not overlap.');
$ok(str_contains($publicHeader,'site-launch-polish.css') &&
  str_contains($portalHeader,'site-launch-polish.css'),
  'Both public and portal must load the site launch polish stylesheet.');
$ok(str_contains($jobsRouter,'Custom service orders require an administrator-approved quote'),
  'Users must not be able to order an internal zero-price placeholder.');
$ok(str_contains($jobsRouter,"'business-name-registration','business-name-registration-cac-nigeria','company-registration'"),
  'Checkout cannot bypass the guided CAC application.');
$ok(str_contains($footer,'data-iw-to-top'),
  'Existing back-to-top behavior must remain functional.');
echo "site_launch_blockers_contract_test passed\n";
