<?php
declare(strict_types=1);
function ac_assessment_admin(int $course,int $lesson):void{
 echo '<details class="ac-lms-editor" data-ac-popup><summary>Private PDF materials</summary>';
 foreach(academy_materials($lesson) as $m)echo '<p>'.e($m['filename']).' · '.number_format((int)$m['size_bytes']/1024).' KB</p>';
 echo '<form method="post" enctype="multipart/form-data" action="/academy.php?action=builder"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'"><input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="mode" value="material"><input type="hidden" name="lesson" value="'.$lesson.'"><label>PDF, maximum 2 MB<input type="file" name="material" accept=".pdf,application/pdf" required></label><button>Upload material</button></form></details>';
 echo '<details class="ac-lms-editor" data-ac-popup><summary>Add quiz, exam or assignment</summary>';ac_build_form($course,'assessment');echo '<input type="hidden" name="lesson" value="'.$lesson.'"><div data-ac-author><label>Type<select name="kind" data-ac-kind><option value="quiz">Quiz</option><option value="exam">Exam</option><option value="assignment">Assignment</option></select></label>';
 ac_input('title','Assessment title');
 echo '<label>Instructions<textarea name="instructions" rows="3" maxlength="10000"></textarea></label><label>Pass mark (%)<input type="number" name="pass_percent" min="1" max="100" value="60" required></label><label data-ac-exam>Exam duration in minutes (0 = untimed)<input type="number" name="duration_minutes" min="0" max="240" value="0"></label><label data-ac-questions>Multiple-choice questions (quiz/exam only)<textarea name="questions" rows="5" maxlength="60000" placeholder="Question | Option 1 | Option 2 | Option 3 | Option 4 | 1"></textarea></label><p>One question per line; four options and the correct option number (1–4). Assignments use instructions only. One initial attempt per student. Only an administrator may approve one retake after a failed quiz/exam or expired timed exam.</p></div>';
 ac_build_publish(false);echo '<button>Create assessment</button></form></details>';
 foreach(db_all('SELECT id,kind,title,published,pass_percent,duration_minutes FROM academy_assessments WHERE lesson_id=:lesson ORDER BY id',['lesson'=>$lesson]) as $a){
  $id=(int)$a['id'];echo '<details class="ac-lms-editor" data-ac-popup><summary>'.e(ucfirst($a['kind']).': '.$a['title']).' · '.($a['published']?'Published':'Draft').'</summary>';
  ac_build_form($course,'assessment_publish',$id);echo '<input type="hidden" name="lesson" value="'.$lesson.'">';ac_build_publish((bool)$a['published']);echo '<button>Save visibility</button></form>';
  if((int)$a['duration_minutes']>0)echo '<p>Time limit: '.(int)$a['duration_minutes'].' minutes.</p>';
  $submissions=db_all('SELECT s.id,s.response_json,s.score_percent,s.feedback,u.full_name FROM academy_assessment_submissions s JOIN users u ON u.id=s.student_id WHERE s.assessment_id=:id ORDER BY s.id DESC LIMIT 50',['id'=>$id]);
  if(in_array($a['kind'],['quiz','exam'],true)){
   $eligible=db_all("SELECT DISTINCT u.id,u.full_name,s.score_percent,t.deadline_epoch FROM users u JOIN academy_enrollments en ON en.student_id=u.id AND en.course_id=:course AND en.status IN ('enrolled','completed') LEFT JOIN academy_assessment_submissions s ON s.student_id=u.id AND s.assessment_id=:submission_assessment LEFT JOIN academy_exam_starts t ON t.student_id=u.id AND t.assessment_id=:timer_assessment LEFT JOIN academy_assessment_retakes r ON r.student_id=u.id AND r.assessment_id=:retake_assessment WHERE u.role='student' AND u.status='active' AND u.account_state='active' AND u.deleted_at IS NULL AND u.email_verified_at IS NOT NULL AND r.assessment_id IS NULL AND ((s.score_percent IS NOT NULL AND s.score_percent<:pass) OR (s.id IS NULL AND t.deadline_epoch<=UNIX_TIMESTAMP())) ORDER BY u.full_name LIMIT 50",['course'=>$course,'submission_assessment'=>$id,'timer_assessment'=>$id,'retake_assessment'=>$id,'pass'=>$a['pass_percent']]);
   if($a['published']&&$eligible){
    echo '<h4>Eligible retakes</h4><p>One additional attempt only. The original result and timer will be retained in the audit history.</p>';
    foreach($eligible as $candidate){
     echo '<div class="ac-lms-submission"><strong>'.e($candidate['full_name']).'</strong><p>'.($candidate['score_percent']===null?'Exam expired':'Failed result: '.(int)$candidate['score_percent'].'%').'</p>';
     ac_build_form($course,'grant_retake',$id);echo '<input type="hidden" name="lesson" value="'.$lesson.'"><input type="hidden" name="student" value="'.(int)$candidate['id'].'"><label>Reason for retake<textarea name="reason" rows="2" maxlength="500" required></textarea></label><button>Approve one retake</button></form></div>';
    }
   }
   $history=db_all('SELECT r.granted_at,r.reason,r.prior_submission,u.full_name FROM academy_assessment_retakes r JOIN users u ON u.id=r.student_id WHERE r.assessment_id=:id ORDER BY r.granted_at DESC LIMIT 50',['id'=>$id]);
   if($history){echo '<h4>Retake history</h4>';foreach($history as $r){$prior=json_decode((string)$r['prior_submission'],true);echo '<div class="ac-lms-submission"><strong>'.e($r['full_name']).'</strong><p>'.(is_array($prior)?'Original result: '.(int)$prior['score_percent'].'%':'Original exam expired').'</p><p>'.e($r['reason']).' · '.e($r['granted_at']).'</p></div>';}}
  }
  echo '<h4>Recent submissions (up to 50)</h4>';if(!$submissions)echo '<p>No submissions yet.</p>';
  foreach($submissions as $s){
   echo '<div class="ac-lms-submission"><strong>'.e($s['full_name']).'</strong><p>'.($s['score_percent']===null?'Awaiting grading':(int)$s['score_percent'].'% · '.((int)$s['score_percent']>=(int)$a['pass_percent']?'Passed':'Not passed')).'</p>';
   if($a['kind']==='assignment'){
    $response=json_decode($s['response_json'],true);echo '<p class="ac-lesson-body">'.nl2br(e(is_string($response)?$response:'')).'</p>';
    ac_build_form($course,'grade',(int)$s['id']);echo '<input type="hidden" name="lesson" value="'.$lesson.'"><label>Score (%)<input type="number" name="score" min="0" max="100" required value="'.e((string)($s['score_percent']??'')).'"></label><label>Feedback<textarea name="feedback" maxlength="10000" rows="3">'.e($s['feedback']).'</textarea></label><button>Save grade</button></form>';
   }
   echo '</div>';
  }
  echo '</details>';
 }
}
function ac_assessment_student(int $student,int $course,int $lesson):void{
 foreach(academy_materials($lesson) as $m)echo '<p><a class="ac-student-link" href="/academy.php?action=material&amp;course='.$course.'&amp;lesson='.$lesson.'&amp;id='.(int)$m['id'].'">'.e($m['filename']).' · Download PDF</a></p>';
 $rows=db_all('SELECT a.id,a.kind,a.title,a.instructions,a.questions_json,a.pass_percent,a.duration_minutes,t.deadline_epoch,UNIX_TIMESTAMP() server_epoch,s.id submission_id,s.score_percent,s.feedback,r.granted_at retake_granted_at,r.prior_submission FROM academy_assessments a LEFT JOIN academy_assessment_submissions s ON s.assessment_id=a.id AND s.student_id=:student LEFT JOIN academy_exam_starts t ON t.assessment_id=a.id AND t.student_id=:timer_student LEFT JOIN academy_assessment_retakes r ON r.assessment_id=a.id AND r.student_id=:retake_student WHERE a.lesson_id=:lesson AND a.published=1 ORDER BY a.id',['student'=>$student,'timer_student'=>$student,'retake_student'=>$student,'lesson'=>$lesson]);
 foreach($rows as $a){
  $generation=$a['retake_granted_at']===null?0:1;
  echo '<details class="ac-lms-editor" data-ac-popup id="ac-assessment-'.(int)$a['id'].'"><summary>'.e(ucfirst($a['kind']).': '.$a['title']).'</summary><p>'.nl2br(e($a['instructions'])).'</p><p>Pass mark: '.(int)$a['pass_percent'].'%</p>';
  if($generation===1){$prior=json_decode((string)$a['prior_submission'],true);echo '<p role="status">Additional attempt approved. '.(is_array($prior)?'Original result: '.(int)$prior['score_percent'].'%.':'Original exam expired.').'</p>';}
  if($a['submission_id']){
   echo '<p role="status">'.($a['score_percent']===null?'Submitted — awaiting administrator grading.':'Result: '.(int)$a['score_percent'].'% · '.((int)$a['score_percent']>=(int)$a['pass_percent']?'Passed':'Not passed')).'</p>';
   if($a['feedback']!=='')echo '<p>'.nl2br(e($a['feedback'])).'</p>';
  }elseif((int)$a['duration_minutes']>0&&$a['deadline_epoch']===null){
   echo '<p>Time limit: '.(int)$a['duration_minutes'].' minutes. Starting the exam begins your current attempt. Closing this window or refreshing does not pause the clock.</p>';
   ac_form('exam_start');echo '<input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="lesson" value="'.$lesson.'"><input type="hidden" name="id" value="'.(int)$a['id'].'"><input type="hidden" name="attempt_generation" value="'.$generation.'"><button>Start timed exam</button></form>';
  }elseif((int)$a['duration_minutes']>0&&(int)$a['deadline_epoch']<=(int)$a['server_epoch']){
   echo '<p role="status">Exam time expired. An administrator must approve any eligible retake.</p>';
  }else{
   if((int)$a['duration_minutes']>0)echo '<p class="ac-exam-clock" role="timer" data-exam-seconds="'.max(0,(int)$a['deadline_epoch']-(int)$a['server_epoch']).'">Time remaining: '.max(0,(int)$a['deadline_epoch']-(int)$a['server_epoch']).' seconds</p>';
   ac_form('assessment_submit');echo '<input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="lesson" value="'.$lesson.'"><input type="hidden" name="id" value="'.(int)$a['id'].'"><input type="hidden" name="attempt_generation" value="'.$generation.'">';
   if($a['kind']==='assignment')echo '<label>Your response<textarea name="response" rows="6" maxlength="20000" required></textarea></label>';
   else {echo '<div data-ac-quiz>';foreach(json_decode($a['questions_json'],true,512,JSON_THROW_ON_ERROR) as $i=>$q){
    echo '<fieldset class="ac-lms-question"><legend>'.($i+1).'. '.e($q['question']).'</legend>';
    foreach($q['options'] as $j=>$opt)echo '<label class="ac-check"><input type="radio" name="answers['.$i.']" value="'.$j.'" required> '.e($opt).'</label>';
    echo '</fieldset>';
   }echo '</div>';}
   echo '<p>One submission per attempt. Review your answers before submitting.</p><button>Submit assessment</button></form>';
  }
  echo '</details>';
 }
}
