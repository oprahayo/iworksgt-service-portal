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
    $tty=fopen('/dev/tty','r');
    if(!$tty)throw new AcademySandboxFailure('Interactive terminal required');
    echo "Enter an email address you control for the sandbox checkout: ";
    $email=trim((string)fgets($tty));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new AcademySandboxFailure('Invalid test email');
    // Deliberately not an IWGA reference: even a signed test event cannot match
    // a production certificate or invoice.
    echo "Existing Academy TEST transaction reference (from Paystack Test Mode dashboard), or Enter to start a new test: ";
    $existingRef=trim((string)fgets($tty));
    if($existingRef!==''&&!preg_match('/^academy-qa-[a-f0-9]{32}$/D',$existingRef))throw new AcademySandboxFailure('Enter only the reference of a previous Academy sandbox transaction.');
    $ref=$existingRef!==''?$existingRef:'academy-qa-'.bin2hex(random_bytes(16));
    $amount=ACADEMY_FEE_MINOR;
    if($existingRef===''){
    $result=gateway_json_post('https://api.paystack.co/transaction/initialize',[
        'email'=>$email,'amount'=>$amount,'currency'=>'NGN','reference'=>$ref,
        'channels'=>['card'],'metadata'=>['purpose'=>'ACADEMY SANDBOX TEST - NO CERTIFICATE']
    ],$secret);
    $url=(string)($result['data']['authorization_url']??'');
    sandbox_check(($result['status']??false)===true&&($result['data']['reference']??'')===$ref&&academy_checkout_url_valid($url),'Actual Paystack test checkout initialized');
    echo "\nTest reference: ".$ref."\nOpen the following TEST checkout URL in a browser:\n".$url."\n";
    echo "Use Paystack's official sandbox test card, not a real bank card. No money will move.\n";
    echo "Test card: 4084 0840 8408 4081; CVV: 408; any future expiry date.\n";
    echo "After completing the TEST checkout, press Enter here to verify: ";
    fgets($tty);
    }
    fclose($tty);
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
    echo "PAYSTACK SANDBOX API TEST PASSED. No real money moved and no certificate issued.\n";
    echo "Webhook signature handling was tested separately; actual provider delivery history is not established by this test.\n";
}catch(Throwable $e){
    if($e instanceof AcademySandboxFailure)fwrite(STDERR,"BLOCK ".$e->getMessage()."\n");
    else fwrite(STDERR,"BLOCK Paystack sandbox request or local verification failed (".get_class($e)."). Check test key and gateway dashboard.\n");
    exit(3);
}finally{unset($secret);}
