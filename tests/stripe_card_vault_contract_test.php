<?php
declare(strict_types=1);
function vault_assert(bool $value, string $message): void
{
    if (!$value) { throw new RuntimeException($message); }
}
require dirname(__DIR__) . '/app/stripe_card_vault.php';

vault_assert(stripe_vault_valid_id('cus_PfK1234567890', 'cus_'), 'Stripe customer ID should be accepted.');
vault_assert(stripe_vault_valid_id('pm_1234567890abcdefgh', 'pm_'), 'Stripe payment method ID should be accepted.');
vault_assert(stripe_vault_valid_id('cs_test_a1t123456789', 'cs_'), 'Stripe setup checkout ID should be accepted.');
vault_assert(!stripe_vault_valid_id('../../other', 'cus_'), 'Path traversal must be invalid.');
vault_assert(!stripe_vault_valid_id('https://example.com', 'pm_'), 'URL must be invalid.');
vault_assert(!stripe_vault_valid_id('pm_1234567890abcdefgh', 'cus_'), 'Prefix must match type.');

$customerId = 'cus_PfK1234567890';
$owned = ['id'=>'pm_1234567890abcdefgh', 'customer'=>$customerId, 'type'=>'card'];
vault_assert(stripe_vault_owns_payment_method($owned, $customerId), 'A saved card belonging to client is accepted.');
vault_assert(!stripe_vault_owns_payment_method($owned, 'cus_otherAccount123'), 'Another Stripe customer must not own this card.');
$bank = $owned; $bank['type'] = 'us_bank_account';
vault_assert(!stripe_vault_owns_payment_method($bank, $customerId), 'Only card methods can be removed in vault.');

$session = [
    'mode'=>'setup','status'=>'complete','customer'=>$customerId,
    'client_reference_id'=>'iworks-client-17',
    'metadata'=>['iworks_user_id'=>'17'],
];
vault_assert(stripe_vault_session_valid_for_customer($session, $customerId, 17), 'Client setup session must match owner.');
vault_assert(!stripe_vault_session_valid_for_customer($session, $customerId, 19), 'Another client cannot claim setup.');
$unpaid = $session; $unpaid['status'] = 'open';
vault_assert(!stripe_vault_session_valid_for_customer($unpaid, $customerId, 17), 'Incomplete setup must not be accepted.');
$wrongCustomer = $session; $wrongCustomer['customer'] = 'cus_PfK4444444444';
vault_assert(!stripe_vault_session_valid_for_customer($wrongCustomer, $customerId, 17), 'Wrong customer must be rejected.');

$root = dirname(__DIR__);
$router = file_get_contents($root . '/index.php');
$checkout = file_get_contents($root . '/app/domain.php');
$bill = file_get_contents($root . '/views/client-billing.php');
$vault = file_get_contents($root . '/views/client-card-vault.php');
$head = file_get_contents($root . '/views/partials/client-header.php');
$bootstrap = file_get_contents($root . '/app/bootstrap.php');
vault_assert(str_contains($router, "case 'card_vault':"), 'Saved card route missing.');
vault_assert(str_contains($router, 'require_roles([\'client\'])'), 'Customer role gate missing.');
vault_assert(str_contains($router, 'require_csrf();'), 'POST routes must enforce CSRF.');
vault_assert(str_contains($router, 'stripe_vault_confirm_setup'), 'Stripe-return verification missing.');
vault_assert(str_contains($checkout, "saved_payment_method_options[payment_method_remove]"), 'Existing USD Stripe checkout must reuse saved cards.');
vault_assert(str_contains($checkout, "stripe_vault_customer_id"), 'Stripe checkout must use mapped customer when available.');
vault_assert(str_contains($vault, 'save_card_consent'), 'Explicit save-card consent missing.');
vault_assert(str_contains($vault, 'payment_method_id'), 'Client card deletion missing.');
vault_assert(!str_contains($vault, 'name="card_number"') && !str_contains($vault, 'name="cvv"'), 'Raw card inputs must never be present.');
vault_assert(str_contains($bill, "url('card_vault')"), 'Billing must link to card vault.');
vault_assert(str_contains($bill, 'Payment methods') && str_contains($bill, 'stripe_vault_remove_card') === false,
    'Billing must display masked saved payment methods without exposing Stripe secret operations.');
vault_assert(str_contains($head, 'Billing') && !str_contains($head, 'Saved cards'),
    'Saved cards should be managed inside Billing without a separate navigation page.');
vault_assert(str_contains($bootstrap, "require_once __DIR__ . '/stripe_card_vault.php'"), 'Vault service not loaded.');

echo "stripe_card_vault_contract_test passed\n";
