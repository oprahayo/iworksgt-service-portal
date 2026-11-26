<?php

declare(strict_types=1);

/** First-party raster logo generated from the approved current brand artwork. */
function iworks_email_logo_markup(): string
{
    return '<img src="https://portal.iworksgt.com/assets/branding/iworks-email-logo.png" width="270" alt="iWORKS GLOBAL" style="display:block;width:270px;max-width:100%;height:auto;border:0;color:#17212e;font-weight:700;font-size:20px;">';
}

/** All content is plain text unless constructed here. Only first-party URLs become actions. */
function iworks_email_safe_url(string $url): bool
{
    $p = parse_url($url);
    return is_array($p) && ($p['scheme'] ?? '') === 'https'
        && in_array(strtolower((string)($p['host'] ?? '')), ['iworksgt.com','www.iworksgt.com','portal.iworksgt.com'], true)
        && !isset($p['user']) && !isset($p['pass']) && !isset($p['port'])
        && !preg_match('/[\x00-\x20\x7f]/', $url);
}

function iworks_email_html(string $subject, string $plain): string
{
    $esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $title = trim($subject) ?: 'An update from iWorks Global';
    $category = 'ACCOUNT UPDATE';
    $security = (bool)preg_match('/\b(?:otp|verify your .*account|reset .*password|security code)\b/i', $title);
    if ($security) $category = 'ACCOUNT SECURITY';
    elseif (preg_match('/certificate|certification|academy|course|enrollment|assessment|exam|training/i', $title)) $category = 'iWORKS GLOBAL ACADEMY';
    elseif (preg_match('/payment|invoice|commission|withdrawal|payout|receipt/i', $title)) $category = 'BILLING & PAYMENTS';
    elseif (preg_match('/support|ticket|reply/i', $title)) $category = 'CUSTOMER SUPPORT';
    elseif (preg_match('/job|order|service|document/i', $title)) $category = 'SERVICE UPDATE';
    $body = '';
    $action = null;
    foreach (preg_split('/\r\n|\n|\r/', $plain) ?: [] as $line) {
        if (trim($line) === '') { $body .= '<div style="height:12px;line-height:12px;">&nbsp;</div>'; continue; }
        if ($security && preg_match('/^\s*Your OTP code is:\s*([0-9]{4,10})\s*$/iD', $line, $m)) {
            $body .= '<p style="margin:0 0 8px;">Your one-time code is:</p><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#fff4eb" style="background:#fff4eb;border:1px solid #ffd4b5;border-radius:8px;padding:20px;color:#17212e;font-size:32px;font-weight:bold;letter-spacing:7px;">'.$esc($m[1]).'</td></tr></table>';
            continue;
        }
        if (preg_match('~^\s*(?:(Open|View and reply|Verify|Download|Reset password):\s*)?(https://[^\s]+)\s*$~iD', $line, $m) && iworks_email_safe_url($m[2])) {
            $url = $m[2];
            $label = $m[1] ?: ($security && stripos($title,'reset')!==false ? 'Reset password' : 'Open portal');
            if (strcasecmp($label,'Open')===0) $label='Open portal';
            if ($action===null) $action=[$label,$url];
            $body .= '<p style="margin:0 0 8px;word-break:break-all;overflow-wrap:anywhere;font-size:13px;"><a style="color:#a94300;text-decoration:underline;" href="'.$esc($url).'">'.$esc($line).'</a></p>';
            continue;
        }
        $body .= '<p style="margin:0 0 8px;white-space:pre-wrap;overflow-wrap:anywhere;">'.$esc($line).'</p>';
    }
    $button = $action===null ? '' : '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0 12px;"><tr><td bgcolor="#b94a00" style="background:#b94a00;border-radius:6px;"><a href="'.$esc($action[1]).'" style="display:inline-block;padding:14px 24px;color:#ffffff;font-weight:bold;font-size:16px;text-decoration:none;border-radius:6px;">'.$esc($action[0]).'</a></td></tr></table>';
    return '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$esc($title).'</title>'
        .'<style>@media only screen and (max-width:620px){.iw-mail-shell{padding:12px 8px!important}.iw-mail-card{padding:24px 18px!important}.iw-mail-title{font-size:24px!important}}</style></head>'
        .'<body style="margin:0;padding:0;background:#f2f4f7;color:#344054;font-family:Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f4f7"><tr><td align="center" class="iw-mail-shell" style="padding:32px 16px;">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:600px;border:1px solid #e1e5eb;border-radius:10px;background:#ffffff;">'
        .'<tr><td height="5" bgcolor="#ff6600" style="background:#ff6600;border-radius:9px 9px 0 0;font-size:1px;">&nbsp;</td></tr>'
        .'<tr><td class="iw-mail-card" style="padding:34px 36px 14px;">'.iworks_email_logo_markup().'</td></tr>'
        .'<tr><td class="iw-mail-card" style="padding:14px 36px 30px;"><p style="margin:0 0 10px;font-size:11px;letter-spacing:1.5px;font-weight:bold;color:#a94300;">'.$esc($category).'</p>'
        .'<h1 class="iw-mail-title" style="font-size:28px;line-height:1.3;color:#17212e;margin:0 0 22px;">'.$esc($title).'</h1>'
        .'<div style="font-size:16px;line-height:1.65;color:#344054;">'.$body.'</div>'.$button
        .'<p style="margin:24px 0 0;font-size:14px;color:#475467;">The iWorks Global Team</p></td></tr>'
        .'<tr><td class="iw-mail-card" bgcolor="#fafafa" style="padding:20px 36px;border-top:1px solid #e4e7ec;font-size:13px;line-height:1.7;color:#667085;border-radius:0 0 9px 9px;">iWorks Global Limited<br><a href="https://iworksgt.com" style="color:#a94300;text-decoration:underline;">iworksgt.com</a> &nbsp;|&nbsp; <a href="https://portal.iworksgt.com" style="color:#a94300;text-decoration:underline;">Client portal</a><br>This is a service-related email from iWorks Global.</td></tr>'
        .'</table></td></tr></table></body></html>';
}

/** MIME alternative body, reusable inside certificate attachment messages. */
function iworks_email_alternative_body(string $boundary, string $plain, string $html): string
{
    return '--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
        .quoted_printable_encode($plain)."\r\n--".$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
        .quoted_printable_encode($html)."\r\n--".$boundary."--\r\n";
}


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

    $html = iworks_email_html('You have a new reply', $plain);

    return [
        'subject' => $subject,
        'plain' => $plain,
        'html' => $html,
        'url' => $replyUrl,
    ];
}

/**
 * Sends a true multipart/alternative message. The shared sender also uses this
 * transport for account security and transactional notifications.
 */
function iworks_send_html_email(string $to, string $subject, string $plain, string $html): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !email_enabled()) {
        return false;
    }

    $fromEmail = trim(setting('mail_from_email', 'no-reply@iworksgt.com'));
    $envelope = iworks_mail_envelope_parameters($fromEmail);
    if ($envelope === null) {
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

    $message = iworks_email_alternative_body($boundary, $plain, $html);

    // Preserve the existing operational log, without storing private ticket
    // replies or the entire HTML message in a world-readable log.
    log_email_message($to, $subject, '[branded multipart notification]');
    return @mail($to, $subject, $message, implode("\r\n", $headers), $envelope);
}
