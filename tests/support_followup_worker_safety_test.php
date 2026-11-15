<?php
declare(strict_types=1);
/**
 * Isolated, no database or outbound mail: verify OTP attempts are preserved
 * while transport is disabled and both scheduled entrypoints share a lock.
 */
$GLOBALS['mailOn'] = false;
$GLOBALS['otpSelects'] = 0;
function email_enabled(): bool { return $GLOBALS['mailOn']; }
function now(): string { return '2026-11-15 08:00:00'; }
function db_one(string $sql, array $params=[]): ?array {
    return str_contains($sql,'information_schema.tables') ? ['c'=>1] : null;
}
function db_all(string $sql, array $params=[]): array {
    $GLOBALS['otpSelects']++;
    return [];
}
require __DIR__.'/../app/auth.php';
$summary=process_otp_dispatch_queue(5);
if ($summary !== ['processed'=>0,'sent'=>0,'failed'=>0] || $GLOBALS['otpSelects'] !== 0) {
    throw new RuntimeException('Disabled email must not claim or exhaust queued OTP attempts.');
}
$GLOBALS['mailOn'] = true;
process_otp_dispatch_queue(5);
if ($GLOBALS['otpSelects'] !== 1) {
    throw new RuntimeException('Enabled transport should resume normal OTP queue polling.');
}
$main=(string) file_get_contents(__DIR__.'/../scripts/run_scheduled_operations.php');
$fallback=(string) file_get_contents(__DIR__.'/../scripts/cron_support_chat.php');
// The primary worker builds the filename dynamically from task='queue';
// the fallback uses the equivalent literal filename.
if (!str_contains($main, "'/cron-' . " . '$task' . " . '.lock'")) {
    throw new RuntimeException('Main scheduler must retain the queue-specific private lock.');
}
if (!str_contains($fallback,'/cron-queue.lock')) {
    throw new RuntimeException('Fallback must reuse the main queue lock.');
}
foreach ([$main,$fallback] as $worker) {
    if (!str_contains($worker,'LOCK_EX | LOCK_NB')
        || !str_contains($worker,'process_otp_dispatch_queue(')
        || !str_contains($worker,'iworks_process_notification_email_outbox(')
        || !str_contains($worker,'iw_chat_process_email_outbox(')
        || !str_contains($worker,'DELETE FROM iw_live_threads')) {
        throw new RuntimeException('Both queue workers must coordinate and process all channels and retention.');
    }
}
if (!str_contains($main,"'degraded'") || !str_contains($fallback,'OTP dispatch queue ready:')) {
    throw new RuntimeException('Disabled mail or absent OTP queue must not report healthy readiness.');
}
echo "support_followup_worker_safety_test passed\n";
