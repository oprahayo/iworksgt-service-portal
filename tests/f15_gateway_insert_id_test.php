<?php
declare(strict_types=1);
/**
 * Run the actual gateway record creator against an isolated DB boundary.
 * Emulate a driver where lastInsertId changes after a later job operation.
 */
$GLOBALS['db_last_insert'] = 0;
$GLOBALS['payment_writes'] = [];
function db_execute(string $sql, array $params = []): bool
{
    $GLOBALS['payment_writes'][] = ['sql' => $sql, 'params' => $params];
    if (str_contains($sql, 'INSERT INTO job_payments')) {
        $GLOBALS['db_last_insert'] = 204;
    } else {
        $GLOBALS['db_last_insert'] = 0; // Reproduced incident after auxiliary update.
    }
    return true;
}
function db_last_id(): int { return (int) $GLOBALS['db_last_insert']; }
function now(): string { return '2026-11-13 12:00:00'; }
function default_currency(): string { return 'NGN'; }
function normalize_payment_currency(string $v): string { return strtoupper($v); }
function fx_ngn_per_usd(): float { return 1500.0; }
require __DIR__ . '/../app/domain.php';
$invoice = [
    'id' => 16, 'job_id' => 102, 'client_id' => 9,
    'amount' => 17500.00, 'currency' => 'NGN',
    'pay_currency' => 'NGN', 'pay_method' => 'card',
    'fx_rate_used' => 1500.00, 'rate_locked_at' => '2026-11-13 12:00:00',
];
$created = create_gateway_payment_record($invoice, 9, 3, 'paystack', 'test-unique-ref');
if ($created !== 204 || $GLOBALS['db_last_insert'] !== 0) {
    throw new RuntimeException('Payment creator returned wrong ID after job update.');
}
if (count($GLOBALS['payment_writes']) !== 2
    || !str_contains($GLOBALS['payment_writes'][0]['sql'], 'INSERT INTO job_payments')
    || !str_contains($GLOBALS['payment_writes'][1]['sql'], 'UPDATE jobs')) {
    throw new RuntimeException('Original creator must persist gateway and unpaid job state.');
}
$submitted = $GLOBALS['payment_writes'][0]['params'];
if (($submitted['invoice_id'] ?? null) !== 16
    || ($submitted['client_id'] ?? null) !== 9
    || ($submitted['amount'] ?? null) !== 17500.00) {
    throw new RuntimeException('Payment record must retain frozen invoice values.');
}
echo "f15_gateway_insert_id_test passed\n";
