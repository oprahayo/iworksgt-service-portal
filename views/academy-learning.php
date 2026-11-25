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
if($notice=flash('academy_student_notice'))echo '<p class="ac-student-notice" role="status">'.e($notice).'</p>';
if(!$total){echo '<section class="ac-student-panel"><h2>Your learning area</h2><p>Course lessons and modules have not been published yet. You will be able to access them here when available.</p></section>';}
else{
    echo '<p>'.e((string)$course['description']).'</p><div class="ac-lms-progress"><strong>'.$done.' of '.$total.' lessons completed</strong><progress value="'.$done.'" max="'.$total.'"></progress></div><div class="ac-lms-layout"><section class="ac-student-panel"><h2>Course content</h2>';
    $module=0;
    foreach($lessons as $l){
        if($module!==(int)$l['module_id']){$module=(int)$l['module_id'];echo '<h3>'.e($l['module_title']).'</h3>';}
        echo '<a class="ac-lms-lesson'.((int)$l['id']===$lid?' active':'').'" href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$l['id'].'">'.e($l['title']).'<span>'.($l['completed']?'Completed':'Open').'</span></a>';
    }
    echo '</section><section class="ac-student-panel">';
    if(!$lesson)echo '<p>Lesson unavailable. Choose a published lesson.</p>';
    else{
        echo '<h2>'.e($lesson['title']).'</h2><div class="ac-lesson-body">'.nl2br(e($lesson['body'])).'</div>';
        if($embed=academy_video_embed($lesson['video_url']))echo '<div class="ac-lms-video"><iframe src="'.e($embed).'" title="Lesson video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
        if($lesson['video_url']!=='')echo '<p><a class="ac-student-link" href="'.e(academy_learning_url($lesson['video_url'])).'" target="_blank" rel="noopener noreferrer">Watch lesson video</a></p>';
        if($lesson['material_url']!=='')echo '<p><a class="ac-student-link" href="'.e(academy_learning_url($lesson['material_url'])).'" target="_blank" rel="noopener noreferrer">Open learning material</a></p>';
        ac_assessment_student((int)$user['id'],$cid,$lid);
        $completed=false;foreach($lessons as $l)if((int)$l['id']===$lid)$completed=(bool)$l['completed'];
        if($completed)echo '<p>Lesson completed.</p>';
        else{ac_form('lesson_complete');echo '<input type="hidden" name="course" value="'.$cid.'"><input type="hidden" name="lesson" value="'.$lid.'"><button>Mark as complete</button></form>';}
        $next=false;foreach($lessons as $l){if($next){echo '<p><a class="ac-student-link" href="/academy.php?action=learn&amp;course='.$cid.'&amp;lesson='.(int)$l['id'].'">Next lesson</a></p>';break;}if((int)$l['id']===$lid)$next=true;}
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
        echo '<small>These are your learning results, not an official transcript or certificate.</small></div></details></section>';
    }
    if($done===$total)echo '<p>All published lessons completed. The Academy administrator reviews training completion separately before certification.</p>';
}
echo '</div><script src="/assets/js/academy-actions.js?v=1" defer></script>';ac_end();
