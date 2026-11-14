<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require $root.'/app/helpers.php';
require $root.'/app/launch_packages.php';
require $root.'/app/customer_service_policy.php';
function launch_check(bool $ok, string $why):void { if (!$ok) throw new RuntimeException($why); }
launch_check(iworks_client_country_currency(['role'=>'client','country'=>'Nigeria'])==='NGN','Domestic country must use NGN.');
foreach(['United Kingdom','United States','Canada','Germany','Australia'] as $country) {
    launch_check(iworks_client_country_currency(['role'=>'client','country'=>$country])==='USD',
      'Diaspora country must use USD: '.$country);
}
launch_check(iworks_client_country_currency(['role'=>'admin','country'=>'Nigeria'])==='','Staff are not locked to a client market.');
launch_check(iworks_enforce_client_currency('NGN',['role'=>'client','country'=>'Nigeria'])==='NGN','Domestic checkout allowed.');
launch_check(iworks_enforce_client_currency('USD',['role'=>'client','country'=>'Canada'])==='USD','Diaspora checkout allowed.');
foreach([['NGN','Germany'],['USD','Nigeria']] as [$currency,$country]) {
    try {
        iworks_enforce_client_currency($currency,['role'=>'client','country'=>$country]);
        throw new RuntimeException('Region/currency mismatch not blocked: '.$currency.' / '.$country);
    } catch (RuntimeException $error) {
        launch_check(str_contains($error->getMessage(),'must use') ,'Regional mismatch did not fail safely.');
    }
}
$ngn = iworks_website_package_options('NGN');
$usd = iworks_website_package_options('USD');
launch_check(count($ngn)===4 && count($usd)===4,'Four website package choices required for both markets.');
launch_check($ngn[0]['price']===180000 && $ngn[1]['price']===350000
    && $ngn[2]['price']===750000 && $ngn[3]['type']==='custom',
    'Nigeria website packages must follow approved indicative pricing.');
launch_check($usd[0]['price']===320 && $usd[1]['price']===650
    && $usd[2]['price']===1400 && $usd[3]['type']==='custom',
    'Diaspora website prices must be independent published amounts, not currency conversion.');
launch_check(iworks_international_standard_rate('seo-smm-digital-marketing')===450.0
    && iworks_international_standard_rate('domain-web-hosting')===180.0,
    'Overseas ordinary services need independent rates.');
launch_check(iworks_international_standard_rate('unknown-product')===null,
    'Unconfigured overseas fees must fail closed to quotation.');
$web = iworks_customer_published_service([
    'id'=>1,'slug'=>'website-design-development','name'=>'Website Design','price'=>250000,
    'currency'=>'NGN','is_active'=>1,'is_public'=>1,
]);
launch_check(($web['_iworks_checkout_mode']??'')==='consultation'
    && !iworks_customer_can_standard_checkout($web),
    'Website design must never be instantly invoiced with a generic fee.');
$_SERVER['HTTP_CF_IPCOUNTRY']='GB';
$seo = iworks_customer_published_service([
    'slug'=>'seo-smm-digital-marketing','name'=>'SEO Marketing','price'=>150000,
    'currency'=>'NGN','is_active'=>1,'is_public'=>1,
]);
launch_check((float)($seo['price']??0)===450.0 && ($seo['currency']??'')==='USD',
    'Overseas product catalogue must show independent USD tariff.');
$_SERVER['HTTP_CF_IPCOUNTRY']='NG';
$seoHome=iworks_customer_published_service([
    'slug'=>'seo-smm-digital-marketing','name'=>'SEO Marketing','price'=>150000,
    'currency'=>'NGN','is_active'=>1,'is_public'=>1,
]);
launch_check((float)($seoHome['price']??0)===150000.0 && ($seoHome['currency']??'')==='NGN',
    'Domestic product catalogue must preserve admin NGN price.');
$source = static fn(string $p):string => (string)file_get_contents($root.'/'.$p);
$detail = $source('views/public-service-detail.php');
$checkout = $source('views/client-service-checkout.php');
$chooser = $source('views/client-cac-pathway.php');
$wizard = $source('views/client-cac-filing.php');
$region = $source('app/cac_filing.php');
$domain = $source('app/domain.php');
$router = $source('index.php');
$profile = $source('views/client-profile.php');
foreach(['iwWebsitePackageRequest','website_package','iw-website-launch-grid','Request a project quote','iwSpecialistQuoteForm'] as $part) {
    launch_check(str_contains($detail,$part),'Self-service website/custom quote flow missing: '.$part);
}
launch_check(str_contains($router,'$validPackages')
    && str_contains($router,'array_key_exists($leadPackage'),
    'Package values need strict server-side allowlist validation.');
launch_check(str_contains($domain,"notify_roles(") && str_contains($domain,"['admin', 'agent']"),
    'Website enquiries must notify active agents and admins.');
launch_check(str_contains($router,"['admin', 'agent', 'finance']")
    && str_contains($router,'Order received:')
    && str_contains($router,'create_notification('),
    'New orders need customer receipt and admin/agent alerts.');
launch_check(str_contains($region,'New CAC registration order')
    && str_contains($region,'CAC registration order received'),
    'Guided CAC orders must enqueue customer and staff notifications.');
launch_check(str_contains($region,'cac_company_growth_ngn')
    && str_contains($region,'diaspora_cac_service_multiplier')
    && str_contains($region,'$shareCapital'),
    'Growth capital tier and diaspora CAC fee must be calculated server-side.');
foreach(['OPTION 1','OPTION 2','OPTION 3','OPTION 4','incorporated-trustees','tier\'=>\'growth'] as $token) {
    launch_check(str_contains($chooser,$token),'Missing one of four registration routes: '.$token);
}
launch_check(str_contains($wizard,'$clientCurrency') && !str_contains($wizard,'<select name="filing[currency]"'),
    'CAC applicants must not change invoice market with a select box.');
launch_check(!str_contains($checkout,'<select name="pay_currency"')
    && str_contains($checkout,'name="pay_currency"')
    && str_contains($checkout,'Privacy Policy'),
    'Standard checkout must lock currency and show legal notice.');
launch_check(!str_contains($profile,'<select name="pay_currency"')
    && str_contains($profile,'Billing currency'),
    'Clients must not change currency preferences in profile.');
launch_check(str_contains($router,'changing your country of residence requires support verification')
    && str_contains($domain,'iworks_enforce_client_currency($currency, $actor)'),
    'Server must prevent manual country/currency arbitrage.');
echo "launch_product_order_contract_test passed\n";
