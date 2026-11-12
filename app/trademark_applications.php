<?php
declare(strict_types=1);

/**
 * First product-specific customer workflow: Nigerian trademark application.
 * Data belongs to one authenticated client. Checkout uses the existing
 * jobs / invoices / hosted gateway implementations.
 */

function trademark_price_usd(): float
{
    $value = (float) setting('trademark_price_usd_per_class', '185');
    return $value >= 1 && $value <= 100000 ? round($value, 2) : 185.00;
}

function trademark_class_labels(): array
{
    return [
        1=>'Chemicals',2=>'Paints and coatings',3=>'Cosmetics and cleaning',
        4=>'Industrial oils and fuels',5=>'Pharmaceuticals',6=>'Common metals',
        7=>'Machines',8=>'Hand tools',9=>'Electronics and software',
        10=>'Medical apparatus',11=>'Lighting and heating',12=>'Vehicles',
        13=>'Firearms and explosives',14=>'Jewellery and watches',15=>'Musical instruments',
        16=>'Paper and printed matter',17=>'Rubber and plastics',18=>'Leather and bags',
        19=>'Non-metallic building materials',20=>'Furniture',21=>'Housewares',
        22=>'Ropes and tents',23=>'Yarns and threads',24=>'Textiles',
        25=>'Clothing and footwear',26=>'Lace and buttons',27=>'Carpets and mats',
        28=>'Games and sporting goods',29=>'Meat and dairy foods',30=>'Coffee, tea and cereals',
        31=>'Raw agricultural products',32=>'Beer and non-alcoholic drinks',
        33=>'Alcoholic beverages',34=>'Tobacco and smoking articles',
        35=>'Advertising and business',36=>'Finance and insurance',
        37=>'Construction and repair',38=>'Telecommunications',
        39=>'Transport and storage',40=>'Treatment of materials',
        41=>'Education and entertainment',42=>'Technology and software services',
        43=>'Food, drink and accommodation services',
        44=>'Medical, beauty and agricultural services',
        45=>'Legal, security and personal services',
    ];
}

function trademark_normalize_classes(mixed $input): array
{
    if (!is_array($input)) { return []; }
    $classes = [];
    foreach ($input as $entry) {
        if (!is_string($entry) && !is_int($entry)) { continue; }
        $raw = (string) $entry;
        if (!preg_match('/^(?:[1-9]|[1-3][0-9]|4[0-5])$/D', $raw)) { continue; }
        $classes[] = (int) $raw;
    }
    $classes = array_values(array_unique($classes));
    sort($classes, SORT_NUMERIC);
    return $classes;
}

function trademark_quote(array $classes, string $currency, float $unitPrice, float $fxRate): array
{
    $count = count(trademark_normalize_classes($classes));
    if (!in_array($currency, ['USD', 'NGN'], true)) {
        throw new RuntimeException('Choose USD or NGN as your payment currency.');
    }
    if (!is_finite($unitPrice) || $unitPrice < 1 || $unitPrice > 100000) {
        throw new RuntimeException('Invalid configured price per trademark class.');
    }
    if (!is_finite($fxRate) || $fxRate <= 0) {
        throw new RuntimeException('NGN exchange rate unavailable. Contact support before checkout.');
    }
    $usdTotal = round($count * $unitPrice, 2);
    $total = $currency === 'NGN' ? round($usdTotal * $fxRate, 2) : $usdTotal;
    return [
        'count' => $count, 'unit_usd' => round($unitPrice, 2),
        'usd_total' => $usdTotal, 'currency' => $currency,
        'fx_rate' => $fxRate, 'total' => $total,
    ];
}

function trademark_form_values(array $post): array
{
    $fields = ['mark_name'=>180, 'owner_name'=>180, 'owner_email'=>190,
        'owner_phone'=>50, 'owner_address'=>500, 'description'=>2500];
    $data = [];
    foreach ($fields as $key=>$max) {
        $value = $post[$key] ?? '';
        $data[$key] = is_string($value) ? mb_safe_trim(strip_tags($value), $max) : '';
    }
    $type = (string) ($post['mark_type'] ?? 'word');
    $data['mark_type'] = in_array($type, ['word','logo','slogan','combined'], true) ? $type : 'word';
    $data['country'] = 'Nigeria'; // Explicit launch scope. Do not silently price other jurisdictions.
    $data['classes'] = trademark_normalize_classes($post['classes'] ?? []);
    $currency = (string) ($post['currency'] ?? 'USD');
    $data['currency'] = in_array($currency, ['USD','NGN'], true) ? $currency : 'USD';
    return $data;
}

