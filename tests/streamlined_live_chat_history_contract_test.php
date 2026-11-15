<?php
declare(strict_types=1);
/**
 * No database/secrets required: structure, trust boundaries and a few pure
 * behavior checks covering low-friction 30-day chat plus homepage release.
 */
$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$check=static function(bool $ok,string $message):void {
    if (!$ok) throw new RuntimeException($message);
};
$widget=$read('views/partials/public-assistance.php');
$visitor=$read('assets/js/public-assistance.js');
$server=$read('app/live_chat.php');
$staff=$read('assets/js/live-inbox.js');
$staffView=$read('views/live-inbox.php');
$worker=$read('scripts/cron_support_chat.php');
$home=$read('views/public-home-clean.php');
$css=$read('assets/css/public-assistance.css');
$privacy=$read('views/public-legal-pages.php');
$footer=$read('views/partials/public-footer.php');
$header=$read('views/partials/public-header.php');
$installer=$read('scripts/deploy_chat_case_history_refresh.sh');

foreach(['id="iwAssistStartForm"','name="full_name"','name="email"',
    'data-assist-country-label','data-country="<?= e($iwChatCountryCode) ?>"',
    'Start conversation','How we use your details','30 days'] as $name){
    $check(str_contains($widget,$name),'First-contact form missing: '.$name);
}
foreach(['name="remember_chat"','name="notify_offline"','name="track_page"',
    '<select id="iwAssistCountry"','iwAssistPreferencesForm'] as $bad){
    $check(!str_contains($widget,$bad),'Unnecessary visitor choice still shown: '.$bad);
}
$check(str_contains($visitor,'String.fromCodePoint(')
    && str_contains($visitor,'data-assist-country-label')
    && !str_contains($visitor,'optedIn('),
    'Country flag must be read-only and checkbox logic must be removed.');
$check(str_contains($visitor,"liveApi('restart'")
    && str_contains($visitor,'conversation.hidden = !(active || done);')
    && str_contains($visitor,"session.status = 'ended'"),
    'Ended cases must retain history and start new topics without repeating identity.');
$check(str_contains($server,'$remember = $engagement;')
    && str_contains($server,'$notify = $engagement;')
    && str_contains($server,'$pageTracking = $engagement;'),
    'Required support behavior must come from server, not client checkbox values.');
$check(str_contains($server,'iw_chat_country_code();')
    && !str_contains($server,'iw_chat_country_code((string)'),
    'No client-forged country is allowed in the visitor start endpoint.');
$check(str_contains($server,"notify_roles(['admin','agent']")
    && str_contains($server,'New visitor support message')
    && str_contains($server,'New website support conversation'),
    'Agents and admins should receive email-outbox backed new-chat/message alerts.');
$check(str_contains($server,'iw_chat_close_and_queue_receipt')
    && str_contains($server,'Chat receipt:')
    && str_contains($server,'iWORKS Support conversation receipt #')
    && str_contains($server,'iw_chat_process_email_outbox'),
    'Each closed support case should enqueue a privacy-safe service email receipt.');
$check(str_contains($server,'if ($action === \'restart\')')
    && str_contains($server,"'reopen'")
    && str_contains($server,'New conversation started on')
    && str_contains($server,'Topic: '),
    'Both staff and visitors should be able to resume dated cases/topics.');
$check(str_contains($server,'DATE_SUB(NOW(),INTERVAL 30 DAY)'),
    'Visitor bearer tokens must not be valid beyond 30 days of inactivity.');
$check(str_contains($worker,"'--check'")
    && str_contains($worker,"'--run'")
    && str_contains($worker,'iworks_process_notification_email_outbox')
    && str_contains($worker,'iw_chat_process_email_outbox')
    && str_contains($worker,'DELETE FROM iw_live_threads')
    && str_contains($worker,'INTERVAL 30 DAY')
    && str_contains($worker,"PHP_SAPI !== 'cli'"),
    'The private scheduled worker must deliver both email queues and enforce 30-day expiry.');
$check(str_contains($staff,'Reopen case') && str_contains($staff,"api(reopening ? 'reopen' : 'close'")
    && str_contains($staff,'Case history'),
    'Staff need historic messages, close/reopen transitions and explicit system events.');
$check(str_contains($staffView,'30 days of inactivity'),
    'Staff notice must match the automated retention behavior.');
$check(str_contains($footer,'20261115-quickchat1')
    && str_contains($header,'20261115-quickchat1')
    && str_contains($staffView,'20261115-casehistory1'),
    'Cache refresh is needed for current mobile and operator browsers.');
$check(str_contains($privacy,'Website support chats are scheduled for removal 30 days')
    && str_contains($privacy,'first-party, HttpOnly 30-day support-return cookie'),
    'Privacy/Cookie policies must disclose the new defaults and records handling.');
$check(str_contains($css,'iw-assist-country-readonly')
    && str_contains($css,'iw-assist-short-notice'),
    'Compact automatic country should render on mobile and desktop.');
$check(substr_count($home,"public_url('services')")>=1
    && !str_contains($home,'Looking for something else?')
    && str_contains($home,'iw-clean-all-services'),
    'Keep one full service catalogue CTA; remove the duplicate.');
$check((str_contains($home,"iworks_editorial_cases(true, 5)")
        || str_contains($home,"iworks_editorial_cases(true, 6)"))
    && str_contains($home,'$caseScreenshotPath')
    && str_contains($home,'is_file(__DIR__'),
    'Case study carousel must use published projects and genuine preview images.');
$check(str_contains($installer,'check_remediation_release.php')
    && str_contains($installer,'rollback()')
    && str_contains($installer,'case-studies')
    && !str_contains($installer,'mysqldump'),
    'Deployment must preserve production safety gates, image assets and private file rollback.');
echo "streamlined_live_chat_history_contract_test passed\n";
