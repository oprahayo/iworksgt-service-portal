<?php
declare(strict_types=1);
/**
 * Contract test: popup authentication must reuse the existing portal's forms,
 * CSRF, OTP workflow, and role-aware dashboard, not a new credential endpoint.
 */
function assert_contract(bool $value, string $message): void
{
    if (!$value) { throw new RuntimeException($message); }
}
$root = dirname(__DIR__);
$login = file_get_contents($root.'/views/login.php');
$register = file_get_contents($root.'/views/register.php');
$bootstrap = file_get_contents($root.'/app/bootstrap.php');
$router = file_get_contents($root.'/index.php');
$guest = file_get_contents($root.'/views/partials/public-footer.php');
$header = file_get_contents($root.'/views/partials/public-header.php');
$authCss = file_get_contents($root.'/assets/css/auth-clean.css');

foreach (['name="email"','name="password"','name="csrf_token"','target="_top"',"url('login')"] as $token) {
    assert_contract(str_contains($login, $token), 'Login field/security requirement missing: '.$token);
}
foreach (['first_name','last_name','email','phone','country','state_region','city','address_line','password','confirm_password'] as $field) {
    assert_contract(str_contains($register, 'name="'.$field.'"'), 'Register required field missing: '.$field);
}
assert_contract(str_contains($register, 'csrf_token'), 'Registration CSRF missing.');
assert_contract(str_contains($register, 'target="_top"'), 'Modal registration must submit to top-level portal.');
assert_contract(!str_contains($bootstrap, 'frame-ancestors'), 'Native auth modal must not require a framing allowance.');
assert_contract(!str_contains($guest, '<iframe'), 'Native modal must not embed browser-like iframe content.');
assert_contract(str_contains($guest, "require __DIR__ . '/../login.php'"), 'Dialog must render the existing login form directly.');
assert_contract(str_contains($guest, "require __DIR__ . '/../register.php'"), 'Dialog must render the existing registration form directly.');
assert_contract(str_contains($guest, 'data-iw-auth-pane="login"') && str_contains($guest, 'data-iw-auth-pane="register"'), 'Auth popup must have two switchable native panels.');
assert_contract(str_contains($guest, "new URL(authTrigger.href, window.location.href).origin !== window.location.origin"), 'Cross-origin auth must fall back to the secure portal.');
assert_contract(str_contains($bootstrap, "header('X-Frame-Options: SAMEORIGIN')"), 'Normal pages must remain frame-protected.');
assert_contract(str_contains($router, '/views/partials/auth-embed-header.php'), 'Modal routes must select the dedicated auth shell.');
assert_contract(str_contains($router, '/views/partials/auth-embed-footer.php'), 'Modal routes must close the dedicated auth shell.');
assert_contract(str_contains($router, 'authenticate_with_password('), 'Existing password verification must remain unchanged.');
assert_contract(str_contains($router, 'start_otp_challenge('), 'OTP must remain in the login flow.');
assert_contract(str_contains($guest, 'setAuthMode(') && str_contains($guest, 'iwPublicAuthContent'), 'Native account popup needs working panel switching.');
assert_contract(str_contains($guest, 'data-iw-dialog-close'), 'Auth modal must have a close action.');
assert_contract(str_contains($guest, 'data-iw-help-open'), 'Support dialog should be accessible.');
assert_contract(str_contains($header, 'data-iw-auth="login"'), 'Header sign-in must open secure popup.');
assert_contract(str_contains($authCss, '.iw-auth-embed'), 'Small embedded auth styling is missing.');
echo "public_auth_modal_contract_test passed\n";
