<?php
$user = current_user();
$isAgent = ($user['role'] ?? '') === 'agent';
$isFinance = has_role(['admin', 'finance']);
$payoutPolicy = iworks_agent_payout_policy();
$payoutMode = iworks_agent_payout_mode();
$bankReady = !empty($agentPayoutBeneficiary)
    && (string)($agentPayoutBeneficiary['environment'] ?? '') === $payoutMode;
$bankHoldUntil = ($bankReady && $payoutMode === 'live')
    ? strtotime((string)$agentPayoutBeneficiary['verified_at']) + iworks_agent_payout_hold_seconds()
    : 0;
$canWithdraw = $isAgent && $withdrawalSummary && $withdrawalSummary['can_request'] && $bankReady
    && $payoutMode !== 'off' && (!$bankHoldUntil || time() >= $bankHoldUntil);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Withdrawals</h1>
  <span class="badge <?= $payoutMode === 'live' ? 'bg-success' : 'bg-secondary' ?>">
    <?= $payoutMode === 'live' ? 'Automatic payouts available' : ($payoutMode === 'test' ? 'Test environment' : 'Payouts temporarily paused') ?>
  </span>
</div>

<div class="card mb-3"><div class="card-body">
  <div class="row g-3">
    <div class="col-6 col-lg-3"><small class="kicker">Minimum withdrawal</small><div class="fw-semibold">₦5,000</div></div>
    <div class="col-6 col-lg-3"><small class="kicker">Automatic processing</small><div class="fw-semibold">₦5,000–₦100,000</div></div>
    <div class="col-6 col-lg-3"><small class="kicker">Finance review</small><div class="fw-semibold">Above ₦100,000</div></div>
    <div class="col-6 col-lg-3"><small class="kicker">Request interval</small><div class="fw-semibold">No 14-day waiting period</div></div>
  </div>
  <p class="small text-muted mb-0 mt-3">Verified Nigerian bank accounts only. Requests that total more than ₦100,000 within 24 hours also require Finance review. New or changed bank accounts have a 24-hour security hold.</p>
</div></div>

