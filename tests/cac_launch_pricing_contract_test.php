<?php
declare(strict_types=1);
function setting(string $name,string $fallback=''): string { return $fallback; }
function fx_ngn_per_usd(): float { return 1500.00; }
function get_service_by_slug(string $slug): ?array { return null; }
function mb_safe_trim(string $value,int $max): string { return substr(trim($value),0,$max); }
function get_job(int $id): ?array {
    $owners=[5=>10,6=>12,7=>10];
    return isset($owners[$id])?['id'=>$id,'client_id'=>$owners[$id]]:null;
}
function assertCAC(bool $value,string $message): void { if(!$value)throw new RuntimeException($message); }
require dirname(__DIR__).'/app/cac_applications.php';
require dirname(__DIR__).'/app/support_links.php';
$price=['business_base'=>26000.0,'business_extra'=>15000.0,
    'company_base'=>90000.0,'company_director_extra'=>7000.0,
    'company_shareholder_extra'=>3000.0];
$lead=['full_name'=>'Lead','role'=>'proprietor'];
$other=['full_name'=>'Partner','role'=>'proprietor'];
$solo=cac_quote('business_name',['structure'=>'sole','people'=>[$lead]],'NGN',$price);
assertCAC($solo['total']===26000.0,'One proprietor base price must be NGN 26000.');
$partners=cac_quote('business_name',['structure'=>'partnership','people'=>[$lead,$other]],'NGN',$price);
assertCAC($partners['total']===41000.0,'One extra partner adds NGN 15000.');
$eleven=cac_quote('business_name',['structure'=>'partnership','people'=>array_fill(0,11,$other)],'NGN',$price);
assertCAC($eleven['total']===176000.0,'Ten additional partners produce NGN 176000.');
$dollar=cac_quote('business_name',['structure'=>'partnership','people'=>[$lead,$other]],'USD',$price);
assertCAC(abs($dollar['total']-27.33)<0.01,'USD quote must use the configured FX.');
$company=cac_quote('company',['people'=>[['role'=>'both'],['role'=>'director'],['role'=>'shareholder']]],'NGN',$price);
assertCAC($company['total']===100000.0,'Company pricing includes configurable per-director/shareholder fees.');
assertCAC(cac_valid_file_key('person_0_id') && cac_valid_file_key('person_10_signature'),'Individual upload keys must be valid.');
assertCAC(!cac_valid_file_key('../.env') && !cac_valid_file_key('person_1_script'),'Unsafe file keys must be blocked.');
try {
    cac_validate(['kind'=>'business_name','name1'=>'Test Ltd','activity'=>'Software',
        'business_address'=>'1 Road','business_city'=>'Lagos','business_lga'=>'Ikeja',
        'business_state'=>'Lagos','structure'=>'partnership',
        'people'=>[$lead],'declaration'=>true],[]);
    throw new RuntimeException('Partnership without second partner accepted.');
} catch (RuntimeException $e) {
    assertCAC(str_contains($e->getMessage(),'Partnership needs'),'Partnership must require a second applicant.');
}
$person=['full_name'=>'Test A','email'=>'test@example.com','phone'=>'08000000000',
    'date_of_birth'=>'1990-01-01','nationality'=>'Nigerian','occupation'=>'Developer',
    'address'=>'2 Ope Street','city'=>'Ikeja','lga'=>'Ikeja','state'=>'Lagos',
    'id_type'=>'passport','id_number'=>'ABC123456'];
$data=['kind'=>'business_name','structure'=>'sole','name1'=>'Test Technology',
    'activity'=>'Software services','business_address'=>'1 Lagos Rd','business_city'=>'Ikeja',
    'business_lga'=>'Ikeja','business_state'=>'Lagos','people'=>[$person],'declaration'=>true];
try {
    cac_validate($data,[]);
    throw new RuntimeException('Missing documents were accepted.');
} catch (RuntimeException $e) {
    assertCAC(str_contains($e->getMessage(),'Upload government ID'),'ID needs server upload record.');
}
cac_validate($data,['person_0_id','person_0_photo','person_0_signature']);
assertCAC(support_client_job_ids(10,[5,7,5])===[5,7],'Tickets can link multiple owned orders without duplicates.');
try { support_client_job_ids(10,[5,6]);throw new RuntimeException('Cross-customer support link allowed.'); }
catch (RuntimeException $e) { assertCAC(str_contains($e->getMessage(),'not in your account'),'Support cannot link another client order.'); }
$root=dirname(__DIR__);
$router=file_get_contents($root.'/index.php');
$dashboard=file_get_contents($root.'/views/client-dashboard.php');
$views=file_get_contents($root.'/views/client-cac.php');
$personView=file_get_contents($root.'/views/partials/cac-person.php');
$js=file_get_contents($root.'/assets/js/cac-application.js');
$apps=file_get_contents($root.'/views/client-applications.php');
$settings=file_get_contents($root.'/views/settings.php');
$billing=file_get_contents($root.'/views/client-billing.php');
$public=file_get_contents($root.'/views/public-home-clean.php');
$header=file_get_contents($root.'/views/partials/public-header.php');
assertCAC(str_contains($router,"case 'cac':") && str_contains($router,"case 'cac_file':"),'CAC wizard and secured files need routes.');
assertCAC(str_contains($router,'require_csrf();'),'Mutations must be CSRF-protected.');
assertCAC(str_contains($js,'result.uploaded')&&str_contains($js,'Successfully attached:'),'Upload mark must wait for server acknowledgement.');
assertCAC(str_contains($js,'canvas.toBlob')&&str_contains($personView,'data-sign-canvas'),'Drawing signatures must upload through same secure endpoint.');
assertCAC(str_contains($views,'cacPersonTemplate')&&str_contains($personView,'person_'),'Per-person details and documents must be supported.');
assertCAC(str_contains($dashboard,"url('cac',['kind'=>'business_name'])")&&str_contains($dashboard,"url('cac',['kind'=>'company'])"),'Dashboard must direct CAC products to intake-before-pay flow.');
assertCAC(str_contains($apps,"$"."item['kind']==='cac'"),'Draft deletion must include CAC applications.');
assertCAC(str_contains($settings,'save_cac_pricing'),'Administrator must set attribute pricing.');
assertCAC(str_contains($billing,'Payment methods') && !str_contains($billing,"url('card_vault') ?>\" class=\"iw-client-button"),'Saved cards should be embedded inside Billing.');
assertCAC(str_contains($header,'Insights') && str_contains($public,'iwLaunchInsightsTrack'),'Insights carousel and navigation must be present.');
echo "cac_launch_pricing_contract_test passed\n";
