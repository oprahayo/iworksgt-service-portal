<?php
$paymentFilters = is_array($paymentFilters ?? null) ? $paymentFilters : [];
$paymentStatusOptions = ['submitted', 'confirmed', 'rejected'];
$paymentSortOptions = [
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
    'amount_high' => 'Amount high to low',
    'amount_low' => 'Amount low to high',
    'status' => 'Sort by status',
    'method' => 'Sort by method',
];
$paymentPerPageOptions = [10, 25, 50, 100];
$paymentPagination = is_array($paymentPagination ?? null) ? $paymentPagination : [
    'page' => 1,
    'per_page' => (int) ($paymentFilters['per_page'] ?? 10),
    'total' => is_array($payments ?? null) ? count($payments) : 0,
    'total_pages' => 1,
    'from' => !empty($payments) ? 1 : 0,
    'to' => is_array($payments ?? null) ? count($payments) : 0,
];
$paymentPerPageCurrent = (int) ($paymentPagination['per_page'] ?? 10);
if (!in_array($paymentPerPageCurrent, $paymentPerPageOptions, true)) {
    $paymentPerPageCurrent = 10;
}
$currentPaymentPage = max(1, (int) ($paymentPagination['page'] ?? 1));
$totalPaymentPages = max(1, (int) ($paymentPagination['total_pages'] ?? 1));
$currentMethodFilter = (int) ($paymentFilters['method_id'] ?? 0);
$paymentPaginationBase = [
    'status' => (string) ($paymentFilters['status'] ?? ''),
    'method_id' => $currentMethodFilter > 0 ? $currentMethodFilter : '',
    'sort' => (string) ($paymentFilters['sort'] ?? 'newest'),
    'per_page' => $paymentPerPageCurrent,
];
$paymentActionUrl = url('payments', array_merge($paymentPaginationBase, ['p' => $currentPaymentPage]));
$hasPaymentFilters = (string) ($paymentFilters['status'] ?? '') !== ''
    || $currentMethodFilter > 0
    || (string) ($paymentFilters['sort'] ?? 'newest') !== 'newest'
    || $paymentPerPageCurrent !== 10;
