<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$check=static function(bool $condition,string $message):void { if(!$condition)throw new RuntimeException($message); };
$api=$read('app/live_chat.php');
$route=$read('index.php');
$widget=$read('views/partials/public-assistance.php');
$js=$read('assets/js/public-assistance.js');
$staff=$read('views/live-inbox.php');
$staffJs=$read('assets/js/live-inbox.js');
$schema=$read('database.sql');
$deploy=$read('scripts/migrate_live_chat.php');
$client=$read('views/client-job.php');
$job=$read('views/job.php');
$reviews=$read('app/job_reviews.php');
$publicReviews=$read('views/partials/public-review-carousel.php');
$editorial=$read('assets/css/public-editorial-refinement.css');
$home=$read('views/public-home-clean.php');

$check(str_contains($route,"$"."page === 'live_chat'") && str_contains($route,"iw_chat_public_request()"),'Public chat endpoint must reach live handler.');
$check(str_contains($route,"case 'live_inbox':") && str_contains($route,"case 'live_inbox_api':"),'Staff inbox HTML/API routes missing.');
$check(str_contains($route,"require_roles(['admin','agent']);"),'Staff route must require staff role.');
$check(str_contains($api,"verify_csrf()"),'Both live endpoints require request forgery checks.');
$check(str_contains($api,"visitor_token_hash"),'Visitor credentials must be hashed before storing.');
$check(str_contains($api,"bin2hex(random_bytes(32))"),'Visitor sessions require cryptographically secure random tokens.');
$check(str_contains($api,"'REMOTE_ADDR'") && !str_contains($api,"'HTTP_X_FORWARDED_FOR'"),'IP metadata must come from socket, not user-supplied forwarded headers.');
$check(str_contains($api,"'/24'") && str_contains($api,"'/48'"),'IPv4 and IPv6 addresses must be masked.');
$check(str_contains($api,"'HTTP_CF_IPCOUNTRY'"),'Country location may be shown only as a hint if a CDN provides it.');
$check(str_contains($api,"last_seen_at >= DATE_SUB(NOW(), INTERVAL 70 SECOND)"),'Staff online status must expire without a heartbeat.');
$check(str_contains($api,"visitor_last_seen_at>=DATE_SUB(NOW(),INTERVAL 70 SECOND)"),'Visitor online status must expire without a heartbeat.');
$check(str_contains($api,"'staff'") && str_contains($api,"'visitor'"),'Backend needs both message directions.');
$check(str_contains($js,"liveApi('start'") && str_contains($js,"liveApi('send'") && str_contains($js,"liveApi('poll'"),'Public widget must send and receive live chat messages.');
$check(str_contains($widget,'data-live-endpoint=') && str_contains($widget,'iwAssistStartError'),'Public widget must expose chat endpoint and errors.');
$check(str_contains($staffJs,"setInterval(poll,5000)") && str_contains($staffJs,"Enable sound alerts"),'Staff inbox should poll with user-activated audio.');
$check(str_contains($staffJs,"AudioContext") && str_contains($staffJs,"document.visibilityState"),'Audio and presence polling must respect browser capabilities.');
$check(str_contains($staff,'iwLiveInbox') && str_contains($staff,'Live chat database is not installed'),'Admin should have a transparent migration prompt.');
foreach(['iw_live_threads','iw_live_messages','iw_live_staff_presence'] as $table) {
    $check(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table),'Fresh schema missing '.$table);
    $check(str_contains($deploy,$table),'Migration missing '.$table);
}
$check(str_contains($deploy,"'--check'") && str_contains($deploy,"'--apply'"),'Chat migration needs explicit check and apply modes.');
$check(str_contains($reviews,'job_review_five_point_score') && str_contains($reviews,'$rating * 2'),'New reviews must normalize while preserving historical database scale.');
$check(str_contains($client,'iw-review-emoji-options') && str_contains($job,'iw-review-emoji-options'),'Both completed-order views must show visual emoji choices.');
$check(str_contains($publicReviews,'job_review_five_point_score'),'Public reviews must use new five-point format.');
$check(str_contains($editorial,'Georgia') && str_contains($editorial,'iw-insights-grid'),'Editorial archive requires serif headings and compact grid.');
$check(!str_contains($home,'track—everything'),'Home process text should not have an em dash.');
echo "live_chat_emoji_editorial_contract_test passed\n";
