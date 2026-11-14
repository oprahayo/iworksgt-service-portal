<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$src=static fn(string $path):string => (string) file_get_contents($root.'/'.$path);
$check=static function(bool $ok,string $message):void {
    if (!$ok) { throw new RuntimeException($message); }
};
$home=$src('views/public-home-clean.php');
$photo=$src('app/public-photo-config.php');
$faqCss=$src('assets/css/public-layout-fixes.css');
$caseCss=$src('assets/css/editorial-brand.css');
$brandCss=$src('assets/css/public-clean.css');
$footerCss=$src('assets/css/site-launch-polish.css');
$staff=$src('views/partials/header.php');
$blog=$src('views/blog.php');
$editorial=$src('views/editorial-showcase.php');
$installer=$src('scripts/deploy_home_brand_polish.sh');
$check(str_contains($home, "iworks_authentic_photo_url('support-agent')"),
    'Support section must use a local real photo with safe fallback.');
$check(str_contains($photo, "'support-agent' => 'support-agent-black.jpg'"),
    'Source map must contain the real support photo.');
$check(str_contains($installer, 'support-agent-black.jpg|7689752|1000'),
    'Installer must fetch the vetted original Pexels photograph.');
$check(str_contains($faqCss, 'background:#191d23') && str_contains($faqCss, 'color:#f8fafc')
    && str_contains($faqCss, '.iw-clean-faq-intro::before'),
    'FAQ must use an accessible dark/bright treatment with dark photo overlay.');
$check(str_contains($home, "sections['trusted_logos']")
    && str_contains($home, 'get_media_asset($imageId)')
    && str_contains($home, 'iworks_editorial_cases(true, 48)')
    && str_contains($home, 'iworks_editorial_reviews(true, 48)')
    && str_contains($home, 'iw-home-brand-ribbon'),
    'Hero ribbon must draw original Trusted Logos plus approved published CMS records.');
$check(str_contains($home, 'count($homeBrandLogos) >= 4')
    && str_contains($home, 'aria-hidden="true"')
    && str_contains($brandCss, 'filter:grayscale(1)')
    && str_contains($brandCss, 'filter:grayscale(0)')
    && str_contains($brandCss, 'prefers-reduced-motion:reduce'),
    'Non-clickable logo loop must be grayscale, hover-color and motion-safe.');
$check(!str_contains($home, 'href="<?= e($brand[\'logo\']) ?>"'),
    'Client logos should not be clickable.');
$check(str_contains($home, 'array_slice($homeCases, 0, 3)')
    && str_contains($home, 'Only approved case studies will appear here.'),
    'Homepage must show a maximum of three published case studies and honest empty state.');
$check(strpos($home, 'class="iw-home-case-stories"') < strpos($home, 'class="iw-clean-section iw-clean-support"'),
    'Case study section should be placed after steps and before support.');
$check(str_contains($caseCss, '.iw-home-case-grid') && str_contains($caseCss, 'grid-template-columns:repeat(3,minmax(0,1fr))'),
    'Responsive case study cards are required.');
$check(str_contains($footerCss, '.iw-sitewide-cta + .public-footer') && str_contains($footerCss, 'margin-top:0!important'),
    'CTA and footer must no longer have an extra spacer.');
foreach ([$staff,$blog,$editorial] as $content) {
    $check(!preg_match('/href="<\?= e\(public_url\([^;]*?\) \?>" target="_blank"/', $content),
        'Internal public navigation must stay in same tab.');
}
$check(str_contains($installer, 'check_remediation_release.php')
    && str_contains($installer, 'portal.tgz')
    && str_contains($installer, 'rollback()')
    && !str_contains($installer, 'mysqldump')
    && !str_contains($installer, 'DROP DATABASE'),
    'Installer must pass release gates, be file-backup-safe and avoid DB modifications.');
echo "home_brand_refinement_contract_test passed\n";
