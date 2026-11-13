<?php
declare(strict_types=1);
/* Render CAC intake in isolation to catch runtime template errors before cPanel. */
function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');}
function url(string $page,array $params=[]):string{return 'index.php?'.http_build_query(['page'=>$page]+$params);}
function csrf_token():string{return 'test-csrf-token';}
function setting(string $key,string $default=''):string{return $default;}
function cac_money_setting(string $key,float $default):float{return $default;}
function cac_virtual_address_eligible():bool{return false;}
function cac_registration_natures():array{return ['Information technology','Agriculture','Trading'];}
function cac_id_type_options():array{return ['NIN','International passport'];}
function cac_name_review(string $name,string $kind):array{return ['errors'=>[],'warnings'=>[],'suggestions'=>[]];}
function check_render(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}

$render=static function(string $kind):string{
    $cacDraft=null;
    $filingKind=$kind;
    $cacQuote=null;
    $cacErrors=[];
    $client=['email'=>'customer@example.test','phone'=>'08001234567'];
    ob_start();
    require dirname(__DIR__).'/views/client-cac-filing.php';
    return (string)ob_get_clean();
};
$business=$render('business');
$company=$render('company');
foreach(['data-cac-stage="1"','data-cac-stage="2"','data-cac-stage="3"',
    'id="cacName1"','id="cacNature"','id="cacAddressMode"',
    'id="cacPeople"','id="cacFilingForm"','name="form_action" value="save_draft"'] as $tag){
    check_render(str_contains($business,$tag),'Business-name application missing '.$tag);
    check_render(str_contains($company,$tag),'Private Ltd application missing '.$tag);
}
check_render(str_contains($business,'Sole proprietorship') && str_contains($business,'Partnership'),
    'Business Name flow must show solo or partner options.');
check_render(!str_contains($business,'Issued share capital'),
    'Enterprise must not request company shares.');
check_render(str_contains($company,'Issued share capital') && str_contains($company,'Shareholding'),
    'Ltd flow must request company capital and ownership.');
check_render(!str_contains($company,'<option value="limited_by_guarantee"'),
    'Ltd by Guarantee should not appear in self-service choices.');
check_render(str_contains($company,'private_limited') && str_contains($company,'test-csrf-token'),
    'Private Ltd must preserve exact legal form and CSRF.');
check_render(str_contains($business,'data-cac-next="2"') &&
    str_contains($business,'data-cac-next="3"') &&
    str_contains($business,'validStage(stepNumber)'),
    'Intake must progress with client-side required field validation.');
check_render(str_contains($business,'hasSavedDraft=false') &&
    str_contains($business,'data-cac-jump="4" disabled'),
    'Document stage unavailable until draft is securely saved.');
check_render(str_contains($business,'no payment is taken yet')
    || str_contains($business,'No payment is taken yet'),
    'Show clear no-payment-until-review message.');
echo "cac_guided_render_test passed\n";
