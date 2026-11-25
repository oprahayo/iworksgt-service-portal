<?php
declare(strict_types=1);
const ACADEMY_FEE_MINOR = 10200000;
const ACADEMY_VERIFY_URL = 'https://portal.iworksgt.com/verify';

function academy_template(string $colour): string {
    if (!in_array($colour, ['orange','purple','green','blue'], true)) throw new RuntimeException('Invalid template.');
    $path = __DIR__.'/../storage/academy-templates/iworks-'.$colour.'.svg';
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
function academy_paystack_key(): string {
    $method = get_payment_method_by_code('paystack');
    if (!$method || (int)$method['is_active'] !== 1 || trim((string)$method['secret_key']) === '') {
        throw new RuntimeException('Certification payments are temporarily unavailable.');
    }
    return trim((string)$method['secret_key']);
}
function academy_valid_name(string $name): bool {
    return preg_match("/^[\\p{L}\\p{M} .’'\\-]{2,150}$/u", $name) === 1;
}
function academy_render(array $cert, bool $preview = false): string {
    $svg = academy_template($cert['colour']);
    $values = [
        'CERTIFICATE_NUMBER' => $preview ? 'PREVIEW' : $cert['certificate_number'],
        'STUDENT_FULL_NAME' => $cert['student_name'],
        'COURSE_TITLE' => $cert['course_title'],
        'DURATION' => $cert['duration'],
        'ISSUE_DATE' => $preview ? 'On issuance' : date('d F Y', strtotime($cert['issued_at']))
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
    if ($preview) $svg = str_replace('</svg>','<text x="723" y="710" text-anchor="middle" font-size="23" fill="#a00000">PREVIEW - NOT VALID</text></svg>',$svg);
    return $svg;
}
function academy_pdf(array $cert): string {
    // Server operator installs Inkscape and the exact template fonts.
    $binary = '/usr/bin/inkscape';
    if (!is_executable($binary) || !function_exists('proc_open')) throw new RuntimeException('PDF renderer unavailable.');
    $input = tempnam(sys_get_temp_dir(),'iwga-');
    if ($input === false) throw new RuntimeException('Unable to prepare certificate.');
    $svgInput = $input.'.svg';
    $output = $input.'.pdf';
    try {
        file_put_contents($svgInput,academy_render($cert));
        $process = proc_open([$binary,'--export-type=pdf','--export-filename='.$output,$svgInput],
            [0=>['pipe','r'],1=>['file','/dev/null','a'],2=>['file','/dev/null','a']],$pipes);
        if (!is_resource($process)) throw new RuntimeException('PDF renderer unavailable.');
        fclose($pipes[0]);
        if (proc_close($process)!==0 || !is_file($output)) throw new RuntimeException('Certificate rendering failed.');
        $pdf = file_get_contents($output);
        if (!$pdf || !str_starts_with($pdf,'%PDF-')) throw new RuntimeException('Invalid PDF.');
        return $pdf;
    } finally { @unlink($input); @unlink($svgInput); @unlink($output); }
}
function academy_confirm(string $reference): bool {
    if (!preg_match('/^IWGA-[a-f0-9]{32}$/D',$reference)) return false;
    $cert = db_one('SELECT * FROM academy_certificates WHERE payment_reference=:ref',['ref'=>$reference]);
    if (!$cert) return false;
    if ($cert['status']==='issued') return true;
    if ($cert['status']!=='pending') return false;
    $verified = gateway_json_get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference),academy_paystack_key());
    $data = $verified['data'] ?? [];
    if (empty($verified['status']) || ($data['status']??'')!=='success'
        || ($data['reference']??'')!==$reference || ($data['currency']??'')!=='NGN'
        || (int)($data['amount']??0)!==(int)$cert['amount_minor'] || (int)$cert['amount_minor']<=0
        || empty($data['id'])) return false;
    // Only a verified provider transaction can issue a certificate.
    db()->beginTransaction();
    try {
        $row=db_one('SELECT * FROM academy_certificates WHERE id=:id FOR UPDATE',['id'=>$cert['id']]);
        if ($row['status']==='pending') {
            db_execute("UPDATE academy_certificates SET status='issued',provider_transaction=:tx,issued_at=:issued WHERE id=:id AND status='pending'",
                ['tx'=>(string)$data['id'],'issued'=>now(),'id'=>$row['id']]);
        }
        db()->commit();
    } catch (Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; }
    return $row['status']!=='revoked';
}
function academy_owned_certificate(int $id,int $student): ?array {
    return db_one("SELECT * FROM academy_certificates WHERE id=:id AND student_id=:student AND status='issued'",
        ['id'=>$id,'student'=>$student]);
}
function academy_new_checkout(array $user,int $graduate,string $name): string {
    if (!is_email_verified($user) || !academy_valid_name($name) || trim((string)$user['phone'])==='') {
        throw new RuntimeException('Complete your verified profile and enter a valid certificate name.');
    }
    $g = db_one('SELECT g.*,c.title,c.colour FROM academy_graduates g JOIN academy_courses c ON c.id=g.course_id WHERE g.id=:id AND g.student_id=:student',
        ['id'=>$graduate,'student'=>$user['id']]);
    if (!$g) throw new RuntimeException('Course completion must be approved by an administrator.');
    $secret=academy_paystack_key();
    $ref='IWGA-'.bin2hex(random_bytes(16));
    $certno='IWGA-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8)));
    // Fail before initiating checkout if assets or PDF renderer are unavailable.
    academy_pdf(['colour'=>$g['colour'],'certificate_number'=>$certno,'student_name'=>$name,
        'course_title'=>$g['title'],'duration'=>$g['duration'],'issued_at'=>now()]);
    db_execute('INSERT INTO academy_certificates (graduate_id,student_id,certificate_number,student_name,course_title,duration,colour,amount_minor,payment_reference,created_at)
        VALUES (:graduate,:student,:number,:name,:course,:duration,:colour,:amount,:ref,:created)',
        ['graduate'=>$graduate,'student'=>$user['id'],'number'=>$certno,'name'=>$name,'course'=>$g['title'],'duration'=>$g['duration'],
        'colour'=>$g['colour'],'amount'=>ACADEMY_FEE_MINOR,'ref'=>$ref,'created'=>now()]);
    $response=gateway_json_post('https://api.paystack.co/transaction/initialize',[
        'email'=>$user['email'],'amount'=>ACADEMY_FEE_MINOR,'currency'=>'NGN','reference'=>$ref,
        'callback_url'=>'https://portal.iworksgt.com/academy.php?action=callback'
    ],$secret);
    $url=(string)($response['data']['authorization_url']??'');
    if (empty($response['status']) || parse_url($url,PHP_URL_SCHEME)!=='https' || parse_url($url,PHP_URL_HOST)!=='checkout.paystack.com') {
        throw new RuntimeException('Checkout could not be started. Contact support before retrying.');
    }
    return $url;
}
