<?php
declare(strict_types=1);

require __DIR__ . '/../app/agent_withdrawals.php';

function payout_assert(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}

function payout_rejects(callable $operation, string $message): void
{
    try { $operation(); }
    catch (RuntimeException $e) { return; }
    throw new RuntimeException($message);
}

$policy = iworks_agent_payout_policy();
payout_assert($policy['minimum_kobo'] === 500000, 'Minimum must be exactly NGN 5,000.');
payout_assert($policy['review_above_kobo'] === 10000000, 'Review threshold must be above NGN 100,000.');
payout_assert($policy['currency'] === 'NGN', 'Payout currency must be NGN.');
payout_assert(iworks_agent_payout_kobo(5000.00) === 500000, 'NGN to kobo conversion failed.');
payout_assert(iworks_agent_payout_kobo(100000.00) === 10000000, 'Boundary amount conversion failed.');
payout_assert(!iworks_agent_payout_review_required(500000,0), 'Minimum should be auto eligible.');
payout_assert(!iworks_agent_payout_review_required(10000000,0), '100k should be auto eligible.');
payout_assert(iworks_agent_payout_review_required(10000001,0), 'Above 100k must be reviewed.');
payout_assert(iworks_agent_payout_review_required(5000000,6000000), 'Split requests must require review.');
payout_assert(iworks_agent_payout_kobo(100000.01) > $policy['review_above_kobo'], 'Manual review boundary failed.');
payout_rejects(static fn() => iworks_agent_payout_kobo(4999.999), 'Fractional kobo must be rejected.');
payout_rejects(static fn() => iworks_agent_payout_kobo(-5), 'Negative amount must be rejected.');
payout_rejects(static fn() => iworks_agent_payout_kobo(INF), 'Infinite amount must be rejected.');

putenv('IWORKS_PAYSTACK_PAYOUT_MODE=live');
putenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET=sk_test_placeholder');
payout_assert(iworks_agent_payout_mode() === 'off', 'Live mode without private double-authorization must remain OFF.');
payout_rejects(static fn() => iworks_agent_payout_secret(), 'Live mode without private approval must remain blocked.');

putenv('IWORKS_PAYSTACK_PAYOUT_MODE=test');
putenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET=sk_live_bad');
payout_rejects(static fn() => iworks_agent_payout_secret(), 'Live key must never be used for payouts.');
putenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET=sk_test_placeholder');
payout_assert(iworks_agent_payout_secret() === 'sk_test_placeholder', 'Test key should be allowed in test mode.');

putenv('IWORKS_PAYSTACK_PAYOUT_MODE=off');
putenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET');
payout_assert(!iworks_agent_payout_enabled(), 'Payouts must default to disabled.');
payout_assert(iworks_agent_payout_mode() === 'off', 'Payouts must remain off by default.');
payout_assert(is_file(__DIR__ . '/../scripts/configure_agent_paystack.php'), 'Live configuration CLI must exist.');

$domain = file_get_contents(__DIR__ . '/../app/domain.php');
$controller = file_get_contents(__DIR__ . '/../index.php');
$schema = file_get_contents(__DIR__ . '/../database.sql');
payout_assert(str_contains($domain, 'iworks_agent_handle_transfer_webhook('), 'Webhook handler must be wired.');
payout_assert(str_contains($domain, 'iworks_agent_create_withdrawal('), 'Withdrawal creation must be wired.');
payout_assert(str_contains($controller, 'iworks_agent_verify_payout_bank('), 'Bank verification route missing.');
payout_assert(str_contains($schema, 'UNIQUE'), 'Transfer reference uniqueness must be enforced.');
payout_assert(str_contains($schema, 'agent_withdrawal_transfers'), 'Transfer ledger table missing.');
payout_assert(str_contains($schema, "environment ENUM('test','live')"), 'Environment-isolated transfer records required.');
payout_assert(str_contains(file_get_contents(__DIR__ . '/../app/agent_withdrawals.php'),
    'I_ACCEPT_REAL_MONEY_PAYOUTS'), 'Live mode must have explicit private activation.');
payout_assert(str_contains(file_get_contents(__DIR__ . '/../app/agent_withdrawals.php'),
    'iworks_agent_payout_hold_seconds'), 'Live bank change hold missing.');

echo "Agent payout policy, live activation guard, and integration contracts PASS.\n";
