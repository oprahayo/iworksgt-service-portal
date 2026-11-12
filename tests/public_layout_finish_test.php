<?php
declare(strict_types=1);
// Visual/interaction contract checks for the public release.
// Actual live browser QA is additionally required following deployment.
$root = dirname(__DIR__);
function must_have(bool $pass, string $message): void {
    if (!$pass) { throw new RuntimeException($message); }
}
$source = static fn(string $path): string => (string) file_get_contents($root.'/'.$path);
$legacyCss = $source('assets/css/app.css');
$latestNav = $source('assets/css/ux-finish.css');
$fixes = $source('assets/css/public-layout-fixes.css');
$home = $source('views/public-home-clean.php');
$about = $source('views/public-about-refined.php');
$footer = $source('views/partials/public-footer.php');
$header = $source('views/partials/public-header.php');
$services = $source('views/public-services-refined.php');
$js = $source('assets/js/public-ux.js');

must_have(str_contains($legacyCss, '.public-nav-center .nav-link::after'), 'The original single navigation underline should remain.');
must_have(!str_contains($latestNav, 'border-bottom:2px solid transparent!important'), 'The duplicate underline must be removed.');
must_have(!str_contains($latestNav, 'border-bottom-color:#f16816!important'), 'A second active underline must not be drawn.');
must_have(str_contains($fixes, '.iw-clean-service-icon') && str_contains($fixes, 'width:66px'), 'Home service icons must be larger and centered.');
must_have(str_contains($fixes, '.iw-about-min-service-grid>a>i'), 'About service icons must match.');
must_have(str_contains($fixes, '@keyframes iw-step-transmit'), 'How-it-works arrow transmission animation missing.');
must_have(str_contains($fixes, 'prefers-reduced-motion:reduce'), 'Motion reduction must be supported.');
must_have(str_contains($home, 'class="iw-clean-faq-intro"'), 'FAQ image panel missing.');
must_have(str_contains($home, 'pexels-photo-8866777'), 'Support-agent FAQ photograph missing.');
must_have(str_contains($fixes, '.iw-clean-faq-intro::before'), 'FAQ image needs faded overlay.');
must_have(str_contains($fixes, 'main.iw-clean-home') && str_contains($fixes, 'padding-bottom:0!important'), 'Remove review-to-footer gap.');
must_have(str_contains($fixes, 'background:#fff'), 'Keep reviews on the website background.');
must_have(str_contains($about, 'has-founder-photo'), 'Founder image direction must depend on photo presence.');
must_have(str_contains($fixes, '.iw-about-min-founder-grid.has-founder-photo>img'), 'Founder photo must be on the left.');
must_have(str_contains($fixes, 'width:100vw'), 'Custom-services CTA should be full bleed.');
must_have(str_contains($services, "function selectView(value)"), 'Directory grid toggle must run locally with the service page.');
must_have(str_contains($services, 'data-layout="grid"'), 'Grid must be the default view.');
must_have(str_contains($source('assets/css/services-list.css'), 'data-tone="1"'), 'Product tiles need distinct colour artwork.');
must_have(str_contains($source('assets/css/auth-compact.css'), 'height:fit-content!important'), 'Login popup must shrink to its form content.');
must_have(str_contains($source('views/login.php'), 'class="iw-auth-home"'), 'Standalone sign-in requires a home shortcut.');
must_have(!str_contains($js, "var serviceList = document.getElementById('iwServiceList')"), 'Grid must not be bound twice.');
must_have(!str_contains($footer, '<iframe'), 'No iframe or browser-like frame in the auth popup.');
must_have(str_contains($footer, "require __DIR__ . '/../login.php'"), 'Reuse existing login backend form.');
must_have(str_contains($footer, "require __DIR__ . '/../register.php'"), 'Reuse existing registration backend form.');
must_have(str_contains($footer, "new URL(authTrigger.href, window.location.href).origin !== window.location.origin"), 'Modal may only use same-origin auth sessions.');
must_have(str_contains($footer, 'iwPublicAuthContent'), 'Modal flip must switch actual forms.');
must_have(str_contains($header, 'assets/css/auth-clean.css'), 'Public dialog must load native auth form styles.');
must_have(str_contains($header, 'public-layout-fixes.css'), 'Latest layout CSS must be loaded.');
echo "public_layout_finish_test passed\n";
