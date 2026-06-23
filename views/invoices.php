<?php
$invoiceFilters = is_array($invoiceFilters ?? null) ? $invoiceFilters : [];
$invoiceStatusOptions = ['draft', 'issued', 'paid', 'void'];
$invoicePaymentOptions = ['unpaid', 'partial', 'paid', 'refunded'];
$invoiceSortOptions = [
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
    'amount_high' => 'Amount high to low',
    'amount_low' => 'Amount low to high',
    'status' => 'Sort by invoice status',
    'payment' => 'Sort by payment status',
];
$paymentStatusLabels = [
    'partial' => 'Partially Paid',
];
$invoicePerPageOptions = [10, 25, 50, 100];
$invoicePagination = is_array($invoicePagination ?? null) ? $invoicePagination : [
    'page' => 1,
    'per_page' => (int) ($invoiceFilters['per_page'] ?? 10),
    'total' => is_array($invoices ?? null) ? count($invoices) : 0,
    'total_pages' => 1,
    'from' => !empty($invoices) ? 1 : 0,
    'to' => is_array($invoices ?? null) ? count($invoices) : 0,
];
$invoicePerPageCurrent = (int) ($invoicePagination['per_page'] ?? 10);
if (!in_array($invoicePerPageCurrent, $invoicePerPageOptions, true)) {
    $invoicePerPageCurrent = 10;
}
$currentInvoicePage = max(1, (int) ($invoicePagination['page'] ?? 1));
$totalInvoicePages = max(1, (int) ($invoicePagination['total_pages'] ?? 1));
$invoicePaginationBase = [
    'status' => (string) ($invoiceFilters['status'] ?? ''),
    'payment_status' => (string) ($invoiceFilters['payment_status'] ?? ''),
    'sort' => (string) ($invoiceFilters['sort'] ?? 'newest'),
    'per_page' => $invoicePerPageCurrent,
];
$hasInvoiceFilters = (string) ($invoiceFilters['status'] ?? '') !== ''
    || (string) ($invoiceFilters['payment_status'] ?? '') !== ''
    || (string) ($invoiceFilters['sort'] ?? 'newest') !== 'newest'
    || $invoicePerPageCurrent !== 10;
