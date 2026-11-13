<?php

declare(strict_types=1);

/**
 * Transactional client support email. All variable content is escaped and the
 * approved logo is pulled from the existing branding setting, never recreated.
 */
function iworks_support_email_payload(array $ticket, array $agent, array $client): array
{
    $clientName = trim((string) ($client['full_name'] ?? $ticket['client_name'] ?? ''));
    $firstName = $clientName !== '' ? (string) (preg_split('/\s+/u', $clientName)[0] ?? '') : 'there';
    $agentName = trim((string) ($agent['full_name'] ?? ''));
    $ticketCode = trim((string) ($ticket['ticket_code'] ?? ''));
    $ticketCode = $ticketCode !== '' ? $ticketCode : '#' . (int) ($ticket['id'] ?? 0);
    $ticketId = (int) ($ticket['id'] ?? 0);

    // Always use the authenticated portal route. Never use a URL supplied by
    // either the ticket subject or the incoming reply message.
    $replyUrl = portal_absolute_url(url('support', ['ticket_id' => $ticketId]));
    $replyLine = $agentName !== ''
        ? $agentName . ' from iWORKSGLOBAL Support replied to your request'
        : 'Our iWORKSGLOBAL Support team replied to your request';
    $ticketLabel = 'ticket ' . $ticketCode;
    $greeting = 'Hi ' . $firstName . ',';
    $lead = $replyLine . ' (' . $ticketLabel . ').';
    $subject = 'You have a new reply - ' . $ticketCode . ' | iWORKSGLOBAL';

    $plain = $greeting . "\n\n"
        . $lead . "\n\n"
        . "Open it in your dashboard to read the full message and reply.\n\n"
        . 'View and reply: ' . $replyUrl . "\n\n"
        . "Warmly,\nThe iWORKSGLOBAL Team";

    $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeReplyUrl = $esc($replyUrl);
    $supportAddress = trim(setting('mail_from_email', 'no-reply@iworksgt.com'));
    if (!filter_var($supportAddress, FILTER_VALIDATE_EMAIL)) {
        $supportAddress = 'no-reply@iworksgt.com';
    }
    $safeSupportAddress = $esc($supportAddress);
    $company = trim(company_legal_name()) ?: 'iWorks Global Limited';

    // Only embed a first-party HTTPS logo. Email clients will otherwise show
    // the typographic wordmark; no substitute or invented logo artwork.
    $rawLogoPath = ltrim(trim(setting('logo_path', '')), '/');
    $logo = preg_match('~^assets/uploads/branding/[A-Za-z0-9_.-]+\\.(?:png|jpe?g|gif|webp)$~i', $rawLogoPath)
        ? portal_absolute_url($rawLogoPath)
        : trim(logo_path());
    $logoHost = strtolower((string) parse_url($logo, PHP_URL_HOST));
    $allowedHosts = array_filter([
        strtolower((string) parse_url(public_base_url(), PHP_URL_HOST)),
        strtolower((string) parse_url(portal_base_url(), PHP_URL_HOST)),
    ]);
    $logoMarkup = $logo !== ''
        && strtolower((string) parse_url($logo, PHP_URL_SCHEME)) === 'https'
        && $logoHost !== ''
        && in_array($logoHost, $allowedHosts, true)
            ? '<img src="' . $esc($logo) . '" width="205" alt="iWORKSGLOBAL" style="max-width:205px;width:100%;height:auto;display:block;border:0;">'
            : '<span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;letter-spacing:1px;font-weight:800;color:#ff6600;">iWORKS<span style="font-weight:400;">GLOBAL</span></span>';

    $html = '<!doctype html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>You have a new reply</title>'
        . '<style>@media only screen and (max-width:620px){.iw-mail-shell{padding:16px 10px!important}.iw-mail-card{padding:28px 23px!important}.iw-mail-title{font-size:26px!important}.iw-mail-copy{font-size:16px!important}}</style>'
        . '</head><body style="margin:0;padding:0;background:#f3f5f9;color:#111827;font-family:Arial,Helvetica,sans-serif;">'
        . '<div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">'
        . $esc('iWORKSGLOBAL Support replied to ' . $ticketLabel . '.')
        . '</div>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background:#f3f5f9;border-collapse:collapse;">'
        . '<tr><td class="iw-mail-shell" align="center" style="padding:36px 16px 16px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:#ffffff;border:1px solid #e4e7ec;border-radius:7px;border-collapse:separate;">'
        . '<tr><td class="iw-mail-card" style="padding:42px 44px 44px;">'
        . '<div style="margin:0 0 39px;">' . $logoMarkup . '</div>'
        . '<h1 class="iw-mail-title" style="margin:0 0 24px;font-size:30px;line-height:1.25;letter-spacing:-.8px;font-weight:700;color:#101828;">You have a new reply</h1>'
        . '<p class="iw-mail-copy" style="margin:0 0 23px;color:#344054;font-size:17px;line-height:1.65;">'
        . $esc($greeting) . ' ' . $esc($lead) . '</p>'
        . '<p class="iw-mail-copy" style="margin:0 0 28px;color:#344054;font-size:17px;line-height:1.65;">'
        . 'Open it in your dashboard to read the full message and reply.</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#ff6600" style="border-radius:5px;background:#ff6600;">'
        . '<a href="' . $safeReplyUrl . '" style="display:inline-block;padding:15px 25px;border-radius:5px;background:#ff6600;color:#ffffff;font-size:17px;font-weight:700;line-height:1.3;text-decoration:none;">View and reply</a>'
        . '</td></tr></table>'
        . '<p style="margin:36px 0 0;color:#344054;font-size:16px;line-height:1.65;">Warmly,<br><strong style="color:#101828;">The iWORKSGLOBAL Team</strong></p>'
        . '</td></tr></table>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;border-collapse:collapse;">'
        . '<tr><td style="padding:22px 9px 26px;color:#8993a3;font-size:13px;line-height:1.8;">'
        . 'Sent with care from iWORKSGLOBAL.<br>'
        . $esc($company) . '<br>'
        . '<a href="mailto:' . $safeSupportAddress . '" style="color:#667085;text-decoration:underline;">Contact our support team</a>'
        . '</td></tr></table>'
        . '</td></tr></table></body></html>';

    return [
        'subject' => $subject,
        'plain' => $plain,
        'html' => $html,
        'url' => $replyUrl,
    ];
}

/**
 * Sends a true multipart/alternative message. Existing password-reset and
 * OTP email delivery stays on the established plain-text send_email() path.
 */
function iworks_send_html_email(string $to, string $subject, string $plain, string $html): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !email_enabled()) {
        return false;
    }

    $fromEmail = trim(setting('mail_from_email', 'no-reply@iworksgt.com'));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromName = trim(setting('mail_from_name', app_name()));
    $fromName = trim(str_replace(["\r", "\n"], ' ', $fromName));
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $boundary = 'iworks_' . bin2hex(random_bytes(16));
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'X-Auto-Response-Suppress: All',
    ];

    $message = '--' . $boundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . quoted_printable_encode($plain) . "\r\n"
        . '--' . $boundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . quoted_printable_encode($html) . "\r\n"
        . '--' . $boundary . "--\r\n";

    // Preserve the existing operational log, without storing private ticket
    // replies or the entire HTML message in a world-readable log.
    log_email_message($to, $subject, '[branded multipart support notification]');
    return @mail($to, $subject, $message, implode("\r\n", $headers));
}
