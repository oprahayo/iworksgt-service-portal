<?php
declare(strict_types=1);
/**
 * One SCUML document-review guidance service at NGN 40,000.
 * Official EFCC/SCUML application and certification are free.
 *
 * --check (default): database read only.
 * --apply --backup-confirmed: scoped catalogue transaction, private snapshot.
 * Existing invoice, payment and order records are never modified.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode=(string)($argv[1]??'--check');
if (!in_array($mode,['--check','--apply'],true)
    || ($mode==='--check' && count($argv)!==2)
    || ($mode==='--apply' && (count($argv)!==3 || $argv[2]!=='--backup-confirmed'))) {
    fwrite(STDERR,"Usage: php scripts/publish_scuml_guidance.php --check|--apply --backup-confirmed\n");
    exit(2);
}
$appRoot=realpath(trim((string)(getenv('IWORKS_PORTAL_ROOT')?:dirname(__DIR__))))?:'';
if (!$appRoot || !is_file($appRoot.'/app/config.php') || !is_file($appRoot.'/app/bootstrap.php')) {
    fwrite(STDERR,"BLOCK: no existing configured portal.\n");exit(3);
}
require_once $appRoot.'/app/bootstrap.php';
if (!is_configured() || !schema_table_exists('services')) {
    fwrite(STDERR,"BLOCK: service database unavailable.\n");exit(3);
}
$canonical='scuml-document-support';
$oldSlugs=[
    'scuml-business-name-document-support',
    'scuml-company-document-support',
];
$record=[
    'name'=>'SCUML Registration Support',
    'slug'=>$canonical,
    'description'=>'<p>iWORKS GLOBAL provides independent SCUML document-checking and compliance guidance to eligible businesses and organisations. We review your existing CAC documents, business tax evidence and applicable professional or sector records, helping you understand what to prepare before you apply through the official EFCC portal.</p>'
        .'<p><strong>SCUML registration and certificate are free of charge.</strong> ₦40,000 is the independent iWORKS standard service fee, not a government filing fee. iWORKS is not an authorised agent or representative of EFCC/SCUML. The business itself must submit its official application using its own genuine email and phone number. We cannot issue a certificate or guarantee approval.</p>'
        .'<p><strong>Special sectors:</strong> power/energy and other cases requiring additional documentation receive a separate quote before work or payment. Any additional charge is iWORKS-only, never a SCUML fee.</p>'
        .'<p>Official guidance: <a href="https://scuml.efcc.gov.ng/registration-guideline/">EFCC SCUML registration guidelines</a>.</p>',
    'short_description'=>'Independent SCUML document checking and registration guidance. Official EFCC registration and certificate are free.',
    'price'=>40000.00,
    'currency'=>'NGN',
    'category'=>'compliance',
    'featured_bullets'=>json_encode([
        'Review of CAC company or Business Name documents',
        'TIN and sector-specific requirement checklist',
        'Document organisation and application walkthrough',
        'Guidance on official queries and corrections',
        'Government registration and certificate remain free',
    ],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
];
try {
    $all=db_all(
        "SELECT id, slug, name, price, currency, is_active, is_public
         FROM services WHERE slug IN
         ('scuml-document-support','scuml-business-name-document-support',
          'scuml-company-document-support') ORDER BY slug"
    );
    $bySlug=[];
    foreach($all as $row)$bySlug[(string)$row['slug']]=$row;
    $actual=$bySlug[$canonical]??null;
    $oldPublished=array_values(array_filter($oldSlugs,
        static fn(string $slug):bool => isset($bySlug[$slug]) && (int)$bySlug[$slug]['is_public']===1
    ));
    echo "One public SCUML service: {$canonical} (NGN 40,000 iWORKS standard guidance fee).\n";
    echo "EFCC/SCUML registration/certificate: NGN 0. No government price difference by business structure.\n";
    echo "Power/energy enhanced scope is a separately confirmed iWORKS quote, not an EFCC tariff.\n";
    echo $actual ? "CANONICAL EXISTS: will align name, price and publication.\n"
                 : "CANONICAL MISSING: will create one new service.\n";
    foreach ($oldPublished as $slug) echo "HIDE DUPLICATE PUBLIC LISTING: {$slug}; preserve previous job references.\n";
    if ($mode==='--check') {echo "READ-ONLY CHECK PASSED; no data changed.\n";exit(0);}
    $needChange=!$actual
        || (string)$actual['name']!==$record['name']
        || (float)$actual['price']!==40000.00
        || (string)$actual['currency']!=='NGN'
        || (int)$actual['is_public']!==1
        || (int)$actual['is_active']!==1
        || $oldPublished!==[];
    if (!$needChange) {echo "ALREADY CURRENT: one public SCUML guidance service.\n";exit(0);}

    $home=getenv('HOME')?:'';
    if ($home==='' || !is_dir($home))throw new RuntimeException('Private HOME unavailable.');
    $parent=$home.'/iworks-deployment-backups';
    if (!is_dir($parent) && !mkdir($parent,0700,true) && !is_dir($parent)) {
        throw new RuntimeException('Private backup directory unavailable.');
    }
    chmod($parent,0700);
    $backup=$parent.'/scuml-unified-'.gmdate('Ymd\THis\Z').'-'.getmypid();
    if (!mkdir($backup,0700))throw new RuntimeException('Cannot create private SCUML snapshot.');
    $snapshot=$backup.'/before.json';
    $encoded=json_encode([
        'captured_at'=>gmdate('c'),
        'before'=>$all,
        'new_slug'=>$canonical,
        'action'=>'Create/update only the canonical offer; unpublish legacy listing, retain legacy rows and all orders',
    ],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if (file_put_contents($snapshot,$encoded,LOCK_EX)===false)throw new RuntimeException('Snapshot failed.');
    chmod($snapshot,0600);
    if (!is_file($snapshot) || filesize($snapshot)<150)throw new RuntimeException('Snapshot integrity failure.');
    $pdo=db();
    if ($pdo->inTransaction())throw new RuntimeException('Nested database transaction refused.');
    $pdo->beginTransaction();
    try {
        if ($actual) {
            db_execute(
                "UPDATE services
                    SET name=:name, description=:description, short_description=:short_description,
                        price=:price, currency=:currency, category=:category, featured_bullets=:featured_bullets,
                        is_active=1, is_public=1
                  WHERE id=:id AND slug=:slug",
                $record+['id'=>(int)$actual['id']]
            );
        } else {
            db_execute(
                "INSERT INTO services
                   (name,slug,description,short_description,price,currency,commission_percent,
                    tax_percent,category,featured_bullets,featured_image_id,
                    is_active,is_public,is_featured,created_at)
                 VALUES
                   (:name,:slug,:description,:short_description,:price,:currency,:commission_percent,
                    :tax_percent,:category,:featured_bullets,:featured_image_id,
                    :is_active,:is_public,:is_featured,:created_at)",
                $record+[
                    'commission_percent'=>0,
                    'tax_percent'=>7.50,
                    'featured_image_id'=>null,
                    'is_active'=>1,
                    'is_public'=>1,
                    'is_featured'=>0,
                    'created_at'=>date('Y-m-d H:i:s'),
                ]
            );
        }
        foreach ($oldPublished as $slug) {
            db_execute(
                'UPDATE services SET is_public=0 WHERE id=:id AND slug=:slug',
                ['id'=>(int)$bySlug[$slug]['id'],'slug'=>$slug]
            );
        }
        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }
    echo "PUBLISHED: {$canonical}; iWORKS guidance fee NGN 40,000.\n";
    echo "HISTORICAL SERVICES: preserved, old duplicate listings unpublished only.\n";
    echo "PRIVATE SNAPSHOT: $snapshot\n";
    echo "No existing order, invoice, payment or job modified.\n";
}catch(Throwable $e){
    fwrite(STDERR,"SCUML publishing BLOCKED: ".$e->getMessage()."\n");
    exit(1);
}
