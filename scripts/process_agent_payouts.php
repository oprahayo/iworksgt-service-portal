<?php
declare(strict_types=1);

// Recovery worker: only queued, never-attempted transfers may be submitted.
// Ambiguous/submitted transfers use GET verification only, including when OFF.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$action = $argv[1] ?? '--check';
if (!in_array($action, ['--check','--run'], true)) { exit(2); }
require __DIR__ . '/../app/bootstrap.php';
if (!schema_table_exists('agent_withdrawal_transfers')) {
    fwrite(STDERR, "Payout migration required.\n"); exit(1);
}
$rows = db_all("SELECT withdrawal_id, state, environment FROM agent_withdrawal_transfers
    WHERE (state='queued' OR state IN ('submitting','pending','exception')
        OR (state='success' AND settled_at >= :recent))
      AND (last_checked_at IS NULL OR last_checked_at < :cutoff)
    ORDER BY COALESCE(last_checked_at, created_at) ASC LIMIT 20",
    ['recent'=>date('Y-m-d H:i:s',time()-7*86400),'cutoff'=>date('Y-m-d H:i:s',time()-300)]);
$errors = 0;
foreach ($rows as $row) {
    $id = (int)$row['withdrawal_id'];
    if ($action === '--check') { echo "Withdrawal #{$id}: {$row['state']} ({$row['environment']})\n"; continue; }
    try {
        // Throttle both successful and failed checks; do not strand later rows.
        db_execute('UPDATE agent_withdrawal_transfers SET last_checked_at=:at WHERE withdrawal_id=:id',
            ['at'=>now(),'id'=>$id]);
        if ($row['state'] === 'queued') {
            if (iworks_agent_payout_mode() === $row['environment']) { iworks_agent_submit_transfer($id); }
        } else {
            iworks_agent_reconcile_transfer($id);
        }
        echo "Withdrawal #{$id}: checked\n";
    } catch (Throwable $e) {
        $errors++;
        // Never print API payloads, keys or beneficiary details to cron logs.
        fwrite(STDERR, "Withdrawal #{$id}: Finance reconciliation required\n");
    }
}
echo $action === '--check' ? "Read only; no gateway calls or database changes.\n" : "Payout recovery cycle complete.\n";
exit($errors ? 1 : 0);
