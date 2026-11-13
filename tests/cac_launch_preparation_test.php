<?php
declare(strict_types=1);
function cac_assert(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException($message);
}
function setting(string $key,string $default=''):string{return $default;}
function get_service_by_slug(string $slug):?array{return $slug==='company-registration'?['price'=>100000,'currency'=>'NGN']:null;}
function convert_amount(float $amount,string $from,string $to):float{return $amount;}
function fx_ngn_per_usd():float{return 1500;}
require dirname(__DIR__).'/app/cac_registration_policy.php';
require dirname(__DIR__).'/app/cac_filing.php';

cac_assert(cac_business_quote('sole',1)===26000.0,'Sole proprietor must cost NGN 26000.');
cac_assert(cac_business_quote('partnership',2)===41000.0,'Two proprietors must cost NGN 41000.');
cac_assert(cac_business_quote('partnership',3)===56000.0,'Three proprietors must cost NGN 56000.');
cac_assert(cac_business_quote('partnership',10)===161000.0,'Ten proprietors: base + nine extras.');
foreach([['sole',2],['partnership',1],['partnership',0]] as $wrong){
    $throws=false;try{cac_business_quote($wrong[0],$wrong[1]);}catch(RuntimeException $e){$throws=true;}
    cac_assert($throws,'Invalid structure/proprietor count must not have checkout.');
}
cac_assert(cac_company_quote(1)===100000.0,'Company base price must come from published service record.');
cac_assert(cac_company_quote(2)===100000.0,'Extra director fee defaults to zero until admin specifies it.');
$input=['structure'=>'partnership','people'=>[
    ['full_name'=>'Lead Proprietor','role'=>'Director and shareholder'],
    ['full_name'=>'Other Proprietor','role'=>'Shareholder']
], 'currency'=>'NGN'];
$quote=cac_filing_quote('business',$input);
cac_assert($quote['ngn']===41000.0 && $quote['currency']==='NGN','NGN partnership quote must be calculated on server.');
$input['currency']='USD';
$quote=cac_filing_quote('business',$input);
cac_assert($quote['ngn']===41000.0 && $quote['currency']==='USD','USD quote should preserve NGN base.');
$company=cac_filing_quote('company',['people'=>[
    ['role'=>'Director and shareholder'],['role'=>'Shareholder']
], 'currency'=>'NGN']);
cac_assert($company['total']===100000.0,'Shareholder-only person must not count as an extra director.');
$root=dirname(__DIR__);
$files=['app/cac_filing.php','index.php','views/client-cac-filing.php','views/staff-cac-review.php','views/client-dashboard.php','views/client-applications.php','views/intake-submission.php','views/support.php','views/client-billing.php','views/public-home-clean.php','views/partials/public-header.php'];
foreach($files as $path){cac_assert(is_file($root.'/'.$path),'Missing file '.$path);}
$router=file_get_contents($root.'/index.php');
$wizard=file_get_contents($root.'/views/client-cac-filing.php');
$intake=file_get_contents($root.'/views/intake-submission.php');
$settings=file_get_contents($root.'/views/settings.php');
$dashboard=file_get_contents($root.'/views/client-dashboard.php');
$billing=file_get_contents($root.'/views/client-billing.php');
cac_assert(str_contains($router,"case 'cac_filing':"),'Guided CAC route missing.');
cac_assert(str_contains($router,"case 'cac_review':"),'Staff review missing.');
cac_assert(str_contains($router,"case 'cac_document':"),'Private document download missing.');
cac_assert(str_contains($router,'require_csrf();'),'All authenticated writes must have CSRF.');
cac_assert(str_contains($wizard,'data-cac-upload') && str_contains($wizard,'draw_signature'),'Passport and drawn signatures must save securely.');
cac_assert(str_contains($wizard,'Refresh document checks') && str_contains($wizard,'data-cac-stage="5"'),'User must be able to review verified document completion.');
cac_assert(str_contains($intake,'upload_intake_file'),'Old intake form must support real immediate uploads.');
cac_assert(!str_contains($intake,'style="width:100%"'),'Old upload must not show fake complete progress bars.');
$servicesAdmin=file_get_contents($root.'/views/services.php');
cac_assert(str_contains($servicesAdmin,'cac_business_base_ngn') && str_contains($servicesAdmin,'cac_business_partner_extra_ngn'),'Unified service admin pricing inputs missing.');
cac_assert(!str_contains($settings,'name="cac_business_base_ngn"') && str_contains($settings,"url('services'"),'Settings must link to one pricing editor rather than duplicate it.');
cac_assert(str_contains($dashboard,"url('cac_filing'"),'Published CAC services must start guided workflow.');
cac_assert(str_contains($billing,'billingCards'),'Billing needs saved method preview.');
cac_assert(str_contains(file_get_contents($root.'/views/partials/public-header.php'),'Insights'),'Public Insights nav must be present.');
cac_assert(str_contains(file_get_contents($root.'/views/public-home-clean.php'),'featuredInsights'),'Home insights must use published posts.');
echo "cac_launch_preparation_test passed\n";
