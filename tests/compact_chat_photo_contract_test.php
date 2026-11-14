<?php
declare(strict_types=1);
// Chat first-contact + genuine photography presentation regression guard.
// No database or live network dependency.
$root=dirname(__DIR__);
$read=static fn(string $path):string => (string) file_get_contents($root.'/'.$path);
$assert=static function(bool $ok,string $message):void { if (!$ok) throw new RuntimeException($message); };
$widget=$read('views/partials/public-assistance.php');
$chatCss=$read('assets/css/public-assistance.css');
$chatJs=$read('assets/js/public-assistance.js');
$visual=$read('assets/css/site-launch-polish.css');
$home=$read('views/public-home-clean.php');
$about=$read('views/public-about-refined.php');
$product=$read('views/public-service-detail.php');
$article=$read('views/public-blog-detail.php');
$publicHeader=$read('views/partials/public-header.php');
$publicFooter=$read('views/partials/public-footer.php');
$installer=$read('scripts/deploy_chat_photo_polish.sh');

$assert(str_contains($widget,'id="iwAssistStartForm"')
    && str_contains($widget,'name="full_name"')
    && str_contains($widget,'name="email"')
    && str_contains($widget,'autocomplete="name"')
    && str_contains($widget,'autocomplete="email"'),
    'Preserve existing live chat identity requirements and autofill.');
$startButton=strpos($widget, 'type="submit" class="iw-assist-primary">Start conversation');
$privacyDetail=strpos($widget, '<details class="iw-assist-privacy-details">');
$assert($startButton!==false && $privacyDetail!==false && $startButton<$privacyDetail,
    'First-screen start button must appear above long optional privacy details.');
$assert(str_contains($widget,'<summary>How we use your details</summary>')
    && str_contains($widget,'No precise GPS is collected')
    && str_contains($widget,"public_url('privacy')"),
    'Privacy details must remain accessible and truthful.');
$assert(str_contains($chatCss,'.iw-assist .iw-assist-welcome')
    && str_contains($chatCss,'.iw-assist .iw-assist-identity input')
    && str_contains($chatCss,'min-height:0; text-align:left;')
    && str_contains($chatCss,'height:100dvh'),
    'Compact one-screen start stage and mobile viewport support missing.');
$assert(str_contains($chatJs,'preventScroll:true')
    && str_contains($chatJs,"window.matchMedia('(min-width:581px)')")
    && str_contains($chatJs,".iw-assist-chat-main').scrollTop = 0"),
    'Opening chat must not automatically scroll CTA away or trigger mobile keyboard.');
$assert(str_contains($chatJs,"liveApi('start'") && str_contains($chatJs,'session.status'),
    'Existing live support backend and chat state must remain intact.');
$assert(str_contains($home,'iw-photo-feature-hero')
    && str_contains($home,'iw-photo-feature-support')
    && str_contains($home,"iworks_authentic_photo_url('support-agent')")
    && str_contains($home,'<picture>'),
    'Real photos must remain intact and gain reusable decorative treatments.');
$assert(str_contains($about,'iw-photo-feature-about') && str_contains($about,'$heroImageUrl'),
    'About must retain CMS/real photography under decorative photo treatment.');
$assert(str_contains($product,'iw-photo-feature-product') && str_contains($product,'$imageUrl'),
    'Service details must retain their existing CMS photographs.');
$assert(str_contains($article,'iw-photo-feature-article') && str_contains($article,'$cover'),
    'Insights must retain their existing CMS photographs.');
foreach(['.iw-photo-feature-hero::before','.iw-photo-feature-support::before',
         '.iw-photo-feature-about::before','.iw-photo-feature-product::after',
         '.iw-photo-chip'] as $selector) {
    $assert(str_contains($visual,$selector),'Missing lightweight graphic accent: '.$selector);
}
$assert(str_contains($home,'Business services')
    && str_contains($home,'Web &amp; digital')
    && !str_contains($home,'830+ Countries'),
    'Decorative service labels must not invent numerical statistics.');
$assert(str_contains($publicHeader,'20261114-photo2')
    && str_contains($publicHeader,'20261114-compact2')
    && str_contains($publicFooter,'20261114-compact2'),
    'Public split-root CSS/JS cache versions must be updated.');
$assert(str_contains($installer,'check_remediation_release.php')
    && str_contains($installer,'portal.tgz')
    && str_contains($installer,'rollback()'),
    'Guarded cPanel release must require preflight and private rollback.');
$assert(!str_contains($installer,'mysqldump')
    && !str_contains($installer,'DROP DATABASE')
    && !str_contains($installer,'assets/uploads'),
    'Visual update cannot modify client data or uploaded media.');
echo "compact_chat_photo_contract_test passed\n";
