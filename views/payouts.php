<?php
$payoutFilters = is_array($payoutFilters ?? null) ? $payoutFilters : [];
$payoutStatusOptions = ['accrued', 'approved', 'paid'];
$payoutSortOptions = [
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
    'amount_high' => 'Amount high to low',
    'amount_low' => 'Amount low to high',
    'status' => 'Sort by status',
    'agent' => 'Sort by agent',
];
$payoutPerPageOptions = [10, 25, 50, 100];
$payoutPagination = is_array($payoutPagination ?? null) ? $payoutPagination : [
    'page' => 1,
    'per_page' => (int) ($payoutFilters['per_page'] ?? 10),
    'total' => is_array($payouts ?? null) ? count($payouts) : 0,
    'total_pages' => 1,
    'from' => !empty($payouts) ? 1 : 0,
    'to' => is_array($payouts ?? null) ? count($payouts) : 0,
];
$payoutPerPageCurrent = (int) ($payoutPagination['per_page'] ?? 10);
if (!in_array($payoutPerPageCurrent, $payoutPerPageOptions, true)) {
    $payoutPerPageCurrent = 10;
}
$currentPayoutPage = max(1, (int) ($payoutPagination['page'] ?? 1));
$totalPayoutPages = max(1, (int) ($payoutPagination['total_pages'] ?? 1));
$payoutPaginationBase = [
    'status' => (string) ($payoutFilters['status'] ?? ''),
    'sort' => (string) ($payoutFilters['sort'] ?? 'newest'),
    'per_page' => $payoutPerPageCurrent,
];
$payoutActionUrl = url('payouts', array_merge($payoutPaginationBase, ['p' => $currentPayoutPage]));
$hasPayoutFilters = (string) ($payoutFilters['status'] ?? '') !== ''
    || (string) ($payoutFilters['sort'] ?? 'newest') !== 'newest'
    || $payoutPerPageCurrent !== 10;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Agent Commission Payouts</h1>
  <button class="btn btn-sm btn-outline-secondary mobile-filter-toggle" type="button" id="payoutFilterToggle" aria-label="Open payout filters">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="card mb-3 mobile-filter-panel <?= $hasPayoutFilters ? 'open' : '' ?>" id="payoutFilterPanel">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end" method="get" action="index.php">
      <input type="hidden" name="page" value="payouts">
      <input type="hidden" name="p" value="1">
      <div class="col-sm-6 col-lg-4">
        <label class="form-label mb-1">Status</label>
        <select class="form-select" name="status">
          <option value="">All statuses</option>
          <?php foreach ($payoutStatusOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($payoutFilters['status'] ?? '')) ?>><?= e(ucwords($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-4">
        <label class="form-label mb-1">Sort</label>
        <select class="form-select" name="sort">
          <?php foreach ($payoutSortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= selected_if($key, (string) ($payoutFilters['sort'] ?? 'newest')) ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Per page</label>
        <select class="form-select" name="per_page">
          <?php foreach ($payoutPerPageOptions as $opt): ?>
            <option value="<?= (int) $opt ?>" <?= selected_if((string) $opt, (string) $payoutPerPageCurrent) ?>><?= (int) $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2 d-grid">
        <button class="btn btn-outline-primary" type="submit">Apply</button>
      </div>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-3">
    <form method="post" action="<?= e($payoutActionUrl) ?>" id="payoutBulkForm" class="row g-2 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="bulk_update_payout_status">
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Bulk action</label>
        <select class="form-select" name="bulk_status" required>
          <?php foreach ($payoutStatusOptions as $status): ?>
            <option value="<?= e($status) ?>"><?= e('Set status: ' . ucwords($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2 d-grid">
        <button type="submit" class="btn btn-primary">Apply action</button>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button type="button" class="btn btn-outline-secondary" id="payoutCheckAllBtn">Check all</button>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button type="button" class="btn btn-outline-secondary" id="payoutUncheckAllBtn">Uncheck all</button>
      </div>
      <div class="col-12 col-lg-3 text-muted small d-flex align-items-center">
        <span id="payoutSelectedCount">0</span>&nbsp;selected
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-cards table-hover align-middle mb-0">
      <thead>
      <tr>
        <th style="width: 52px;">
          <input class="form-check-input" type="checkbox" id="payoutSelectAll" aria-label="Select all payouts">
        </th>
        <th>Agent</th>
        <th>Job</th>
        <th>Service</th>
        <th>Commission</th>
        <th>Bank details</th>
        <th>Status</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($payouts as $payout): ?>
        <tr>
          <td data-label="Select">
            <input
              class="form-check-input payout-row-check"
              type="checkbox"
              name="payout_ids[]"
              value="<?= (int) $payout['id'] ?>"
              form="payoutBulkForm"
            >
          </td>
          <td data-label="Agent"><?= e($payout['agent_name']) ?></td>
          <td data-label="Job"><?= e($payout['job_code']) ?></td>
          <td data-label="Service"><?= e($payout['service_name']) ?></td>
          <td data-label="Commission"><?= e(money((float) $payout['commission_amount'], $payout['currency'] ?? default_currency())) ?></td>
          <td data-label="Bank details">
            <div><?= e($payout['bank_name'] ?: '-') ?></div>
            <small class="text-muted"><?= e(($payout['bank_account_name'] ?: '-') . ' / ' . ($payout['bank_account_number'] ?: '-')) ?></small>
          </td>
          <td data-label="Status"><?= status_badge($payout['status']) ?></td>
          <td data-label="Action" class="cell-form">
            <form method="post" action="<?= e($payoutActionUrl) ?>" class="d-flex gap-2">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="update_payout_status">
              <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
              <select class="form-select form-select-sm" name="status">
                <?php foreach ($payoutStatusOptions as $status): ?>
                  <option value="<?= e($status) ?>" <?= selected_if($status, $payout['status']) ?>><?= e(ucwords($status)) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payouts): ?>
        <tr class="table-empty-row"><td colspan="8" class="text-center text-muted py-4">No commission records yet. Records appear after paid + delivered jobs.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
  $payoutTotal = (int) ($payoutPagination['total'] ?? 0);
  $payoutFrom = (int) ($payoutPagination['from'] ?? 0);
  $payoutTo = (int) ($payoutPagination['to'] ?? 0);
  $payoutPageStart = max(1, $currentPayoutPage - 2);
  $payoutPageEnd = min($totalPayoutPages, $currentPayoutPage + 2);
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
  <small class="text-muted">Showing <?= (int) $payoutFrom ?> - <?= (int) $payoutTo ?> of <?= (int) $payoutTotal ?> payouts</small>
  <nav aria-label="Payout pagination">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $currentPayoutPage <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('payouts', array_merge($payoutPaginationBase, ['p' => max(1, $currentPayoutPage - 1)]))) ?>">Previous</a>
      </li>
      <?php if ($payoutPageStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('payouts', array_merge($payoutPaginationBase, ['p' => 1]))) ?>">1</a></li>
        <?php if ($payoutPageStart > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($pageNo = $payoutPageStart; $pageNo <= $payoutPageEnd; $pageNo++): ?>
        <li class="page-item <?= $pageNo === $currentPayoutPage ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(url('payouts', array_merge($payoutPaginationBase, ['p' => $pageNo]))) ?>"><?= (int) $pageNo ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($payoutPageEnd < $totalPayoutPages): ?>
        <?php if ($payoutPageEnd < ($totalPayoutPages - 1)): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('payouts', array_merge($payoutPaginationBase, ['p' => $totalPayoutPages]))) ?>"><?= (int) $totalPayoutPages ?></a></li>
      <?php endif; ?>

      <li class="page-item <?= $currentPayoutPage >= $totalPayoutPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('payouts', array_merge($payoutPaginationBase, ['p' => min($totalPayoutPages, $currentPayoutPage + 1)]))) ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>

<script>
(function () {
  var filterToggle = document.getElementById('payoutFilterToggle');
  var filterPanel = document.getElementById('payoutFilterPanel');
  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      filterPanel.classList.toggle('open');
    });
  }

  var selectAllBox = document.getElementById('payoutSelectAll');
  var checkAllBtn = document.getElementById('payoutCheckAllBtn');
  var uncheckAllBtn = document.getElementById('payoutUncheckAllBtn');
  var selectedCountEl = document.getElementById('payoutSelectedCount');
  var rowChecks = Array.prototype.slice.call(document.querySelectorAll('.payout-row-check'));

  var updateSelectedCount = function () {
    if (!selectedCountEl) {
      return;
    }
    var selected = rowChecks.filter(function (checkbox) {
      return checkbox.checked;
    }).length;
    selectedCountEl.textContent = String(selected);
    if (selectAllBox) {
      selectAllBox.checked = rowChecks.length > 0 && selected === rowChecks.length;
      selectAllBox.indeterminate = selected > 0 && selected < rowChecks.length;
    }
  };

  var setAllChecked = function (checked) {
    rowChecks.forEach(function (checkbox) {
      checkbox.checked = checked;
    });
    updateSelectedCount();
  };

  if (selectAllBox) {
    selectAllBox.addEventListener('change', function () {
      setAllChecked(selectAllBox.checked);
    });
  }
  if (checkAllBtn) {
    checkAllBtn.addEventListener('click', function () {
      setAllChecked(true);
    });
  }
  if (uncheckAllBtn) {
    uncheckAllBtn.addEventListener('click', function () {
      setAllChecked(false);
    });
  }
  rowChecks.forEach(function (checkbox) {
    checkbox.addEventListener('change', updateSelectedCount);
  });

  updateSelectedCount();
})();
</script>
