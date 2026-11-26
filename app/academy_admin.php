<?php
declare(strict_types=1);

/** Scoped administrative corrections; completion and payment remain separate. */
function academy_admin_assert(int $admin):void {
 if(!db_one("SELECT id FROM users WHERE id=:id AND role='admin' AND status='active' AND account_state='active' AND deleted_at IS NULL",['id'=>$admin]))throw new InvalidArgumentException('Administrator authorization required.');
}
function academy_admin_record(int $admin,int $student,string $type,int $id,string $old,string $new,string $reason):void {
 db_execute('INSERT INTO academy_admin_history(student_id,admin_id,entity_type,entity_id,old_state,new_state,reason,changed_at) VALUES(:student,:admin,:type,:id,:old,:new,:reason,:at)',compact('student','admin','type','id','old','new','reason')+['at'=>now()]);
}
function academy_admin_transaction(callable $fn):mixed {
 $own=!db()->inTransaction();if($own)db()->beginTransaction();
 try{$result=$fn();if($own)db()->commit();return $result;}catch(Throwable $e){if($own&&db()->inTransaction())db()->rollBack();throw $e;}
}
function academy_admin_enrollment(int $admin,int $id,string $expected,string $status,string $reason,int $scope=0):array {
 academy_admin_assert($admin);$reason=academy_learning_text($reason,500,true);
 $allowed=['requested'=>['enrolled','cancelled'],'enrolled'=>['requested','cancelled'],'cancelled'=>['requested','enrolled']];
 if(!in_array($status,$allowed[$expected]??[],true))throw new InvalidArgumentException('Choose a permitted enrollment status. Completed training is managed separately.');
 return academy_admin_transaction(function()use($admin,$id,$expected,$status,$reason,$scope){
  $initial=db_one('SELECT student_id FROM academy_enrollments WHERE id=:id',['id'=>$id]);
  if(!$initial||($scope>0&&(int)$initial['student_id']!==$scope))throw new InvalidArgumentException('Enrollment not found for this student.');
  $student=(int)$initial['student_id'];
  $u=db_one("SELECT id,status,account_state,email_verified_at FROM users WHERE id=:id AND role='student' AND deleted_at IS NULL FOR UPDATE",['id'=>$student]);
  $r=db_one('SELECT en.*,c.title,c.active FROM academy_enrollments en JOIN academy_courses c ON c.id=en.course_id WHERE en.id=:id AND en.student_id=:student FOR UPDATE',compact('id','student'));
  if(!$u||!$r||$r['status']!==$expected)throw new InvalidArgumentException('Enrollment changed. Refresh and try again.');
  if($status!=='cancelled'&&(!$r['active']||$u['status']!=='active'||$u['account_state']!=='active'||!$u['email_verified_at']))throw new InvalidArgumentException('The course and verified student account must be active.');
  if(db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',['student'=>$student,'course'=>$r['course_id']]))throw new InvalidArgumentException('Completed training cannot be changed through enrollment status.');
  db_execute('UPDATE academy_enrollments SET status=:status,updated_at=:at WHERE id=:id AND student_id=:student',['status'=>$status,'at'=>now(),'id'=>$id,'student'=>$student]);
  academy_admin_record($admin,$student,'enrollment',$id,$expected,$status,$reason);
  return ['student'=>$student,'title'=>$r['title'],'status'=>$status];
 });
}
function academy_admin_reopen_request(int $admin,int $id,int $attempt,string $reason,int $scope=0):array {
 academy_admin_assert($admin);$reason=academy_learning_text($reason,500,true);
 return academy_admin_transaction(function()use($admin,$id,$attempt,$reason,$scope){
  $initial=db_one('SELECT student_id FROM academy_certification_requests WHERE id=:id',['id'=>$id]);
  if(!$initial||($scope>0&&(int)$initial['student_id']!==$scope))throw new InvalidArgumentException('Request not found for this student.');
  $student=(int)$initial['student_id'];
  $u=db_one("SELECT id FROM users WHERE id=:id AND role='student' AND status='active' AND account_state='active' AND deleted_at IS NULL AND email_verified_at IS NOT NULL FOR UPDATE",['id'=>$student]);
  $r=db_one('SELECT r.*,c.title,c.active FROM academy_certification_requests r JOIN academy_courses c ON c.id=r.course_id WHERE r.id=:id AND r.student_id=:student FOR UPDATE',compact('id','student'));
  if(!$u||!$r||!$r['active']||$r['status']!=='declined'||(int)$r['attempts']!==$attempt)throw new InvalidArgumentException('Only an unchanged declined request for an active student/course can be reopened.');
  if(db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',['student'=>$student,'course'=>$r['course_id']]))throw new InvalidArgumentException('Training is already approved.');
  academy_admin_record($admin,$student,'certification_request',$id,'declined','pending',$reason."\nPrevious decline: ".$r['review_note']);
  db_execute("UPDATE academy_certification_requests SET status='pending',attempts=attempts+1 WHERE id=:id AND student_id=:student",compact('id','student'));
  return ['student'=>$student,'title'=>$r['title']];
 });
}
function academy_admin_edit_student(int $admin,int $student,string $name,string $phone,string $reason):bool {
 academy_admin_assert($admin);$reason=academy_learning_text($reason,500,true);$name=trim($name);$phone=trim($phone);
 if(!academy_valid_name($name)||strlen($phone)<7||strlen($phone)>40||!preg_match('/^[0-9+(). \-]+$/D',$phone))throw new InvalidArgumentException('Enter a valid name and telephone number.');
 return academy_admin_transaction(function()use($admin,$student,$name,$phone,$reason){
  $u=db_one("SELECT id,full_name,phone FROM users WHERE id=:id AND role='student' AND deleted_at IS NULL FOR UPDATE",['id'=>$student]);
  if(!$u)throw new InvalidArgumentException('Student not found.');
  if($u['full_name']===$name&&(string)$u['phone']===$phone)return false;
  db_execute("UPDATE users SET full_name=:name,phone=:phone WHERE id=:id AND role='student'",['name'=>$name,'phone'=>$phone,'id'=>$student]);
  // Record which fields changed, not copies of contact details.
  $fields=implode(', ',array_filter([$u['full_name']!==$name?'name':'',(string)$u['phone']!==$phone?'phone':'']));
  academy_admin_record($admin,$student,'profile',$student,'previous profile','updated '.$fields,$reason);
  return true;
 });
}
function academy_admin_revoke(int $admin,int $id,string $reason,int $scope=0):array {
 academy_admin_assert($admin);$reason=academy_learning_text($reason,500,true);
 return academy_admin_transaction(function()use($admin,$id,$reason,$scope){
  $r=db_one("SELECT id,student_id,course_title,status FROM academy_certificates WHERE id=:id FOR UPDATE",['id'=>$id]);
  if(!$r||$r['status']!=='issued'||($scope>0&&(int)$r['student_id']!==$scope))throw new InvalidArgumentException('Only an issued certificate belonging to this student can be revoked.');
  $student=(int)$r['student_id'];
  db_execute("UPDATE academy_certificates SET status='revoked' WHERE id=:id AND status='issued'",['id'=>$id]);
  academy_admin_record($admin,$student,'certificate',$id,'issued','revoked',$reason);
  return ['student'=>$student,'title'=>$r['course_title']];
 });
}
/** Admin-only read model includes historical progress even after cancellation. */
function academy_admin_progress(int $student,int $course):array {
 $counts=db_one("SELECT COUNT(*) total,COALESCE(SUM(p.lesson_id IS NOT NULL),0) completed FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id LEFT JOIN academy_lesson_progress p ON p.lesson_id=l.id AND p.student_id=:student WHERE m.course_id=:course AND m.published=1 AND l.published=1",compact('student','course'));
 $results=db_all("SELECT a.title,a.kind,a.pass_percent,s.score_percent,s.graded_at,s.submitted_at,r.prior_submission,r.granted_at FROM academy_assessments a JOIN academy_lessons l ON l.id=a.lesson_id JOIN academy_modules m ON m.id=l.module_id LEFT JOIN academy_assessment_submissions s ON s.assessment_id=a.id AND s.student_id=:student LEFT JOIN academy_assessment_retakes r ON r.assessment_id=a.id AND r.student_id=:retake_student WHERE m.course_id=:course AND m.published=1 AND l.published=1 AND a.published=1 ORDER BY m.position,l.position,a.id",['student'=>$student,'retake_student'=>$student,'course'=>$course]);
 $lessons=db_all("SELECT l.title,p.completed_at FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id LEFT JOIN academy_lesson_progress p ON p.lesson_id=l.id AND p.student_id=:student WHERE m.course_id=:course AND m.published=1 AND l.published=1 ORDER BY m.position,l.position,l.id",compact('student','course'));
 return ['total'=>(int)($counts['total']??0),'completed'=>(int)($counts['completed']??0),'results'=>$results,'lessons'=>$lessons];
}

/** First-grade only from the centralized queue. A stale review cannot overwrite
 * a grade saved by another administrator or the course builder. */
function academy_admin_grade_pending(int $admin,int $submission,array $data):array {
 academy_admin_assert($admin);
 $score=filter_var($data['score']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);
 if($score===false)throw new InvalidArgumentException('Enter a score from 0 to 100.');
 $feedback=academy_learning_text($data['feedback']??'',10000);
 return academy_admin_transaction(function()use($admin,$submission,$score,$feedback){
  $r=db_one("SELECT s.id,s.student_id,s.score_percent,a.title,a.pass_percent FROM academy_assessment_submissions s JOIN academy_assessments a ON a.id=s.assessment_id JOIN users u ON u.id=s.student_id WHERE s.id=:id AND a.kind='assignment' AND u.role='student' AND u.deleted_at IS NULL FOR UPDATE",['id'=>$submission]);
  if(!$r||$r['score_percent']!==null)throw new InvalidArgumentException('Assignment unavailable or already graded. Refresh the review queue.');
  $count=db_query('UPDATE academy_assessment_submissions SET score_percent=:score,feedback=:feedback,graded_by=:admin,graded_at=:at WHERE id=:id AND score_percent IS NULL',['score'=>$score,'feedback'=>$feedback,'admin'=>$admin,'at'=>now(),'id'=>$submission])->rowCount();
  if($count!==1)throw new InvalidArgumentException('Assignment already graded. Refresh the review queue.');
  academy_admin_record($admin,(int)$r['student_id'],'assignment',$submission,'awaiting grading',(string)$score.'%','Initial grade saved from review queue.');
  return ['student'=>(int)$r['student_id'],'title'=>$r['title'],'score'=>$score,'passed'=>$score>=(int)$r['pass_percent']];
 });
}

/** Advisory evidence only: never grants course completion or certification.
 * A course without published lessons cannot be marked ready by an empty checklist. */
function academy_completion_summary(int $total,int $completed,array $results):array {
 $total=max(0,$total);$completed=max(0,min($total,$completed));
 $passed=0;$failed=0;$awaiting=0;$missing=0;
 foreach($results as $r){
  if(($r['submitted_at']??null)===null){$missing++;continue;}
  if(($r['score_percent']??null)===null){$awaiting++;continue;}
  if((int)$r['score_percent']>=(int)$r['pass_percent'])$passed++;else $failed++;
 }
 $assessments=count($results);
 return ['lessons'=>$total,'completed'=>$completed,'assessments'=>$assessments,'passed'=>$passed,'failed'=>$failed,'awaiting'=>$awaiting,'missing'=>$missing,'ready'=>$total>0&&$completed===$total&&$passed===$assessments];
}
