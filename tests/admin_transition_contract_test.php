<?php
declare(strict_types=1);
/**
 * The iWORKS pulse must be shared by ALL logged-in portal accounts, including
 * customers who use a separate shell; never use it on the public/auth pages.
 */
$base = dirname(__DIR__);
$src = static fn(string $p): string => (string)file_get_contents($base.'/'.$p);
$assert = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$header = $src('views/partials/header.php');
$footer = $src('views/partials/footer.php');
$client = $src('views/partials/client-header.php');
$publicHeader = $src('views/partials/public-header.php');
$publicFooter = $src('views/partials/public-footer.php');
$authEmbed = $src('views/partials/auth-embed-header.php');
$head = $src('views/partials/portal-transition-head.php');
$overlay = $src('views/partials/portal-transition-overlay.php');
$css = $src('assets/css/admin-transition.css');
$js = $src('assets/js/admin-transition.js');

$clientReturn = strpos($header, "require __DIR__ . '/client-header.php';");
$staffHeadInclude = strpos($header, "require __DIR__ . '/portal-transition-head.php';");
$staffOverlayInclude = strpos($header, "require __DIR__ . '/portal-transition-overlay.php';");
$assert($clientReturn !== false && $staffHeadInclude !== false
    && $staffOverlayInclude !== false && $clientReturn < $staffHeadInclude
    && $staffHeadInclude < $staffOverlayInclude,
    'The staff shell and dedicated client shell must share transition includes.');
$assert(str_contains($client, "require __DIR__ . '/portal-transition-head.php';")
    && str_contains($client, "require __DIR__ . '/portal-transition-overlay.php';"),
    'The customer workspace must load the same loading screen as staff.');
$assert(str_contains($head, 'if (!empty($user))')
    && str_contains($overlay, 'if (!empty($user))')
    && str_contains($head, 'assets/css/admin-transition.css?v=20261114-pulse2')
    && str_contains($head, 'iw-admin-transition-enter')
    && str_contains($head, '}, 900);'),
    'Only authenticated HTML should start a loader with a safe timeout.');
$assert(str_contains($overlay, 'id="iwAdminTransition"')
    && str_contains($overlay, 'iworks-compact.svg')
    && str_contains($overlay, 'iw-admin-transition-name">iWORKS</span>'),
    'Staff and customers must see the identical official orange iWORKS lockup.');
$assert(str_contains($footer, '<?php if ($user): ?>')
    && str_contains($footer, 'assets/js/admin-transition.js?v=20261114-pulse2')
    && !str_contains($footer, "if (\$user && (string)(\$user['role'] ?? '') !== 'client')"),
    'All authenticated users must receive the navigation script, including clients.');
$assert(!str_contains($publicHeader,'iw-admin-transition')
    && !str_contains($publicFooter,'iw-admin-transition')
    && !str_contains($authEmbed,'iw-admin-transition'),
    'Do not add loading animation to public marketing or embedded authentication.');
$assert(str_contains($css, 'rgba(255, 255, 255, .72)')
    && str_contains($css, 'backdrop-filter: blur(7px)')
    && str_contains($css, 'pointer-events: none')
    && str_contains($css, 'prefers-reduced-motion: reduce'),
    'Loading screen must be translucent, non-blocking and reduced-motion safe.');
$assert(str_contains($css, '@keyframes iw-admin-brand-pulse')
    && str_contains($css, 'scale(1.06)'),
    'Logo must keep the approved restrained zoom pulse.');
$assert(str_contains($js, 'new URL(anchor.href, window.location.href)')
    && str_contains($js, "destination.hostname === 'iworksgt.com'")
    && str_contains($js, 'event.defaultPrevented')
    && str_contains($js, "window.addEventListener('pageshow'")
    && str_contains($js, 'window.setTimeout(hideOverlay, 1050)')
    && !str_contains($js, 'preventDefault()'),
    'Navigation must not hijack clicks or leave a stuck overlay on browser return.');

// Run the two shared partials in isolation for each account role. This catches
// accidental role filtering as well as a missing image/overlay markup.
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function portal_absolute_url(string $path): string {
    return 'https://portal.iworksgt.com/' . ltrim($path, '/');
}
$render = static function (?array $account) use ($base): string {
    $user = $account;
    ob_start();
    require $base.'/views/partials/portal-transition-head.php';
    require $base.'/views/partials/portal-transition-overlay.php';
    return (string)ob_get_clean();
};
foreach (['admin','agent','finance','author','client'] as $role) {
    $html = $render(['id'=>99,'role'=>$role]);
    $assert(substr_count($html, 'id="iwAdminTransition"')===1
        && str_contains($html, 'assets/css/admin-transition.css')
        && str_contains($html, 'iworks-compact.svg')
        && str_contains($html, '>iWORKS</span>'),
        'Authenticated role '.$role.' must see exactly one branded flash screen.');
}
$assert(trim($render(null))==='', 'A guest must not get authenticated transition markup.');

echo "admin_transition_contract_test passed\n";
