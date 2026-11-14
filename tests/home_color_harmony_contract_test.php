<?php
declare(strict_types=1);
// Static contract for this narrowly scoped, CSS-only homepage adjustment.
$root = dirname(__DIR__);
$css = (string) file_get_contents($root . '/assets/css/site-launch-polish.css');
$header = (string) file_get_contents($root . '/views/partials/public-header.php');
$installer = (string) file_get_contents($root . '/scripts/deploy_home_color_finish.sh');
function color_contract(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$faq = [];
$cta = [];
color_contract((bool) preg_match('~body\.page-public \.iw-clean-home \.iw-clean-faq \{\s*background:(.*?)!important;~s', $css, $faq),
    'FAQ must declare the shared warm charcoal surface.');
color_contract((bool) preg_match('~body\.page-public \.iw-sitewide-cta\{\s*background:(.*?)!important;~s', $css, $cta),
    'Existing CTA gradient must remain.');
$normalize = static fn(string $value): string => (string) preg_replace('/\s+/', '', $value);
color_contract($normalize($faq[1]) === $normalize($cta[1]),
    'FAQ must match Ready to Launch gradient exactly.');
color_contract(str_contains($css, 'body.page-public .iw-clean-home .iw-clean-support,')
    && str_contains($css, 'body.page-public .iw-clean-home .iw-clean-services')
    && str_contains($css, 'background:#fff!important;'),
    'Support and service group backgrounds must both be plain white.');
color_contract(str_contains($css, 'rgba(241,109,24,.23)')
    && str_contains($css, 'linear-gradient(112deg,#fff 4%,#fffaf6 57%,#fff0e3 100%)'),
    'Hero needs a broader, welcoming warm orange gradient.');
color_contract(str_contains($css, '.iw-clean-faq-intro h2,')
    && str_contains($css, '.iw-clean-faq-items summary')
    && str_contains($css, 'color:#fff!important;'),
    'FAQ headings and accordion controls must remain readable on dark surface.');
color_contract(str_contains($header, '20261114-photo2'),
    'Fallback CSS link must cache-bust the new color revision.');
color_contract(str_contains($installer, 'check_remediation_release.php')
    && str_contains($installer, 'home-color-')
    && str_contains($installer, 'restore_on_error()'),
    'Color rollout must use a fail-closed preflight and private rollback.');
color_contract(!str_contains($installer, 'mysqldump')
    && !str_contains($installer, 'DROP DATABASE')
    && !str_contains($installer, 'assets/uploads'),
    'Color rollout must never modify database or uploaded assets.');
echo "home_color_harmony_contract_test passed\n";
