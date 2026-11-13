<?php
declare(strict_types=1);

/**
 * Offline validation for production fail-closed release gate.
 * It intentionally does not need a database or consume private credentials.
 */
require __DIR__ . '/../app/release_preflight.php';
function check_gate(bool $ok, string $reason): void
{
    if (!$ok) { throw new RuntimeException($reason); }
}
$schema = iworks_required_release_schema();
check_gate(in_array('otp_codes', $schema['tables'], true)
    && in_array('auth_attempt_limits', $schema['tables'], true)
    && in_array('notification_email_outbox', $schema['tables'], true),
    'Release must require installed OTP and notification schemas.');
foreach ([
    ['otp_codes', 'failed_attempts'],
    ['jobs', 'managed_by_admin_id'],
    ['job_payments', 'gateway_session_id'],
] as $required) {
    check_gate(in_array($required, $schema['columns'], true),
        'Missing critical access, OTP, or payment schema requirement.');
}
foreach ([
    ['job_payments', 'uniq_gateway_provider_tx'],
    ['job_payments', 'uniq_gateway_provider_session'],
] as $required) {
    check_gate(in_array($required, $schema['indexes'], true),
        'Payment transaction/session uniqueness must be a release prerequisite.');
}
$script = file_get_contents(__DIR__ . '/../scripts/check_remediation_release.php');
$deploy = file_get_contents(__DIR__ . '/../scripts/deploy_admin_mobile_refresh.sh');
check_gate(str_contains($script, "WHERE table_schema = DATABASE()")
    && str_contains($script, "'transport-enabled'")
    && str_contains($script, "'sender-email-valid'")
    && str_contains($script, "BEGIN IWORKS PRIVATE FILE PROTECTION")
    && str_contains($script, "'BLOCK'")
    && str_contains($script, "exit(3)"),
    'Read-only preflight must fail on schema, mail and private HTTP protections.');
check_gate(str_contains($deploy, 'if [[ "$MODE" == "--check" ]]')
    && str_contains($deploy, '$SRC/scripts/check_remediation_release.php')
    && str_contains($deploy, 'Release safety preflight blocked deployment')
    && str_contains($deploy, 'OLD_UMASK='),
    'Immutable installer must check release prerequisites before copying.');
$gatePosition = strpos($deploy, '"$PHP_BIN" "$SRC/scripts/check_remediation_release.php"');
$backupPosition = strpos($deploy, 'OLD_UMASK=');
check_gate($gatePosition !== false && $backupPosition !== false
    && $gatePosition < $backupPosition,
    'Release preflight must execute BEFORE backup and any source writes.');
$handoff = file_get_contents(__DIR__ . '/../docs/remediation/F01-F22-2026-11-13.md');
for ($i = 1; $i <= 22; $i++) {
    check_gate(str_contains($handoff, '| F' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' |'),
        'Remediation ledger missing F' . $i);
}
check_gate(str_contains($handoff, 'no cPanel access') || str_contains($handoff, 'No live shell'),
    'Handoff must clearly disclose live deployment access blocker.');
echo "remediation_release_gate_test passed\n";
