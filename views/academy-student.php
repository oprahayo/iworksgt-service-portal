<?php
declare(strict_types=1);
if (!defined('IWORKS_ACADEMY_STUDENT_VIEW') || !has_role(['student'])) { http_response_code(403); exit; }
require_once __DIR__.'/academy-guide.php';
function ac_student_link(string $action,string $label,string $class='ac-student-link'):void{
    echo '<a class="'.e($class).'" href="/academy.php?action='.e($action).'">'.e($label).'</a>';
}
function ac_student_status(string $status):string{
    return ['requested'=>'Pending application','enrolled'=>'Approved','completed'=>'Completed','cancelled'=>'Cancelled','pending'=>'Payment pending','issued'=>'Issued','revoked'=>'Revoked'][$status]??'Available';
}
function ac_student_courses(array $courses,bool $dashboard=false):void{
    if(!$courses){echo '<p class="ac-student-empty">No courses to display yet.</p>';return;}
    echo '<div class="ac-course-grid'.($dashboard?'':' ac-catalogue-grid').'">';
    foreach($courses as $c){
        $status=(string)($c['enrollment_status']??'');
        echo '<article class="ac-course-card">'.($dashboard?'':'<span class="ac-catalogue-icon" aria-hidden="true"><i class="bi bi-book"></i></span>').'<div class="ac-course-copy"><h3>'.e($c['title']).'</h3>';if($status!=='')echo '<p class="ac-course-status">'.e(ac_student_status($status)).'</p>';echo '</div>';
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
$courseRows=[];$graduates=[];$requests=[];
if(in_array($screen,['dashboard','certificates'],true))$requests=db_all('SELECT r.*,c.title,c.active FROM academy_certification_requests r JOIN academy_courses c ON c.id=r.course_id WHERE r.student_id=:student ORDER BY r.submitted_at DESC',['student'=>$studentId]);
if(in_array($screen,['dashboard','courses'],true)){
    $courseRows=db_all("SELECT c.id,c.title,c.active,en.status AS enrollment_status FROM academy_courses c LEFT JOIN academy_enrollments en ON en.course_id=c.id AND en.student_id=:student WHERE c.active=1 OR en.id IS NOT NULL ORDER BY c.title",['student'=>$studentId]);
}
if(in_array($screen,['dashboard','certificates'],true)){
    $graduates=db_all('SELECT g.id,g.duration,g.approved_at,c.title,c.colour,COALESCE(ce.issued_at,ce.created_at,g.approved_at) AS activity_at,ce.id AS certificate_id,ce.status AS certificate_status,ce.certificate_number,ce.checkout_url,ce.payment_reference,ce.email_sent_at FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id WHERE g.student_id=:student ORDER BY g.id DESC',['student'=>$studentId]);
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
    ac_guide('dashboard',$studentId,empty($_SESSION['academy_course_intent']));
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
        if($ready>0){echo '<div><span><strong>Certificate action required</strong></span>';echo '<a class="ac-student-link" href="/academy.php?action=certificates#ac-certificate-history">My Certificates</a>';echo '</div>';}
        if($pending>0){echo '<div><span><strong>'.(int)$pending.' pending application'.($pending===1?'':'s').'</strong></span>';ac_student_link('courses','View status');echo '</div>';}
        if(trim((string)($user['phone']??''))!==''&&$ready===0&&$pending===0&&$learning===0){
            echo '<div><span><strong>Choose your next step</strong></span><a class="ac-student-link" href="#available-courses">Explore courses</a></div>';
        }elseif(trim((string)($user['phone']??''))!==''&&$ready===0&&$pending===0&&$learning>0){
            echo '<div><span><strong>Continue your learning journey</strong></span>';ac_student_link('courses','My Courses');echo '</div>';
        }
        echo '</div></section>';
    }
    echo '<section class="ac-student-panel ac-past-training"><div><h2>Already completed training with iWorks?</h2><p>Request certification for training you completed before the portal upgrade.</p></div>';
    echo '<a class="ac-student-primary" href="/academy.php?action=certificates#ac-new-certificate">Request certification</a>';
    $reviewPending=count(array_filter($requests,fn($r)=>$r['status']==='pending'));
    if($reviewPending)echo '<p>'.(int)$reviewPending.' certification request(s) awaiting review.</p>';
    echo '</section></div><section class="ac-student-panel" id="available-courses"><div class="ac-student-heading"><div><h2>Available courses</h2></div>';ac_student_link('courses','View my courses');echo '</div>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['active']&&!$c['enrollment_status'])),true);echo '</section></div>';
}elseif($screen==='courses'){
    echo '<div class="ac-course-workspace"><header class="ac-course-page-head"><h1>My courses &amp; applications</h1><p>Track your applications, continue learning or find your next course.</p></header>';ac_guide('courses',$studentId);
    echo '<section class="ac-course-section"><h2>My learning</h2><p>Track submitted applications and open approved courses.</p>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['enrollment_status']!==null)));
    echo '</section><section class="ac-course-section" id="ac-explore-courses" tabindex="-1"><h2>Explore courses</h2><p>Choose a course to start your next training.</p>';
    ac_student_courses(array_values(array_filter($courseRows,fn($c)=>$c['active']&&!$c['enrollment_status'])));echo '</section></div>';
}elseif($screen==='certificates'){
    echo '<div class="ac-cert-workspace ac-cert-compact"><header class="ac-course-page-head ac-cert-page-head"><div><h1>My Certificates</h1><p>Request, track and download your certificates.</p></div></header>';ac_guide('certificates',$studentId);
    $available=db_all("SELECT c.id,c.title FROM academy_courses c LEFT JOIN academy_certification_requests r ON r.course_id=c.id AND r.student_id=:request_student WHERE c.active=1 AND (r.id IS NULL OR r.status='declined') AND NOT EXISTS(SELECT 1 FROM academy_graduates g WHERE g.course_id=c.id AND g.student_id=:graduate_student) ORDER BY c.title",['request_student'=>$studentId,'graduate_student'=>$studentId]);
    $waiting=array_filter($requests,fn($r)=>$r['status']!=='approved');
    $ready=count(array_filter($graduates,fn($g)=>!$g['certificate_id']));
    $paying=count(array_filter($graduates,fn($g)=>$g['certificate_status']==='pending'));
    echo '<section class="ac-student-panel ac-cert-group ac-cert-start"><details id="ac-new-certificate" data-cert-record><summary class="ac-cert-summary"><span class="ac-cert-start-icon" aria-hidden="true">+</span><span class="ac-cert-summary-copy"><strong>Request certification</strong><small>For training you have completed</small></span><span class="ac-cert-start-cta">New request <span aria-hidden="true">›</span></span></summary><div class="ac-cert-expanded" data-cert-details>';
    if($available){
        ac_form('request_certificate');
        echo '<div class="ac-cert-wizard" data-cert-wizard><label>1. Completed course<select name="course" required data-cert-course><option value="">Select your completed course</option>';
        foreach($available as $c)echo '<option value="'.(int)$c['id'].'">'.e($c['title']).'</option>';
        echo '</select></label><div data-cert-name-step><label>2. Name on certificate<input type="text" name="name" required maxlength="150" value="'.e($user['full_name']).'" data-cert-name autocomplete="name"></label><small>Use the exact spelling and order you want printed.</small></div><div data-cert-confirm-step><div class="ac-cert-warning"><strong>3. Confirm your certificate name</strong><output data-cert-name-output>'.e($user['full_name']).'</output><p>Check the name carefully. After approval, the name cannot be changed by you. Once payment is started, the certificate details are locked. An issued certificate cannot be regenerated with a different name.</p></div><label class="ac-cert-check"><input type="checkbox" name="confirm_name" value="1" required data-cert-confirm> I confirm that this is the exact name I want on my certificate.</label><button>Submit certification request</button><small>Payment is only available after approval.</small></div></div></form>';
    }else echo '<p>No additional courses available for certification requests.</p>';
    echo '</div></details></section>';
    echo '<section class="ac-student-panel ac-cert-group ac-cert-activity" id="ac-certificate-history" tabindex="-1"><div class="ac-cert-activity-head"><h2>Certificate history</h2><p>'.(count($waiting)+count($graduates)).' activities · '.$ready.' ready · '.$paying.' awaiting payment</p></div>';
    if(!$waiting&&!$graduates)echo '<p class="ac-student-empty">No certificate activity yet. Submit a certification request above.</p>';
    $activities=[];
    foreach($waiting as $r)$activities[]=['request'=>$r,'at'=>max((string)$r['submitted_at'],(string)$r['reviewed_at'])];
    foreach($graduates as $g)$activities[]=['graduate'=>$g,'at'=>$g['activity_at']];
    usort($activities,fn($a,$b)=>strcmp($b['at'],$a['at']));
    foreach($activities as $activity){
      if(isset($activity['request'])){
        $r=$activity['request'];
        echo '<details class="ac-cert-record" data-cert-record><summary class="ac-cert-summary"><span class="ac-cert-history-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span><span class="ac-cert-summary-copy"><strong>'.e($r['title']).'</strong><small>Submitted '.e(date('d M Y',strtotime($r['submitted_at']))).'</small></span><span class="ac-request-state state-'.e($r['status']).'">'.e($r['status']==='pending'?'Awaiting review':'Declined').'</span><span class="ac-cert-next">'.($r['status']==='pending'?'View status':'View reason').'</span><span class="ac-cert-chevron" aria-hidden="true"></span></summary><div class="ac-cert-expanded" data-cert-details>';
        if($r['review_note']!=='')echo '<div class="ac-query-reason"><strong>Administrator query</strong><p>'.nl2br(e($r['review_note'])).'</p></div>';
        if($r['status']==='declined'&&$r['active'])echo '<small>You may submit a fresh request after addressing the administrator query.</small>';
        elseif($r['status']==='pending')echo '<p>Waiting for administrator review. You will be notified of the decision.</p>';
        echo '</div></details>';
      }else{
        $g=$activity['graduate'];
        $status=$g['certificate_status']??'ready';
        $label=['ready'=>'Ready to certify','pending'=>'Payment pending','issued'=>'Issued','revoked'=>'Revoked'][$status]??'Unavailable';
        echo '<details class="ac-cert-record" data-cert-record><summary class="ac-cert-summary"><span class="ac-cert-history-icon" aria-hidden="true"><i class="bi bi-award"></i></span><span class="ac-cert-summary-copy"><strong>'.e($g['title']).'</strong><small>'.e($g['duration']).' · '.e(date('d M Y',strtotime($g['activity_at']))).'</small></span><span class="ac-request-state state-'.e($status==='ready'||$status==='issued'?'approved':($status==='pending'?'pending':'declined')).'">'.e($label).'</span><span class="ac-cert-next">'.e(['ready'=>'Get certificate','pending'=>'Payment details','issued'=>'Download','revoked'=>'View details'][$status]??'View details').'</span><span class="ac-cert-chevron" aria-hidden="true"></span></summary><div class="ac-cert-expanded" data-cert-details>';
        if(!$g['certificate_id']){
            ac_form('preview');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'">';
            $approvedName=academy_request_name($studentId,(int)$g['id'],'');
            if($approvedName!==''){echo '<p><strong>Confirmed name:</strong> '.e($approvedName).'</p><input type="hidden" name="name" value="'.e($approvedName).'">';}
            else {ac_input('name','Name on certificate',$user['full_name']);echo '<small>Legacy approval: verify your name before previewing.</small>';}
            echo '<button>Get Certificate</button></form>';
        }elseif($status==='issued'){echo '<p>Issued: '.e($g['certificate_number']).'</p><p class="ac-cert-email">'.($g['email_sent_at']?'Certificate email sent.':'Certificate email queued for delivery.').'</p><a class="ac-student-primary" href="/academy.php?action=download&amp;id='.(int)$g['certificate_id'].'">Download Certificate</a>';}
        elseif($status==='pending'){echo '<p>Payment pending. If you have already paid, check the status; do not pay again.</p><div class="ac-cert-payment-actions">';ac_form('recheck');echo '<input type="hidden" name="reference" value="'.e($g['payment_reference']).'"><button>Check Payment</button></form>';if(!empty($g['checkout_url'])&&academy_checkout_enabled()){ac_form('resume');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'"><button class="ac-secondary">Continue Payment</button></form>';}echo '</div>';}
        else echo '<p>Certificate revoked. Contact support.</p>';
        echo '</div></details>';
      }
    }
    echo '</section><dialog class="ac-cert-dialog" id="ac-cert-dialog" aria-labelledby="ac-cert-dialog-title"><div class="ac-cert-dialog-head"><div><h2 id="ac-cert-dialog-title">Certificate details</h2><p id="ac-cert-dialog-meta"></p></div><button type="button" data-cert-close aria-label="Close certificate details"><i class="bi bi-x-lg" aria-hidden="true"></i><span aria-hidden="true" class="ac-x-fallback">×</span></button></div><div data-cert-dialog-body></div></dialog><script src="/assets/js/academy-certificates.js?v=4" defer></script></div>';
}else{
    ac_guide('profile',$studentId);
    echo '<section class="ac-student-panel ac-profile-panel"><h2>Personal information</h2><p>Keep your name and contact details up to date.</p><label>Email address<input type="email" readonly value="'.e($user['email']).'"></label><small>Email verified. Contact support if you need to change it.</small>';
    ac_form('profile');ac_input('name','Full name',$user['full_name']);ac_input('phone','Phone',(string)$user['phone'],'tel');echo '<button>Save profile</button></form></section>';
}
echo '</div>';ac_end();
