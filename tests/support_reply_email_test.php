<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function support_mail_check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$ticket = [
    'id' => 77541,
    'ticket_code' => 'SUP-20261113-A1B2',
    'client_name' => 'Opeyemi <script>alert(1)</script>',
    'subject' => 'Private subject that must not appear',
];
$staff = ['full_name' => 'Santhy <img src=x onerror=alert(1)>'];
$email = iworks_support_email_payload($ticket, $staff, ['full_name' => $ticket['client_name']]);

support_mail_check(str_contains($email['subject'], 'SUP-20261113-A1B2'), 'Subject must include ticket reference.');
support_mail_check(str_contains($email['plain'], 'View and reply: '), 'Plain-text fallback must contain a direct action URL.');
support_mail_check(str_contains($email['url'], 'page=support&ticket_id=77541'), 'Reply button must link to the exact support ticket.');
support_mail_check(str_contains($email['html'], 'page=support&amp;ticket_id=77541'), 'HTML reply URL must be escaped.');
support_mail_check(str_contains($email['html'], 'You have a new reply'), 'Reference layout heading must be present.');
support_mail_check(str_contains($email['html'], 'View and reply'), 'Reference call to action must be present.');
support_mail_check(str_contains($email['html'], '#ff6600'), 'Email must use approved orange accent.');
support_mail_check(str_contains($email['html'], 'iWORKSGLOBAL'), 'Email must use the current company identity.');
support_mail_check(!str_contains($email['html'], '<script>alert(1)</script>'), 'Client name must be HTML escaped.');
support_mail_check(!str_contains($email['html'], '<img src=x onerror=alert(1)>'), 'Agent name must be HTML escaped.');
support_mail_check(str_contains($email['html'], '&lt;img src=x onerror=alert(1)&gt;'), 'Agent name should still be readable when escaped.');
support_mail_check(!str_contains($email['html'], $ticket['subject']), 'Notification must not leak the ticket subject.');
support_mail_check(!str_contains($email['html'], 'Private ticket reply body'), 'Notification must not include ticket content.');
support_mail_check(iworks_send_html_email('client@example.com', 'test', 'plain', '<p>html</p>') === false, 'Disabled mail must never send.');

$anonymous = iworks_support_email_payload(['id' => 3, 'ticket_code' => 'SUP-3'], [], []);
support_mail_check(str_contains($anonymous['plain'], 'Hi there,'), 'Missing client name must have a safe fallback.');
support_mail_check(str_contains($anonymous['plain'], 'Our iWORKSGLOBAL Support team'), 'Missing staff name must have a safe fallback.');

// The same ticket event must continue to be one persisted dashboard
// notification, with a per-event HTML email override rather than a second
// notification or a second send.
$domainSource = file_get_contents(__DIR__ . '/../app/domain.php');
support_mail_check(str_contains($domainSource, '?array $emailOverride = null'), 'Notification must support optional HTML override.');
support_mail_check(str_contains($domainSource, 'iworks_support_email_payload($ticket, $sender,'), 'Staff replies must use branded template.');
support_mail_check(str_contains($domainSource, 'iworks_send_html_email('), 'Override must send multipart email.');
support_mail_check(str_contains($domainSource, 'send_email((string) $user[' . "'email'" . '], $title, $mailMessage)'), 'Generic notifications must retain existing plain-text behavior.');

echo "support_reply_email_test passed\n";
