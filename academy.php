<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/academy.php';
require __DIR__.'/app/academy_requests.php';
require __DIR__.'/app/academy_learning.php';
require __DIR__.'/app/academy_admin.php';
require __DIR__.'/app/academy_transcript.php';
require __DIR__.'/app/academy_assessments.php';
require __DIR__.'/views/academy-assessments.php';
if (!is_configured()) { http_response_code(503); exit('Academy unavailable.'); }
if (should_enforce_split_domain() && request_host()!==configured_host(portal_base_url())) {
    redirect('https://portal.iworksgt.com/academy.php');
}
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
$action=(string)($_GET['action']??'dashboard');
$base='https://portal.iworksgt.com/academy.php';
function ac_form(string $action): void { echo '<form method="post" action="/academy.php?action='.e($action).'"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function ac_input(string $name,string $label,string $value='',string $type='text'): void {
    echo '<label>'.e($label).'<input required type="'.e($type).'" name="'.e($name).'" value="'.e($value).'"></label>';
}
function ac_head(string $title): void {
    $GLOBALS['academy_native_shell'] = is_logged_in() && has_role(['admin','student']) && ($_GET['action']??'')!=='verify';
    if ($GLOBALS['academy_native_shell']) {
        $_GET['page']='academy';
        if(has_role(['student'])) { $studentAction=(string)($_GET['action']??'dashboard'); $_GET['page']=match($studentAction){'courses','learn'=>'academy_courses','certificates','preview','certificate_preview','preview_image','callback','checkout','resume','recheck'=>'academy_certificates','profile'=>'academy_profile',default=>'academy'}; }
        $title=$title.' - '.app_name();
        require __DIR__.'/views/partials/header.php';
        echo '<link rel="stylesheet" href="/assets/css/academy-compact.css?v=18"><div class="academy-workspace">';
        return;
    }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).' | iWorks Global</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/brand-color-pass.css"><style>*{box-sizing:border-box}body{font-family:Outfit,system-ui,sans-serif;color:var(--brand-text);margin:0}header,main{max-width:960px;margin:auto;padding:24px}header{display:flex;align-items:center;justify-content:space-between}header img{width:180px;max-width:45vw}section,article,form{background:white;border:1px solid #e1e4e8;border-radius:12px;padding:20px;margin:16px 0}label{display:block;margin:12px 0}input,select{display:block;width:100%;padding:12px;border:1px solid #bfc5ca;border-radius:6px;font:inherit}button{min-height:44px;background:var(--brand-primary);color:white;border:0;border-radius:6px;padding:12px 18px;font:inherit;cursor:pointer}a{color:var(--brand-primary)}nav a{margin-right:16px}small{color:#555}h1{font-size:28px}img.preview{max-width:100%;height:auto}@media(max-width:600px){header,main{padding:16px}button{width:100%}h1{font-size:24px}section,article,form{padding:16px}}</style><header><img src="/assets/branding/iworks-desktop.svg" alt="iWorks Global"><a href="/logout.php">Sign out</a></header><main><h1>'.e($title).'</h1>';
}
function ac_end(): void {
    if (!empty($GLOBALS['academy_native_shell'])) {
        echo '</div>';
        require __DIR__.'/views/partials/footer.php';
        return;
    }
    echo '</main></html>';
}
function ac_notify(array $cert,array $user): void {
    if ((int)$cert['student_id']!==(int)$user['id'] || !empty($cert['email_sent_at'])) return;
    try { academy_deliver_email((int)$cert['id']); }
    catch(Throwable $e) { error_log('Academy email queued for retry: '.get_class($e)); }
}
try {
    if ($action==='verify') {
        $number=strtoupper(trim((string)($_GET['number']??'')));
        if ($number!=='') {
            $ip=iworks_auth_ip();
            if (!iworks_auth_limit_allows('academy_verify_ip',$ip,60,900)) {http_response_code(429);exit('Too many verification attempts. Try again later.');}
            iworks_auth_limit_record('academy_verify_ip',$ip,900);
        }
        ac_head('Verify a certificate');
        echo '<form method="get" action="/verify"><input type="hidden" name="action" value="verify">';
        ac_input('number','Certificate number',(string)($_GET['number']??'')); echo '<button>Verify certificate</button></form>';
        if ($number!=='') {
            $cert=preg_match('/^IWGA-\\d{4}-[A-F0-9]{16}$/D',$number)?db_one("SELECT certificate_number,student_name,course_title,duration,issued_at,status FROM academy_certificates WHERE certificate_number=:number AND status IN ('issued','revoked')",['number'=>$number]):null;
            if (!$cert || $cert['status']!=='issued') echo '<section>No valid certificate found.</section>';
            else { echo '<section><h2>Verified certificate</h2>'; foreach(['certificate_number','student_name','course_title','duration','issued_at'] as $key) echo '<p><strong>'.e(ucwords(str_replace('_',' ',$key))).':</strong> '.e((string)$cert[$key]).'</p>'; echo '</section>'; }
        }
        ac_end(); exit;
    }
    if ($action==='apply') {
        $slug=trim((string)($_GET['course']??''));
        $course=preg_match('/^[a-z0-9-]{1,100}$/D',$slug)?db_one('SELECT id,slug,title FROM academy_courses WHERE slug=:slug AND active=1',['slug'=>$slug]):null;
        if(!$course){http_response_code(404);ac_head('Course unavailable');echo '<p>This course is not currently accepting applications.</p><a href="https://iworksgt.com/services/pro-training">View training courses</a>';ac_end();exit;}
        if(is_logged_in()&&!has_role(['student'])){http_response_code(403);ac_head('Student account required');echo '<p>Sign out and use a separate student account to apply. Your existing portal role will not be changed.</p><a href="/logout.php">Sign out</a>';ac_end();exit;}
        $_SESSION['academy_course_intent']=(int)$course['id'];
        redirect(is_logged_in()?$base.'?action=dashboard#application':'https://portal.iworksgt.com/student/register');
    }
    if ($action==='register') {
        if (is_logged_in()) {
            if(!method_is('GET')){http_response_code(403);exit('Use a separate signed-out browser to register a new student.');}
            if(!has_role(['admin']))redirect(has_role(['student'])?$base:portal_url('dashboard'));
            ac_head('Student registration link');
            echo '<div class="ac-admin"><section class="ac-panel"><h2>Invite a student</h2><p>Your administrator session is active. Share this public link or open it in a private browser window to see the registration form.</p><label>Public registration address<input type="url" readonly value="https://portal.iworksgt.com/academy.php?action=register" onclick="this.select()"></label><p><small>Copy the address and send it to the student. Your current session will remain signed in.</small></p><a class="ac-secondary" href="/academy.php?action=admin&amp;tab=students">Back to students</a></section></div>';ac_end();exit;
        }
        $selectedCourse=(int)($_SESSION['academy_course_intent']??0)>0?db_one('SELECT id,title FROM academy_courses WHERE id=:id AND active=1',['id'=>(int)$_SESSION['academy_course_intent']]):null;
        if (method_is('POST')) {
            require_csrf();
            try {
            $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??'')));
            $phone=trim((string)($_POST['phone']??''));$password=(string)($_POST['password']??'');
            if (!academy_valid_name($name)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strlen($phone)<7||strlen($phone)>40||strlen($password)<12||strlen($password)>72||$password!==(string)($_POST['confirm_password']??'')||!email_enabled()||find_user_by_email($email)) throw new RuntimeException('Check the registration details or sign in if you already have an account.');
            $ip=iworks_auth_ip(); if (!iworks_auth_limit_allows('academy_register_ip',$ip,8,3600)) throw new RuntimeException('Try again later.');
            iworks_auth_limit_record('academy_register_ip',$ip,3600);
            $id=create_user(['full_name'=>$name,'email'=>$email,'phone'=>$phone,'password'=>$password,'role'=>'student']);
            start_otp_challenge($id,'verify_email',!empty($_POST['remember_30days'])); redirect(portal_url('otp'));
            } catch (Throwable $e) {
                error_log('Academy registration failed: '.get_class($e));
                flash('error','Unable to register. Check your details, use a matching password of at least 12 characters, or sign in if you already have an account.');
            }
        }
        $_GET['page']='register';
        $academyGuestRegistration=true;
        $title='Student registration - '.app_name();
        require __DIR__.'/views/partials/header.php';
        require __DIR__.'/views/academy-register.php';
        require __DIR__.'/views/partials/footer.php';
        exit;
    }
    if ($action==='transcript'){
        require_roles(['admin','student']);
        if(!method_is('GET')){http_response_code(405);exit;}
        $viewer=current_user();$admin=has_role(['admin']);$student=$admin?(int)($_GET['student']??0):(int)$viewer['id'];
        if(!$admin&&isset($_GET['student'])&&(int)$_GET['student']!==$student){http_response_code(404);exit;}
        $data=academy_transcript_data((int)$viewer['id'],$student,(int)($_GET['course']??0),$admin);
        if(!$data){http_response_code(404);exit('Transcript unavailable.');}
        try{$pdf=academy_transcript_pdf($data);}catch(Throwable $e){error_log('Academy transcript rendering failed: '.get_class($e));http_response_code(503);exit('Transcript temporarily unavailable.');}
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="iWorks-Academic-Transcript.pdf"');header('Content-Length: '.strlen($pdf));header('X-Content-Type-Options: nosniff');header('Cross-Origin-Resource-Policy: same-origin');echo $pdf;exit;
    }
    if ($action==='assignment_file'){
        require_roles(['admin','student']);
        if(!method_is('GET')){http_response_code(405);exit;}
        $viewer=current_user();$admin=has_role(['admin']);
        if(!$admin&&!is_email_verified($viewer)){http_response_code(403);exit;}
        $file=academy_assignment_file_download((int)$viewer['id'],(int)($_GET['course']??0),(int)($_GET['lesson']??0),(int)($_GET['id']??0),$admin);
        if(!$file){http_response_code(404);exit('Assignment file unavailable.');}
        header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._-]/','_',$file['filename']).'"');
        header('Content-Length: '.strlen($file['content']));header('X-Content-Type-Options: nosniff');header('Cross-Origin-Resource-Policy: same-origin');header("Content-Security-Policy: default-src 'none'; sandbox");echo $file['content'];exit;
    }
    if ($action==='builder'){
        require_roles(['admin']);
        $cid=(int)(method_is('POST')?($_POST['course']??0):($_GET['course']??0));
        $course=db_one('SELECT id,title FROM academy_courses WHERE id=:id',['id'=>$cid]);
        if(!$course){http_response_code(404);exit('Course unavailable.');}
        if(method_is('POST')){
            require_csrf();
            try{
                $mode=(string)($_POST['mode']??'');$lesson=(int)($_POST['lesson']??0);$id=(int)($_POST['id']??0);
                if($mode==='material')academy_material_upload($cid,$lesson,$_FILES['material']??[]);
                elseif($mode==='assessment')academy_assessment_create($cid,$lesson,$_POST);
                elseif($mode==='assessment_publish')academy_assessment_publish($cid,$lesson,$id,($_POST['published']??'')==='1');
                elseif($mode==='grant_retake'){
                    academy_grant_retake((int)current_user()['id'],(int)($_POST['student']??0),$cid,$lesson,$id,(string)($_POST['reason']??''));
                    academy_notice((int)$_POST['student'],'Assessment retake approved','An administrator approved one additional attempt. Open your course to start.','courses');
                }
                elseif($mode==='grade')academy_assignment_grade((int)current_user()['id'],$cid,$lesson,$id,$_POST);
                else academy_learning_save($cid,$mode,$_POST);
                flash('academy_admin_notice','Learning content saved.');
            }
            catch(InvalidArgumentException $e){flash('academy_admin_error',$e->getMessage());}
            catch(Throwable $e){error_log('Academy builder save failed: '.get_class($e));flash('academy_admin_error','Unable to save content.');}
            redirect($base.'?action=builder&course='.$cid);
        }
        define('IWORKS_ACADEMY_BUILDER',true);require __DIR__.'/views/academy-builder.php';exit;
    }
    if ($action==='admin') {
        require_roles(['admin']);
        if(method_is('POST')){
            require_csrf();
            $mode=(string)($_POST['mode']??'');
            $tab=(string)($_POST['return_tab']??'students');
            if(!in_array($tab,['students','student','enrollments','courses','certificates','requests','analytics'],true))$tab='students';
            $studentId=(int)($_POST['student_id']??0);
            $destination=$base.'?'.http_build_query(['action'=>'admin','tab'=>$tab]+($tab==='student'&&$studentId>0?['id'=>$studentId]:[]));
            $error='';$success='';
            try{
                if($mode==='grade_pending'){
                    $result=academy_admin_grade_pending((int)current_user()['id'],(int)($_POST['id']??0),$_POST);
                    $success='Assignment grade saved.';
                    academy_notice($result['student'],'Assignment graded',$result['title'].': '.$result['score'].'%. Open your course to view feedback.','courses');
                }elseif($mode==='create_course'){
                    $title=trim((string)($_POST['title']??''));$colour=(string)($_POST['colour']??'');
                    $slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($title)),'-');
                    if($title===''||mb_strlen($title)>160||$slug===''||strlen($slug)>100||!in_array($colour,['orange','purple','green','blue'],true))$error='Enter a course title and select a valid certificate colour.';
                    elseif(db_one('SELECT id FROM academy_courses WHERE slug=:slug',['slug'=>$slug]))$error='A course with that title already exists.';
                    else{db_execute('INSERT INTO academy_courses (slug,title,colour) VALUES (:slug,:title,:colour)',compact('slug','title','colour'));$success='Course added.';}
                }elseif($mode==='enrollment'){
                    $result=academy_admin_enrollment((int)current_user()['id'],(int)($_POST['id']??0),(string)($_POST['expected']??''),(string)($_POST['status']??''),(string)($_POST['reason']??''),$studentId);
                    $success=match($result['status']){'enrolled'=>'Enrollment accepted.','requested'=>'Enrollment returned to review.','cancelled'=>'Enrollment cancelled.'};
                    academy_notice($result['student'],$success,$result['title'].': '.$success,'courses');
                }elseif($mode==='reopen_request'){
                    $result=academy_admin_reopen_request((int)current_user()['id'],(int)($_POST['request']??0),(int)($_POST['attempt']??0),(string)($_POST['reason']??''),$studentId);
                    $success='Certification request reopened for review.';
                    academy_notice($result['student'],$success,$result['title'].': '.$success,'certificates');
                }elseif($mode==='delete_student'){
                    academy_admin_delete_student((int)current_user()['id'],$studentId,(string)($_POST['confirm_delete']??''),(string)($_POST['confirm_email']??''));
                    $success='Student permanently deleted.';
                    $destination=$base.'?action=admin&tab=students';
                }elseif($mode==='edit_student'){
                    $success=academy_admin_edit_student((int)current_user()['id'],$studentId,(string)($_POST['name']??''),(string)($_POST['phone']??''),(string)($_POST['reason']??''))?'Student profile updated.':'No profile changes required.';
                }elseif($mode==='edit_duration'){
                    $graduate=(int)($_POST['graduate']??0);
                    if(academy_edit_duration($studentId,$graduate,(string)($_POST['duration']??'')))$success='Training duration updated.';
                    else $error='No change saved. The duration may already match, or a certificate payment has started.';
                }elseif($mode==='review_request'){
                    try{
                        $changed=academy_request_review((int)current_user()['id'],(int)($_POST['request']??0),(string)($_POST['decision']??''),(string)($_POST['duration']??''),(string)($_POST['reason']??''),(int)($_POST['attempt']??0));
                        if($changed)$success='Certification request reviewed. Student notified.';
                        else $error='This request is no longer pending. Refresh the queue.';
                    }catch(InvalidArgumentException $e){$error=$e->getMessage();}
                }elseif($mode==='colour'){
                    $colour=(string)($_POST['colour']??'');$course=(int)($_POST['course']??0);
                    if(!in_array($colour,['orange','purple','green','blue'],true)||!db_one('SELECT id FROM academy_courses WHERE id=:id',['id'=>$course]))$error='Select a valid course and colour.';
                    else{db_execute('UPDATE academy_courses SET colour=:colour WHERE id=:id',['colour'=>$colour,'id'=>$course]);$success='Certificate colour saved for future requests.';}
                }elseif($mode==='verify_payment'){
                    $cert=db_one("SELECT id,payment_reference FROM academy_certificates WHERE id=:id AND status='pending'",['id'=>(int)($_POST['id']??0)]);
                    if(!$cert)$error='This payment is no longer pending. Refresh the certificate list.';
                    elseif(academy_confirm($cert['payment_reference']))$success='Payment verified. Certificate issued; email delivery is queued.';
                    else $error='Payment is not yet verified by Paystack. No certificate was issued and no new payment was initiated.';
                }elseif($mode==='retry_email'){
                    $cert=db_one("SELECT id,email_sent_at FROM academy_certificates WHERE id=:id AND status='issued'",['id'=>(int)($_POST['id']??0)]);
                    if(!$cert)$error='Only issued certificates can be delivered.';
                    elseif($cert['email_sent_at'])$success='Certificate email was already accepted by the mail server.';
                    elseif(academy_deliver_email((int)$cert['id']))$success='Certificate email accepted by the mail server.';
                    else $error='Email could not be sent now. The scheduled delivery worker will retry.';
                }elseif($mode==='revoke'){
                    if(($_POST['confirm_revoke']??'')!=='1')$error='Confirm revocation before proceeding.';
                    else{
                        $result=academy_admin_revoke((int)current_user()['id'],(int)($_POST['id']??0),(string)($_POST['reason']??''),$studentId);
                        $success='Certificate revoked. Public verification and downloads are disabled for it.';
                        academy_notice($result['student'],'Certificate revoked',$result['title'].': Certificate revoked. Contact Academy support for details.','certificates');
                    }
                }else $error='Unknown Academy action.';
            }catch(InvalidArgumentException $e){
                if(db()->inTransaction())db()->rollBack();
                $error=$e->getMessage();
            }catch(Throwable $e){
                if(db()->inTransaction())db()->rollBack();
                error_log('Academy admin action failed: '.get_class($e));
                $error='The change could not be saved. Refresh and try again.';
            }
            if($error!=='')flash('academy_admin_error',$error);
            elseif($success!=='')flash('academy_admin_notice',$success);
            redirect($destination);
        }
        define('IWORKS_ACADEMY_ADMIN_VIEW',true);
        require __DIR__.'/views/academy-admin.php';
        exit;
    }
    require_roles(['student']);$user=current_user();
    if (!is_email_verified($user)) { http_response_code(403);exit('Verify your email first.'); }
    if(in_array($action,['certificate_preview','preview_image'],true)&&!method_is('POST')){
        $p=$_SESSION['academy_preview']??[];
        $g=($p['student']??0)===(int)$user['id']&&($p['expires']??0)>=time()?db_one('SELECT g.*,c.title,c.colour FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id WHERE g.id=:id AND g.student_id=:student',['id'=>(int)$p['graduate'],'student'=>$user['id']]):null;
        if(!$g){if($action==='preview_image'){http_response_code(404);exit;}flash('academy_student_notice','Preview expired. Open your certificate again.');redirect($base.'?action=certificates');}
        $name=academy_request_name((int)$user['id'],(int)$g['id'],(string)$p['name']);
        try{$png=academy_preview_png(['colour'=>$g['colour'],'student_name'=>$name,'course_title'=>$g['title'],'duration'=>$g['duration']]);}
        catch(Throwable $e){error_log('Academy preview rendering failed: '.get_class($e));if($action==='preview_image'){http_response_code(503);exit;}flash('academy_student_notice','Certificate artwork is temporarily unavailable. No payment was started. Please contact Academy support.');redirect($base.'?action=certificates');}
        if($action==='preview_image'){
            header('Content-Type: image/png');header('Content-Length: '.strlen($png));header('Cache-Control: private, no-store, max-age=0');header('X-Content-Type-Options: nosniff');header('Cross-Origin-Resource-Policy: same-origin');header("Content-Security-Policy: default-src 'none'; sandbox");echo $png;exit;
        }
        ac_head('Preview your certificate');
        require_once __DIR__.'/views/academy-guide.php';
        echo '<div class="ac-student">';ac_guide('preview',(int)$user['id']);echo '<section class="ac-student-panel"><h2>Review your certificate</h2><style>@media print{.ac-preview-protected{display:none!important}}</style><img class="preview ac-preview-protected" style="max-width:100%;height:auto;user-select:none;-webkit-user-drag:none" draggable="false" alt="Watermarked certificate preview" src="/academy.php?action=preview_image"><p>'.e($name).' · '.e($g['title']).' · '.e($g['duration']).'</p>';
        ac_form('checkout');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'"><input type="hidden" name="name" value="'.e($name).'"><p>Total payable: <strong>₦'.number_format(ACADEMY_FEE_MINOR/100,2).'</strong></p><div class="ac-cert-warning"><strong>Final confirmation</strong><p>Check the exact name above. Starting payment locks your certificate details. Once issued, you cannot regenerate it with a different name.</p></div><label class="ac-cert-check"><input type="checkbox" name="confirm_name" value="1" required> I confirm the certificate details are correct.</label><small>Review the amount before authorizing payment.</small><p>'.(academy_checkout_enabled()?'<button>Pay Now</button>':'<button disabled>Payment unavailable</button>').'</p></form><a href="'.$base.'?action=certificates">Back to certificates</a></section></div>';ac_end();exit;
    }
    if ($action==='learn'){
        $course=academy_learning_access((int)$user['id'],(int)($_GET['course']??0));
        if(!$course){http_response_code(403);ac_head('Learning access unavailable');echo '<p>Your course application must be approved before you can access the learning area.</p><a href="'.$base.'?action=courses">My courses</a>';ac_end();exit;}
        define('IWORKS_ACADEMY_LEARNING_VIEW',true);require __DIR__.'/views/academy-learning.php';exit;
    }
    if ($action==='material'){
        if(method_is('POST')){http_response_code(405);exit;}
        $m=academy_material_download((int)$user['id'],(int)($_GET['course']??0),(int)($_GET['lesson']??0),(int)($_GET['id']??0));
        if(!$m){http_response_code(404);exit('Material unavailable.');}
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._-]/','_',$m['filename']).'"');header('Content-Length: '.strlen($m['content']));header('X-Content-Type-Options: nosniff');echo $m['content'];exit;
    }
    if ($action==='exam_start'){
        if(!method_is('POST')){http_response_code(405);exit;}
        require_csrf();$cid=(int)($_POST['course']??0);$lid=(int)($_POST['lesson']??0);$id=(int)($_POST['id']??0);
        try{if(!academy_exam_start((int)$user['id'],$cid,$lid,$id,(int)($_POST['attempt_generation']??0)))flash('academy_student_notice','Exam unavailable, completed or expired.');}
        catch(InvalidArgumentException $e){flash('academy_student_notice',$e->getMessage());}
        redirect($base.'?action=learn&course='.$cid.'&lesson='.$lid.'#ac-assessment-'.$id);
    }
    if ($action==='assessment_submit'){
        if(!method_is('POST')){http_response_code(405);exit;}
        require_csrf();$cid=(int)($_POST['course']??0);$lid=(int)($_POST['lesson']??0);$id=(int)($_POST['id']??0);
        if(!academy_learning_lesson((int)$user['id'],$cid,$lid)){http_response_code(403);exit('Assessment unavailable.');}
        try{$attachment=academy_assignment_upload_read($_FILES['assignment_file']??[]);$saved=academy_assessment_submit((int)$user['id'],$cid,$lid,$id,$_POST,$attachment);flash('academy_student_notice',$saved?'Assessment submitted.':'No new submission saved; check your assessment status.');}
        catch(InvalidArgumentException $e){flash('academy_student_notice',$e->getMessage());}
        redirect($base.'?action=learn&course='.$cid.'&lesson='.$lid);
    }
    if ($action==='lesson_complete'){
        if(!method_is('POST')){http_response_code(405);exit;}
        require_csrf();$cid=(int)($_POST['course']??0);$lid=(int)($_POST['lesson']??0);
        if(!academy_learning_lesson((int)$user['id'],$cid,$lid)){http_response_code(403);exit('Lesson unavailable.');}
        academy_learning_complete((int)$user['id'],$cid,$lid);
        flash('academy_student_notice','Lesson progress saved.');
        redirect($base.'?action=learn&course='.$cid.'&lesson='.$lid);
    }
    if ($action==='callback'){
        $ref=(string)($_GET['reference']??'');
        if(db_one('SELECT id FROM academy_certificates WHERE payment_reference=:ref AND student_id=:student',['ref'=>$ref,'student'=>$user['id']])){
            try{flash('academy_student_notice',academy_confirm($ref)?'Payment verified. Your certificate is available to download.':'Payment is not yet confirmed. Check again later; do not pay twice.');}
            catch(Throwable $e){error_log('Academy callback verification deferred: '.get_class($e));flash('academy_student_notice','We are checking your payment. You can return later or check the payment status.');}
        }
        redirect($base.'?action=certificates');
    }
    if ($action==='download'){
        $cert=academy_owned_certificate((int)($_GET['id']??0),(int)$user['id']);if(!$cert){http_response_code(404);exit('Certificate unavailable.');}
        $pdf=academy_pdf($cert); ac_notify($cert,$user);
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$cert['certificate_number'].'.pdf"');echo $pdf;exit;
    }
    if (method_is('POST')) {
        require_csrf();
        if ($action==='request_certificate'){
            try{
                if(($_POST['confirm_name']??'')!=='1')throw new InvalidArgumentException('Confirm the exact certificate name before submitting your request.');
                $created=academy_request_submit((int)$user['id'],(int)($_POST['course']??0),(string)($_POST['name']??''));
                flash('academy_student_notice',$created?'Certification request submitted. You will be notified after review.':'Your request is already submitted.');
            }catch(InvalidArgumentException $e){flash('academy_student_notice',$e->getMessage());}
            redirect($base.'?action=certificates');
        }
        if ($action==='enroll'){
            $course=(int)($_POST['course']??0);
            if(!db_one('SELECT id FROM academy_courses WHERE id=:id AND active=1',['id'=>$course])) throw new RuntimeException('Course unavailable.');
            $created=db_query("INSERT IGNORE INTO academy_enrollments (student_id,course_id,status,created_at,updated_at) VALUES (:student,:course,'requested',:created,:updated)",['student'=>$user['id'],'course'=>$course,'created'=>now(),'updated'=>now()])->rowCount();
            if($created===1){$courseInfo=db_one('SELECT title FROM academy_courses WHERE id=:id',['id'=>$course]);try{notify_roles(['admin'],'New course application',($courseInfo['title']??'Course').' — review the student application.','/academy.php?action=admin&tab=enrollments');}catch(Throwable $e){error_log('Academy enrollment notice deferred');}}
            if((int)($_SESSION['academy_course_intent']??0)===$course)unset($_SESSION['academy_course_intent']);
            flash('academy_student_notice','Course application submitted. The Academy administrator will review your request.');
            redirect($base.'?action='.(in_array(($_POST['return_to']??''),['dashboard','courses','certificates'],true)?$_POST['return_to']:'courses'));
        }
        if ($action==='inquiry'){http_response_code(403);exit('Student messaging is unavailable.');}
        if ($action==='profile'){
            $json=($_SERVER['HTTP_ACCEPT']??'')==='application/json';
            $name=trim((string)($_POST['name']??''));$phone=trim((string)($_POST['phone']??''));
            if(!academy_valid_name($name)||strlen($phone)<7||strlen($phone)>40){
                if($json){http_response_code(422);header('Content-Type: application/json');header('Cache-Control: no-store');echo json_encode(['ok'=>false,'message'=>'Enter a valid name and phone.']);exit;}
                flash('academy_student_notice','Enter a valid name and phone.');redirect($base.'?action=profile');
            }
            db_execute('UPDATE users SET full_name=:name,phone=:phone WHERE id=:id',['name'=>$name,'phone'=>$phone,'id'=>$user['id']]);
            if($json){header('Content-Type: application/json');header('Cache-Control: no-store');echo json_encode(['ok'=>true,'name'=>$name,'phone'=>$phone],JSON_INVALID_UTF8_SUBSTITUTE);exit;}
            flash('academy_student_notice','Profile updated.');redirect($base.'?action=profile');
        }
        if ($action==='checkout') {
            $graduate=(int)($_POST['graduate']??0);
            $p=$_SESSION['academy_preview']??[];
            $name=academy_request_name((int)$user['id'],$graduate,trim((string)($_POST['name']??'')));
            if(($_POST['confirm_name']??'')!=='1'||($p['student']??0)!==(int)$user['id']||($p['graduate']??0)!==$graduate||($p['expires']??0)<time()||($p['name']??'')!==$name){
                flash('academy_student_notice','Please open the certificate preview and confirm its details before paying.');redirect($base.'?action=certificates');
            }
            redirect(academy_new_checkout($user,$graduate,$name));
        }
        if ($action==='resume') redirect(academy_resume_checkout($user,(int)($_POST['graduate']??0)));
        if ($action==='recheck'){
            $ref=(string)($_POST['reference']??'');
            if(db_one('SELECT id FROM academy_certificates WHERE payment_reference=:ref AND student_id=:student',['ref'=>$ref,'student'=>$user['id']])){
                try{flash('academy_student_notice',academy_confirm($ref)?'Payment verified. Your certificate is available to download.':'Payment is not yet confirmed. Check again later; do not pay twice.');}
                catch(Throwable $e){error_log('Academy student payment verification deferred: '.get_class($e));flash('academy_student_notice','Paystack verification is temporarily unavailable. Your existing payment reference is unchanged.');}
            }
            redirect($base.'?action=certificates');
        }
        if ($action==='preview'){
            $graduate=(int)($_POST['graduate']??0);
            $g=db_one('SELECT id FROM academy_graduates WHERE id=:id AND student_id=:student',['id'=>$graduate,'student'=>$user['id']]);
            $name=$g?academy_request_name((int)$user['id'],$graduate,trim((string)($_POST['name']??''))):'';
            if(!$g||!academy_valid_name($name)){flash('academy_student_notice','Select an approved certificate and enter a valid name.');redirect($base.'?action=certificates');}
            $_SESSION['academy_preview']=['student'=>(int)$user['id'],'graduate'=>$graduate,'name'=>$name,'expires'=>time()+1800];
            redirect($base.'?action=certificate_preview');
        }
    }
    define('IWORKS_ACADEMY_STUDENT_VIEW',true);
    require __DIR__.'/views/academy-student.php';
    exit;
} catch(Throwable $e){http_response_code(400);ac_head('Academy request unavailable');echo '<p>We could not complete this request. Check your information or contact support.</p><a href="'.$base.'">Return to Academy</a>';ac_end();error_log('Academy request failed: '.get_class($e));}
