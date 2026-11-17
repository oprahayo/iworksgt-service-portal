<?php
declare(strict_types=1);
/** Real Paystack sandbox acceptance, using an isolated, empty cPanel database.
 * No live key, live ledger, real bank account or production config is allowed.
 * The temporary public receiver defines IWORKS_SANDBOX_RECEIVER, then includes
 * this script from its private source checkout. Never expose the checkout.
 */
$receiving = PHP_SAPI !== 'cli';
if ($receiving && !defined('IWORKS_SANDBOX_RECEIVER')) { http_response_code(404); exit; }
$action = $receiving ? '--receive' : ($argv[1] ?? '--status');
require __DIR__ . '/../app/bootstrap.php';
$root = dirname(__DIR__);
$manifestPath = $root . '/storage/payout-sandbox.json';
$logPath = $root . '/storage/payout-sandbox-events.jsonl';
function sandbox_expect(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
function sandbox_save(string $path, array $value): void {
    sandbox_expect(file_put_contents($path, json_encode($value, JSON_THROW_ON_ERROR), LOCK_EX) !== false, 'Cannot save sandbox state.');
    chmod($path, 0600);
}
try {
    umask(0077);
    $database = (string)(app_config()['db']['name'] ?? '');
    sandbox_expect((bool)preg_match('/^[a-zA-Z0-9]+_iwqa_[a-f0-9]{8}$/D', $database), 'A dedicated cPanel iwqa database is required.');
    sandbox_expect(iworks_agent_payout_mode() === 'test', 'Sandbox mode is required.');
    $secret = iworks_agent_payout_secret();
    sandbox_expect(str_starts_with($secret, 'sk_test_'), 'Only a test credential is allowed.');
    sandbox_expect(empty(iworks_agent_payout_config()['live_secret_key']), 'Live credentials are forbidden.');
    sandbox_expect((string)db()->query('SELECT DATABASE()')->fetchColumn() === $database, 'Database isolation mismatch.');

    if ($receiving) {
        header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
        $raw = file_get_contents('php://input', false, null, 0, 1048577);
        if (!is_string($raw) || strlen($raw) > 1048576) { http_response_code(413); exit; }
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $payload = json_decode($raw, true);
        $result = handle_gateway_webhook('paystack', $raw, $headers);
        $entry = ['at'=>gmdate('c'), 'event'=>$payload['event'] ?? null,
            'reference'=>$payload['data']['reference'] ?? null,
            'domain'=>$payload['data']['domain'] ?? null,
            'signature_valid'=>iworks_paystack_signature_valid($raw, $headers, $secret),
            'http'=>$result['status_code'], 'message'=>$result['message']];
        file_put_contents($logPath, json_encode($entry, JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX);
        chmod($logPath, 0600);
        http_response_code($result['status_code']);
        echo json_encode($result, JSON_THROW_ON_ERROR);
        exit;
    }

    if ($action === '--init') {
        sandbox_expect(!file_exists($manifestPath), 'Sandbox fixture already exists.');
        sandbox_expect(count(db()->query('SHOW TABLES')->fetchAll()) === 0, 'Sandbox database must be empty.');
        db()->exec(file_get_contents($root . '/database.sql'));
        db_execute("INSERT INTO app_settings (setting_key,setting_value,updated_at) VALUES ('email_notifications_enabled','0',:at)
            ON DUPLICATE KEY UPDATE setting_value='0'", ['at'=>now()]);
        $password = bin2hex(random_bytes(32));
        $tag = bin2hex(random_bytes(8));
        db_execute("INSERT INTO users (full_name,email,password_hash,role,status,account_state,created_at)
            VALUES ('Paystack Sandbox Fixture',:email,:hash,'agent','active','active',:at)",
            ['email'=>$tag.'@example.invalid','hash'=>password_hash($password, PASSWORD_DEFAULT),'at'=>now()]);
        $agentId = db_last_id();
        db_execute("INSERT INTO services (name,slug,created_at) VALUES ('Sandbox fixture',:slug,:at)", ['slug'=>$tag,'at'=>now()]);
        $serviceId = db_last_id();
        db_execute("INSERT INTO jobs (job_code,client_id,service_id,agent_id,title,status,payment_status,currency,invoice_number,created_at,updated_at)
            VALUES (:code,:client,:service,:agent,'Sandbox fixture','delivered','paid','NGN',:invoice,:at,:updated)",
            ['code'=>$tag,'client'=>$agentId,'service'=>$serviceId,'agent'=>$agentId,'invoice'=>$tag,'at'=>now(),'updated'=>now()]);
        db_execute("INSERT INTO commission_payouts (agent_id,job_id,commission_amount,status,created_at)
            VALUES (:agent,:job,200000,'accrued',:at)", ['agent'=>$agentId,'job'=>db_last_id(),'at'=>now()]);
        sandbox_save($manifestPath, ['agent_id'=>$agentId,'password'=>$password,'request_key'=>bin2hex(random_bytes(32))]);
        echo "PASS isolated schema and synthetic agent/commission fixture; email disabled\n";
        exit;
    }
    sandbox_expect(is_file($manifestPath), 'Initialize the isolated fixture first.');
    $fixture = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    if ($action === '--verify-bank') {
        // Official Paystack test account: Zenith, bank 057, account 0000000000.
        iworks_agent_verify_payout_bank((int)$fixture['agent_id'], '057', '0000000000', $fixture['password']);
        $beneficiary = iworks_agent_payout_beneficiary((int)$fixture['agent_id']);
        sandbox_expect(($beneficiary['environment'] ?? '') === 'test', 'Beneficiary is not test-mode.');
        echo "PASS Paystack test bank resolution and recipient registration\n";
    } elseif ($action === '--submit') {
        $id = iworks_agent_create_withdrawal((int)$fixture['agent_id'], 5000, 'Automated sandbox acceptance', $fixture['request_key']);
        $duplicate = iworks_agent_create_withdrawal((int)$fixture['agent_id'], 5000, 'Automated sandbox acceptance', $fixture['request_key']);
        sandbox_expect($id === $duplicate, 'Duplicate request did not reuse the same withdrawal.');
        $fixture['withdrawal_id'] = $id;
        sandbox_save($manifestPath, $fixture);
        echo "PASS test withdrawal request and duplicate form reuse; withdrawal_id=$id\n";
    } elseif ($action === '--reconcile') {
        sandbox_expect(!empty($fixture['withdrawal_id']), 'No submitted test transfer.');
        echo 'Paystack verify status: '.iworks_agent_reconcile_transfer((int)$fixture['withdrawal_id'])."\n";
    } elseif ($action === '--probe') {
        $url = (string)(app_config()['app']['base_url'] ?? '');
        sandbox_expect((bool)preg_match('#^https://portal\.iworksgt\.com/payout-sandbox-[a-f0-9]{16}/$#D', $url), 'Invalid isolated receiver URL.');
        $raw = json_encode(['event'=>'transfer.success','data'=>['domain'=>'test','reference'=>'iworks-signature-probe','status'=>'success']]);
        foreach (['unsigned'=>'', 'invalid'=>str_repeat('0',128), 'valid'=>hash_hmac('sha512',$raw,$secret)] as $name=>$signature) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$raw, CURLOPT_HTTPHEADER=>[
                'Content-Type: application/json','x-paystack-signature: '.$signature], CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20]);
            $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            sandbox_expect($code === ($name === 'valid' ? 200 : 403), 'Unexpected '.$name.' probe result: '.$code);
            echo 'PASS '.$name.' HTTP '.$code."\n";
        }
    } elseif ($action !== '--status') {
        throw new RuntimeException('Use --init, --verify-bank, --submit, --probe, --status or --reconcile.');
    }
    $rows = db_all('SELECT t.withdrawal_id,t.reference,t.environment,t.state,t.provider_transfer_id,t.last_error_code,w.status
        FROM agent_withdrawal_transfers t JOIN withdrawal_requests w ON w.id=t.withdrawal_id');
    echo json_encode(['transfers'=>$rows,'balance'=>agent_withdrawal_summary((int)$fixture['agent_id'])], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    if (is_file($logPath)) { echo file_get_contents($logPath); }
} catch (Throwable $e) {
    if ($receiving) { http_response_code(500); echo '{"success":false}'; }
    else { fwrite(STDERR, 'BLOCKED: '.$e->getMessage()."\n"); }
    exit(1);
}
