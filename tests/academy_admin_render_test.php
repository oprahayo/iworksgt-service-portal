<?php
declare(strict_types=1);
// Render the real admin view against the isolated Academy CI database.
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
function has_role(array $roles):bool{return in_array('admin',$roles,true);}
function is_email_verified(array $u):bool{return !empty($u['email_verified_at']);}
require __DIR__.'/../app/academy.php';
require __DIR__.'/../app/academy_admin.php';
function setting(string $key,string $default=''):string{return '0';}
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
    if($tab==='analytics'){
        db_execute("INSERT INTO academy_modules(course_id,title,published) VALUES(:id,'Review fixture',1)",['id'=>$course['id']]);$module=db_last_id();
        db_execute("INSERT INTO academy_lessons(module_id,title,body,published) VALUES(:id,'Practical lesson','',1)",['id'=>$module]);$lesson=db_last_id();
        db_execute("INSERT INTO academy_assessments(lesson_id,kind,title,instructions,questions_json,published,created_at) VALUES(:id,'assignment','Practical review fixture','Submit work','[]',1,'2026-11-25 12:00:00')",['id'=>$lesson]);$assessment=db_last_id();
        db_execute("INSERT INTO academy_assessment_submissions(assessment_id,student_id,response_json,feedback,submitted_at) VALUES(:assessment,:student,:response,'','2026-11-25 12:00:00')",['assessment'=>$assessment,'student'=>$id,'response'=>json_encode('Sample written response <safe>')]);$submission=db_last_id();
        db_execute("INSERT INTO academy_assignment_files(submission_id,filename,content,size_bytes,uploaded_at) VALUES(:id,'work.pdf','%PDF-fixture',12,'2026-11-25 12:00:00')",['id'=>$submission]);
    }
    if($tab==='certificates'){
        $other=db_one("SELECT id FROM users WHERE email='other@academy.invalid'");
        foreach([['backend','pending','B'],['fullstack','issued','C']] as [$slug,$state,$suffix]){
            $extra=db_one('SELECT id FROM academy_courses WHERE slug=:slug',['slug'=>$slug]);
            db_execute("INSERT INTO academy_graduates(student_id,course_id,duration,approved_by,approved_at) VALUES(:student,:course,'8 weeks',:admin,'2026-11-25 12:00:00')",['student'=>$other['id'],'course'=>$extra['id'],'admin'=>$admin['id']]);
            $graduate=db_last_id();
            db_execute("INSERT INTO academy_certificates(graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,status,issued_at,created_at) VALUES(:g,:s,:n,'CI TEST STUDENT',:title,'8 weeks','blue',10200000,:ref,:state,:issued,'2026-11-25 12:00:00')",['g'=>$graduate,'s'=>$other['id'],'n'=>'IWGA-2026-'.str_repeat($suffix,16),'title'=>$slug,'ref'=>'IWGA-'.str_repeat(strtolower($suffix),32),'state'=>$state,'issued'=>$state==='issued'?'2026-11-25 12:00:00':null]);
        }
    }
    if($tab==='requests'){
        db_execute("UPDATE academy_enrollments SET status='enrolled' WHERE student_id=:student AND course_id=:course",['student'=>$id,'course'=>$course['id']]);
        db_execute("INSERT INTO academy_learning_courses(course_id,description,published,updated_at) VALUES(:course,'Review fixture',1,'2026-11-25 12:00:00') ON DUPLICATE KEY UPDATE published=1",['course'=>$course['id']]);
    }
    if($tab==='requests')db_execute("INSERT INTO academy_certification_requests(student_id,course_id,student_name,student_note,review_note,submitted_at) VALUES(:student,:course,'CI Test student','Completed training','','2026-11-25 12:00:00')",['student'=>$id,'course'=>$course['id']]);
    $_GET=['tab'=>$tab,'id'=>$id,'q'=>$tab==='students'?'CI Test':'','status'=>''];
    define('IWORKS_ACADEMY_ADMIN_VIEW',true);
    ob_start();
    require __DIR__.'/../views/academy-admin.php';
    $html=ob_get_clean();
    $expected=match($tab){
      'analytics'=>['Learning overview','Active enrollments','Awaiting grading','Certificates issued','Course progress','Completion rate','Web Hosting','Assignment review','Practical review fixture','Review assignment','Download submitted PDF','Save grade','Sample written response &lt;safe&gt;'],
      'students'=>['CI Test student','student@academy.invalid','View student','Verified'],
      'student'=>['CI Test student','Review student requests','name="student_id"','Ready to certify','Training duration','Edit duration','Duration locked after checkout starts.','Revoked','Edit student profile','View progress','Administrator activity','Change status','Download transcript'],
      'enrollments'=>['CI Test student','Web Hosting','New request','Accept'],
      'courses'=>['Add a course','WordPress &amp; CMS Web Design','Certificate colour','Create Course'],
      'requests'=>['Certification requests','Certificate name','Approve for certification','Decline and notify student','Learning evidence','Review needed','Download academic transcript'],
      'certificates'=>['CI Test student','Digital Marketing','Ready to certify','Not started','Verify payment','Retry email'],
      default=>throw new RuntimeException('Unknown test tab')
    };
    foreach($expected as $needle)if(!str_contains($html,$needle))throw new RuntimeException($tab.' missing '.$needle);
    if(str_contains($html,'Send an update to this student')||str_contains($html,'Student note:')||str_contains($html,'Optional review note'))throw new RuntimeException('Messaging control exposed');
    if(str_contains($html,'admin@academy.invalid'))throw new RuntimeException('Nonstudent leaked into Academy roster');
    if(getenv('ACADEMY_VISUAL_FIXTURES')==='1'){
        $dir=__DIR__.'/../docs/visual-qa/academy';
        if(!is_dir($dir))mkdir($dir,0755,true);
        file_put_contents($dir.'/admin-'.$tab.'.html','<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Academy visual fixture</title><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/admin-workspace-refresh.css"><link rel="stylesheet" href="../../../assets/css/academy.css"><link rel="stylesheet" href="../../../assets/css/academy-compact.css"><style>:root{--brand-primary:#e65c00;--brand-primary-dark:#b54708}body{font:16px system-ui,sans-serif;background:#f6f8fb;margin:0}.fixture-shell{min-width:0;margin-left:240px;padding:24px}@media(max-width:991px){.fixture-shell{margin-left:0;padding:12px}}</style></head><body><main class="fixture-shell"><div class="academy-workspace">'.str_replace('/assets/js/academy-actions.js?v=1','../../../assets/js/academy-actions.js?v=1',$html).'</div></main></body></html>');
    }
    echo 'Academy admin '.$tab." rendered against real MySQL fixture.\n";
}finally{if(db()->inTransaction())db()->rollBack();}
