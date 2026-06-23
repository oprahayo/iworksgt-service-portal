<?php $user = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Withdrawals</h1>
</div>

<?php if ($user['role'] === 'agent' && $withdrawalSummary): ?>
<div class="card mb-3">
  <div class="card-body">
    <h2 class="h5 mb-3">Request withdrawal</h2>
    <div class="row g-3 mb-3">
      <div class="col-md-3"><small class="kicker">Total earned</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['total_earned'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Available balance</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['available_balance'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Minimum threshold</small><div class="fw-semibold"><?= e(money((float) $withdrawalSummary['minimum_threshold'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Interval limit</small><div class="fw-semibold">Every <?= (int) $withdrawalSummary['cooldown_days'] ?> days</div></div>
    </div>

    <?php if (!$withdrawalSummary['can_request'] && !empty($withdrawalSummary['next_request_at'])): ?>
      <div class="alert alert-warning py-2">Next withdrawal request time: <?= e($withdrawalSummary['next_request_at']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('withdrawals')) ?>" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="request_withdrawal">
      <div class="col-md-4">
        <label class="form-label">Amount</label>
        <input class="form-control" name="amount" type="number" min="0" step="0.01" required>
      </div>
      <div class="col-md-5">
        <label class="form-label">Note (optional)</label>
        <input class="form-control" name="note" placeholder="Any payout note">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100" type="submit" <?= !$withdrawalSummary['can_request'] ? 'disabled' : '' ?>>Submit request</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-rows table-hover align-middle mb-0">
      <thead>
      <tr>
        <th>Agent</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Requested</th>
        <th>Admin note</th>
        <?php if (has_role(['admin', 'finance'])): ?><th></th><?php endif; ?>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($withdrawals as $request): ?>
        <tr>
          <td data-label="Agent"><?= e($request['agent_name']) ?></td>
          <td data-label="Amount"><?= e(money((float) $request['amount'], $request['currency'] ?? default_currency())) ?></td>
          <td data-label="Status"><?= status_badge($request['status']) ?></td>
          <td data-label="Requested"><?= e($request['requested_at']) ?></td>
          <td data-label="Admin note"><?= e($request['admin_note'] ?: '-') ?></td>
          <?php if (has_role(['admin', 'finance'])): ?>
            <td data-label="Action" class="cell-form">
              <form method="post" action="<?= e(url('withdrawals')) ?>" class="d-flex gap-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="update_withdrawal_status">
                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                <select class="form-select form-select-sm" name="status">
                  <?php foreach (['pending', 'approved', 'paid', 'rejected'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected_if($status, $request['status']) ?>><?= e(ucwords($status)) ?></option>
                  <?php endforeach; ?>
                </select>
                <input class="form-control form-control-sm" name="admin_note" placeholder="Note" value="<?= e($request['admin_note'] ?? '') ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
              </form>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$withdrawals): ?>
        <tr class="table-empty-row"><td colspan="<?= has_role(['admin', 'finance']) ? '6' : '5' ?>" class="text-center text-muted py-4">No withdrawal requests yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
