<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/academy_learning.php';
require __DIR__.'/../app/academy_assessments.php';
require __DIR__.'/../app/academy_admin.php';
require __DIR__.'/../app/academy_transcript.php';
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
 academy_learning_save($cid,'lesson',['module'=>$mid,'title'=>'Published lesson','position'=>'10','body'=>"<script>alert(1)</script>\n\nStep 1: Plan a campaign\nChoose a goal.\n[[visual|Example campaign|Objective|Leads|Status|Draft]]\n\nQuick knowledge check\n[[check|Which objective fits enquiries?|Leads.]]\n[[source|https://example.com/guide]]",'video_url'=>'https://example.com/video','material_url'=>'https://example.com/resource.pdf','published'=>'1']);
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
 $exam=academy_assessment_create($cid,$lid,['kind'=>'exam','duration_minutes'=>'30','title'=>'Final exam','questions'=>'One plus two? | 2 | 3 | 4 | 5 | 2','pass_percent'=>'60','published'=>'1']);
 $assignment=academy_assessment_create($cid,$lid,['kind'=>'assignment','title'=>'Practical work','instructions'=>'Describe your work.','published'=>'1']);
 $hidden=academy_assessment_create($cid,$lid,['kind'=>'quiz','title'=>'Hidden assessment','questions'=>'Q | A | B | C | D | 1']);
 foreach([[$oid,$quiz],[$sid,$hidden]] as [$who,$aid]){$denied=false;try{academy_assessment_submit($who,$cid,$lid,$aid,['answers'=>['0']]);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Reject unauthorized/draft assessment');}
 expect(academy_assessment_submit($sid,$cid,$lid,$quiz,['answers'=>['0']]),'Quiz submission');
 expect(!academy_assessment_submit($sid,$cid,$lid,$quiz,['answers'=>['1']]),'One attempt only');
 $blocked=false;try{academy_assessment_submit($sid,$cid,$lid,$exam,['answers'=>['0']]);}catch(InvalidArgumentException $e){$blocked=true;}expect($blocked,'Timed exam must be started');
 $blocked=false;try{academy_exam_start($oid,$cid,$lid,$exam);}catch(InvalidArgumentException $e){$blocked=true;}expect($blocked,'Unapproved student cannot start exam');
 expect(academy_exam_start($sid,$cid,$lid,$exam),'Timed exam starts');
 $deadline=(int)db_one('SELECT deadline_epoch FROM academy_exam_starts WHERE assessment_id=:id AND student_id=:student',['id'=>$exam,'student'=>$sid])['deadline_epoch'];
 expect(academy_exam_start($sid,$cid,$lid,$exam),'Reopen active exam');
 expect((int)db_one('SELECT deadline_epoch FROM academy_exam_starts WHERE assessment_id=:id AND student_id=:student',['id'=>$exam,'student'=>$sid])['deadline_epoch']===$deadline,'Reopen does not extend timer');
 db_execute('UPDATE academy_exam_starts SET deadline_epoch=UNIX_TIMESTAMP()-1 WHERE assessment_id=:id AND student_id=:student',['id'=>$exam,'student'=>$sid]);
 expect(!academy_exam_start($sid,$cid,$lid,$exam),'Expired exam cannot restart');
 $blocked=false;try{academy_assessment_submit($sid,$cid,$lid,$exam,['answers'=>['0']]);}catch(InvalidArgumentException $e){$blocked=true;}expect($blocked,'Expired exam cannot submit');
 db_execute('UPDATE academy_exam_starts SET deadline_epoch=:deadline WHERE assessment_id=:id AND student_id=:student',['deadline'=>$deadline,'id'=>$exam,'student'=>$sid]);
 expect(academy_assessment_submit($sid,$cid,$lid,$exam,['answers'=>['0']]),'Exam submission');
 expect(!academy_exam_start($sid,$cid,$lid,$exam),'Submitted exam cannot restart');
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$quiz])['score_percent']===100,'Server graded correct quiz');
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$exam])['score_percent']===0,'Server graded incorrect exam');
 expect(academy_assessment_submit($sid,$cid,$lid,$assignment,['response'=>'Student work <script>']), 'Assignment submitted');
 $sub=db_one('SELECT id,score_percent FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$assignment]);
 expect($sub['score_percent']===null,'Assignment awaits manual grade');
 $admin=(int)db_one("SELECT id FROM users WHERE email='admin@academy.invalid'")['id'];
 academy_assignment_grade($admin,$cid,$lid,(int)$sub['id'],['score'=>'85','feedback'=>'Good work']);
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE id=:id',['id'=>$sub['id']])['score_percent']===85,'Assignment manual grade saved');
 $empty=academy_completion_summary(0,0,[]);expect(!$empty['ready'],'Empty course not marked ready');
 expect(academy_completion_summary(1,1,[])['ready'],'Completed course with no published assessments ready for review');
 $check=academy_completion_summary(2,1,[['submitted_at'=>null,'score_percent'=>null,'pass_percent'=>60],['submitted_at'=>now(),'score_percent'=>null,'pass_percent'=>60],['submitted_at'=>now(),'score_percent'=>59,'pass_percent'=>60],['submitted_at'=>now(),'score_percent'=>60,'pass_percent'=>60]]);
 expect(!$check['ready']&&$check['passed']===1&&$check['failed']===1&&$check['awaiting']===1&&$check['missing']===1,'Checklist distinguishes unsubmitted, ungraded, failed and passed');
 expect(academy_completion_summary(1,1,[['submitted_at'=>now(),'score_percent'=>60,'pass_percent'=>60]])['ready'],'Exact pass mark counts');
 $results=academy_course_results($sid,$cid);
 expect(count($results)===3,'Only published assessment results shown');
 expect(academy_course_results($oid,$cid)===[],'Unapproved student denied results');
 expect(academy_course_results($sid,0)===[],'Wrong course denied results');
 expect(count(array_filter($results,fn($r)=>$r['score_percent']!==null))===3,'All own graded results visible');
 expect(!array_key_exists('questions_json',$results[0]),'Answer keys not exposed');
 $practice=academy_assessment_create($cid,$lid,['kind'=>'quiz','title'=>'Practice quiz','questions'=>"One? | A | B | C | D | 1\nTwo? | A | B | C | D | 2",'published'=>'1']);
 $course=academy_learning_access($sid,$cid);$_GET=['lesson'=>$lid];define('IWORKS_ACADEMY_LEARNING_VIEW',true);
 ob_start();require __DIR__.'/../views/academy-learning.php';$html=ob_get_clean();
 expect(str_contains($html,'How to study your course'),'Learning guidance rendered');
 expect(str_contains($html,'ac-study-directory')&&str_contains($html,'Lesson 1 of 1')&&str_contains($html,'aria-current="page"')&&str_contains($html,'Last published lesson'),'Study navigation shows real lesson position and boundaries');
 expect(str_contains($html,'Academic results')&&str_contains($html,'3 of 4 submitted')&&str_contains($html,'Good work'),'Own course academic results rendered');
 expect(str_contains($html,'1 of 1 lessons completed')&&str_contains($html,'Watch lesson video')&&!str_contains($html,'<script>alert(1)</script>')&&!str_contains($html,'Draft secret'),'Student view renders escaped published content and progress');
 expect(str_contains($html,'class="ac-lesson-visual"')&&str_contains($html,'class="ac-lesson-check"')&&str_contains($html,'Read official guide'),'Illustrated lesson and self-check render in actual view');
 expect(str_contains($html,'Download PDF')&&str_contains($html,'Result: 100%')&&str_contains($html,'Result: 85%')&&!str_contains($html,'Hidden assessment'),'Published assessment results visible; drafts hidden');
 academy_learning_save($cid,'module',['title'=>'Second module','position'=>'30']);
 $GLOBALS['fixtureRole']='admin';define('IWORKS_ACADEMY_BUILDER',true);
 ob_start();require __DIR__.'/../views/academy-builder.php';$builder=ob_get_clean();
 expect(str_contains($builder,'Draft secret')&&str_contains($builder,'Save lesson')&&str_contains($builder,'Save course settings'),'Builder renders editable draft content');
 expect(str_contains($builder,'ac-module-group" open')&&str_contains($builder,'Second module')&&str_contains($builder,'1/2 lessons published'),'Module groups show real publication totals');
 expect(str_contains($builder,'Private PDF materials')&&str_contains($builder,'Add quiz, exam or assignment')&&str_contains($builder,'Save grade'),'Admin authoring and review rendered');
 if(getenv('ACADEMY_VISUAL_FIXTURES')==='1'){
   $dir=__DIR__.'/../docs/visual-qa/academy';if(!is_dir($dir))mkdir($dir,0755,true);
   foreach(['student-learning'=>$html,'admin-builder'=>$builder] as $name=>$body)file_put_contents($dir.'/'.$name.'.html','<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/admin-workspace-refresh.css"><link rel="stylesheet" href="../../../assets/css/academy.css"><link rel="stylesheet" href="../../../assets/css/academy-compact.css"><style>:root{--brand-primary:#e65c00}body{font:16px system-ui,sans-serif;background:#f6f8fb;margin:0}.fixture-shell{min-width:0;margin-left:240px;padding:24px}@media(max-width:991px){.fixture-shell{margin-left:0;padding:12px}}</style></head><body><main class="fixture-shell"><div class="academy-workspace">'.str_replace(['/assets/js/academy-guide.js?v=4','/assets/js/academy-actions.js?v=1'],['../../../assets/js/academy-guide.js?v=4','../../../assets/js/academy-actions.js?v=1'],$body).'</div></main></body></html>');
 }
 // Retake only after failure; preserve original attempt and prevent replay.
 $denied=false;try{academy_grant_retake($admin,$sid,$cid,$lid,$quiz,'Passed quiz');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Passed result cannot be reopened');
 $denied=false;try{academy_grant_retake($sid,$sid,$cid,$lid,$exam,'Unauthorized');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Student cannot grant retake');
 expect(academy_grant_retake($admin,$sid,$cid,$lid,$exam,'Approved after failed result'),'Admin approves one retake');
 $archived=db_one('SELECT prior_submission,reason,granted_by FROM academy_assessment_retakes WHERE assessment_id=:id AND student_id=:student',['id'=>$exam,'student'=>$sid]);
 expect((int)json_decode($archived['prior_submission'],true)['score_percent']===0&&(int)$archived['granted_by']===$admin,'Original failed result retained in audit');
 expect(academy_attempt_generation($sid,$exam)===1,'New attempt generation');
 $denied=false;try{academy_exam_start($sid,$cid,$lid,$exam);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Stale form cannot start retake');
 expect(academy_exam_start($sid,$cid,$lid,$exam,1),'Approved retake starts fresh timer');
 expect(academy_assessment_submit($sid,$cid,$lid,$exam,['answers'=>['1'],'attempt_generation'=>'1']),'Retake submission');
 $denied=false;try{academy_grant_retake($admin,$sid,$cid,$lid,$exam,'Again');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'No second retake');
 $expired=academy_assessment_create($cid,$lid,['kind'=>'exam','duration_minutes'=>'10','title'=>'Expired fixture','questions'=>'Q | A | B | C | D | 1','published'=>'1']);
 expect(academy_exam_start($sid,$cid,$lid,$expired),'Start expiring exam');
 db_execute('UPDATE academy_exam_starts SET deadline_epoch=UNIX_TIMESTAMP()-1 WHERE assessment_id=:id AND student_id=:student',['id'=>$expired,'student'=>$sid]);
 expect(academy_grant_retake($admin,$sid,$cid,$lid,$expired,'Expired during exam'),'Expired exam eligible');
 expect(academy_exam_start($sid,$cid,$lid,$expired,1),'Expired exam receives a new timer');
 $en=db_one('SELECT id,status FROM academy_enrollments WHERE student_id=:student AND course_id=:course',['student'=>$oid,'course'=>$cid]);
 $enid=(int)$en['id'];
 expect($en['status']==='requested','Independent requested enrollment fixture');
 academy_admin_enrollment($admin,$enid,'requested','enrolled','Approve fixture',$oid);
 expect(academy_learning_complete($oid,$cid,$lid),'Record progress before cancellation');
 academy_admin_enrollment($admin,$enid,'enrolled','cancelled','Fixture cancellation',$oid);
 expect(academy_learning_access($oid,$cid)===null,'Cancellation blocks course access');
 expect(academy_admin_progress($oid,$cid)['completed']===1,'Admin sees preserved cancelled progress');
 $denied=false;try{academy_admin_enrollment($admin,$enid,'cancelled','enrolled','Wrong student',$sid);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Cross-student correction denied');
 academy_admin_enrollment($admin,$enid,'cancelled','requested','Reopen mistaken cancellation',$oid);
 expect(academy_learning_access($oid,$cid)===null,'Pending review does not grant access');
 academy_admin_enrollment($admin,$enid,'requested','enrolled','Approve restored enrollment',$oid);
 expect(academy_learning_access($oid,$cid)!==null&&academy_admin_progress($oid,$cid)['completed']===1,'Access restored without losing progress');
 $denied=false;try{academy_admin_enrollment($admin,$enid,'cancelled','enrolled','Stale form',$oid);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Stale status rejected');
 expect((int)db_one("SELECT COUNT(*) n FROM academy_admin_history WHERE student_id=:student AND entity_type='enrollment' AND entity_id=:id",['student'=>$oid,'id'=>$enid])['n']===4,'Each correction audited');
 // Private assignment file is atomic with its owning submission.
 $attachment=['filename'=>'student-work.pdf','content'=>$pdf];
 $fileAssignment=academy_assessment_create($cid,$lid,['kind'=>'assignment','title'=>'PDF practical','instructions'=>'Submit your work.','published'=>'1']);
 $denied=false;try{academy_assessment_submit($sid,$cid,$lid,$fileAssignment,['response'=>'']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Empty assignment rejected');
 $denied=false;try{academy_assessment_submit($sid,$cid,$lid,$fileAssignment,['response'=>''],['filename'=>'fake.pdf','content'=>'<script>fake</script>']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Invalid PDF rejected');
 expect(!db_one('SELECT id FROM academy_assessment_submissions WHERE assessment_id=:id',['id'=>$fileAssignment]),'Invalid file creates no submission');
 expect(academy_assessment_submit($sid,$cid,$lid,$fileAssignment,['response'=>''],$attachment),'PDF-only submission accepted');
 $fid=(int)db_one('SELECT id FROM academy_assessment_submissions WHERE assessment_id=:id AND student_id=:student',['id'=>$fileAssignment,'student'=>$sid])['id'];
 expect(academy_assignment_file_download($sid,$cid,$lid,$fid)['content']===$pdf,'Owner receives exact PDF bytes');
 expect(academy_assignment_file_download($oid,$cid,$lid,$fid)===null,'Other enrolled student denied');
 expect(academy_assignment_file_download($sid,$cid,$draft,$fid)===null,'Wrong lesson denied');
 expect(academy_assignment_file_download($sid,$cid,$lid,$fid,true)===null,'Forged admin flag denied');
 expect(academy_assignment_file_download($admin,$cid,$lid,$fid,true)['filename']==='student-work.pdf','Administrator can review PDF');
 expect(!academy_assessment_submit($sid,$cid,$lid,$fileAssignment,['response'=>'Duplicate'],$attachment),'Duplicate submission denied');
 expect((int)db_one('SELECT COUNT(*) n FROM academy_assignment_files WHERE submission_id=:id',['id'=>$fid])['n']===1,'Exactly one file per submission');
 academy_assignment_grade($admin,$cid,$lid,$fid,['score'=>'90','feedback'=>'PDF reviewed']);
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE id=:id',['id'=>$fid])['score_percent']===90,'Existing grading works with attachment');
 $mixed=academy_assessment_create($cid,$lid,['kind'=>'assignment','title'=>'Mixed practical','instructions'=>'Submit text and file.','published'=>'1']);
 expect(academy_assessment_submit($sid,$cid,$lid,$mixed,['response'=>'Written explanation'],$attachment),'Text and PDF accepted together');
 $denied=false;try{academy_assessment_submit($sid,$cid,$lid,$practice,['answers'=>['0','1']],$attachment);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Quiz attachments rejected');
 ob_start();ac_assessment_student($sid,$cid,$lid);$studentFiles=ob_get_clean();
 expect(str_contains($studentFiles,'Your submitted PDF')&&str_contains($studentFiles,'student-work.pdf'),'Student attachment visible in existing popup');
 ob_start();ac_assessment_admin($cid,$lid);$adminFiles=ob_get_clean();
 expect(str_contains($adminFiles,'Download submitted PDF')&&str_contains($adminFiles,'student-work.pdf'),'Administrator attachment visible alongside grading');
 $transcript=academy_transcript_data($sid,$sid,$cid);
 expect($transcript!==null&&$transcript['completed']===1&&$transcript['total']===1,'Own transcript contains real progress');
 expect(!str_contains(json_encode($transcript),'Hidden assessment'),'Draft assessment excluded');
 expect(str_contains(json_encode($transcript),'90% - Passed')&&str_contains(json_encode($transcript),'retake approved'),'Real grades and retake history included');
 expect(academy_transcript_data($oid,$sid,$cid)===null,'Cross-student transcript denied');
 expect(academy_transcript_data($sid,$sid,0)===null,'Wrong course denied');
 expect(academy_transcript_data($admin,$sid,$cid,true)!==null,'Administrator can access student transcript');
 $denied=false;try{academy_transcript_data($sid,$sid,$cid,true);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Forged administrator transcript denied');
 academy_admin_enrollment($admin,$enid,'enrolled','cancelled','Check cancelled transcript',$oid);
 expect(academy_transcript_data($oid,$oid,$cid)===null,'Cancelled student transcript denied');
 expect(academy_transcript_data($admin,$oid,$cid,true)!==null,'Administrator retains historical transcript');
 // Central queue: first-grade-only, no stale overwrites, audit.
 $mid=(int)db_one('SELECT id FROM academy_assessment_submissions WHERE assessment_id=:id AND student_id=:student',['id'=>$mixed,'student'=>$sid])['id'];
 $denied=false;try{academy_admin_grade_pending($sid,$mid,['score'=>'75']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Student cannot grade pending work');
 $denied=false;try{academy_admin_grade_pending($admin,$mid,['score'=>'101']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Invalid queue grade rejected');
 $grade=academy_admin_grade_pending($admin,$mid,['score'=>'78','feedback'=>'Reviewed in queue']);
 expect($grade['student']===$sid&&$grade['score']===78&&$grade['passed'],'Queue grade saved for correct student');
 $denied=false;try{academy_admin_grade_pending($admin,$mid,['score'=>'10']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Stale queue form cannot overwrite grade');
 expect((int)db_one('SELECT score_percent FROM academy_assessment_submissions WHERE id=:id',['id'=>$mid])['score_percent']===78,'Grade preserved');
 expect((int)db_one("SELECT COUNT(*) n FROM academy_admin_history WHERE entity_type='assignment' AND entity_id=:id AND student_id=:student",['id'=>$mid,'student'=>$sid])['n']===1,'Initial grade audited');
 // Permanent deletion is explicitly confirmed and restricted to eligible students.
 db_execute("INSERT INTO users(full_name,email,phone,password_hash,role,status,account_state,email_verified_at,created_at) VALUES('Deletion fixture','deletion-fixture@academy.invalid','1234567890',:hash,'student','active','active',:verified,:created)",['hash'=>password_hash('fixture-password-123',PASSWORD_DEFAULT),'verified'=>now(),'created'=>now()]);
 $deleteId=db_last_id();
 db_execute("INSERT INTO academy_enrollments(student_id,course_id,status,created_at,updated_at) VALUES(:student,:course,'enrolled',:created,:updated)",['student'=>$deleteId,'course'=>$cid,'created'=>now(),'updated'=>now()]);
 expect(academy_learning_complete($deleteId,$cid,$lid),'Deletion fixture records progress');
 expect(academy_assessment_submit($deleteId,$cid,$lid,$mixed,['response'=>'Fixture deletion'],$attachment),'Deletion fixture has private assignment');
 $deleteSubmission=(int)db_one('SELECT id FROM academy_assessment_submissions WHERE student_id=:student AND assessment_id=:assessment',['student'=>$deleteId,'assessment'=>$mixed])['id'];
 $denied=false;try{academy_admin_delete_student($sid,$deleteId,'DELETE','deletion-fixture@academy.invalid');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Nonadministrator deletion denied');
 $denied=false;try{academy_admin_delete_student($admin,$deleteId,'DELETE','wrong@academy.invalid');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Wrong email denied');
 $denied=false;try{academy_admin_delete_student($admin,$deleteId,'delete','deletion-fixture@academy.invalid');}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Exact confirmation required');
 $denied=false;try{academy_admin_delete_student($admin,$sid,'DELETE',$user['email']);}catch(InvalidArgumentException $e){$denied=true;}expect($denied,'Approved training protected');
 academy_admin_delete_student($admin,$deleteId,'DELETE','deletion-fixture@academy.invalid');
 expect(!db_one('SELECT id FROM users WHERE id=:id',['id'=>$deleteId]),'Student account physically removed');
 expect(!db_one('SELECT student_id FROM academy_lesson_progress WHERE student_id=:id',['id'=>$deleteId]),'Learning progress removed');
 expect(!db_one('SELECT submission_id FROM academy_assignment_files WHERE submission_id=:id',['id'=>$deleteSubmission]),'Private attachment removed');
 expect(!db_one('SELECT id FROM academy_assessment_submissions WHERE student_id=:id',['id'=>$deleteId]),'Assignment submission removed');
 expect((int)db_one('SELECT admin_id FROM academy_student_deletions WHERE deleted_student_id=:id',['id'=>$deleteId])['admin_id']===$admin,'Minimal deletion receipt preserved');
 academy_learning_save($cid,'course',['description'=>'Temporarily unpublished']);
 expect(academy_transcript_data($sid,$sid,$cid)['results']===[],'Unpublished learning content not exposed to student');
 expect(str_contains($html,'Download academic transcript (PDF)'),'Course transcript link present');
 expect(str_contains($html,'Completion checklist')&&str_contains($html,'Learning checklist in progress')&&str_contains($html,'Not submitted: 1'),'Student sees real remaining activity without automatic approval');
 echo "PASS LMS draft isolation, approved access, editing, progress, safe links and both real views.\n";
}finally{db()->rollBack();}
