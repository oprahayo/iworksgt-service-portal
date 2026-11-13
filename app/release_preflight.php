<?php
declare(strict_types=1);

/**
 * Essential runtime schema required by this immutable release.
 * These are read-only checks; controlled CLI migrations bring old installs
 * into compliance before the site can be updated.
 */
function iworks_required_release_schema(): array
{
    return [
        'tables' => [
            'app_settings',
            'users',
            'services',
            'jobs',
            'invoices',
            'job_payments',
            'otp_codes',
            'auth_attempt_limits',
            'notification_email_outbox',
            'crypto_rates',
            'crypto_rate_snapshots',
            'job_reviews',
            'cac_filing_applications',
            'trademark_applications',
            'stripe_customer_vault',
            'media_library',
            'media_assets',
        ],
        'columns' => [
            ['jobs', 'managed_by_admin_id'],
            ['jobs', 'pay_currency'],
            ['jobs', 'pay_method'],
            ['jobs', 'fx_rate_used'],
            ['jobs', 'crypto_asset'],
            ['jobs', 'crypto_rate_used'],
            ['jobs', 'crypto_amount_due'],
            ['jobs', 'crypto_network'],
            ['jobs', 'rate_locked_at'],
            ['jobs', 'delivered_by'],
            ['jobs', 'accepted_by'],
            ['jobs', 'accepted_at'],
            ['invoices', 'pay_currency'],
            ['invoices', 'pay_method'],
            ['invoices', 'fx_rate_used'],
            ['invoices', 'crypto_asset'],
            ['invoices', 'crypto_rate_used'],
            ['invoices', 'crypto_amount_due'],
            ['invoices', 'crypto_network'],
            ['invoices', 'rate_locked_at'],
            ['job_payments', 'pay_currency'],
            ['job_payments', 'pay_method'],
            ['job_payments', 'fx_rate_used'],
            ['job_payments', 'crypto_asset'],
            ['job_payments', 'crypto_rate_used'],
            ['job_payments', 'crypto_amount_due'],
            ['job_payments', 'crypto_network'],
            ['job_payments', 'rate_locked_at'],
            ['services', 'category'],
            ['services', 'short_description'],
            ['services', 'is_public'],
            ['services', 'is_featured'],
            ['services', 'featured_bullets'],
            ['services', 'featured_image_id'],
            ['otp_codes', 'failed_attempts'],
            ['media_library', 'label'],
            ['media_assets', 'label'],
        ],
        'indexes' => [
            ['job_payments', 'uniq_gateway_provider_tx'],
            ['job_payments', 'uniq_gateway_provider_session'],
        ],
    ];
}
