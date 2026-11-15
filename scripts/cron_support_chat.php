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
    if ($mode === '--check') {
        echo "READ ONLY: no messages sent or deleted.\n";
        exit(0);
    }
    if (!email_enabled()) {
        throw new RuntimeException('Transactional email transport is not enabled.');
    }
    // Existing in-app notification emails deliver to authorized admin/agent
    // accounts; chat outbox delivers replies and closure receipts to visitors.
    $staff = function_exists('iworks_process_notification_email_outbox')
        ? iworks_process_notification_email_outbox(20) : [];
    $customer = iw_chat_process_email_outbox(20);
    $statement = db_query("DELETE FROM iw_live_threads
         WHERE updated_at < DATE_SUB(NOW(),INTERVAL 30 DAY)
         ORDER BY updated_at ASC LIMIT 150");
    echo 'Staff notifications: '.json_encode($staff,JSON_UNESCAPED_SLASHES)."\n";
    echo 'Visitor messages: '.json_encode($customer,JSON_UNESCAPED_SLASHES)."\n";
    echo 'Expired conversations deleted: '.$statement->rowCount()."\n";
    echo "DONE: older messages and their queued mail are removed by FK cascade.\n";
} catch (Throwable $e) {
    error_log('[iWORKS support chat worker] '.get_class($e));
    fwrite(STDERR,"Support worker failed; check private PHP logs and email configuration.\n");
    exit(1);
}
