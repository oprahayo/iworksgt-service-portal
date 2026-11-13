<?php
declare(strict_types=1);

/**
 * DB-backed sliding-window buckets; no raw emails or IP addresses stored.
 * All callers fail closed if the mandatory migration is unavailable.
 */
function iworks_auth_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function iworks_auth_bucket(string $scope, string $actor, int $seconds): array
{
    $allowed = ['password_actor', 'password_ip', 'otp_ip', 'otp_request', 'reset_actor', 'reset_ip'];
    if (!in_array($scope, $allowed, true)) {
        throw new InvalidArgumentException('Unrecognized authorization limit.');
    }
    $seconds = max(60, min(86400, $seconds));
    return [
        'scope' => $scope,
        'subject_hash' => hash('sha256', $actor),
        'window_started_at' => intdiv(time(), $seconds) * $seconds,
    ];
}

function iworks_auth_limit_allows(string $scope, string $actor, int $limit, int $seconds): bool
{
    $key = iworks_auth_bucket($scope, $actor, $seconds);
    $row = db_one(
        'SELECT attempts FROM auth_attempt_limits
         WHERE scope = :scope AND subject_hash = :subject_hash
           AND window_started_at = :window_started_at LIMIT 1',
        $key
    );
    return (int) ($row['attempts'] ?? 0) < max(1, $limit);
}

function iworks_auth_limit_record(string $scope, string $actor, int $seconds): void
{
    $key = iworks_auth_bucket($scope, $actor, $seconds);
    db_execute(
        'INSERT INTO auth_attempt_limits (scope, subject_hash, window_started_at, attempts)
         VALUES (:scope, :subject_hash, :window_started_at, 1)
         ON DUPLICATE KEY UPDATE attempts = attempts + 1',
        $key
    );
}

function iworks_auth_actor(string $identifier): string
{
    return strtolower(trim($identifier)) . '|' . iworks_auth_ip();
}
