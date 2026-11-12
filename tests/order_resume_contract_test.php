<?php
declare(strict_types=1);
function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}
function get_service_by_slug(string $slug): ?array
{
    return match ($slug) {
        'business-name-registration-cac-nigeria' => ['id' => 7, 'slug' => $slug, 'name' => 'Business Name Registration (CAC)'],
        'company-registration' => ['id'=>8,'slug'=>$slug,'name'=>'Company Registration'],
        'website-design-development' => ['id' => 12, 'slug' => $slug, 'name' => 'Website Design & Development'],
        default => null,
    };
}
function check_order(bool $test, string $reason): void
{
    if (!$test) { throw new RuntimeException($reason); }
}
require dirname(__DIR__) . '/app/order_intent.php';
$_SESSION = [];
$slug = 'business-name-registration-cac-nigeria';
check_order(iworks_order_slug($slug) === $slug, 'Published service slug valid.');
check_order(iworks_order_slug('../dashboard') === '', 'No traversal or free-form redirect.');
check_order(iworks_order_slug('https://evil.test') === '', 'No open redirect.');
check_order(iworks_order_slug(['service'=>'a']) === '', 'Only scalar strings supported.');
check_order(iworks_post_login_destination(['role'=>'client']) === 'index.php?page=dashboard', 'Direct login must still use dashboard.');
iworks_order_intent_capture(['service'=>$slug]);
check_order(iworks_order_intent_peek() === $slug, 'Pending order should be held in server session.');
check_order(iworks_post_login_destination(['role'=>'client']) === 'index.php?page=cac&kind=business_name', 'Google/password/OTP must resume selected CAC Business Name intake.');
iworks_order_intent_capture(['service'=>'company-registration']);
check_order(iworks_post_login_destination(['role'=>'client']) === 'index.php?page=cac&kind=company', 'Company registration must resume its dedicated intake.');
iworks_order_intent_capture(['service'=>$slug]);
check_order(iworks_post_login_destination(['role'=>'admin']) === 'index.php?page=dashboard', 'Admin should not be placed into client checkout.');
$selected = iworks_order_catalog_selection($slug, [['id'=>7],['id'=>12]]);
check_order($selected !== null && $selected['id'] === 7, 'Checkout catalog must resolve the selected service.');
check_order(iworks_order_catalog_selection($slug, [['id'=>12]]) === null, 'Unavailable catalog service must not be selected.');
iworks_order_intent_capture(['service'=>'private-hidden-service']);
check_order(iworks_order_intent_peek() === '', 'Unknown/private service must clear intent.');
iworks_order_intent_capture(['service'=>$slug]);
$_SESSION['iworks_pending_order']['at'] = time() - 3605;
check_order(iworks_order_intent_peek() === '', 'Stale order intent must expire.');
iworks_order_intent_capture(['service'=>$slug]);
iworks_order_intent_clear();
check_order(iworks_order_intent_peek() === '', 'Order intent cleared after successful invoice checkout.');

$root = dirname(__DIR__);
$index = file_get_contents($root . '/index.php');
$service = file_get_contents($root . '/views/public-service-detail.php');
$jobs = file_get_contents($root . '/views/jobs.php');
$bootstrap = file_get_contents($root . '/app/bootstrap.php');
check_order(str_contains($bootstrap, "require_once __DIR__ . '/order_intent.php'"), 'Order intent must be loaded on every route.');
check_order(str_contains($index, "iworks_order_intent_capture(\$_GET)"), 'Portal login must persist selected service.');
check_order(str_contains($index, "case 'google_callback':") && str_contains($index, 'redirect(iworks_post_login_destination(current_user()))'), 'Google callback must resume selected service.');
check_order(str_contains($index, "case 'otp':") && str_contains($index, "case 'register':"), 'OTP and registration paths must exist.');
check_order(str_contains($service, "portal_url('login', ['service'"), 'Public order button must carry selected service.');
check_order(str_contains($index, 'iworks_order_catalog_selection('), 'Checkout must validate service against catalog.');
check_order(str_contains($jobs, 'preselectedOrder') && str_contains($jobs, 'checked'), 'Checkout must preselect retained service.');
check_order(str_contains($index, 'iworks_order_intent_clear();'), 'Successful checkout must clear intent.');
check_order(str_contains($index, 'redirect($redirectInvoice);'), 'Successful order must proceed to payment invoice.');
echo "order_resume_contract_test passed\n";
