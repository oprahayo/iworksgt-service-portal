<?php
declare(strict_types=1);
/** Loading transition must be lightweight and strictly operational-backend. */
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
$css = $src('assets/css/admin-transition.css');
$js = $src('assets/js/admin-transition.js');

$clientReturn = strpos($header, "require __DIR__ . '/client-header.php';");
$overlay = strpos($header, 'id="iwAdminTransition"');
$assert($clientReturn !== false && $overlay !== false && $clientReturn < $overlay,
    'Client role must return before backend-only overlay markup.');
$assert(str_contains($header,'<?php if ($user): ?>') && str_contains($header,'assets/css/admin-transition.css')
    && str_contains($header,'iw-admin-transition-enter'), 'Only authenticated backend HTML should bootstrap the loader.');
$assert(str_contains($header,'iworks-compact.svg') && str_contains($header,'iw-admin-transition-name">iWORKS</span>'),
    'Loader must use original approved orange mark and short iWORKS wordmark.');
$assert(str_contains($footer,"if (\$user && (string)(\$user['role'] ?? '') !== 'client')")
    && str_contains($footer,'assets/js/admin-transition.js'),
    'Operational backend footer must load navigation behavior; exclude clients.');
$assert(!str_contains($client,'iw-admin-transition')
    && !str_contains($publicHeader,'iw-admin-transition')
    && !str_contains($publicFooter,'iw-admin-transition'),
    'No splash overlay on public marketing, login or customer shell.');
$assert(str_contains($css, 'rgba(255, 255, 255, .72)')
    && str_contains($css, 'backdrop-filter: blur(7px)')
    && str_contains($css, 'pointer-events: none')
    && str_contains($css, 'prefers-reduced-motion: reduce'),
    'Loading screen must be frosted/translucent, non-blocking and accessible.');
$assert(str_contains($css, '@keyframes iw-admin-brand-pulse')
    && str_contains($css, 'scale(1.06)'),
    'Logo must animate a restrained zoom pulse.');
$assert(str_contains($js, 'new URL(anchor.href, window.location.href)')
    && str_contains($js, "destination.hostname === 'iworksgt.com'")
    && str_contains($js, 'event.defaultPrevented')
    && str_contains($js, "window.addEventListener('pageshow'")
    && str_contains($js,'window.setTimeout(hideOverlay, 1050)')
    && !str_contains($js,'preventDefault()'),
    'Navigation animation must include backend-to-public links without hijacking navigation or back-forward cache.');
echo "admin_transition_contract_test passed\n";
