<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/academy_learning.php';
require __DIR__.'/../app/academy_assessments.php';
require __DIR__.'/../views/academy-assessments.php';
function csrf_token():string{return 'fixture';}
function now():string{return '2026-11-25 12:00:00';}
function expect(bool $v,string $m):void{if(!$v)throw new RuntimeException($m);}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function has_role(array $roles):bool{return in_array($GLOBALS['fixtureRole']??'student',$roles,true);}
function flash(string $key):?string{return null;}
function ac_head(string $title):void{echo '<header>'.e($title).'</header>';}
function ac_end():void{}
function ac_form(string $action):void{echo '<form method="post" action="/academy.php?action='.e($action).'"><input type="hidden" name="csrf_token" value="fixture">';}
function ac_input(string $name,string $label,string $value='',string $type='text'):void{echo '<label>'.e($label).'<input name="'.e($name).'" type="'.e($type).'" value="'.e($value).'"></label>';}
$user=db_one("SELECT * FROM users WHERE email='student@academy.invalid'");
$other=db_one("SELECT id FROM users WHERE email='other@academy.invalid'");
$cid=(int)db_one("SELECT id FROM academy_courses WHERE slug='wordpress'")['id'];
$sid=(int)$user['id'];$oid=(int)$other['id'];
db()->beginTransaction();
try{
 foreach([$sid,$oid] as $id)db_execute("UPDATE users SET email_verified_at=:at,status='active',account_state='active' WHERE id=:id",['at'=>now(),'id'=>$id]);
 db_execute("INSERT INTO academy_enrollments(student_id,course_id,status,created_at,updated_at) VALUES(:student,:course,'requested',:created,:updated)",['student'=>$oid,'course'=>$cid,'created'=>now(),'updated'=>now()]);
 academy_learning_save($cid,'course',['description'=>'Fixture course']);
 academy_learning_save($cid,'module',['title'=>'Module 1','position'=>'10','published'=>'1']);$mid=db_last_id();
 academy_learning_save($cid,'lesson',['module'=>$mid,'title'=>'Draft secret','position'=>'20','body'=>'Private draft']);
 $draft=db_last_id();
 academy_learning_save($cid,'lesson',['module'=>$mid,'title'=>'Published lesson','position'=>'10','body'=>'<script>alert(1)</script>','video_url'=>'https://example.com/video','material_url'=>'https://example.com/resource.pdf','published'=>'1']);
 $lid=db_last_id();
 expect(academy_learning_lessons($sid,$cid)===[],'Unpublished course hidden');
 academy_learning_save($cid,'course',['description'=>'Fixture course','published'=>'1']);
 expect(academy_learning_access($oid,$cid)===null,'Requested student denied');
 expect(academy_learning_lesson($sid,$cid,$draft)===null,'Draft lesson hidden');
 expect(academy_learning_lesson($oid,$cid,$lid)===null,'Unapproved direct access denied');
 expect(count(academy_learning_lessons($sid,$cid))===1,'Only published lessons visible');
 expect(!academy_learning_complete($oid,$cid,$lid),'Unapproved progress denied');
 expect(!academy_learning_complete($sid,$cid,$draft),'Draft progress denied');
 expect(academy_learning_complete($sid,$cid,$lid),'Progress saved');
 expect(!academy_learning_complete($sid,$cid,$lid),'Completion is idempotent');
 expect((int)academy_learning_lessons($sid,$cid)[0]['completed']===1,'Progress visible');
 academy_learning_save($cid,'module',['id'=>$mid,'title'=>'Module 1','position'=>'10']);
 expect(academy_learning_lesson($sid,$cid,$lid)===null,'Unpublished module blocks lesson');
 academy_learning_save($cid,'module',['id'=>$mid,'title'=>'Module 1','position'=>'10','published'=>'1']);
 foreach(['javascript:alert(1)','http://example.com','https://user:pass@example.com'] as $bad){$rejected=false;try{academy_learning_url($bad);}catch(InvalidArgumentException $e){$rejected=true;}expect($rejected,'Unsafe URL rejected');}
 expect(academy_video_embed('https://youtu.be/dQw4w9WgXcQ')==='https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ','Allowlisted video');
 expect(academy_video_embed('https://www.youtube.com.evil.invalid/watch?v=dQw4w9WgXcQ')===null,'Untrusted video not embedded');
 expect(academy_video_embed('https://vimeo.com/123456')==='https://player.vimeo.com/video/123456','Vimeo supported');
 $pdf="%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n";
 $filename=academy_material_validate('fixture.pdf',$pdf);
 $bad=false;try{academy_material_validate('evil.pdf','<script>fake</script>');}catch(InvalidArgumentException $e){$bad=true;}expect($bad,'Reject non-PDF content');
 db_execute('INSERT INTO academy_lesson_materials(lesson_id,filename,content,size_bytes,created_at) VALUES(:lesson,:name,:content,:size,:at)',['lesson'=>$lid,'name'=>$filename,'content'=>$pdf,'size'=>strlen($pdf),'at'=>now()]);
 $file=db_last_id();
 expect(academy_material_download($sid,$cid,$lid,$file)['content']===$pdf,'Approved student can access PDF');
 expect(academy_material_download($oid,$cid,$lid,$file)===null,'Unapproved student cannot download');
 expect(academy_material_download($sid,$cid,$draft,$file)===null,'Wrong lesson cannot download');
 $quiz=academy_assessment_create($cid,$lid,['kind'=>'quiz','title'=>'Knowledge quiz','instructions'=>'Choose one answer.','questions'=>'One plus one? | 2 | 3 | 4 | 5 | 1','pass_percent'=>'60','published'=>'1']);
 $exam=academy_assessment_create($cid,$lid,['kind'=>'exam','title'=>'Final exam','questions'=>'One plus two? | 2 | 3 | 4 | 5 | 2','pass_percent'=>'60','published'=>'1']);
 $assignment=academy_assessment_create($cid,$lid,['kind'=>'assignment','title'=>'Practical work','instructions'=>'Describe your work.','published'=>'1']);
 $hidden=academy_assessment_create($cid,$lid,['kind'=>'quiz','title'=>'Hidden assessment','questions'=>'Q | A | B | C | D | 1']);
 foreach([[$oid,$quiz],[$sid,$hidden]] as [$who,$aid]){$denied=false;try{academy_assessment_submit($who,$cid,$lid,$aid,['answers'=>['0']]);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Reject unauthorized/draft assessment');}
 expect(academy_assessment_submit($sid,$cid,$lid,$quiz,['answers'=>['0']]),'Quiz submission');
 expect(!academy_assessment_submit($sid,$cid,$lid,$quiz,['answers'=>['1']]),'One attempt only');
 expect(academy_assessment_submit($sid,$cid,$lid,$exam,['answers'=>['0']]),'Exam submission');
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$quiz])['score_percent']===100,'Server graded correct quiz');
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$exam])['score_percent']===0,'Server graded incorrect exam');
 expect(academy_assessment_submit($sid,$cid,$lid,$assignment,['response'=>'Student work <script>']), 'Assignment submitted');
 $sub=db_one('SELECT id,score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$assignment]);
 expect($sub['score_percent']===null,'Assignment awaits manual grade');
 $admin=(int)db_one("SELECT id FROM users WHERE email='admin@academy.invalid'")['id'];
 academy_assignment_grade($admin,$cid,$lid,(int)$sub['id'],['score'=>'85','feedback'=>'Good work']);
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE id=:id',['id'=>$sub['id']])['score_percent']===85,'Assignment manual grade saved');
 $results=academy_course_results($sid,$cid);
 expect(count($results)===3,'Only published assessment results shown');
 expect(academy_course_results($oid,$cid)===[],'Unapproved student denied results');
 expect(academy_course_results($sid,0)===[],'Wrong course denied results');
 expect(count(array_filter($results,fn($r)=>$r['score_percent']!==null))===3,'All own graded results visible');
 expect(!array_key_exists('questions_json',$results[0]),'Answer keys not exposed');
 $course=academy_learning_access($sid,$cid);$_GET=['lesson'=>$lid];define('IWORKS_ACADEMY_LEARNING_VIEW',true);
 ob_start();require __DIR__.'/../views/academy-learning.php';$html=ob_get_clean();
 expect(str_contains($html,'How to study your course'),'Learning guidance rendered');
 expect(str_contains($html,'Academic results')&&str_contains($html,'3 of 3 submitted')&&str_contains($html,'Good work'),'Own course academic results rendered');
 expect(str_contains($html,'1 of 1 lessons completed')&&str_contains($html,'Watch lesson video')&&!str_contains($html,'<script>alert(1)</script>')&&!str_contains($html,'Draft secret'),'Student view renders escaped published content and progress');
 expect(str_contains($html,'Download PDF')&&str_contains($html,'Result: 100%')&&str_contains($html,'Result: 85%')&&!str_contains($html,'Hidden assessment'),'Published assessment results visible; drafts hidden');
 $GLOBALS['fixtureRole']='admin';define('IWORKS_ACADEMY_BUILDER',true);
 ob_start();require __DIR__.'/../views/academy-builder.php';$builder=ob_get_clean();
 expect(str_contains($builder,'Draft secret')&&str_contains($builder,'Save lesson')&&str_contains($builder,'Save course settings'),'Builder renders editable draft content');
 expect(str_contains($builder,'Private PDF materials')&&str_contains($builder,'Add quiz, exam or assignment')&&str_contains($builder,'Save grade'),'Admin authoring and review rendered');
 if(getenv('ACADEMY_VISUAL_FIXTURES')==='1'){
   $dir=__DIR__.'/../docs/visual-qa/academy';if(!is_dir($dir))mkdir($dir,0755,true);
   foreach(['student-learning'=>$html,'admin-builder'=>$builder] as $name=>$body)file_put_contents($dir.'/'.$name.'.html','<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/admin-workspace-refresh.css"><link rel="stylesheet" href="../../../assets/css/academy.css"><style>:root{--brand-primary:#e65c00}body{font:16px system-ui,sans-serif;background:#f6f8fb;margin:0}.fixture-shell{min-width:0;margin-left:240px;padding:24px}@media(max-width:991px){.fixture-shell{margin-left:0;padding:12px}}</style></head><body><main class="fixture-shell"><div class="academy-workspace">'.$body.'</div></main></body></html>');
 }
 echo "PASS LMS draft isolation, approved access, editing, progress, safe links and both real views.\n";
}finally{db()->rollBack();}
