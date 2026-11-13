<?php
declare(strict_types=1);

/** Immutable safety checks required before deploying the current app. */
function iworks_required_release_schema(): array
{
    return [
        'tables' => [
            'app_settings', 'otp_codes', 'auth_attempt_limits',
            'notification_email_outbox', 'job_payments', 'jobs',
        ],
        'columns' => [
            ['otp_codes','failed_attempts'],
            ['jobs','managed_by_admin_id'],
            ['job_payments','gateway_session_id'],
        ],
        'indexes' => [
            ['job_payments','uniq_gateway_provider_tx'],
            ['job_payments','uniq_gateway_provider_session'],
        ],
    ];
}
