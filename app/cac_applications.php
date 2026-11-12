<?php
declare(strict_types=1);

/**
 * Nigerian CAC pre-checkout application intake. Each client controls their
 * drafts and document uploads. Files live outside public_html. No CAC filing
 * is represented as complete until payment and staff processing take place.
 */
function cac_kind(string $input): string
{
    return in_array($input, ['business_name','company'], true) ? $input : 'business_name';
}
function cac_price_setting(string $name, float $fallback): float
{
    $n = (float) setting($name, (string) $fallback);
    return is_finite($n) && $n >= 0 && $n <= 100000000 ? round($n,2) : $fallback;
}
function cac_company_default_ngn(): float
{
    $configured = cac_price_setting('cac_company_base_ngn', 0);
    if ($configured > 0) { return $configured; }
    $service = get_service_by_slug('company-registration');
    if (!$service) { return 0; }
    $price = (float) ($service['price'] ?? 0);
    if ($price <= 0) { return 0; }
    $currency = strtoupper((string) ($service['currency'] ?? 'NGN'));
    if ($currency === 'NGN') { return $price; }
    return $currency === 'USD' ? round($price * (float) fx_ngn_per_usd(), 2) : 0;
}
function cac_quote(string $kind, array $data, string $currency = 'NGN', ?array $pricing = null): array
{
    $kind = cac_kind($kind);
    $people = is_array($data['people'] ?? null) ? $data['people'] : [];
    $count = count($people);
    $extra = max(0, $count - 1);
    $pricing ??= [
        'business_base'=>cac_price_setting('cac_business_base_ngn',26000),
        'business_extra'=>cac_price_setting('cac_business_extra_partner_ngn',15000),
        'company_base'=>cac_company_default_ngn(),
        'company_director_extra'=>cac_price_setting('cac_company_extra_director_ngn',0),
        'company_shareholder_extra'=>cac_price_setting('cac_company_extra_shareholder_ngn',0),
    ];
    if ($kind === 'business_name') {
        $base = (float) $pricing['business_base'];
        $perPerson = (float) $pricing['business_extra'];
        $extraCount = (string) ($data['structure'] ?? 'sole') === 'partnership' ? $extra : 0;
        $line = $perPerson * $extraCount;
        $details = [
            ['label'=>'Business name filing package','quantity'=>1,'unit'=>$base],
            ['label'=>'Additional partners','quantity'=>$extraCount,'unit'=>$perPerson],
        ];
    } else {
        $base = (float) $pricing['company_base'];
        $extraDirectors = 0; $extraShareholders = 0;
        foreach ($people as $i=>$person) {
            $role = (string) ($person['role'] ?? ($i === 0 ? 'both' : 'director'));
            if (in_array($role, ['director','both'],true)) { $extraDirectors++; }
            if (in_array($role, ['shareholder','both'],true)) { $extraShareholders++; }
        }
        $extraDirectors = max(0, $extraDirectors-1);
        $extraShareholders = max(0, $extraShareholders-1);
        $perDirector = (float) $pricing['company_director_extra'];
        $perShareholder = (float) $pricing['company_shareholder_extra'];
        $line = $extraDirectors*$perDirector + $extraShareholders*$perShareholder;
        $details = [
            ['label'=>'Private company incorporation package','quantity'=>1,'unit'=>$base],
            ['label'=>'Additional directors','quantity'=>$extraDirectors,'unit'=>$perDirector],
            ['label'=>'Additional shareholders','quantity'=>$extraShareholders,'unit'=>$perShareholder],
        ];
    }
    $totalNgn = round($base+$line,2);
    if (!in_array($currency,['NGN','USD'],true)) { throw new RuntimeException('Choose NGN or USD.'); }
    $fx = (float) fx_ngn_per_usd();
    if ($currency === 'USD' && (!is_finite($fx) || $fx <= 0)) { throw new RuntimeException('Exchange rate is unavailable.'); }
    $total = $currency === 'USD' ? round($totalNgn/$fx,2) : $totalNgn;
    return ['currency'=>$currency,'total'=>$total,'ngn_total'=>$totalNgn,'fx'=>$fx,
        'base_ngn'=>$base,'additional_ngn'=>round($line,2),'people_count'=>$count,'lines'=>$details];
}
function cac_people_from_input(mixed $raw, string $kind): array
{
    if (!is_array($raw)) { return []; }
    $people = [];
    foreach (array_slice(array_values($raw),0,25) as $person) {
        if (!is_array($person)) { continue; }
        $row = [];
        foreach (['full_name'=>180,'email'=>190,'phone'=>60,'date_of_birth'=>12,'gender'=>24,
            'nationality'=>80,'occupation'=>140,'address'=>450,'city'=>100,'lga'=>100,
            'state'=>100,'id_number'=>80,'nin'=>80,'shares'=>24] as $key=>$max) {
            $s = $person[$key] ?? '';
            $row[$key] = is_string($s) ? mb_safe_trim(strip_tags($s), $max) : '';
        }
        $idType = (string) ($person['id_type'] ?? '');
        $row['id_type'] = in_array($idType,['nin','national_id','passport','drivers_license','voters_card'],true) ? $idType : '';
        $role = (string) ($person['role'] ?? 'both');
        $row['role'] = $kind === 'company' && in_array($role,['director','shareholder','both'],true) ? $role : 'proprietor';
        $people[] = $row;
    }
    return $people;
}
function cac_form_values(array $post): array
{
    $kind = cac_kind((string) ($post['kind'] ?? 'business_name'));
    $fields = ['name1'=>190,'name2'=>190,'reservation_code'=>70,'activity'=>1800,
        'commencement'=>12,'business_address'=>450,'business_city'=>100,
        'business_lga'=>100,'business_state'=>100,'share_capital'=>25,'share_count'=>20,
        'registered_business_email'=>190,'registered_business_phone'=>60];
    $data = [];
    foreach ($fields as $key=>$max) {
        $v = $post[$key] ?? '';
        $data[$key] = is_string($v) ? mb_safe_trim(strip_tags($v),$max) : '';
    }
    $structure = (string) ($post['structure'] ?? 'sole');
    $data['structure'] = $kind === 'business_name' && $structure === 'partnership' ? 'partnership' : 'sole';
    $data['people'] = cac_people_from_input($post['people'] ?? [],$kind);
    $data['kind'] = $kind;
    $currency = strtoupper((string) ($post['currency'] ?? 'NGN'));
    $data['currency'] = in_array($currency,['NGN','USD'],true) ? $currency : 'NGN';
    $data['declaration'] = (string) ($post['declaration'] ?? '') === '1';
    $data['same_address'] = (string) ($post['same_address'] ?? '') === '1';
    if ($data['same_address'] && isset($data['people'][0])) {
        $lead = $data['people'][0];
        foreach (['address'=>'business_address','city'=>'business_city','lga'=>'business_lga','state'=>'business_state'] as $source=>$dest) {
            $data[$dest] = $lead[$source];
        }
    }
    return $data;
}
function cac_validate(array $data, array $uploadedKeys): void
{
    $kind = cac_kind((string) ($data['kind'] ?? 'business_name'));
    foreach (['name1'=>'First proposed name','activity'=>'Nature of business',
        'business_address'=>'Business address','business_city'=>'Business city',
        'business_lga'=>'Business local government','business_state'=>'Business state'] as $key=>$label) {
        if (trim((string) ($data[$key] ?? '')) === '') {
            throw new RuntimeException($label . ' is required before review.');
        }
    }
    $people = is_array($data['people'] ?? null) ? $data['people'] : [];
    if (!$people || count($people)>25) { throw new RuntimeException('Add at least one applicant.'); }
    if ($kind === 'business_name') {
        $partnership = ($data['structure'] ?? 'sole') === 'partnership';
        if ($partnership && count($people)<2) {
            throw new RuntimeException('Partnership needs at least one additional partner. Choose sole proprietor if you are registering alone.');
        }
        if (!$partnership && count($people)!==1) {
            throw new RuntimeException('Sole proprietor registration accepts one proprietor. Choose partnership for additional people.');
        }
    }
    $directorCount=0;$shareholderCount=0;
    foreach ($people as $i=>$person) {
        foreach (['full_name'=>'Legal name','email'=>'Email','phone'=>'Phone',
            'date_of_birth'=>'Date of birth','nationality'=>'Nationality','occupation'=>'Occupation',
            'address'=>'Residential address','city'=>'City','lga'=>'Local government',
            'state'=>'State','id_type'=>'Identification type','id_number'=>'Identification number'] as $key=>$label) {
            if (trim((string) ($person[$key] ?? '')) === '') {
                throw new RuntimeException($label . ' is required for applicant ' . ($i+1) . '.');
            }
        }
        if (!filter_var((string) $person['email'],FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Applicant ' . ($i+1) . ' needs a valid email.');
        }
        if ($kind === 'business_name' && strtolower((string) $person['nationality']) !== 'nigerian') {
            throw new RuntimeException('Business Name proprietor eligibility requires review. Contact support about a private company registration instead.');
        }
        foreach (['id','signature'] as $doc) {
            if (!in_array('person_' . $i . '_' . $doc, $uploadedKeys,true)) {
                throw new RuntimeException('Upload ' . ($doc === 'id' ? 'government ID' : 'signature') . ' for applicant ' . ($i+1) . ' and wait for the green upload confirmation.');
            }
        }
        if ($kind === 'business_name' && !in_array('person_'.$i.'_photo',$uploadedKeys,true)) {
            throw new RuntimeException('Upload passport photograph for proprietor ' . ($i+1) . '.');
        }
        $role = (string) ($person['role'] ?? '');
        if ($role === 'director' || $role === 'both') { $directorCount++; }
        if ($role === 'shareholder' || $role === 'both') { $shareholderCount++; }
    }
    if ($kind === 'company') {
        if ($directorCount<1 || $shareholderCount<1) {
            throw new RuntimeException('A private company requires at least one director and one shareholder.');
        }
        if ((float) ($data['share_capital'] ?? 0) <= 0) {
            throw new RuntimeException('Enter the proposed issued share capital.');
        }
    }
    if (empty($data['declaration'])) { throw new RuntimeException('Confirm the accuracy and authorisation declaration before checkout.'); }
}
function cac_ensure_schema(): void
{
    if (!schema_table_exists('cac_applications')) {
        db_execute("CREATE TABLE IF NOT EXISTS cac_applications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            kind VARCHAR(24) NOT NULL,
            status VARCHAR(24) NOT NULL DEFAULT 'draft',
            payload_json MEDIUMTEXT NOT NULL,
            job_id INT UNSIGNED NULL,
            invoice_id INT UNSIGNED NULL,
            quote_currency CHAR(3) NULL,
            total_amount DECIMAL(16,2) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_cac_client (client_id, status),
            INDEX idx_cac_job (job_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    if (!schema_table_exists('cac_application_files')) {
        db_execute("CREATE TABLE IF NOT EXISTS cac_application_files (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NOT NULL,
            field_key VARCHAR(80) NOT NULL,
            storage_name VARCHAR(80) NOT NULL,
            original_name VARCHAR(200) NOT NULL,
            mime VARCHAR(80) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_cac_field (application_id, field_key),
            INDEX idx_cac_client_files (client_id, application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
function cac_decode(array $row): array
{
    $data = json_decode((string) ($row['payload_json'] ?? '{}'),true);
    $row['data'] = is_array($data) ? $data : [];
    return $row;
}
function cac_find(int $id,int $clientId): ?array
{
    if ($id<=0 || $clientId<=0) { return null; }
    $r=db_one('SELECT a.*, j.payment_status, j.status AS job_status FROM cac_applications a
        LEFT JOIN jobs j ON j.id=a.job_id WHERE a.id=:id AND a.client_id=:client LIMIT 1',
        ['id'=>$id,'client'=>$clientId]);
    return $r?cac_decode($r):null;
}
function cac_list(int $clientId): array
{
    if (!schema_table_exists('cac_applications')) { return []; }
    return array_map('cac_decode',db_all('SELECT a.*, j.payment_status, j.status AS job_status FROM cac_applications a
        LEFT JOIN jobs j ON j.id=a.job_id WHERE a.client_id=:client ORDER BY a.updated_at DESC LIMIT 100',['client'=>$clientId]));
}
function cac_list_files(int $id,int $clientId): array
{
    return db_all('SELECT id,field_key,original_name,mime,file_size FROM cac_application_files
        WHERE application_id=:id AND client_id=:client ORDER BY id ASC',['id'=>$id,'client'=>$clientId]);
}
function cac_save_draft(int $clientId,int $id,array $data): int
{
    $old=$id>0?cac_find($id,$clientId):null;
    if ($id>0 && !$old) { throw new RuntimeException('Application not found.'); }
    if ($old && ($old['status'] ?? '') !== 'draft') { throw new RuntimeException('This application has already been submitted for payment.'); }
    $json=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) { throw new RuntimeException('Invalid application details.'); }
    if ($old) {
        if ((string) ($old['kind'] ?? '') !== (string) ($data['kind'] ?? '')) {
            throw new RuntimeException('Registration type cannot be changed for an existing draft.');
        }
        db_execute('UPDATE cac_applications SET payload_json=:data,updated_at=:updated
            WHERE id=:id AND client_id=:client AND status=:status',
            ['data'=>$json,'updated'=>now(),'id'=>$id,'client'=>$clientId,'status'=>'draft']);
        return $id;
    }
    db_execute('INSERT INTO cac_applications (client_id,kind,status,payload_json,created_at,updated_at)
        VALUES (:client,:kind,:status,:data,:created,:updated)',
        ['client'=>$clientId,'kind'=>$data['kind'],'status'=>'draft','data'=>$json,'created'=>now(),'updated'=>now()]);
    return db_last_id();
}
function cac_private_dir(): string
{
    $home = trim((string) getenv('HOME'));
    if ($home==='' || !is_dir($home)) { $home=dirname(__DIR__,3); }
    return rtrim($home,'/') . '/.iworks-private/cac-documents';
}
function cac_valid_file_key(string $key): bool
{
    return preg_match('/^person_(?:0|[1-9][0-9]?)_(?:id|photo|signature)$/D',$key)===1;
}
function cac_upload_file(int $clientId,int $id,string $fieldKey,?array $file): array
{
    $app=cac_find($id,$clientId);
    if (!$app || $app['status']!=='draft') { throw new RuntimeException('Save your draft before uploading documents.'); }
    if (!cac_valid_file_key($fieldKey)) { throw new RuntimeException('Invalid document field.'); }
    if (!$file || (int) ($file['error'] ?? -1)!==UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Upload did not complete. Select the file again.');
    }
    $size=(int) ($file['size'] ?? 0);
    if ($size<1 || $size>6*1024*1024) { throw new RuntimeException('File must be between 1 byte and 6 MB.'); }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $isPhoto=str_ends_with($fieldKey,'_photo');
    $allowed=$isPhoto?['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']:
        ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    $ext=$allowed[(string) $mime] ?? null;
    if (!$ext) { throw new RuntimeException('Use JPG, PNG, WebP or PDF documents (photos must be images).'); }
    if ($ext==='pdf') {
        $head=file_get_contents((string) $file['tmp_name'],false,null,0,5);
        if ($head!=='%PDF-') { throw new RuntimeException('Invalid PDF document.'); }
    } elseif (@getimagesize((string) $file['tmp_name'])===false) {
        throw new RuntimeException('Invalid image file.');
    }
    $folder=cac_private_dir();
    if (!is_dir($folder) && !mkdir($folder,0700,true) && !is_dir($folder)) {
        throw new RuntimeException('Secure document storage is unavailable.');
    }
    $name=bin2hex(random_bytes(18)).'.'.$ext;
    if (!move_uploaded_file((string) $file['tmp_name'],$folder.'/'.$name)) {
        throw new RuntimeException('Unable to store the uploaded document.');
    }
    chmod($folder.'/'.$name,0600);
    $old=db_one('SELECT storage_name FROM cac_application_files WHERE application_id=:id AND client_id=:client AND field_key=:field',
        ['id'=>$id,'client'=>$clientId,'field'=>$fieldKey]);
    $original=mb_safe_trim(basename((string) ($file['name'] ?? 'document.'.$ext)),190);
    try {
        db_execute('INSERT INTO cac_application_files
            (application_id,client_id,field_key,storage_name,original_name,mime,file_size,created_at)
            VALUES (:id,:client,:field,:name,:original,:mime,:size,:created)
            ON DUPLICATE KEY UPDATE storage_name=VALUES(storage_name),original_name=VALUES(original_name),
                mime=VALUES(mime),file_size=VALUES(file_size),created_at=VALUES(created_at)',
            ['id'=>$id,'client'=>$clientId,'field'=>$fieldKey,'name'=>$name,'original'=>$original,
                'mime'=>$mime,'size'=>$size,'created'=>now()]);
        if ($old && !empty($old['storage_name'])) { @unlink($folder.'/'.basename((string) $old['storage_name'])); }
    } catch (Throwable $e) { @unlink($folder.'/'.$name); throw $e; }
    return ['uploaded'=>true,'field_key'=>$fieldKey,'filename'=>$original,'size'=>$size];
}
function cac_delete_draft(int $id,int $clientId): void
{
    $app=cac_find($id,$clientId);
    if (!$app || $app['status']!=='draft') { throw new RuntimeException('Only unsubmitted drafts can be deleted.'); }
    $all=db_all('SELECT storage_name FROM cac_application_files WHERE application_id=:id AND client_id=:client',
        ['id'=>$id,'client'=>$clientId]);
    db()->beginTransaction();
    try {
        db_execute('DELETE FROM cac_application_files WHERE application_id=:id AND client_id=:client',
            ['id'=>$id,'client'=>$clientId]);
        db_execute('DELETE FROM cac_applications WHERE id=:id AND client_id=:client AND status=:status',
            ['id'=>$id,'client'=>$clientId,'status'=>'draft']);
        db()->commit();
    } catch (Throwable $e) { if (db()->inTransaction()) db()->rollBack(); throw $e; }
    foreach ($all as $file) { @unlink(cac_private_dir().'/'.basename((string) $file['storage_name'])); }
}
function cac_checkout(int $clientId,int $id): int
{
    db()->beginTransaction();
    try {
        $raw=db_one('SELECT * FROM cac_applications WHERE id=:id AND client_id=:client FOR UPDATE',
            ['id'=>$id,'client'=>$clientId]);
        if (!$raw || $raw['status']!=='draft') { throw new RuntimeException('This application has already been submitted.'); }
        $app=cac_decode($raw);$data=$app['data'];
        $fileRows=cac_list_files($id,$clientId);
        cac_validate($data,array_column($fileRows,'field_key'));
        $quote=cac_quote($app['kind'],$data,(string) ($data['currency'] ?? 'NGN'));
        if ($quote['total']<1) { throw new RuntimeException('This registration price has not been configured by iWorks yet. Contact support.'); }
        $serviceSlug=$app['kind']==='company'?'company-registration':'business-name-registration-cac-nigeria';
        $service=get_service_by_slug($serviceSlug);
        if (!$service) { throw new RuntimeException('Registration product is not active. Contact support.'); }
        $personCount=count($data['people'] ?? []);
        $description=($app['kind']==='company'?'Company incorporation':'Business name registration') . "\n";
        $description.='Proposed name: '.$data['name1']."\n";
        $description.='Registration type: '.($app['kind']==='company'?'Private company limited by shares':$data['structure'])."\n";
        $description.='Applicants: '.$personCount."\n";
        $description.='Application reference: CAC-'.$id."\n";
        $description.='Application data and ID/signature files are available securely to authorised staff.';
        $jobId=create_job(['service_id'=>(int) $service['id'],
            'client_id'=>$clientId,'created_by'=>$clientId,
            'title'=>($app['kind']==='company'?'Company: ':'Business Name: ').$data['name1'],
            'description'=>$description,'amount'=>$quote['total'],'currency'=>$quote['currency'],
            'pay_currency'=>$quote['currency'],'tax_percent'=>0,'checkout_pay_method'=>'card',
            'payment_status'=>'unpaid'],['skip_duplicate_intake'=>true]);
        $invoice=get_invoice_by_job_id($jobId);
        if (!$invoice) { throw new RuntimeException('Invoice not created.'); }
        db_execute('UPDATE cac_applications SET status=:status,job_id=:job,invoice_id=:invoice,
            quote_currency=:currency,total_amount=:total,updated_at=:updated
            WHERE id=:id AND client_id=:client AND status=:draft',
            ['status'=>'awaiting_payment','job'=>$jobId,'invoice'=>(int) $invoice['id'],
                'currency'=>$quote['currency'],'total'=>$quote['total'],'updated'=>now(),
                'id'=>$id,'client'=>$clientId,'draft'=>'draft']);
        db()->commit();
        return (int) $invoice['id'];
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); } throw $e;
    }
}
function cac_find_for_job(int $jobId,int $clientId): ?array
{
    if ($jobId<1 || !schema_table_exists('cac_applications')) { return null; }
    $r=db_one('SELECT * FROM cac_applications WHERE job_id=:job AND client_id=:client LIMIT 1',
        ['job'=>$jobId,'client'=>$clientId]);
    return $r?cac_decode($r):null;
}
