<?php
declare(strict_types=1);
require __DIR__.'/../app/gateway_verification.php';
require __DIR__.'/../app/agent_withdrawals.php';
function security_check(bool $ok,string $label): void { if(!$ok) {throw new RuntimeException($label);} }
$raw='{"event":"transfer.success","data":{"amount":500000}}';
$api='sk_test_synthetic';$secret='whsec_synthetic';$time=time();
$legacy=hash_hmac('sha512',$raw,$api);
security_check(iworks_paystack_signature_valid($raw,['X-Paystack-Signature'=>$legacy],$api),'Valid legacy signature');
security_check(!iworks_paystack_signature_valid($raw.' ',['x-paystack-signature'=>$legacy],$api),'Raw-body tamper accepted');
security_check(!iworks_paystack_signature_valid($raw,[],$api),'Unsigned event accepted');
$sign=fn($t)=>'t='.$t.',v1=whk_fixture:'.hash_hmac('sha512',$t.'.'.$raw,$secret);
security_check(iworks_paystack_signature_valid($raw,['Paystack-Signature'=>$sign($time)],$api,$secret),'Timestamped signature rejected');
security_check(!iworks_paystack_signature_valid($raw,['Paystack-Signature'=>$sign($time-301)],$api,$secret),'Expired signed event accepted');
security_check(!iworks_paystack_signature_valid($raw,['Paystack-Signature'=>$sign($time+301)],$api,$secret),'Future signed event accepted');
security_check(!iworks_paystack_signature_valid($raw,['x-paystack-signature'=>$legacy],$api,$secret),'Signing-secret downgrade accepted');
security_check(!iworks_paystack_signature_valid($raw,['Paystack-Signature'=>'t='.$time.',t='.$time.',v1=whk_fixture:'.$legacy],$api,$secret),'Ambiguous timestamp accepted');
security_check(iworks_paystack_signature_valid($raw,['Paystack-Signature'=>$sign($time).',v1=whk_old:'.str_repeat('0',128)],$api,$secret),'Key rotation overlap rejected');
$row=['reference'=>'iwagent_fixture','amount_kobo'=>500000,'recipient_code'=>'RCP_fixture','environment'=>'test',
    'provider_transfer_id'=>'123','bank_code'=>'058','account_last4'=>'1234'];
$data=['reference'=>'iwagent_fixture','amount'=>500000,'recipient'=>['recipient_code'=>'RCP_fixture',
    'details'=>['bank_code'=>'058','account_number'=>'0000001234']],'domain'=>'test','id'=>123,'currency'=>'NGN'];
security_check(iworks_agent_transfer_matches($row,$data),'Exact settlement rejected');
$bad=$data;$bad['amount']=500000.9;
security_check(!iworks_agent_transfer_matches($row,$bad),'Fractional kobo truncated');
$bad=$data;$bad['recipient']['details']['bank_code']='011';
security_check(!iworks_agent_transfer_matches($row,$bad),'Wrong bank accepted');
$bad=$data;$bad['recipient']['details']['account_number']='0000009999';
security_check(!iworks_agent_transfer_matches($row,$bad),'Wrong account accepted');
echo "Paystack raw-body signatures, replay window, rotation, downgrade and transfer-identity checks PASS.\n";
