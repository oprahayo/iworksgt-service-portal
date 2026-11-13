<?php
declare(strict_types=1);

/**
 * iWorks pre-payment CAC filing intake. This prepares a client file for iWorks;
 * it does NOT submit anything directly to the government CAC platform.
 */
function cac_filing_schema(): void {
    if (schema_table_exists('cac_filing_applications')) return;
    db_execute("CREATE TABLE IF NOT EXISTS cac_filing_applications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNSIGNED NOT NULL,
        kind VARCHAR(20) NOT NULL,
        status VARCHAR(24) NOT NULL DEFAULT 'draft',
        data_json LONGTEXT NOT NULL,
        docs_json LONGTEXT NOT NULL,
        job_id INT UNSIGNED NULL,
        invoice_id INT UNSIGNED NULL,
        quoted_ngn DECIMAL(14,2) NULL,
        quoted_currency VARCHAR(3) NULL,
        quoted_total DECIMAL(14,2) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX cac_client_updated (client_id, updated_at),
        UNIQUE KEY cac_linked_job (job_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function cac_money_setting(string $name, float $default): float {
    $v = setting($name, (string) $default);
    if (!is_numeric($v)) return $default;
    return max(0, min(100000000, (float)$v));
}
function cac_business_quote(string $structure, int $peopleCount): float {
    $base = cac_money_setting('cac_business_base_ngn', 26000);
    $extra = cac_money_setting('cac_business_partner_extra_ngn', 15000);
    if ($structure === 'sole') {
        if ($peopleCount !== 1) throw new RuntimeException('Sole proprietorship requires exactly one proprietor.');
        return $base;
    }
    if ($structure !== 'partnership' || $peopleCount < 2) {
        throw new RuntimeException('A partnership requires at least two proprietors. Choose sole proprietorship if you are the only owner.');
    }
    if ($peopleCount > 20) throw new RuntimeException('Contact support to register more than 20 partners.');
    return $base + ($peopleCount - 1) * $extra;
}
function cac_company_quote(int $directorCount): float {
    if ($directorCount < 1 || $directorCount > 20) throw new RuntimeException('Add between 1 and 20 directors.');
    $company = get_service_by_slug('company-registration');
    if (!$company) throw new RuntimeException('Company registration is not currently available.');
    $amount = (float)($company['price'] ?? 0);
    $currency = strtoupper((string)($company['currency'] ?? 'NGN'));
    $base = $currency === 'NGN' ? $amount : convert_amount($amount, $currency, 'NGN');
    if ($base <= 0) throw new RuntimeException('Ask iWorks to configure a company registration price.');
    return round($base + ($directorCount - 1) * cac_money_setting('cac_company_extra_director_ngn', 0), 2);
}
function cac_filing_quote(string $kind, array $data): array {
    $people = is_array($data['people'] ?? null) ? $data['people'] : [];
    $directors = count(array_filter($people, static fn(array $person): bool =>
        str_contains(strtolower((string)($person['role']??'Director and shareholder')), 'director')
    ));
    $ngn = $kind === 'business'
        ? cac_business_quote((string)($data['structure'] ?? 'sole'), count($people))
        : cac_company_quote($directors);

    $extras = ['registered_office'=>0.0,'privacy_advisory'=>0.0];
    $addressChoice = (string)($data['address_mode'] ?? 'own');
    if ($addressChoice === 'virtual') {
        if (!cac_virtual_address_eligible()) {
            throw new RuntimeException('Managed registered office is not available. Select your own address.');
        }
        $extras['registered_office'] = cac_money_setting('cac_virtual_address_ngn', 0);
    } elseif ($addressChoice !== 'own') {
        throw new RuntimeException('Invalid registered-office selection.');
    }
    if (!empty($data['privacy_advisory'])) {
        if ($kind !== 'company') throw new RuntimeException('Director privacy advisory is offered for companies only.');
        $extras['privacy_advisory'] = $directors * cac_money_setting('cac_privacy_advisory_per_director_ngn', 50000);
    }
    $ngn += array_sum($extras);
    $currency = strtoupper((string) ($data['currency'] ?? 'NGN'));
    if (!in_array($currency, ['NGN','USD'], true)) $currency = 'NGN';
    $amount = $currency === 'NGN' ? $ngn : round($ngn / max(1, fx_ngn_per_usd()), 2);
    return ['ngn'=>$ngn, 'currency'=>$currency, 'total'=>$amount,
        'base_ngn'=>$ngn-array_sum($extras), 'extras_ngn'=>$extras, 'fx'=>fx_ngn_per_usd()];
}
function cac_filing_decode(array $row): array {
    $row['data'] = is_array(json_decode((string)($row['data_json'] ?? '{}'), true)) ? json_decode((string)$row['data_json'],true) : [];
    $row['docs'] = is_array(json_decode((string)($row['docs_json'] ?? '{}'), true)) ? json_decode((string)$row['docs_json'],true) : [];
    return $row;
}
function cac_filing_load(int $id, int $userId): ?array {
    if ($id<=0 || $userId<=0) return null;
    cac_filing_schema();
    $row=db_one('SELECT * FROM cac_filing_applications WHERE id=:id AND client_id=:client LIMIT 1',
        ['id'=>$id,'client'=>$userId]);
    return is_array($row) ? cac_filing_decode($row) : null;
}
function cac_filing_list(int $userId): array {
    cac_filing_schema();
    return array_map('cac_filing_decode', db_all(
        'SELECT c.*, j.payment_status AS payment_status, j.status AS job_status
         FROM cac_filing_applications c LEFT JOIN jobs j ON j.id=c.job_id
         WHERE c.client_id=:client ORDER BY c.updated_at DESC, c.id DESC LIMIT 100',
        ['client'=>$userId]
    ));
}
function cac_clean_person(array $person): array {
    $fields=['full_name','dob','nationality','occupation','email','phone','residential_address','id_type','id_number','nin','role','share_percent'];
    $out=[];
    foreach($fields as $key) { $out[$key]=mb_safe_trim(trim((string)($person[$key] ?? '')), $key==='residential_address'?500:190); }
    return $out;
}
function cac_clean_data(array $input, string $kind): array {
    $fields=['first_choice','second_choice','av_code','business_activity','nature_category','business_email',
        'business_phone','business_address','address_mode','commencement_date','structure','currency','share_capital','company_type'];
    $out=[];
    foreach($fields as $key) {
        $out[$key]=mb_safe_trim(trim((string)($input[$key] ?? '')), $key==='business_address'?500:240);
    }
    $out['structure'] = $kind === 'business' ? (in_array($out['structure'],['sole','partnership'],true) ? $out['structure'] : 'sole') : 'company';
    $out['address_mode'] = in_array($out['address_mode'], ['own','virtual'], true) ? $out['address_mode'] : 'own';
    $out['privacy_advisory'] = $kind === 'company' && !empty($input['privacy_advisory']) ? 1 : 0;
    // A customer cannot enter an arbitrary address and label it as ours.
    if ($out['address_mode'] === 'virtual' && cac_virtual_address_eligible()) {
        $out['business_address'] = cac_registered_office('virtual', '');
    }
    $out['currency'] = in_array(strtoupper($out['currency']),['NGN','USD'],true) ? strtoupper($out['currency']) : 'NGN';
    $people=is_array($input['people'] ?? null) ? array_values($input['people']) : [];
    $out['people']=array_map('cac_clean_person', array_filter(array_slice($people,0,20),'is_array'));
    return $out;
}
function cac_filing_save(int $clientId, int $id, string $kind, array $input): int {
    if (!in_array($kind,['business','company'],true)) throw new RuntimeException('Invalid CAC service selected.');
    cac_filing_schema();
    $data = cac_clean_data($input,$kind);
    $json=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if ($id>0) {
        $row=cac_filing_load($id,$clientId);
        if (!$row || $row['kind']!==$kind || $row['status']!=='draft') throw new RuntimeException('This draft cannot be edited.');
        // Avoid attaching documents to wrong people when changing the count.
        // Documents belong to a named person. Never silently reassign an ID
        // or signature when users remove/reorder people or change their name.
        foreach ($row['docs'] as $index=>$documentGroup) {
            if (empty($documentGroup)) continue;
            $originalName=trim((string)($row['data']['people'][$index]['full_name']??''));
            $newName=trim((string)($data['people'][$index]['full_name']??''));
            if ($newName==='' || $originalName!==$newName) {
                throw new RuntimeException('Uploaded documents belong to '.($originalName?:'another person').'. Keep the person in the same position or contact support to reset their documents.');
            }
        }
        db_execute('UPDATE cac_filing_applications SET data_json=:data,updated_at=:updated WHERE id=:id AND client_id=:client',
            ['data'=>$json,'updated'=>now(),'id'=>$id,'client'=>$clientId]);
        return $id;
    }
    db_execute("INSERT INTO cac_filing_applications
        (client_id,kind,status,data_json,docs_json,created_at,updated_at)
        VALUES (:client,:kind,'draft',:data,'{}',:created,:updated)",
        ['client'=>$clientId,'kind'=>$kind,'data'=>$json,'created'=>now(),'updated'=>now()]);
    return db_last_id();
}
function cac_ensure_private_storage(): void {
    ensure_upload_directory();
    $dir=upload_directory().'/cac';
    if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Private document storage unavailable.');
    $deny=$dir.'/.htaccess';
    if(!is_file($deny)) {
        file_put_contents($deny, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",LOCK_EX);
    }
}
function cac_doc_validate(string $type, array $file): string {
    if (!in_array($type,['id','photo','signature'],true)) throw new RuntimeException('Invalid document type.');
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
        throw new RuntimeException('Document upload failed.');
    if ((int)($file['size'] ?? 0)>5*1024*1024 || (int)($file['size'] ?? 0)<1)
        throw new RuntimeException('Document must be no larger than 5MB.');
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file((string)($file['tmp_name'] ?? ''));
    $allow=['image/jpeg'=>'jpg','image/png'=>'png'];
    if ($type==='id') $allow['application/pdf']='pdf';
    if (!isset($allow[$mime])) throw new RuntimeException('Use JPG/PNG for photo and signature, or JPG/PNG/PDF for ID.');
    return $allow[$mime];
}
function cac_filing_save_document(int $clientId,int $id,int $personIndex,string $type,array $file): array {
    $row=cac_filing_load($id,$clientId);
    if (!$row || $row['status']!=='draft') throw new RuntimeException('This application is not editable.');
    if ($personIndex<0 || !isset($row['data']['people'][$personIndex])) throw new RuntimeException('Save person details first.');
    $ext=cac_doc_validate($type,$file);
    cac_ensure_private_storage();
    $dir=upload_directory().'/cac/filing_'.$id.'/person_'.$personIndex;
    if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Storage unavailable.');
    $path=$dir.'/'.bin2hex(random_bytes(16)).'.'.$ext;
    if (!move_uploaded_file((string)$file['tmp_name'],$path)) throw new RuntimeException('Could not securely save the document.');
    chmod($path,0600);
    $docs=$row['docs'];
    $previous=$docs[$personIndex][$type]['path'] ?? '';
    $docs[$personIndex][$type]=['path'=>$path, 'name'=>mb_safe_trim((string)($file['name'] ?? 'Document'),150), 'size'=>(int)$file['size'],'mime'=>$ext];
    db_execute('UPDATE cac_filing_applications SET docs_json=:docs,updated_at=:updated WHERE id=:id AND client_id=:client',
        ['docs'=>json_encode($docs,JSON_THROW_ON_ERROR),'updated'=>now(),'id'=>$id,'client'=>$clientId]);
    if ($previous!=='' && is_file($previous) && str_starts_with(realpath($previous) ?: '',realpath(upload_directory().'/cac') ?: '/impossible')) @unlink($previous);
    return ['name'=>$docs[$personIndex][$type]['name'], 'size'=>$docs[$personIndex][$type]['size']];
}
function cac_filing_save_drawn_signature(int $clientId,int $id,int $personIndex,string $dataUrl): array {
    if (!preg_match('~^data:image/png;base64,([A-Za-z0-9+/=]+)$~D',$dataUrl,$match))
        throw new RuntimeException('Please sign in the signature box.');
    $bytes=base64_decode($match[1],true);
    if (!is_string($bytes) || strlen($bytes)>1024*1024 || strlen($bytes)<80 || substr($bytes,0,8)!=="\x89PNG\r\n\x1a\n")
        throw new RuntimeException('Invalid signature image.');
    $tmp=tempnam(sys_get_temp_dir(),'iworks_sig_');
    if ($tmp===false) throw new RuntimeException('Signature storage temporarily unavailable.');
    try {
        file_put_contents($tmp,$bytes);
        $row=cac_filing_load($id,$clientId);
        if (!$row || $row['status']!=='draft' || !isset($row['data']['people'][$personIndex])) throw new RuntimeException('Application not editable.');
        ensure_upload_directory();
        $dir=upload_directory().'/cac/filing_'.$id.'/person_'.$personIndex;
        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Signature storage unavailable.');
        $path=$dir.'/'.bin2hex(random_bytes(16)).'.png';
        if(!rename($tmp,$path)) throw new RuntimeException('Could not save signature.');
        chmod($path,0600);
        $docs=$row['docs'];$prev=$docs[$personIndex]['signature']['path']??'';
        $docs[$personIndex]['signature']=['path'=>$path,'name'=>'Drawn signature.png','size'=>strlen($bytes),'mime'=>'png'];
        db_execute('UPDATE cac_filing_applications SET docs_json=:docs,updated_at=:updated WHERE id=:id AND client_id=:client',
            ['docs'=>json_encode($docs,JSON_THROW_ON_ERROR),'updated'=>now(),'id'=>$id,'client'=>$clientId]);
        if($prev!=='' && is_file($prev) && str_starts_with(realpath($prev) ?: '',realpath(upload_directory().'/cac') ?: '/impossible'))@unlink($prev);
        return ['name'=>'Drawn signature','size'=>strlen($bytes)];
    } finally { if(is_file($tmp))@unlink($tmp); }
}
function cac_filing_missing(array $row): array {
    $d=$row['data'];$docs=$row['docs'];$errors=[];
    foreach(['first_choice'=>'First business name','business_activity'=>'Specific business activity',
      'business_email'=>'Business email','business_phone'=>'Business phone',
      'commencement_date'=>'Commencement date'] as $key=>$label)
        if(trim((string)($d[$key]??''))==='')$errors[]=$label.' is required.';
    $addressMode = (string)($d['address_mode'] ?? 'own');
    try {
        $office = cac_registered_office($addressMode, (string)($d['business_address'] ?? ''));
        if ($office === '') $errors[] = 'A traceable registered office address is required.';
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
    $nameCheck = cac_name_review((string)($d['first_choice'] ?? ''), (string)$row['kind']);
    foreach ($nameCheck['errors'] as $problem) $errors[] = 'Proposed name: ' . $problem;
    if (trim((string)($d['second_choice'] ?? '')) !== '') {
        $alternative = cac_name_review((string)$d['second_choice'], (string)$row['kind']);
        foreach ($alternative['errors'] as $problem) $errors[] = 'Alternative name: ' . $problem;
        if (strcasecmp(trim((string)$d['first_choice']), trim((string)$d['second_choice']))===0) {
            $errors[] = 'Use a different alternative name.';
        }
    }
    $people=is_array($d['people']??null)?$d['people']:[];
    try { cac_filing_quote((string)$row['kind'],$d); }
    catch(Throwable $e){$errors[]=$e->getMessage();}
    foreach($people as $i=>$person){
        foreach(['full_name'=>'full legal name','dob'=>'date of birth','nationality'=>'nationality',
          'residential_address'=>'residence address','id_type'=>'ID type','id_number'=>'ID number'] as $key=>$label)
            if(trim((string)($person[$key]??''))==='')$errors[]='Person '.($i+1).': '.$label.' required.';
        if (($person['id_type'] ?? '') !== '' || ($person['id_number'] ?? '') !== '') {
            $invalid = cac_validate_id_number((string)($person['id_type'] ?? ''), (string)($person['id_number'] ?? ''));
            if ($invalid !== null) $errors[] = 'Person '.($i+1).': ' . $invalid;
        }
        if (trim((string)($person['nin'] ?? '')) !== ''
            && !preg_match('/^[0-9]{11}$/D', trim((string)$person['nin']))) {
            $errors[] = 'Person '.($i+1).': NIN must be exactly 11 digits.';
        }
        foreach(['id'=>'government ID','photo'=>'passport photograph','signature'=>'signature'] as $type=>$label)
            if(empty($docs[$i][$type]['path']) || !is_file((string)$docs[$i][$type]['path']))
                $errors[]='Person '.($i+1).': '.$label.' must be uploaded.';
    }
    if ($row['kind']==='company') {
        if (($d['company_type'] ?? 'private_limited') !== 'private_limited') {
            $errors[]='Companies limited by guarantee require a specialist quote and regulatory review. Contact iWorks support before ordering.';
        }
        if(!is_numeric($d['share_capital']??null) || (float)$d['share_capital']<=0)$errors[]='Company share capital is required.';
        $directorCount = count(array_filter($people, static fn(array $p): bool =>
            str_contains(strtolower((string)($p['role'] ?? 'Director and shareholder')), 'director')));
        if ($directorCount < 1) $errors[] = 'At least one director is required.';
        $shares = array_values(array_filter(array_map(static fn(array $p): string =>
            trim((string)($p['share_percent'] ?? '')), $people),static fn(string $v):bool=>$v!==''));
        if (count($shares) > 0) {
            $sum = array_sum(array_map('floatval', $shares));
            if ($sum > 100.001) $errors[] = 'Share allocations cannot exceed 100%.';
        }
        // Complete share capital allocation and PSC checks require staff review.
    }
    return $errors;
}
function cac_filing_checkout(int $clientId,int $id): int {
    db()->beginTransaction();
    try {
        $raw=db_one('SELECT * FROM cac_filing_applications WHERE id=:id AND client_id=:client FOR UPDATE',['id'=>$id,'client'=>$clientId]);
        if(!$raw || $raw['status']!=='draft')throw new RuntimeException('Application already submitted or not found.');
        $row=cac_filing_decode($raw);
        $errors=cac_filing_missing($row);
        if($errors)throw new RuntimeException($errors[0]);
        $quote=cac_filing_quote((string)$row['kind'],$row['data']);
        // One current product; legacy business-name service IDs remain for historic jobs.
        $slug='company-registration';
        $service=get_service_by_slug($slug);
        if(!$service)throw new RuntimeException('Registration service is not active.');
        $data=$row['data'];$personNames=array_map(static fn(array $p):string=>(string)$p['full_name'],$data['people']);
        $description='CAC '.($row['kind']==='business'?'Business Name':'Company').' registration intake #'.$id."\n";
        $description.='Proposed name: '.$data['first_choice']."\n";
        $description.='Registration structure: '.($data['structure']??'company')."\n";
        $description.='Proprietors/directors: '.implode(', ',$personNames)."\n";
        $description.='Business activity: '.$data['business_activity']."\n";
        $description.='Registered address: '.cac_registered_office((string)($data['address_mode'] ?? 'own'), (string)$data['business_address'])."\n";
        $description.='Office service: '.(($data['address_mode'] ?? 'own')==='virtual'?'Managed registered office':'Customer-provided office')."\n";
        $description.='Optional privacy advisory: '.(!empty($data['privacy_advisory'])?'Requested (no concealment of CAC-mandated public records)':'No')."\n";
        $description.='Documents and sensitive personal details are in the restricted CAC filing record.';
        $jobId=create_job([
            'service_id'=>(int)$service['id'],'client_id'=>$clientId,'created_by'=>$clientId,
            'title'=>($row['kind']==='business'?'Business Name: ':'Company: ').$data['first_choice'],
            'description'=>$description,'amount'=>$quote['total'],'currency'=>$quote['currency'],
            'pay_currency'=>$quote['currency'],'tax_percent'=>0,
            'checkout_pay_method'=>'card','payment_status'=>'unpaid',
        ],['skip_duplicate_intake'=>true]);
        $invoice=get_invoice_by_job_id($jobId);
        if(!$invoice)throw new RuntimeException('Invoice could not be generated.');
        db_execute('UPDATE cac_filing_applications
            SET status=:status,job_id=:job,invoice_id=:invoice,quoted_ngn=:ngn,
                quoted_currency=:currency,quoted_total=:total,updated_at=:updated
            WHERE id=:id AND client_id=:client',
            ['status'=>'awaiting_payment','job'=>$jobId,'invoice'=>(int)$invoice['id'],
             'ngn'=>$quote['ngn'],'currency'=>$quote['currency'],'total'=>$quote['total'],
             'updated'=>now(),'id'=>$id,'client'=>$clientId]);
        db()->commit();
        return (int)$invoice['id'];
    } catch(Throwable $e) {
        if(db()->inTransaction())db()->rollBack();
        throw $e;
    }
}
function cac_filing_delete_draft(int $clientId,int $id): void {
    $row=cac_filing_load($id,$clientId);
    if(!$row || $row['status']!=='draft')throw new RuntimeException('Only a draft may be deleted.');
    db_execute("DELETE FROM cac_filing_applications WHERE id=:id AND client_id=:client AND status='draft'",
        ['id'=>$id,'client'=>$clientId]);
    foreach($row['docs'] as $personDocs) foreach((array)$personDocs as $doc){
        $path=(string)($doc['path']??'');
        if($path!=='' && is_file($path) && str_starts_with(realpath($path) ?: '',realpath(upload_directory().'/cac') ?: '/impossible')) @unlink($path);
    }
}
