<?php
declare(strict_types=1);
$GLOBALS['regional_settings']=[];
function setting(string $key,string $fallback=''):string {
    return (string)($GLOBALS['regional_settings'][$key]??$fallback);
}
function portal_country_options():array {
    return ['NG'=>'Nigeria','GB'=>'United Kingdom','US'=>'United States','CA'=>'Canada',
      'DE'=>'Germany','AU'=>'Australia','ES'=>'Spain','GH'=>'Ghana'];
}
function default_currency():string{return 'NGN';}
function money(float $amount,string $currency):string{return $currency.' '.number_format($amount,2);}
function fx_ngn_per_usd():float{return 1400;}
function is_logged_in():bool{return false;}
function get_service_by_slug(string $slug):?array {
    return $slug==='company-registration' ? ['id'=>10,'name'=>'Company Registration','slug'=>$slug,
        'price'=>120000,'currency'=>'NGN','tax_percent'=>7.5,'is_active'=>1,'is_public'=>1] : null;
}
function check_launch(bool $ok,string $message):void {
    if(!$ok) throw new RuntimeException($message);
}
require dirname(__DIR__).'/app/launch_pricing.php';
require dirname(__DIR__).'/app/customer_service_policy.php';
require dirname(__DIR__).'/app/cac_registration_policy.php';
require dirname(__DIR__).'/app/cac_filing.php';
foreach(['Nigeria','ng','NG'] as $country) {
    check_launch(iworks_launch_currency_for_country($country)==='NGN',
        'Nigeria must be invoiced in NGN.');
}
foreach(['United Kingdom','Canada','United States','Germany','Australia','Spain','GH'] as $country) {
    check_launch(iworks_launch_currency_for_country($country)==='USD',
        'Overseas account must be invoiced in USD, not NGN: '.$country);
}
try { iworks_launch_billing_country(['country'=>'']);throw new RuntimeException('Missing country should fail closed'); }
catch(RuntimeException $e) { check_launch(str_contains($e->getMessage(),'billing country'),
    'Missing country must reject new price.'); }
$nigeria=iworks_launch_registration_options('NG');
$overseas=iworks_launch_registration_options('GB');
check_launch(count($nigeria)===4,'Four registration choices required.');
check_launch($nigeria['business']['price']===49000.0,'BN Nigeria package is NGN 49,000.');
check_launch($nigeria['company-small']['price']===60000.0,'Small Ltd Nigeria package is NGN 60,000.');
check_launch($nigeria['company-large']['price']===150000.0,'Large Ltd Nigeria package is NGN 150,000.');
check_launch($nigeria['ngo']['price']===180000.0&&$nigeria['ngo']['mode']==='quote',
    'NGO from 180k needs specialist review.');
check_launch($overseas['business']['price']===95.0&&$overseas['company-large']['price']===325.0,
    'USD regional packages must have independent higher commercial prices.');
check_launch($overseas['company-small']['price']===175.0&&$overseas['ngo']['price']===495.0,
    'Publish both smaller Ltd and NGO diaspora price points.');
check_launch($overseas['business']['currency']==='USD' && $nigeria['business']['currency']==='NGN',
    'Account-country currency must be locked.');
$webNg=iworks_launch_website_packages('NG');
$webUs=iworks_launch_website_packages('US');
check_launch(count($webNg)===4&&$webNg['starter']['amount']===250000.0
    &&$webUs['starter']['amount']===480.0
    &&$webNg['custom']['amount']===0.0,
    'Websites need three transparent indicative tiers and a custom quote.');
$other=['slug'=>'graphics-design','name'=>'Graphics Design','price'=>70000,'currency'=>'NGN'];
check_launch(iworks_launch_service_rate($other,'GB')['amount']===195.0
    &&iworks_launch_service_rate($other,'NG')['amount']===95000.0,
    'Graphics Nigeria/diaspora rates must not be FX conversions.');
check_launch(iworks_launch_service_rate(['slug'=>'unconfigured','name'=>'Special'], 'GB')['mode']==='quote',
    'Never convert an unconfigured Nigerian service into a payable USD offer.');
$site=iworks_customer_published_service([
    'slug'=>'website-design-development','name'=>'Website Design & Development',
    'is_active'=>1,'is_public'=>1,'price'=>250000,'currency'=>'NGN'
]);
check_launch($site['_iworks_checkout_mode']==='consultation'
    &&!iworks_customer_can_standard_checkout($site),
    'Bespoke web work cannot auto-generate fixed generic invoices.');
