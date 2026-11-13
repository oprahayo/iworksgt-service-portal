<?php
declare(strict_types=1);
require __DIR__ . '/../app/public_marketing.php';
function check_remediation(bool $ok, string $description): void
{
    if (!$ok) { throw new RuntimeException($description); }
}
foreach (['/unknown-path-x92','/random/deep/path','/services/deleted/deep'] as $path) {
    check_remediation(iworks_unmatched_pretty_path($path, false), 'Unknown path must produce a 404: ' . $path);
}
foreach (['','/','index.php','/index.html'] as $path) {
    check_remediation(!iworks_unmatched_pretty_path($path, false), 'Root and direct entrypoint must remain valid.');
}
foreach (['services','/services/website-design-development','blog/example','about'] as $path) {
    check_remediation(!iworks_unmatched_pretty_path($path, true), 'Known route must remain available.');
}
$marketing = ['home','about','services','blog','case-studies','management','pricing','privacy','terms','contact','faq','track'];
$descriptions=[];
foreach ($marketing as $page) {
    $meta=iworks_search_page_meta($page);
    check_remediation(!empty($meta['title']) && !empty($meta['description']), 'Missing route metadata for '.$page);
    check_remediation(!isset($descriptions[$meta['description']]), 'Duplicated metadata for '.$page);
    $descriptions[$meta['description']]=true;
}
$index=file_get_contents(__DIR__.'/../index.php');
check_remediation(str_contains($index,'iworks_unmatched_pretty_path($rawPath, $requestedPrettyPublicPage)'), 'Router must use tested classifier.');
check_remediation(str_contains($index,"http_response_code(404);") && str_contains($index,"'/views/public-404.php'"), 'Missing routes must display existing 404 template.');
$footer=file_get_contents(__DIR__.'/../assets/css/site-launch-polish.css');
check_remediation(str_contains($footer,'.public-footer-contact-col a') && str_contains($footer,'overflow-wrap: anywhere'), 'Footer contact must wrap without overflow clipping.');
$chat=file_get_contents(__DIR__.'/../views/partials/public-assistance.php');
check_remediation(str_contains($chat,'Send a message. Our team will reply here when available.'), 'Chat idle state must not claim to check staff availability before session.');
check_remediation(str_contains($chat,'aria-live="polite"'), 'Support status changes must be announced.');
$deployer=file_get_contents(__DIR__.'/../scripts/deploy_admin_mobile_refresh.sh');
check_remediation(str_contains($deployer,'|database.sql) continue'), 'SQL must not be copied to public document root.');
check_remediation(str_contains($deployer,'-${BASHPID}'), 'Private backup path must include process ID.');
echo "f16_f17_f18_f19_site_remediation_test passed\n";
