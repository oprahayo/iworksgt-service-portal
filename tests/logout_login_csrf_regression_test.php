<?php
declare(strict_types=1);
/**
 * Regression: immediate login after logout must use the new anonymous
 * session CSRF token, not a cached pre-logout form. Never bypass CSRF.
 */
require __DIR__.'/../app/helpers.php';
function require_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
session_start();
$oldSessionId = session_id();
$oldToken = csrf_token();
$_POST['csrf_token'] = $oldToken;
require_check(verify_csrf(), 'Valid initial CSRF token must be accepted.');
// Mirrors logout_user() clearing state and rotating the anonymous session.
// The real logout_user() session clear + rotation is tested in f03/f05.
$_SESSION = [];
require_check(session_regenerate_id(true), 'Anonymous session rotation failed.');
require_check(session_id() !== $oldSessionId, 'Logout must rotate session ID.');
$newToken = csrf_token();
require_check(!hash_equals($oldToken, $newToken), 'Logout must invalidate the old CSRF token.');
require_check(!verify_csrf(), 'Pre-logout cached CSRF token must not be accepted.');
$_POST['csrf_token'] = $newToken;
require_check(verify_csrf(), 'Fresh sign-in form token must work after logout.');
session_write_close();

$index = (string)file_get_contents(__DIR__.'/../index.php');
$logout = (string)file_get_contents(__DIR__.'/../logout.php');
$view = (string)file_get_contents(__DIR__.'/../views/login.php');
$start = strpos($index, "case 'login':");
$end = strpos($index, "case 'frontend':", $start === false ? 0 : $start);
require_check($start !== false && $end !== false, 'Login route missing.');
$login = substr($index, $start, $end - $start);
require_check(str_contains($index, "['login', 'register', 'otp', 'forgot_password', 'reset_password']")
    && str_contains($index, 'Cache-Control: private, no-store, no-cache, must-revalidate'),
    'Login and OTP forms must be uncacheable.');
require_check(str_contains($logout, 'session_write_close()')
    && str_contains($logout, 'Cache-Control: private, no-store, no-cache, must-revalidate'),
    'Logout must persist its fresh anonymous session before an uncached redirect.');
require_check(str_contains($view, "'pageshow'") && str_contains($view, 'event.persisted')
    && str_contains($view, 'window.location.reload()')
    && str_contains($view, 'empty($iwEmbeddedPublicAuth)'),
    'Standalone login must reload browser back/forward cached CSRF forms.');
$csrfGate = strpos($login, 'if (!verify_csrf())');
$authenticate = strpos($login, 'authenticate_with_password(');
require_check($csrfGate !== false && $authenticate !== false && $csrfGate < $authenticate,
    'Login must reject stale CSRF before password authentication.');
require_check(str_contains($login, "header('Location: ' . url('login'), true, 303)")
    && str_contains($login, "flash('error', 'This sign-in form is out of date."),
    'Stale form submission must show a safe reload prompt, not raw error or credential reuse.');
echo "logout_login_csrf_regression_test passed\n";
