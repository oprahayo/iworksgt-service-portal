<?php
declare(strict_types=1);
/**
 * Visitor continuity, privacy, dashboard and email queue contract.
 * Calls pure privacy helpers to verify real data minimization and safe tokens.
 */
require dirname(__DIR__).'/app/live_chat.php';
$GLOBALS['iw_test_sql_ops'] = [];
function schema_table_exists(string $table): bool { return true; }
function db_execute(string $sql, array $params = []): bool {
    $GLOBALS['iw_test_sql_ops'][] = ['sql'=>$sql,'params'=>$params];
    return true;
}
$root=dirname(__DIR__);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$ok=static function(bool $value,string $why):void {
    if (!$value) throw new RuntimeException($why);
};
$ok(iw_chat_safe_page('/services/company-registration?secret=abc')==='/services/company-registration',
    'Never include query strings in staff page monitoring.');
$ok(iw_chat_safe_page('/blog/post-title')==='/blog/post-title',
    'Known public editorial pages can be described without full browser history.');
$ok(iw_chat_safe_page('/customer/afolayan?token=private')===null,
    'Reject account pages and unrecognized private URLs.');
$ok(iw_chat_safe_page('/reset-password/token123')===null,
    'Never record account recovery URLs.');
$_SERVER['REMOTE_ADDR']='198.51.100.129';
$ok(iw_chat_ip_prefix()==='198.51.100.0/24',
    'Never store raw IPv4 visitor IP in chat.');
$_SERVER['REMOTE_ADDR']='2001:db8:1234:5678:9999::11';
$ok(str_ends_with(iw_chat_ip_prefix(),'/48') && !str_contains(iw_chat_ip_prefix(),'5678'),
    'IPv6 storage must mask at least the last 80 bits.');
// An optional country selection can always be supplied without an external IP service.
unset($_SERVER['GEOIP_COUNTRY_CODE'], $_SERVER['MM_COUNTRY_CODE'], $_SERVER['HTTP_CF_IPCOUNTRY']);
$_SERVER['REMOTE_ADDR']='invalid address';
$ok(iw_chat_country_code('ng')===null, 'Client supplied country must not replace server geography.');
$ok(iw_chat_country_code('XX')===null, 'Unknown country must not be fabricated.');
$_SERVER['GEOIP_COUNTRY_CODE']='ES';
$ok(iw_chat_country_code()==='ES', 'Server-local GeoIP metadata should supply country when present.');
$ok(iw_chat_country_code('NG')==='ES', 'Trusted network country takes precedence over client input.');
unset($_SERVER['GEOIP_COUNTRY_CODE']);
$_COOKIE[iw_chat_resume_cookie_name()]=str_repeat('a',64);
$ok(iw_chat_resume_cookie_token()===str_repeat('a',64),
    'Valid opaque chat cookie should allow safe lookup.');
$_COOKIE[iw_chat_resume_cookie_name()]='malicious-cookie';
$ok(iw_chat_resume_cookie_token()==='',
    'Invalid or short-lived-tampered cookie must be rejected.');
$recentVisitor = [
    'id'=>17,'notify_offline'=>1,'email'=>'visitor@example.com',
    'visitor_last_seen_at'=>date('Y-m-d H:i:s'),
];
iw_chat_enqueue_offline_reply($recentVisitor,42);
$ok(count($GLOBALS['iw_test_sql_ops'])===1
    && str_contains($GLOBALS['iw_test_sql_ops'][0]['sql'],'INSERT IGNORE INTO iw_live_email_outbox')
    && strtotime((string)$GLOBALS['iw_test_sql_ops'][0]['params']['next']) >= time()+70,
    'An opted-in visitor who just left must not miss a delayed offline email queue.');
$recentVisitor['notify_offline']=0;
iw_chat_enqueue_offline_reply($recentVisitor,43);
$ok(count($GLOBALS['iw_test_sql_ops'])===1,
    'Opted-out visitors must never be queued for offline email.');


$server=$read('app/live_chat.php');
$migration=$read('scripts/migrate_live_chat_engagement.php');
$sql=$read('database.sql');
$widget=$read('views/partials/public-assistance.php');
$client=$read('assets/js/public-assistance.js');
$staff=$read('assets/js/live-inbox.js');
$worker=$read('scripts/run_scheduled_operations.php');
$dashboard=$read('views/dashboard.php');
$summary=$read('views/partials/live-visitor-overview.php');
$dashboardJs=$read('assets/js/dashboard-live-visitors.js');
$inbox=$read('views/live-inbox.php');

