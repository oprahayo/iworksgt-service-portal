<?php
declare(strict_types=1);
/**
 * Explicit TEST-only queue drain for requests committed while test mode was off.
 * This is NOT installed as a cron job and cannot use live Paystack credentials.
 *
 * php scripts/process_agent_payout_test_queue.php --check
 * php scripts/process_agent_payout_test_queue.php --run-test
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--run-test'], true)) {
    fwrite(STDERR, "Use --check or --run-test only.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';
if (!schema_table_exists('agent_withdrawal_transfers')) {
    fwrite(STDERR, "Paystack payout migration required first.\n");
    exit(1);
}
if ($mode === '--run-test') {
    try {
        iworks_agent_payout_secret(); // Explicitly rejects all live keys/modes.
    } catch (Throwable $e) {
        fwrite(STDERR, "Only Paystack TEST payout mode with a test key is allowed.\n");
        exit(1);
    }
}
$rows = db_all(
    "SELECT t.withdrawal_id, t.amount_kobo, w.status
     FROM agent_withdrawal_transfers t JOIN withdrawal_requests w ON w.id=t.withdrawal_id
     WHERE t.state='queued' AND w.status IN ('pending','approved')
     ORDER BY t.created_at ASC LIMIT 20"
);
foreach ($rows as $row) {
    $id = (int)$row['withdrawal_id'];
    $requiresReview = (int)$row['amount_kobo'] > iworks_agent_payout_policy()['review_above_kobo'];
    if ($requiresReview && $row['status'] !== 'approved') {
        continue;
    }
    if ($mode === '--check') {
        echo "Queued request #{$id} (no transfer attempted)\n";
        continue;
    }
    try {
        iworks_agent_submit_test_transfer($id);
        echo "Request #{$id} checked for TEST transfer; reconcile status in Finance.\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "Request #{$id} requires Finance follow-up.\n");
    }
}
if ($mode === '--check') {
    echo "CHECK ONLY: no API request or database mutation.\n";
}
