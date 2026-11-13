<?php
declare(strict_types=1);

/** Pure-PHP CAC intake guard tests; no personal details or database needed. */
$settings = [];
function setting(string $key,string $fallback=''): string {
    global $settings;
    return array_key_exists($key,$settings) ? (string)$settings[$key] : $fallback;
}
function mb_safe_trim(string $text,int $max=190):string { return substr($text,0,$max); }
function get_service_by_slug(string $slug):?array {
    return $slug === 'company-registration' ? ['id'=>6,'slug'=>$slug,'price'=>100000,'currency'=>'NGN'] : null;
}
function convert_amount(float $amount,string $from,string $to):float {return $amount;}
function fx_ngn_per_usd():float{return 1600;}
function check_cac(bool $result,string $description):void {
    if(!$result) throw new RuntimeException($description);
}
function fails_cac(callable $fn,string $description):void {
    try {$fn();}catch(Throwable $e){return;}
    throw new RuntimeException($description);
}
require dirname(__DIR__).'/app/cac_registration_policy.php';
require dirname(__DIR__).'/app/cac_filing.php';

check_cac(cac_validate_id_number('NIN','12345678901')===null,'11-digit NIN should pass format screen.');
check_cac(cac_validate_id_number('NIN','1234567890')!==null,'10 digits must fail NIN format check.');
check_cac(cac_validate_id_number('NIN','1234567890A')!==null,'Letters must fail NIN format.');
check_cac(cac_validate_id_number('International passport','A12345678')===null,'Alphanumeric passport number should pass.');
check_cac(cac_validate_id_number('Driver licence','ABC1234567')===null,'Alphanumeric driver licence should pass.');
check_cac(cac_validate_id_number('Voter card','123456789ABC')===null,'Voter ID should allow verified-card numbers.');
check_cac(cac_validate_id_number('Bank Verification Number','12345678901')!==null,'Unsupported ID type must fail.');
check_cac(cac_name_review('Acme Ltd','business')['errors'] !== [], 'Ltd cannot be filed as a Business Name.');
check_cac(cac_name_review('Acme LLC','company')['errors'] !== [], 'US LLC suffix should not be used for a Nigerian Ltd.');
check_cac(cac_name_review('Acme Technologies Limited','company')['errors'] === [], 'Valid private Ltd name should pass basic format screen.');
check_cac(cac_name_review('National Savings Bank Ltd','company')['warnings'] !== [], 'Possibly restricted wording needs manual CAC review.');
check_cac(count(cac_registration_natures()) >= 25, 'Industry/profession picker should cover major sectors.');
check_cac(cac_public_service_record(['slug'=>'business-name-registration-cac-nigeria'])===null,
    'Duplicate Business Name public listing must be hidden.');
$unified = cac_public_service_record(['slug'=>'company-registration','name'=>'Company Registration','price'=>100000]);
check_cac($unified['name']==='Business Registration' && $unified['price']===26000.0,
    'Canonical public product should start at Business Name base price.');

$base = ['people'=>[['role'=>'Director and shareholder']], 'currency'=>'NGN','address_mode'=>'own'];
check_cac(cac_filing_quote('company',$base)['ngn']===100000.0,'Company base must come from canonical service record.');
$base['privacy_advisory']=1;
check_cac(cac_filing_quote('company',$base)['ngn']===150000.0,'Director advisory adds configured NGN 50,000 for one director.');
$base['people'][]=['role'=>'Director'];
check_cac(cac_filing_quote('company',$base)['ngn']===200000.0,'Privacy advisory is per director, not per shareholder.');
fails_cac(static fn()=>cac_filing_quote('business',[
    'people'=>[['role'=>'Proprietor']],'structure'=>'sole','privacy_advisory'=>1
]),'Company-only advisory cannot be sold on Business Name.');
fails_cac(static fn()=>cac_filing_quote('company',[
    'people'=>[['role'=>'Director']],'address_mode'=>'virtual'
]),'Unconfigured managed office must be rejected.');
$settings = [
    'cac_virtual_address_enabled'=>'1',
    'cac_virtual_address_details'=>'12 Market Road, Ado-Ekiti, Ekiti State, Nigeria',
    'cac_virtual_address_ngn'=>'30000',
];
$quote = cac_filing_quote('business',[
    'people'=>[['role'=>'Proprietor']],'structure'=>'sole','currency'=>'NGN','address_mode'=>'virtual',
]);
check_cac($quote['ngn']===56000.0,'Managed office fee must be added to Business Name quote on server.');
check_cac(cac_registered_office('virtual','client-supplied address')===$settings['cac_virtual_address_details'],
    'Virtual address must be administrator-owned, never taken from client input.');

$root=dirname(__DIR__);
$router=(string)file_get_contents($root.'/index.php');
$editor=(string)file_get_contents($root.'/views/services.php');
$settingsHtml=(string)file_get_contents($root.'/views/settings.php');
$client=(string)file_get_contents($root.'/views/client-cac-filing.php');
$pathway=(string)file_get_contents($root.'/views/client-cac-pathway.php');
$index=(string)file_get_contents($root.'/app/domain.php');
check_cac(str_contains($router,"if ($"."serviceAction === 'save_cac_pricing')"),'Services route needs centralized price action.');
check_cac(str_contains($editor,'cac_company_base_ngn') && str_contains($editor,'cac_business_base_ngn'),
    'One admin form must contain both legal-pathway prices.');
check_cac(!str_contains($settingsHtml,'name="cac_business_base_ngn"'),
    'There must be no competing CAC price editor in settings.');
check_cac(str_contains($client,'cacNameSuggestions') && str_contains($client,'cacNature') &&
    str_contains($client,'cacAddressMode') && str_contains($client,'data-cac-id-type'),
    'CAC form missing candidate screening, profession, managed office or per-ID controls.');
check_cac(str_contains($pathway,"['type'=>'business']") && str_contains($pathway,"['type'=>'company']"),
    'One product must route to each registration pathway.');
check_cac(str_contains($index,'function list_public_services_catalog') && str_contains($index,'cac_public_service_record'),
    'Public service catalogue must use canonical CAC product mapping.');
check_cac(str_contains((string)file_get_contents($root.'/app/cac_filing.php'),"$"."slug='company-registration'"),
    'All new CAC purchases must reference the canonical service ID.');
foreach(['views/partials/header.php','views/partials/public-header.php','views/partials/client-header.php'] as $header) {
    check_cac(str_contains((string)file_get_contents($root.'/'.$header),'favicon_mime_type('),
        'Favicon missing from '.$header);
}
check_cac(str_contains((string)file_get_contents($root.'/app/helpers.php'),'data:image/svg+xml'),
    'Fallback first-party icon must be available even if no asset uploaded.');
check_cac(str_contains($router,"if ($"."page === 'brand_icon')") &&
    str_contains($router,"'image/webp'") &&
    str_contains($router,"str_starts_with($"."file,$"."brandRoot.DIRECTORY_SEPARATOR)"),
    'Uploaded favicons must use a restricted public route with MIME and path checks.');
echo "cac_unified_product_contract_test passed\n";
