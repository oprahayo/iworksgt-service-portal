<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'iWorks Global Business Support Portal',
        'brand_name' => getenv('APP_BRAND_NAME') ?: 'iWORKS GLOBAL',
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Africa/Lagos',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'iworksgt_portal',
        'user' => getenv('DB_USER') ?: 'iworksgt_portal_user',
        'pass' => getenv('DB_PASS') ?: 'change-this-password',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        // Set the same value on iworksgt.com and portal for signed login handoff.
        'sso_shared_secret' => getenv('SSO_SHARED_SECRET') ?: 'change-this-sso-secret',
    ],
    'files' => [
        'upload_dir' => __DIR__ . '/../storage/uploads',
        'max_size_bytes' => 20 * 1024 * 1024,
    ],
];
