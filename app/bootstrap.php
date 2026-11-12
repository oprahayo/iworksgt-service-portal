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
    session_start();
}

if (PHP_SAPI !== 'cli') {
    // Only the read-only embedded login/register GET views may be framed by
    // our known public origins. All other routes remain SAMEORIGIN protected.
    $embedPage = (string) ($_GET['page'] ?? '');
    $isEmbeddedAuth = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
        && (string) ($_GET['embed'] ?? '') === '1'
        && in_array($embedPage, ['login', 'register'], true);
    if ($isEmbeddedAuth) {
        $allowedOrigins = ["'self'", 'https://portal.iworksgt.com', 'https://iworksgt.com', 'https://www.iworksgt.com'];
        $configuredPublic = trim((string) ($__appConfig['app']['public_base_url'] ?? ''));
        $scheme = strtolower((string) parse_url($configuredPublic, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($configuredPublic, PHP_URL_HOST));
        if (in_array($scheme, ['https', 'http'], true) && $host !== '' && preg_match('/^[a-z0-9.-]+$/', $host)) {
            $allowedOrigins[] = $scheme . '://' . $host;
        }
        header('Content-Security-Policy: frame-ancestors ' . implode(' ', array_unique($allowedOrigins)));
    } else {
        header('X-Frame-Options: SAMEORIGIN');
    }
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
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/domain.php';
require_once __DIR__ . '/job_reviews.php';
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
