<?php
declare(strict_types=1);

/** No database required: verify the dashboard reads invoices and payments only. */
$root = dirname(__DIR__);
$role = 'admin';
$queries = [];
function has_role(array $roles): bool {
    global $role;
    return in_array($role, $roles, true);
}
function db_one(string $sql, array $params = []): ?array {
    global $queries;
    $queries[] = $sql;
    return ['orders'=>12,'orders_today'=>2,'confirmed'=>7,'pending'=>3];
}
function db_all(string $sql, array $params = []): array {
    global $queries;
    $queries[] = $sql;
    if (str_contains($sql,'GROUP BY DATE(')) {
        return [['day'=>date('Y-m-d'),'total'=>4]];
    }
    if (str_contains($sql,'GROUP BY COALESCE(')) {
        return [
            ['currency'=>'NGN','transactions'=>3,'total'=>'10000.00'],
            ['currency'=>'USD','transactions'=>4,'total'=>'100.00'],
        ];
    }
    if (str_contains($sql,'LIMIT 6')) {
        return [['invoice_number'=>'INV-2026-01','currency'=>'NGN','amount'=>'10000','method_name'=>'Paystack','status'=>'confirmed']];
    }
    throw new RuntimeException('Unexpected SQL statement');
}
require $root.'/app/transaction_dashboard.php';
$check = static function(bool $valid, string $message): void {
    if (!$valid) throw new RuntimeException($message);
};
$summary = iworks_transaction_dashboard_summary();
$check($summary['orders']===12 && $summary['orders_today']===2 &&
       $summary['confirmed']===7 && $summary['pending']===3,
       'Metric values must be returned from actual invoice and payment SQL.');
$check(count($summary['activity'])===7 && $summary['activity'][6]['total']===4,
       'Show exactly seven confirmed-payment activity dates, including zero-activity days.');
$check(count($summary['receipts'])===2 &&
       $summary['receipts'][0]['currency']==='NGN' &&
       $summary['receipts'][1]['currency']==='USD',
       'Do not add unlike currencies together.');
$check(count($summary['recent'])===1,'Recent payments must read from transaction records.');
foreach ($queries as $sql) {
    $check(!str_contains(strtolower($sql),'chat_') && !str_contains(strtolower($sql),'visitor'),
        'Transaction dashboard must not query live chat or visitors.');
}
$role = 'agent';
$blocked = false;
try { iworks_transaction_dashboard_summary(); } catch (RuntimeException $e) { $blocked=true; }
$check($blocked,'Agent must not access all customer financial activity.');
$dashboard = file_get_contents($root.'/views/dashboard.php');
$controller = file_get_contents($root.'/index.php');
$header = file_get_contents($root.'/views/partials/header.php');
$domain = file_get_contents($root.'/app/domain.php');
$template = file_get_contents($root.'/views/partials/transaction-overview.php');
$css = file_get_contents($root.'/assets/css/dashboard-transactions.css');
$deployer = file_get_contents($root.'/scripts/deploy_transaction_dashboard.sh');
$check(str_contains($dashboard,"partials/transaction-overview.php") &&
      !str_contains($dashboard,'partials/live-visitor-overview.php'),
      'Replace chat analytics panel, not simply rename it.');
$check(str_contains($controller,'iworks_transaction_dashboard_summary()') &&
      !str_contains($controller,'$liveOverview ='),
      'Controller must supply real transaction rows, not live chat data.');
$check(str_contains($header,'dashboard-transactions.css') &&
      !str_contains($header,'dashboard-live-visitors.css'),
      'Load only transaction styling on admin/finance dashboard.');
$check(!str_contains($domain,'Operations Guide') &&
      !str_contains($domain,'Monitor payments, assign agents') &&
      !str_contains($domain,"'revenue_paid' =>"),
      'Remove generic operations hint and invalid mixed-currency revenue.');
$check(str_contains($template,'Payments &amp; orders') &&
       str_contains($template,'Recent payments') &&
       str_contains($template,'Confirmed payment value by invoice currency') &&
       !str_contains($template,'Visitor conversations') &&
       !str_contains($template,'CUSTOMER EXPERIENCE') &&
       !str_contains($template,'Message activity'),
       'Financial UI must not show customer-experience or chat headings.');
$check(str_contains($css,'.iw-transactions-metrics') &&
       str_contains($css,'@media(max-width:740px)'),
       'Transaction analytics need readable responsive styling.');
$check(str_contains($deployer,'check_remediation_release.php') &&
       str_contains($deployer,'transaction_dashboard_contract_test.php') &&
       str_contains($deployer,'rollback()') &&
       str_contains($deployer,'--check|--apply'),
       'Live deployment must check source, backup and roll back.');
echo "transaction_dashboard_contract_test passed\n";