$currentUser = current_user();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Invoices</h1>
  <button class="btn btn-sm btn-outline-secondary mobile-filter-toggle" type="button" id="invoiceFilterToggle" aria-label="Open invoice filters">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="card mb-3 mobile-filter-panel <?= $hasInvoiceFilters ? 'open' : '' ?>" id="invoiceFilterPanel">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end" method="get" action="index.php">
      <input type="hidden" name="page" value="invoices">
      <input type="hidden" name="p" value="1">
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Invoice status</label>
        <select class="form-select" name="status">
          <option value="">All invoice statuses</option>
          <?php foreach ($invoiceStatusOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($invoiceFilters['status'] ?? '')) ?>><?= e(ucwords($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Payment status</label>
        <select class="form-select" name="payment_status">
          <option value="">All payment statuses</option>
          <?php foreach ($invoicePaymentOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($invoiceFilters['payment_status'] ?? '')) ?>><?= e($paymentStatusLabels[$status] ?? ucwords($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Sort</label>
        <select class="form-select" name="sort">
          <?php foreach ($invoiceSortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= selected_if($key, (string) ($invoiceFilters['sort'] ?? 'newest')) ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Per page</label>
        <select class="form-select" name="per_page">
          <?php foreach ($invoicePerPageOptions as $opt): ?>
            <option value="<?= (int) $opt ?>" <?= selected_if((string) $opt, (string) $invoicePerPageCurrent) ?>><?= (int) $opt ?></option>
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
    <table class="table table-mobile-rows table-hover align-middle mb-0">
      <thead>
      <tr>
        <th>Invoice</th>
        <th>Job</th>
        <?php if (has_role(['admin', 'finance'])): ?><th>Client</th><?php endif; ?>
        <th>Payment</th>
        <th>Amount</th>
        <th>Status</th>
        <th class="text-end">Action</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($invoices as $invoice): ?>
        <?php
          $invoicePaymentStatus = (string) ($invoice['payment_status_effective'] ?? $invoice['payment_status'] ?? 'unpaid');
          $isClientUnpaidDraft = $currentUser['role'] === 'client'
            && $invoicePaymentStatus === 'unpaid'
            && (string) ($invoice['status'] ?? 'issued') !== 'paid';
          $canPay = $currentUser['role'] === 'client' && (string) ($invoice['status'] ?? 'issued') !== 'paid';
        ?>
        <tr>
          <td><?= e($invoice['invoice_number']) ?></td>
          <td>
            <div><?= e($invoice['job_code']) ?></div>
            <small class="text-muted"><?= e($invoice['service_name']) ?></small>
          </td>
          <?php if (has_role(['admin', 'finance'])): ?><td><?= e($invoice['client_name'] ?? '-') ?></td><?php endif; ?>
          <td><?= status_badge($invoicePaymentStatus) ?></td>
          <td><?= e(money((float) $invoice['amount'], $invoice['currency'] ?? default_currency())) ?></td>
          <td><?= status_badge((string) ($invoice['status'] ?? 'issued')) ?></td>
          <td class="text-end cell-actions">
            <div class="d-inline-flex gap-1 align-items-center flex-wrap justify-content-end">
              <?php if ($canPay): ?>
                <a
                  class="btn btn-sm btn-primary"
                  href="invoice.php?id=<?= (int) $invoice['id'] ?>"
                >Pay now</a>
              <?php else: ?>
                <button
                  class="btn btn-sm btn-outline-primary js-open-invoice-modal"
                  type="button"
                  data-bs-toggle="modal"
                  data-bs-target="#invoicePreviewModal"
                  data-invoice-url="invoice.php?id=<?= (int) $invoice['id'] ?>"
                >View</button>
              <?php endif; ?>

              <?php if ($isClientUnpaidDraft): ?>
                <button
                  class="btn btn-sm btn-outline-secondary js-open-invoice-edit"
                  type="button"
                  data-bs-toggle="modal"
                  data-bs-target="#invoiceEditModal"
                  data-invoice-id="<?= (int) $invoice['id'] ?>"
                  data-job-title="<?= e((string) ($invoice['job_title'] ?? $invoice['service_name'])) ?>"
                  data-job-description="<?= e((string) ($invoice['job_description'] ?? '')) ?>"
                >Edit</button>

                <form method="post" action="<?= e(url('invoices')) ?>" onsubmit="return confirm('Delete this unpaid invoice and its order?');" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_unpaid_invoice">
                  <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$invoices): ?>
        <tr><td colspan="<?= has_role(['admin', 'finance']) ? '7' : '6' ?>" class="text-center text-muted py-4">No invoices yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
  $invoiceTotal = (int) ($invoicePagination['total'] ?? 0);
  $invoiceFrom = (int) ($invoicePagination['from'] ?? 0);
  $invoiceTo = (int) ($invoicePagination['to'] ?? 0);
  $invoicePageStart = max(1, $currentInvoicePage - 2);
  $invoicePageEnd = min($totalInvoicePages, $currentInvoicePage + 2);
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
  <small class="text-muted">Showing <?= (int) $invoiceFrom ?> - <?= (int) $invoiceTo ?> of <?= (int) $invoiceTotal ?> invoices</small>
  <nav aria-label="Invoices pagination">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $currentInvoicePage <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('invoices', array_merge($invoicePaginationBase, ['p' => max(1, $currentInvoicePage - 1)]))) ?>">Previous</a>
      </li>
      <?php if ($invoicePageStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('invoices', array_merge($invoicePaginationBase, ['p' => 1]))) ?>">1</a></li>
        <?php if ($invoicePageStart > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($pageNo = $invoicePageStart; $pageNo <= $invoicePageEnd; $pageNo++): ?>
        <li class="page-item <?= $pageNo === $currentInvoicePage ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(url('invoices', array_merge($invoicePaginationBase, ['p' => $pageNo]))) ?>"><?= (int) $pageNo ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($invoicePageEnd < $totalInvoicePages): ?>
        <?php if ($invoicePageEnd < ($totalInvoicePages - 1)): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('invoices', array_merge($invoicePaginationBase, ['p' => $totalInvoicePages]))) ?>"><?= (int) $totalInvoicePages ?></a></li>
      <?php endif; ?>

      <li class="page-item <?= $currentInvoicePage >= $totalInvoicePages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('invoices', array_merge($invoicePaginationBase, ['p' => min($totalInvoicePages, $currentInvoicePage + 1)]))) ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>

<div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable invoice-modal-fast">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="h5 mb-0">Invoice Preview</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 position-relative">
        <div class="invoice-preview-loading d-flex flex-column align-items-center justify-content-center gap-2 p-3" id="invoicePreviewLoading">
          <div class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></div>
          <small class="text-muted">Loading invoice preview...</small>
          <a class="btn btn-sm btn-outline-secondary d-none" id="invoicePreviewFallbackLink" href="#" target="_blank" rel="noopener noreferrer">Open invoice in new tab</a>
        </div>
        <iframe id="invoicePreviewFrame" src="about:blank" style="width:100%;height:78vh;border:0;" class="d-none"></iframe>
      </div>
    </div>
  </div>
</div>

<?php if ($currentUser['role'] === 'client'): ?>
<div class="modal fade" id="invoiceEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="h5 mb-0">Edit Unpaid Invoice</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= e(url('invoices')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="edit_unpaid_invoice">
        <input type="hidden" name="invoice_id" id="editInvoiceId" value="">
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Order title</label>
            <input class="form-control" id="editInvoiceTitle" name="job_title" required>
          </div>
          <div>
            <label class="form-label">Order description</label>
            <textarea class="form-control" id="editInvoiceDescription" name="job_description" rows="4"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var filterToggle = document.getElementById('invoiceFilterToggle');
  var filterPanel = document.getElementById('invoiceFilterPanel');
  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      filterPanel.classList.toggle('open');
    });
  }

  var frame = document.getElementById('invoicePreviewFrame');
  var loading = document.getElementById('invoicePreviewLoading');
  var fallbackLink = document.getElementById('invoicePreviewFallbackLink');
  var previewLoadTimer = null;
  var pendingInvoiceUrl = '';

  function resetPreviewFrame() {
    if (previewLoadTimer) {
      window.clearTimeout(previewLoadTimer);
      previewLoadTimer = null;
    }
    if (frame) {
      frame.classList.add('d-none');
      frame.setAttribute('src', 'about:blank');
    }
    if (loading) {
      loading.classList.remove('d-none');
    }
    if (fallbackLink) {
      fallbackLink.classList.add('d-none');
      fallbackLink.setAttribute('href', '#');
    }
    pendingInvoiceUrl = '';
  }

  if (frame) {
    frame.addEventListener('load', function () {
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      if (loading) {
        loading.classList.add('d-none');
      }
      frame.classList.remove('d-none');
    });

    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('.js-open-invoice-modal');
      if (!trigger) return;
      var url = trigger.getAttribute('data-invoice-url') || '';
      if (url !== '') {
        resetPreviewFrame();
        pendingInvoiceUrl = url;
        var joiner = url.indexOf('?') === -1 ? '?' : '&';
        frame.setAttribute('src', url + joiner + '_pv=' + String(Date.now()));
        previewLoadTimer = window.setTimeout(function () {
          if (loading) {
            loading.classList.remove('d-none');
          }
          if (fallbackLink) {
            fallbackLink.classList.remove('d-none');
            fallbackLink.setAttribute('href', pendingInvoiceUrl || url);
          }
        }, 5000);
      }
    });
  }

  var previewModal = document.getElementById('invoicePreviewModal');
  if (previewModal && frame) {
    previewModal.addEventListener('hidden.bs.modal', function () {
      resetPreviewFrame();
    });

    window.addEventListener('message', function (event) {
      if (event.origin !== window.location.origin) {
        return;
      }
      var data = event.data || {};
      if (!data || data.type !== 'iworksgt_invoice_preview_close') {
        return;
      }
      if (!window.bootstrap || !window.bootstrap.Modal) {
        return;
      }
      var modalInstance = window.bootstrap.Modal.getOrCreateInstance(previewModal);
      modalInstance.hide();
    });
  }

  var editInvoiceId = document.getElementById('editInvoiceId');
  var editInvoiceTitle = document.getElementById('editInvoiceTitle');
  var editInvoiceDescription = document.getElementById('editInvoiceDescription');
  if (editInvoiceId && editInvoiceTitle && editInvoiceDescription) {
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('.js-open-invoice-edit');
      if (!trigger) return;
      editInvoiceId.value = trigger.getAttribute('data-invoice-id') || '';
      editInvoiceTitle.value = trigger.getAttribute('data-job-title') || '';
      editInvoiceDescription.value = trigger.getAttribute('data-job-description') || '';
    });
  }
})();
</script>
