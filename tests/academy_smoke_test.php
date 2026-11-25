<?php
declare(strict_types=1);
require __DIR__.'/../app/academy.php';
function academy_assert(bool $condition,string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
academy_assert(ACADEMY_FEE_MINOR===10200000,'Initial checkout amount');
academy_assert(academy_valid_name("Adeola Michael O'Connor"),'Normal name');
academy_assert(!academy_valid_name('<script>'),'Reject markup');
academy_assert(academy_checkout_url_valid('https://checkout.paystack.com/example'),'Valid checkout');
foreach(['http://checkout.paystack.com/example','https://checkout.paystack.com.evil.test/a','https://user@checkout.paystack.com/a','https://checkout.paystack.com:123/a'] as $url) academy_assert(!academy_checkout_url_valid($url),'Reject unsafe URL');
$entry=file_get_contents(__DIR__.'/../academy.php');
academy_assert(str_contains($entry,'Get Certificate')&&!str_contains($entry,'Preview certificate ·'),'Simple dashboard CTA');
academy_assert(str_contains($entry,'Pay Now')&&str_contains($entry,'number_format(ACADEMY_FEE_MINOR/100,2)'),'Checkout amount disclosed');
academy_assert(str_contains($entry,"require_roles(['student'])"),'Student role check');
academy_assert(str_contains($entry,"require_roles(['admin'])"),'Admin role check');
echo "Academy smoke checks passed.\n";
