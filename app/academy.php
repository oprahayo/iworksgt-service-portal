<?php
declare(strict_types=1);
const ACADEMY_FEE_MINOR = 10200000;
const ACADEMY_VERIFY_URL = 'https://portal.iworksgt.com/verify';

function academy_template(string $colour): string {
    if (!in_array($colour, ['orange','purple','green','blue'], true)) throw new RuntimeException('Invalid template.');
    // Signed templates must remain outside all HTTP document roots.
    $path = academy_private_home().'/.iworks-academy-templates/iworks-'.$colour.'.svg';
    // The isolated CLI preflight deliberately stages assets inside its private
    // temporary checkout; never use this fallback in an HTTP request.
    if (PHP_SAPI === 'cli' && !is_readable($path)) {
        $path = __DIR__.'/../storage/academy-templates/iworks-'.$colour.'.svg';
    }
    $approvedHashes = [
        'blue'=>'31b7a6a8ddd1d2df5f62a70c9a0b3a895b7cc93836b1c9a6c987d684a43b72e0',
        'green'=>'a0f6334eaaf448529ace4c9e5ab0bf50a7529f72fdb00ca85216f22dcae287e2',
        'orange'=>'feb655f2c721314f77102f25e3aa7793288a40daaa3db75caf1fe8867816262e',
        'purple'=>'4934580781f3e36530c4ae020efe2f31a6d2c783e9761951791a2f628f971b51'
    ];
    $svg = is_readable($path) ? file_get_contents($path) : false;
    if (!$svg || !hash_equals($approvedHashes[$colour],hash('sha256',$svg))) throw new RuntimeException('Approved certificate artwork not installed.');
    if (!$svg || !str_contains($svg, '{{STUDENT_FULL_NAME}}') || !str_contains($svg,'id="verification_qr"')) {
        throw new RuntimeException('Certificate template not installed.');
    }
    return $svg;
}
function academy_checkout_enabled(): bool {
    return setting('academy_checkout_enabled','0')==='1';
}
function academy_paystack_key(): string {
    $method = get_payment_method_by_code('paystack');
    if (!$method || (int)$method['is_active'] !== 1 || trim((string)$method['secret_key']) === '') {
        throw new RuntimeException('Certification payments are temporarily unavailable.');
    }
    return trim((string)$method['secret_key']);
}
/** Live issuance must never accept a sandbox transaction or a test secret. */
function academy_live_paystack_key(): string {
    $key=academy_paystack_key();
    if (!preg_match('/^sk_live_[A-Za-z0-9]+$/D',$key)) {
        throw new RuntimeException('Live certification payments require a live Paystack key.');
    }
    return $key;
}
function academy_live_payment_matches(array $cert,array $verified): bool {
    return ($verified['data']['domain']??'')==='live' && academy_verified_payment_matches($cert,$verified);
}
function academy_valid_name(string $name): bool {
    return preg_match("/^[\\p{L}\\p{M} .’'\\-]{2,150}$/u", $name) === 1;
}
function academy_render(array $cert, bool $preview = false): string {
    if ($preview) throw new LogicException('Use the restricted raster preview renderer.');
    $svg = academy_template($cert['colour']);
    $values = [
        'CERTIFICATE_NUMBER' => $cert['certificate_number'],
        'STUDENT_FULL_NAME' => $cert['student_name'],
        'COURSE_TITLE' => $cert['course_title'],
        'DURATION' => $cert['duration'],
        'ISSUE_DATE' => date('d F Y', strtotime($cert['issued_at']))
    ];
    if (function_exists('mb_strtoupper')) $values['STUDENT_FULL_NAME'] = mb_strtoupper($values['STUDENT_FULL_NAME'],'UTF-8');
    else $values['STUDENT_FULL_NAME'] = strtoupper($values['STUDENT_FULL_NAME']);
    // Fit long names without changing their spelling. SVG textLength only
    // applies to overlong fields, keeping all original coordinates and fonts.
    foreach (['student_full_name'=>['STUDENT_FULL_NAME',69,1150], 'course_title'=>['COURSE_TITLE',27,1120], 'duration'=>['DURATION',25,800]] as $id=>[$key,$size,$max]) {
        $length = function_exists('mb_strlen') ? mb_strlen($values[$key],'UTF-8') : strlen($values[$key]);
        $prefix = $id === 'course_title' ? 14 : ($id === 'duration' ? 10 : 0);
        $fitted = min($size, max(16, (int)floor($max/(max(1,$length+$prefix)*0.85))));
        $svg = preg_replace_callback('/(<text id="'.preg_quote($id,'/').'"[^>]*font-size=")\\d+(")/',fn($m)=>$m[1].$fitted.$m[2],$svg,1);
    }
    foreach ($values as $key=>$value) {
        $token = '{{'.$key.'}}';
        if (substr_count($svg,$token)!==1) throw new RuntimeException('Invalid certificate template token.');
        $svg = str_replace($token,htmlspecialchars((string)$value, ENT_QUOTES|ENT_XML1,'UTF-8'),$svg);
    }
    return $svg;
}
/** Unpaid previews are independent of the signed SVG and never receive issuance credentials. */
function academy_preview_png(array $cert): string {
    $home=academy_private_home();
    $python=$home.'/.iworks-academy-renderer/bin/python';
    $script=__DIR__.'/../scripts/render_academy_preview.py';
    $fonts=$home.'/.local/share/fonts/iworks-academy';
    if (!function_exists('proc_open') || !is_executable($python) || !is_readable($script)) throw new RuntimeException('Restricted preview renderer unavailable.');
    $payload=[];
    foreach (['colour','student_name','course_title','duration'] as $key) $payload[$key]=(string)($cert[$key]??'');
    if (!in_array($payload['colour'],['orange','purple','green','blue'],true) || !academy_valid_name($payload['student_name'])) throw new InvalidArgumentException('Invalid preview data.');
    $json=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $process=proc_open([$python,'-I',$script,$fonts],[0=>['pipe','r'],1=>['pipe','w'],2=>['file','/dev/null','a']],$pipes);
    if (!is_resource($process)) throw new RuntimeException('Restricted preview unavailable.');
    $written=fwrite($pipes[0],$json);fclose($pipes[0]);
    $png=stream_get_contents($pipes[1],4194305);fclose($pipes[1]);
    $status=proc_close($process);
    if ($written!==strlen($json) || $status!==0 || !is_string($png) || strlen($png)>4194304 || !str_starts_with($png,"\x89PNG\r\n\x1a\n")) throw new RuntimeException('Restricted preview rendering failed.');
    return $png;
}
// Resolve the hosting account home from the application owner, not a request.
function academy_private_home(): string {
    if (function_exists('posix_getpwuid')) {
        $owner=posix_getpwuid(fileowner(__FILE__));
        if (is_array($owner) && !empty($owner['dir']) && is_dir($owner['dir'])) return rtrim($owner['dir'],'/');
    }
    // cPanel document root and private staging directory are both under /home*/ACCOUNT.
    if (preg_match('~^(/home[^/]*/[^/]+)(?:/|$)~',__DIR__,$m)) return $m[1];
    $home=getenv('HOME');
    if (is_string($home) && $home!=='' && is_dir($home)) return rtrim($home,'/');
    throw new RuntimeException('Private hosting home not found.');
}
function academy_renderer_command(string $input,string $output): array {
    if (!function_exists('proc_open')) throw new RuntimeException('PDF renderer process execution is unavailable.');
    $home=academy_private_home();
    $python=$home.'/.iworks-academy-renderer/bin/python';
    $script=__DIR__.'/../scripts/render_academy_pdf.py';
    $fonts=$home.'/.local/share/fonts/iworks-academy';
    if (is_executable($python) && is_readable($script)) {
        foreach (['Lato-Regular.ttf','Lato-Bold.ttf','Lato-Black.ttf','LiberationSerif-Bold.ttf'] as $font) {
            if (!is_readable($fonts.'/'.$font)) throw new RuntimeException('Required certificate font unavailable.');
        }
        return [$python,'-I',$script,$input,$output,$fonts];
    }
    if (is_executable('/usr/bin/inkscape')) return ['/usr/bin/inkscape','--export-type=pdf','--export-filename='.$output,$input];
    throw new RuntimeException('Install the private Academy PDF renderer or Inkscape.');
}
function academy_renderer_available(): bool {
    try { academy_renderer_command('/tmp/academy-input.svg','/tmp/academy-output.pdf'); return true; }
    catch(Throwable $e) { return false; }
}
function academy_pdf(array $cert): string {
    // Protect personal data and signed artwork throughout rendering.
    $work=rtrim(sys_get_temp_dir(),'/').'/iwga-'.bin2hex(random_bytes(16));
    if (!mkdir($work,0700)) throw new RuntimeException('Unable to prepare private certificate directory.');
    $svgInput=$work.'/input.svg';$output=$work.'/output.pdf';
    try {
        if (file_put_contents($svgInput,academy_render($cert))===false) throw new RuntimeException('Unable to write certificate.');
        chmod($svgInput,0600);
        $command=academy_renderer_command($svgInput,$output);
        $process=proc_open($command,[0=>['pipe','r'],1=>['file','/dev/null','a'],2=>['file','/dev/null','a']],$pipes);
        if (!is_resource($process)) throw new RuntimeException('PDF renderer unavailable.');
        fclose($pipes[0]);
        if (proc_close($process)!==0 || !is_file($output)) throw new RuntimeException('Certificate rendering failed.');
        $pdf=file_get_contents($output);
        if (!$pdf || !str_starts_with($pdf,'%PDF-')) throw new RuntimeException('Invalid PDF.');
        return $pdf;
    } finally { @unlink($svgInput); @unlink($output); @rmdir($work); }
}
function academy_verified_payment_matches(array $cert,array $verified): bool {
    $data=$verified['data']??null;
    if (!is_array($data) || ($verified['status']??false)!==true) return false;
    $amount=$data['amount']??null;
    $tx=$data['id']??null;
    return ($data['status']??'')==='success'
        && ($data['reference']??'')===$cert['payment_reference']
        && ($data['currency']??'')==='NGN'
        && is_int($amount) && $amount===(int)$cert['amount_minor'] && $amount>0
        && (is_int($tx)||is_string($tx)) && preg_match('/^[0-9]{1,80}$/D',(string)$tx)===1;
}
/** Notifications are supplementary: never reverse a saved payment or enrollment on mail failure. */
function academy_notice(int $studentId,string $title,string $message,string $page='courses',bool $admins=false):void{
    try{
        if($studentId>0)create_notification($studentId,$title,$message,'/academy.php?action='.$page);
    }catch(Throwable $e){error_log('Academy student notification deferred: '.get_class($e));}
    if($admins){
        try{notify_roles(['admin'],$title,$message,'/academy.php?action=admin&tab=student&id='.$studentId);}
        catch(Throwable $e){error_log('Academy admin notification deferred: '.get_class($e));}
    }
}
function academy_confirm(string $reference): bool {
    if (!preg_match('/^IWGA-[a-f0-9]{32}$/D',$reference)) return false;
    $cert = db_one('SELECT * FROM academy_certificates WHERE payment_reference=:ref',['ref'=>$reference]);
    if (!$cert) return false;
    if ($cert['status']==='issued') return true;
    if ($cert['status']!=='pending') return false;
    $verified = gateway_json_get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference),academy_live_paystack_key());
    $data = $verified['data'] ?? [];
    if (!academy_live_payment_matches($cert,$verified)) return false;
    // Only a verified provider transaction can issue a certificate.
    db()->beginTransaction();
    try {
        $row=db_one('SELECT * FROM academy_certificates WHERE id=:id FOR UPDATE',['id'=>$cert['id']]);
        if (!$row || !academy_live_payment_matches($row,$verified)) {
            db()->rollBack(); return false;
        }
        if ($row['status']==='pending') {
            db_execute("UPDATE academy_certificates SET status='issued',provider_transaction=:tx,issued_at=:issued WHERE id=:id AND status='pending'",
                ['tx'=>(string)$data['id'],'issued'=>now(),'id'=>$row['id']]);
        }
        db()->commit();
    } catch (Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; }
    if($row['status']==='pending')academy_notice((int)$row['student_id'],'Certificate issued','Payment verified for '.$row['course_title'].'. Your certificate is available in My Certificates.','certificates',true);
    return in_array($row['status'],['pending','issued'],true);
}
/** Recheck only existing pending references, never create a new charge.
 * The scheduled worker supplies its private cursor so old unpaid rows cannot
 * monopolize every batch. Checkout disablement prevents new payments, not
 * recovery of an already-paid certificate.
 */
