<?php

declare(strict_types=1);

/** First-party raster logo generated from the approved current brand artwork. */
function iworks_email_logo_markup(): string
{
    return '<img src="https://portal.iworksgt.com/assets/branding/iworks-email-logo.png" width="240" alt="iWorks Global" style="display:block;margin:0 auto;width:240px;max-width:100%;height:auto;border:0;color:#17212e;font-size:18px;">';
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
    $security = (bool)preg_match('/\b(?:otp|verify your .*account|reset .*password|security code)\b/i', $title);
    $otp = $security && preg_match('/^\s*Your OTP code is:\s*([0-9]{4,10})\s*$/mi', $plain, $code);
    $reset = $security && stripos($title, 'reset') !== false;
    $support = (bool)preg_match('/support|ticket|reply/i', $title);
    $certificate = (bool)preg_match('/certificate/i', $title);
    $category = $security ? 'ACCOUNT SECURITY' : '';
    if (!$security && preg_match('/certificate|certification|academy|course|enrollment|assessment|exam|training/i', $title)) $category = 'iWORKS GLOBAL ACADEMY';
    elseif (!$security && preg_match('/payment|invoice|commission|withdrawal|payout|receipt/i', $title)) $category = 'BILLING & PAYMENTS';
    elseif (!$security && $support) $category = 'CUSTOMER SUPPORT';
    $body = '';
    $action = null;
    if ($otp) {
        $title = preg_match('/verify/i', $title) ? 'Verify your account' : 'Your verification code';
        $greeting = '';
        if (preg_match('/^Hello ([^\r\n]+),\s*$/mi', $plain, $greetingMatch)) {
            $greeting = '<p style="margin:0 0 14px;">Hello '.$esc($greetingMatch[1]).',</p>';
        }
        $expiry = '';
        if (preg_match('/^This code expires in [^\r\n]+$/mi', $plain, $expiryMatch)) {
            $expiry = '<p style="margin:14px 0 0;color:#667085;font-size:13px;">'.$esc(trim($expiryMatch[0])).'</p>';
        }
        $body = $greeting.'<p style="margin:0 0 22px;">Use this verification code to verify your account.</p>'
            .'<table role="presentation" align="center" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#fff4eb" style="background:#fff4eb;border:1px solid #ffd4b5;border-radius:8px;padding:18px 8px;">'
            .'<span style="font-family:Arial,Helvetica,sans-serif;color:#1d1d1f;font-size:32px;font-weight:700;letter-spacing:5px;white-space:nowrap;user-select:all;-webkit-user-select:all;">'.$esc($code[1]).'</span>'
            .'<p style="margin:8px 0 0;color:#667085;font-size:12px;line-height:1.4;">Copy code: select or press and hold</p></td></tr></table>'
            .$expiry.'<p style="margin:26px 0 0;color:#667085;font-size:13px;">If you did not request this code, ignore this email.</p>';
    } else {
        foreach (preg_split('/\r\n|\n|\r/', $plain) ?: [] as $line) {
            if (trim($line) === '') continue;
            if (preg_match('/^\s*(?:Warmly,|The iworks\s*global Team)\s*$/iD', $line)) continue;
            if (preg_match('~^\s*(?:(Open|View and reply|Verify|Download|Reset password):\s*)?(https://[^\s]+)\s*$~iD', $line, $m) && iworks_email_safe_url($m[2])) {
                // No redundant links or footer navigation. Only actions required
                // to complete password recovery, support replies or verification.
                if ($action===null && ($reset || $support || ($certificate && strcasecmp($m[1],'Verify')===0))) {
                    $label = $reset ? 'Reset password' : ($support ? 'View and reply' : 'Verify certificate');
                    $action=[$label,$m[2]];
                }
                continue;
            }
            $body .= '<p style="margin:0 0 14px;white-space:pre-wrap;overflow-wrap:anywhere;">'.$esc($line).'</p>';
        }
    }
    $button = $action===null ? '' : '<table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:22px auto 0;"><tr><td bgcolor="#b94a00" style="background:#b94a00;border-radius:8px;"><a href="'.$esc($action[1]).'" style="display:inline-block;padding:14px 26px;color:#ffffff;font-weight:bold;font-size:15px;text-decoration:none;">'.$esc($action[0]).'</a></td></tr></table>';
    return '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$esc($title).'</title>'
        .'<style>@media only screen and (max-width:620px){.iw-mail-shell{padding:12px 8px!important}.iw-mail-card{padding:24px 20px!important}.iw-mail-title{font-size:24px!important}}</style></head>'
        .'<body style="margin:0;padding:0;background:#f2f4f7;color:#1d1d1f;font-family:-apple-system,BlinkMacSystemFont,Arial,Helvetica,sans-serif;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f4f7"><tr><td align="center" class="iw-mail-shell" style="padding:24px 12px;">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:460px;border:1px solid #e1e5eb;border-radius:10px;background:#ffffff;">'
        .'<tr><td height="4" bgcolor="#ff6600" style="background:#ff6600;border-radius:9px 9px 0 0;font-size:1px;">&nbsp;</td></tr>'
        .'<tr><td align="center" class="iw-mail-card" style="padding:28px 26px 12px;">'.iworks_email_logo_markup().'</td></tr>'
        .'<tr><td align="center" class="iw-mail-card" style="padding:12px 26px 26px;text-align:center;">'
        .($category === '' ? '' : '<p style="margin:0 0 10px;font-size:11px;letter-spacing:1.2px;font-weight:bold;color:#a94300;">'.$esc($category).'</p>')
        .'<h1 class="iw-mail-title" style="font-size:26px;line-height:1.25;letter-spacing:-.5px;color:#1d1d1f;margin:0 0 20px;">'.$esc($title).'</h1>'
        .'<div style="font-size:16px;line-height:1.6;color:#424245;text-align:center;">'.$body.'</div>'.$button
        .'</td></tr>'
        .'<tr><td align="center" style="padding:14px 20px;border-top:1px solid #ececef;font-size:12px;line-height:1.6;color:#86868b;">&copy; iWorks Global</td></tr>'
        .'</table></td></tr></table></body></html>';
}

