<?php
declare(strict_types=1);
// Actual HTTPS registration and email OTP check. Only creates one labelled QA account.
// Never initializes a payment, changes gateway settings or issues a certificate.
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$root=realpath($argv[1]??'');$home=realpath((string)getenv('HOME'));
if(!$root||!$home||$root!==realpath($home.'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
final class AcademyQaFailure extends RuntimeException {}
function qa_assert(bool $ok,string $label):void{
    if(!$ok)throw new AcademyQaFailure($label);
    echo "PASS ".$label."\n";
}
function qa_hidden(string $html,string $name):string{
    preg_match('~<input\b[^>]*\bname="'.preg_quote($name,'~').'"[^>]*\bvalue="([^"]*)"~i',$html,$m);
    if(!isset($m[1])||$m[1]==='')throw new AcademyQaFailure('Expected form token missing: '.$name);
    return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
}
function qa_http($ch,string $path,?array $fields=null):array{
    for($i=0;$i<6;$i++){
        if(!str_starts_with($path,'/')||str_starts_with($path,'//'))throw new AcademyQaFailure('Unexpected request path');
        $location='';
        curl_setopt_array($ch,[CURLOPT_URL=>'https://portal.iworksgt.com'.$path,CURLOPT_HTTPGET=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_TIMEOUT=>40,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_HEADERFUNCTION=>static function($c,$line)use(&$location){if(stripos($line,'Location:')===0)$location=trim(substr($line,9));return strlen($line);}]);
        if($fields!==null){
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($fields));
        }
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if(!is_string($body))throw new AcademyQaFailure('HTTPS request failed');
        if(!in_array($status,[301,302,303],true)||$location==='')return [$status,$body];
        $fields=null;
        if(str_starts_with($location,'https://portal.iworksgt.com/'))$path=substr($location,strlen('https://portal.iworksgt.com'));
        elseif(str_starts_with($location,'/')&&!str_starts_with($location,'//'))$path=$location;
        elseif(preg_match('~^index\.php(?:\?.*)?$~D',$location))$path='/'.$location;
        else throw new AcademyQaFailure('Unexpected redirect; no credentials forwarded');
    }
    throw new AcademyQaFailure('Too many redirects');
}
$ch=null;$email='';$password='';$attempted=false;$stage='initialization';$tag=bin2hex(random_bytes(5));$name='ACADEMY QA TEST '.$tag;
try{
    qa_assert(!academy_checkout_enabled(),'Live certificate checkout disabled');
    $secret=academy_paystack_key();
    $mode=str_starts_with($secret,'sk_test_')?'TEST':(str_starts_with($secret,'sk_live_')?'LIVE':'UNKNOWN');
    unset($secret);
    echo "Paystack configured key mode: ".$mode." (key not displayed; no gateway request made)\n";
    // Use a fresh forwarding alias, not the existing administrator's address.
    $email='academyqa-'.$tag.'@iworkscac.33mail.com';
    echo "Proposed fresh test alias: ".$email."\n";
    echo "Press Enter to use it if you control this forwarding mailbox, or type a different unused address: ";
    $input=trim((string)fgets(STDIN));
    if($input!=='')$email=strtolower($input);
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new AcademyQaFailure('Invalid test email');
    $stage='existing-email lookup';
    // Compare bytes to avoid an incompatible connection/column collation.
    // Also run the usual lookup so the deployed registration cannot fail later.
    $existing=db_one('SELECT id FROM users WHERE BINARY LOWER(email)=BINARY :email LIMIT 1',['email'=>$email]);
    if($existing!==null||find_user_by_email($email)!==null)throw new AcademyQaFailure('Address already belongs to a portal account. Use the fresh alias instead.');
    echo "PASS fresh student email\n";
    $password=bin2hex(random_bytes(24));
    // Name validation allows only letters, so convert random hex digits to letters.
    $name='ACADEMY QA TEST '.strtr(strtoupper($tag),'0123456789','GHIJKLMNOP');
    $stage='registration page';
    $ch=curl_init();curl_setopt($ch,CURLOPT_COOKIEFILE,'');
    [$status,$html]=qa_http($ch,'/student/register');
    qa_assert($status===200,'Real registration page available');
    $csrf=qa_hidden($html,'csrf_token');
    $stage='registration form submission';
    $attempted=true;
    [$status,$html]=qa_http($ch,'/academy.php?action=register',['csrf_token'=>$csrf,'name'=>$name,'email'=>$email,'phone'=>'00000000000','password'=>$password]);
    $stage='registered account lookup';
    $user=find_user_by_email($email);
    qa_assert($user!==null&&$user['role']==='student'&&$user['full_name']===$name&&password_verify($password,$user['password_hash']),'Real HTTP student registration');
    qa_assert($status===200&&str_contains($html,'otp_challenge_csrf')&&str_contains($html,'otp_code'),'Real email OTP challenge displayed');
    $csrf=qa_hidden($html,'csrf_token');$challenge=qa_hidden($html,'otp_challenge_csrf');
    echo "Enter the six-digit OTP from your own mailbox (or press Enter to stop safely): ";
    $otp=trim((string)fgets(STDIN));
    if(!preg_match('/^[0-9]{6}$/D',$otp))throw new AcademyQaFailure('OTP not supplied; check inbox or forwarding');
    $stage='OTP form submission';
    [$status,$html]=qa_http($ch,'/index.php?page=otp',['csrf_token'=>$csrf,'otp_challenge_csrf'=>$challenge,'form_action'=>'verify_otp','otp_code'=>$otp]);
    unset($otp);
    qa_assert($status===200&&is_email_verified(find_user_by_email($email)??[])&&stripos($html,'My Certificates')!==false,'Real OTP verified and student dashboard reached');
    [$status,$html]=qa_http($ch,'/index.php?page=jobs');
    qa_assert($status===403,'Student denied access to customer/staff jobs');
    [$status,$html]=qa_http($ch,'/academy.php?action=admin');
    qa_assert($status===403,'Student denied access to Academy administration');
    echo "REAL STUDENT OTP AND DASHBOARD CHECKS PASSED.\n";
}catch(Throwable $e){
    if($e instanceof AcademyQaFailure)fwrite(STDERR,"BLOCK ".$e->getMessage()."\n");
    elseif($e instanceof PDOException){
        $state=(string)($e->errorInfo[0]??$e->getCode());
        $driver=(string)($e->errorInfo[1]??'unknown');
        if(!preg_match('/^[A-Z0-9]{5}$/D',$state))$state='unknown';
        if(!preg_match('/^[0-9]+$/D',$driver))$driver='unknown';
        fwrite(STDERR,"BLOCK database stage: ".$stage."; SQLSTATE=".$state."; driver=".$driver."\n");
    }else fwrite(STDERR,"BLOCK ".$stage.": ".get_class($e)."; inspect private logs\n");
    $failed=true;
}finally{
    if($ch!==null)curl_close($ch);
    // Retain an audit record, invalidate only the account we demonstrably created.
    if($attempted&&$email!==''&&$password!==''){
        try{
            $u=find_user_by_email($email);
            if($u&&$u['role']==='student'&&$u['full_name']===$name&&password_verify($password,$u['password_hash'])){
                db_execute('UPDATE users SET deleted_at=:deleted,password_hash=:hash WHERE id=:id AND role=:role AND deleted_at IS NULL',['deleted'=>now(),'hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'id'=>$u['id'],'role'=>'student']);
                echo "PASS QA account disabled; audit record retained\n";
            }
        }catch(Throwable $e){fwrite(STDERR,"BLOCK QA account cleanup; contact administrator\n");$failed=true;}
    }
}
echo "No payment initiated, no certificate issued, no gateway settings changed.\n";
exit(!empty($failed)?3:0);
