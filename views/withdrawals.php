<?php
$user = current_user();
$isAgent = ($user['role'] ?? '') === 'agent';
$isFinance = has_role(['admin', 'finance']);
$payoutPolicy = iworks_agent_payout_policy();
$bankReady = !empty($agentPayoutBeneficiary);
$canWithdraw = $isAgent && $withdrawalSummary && $withdrawalSummary['can_request'] && $bankReady;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Agent Withdrawals</h1>
  <span class="badge bg-secondary">Paystack live payouts disabled</span>
</div>

<?php if ($isAgent): ?>
  <div class="alert alert-info small">
    Withdrawals are in TEST-ONLY preparation. A request does not send real money.
    Verified Nigerian bank details are required before requesting a test payout.
    Amounts above ₦100,000 always require Finance approval.
  </div>

  <div class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-3">Bank account for Paystack payouts</h2>
    <?php if ($bankReady): ?>
      <p class="small text-success mb-2">
        Verified: <?= e($agentPayoutBeneficiary['bank_name'] ?: 'Nigerian bank') ?>
        · <?= e($agentPayoutBeneficiary['account_name']) ?>
        · ****<?= e(substr($agentPayoutBeneficiary['account_number'], -4)) ?>
      </p>
    <?php else: ?>
      <p class="small text-muted mb-2">No verified payout account is currently saved.</p>
    <?php endif; ?>
    <?php if (!iworks_agent_payout_enabled()): ?>
      <div class="alert alert-warning small mb-0">
        Paystack TEST payouts are not configured. Finance must complete the test-only setup before verification.
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
          <button class="btn btn-outline-primary w-100" type="submit">Verify account</button>
        </div>
      </form>
      <p class="small text-muted mt-2 mb-0">Paystack resolves the account holder name and creates a TEST recipient. Editing bank details later requires re-verification.</p>
    <?php endif; ?>
  </div></div>

  <div class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-3">Request withdrawal</h2>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><small class="kicker">Earned NGN commission</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['total_earned'], 'NGN')) ?></div></div>
      <div class="col-md-4"><small class="kicker">Available balance</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['available_balance'], 'NGN')) ?></div></div>
      <div class="col-md-4"><small class="kicker">Minimum withdrawal</small><div class="fw-semibold">₦5,000</div></div>
    </div>
    <form method="post" action="<?= e(url('withdrawals')) ?>" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="request_withdrawal">
      <div class="col-md-4">
        <label class="form-label" for="withdrawalAmount">Amount (NGN)</label>
        <input id="withdrawalAmount" class="form-control" name="amount" type="number" min="5000" step="0.01" required>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="withdrawalNote">Note (optional)</label>
        <input id="withdrawalNote" class="form-control" name="note" maxlength="500" placeholder="Withdrawal note">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100" type="submit" <?= $canWithdraw ? '' : 'disabled' ?>>Submit request</button>
      </div>
    </form>
    <p class="small text-muted mb-0 mt-2">
      ₦5,000–₦100,000: queued automatically for test transfers. Above ₦100,000: manual Finance review.
      If test processing is disabled, a submitted request is held without contacting Paystack.
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
            <?php else: ?><span class="small text-muted">Legacy manual record</span><?php endif; ?>
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