$ok(str_contains($server,'function iw_chat_resume_cookie_name()')
    && str_contains($server,'httponly') && str_contains($server,'samesite')
    && str_contains($server,'30 * 86400')
    && str_contains($server,"$"."action === 'resume'"),
    'Returning browser conversation cookie must be HttpOnly and bounded to 30 days.');
$ok(str_contains($server,'visitor_token_hash=:hash')
    && str_contains($server,"hash('sha256', $"."token)"),
    'Resume credentials must be validated against hashed server-side tokens.');
$ok(str_contains($server,"$"."action === 'resume'")
    && str_contains($server,"$"."savedToken=iw_chat_resume_cookie_token()")
    && str_contains($client,"liveApi('resume'"),
    'Returning visitors must actually fetch persisted thread messages.');
$ok(str_contains($server,'visitor_last_read_message_id')
    && str_contains($client,'iwAssistUnread')
    && str_contains($client,'updateUnread(data.unread)'),
    'Customer should find unread staff replies without opening a new chat.');
$ok(!str_contains($widget,'name="notify_offline"')
    && !str_contains($widget,'name="remember_chat"')
    && !str_contains($widget,'name="track_page"')
    && str_contains($widget,'How we use your details'),
    'Standard support follow-up is disclosed without three distracting checkbox controls.');
$ok(str_contains($server,"$"."action === 'preferences'")
    && str_contains($server,'function iw_chat_preferences(')
    && str_contains($server,'standard support-follow-up workflow')
    && !str_contains($widget,'id="iwAssistPreferencesForm"')
    && str_contains($client,"liveApi('restart'"),
    'Legacy preference requests cannot disable required support messages; visitors can start new topics.');
$ok(str_contains($widget,'data-assist-country-label')
    && str_contains($client,'Intl.DisplayNames')
    && str_contains($server,"$"."countryCode = iw_chat_country_code();")
    && str_contains($server,"'country_code'=>(string)($"."countryCode ?? '')")
    && str_contains($server,"'country'=>$"."countryCode")
    && !str_contains($server,'HTTP_X_FORWARDED_FOR')
    && !str_contains($server,'ip-api.com'),
    'Country must be a read-only approximate server-derived hint.');
$ok(str_contains($server,"'token'=>$"."savedToken")
    && str_contains($client,'session.token = data.token')
    && str_contains($server,"'preferences'=>iw_chat_preferences")
    && str_contains($client,'applyPreferences(data.preferences)'),
    'Browser-returning visitors must retain chat access and see actual saved settings.');
$ok(str_contains($server,'$notify = $engagement;')
    && str_contains($server,'iw_chat_enqueue_offline_reply(')
    && str_contains($server,'INSERT IGNORE INTO iw_live_email_outbox')
    && str_contains($worker,'iw_chat_process_email_outbox(15)')
    && str_contains($server,'New reply from iWORKS Support')
    && str_contains($server,'iWORKS Support conversation receipt #')
    && str_contains($server,'INTERVAL 15 MINUTE'),
    'Initiated support chats need automatic, durable service emails and closure receipts.');
$ok(!str_contains($server,'HTTP_X_FORWARDED_FOR')
    && str_contains($server,'iw_chat_safe_page(')
    && str_contains($server,'current_page=CASE WHEN track_page=1')
    && str_contains($inbox,'Current public page paths are recorded'),
    'Only a requested support chat may include a whitelisted public page path; never raw IP or full history.');
$ok(str_contains($client,"pageSuggestion()") && str_contains($widget,'AUTOMATED SUGGESTION')
    && str_contains($client,'nudge-dismissed')
    && str_contains($client,'120000 -'),
    'Context-aware prompts must be disclosed, dismissible and not overly frequent.');
$ok(str_contains($dashboard,'live-visitor-overview.php')
    && str_contains($dashboard,'has_role([')
    && str_contains($summary,'data-live-metric="online"')
    && str_contains($summary,'iwLiveOverviewBars')
    && str_contains($dashboardJs,"fields.set('action','summary')"),
    'Authorized staff dashboard must show graphical live chat activity near the top.');
$ok(str_contains($staff,'thread.current_page')
    && str_contains($staff,'Offline reply and closure emails')
    && str_contains($inbox,'iwLiveActivityBars'),
    'Staff inbox must show current public page, transactional email notices and activity.');
$ok(str_contains($migration,'--backup-confirmed') && str_contains($migration,'information_schema.COLUMNS')
    && str_contains($migration,'CREATE TABLE IF NOT EXISTS iw_live_email_outbox')
    && str_contains($sql,'visitor_last_read_message_id BIGINT UNSIGNED'),
    'Additive schema updates must require verified backup and never delete existing chat history.');
echo "visitor_engagement_contract_test passed\n";
