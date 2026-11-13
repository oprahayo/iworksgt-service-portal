<?php
declare(strict_types=1);

/** Safe error response before bootstrap, after bootstrap and in webhook routes. */
function iworks_report_error(Throwable $error): string
{
    try {
        $id = strtoupper(bin2hex(random_bytes(6)));
    } catch (Throwable $rngError) {
        $id = strtoupper(substr(hash('sha256', (string) microtime(true)), 0, 12));
    }
    // Strictly metadata, never exception message, SQL, config, tokens, request
    // fields, stack arguments or customer information.
    error_log(json_encode([
        'service'=>'iworks', 'event'=>'application_error', 'ref'=>$id,
        'exception_type'=>get_class($error),
        'source'=>basename($error->getFile()), 'line'=>$error->getLine(),
    ], JSON_UNESCAPED_SLASHES));
    return $id;
}

function iworks_public_error_response(Throwable $error): void
{
    $id = iworks_report_error($error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Service temporarily unavailable - iWORKS GLOBAL</title>'
        . '<style>body{font:16px/1.7 system-ui,Arial,sans-serif;background:#f9fafb;color:#20242c;margin:0}'
        . 'main{max-width:620px;margin:11vh auto;padding:24px}'
        . 'a{color:#c45210}small{color:#6b7280}</style></head>'
        . '<body><main><h1>We could not complete your request</h1>'
        . '<p>Our service is temporarily unavailable. Please try again or contact our support team.</p>'
        . '<p><small>Reference: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</small></p>'
        . '<p><a href="https://iworksgt.com/contact">Contact support</a></p>'
        . '</main></body></html>';
}
