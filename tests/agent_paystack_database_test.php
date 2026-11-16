<?php
declare(strict_types=1);
// Creates a fresh database; no live API or production schema can be used.
if (!getenv('IWORKS_TEST_MYSQL_DSN')) { fwrite(STDERR,"Set IWORKS_TEST_MYSQL_DSN for an isolated MySQL server.\n"); exit(2); }
function expect_payout(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function reject_payout(callable $fn, string $message): void {
    try { $fn(); } catch (RuntimeException $e) { return; }
    throw new RuntimeException($message);
}
$worker = ($argv[1] ?? '') === '--worker';
$database = $worker ? (string)getenv('DB_NAME') : 'iworks_payout_test_' . bin2hex(random_bytes(6));
$admin = new PDO((string)getenv('IWORKS_TEST_MYSQL_DSN'), getenv('DB_USER') ?: 'root', (string)getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if (!$worker) { $admin->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4"); }
putenv('DB_NAME='.$database);
require __DIR__ . '/agent_payout_test_bootstrap.php';
if ($worker) {
    try {
        if ($argv[2] === 'submit') { iworks_agent_submit_transfer((int)$argv[3]); echo 'submitted'; }
        else { echo iworks_agent_create_withdrawal((int)$argv[3], (float)$argv[4], '', $argv[5]); }
    } catch (Throwable $e) { echo 'REJECTED'; }
    exit;
}
function fixture_agent(float $earned = 200000): int {
    $uniq=bin2hex(random_bytes(5));
    db_execute("INSERT INTO users (full_name,email,password_hash,role,status,account_state,created_at)
        VALUES ('Fixture Agent',:email,:password,'agent','active','active',:at)",
        ['email'=>$uniq.'@example.invalid','password'=>password_hash('fixture-password',PASSWORD_DEFAULT),'at'=>now()]);
    $agent=db_last_id();
    db_execute("INSERT INTO services (name,slug,created_at) VALUES ('Fixture service',:slug,:at)",['slug'=>$uniq,'at'=>now()]);
    $service=db_last_id();
    db_execute("INSERT INTO jobs (job_code,client_id,service_id,agent_id,title,status,payment_status,currency,invoice_number,created_at,updated_at)
        VALUES (:code,:client,:service,:agent,'Fixture','delivered','paid','NGN',:invoice,:at,:updated)",
        ['code'=>$uniq,'client'=>$agent,'service'=>$service,'agent'=>$agent,'invoice'=>$uniq,'at'=>now(),'updated'=>now()]);
    $job=db_last_id();
    db_execute("INSERT INTO commission_payouts (agent_id,job_id,commission_amount,status,created_at)
        VALUES (:agent,:job,:amount,'accrued',:at)",['agent'=>$agent,'job'=>$job,'amount'=>$earned,'at'=>now()]);
    db_execute("INSERT INTO agent_payout_beneficiaries (agent_id,bank_code,bank_name,account_number,account_name,recipient_code,environment,verified_at)
        VALUES (:id,'058','Fixture Bank','0000000000','Fixture Agent','RCP_fixture','test',:at)",
        ['id'=>$agent,'at'=>date('Y-m-d H:i:s',time()-90000)]);
    return $agent;
}
function transfer_row(int $id): array { return db_one('SELECT * FROM agent_withdrawal_transfers WHERE withdrawal_id=:id',['id'=>$id]); }
function transfer_event(int $id,string $state='success'): array {
    $row=transfer_row($id);
    $call=db_one('SELECT id FROM test_gateway_calls WHERE reference=:ref',['ref'=>$row['reference']]);
    return ['id'=>(int)$call['id'],'reference'=>$row['reference'],'amount'=>(int)$row['amount_kobo'],'currency'=>'NGN',
        'domain'=>'test','status'=>$state,'recipient'=>['recipient_code'=>$row['recipient_code'],
        'details'=>['bank_code'=>$row['bank_code'],'account_number'=>'0000000000']]];
}
function workers(array $specs): array {
    $running=[];
    foreach($specs as $args) {
        $command=array_merge([PHP_BINARY,'-c',(string)php_ini_loaded_file(),__FILE__,'--worker'],$args);
        $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        fclose($pipes[0]); $running[]=[$p,$pipes];
    }
    $results=[];
    foreach($running as [$p,$pipes]) { $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);expect_payout(proc_close($p)===0,'Worker failed: '.$err);$results[]=trim($out); }
    return $results;
}
function migration_cli(array $args, int $expected): void {
    $command=array_merge([PHP_BINARY,'-c',(string)php_ini_loaded_file(),
        __DIR__.'/../scripts/migrate_agent_paystack_withdrawals.php'],$args);
    $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);
    expect_payout(proc_close($p)===$expected,'Migration result: '.$out.$err);
}
try {
    db()->exec(file_get_contents(__DIR__.'/../database.sql'));
    db()->exec('DROP TABLE agent_withdrawal_transfers');
    db()->exec('DROP TABLE agent_payout_beneficiaries');
    migration_cli(['--check'],1);
    migration_cli(['--apply'],2);
    migration_cli(['--apply','--backup-confirmed'],0);
    migration_cli(['--check'],0);
    db()->exec('ALTER TABLE agent_withdrawal_transfers DROP COLUMN request_key, DROP COLUMN last_checked_at');
    migration_cli(['--check'],1);
    migration_cli(['--apply','--backup-confirmed'],0);
    migration_cli(['--apply','--backup-confirmed'],0);
    echo "PASS fresh/upgrade migrations, backup gate and repeat application\n";
    db()->exec('CREATE TABLE test_gateway_calls (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(50), payload TEXT) ENGINE=InnoDB');
    db()->exec('CREATE TABLE test_gateway_response (payload TEXT) ENGINE=InnoDB');
    db_execute("INSERT INTO test_gateway_response VALUES ('{}')");
    db_execute("INSERT INTO app_settings (setting_key,setting_value,updated_at) VALUES ('email_notifications_enabled','0',:at)
        ON DUPLICATE KEY UPDATE setting_value='0'",['at'=>now()]);
    $agent=fixture_agent();$token=bin2hex(random_bytes(32));
    reject_payout(fn()=>iworks_agent_create_withdrawal($agent,4999,'',$token),'Minimum not enforced');
    reject_payout(fn()=>iworks_agent_create_withdrawal($agent,5000,'',''),'Missing request token accepted');
    $id=iworks_agent_create_withdrawal($agent,5000,'',$token);
    expect_payout(iworks_agent_create_withdrawal($agent,5000,'',$token)===$id,'Duplicate form created another withdrawal');
    expect_payout((int)db_one('SELECT COUNT(*) c FROM test_gateway_calls')['c']===1,'Duplicate API submission');
    reject_payout(fn()=>iworks_agent_create_withdrawal($agent,6000,'',$token),'Token reused with a changed amount');
    expect_payout(agent_withdrawal_summary($agent)['available_balance']===195000.0,'Reservation failed');
    $data=transfer_event($id);
    foreach(['reference'=>'other','amount'=>500001,'currency'=>'USD','domain'=>'live','id'=>999,'recipient'=>'RCP_other'] as $key=>$bad) {
        $m=$data;$m[$key]=$bad;
        reject_payout(fn()=>iworks_agent_record_transfer_result($data['reference'],'success',$m),'Mismatched '.$key.' settled');
    }
    $raw=json_encode(['event'=>'transfer.success','data'=>$data]);
    $headers=['x-paystack-signature'=>hash_hmac('sha512',$raw,'sk_test_synthetic_only')];
    expect_payout(iworks_agent_handle_transfer_webhook($raw,[],json_decode($raw,true))['status_code']===403,'Unsigned callback accepted');
    expect_payout(iworks_agent_handle_transfer_webhook($raw,$headers,json_decode($raw,true))['status_code']===200,'Signed callback rejected');
    iworks_agent_handle_transfer_webhook($raw,$headers,json_decode($raw,true));
    expect_payout(agent_withdrawal_summary($agent)['available_balance']===195000.0,'Repeated success changed balance');
    $rev=transfer_event($id,'reversed');iworks_agent_record_transfer_result($rev['reference'],'reversed',$rev);
    iworks_agent_record_transfer_result($rev['reference'],'reversed',$rev);
    iworks_agent_record_transfer_result($data['reference'],'success',$data);
    expect_payout(agent_withdrawal_summary($agent)['available_balance']===200000.0,'Reversal/replay corrupted balance');
    echo "PASS duplicate forms, settlement identity, signed callbacks, replay and reversal\n";

    $agent2=fixture_agent();$key=bin2hex(random_bytes(32));
    $same=workers([['create',(string)$agent2,'5000',$key],['create',(string)$agent2,'5000',$key]]);
    expect_payout($same[0]===$same[1] && ctype_digit($same[0]),'Concurrent duplicate not idempotent');
    $agent3=fixture_agent(100000);
    $limited=workers([['create',(string)$agent3,'60000',bin2hex(random_bytes(32))],['create',(string)$agent3,'60000',bin2hex(random_bytes(32))]]);
    expect_payout(count(array_filter($limited,fn($v)=>$v==='REJECTED'))===1,'Concurrent withdrawal overspent balance');
    $agent4=fixture_agent();
    workers([['create',(string)$agent4,'60000',bin2hex(random_bytes(32))],['create',(string)$agent4,'60000',bin2hex(random_bytes(32))]]);
    $states=db_all('SELECT t.state FROM agent_withdrawal_transfers t JOIN withdrawal_requests w ON w.id=t.withdrawal_id WHERE w.agent_id=:id',['id'=>$agent4]);
    expect_payout(count(array_filter($states,fn($r)=>$r['state']==='awaiting_review'))===1,'Split request escaped review');
    echo "PASS concurrent requests, overdraw protection and rolling anti-splitting\n";

    $agent5=fixture_agent();putenv('IWORKS_TEST_GATEWAY_TIMEOUT=1');
    $uncertain=iworks_agent_create_withdrawal($agent5,5000,'',bin2hex(random_bytes(32)));
    putenv('IWORKS_TEST_GATEWAY_TIMEOUT');
    expect_payout(transfer_row($uncertain)['state']==='exception','Timeout did not retain reservation');
    $before=(int)db_one('SELECT COUNT(*) c FROM test_gateway_calls')['c'];
    workers([['submit',(string)$uncertain],['submit',(string)$uncertain]]);
    expect_payout((int)db_one('SELECT COUNT(*) c FROM test_gateway_calls')['c']===$before,'Ambiguous transfer retried');
    $event=transfer_event($uncertain,'abandoned');
    db_execute('UPDATE test_gateway_response SET payload=:p',['p'=>json_encode(['status'=>true,'data'=>$event])]);
    putenv('IWORKS_PAYSTACK_PAYOUT_MODE=off');
    expect_payout(iworks_agent_reconcile_transfer($uncertain)==='abandoned','Disabled mode blocked reconciliation');
    expect_payout(agent_withdrawal_summary($agent5)['available_balance']===200000.0,'Terminal abandonment did not release funds');
    putenv('IWORKS_PAYSTACK_PAYOUT_MODE=test');
    echo "PASS timeout safety, no blind retries and reconciliation while disabled\n";

    $agent6=fixture_agent();$manual=iworks_agent_create_withdrawal($agent6,100000.01,'',bin2hex(random_bytes(32)));
    reject_payout(fn()=>iworks_agent_review_withdrawal($manual,'approved',$agent6),'Agent approved their own withdrawal');
    db_execute("INSERT INTO users (full_name,email,password_hash,role,status,account_state,created_at) VALUES ('Fixture Finance','finance@example.invalid','none','finance','active','active',:at)",['at'=>now()]);
    $finance=db_last_id();iworks_agent_review_withdrawal($manual,'approved',$finance,'Verified');
    expect_payout(transfer_row($manual)['state']==='pending','Finance-approved transfer not submitted');
    reject_payout(fn()=>iworks_agent_review_withdrawal($manual,'approved',$finance),'Repeated approval accepted');
    $payout=db_one('SELECT id FROM commission_payouts WHERE agent_id=:id',['id'=>$agent6]);
    reject_payout(fn()=>update_payout_status((int)$payout['id'],'paid',$finance),'Legacy payout bypass accepted');
    reject_payout(fn()=>bulk_update_payout_status([(int)$payout['id']],'paid',$finance),'Bulk legacy bypass accepted');
    echo "PASS Finance authorization, manual threshold and legacy double-payment prevention\n";

    $claimAgent=fixture_agent();$claim=iworks_agent_create_withdrawal($claimAgent,100001,'',bin2hex(random_bytes(32)));
    // Simulate a crash immediately after Finance committed approval, before dispatch.
    db_execute("UPDATE withdrawal_requests SET status='approved' WHERE id=:id",['id'=>$claim]);
    db_execute("UPDATE agent_withdrawal_transfers SET state='queued',reviewed_by=:reviewer WHERE withdrawal_id=:id",['reviewer'=>$finance,'id'=>$claim]);
    $before=(int)db_one('SELECT COUNT(*) c FROM test_gateway_calls')['c'];
    workers([['submit',(string)$claim],['submit',(string)$claim]]);
    expect_payout((int)db_one('SELECT COUNT(*) c FROM test_gateway_calls')['c']===$before+1,'Concurrent worker submitted twice');
    echo "PASS concurrent queue dispatch claims exactly once\n";

    $bankAgent=fixture_agent();
    iworks_agent_verify_payout_bank($bankAgent,'058','0000000000','fixture-password');
    expect_payout(iworks_agent_payout_beneficiary($bankAgent)['recipient_code']==='RCP_fixture','Bank verification failed');
    for($i=0;$i<4;$i++) { reject_payout(fn()=>iworks_agent_verify_payout_bank($bankAgent,'058','0000000000','bad'),'Wrong password accepted'); }
    reject_payout(fn()=>iworks_agent_verify_payout_bank($bankAgent,'058','0000000000','fixture-password'),'Bank verification not rate limited');
    echo "PASS beneficiary verification and password attempt limits\n";
} finally { $admin->exec("DROP DATABASE `$database`"); }
echo "All payout database integration tests passed. No external API was called.\n";