$paymentMethodOptions = is_array($paymentMethodOptions ?? null) ? $paymentMethodOptions : [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Payment Reviews</h1>
  <button class="btn btn-sm btn-outline-secondary mobile-filter-toggle" type="button" id="paymentFilterToggle" aria-label="Open payment filters">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="card mb-3 mobile-filter-panel <?= $hasPaymentFilters ? 'open' : '' ?>" id="paymentFilterPanel">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end" method="get" action="index.php">
      <input type="hidden" name="page" value="payments">
      <input type="hidden" name="p" value="1">
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Status</label>
        <select class="form-select" name="status">
          <option value="">All statuses</option>
          <?php foreach ($paymentStatusOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($paymentFilters['status'] ?? '')) ?>><?= e(ucwords($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Method</label>
        <select class="form-select" name="method_id">
          <option value="">All methods</option>
          <?php foreach ($paymentMethodOptions as $method): ?>
            <option value="<?= (int) $method['id'] ?>" <?= selected_if((string) $method['id'], (string) $currentMethodFilter) ?>><?= e((string) ($method['display_name'] ?? 'Method')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Sort</label>
        <select class="form-select" name="sort">
          <?php foreach ($paymentSortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= selected_if($key, (string) ($paymentFilters['sort'] ?? 'newest')) ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Per page</label>
        <select class="form-select" name="per_page">
          <?php foreach ($paymentPerPageOptions as $opt): ?>
            <option value="<?= (int) $opt ?>" <?= selected_if((string) $opt, (string) $paymentPerPageCurrent) ?>><?= (int) $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2 d-grid">
        <button class="btn btn-outline-primary" type="submit">Apply</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-cards table-hover align-middle mb-0">
      <thead>
      <tr>
        <th>Invoice</th>
        <th>Client</th>
        <th>Method</th>
        <th>Reference</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Proof</th>
        <th>Submitted</th>
        <th style="min-width: 280px;">Review</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($payments as $payment): ?>
        <tr>
          <td data-label="Invoice">
            <div class="fw-semibold"><?= e($payment['invoice_number']) ?></div>
            <small class="text-muted"><?= e($payment['job_code']) ?></small>
          </td>
          <td data-label="Client"><?= e($payment['client_name']) ?></td>
          <td data-label="Method"><?= e($payment['method_name']) ?></td>
          <td data-label="Reference">
            <?= e($payment['reference']) ?>
            <?php if (!empty($payment['note'])): ?>
              <div class="small text-muted"><?= e($payment['note']) ?></div>
            <?php endif; ?>
          </td>
          <td data-label="Amount"><?= e(money((float) $payment['amount'], $payment['invoice_currency'] ?? default_currency())) ?></td>
          <td data-label="Status">
            <?= status_badge($payment['status']) ?>
            <?php if (!empty($payment['rejection_reason'])): ?>
              <div class="small text-danger mt-1"><?= e($payment['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td data-label="Proof" class="cell-actions">
            <?php if (!empty($payment['proof_path'])): ?>
              <a class="btn btn-sm btn-outline-secondary" href="download.php?payment_id=<?= (int) $payment['id'] ?>">Download</a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td data-label="Submitted">
            <div><?= e($payment['created_at']) ?></div>
            <?php if (!empty($payment['reviewer_name'])): ?>
              <small class="text-muted">By <?= e($payment['reviewer_name']) ?></small>
            <?php endif; ?>
          </td>
          <td data-label="Review" class="cell-form">
            <form method="post" action="<?= e($paymentActionUrl) ?>" class="d-flex flex-column gap-2">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="review_payment">
              <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
              <select class="form-select form-select-sm" name="status" required>
                <option value="confirmed" <?= selected_if('confirmed', $payment['status']) ?>>Confirm</option>
                <option value="rejected" <?= selected_if('rejected', $payment['status']) ?>>Reject</option>
              </select>
              <input class="form-control form-control-sm" name="review_note" placeholder="Reason (required for rejection)">
              <button class="btn btn-sm btn-outline-primary" type="submit">Save review</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?>
        <tr class="table-empty-row"><td colspan="9" class="text-center text-muted py-4">No submitted payments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
  $paymentTotal = (int) ($paymentPagination['total'] ?? 0);
  $paymentFrom = (int) ($paymentPagination['from'] ?? 0);
  $paymentTo = (int) ($paymentPagination['to'] ?? 0);
  $paymentPageStart = max(1, $currentPaymentPage - 2);
  $paymentPageEnd = min($totalPaymentPages, $currentPaymentPage + 2);
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
  <small class="text-muted">Showing <?= (int) $paymentFrom ?> - <?= (int) $paymentTo ?> of <?= (int) $paymentTotal ?> payments</small>
  <nav aria-label="Payments pagination">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $currentPaymentPage <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('payments', array_merge($paymentPaginationBase, ['p' => max(1, $currentPaymentPage - 1)]))) ?>">Previous</a>
      </li>
      <?php if ($paymentPageStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('payments', array_merge($paymentPaginationBase, ['p' => 1]))) ?>">1</a></li>
        <?php if ($paymentPageStart > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($pageNo = $paymentPageStart; $pageNo <= $paymentPageEnd; $pageNo++): ?>
        <li class="page-item <?= $pageNo === $currentPaymentPage ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(url('payments', array_merge($paymentPaginationBase, ['p' => $pageNo]))) ?>"><?= (int) $pageNo ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($paymentPageEnd < $totalPaymentPages): ?>
        <?php if ($paymentPageEnd < ($totalPaymentPages - 1)): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('payments', array_merge($paymentPaginationBase, ['p' => $totalPaymentPages]))) ?>"><?= (int) $totalPaymentPages ?></a></li>
      <?php endif; ?>

      <li class="page-item <?= $currentPaymentPage >= $totalPaymentPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('payments', array_merge($paymentPaginationBase, ['p' => min($totalPaymentPages, $currentPaymentPage + 1)]))) ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>

<script>
(function () {
  var filterToggle = document.getElementById('paymentFilterToggle');
  var filterPanel = document.getElementById('paymentFilterPanel');
  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      filterPanel.classList.toggle('open');
    });
  }
})();
</script>
