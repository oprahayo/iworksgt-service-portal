<?php
declare(strict_types=1);
/**
 * Controlled, idempotent security migration: PHP CLI only.
 * php scripts/migrate_auth_attempt_limits.php --check
 * php scripts/migrate_auth_attempt_limits.php --apply --backup-confirmed
 *
 * Require a verified private SQL backup; MySQL DDL commits implicitly.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)
    || ($mode === '--apply' && ($argv[2] ?? '') !== '--backup-confirmed')) {
    fwrite(STDERR, "Use --check or --apply --backup-confirmed (after a private verified SQL backup).\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';
if (!is_configured()) { fwrite(STDERR, "Database configuration unavailable.\n"); exit(2); }
try {
    $table = (int) (db_one(
        "SELECT COUNT(*) AS n FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'auth_attempt_limits'"
    )['n'] ?? 0) > 0;
    $column = (int) (db_one(
        "SELECT COUNT(*) AS n FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'otp_codes'
           AND column_name = 'failed_attempts'"
    )['n'] ?? 0) > 0;
    echo 'Rate-limit table: ' . ($table ? 'present' : 'missing') . "\n";
    echo 'OTP lockout column: ' . ($column ? 'present' : 'missing') . "\n";
    if ($mode === '--check') {
        echo "CHECK ONLY: no changes made.\n";
        exit($table && $column ? 0 : 3);
    }

    if (!$table) {
        db_execute(
            'CREATE TABLE IF NOT EXISTS auth_attempt_limits (
                scope VARCHAR(32) NOT NULL,
                subject_hash CHAR(64) NOT NULL,
                window_started_at INT UNSIGNED NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (scope, subject_hash, window_started_at),
                KEY idx_auth_window_age (window_started_at)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
    if (!$column) {
        db_execute(
            'ALTER TABLE otp_codes ADD COLUMN
             failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER consumed_at'
        );
    }
    echo "Authentication rate-limit migration applied. Run --check to verify.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Authentication migration failed; inspect private logs and restore using the verified backup if needed.\n");
    exit(1);
}
