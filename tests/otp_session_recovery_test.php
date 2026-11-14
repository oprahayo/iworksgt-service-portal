<?php
declare(strict_types=1);
/**
 * Signed OTP recovery test: protects delayed delivery from PHP session
 * storage eviction without bypassing password, code or CSRF validation.
 */
$GLOBALS['otp_test_user'] = [
    'id' => 7,
    'status' => 'active',
    'account_state' => 'active',
    'password_hash' => password_hash('unique-strong-password', PASSWORD_DEFAULT),
];
function db_one(string $sql, array $params = []): ?array
{
    if (str_contains($sql, 'FROM users WHERE id')) {
        return (int)($params['id'] ?? 0) === 7 ? $GLOBALS['otp_test_user'] : null;
    }
    return null;
}
function setting(string $key, string $default = ''): string
{
    return $key === 'otp_expiry_minutes' ? '10' : $default;
}
function verify_csrf(): bool
{
    return isset($_SESSION['csrf_token'], $_POST['csrf_token'])
        && hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
}
require __DIR__.'/../app/auth.php';
function assert_recovery(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$_SERVER['HTTP_HOST'] = 'portal.iworksgt.com';
session_start();
set_pending_otp(7, 'login');
assert_recovery(pending_otp_context()['user_id'] === 7, 'Normal session must retain pending OTP.');
$name = otp_recovery_cookie_name();
$token = otp_recovery_build_cookie(7, 'login', $GLOBALS['otp_test_user']);
$_COOKIE[$name] = $token;
$formToken = otp_recovery_form_token();
assert_recovery(strlen($formToken) === 64, 'Signed challenge must mint a CSRF-bound form token.');
$_POST = ['otp_challenge_csrf' => $formToken];
$_SESSION = []; // Simulate cPanel PHP session garbage collection during email delay.
assert_recovery(pending_otp_context()['purpose'] === 'login', 'Lost PHP session must recover OTP challenge.');
assert_recovery(otp_submission_csrf_valid(), 'Valid signed OTP form must survive session eviction.');
$_POST['otp_challenge_csrf'] = str_repeat('0',64);
assert_recovery(!otp_submission_csrf_valid(), 'Forged challenge CSRF must be blocked.');
$_POST['otp_challenge_csrf'] = $formToken;
$_COOKIE[$name] = substr_replace($token, '0', -1, 1);
assert_recovery(!otp_submission_csrf_valid(), 'Tampered cookie must not authorize OTP.');
assert_recovery(pending_otp_context() === null, 'Tampered cookie with lost session must not recover.');
$_COOKIE[$name] = otp_recovery_build_cookie(7, 'login', $GLOBALS['otp_test_user'], time()-3600);
assert_recovery(pending_otp_context() === null, 'Expired signed challenge must not recover.');
$_COOKIE[$name] = otp_recovery_build_cookie(7, 'login', $GLOBALS['otp_test_user'], time()+120);
assert_recovery(pending_otp_context() === null, 'Future-dated signed challenge must not recover.');
$_COOKIE[$name] = $token;
$GLOBALS['otp_test_user']['password_hash'] = password_hash('new-password', PASSWORD_DEFAULT);
assert_recovery(pending_otp_context() === null, 'Password rotation must revoke signed OTP recovery.');
$GLOBALS['otp_test_user']['password_hash'] = password_hash('newer-password', PASSWORD_DEFAULT);
$GLOBALS['otp_test_user']['account_state'] = 'suspended';
assert_recovery(pending_otp_context() === null, 'Suspended account must not recover OTP.');
$GLOBALS['otp_test_user']['account_state'] = 'active';
$_SESSION['pending_otp'] = [
    'user_id'=>7, 'purpose'=>'verify_email', 'created_at'=>date('Y-m-d H:i:s'),
];
unset($_COOKIE[$name]);
assert_recovery(pending_otp_context()['purpose'] === 'verify_email',
    'Legacy session-only flow must continue when recovery cookie is blocked.');
$_SESSION['csrf_token'] = 'legacy-form-token';
$_POST = ['csrf_token'=>'legacy-form-token'];
assert_recovery(otp_submission_csrf_valid(), 'Existing session CSRF must remain accepted.');
clear_pending_otp();
assert_recovery(pending_otp_context() === null, 'Completed login must remove pending challenge.');
session_write_close();
echo "otp_session_recovery_test passed\n";
