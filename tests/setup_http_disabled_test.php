<?php
declare(strict_types=1);
/** Live setup HTTP must remain inaccessible in normal and locked operation. */
require dirname(__DIR__).'/app/setup_access.php';
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$assert(!iworks_web_setup_allowed(false, true), 'Locked production must deny setup.');
$assert(!iworks_web_setup_allowed(false, false), 'Absent lock must not automatically allow web setup.');
$assert(!iworks_web_setup_allowed(true, true), 'Explicit opt-in must not bypass completed installation lock.');
$assert(iworks_web_setup_allowed(true, false), 'Deliberate first-install activation should be possible only when unlocked.');
$source = (string)file_get_contents(dirname(__DIR__).'/setup.php');
$start = strpos($source, "require_once __DIR__ . '/app/setup_access.php';");
$bootstrap = strpos($source, "require __DIR__ . '/app/bootstrap.php';");
$assert($start!==false && $bootstrap!==false && $start<$bootstrap,
    'Setup must deny HTTP requests before opening sessions or database connections.');
$assert(str_contains($source, "getenv('IWORKS_ENABLE_WEB_SETUP') === '1'")
    && str_contains($source, "http_response_code(404)")
    && str_contains($source, "X-Robots-Tag: noindex, nofollow")
    && str_contains($source, "iworks_web_setup_allowed("),
    'Production installer must fail closed with a real HTTP 404.');
echo "setup_http_disabled_test passed\n";
