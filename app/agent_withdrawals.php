<?php
declare(strict_types=1);

/**
 * iWORKS agent withdrawals. The transfer engine is intentionally TEST ONLY.
 * No "live" mode exists; a future production rollout requires a separate
 * security review, approval, and code change.
 */
function iworks_agent_payout_policy(): array
{
    return ['minimum_kobo' => 500000, 'review_above_kobo' => 10000000, 'currency' => 'NGN'];
}

function iworks_agent_payout_mode(): string
{
    return getenv('IWORKS_PAYSTACK_PAYOUT_MODE') === 'test' ? 'test' : 'off';
}

function iworks_agent_payout_secret(): string
{
    if (iworks_agent_payout_mode() !== 'test') {
        throw new RuntimeException('Automatic transfers are disabled.');
    }
    $key = trim((string) getenv('IWORKS_PAYSTACK_PAYOUT_TEST_SECRET'));
    if (!preg_match('/^sk_test_[A-Za-z0-9]+$/', $key)) {
        throw new RuntimeException('A Paystack TEST secret key is required; live keys are prohibited.');
    }
    return $key;
}

function iworks_agent_payout_enabled(): bool
{
    return iworks_agent_payout_mode() === 'test';
}

function iworks_agent_payout_kobo(float $amount): int
{
    if (!is_finite($amount) || $amount <= 0 || $amount > 9999999999.99
        || abs($amount * 100 - round($amount * 100)) > 0.00001) {
        throw new RuntimeException('Enter a valid NGN amount with at most two decimal places.');
    }
    return (int) round($amount * 100);
}

