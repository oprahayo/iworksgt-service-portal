<?php
declare(strict_types=1);
// Included only by the authenticated Academy admin controller.
if (!defined('IWORKS_ACADEMY_ADMIN_VIEW') || !has_role(['admin'])) { http_response_code(403); exit; }
function ac_admin_url(string $tab='students',array $params=[]):string{
    return '/academy.php?'.http_build_query(['action'=>'admin','tab'=>$tab]+$params);
}
function ac_admin_link(string $label,string $tab,array $params=[],string $class='ac-link'):void{
    echo '<a class="'.e($class).'" href="'.e(ac_admin_url($tab,$params)).'">'.e($label).'</a>';
}
function ac_admin_status(string $status):void{
    $labels=['requested'=>'New request','enrolled'=>'Enrolled','completed'=>'Completed','cancelled'=>'Cancelled','ready'=>'Ready to certify','pending'=>'Payment pending','issued'=>'Issued','revoked'=>'Revoked','unverified'=>'Email unverified','verified'=>'Verified','inactive'=>'Inactive','none'=>'Not yet enrolled','sent'=>'Sent','queued'=>'Awaiting email'];
    echo '<span class="ac-status ac-status-'.e($status).'">'.e($labels[$status]??ucfirst($status)).'</span>';
}
function ac_admin_post(string $mode,string $tab,int $student=0):void{
    ac_form('admin');
    echo '<input type="hidden" name="mode" value="'.e($mode).'"><input type="hidden" name="return_tab" value="'.e($tab).'">';
    if($student>0)echo '<input type="hidden" name="student_id" value="'.$student.'">';
}
function ac_admin_enrollment_action(array $r,string $tab,int $student=0):void{
    $state=(string)($r['enrollment_status']??$r['status']??'');
    $id=(int)($r['enrollment_id']??$r['id']??0);
    $options=match($state){'requested'=>['enrolled'=>'Accept','cancelled'=>'Cancel'],'enrolled'=>['requested'=>'Return to review','cancelled'=>'Cancel'],'cancelled'=>['enrolled'=>'Restore enrollment','requested'=>'Reopen request'],default=>[]};
    if(!$id||!$options)return;
    echo '<details class="ac-contact" data-ac-popup><summary>'.($state==='cancelled'?'Restore / reopen':'Change status').'</summary>';
    ac_admin_post('enrollment',$tab,$student);
    echo '<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="expected" value="'.e($state).'"><label>New status<select name="status" required><option value="">Choose status</option>';
    foreach($options as $value=>$label)echo '<option value="'.e($value).'">'.e($label).'</option>';
    echo '</select></label><label>Reason for change<textarea name="reason" maxlength="500" rows="2" required></textarea></label><button>Save status</button></form></details>';
}
function ac_admin_empty(string $message):void{echo '<div class="ac-empty">'.e($message).'</div>';}
function ac_admin_table(array $headers):void{
    echo '<div class="ac-table-wrap"><table class="ac-table" role="table"><thead role="rowgroup"><tr role="row">';
    foreach($headers as $h)echo '<th scope="col" role="columnheader">'.e($h).'</th>';
    echo '</tr></thead><tbody role="rowgroup">';
}
function ac_admin_table_end():void{echo '</tbody></table></div>';}
function ac_admin_pager(string $tab,int $page,int $total,array $params=[]):void{
    $pages=max(1,(int)ceil($total/25));
    echo '<div class="ac-pagination"><span>'.number_format($total).' record'.($total===1?'':'s').' · Page '.$page.' of '.$pages.'</span><div>';
    if($page>1)ac_admin_link('Previous',$tab,['p'=>$page-1]+$params,'ac-secondary');
    if($page<$pages)ac_admin_link('Next',$tab,['p'=>$page+1]+$params,'ac-secondary');
    echo '</div></div>';
}
function ac_admin_colour_options(string $selected='orange'):void{
    foreach(['orange','purple','green','blue'] as $colour)echo '<option value="'.$colour.'"'.($colour===$selected?' selected':'').'>'.ucfirst($colour).'</option>';
}
function ac_admin_search(string $tab,string $query,string $status='',array $options=[]):void{
    echo '<form class="ac-filter" method="get" action="/academy.php"><input type="hidden" name="action" value="admin"><input type="hidden" name="tab" value="'.e($tab).'"><label>Search<input type="search" name="q" value="'.e($query).'" placeholder="Name, email or course"></label>';
    if(in_array($tab,['certificates','requests'],true)&&(int)($_GET['student']??0)>0)echo '<input type="hidden" name="student" value="'.(int)$_GET['student'].'">';
    if($options){echo '<label>Status<select name="status"><option value="">All statuses</option>';foreach($options as $value=>$label)echo '<option value="'.e($value).'"'.($value===$status?' selected':'').'>'.e($label).'</option>';echo '</select></label>';}
    echo '<button>Search</button>';ac_admin_link('Reset',$tab,[],'ac-secondary');echo '</form>';
}
function ac_admin_search_params(string $query,array $columns):array{
    if($query==='')return ['',[]];
    $parts=[];$params=[];
    foreach($columns as $i=>$column){$key='search'.$i;$parts[]=$column.' LIKE :'.$key;$params[$key]='%'.$query.'%';}
    return [' AND ('.implode(' OR ',$parts).')',$params];
}
function ac_admin_student_state(array $u):string{
    if(($u['status']??'')!=='active'||($u['account_state']??'')!=='active')return 'inactive';
    return is_email_verified($u)?'verified':'unverified';
}
function ac_admin_count(string $sql,array $params=[]):int{return (int)(db_one($sql,$params)['n']??0);}
function ac_admin_certificate_state(array $row):string{return (string)($row['certificate_status']??'ready');}
$tab=(string)($_GET['tab']??'requests');
if(!in_array($tab,['students','student','enrollments','courses','certificates','requests','analytics'],true))$tab='students';
$query=mb_substr(trim((string)($_GET['q']??'')),0,100);
$status=(string)($_GET['status']??'');
$page=max(1,min(100000,(int)($_GET['p']??1)));
$notice=flash('academy_admin_notice');$error=flash('academy_admin_error');
ac_head('Academy');
echo '<div class="ac-admin"><div class="ac-intro"><p>Manage students, courses and certificates.</p><a class="ac-secondary" href="/student/register" target="_blank" rel="noopener">Student registration link</a></div><nav class="ac-tabs" aria-label="Academy sections">';
foreach(['requests'=>'Certification requests','students'=>'Students','enrollments'=>'Enrollments','courses'=>'Courses','certificates'=>'Certificates','analytics'=>'Analytics'] as $key=>$label){$active=$key===$tab||($key==='students'&&$tab==='student');echo '<a href="'.e(ac_admin_url($key)).'"'.($active?' class="active" aria-current="page"':'').'>'.e($label).($key==='requests'?' ('.ac_admin_count("SELECT COUNT(*) n FROM academy_certification_requests WHERE status='pending'").')':'').'</a>';}
echo '</nav>';
if($notice)echo '<div class="ac-message" role="status">'.e($notice).'</div>';
if($error)echo '<div class="ac-message ac-error" role="alert">'.e($error).'</div>';
if(!academy_checkout_enabled()&&in_array($tab,['students','student','certificates'],true))echo '<div class="ac-callout">Payments are off. Student approvals are available.</div>';
if($tab==='analytics'){
    echo '<section class="ac-panel"><div class="ac-panel-title"><div><h2>Learning overview</h2><p>Current Academy activity and course progress.</p></div></div>';
    $metrics=[
      ['Students',ac_admin_count("SELECT COUNT(*) n FROM users WHERE role='student' AND deleted_at IS NULL")],
      ['Active enrollments',ac_admin_count("SELECT COUNT(*) n FROM academy_enrollments en JOIN users u ON u.id=en.student_id WHERE en.status='enrolled' AND u.role='student' AND u.deleted_at IS NULL AND u.status='active' AND u.account_state='active'")],
      ['Awaiting grading',ac_admin_count("SELECT COUNT(*) n FROM academy_assessment_submissions s JOIN academy_assessments a ON a.id=s.assessment_id JOIN users u ON u.id=s.student_id WHERE a.kind='assignment' AND s.score_percent IS NULL AND u.role='student' AND u.deleted_at IS NULL")],
      ['Certificates issued',ac_admin_count("SELECT COUNT(*) n FROM academy_certificates ce JOIN users u ON u.id=ce.student_id WHERE ce.status='issued' AND u.role='student' AND u.deleted_at IS NULL")]
    ];
    echo '<div class="ac-metric-strip">';
    foreach($metrics as [$label,$value])echo '<div class="ac-metric"><strong>'.number_format($value).'</strong><small>'.($label==='Awaiting grading'?'<a href="#assignment-review">Awaiting grading</a>':e($label)).'</small></div>';
    echo '</div><h3>Course progress</h3><p>Completed enrollments and outstanding course applications.</p>';
    $rows=db_all("SELECT c.id,c.title,c.active,COALESCE(x.pending,0) pending,COALESCE(x.enrolled,0) enrolled,COALESCE(x.completed,0) completed,COALESCE(x.cancelled,0) cancelled FROM academy_courses c LEFT JOIN (SELECT en.course_id,SUM(en.status='requested') pending,SUM(en.status='enrolled') enrolled,SUM(en.status='completed') completed,SUM(en.status='cancelled') cancelled FROM academy_enrollments en JOIN users u ON u.id=en.student_id WHERE u.role='student' AND u.deleted_at IS NULL GROUP BY en.course_id) x ON x.course_id=c.id ORDER BY c.title");
    if(!$rows)ac_admin_empty('No courses available.');
    else{
      ac_admin_table(['Course','Pending','Enrolled','Completed','Completion']);
      foreach($rows as $r){
        $total=(int)$r['enrolled']+(int)$r['completed'];$pct=$total?(int)round(100*(int)$r['completed']/$total):0;
        echo '<tr role="row"><td role="cell"><strong>'.e($r['title']).'</strong><small>'.($r['active']?'Active':'Inactive').' · '.(int)$r['cancelled'].' cancelled</small></td><td role="cell" data-label="Pending">'.(int)$r['pending'].'</td><td role="cell" data-label="Enrolled">'.(int)$r['enrolled'].'</td><td role="cell" data-label="Completed">'.(int)$r['completed'].'</td><td role="cell" data-label="Completion"><strong>'.($total?$pct.'%':'—').'</strong>';
        if($total)echo '<progress class="ac-analytics-track" max="100" value="'.$pct.'" aria-label="Course completion rate"></progress>';
        echo '</td></tr>';
      }
      ac_admin_table_end();
    }
    echo '<small>Completion rate = completed ÷ (enrolled + completed). Counts represent current records, not a historical trend.</small></section>';
    echo '<section class="ac-panel" id="assignment-review"><div class="ac-panel-title"><div><h2>Assignment review</h2><p>Grade pending work without opening individual course lessons.</p></div></div>';
    ac_admin_search('analytics',$query);
    [$where,$params]=ac_admin_search_params($query,['u.full_name','u.email','c.title','a.title']);
    $from=" FROM academy_assessment_submissions s JOIN academy_assessments a ON a.id=s.assessment_id JOIN academy_lessons l ON l.id=a.lesson_id JOIN academy_modules m ON m.id=l.module_id JOIN academy_courses c ON c.id=m.course_id JOIN users u ON u.id=s.student_id LEFT JOIN academy_assignment_files f ON f.submission_id=s.id WHERE a.kind='assignment' AND s.score_percent IS NULL AND u.role='student' AND u.deleted_at IS NULL".$where;
    $total=ac_admin_count('SELECT COUNT(*) n'.$from,$params);$page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
    $pending=db_all('SELECT s.id,s.student_id,s.submitted_at,s.response_json,u.full_name,c.id course_id,c.title course_title,l.id lesson_id,a.title,a.pass_percent,f.filename'.$from.' ORDER BY s.submitted_at ASC,s.id ASC LIMIT 25 OFFSET '.$offset,$params);
    if(!$pending)ac_admin_empty('No assignments awaiting grading in this view.');
    else{
      ac_admin_table(['Student','Assignment','Submitted','Action']);
      foreach($pending as $item){
        echo '<tr role="row"><td role="cell"><strong>'.e($item['full_name']).'</strong></td><td role="cell" data-label="Assignment"><strong>'.e($item['title']).'</strong><small>'.e($item['course_title']).'</small></td><td role="cell" data-label="Submitted">'.e(date('d M Y',strtotime($item['submitted_at']))).'</td><td role="cell"><div class="ac-actions">';
        echo '<details class="ac-contact" data-ac-popup><summary>Review assignment</summary><p><strong>'.e($item['full_name']).'</strong> · '.e($item['title']).'</p><p>Pass mark: '.(int)$item['pass_percent'].'%</p>';
        $response=json_decode($item['response_json'],true);if(is_string($response)&&$response!=='')echo '<details class="ac-assignment-response"><summary>Written response</summary><div class="ac-lesson-body">'.nl2br(e($response)).'</div></details>';
        if($item['filename'])echo '<p><a class="ac-link" href="/academy.php?action=assignment_file&amp;course='.(int)$item['course_id'].'&amp;lesson='.(int)$item['lesson_id'].'&amp;id='.(int)$item['id'].'">Download submitted PDF</a><small>'.e($item['filename']).'</small></p>';
        ac_admin_post('grade_pending','analytics');echo '<input type="hidden" name="id" value="'.(int)$item['id'].'"><label>Score (%)<input type="number" name="score" min="0" max="100" required></label><label>Feedback<textarea name="feedback" rows="3" maxlength="10000"></textarea></label><button>Save grade</button></form></details>';
        ac_admin_link('Student','student',['id'=>(int)$item['student_id']],'ac-link');
        echo '</div></td></tr>';
      }
      ac_admin_table_end();
    }
    ac_admin_pager('analytics',$page,$total,['q'=>$query]);echo '</section>';

}elseif($tab==='requests'){
    echo '<section class="ac-request-shell"><div class="ac-request-heading"><div><h2>Certification requests</h2><p>Review completed training and decide whether to approve certification.</p></div></div>';
    $options=['pending'=>'Awaiting review','approved'=>'Approved','declined'=>'Declined'];
    if(!isset($options[$status]))$status='pending';
    ac_admin_search('requests',$query,$status,$options);
    [$where,$params]=ac_admin_search_params($query,['u.full_name','u.email','c.title']);
    $where.=' AND r.status=:status';$params['status']=$status;
    $student=(int)($_GET['student']??0);if($student>0){$where.=' AND r.student_id=:student';$params['student']=$student;}
    $from=" FROM academy_certification_requests r JOIN users u ON u.id=r.student_id JOIN academy_courses c ON c.id=r.course_id WHERE u.role='student' AND u.deleted_at IS NULL".$where;
    $total=ac_admin_count('SELECT COUNT(*) n'.$from,$params);$page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
    $rows=db_all('SELECT r.*,u.full_name,u.email,c.title,c.slug'.$from.' ORDER BY r.submitted_at DESC,r.id DESC LIMIT 25 OFFSET '.$offset,$params);
    if(!$rows)ac_admin_empty('No certification requests in this view.');
    echo '<div class="ac-request-list">';
    foreach($rows as $r){
        echo '<article class="ac-request-row"><div class="ac-request-top"><div><h3>'.e($r['title']).'</h3><p class="ac-request-person">'.e($r['full_name']).' <span>·</span> '.e($r['email']).'</p></div><span class="ac-request-state state-'.e($r['status']).'">'.e(['pending'=>'Awaiting review','approved'=>'Approved','declined'=>'Declined'][$r['status']]).'</span></div><div class="ac-request-meta"><div><small>Certificate name</small><strong>'.e($r['student_name']).'</strong></div><div><small>Submitted</small><strong>'.e(date('d M Y',strtotime($r['submitted_at']))).'</strong></div><div><small>Request</small><strong>#'.(int)$r['id'].' · Attempt '.(int)$r['attempts'].'</strong></div></div>';
        if($r['review_note']!=='')echo '<div class="ac-query-reason"><strong>Administrator review</strong><p>'.nl2br(e($r['review_note'])).'</p></div>';
        if($r['status']==='pending'){
            $learning=db_one("SELECT en.status,lc.published FROM academy_enrollments en JOIN academy_learning_courses lc ON lc.course_id=en.course_id WHERE en.student_id=:student AND en.course_id=:course AND en.status IN ('enrolled','completed')",['student'=>(int)$r['student_id'],'course'=>(int)$r['course_id']]);
            if($learning&&$learning['published']){
                $progress=academy_admin_progress((int)$r['student_id'],(int)$r['course_id']);$summary=academy_completion_summary($progress['total'],$progress['completed'],$progress['results']);
                echo '<details class="ac-contact" data-ac-popup><summary>Learning evidence · '.($summary['ready']?'Checklist complete':'Review needed').'</summary><p>Lessons completed: '.$summary['completed'].' / '.$summary['lessons'].'</p><p>Assessments passed: '.$summary['passed'].' / '.$summary['assessments'].'</p><p>Not submitted: '.$summary['missing'].' · Awaiting grading: '.$summary['awaiting'].' · Not passed: '.$summary['failed'].'</p><small>Advisory evidence from currently published content. It does not replace administrator review.</small><p><a class="ac-link" href="/academy.php?action=transcript&amp;student='.(int)$r['student_id'].'&amp;course='.(int)$r['course_id'].'">Download academic transcript</a></p></details>';
            }else echo '<small>No active published learning record. Review training evidence separately.</small>';
            echo '<div class="ac-review-actions"><details class="ac-contact" data-ac-popup><summary>Approve request</summary>';ac_admin_post('review_request','requests');echo '<input type="hidden" name="request" value="'.(int)$r['id'].'"><input type="hidden" name="attempt" value="'.(int)$r['attempts'].'"><input type="hidden" name="decision" value="approved"><label>Training duration (weeks)<input type="number" name="duration" min="1" max="104" required value="'.e((string)(academy_recommended_weeks($r['slug'])??'')).'"></label><button>Approve for certification</button></form></details>';
            echo '<details class="ac-contact" data-ac-popup><summary>Decline request</summary>';ac_admin_post('review_request','requests');echo '<input type="hidden" name="request" value="'.(int)$r['id'].'"><input type="hidden" name="attempt" value="'.(int)$r['attempts'].'"><input type="hidden" name="decision" value="declined"><label>Reason for decline (shown to student)<textarea name="reason" maxlength="2000" rows="3" required></textarea></label><button class="ac-secondary">Decline and notify student</button></form></details></div>';
        }
        if($r['status']==='declined'){
            echo '<details class="ac-contact" data-ac-popup><summary>Reopen request</summary>';ac_admin_post('reopen_request','requests');echo '<input type="hidden" name="request" value="'.(int)$r['id'].'"><input type="hidden" name="attempt" value="'.(int)$r['attempts'].'"><label>Reason for reopening<textarea name="reason" maxlength="500" rows="2" required></textarea></label><button>Return to review</button></form></details>';
        }
        ac_admin_link('View student','student',['id'=>(int)$r['student_id']],'ac-secondary');echo '</article>';
    }
    echo '</div>';ac_admin_pager('requests',$page,$total,['q'=>$query,'status'=>$status,'student'=>$student]);echo '</section>';
}elseif($tab==='students'){
    echo '<section class="ac-panel"><div class="ac-panel-title"><div><h2>Students</h2><p>Registered Academy students and their progress.</p></div></div>';
    $options=['verified'=>'Verified','unverified'=>'Email unverified','inactive'=>'Inactive'];
    if(!isset($options[$status]))$status='';
    ac_admin_search('students',$query,$status,$options);
    [$where,$params]=ac_admin_search_params($query,['u.full_name','u.email']);
    if($status==='verified')$where.=" AND u.email_verified_at IS NOT NULL AND u.status='active' AND u.account_state='active'";
    if($status==='unverified')$where.=" AND u.email_verified_at IS NULL AND u.status='active' AND u.account_state='active'";
    if($status==='inactive')$where.=" AND (u.status<>'active' OR u.account_state<>'active')";
    $from=" FROM users u WHERE u.role='student' AND u.deleted_at IS NULL".$where;
    $total=ac_admin_count('SELECT COUNT(*) n'.$from,$params);$page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
    $rows=db_all("SELECT u.id,u.full_name,u.email,u.status,u.account_state,u.email_verified_at,
        (SELECT COUNT(*) FROM academy_graduates g WHERE g.student_id=u.id) AS completed_count,
        (SELECT COUNT(*) FROM academy_certificates ce WHERE ce.student_id=u.id AND ce.status='issued') AS certificate_count".$from." ORDER BY u.id DESC LIMIT 25 OFFSET ".$offset,$params);
    if(!$rows)ac_admin_empty('No students match your search. New students appear here after registration.');
    else{
        ac_admin_table(['Student','Account','Progress','Action']);
        foreach($rows as $u){echo '<tr role="row"><td role="cell"><strong>'.e($u['full_name']).'</strong><small>'.e($u['email']).'</small></td><td role="cell" data-label="Account">';ac_admin_status(ac_admin_student_state($u));echo '</td><td role="cell" data-label="Progress">'.(int)$u['completed_count'].' completed<small>'.(int)$u['certificate_count'].' issued certificate(s)</small></td><td role="cell">';ac_admin_link('View student','student',['id'=>(int)$u['id']],'ac-primary');echo '</td></tr>';}
        ac_admin_table_end();
    }
    ac_admin_pager('students',$page,$total,['q'=>$query,'status'=>$status]);echo '</section>';
}elseif($tab==='student'){
    $id=(int)($_GET['id']??0);
    $u=db_one("SELECT id,full_name,email,phone,status,account_state,email_verified_at,created_at FROM users WHERE id=:id AND role='student' AND deleted_at IS NULL",['id'=>$id]);
    ac_admin_link('← All students','students',[],'ac-secondary');
    if(!$u)ac_admin_empty('Student not found.');
    else{
        echo '<section class="ac-panel"><div class="ac-panel-title"><div><h2>'.e($u['full_name']).'</h2><p>'.e($u['email']).' · '.e((string)($u['phone']??'')).'</p></div>';ac_admin_status(ac_admin_student_state($u));echo '</div>';
        echo '<details class="ac-contact" data-ac-popup><summary>Edit student profile</summary>';ac_admin_post('edit_student','student',$id);echo '<label>Full name<input name="name" maxlength="150" value="'.e($u['full_name']).'" required></label><label>Phone<input name="phone" maxlength="40" value="'.e((string)$u['phone']).'" required></label><p>Email verification and issued certificate details are not changed here.</p><label>Reason for correction<textarea name="reason" maxlength="500" rows="2" required></textarea></label><button>Save profile</button></form></details>';
        echo '<div class="ac-approval"><h3>Certification requests</h3><p>Students submit their own requests. Review and approve or decline from the request queue.</p>';ac_admin_link('Review student requests','requests',['student'=>$id],'ac-primary');echo '</div>';
        echo '</section><section class="ac-panel">';
        $rows=db_all("SELECT c.id,c.title,c.colour,c.active,en.id AS enrollment_id,en.status AS enrollment_status,g.id AS graduate_id,g.duration,ce.status AS certificate_status,ce.certificate_number
            FROM academy_courses c LEFT JOIN academy_enrollments en ON en.course_id=c.id AND en.student_id=:en_student
            LEFT JOIN academy_graduates g ON g.course_id=c.id AND g.student_id=:g_student LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id
            WHERE en.id IS NOT NULL OR g.id IS NOT NULL ORDER BY c.title",['en_student'=>$id,'g_student'=>$id]);
        echo '<h3>Course and certificate history</h3>';
        if(!$rows)ac_admin_empty('No course history yet. Certification requests are reviewed in the request queue.');
        else{
            ac_admin_table(['Course','Enrollment','Duration','Certificate','Action']);
            foreach($rows as $r){
                echo '<tr role="row"><td role="cell"><strong>'.e($r['title']).'</strong></td><td role="cell" data-label="Enrollment">';ac_admin_status((string)($r['enrollment_status']??'completed'));echo '</td><td role="cell" data-label="Duration">'.e((string)($r['duration']??'—')).'</td><td role="cell" data-label="Certificate">';
                if($r['graduate_id']){ac_admin_status(ac_admin_certificate_state($r));if($r['certificate_number']&&$r['certificate_status']==='issued')echo '<small>'.e($r['certificate_number']).'</small>';}else echo '—';
                echo '</td><td role="cell">';
                if($r['graduate_id']){
                    ac_admin_link('View certificate','certificates',['student'=>$id],'ac-secondary');
                    if(!$r['certificate_status']){echo '<details class="ac-contact" data-ac-popup><summary>Edit duration</summary>';ac_admin_post('edit_duration','student',$id);echo '<input type="hidden" name="graduate" value="'.(int)$r['graduate_id'].'"><label>Training duration (weeks)<input type="number" name="duration" min="1" max="104" required value="'.e((string)(academy_duration_weeks((string)$r['duration'])??'')).'"></label><button>Save duration</button></form></details>';}
                    else echo '<small>Duration locked after checkout starts.</small>';
                }
                else{
                    ac_admin_enrollment_action($r,'student',$id);
                    if($r['active'])ac_admin_link('Review requests','requests',['student'=>$id],'ac-secondary');
                }
                echo '<a class="ac-secondary" href="/academy.php?action=transcript&amp;student='.$id.'&amp;course='.(int)$r['id'].'">Download transcript</a>';
                if($r['enrollment_id']){
                    $progress=academy_admin_progress($id,(int)$r['id']);$pct=$progress['total']?(int)round(100*$progress['completed']/$progress['total']):0;
                    $summary=academy_completion_summary($progress['total'],$progress['completed'],$progress['results']);
                    echo '<details class="ac-contact" data-ac-popup><summary>View progress · '.$pct.'%</summary><p><strong>'.$progress['completed'].' of '.$progress['total'].' lessons completed</strong></p><progress max="100" value="'.$pct.'" style="width:100%"></progress>';
                    foreach($progress['lessons'] as $lesson)echo '<p>'.e($lesson['title']).' · '.($lesson['completed_at']?'Completed':'Not completed').'</p>';
                    echo '<h4>Assessments</h4><p>'.$summary['passed'].' of '.$summary['assessments'].' passed · '.$summary['awaiting'].' awaiting grading · '.$summary['missing'].' not submitted</p>';if(!$progress['results'])echo '<p>No published assessments.</p>';
                    foreach($progress['results'] as $result){echo '<p><strong>'.e($result['title']).'</strong> · '.($result['score_percent']!==null?(int)$result['score_percent'].'% · '.((int)$result['score_percent']>=(int)$result['pass_percent']?'Passed':'Not passed'):($result['submitted_at']?'Awaiting grading':'Not submitted')).'</p>';if($result['granted_at']){$prior=json_decode((string)$result['prior_submission'],true);echo '<small>Retake approved · '.(is_array($prior)?'Original result: '.(int)$prior['score_percent'].'%':'Original exam expired').'</small>';}}
                    echo '</details>';
                }
                echo '</td></tr>';
            }
            ac_admin_table_end();
        }
        echo '</section><section class="ac-panel"><details class="ac-history-panel" data-ac-popup><summary>Administrator activity</summary>';
        $history=db_all('SELECT h.*,u.full_name admin_name FROM academy_admin_history h JOIN users u ON u.id=h.admin_id WHERE h.student_id=:student ORDER BY h.id DESC LIMIT 50',['student'=>$id]);
        if(!$history)ac_admin_empty('No administrative corrections recorded.');
        foreach($history as $event)echo '<div class="ac-lms-submission"><strong>'.e(ucfirst(str_replace('_',' ',$event['entity_type']))).': '.e($event['old_state']).' → '.e($event['new_state']).'</strong><p>'.nl2br(e($event['reason'])).'</p><small>'.e($event['admin_name']).' · '.e($event['changed_at']).'</small></div>';
        echo '</details></section>';
    }
}elseif($tab==='enrollments'){
    echo '<section class="ac-panel"><h2>Enrollment requests</h2><p>Accept new requests or open a student to approve completed training.</p>';
    $options=['requested'=>'New requests','enrolled'=>'Enrolled','completed'=>'Completed','cancelled'=>'Cancelled'];
    if(!isset($options[$status]))$status='';
    ac_admin_search('enrollments',$query,$status,$options);
    [$where,$params]=ac_admin_search_params($query,['u.full_name','u.email','c.title']);
    if($status!==''){$where.=' AND en.status=:status';$params['status']=$status;}
    $from=" FROM academy_enrollments en JOIN users u ON u.id=en.student_id JOIN academy_courses c ON c.id=en.course_id WHERE u.role='student' AND u.deleted_at IS NULL".$where;
    $total=ac_admin_count('SELECT COUNT(*) n'.$from,$params);$page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
    $rows=db_all('SELECT en.id,en.student_id,en.course_id,en.status,u.full_name,u.email,c.title'.$from.' ORDER BY en.id DESC LIMIT 25 OFFSET '.$offset,$params);
    if(!$rows)ac_admin_empty('No enrollment records match your search.');
    else{
        ac_admin_table(['Student','Course','Status','Action']);
        foreach($rows as $r){echo '<tr role="row"><td role="cell"><strong>'.e($r['full_name']).'</strong><small>'.e($r['email']).'</small></td><td role="cell" data-label="Course">'.e($r['title']).'</td><td role="cell" data-label="Status">';ac_admin_status($r['status']);echo '</td><td role="cell"><div class="ac-actions">';
            ac_admin_enrollment_action($r,'enrollments');
            ac_admin_link('View student','student',['id'=>(int)$r['student_id'],'course'=>(int)$r['course_id']],'ac-secondary');echo '</div></td></tr>';
        }
        ac_admin_table_end();
    }
    ac_admin_pager('enrollments',$page,$total,['q'=>$query,'status'=>$status]);echo '</section>';
}elseif($tab==='courses'){
    echo '<section class="ac-panel"><div class="ac-panel-title"><div><h2>Courses</h2><p>Manage the course catalogue and certificate colours.</p></div></div><details class="ac-create"><summary>Add a course</summary>';
    ac_admin_post('create_course','courses');echo '<div class="ac-fields"><label>Course title<input name="title" maxlength="160" required></label><label>Certificate colour<select name="colour">';ac_admin_colour_options();echo '</select></label></div><button>Create Course</button></form></details>';
    $rows=db_all("SELECT c.*,(SELECT COUNT(*) FROM academy_enrollments en WHERE en.course_id=c.id AND en.status<>'cancelled') AS enrolled_count,(SELECT COUNT(*) FROM academy_graduates g WHERE g.course_id=c.id) AS completed_count FROM academy_courses c ORDER BY c.title");
    if(!$rows)ac_admin_empty('No courses yet.');
    else{
        ac_admin_table(['Course','Students','Completed','Certificate colour','Action']);
        foreach($rows as $c){echo '<tr role="row"><td role="cell"><strong>'.e($c['title']).'</strong><small>'.($c['active']?'Active':'Inactive').'</small></td><td role="cell" data-label="Students">'.(int)$c['enrolled_count'].'</td><td role="cell" data-label="Completed">'.(int)$c['completed_count'].'</td><td role="cell" colspan="2">';ac_admin_post('colour','courses');echo '<input type="hidden" name="course" value="'.(int)$c['id'].'"><div class="ac-inline"><span class="ac-swatch ac-swatch-'.e($c['colour']).'" aria-hidden="true"></span><select name="colour" aria-label="Certificate colour for '.e($c['title']).'">';ac_admin_colour_options($c['colour']);echo '</select><button class="ac-secondary">Save</button></div></form><a class="ac-link" href="/academy.php?action=builder&amp;course='.(int)$c['id'].'">Course builder</a></td></tr>';}
        ac_admin_table_end();
    }
    echo '</section>';
}else{
    echo '<section class="ac-panel"><h2>Certificates</h2><p>Track approved graduates, payment status, issued certificates and email delivery.</p>';
    $options=['ready'=>'Ready to certify','pending'=>'Payment pending','issued'=>'Issued','revoked'=>'Revoked'];
    if(!isset($options[$status]))$status='';
    ac_admin_search('certificates',$query,$status,$options);
    [$where,$params]=ac_admin_search_params($query,['u.full_name','u.email','c.title','ce.certificate_number']);
    $student=(int)($_GET['student']??0);
    if($student>0){$where.=' AND g.student_id=:student';$params['student']=$student;echo '<div class="ac-callout">Showing one student’s certificates. ';ac_admin_link('Show all','certificates');echo '</div>';}
    if($status==='ready')$where.=' AND ce.id IS NULL';elseif($status!==''){$where.=' AND ce.status=:status';$params['status']=$status;}
    $from=' FROM academy_graduates g JOIN users u ON u.id=g.student_id JOIN academy_courses c ON c.id=g.course_id LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id WHERE 1=1'.$where;
    $total=ac_admin_count('SELECT COUNT(*) n'.$from,$params);$page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
    $rows=db_all('SELECT g.student_id,g.duration,u.full_name,u.email,c.title,ce.id AS certificate_id,ce.status AS certificate_status,ce.certificate_number,ce.amount_minor,ce.email_sent_at'.$from.' ORDER BY g.id DESC LIMIT 25 OFFSET '.$offset,$params);
    if(!$rows)ac_admin_empty('No certificate records match your search. Approve a completed course from the Students tab to get started.');
    else{
        ac_admin_table(['Student','Course / duration','Status','Payment / email','Action']);
        foreach($rows as $r){$st=ac_admin_certificate_state($r);echo '<tr role="row"><td role="cell"><strong>'.e($r['full_name']).'</strong><small>'.e($r['email']).'</small></td><td role="cell" data-label="Course / duration">'.e($r['title']).'<small>'.e($r['duration']).'</small></td><td role="cell" data-label="Status">';ac_admin_status($st);if($st==='issued'||$st==='revoked')echo '<small>'.e((string)$r['certificate_number']).'</small>';echo '</td><td role="cell" data-label="Payment / email">';
            if($st==='ready')echo 'Not started';elseif($st==='pending')echo '₦'.number_format((int)$r['amount_minor']/100,2).' · Pending';else {echo '₦'.number_format((int)$r['amount_minor']/100,2).' · Verified payment';echo '<small>Email: '.($r['email_sent_at']?'Accepted by mail server':'Awaiting delivery').'</small>';}
            echo '</td><td role="cell"><div class="ac-actions">';ac_admin_link('View student','student',['id'=>(int)$r['student_id']],'ac-secondary');
            if($st==='pending'){ac_admin_post('verify_payment','certificates');echo '<input type="hidden" name="id" value="'.(int)$r['certificate_id'].'"><button>Verify payment</button></form>';}
            if($st==='issued'&&!$r['email_sent_at']){ac_admin_post('retry_email','certificates');echo '<input type="hidden" name="id" value="'.(int)$r['certificate_id'].'"><button class="ac-secondary">Retry email</button></form>';}
            if($st==='issued'){echo '<details class="ac-danger-details" data-ac-popup><summary>Revoke</summary>';ac_admin_post('revoke','certificates');echo '<input type="hidden" name="id" value="'.(int)$r['certificate_id'].'"><label>Reason for revocation<textarea name="reason" maxlength="500" rows="2" required></textarea></label><label class="ac-check"><input type="checkbox" name="confirm_revoke" value="1" required> I confirm revocation.</label><button class="ac-danger">Revoke certificate</button></form></details>';}
            echo '</div></td></tr>';
        }
        ac_admin_table_end();
    }
    ac_admin_pager('certificates',$page,$total,['q'=>$query,'status'=>$status,'student'=>$student]);echo '</section>';
}
echo '</div><script src="/assets/js/academy-actions.js?v=1" defer></script>';ac_end();
