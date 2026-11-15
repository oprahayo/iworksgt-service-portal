<?php
declare(strict_types=1);
/**
 * Five-minute cPanel worker: transactional email dispatch and strict 30-day
 * inactivity expiry. CLI only; never execute through an HTTP route.
 *
 * php scripts/cron_support_chat.php --check
 * php scripts/cron_support_chat.php --run
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = (string)($argv[1] ?? '--check');
if (count($argv) !== 2 || !in_array($mode,['--check','--run'],true)) {
    fwrite(STDERR,"Usage: php scripts/cron_support_chat.php --check|--run\n");
    exit(2);
}
require dirname(__DIR__).'/app/bootstrap.php';
try {
    if (!is_configured() || !iw_chat_engagement_ready()) {
        throw new RuntimeException('The live chat/outbox schema is unavailable.');
    }
    $old = db_one("SELECT COUNT(*) AS n FROM iw_live_threads
        WHERE updated_at < DATE_SUB(NOW(),INTERVAL 30 DAY)");
    $queue = db_one("SELECT COUNT(*) AS n FROM iw_live_email_outbox
        WHERE status IN ('pending','retry','sending')");
    echo 'Expired support cases: '.(int)($old['n']??0)."\n";
    echo 'Pending customer emails: '.(int)($queue['n']??0)."\n";
    echo 'SMTP configured: '.(email_enabled()?'yes':'NO')."\n";
    echo 'OTP dispatch queue ready: '.(otp_queue_table_available()?'yes':'NO')."\n";
    if ($mode === '--check') {
        echo "READ ONLY: no messages sent or deleted.\n";
        exit(email_enabled() && otp_queue_table_available() ? 0 : 3);
    }
    // Share the main queue worker's lock: two schedules must never compete.
    $lockFile = dirname(mail_log_path()).'/cron-queue.lock';
    $lock = @fopen($lockFile,'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        fwrite(STDERR,"Queue worker already running or private lock unavailable.\n");
        exit($lock ? 0 : 1);
    }
    @chmod($lockFile,0600);
    // Keep retention enforcement independent of mail transport outages.
    // Pending mail remains queued for the next successful worker run.
    $otp=[]; $staff=[]; $customer=[]; $mailFailed=false;
    if (email_enabled()) {
        try {
            // Standalone fallback must also deliver login verification codes.
            $otp = process_otp_dispatch_queue(25);
            $staff = function_exists('iworks_process_notification_email_outbox')
                ? iworks_process_notification_email_outbox(20) : [];
            $customer = iw_chat_process_email_outbox(20);
        } catch (Throwable $emailError) {
            $mailFailed=true;
            error_log('[iWORKS chat email] '.get_class($emailError));
        }
    } else {
        $mailFailed=true;
        error_log('[iWORKS chat email] Transport disabled; retry after configuration.');
    }
    $statement = db_query("DELETE FROM iw_live_threads
         WHERE updated_at < DATE_SUB(NOW(),INTERVAL 30 DAY)
         ORDER BY updated_at ASC LIMIT 150");
    echo 'OTP deliveries: '.json_encode($otp,JSON_UNESCAPED_SLASHES)."\n";
    echo 'Staff notifications: '.json_encode($staff,JSON_UNESCAPED_SLASHES)."\n";
    echo 'Visitor messages: '.json_encode($customer,JSON_UNESCAPED_SLASHES)."\n";
    echo 'Expired conversations deleted: '.$statement->rowCount()."\n";
    echo "DONE: old messages and their associated queued mail are removed by FK cascade.\n";
    if ($mailFailed) {
        fwrite(STDERR,"Transactional email delivery is not ready; pending messages remain queued.\n");
        exit(1);
    }
} catch (Throwable $e) {
    error_log('[iWORKS support chat worker] '.get_class($e));
    fwrite(STDERR,"Support worker failed; check private PHP logs and email configuration.\n");
    exit(1);
}
