<?php
declare(strict_types=1);
/**
 * Explicit, private CLI live payout switch. No transfer is sent by this script.
 *
 *  php scripts/configure_agent_paystack.php --check
 *  IWORKS_PAYSTACK_LIVE_KEY=... php scripts/configure_agent_paystack.php --activate-live
 *  php scripts/configure_agent_paystack.php --disable
 *
 * Do not pass keys in shell arguments or commit the runtime file.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$action = $argv[1] ?? '--check';
if (!in_array($action, ['--check', '--activate-live', '--disable'], true)) {
    fwrite(STDERR, "Use --check, --activate-live, or --disable\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';

function iworks_payout_schema_ready(): bool
{
    foreach (['agent_payout_beneficiaries','agent_withdrawal_transfers'] as $table) {
        if (!schema_table_exists($table) || !schema_column_exists($table, 'environment')) {
            return false;
        }
    }
    return schema_column_exists('agent_withdrawal_transfers', 'request_key')
        && schema_column_exists('agent_withdrawal_transfers', 'last_checked_at');
}
function iworks_payout_write_runtime(array $config): void
{
    $path = __DIR__ . '/../app/agent_payout_runtime.php';
    if (is_link($path)) { throw new RuntimeException('Refusing a symlinked payout configuration.'); }
    $dir = dirname($path);
    $temp = tempnam($dir, '.payout-');
    if ($temp === false) { throw new RuntimeException('Private payout configuration not writable.'); }
    try {
        chmod($temp, 0600);
        $bytes = file_put_contents($temp, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX);
        if ($bytes === false) { throw new RuntimeException('Could not save private payout config.'); }
        if (!rename($temp, $path)) { throw new RuntimeException('Could not activate private payout config.'); }
        chmod($path, 0600);
    } finally {
        if (file_exists($temp)) { unlink($temp); }
    }
}

try {
    if ($action === '--disable') {
        $previous = iworks_agent_payout_config();
        $disabled = ['mode' => 'off'];
        $liveSecret = trim((string)($previous['live_secret_key'] ?? ''));
        if (preg_match('/^sk_live_[A-Za-z0-9_-]+$/', $liveSecret)) {
            // Keep validating terminal events for transfers already submitted.
            $disabled['live_secret_key'] = $liveSecret;
            $disabled['accept_live_webhooks'] = true;
        }
        foreach (['test_secret_key','test_webhook_secret','live_webhook_secret'] as $retained) {
            if (!empty($previous[$retained])) { $disabled[$retained] = $previous[$retained]; }
        }
        iworks_payout_write_runtime($disabled);
        echo "New LIVE payouts DISABLED; signed in-flight settlement notifications remain enabled.\n";
        exit(0);
    }
    if (!iworks_payout_schema_ready()) {
        throw new RuntimeException('Missing payout schema. Apply the backup-gated migration first.');
    }
    $testHistory = (int)(db_one(
        "SELECT COUNT(*) AS c FROM agent_withdrawal_transfers
         WHERE environment='test'"
    )['c'] ?? 0);
    if ($testHistory !== 0) {
        throw new RuntimeException('Existing TEST withdrawal history exists in this database. Finance must explicitly segregate it before LIVE activation.');
    }
    $pendingUnknown = (int)(db_one(
        "SELECT COUNT(*) AS c FROM withdrawal_requests w
         LEFT JOIN agent_withdrawal_transfers t ON t.withdrawal_id=w.id
         WHERE w.status IN ('pending','approved') AND t.withdrawal_id IS NULL"
    )['c'] ?? 0);
    if ($pendingUnknown !== 0) {
        throw new RuntimeException('Legacy pending withdrawals need Finance reconciliation before enabling LIVE.');
    }

    if ($action === '--check') {
        echo "Payout schema: ready\n";
        echo "Paystack environment: " . strtoupper(iworks_agent_payout_mode()) . "\n";
        echo "Agent payout policy: min NGN 5,000; automatic <= NGN 100,000; manual review above.\n";
        echo "Test transfer records / unsettled legacy withdrawals: none\n";
        echo "Live activation requires a Registered Paystack Business, live webhook, funding, and transfer approvals configured.\n";
        exit(0);
    }

    $key = trim((string) getenv('IWORKS_PAYSTACK_LIVE_KEY'));
    if ($key === '' && in_array('--use-existing-checkout-key', $argv, true)) {
        $method = get_payment_method_by_code('paystack');
        $key = trim((string)($method['secret_key'] ?? ''));
    }
    if (!preg_match('/^sk_live_[A-Za-z0-9_-]+$/', $key)) {
        throw new RuntimeException('Missing live Paystack key in private environment input.');
    }
    $approval = (string) getenv('IWORKS_PAYSTACK_LIVE_APPROVAL');
    if ($approval !== 'I_HAVE_VERIFIED_REGISTERED_BUSINESS_WEBHOOK_AND_TRANSFER_APPROVALS') {
        throw new RuntimeException('Account readiness confirmation is required.');
    }

    // Read-only API call to validate the credential. Never send money here.
    $balance = gateway_json_get('https://api.paystack.co/balance', $key);
    if (($balance['status'] ?? false) !== true || !is_array($balance['data'] ?? null)) {
        throw new RuntimeException('Live Paystack balance query was unsuccessful.');
    }
    $hasNgn = false;
    foreach ($balance['data'] as $currencyBalance) {
        if (($currencyBalance['currency'] ?? '') === 'NGN') {
            $hasNgn = (int)($currencyBalance['balance'] ?? 0) >= iworks_agent_payout_policy()['minimum_kobo'];
            break;
        }
    }
    if (!$hasNgn) {
        throw new RuntimeException('Paystack NGN balance is below the minimum withdrawal. Fund payouts and fees first.');
    }

    $config = iworks_agent_payout_config();
    $method = get_payment_method_by_code('paystack');
    $signingSecret = trim((string)(getenv('IWORKS_PAYSTACK_LIVE_WEBHOOK_SECRET') ?: ($config['live_webhook_secret'] ?? $method['private_key'] ?? '')));
    if ($signingSecret !== '' && !str_starts_with($signingSecret, 'whsec_')) {
        throw new RuntimeException('Invalid webhook signing secret configuration.');
    }
    iworks_payout_write_runtime(array_merge($config, [
        'mode' => 'live',
        'live_enabled' => 'I_ACCEPT_REAL_MONEY_PAYOUTS',
        'live_secret_key' => $key,
        'live_webhook_secret' => $signingSecret,
    ]));
    // The process did not transfer money or run any queued payouts.
    echo "LIVE payout configuration saved. No transfer initiated.\n";
    echo "New verified LIVE beneficiaries have a 24-hour security hold.\n";
    echo "Monitor transfer webhooks and Finance reconciliation before accepting agent withdrawals.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'BLOCKED: ' . $e->getMessage() . "\n");
    exit(1);
}
