<?php
declare(strict_types=1);
// Real auth functions with an isolated transaction-capable PDO boundary.
final class AuthFakePdo extends PDO
{
    private bool $open = false;
    public function __construct() {}
    public function beginTransaction(): bool { $this->open = true; return true; }
    public function commit(): bool { $this->open = false; return true; }
    public function rollBack(): bool { $this->open = false; return true; }
    public function inTransaction(): bool { return $this->open; }
}
$GLOBALS['authpdo'] = new AuthFakePdo();
$GLOBALS['account'] = [
    'id' => 3, 'full_name' => 'Synthetic Tester', 'email' => 'tester@example.invalid',
    'password_hash' => password_hash('old-secret', PASSWORD_DEFAULT),
    'status' => 'active', 'account_state' => 'active', 'deleted_at' => null,
];
$GLOBALS['tokens'] = [
    1 => ['id'=>1,'user_id'=>3,'token_hash'=>hash('sha256','old-link'),
          'expires_at'=>'2099-01-01 00:00:00','used_at'=>null],
    2 => ['id'=>2,'user_id'=>3,'token_hash'=>hash('sha256','new-link'),
          'expires_at'=>'2099-01-01 00:00:00','used_at'=>null],
];
$GLOBALS['trusted'] = true;
$GLOBALS['reset_writes'] = [];
function db(): PDO { return $GLOBALS['authpdo']; }
function now(): string { return '2026-11-13 12:00:00'; }
function email_enabled(): bool { return false; }
function db_one(string $sql, array $params=[]): ?array
{
    if (str_contains($sql, 'FROM password_resets WHERE token_hash')) {
        foreach ($GLOBALS['tokens'] as $row) {
            if ($row['token_hash'] === ($params['token_hash'] ?? '')) return $row;
        }
        return null;
    }
    if (str_contains($sql, 'FROM users WHERE id')) return $GLOBALS['account'];
    return null;
}
function db_execute(string $sql, array $params=[]): bool
{
    $GLOBALS['reset_writes'][] = ['sql'=>$sql,'params'=>$params];
    if (str_contains($sql, 'UPDATE users SET password_hash')) {
        $GLOBALS['account']['password_hash']=$params['password_hash'];
    }
    if (str_contains($sql, 'DELETE FROM trusted_devices')) $GLOBALS['trusted']=false;
    if (str_contains($sql, 'UPDATE password_resets SET used_at')) {
        foreach ($GLOBALS['tokens'] as &$row) {
            if ($row['user_id'] === $params['user_id'] && $row['used_at'] === null) {
                $row['used_at']=$params['used_at'];
            }
        }
        unset($row);
    }
    return true;
}
require __DIR__ . '/../app/auth.php';
function auth_check(bool $value, string $reason): void
{
    if (!$value) throw new RuntimeException($reason);
}
function expect_auth_failure(callable $operation): void
{
    try { $operation(); } catch (RuntimeException $e) { return; }
    throw new RuntimeException('Expected authentication failure.');
}
session_start();
login_user(3);
$oldSessionId = session_id();
auth_check(isset($_SESSION['auth_password_marker']), 'Login must record a revocable auth marker.');
login_user(3);
auth_check(session_id() !== $oldSessionId, 'Login must rotate session identifier.');
auth_check(send_password_reset_link_to_user(3) === false, 'Disabled mail must not issue a reset token.');
reset_password_with_token('new-link', 'new-password-123', 'new-password-123');
auth_check(password_verify('new-password-123',$GLOBALS['account']['password_hash']),
    'Password reset must update the actual password hash.');
auth_check($GLOBALS['tokens'][1]['used_at'] !== null && $GLOBALS['tokens'][2]['used_at'] !== null,
    'Successful reset must invalidate all older and current links.');
auth_check($GLOBALS['trusted'] === false, 'Password reset must revoke trusted devices.');
auth_check(current_user() === null && !isset($_SESSION['user_id']),
    'Reset must invalidate a prior authenticated PHP session.');
expect_auth_failure(static fn()=>reset_password_with_token('old-link','another-new','another-new'));
expect_auth_failure(static fn()=>reset_password_with_token('new-link','another-new','another-new'));
$GLOBALS['tokens'][3] = [
    'id'=>3,'user_id'=>3,'token_hash'=>hash('sha256','disabled-link'),
    'expires_at'=>'2099-01-01 00:00:00','used_at'=>null,
];
$GLOBALS['account']['account_state']='suspended';
expect_auth_failure(static fn()=>reset_password_with_token('disabled-link','another-new','another-new'));
$GLOBALS['account']['account_state']='active';
$GLOBALS['tokens'][4] = [
    'id'=>4,'user_id'=>3,'token_hash'=>hash('sha256','expired-link'),
    'expires_at'=>'2020-01-01 00:00:00','used_at'=>null,
];
expect_auth_failure(static fn()=>reset_password_with_token('expired-link','another-new','another-new'));
login_user(3);
logout_user();
auth_check(!isset($_SESSION['user_id']) && !isset($_SESSION['auth_password_marker']),
    'Logout must clear all authenticated state.');
session_write_close();
echo "f03_f05_auth_session_reset_test passed\n";