$ngo=iworks_customer_published_service([
    'slug'=>'incorporated-trustees','name'=>'Incorporated Trustees',
    'is_active'=>1,'is_public'=>1,'price'=>180000,'currency'=>'NGN'
]);
check_launch($ngo['_iworks_checkout_mode']==='consultation','NGO must use a quote.');
$GLOBALS['regional_settings']['launch_bn_ngn']='52000';
check_launch(iworks_launch_registration_options('NG')['business']['price']===52000.0,
    'Admin-configured launch pricing must be authoritative.');
$GLOBALS['regional_settings']['launch_bn_ngn']='';
check_launch(iworks_launch_registration_options('NG')['business']['price']===49000.0,
    'Invalid regional rate must fall back to safe package default.');
$GLOBALS['regional_settings']=[];
$data=['people'=>[['role'=>'Director']], 'currency'=>'NGN',
       'share_capital'=>'1000000','address_mode'=>'own'];
$small=cac_filing_quote('company',$data);
check_launch($small['subtotal']===60000.0&&$small['base_price']===60000.0,
    'Company small checkout must match advertised launch price.');
$data['share_capital']='2000000';
$large=cac_filing_quote('company',$data);
check_launch($large['subtotal']===150000.0&&$large['package']==='company-large',
    'Capital between NGN 2m and 5m must use large tier.');
$data['currency']='USD';
$usd=cac_filing_quote('company',$data);
check_launch($usd['subtotal']===325.0 && $usd['currency']==='USD',
    'Diaspora checkout USD amount is not Nigerian FX conversion.');
$data['share_capital']='6000000';
try { cac_filing_quote('company',$data);throw new RuntimeException('Large capital should require review'); }
catch(RuntimeException $e) {check_launch(str_contains($e->getMessage(),'capital'),
    'Unpriced large share capital must be rejected safely.');}
$bn=cac_filing_quote('business',['people'=>[['role'=>'proprietor']],
    'structure'=>'sole','currency'=>'NGN','address_mode'=>'own']);
check_launch($bn['total']===49000.0,'Business Name checkout must match package list.');
$source=static fn(string $p):string=>(string)file_get_contents(dirname(__DIR__).'/'.$p);
$router=$source('index.php');
$checkout=$source('views/client-service-checkout.php');
$wizard=$source('views/client-cac-filing.php');
$trademark=$source('views/client-trademark.php');
$profile=$source('views/client-profile.php');
$quote=$source('views/client-quote-request.php');
$domain=$source('app/domain.php');
check_launch(str_contains($router,"case 'quote_request':")
  &&str_contains($router,'iworks_launch_notify_new_order')
  &&str_contains($router,'iworks_launch_account_currency(current_user())'),
  'Quote route, customer currency and order email must be wired.');
check_launch(str_contains($checkout,'iworks_launch_service_rate')
  &&str_contains($checkout,'name="pay_currency" value="'),
  'Standard checkout must be server priced with fixed invoice currency.');
check_launch(!str_contains($checkout,'<select name="pay_currency"'),
  'Checkout must not expose an NGN/USD choice.');
check_launch(str_contains($wizard,'iworks_launch_account_currency($client)')
  && !str_contains($wizard,'<select name="filing[currency]"'),
  'CAC wizard must not allow choosing cheaper regional currency.');
check_launch(!str_contains($trademark,'<select id="tmCurrency"'),
  'Trademark wizard cannot expose a currency switch.');
check_launch(str_contains($profile,'Request a billing-country review')
  &&str_contains($router,'Billing country changes require a support review.'),
  'Accounts must not self-switch billing country to buy lower region price.');
check_launch(str_contains($quote,'Submit quotation request')
  &&str_contains($router,"'submit_quote'")===false,
  'Quote submission should be handled only by the dedicated route.');
check_launch(str_contains($domain,"['admin','finance','agent']") || str_contains($router,"['admin','finance','agent']"),
  'Agents must receive quotation/team notifications.');
check_launch(str_contains($source('views/services.php'),'save_launch_prices'),
  'Admins need actual editable regional pricing, not constants only.');
echo "launch_regional_catalogue_test passed\n";
