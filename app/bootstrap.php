<?php

declare(strict_types=1);

$__configPath = __DIR__ . '/config.php';

// Keep local credentials out of source control while allowing a fresh clone
// to run setup checks and the test suite with safe placeholder values.
if (!is_file($__configPath)) {
    $__configPath = __DIR__ . '/config.example.php';
}

$__appConfig = require $__configPath;

if (!isset($__appConfig['app']['timezone'])) {
    $__appConfig['app']['timezone'] = 'UTC';
}

date_default_timezone_set($__appConfig['app']['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    // Must be configured BEFORE session_start(), including embedded auth,
    // OAuth and SSO entrypoints. Existing browser sessions without an auth
    // marker are invalidated after the corresponding security release.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    if (PHP_SAPI !== 'cli') {
        $host = strtolower((string) preg_replace('/:\\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !$local || $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

if (PHP_SAPI !== 'cli') {
    // All pages, including authentication, remain protected from framing.
    // The public account dialog now embeds forms directly in the page.
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function app_config(): array
{
    static $cfg = null;

    global $__appConfig;

    if ($cfg === null) {
        $cfg = $__appConfig;
    }

    return $cfg;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/support_email.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gateway_verification.php';
require_once __DIR__ . '/domain.php';
require_once __DIR__ . '/customer_service_policy.php';
require_once __DIR__ . '/live_chat.php';
require_once __DIR__ . '/editorial.php';
require_once __DIR__ . '/job_reviews.php';
require_once __DIR__ . '/public_seo.php';
require_once __DIR__ . '/public_marketing.php';
require_once __DIR__ . '/order_intent.php';
require_once __DIR__ . '/trademark_applications.php';
require_once __DIR__ . '/cac_registration_policy.php';
require_once __DIR__ . '/cac_filing.php';
require_once __DIR__ . '/stripe_card_vault.php';
require_once __DIR__ . '/client_workspace.php';
require_once __DIR__ . '/file_access.php';

if (is_configured()) {
    try {
        ensure_crypto_schema_upgrades();
        ensure_media_schema_upgrades();
        ensure_job_review_schema();
    } catch (Throwable $e) {
        // Ignore runtime schema patch failures in bootstrap.
    }
}
