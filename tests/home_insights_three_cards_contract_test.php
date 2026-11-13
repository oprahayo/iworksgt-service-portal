<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$css=file_get_contents($root.'/assets/css/public-mobile-audit.css');
$home=file_get_contents($root.'/views/public-home-clean.php');
function check_home_insights(bool $ok,string $message):void {
    if (!$ok) throw new RuntimeException($message);
}
check_home_insights(str_contains($css,'.iw-home-insight{flex:0 0 calc((100% - 32px)/3);'),
    'Desktop Business Insights must show three cards, not four.');
check_home_insights(!str_contains($css,'.iw-home-insight{flex:0 0 calc((100% - 48px)/4);'),
    'Legacy four-up width must be removed.');
check_home_insights(str_contains($css,'@media(max-width:1050px){.iw-home-insight{flex-basis:calc((100% - 16px)/2)}'),
    'Tablet must show two readable cards.');
check_home_insights(str_contains($css,'@media(max-width:510px){.iw-home-insight{flex-basis:85%}'),
    'Phones must retain a readable single-card carousel and scroll cue.');
check_home_insights(str_contains($home,"list_blog_posts_public(4)") &&
    str_contains($home,'id="iwHomeInsights"') &&
    str_contains($home,'data-iw-insights-next') &&
    str_contains($home,'data-iw-insights-prev'),
    'Fourth article must remain accessible by working carousel instead of being discarded.');
check_home_insights(str_contains($home,'iw-home-journal-no-cover') &&
    str_contains($home,'iw-home-journal-art'),
    'Approved gradient artwork must remain intact.');
echo "home_insights_three_cards_contract_test passed\n";
