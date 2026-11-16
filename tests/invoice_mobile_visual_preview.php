<?php
declare(strict_types=1);
/** Isolated visual QA: uses actual invoice and payment templates with FAKE data.
 *  No DB access, no customer details. Not shipped by production publisher.
 */
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function money(float $v,string $currency='NGN'):string{return $currency.' '.number_format($v,2);}
function default_currency():string{return 'NGN';}
function url(string $page, array $params=[]):string{return 'index.php?'.http_build_query(['page'=>$page]+$params);}
function seo_description():string{return 'iWORKS invoice preview';}
function flash(string $type):?string{return null;}
function brand_primary_color():string{return '#f56613';}
function brand_primary_dark_color():string{return '#dd5c16';}
function brand_accent_color():string{return '#e96522';}
function csrf_token():string{return 'visual-test-token';}
function normalize_payment_currency(string $value):string{return $value;}
function selected_if(string $expected,string $actual):string{return $expected===$actual?'selected':'';}
function status_badge(string $s):string{return '<span class="badge">'.e(ucfirst($s)).'</span>';}
$user=['role'=>'client'];
$invoiceId=100;
$invoice=[
  'id'=>100,
  'invoice_number'=>'INV-20261116-5845',
  'client_name'=>'Example Client',
  'client_email'=>'client@example.com',
  'client_phone'=>'+234 000 000 0000',
  'job_code'=>'JOB-20261116-6F8A',
  'service_name'=>'SCUML Registration Support',
  'job_description'=>'',
  'currency'=>'NGN',
  'status'=>'paid',
  'issued_at'=>'2026-11-16 14:00:24',
  'due_at'=>'2026-11-23 14:00:24',
  'amount'=>43000,
  'subtotal_amount'=>40000,
  'tax_amount'=>3000,
  'pay_method'=>'card'
];
$invoiceJobs=[$invoice];
$paymentHistory=[
  [
    'id'=>1,'method_name'=>'Paystack',
    'reference'=>'PAYSTACK-20261116140301-010C4A',
    'amount'=>43000,'status'=>'confirmed',
    'created_at'=>'2026-11-16 14:03:02','proof_path'=>null,
  ],
  [
    'id'=>2,'method_name'=>'Bank Transfer',
    'reference'=>'NXG000014261116132800251733442043',
    'amount'=>43000,'status'=>'submitted',
    'created_at'=>'2026-11-16 14:02:06','proof_path'=>null,
  ],
];
$methods=[];$hasMethods=false;$hasCardLikeMethods=false;$hasCryptoMethods=false;
$payModePref='card';$cryptoAssetPref='USDT';$cryptoNetworkPref='TRC20';
$cryptoQuote=[];$logo='/assets/branding/iworks-desktop.svg';$favicon='';

if(($_GET['mode']??'invoice')==='payments'){
  $paymentFilters=[];$paymentPagination=['page'=>1,'per_page'=>10,'total'=>1,
    'total_pages'=>1,'from'=>1,'to'=>1];
  $paymentMethodOptions=[];
  $payments=[[
    'id'=>1,'invoice_number'=>'INV-20261116-5845','job_code'=>'JOB-20261116-6F8A',
    'client_name'=>'Example Client','method_name'=>'Paystack',
    'reference'=>'PAYSTACK-20261116140301-010C4A','amount'=>43000,
    'invoice_currency'=>'NGN','status'=>'confirmed','proof_path'=>null,
    'created_at'=>'2026-11-16 14:03:02','reviewer_name'=>'iWORKS SUPPORT',
    'note'=>'Gateway checkout initiated.',
  ]];
  ?>
  <!doctype html><html lang="en"><head><meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payments — visual QA</title><base href="/">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/payment-review-mobile.css">
  <style>
    body{margin:0;padding:20px;background:#f5f6f9;font:14px Inter,Arial,sans-serif}
    .iw-payments-page{max-width:1100px;margin:0 auto}
    .card{border:1px solid #e0e5ea;border-radius:12px;background:white}
    .table{width:100%}.table td,.table th{padding:10px}
    .form-select,.form-control{display:block;width:100%;padding:8px;border:1px solid #cbd4df;border-radius:6px}
    .btn{padding:8px 12px;border:1px solid #d0d9e0;border-radius:7px}
    .mobile-filter-panel{display:none}.mobile-filter-toggle{display:none}
  </style></head><body>
  <?php require dirname(__DIR__).'/views/payments.php'; ?>
  </body></html>
  <?php
  exit;
}
$source=(string)file_get_contents(dirname(__DIR__).'/invoice.php');
$markup=strpos($source,'<!doctype html>');
if($markup===false){http_response_code(500);exit('Invoice markup missing');}
$source=substr($source,$markup);
$source=str_replace('<head>','<head><base href="/">',$source);
eval('?>'.$source);