/** Local approved PNG, never fetched from the network during mail delivery. */
function iworks_email_logo_bytes(): ?string
{
    $roots=[dirname(__DIR__), dirname(__DIR__).'/portal.iworksgt.com'];
    foreach ($roots as $root) {
        $path=$root.'/assets/branding/iworks-email-logo.png';
        if (!is_readable($path) || !is_file($path) || filesize($path)>524288) continue;
        $data=file_get_contents($path);
        if (is_string($data) && str_starts_with($data,"\x89PNG\r\n\x1a\n")) return $data;
    }
    return null;
}

/** MIME alternative with a related, inline PNG in the HTML branch.
 * Preserve the plain fallback and existing attachment-safe API.
 */
function iworks_email_alternative_body(string $boundary, string $plain, string $html, ?string $logo = null): string
{
    $result='--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
        .quoted_printable_encode($plain)."\r\n--".$boundary."\r\n";
    $remote='https://portal.iworksgt.com/assets/branding/iworks-email-logo.png';
    if (str_contains($html,$remote)) {
        $logo=$logo??iworks_email_logo_bytes();
        if ($logo!==null && str_starts_with($logo,"\x89PNG\r\n\x1a\n")) {
            $related='iworks-logo-'.bin2hex(random_bytes(16));
            $cid='iworks-logo-'.bin2hex(random_bytes(12)).'@iworksgt.com';
            $html=str_replace($remote,'cid:'.$cid,$html);
            $result.='Content-Type: multipart/related; type="text/html"; boundary="'.$related."\"\r\n\r\n"
                .'--'.$related."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
                .quoted_printable_encode($html)."\r\n--".$related."\r\nContent-Type: image/png; name=\"iworks-email-logo.png\"\r\n"
                ."Content-Transfer-Encoding: base64\r\nContent-ID: <".$cid.">\r\nContent-Disposition: inline; filename=\"iworks-email-logo.png\"\r\n\r\n"
                .chunk_split(base64_encode($logo)).'--'.$related."--\r\n";
            return $result.'--'.$boundary."--\r\n";
        }
    }
    return $result."Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
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
