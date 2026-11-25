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
require __DIR__.'/../app/academy_requests.php';
function academy_checkout_enabled():bool{return false;}
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
    $_SESSION=[];
    define('IWORKS_ACADEMY_STUDENT_VIEW',true);
    ob_start();require __DIR__.'/../views/academy-student.php';$html=ob_get_clean();
    $expected=match($action){
        'dashboard'=>['Welcome, CI','Pending applications','Things to do','Already completed training with iWorks?','Available courses','Request certification'],
        'courses'=>['My courses &amp; applications','Pending application','Explore courses','Apply for course'],
        'certificates'=>['My certificates','Submit certification request','Get Certificate','Digital Marketing','Certificate revoked','Administrator query','Training could not be verified.'],
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
    foreach($expected as $needle)if(!str_contains($html,$needle))throw new RuntimeException($action.' missing '.$needle);
    foreach($excluded as $needle)if(str_contains($html,$needle))throw new RuntimeException($action.' incorrectly contains '.$needle);
    if(str_contains($html,'name="note"')||str_contains($html,'Contact Academy')||str_contains($html,'Send inquiry'))throw new RuntimeException('Student messaging exposed');
    if(str_contains($html,'admin@academy.invalid'))throw new RuntimeException('Other user exposed');
    if(getenv('ACADEMY_VISUAL_FIXTURES')==='1'){
        $dir=__DIR__.'/../docs/visual-qa/academy';
        if(!is_dir($dir))mkdir($dir,0755,true);
        file_put_contents($dir.'/student-'.$action.'.html','<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Academy visual fixture</title><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/admin-workspace-refresh.css"><link rel="stylesheet" href="../../../assets/css/academy.css"><style>:root{--brand-primary:#e65c00;--brand-primary-dark:#b54708}body{font:16px system-ui,sans-serif;background:#f6f8fb;margin:0}.fixture-shell{min-width:0;margin-left:240px;padding:24px}@media(max-width:991px){.fixture-shell{margin-left:0;padding:12px}}</style></head><body><main class="fixture-shell"><div class="academy-workspace">'.str_replace('/assets/js/academy-guide.js?v=1','../../../assets/js/academy-guide.js?v=1',$html).'</div></main></body></html>');
    }
    echo 'Student '.$action." rendered using isolated MySQL fixtures.\n";
}finally{if(db()->inTransaction())db()->rollBack();}
