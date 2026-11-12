<?php
declare(strict_types=1);

function verify_split(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function app_config(): array
{
    return ['app'=>[
        'base_url'=>'https://portal.iworksgt.com',
        'public_base_url'=>'https://portal.iworksgt.com',
        'timezone'=>'Africa/Lagos',
    ]];
}
require dirname(__DIR__) . '/app/helpers.php';

// An old public URL misconfiguration must not keep canonical URLs on portal.
$_SERVER['HTTP_HOST'] = 'portal.iworksgt.com';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTPS'] = 'on';
verify_split(public_base_url() === 'https://iworksgt.com', 'Public canonical must be apex on portal host.');
verify_split(portal_base_url() === 'https://portal.iworksgt.com', 'Portal origin must stay separate.');
verify_split(public_url('home') === 'https://iworksgt.com/', 'Homepage must be canonical root.');
verify_split(public_url('services') === 'https://iworksgt.com/services', 'Services URLs must be public.');
verify_split(public_url('service',['slug'=>'company-registration']) === 'https://iworksgt.com/services/company-registration', 'Service detail must use public host.');
verify_split(portal_url('login') === 'https://portal.iworksgt.com/index.php?page=login', 'Login must stay portal.');
verify_split(portal_url('google_callback') === 'https://portal.iworksgt.com/index.php?page=google_callback', 'Google callback must stay portal.');
verify_split(should_enforce_split_domain(), 'Split routing should be enforced for the public installation.');

$_SERVER['HTTP_HOST'] = 'iworksgt.com';
verify_split(public_base_url() === 'https://iworksgt.com', 'Apex request must stay on apex.');
verify_split(portal_url('register') === 'https://portal.iworksgt.com/index.php?page=register', 'Registration must stay portal.');

// App source contracts: ensure authenticated forms do not render on public
// host and sitemap + non-public routes are routed to their proper hostname.
$index = file_get_contents(dirname(__DIR__) . '/index.php');
$footer = file_get_contents(dirname(__DIR__) . '/views/partials/public-footer.php');
$front = file_get_contents(dirname(__DIR__) . '/public-root/index.php');
$rewrites = file_get_contents(dirname(__DIR__) . '/public-root/.htaccess');
verify_split(str_contains($index, 'seo_robots') && str_contains($index, 'public_absolute_url'), 'Search discovery must canonicalize.');
verify_split(str_contains($index, 'should_enforce_split_domain() && request_host() === configured_host(public_base_url())'), 'Public-root homepage must ignore portal session.');
verify_split(str_contains($footer, 'request_host() === configured_host(portal_base_url())'), 'Password forms must not render on apex host.');
verify_split(str_contains($front, 'portal.iworksgt.com/index.php'), 'Root front controller must route to existing portal app.');
verify_split(str_contains($rewrites, 'RewriteRule ^sitemap\\.xml$') && str_contains($rewrites, 'RewriteRule ^robots\\.txt$'), 'Public sitemap and robots rewrites required.');
verify_split(str_contains($rewrites, 'RewriteCond %{HTTP_HOST} ^iworksgt\\.com$'), 'Root rewrites must not affect portal host.');
echo "domain_split_contract_test passed\n";
