<?php
declare(strict_types=1);
final class GatewayFakePdo extends PDO
{
    private bool $active = false;
    private array $snapshot = [];
    public function __construct() {}
    public function beginTransaction(): bool {
        $this->snapshot = [$GLOBALS['payment_fixture'],$GLOBALS['invoice_fixture'],$GLOBALS['job_fixture']];
        $this->active=true;
        return true;
    }
    public function commit(): bool { $this->active=false; return true; }
    public function rollBack(): bool {
        [$GLOBALS['payment_fixture'],$GLOBALS['invoice_fixture'],$GLOBALS['job_fixture']]=$this->snapshot;
        $this->active=false;
        return true;
    }
    public function inTransaction(): bool { return $this->active; }
}
$GLOBALS['pdo'] = new GatewayFakePdo();
$GLOBALS['payment_fixture'] = [
    'id'=>10,'invoice_id'=>20,'job_id'=>30,'client_id'=>40,
    'reference'=>'gateway-001','gateway_provider'=>'stripe',
    'status'=>'submitted','gateway_transaction_id'=>null,
];
$GLOBALS['invoice_fixture'] = ['id'=>20,'client_id'=>40,'payment_group'=>null,'status'=>'issued'];
$GLOBALS['job_fixture'] = ['id'=>30,'payment_status'=>'unpaid'];
$GLOBALS['invoiceFailure'] = true;
$GLOBALS['duplicateTx'] = false;
$GLOBALS['notificationWrites'] = 0;
function db(): PDO { return $GLOBALS['pdo']; }
function now(): string { return '2026-11-13 12:00:00'; }
function db_one(string $sql,array $params=[]): ?array
{
    if (str_contains($sql,'FROM job_payments WHERE id')) return $GLOBALS['payment_fixture'];
    if (str_contains($sql,'FROM invoices WHERE id')) return $GLOBALS['invoice_fixture'];
    if (str_contains($sql,'FROM jobs WHERE id')) return $GLOBALS['job_fixture'];
    if (str_contains($sql,'gateway_transaction_id = :tx')) {
        return $GLOBALS['duplicateTx'] ? ['id'=>99] : null;
    }
    return null;
}
function db_execute(string $sql,array $params=[]): bool
{
    if (str_contains($sql,"UPDATE jobs SET payment_status = 'paid'")) {
        $GLOBALS['job_fixture']['payment_status']='paid';
    } elseif (str_contains($sql,"UPDATE invoices SET status = 'paid'")) {
        if ($GLOBALS['invoiceFailure']) throw new RuntimeException('Synthetic invoice storage failure');
        $GLOBALS['invoice_fixture']['status']='paid';
    } elseif (str_contains($sql,'UPDATE job_payments')) {
        $GLOBALS['payment_fixture']['status']='confirmed';
    } elseif (str_contains($sql,'INSERT INTO notifications')) {
        $GLOBALS['notificationWrites']++;
    }
    return true;
}
require __DIR__ . '/../app/domain.php';
function assert_gateway(bool $condition,string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
try {
    mark_gateway_payment_confirmed(10,'pi_tx_100',['id'=>'cs_test']);
    throw new RuntimeException('Invoice write failure was not detected.');
} catch (RuntimeException $e) {
    assert_gateway(str_contains($e->getMessage(),'Synthetic invoice'), 'Expected injected DB failure.');
}
assert_gateway(!$GLOBALS['pdo']->inTransaction(), 'Failed payment update must roll back.');
assert_gateway($GLOBALS['payment_fixture']['status']==='submitted'
    && $GLOBALS['invoice_fixture']['status']==='issued'
    && $GLOBALS['job_fixture']['payment_status']==='unpaid',
    'Rollback must restore payment, invoice and job together.');
assert_gateway($GLOBALS['notificationWrites']===0, 'Rollback must not create notifications.');

$GLOBALS['invoiceFailure']=false;
$GLOBALS['duplicateTx']=true;
try {
    mark_gateway_payment_confirmed(10,'pi_tx_100',[]);
    throw new RuntimeException('Duplicate transaction accepted.');
} catch (RuntimeException $e) {
    assert_gateway(str_contains($e->getMessage(),'already associated'),
        'Cross-invoice gateway transaction duplication must be rejected.');
}
assert_gateway($GLOBALS['payment_fixture']['status']==='submitted',
    'Duplicate transaction must not alter payment.');

$GLOBALS['duplicateTx']=false;
$GLOBALS['payment_fixture']['status']='confirmed';
$GLOBALS['invoice_fixture']['status']='paid';
$GLOBALS['job_fixture']['payment_status']='paid';
mark_gateway_payment_confirmed(10,'pi_tx_100',[]);
mark_gateway_payment_rejected(10,'late failure',[]);
assert_gateway($GLOBALS['payment_fixture']['status']==='confirmed'
    && $GLOBALS['invoice_fixture']['status']==='paid'
    && $GLOBALS['job_fixture']['payment_status']==='paid',
    'Replay or delayed rejection must never downgrade a successful payment.');
assert_gateway($GLOBALS['notificationWrites']===0,
    'Repeated confirmed/failed callbacks must not send new notifications.');
echo "f09_payment_atomic_transition_test passed\n";
