<?php
declare(strict_types=1);
if(!defined('IWORKS_ACADEMY_LEARNING_VIEW')||!has_role(['student'])){http_response_code(403);exit;}
require_once __DIR__.'/academy-guide.php';
$cid=(int)$course['id'];
$lessons=academy_learning_lessons((int)$user['id'],$cid);
$done=count(array_filter($lessons,fn($l)=>(int)$l['completed']===1));
$total=count($lessons);
$lid=(int)($_GET['lesson']??0);
if(!$lid&&$lessons){foreach($lessons as $l)if(!$l['completed']){$lid=(int)$l['id'];break;}if(!$lid)$lid=(int)$lessons[0]['id'];}
$lesson=$lid?academy_learning_lesson((int)$user['id'],$cid,$lid):null;
if($lid&&!$lesson)http_response_code(404);
ac_head($course['title']);
echo '<div class="ac-student ac-lms"><a class="ac-student-link" href="/academy.php?action=courses">Back to My Courses</a><h1>'.e($course['title']).'</h1>';
ac_guide('learn',(int)$user['id']);
echo '<p><a class="ac-student-link" href="/academy.php?action=transcript&amp;course='.$cid.'">Download academic transcript (PDF)</a></p>';
if($notice=flash('academy_student_notice'))echo '<p class="ac-student-notice" role="status">'.e($notice).'</p>';
if(!$total){echo '<section class="ac-student-panel"><h2>Your learning area</h2><p>Course lessons and modules have not been published yet. You will be able to access them here when available.</p></section>';}
else{
    $pct=(int)round(100*$done/$total);
    $currentIndex=null;foreach($lessons as $i=>$l)if((int)$l['id']===$lid){$currentIndex=$i;break;}
    echo '<div class="ac-study-progress"><div><strong>'.$done.' of '.$total.' lessons completed</strong><span>'.$pct.'%</span></div><progress value="'.$done.'" max="'.$total.'" aria-label="Course lesson progress"></progress></div>';
    echo '<div class="ac-study-layout"><section class="ac-student-panel ac-study-outline"><details class="ac-study-directory"><summary data-ac-icon="courses">Course content <small>'.$total.' lessons</small><span class="ac-course-chevron" aria-hidden="true">›</span></summary>';
    if(!empty($course['description']))echo '<p>'.e((string)$course['description']).'</p>';
    $module=0;
    foreach($lessons as $l){
        if($module!==(int)$l['module_id']){$module=(int)$l['module_id'];echo '<h3>'.e($l['module_title']).'</h3>';}
        $selected=(int)$l['id']===$lid;
        echo '<a class="ac-lms-lesson'.($selected?' active':'').'"'.($selected?' aria-current="page"':'').' href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$l['id'].'"><span data-ac-icon="'.($l['completed']?'check':'lesson').'">'.e($l['title']).'</span><small>'.($l['completed']?'Completed':($selected?'Current':'Open')).'</small></a>';
    }
    echo '</details></section><section class="ac-student-panel ac-study-reading">';
    if(!$lesson)echo '<p>Lesson unavailable. Choose a published lesson.</p>';
    else{
        echo '<div class="ac-study-heading"><small>Lesson '.($currentIndex+1).' of '.$total.' · '.e($lesson['module_title']).'</small><h2>'.e($lesson['title']).'</h2></div><div class="ac-lesson-body">'.nl2br(e($lesson['body'])).'</div>';
        if($embed=academy_video_embed($lesson['video_url']))echo '<div class="ac-lms-video"><iframe src="'.e($embed).'" title="Lesson video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
        if($lesson['video_url']!=='')echo '<p><a class="ac-student-link" href="'.e(academy_learning_url($lesson['video_url'])).'" target="_blank" rel="noopener noreferrer">Watch lesson video</a></p>';
        if($lesson['material_url']!=='')echo '<p><a class="ac-student-link" href="'.e(academy_learning_url($lesson['material_url'])).'" target="_blank" rel="noopener noreferrer">Open learning material</a></p>';
        ac_assessment_student((int)$user['id'],$cid,$lid);
        $completed=false;foreach($lessons as $l)if((int)$l['id']===$lid)$completed=(bool)$l['completed'];
        if($completed)echo '<p>Lesson completed.</p>';
        else{ac_form('lesson_complete');echo '<input type="hidden" name="course" value="'.$cid.'"><input type="hidden" name="lesson" value="'.$lid.'"><button data-ac-icon="check">Mark as complete</button></form>';}
        echo '<nav class="ac-study-nav" aria-label="Lesson navigation">';
        if($currentIndex!==null&&$currentIndex>0)echo '<a class="ac-student-link" href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$lessons[$currentIndex-1]['id'].'">‹ Previous lesson</a>';else echo '<span></span>';
        if($currentIndex!==null&&isset($lessons[$currentIndex+1]))echo '<a class="ac-student-link" data-ac-icon="arrow" href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$lessons[$currentIndex+1]['id'].'">Next lesson</a>';
        else echo '<span class="ac-study-end">Last published lesson</span>';
        echo '</nav>';
    }
    echo '</section></div>';
    $results=academy_course_results((int)$user['id'],$cid);
    if($results){
        $submitted=count(array_filter($results,fn($r)=>$r['submitted_at']!==null));
        $graded=count(array_filter($results,fn($r)=>$r['score_percent']!==null));
        echo '<section class="ac-student-panel ac-results-panel"><details><summary class="ac-cert-summary"><span class="ac-cert-summary-copy"><strong>Academic results</strong><small>'.$submitted.' of '.count($results).' submitted · '.$graded.' graded</small></span><span class="ac-cert-chevron" aria-hidden="true"></span></summary><div class="ac-results-list">';
        foreach($results as $r){
            $result=$r['submitted_at']===null?'Not submitted':($r['score_percent']===null?'Awaiting grading':(int)$r['score_percent'].'% · '.((int)$r['score_percent']>=(int)$r['pass_percent']?'Passed':'Not passed'));
            echo '<article class="ac-result-row"><div><strong>'.e($r['title']).'</strong><small>'.e(ucfirst($r['kind']).' · '.$r['lesson_title']).'</small><small>Pass mark: '.(int)$r['pass_percent'].'%</small></div><span>'.e($result).'</span><a class="ac-student-link" href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$r['lesson_id'].'">View lesson</a>';
            if($r['feedback']!==' '&&!empty($r['feedback']))echo '<p class="ac-result-feedback">'.nl2br(e($r['feedback'])).'</p>';
            echo '</article>';
        }
        echo '<small>Download a dated academic transcript above. It is not an issued certificate.</small></div></details></section>';
    }
    $completion=academy_completion_summary($total,$done,$results);
    $request=db_one('SELECT status FROM academy_certification_requests WHERE student_id=:student AND course_id=:course',['student'=>(int)$user['id'],'course'=>$cid]);
    $approved=db_one('SELECT id FROM academy_graduates WHERE student_id=:student AND course_id=:course',['student'=>(int)$user['id'],'course'=>$cid]);
    echo '<section class="ac-student-panel"><details data-ac-popup class="ac-contact"><summary data-ac-icon="grade">Completion checklist · '.$completion['completed'].'/'.$completion['lessons'].' lessons</summary><p><strong>'.($completion['ready']?'Published learning checklist complete':'Learning checklist in progress').'</strong></p><p>Lessons: '.$completion['completed'].' / '.$completion['lessons'].'</p><p>Assessments passed: '.$completion['passed'].' / '.$completion['assessments'].'</p>';
    if($completion['missing'])echo '<p>Not submitted: '.$completion['missing'].'</p>';
    if($completion['awaiting'])echo '<p>Awaiting grading: '.$completion['awaiting'].'</p>';
    if($completion['failed'])echo '<p>Not passed: '.$completion['failed'].'</p>';
    if($approved)echo '<p>Training already approved.</p><a class="ac-student-link" href="/academy.php?action=certificates">My certificates</a>';
    elseif(($request['status']??'')==='pending')echo '<p>Certification request awaiting administrator review.</p><a class="ac-student-link" href="/academy.php?action=certificates#ac-certificate-history">Track request</a>';
    elseif($completion['ready'])echo '<p>Your published learning records are ready for review. Certification still requires administrator approval.</p><a class="ac-student-link" href="/academy.php?action=certificates#ac-new-certificate">Request certification</a>';
    else echo '<small>Complete remaining learning activities. Your administrator reviews training separately.</small>';
    echo '</details></section>';

}
echo '</div><script src="/assets/js/academy-actions.js?v=1" defer></script>';ac_end();
