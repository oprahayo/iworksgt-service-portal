<?php
declare(strict_types=1);
// Exercise real log_email_message without database or customer messages.
require __DIR__ . '/../app/helpers.php';
$base = sys_get_temp_dir() . '/iworks-f01-fixture-' . bin2hex(random_bytes(6));
mkdir($base, 0700);
putenv('IWORKS_PRIVATE_LOG_DIR=' . $base);
$secret = '123456 private reset token https://portal.example/reset?token=private';
$to = 'synthetic-client@example.invalid';
log_email_message($to, 'Verify your 123456 account', $secret);
$path = mail_log_path();
$raw = (string) file_get_contents($path);
$entry = json_decode($raw, true);
$ok = is_array($entry)
    && isset($entry['recipient_sha256'], $entry['subject_sha256'])
    && $entry['recipient_sha256'] === hash('sha256', $to)
    && !str_contains($raw, $to)
    && !str_contains($raw, '123456')
    && !str_contains($raw, 'token=private')
    && !str_contains($raw, 'Verify your')
    && !str_contains($path, '/public_html/')
    && (fileperms($path) & 0777) === 0600;
@unlink($path);
@rmdir($base);
if (!$ok) { throw new RuntimeException('Private metadata logging failed.'); }
echo "f01_mail_metadata_test passed\n";
