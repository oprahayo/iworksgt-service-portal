<?php
declare(strict_types=1);
final class RateFakePdo extends PDO
{
    private bool $active=false;
    public function __construct() {}
    public function beginTransaction(): bool { $this->active=true; return true; }
    public function commit(): bool { $this->active=false; return true; }
    public function rollBack(): bool { $this->active=false; return true; }
    public function inTransaction(): bool { return $this->active; }
}
$GLOBALS['pdo']=new RateFakePdo();
$_SERVER['REMOTE_ADDR']='198.51.100.24';
$GLOBALS['attempts']=[];
$GLOBALS['record']=[
    'id'=>101,'user_id'=>7,'purpose'=>'login',
    'code_hash'=>password_hash('123456', PASSWORD_DEFAULT),
    'failed_attempts'=>0,'consumed_at'=>null,'expires_at'=>'2099-01-01 00:00:00'
];
$GLOBALS['account']=[
    'id'=>7,'email'=>'example@example.invalid',
    'password_hash'=>password_hash('good-password', PASSWORD_DEFAULT),
    'status'=>'active','account_state'=>'active','deleted_at'=>null
];
function db(): PDO { return $GLOBALS['pdo']; }
function now(): string { return '2026-11-13 12:00:00'; }
function setting(string $key, string $default=''): string { return $default; }
function db_one(string $sql,array $params=[]): ?array
{
    if (str_contains($sql,'FROM auth_attempt_limits')) {
        $key=implode(':',$params);
        return ['attempts'=>$GLOBALS['attempts'][$key]??0];
    }
    if (str_contains($sql,'FROM otp_codes')) {
        return ($GLOBALS['record']['consumed_at']===null) ? $GLOBALS['record'] : null;
    }
    if (str_contains($sql,'FROM users WHERE email')) return $GLOBALS['account'];
    if (str_contains($sql,'FROM users WHERE id')) return ['id'=>7];
    return null;
}
function db_execute(string $sql,array $params=[]): bool
{
    if (str_contains($sql,'INSERT INTO auth_attempt_limits')) {
        $key=implode(':',$params);
        $GLOBALS['attempts'][$key]=($GLOBALS['attempts'][$key]??0)+1;
    }
    if (str_contains($sql,'UPDATE otp_codes SET failed_attempts')) {
        $GLOBALS['record']['failed_attempts']=$params['attempts'];
        $GLOBALS['record']['consumed_at']=$params['consumed_at'];
    }
    if (str_contains($sql,'UPDATE otp_codes SET consumed_at')) {
        $GLOBALS['record']['consumed_at']=$params['at'];
    }
    return true;
}
require __DIR__.'/../app/auth_rate_limits.php';
require __DIR__.'/../app/auth.php';
function assert_limit(bool $pass,string $message): void
{
    if (!$pass) throw new RuntimeException($message);
}
for ($i=0;$i<4;$i++) {
    assert_limit(!verify_otp_code(7,'login','000000'),'Wrong OTP must fail.');
}
assert_limit($GLOBALS['record']['failed_attempts']===4,'Attempts must be recorded on challenge.');
assert_limit(verify_otp_code(7,'login','123456'),'Valid code before lockout must be accepted.');
assert_limit(!verify_otp_code(7,'login','123456'),'Consumed OTP may not be replayed.');
$GLOBALS['record']['id']=102;
$GLOBALS['record']['failed_attempts']=0;
$GLOBALS['record']['consumed_at']=null;
for ($i=0;$i<5;$i++) {
    assert_limit(!verify_otp_code(7,'login','000000'),'Wrong OTP must fail.');
}
assert_limit($GLOBALS['record']['failed_attempts']===5 && $GLOBALS['record']['consumed_at']!==null,
    'Fifth failure must consume the challenge.');
assert_limit(!verify_otp_code(7,'login','123456'),
    'Previously vulnerable correct-after-eight-guesses path must be blocked.');

for ($i=0;$i<8;$i++) {
    assert_limit(authenticate_with_password('example@example.invalid','wrong')===null,
        'Invalid password must fail.');
}
assert_limit(authenticate_with_password('example@example.invalid','good-password')===null,
    'Password attempt threshold must throttle further attempts from this IP.');
assert_limit(authenticate_with_password('other@example.invalid','good-password')!==null,
    'Throttle must not disable unrelated accounts sharing an address.');
$actor=iworks_auth_actor('example@example.invalid');
$key=iworks_auth_bucket('password_actor',$actor,900);
assert_limit(isset($GLOBALS['attempts'][implode(':',$key)]), 'Password throttle must persist a hashed bucket.');
assert_limit(!str_contains(implode(':',$key),'example@example.invalid'), 'Rate bucket must not include raw email.');
$oldWindow=$key;$oldWindow['window_started_at']-=900;
$GLOBALS['attempts'][implode(':',$oldWindow)]=500;
assert_limit(!empty($GLOBALS['attempts'][implode(':',$oldWindow)])
    && iworks_auth_limit_allows('password_actor','new@example.invalid|198.51.100.24',8,900),
    'Old buckets must not block a new account/window.');
echo "f04_auth_rate_limit_test passed\n";
