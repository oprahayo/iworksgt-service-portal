<?php
declare(strict_types=1);
function check_catalogue(bool $condition,string $message):void {
    if (!$condition) throw new RuntimeException($message);
}
function cac_public_service_record(array $service): ?array {
    if (($service['slug']??'')!=='company-registration') return $service;
    $service['name']='Business Registration';
    $service['price']=26000;
    $service['currency']='NGN';
    return $service;
}
function e(string $text):string{return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function url(string $page,array $params=[]):string{return 'index.php?'.http_build_query(['page'=>$page]+$params);}
function public_url(string $page,array $params=[]):string{
    return '/'.($page==='service'?'services':$page).($params?'/'.($params['slug']??''):'');
}
function money(float $v,string $currency):string{return $currency.' '.number_format($v,2);}
function default_currency():string{return 'NGN';}
function payment_currency_options():array{return ['NGN','USD'];}
function csrf_token():string{return 'safe-token';}
function iworks_client_service_icon(array $service):string{return 'bi-briefcase';}
require dirname(__DIR__).'/app/customer_service_policy.php';

$offer=static fn(string $slug,string $name,array $other=[]):array =>
    array_replace(['id'=>count($other)+8,'slug'=>$slug,'name'=>$name,'price'=>45000,
     'currency'=>'NGN','is_active'=>1,'is_public'=>1,'category'=>'business-registration'],$other);
$rows=[
    $offer('business-name-registration','Business Name Registration'),
    $offer('business-name-registration-cac-nigeria','Business Name Registration (CAC)'),
    $offer('company-registration','Company Registration'),
    $offer('launch-a-company-package','Launch a Company Package'),
    $offer('trademark','Trademark'),
    $offer('website-design-development','Website Design & Development'),
    $offer('social-enterprise-ltd-by-guarantee','Social Enterprise (Ltd by Guarantee)'),
    $offer('incorporated-trustees','Incorporated Trustees'),
    $offer('custom-service-system','Custom Service',['price'=>0]),
    $offer('bank-account-opening','Business Banking'),
    $offer('inactive-product','Deactivated service',['is_active'=>0]),
    $offer('private-product','Internal use only',['is_public'=>0]),
];
$catalogue=iworks_customer_published_catalogue($rows);
$slugs=array_column($catalogue,'slug');
check_catalogue(count($catalogue)===5,'Only five current public customer products should remain visible.');
check_catalogue(count(array_unique($slugs))===count($slugs),'Catalogue slugs must be unique.');
check_catalogue(in_array('company-registration',$slugs,true),'One canonical CAC product must remain.');
check_catalogue(!in_array('launch-a-company-package',$slugs,true),'Legacy company bundle must never be a new customer purchase.');
check_catalogue(!in_array('business-name-registration-cac-nigeria',$slugs,true),'Legacy Business Name duplicate must not appear.');
check_catalogue(!in_array('custom-service-system',$slugs,true),'Internal placeholder must never appear.');
check_catalogue(!in_array('bank-account-opening',$slugs,true),'Banking products must be excluded.');
$bySlug=array_column($catalogue,null,'slug');
check_catalogue($bySlug['company-registration']['name']==='Business Registration' &&
    (float)$bySlug['company-registration']['price']===26000.0,
    'Public CAC price must be the unified Business Name starting fee, not the old Ltd price.');
check_catalogue($bySlug['company-registration']['_iworks_checkout_mode']==='cac',
    'CAC must use its guided structure-specific intake.');
check_catalogue($bySlug['trademark']['_iworks_checkout_mode']==='trademark',
    'Trademark must use its class-aware wizard.');
check_catalogue($bySlug['incorporated-trustees']['_iworks_checkout_mode']==='consultation'
    && $bySlug['social-enterprise-ltd-by-guarantee']['_iworks_checkout_mode']==='consultation',
    'Specialist NGO/legal products must not use generic checkout.');
check_catalogue(iworks_customer_can_standard_checkout($bySlug['website-design-development']),
    'Website design should retain ordinary invoice checkout.');
foreach(['company-registration','trademark','incorporated-trustees','social-enterprise-ltd-by-guarantee'] as $slug) {
    check_catalogue(!iworks_customer_can_standard_checkout($bySlug[$slug]),'Unsafe checkout allowed: '.$slug);
}
check_catalogue(!iworks_customer_can_standard_checkout($rows[10]),'Inactive products must not be purchased.');
check_catalogue(!iworks_customer_can_standard_checkout($rows[11]),'Private products must not be purchased.');
$zero=$offer('free-but-not-public','Unsupported zero-price item',['price'=>0]);
check_catalogue(!iworks_customer_can_standard_checkout($zero),'Zero-price standard orders must not become silent invoices.');

$checkoutService=$bySlug['website-design-development'];
ob_start();
require dirname(__DIR__).'/views/client-service-checkout.php';
$html=(string)ob_get_clean();
check_catalogue(str_contains($html,'Review your service') && str_contains($html,'Website Design'),
    'Single-product review must display chosen product.');
check_catalogue(str_contains($html,'name="service_id"') && str_contains($html,'safe-token'),
    'Checkout must post service ID and CSRF token.');
check_catalogue(!str_contains($html,'service-catalog-grid') && !str_contains($html,'All Services ('),
    'Old multi-product selection list must not render inside checkout.');
check_catalogue(str_contains($html,'Continue to invoice') && str_contains($html,'Estimated total'),
    'Clear invoice stage and calculated tax must be shown.');

$root=dirname(__DIR__);
$router=file_get_contents($root.'/index.php');
$auth=file_get_contents($root.'/app/order_intent.php');
$domain=file_get_contents($root.'/app/domain.php');
$client=file_get_contents($root.'/views/client-dashboard.php');
$services=file_get_contents($root.'/views/public-services-refined.php');
$detail=file_get_contents($root.'/views/public-service-detail.php');
$bootstrap=file_get_contents($root.'/app/bootstrap.php');
check_catalogue(str_contains($bootstrap,"require_once __DIR__ . '/customer_service_policy.php'"),
    'Policy must load for public and authenticated routes.');
check_catalogue(str_contains($domain,'iworks_customer_published_catalogue($catalog)') &&
    str_contains($router,'iworks_client_catalogue(list_public_services_catalog())'),
    'Public and client dashboards must share a single published catalogue source.');
check_catalogue(str_contains($router,"require __DIR__.'/views/client-service-checkout.php'"),
    'Legacy client cart must be bypassed.');
check_catalogue(str_contains($router,'iworks_customer_can_standard_checkout($requestedService)'),
    'POST cannot bypass product eligibility.');
check_catalogue(str_contains($router,'unset($payload') &&
    str_contains($router,'$payload[\'title\'] = (string)$requestedService[\'name\']'),
    'POST cannot override prices and product titles.');
check_catalogue(str_contains($auth,"return url('cac_filing');"),
    'CAC sign-in must land at the company/business-name structure selector.');
check_catalogue(str_contains($client,"$"."checkoutMode === 'consultation'") &&
    str_contains($detail,'Request a specialist quote') &&
    str_contains($services,'Specialist quote'),
    'Public/customer product action must agree on assisted services.');
check_catalogue(str_contains(file_get_contents($root.'/views/partials/header.php'),
    'customer-focused-checkout.css'),'Customer checkout needs its responsive stylesheet.');
echo "customer_catalogue_unification_test passed\n";
