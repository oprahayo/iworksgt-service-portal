<?php
declare(strict_types=1);
// Loaded only by the isolated MySQL integration test. Never used in production.
$testDb = (string)getenv('DB_NAME');
if (!preg_match('/^iworks_payout_test_[a-z0-9_]+$/D', $testDb)) {
    throw new RuntimeException('A dedicated disposable payout test database is required.');
}
function app_config(): array { return require __DIR__ . '/../app/config.example.php'; }
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/auth_rate_limits.php';
require __DIR__ . '/../app/gateway_verification.php';
require __DIR__ . '/../app/domain.php';
require __DIR__ . '/../app/agent_withdrawals.php';
date_default_timezone_set('UTC');
putenv('IWORKS_PAYSTACK_PAYOUT_MODE=test');
putenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET=sk_test_synthetic_only');
function iworks_agent_paystack_post(string $url, array $payload, string $secret): array
{
    if ($secret !== 'sk_test_synthetic_only') { throw new RuntimeException('Test gateway refuses real credentials.'); }
    if ($url === 'https://api.paystack.co/transferrecipient') {
        return ['status'=>true,'data'=>['recipient_code'=>'RCP_fixture','active'=>true,'type'=>'nuban',
            'domain'=>'test','currency'=>'NGN','details'=>['account_number'=>$payload['account_number'],
            'bank_code'=>$payload['bank_code'],'bank_name'=>'Fixture Bank']]];
    }
    if ($url !== 'https://api.paystack.co/transfer') { throw new RuntimeException('Unexpected test API call.'); }
    db_execute('INSERT INTO test_gateway_calls (reference, payload) VALUES (:ref,:payload)',
        ['ref'=>$payload['reference'],'payload'=>json_encode($payload)]);
    $id = db_last_id();
    usleep(100000); // Overlap independent workers to exercise claim locking.
    if (getenv('IWORKS_TEST_GATEWAY_TIMEOUT') === '1') { throw new RuntimeException('Simulated timeout after acceptance'); }
    return ['status'=>true,'data'=>['id'=>$id,'domain'=>'test','reference'=>$payload['reference'],
        'amount'=>$payload['amount'],'currency'=>'NGN','status'=>'pending','recipient'=>$payload['recipient']]];
}
function iworks_agent_paystack_get(string $url, string $secret): array
{
    if ($secret !== 'sk_test_synthetic_only') { throw new RuntimeException('Test gateway refuses real credentials.'); }
    if (str_starts_with($url, 'https://api.paystack.co/bank/resolve?')) {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $q);
        return ['status'=>true,'data'=>['account_number'=>$q['account_number'],'account_name'=>'Fixture Agent']];
    }
    if (!str_starts_with($url, 'https://api.paystack.co/transfer/verify/')) { throw new RuntimeException('Unexpected test API call.'); }
    return json_decode((string)(db_one('SELECT payload FROM test_gateway_response LIMIT 1')['payload'] ?? '{}'),true);
}
