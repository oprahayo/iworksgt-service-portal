<?php
declare(strict_types=1);

/**
 * F02: real domain job policy and the real central file resolver.
 * Stub only the DB I/O boundary; no duplicated access-check implementation.
 */
function db_one(string $sql, array $params = []): ?array
{
    if (str_contains($sql, 'FROM job_files f')) {
        if (!str_contains($sql, 'j.managed_by_admin_id')) {
            throw new RuntimeException('File query must include admin management state.');
        }
        return $GLOBALS['fixture_file'] ?? null;
    }
    if (str_contains($sql, 'FROM jobs j')) {
        return $GLOBALS['fixture_job'] ?? null;
    }
    return null;
}
require __DIR__ . '/../app/domain.php';
require __DIR__ . '/../app/file_access.php';
function f02_check(bool $pass, string $reason): void
{
    if (!$pass) { throw new RuntimeException($reason); }
}
function f02_allowed(array $user): bool
{
    try {
        resolve_portal_file_request($user, ['id' => 19]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
$GLOBALS['fixture_file'] = [
    'id' => 19, 'job_id' => 500, 'client_id' => 30,
    'agent_id' => null, 'managed_by_admin_id' => 7,
    'payment_status' => 'paid', 'file_path' => 'storage/uploads/synthetic.bin',
    'file_name' => 'synthetic.bin', 'mime_type' => 'application/octet-stream'
];
$GLOBALS['fixture_job'] = [
    'id' => 500, 'client_id' => 30, 'agent_id' => null,
    'managed_by_admin_id' => 7, 'payment_status' => 'paid',
    'payment_status_effective' => 'paid'
];
$admin = ['id' => 7, 'role' => 'admin'];
$finance = ['id' => 8, 'role' => 'finance'];
$owner = ['id' => 30, 'role' => 'client'];
$other = ['id' => 31, 'role' => 'client'];
$assigned = ['id' => 40, 'role' => 'agent'];
$unassigned = ['id' => 41, 'role' => 'agent'];
f02_check(f02_allowed($admin) && f02_allowed($finance), 'Admin and finance must access managed jobs.');
f02_check(f02_allowed($owner) && !f02_allowed($other), 'Only the owning client may access files.');
f02_check(!f02_allowed($assigned) && !f02_allowed($unassigned),
    'Admin-managed unassigned job must be blocked from every agent.');

$GLOBALS['fixture_job']['managed_by_admin_id'] = null;
$GLOBALS['fixture_job']['agent_id'] = 40;
f02_check(f02_allowed($assigned) && !f02_allowed($unassigned),
    'Only the assigned agent should access an assigned paid job.');
$GLOBALS['fixture_job']['payment_status'] = 'unpaid';
$GLOBALS['fixture_job']['payment_status_effective'] = 'unpaid';
f02_check(!f02_allowed($assigned) && f02_allowed($owner), 'Unpaid job must be hidden from agents.');
$GLOBALS['fixture_job']['payment_status'] = 'paid';
$GLOBALS['fixture_job']['payment_status_effective'] = 'paid';
$GLOBALS['fixture_job']['agent_id'] = null;
f02_check(f02_allowed($assigned) && f02_allowed($unassigned),
    'Unassigned paid job may be available to agents only when not admin-managed.');
unset($GLOBALS['fixture_job']['managed_by_admin_id']);
f02_check(!f02_allowed($assigned), 'Missing management flag must fail closed.');
$GLOBALS['fixture_job'] = null;
f02_check(!f02_allowed($owner) && !f02_allowed($admin), 'Deleted job must deny file access.');

$download = file_get_contents(__DIR__ . '/../download.php');
$preview = file_get_contents(__DIR__ . '/../preview.php');
f02_check(str_contains($download, 'resolve_portal_file_request($user, $_GET)')
    && str_contains($preview, 'resolve_portal_file_request($user, $_GET)'),
    'Preview and download must enforce the same centralized policy.');
echo "f02_job_file_authorization_test passed\n";
