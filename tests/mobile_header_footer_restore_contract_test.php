<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$header = (string) file_get_contents($root.'/views/partials/public-header.php');
$footer = (string) file_get_contents($root.'/views/partials/public-footer.php');
$css = (string) file_get_contents($root.'/assets/css/site-launch-polish.css');
$navCss = (string) file_get_contents($root.'/assets/css/public-navigation-cta.css');
$installer = (string) file_get_contents($root.'/scripts/deploy_mobile_header_footer_restore.sh');
$check = static function (bool $ok, string $why): void {
    if (!$ok) { throw new RuntimeException($why); }
};
$check(str_contains($header, 'class="iw-mobile-menu-cta-row"'), 'Mobile CTA pair wrapper missing.');
$start = strpos($header, 'class="iw-mobile-menu-start"');
$consultation = strpos($header, 'class="iw-mobile-menu-book"');
$signin = strpos($header, 'class="iw-mobile-menu-signin');
$check($signin!==false && $start!==false && $consultation!==false
    && $signin < $start && $start < $consultation,
    'Mobile must keep sign in separately, Get started left and consultation right.');
$check(str_contains($header, 'data-assist-book-open')
    && str_contains($header, 'data-iw-auth-status')
    && str_contains($header, 'data-iw-guest-cta'),
    'Existing booking, login and authenticated Get started behavior must remain.');
$check(str_contains($header, 'aria-label="Request a consultation"'),
    'Short consultation button needs an accessible full name.');
$check(str_contains($header, '<details class="iw-mobile-menu-group">')
    && str_contains($header, '<summary>What we do')
    && str_contains($header, '<summary>Our Solutions')
    && str_contains($header, '<summary>Resources')
    && str_contains($header, '<summary>About'),
    'Preserve functional native mobile accordion groups.');
$check(str_contains($css, 'grid-template-columns: minmax(0, .94fr) minmax(0, 1.06fr)')
    && !str_contains($css, 'grid-template-columns: minmax(0, 1fr);\n    width: 100%;'),
    'Mobile footer must be restored to two columns below 640px.');
$check(str_contains($footer, 'class="public-footer-top-grid"')
    && str_contains($footer, 'public-footer-contact-col'),
    'Keep company/services/legal/contact sections from CMS footer.');
$check(str_contains($css, 'body.page-public .public-footer-socials')
    && str_contains($css, 'justify-content: center;')
    && str_contains($css, 'justify-self: center;'),
    'Social icons must be centred on mobile.');
$check(str_contains($css, 'overflow-wrap: anywhere;'),
    'Long real contact information must wrap without horizontally scrolling.');
$check(str_contains($css, '#publicNav:not(.show):not(.collapsing)')
    && str_contains($css, 'margin-top: 0!important;')
    && str_contains($css, 'padding: 0!important;'),
    'Collapsed header must not reserve blank space.');
$check(str_contains($css, 'min-height: 46px;')
    && str_contains($css, '.iw-mobile-menu-group summary i')
    && str_contains($css, '.iw-mobile-menu-group[open] summary'),
    'Accordions must be compact, clear and visibly expandable.');
$check(str_contains($css, '.iw-mobile-menu-cta-row')
    && str_contains($css, 'display: flex;')
    && str_contains($css, 'flex: 1 1 0;'),
    'Get started and consultation must share one flexible row.');
$check(str_contains($css, '@media (max-width: 1199.98px)')
    && str_contains($css, '@media (max-width: 640px)'),
    'Desktop layout must not inherit the mobile-only refinements.');
$check(str_contains($header, '20261115-visual-finish1'),
    'Bump fallback CSS cache version to show the restored footer.');
$check(str_contains($navCss, '[data-iw-guest-cta][hidden]{display:none!important}'),
    'Guest-only CTA must still disappear when user is logged in.');
$check(str_contains($installer, 'check_remediation_release.php')
    && str_contains($installer, 'restore_on_error()')
    && str_contains($installer, 'portal.tgz'),
    'CPanel rollout requires preflight, private backup and error rollback.');
$check(!str_contains($installer, 'mysqldump')
    && !str_contains($installer, 'DROP DATABASE'),
    'This is a visual-only deployment; do not change database.');
echo "mobile_header_footer_restore_contract_test passed\n";
