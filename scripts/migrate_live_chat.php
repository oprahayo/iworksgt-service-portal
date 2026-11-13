<?php
declare(strict_types=1);
/**
 * Non-destructive iWorks live-chat migration. Requires cPanel CLI PHP.
 * php scripts/migrate_live_chat.php --check
 * php scripts/migrate_live_chat.php --apply
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check','--apply'], true)) {
    fwrite(STDERR,"Usage: php scripts/migrate_live_chat.php --check|--apply\n"); exit(2);
}
require_once dirname(__DIR__) . '/app/bootstrap.php';
$tables = ['iw_live_threads','iw_live_messages','iw_live_staff_presence'];
if (!is_configured()) { fwrite(STDERR,"Database is not configured.\n"); exit(1); }
try {
    if ($mode === '--apply') {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS iw_live_threads (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                visitor_token_hash CHAR(64) NOT NULL UNIQUE,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(254) NOT NULL,
                ip_prefix VARCHAR(80) NOT NULL,
                country_code CHAR(2) NULL,
                landing_page VARCHAR(255) NULL,
                status ENUM('open','ended','closed') NOT NULL DEFAULT 'open',
                assigned_user_id INT UNSIGNED NULL,
                visitor_last_seen_at DATETIME NULL,
                staff_last_seen_at DATETIME NULL,
                last_message_at DATETIME NULL,
                last_staff_read_at DATETIME NULL,
                rating TINYINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_live_status (status,updated_at),
                INDEX idx_live_ip (ip_prefix,created_at),
                INDEX idx_live_last_message (last_message_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS iw_live_messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                thread_id BIGINT UNSIGNED NOT NULL,
                sender ENUM('visitor','staff','system') NOT NULL,
                staff_user_id INT UNSIGNED NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_live_messages_thread (thread_id,id),
                INDEX idx_live_messages_sender (thread_id,sender,created_at),
                CONSTRAINT fk_live_messages_thread FOREIGN KEY (thread_id)
                    REFERENCES iw_live_threads (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS iw_live_staff_presence (
                user_id INT UNSIGNED NOT NULL PRIMARY KEY,
                last_seen_at DATETIME NOT NULL,
                INDEX idx_live_staff_seen (last_seen_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
    $ready = true;
    foreach ($tables as $table) {
        $columns = db_all('SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table',['table'=>$table]);
        $ok = count($columns) > 0;
        $ready = $ready && $ok;
        echo ($ok?'[OK] ':'[MISSING] ') . $table . "\n";
    }
    if (!$ready) {
        fwrite(STDERR, "Live chat is not yet installed. Back up the database, then run --apply.\n");
        exit(1);
    }
    echo "Live chat storage available. No existing customer records changed.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"Live chat migration failed: ".$e->getMessage()."\n");
    exit(1);
}
