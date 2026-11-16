<?php
declare(strict_types=1);
/** Public catalogue + checkout regression for one SCUML service. */
require_once dirname(__DIR__).'/app/customer_service_policy.php';
$ok=static function(bool $valid,string $why):void{
    if (!$valid) throw new RuntimeException($why);
};
$canonical=[
    'id'=>901,'slug'=>'scuml-document-support','name'=>'SCUML Registration Support',
    'price'=>40000.00,'currency'=>'NGN','is_active'=>1,'is_public'=>1,
    'category'=>'compliance',
];
$oldBn=[
    'id'=>899,'slug'=>'scuml-business-name-document-support',
    'name'=>'SCUML Document Support — Business Name',
    'price'=>35000.00,'currency'=>'NGN','is_active'=>1,'is_public'=>1,
    'category'=>'compliance',
];
$oldLtd=[
    'id'=>900,'slug'=>'scuml-company-document-support',
    'name'=>'SCUML Document Support — Limited Company',
    'price'=>45000.00,'currency'=>'NGN','is_active'=>1,'is_public'=>1,
    'category'=>'compliance',
];
$visible=iworks_customer_published_catalogue([$oldBn,$oldLtd,$canonical]);
$ok(count($visible)===1 && (string)$visible[0]['slug']==='scuml-document-support',
    'Legacy Business Name and Ltd records must not create multiple public SCUML products.');
$ok((float)$visible[0]['price']===40000.00 &&
    (string)$visible[0]['currency']==='NGN',
    'Public SCUML base price is 40,000 NGN, no separate legal-structure fees.');
$ok(($visible[0]['_iworks_checkout_mode']??'')==='standard',
    'Ordinary SCUML document support must be eligible for client onboarding.');
$ok(iworks_customer_can_standard_checkout($canonical),
    'Customer can start canonical SCUML support through existing order flow.');
$ok(!iworks_customer_can_standard_checkout($oldBn)
    && !iworks_customer_can_standard_checkout($oldLtd),
    'Old split offers must not accept any new standard checkout.');
$_SERVER['HTTP_CF_IPCOUNTRY']='US';
$abroad=iworks_customer_published_service($canonical);
$ok(is_array($abroad) && ($abroad['_iworks_checkout_mode']??'')==='standard'
    && (float)$abroad['price']===40000.00
    && (string)$abroad['currency']==='NGN',
    'One canonical product must not become a fabricated foreign market price tier.');
unset($_SERVER['HTTP_CF_IPCOUNTRY']);
echo "scuml_customer_catalogue_test passed\n";
