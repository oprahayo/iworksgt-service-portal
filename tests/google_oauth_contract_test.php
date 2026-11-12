<?php
declare(strict_types=1);
function check_oauth(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
$root = dirname(__DIR__);
require_once $root . '/app/helpers.php';
require_once $root . '/app/auth.php';
$portal = 'https://portal.iworksgt.com';
$uri = $portal . '/index.php?page=google_callback';
check_oauth(google_oauth_redirect_problems($uri, $portal) === [], 'Canonical HTTPS Google callback should be accepted.');
check_oauth(google_oauth_redirect_problems('http://portal.iworksgt.com/index.php?page=google_callback', $portal) !== [], 'Public HTTP callback must be rejected.');
check_oauth(google_oauth_redirect_problems('https://iworksgt.com/index.php?page=google_callback', $portal) !== [], 'Wrong-origin callback would lose the OAuth session.');
check_oauth(google_oauth_redirect_problems($portal . '/index.php?page=login', $portal) !== [], 'Callback page must be google_callback.');
check_oauth(google_oauth_redirect_problems($portal . '/callback', $portal) !== [], 'Unsupported pretty callback path should be rejected.');
check_oauth(google_oauth_redirect_problems('http://localhost/index.php?page=google_callback', 'http://localhost') === [], 'Local development HTTP must remain usable.');

$_SESSION = [];
$state = str_repeat('a', 48);
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_state_created_at'] = time();
check_oauth(consume_google_oauth_state($state), 'Valid Google OAuth state must pass.');
check_oauth(!consume_google_oauth_state($state), 'OAuth state must be one-time use.');
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_state_created_at'] = time() - 901;
check_oauth(!consume_google_oauth_state($state), 'Expired OAuth state must be rejected.');

$login = file_get_contents($root . '/views/login.php');
$signup = file_get_contents($root . '/views/register.php');
$settings = file_get_contents($root . '/views/settings.php');
$auth = file_get_contents($root . '/app/auth.php');
$router = file_get_contents($root . '/index.php');
check_oauth(str_contains($login, "portal_url('google_start')"), 'Login must send OAuth via absolute portal origin.');
check_oauth(str_contains($signup, "portal_url('google_start')"), 'Registration must offer Google sign up via portal.');
check_oauth(str_contains($login, 'class="iw-auth-home"') && str_contains($signup, 'class="iw-auth-home"'), 'Standalone auth pages need a home link.');
check_oauth(str_contains($settings, 'Authorized redirect URI'), 'Admin must provide exact callback URI.');
check_oauth(!str_contains($settings, 'value="<?= e(google_client_secret()) ?>"'), 'Admin must not echo saved secret into form HTML.');
check_oauth(str_contains($router, "\$clientSecret = google_client_secret()"), 'Blank settings secret must preserve existing secret.');
check_oauth(str_contains($auth, 'google_oauth_redirect_problems()'), 'Authorize only a verified portal callback route.');
echo "google_oauth_contract_test passed\n";
