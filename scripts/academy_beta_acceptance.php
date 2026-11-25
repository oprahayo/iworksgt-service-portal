<?php
declare(strict_types=1);
// One-time CLI acceptance checks for the deployed Academy beta.
// No payment initialization, no issued test certificate, no changes to existing accounts.
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$portal=realpath($argv[1]??'');
$home=realpath((string)getenv('HOME'));
if(!$portal||!$home||$portal!==realpath($home.'/public_html/portal.iworksgt.com')||!is_file($portal.'/app/config.php')){fwrite(STDERR,"BLOCK portal root invalid\n");exit(2);}
require $portal.'/app/bootstrap.php';
require $portal.'/app/academy.php';
final class AcademyAcceptanceFailure extends RuntimeException {}
function ac_accept(bool $value,string $label):void{
    if(!$value)throw new AcademyAcceptanceFailure($label);
    echo "PASS ".$label."\n";
}
function ac_http(string $path,?string $body=null,array $headers=[]):array{
    $ch=curl_init('https://portal.iworksgt.com'.$path);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>35,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    if($body!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>$headers]);
    $response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    if(!is_string($response))throw new AcademyAcceptanceFailure('HTTPS request failed');
    return [$status,$response];
}
function ac_test_attachment(string $recipient,string $pdf):bool{
    $from=trim(setting('mail_from_email',''));
    $envelope=iworks_mail_envelope_parameters($from);
    if(!email_enabled()||$envelope===null||!filter_var($recipient,FILTER_VALIDATE_EMAIL)||strpbrk($recipient,"\r\n")!==false)return false;
    $boundary='academy-test-'.bin2hex(random_bytes(12));
    $headers=implode("\r\n",['MIME-Version: 1.0','From: '.$from,'Reply-To: '.$from,'Content-Type: multipart/mixed; boundary="'.$boundary.'"']);
    $body="--".$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nThis is a controlled iWorks Academy test. The attached sample is NOT a qualification or valid certificate. No payment was made.\r\n"
        ."--".$boundary."\r\nContent-Type: application/pdf; name=\"ACADEMY-TEST-NOT-VALID.pdf\"\r\nContent-Disposition: attachment; filename=\"ACADEMY-TEST-NOT-VALID.pdf\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        .chunk_split(base64_encode($pdf))."\r\n--".$boundary."--\r\n";
    return @mail($recipient,'[iWorks Academy TEST] PDF delivery check',$body,$headers,$envelope);
}
try{
    ac_accept(!academy_checkout_enabled(),'Certification checkout remains disabled');
    foreach(['users','academy_courses','academy_enrollments','academy_graduates','academy_certificates'] as $table){
        $row=db_one("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table",['table'=>$table]);
        ac_accept(strtoupper((string)($row['ENGINE']??''))==='INNODB','Transactional table '.$table);
    }
    // Refuse to exercise a production fixture if custom triggers could cause side effects.
    $triggers=db_one("SELECT COUNT(*) AS n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE IN ('users','academy_courses','academy_enrollments','academy_graduates','academy_certificates')");
    ac_accept((int)($triggers['n']??-1)===0,'No unexpected fixture-table triggers');
    $tag=bin2hex(random_bytes(12));
    $email='academy-test-'.$tag.'@example.invalid';
    $otherEmail='academy-test-other-'.$tag.'@example.invalid';
    $password=bin2hex(random_bytes(24));
    $ref='IWGA-'.bin2hex(random_bytes(16));
    $number='IWGA-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8)));
    $admin=db_one("SELECT id FROM users WHERE role='admin' AND deleted_at IS NULL AND status='active' ORDER BY id LIMIT 1");
    ac_accept($admin!==null,'Existing administrator available');
    db()->beginTransaction();
    try{
        $student=create_user(['full_name'=>'ACADEMY TEST STUDENT','email'=>$email,'phone'=>'00000000000','password'=>$password,'role'=>'student']);
        $other=create_user(['full_name'=>'ACADEMY TEST OTHER','email'=>$otherEmail,'phone'=>'00000000000','password'=>bin2hex(random_bytes(24)),'role'=>'student']);
        $u=find_user_by_id($student);
        ac_accept($u!==null&&$u['role']==='student'&&!is_email_verified($u)&&password_verify($password,$u['password_hash']),'Student creation uses existing authentication and hashed password');
        mark_email_verified($student);
        ac_accept(is_email_verified(find_user_by_id($student)),'Existing account verification state');
        $course=db_one("SELECT id,title,colour FROM academy_courses WHERE slug='wordpress' AND active=1");
        ac_accept($course!==null&&$course['colour']==='purple','WordPress course and purple template mapping');
        $cid=(int)$course['id'];
        db_execute("INSERT INTO academy_enrollments (student_id,course_id,status,created_at,updated_at) VALUES (:student,:course,'requested',:created,:updated)",['student'=>$student,'course'=>$cid,'created'=>now(),'updated'=>now()]);
        db_execute("UPDATE academy_enrollments SET status='completed' WHERE student_id=:student AND course_id=:course",['student'=>$student,'course'=>$cid]);
        db_execute("INSERT INTO academy_graduates (student_id,course_id,duration,approved_by,approved_at) VALUES (:student,:course,'12 weeks',:admin,:approved)",['student'=>$student,'course'=>$cid,'admin'=>$admin['id'],'approved'=>now()]);
        $graduate=db_last_id();
        ac_accept(db_one('SELECT id FROM academy_graduates WHERE id=:id AND student_id=:student',['id'=>$graduate,'student'=>$student])!==null,'Enrollment and graduate approval database records');
        $preview=academy_render(['colour'=>'purple','student_name'=>'ACADEMY TEST STUDENT','course_title'=>$course['title'],'duration'=>'12 weeks'],true);
        ac_accept(str_contains($preview,'PREVIEW - NOT VALID')&&str_contains($preview,'ACADEMY TEST STUDENT'),'Certificate preview marked invalid');
        db_execute("INSERT INTO academy_certificates (graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,created_at) VALUES (:g,:s,:number,'ACADEMY TEST STUDENT','WordPress & CMS Web Design','12 weeks','purple',10200000,:ref,:created)",['g'=>$graduate,'s'=>$student,'number'=>$number,'ref'=>$ref,'created'=>now()]);
        $cert=db_last_id();
        ac_accept(academy_owned_certificate($cert,$student)===null&&academy_owned_certificate($cert,$other)===null,'Unpaid certificate not downloadable');
        $fixture=db_one('SELECT * FROM academy_certificates WHERE id=:id',['id'=>$cert]);
        $response=['status'=>true,'data'=>['status'=>'success','id'=>123456,'reference'=>$ref,'currency'=>'NGN','amount'=>10200000]];
        ac_accept(academy_verified_payment_matches($fixture,$response),'Exact NGN 102,000 verification contract (synthetic response)');
        $response['data']['amount']=10199999;
        ac_accept(!academy_verified_payment_matches($fixture,$response),'Incorrect payment amount rejected');
        ac_accept(!academy_checkout_enabled(),'No real checkout opened');
        // Exercise owner/revocation queries only within the uncommitted transaction.
        db_execute("UPDATE academy_certificates SET status='issued',issued_at=:issued WHERE id=:id",['issued'=>now(),'id'=>$cert]);
        ac_accept(academy_owned_certificate($cert,$student)!==null&&academy_owned_certificate($cert,$other)===null,'Certificate ownership isolation');
        db_execute("UPDATE academy_certificates SET status='revoked' WHERE id=:id",['id'=>$cert]);
        ac_accept(academy_owned_certificate($cert,$student)===null,'Revoked certificate not downloadable');
    }finally{
        if(db()->inTransaction())db()->rollBack();
    }
    ac_accept(find_user_by_email($email)===null&&find_user_by_email($otherEmail)===null&&db_one('SELECT id FROM academy_certificates WHERE payment_reference=:ref',['ref'=>$ref])===null,'All database test records rolled back');
    $pdf='';
    foreach(['orange','purple','green','blue'] as $colour){
        $sample=academy_pdf(['colour'=>$colour,'certificate_number'=>'TEST ONLY - NOT VALID','student_name'=>'ACADEMY TEST STUDENT','course_title'=>'TEST ONLY - NOT A QUALIFICATION','duration'=>'12 weeks','issued_at'=>now()]);
        ac_accept(str_starts_with($sample,'%PDF-')&&strlen($sample)>1000,'Deployed '.$colour.' PDF rendering');
        if($colour==='purple')$pdf=$sample;
    }
    [$status,$html]=ac_http('/student/register');
    ac_accept($status===200&&stripos($html,'student registration')!==false&&str_contains($html,'csrf_token'),'Public registration form and CSRF field');
    [$status,$html]=ac_http('/verify?number='.rawurlencode($number));
    ac_accept($status===200&&stripos($html,'No valid certificate found')!==false,'Public verification rejects nonexistent test number');
    [$status,$html]=ac_http('/academy.php?action=admin');
    ac_accept(in_array($status,[301,302,303,403],true),'Unauthenticated admin access denied');
    [$status,$html]=ac_http('/academy.php?action=download&id=0');
    ac_accept(in_array($status,[301,302,303,403,404],true),'Unauthenticated certificate download denied');
    // Harmless non-charge event checks the actual deployed HTTP webhook signature gate.
    $event=json_encode(['event'=>'academy.healthcheck','data'=>[]],JSON_THROW_ON_ERROR);
    $path='/index.php?page=payment_webhook&provider=paystack';
    [$status,$body]=ac_http($path,$event,['Content-Type: application/json','X-Paystack-Signature: invalid']);
    ac_accept($status===403,'Deployed webhook rejects invalid signature');
    $signature=hash_hmac('sha512',$event,academy_paystack_key());
    [$status,$body]=ac_http($path,$event,['Content-Type: application/json','X-Paystack-Signature: '.$signature]);
    $reply=json_decode($body,true);
    ac_accept($status===200&&($reply['success']??false)===true&&($reply['message']??'')==='Event ignored.','Deployed webhook accepts valid signature and ignores harmless event');
    echo "AUTOMATED BETA CHECKS PASSED. No charge or certificate issued.\n";
    // Optional operator-controlled mail probe; no test account or fake certificate number.
    echo "Enter an email address you control for one clearly marked test PDF (or press Enter to skip): ";
    $recipient=trim((string)fgets(STDIN));
    if($recipient!==''){
        if(!filter_var($recipient,FILTER_VALIDATE_EMAIL))throw new AcademyAcceptanceFailure('Invalid test recipient');
        ac_accept(ac_test_attachment($recipient,$pdf),'Mail transport accepted marked test PDF; confirm actual inbox delivery separately');
    }else{echo "SKIP test email (no recipient supplied)\n";}
    echo "PENDING: real registration OTP/browser journey, actual Paystack test-mode checkout and provider webhook delivery, issued-certificate email workflow, backup restoration rehearsal. Checkout remains disabled.\n";
}catch(Throwable $e){
    if(db()->inTransaction())db()->rollBack();
    // Only controlled assertion labels are safe to display, never PDO errors/config.
    if($e instanceof AcademyAcceptanceFailure)fwrite(STDERR,"BLOCK ".$e->getMessage()."\n");
    else fwrite(STDERR,"BLOCK unexpected ".get_class($e)."; review private application logs\n");
    exit(3);
}
