<?php
declare(strict_types=1);
// This script runs only against an isolated CI database; all payment responses are simulated.
if (PHP_SAPI!=='cli' || getenv('ACADEMY_CI_DATABASE')!=='1' || getenv('DB_NAME')!=='academy_ci') exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
function now():string{return '2026-11-25 12:00:00';}
function get_payment_method_by_code(string $code):array{return ['is_active'=>1,'secret_key'=>'sk_live_CIFixtureNotARealSecret'];}
function gateway_json_get(string $url,string $secret):array{return $GLOBALS['gateway_fixture'];}
require __DIR__.'/../app/academy.php';
function check(bool $value,string $message):void {if(!$value)throw new RuntimeException($message);}
function user(string $role,string $email):int{
 db_execute("INSERT INTO users (full_name,email,password_hash,role,created_at) VALUES (:name,:email,:hash,:role,:created)",
 ['name'=>'CI Test '.$role,'email'=>$email,'hash'=>password_hash('fixture-password',PASSWORD_DEFAULT),'role'=>$role,'created'=>now()]);
 return db_last_id();
}
db()->beginTransaction();
try{
 foreach(['admin','agent','client','finance','author'] as $role){
  $uid=user($role,$role.'@academy.invalid');
  check(db_one('SELECT role FROM users WHERE id=:id',['id'=>$uid])['role']===$role,'Legacy role preserved '.$role);
  if($role==='admin')$admin=$uid;
 }
 $student=user('student','student@academy.invalid');$other=user('student','other@academy.invalid');
 $course=db_one("SELECT id,colour FROM academy_courses WHERE slug='wordpress'");
 check($course!==null&&$course['colour']==='purple','Course colour and seed');
 $cid=(int)$course['id'];
 db_execute("INSERT INTO academy_enrollments (student_id,course_id,status,created_at,updated_at) VALUES (:student,:course,'requested',:created,:updated)",['student'=>$student,'course'=>$cid,'created'=>now(),'updated'=>now()]);
 db_execute("UPDATE academy_enrollments SET status='completed' WHERE student_id=:student AND course_id=:course",['student'=>$student,'course'=>$cid]);
 db_execute("INSERT INTO academy_graduates (student_id,course_id,duration,approved_by,approved_at) VALUES (:student,:course,'12 weeks',:admin,:approved)",['student'=>$student,'course'=>$cid,'admin'=>$admin,'approved'=>now()]);
 $graduate=db_last_id();
 $ref='IWGA-'.str_repeat('a',32);
 db_execute("INSERT INTO academy_certificates (graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,created_at) VALUES (:g,:s,'IWGA-2026-AAAAAAAAAAAAAAAA','CI TEST STUDENT','WordPress & CMS Web Design','12 weeks','purple',10200000,:ref,:created)",['g'=>$graduate,'s'=>$student,'ref'=>$ref,'created'=>now()]);
 $cert=db_last_id();
 // academy_confirm owns its transaction, so commit fixture rows first.
 db()->commit();
 $GLOBALS['gateway_fixture']=['status'=>true,'data'=>['domain'=>'live','id'=>765432,'status'=>'success','reference'=>$ref,'currency'=>'NGN','amount'=>10199999]];
 check(!academy_confirm($ref),'Reject wrong amount');
 check(academy_owned_certificate($cert,$student)===null,'No pending PDF');
 $GLOBALS['gateway_fixture']['data']['amount']=10200000;
 $GLOBALS['gateway_fixture']['data']['domain']='test';
 check(!academy_confirm($ref),'Reject sandbox transaction for production issuance');
 $GLOBALS['gateway_fixture']['data']['domain']='live';
 check(academy_confirm($ref),'Issue on verified payment');
 check(academy_confirm($ref),'Replay idempotent');
 check(academy_owned_certificate($cert,$other)===null,'Ownership enforced');
 $row=academy_owned_certificate($cert,$student);
 check($row!==null&&$row['status']==='issued'&&$row['provider_transaction']==='765432'&&$row['email_sent_at']===null,'Issuance and durable email pending');
 $dup=false;
 try{
  db_execute("INSERT INTO academy_certificates (graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,created_at) VALUES (:g,:s,'IWGA-2026-BBBBBBBBBBBBBBBB','CI TEST STUDENT','WordPress','12 weeks','purple',10200000,:ref,:created)",['g'=>$graduate,'s'=>$student,'ref'=>'IWGA-'.str_repeat('b',32),'created'=>now()]);
 }catch(PDOException $e){$dup=$e->getCode()==='23000';}
 check($dup,'Unique graduate certificate constraint');
 db_execute("UPDATE academy_certificates SET status='revoked' WHERE id=:id",['id'=>$cert]);
 check(!academy_confirm($ref)&&academy_owned_certificate($cert,$student)===null,'Revocation stays effective');
 echo "Academy MySQL migration, enrollment, payment and certificate lifecycle passed.\n";
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