/** Agent bank verification must be performed server-side using the test key. */
function iworks_agent_verify_payout_bank(int $agentId, string $bankCode, string $accountNumber): void
{
    $user = find_user_by_id($agentId);
    if (!$user || ($user['role'] ?? '') !== 'agent' || ($user['status'] ?? '') !== 'active') {
        throw new RuntimeException('Active agent account required.');
    }
    if (!preg_match('/^\d{3,8}$/', $bankCode) || !preg_match('/^\d{10}$/', $accountNumber)) {
        throw new RuntimeException('Provide a valid Paystack bank code and 10-digit NUBAN.');
    }
    $secret = iworks_agent_payout_secret();
    $active = (int) (db_one(
        "SELECT COUNT(*) AS c FROM withdrawal_requests
          WHERE agent_id = :agent_id AND status IN ('pending', 'approved')",
        ['agent_id' => $agentId]
    )['c'] ?? 0);
    if ($active !== 0) {
        throw new RuntimeException('Bank details cannot be changed while a withdrawal is unsettled.');
    }
    $url = 'https://api.paystack.co/bank/resolve?account_number='
        . rawurlencode($accountNumber) . '&bank_code=' . rawurlencode($bankCode);
    $verified = gateway_json_get($url, $secret);
    $name = trim((string) ($verified['data']['account_name'] ?? ''));
    if (($verified['status'] ?? false) !== true
        || (string) ($verified['data']['account_number'] ?? '') !== $accountNumber
        || $name === '') {
        throw new RuntimeException('Paystack could not verify that bank account.');
    }
    $recipient = gateway_json_post('https://api.paystack.co/transferrecipient', [
        'type' => 'nuban',
        'name' => $name,
        'account_number' => $accountNumber,
        'bank_code' => $bankCode,
        'currency' => 'NGN',
    ], $secret);
    $code = trim((string) ($recipient['data']['recipient_code'] ?? ''));
    if (($recipient['status'] ?? false) !== true || $code === '') {
        throw new RuntimeException('Unable to register the bank beneficiary.');
    }
    $bankName = trim((string) ($recipient['data']['details']['bank_name'] ?? ''));
    // Protect changes against a withdrawal created while the API calls ran.
    $pdo = db();
    $pdo->beginTransaction();
    try {
        db_one('SELECT id FROM users WHERE id = :id FOR UPDATE', ['id' => $agentId]);
        $active = (int) (db_one(
            "SELECT COUNT(*) AS c FROM withdrawal_requests
              WHERE agent_id = :agent_id AND status IN ('pending', 'approved')",
            ['agent_id' => $agentId]
        )['c'] ?? 0);
        if ($active !== 0) {
            throw new RuntimeException('A withdrawal was created during bank verification. Try again later.');
        }
        db_execute(
            'INSERT INTO agent_payout_beneficiaries
             (agent_id, bank_code, bank_name, account_number, account_name, recipient_code, verified_at)
             VALUES (:agent_id, :bank_code, :bank_name, :account_number, :account_name, :recipient_code, :verified_at)
             ON DUPLICATE KEY UPDATE bank_code = VALUES(bank_code), bank_name = VALUES(bank_name),
               account_number = VALUES(account_number), account_name = VALUES(account_name),
               recipient_code = VALUES(recipient_code), verified_at = VALUES(verified_at)',
            ['agent_id'=>$agentId,'bank_code'=>$bankCode,'bank_name'=>$bankName,
             'account_number'=>$accountNumber,'account_name'=>$name,'recipient_code'=>$code,'verified_at'=>now()]
        );
        db_execute(
            'UPDATE users SET bank_name = :bank_name, bank_account_name = :account_name,
               bank_account_number = :account_number, bank_currency = :currency WHERE id = :id',
            ['bank_name'=>$bankName,'account_name'=>$name,'account_number'=>$accountNumber,
             'currency'=>'NGN','id'=>$agentId]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    notify_roles(['admin', 'finance'], 'Agent payout bank changed',
        'An agent verified payout bank details. Review changes before production activation.', url('withdrawals'));
}

function iworks_agent_payout_beneficiary(int $agentId): ?array
{
    return db_one('SELECT * FROM agent_payout_beneficiaries WHERE agent_id = :id', ['id' => $agentId]);
}

function iworks_agent_payout_banks(): array
{
    if (!iworks_agent_payout_enabled()) { return []; }
    try {
        $r = gateway_json_get('https://api.paystack.co/bank?country=nigeria&perPage=200',
            iworks_agent_payout_secret());
        $banks = is_array($r['data'] ?? null) ? $r['data'] : [];
        usort($banks, static fn(array $a, array $b): int =>
            strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        return $banks;
    } catch (Throwable $e) { return []; }
}

/**
 * One withdrawal per committed balance reservation, serialized per agent.
 * API calls are outside the SQL transaction so network latency cannot hold a lock.
 */
function iworks_agent_create_withdrawal(int $agentId, float $amount, string $note = ''): int
{
    $kobo = iworks_agent_payout_kobo($amount);
    $policy = iworks_agent_payout_policy();
    if ($kobo < $policy['minimum_kobo']) {
        throw new RuntimeException('Minimum withdrawal is NGN 5,000.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $agent = db_one('SELECT id, role, status, account_state FROM users WHERE id = :id FOR UPDATE',
            ['id' => $agentId]);
        if (!$agent || $agent['role'] !== 'agent' || $agent['status'] !== 'active'
            || $agent['account_state'] !== 'active') {
            throw new RuntimeException('Active agent account required.');
        }
        if (!schema_table_exists('agent_payout_beneficiaries')
            || !schema_table_exists('agent_withdrawal_transfers')) {
            throw new RuntimeException('Withdrawal migration not yet applied.');
        }
        $beneficiary = iworks_agent_payout_beneficiary($agentId);
        if (!$beneficiary) {
            throw new RuntimeException('Verify a Nigerian bank account before requesting a withdrawal.');
        }
        $summary = agent_withdrawal_summary($agentId);
        if ($kobo > (int) round((float) $summary['available_balance'] * 100)) {
            throw new RuntimeException('Withdrawal exceeds available NGN commission balance.');
        }
        // UUID-derived reference: 16-50 chars, Paystack idempotency/reconciliation key.
        $reference = 'iwagent_' . bin2hex(random_bytes(16));
        db_execute(
            "INSERT INTO withdrawal_requests
             (agent_id, amount, currency, note, status, requested_at)
             VALUES (:agent_id, :amount, 'NGN', :note, 'pending', :requested_at)",
            ['agent_id'=>$agentId,'amount'=>number_format($kobo/100,2,'.',''),
             'note'=>substr(trim($note),0,500),'requested_at'=>now()]
        );
        $id = db_last_id();
        $manual = $kobo > $policy['review_above_kobo'];
        db_execute(
            'INSERT INTO agent_withdrawal_transfers
             (withdrawal_id, reference, amount_kobo, recipient_code, bank_code,
              account_last4, state, created_at, updated_at)
             VALUES (:withdrawal_id, :reference, :amount_kobo, :recipient_code, :bank_code,
              :account_last4, :state, :created_at, :updated_at)',
            ['withdrawal_id'=>$id,'reference'=>$reference,'amount_kobo'=>$kobo,
             'recipient_code'=>$beneficiary['recipient_code'],'bank_code'=>$beneficiary['bank_code'],
             'account_last4'=>substr((string)$beneficiary['account_number'],-4),
             'state'=>$manual?'awaiting_review':'queued','created_at'=>now(),'updated_at'=>now()]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    notify_roles(['admin', 'finance'], 'Agent withdrawal created',
        $manual ? 'Withdrawal above NGN 100,000 requires manual review.' :
            'An agent payout is queued for automatic TEST transfer.', url('withdrawals'));
    if (!$manual && iworks_agent_payout_enabled()) {
        iworks_agent_submit_test_transfer($id);
    }
    return $id;
}

/**
 * Claim exactly once. An ambiguous network response remains in "exception"
 * and must be reconciled using GET /transfer/verify; NEVER blindly re-post.
 */
function iworks_agent_submit_test_transfer(int $withdrawalId): void
{
    $secret = iworks_agent_payout_secret();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $transfer = db_one(
            'SELECT t.*, w.status AS withdrawal_status, w.amount, w.currency
             FROM agent_withdrawal_transfers t
             JOIN withdrawal_requests w ON w.id = t.withdrawal_id
             WHERE t.withdrawal_id = :id FOR UPDATE',
            ['id'=>$withdrawalId]
        );
        if (!$transfer || $transfer['state'] !== 'queued') {
            $pdo->commit();
            return;
        }
        if (!in_array($transfer['withdrawal_status'], ['pending','approved'], true)
            || $transfer['currency'] !== 'NGN') {
            throw new RuntimeException('Withdrawal cannot be transferred.');
        }
        if ((int)$transfer['amount_kobo'] > iworks_agent_payout_policy()['review_above_kobo']
            && $transfer['withdrawal_status'] !== 'approved') {
            throw new RuntimeException('Manual approval required.');
        }
        db_execute("UPDATE agent_withdrawal_transfers
           SET state = 'submitting', attempted_at = :at, updated_at = :at2
           WHERE withdrawal_id = :id AND state = 'queued'",
            ['at'=>now(),'at2'=>now(),'id'=>$withdrawalId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    try {
        $r = gateway_json_post('https://api.paystack.co/transfer', [
            'source'=>'balance','amount'=>(int)$transfer['amount_kobo'],
            'recipient'=>$transfer['recipient_code'],'reference'=>$transfer['reference'],
            'currency'=>'NGN','reason'=>'iWORKS agent commission withdrawal #' . $withdrawalId,
        ], $secret);
        $data = is_array($r['data'] ?? null) ? $r['data'] : [];
        if (($r['status'] ?? false) !== true
            || (string)($data['reference'] ?? '') !== (string)$transfer['reference']
            || (int)($data['amount'] ?? -1) !== (int)$transfer['amount_kobo']
            || strtoupper((string)($data['currency'] ?? '')) !== 'NGN'
            || (string)($data['domain'] ?? '') !== 'test') {
            throw new RuntimeException('Unexpected Paystack test transfer acknowledgement.');
        }
        // A transfer initiation response is NOT proof of settlement.
        db_execute("UPDATE agent_withdrawal_transfers SET state = 'pending',
           provider_transfer_id = :provider_id, updated_at = :at
           WHERE withdrawal_id = :id AND state = 'submitting'",
           ['provider_id'=>(string)($data['id'] ?? ''),'at'=>now(),'id'=>$withdrawalId]);
    } catch (Throwable $e) {
        db_execute("UPDATE agent_withdrawal_transfers SET state = 'exception',
          last_error_code = 'needs_reconciliation', updated_at = :at
          WHERE withdrawal_id = :id AND state = 'submitting'",
          ['at'=>now(),'id'=>$withdrawalId]);
        notify_roles(['admin','finance'],'Paystack payout needs reconciliation',
            'Withdrawal #' . $withdrawalId . ' needs transfer verification; do not retry blindly.', url('withdrawals'));
    }
}

/** Manual approval permitted only for requests above the review threshold. */
function iworks_agent_review_withdrawal(int $withdrawalId, string $decision, int $reviewerId, string $note = ''): void
{
    if (!in_array($decision, ['approved','rejected'], true)) {
        throw new RuntimeException('Choose approve or reject.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $row = db_one(
            'SELECT t.state, t.amount_kobo, w.status, w.agent_id
             FROM agent_withdrawal_transfers t JOIN withdrawal_requests w ON w.id=t.withdrawal_id
             WHERE t.withdrawal_id=:id FOR UPDATE', ['id'=>$withdrawalId]
        );
        if (!$row || $row['state'] !== 'awaiting_review' || $row['status'] !== 'pending'
            || (int)$row['amount_kobo'] <= iworks_agent_payout_policy()['review_above_kobo']) {
            throw new RuntimeException('Request is not awaiting manual approval.');
        }
        db_execute('UPDATE withdrawal_requests SET status=:status, admin_note=:note,
            reviewed_at=:at WHERE id=:id',
            ['status'=>$decision,'note'=>substr(trim($note),0,500),
             'at'=>now(),'id'=>$withdrawalId]);
        db_execute('UPDATE agent_withdrawal_transfers SET state=:state,
            reviewed_by=:reviewer_id, updated_at=:at WHERE withdrawal_id=:id',
            ['state'=>$decision === 'approved'?'queued':'failed',
             'reviewer_id'=>$reviewerId,'at'=>now(),'id'=>$withdrawalId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    create_notification((int)$row['agent_id'],'Withdrawal reviewed',
        'Your withdrawal request #' . $withdrawalId . ' was ' . $decision . '.', url('withdrawals'));
    if ($decision === 'approved' && iworks_agent_payout_enabled()) {
        iworks_agent_submit_test_transfer($withdrawalId);
    }
}

/** Verified transfer event or verification response; idempotent and reversible. */
function iworks_agent_record_transfer_result(string $reference, string $state, array $data): bool
{
    if (!in_array($state, ['success','failed','reversed'], true)) { return false; }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $row = db_one(
            'SELECT t.*, w.agent_id, w.status AS withdrawal_status
             FROM agent_withdrawal_transfers t
             JOIN withdrawal_requests w ON w.id=t.withdrawal_id
             WHERE t.reference=:reference FOR UPDATE', ['reference'=>$reference]
        );
        if (!$row) { $pdo->commit(); return false; }
        if ((int)($data['amount'] ?? -1) !== (int)$row['amount_kobo']
            || strtoupper((string)($data['currency'] ?? '')) !== 'NGN'
            || (string)($data['domain'] ?? '') !== 'test') {
            throw new RuntimeException('Transfer event amount, currency, or environment mismatch.');
        }
        if (!in_array($row['state'], ['submitting','pending','exception','success','failed','reversed'], true)) {
            throw new RuntimeException('Transfer event not allowed for unsubmitted request.');
        }
        $current = (string)$row['state'];
        if ($current === 'reversed' || ($current === 'failed' && $state !== 'reversed')
            || ($current === 'success' && $state === 'failed')) {
            $pdo->commit(); return true;
        }
        if ($current === $state) { $pdo->commit(); return true; }
        db_execute('UPDATE agent_withdrawal_transfers SET state=:state,
            settled_at=:at, updated_at=:updated WHERE withdrawal_id=:id',
            ['state'=>$state,'at'=>now(),'updated'=>now(),'id'=>$row['withdrawal_id']]);
        db_execute('UPDATE withdrawal_requests SET status=:status,
            paid_at=:paid_at WHERE id=:id',
            ['status'=>$state==='success'?'paid':'rejected',
             'paid_at'=>$state==='success'?now():null,'id'=>$row['withdrawal_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    create_notification((int)$row['agent_id'],'Withdrawal transfer update',
        'Withdrawal #' . $row['withdrawal_id'] . ' transfer status: ' . $state . '.',url('withdrawals'));
    return true;
}

function iworks_agent_handle_transfer_webhook(string $raw, array $headers, array $event): array
{
    // A transfer webhook must never be accepted with the checkout/live key.
    try { $secret = iworks_agent_payout_secret(); }
    catch (Throwable $e) { return ['success'=>false,'status_code'=>503,'message'=>'Payout test mode disabled.']; }
    $signature = header_value($headers, 'x-paystack-signature');
    if ($signature === '' || !hash_equals(hash_hmac('sha512', $raw, $secret), $signature)) {
        return ['success'=>false,'status_code'=>403,'message'=>'Invalid payout webhook signature.'];
    }
    $type = strtolower((string)($event['event'] ?? ''));
    if (!in_array($type,['transfer.success','transfer.failed','transfer.reversed'],true)) {
        return ['success'=>true,'status_code'=>200,'message'=>'Event ignored.'];
    }
    $data = is_array($event['data'] ?? null) ? $event['data'] : [];
    $reference = (string)($data['reference'] ?? '');
    if ($reference === '') {
        return ['success'=>false,'status_code'=>400,'message'=>'Transfer reference missing.'];
    }
    try {
        iworks_agent_record_transfer_result($reference, substr($type,9), $data);
        return ['success'=>true,'status_code'=>200,'message'=>'Transfer event processed.'];
    } catch (Throwable $e) {
        return ['success'=>false,'status_code'=>422,'message'=>'Transfer event could not be matched.'];
    }
}

/** Read-only remote status check; never retries a POST transfer request. */
function iworks_agent_reconcile_test_transfer(int $withdrawalId): string
{
    $secret = iworks_agent_payout_secret();
    $transfer = db_one('SELECT * FROM agent_withdrawal_transfers WHERE withdrawal_id=:id',
        ['id'=>$withdrawalId]);
    if (!$transfer || !in_array($transfer['state'], ['submitting','exception','pending','success'], true)) {
        throw new RuntimeException('Transfer is not eligible for reconciliation.');
    }
    $r = gateway_json_get('https://api.paystack.co/transfer/verify/'
        . rawurlencode((string)$transfer['reference']), $secret);
    $data = $r['data'] ?? [];
    $state = strtolower((string)($data['status'] ?? ''));
    if (in_array($state,['success','failed','reversed'],true)) {
        iworks_agent_record_transfer_result((string)$transfer['reference'],$state,$data);
    }
    return $state !== '' ? $state : 'unconfirmed';
}
