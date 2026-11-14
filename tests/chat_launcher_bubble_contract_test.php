<?php
declare(strict_types=1);
// The launcher is a visual-only change; interaction and CSRF still use the
// existing support widget controller and backend handlers.
$root = dirname(__DIR__);
$widget = (string) file_get_contents($root . '/views/partials/public-assistance.php');
$css = (string) file_get_contents($root . '/assets/css/public-assistance.css');
$header = (string) file_get_contents($root . '/views/partials/public-header.php');
$js = (string) file_get_contents($root . '/assets/js/public-assistance.js');
$installer = (string) file_get_contents($root . '/scripts/deploy_chat_launcher_bubble.sh');
function chat_bubble_check(bool $ok,string $msg):void { if (!$ok) throw new RuntimeException($msg); }
chat_bubble_check(str_contains($widget, 'class="iw-assist-launcher" data-assist-chat-open')
    && str_contains($widget, 'aria-controls="iwAssistChat"') && str_contains($widget, 'aria-label="Open iWorks support"'),
    'Existing launcher interaction and accessible label must remain.');
chat_bubble_check(str_contains($widget, '<svg class="iw-assist-launcher-icon"')
    && str_contains($widget, 'viewBox="0 0 96 96"')
    && substr_count($widget, '<circle cx=') === 3,
    'Custom vector bubble must include exactly three visible white dots.');
chat_bubble_check(str_contains($widget, 'fill="currentColor"')
    && str_contains($widget, 'fill="#fff"')
    && str_contains($widget, 'l5 17-24-10'),
    'Speech bubble must be orange with a bottom-right tail and white dots.');
chat_bubble_check(!str_contains($widget, 'class="bi bi-chat-dots-fill"'),
    'Do not restore legacy circular badge icon.');
chat_bubble_check(str_contains($css, 'background:transparent!important')
    && str_contains($css, '.iw-assist-launcher-icon{display:block;width:74px;height:74px')
    && str_contains($css, '.iw-assist-launcher-icon{width:70px;height:70px'),
    'Transparent outer hit area and prominent mobile bubble required.');
chat_bubble_check(str_contains($css, '.iw-assist-launcher:hover{background:transparent!important'),
    'Hover must not repaint outer circle.');
chat_bubble_check(str_contains($css, 'prefers-reduced-motion:reduce')
    && str_contains($css, 'outline:3px solid #f8ba8c'),
    'Retain reduced-motion behavior and keyboard focus ring.');
chat_bubble_check(str_contains($header, '20261114-compact2'),
    'Fallback CSS must have fresh cache revision.');
chat_bubble_check(str_contains($js, 'launcher.hidden = true')
    && str_contains($js, 'launcher.hidden = false')
    && str_contains($js, "root.querySelector('.iw-assist-launcher')"),
    'Existing support open/close workflow must remain unchanged.');
chat_bubble_check(str_contains($installer, 'check_remediation_release.php')
    && str_contains($installer, 'portal.tgz')
    && str_contains($installer, 'rollback()'),
    'Minimal cPanel installer must have safety gate and rollback.');
chat_bubble_check(!str_contains($installer, 'mysqldump')
    && !str_contains($installer, 'DROP DATABASE'),
    'Launcher changes must never alter the database.');
echo "chat_launcher_bubble_contract_test passed\n";
