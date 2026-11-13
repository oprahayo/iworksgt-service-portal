<?php
declare(strict_types=1);

/**
 * Incident-only token invalidation after a private database backup.
 * Usage: php scripts/f01_expire_exposed_tokens.php --check|--apply
 * Print counts only: never print token values, email, queue message or URLs.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Use --check or --apply\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';
if (!is_configured()) {
    fwrite(STDERR, "Configured database connection required.\n");
    exit(2);
}
$queueAvailable = otp_queue_table_available();
try {
    $otp = (int) (db_one('SELECT COUNT(*) AS n FROM otp_codes WHERE consumed_at IS NULL')['n'] ?? 0);
    $reset = (int) (db_one('SELECT COUNT(*) AS n FROM password_resets WHERE used_at IS NULL')['n'] ?? 0);
    $queue = $queueAvailable
        ? (int) (db_one("SELECT COUNT(*) AS n FROM otp_dispatch_queue WHERE status IN ('queued','processing','retrying')")['n'] ?? 0)
        : 0;
    printf("Outstanding OTP challenges: %d; password resets: %d; queued OTP deliveries: %d\n", $otp, $reset, $queue);
    if ($mode === '--check') {
        echo "CHECK ONLY: no records changed.\n";
        exit(0);
    }
    $db = db();
    $db->beginTransaction();
    try {
        db_execute('UPDATE otp_codes SET consumed_at = :at WHERE consumed_at IS NULL', ['at' => now()]);
        db_execute('UPDATE password_resets SET used_at = :at WHERE used_at IS NULL', ['at' => now()]);
        if ($queueAvailable) {
            // Include already-sent queue rows: no residual recoverable codes or
            // email bodies should remain after incident evidence is backed up.
            db_execute("UPDATE otp_dispatch_queue SET otp_code='', message='', subject='',
                status=CASE WHEN status IN ('queued','processing','retrying')
                    THEN 'superseded' ELSE status END, updated_at=:at", ['at' => now()]);
        }
        $db->commit();
        echo "Outstanding reset/OTP tokens invalidated. Re-login or request fresh codes.\n";
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        throw $e;
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Token invalidation failed; inspect private server logs, do not retry blindly.\n");
    exit(1);
}
