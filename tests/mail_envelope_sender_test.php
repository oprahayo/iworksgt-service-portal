<?php
declare(strict_types=1);

// Exercise PHP's real mail() boundary using a local capture transport.
// No network delivery, application database, or customer account is used.
function app_config(): array { return ['app'=>['name'=>'iWORKS GLOBAL']]; }
require __DIR__.'/../app/helpers.php';
require __DIR__.'/../app/support_email.php';

function envelope_check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

foreach (['no-reply@iworksgt.com','test+deployment@example.com'] as $sender) {
    envelope_check(iworks_mail_envelope_parameters($sender) === '-f'.$sender, 'Valid sender rejected.');
}
foreach (["a@example.com\r\nBcc: other@example.com",'a@example.com -X/tmp/log',
    '"a b"@example.com','-option@example.com','a$(id)@example.com','bad-address'] as $sender) {
    envelope_check(iworks_mail_envelope_parameters($sender) === null, 'Unsafe sender accepted.');
}

if (($argv[1] ?? '') === '--capture-child') {
    $GLOBALS['__settings_cache'] = [
        'mail_enabled'=>'1', 'mail_from_email'=>'no-reply@iworksgt.com',
        'mail_from_name'=>'iWORKS GLOBAL',
    ];
    envelope_check(send_email('synthetic@example.invalid','Plain test','Synthetic body'), 'Plain transport failed.');
    envelope_check(iworks_send_html_email('synthetic@example.invalid','HTML test','Synthetic body','<p>Synthetic body</p>'), 'HTML transport failed.');
    $GLOBALS['__settings_cache']['mail_from_email'] = 'a@example.com -X/tmp/log';
    envelope_check(!send_email('synthetic@example.invalid','Unsafe test','body'), 'Plain path accepted unsafe sender.');
    envelope_check(!iworks_send_html_email('synthetic@example.invalid','Unsafe test','body','<p>body</p>'), 'HTML path accepted unsafe sender.');
    exit(0);
}

$temporaryBase = getenv('IWORKS_TEST_TMPDIR') ?: sys_get_temp_dir();
$root = rtrim($temporaryBase,'/').'/iworks-mail-envelope-'.bin2hex(random_bytes(6));
mkdir($root,0700);
$transport = $root.'/sendmail';
file_put_contents($transport, '#!/bin/sh'."\n".'printf "%s\\n" "$@" >> "$IWORKS_TEST_MAIL_CAPTURE"'."\n".'cat >/dev/null'."\n");
chmod($transport,0700);
putenv('IWORKS_TEST_MAIL_CAPTURE='.$root.'/args.txt');
putenv('IWORKS_PRIVATE_LOG_DIR='.$root);
$originalDirectory=getcwd();
try {
    chdir($root);
    $_SERVER['DOCUMENT_ROOT']='';
    envelope_check(mail_log_path() === $root.'/mail-events.log', 'Empty CLI document root must not reject a private log.');
    $process = proc_open([
        PHP_BINARY,'-n','-d','display_errors=stderr','-d','sendmail_path='.escapeshellarg($transport).' -t -i',
        __FILE__,'--capture-child',
    ],[0=>['file','/dev/null','r'],1=>['file',$root.'/stdout.txt','w'],2=>['file',$root.'/stderr.txt','w']],$pipes);
    envelope_check(is_resource($process) && proc_close($process) === 0,
        'Capture child failed: '.(string)file_get_contents($root.'/stderr.txt'));
    $arguments = file($root.'/args.txt',FILE_IGNORE_NEW_LINES);
    envelope_check(count(array_filter($arguments,fn($arg)=>$arg==='-fno-reply@iworksgt.com')) === 2,
        'Both mail paths must pass the aligned envelope sender: '.json_encode($arguments));
    envelope_check(count($arguments) === 6, 'Unexpected transport call or argument.');
} finally {
    chdir($originalDirectory);
    foreach (glob($root.'/*') as $file) unlink($file);
    rmdir($root);
}
echo "mail_envelope_sender_test passed\n";
