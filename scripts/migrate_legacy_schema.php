<?php
declare(strict_types=1);
/**
 * Legacy schema migrations for published PHP/MySQL service modules.
 *
 * Usage (from an isolated private release stage containing authorized
 * app/config.php):
 *   php scripts/migrate_legacy_schema.php --check
 *   php scripts/migrate_legacy_schema.php --apply --backup-confirmed
 *
 * MySQL DDL implicitly commits; inspect and restore a verified private SQL
 * backup on staging before production apply. Never runs through HTTP.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)
    || ($mode === '--apply' && ($argv[2] ?? '') !== '--backup-confirmed')) {
    fwrite(STDERR, "Use --check or --apply --backup-confirmed after verified private backup.\n");
    exit(2);
}
if ($mode === '--apply') {
    define('IWORKS_SCHEMA_MIGRATION', true);
}
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/release_preflight.php';
if (!is_configured()) {
    fwrite(STDERR, "Configured database credentials are required.\n");
    exit(2);
}
try {
    $schema = iworks_required_release_schema();
    $tableNames = array_unique(array_merge($schema['tables'], [
        'job_reviews', 'cac_filing_applications', 'trademark_applications',
        'stripe_customer_vault',
    ]));
    foreach ($tableNames as $name) {
        echo 'table ' . $name . ': ' . (schema_table_exists($name) ? 'present' : 'missing') . "\n";
    }
    if ($mode === '--check') {
        echo "READ-ONLY: no schema changes attempted.\n";
        exit(0);
    }
    if (!iworks_schema_mutation_allowed()) {
        throw new RuntimeException('Schema migration privilege not enabled.');
    }

    // Explicitly invoke previously implicit or route-time schema builders.
    // Each builder is designed to be idempotent when its table/columns exist.
    ensure_crypto_schema_upgrades();
    ensure_media_schema_upgrades();
    ensure_job_review_schema();
    cac_filing_schema();
    trademark_ensure_schema();
    stripe_vault_schema();
    echo "Legacy schema migrations completed. Rerun --check and release preflight.\n";
} catch (Throwable $error) {
    error_log('iWorks controlled schema migration failed: ' . get_class($error));
    fwrite(STDERR, "Migration failed; review private diagnostics and restore from the verified staging backup if needed.\n");
    exit(1);
}
