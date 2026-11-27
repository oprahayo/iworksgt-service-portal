<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
function has_role(array $roles):bool{return in_array('student',$roles,true);}
function flash(string $key):?string{return null;}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function ac_form(string $action):void{echo '<form method="post" action="/academy.php?action='.e($action).'"><input type="hidden" name="csrf_token" value="fixture">';}
function ac_input(string $name,string $label,string $value='',string $type='text'):void{echo '<label>'.e($label).'<input name="'.e($name).'" value="'.e($value).'"></label>';}
function ac_head(string $title):void{echo '<header>'.e($title).'</header>';}
function ac_end():void{}
function csrf_token():string{return 'fixture';}
function url(string $page):string{return '/index.php?page='.$page;}
require __DIR__.'/../app/academy_requests.php';
function academy_checkout_enabled():bool{return ($GLOBALS['action']??'')==='certificates';}
$action=$argv[1]??'dashboard';
$user=db_one("SELECT * FROM users WHERE email='student@academy.invalid'");
$admin=db_one("SELECT id FROM users WHERE email='admin@academy.invalid'");
if(!$user||!$admin)throw new RuntimeException('Missing integration fixtures');
db()->beginTransaction();
try{
    $course=db_one("SELECT id FROM academy_courses WHERE slug='digital-marketing'");
    db_execute("INSERT INTO academy_graduates(student_id,course_id,duration,approved_by,approved_at) VALUES(:student,:course,'6 weeks',:admin,'2026-11-25 12:00:00')",['student'=>$user['id'],'course'=>$course['id'],'admin'=>$admin['id']]);
    $course=db_one("SELECT id FROM academy_courses WHERE slug='hosting'");
    db_execute("INSERT INTO academy_enrollments(student_id,course_id,status,created_at,updated_at) VALUES(:student,:course,'requested','2026-11-25 12:00:00','2026-11-25 12:00:00')",['student'=>$user['id'],'course'=>$course['id']]);
    if($action==='certificates')db_execute("INSERT INTO academy_certification_requests(student_id,course_id,student_name,student_note,review_note,status,submitted_at) VALUES(:student,:course,'CI Test Student','','Training could not be verified.','declined','2026-11-25 12:00:00')",['student'=>$user['id'],'course'=>$course['id']]);
    if($action==='certificates'){
        foreach([['frontend-advanced','pending','CCCCCCCCCCCCCCCC'],['frontend-basic','issued','DDDDDDDDDDDDDDDD']] as [$slug,$status,$number]){
            $fixture=db_one('SELECT id,title,colour FROM academy_courses WHERE slug=:slug',compact('slug'));
            db_execute("INSERT INTO academy_graduates(student_id,course_id,duration,approved_by,approved_at) VALUES(:student,:course,'16 Weeks',:admin,'2026-11-25 12:00:00')",['student'=>$user['id'],'course'=>$fixture['id'],'admin'=>$admin['id']]);
            $graduate=db_last_id();
            db_execute("INSERT INTO academy_certificates(graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,checkout_url,status,issued_at,created_at) VALUES(:g,:s,:number,'CI Test Student',:title,'16 Weeks',:colour,10200000,:ref,:url,:status,:issued,'2026-11-25 12:00:00')",['g'=>$graduate,'s'=>$user['id'],'number'=>'IWGA-2026-'.$number,'title'=>$fixture['title'],'colour'=>$fixture['colour'],'ref'=>'IWGA-'.str_repeat(strtolower($number[0]),32),'url'=>'https://checkout.paystack.com/fixture','status'=>$status,'issued'=>$status==='issued'?'2026-11-25 12:05:00':null]);
        }
    }
    if($action==='dashboard'){
        db_execute("UPDATE users SET status='active',account_state='active',email_verified_at='2026-11-25 12:00:00' WHERE id=:id",['id'=>$user['id']]);
        $resumeCourse=db_one("SELECT id FROM academy_courses WHERE slug='wordpress'");
        db_execute("UPDATE academy_enrollments SET status='enrolled' WHERE student_id=:student AND course_id=:course",['student'=>$user['id'],'course'=>$resumeCourse['id']]);
        db_execute("INSERT INTO academy_learning_courses(course_id,description,published,updated_at) VALUES(:course,'Dashboard fixture',1,'2026-11-25 12:00:00') ON DUPLICATE KEY UPDATE published=1",['course'=>$resumeCourse['id']]);
        db_execute("INSERT INTO academy_modules(course_id,title,published) VALUES(:course,'Dashboard module',1)",['course'=>$resumeCourse['id']]);$mid=db_last_id();
        db_execute("INSERT INTO academy_lessons(module_id,title,body,published) VALUES(:module,'Resume lesson','Fixture',1)",['module'=>$mid]);
    }
    $_SESSION=[];
    define('IWORKS_ACADEMY_STUDENT_VIEW',true);
    ob_start();require __DIR__.'/../views/academy-student.php';$html=ob_get_clean();
    $expected=match($action){
        'dashboard'=>['Welcome, CI','Pending applications','Things to do','Already completed training with iWorks?','Available courses','Request certification','Continue learning','0/1 lessons'],
        'courses'=>['My courses &amp; applications','Pending application','Explore courses','Apply for course'],
        'certificates'=>['Certificate history','Submit certification request','Get Certificate','Digital Marketing','Certificate revoked','Administrator query','Training could not be verified.','Check Payment','Continue Payment','Download Certificate'],
        'profile'=>['Personal information','student@academy.invalid','Save profile'],
        default=>throw new RuntimeException('Unknown screen')
    };
    $excluded=match($action){
        'dashboard'=>['name="graduate"','name="phone"'],
        'courses'=>['Request course review','Save profile','Get Certificate'],
        'certificates'=>['Apply for course','Save profile'],
        'profile'=>['Apply for course','Get Certificate']
    };
    $guideTitle=['dashboard'=>'Getting started','courses'=>'How to apply for a course','certificates'=>'How to get your certificate','profile'=>'How to update your profile'][$action];
    if(!str_contains($html,$guideTitle)||!str_contains($html,'id="ac-guide-'.$action.'"'))throw new RuntimeException('Missing contextual student guide');
    if($action==='dashboard'&&(!str_contains($html,'id="ac-welcome-guide"')||!str_contains($html,'data-guide-auto="1"')||!str_contains($html,'Go to My Certificates')||!str_contains($html,'Go to My Courses')))throw new RuntimeException('Missing welcome paths');
    if($action!=='dashboard'&&str_contains($html,'id="ac-welcome-guide"'))throw new RuntimeException('Welcome dialog outside dashboard');
    if($action==='certificates'&&(!str_contains($html,'data-cert-wizard')||!str_contains($html,'name="confirm_name"')||!str_contains($html,'id="ac-cert-dialog"')||!str_contains($html,'ac-cert-record')||!str_contains($html,'cannot be regenerated')))throw new RuntimeException('Missing progressive certificate request or confirmation');
    if($action==='dashboard'&&(!str_contains($html,'certificates#ac-new-certificate')||!str_contains($html,'courses#ac-explore-courses')))throw new RuntimeException('Guide action deep links absent');
    if($action==='certificates'&&(!str_contains($html,'id="ac-certificate-history"')||!str_contains($html,'ac-cert-next')))throw new RuntimeException('Certificate action history missing');
    foreach($expected as $needle)if(!str_contains($html,$needle))throw new RuntimeException($action.' missing '.$needle);
    foreach($excluded as $needle)if(str_contains($html,$needle))throw new RuntimeException($action.' incorrectly contains '.$needle);
    if(str_contains($html,'name="note"')||str_contains($html,'Contact Academy')||str_contains($html,'Send inquiry'))throw new RuntimeException('Student messaging exposed');
    if(str_contains($html,'admin@academy.invalid'))throw new RuntimeException('Other user exposed');
    if(getenv('ACADEMY_VISUAL_FIXTURES')==='1'){
        $dir=__DIR__.'/../docs/visual-qa/academy';
        if(!is_dir($dir))mkdir($dir,0755,true);
        $pageHeading=$action==='profile'?'My Profile':($action==='courses'?'My Courses':'My Certificates');
        $unreadCount=4;$latestNotificationId=42;
        $originalName=$user['full_name'];$user['full_name']='CI Student With A Very Long Name For Mobile Layout';
        ob_start();require __DIR__.'/../views/partials/academy-student-topbar.php';$topbar=ob_get_clean();$user['full_name']=$originalName;
        if(!str_contains($topbar,'topbarUnreadBadge')||!str_contains($topbar,'data-profile-form'))throw new RuntimeException('Student shell missing profile or notification controls');
        $topbar=str_replace('/assets/js/academy-profile.js?v=1','../../../assets/js/academy-profile.js?v=1',$topbar);
        file_put_contents($dir.'/student-'.$action.'.html','<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Academy visual fixture</title><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/admin-workspace-refresh.css"><link rel="stylesheet" href="../../../assets/css/academy.css"><link rel="stylesheet" href="../../../assets/css/academy-compact.css"><style>:root{--brand-primary:#e65c00;--brand-primary-dark:#b54708}body{font:16px system-ui,sans-serif;background:#f6f8fb;margin:0}.fixture-shell{min-width:0;margin-left:240px;padding:24px}@media(max-width:991px){.fixture-shell{margin-left:0;padding:12px}}</style></head><body class="portal-authenticated role-student"><main class="fixture-shell">'.$topbar.'<div class="academy-workspace">'.str_replace(['/assets/js/academy-guide.js?v=4','/assets/js/academy-certificates.js?v=4'],['../../../assets/js/academy-guide.js?v=4','../../../assets/js/academy-certificates.js?v=4'],$html).'</div></main></body></html>');
    }
    echo 'Student '.$action." rendered using isolated MySQL fixtures.\n";
}finally{if(db()->inTransaction())db()->rollBack();}
