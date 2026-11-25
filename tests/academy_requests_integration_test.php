<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/academy.php';
require __DIR__.'/../app/academy_requests.php';
function now():string{return '2026-11-25 12:00:00';}
function create_notification(int $id,string $title,string $message,string $url=''):void{$GLOBALS['notices'][]=[$id,$title,$message,$url];}
function notify_roles(array $roles,string $title,string $message,string $url=''):void{$GLOBALS['admin_notices'][]=[$roles,$title,$url];}
function expect(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$student=(int)db_one("SELECT id FROM users WHERE email='other@academy.invalid'")['id'];
$admin=(int)db_one("SELECT id FROM users WHERE email='admin@academy.invalid'")['id'];
$course=(int)db_one("SELECT id FROM academy_courses WHERE slug='backend'")['id'];
$old=db_one("SELECT email_verified_at,status,account_state FROM users WHERE id=:id",['id'=>$student]);
try{
 db_execute("UPDATE users SET email_verified_at=:at,status='active',account_state='active' WHERE id=:id",['at'=>now(),'id'=>$student]);
 expect(academy_request_submit($student,$course,'Test Student','Completed course'),'Student submits');
 expect(!academy_request_submit($student,$course,'Test Student','Duplicate'),'Duplicate request blocked');
 $r=db_one('SELECT * FROM academy_certification_requests WHERE student_id=:student AND course_id=:course',compact('student','course'));
 $id=(int)$r['id'];
 expect($r['status']==='pending','Pending review');
 expect(!db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',compact('student','course')),'No automatic approval');
 $bad=false;try{academy_request_review($admin,$id,'declined','','');}catch(InvalidArgumentException $e){$bad=true;}expect($bad,'Decline requires reason');
 expect(academy_request_review($admin,$id,'declined','','Please provide training details.'),'Decline stored');
 expect(!academy_request_review($admin,$id,'approved','16',''),'Cannot approve stale declined request');
 expect(db_one('SELECT review_note FROM academy_certification_requests WHERE id=:id',compact('id'))['review_note']==='Please provide training details.','Reason visible to student');
 expect(academy_request_submit($student,$course,'Corrected Student','Training details supplied.'),'Resubmit after decline');
 expect(academy_request_review($admin,$id,'approved','16','Verified training'),'Approval');
 expect(!academy_request_review($admin,$id,'approved','16',''),'Duplicate review blocked');
 $g=db_one('SELECT id,duration FROM academy_graduates WHERE student_id=:student AND course_id=:course',compact('student','course'));
 expect($g&&$g['duration']==='16 Weeks','Approved graduate has normalized duration');
 expect(academy_request_name($student,(int)$g['id'],'Other Name')==='Corrected Student','Approved name locked');
 expect(!db_one('SELECT id FROM academy_certificates WHERE graduate_id=:id',['id'=>$g['id']]),'No certificate issued before payment');
 expect(count($GLOBALS['notices']??[])===4&&count($GLOBALS['admin_notices']??[])===2,'Notify each request and decision once');
 echo "PASS student request, decline reason, resubmit, approval, name lock and notification events.\n";
}finally{
 db_execute('DELETE FROM academy_certification_requests WHERE student_id=:student AND course_id=:course',compact('student','course'));
 db_execute('DELETE FROM academy_graduates WHERE student_id=:student AND course_id=:course',compact('student','course'));
 db_execute('DELETE FROM academy_enrollments WHERE student_id=:student AND course_id=:course',compact('student','course'));
 db_execute('UPDATE users SET email_verified_at=:verified,status=:status,account_state=:state WHERE id=:id',['verified'=>$old['email_verified_at'],'status'=>$old['status'],'state'=>$old['account_state'],'id'=>$student]);
}
