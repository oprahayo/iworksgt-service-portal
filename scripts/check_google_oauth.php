<?php
/**
 * Safe CLI-only Google OAuth readiness report.
 * Run from portal root: php scripts/check_google_oauth.php
 * Never prints Client ID, Client Secret, tokens, state or customer data.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

echo "iWorks Google sign-in readiness\n";
echo "--------------------------------\n";
echo 'Enabled: ' . (google_auth_enabled() ? 'yes' : 'no') . "\n";
echo 'Client ID present: ' . (google_client_id() !== '' ? 'yes' : 'no') . "\n";
echo 'Client Secret present: ' . (google_client_secret() !== '' ? 'yes' : 'no') . "\n";
echo 'HTTPS callback: ' . (str_starts_with(google_redirect_uri(), 'https://') ? 'yes' : 'no') . "\n";
echo 'Exact callback to authorize in Google Cloud: ' . google_redirect_uri() . "\n";
$issues = google_oauth_redirect_problems();
foreach ($issues as $issue) {
    echo 'ACTION: ' . $issue . "\n";
}
if (google_auth_ready() && $issues === []) {
    echo "Local configuration: READY to test.\n";
    echo "NEXT: Check that Google Cloud has the exact callback URI under OAuth Web client authorized redirect URIs.\n";
    echo "Check that Audience allows the testing account or the app has been published.\n";
    echo "Real sign-in is not confirmed until a user completes Google consent and lands in the portal.\n";
} else {
    echo "Local configuration: NEEDS ATTENTION in Admin -> Settings -> Google Sign-In.\n";
}
