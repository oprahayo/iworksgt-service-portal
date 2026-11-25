<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/academy.php';
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
        require __DIR__.'/views/partials/header.php';
        echo '<link rel="stylesheet" href="/assets/css/academy.css"><div class="academy-workspace"><h1>'.e($title).'</h1>';
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
    if ($action==='register') {
        if (is_logged_in()) redirect(has_role(['student']) ? $base : portal_url('dashboard'));
        if (method_is('POST')) {
            require_csrf();
            $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??'')));
            $phone=trim((string)($_POST['phone']??''));$password=(string)($_POST['password']??'');
            if (!academy_valid_name($name)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strlen($phone)<7||strlen($phone)>40||strlen($password)<12||strlen($password)>72||!email_enabled()||find_user_by_email($email)) throw new RuntimeException('Check the registration details or sign in if you already have an account.');
            $ip=iworks_auth_ip(); if (!iworks_auth_limit_allows('academy_register_ip',$ip,8,3600)) throw new RuntimeException('Try again later.');
            iworks_auth_limit_record('academy_register_ip',$ip,3600);
            $id=create_user(['full_name'=>$name,'email'=>$email,'phone'=>$phone,'password'=>$password,'role'=>'student']);
            start_otp_challenge($id,'verify_email'); redirect(portal_url('otp'));
        }
        ac_head('Student registration');ac_form('register');ac_input('name','Full name');ac_input('email','Email','','email');ac_input('phone','Phone','','tel');ac_input('password','Password (12 or more characters)','','password');echo '<button>Register</button></form><a href="/index.php?page=login">Already registered? Sign in</a>';ac_end();exit;
    }
    if ($action==='admin') {
        require_roles(['admin']);
        if(method_is('POST')){
            require_csrf();$mode=(string)($_POST['mode']??'');
            if($mode==='create_course'){
                $title=trim((string)($_POST['title']??''));$colour=(string)($_POST['colour']??'');
                $slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($title)),'-');
                if($title===''||strlen($title)>160||$slug===''||strlen($slug)>100||!in_array($colour,['orange','purple','green','blue'],true)) throw new RuntimeException('Invalid course.');
                db_execute('INSERT INTO academy_courses (slug,title,colour) VALUES (:slug,:title,:colour)',compact('slug','title','colour'));
            } elseif($mode==='enrollment'){
                $status=(string)($_POST['status']??'');
                if(!in_array($status,['enrolled','cancelled'],true)) throw new RuntimeException('Invalid enrollment status.');
                db_execute("UPDATE academy_enrollments SET status=:status,updated_at=:updated WHERE id=:id AND status IN ('requested','enrolled')",['status'=>$status,'updated'=>now(),'id'=>(int)$_POST['id']]);
            } elseif($mode==='approve'){
                $student=find_user_by_email((string)($_POST['email']??''));$course=(int)($_POST['course']??0);$duration=trim((string)($_POST['duration']??''));
                if(!$student||$student['role']!=='student'||!is_email_verified($student)||$duration===''||strlen($duration)>80||!db_one('SELECT id FROM academy_courses WHERE id=:id AND active=1',['id'=>$course])) throw new RuntimeException('Select a verified student, active course and duration.');
                db()->beginTransaction();
                try {
                db_execute('INSERT INTO academy_graduates (student_id,course_id,duration,approved_by,approved_at) VALUES (:student,:course,:duration,:admin,:approved)',['student'=>$student['id'],'course'=>$course,'duration'=>$duration,'admin'=>current_user()['id'],'approved'=>now()]);
                db_execute("INSERT INTO academy_enrollments (student_id,course_id,status,created_at,updated_at) VALUES (:student,:course,'completed',:created,:updated) ON DUPLICATE KEY UPDATE status='completed',updated_at=VALUES(updated_at)",['student'=>$student['id'],'course'=>$course,'created'=>now(),'updated'=>now()]);
                db()->commit();
                } catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
            } elseif($mode==='colour'){
                $colour=(string)($_POST['colour']??'');if(!in_array($colour,['orange','purple','green','blue'],true)) throw new RuntimeException('Invalid colour.');
                db_execute('UPDATE academy_courses SET colour=:colour WHERE id=:id',['colour'=>$colour,'id'=>(int)$_POST['course']]);
            } elseif($mode==='revoke'){
                db_execute("UPDATE academy_certificates SET status='revoked' WHERE id=:id AND status='issued'",['id'=>(int)$_POST['id']]);
            }
            redirect($base.'?action=admin');
        }
        $courses=db_all('SELECT * FROM academy_courses ORDER BY title');
        ac_head('Academy management'); echo '<h2>Create a course</h2>'; ac_form('admin');echo '<input type="hidden" name="mode" value="create_course">';ac_input('title','Course title');echo '<label>Certificate colour<select name="colour">';foreach(['orange','purple','green','blue'] as $colour)echo '<option>'.e($colour).'</option>';echo '</select></label><button>Create Course</button></form>';
        echo '<h2>Enrollment requests</h2>';
        foreach(db_all("SELECT en.*,u.full_name,c.title FROM academy_enrollments en JOIN users u ON u.id=en.student_id JOIN academy_courses c ON c.id=en.course_id WHERE en.status IN ('requested','enrolled') ORDER BY en.id DESC LIMIT 100") as $en){
            echo '<article>'.e($en['full_name']).' — '.e($en['title']).' — '.e($en['status']);ac_form('admin');echo '<input type="hidden" name="mode" value="enrollment"><input type="hidden" name="id" value="'.(int)$en['id'].'"><label>Status<select name="status"><option value="enrolled">Enrolled</option><option value="cancelled">Cancelled</option></select></label><button>Save</button></form></article>';
        }
        echo '<h2>Approve existing graduate</h2>'; ac_form('admin');echo '<input type="hidden" name="mode" value="approve">';ac_input('email','Verified student email','','email');echo '<label>Course<select name="course">';
        foreach($courses as $c) echo '<option value="'.(int)$c['id'].'">'.e($c['title']).'</option>';echo '</select></label>';ac_input('duration','Training duration (e.g. 12 weeks)');echo '<button>Approve course completion</button></form><h2>Course certificate colours</h2>';
        foreach($courses as $c){ac_form('admin');echo '<input type="hidden" name="mode" value="colour"><input type="hidden" name="course" value="'.(int)$c['id'].'"><strong>'.e($c['title']).'</strong><select name="colour">';foreach(['orange','purple','green','blue'] as $colour)echo '<option'.($colour===$c['colour']?' selected':'').'>'.e($colour).'</option>';echo '</select><button>Save colour</button></form>';}
        echo '<h2>Certification records</h2>';foreach(db_all('SELECT * FROM academy_certificates ORDER BY id DESC LIMIT 100') as $c){echo '<article>'.e($c['student_name']).' — '.e($c['course_title']).' — '.e($c['status']).' — '.e($c['certificate_number']);if($c['status']==='issued'){ac_form('admin');echo '<input type="hidden" name="mode" value="revoke"><input type="hidden" name="id" value="'.(int)$c['id'].'"><button>Revoke</button></form>';}echo '</article>';}ac_end();exit;
    }
    require_roles(['student']);$user=current_user();
    if (!is_email_verified($user)) { http_response_code(403);exit('Verify your email first.'); }
    if ($action==='callback'){
        $ref=(string)($_GET['reference']??'');
        if(db_one('SELECT id FROM academy_certificates WHERE payment_reference=:ref AND student_id=:student',['ref'=>$ref,'student'=>$user['id']])) academy_confirm($ref);
        redirect($base);
    }
    if ($action==='download'){
        $cert=academy_owned_certificate((int)($_GET['id']??0),(int)$user['id']);if(!$cert){http_response_code(404);exit('Certificate unavailable.');}
        $pdf=academy_pdf($cert); ac_notify($cert,$user);
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$cert['certificate_number'].'.pdf"');echo $pdf;exit;
    }
    if (method_is('POST')) {
        require_csrf();
        if ($action==='enroll'){
            $course=(int)($_POST['course']??0);
            if(!db_one('SELECT id FROM academy_courses WHERE id=:id AND active=1',['id'=>$course])) throw new RuntimeException('Course unavailable.');
            db_execute("INSERT IGNORE INTO academy_enrollments (student_id,course_id,status,created_at,updated_at) VALUES (:student,:course,'requested',:created,:updated)",['student'=>$user['id'],'course'=>$course,'created'=>now(),'updated'=>now()]);
            redirect($base.'#courses');
        }
        if ($action==='profile'){
            $name=trim((string)($_POST['name']??''));$phone=trim((string)($_POST['phone']??''));
            if(!academy_valid_name($name)||strlen($phone)<7||strlen($phone)>40)throw new RuntimeException('Enter a valid name and phone.');
            db_execute('UPDATE users SET full_name=:name,phone=:phone WHERE id=:id',['name'=>$name,'phone'=>$phone,'id'=>$user['id']]);redirect($base);
        }
        if ($action==='checkout') redirect(academy_new_checkout($user,(int)($_POST['graduate']??0),trim((string)($_POST['name']??''))));
        if ($action==='resume') redirect(academy_resume_checkout($user,(int)($_POST['graduate']??0)));
        if ($action==='recheck'){
            $ref=(string)($_POST['reference']??'');
            if(db_one('SELECT id FROM academy_certificates WHERE payment_reference=:ref AND student_id=:student',['ref'=>$ref,'student'=>$user['id']])) academy_confirm($ref);redirect($base);
        }
        if ($action==='preview'){
            $g=db_one('SELECT g.*,c.title,c.colour FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id WHERE g.id=:id AND g.student_id=:student',['id'=>(int)($_POST['graduate']??0),'student'=>$user['id']]);
            $name=trim((string)($_POST['name']??''));if(!$g||!academy_valid_name($name))throw new RuntimeException('Invalid certificate application.');
            $svg=academy_render(['colour'=>$g['colour'],'student_name'=>$name,'course_title'=>$g['title'],'duration'=>$g['duration']],true);
            ac_head('Preview your certificate');echo '<img class="preview" alt="Certificate preview" src="data:image/svg+xml;base64,'.base64_encode($svg).'">';ac_form('checkout');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'"><input type="hidden" name="name" value="'.e($name).'"><p>Total payable: <strong>₦'.number_format(ACADEMY_FEE_MINOR/100,2).'</strong></p><small>Review the payment amount before authorizing payment. Any additional provider charge, if applicable, will be shown at checkout.</small><p>'.(academy_checkout_enabled()?'<button>Pay Now</button>':'<button disabled>Payment unavailable</button>').'</p></form><a href="'.$base.'">Back</a>';ac_end();exit;
        }
    }
    ac_head('Welcome, '.(string)$user['full_name']);echo '<nav><a href="#certificates">My Certificates</a><a href="#courses">My Courses</a><a href="#profile">My Profile</a></nav><h2 id="profile">Complete your profile</h2>';ac_form('profile');ac_input('name','Full name',$user['full_name']);ac_input('phone','Phone',(string)$user['phone'],'tel');echo '<button>Save profile</button></form><h2 id="certificates">My certificates</h2>';
    $graduates=db_all('SELECT g.*,c.title,c.colour FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id WHERE g.student_id=:student',['student'=>$user['id']]);
    if(!$graduates)echo '<section>Awaiting administrator approval of your completed course.</section>';
    foreach($graduates as $g){
        echo '<article><h3>'.e($g['title']).'</h3><p>'.e($g['duration']).' · '.e(ucfirst($g['colour'])).' certificate</p>';
        $cert=db_one('SELECT * FROM academy_certificates WHERE graduate_id=:id',['id'=>$g['id']]);
        if(!$cert){ac_form('preview');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'">';ac_input('name','Name on certificate',$user['full_name']);echo '<button>Get Certificate</button></form>';}
        elseif($cert['status']==='issued'){echo '<p>Issued: '.e($cert['certificate_number']).'</p><a href="/academy.php?action=download&amp;id='.(int)$cert['id'].'">Download Certificate</a>';ac_notify($cert,$user);}
        elseif($cert['status']==='pending'){echo '<p>Payment pending. Continue your existing payment or check its status.</p>';if(!empty($cert['checkout_url'])){ac_form('resume');echo '<input type="hidden" name="graduate" value="'.(int)$g['id'].'"><button>Continue Payment</button></form>';}ac_form('recheck');echo '<input type="hidden" name="reference" value="'.e($cert['payment_reference']).'"><button>Check Payment</button></form>';}
        else echo '<p>Certificate revoked. Contact support.</p>';echo '</article>';
    }
    echo '<h2 id="courses">Courses</h2>';
    foreach(db_all("SELECT c.*,en.status AS enrollment_status FROM academy_courses c LEFT JOIN academy_enrollments en ON en.course_id=c.id AND en.student_id=:student WHERE c.active=1 ORDER BY c.title",['student'=>$user['id']]) as $c){
        echo '<article><h3>'.e($c['title']).'</h3>';
        if($c['enrollment_status']) echo '<p>'.e(ucfirst($c['enrollment_status'])).'</p>';
        else {ac_form('enroll');echo '<input type="hidden" name="course" value="'.(int)$c['id'].'"><button>Enroll</button></form>';}
        echo '</article>';
    }
    ac_end();
} catch(Throwable $e){http_response_code(400);ac_head('Academy request unavailable');echo '<p>We could not complete this request. Check your information or contact support.</p><a href="'.$base.'">Return to Academy</a>';ac_end();error_log('Academy request failed: '.get_class($e));}
