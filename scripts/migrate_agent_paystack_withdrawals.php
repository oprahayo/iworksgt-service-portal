<?php
declare(strict_types=1);
/**
 * CLI-only backup-gated migration for Paystack agent payout groundwork (live gated off by default).
 * php scripts/migrate_agent_paystack_withdrawals.php --check
 * php scripts/migrate_agent_paystack_withdrawals.php --apply --backup-confirmed
 *
 * Make and verify an off-server backup first. DDL implicitly commits on MySQL.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)
    || ($mode === '--apply' && ($argv[2] ?? '') !== '--backup-confirmed')) {
    fwrite(STDERR, "Use --check or --apply --backup-confirmed after verifying a database backup.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';
$tables = [
    'agent_payout_beneficiaries' => <<<'SQL'
CREATE TABLE IF NOT EXISTS agent_payout_beneficiaries (
    agent_id INT UNSIGNED PRIMARY KEY,
    bank_code VARCHAR(12) NOT NULL,
    bank_name VARCHAR(120) NOT NULL,
    account_number VARCHAR(10) NOT NULL,
    account_name VARCHAR(140) NOT NULL,
    recipient_code VARCHAR(100) NOT NULL,
    environment ENUM('test','live') NOT NULL DEFAULT 'test',
    verified_at DATETIME NOT NULL,
    CONSTRAINT fk_agent_payout_beneficiary FOREIGN KEY (agent_id)
        REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
    'agent_withdrawal_transfers' => <<<'SQL'
CREATE TABLE IF NOT EXISTS agent_withdrawal_transfers (
    withdrawal_id INT UNSIGNED PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    amount_kobo BIGINT UNSIGNED NOT NULL,
    recipient_code VARCHAR(100) NOT NULL,
    bank_code VARCHAR(12) NOT NULL,
    account_last4 VARCHAR(4) NOT NULL,
    environment ENUM('test','live') NOT NULL DEFAULT 'test',
    state ENUM('awaiting_review','queued','submitting','pending','success','failed','reversed','exception')
        NOT NULL DEFAULT 'queued',
    reviewed_by INT UNSIGNED NULL,
    provider_transfer_id VARCHAR(80) NULL,
    last_error_code VARCHAR(50) NULL,
    attempted_at DATETIME NULL,
    settled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_agent_withdrawal_transfer FOREIGN KEY (withdrawal_id)
        REFERENCES withdrawal_requests(id) ON DELETE RESTRICT,
    CONSTRAINT fk_agent_payout_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
];
try {
    foreach ($tables as $table => $ddl) {
        $exists = schema_table_exists($table);
        echo $table . ': ' . ($exists ? 'present' : 'missing') . "\n";
        if (!$exists && $mode === '--apply') {
            db_execute($ddl);
            if (!schema_table_exists($table)) {
                throw new RuntimeException('New table could not be verified.');
            }
        }
    }
    if ($mode === '--apply') {
        // Forward-compatible upgrade for a previously installed TEST-only draft.
        foreach (['agent_payout_beneficiaries', 'agent_withdrawal_transfers'] as $table) {
            if (!schema_column_exists($table, 'environment')) {
                db_execute("ALTER TABLE " . $table .
                    " ADD COLUMN environment ENUM('test','live') NOT NULL DEFAULT 'test'");
            }
        }
        set_setting('withdrawal_minimum', '5000');
        set_setting('withdrawal_cooldown_days', '0');
        echo "Approved withdrawal defaults saved. Schema prepared. Payout mode remains whatever private runtime configuration explicitly selects.\n";
    } else {
        echo "CHECK ONLY; no changes applied.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Migration blocked; check database backup and private logs.\n");
    exit(1);
}
