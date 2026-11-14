<?php
declare(strict_types=1);
$GLOBALS['settings'] = [];
function setting(string $key,string $fallback=''): string {
    return (string)($GLOBALS['settings'][$key]??$fallback);
}
function default_currency(): string {return 'NGN';}
function fx_ngn_per_usd(): float {return 1000.0;}
function convert_amount(float $amount,string $from,string $to): float {
    return $amount;
}
function get_service_by_slug(string $slug): ?array {
    return $slug==='company-registration' ? [
        'id'=>2,'name'=>'Limited Company','slug'=>$slug,
        'price'=>45000.0,'currency'=>'NGN','tax_percent'=>7.5,
    ] : null;
}
function cac_virtual_address_eligible(): bool {return true;}
function money(float $amount,string $currency): string {
    return $currency.' '.number_format($amount,2);
}
require __DIR__.'/../app/cac_filing.php';
require __DIR__.'/../app/customer_service_policy.php';
function check_quote(bool $pass,string $message):void {
    if (!$pass) throw new RuntimeException($message);
}
$sole = cac_filing_quote('business', [
    'structure'=>'sole','people'=>[['role'=>'proprietor']],'currency'=>'NGN',
]);
check_quote($sole['total']===49000.0 && $sole['tax_percent']===0.0,
    'New Business Name orders use launch pricing and preserved configured tax policy.');
$partner=cac_filing_quote('business', [
    'structure'=>'partnership','people'=>[['role'=>'partner'],['role'=>'partner']],'currency'=>'NGN',
]);
check_quote($partner['subtotal']===64000.0 && $partner['total']===64000.0,
    'Second proprietor adds the configured NGN surcharge to the new launch base.');
$service = get_service_by_slug('company-registration');
$display = iworks_customer_product_price($service);
check_quote($display['tax_note']==='', 'Unrelated generic 7.5% CAC service tax must not be advertised.');

$GLOBALS['settings']=[
    'cac_filing_tax_percent'=>'7.5',
    'cac_company_extra_director_ngn'=>'1000',
    'cac_virtual_address_ngn'=>'10000',
];
$companyData=[
    'people'=>[['role'=>'Director'],['role'=>'Director and shareholder']],
    'address_mode'=>'virtual',
    'privacy_advisory'=>true,
    'currency'=>'NGN',
];
$company=cac_filing_quote('company',$companyData);
check_quote($company['base_ngn']===60000.0
    && $company['additional_people_ngn']===1000.0
    && $company['extras_ngn']['registered_office']===10000.0
    && $company['extras_ngn']['privacy_advisory']===100000.0
    && $company['subtotal']===171000.0
    && $company['tax_amount']===12825.0
    && $company['total']===183825.0,
    'Configured tax and add-ons must produce one total.');
$companyData['currency']='USD';
$usd=cac_filing_quote('company',$companyData);
check_quote($usd['subtotal']===286.0
    && $usd['tax_amount']===21.45
    && $usd['total']===307.45,
    'USD amount and tax must use the same invoice-currency rounding.');
$display=iworks_customer_product_price($service);
check_quote(str_contains($display['tax_note'],'7.50%'),
    'Public detail must reflect the same configured CAC tax policy.');
$source=file_get_contents(__DIR__.'/../app/cac_filing.php');
check_quote(str_contains($source,"'amount'=>" . '$quote' . "['subtotal']")
    && str_contains($source,"'tax_percent'=>" . '$quote' . "['tax_percent']"),
    'Checkout must pass the calculated subtotal and rate to the trusted invoice builder.');
echo "f13_cac_quote_consistency_test passed\n";
