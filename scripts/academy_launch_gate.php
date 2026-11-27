<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit(2);}
$home=realpath((string)getenv('HOME'));
$root=realpath($argv[1]??'');
if(!$home||!$root||$root!==realpath($home.'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
function launch_check(bool $ok,string $label):void {
    if(!$ok)throw new RuntimeException($label);
    echo "PASS ".$label."\n";
}
try {
    $mode=$argv[2]??'check';
    if(!in_array($mode,['check','enable','status'],true))exit(2);
    if($mode==='status'){
        echo academy_checkout_enabled()?"LIVE CERTIFICATE CHECKOUT ENABLED\n":"LIVE CERTIFICATE CHECKOUT DISABLED\n";
        exit(academy_checkout_enabled()?0:3);
    }
    launch_check(ACADEMY_FEE_MINOR===10200000,'Certificate fee is NGN 102,000');
    launch_check(!academy_checkout_enabled(),'Live checkout remains disabled before activation');
    $live=academy_live_paystack_key();
    $r=gateway_json_get('https://api.paystack.co/integration/payment_session_timeout',$live);
    unset($live);
    launch_check(($r['status']??false)===true,'Live Paystack API authenticated without charge');
    launch_check(email_enabled(),'Certificate mail enabled');
    launch_check(iworks_mail_envelope_parameters(trim(setting('mail_from_email','no-reply@iworksgt.com')))!==null,'Mail sender configuration valid');
    launch_check(function_exists('academy_reconcile_pending')&&function_exists('academy_deliver_email'),'Payment recovery and email delivery installed');
    $p=dirname(mail_log_path()).'/cron-queue-status.json';
    $m=is_file($p)?json_decode((string)file_get_contents($p),true):null;
    launch_check(is_array($m)&&($m['status']??'')==='ok'&&strtotime((string)($m['last_success_at']??''))>=time()-1800&&array_key_exists('academy_email_processed',$m)&&array_key_exists('academy_payments_checked',$m)&&isset($m['academy_payment_errors'])&&(int)$m['academy_payment_errors']===0,'Recent successful Academy payment and mail worker');
    if($mode==='check'){
        foreach(['orange','purple','green','blue'] as $colour){
            $pdf=academy_pdf(['colour'=>$colour,'certificate_number'=>'TEST ONLY - NOT VALID','student_name'=>'ACADEMY TEST STUDENT','course_title'=>'TEST ONLY - NOT A QUALIFICATION','duration'=>'12 weeks','issued_at'=>now()]);
            launch_check(str_starts_with($pdf,'%PDF-')&&strlen($pdf)>1000,'PDF rendering '.$colour);
        }
        echo "PASS launch preflight; no settings changed\n";
    }else{
        // Called by the pinned CLI launch wrapper only after its other gates.
        set_setting('academy_checkout_enabled','1');
        launch_check(academy_checkout_enabled(),'Live certificate checkout enabled');
        echo "No payment initialized. Only approved students can initiate checkout.\n";
    }
}catch(Throwable $e){
    $safe=['Certificate fee is NGN 102,000','Live checkout remains disabled before activation','Live Paystack API authenticated without charge','Certificate mail enabled','Mail sender configuration valid','Payment recovery and email delivery installed','Recent successful Academy payment and mail worker','PDF rendering orange','PDF rendering purple','PDF rendering green','PDF rendering blue','Live certificate checkout enabled'];
    fwrite(STDERR,"BLOCK ".(in_array($e->getMessage(),$safe,true)?$e->getMessage():'Launch check failed ('.get_class($e).')')."\n");
    exit(3);
}