function academy_reconcile_pending(int $cursor=0,int $limit=3): array {
    $result=['checked'=>0,'confirmed'=>0,'errors'=>0,'cursor'=>max(0,$cursor)];
    if (!db_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='academy_certificates'")) return $result;
    $limit=max(1,min(5,$limit));
    $rows=db_all("SELECT id,payment_reference FROM academy_certificates WHERE status='pending' AND id>:cursor ORDER BY id LIMIT ".$limit,['cursor'=>$result['cursor']]);
    if (!$rows && $result['cursor']>0) {
        $result['cursor']=0;
        $rows=db_all("SELECT id,payment_reference FROM academy_certificates WHERE status='pending' ORDER BY id LIMIT ".$limit);
    }
    foreach($rows as $row){
        $result['cursor']=(int)$row['id'];
        $result['checked']++;
        try {
            if(academy_confirm((string)$row['payment_reference']))$result['confirmed']++;
        } catch(Throwable $e) {
            $result['errors']++;
            error_log('Academy pending payment reconciliation failed: '.get_class($e));
        }
    }
    return $result;
}
function academy_owned_certificate(int $id,int $student): ?array {
    return db_one("SELECT * FROM academy_certificates WHERE id=:id AND student_id=:student AND status='issued'",
        ['id'=>$id,'student'=>$student]);
}
function academy_checkout_url_valid(string $url): bool {
    $p = parse_url($url);
    return is_array($p) && ($p['scheme']??'')==='https' && ($p['host']??'')==='checkout.paystack.com'
        && !isset($p['user']) && !isset($p['pass']) && !isset($p['port']) && !empty($p['path']);
}
function academy_resume_checkout(array $user, int $graduate): string {
    if (!academy_checkout_enabled()) throw new RuntimeException('Certification checkout is temporarily unavailable.');
    $cert=db_one("SELECT * FROM academy_certificates WHERE graduate_id=:graduate AND student_id=:student AND status='pending'",
        ['graduate'=>$graduate,'student'=>$user['id']]);
    if (!$cert) throw new RuntimeException('No pending certificate payment.');
    // Never allocate a second payment reference while the original can succeed.
    // A temporary provider outage must not make a valid original checkout
    // impossible to resume; never initialize another reference here.
    try {
        if (academy_confirm($cert['payment_reference'])) return 'https://portal.iworksgt.com/academy.php?action=certificates';
    } catch (Throwable $e) {
        error_log('Academy payment verification temporarily unavailable: '.get_class($e));
    }
    $url=(string)($cert['checkout_url']??'');
    if (!academy_checkout_url_valid($url)) throw new RuntimeException('Contact support to recover this payment.');
    return $url;
}
function academy_recommended_weeks(string $slug):?int{
    return ['digital-marketing'=>12,'wordpress'=>16,'frontend-basic'=>12,'frontend-advanced'=>16,'backend'=>16,'fullstack'=>32,'hosting'=>8][$slug]??null;
}
function academy_duration_weeks(string $raw):?int{
    if(!preg_match('/^\s*([0-9]{1,3})(?:\s*weeks?)?\s*$/iD',$raw,$m))return null;
    $n=(int)$m[1];return $n>=1&&$n<=104?$n:null;
}
function academy_duration_label(int $weeks):string{
    if($weeks<1||$weeks>104)throw new InvalidArgumentException('Enter training duration between 1 and 104 weeks.');
    return $weeks.($weeks===1?' Week':' Weeks');
}
// A certificate snapshot is immutable once checkout exists. This guarded
// update and checkout's conditional INSERT prevent stale duration snapshots.
function academy_edit_duration(int $student,int $graduate,string $duration,?string $expected=null):bool{
    $weeks=academy_duration_weeks($duration);
    if($weeks===null)throw new InvalidArgumentException('Enter training duration in weeks (1–104).');
    $label=academy_duration_label($weeks);
    $params=['student'=>$student,'graduate'=>$graduate,'duration'=>$label];
    $where='';
    if($expected!==null){$where=' AND g.duration=:expected';$params['expected']=$expected;}
    return db_query("UPDATE academy_graduates g LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id SET g.duration=:duration WHERE g.id=:graduate AND g.student_id=:student AND ce.id IS NULL".$where,$params)->rowCount()===1;
}

function academy_new_checkout(array $user,int $graduate,string $name): string {
    if (!academy_checkout_enabled()) throw new RuntimeException('Certification checkout is not yet enabled.');
    if (!is_email_verified($user) || !academy_valid_name($name) || trim((string)$user['phone'])==='') {
        throw new RuntimeException('Complete your verified profile and enter a valid certificate name.');
    }
    $g = db_one('SELECT g.*,c.title,c.colour FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id WHERE g.id=:id AND g.student_id=:student',
        ['id'=>$graduate,'student'=>$user['id']]);
    if (!$g) throw new RuntimeException('Course completion must be approved by an administrator.');
    $previous=db_one('SELECT status FROM academy_certificates WHERE graduate_id=:graduate AND student_id=:student',['graduate'=>$graduate,'student'=>$user['id']]);
    if ($previous) {
        if ($previous['status']==='pending') return academy_resume_checkout($user,$graduate);
        throw new RuntimeException('A certificate already exists.');
    }
    $secret=academy_live_paystack_key();
    $ref='IWGA-'.bin2hex(random_bytes(16));
    $certno='IWGA-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8)));
    // Fail before initiating checkout if assets or PDF renderer are unavailable.
    academy_pdf(['colour'=>$g['colour'],'certificate_number'=>$certno,'student_name'=>$name,
        'course_title'=>$g['title'],'duration'=>$g['duration'],'issued_at'=>now()]);
    $inserted=db_query('INSERT INTO academy_certificates (graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,created_at)
        SELECT g.id,g.student_id,:number,:name,:course,g.duration,:colour,:amount,:ref,:created FROM academy_graduates g WHERE g.id=:graduate AND g.student_id=:student AND g.duration=:duration',
        ['graduate'=>$graduate,'student'=>$user['id'],'number'=>$certno,'name'=>$name,'course'=>$g['title'],'duration'=>$g['duration'],
        'colour'=>$g['colour'],'amount'=>ACADEMY_FEE_MINOR,'ref'=>$ref,'created'=>now()])->rowCount();
    if($inserted!==1)throw new RuntimeException('Training details changed. Refresh the certificate preview before paying.');
    $response=gateway_json_post('https://api.paystack.co/transaction/initialize',[
        'email'=>$user['email'],'amount'=>ACADEMY_FEE_MINOR,'currency'=>'NGN','reference'=>$ref,
        'callback_url'=>'https://portal.iworksgt.com/academy.php?action=callback'
    ],$secret);
    $url=(string)($response['data']['authorization_url']??'');
    if (($response['status']??false)!==true || ($response['data']['reference']??'')!==$ref || !academy_checkout_url_valid($url)) {
        throw new RuntimeException('Checkout could not be started. Contact support before retrying.');
    }
    db_execute('UPDATE academy_certificates SET checkout_url=:url WHERE payment_reference=:ref',['url'=>$url,'ref'=>$ref]);
    return $url;
}

/** Send the approved certificate PDF only to the verified owner. */
function academy_send_pdf_email(array $cert, array $user): bool {
    if (!email_enabled() || !is_email_verified($user) || (int)$cert['student_id']!==(int)$user['id']
        || $cert['status']!=='issued' || !filter_var($user['email'],FILTER_VALIDATE_EMAIL)) return false;
    $from = trim(setting('mail_from_email','no-reply@iworksgt.com'));
    $envelope = iworks_mail_envelope_parameters($from);
    if ($envelope===null) return false;
    $pdf = academy_pdf($cert);
    $boundary='iwga-'.bin2hex(random_bytes(18));
    $number=$cert['certificate_number'];
    if (!preg_match('/^IWGA-\\d{4}-[A-F0-9]{16}$/D',$number)) return false;
    $headers=implode("\r\n",['MIME-Version: 1.0','From: '.$from,'Reply-To: '.$from,
        'Content-Type: multipart/mixed; boundary="'.$boundary.'"']);
    $body="--".$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        .chunk_split(base64_encode("Congratulations! Your iWorks Global certificate is attached. Verify at ".ACADEMY_VERIFY_URL." using ".$number."."))
        ."\r\n--".$boundary."\r\nContent-Type: application/pdf; name=\"".$number.".pdf\"\r\n"
        ."Content-Disposition: attachment; filename=\"".$number.".pdf\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        .chunk_split(base64_encode($pdf))."\r\n--".$boundary."--\r\n";
    return @mail($user['email'],'Your iWorks Global certificate',$body,$headers,$envelope);
}
/** The issued record is a durable email outbox; advisory lock prevents concurrent sends. */
function academy_deliver_email(int $id): bool {
    $lock='academy-cert-mail-'.$id;
    if ((int)(db_one('SELECT GET_LOCK(:name,0) AS acquired',['name'=>$lock])['acquired']??0)!==1) return false;
    try {
        $cert=db_one("SELECT * FROM academy_certificates WHERE id=:id AND status='issued'",['id'=>$id]);
        if (!$cert) return false;
        if (!empty($cert['email_sent_at'])) return true;
        $user=find_user_by_id((int)$cert['student_id']);
        if (!$user || !academy_send_pdf_email($cert,$user)) return false;
        db_execute('UPDATE academy_certificates SET email_sent_at=:sent WHERE id=:id AND email_sent_at IS NULL',['sent'=>now(),'id'=>$id]);
        return true;
    } finally { db_one('SELECT RELEASE_LOCK(:name)',['name'=>$lock]); }
}
function academy_process_certificate_mail(int $limit=5): array {
    $result=['processed'=>0,'sent'=>0,'failed'=>0];
    if (!email_enabled() || !db_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='academy_certificates'")) return $result;
    $limit=max(1,min(20,$limit));
    foreach(db_all("SELECT id FROM academy_certificates WHERE status='issued' AND email_sent_at IS NULL ORDER BY id LIMIT ".$limit) as $row){
        $result['processed']++;
        try { if (academy_deliver_email((int)$row['id'])) $result['sent']++; else $result['failed']++; }
        catch(Throwable $e){$result['failed']++;error_log('Academy certificate mail failed: '.get_class($e));}
    }
    return $result;
}
