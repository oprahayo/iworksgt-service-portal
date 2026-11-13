<?php
declare(strict_types=1);
/**
 * Controlled F09 migration. Never run implicitly during web requests.
 * php scripts/migrate_gateway_uniqueness.php --check
 * php scripts/migrate_gateway_uniqueness.php --apply --backup-confirmed
 *
 * The operator must create/verify a separate private database backup before
 * applying because MySQL DDL commits implicitly.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check','--apply'], true)) {
    fwrite(STDERR, "Use --check or --apply --backup-confirmed\n");
    exit(2);
}
if ($mode === '--apply' && ($argv[2] ?? '') !== '--backup-confirmed') {
    fwrite(STDERR, "Backup confirmation required; no migration attempted.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';
$unique = [
    'uniq_gateway_provider_tx' => 'gateway_transaction_id',
    'uniq_gateway_provider_session' => 'gateway_session_id',
];
try {
    foreach ($unique as $key => $column) {
        $exists = (int) (db_one(
            "SELECT COUNT(*) AS n FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'job_payments'
               AND index_name = :idx", ['idx' => $key]
        )['n'] ?? 0) > 0;
        $duplicateGroups = (int) (db_one(
            "SELECT COUNT(*) AS n FROM (
               SELECT gateway_provider, {$column}
               FROM job_payments
               WHERE gateway_provider IS NOT NULL AND {$column} IS NOT NULL
               GROUP BY gateway_provider, {$column} HAVING COUNT(*) > 1
             ) AS d"
        )['n'] ?? 0);
        echo $key . ': ' . ($exists ? 'present' : 'missing')
            . '; duplicate groups: ' . $duplicateGroups . "\n";
        if (!$exists && $mode === '--apply') {
            if ($duplicateGroups !== 0) {
                throw new RuntimeException('Duplicate payment identifiers require a human reconciliation before migration.');
            }
            // Identifiers are constants from the approved allowlist, never
            // caller-provided SQL.
            db_execute("ALTER TABLE job_payments
                ADD UNIQUE KEY {$key} (gateway_provider, {$column})");
            echo $key . ": added\n";
        }
    }
    if ($mode === '--check') {
        echo "CHECK ONLY: no database records or indexes changed.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Gateway uniqueness migration blocked. Review private diagnostics and backup.\n");
    exit(1);
}