<?php if ($isAgent): ?>
  <div class="alert <?= $payoutMode === 'off' ? 'alert-warning' : 'alert-info' ?> small">
    <?php if ($payoutMode === 'live'): ?>
      Eligible withdrawal requests are sent automatically to your verified bank account. You can follow the transfer status below.
    <?php elseif ($payoutMode === 'test'): ?>
      This is a test environment. Withdrawal requests do not transfer real money.
    <?php else: ?>
      Withdrawals are temporarily paused while payment setup is completed. Your earned balance and withdrawal history are preserved. Contact Finance if you need assistance.
    <?php endif; ?>
  </div>

  <div class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-3">Your withdrawal bank account</h2>
    <?php if ($bankReady): ?>
      <p class="small text-success mb-2">
        Verified: <?= e($agentPayoutBeneficiary['bank_name'] ?: 'Nigerian bank') ?>
        · <?= e($agentPayoutBeneficiary['account_name']) ?>
        · ****<?= e(substr($agentPayoutBeneficiary['account_number'], -4)) ?>
        (<?= e(strtoupper((string)$agentPayoutBeneficiary['environment'])) ?>)
      </p>
    <?php else: ?>
      <p class="small text-muted mb-2">No verified payout account is currently saved.</p>
    <?php endif; ?>
    <?php if (!iworks_agent_payout_enabled()): ?>
      <div class="alert alert-warning small mb-0">
        Bank verification will be available when withdrawals reopen. You will need your bank account number and current account password.
      </div>
    <?php else: ?>
      <form method="post" action="<?= e(url('withdrawals')) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="verify_payout_bank">
        <div class="col-md-5">
          <label class="form-label" for="agentPayoutBankCode">Nigerian bank</label>
          <?php if (!empty($agentPayoutBanks)): ?>
            <select class="form-select" name="bank_code" id="agentPayoutBankCode" required>
              <option value="">Select bank</option>
              <?php foreach ($agentPayoutBanks as $bank): ?>
                <?php if (!empty($bank['code'])): ?>
                  <option value="<?= e((string) $bank['code']) ?>"><?= e((string) ($bank['name'] ?? $bank['code'])) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input class="form-control" name="bank_code" id="agentPayoutBankCode" pattern="[0-9]{3,8}"
                   placeholder="Paystack bank code" required>
          <?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="agentPayoutAccount">10-digit bank account number</label>
          <input class="form-control" name="account_number" id="agentPayoutAccount"
                 inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autocomplete="off" required>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="bankReauthPassword">Current password</label>
          <input class="form-control" id="bankReauthPassword" type="password" name="current_password" autocomplete="current-password" required>
          <button class="btn btn-outline-primary w-100 mt-2" type="submit">Verify account</button>
        </div>
      </form>
      <p class="small text-muted mt-2 mb-0">We verify the account holder name with Paystack. Changing your bank details requires fresh verification and starts a new 24-hour security hold.</p>
    <?php endif; ?>
  </div></div>

  <div class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-3">Request withdrawal</h2>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><small class="kicker">Earned NGN commission</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['total_earned'], 'NGN')) ?></div></div>
      <div class="col-md-4"><small class="kicker">Available balance</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['available_balance'], 'NGN')) ?></div></div>
      <div class="col-md-4"><small class="kicker">Pending requests</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['pending_withdrawal'], 'NGN')) ?></div></div>
    </div>
    <?php if ($bankHoldUntil && time() < $bankHoldUntil): ?>
      <div class="alert alert-warning small">Live bank account security hold: withdrawals available after <?= e(date('Y-m-d H:i', $bankHoldUntil)) ?>.</div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('withdrawals')) ?>" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="request_withdrawal">
      <input type="hidden" name="request_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
      <div class="col-md-4">
        <label class="form-label" for="withdrawalAmount">Amount (NGN)</label>
        <input id="withdrawalAmount" class="form-control" name="amount" type="number" min="5000" max="<?= e(number_format((float) $withdrawalSummary['available_balance'], 2, '.', '')) ?>" step="0.01" required <?= $payoutMode === 'off' ? 'disabled' : '' ?>>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="withdrawalNote">Note (optional)</label>
        <input id="withdrawalNote" class="form-control" name="note" maxlength="500" placeholder="Withdrawal note" <?= $payoutMode === 'off' ? 'disabled' : '' ?>>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100" type="submit" <?= $canWithdraw ? '' : 'disabled' ?>>Submit request</button>
      </div>
    </form>
    <p class="small text-muted mb-0 mt-2">
      <?php if ($payoutMode === 'off'): ?>
        Requests will reopen once payment setup is complete. The ₦5,000 minimum and updated withdrawal rules are already in place.
      <?php else: ?>
        A request is marked paid only after Paystack confirms the transfer. Requests above the automatic limit are sent to Finance for review.
      <?php endif; ?>
    </p>
  </div></div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-rows table-hover align-middle mb-0">
      <thead><tr>
        <th>Agent</th><th>Amount</th><th>Request</th><th>Transfer</th><th>Requested</th><th>Admin note</th>
        <?php if ($isFinance): ?><th>Actions</th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($withdrawals as $request): ?>
        <?php $transferState = (string) ($request['transfer_state'] ?? ''); ?>
        <tr>
          <td data-label="Agent"><?= e($request['agent_name']) ?></td>
          <td data-label="Amount"><?= e(money((float) $request['amount'], $request['currency'] ?? 'NGN')) ?></td>
          <td data-label="Request"><?= status_badge($request['status']) ?></td>
          <td data-label="Transfer">
            <?php if ($transferState !== ''): ?>
              <span class="small"><?= e(ucwords(str_replace('_', ' ', $transferState))) ?></span>
              <small class="d-block text-muted"><?= e($request['transfer_reference'] ?: '') ?></small>
            <?php else: ?><span class="small text-muted">Previous manual payment</span><?php endif; ?>
          </td>
          <td data-label="Requested"><?= e($request['requested_at']) ?></td>
          <td data-label="Admin note"><?= e($request['admin_note'] ?: '-') ?></td>
          <?php if ($isFinance): ?>
            <td data-label="Actions" class="cell-form">
              <?php if ($transferState === 'awaiting_review'): ?>
                <form method="post" action="<?= e(url('withdrawals')) ?>" class="d-flex gap-2">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="update_withdrawal_status">
                  <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                  <select class="form-select form-select-sm" name="status" aria-label="Withdrawal decision" required>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                  </select>
                  <input class="form-control form-control-sm" name="admin_note" placeholder="Reason / note">
                  <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                </form>
              <?php elseif (in_array($transferState, ['pending','exception','submitting','success'], true)): ?>
                <form method="post" action="<?= e(url('withdrawals')) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="reconcile_payout">
                  <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary" type="submit">Verify status</button>
                </form>
              <?php elseif ($transferState === ''): ?>
                <form method="post" action="<?= e(url('withdrawals')) ?>" class="d-flex gap-2">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="update_withdrawal_status">
                  <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                  <select class="form-select form-select-sm" name="status">
                    <?php foreach (['pending','approved','paid','rejected'] as $status): ?>
                      <option value="<?= e($status) ?>" <?= selected_if($status,$request['status']) ?>><?= e(ucwords($status)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input class="form-control form-control-sm" name="admin_note" value="<?= e($request['admin_note'] ?? '') ?>">
                  <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                </form>
              <?php else: ?>—<?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$withdrawals): ?>
        <tr class="table-empty-row"><td colspan="<?= $isFinance ? '7' : '6' ?>" class="text-center text-muted py-4">No withdrawal requests yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
