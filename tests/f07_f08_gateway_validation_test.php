<?php
declare(strict_types=1);
// Test the actual shared validation and signed webhook handlers offline.
require __DIR__ . '/../app/gateway_verification.php';
function check_gateway(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$payment=[
    'id'=>11,'invoice_id'=>22,'client_id'=>33,'job_id'=>44,
    'gateway_provider'=>'paystack','reference'=>'GW-unique-200',
    'pay_currency'=>'NGN','amount'=>'4000.50',
    'gateway_session_id'=>null,
];
$paystack=[
    'status'=>true,'data'=>[
        'status'=>'success','reference'=>'GW-unique-200','amount'=>400050,
        'currency'=>'NGN','id'=>90811,
        'metadata'=>['invoice_id'=>22,'client_id'=>33,'job_id'=>44],
    ],
];
check_gateway(iworks_gateway_verified_matches('paystack',$payment,$paystack),'Matching Paystack payment must pass.');
foreach([
    ['amount',400049],['currency','USD'],['reference','fake-ref'],['status','failed']
] as [$key,$value]){
    $bad=$paystack;$bad['data'][$key]=$value;
    check_gateway(!iworks_gateway_verified_matches('paystack',$payment,$bad),
        'Paystack mismatch accepted: '.$key);
}
$bad=$paystack;$bad['data']['metadata']['client_id']=99;
check_gateway(!iworks_gateway_verified_matches('paystack',$payment,$bad),'Paystack account mismatch must fail.');
$bad=$paystack;$bad['data']['amount']=null;
check_gateway(!iworks_gateway_verified_matches('paystack',$payment,$bad),'Missing amount must fail.');
$flw=$payment;$flw['gateway_provider']='flutterwave';
$flutterwave=[
    'status'=>'success','data'=>[
        'status'=>'successful','tx_ref'=>'GW-unique-200','id'=>5561,
        'currency'=>'NGN','amount'=>4000.50,
        'meta'=>['invoice_id'=>22,'client_id'=>33,'job_id'=>44],
    ],
];
check_gateway(iworks_gateway_verified_matches('flutterwave',$flw,$flutterwave),
    'Matching Flutterwave verification must pass.');
foreach([['amount',4000.49],['currency','USD'],['tx_ref','other'],['status','failed']] as [$key,$value]){
    $bad=$flutterwave;$bad['data'][$key]=$value;
    check_gateway(!iworks_gateway_verified_matches('flutterwave',$flw,$bad),
        'Flutterwave mismatch accepted: '.$key);
}
$stripe=$payment;$stripe['gateway_provider']='stripe';$stripe['gateway_session_id']='cs_test_abc';
$session=[
    'id'=>'cs_test_abc','mode'=>'payment','status'=>'complete',
    'payment_status'=>'paid','client_reference_id'=>'GW-unique-200',
    'amount_total'=>400050,'currency'=>'ngn',
];
check_gateway(iworks_gateway_verified_matches('stripe',$stripe,$session),
    'Matching Stripe session must pass.');
foreach([['amount_total',400049],['currency','usd'],['client_reference_id','wrong'],
    ['id','cs_different'],['payment_status','unpaid']] as [$key,$value]){
    $bad=$session;$bad[$key]=$value;
    check_gateway(!iworks_gateway_verified_matches('stripe',$stripe,$bad),
        'Stripe mismatch accepted: '.$key);
}

$GLOBALS['method']=['is_active'=>1,'secret_key'=>'synthetic-secret','private_key'=>''];
function db_one(string $sql,array $params=[]): ?array
{
    if (str_contains($sql,'FROM payment_methods')) return $GLOBALS['method'];
    return null;
}
require __DIR__ . '/../app/domain.php';
$ignored='{"event":"ignored.event","type":"ignored.event","data":{}}';
$noFlw=handle_gateway_webhook('flutterwave',$ignored,[]);
$noStripe=handle_gateway_webhook('stripe',$ignored,[]);
check_gateway($noFlw['status_code']===503 && $noStripe['status_code']===503,
    'Missing webhook signing keys must block event processing.');
$GLOBALS['method']['private_key']='synthetic-webhook-signature';
$badFlw=handle_gateway_webhook('flutterwave',$ignored,['verif-hash'=>'bad']);
$goodFlw=handle_gateway_webhook('flutterwave',$ignored,['verif-hash'=>'synthetic-webhook-signature']);
check_gateway($badFlw['status_code']===403 && $goodFlw['status_code']===200,
    'Flutterwave v3 signature must be verified before ignoring unrelated events.');
$ts=time();
$signature=hash_hmac('sha256',$ts.'.'.$ignored,'synthetic-webhook-signature');
$goodStripe=handle_gateway_webhook('stripe',$ignored,['Stripe-Signature'=>"t=$ts,v1=$signature"]);
$badStripe=handle_gateway_webhook('stripe',$ignored,['Stripe-Signature'=>"t=".($ts-600).",v1=$signature"]);
check_gateway($goodStripe['status_code']===200 && $badStripe['status_code']===403,
    'Stripe signature and replay timestamp validation must both pass.');
echo "f07_f08_gateway_validation_test passed\n";
