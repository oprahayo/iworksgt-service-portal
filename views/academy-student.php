<?php
declare(strict_types=1);
if (!defined('IWORKS_ACADEMY_STUDENT_VIEW') || !has_role(['student'])) { http_response_code(403); exit; }
function ac_student_link(string $action,string $label,string $class='ac-student-link'):void{
    echo '<a class="'.e($class).'" href="/academy.php?action='.e($action).'">'.e($label).'</a>';
}
function ac_student_status(string $status):string{
    return ['requested'=>'Pending application','enrolled'=>'Approved','completed'=>'Completed','cancelled'=>'Cancelled','pending'=>'Payment pending','issued'=>'Issued','revoked'=>'Revoked'][$status]??'Available';
}
function ac_student_courses(array $courses,bool $dashboard=false):void{
    if(!$courses){echo '<p class="ac-student-empty">No courses to display yet.</p>';return;}
    echo '<div class="ac-course-grid">';
    foreach($courses as $c){
        $status=(string)($c['enrollment_status']??'');
        echo '<article class="ac-course-card"><div class="ac-course-copy"><h3>'.e($c['title']).'</h3>';if($status!=='')echo '<p class="ac-course-status">'.e(ac_student_status($status)).'</p>';echo '</div>';
        if(in_array($status,['enrolled','completed'],true)&&$c['active'])echo '<a class="btn btn-sm btn-outline-primary ac-course-action" href="/academy.php?action=learn&amp;course='.(int)$c['id'].'">Open course</a>';
        elseif($status==='') {ac_form('enroll');echo '<input type="hidden" name="course" value="'.(int)$c['id'].'"><input type="hidden" name="return_to" value="'.($dashboard?'dashboard':'courses').'"><button class="btn btn-sm btn-outline-primary ac-course-action" aria-label="Apply for course: '.e($c['title']).'">Apply<span class="ac-full-label"> for course</span></button></form>';}
        elseif($status==='requested')echo '<small>In review</small>';
        elseif(!$c['active'])echo '<small>Course no longer accepting applications.</small>';
        echo '</article>';
    }
    echo '</div>';
}
$studentId=(int)$user['id'];
$screen=in_array($action,['dashboard','courses','certificates','profile'],true)?$action:'dashboard';
$notice=flash('academy_student_notice');
$courseRows=[];$graduates=[];
if(in_array($screen,['dashboard','courses'],true)){
    $courseRows=db_all("SELECT c.id,c.title,c.active,en.status AS enrollment_status FROM academy_courses c LEFT JOIN academy_enrollments en ON en.course_id=c.id AND en.student_id=:student WHERE c.active=1 OR en.id IS NOT NULL ORDER BY c.title",['student'=>$studentId]);
}
if(in_array($screen,['dashboard','certificates'],true)){
    $graduates=db_all('SELECT g.id,g.duration,c.title,c.colour,ce.id AS certificate_id,ce.status AS certificate_status,ce.certificate_number,ce.checkout_url,ce.payment_reference,ce.email_sent_at FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id WHERE g.student_id=:student ORDER BY g.id DESC',['student'=>$studentId]);
}
$titles=['dashboard'=>'Dashboard','courses'=>'My Courses','certificates'=>'My Certificates','profile'=>'My Profile'];
ac_head($titles[$screen]);
echo '<div class="ac-student">';
if($notice)echo '<div class="ac-student-notice" role="status">'.e($notice).'</div>';
if($screen==='dashboard'){
    $first=trim(explode(' ',trim((string)$user['full_name']))[0]??'');
    $pending=count(array_filter($courseRows,fn($c)=>$c['enrollment_status']==='requested'));
    $learning=count(array_filter($courseRows,fn($c)=>$c['enrollment_status']==='enrolled'));
    $issued=count(array_filter($graduates,fn($g)=>$g['certificate_status']==='issued'));
    $ready=count(array_filter($graduates,fn($g)=>$g['certificate_status']===null||$g['certificate_status']==='pending'));
    echo '<header class="iw-ops-page-head ac-student-welcome"><div><h1>Welcome, '.e($first?:'student').'</h1><p>Manage your learning and certification.</p></div></header>';
    echo '<div class="ac-student-stats" aria-label="Your Academy progress">';
    foreach([['Pending applications',$pending,'courses','bi-hourglass-split'],['My active courses',$learning,'courses','bi-book'],['Certificates issued',$issued,'certificates','bi-award']] as [$label,$count,$dest,$icon]){
        echo '<a href="/academy.php?action='.$dest.'" aria-label="'.e($label).': '.$count.'"><strong>'.$count.'</strong><small>'.e(['Pending applications'=>'Pending applications','My active courses'=>'Active courses','Certificates issued'=>'Certificates'][$label]).'</small></a>';
    }
    echo '</div>';
    $intent=(int)($_SESSION['academy_course_intent']??0);
    if($intent>0){
        $selected=null;foreach($courseRows as $c)if((int)$c['id']===$intent&&$c['active']&&!$c['enrollment_status'])$selected=$c;
        if($selected){
            echo '<section class="ac-student-panel" id="application"><h2>Confirm your course application</h2><p>'.e($selected['title']).'</p><p>No payment is requested when you apply.</p>';
            ac_form('enroll');echo '<input type="hidden" name="course" value="'.(int)$selected['id'].'"><input type="hidden" name="return_to" value="courses"><button>Submit application</button></form></section>';
        }else unset($_SESSION['academy_course_intent']);
    }
    echo '<div class="ac-dashboard-columns"><div class="ac-dashboard-actions">';
    {
        echo '<section class="ac-student-panel"><h2>Things to do</h2><div class="ac-task-list">';
        if(trim((string)($user['phone']??''))===''){echo '<div><span><strong>Complete your profile</strong></span>';ac_student_link('profile','Edit profile');echo '</div>';}
        if($ready>0){echo '<div><span><strong>Certificate action required</strong></span>';ac_student_link('certificates','My Certificates');echo '</div>';}
        if($pending>0){echo '<div><span><strong>'.(int)$pending.' pending application'.($pending===1?'':'s').'</strong></span>';ac_student_link('courses','View status');echo '</div>';}
        if(trim((string)($user['phone']??''))!==''&&$ready===0&&$pending===0&&$learning===0){
            echo '<div><span><strong>Choose your next step</strong></span><a class="ac-student-link" href="#available-courses">Explore courses</a></div>';
        }elseif(trim((string)($user['phone']??''))!==''&&$ready===0&&$pending===0&&$learning>0){
            echo '<div><span><strong>Continue your learning journey</strong></span>';ac_student_link('courses','My Courses');echo '</div>';
        }
        echo '</div></section>';
    }
    echo '<section class="ac-student-panel ac-past-training"><div><h2>Already completed training with iWorks?</h2><p>Request certification for training you completed before the portal upgrade.</p></div>';
    $reviewCourses=array_filter($courseRows,fn($c)=>$c['active']&&!$c['enrollment_status']);
    if($reviewCourses){
        ac_form('enroll');echo '<input type="hidden" name="return_to" value="dashboard"><label><span class="ac-visually-hidden">Completed course</span><select name="course" required><option value="">Select your course</option>';
        foreach($reviewCourses as $c)echo '<option value="'.(int)$c['id'].'">'.e($c['title']).'</option>';
        echo '</select></label><button class="btn btn-primary">Request course review</button></form>';
    }else{echo '<p>Check your existing applications or approved certificates.</p>';ac_student_link('certificates','My Certificates');}
    echo '</section></div><section class="ac-student-panel" id="available-courses"><div class="ac-student-heading"><div><h2>Available courses</h2></div>';ac_student_link('courses','View my courses');echo '</div>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['active']&&!$c['enrollment_status'])),true);echo '</section></div>';
}elseif($screen==='courses'){
    echo '<section class="ac-student-panel"><h2>My courses &amp; applications</h2><p>Track your submitted applications and open approved courses.</p>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['enrollment_status']!==null)));
    echo '</section><section class="ac-student-panel"><h2>Explore courses</h2>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['active']&&!$c['enrollment_status'])));echo '</section>';
}elseif($screen==='certificates'){
    echo '<section class="ac-student-panel"><h2>My certificates</h2><p>Request, preview or download certificates for your approved completed courses.</p>';
    if(!$graduates){echo '<p class="ac-student-empty">No approved certificates yet. If you have completed training, request a course review from your dashboard.</p>';ac_student_link('dashboard','Go to dashboard','ac-student-primary');}
    foreach($graduates as $g){
        echo '<article class="ac-cert-card"><h3>'.e($g['title']).'</h3><p>'.e($g['duration']).'</p>';
        if(!$g['certificate_id']){ac_form('preview');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'">';ac_input('name','Name on certificate',$user['full_name']);echo '<button>Get Certificate</button></form>';}
        elseif($g['certificate_status']==='issued'){echo '<p>Issued: '.e($g['certificate_number']).'</p><p class="ac-cert-email">'.($g['email_sent_at']?'Certificate email sent.':'Certificate email queued for delivery.').'</p><a class="ac-student-primary" href="/academy.php?action=download&amp;id='.(int)$g['certificate_id'].'">Download Certificate</a>';}
        elseif($g['certificate_status']==='pending'){echo '<p>Payment pending. If you have already paid, check the status; do not pay again.</p>';if(!empty($g['checkout_url'])&&academy_checkout_enabled()){ac_form('resume');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'"><button>Continue Payment</button></form>';}ac_form('recheck');echo '<input type="hidden" name="reference" value="'.e($g['payment_reference']).'"><button>Check Payment</button></form>';}
        else echo '<p>Certificate revoked. Contact support.</p>';
        echo '</article>';
    }
    echo '</section>';
}else{
    echo '<section class="ac-student-panel ac-profile-panel"><h2>Personal information</h2><p>Keep your name and contact details up to date.</p><label>Email address<input type="email" readonly value="'.e($user['email']).'"></label><small>Email verified. Contact support if you need to change it.</small>';
    ac_form('profile');ac_input('name','Full name',$user['full_name']);ac_input('phone','Phone',(string)$user['phone'],'tel');echo '<button>Save profile</button></form></section>';
}
echo '</div>';ac_end();
