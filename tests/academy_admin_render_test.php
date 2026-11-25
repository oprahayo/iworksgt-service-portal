<?php
declare(strict_types=1);
// Render the real admin view against the isolated Academy CI database.
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
function has_role(array $roles):bool{return in_array('admin',$roles,true);}
function is_email_verified(array $u):bool{return !empty($u['email_verified_at']);}
function academy_checkout_enabled():bool{return false;}
function flash(string $key):?string{return null;}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function ac_form(string $action):void{echo '<form method="post" action="/academy.php?action='.e($action).'"><input type="hidden" name="csrf_token" value="fixture">';}
function ac_head(string $title):void{echo '<h1>'.e($title).'</h1>';}
function ac_end():void{}
$tab=$argv[1]??'students';
$student=db_one("SELECT id FROM users WHERE email='student@academy.invalid'");
$admin=db_one("SELECT id FROM users WHERE email='admin@academy.invalid'");
if(!$student||!$admin)throw new RuntimeException('Missing integration fixtures');
$id=(int)$student['id'];
db()->beginTransaction();
try{
    db_execute("UPDATE users SET email_verified_at='2026-11-25 12:00:00',status='active',account_state='active' WHERE id=:id",['id'=>$id]);
    $course=db_one("SELECT id FROM academy_courses WHERE slug='digital-marketing'");
    db_execute("INSERT INTO academy_graduates(student_id,course_id,duration,approved_by,approved_at) VALUES(:student,:course,'6 weeks',:admin,'2026-11-25 12:00:00')",['student'=>$id,'course'=>$course['id'],'admin'=>$admin['id']]);
    $course=db_one("SELECT id FROM academy_courses WHERE slug='hosting'");
    db_execute("INSERT INTO academy_enrollments(student_id,course_id,status,created_at,updated_at) VALUES(:student,:course,'requested','2026-11-25 12:00:00','2026-11-25 12:00:00')",['student'=>$id,'course'=>$course['id']]);
    $_GET=['tab'=>$tab,'id'=>$id,'q'=>$tab==='students'?'CI Test':'','status'=>$tab==='certificates'?'ready':''];
    define('IWORKS_ACADEMY_ADMIN_VIEW',true);
    ob_start();
    require __DIR__.'/../views/academy-admin.php';
    $html=ob_get_clean();
    $expected=match($tab){
      'students'=>['CI Test student','student@academy.invalid','View student','Verified'],
      'student'=>['CI Test student','Approve for certification','name="student_id"','Ready to certify','Training duration','Revoked'],
      'enrollments'=>['CI Test student','Web Hosting','New request','Accept'],
      'courses'=>['Add a course','WordPress &amp; CMS Web Design','Certificate colour','Create Course'],
      'certificates'=>['CI Test student','Digital Marketing','Ready to certify','Not started'],
      default=>throw new RuntimeException('Unknown test tab')
    };
    foreach($expected as $needle)if(!str_contains($html,$needle))throw new RuntimeException($tab.' missing '.$needle);
    if(str_contains($html,'admin@academy.invalid'))throw new RuntimeException('Nonstudent leaked into Academy roster');
    echo 'Academy admin '.$tab." rendered against real MySQL fixture.\n";
}finally{if(db()->inTransaction())db()->rollBack();}
