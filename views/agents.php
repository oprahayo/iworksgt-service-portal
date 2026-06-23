<?php $viewer = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Agents</h1>
  <?php if (has_role(['admin'])): ?>
    <a class="btn btn-primary" href="<?= e(url('agents', ['action' => 'new'])) ?>">Add agent</a>
  <?php endif; ?>
</div>

<?php if ($showCreateForm && has_role(['admin'])): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="h5">Create agent user</h2>
    <form method="post" action="<?= e(url('agents')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="create_agent">
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Full name</label><input class="form-control" name="full_name" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
        <div class="col-md-4"><label class="form-label">Temporary password</label><input class="form-control" type="text" name="password" required minlength="6"></div>
        <div class="col-md-4"><label class="form-label">Bank name</label><input class="form-control" name="bank_name"></div>
        <div class="col-md-4"><label class="form-label">Account name</label><input class="form-control" name="bank_account_name"></div>
        <div class="col-md-4"><label class="form-label">Account number</label><input class="form-control" name="bank_account_number"></div>
        <div class="col-md-4"><label class="form-label">SWIFT / sort code</label><input class="form-control" name="bank_swift"></div>
        <div class="col-md-4"><label class="form-label">Currency</label><input class="form-control" name="bank_currency" value="<?= e(default_currency()) ?>"></div>
      </div>
      <button class="btn btn-primary mt-3" type="submit">Create agent</button>
      <a class="btn btn-outline-secondary mt-3" href="<?= e(url('agents')) ?>">Cancel</a>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-cards table-hover align-middle mb-0">
      <thead>
      <tr><th>Agent</th><th>Email</th><th>Phone</th><th>Bank</th><th>Account</th><th>Currency</th></tr>
      </thead>
      <tbody>
      <?php foreach ($agents as $agent): ?>
        <?php $fullBank = can_view_full_bank_details($viewer, $agent); ?>
        <tr>
          <td data-label="Agent"><?= e($agent['full_name']) ?></td>
          <td data-label="Email"><?= e($agent['email']) ?></td>
          <td data-label="Phone"><?= e($agent['phone']) ?></td>
          <td data-label="Bank"><?= e($agent['bank_name'] ?: '-') ?></td>
          <td data-label="Account">
            <?php if ($fullBank): ?>
              <?= e($agent['bank_account_name'] ?: '-') ?>
              <?php if (!empty($agent['bank_account_number'])): ?>
                <br><small class="text-muted"><?= e($agent['bank_account_number']) ?></small>
              <?php endif; ?>
            <?php else: ?>
              <?= e($agent['bank_account_name'] ?: '-') ?>
              <?php if (!empty($agent['bank_account_number'])): ?>
                <br><small class="text-muted"><?= e(mask_account_number($agent['bank_account_number'])) ?></small>
              <?php endif; ?>
            <?php endif; ?>
          </td>
          <td data-label="Currency"><?= e($agent['bank_currency'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$agents): ?>
        <tr class="table-empty-row"><td colspan="6" class="text-center text-muted py-4">No agents found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
