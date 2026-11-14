<?php
declare(strict_types=1);
/**
 * Additive, idempotent live engagement migration.
 * Run only on cPanel CLI after a separately verified private SQL backup.
 * php scripts/migrate_live_chat_engagement.php --check
 * php scripts/migrate_live_chat_engagement.php --apply --backup-confirmed
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(2); }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)
    || ($mode === '--apply' && !in_array('--backup-confirmed', $argv, true))) {
    fwrite(STDERR, "Usage: php scripts/migrate_live_chat_engagement.php --check|--apply --backup-confirmed\n");
    exit(2);
}
require dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) { fwrite(STDERR, "Database unavailable.\n"); exit(3); }
$columns = [
    'current_page' => 'VARCHAR(160) NULL',
    'track_page' => 'TINYINT(1) NOT NULL DEFAULT 0',
    'notify_offline' => 'TINYINT(1) NOT NULL DEFAULT 0',
    'visitor_last_read_message_id' => 'BIGINT UNSIGNED NOT NULL DEFAULT 0',
];
try {
    if (!schema_table_exists('iw_live_threads') || !schema_table_exists('iw_live_messages')) {
        fwrite(STDERR, "Existing live chat tables missing. Run the original live chat migration first.\n");
        exit(3);
    }
    if ($mode === '--apply') {
        foreach ($columns as $name => $definition) {
            $existing = db_one('SELECT COLUMN_NAME FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column_name',
                ['table'=>'iw_live_threads','column_name'=>$name]);
            if (!$existing) {
                db()->exec('ALTER TABLE iw_live_threads ADD COLUMN '.$name.' '.$definition);
            }
        }
        db()->exec(
            "CREATE TABLE IF NOT EXISTS iw_live_email_outbox (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                thread_id BIGINT UNSIGNED NOT NULL,
                message_id BIGINT UNSIGNED NOT NULL,
                recipient_email VARCHAR(254) NOT NULL,
                status ENUM('pending','sending','retry','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
                next_attempt_at DATETIME NOT NULL,
                sent_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_iw_chat_email_message (message_id),
                INDEX idx_iw_chat_email_due (status,next_attempt_at),
                CONSTRAINT fk_iw_chat_email_thread FOREIGN KEY (thread_id)
                    REFERENCES iw_live_threads(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
    $ready = true;
    foreach ($columns as $name => $definition) {
        $row = db_one('SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column_name',
            ['table'=>'iw_live_threads','column_name'=>$name]);
        echo ($row ? 'PASS ' : 'MISSING ') . 'iw_live_threads.'.$name."\n";
        $ready = $ready && (bool)$row;
    }
    $table = schema_table_exists('iw_live_email_outbox');
    echo ($table ? 'PASS ' : 'MISSING ') . "iw_live_email_outbox\n";
    $ready = $ready && $table;
    if (!$ready) {
        fwrite(STDERR, "Engagement schema missing. Verify SQL backup before --apply.\n");
        exit(3);
    }
    echo "Live chat continuation schema ready; existing conversation records preserved.\n";
} catch (Throwable $e) {
    error_log('[iWORKS chat engagement migration] '.get_class($e));
    fwrite(STDERR, "Migration failed. Check private PHP error log; no forced cleanup of customer records.\n");
    exit(3);
}
