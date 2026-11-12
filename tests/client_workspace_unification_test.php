<?php
declare(strict_types=1);
function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page'=>$page] + $params);
}
function check_client(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
require dirname(__DIR__) . '/app/client_workspace.php';

$trademarks = [
    ['id'=>1,'job_id'=>88,'status'=>'awaiting_payment','payment_status'=>'unpaid',
        'job_status'=>'pending','data'=>['mark_name'=>'Test Brand'],'updated_at'=>'2026-11-12 18:56:00'],
    ['id'=>2,'job_id'=>null,'status'=>'draft','data'=>['mark_name'=>'New Draft'],'updated_at'=>'2026-11-12 19:10:00'],
    ['id'=>3,'job_id'=>90,'status'=>'awaiting_payment','payment_status'=>'paid',
        'job_status'=>'closed','data'=>['mark_name'=>'Completed Mark'],'updated_at'=>'2026-11-12 18:00:00'],
];
$jobs = [
    ['id'=>88,'title'=>'Test Brand Trademark','service_name'=>'Trademark','status'=>'pending',
        'payment_status'=>'unpaid','job_code'=>'JOB-TM-1','updated_at'=>'2026-11-12 18:56:00'],
    ['id'=>89,'title'=>'Client website design','service_name'=>'Website Development','status'=>'in_progress',
        'payment_status'=>'paid','job_code'=>'JOB-WEB-2','updated_at'=>'2026-11-12 17:00:00'],
    ['id'=>90,'title'=>'Completed Mark','service_name'=>'Trademark','status'=>'closed',
        'payment_status'=>'paid','job_code'=>'JOB-TM-3','updated_at'=>'2026-11-12 18:00:00'],
];
$items = iworks_client_service_items($trademarks, $jobs);
check_client(count($items) === 4, 'Trademark-linked jobs must be deduplicated, website order still shown.');
check_client(count(array_unique(array_column($items, 'key'))) === 4, 'Each customer item must be unique.');
$byKey = [];
foreach ($items as $i) { $byKey[$i['key']]=$i; }
check_client(!isset($byKey['job:88']) && !isset($byKey['job:90']), 'Linked trademark jobs should not appear again.');
check_client(($byKey['trademark:1']['status'] ?? '') === 'payment', 'Unpaid trademark appears in Awaiting payment.');
check_client(($byKey['trademark:2']['status'] ?? '') === 'draft', 'Unpaid draft remains editable.');
check_client(($byKey['trademark:2']['can_delete_draft'] ?? false), 'Draft can be deleted.');
check_client(($byKey['trademark:3']['status'] ?? '') === 'completed', 'Completed service appears once.');
check_client(($byKey['job:89']['status'] ?? '') === 'progress', 'Website work is also tracked in My Services.');
check_client(str_contains($byKey['job:89']['detail_url'], 'page=job'), 'Website requests keep job detail access.');
check_client($items[0]['key'] === 'trademark:2', 'Recent items should be listed first.');

check_client(iworks_client_state('unpaid','pending') === 'payment', 'Unpaid order requires payment.');
check_client(iworks_client_state('paid','in_progress') === 'progress', 'Paid order is in progress.');
check_client(iworks_client_state('paid','delivered') === 'completed', 'Delivered order is completed.');
check_client(iworks_client_state('unpaid','pending',true) === 'draft', 'Draft state takes precedence.');
check_client(iworks_client_state('paid','cancelled') === 'cancelled', 'Cancelled order remains visible.');
check_client(iworks_client_state_label('payment') === 'Awaiting payment', 'Readable payment label required.');

$catalog = iworks_client_catalogue([
    ['slug'=>'trademark','name'=>'Trademark','category'=>'legal','price'=>185],
    ['slug'=>'website-design-development','name'=>'Website Design','category'=>'digital','price'=>120],
    ['slug'=>'company-registration','name'=>'Company Registration','category'=>'legal','price'=>120],
    ['slug'=>'banking-service','name'=>'Business Banking','category'=>'finance','price'=>300],
    ['slug'=>'custom-service','name'=>'Custom Service','price'=>0],
]);
check_client(count($catalog) === 3, 'Customer catalogue excludes banking and unsupported empty custom service.');
check_client($catalog[0]['slug'] === 'company-registration', 'Core registration appears first.');
check_client(iworks_client_service_icon(['name'=>'Trademark']) === 'bi-patch-check', 'Trademark has tailored icon.');

$apiError = 'Expired API Key provided: sk_live_**********************gkpZ7v';
$message = iworks_client_safe_payment_error($apiError);
check_client(!str_contains($message, 'sk_live') && !str_contains($message, 'gkpZ7v'), 'Never display even a masked Stripe credential.');
check_client(str_contains($message, 'payment provider'), 'A safe customer message should be useful.');

$root = dirname(__DIR__);
$nav = file_get_contents($root . '/views/partials/client-header.php');
$dash = file_get_contents($root . '/views/client-dashboard.php');
$apps = file_get_contents($root . '/views/client-applications.php');
$job = file_get_contents($root . '/views/client-job.php');
$profile = file_get_contents($root . '/views/client-profile.php');
$invoice = file_get_contents($root . '/invoice.php');
$router = file_get_contents($root . '/index.php');
$domain = file_get_contents($root . '/app/domain.php');
$trademark = file_get_contents($root . '/app/trademark_applications.php');
check_client(str_contains($nav, 'My services') && !str_contains($nav, 'My orders'), 'Client navigation must have one service history entry.');
check_client(str_contains($dash, 'clientServiceGrid') && str_contains($dash, 'iworks_client_service_icon'), 'Dashboard should use full published service catalogue.');
check_client(str_contains($apps, 'clientServiceItems') && str_contains($apps, 'Delete draft'), 'Consolidated list must keep draft deletion.');
check_client(str_contains($router, "require __DIR__ . '/views/client-job.php'"), 'Job details must use clean client view.');
check_client(str_contains($router, "require __DIR__ . '/views/client-profile.php'"), 'Profile must use clean client view.');
check_client(str_contains($job, 'add_comment') && str_contains($job, 'upload_file')
    && str_contains($job, 'accept_delivery') && str_contains($job, 'submit_order_review'), 'Customer order view must retain essential actions.');
check_client(str_contains($job, 'Older trademark intake record'), 'Legacy intake must remain accessible for review.');
check_client(str_contains($domain, 'skip_duplicate_intake'), 'New trademark wizard orders must not create duplicate intake.');
check_client(str_contains($trademark, "['skip_duplicate_intake' => true]"), 'Only trusted wizard checkout should skip duplicate intake.');
check_client(str_contains($invoice, 'iw-invoice-main-grid') && str_contains($invoice, 'iworks_client_safe_payment_error'), 'Invoice must have new checkout and safe errors.');
check_client(str_contains($profile, 'update_profile') && str_contains($profile, 'update_password') && str_contains($profile, 'update_pay_currency'), 'Client profile must preserve all write actions.');

echo "client_workspace_unification_test passed\n";
