<?php
declare(strict_types=1);
function setting(string $key, string $default=''): string { return $default; }
function tm_test(bool $ok,string $message): void { if (!$ok) { throw new RuntimeException($message); } }
require dirname(__DIR__) . '/app/trademark_applications.php';

tm_test(trademark_price_usd() === 185.0, 'Trademark price must default to USD 185/class.');
tm_test(count(trademark_class_labels()) === 45, 'Nice Classification should expose all 45 classes.');
tm_test(trademark_class_labels()[35] === 'Advertising and business', 'Business service classification should exist.');
$classes = trademark_normalize_classes(['1','1','5','13','45','46','0','-2','abc',[], '39']);
tm_test($classes === [1,5,13,39,45], 'Class input must be unique and validated to 1–45.');
$one = trademark_quote(['35'],'USD',185,1500);
tm_test($one['count'] === 1 && $one['usd_total'] === 185.0 && $one['total'] === 185.0, 'One class should cost USD 185.');
$three = trademark_quote(['9','35','42'],'USD',185,1500);
tm_test($three['count'] === 3 && $three['total'] === 555.0, 'Three classes should cost USD 555.');
$naira = trademark_quote(['9','35','42'],'NGN',185,1500);
tm_test($naira['count'] === 3 && $naira['usd_total'] === 555.0 && $naira['total'] === 832500.0, 'NGN must use the configured FX rate.');
$changed = trademark_quote(['9','35'],'USD',225,1490);
tm_test($changed['total'] === 450.0, 'Admin-adjusted unit price must be respected.');
try { trademark_quote(['35'],'EUR',185,1500); throw new RuntimeException('EUR accepted'); }
catch (RuntimeException $e) { tm_test(str_contains($e->getMessage(),'USD or NGN'), 'Invalid payment currency should fail.'); }
try { trademark_quote(['35'],'NGN',185,0); throw new RuntimeException('Missing FX accepted'); }
catch (RuntimeException $e) { tm_test(str_contains($e->getMessage(),'exchange rate'), 'Zero FX must fail.'); }
$data = trademark_form_values([
    'mark_name'=>'<script>alert(1)</script> Test Mark',
    'mark_type'=>'logo',
    'classes'=>['5','9'],
    'owner_name'=>'Sample Applicant','owner_email'=>'person@example.com',
    'owner_phone'=>'+2348012345678','owner_address'=>'Sample Street, Nigeria',
    'currency'=>'NGN',
]);
tm_test(!str_contains($data['mark_name'],'<script>'), 'Mark names should not preserve HTML tags.');
tm_test($data['country'] === 'Nigeria', 'Only Nigerian trademark filings are supported in first release.');
try { trademark_validate_complete($data,false); throw new RuntimeException('Logo was not required.'); }
catch (RuntimeException $e) { tm_test(str_contains($e->getMessage(),'Upload'), 'Logo marks need an uploaded image.'); }
trademark_validate_complete($data,true);
$data['classes'] = [];
try { trademark_validate_complete($data,true); throw new RuntimeException('No class accepted.'); }
catch (RuntimeException $e) { tm_test(str_contains($e->getMessage(),'class'), 'Class selection must be required.'); }

$root = dirname(__DIR__);
$router = file_get_contents($root . '/index.php');
$client = file_get_contents($root . '/views/client-trademark.php');
$dashboard = file_get_contents($root . '/views/client-dashboard.php');
$header = file_get_contents($root . '/views/partials/header.php');
$payment = file_get_contents($root . '/invoice.php');
tm_test(str_contains($router,"case 'trademark':") && str_contains($router,"case 'applications':"), 'Customer application routes missing.');
tm_test(str_contains($router,"case 'trademark_file':"), 'Private logo serving route missing.');
tm_test(str_contains($client,"submit_for_payment") && str_contains($client,"review_draft") && str_contains($client,"save_draft"), 'Missing save, review and checkout actions.');
tm_test(str_contains($client,"name=\"classes[]\""), 'Class selection should post to server.');
tm_test(str_contains($dashboard,"url('trademark')"), 'Dashboard must offer direct trademark start.');
tm_test(str_contains($header,"client-header.php"), 'Customer must receive separate clean shell.');
tm_test(str_contains($payment,"start_gateway_checkout"), 'Invoice should use existing secure gateways.');
tm_test(str_contains(file_get_contents($root . '/app/order_intent.php'),"return url('trademark');"), 'Google sign-in intent should route trademark buyers to wizard.');
echo "trademark_pricing_test passed\n";