function mb_safe_trim(string $value, int $max): string
{
    $value = trim($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function trademark_validate_complete(array $data, bool $hasLogo): void
{
    foreach (['mark_name'=>'Trademark name','owner_name'=>'Owner name',
        'owner_address'=>'Owner address','owner_phone'=>'Phone number'] as $key=>$label) {
        if (trim((string) ($data[$key] ?? '')) === '') {
            throw new RuntimeException($label . ' is required before review.');
        }
    }
    if (!filter_var((string) ($data['owner_email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Provide a valid trademark owner email address.');
    }
    if (trademark_normalize_classes($data['classes'] ?? []) === []) {
        throw new RuntimeException('Select at least one Nice Classification class.');
    }
    if (($data['mark_type'] ?? '') === 'logo' || ($data['mark_type'] ?? '') === 'combined') {
        if (!$hasLogo) { throw new RuntimeException('Upload your trademark logo before checkout.'); }
    }
}

function trademark_ensure_schema(): void
{
    static $ready = false;
    if ($ready) { return; }
    // Existing installs should not require CREATE privilege on every page.
    if (schema_table_exists('trademark_applications')) { $ready = true; return; }
    db_execute("CREATE TABLE IF NOT EXISTS trademark_applications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNSIGNED NOT NULL,
        status VARCHAR(24) NOT NULL DEFAULT 'draft',
        payload_json MEDIUMTEXT NOT NULL,
        logo_filename VARCHAR(100) NULL,
        job_id INT UNSIGNED NULL,
        invoice_id INT UNSIGNED NULL,
        unit_price_usd DECIMAL(12,2) NULL,
        price_usd DECIMAL(14,2) NULL,
        pay_currency CHAR(3) NULL,
        total_amount DECIMAL(16,2) NULL,
        fx_rate_used DECIMAL(16,4) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_tm_client_status (client_id, status),
        INDEX idx_tm_invoice (invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ready = true;
}

function trademark_decode(array $row): array
{
    $decoded = json_decode((string) ($row['payload_json'] ?? '{}'), true);
    $row['data'] = is_array($decoded) ? $decoded : [];
    return $row;
}

function trademark_find(int $id, int $clientId): ?array
{
    if ($id <= 0 || $clientId <= 0) { return null; }
    $row = db_one('SELECT tm.*, j.payment_status FROM trademark_applications tm
        LEFT JOIN jobs j ON j.id = tm.job_id
        WHERE tm.id = :id AND tm.client_id = :client_id LIMIT 1',
        ['id'=>$id,'client_id'=>$clientId]);
    return $row ? trademark_decode($row) : null;
}

function trademark_list(int $clientId): array
{
    return array_map('trademark_decode', db_all(
        'SELECT tm.*, j.payment_status FROM trademark_applications tm
        LEFT JOIN jobs j ON j.id = tm.job_id
        WHERE tm.client_id = :client_id ORDER BY tm.updated_at DESC, tm.id DESC LIMIT 100',
        ['client_id'=>$clientId]
    ));
}

function trademark_private_logo_dir(): string
{
    // Keep original logo artwork outside every web document root. The portal
    // is normally /home/USER/public_html/portal.iworksgt.com.
    $home = trim((string) getenv('HOME'));
    if ($home === '' || !is_dir($home)) {
        $home = dirname(__DIR__, 3);
    }
    return rtrim($home, '/') . '/.iworks-private/trademark-logos';
}

function trademark_store_logo(?array $file): ?string
{
    if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int) ($file['error'] ?? -1) !== UPLOAD_ERR_OK
        || (int) ($file['size'] ?? 0) < 1
        || (int) ($file['size'] ?? 0) > 4 * 1024 * 1024
        || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Upload a JPG, PNG or WebP logo smaller than 4 MB.');
    }
    $tmp = (string) $file['tmp_name'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
    if ($ext === null || @getimagesize($tmp) === false) {
        throw new RuntimeException('Only valid JPG, PNG or WebP logo images are accepted.');
    }
    $dir = trademark_private_logo_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Logo storage is unavailable.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new RuntimeException('Unable to save the trademark logo.');
    }
    chmod($dir . '/' . $name, 0600);
    return $name;
}

function trademark_remove_logo(?string $name): void
{
    if (!is_string($name) || !preg_match('/^[a-f0-9]{32}\.(?:png|jpg|webp)$/D', $name)) { return; }
    $path = trademark_private_logo_dir() . '/' . $name;
    if (is_file($path)) { @unlink($path); }
}

function trademark_save_draft(int $clientId, int $id, array $data, ?array $upload): int
{
    $existing = $id > 0 ? trademark_find($id, $clientId) : null;
    if ($id > 0 && !$existing) { throw new RuntimeException('Application not found.'); }
    if ($existing && $existing['status'] !== 'draft') {
        throw new RuntimeException('This application has already been submitted for payment.');
    }
    $newLogo = trademark_store_logo($upload);
    $logo = $newLogo ?? ($existing['logo_filename'] ?? null);
    $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) { throw new RuntimeException('Invalid trademark details.'); }
    try {
        if ($existing) {
            db_execute('UPDATE trademark_applications SET payload_json = :payload, logo_filename = :logo, updated_at = :updated
                WHERE id = :id AND client_id = :client AND status = :status',
                ['payload'=>$encoded,'logo'=>$logo,'updated'=>now(),'id'=>$id,'client'=>$clientId,'status'=>'draft']);
            if ($newLogo && !empty($existing['logo_filename'])) { trademark_remove_logo($existing['logo_filename']); }
            return $id;
        }
        db_execute('INSERT INTO trademark_applications (client_id,status,payload_json,logo_filename,created_at,updated_at)
            VALUES (:client,:status,:payload,:logo,:created,:updated)',
            ['client'=>$clientId,'status'=>'draft','payload'=>$encoded,'logo'=>$logo,'created'=>now(),'updated'=>now()]);
        return db_last_id();
    } catch (Throwable $e) {
        if ($newLogo) { trademark_remove_logo($newLogo); }
        throw $e;
    }
}

function trademark_delete_draft(int $id, int $clientId): void
{
    $row = trademark_find($id, $clientId);
    if (!$row || $row['status'] !== 'draft') {
        throw new RuntimeException('Only your unpaid drafts can be deleted.');
    }
    db_execute('DELETE FROM trademark_applications WHERE id = :id AND client_id = :client AND status = :status',
        ['id'=>$id,'client'=>$clientId,'status'=>'draft']);
    trademark_remove_logo($row['logo_filename'] ?? null);
}

function trademark_checkout(int $id, int $clientId): int
{
    db()->beginTransaction();
    try {
        $row = db_one('SELECT * FROM trademark_applications WHERE id = :id AND client_id = :client FOR UPDATE',
            ['id'=>$id,'client'=>$clientId]);
        if (!$row || $row['status'] !== 'draft') {
            throw new RuntimeException('This application cannot be submitted twice.');
        }
        $row = trademark_decode($row);
        $data = $row['data'];
        trademark_validate_complete($data, !empty($row['logo_filename']));
        $service = get_service_by_slug('trademark');
        if (!$service) {
            throw new RuntimeException('Trademark ordering is not active. Contact iWorks support.');
        }
        $fx = (float) fx_ngn_per_usd();
        $quote = trademark_quote($data['classes'], $data['currency'], trademark_price_usd(), $fx);
        $markName = (string) $data['mark_name'];
        $description = "Trademark application for {$markName}\n";
        $description .= "Country: Nigeria\n";
        $description .= "Nice classes: " . implode(', ', $data['classes']) . "\n";
        $description .= "Trademark type: " . $data['mark_type'] . "\n";
        $description .= "Owner: " . $data['owner_name'] . "\n";
        $description .= "Applicant reference: TM-{$id}\n";
        $description .= "Fee: USD " . number_format($quote['unit_usd'], 2, '.', '') . " per class, " . $quote['count'] . " classes.";
        $jobId = create_job([
            'service_id'=>(int) $service['id'],
            'client_id'=>$clientId,'created_by'=>$clientId,
            'title'=>'Trademark: ' . $markName . ' (' . $quote['count'] . ' classes)',
            'description'=>$description,
            'amount'=>$quote['total'],'currency'=>$quote['currency'],'pay_currency'=>$quote['currency'],
            'tax_percent'=>0,'checkout_pay_method'=>'card','payment_status'=>'unpaid',
        ]);
        $invoice = get_invoice_by_job_id($jobId);
        if (!$invoice) { throw new RuntimeException('Invoice could not be generated.'); }
        db_execute('UPDATE trademark_applications
            SET status = :status, job_id = :job, invoice_id = :invoice,
                unit_price_usd = :unit_usd, price_usd = :usd_total, pay_currency = :currency,
                total_amount = :total, fx_rate_used = :fx, updated_at = :updated
            WHERE id = :id AND client_id = :client AND status = :draft',
            ['status'=>'awaiting_payment','job'=>$jobId,'invoice'=>(int) $invoice['id'],
             'unit_usd'=>$quote['unit_usd'],'usd_total'=>$quote['usd_total'],
             'currency'=>$quote['currency'],'total'=>$quote['total'],'fx'=>$quote['fx_rate'],
             'updated'=>now(),'id'=>$id,'client'=>$clientId,'draft'=>'draft']);
        db()->commit();
        return (int) $invoice['id'];
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); }
        throw $e;
    }
}
