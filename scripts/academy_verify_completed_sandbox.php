<?php
declare(strict_types=1);
// Actual Paystack sandbox API check, isolated from production records.
// Test secret is read from stdin; never print it or write it to disk.
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$root=realpath($argv[1]??'');$home=realpath((string)getenv('HOME'));
if(!$root||!$home||$root!==realpath($home.'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
final class AcademySandboxFailure extends RuntimeException {}
function sandbox_check(bool $ok,string $label):void{
    if(!$ok)throw new AcademySandboxFailure($label);
    echo "PASS ".$label."\n";
}
try{
    sandbox_check(!academy_checkout_enabled(),'Live certificate checkout remains disabled');
    $secret=trim((string)fgets(STDIN));
    if($secret==='')$secret=academy_paystack_key();
    if(!preg_match('/^sk_test_[A-Za-z0-9]+$/D',$secret))throw new AcademySandboxFailure('A Paystack TEST secret key is required. Never use a live key for this check.');
    // Verify the already completed sandbox payment only; never initialize another.
    $ref='academy-qa-ef6f60059a0ceaa6f9e16708e8107896';
    $amount=ACADEMY_FEE_MINOR;
    $verified=gateway_json_get('https://api.paystack.co/transaction/verify/'.rawurlencode($ref),$secret);
    $data=$verified['data']??[];
    sandbox_check(($data['domain']??'')==='test','Paystack confirms test-mode transaction');
    $status=(string)($data['status']??'missing');
    $safeStatus=in_array($status,['success','failed','abandoned','pending','ongoing','processing','queued','reversed'],true)?$status:'unknown';
    echo "Provider transaction status: ".$safeStatus."\n";
    echo "Reference matches: ".(($data['reference']??'')===$ref?'yes':'no')."\n";
    echo "Currency NGN: ".(($data['currency']??'')==='NGN'?'yes':'no')."\n";
    echo "Exact expected amount: ".(is_int($data['amount']??null)&&$data['amount']===$amount?'yes':'no')."\n";
    echo "Numeric transaction ID: ".(preg_match('/^[0-9]{1,80}$/D',(string)($data['id']??''))===1?'yes':'no')."\n";
    sandbox_check(academy_verified_payment_matches(['payment_reference'=>$ref,'amount_minor'=>$amount],$verified),'Actual Paystack success, exact NGN amount and reference verified with production validation logic');
    // A browser checkout may take longer than the host's MySQL idle timeout.
    // Read the two final safety checks through a new CLI-only connection.
    $cfg=app_config()['db'];
    $checkDb=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$cfg['host'],(int)($cfg['port']??3306),$cfg['name'],$cfg['charset']??'utf8mb4'),$cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
    $check=$checkDb->prepare('SELECT id FROM academy_certificates WHERE payment_reference=:ref');$check->execute(['ref'=>$ref]);
    sandbox_check($check->fetchColumn()===false,'No production certificate record created');
    $check=$checkDb->prepare('SELECT setting_value FROM app_settings WHERE setting_key=:key');$check->execute(['key'=>'academy_checkout_enabled']);
    sandbox_check((string)$check->fetchColumn()!=='1','Live checkout still disabled');
    unset($check,$checkDb,$cfg);
    echo "PASS existing successful sandbox transaction verified. No payment initialized and no certificate issued.\n";
    echo "Webhook signature handling was tested separately; actual provider delivery history is not established by this test.\n";
}catch(Throwable $e){
    if($e instanceof AcademySandboxFailure)fwrite(STDERR,"BLOCK ".$e->getMessage()."\n");
    else fwrite(STDERR,"BLOCK Paystack sandbox request or local verification failed (".get_class($e)."). Check test key and gateway dashboard.\n");
    exit(3);
}finally{unset($secret);}
