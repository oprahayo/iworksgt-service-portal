<?php
declare(strict_types=1);

/**
 * cPanel scheduled worker. CLI only, one operation per invocation.
 *
 * php scripts/run_scheduled_operations.php queue
 * php scripts/run_scheduled_operations.php rates
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$task = (string) ($argv[1] ?? '');
if (!in_array($task, ['queue', 'rates'], true)) {
    fwrite(STDERR, "Usage: php scripts/run_scheduled_operations.php queue|rates\n");
    exit(2);
}

require __DIR__ . '/../app/bootstrap.php';
$privateDir = dirname(mail_log_path());
$lockFile = $privateDir . '/cron-' . $task . '.lock';
$lock = @fopen($lockFile, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Worker already running or private lock unavailable.\n";
    exit($lock ? 0 : 1);
}
@chmod($lockFile, 0600);
$started = microtime(true);
$metricPath = $privateDir . '/cron-' . $task . '-status.json';
$previous = is_file($metricPath) ? json_decode((string) @file_get_contents($metricPath), true) : null;
$previous = is_array($previous) ? $previous : [];
$metric = [
    'task'=>$task,
    'last_started_at'=>gmdate('c'),
    'last_success_at'=>(string) ($previous['last_success_at'] ?? ''),
    'status'=>'failed',
    'processed'=>0,
    'sent'=>0,
    'failed'=>0,
    'pending'=>0,
    'duration_ms'=>0,
];
$exitCode = 0;
try {
    if ($task === 'queue') {
        $result = process_otp_dispatch_queue(25);
        $metric['processed'] = (int) ($result['processed'] ?? 0);
        $metric['sent'] = (int) ($result['sent'] ?? 0);
        $metric['failed'] = (int) ($result['failed'] ?? 0);
        $pending = db_one(
            "SELECT COUNT(*) AS n FROM otp_dispatch_queue
             WHERE status IN ('queued','retrying','processing')"
        );
        $metric['pending'] = (int) ($pending['n'] ?? 0);
        $metric['status'] = 'ok';
    } else {
        $result = refresh_crypto_rates_cache(true);
        $metric['source'] = (string) ($result['source'] ?? 'unavailable');
        $metric['rate_updated_at'] = (string) ($result['last_updated_at'] ?? '');
        $metric['status'] = $metric['source'] === 'live' ? 'ok' : 'stale';
        if ($metric['status'] === 'stale') {
            $exitCode = 1;
        }
    }
    if ($metric['status'] === 'ok') {
        $metric['last_success_at'] = gmdate('c');
    }
} catch (Throwable $e) {
    // Never include provider error details, OTP codes or customer records.
    $metric['status'] = 'failed';
    error_log('iWorks scheduled task error: ' . $task . ' / ' . get_class($e));
    $exitCode = 1;
} finally {
    $metric['duration_ms'] = (int) round((microtime(true) - $started) * 1000);
    $encoded = json_encode($metric, JSON_UNESCAPED_SLASHES);
    $old = umask(0077);
    try {
        $temp = tempnam($privateDir, '.cron-status-');
        if ($temp !== false) {
            @file_put_contents($temp, $encoded . "\n", LOCK_EX);
            @chmod($temp, 0600);
            @rename($temp, $metricPath);
        } else {
            $exitCode = 1;
        }
    } finally {
        umask($old);
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
echo $task . ' status=' . $metric['status']
    . ' processed=' . $metric['processed']
    . ' failed=' . $metric['failed']
    . ' pending=' . $metric['pending'] . "\n";
exit($exitCode);
