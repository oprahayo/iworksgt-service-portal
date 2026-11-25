<?php
declare(strict_types=1);
function academy_request_submit(int $student,int $course,string $name,string $note):bool{
 $name=trim($name);$note=trim($note);
 if(!academy_valid_name($name)||mb_strlen($note)>2000)throw new InvalidArgumentException('Enter a valid certificate name and a note of up to 2,000 characters.');
 db()->beginTransaction();
 try{
  $u=db_one("SELECT id FROM users WHERE id=:id AND role='student' AND status='active' AND account_state='active' AND deleted_at IS NULL AND email_verified_at IS NOT NULL FOR UPDATE",['id'=>$student]);
  $c=db_one('SELECT id,title FROM academy_courses WHERE id=:id AND active=1',['id'=>$course]);
  if(!$u||!$c)throw new InvalidArgumentException('Student or course unavailable.');
  if(db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',compact('student','course')))throw new InvalidArgumentException('This course is already approved. Open My Certificates.');
  $r=db_one('SELECT id,status FROM academy_certification_requests WHERE student_id=:student AND course_id=:course FOR UPDATE',compact('student','course'));
  if($r&&$r['status']!=='declined'){db()->commit();return false;}
  if($r)db_execute("UPDATE academy_certification_requests SET student_name=:name,student_note=:note,status='pending',submitted_at=:at,attempts=attempts+1 WHERE id=:id",['name'=>$name,'note'=>$note,'at'=>now(),'id'=>$r['id']]);
  else db_execute("INSERT INTO academy_certification_requests(student_id,course_id,student_name,student_note,review_note,submitted_at) VALUES(:student,:course,:name,:note,'',:at)",['student'=>$student,'course'=>$course,'name'=>$name,'note'=>$note,'at'=>now()]);
  db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
 try{create_notification($student,'Certification request received',$c['title'].' — awaiting administrator review.','/academy.php?action=certificates');}catch(Throwable $e){error_log('Academy request notice deferred');}
 try{notify_roles(['admin'],'New certification request',$c['title'].' — review the student request.','/academy.php?action=admin&tab=requests');}catch(Throwable $e){error_log('Academy admin request notice deferred');}
 return true;
}
function academy_request_review(int $admin,int $id,string $decision,string $duration,string $note,?int $attempt=null):bool{
 $note=trim($note);$weeks=academy_duration_weeks($duration);
 if(!in_array($decision,['approved','declined'],true)||mb_strlen($note)>2000||($decision==='declined'&&$note==='')||($decision==='approved'&&$weeks===null))throw new InvalidArgumentException('Enter a valid duration for approval or a reason for decline.');
 $initial=db_one('SELECT student_id FROM academy_certification_requests WHERE id=:id',['id'=>$id]);
 if(!$initial)return false;
 $student=(int)$initial['student_id'];
 db()->beginTransaction();
 try{
  $u=db_one("SELECT id FROM users WHERE id=:id AND role='student' AND deleted_at IS NULL AND status='active' AND account_state='active' AND email_verified_at IS NOT NULL FOR UPDATE",['id'=>$student]);
  $r=db_one('SELECT r.*,c.title,c.active FROM academy_certification_requests r JOIN academy_courses c ON c.id=r.course_id WHERE r.id=:id AND r.student_id=:student FOR UPDATE',compact('id','student'));
  if(!$u||!$r||$r['status']!=='pending'||($attempt!==null&&$attempt!==(int)$r['attempts'])){db()->commit();return false;}
  $course=(int)$r['course_id'];
  if($decision==='approved'){
   if(!$r['active']||db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',compact('student','course')))throw new InvalidArgumentException('Course unavailable or already approved.');
   db_execute('INSERT INTO academy_graduates(student_id,course_id,duration,approved_by,approved_at) VALUES(:student,:course,:duration,:admin,:at)',['student'=>$student,'course'=>$course,'duration'=>academy_duration_label($weeks),'admin'=>$admin,'at'=>now()]);
   db_execute("INSERT INTO academy_enrollments(student_id,course_id,status,created_at,updated_at) VALUES(:student,:course,'completed',:created,:updated) ON DUPLICATE KEY UPDATE status='completed',updated_at=VALUES(updated_at)",['student'=>$student,'course'=>$course,'created'=>now(),'updated'=>now()]);
  }
  db_execute("UPDATE academy_certification_requests SET status=:decision,review_note=:note,reviewed_at=:at,reviewed_by=:admin WHERE id=:id AND status='pending'",['decision'=>$decision,'note'=>$note,'at'=>now(),'admin'=>$admin,'id'=>$id]);
  db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
 $title=$decision==='approved'?'Certification approved':'Certification declined';
 $message=$r['title'].' — '.($decision==='approved'?'Approved. Preview your certificate and pay when ready.':'Declined. Review the reason and resubmit if appropriate.').($note!==''?"\n".$note:'');
 academy_notice($student,$title,$message,'certificates');
 return true;
}
function academy_request_name(int $student,int $graduate,string $fallback):string{
 $r=db_one("SELECT r.student_name FROM academy_graduates g JOIN academy_certification_requests r ON r.student_id=g.student_id AND r.course_id=g.course_id AND r.status='approved' WHERE g.id=:graduate AND g.student_id=:student",compact('graduate','student'));
 return $r?(string)$r['student_name']:$fallback;
}
