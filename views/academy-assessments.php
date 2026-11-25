<?php
declare(strict_types=1);
function ac_assessment_admin(int $course,int $lesson):void{
 echo '<details class="ac-lms-editor"><summary>Private PDF materials</summary>';
 foreach(academy_materials($lesson) as $m)echo '<p>'.e($m['filename']).' · '.number_format((int)$m['size_bytes']/1024).' KB</p>';
 echo '<form method="post" enctype="multipart/form-data" action="/academy.php?action=builder"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'"><input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="mode" value="material"><input type="hidden" name="lesson" value="'.$lesson.'"><label>PDF, maximum 2 MB<input type="file" name="material" accept=".pdf,application/pdf" required></label><button>Upload material</button></form></details>';
 echo '<details class="ac-lms-editor"><summary>Add quiz, exam or assignment</summary>';ac_build_form($course,'assessment');echo '<input type="hidden" name="lesson" value="'.$lesson.'"><label>Type<select name="kind"><option value="quiz">Quiz</option><option value="exam">Exam</option><option value="assignment">Assignment</option></select></label>';
 ac_input('title','Assessment title');
 echo '<label>Instructions<textarea name="instructions" rows="3" maxlength="10000"></textarea></label><label>Pass mark (%)<input type="number" name="pass_percent" min="1" max="100" value="60" required></label><label>Multiple-choice questions (quiz/exam only)<textarea name="questions" rows="5" maxlength="60000" placeholder="Question | Option 1 | Option 2 | Option 3 | Option 4 | 1"></textarea></label><p>One question per line; four options and the correct option number (1–4). Assignments use instructions only. One submission per student.</p>';
 ac_build_publish(false);echo '<button>Create assessment</button></form></details>';
 foreach(db_all('SELECT id,kind,title,published,pass_percent FROM academy_assessments WHERE lesson_id=:lesson ORDER BY id',['lesson'=>$lesson]) as $a){
  $id=(int)$a['id'];echo '<details class="ac-lms-editor"><summary>'.e(ucfirst($a['kind']).': '.$a['title']).' · '.($a['published']?'Published':'Draft').'</summary>';
  ac_build_form($course,'assessment_publish',$id);echo '<input type="hidden" name="lesson" value="'.$lesson.'">';ac_build_publish((bool)$a['published']);echo '<button>Save visibility</button></form>';
  $submissions=db_all('SELECT s.id,s.response_json,s.score_percent,s.feedback,u.full_name FROM academy_assessment_submissions s JOIN users u ON u.id=s.student_id WHERE s.assessment_id=:id ORDER BY s.id DESC LIMIT 50',['id'=>$id]);
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
 $rows=db_all('SELECT a.id,a.kind,a.title,a.instructions,a.questions_json,a.pass_percent,s.id submission_id,s.score_percent,s.feedback FROM academy_assessments a LEFT JOIN academy_assessment_submissions s ON s.assessment_id=a.id AND s.student_id=:student WHERE a.lesson_id=:lesson AND a.published=1 ORDER BY a.id',compact('student','lesson'));
 foreach($rows as $a){
  echo '<details class="ac-lms-editor"><summary>'.e(ucfirst($a['kind']).': '.$a['title']).'</summary><p>'.nl2br(e($a['instructions'])).'</p><p>Pass mark: '.(int)$a['pass_percent'].'%</p>';
  if($a['submission_id']){
   echo '<p role="status">'.($a['score_percent']===null?'Submitted — awaiting administrator grading.':'Result: '.(int)$a['score_percent'].'% · '.((int)$a['score_percent']>=(int)$a['pass_percent']?'Passed':'Not passed')).'</p>';
   if($a['feedback']!=='')echo '<p>'.nl2br(e($a['feedback'])).'</p>';
  }else{
   ac_form('assessment_submit');echo '<input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="lesson" value="'.$lesson.'"><input type="hidden" name="id" value="'.(int)$a['id'].'">';
   if($a['kind']==='assignment')echo '<label>Your response<textarea name="response" rows="6" maxlength="20000" required></textarea></label>';
   else foreach(json_decode($a['questions_json'],true,512,JSON_THROW_ON_ERROR) as $i=>$q){
    echo '<fieldset class="ac-lms-question"><legend>'.($i+1).'. '.e($q['question']).'</legend>';
    foreach($q['options'] as $j=>$opt)echo '<label class="ac-check"><input type="radio" name="answers['.$i.']" value="'.$j.'" required> '.e($opt).'</label>';
    echo '</fieldset>';
   }
   echo '<p>One submission per assessment. Review your answers before submitting.</p><button>Submit assessment</button></form>';
  }
  echo '</details>';
 }
}
