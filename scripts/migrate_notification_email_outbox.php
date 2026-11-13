<?php
declare(strict_types=1);
// CLI-only controlled migration. Run --check before --apply, and back up DB.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode,['--check','--apply'],true)
    || ($mode === '--apply' && ($argv[2] ?? '') !== '--backup-confirmed')) {
    fwrite(STDERR, "Use --check or --apply --backup-confirmed after verified SQL backup.\n");
    exit(2);
}
require __DIR__.'/../app/bootstrap.php';
try {
    $exists = (int)(db_one(
        "SELECT COUNT(*) AS n FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'notification_email_outbox'"
    )['n'] ?? 0) > 0;
    if ($mode === '--check') {
        echo 'Email notification outbox: ' . ($exists?'ready':'missing') . "\n";
        exit($exists?0:3);
    }
    if (!$exists) {
        db_execute(
            "CREATE TABLE IF NOT EXISTS notification_email_outbox (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              notification_id INT UNSIGNED NOT NULL,
              user_id INT UNSIGNED NOT NULL,
              recipient_email VARCHAR(190) NOT NULL,
              subject VARCHAR(255) NOT NULL,
              plain_body TEXT NOT NULL,
              html_body LONGTEXT NULL,
              status ENUM('pending','sending','retry','sent','failed') NOT NULL DEFAULT 'pending',
              attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
              max_attempts INT UNSIGNED NOT NULL DEFAULT 5,
              next_attempt_at DATETIME NOT NULL,
              sent_at DATETIME NULL,
              last_error_code VARCHAR(80) NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              UNIQUE KEY uniq_notification_email (notification_id),
              KEY idx_notification_queue_due (status,next_attempt_at),
              CONSTRAINT fk_outbox_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
              CONSTRAINT fk_outbox_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
    echo "Notification email outbox schema ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Outbox migration failed; inspect private logs and verified backup.\n");
    exit(1);
}
