<?php
declare(strict_types=1);
/**
 * Publish two independent iWORKS SCUML document-support services.
 *
 * Usage: php scripts/publish_scuml_guidance.php --check
 *        php scripts/publish_scuml_guidance.php --apply --backup-confirmed
 *
 * Important: SCUML government registration is FREE. This is NOT an EFCC
 * filing, government certificate sale or representation as an authorised
 * registration agent. It is a private document-review and guidance service.
 * Only inserts missing, exact-slug services. Existing records and historical
 * invoices/orders/prices are never changed.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = (string)($argv[1] ?? '--check');
if (!in_array($mode, ['--check','--apply'], true)
    || ($mode === '--apply' && (string)($argv[2] ?? '') !== '--backup-confirmed')
    || ($mode === '--check' && count($argv) !== 2)
    || ($mode === '--apply' && count($argv) !== 3)) {
    fwrite(STDERR, "Usage: php scripts/publish_scuml_guidance.php --check|--apply --backup-confirmed\n");
    exit(2);
}
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured() || !schema_table_exists('services')) {
    fwrite(STDERR, "BLOCK: existing iWORKS service catalogue/database is unavailable.\n");
    exit(3);
}

$official = 'https://scuml.efcc.gov.ng/registration-guideline/';
$disclaimer = '<p><strong>EFCC/SCUML registration and certification are completely free.</strong> iWORKS GLOBAL LTD is an independent document review and compliance guidance provider, not an authorised EFCC/SCUML agent. The customer applies through the official government portal with their own valid email and phone. We neither issue certificates nor guarantee approval.</p>';
$serviceRecords = [
    'scuml-business-name-document-support' => [
        'name' => 'SCUML Document Support — Business Name',
        'price' => 35000.00,
        'short' => 'Independent SCUML business name document review and official application guidance. Government registration and certificate: free.',
        'description' => '<p>For eligible Nigerian businesses registered with CAC as a Business Name. iWORKS reviews and organises your existing registration documents and guides you through the SCUML requirements. You submit directly through the EFCC/SCUML portal.</p>'
            . '<p><strong>Prepare:</strong> CAC Business Name certificate, CAC status report (BN-01), TIN printout and any applicable sector licence or professional certificate.</p>'
            . $disclaimer
            . '<p>Official requirements: <a href="' . $official . '">SCUML registration guideline</a>.</p>',
        'bullets' => [
            'Business name eligibility and sector checklist',
            'CAC certificate, BN-01 and TIN document review',
            'PDF organisation and application walkthrough',
            'Clarification of official requests or queries',
            'No government/SCUML certificate fee',
        ],
    ],
    'scuml-company-document-support' => [
        'name' => 'SCUML Document Support — Limited Company',
        'price' => 45000.00,
        'short' => 'Independent SCUML Ltd company document review and official application guidance. Government registration and certificate: free.',
        'description' => '<p>For eligible Nigerian companies limited by shares. iWORKS reviews your CAC incorporation records and tax documentation, and provides a checklist for SCUML’s sector-specific requirements. The company applies directly through the official EFCC/SCUML portal.</p>'
            . '<p><strong>Prepare:</strong> certificate of incorporation, MEMART, CAC status report (CAC 1.1 or older CAC 2/CAC 7), TIN printout and relevant sector licence or professional certificate.</p>'
            . $disclaimer
            . '<p>Official requirements: <a href="' . $official . '">SCUML registration guideline</a>.</p>',
        'bullets' => [
            'Limited company document eligibility review',
            'CAC incorporation, MEMART and status report checks',
            'TIN and sector-specific requirements',
            'PDF organisation and customer-led portal walkthrough',
            'No government/SCUML certificate fee',
        ],
    ],
];

try {
    $existing = db_all(
        "SELECT id, slug, name, price, currency, is_active, is_public FROM services
         WHERE slug IN ('scuml-business-name-document-support','scuml-company-document-support')
         ORDER BY slug"
    );
    $existingBySlug = [];
    foreach ($existing as $row) $existingBySlug[(string)$row['slug']] = $row;
    $toCreate = [];
    foreach ($serviceRecords as $slug => $offer) {
        if (!isset($existingBySlug[$slug])) {
            echo sprintf("NEW %s: NGN %.2f private guidance fee (EFCC: NGN 0)\n", $slug, $offer['price']);
            $toCreate[$slug] = $offer;
            continue;
        }
        $row = $existingBySlug[$slug];
        if ((string)$row['name'] !== $offer['name']
            || (string)$row['currency'] !== 'NGN'
            || (float)$row['price'] <= 0
            || (int)$row['is_active'] !== 1
            || (int)$row['is_public'] !== 1) {
            throw new RuntimeException(
                "Existing SCUML product '$slug' is customised or unpublished. Preserve it and review in Admin → Services."
            );
        }
        echo sprintf("EXISTS %s: NGN %.2f (preserved, no update)\n", $slug, (float)$row['price']);
    }
    echo "SCUML itself charges NGN 0. iWORKS is not an authorised SCUML agent.\n";
    echo "Existing jobs, invoices, payment records, services and CMS settings will not be modified.\n";
    if ($mode === '--check') { echo "READ-ONLY CHECK PASSED.\n"; exit(0); }
    if (!$toCreate) { echo "ALREADY PUBLISHED: No database modifications needed.\n"; exit(0); }

    $home = getenv('HOME') ?: '';
    if ($home === '' || !is_dir($home)) throw new RuntimeException('Private HOME directory is unavailable.');
    $parent = $home.'/iworks-deployment-backups';
    if (!is_dir($parent) && !mkdir($parent,0700,true) && !is_dir($parent)) {
        throw new RuntimeException('Cannot create private backup folder.');
    }
    chmod($parent,0700);
    $backup = $parent.'/scuml-publish-'.gmdate('Ymd\THis\Z').'-'.getmypid();
    if (!mkdir($backup,0700)) throw new RuntimeException('Cannot create SCUML backup directory.');
    $record = [
        'timestamp' => gmdate('c'),
        'existing_services' => $existing,
        'new_slugs' => array_keys($toCreate),
        'proposed_fees_ngn' => array_map(static fn($v) => $v['price'], $serviceRecords),
        'note' => 'Private snapshot. No existing rows were updated.',
    ];
    $snapshotFile = $backup.'/catalogue-before.json';
    $json = json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if (file_put_contents($snapshotFile,$json,LOCK_EX) === false) throw new RuntimeException('Backup snapshot failed.');
    chmod($snapshotFile,0600);
    if (!is_readable($snapshotFile) || filesize($snapshotFile) < 150) {
        throw new RuntimeException('Backup snapshot integrity failed.');
    }
    $pdo = db();
    if ($pdo->inTransaction()) throw new RuntimeException('Nested database transaction refused.');
    $pdo->beginTransaction();
    try {
        foreach ($toCreate as $slug => $offer) {
            db_execute(
                'INSERT INTO services
                  (name, slug, description, short_description, price, currency, commission_percent,
                   tax_percent, category, featured_bullets, featured_image_id,
                   is_active, is_public, is_featured, created_at)
                 VALUES
                  (:name, :slug, :description, :short_description, :price, :currency, :commission_percent,
                   :tax_percent, :category, :featured_bullets, :featured_image_id,
                   :is_active, :is_public, :is_featured, :created_at)',
                [
                    'name' => $offer['name'],
                    'slug' => $slug,
                    'description' => $offer['description'],
                    'short_description' => $offer['short'],
                    'price' => $offer['price'],
                    'currency' => 'NGN',
                    'commission_percent' => 0,
                    'tax_percent' => 7.50,
                    'category' => 'compliance',
                    'featured_bullets' => json_encode($offer['bullets'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
                    'featured_image_id' => null,
                    'is_active' => 1,
                    'is_public' => 1,
                    'is_featured' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    echo "PUBLISHED: ".implode(', ',array_keys($toCreate))."\n";
    echo "Private catalogue snapshot: $snapshotFile\n";
    echo "Only these new SCUML service rows were inserted. Existing orders, invoices and payments remain untouched.\n";
} catch (Throwable $error) {
    fwrite(STDERR,"SCUML publish BLOCKED: ".$error->getMessage()."\n");
    exit(1);
}
