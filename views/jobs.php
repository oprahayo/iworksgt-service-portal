<?php
$user = current_user();
$jobFilters = is_array($jobFilters ?? null) ? $jobFilters : [];
$jobStatusOptions = job_status_options();
$jobPaymentOptions = ['unpaid', 'partial', 'paid', 'refunded'];
$jobSortOptions = [
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
    'amount_high' => 'Amount high to low',
    'amount_low' => 'Amount low to high',
    'status' => 'Sort by status',
    'payment' => 'Sort by payment',
];
$paymentStatusLabels = [
    'partial' => 'Partially Paid',
];
$jobPerPageOptions = [10, 25, 50, 100];
$jobPagination = is_array($jobPagination ?? null) ? $jobPagination : [
    'page' => 1,
    'per_page' => (int) ($jobFilters['per_page'] ?? 10),
    'total' => is_array($jobs ?? null) ? count($jobs) : 0,
    'total_pages' => 1,
    'from' => !empty($jobs) ? 1 : 0,
    'to' => is_array($jobs ?? null) ? count($jobs) : 0,
];
$jobPerPageCurrent = (int) ($jobPagination['per_page'] ?? 10);
if (!in_array($jobPerPageCurrent, $jobPerPageOptions, true)) {
    $jobPerPageCurrent = 10;
}
$currentJobPage = max(1, (int) ($jobPagination['page'] ?? 1));
$totalJobPages = max(1, (int) ($jobPagination['total_pages'] ?? 1));
$compactClientList = $showCreateForm && (($user['role'] ?? '') === 'client');
$jobsTableItems = $compactClientList ? array_slice($jobs, 0, 5) : $jobs;
$jobsTableTotal = $compactClientList ? (int) ($jobPagination['total'] ?? count($jobs)) : count($jobsTableItems);
$jobPaginationBase = [
    'status' => (string) ($jobFilters['status'] ?? ''),
    'payment_status' => (string) ($jobFilters['payment_status'] ?? ''),
    'sort' => (string) ($jobFilters['sort'] ?? 'newest'),
    'per_page' => $jobPerPageCurrent,
];
$jobActionUrl = url('jobs', array_merge($jobPaginationBase, ['p' => $currentJobPage]));
$isAdminJobsPage = has_role(['admin']);
$hasJobFilters = (string) ($jobFilters['status'] ?? '') !== ''
    || (string) ($jobFilters['payment_status'] ?? '') !== ''
    || (string) ($jobFilters['sort'] ?? 'newest') !== 'newest'
    || $jobPerPageCurrent !== 10;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?= $user['role'] === 'client' ? 'My Orders' : 'Jobs' ?></h1>
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-sm btn-outline-secondary mobile-filter-toggle" type="button" id="jobFilterToggle" aria-label="Open job filters">
      <i class="bi bi-list"></i>
    </button>
    <?php if (has_role(['admin', 'client'])): ?>
      <a class="btn btn-primary" href="<?= e(url('jobs', ['action' => 'new'])) ?>"><?= $user['role'] === 'client' ? 'Order New Service' : 'New Job' ?></a>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3 mobile-filter-panel <?= $hasJobFilters ? 'open' : '' ?>" id="jobFilterPanel">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end" method="get" action="index.php">
      <input type="hidden" name="page" value="jobs">
      <input type="hidden" name="p" value="1">
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Status</label>
        <select class="form-select" name="status">
          <option value="">All statuses</option>
          <?php foreach ($jobStatusOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($jobFilters['status'] ?? '')) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-3">
        <label class="form-label mb-1">Payment</label>
        <select class="form-select" name="payment_status">
          <option value="">All payments</option>
          <?php foreach ($jobPaymentOptions as $payment): ?>
            <option value="<?= e($payment) ?>" <?= selected_if($payment, (string) ($jobFilters['payment_status'] ?? '')) ?>><?= e($paymentStatusLabels[$payment] ?? ucwords(str_replace('_', ' ', $payment))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Sort</label>
        <select class="form-select" name="sort">
          <?php foreach ($jobSortOptions as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= selected_if($key, (string) ($jobFilters['sort'] ?? 'newest')) ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2">
        <label class="form-label mb-1">Per page</label>
        <select class="form-select" name="per_page">
          <?php foreach ($jobPerPageOptions as $opt): ?>
            <option value="<?= (int) $opt ?>" <?= selected_if((string) $opt, (string) $jobPerPageCurrent) ?>><?= (int) $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2 d-grid">
        <button class="btn btn-outline-primary" type="submit">Apply</button>
      </div>
    </form>
  </div>
</div>

<?php if ($showCreateForm): ?>
<?php
$defaultPayCurrency = active_pay_currency();
$initialCryptoQuote = get_crypto_checkout_quote(0.0, $defaultPayCurrency, false);
$serviceCategoryMatcher = static function (array $service): string {
    $haystack = strtolower(trim(((string) ($service['name'] ?? '')) . ' ' . ((string) ($service['description'] ?? ''))));
    if ($haystack === '') {
        return 'filing';
    }
    $paymentKeywords = ['payment', 'pay', 'invoice', 'transfer', 'withdraw', 'payout', 'crypto', 'stripe', 'paystack', 'flutterwave', 'bank'];
    foreach ($paymentKeywords as $keyword) {
        if (str_contains($haystack, $keyword)) {
            return 'payment';
        }
    }
    $agentKeywords = ['agent', 'consult', 'support', 'advis', 'mentor', 'coaching', 'training'];
    foreach ($agentKeywords as $keyword) {
        if (str_contains($haystack, $keyword)) {
            return 'agent';
        }
    }
    return 'filing';
};
$serviceCategoryCounts = [
    'all' => count($serviceOptions),
    'filing' => 0,
    'agent' => 0,
    'payment' => 0,
];
$serviceCategoryMap = [];
foreach ($serviceOptions as $serviceOptionRow) {
    $categoryKey = $serviceCategoryMatcher($serviceOptionRow);
    $serviceCategoryMap[(int) ($serviceOptionRow['id'] ?? 0)] = $categoryKey;
    if (isset($serviceCategoryCounts[$categoryKey])) {
        $serviceCategoryCounts[$categoryKey]++;
    }
}
?>
<div class="card mb-4 <?= has_role(['client']) ? 'job-create-card' : '' ?>">
  <div class="card-body">
    <h2 class="h5"><?= $user['role'] === 'client' ? 'Order New Service' : 'Create job' ?></h2>
    <form method="post" action="<?= e(url('jobs')) ?>" id="jobCreateForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="create_job">
      <?php if (has_role(['client'])): ?>
        <input type="hidden" name="client_id" value="<?= (int) $user['id'] ?>">
        <input type="hidden" name="service_id" id="selectedServiceId">
        <input type="hidden" name="title" id="orderTitleInput">
        <input type="hidden" name="pay_currency" id="payCurrencyInput" value="<?= e($defaultPayCurrency) ?>">

        <div class="service-catalog-shell mb-3">
          <div class="service-catalog-toolbar">
            <div class="service-catalog-toolbar-main">
              <button type="button" class="btn btn-outline-secondary service-catalog-back" id="serviceCatalogBackBtn">
                <i class="bi bi-arrow-left"></i>
                <span>Back</span>
              </button>
              <div class="service-catalog-heading">
                <div class="service-catalog-heading-row">
                  <span class="service-catalog-heading-icon"><i class="bi bi-briefcase"></i></span>
                  <h3 class="service-catalog-title mb-0">Business Support Services</h3>
                </div>
                <p class="service-catalog-subtitle mb-0">Select services to add/remove from cart. Use Preview to see full details before checkout.</p>
              </div>
            </div>
            <div class="service-catalog-view-switch" role="group" aria-label="Service list view">
              <button type="button" class="btn btn-sm btn-dark active" id="serviceViewGridBtn"><i class="bi bi-grid"></i> Grid</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="serviceViewListBtn"><i class="bi bi-list"></i> List</button>
            </div>
          </div>
          <div class="service-catalog-filters" id="serviceCategoryChips">
            <button type="button" class="btn btn-sm btn-dark active" data-service-filter="all">All Services (<?= (int) ($serviceCategoryCounts['all'] ?? 0) ?>)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-service-filter="filing"><i class="bi bi-file-earmark-text me-1"></i>Filing Services (<?= (int) ($serviceCategoryCounts['filing'] ?? 0) ?>)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-service-filter="agent"><i class="bi bi-people me-1"></i>Agent Services (<?= (int) ($serviceCategoryCounts['agent'] ?? 0) ?>)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-service-filter="payment"><i class="bi bi-credit-card me-1"></i>Payment Services (<?= (int) ($serviceCategoryCounts['payment'] ?? 0) ?>)</button>
          </div>
        </div>
        <input type="hidden" name="checkout_pay_method" id="checkoutPayMethodInput" value="card">
        <input type="hidden" name="crypto_asset" id="checkoutCryptoAssetInput" value="USDT">
        <input type="hidden" name="crypto_network" id="checkoutCryptoNetworkInput" value="TRC20">

        <div class="checkout-flow-layout">
          <div class="checkout-services-pane">
            <div class="service-catalog-grid">
              <?php foreach ($serviceOptions as $service): ?>
                <?php
                  $rawServiceDescription = (string) ($service['description'] ?? '');
                  $serviceSummary = trim((string) preg_replace('/\s+/', ' ', strip_tags($rawServiceDescription)));
                  if ($serviceSummary === '') {
                      $serviceSummary = 'Service support and processing handled by iWorks Global.';
                  }
                  if (function_exists('mb_strimwidth')) {
                      $serviceSummary = mb_strimwidth($serviceSummary, 0, 90, '...');
                  } elseif (strlen($serviceSummary) > 90) {
                      $serviceSummary = substr($serviceSummary, 0, 87) . '...';
                  }
                  $serviceDescriptionHtml = sanitize_limited_html($rawServiceDescription);
                  if ($serviceDescriptionHtml !== '' && strip_tags($serviceDescriptionHtml) === $serviceDescriptionHtml) {
                      $serviceDescriptionHtml = nl2br(e($rawServiceDescription));
                  }
                  if ($serviceDescriptionHtml === '') {
                      $serviceDescriptionHtml = '<p>No description available.</p>';
                  }
                  $serviceDescriptionEncoded = base64_encode($serviceDescriptionHtml);
                  $serviceGroup = $serviceCategoryMap[(int) ($service['id'] ?? 0)] ?? 'filing';
                ?>
                <article
                  class="service-product-card"
                  data-service-id="<?= (int) $service['id'] ?>"
                  data-service-name="<?= e((string) $service['name']) ?>"
                  data-price="<?= e((string) $service['price']) ?>"
                  data-currency="<?= e((string) ($service['currency'] ?? default_currency())) ?>"
                  data-tax="<?= e((string) ($service['tax_percent'] ?? '0')) ?>"
                  data-description-html="<?= e($serviceDescriptionEncoded) ?>"
                  data-service-group="<?= e($serviceGroup) ?>"
                  role="button"
                  tabindex="0"
                  aria-pressed="false"
                >
                  <input
                    class="service-selector service-selector-native"
                    type="checkbox"
                    name="service_ids[]"
                    value="<?= (int) $service['id'] ?>"
                    id="svc_<?= (int) $service['id'] ?>"
                    tabindex="-1"
                    aria-hidden="true"
                  >
                  <span class="service-selected-indicator" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                  <div class="service-card-head mb-2">
                    <span class="service-mini-icon"><i class="bi bi-grid"></i></span>
                    <div class="service-card-price-wrap">
                      <div class="service-card-price"><?= e(money((float) ($service['price'] ?? 0), (string) ($service['currency'] ?? default_currency()))) ?></div>
                      <div class="service-card-availability">Available</div>
                    </div>
                  </div>
                  <div class="flex-grow-1 min-w-0 service-card-body">
                    <h3 class="service-card-title mb-1"><?= e($service['name']) ?></h3>
                    <p class="service-card-summary mb-2"><?= e($serviceSummary) ?></p>
                  </div>
                  <div class="d-flex justify-content-between align-items-center gap-2 service-card-actions">
                    <div class="flex-grow-1 min-w-0">
                      <button
                        type="button"
                        class="btn btn-sm btn-primary service-toggle-btn"
                        data-unselected-text="Add to cart"
                        data-selected-text="Remove from cart"
                      >
                        Add to cart
                      </button>
                    </div>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary service-detail-btn"
                      data-bs-toggle="modal"
                      data-bs-target="#serviceDetailModal"
                      data-preview-title="<?= e((string) $service['name']) ?>"
                      data-preview-price="<?= e((string) $service['price']) ?>"
                      data-preview-currency="<?= e((string) ($service['currency'] ?? default_currency())) ?>"
                      data-preview-tax="<?= e((string) ($service['tax_percent'] ?? '0')) ?>"
                      data-preview-description="<?= e($serviceDescriptionEncoded) ?>"
                    >
                      Preview
                    </button>
                  </div>
                  <template class="service-description-template"><?= $serviceDescriptionHtml ?></template>
                </article>
              <?php endforeach; ?>
            </div>
            <div class="service-catalog-empty d-none" id="serviceCatalogEmptyState">No service found in this category.</div>
          </div>

          <aside class="checkout-sticky-cart" id="checkoutStickyCart" aria-label="Cart summary">
            <div class="checkout-sticky-head">
              <strong>Order Summary</strong>
              <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="stickyClearCartBtn">Clear cart</button>
            </div>
            <div class="checkout-cart-items" id="stickyCartItems">
              <div class="checkout-cart-empty" id="stickyCartEmpty">No service selected yet.</div>
            </div>
            <div class="checkout-sticky-controls">
              <label class="small text-muted mb-1" for="stickyPayCurrencySelect">Pay currency</label>
              <select class="form-select form-select-sm" id="stickyPayCurrencySelect">
                <?php foreach (payment_currency_options() as $currency): ?>
                  <option value="<?= e($currency) ?>" <?= selected_if($currency, $defaultPayCurrency) ?>><?= e($currency) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted d-block mt-1" id="stickyFxRateLine">Conversion: 1 USD = <?= e(number_format(fx_ngn_per_usd(), 2)) ?> NGN</small>
            </div>
            <small class="text-muted d-none" id="stickyCartHint">Tap any item in cart to remove it.</small>
            <div class="checkout-pay-method-wrap d-none" id="stickyPayMethodWrap">
              <label class="small text-muted mb-1" for="checkoutPayMethodSelect">Payment mode</label>
              <select class="form-select form-select-sm" id="checkoutPayMethodSelect">
                <option value="card">Card / Transfer</option>
                <option value="crypto">Crypto</option>
              </select>
            </div>

            <div class="crypto-checkout-panel mt-2 d-none" id="cryptoCheckoutPanel">
              <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div>
                  <h3 class="h6 mb-1">Crypto conversion quote</h3>
                  <p class="small text-muted mb-0">Send exactly this amount. Network fees are separate.</p>
                </div>
                <small class="text-muted" id="cryptoRatesMeta">
                  Rates last updated: <?= e((string) ($initialCryptoQuote['rates_last_updated_at'] ?? '')) ?>
                </small>
              </div>

              <div class="crypto-rate-warning small text-warning-emphasis mt-2 d-none" id="cryptoRateWarning"></div>

              <div class="crypto-asset-grid mt-2">
                <label class="crypto-asset-option">
                  <input type="radio" name="checkout_crypto_asset" value="USDT" checked>
                  <span>
                    <strong>USDT</strong>
                    <small id="cryptoAmountUSDT">0.00</small>
                  </span>
                </label>
                <label class="crypto-asset-option">
                  <input type="radio" name="checkout_crypto_asset" value="LTC">
                  <span>
                    <strong>LTC</strong>
                    <small id="cryptoAmountLTC">0.00000000</small>
                  </span>
                </label>
                <label class="crypto-asset-option">
                  <input type="radio" name="checkout_crypto_asset" value="BTC">
                  <span>
                    <strong>BTC</strong>
                    <small id="cryptoAmountBTC">0.00000000</small>
                  </span>
                </label>
              </div>

              <div class="row g-2 mt-1" id="cryptoNetworkRow">
                <div class="col-12">
                  <label class="form-label mb-1">USDT network</label>
                  <select class="form-select form-select-sm" id="checkoutCryptoNetworkSelect">
                    <option value="TRC20">TRC20 (default)</option>
                    <option value="ERC20">ERC20</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="checkout-sticky-totals">
              <div class="checkout-sticky-summary-line"><span>Subtotal</span><strong id="stickySummarySubtotal">NGN 0.00</strong></div>
              <div class="checkout-sticky-summary-line"><span>Tax</span><strong id="stickySummaryTax">NGN 0.00</strong></div>
              <div class="checkout-sticky-summary-line"><span>Total</span><strong id="stickySummaryTotal">NGN 0.00</strong></div>
              <div class="checkout-sticky-summary-line"><span>Pay with</span><strong id="stickySummaryMethod">Card / Transfer</strong></div>
              <div class="checkout-sticky-summary-line d-none" id="stickyCryptoRow"><span>Send</span><strong id="stickyCryptoAmount">USDT 0.00</strong></div>
            </div>
            <div class="checkout-order-note">
              <label class="form-label mb-1" for="orderDescriptionInput">Description / requirements</label>
              <textarea class="form-control form-control-sm" rows="3" name="description" id="orderDescriptionInput" placeholder="Add any requirements for this order"></textarea>
              <small class="text-muted d-block mt-1">After placing this order, you will be redirected to your invoice. Payment gateway options are selected automatically from your pay currency.</small>
            </div>
            <div class="checkout-sticky-actions">
              <button type="button" class="btn btn-primary btn-sm w-100" id="stickyContinueBtn">Continue</button>
              <a class="btn btn-outline-secondary btn-sm w-100 mt-2" href="<?= e(url('jobs')) ?>">Cancel</a>
            </div>
          </aside>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Service</label>
            <select class="form-select" name="service_id" required>
              <option value="">Select service</option>
              <?php foreach ($serviceOptions as $service): ?>
                <option value="<?= (int) $service['id'] ?>">
                  <?= e($service['name']) ?> (<?= e(money((float) $service['price'], $service['currency'] ?? default_currency())) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" placeholder="Short job title" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Client</label>
            <select class="form-select" name="client_id" required>
              <option value="">Select client</option>
              <?php foreach ($clientOptions as $client): ?>
                <option value="<?= (int) $client['id'] ?>"><?= e($client['full_name']) ?> (<?= e($client['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Assign agent (optional)</label>
            <select class="form-select" name="agent_id">
              <option value="">Later / job pool</option>
              <?php foreach ($agentOptions as $agent): ?>
                <option value="<?= (int) $agent['id'] ?>"><?= e($agent['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Subtotal</label>
            <input class="form-control" name="amount" type="number" min="0" step="0.01" placeholder="Auto">
          </div>
          <div class="col-md-2">
            <label class="form-label">Currency</label>
            <select class="form-select" name="currency">
              <?php foreach (currency_options() as $currency): ?>
                <option value="<?= e($currency) ?>" <?= selected_if($currency, default_currency()) ?>><?= e($currency) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Commission %</label>
            <input class="form-control" name="commission_percent" type="number" min="0" max="100" step="0.01" placeholder="Auto">
          </div>
          <div class="col-md-2">
            <label class="form-label">Tax %</label>
            <input class="form-control" name="tax_percent" type="number" min="0" max="100" step="0.01" placeholder="Auto">
          </div>
          <div class="col-md-4">
            <label class="form-label">Payment status</label>
            <select class="form-select" name="payment_status">
              <option value="unpaid">Unpaid</option>
              <option value="paid">Paid</option>
              <option value="partial">Partially Paid</option>
            </select>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($user['role'] !== 'client'): ?>
        <div class="col-12 mt-3">
          <label class="form-label">Description / requirements</label>
          <textarea class="form-control" rows="3" name="description"></textarea>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Create job</button>
        <a class="btn btn-outline-secondary mt-3" href="<?= e(url('jobs')) ?>">Cancel</a>
      <?php endif; ?>
    </form>

    <?php if (has_role(['client'])): ?>
      <div class="modal fade" id="serviceDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="serviceDetailTitle">Service preview</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="serviceDetailDescription" class="service-detail-content mb-3"></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Base price</span><strong id="serviceDetailPrice">-</strong></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Tax</span><strong id="serviceDetailTax">-</strong></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($compactClientList): ?>
<div class="d-flex align-items-center justify-content-between mb-2 mt-3">
  <h2 class="h6 mb-0">Recent jobs</h2>
  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('jobs')) ?>">View all Jobs List<?= $jobsTableTotal > 0 ? ' (' . (int) $jobsTableTotal . ')' : '' ?></a>
</div>
<?php endif; ?>

<?php if ($isAdminJobsPage): ?>
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="post" action="<?= e($jobActionUrl) ?>" id="jobsBulkForm" class="row g-2 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="bulk_manage_jobs">
      <div class="col-sm-6 col-lg-4">
        <label class="form-label mb-1">Bulk action</label>
        <select class="form-select" name="bulk_action" required>
          <option value="status:pending">Set status: Pending</option>
          <option value="status:in_progress">Set status: In progress</option>
          <option value="status:waiting_client">Set status: Waiting client</option>
          <option value="status:revision_requested">Set status: Revision requested</option>
          <option value="status:delivered">Set status: Delivered</option>
          <option value="status:closed">Set status: Closed</option>
          <option value="status:cancelled">Set status: Cancelled</option>
          <option value="delete">Delete selected jobs</option>
        </select>
      </div>
      <div class="col-sm-6 col-lg-2 d-grid">
        <button type="submit" class="btn btn-primary">Apply action</button>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button type="button" class="btn btn-outline-secondary" id="jobsCheckAllBtn">Check all</button>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button type="button" class="btn btn-outline-secondary" id="jobsUncheckAllBtn">Uncheck all</button>
      </div>
      <div class="col-12 col-lg-2 text-muted small d-flex align-items-center">
        <span id="jobsSelectedCount">0</span>&nbsp;selected
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-rows align-middle table-hover mb-0">
      <thead>
      <tr>
        <?php if ($isAdminJobsPage): ?>
          <th style="width: 52px;">
            <input class="form-check-input" type="checkbox" id="jobsSelectAll" aria-label="Select all jobs">
          </th>
        <?php endif; ?>
        <th>Job</th>
        <th>Client</th>
        <th>Agent</th>
        <th>Status</th>
        <th>Payment</th>
        <th>Amount</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($jobsTableItems as $job): ?>
        <?php
          $jobPaymentStatus = (string) ($job['payment_status_effective'] ?? $job['payment_status'] ?? 'unpaid');
          $canClientDeleteJob = (($user['role'] ?? '') === 'client')
            && (int) ($job['client_id'] ?? 0) === (int) ($user['id'] ?? 0)
            && $jobPaymentStatus === 'unpaid';
          $canAdminDeleteJob = (($user['role'] ?? '') === 'admin');
          $jobAssigneeLabel = !empty($job['managed_by_admin_name'])
            ? ('Admin: ' . (string) $job['managed_by_admin_name'])
            : ((string) ($job['agent_name'] ?: 'Job pool'));
        ?>
        <tr class="<?= ($job['agent_id'] === null && empty($job['managed_by_admin_id'])) ? 'job-pool-row' : '' ?>">
          <?php if ($isAdminJobsPage): ?>
            <td data-label="Select">
              <input
                class="form-check-input jobs-row-check"
                type="checkbox"
                name="job_ids[]"
                value="<?= (int) $job['id'] ?>"
                form="jobsBulkForm"
              >
            </td>
          <?php endif; ?>
          <td data-label="Job">
            <div class="fw-semibold"><?= e($job['job_code']) ?></div>
            <div class="small text-muted"><?= e($job['service_name']) ?></div>
          </td>
          <td data-label="Client"><?= e($job['client_name']) ?></td>
          <td data-label="Agent"><?= e($jobAssigneeLabel) ?></td>
          <td data-label="Status"><?= status_badge($job['status']) ?></td>
          <td data-label="Payment"><?= status_badge($jobPaymentStatus) ?></td>
          <td data-label="Amount"><?= e(money((float) $job['amount'], $job['currency'] ?? default_currency())) ?></td>
          <td data-label="Action" class="text-end cell-actions">
            <div class="d-inline-flex gap-1 align-items-center flex-wrap justify-content-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('job', ['id' => (int) $job['id']])) ?>">Open</a>
              <?php if ($canClientDeleteJob): ?>
                <form method="post" action="<?= e(url('jobs')) ?>" onsubmit="return confirm('Delete this unpaid job?');" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_unpaid_job">
                  <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
              <?php if ($canAdminDeleteJob): ?>
                <form method="post" action="<?= e(url('jobs')) ?>" onsubmit="return confirm('Delete this job and related records? This action cannot be undone.');" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_job_admin">
                  <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$jobsTableItems): ?>
        <tr class="table-empty-row"><td colspan="<?= $isAdminJobsPage ? 8 : 7 ?>" class="text-center text-muted py-4">No jobs found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($compactClientList && $jobsTableTotal > count($jobsTableItems)): ?>
  <div class="d-flex justify-content-end mt-2">
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('jobs')) ?>">View all Jobs List</a>
  </div>
<?php endif; ?>

<?php if (!$compactClientList): ?>
<?php
  $jobTotal = (int) ($jobPagination['total'] ?? 0);
  $jobFrom = (int) ($jobPagination['from'] ?? 0);
  $jobTo = (int) ($jobPagination['to'] ?? 0);
  $jobPageStart = max(1, $currentJobPage - 2);
  $jobPageEnd = min($totalJobPages, $currentJobPage + 2);
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
  <small class="text-muted">Showing <?= (int) $jobFrom ?> - <?= (int) $jobTo ?> of <?= (int) $jobTotal ?> jobs</small>
  <nav aria-label="Jobs pagination">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $currentJobPage <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('jobs', array_merge($jobPaginationBase, ['p' => max(1, $currentJobPage - 1)]))) ?>">Previous</a>
      </li>
      <?php if ($jobPageStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('jobs', array_merge($jobPaginationBase, ['p' => 1]))) ?>">1</a></li>
        <?php if ($jobPageStart > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($pageNo = $jobPageStart; $pageNo <= $jobPageEnd; $pageNo++): ?>
        <li class="page-item <?= $pageNo === $currentJobPage ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(url('jobs', array_merge($jobPaginationBase, ['p' => $pageNo]))) ?>"><?= (int) $pageNo ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($jobPageEnd < $totalJobPages): ?>
        <?php if ($jobPageEnd < ($totalJobPages - 1)): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('jobs', array_merge($jobPaginationBase, ['p' => $totalJobPages]))) ?>"><?= (int) $totalJobPages ?></a></li>
      <?php endif; ?>

      <li class="page-item <?= $currentJobPage >= $totalJobPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('jobs', array_merge($jobPaginationBase, ['p' => min($totalJobPages, $currentJobPage + 1)]))) ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>
<?php endif; ?>

<script>
(function () {
  var filterToggle = document.getElementById('jobFilterToggle');
  var filterPanel = document.getElementById('jobFilterPanel');
  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function () {
      filterPanel.classList.toggle('open');
    });
  }
})();
</script>

<?php if ($isAdminJobsPage): ?>
<script>
(function () {
  var selectAllBox = document.getElementById('jobsSelectAll');
  var checkAllBtn = document.getElementById('jobsCheckAllBtn');
  var uncheckAllBtn = document.getElementById('jobsUncheckAllBtn');
  var selectedCountEl = document.getElementById('jobsSelectedCount');
  var bulkForm = document.getElementById('jobsBulkForm');
  var rowChecks = Array.prototype.slice.call(document.querySelectorAll('.jobs-row-check'));

  var updateSelectedCount = function () {
    if (!selectedCountEl) {
      return;
    }
    var selected = rowChecks.filter(function (checkbox) { return checkbox.checked; }).length;
    selectedCountEl.textContent = String(selected);
    if (selectAllBox) {
      selectAllBox.checked = rowChecks.length > 0 && selected === rowChecks.length;
      selectAllBox.indeterminate = selected > 0 && selected < rowChecks.length;
    }
  };

  var setAllChecked = function (checked) {
    rowChecks.forEach(function (checkbox) { checkbox.checked = checked; });
    updateSelectedCount();
  };

  if (selectAllBox) {
    selectAllBox.addEventListener('change', function () {
      setAllChecked(!!selectAllBox.checked);
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

  if (bulkForm) {
    bulkForm.addEventListener('submit', function (event) {
      var selected = rowChecks.filter(function (checkbox) { return checkbox.checked; }).length;
      if (selected <= 0) {
        event.preventDefault();
        window.alert('Select at least one job.');
        return;
      }
      var actionSelect = bulkForm.querySelector('select[name=\"bulk_action\"]');
      var actionValue = actionSelect ? String(actionSelect.value || '') : '';
      if (actionValue === 'delete' && !window.confirm('Delete selected job(s)? This action cannot be undone.')) {
        event.preventDefault();
      }
    });
  }

  updateSelectedCount();
})();
</script>

<script>
(function () {
  var grid = document.querySelector('.service-catalog-grid');
  if (!grid) {
    return;
  }

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.service-product-card'));
  if (!cards.length) {
    return;
  }

  var chipsWrap = document.getElementById('serviceCategoryChips');
  var emptyState = document.getElementById('serviceCatalogEmptyState');
  var gridBtn = document.getElementById('serviceViewGridBtn');
  var listBtn = document.getElementById('serviceViewListBtn');
  var backBtn = document.getElementById('serviceCatalogBackBtn');
  var activeFilter = 'all';
  var storageKey = 'iworks_service_view_mode';

  function applyViewMode(mode) {
    var viewMode = mode === 'list' ? 'list' : 'grid';
    grid.classList.toggle('is-list-view', viewMode === 'list');
    if (gridBtn) {
      gridBtn.classList.toggle('active', viewMode === 'grid');
      gridBtn.classList.toggle('btn-dark', viewMode === 'grid');
      gridBtn.classList.toggle('btn-outline-secondary', viewMode !== 'grid');
    }
    if (listBtn) {
      listBtn.classList.toggle('active', viewMode === 'list');
      listBtn.classList.toggle('btn-dark', viewMode === 'list');
      listBtn.classList.toggle('btn-outline-secondary', viewMode !== 'list');
    }
    try {
      window.localStorage.setItem(storageKey, viewMode);
    } catch (error) {}
  }

  function updateVisibleCards() {
    var visibleCount = 0;
    cards.forEach(function (card) {
      var category = String(card.getAttribute('data-service-group') || 'filing');
      var match = activeFilter === 'all' || activeFilter === category;
      card.style.display = match ? '' : 'none';
      if (match) {
        visibleCount += 1;
      }
    });
    if (emptyState) {
      emptyState.classList.toggle('d-none', visibleCount > 0);
    }
  }

  function setFilter(nextFilter) {
    activeFilter = nextFilter || 'all';
    if (chipsWrap) {
      chipsWrap.querySelectorAll('[data-service-filter]').forEach(function (chip) {
        var isActive = String(chip.getAttribute('data-service-filter') || '') === activeFilter;
        chip.classList.toggle('active', isActive);
        chip.classList.toggle('btn-dark', isActive);
        chip.classList.toggle('btn-outline-secondary', !isActive);
      });
    }
    updateVisibleCards();
  }

  if (chipsWrap) {
    chipsWrap.addEventListener('click', function (event) {
      var chip = event.target && event.target.closest ? event.target.closest('[data-service-filter]') : null;
      if (!chip) return;
      event.preventDefault();
      setFilter(String(chip.getAttribute('data-service-filter') || 'all'));
    });
  }

  if (gridBtn) {
    gridBtn.addEventListener('click', function () {
      applyViewMode('grid');
    });
  }
  if (listBtn) {
    listBtn.addEventListener('click', function () {
      applyViewMode('list');
    });
  }

  if (backBtn) {
    backBtn.addEventListener('click', function (event) {
      event.preventDefault();
      if (window.history.length > 1) {
        window.history.back();
        return;
      }
      window.location.href = 'index.php?page=jobs';
    });
  }

  var initialMode = 'grid';
  try {
    var savedMode = window.localStorage.getItem(storageKey);
    if (savedMode === 'list' || savedMode === 'grid') {
      initialMode = savedMode;
    }
  } catch (error) {}

  applyViewMode(initialMode);
  setFilter('all');
})();
</script>
<?php endif; ?>

<?php if ($showCreateForm && has_role(['client'])): ?>
<script>
(function () {
  var cards = document.querySelectorAll('.service-product-card');
  var serviceIdInput = document.getElementById('selectedServiceId');
  var orderTitleInput = document.getElementById('orderTitleInput');
  var payCurrencySelect = document.getElementById('stickyPayCurrencySelect');
  var payCurrencyInput = document.getElementById('payCurrencyInput');
  var checkoutPayMethodSelect = document.getElementById('checkoutPayMethodSelect');
  var checkoutPayMethodInput = document.getElementById('checkoutPayMethodInput');
  var checkoutCryptoAssetInput = document.getElementById('checkoutCryptoAssetInput');
  var checkoutCryptoNetworkInput = document.getElementById('checkoutCryptoNetworkInput');
  var checkoutCryptoNetworkSelect = document.getElementById('checkoutCryptoNetworkSelect');
  var cryptoNetworkRow = document.getElementById('cryptoNetworkRow');
  var cryptoPanel = document.getElementById('cryptoCheckoutPanel');
  var cryptoRatesMeta = document.getElementById('cryptoRatesMeta');
  var cryptoRateWarning = document.getElementById('cryptoRateWarning');
  var cryptoAssetRadios = document.querySelectorAll('input[name=\"checkout_crypto_asset\"]');
  var cryptoAmountUSDT = document.getElementById('cryptoAmountUSDT');
  var cryptoAmountLTC = document.getElementById('cryptoAmountLTC');
  var cryptoAmountBTC = document.getElementById('cryptoAmountBTC');
  var stickyFxRateLine = document.getElementById('stickyFxRateLine');
  var stickyCartItems = document.getElementById('stickyCartItems');
  var stickyCartEmpty = document.getElementById('stickyCartEmpty');
  var stickySummarySubtotal = document.getElementById('stickySummarySubtotal');
  var stickySummaryTax = document.getElementById('stickySummaryTax');
  var stickySummaryTotal = document.getElementById('stickySummaryTotal');
  var stickySummaryMethod = document.getElementById('stickySummaryMethod');
  var stickyCryptoRow = document.getElementById('stickyCryptoRow');
  var stickyCryptoAmount = document.getElementById('stickyCryptoAmount');
  var stickyCartHint = document.getElementById('stickyCartHint');
  var stickyClearCartBtn = document.getElementById('stickyClearCartBtn');
  var stickyContinueBtn = document.getElementById('stickyContinueBtn');
  var stickyPayMethodWrap = document.getElementById('stickyPayMethodWrap');
  var serviceDetailModalEl = document.getElementById('serviceDetailModal');
  var serviceDetailModal = null;
  var detailTitle = document.getElementById('serviceDetailTitle');
  var detailDescription = document.getElementById('serviceDetailDescription');
  var detailPrice = document.getElementById('serviceDetailPrice');
  var detailTax = document.getElementById('serviceDetailTax');
  var ngnPerUsd = <?= json_encode((float) fx_ngn_per_usd()) ?>;
  var initialCryptoQuote = <?= json_encode($initialCryptoQuote, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' ?>;
  var latestCryptoQuote = initialCryptoQuote || {};
  var quoteRefreshTimer = null;
  var quotePollTimer = null;
  var latestSummaryTotal = 0;
  var latestSummaryCurrency = (payCurrencySelect && payCurrencySelect.value ? payCurrencySelect.value : 'NGN').toUpperCase();

  if (serviceDetailModalEl && window.bootstrap && window.bootstrap.Modal) {
    var ModalCtor = window.bootstrap.Modal;
    if (typeof ModalCtor.getOrCreateInstance === 'function') {
      serviceDetailModal = ModalCtor.getOrCreateInstance(serviceDetailModalEl);
    } else {
      serviceDetailModal = new ModalCtor(serviceDetailModalEl);
    }
  }

  function toNumber(value) {
    var n = parseFloat(value || '0');
    return Number.isFinite(n) ? n : 0;
  }

  function activePayCurrency() {
    return (payCurrencySelect && payCurrencySelect.value ? payCurrencySelect.value : 'NGN').toUpperCase();
  }

  function syncPayCurrencyInput() {
    if (payCurrencyInput) {
      payCurrencyInput.value = activePayCurrency();
    }
    if (stickyFxRateLine) {
      stickyFxRateLine.textContent = 'Conversion: 1 USD = ' + ngnPerUsd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' NGN';
    }
  }

  function convertAmount(amount, fromCurrency, toCurrency) {
    if (fromCurrency === toCurrency) {
      return amount;
    }
    if (fromCurrency === 'NGN' && toCurrency === 'USD') {
      return amount / ngnPerUsd;
    }
    if (fromCurrency === 'USD' && toCurrency === 'NGN') {
      return amount * ngnPerUsd;
    }
    return amount;
  }

  function money(amount, currency) {
    return currency + ' ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function closestFromTarget(target, selector) {
    if (!target) return null;
    var node = target.nodeType === 1 ? target : target.parentElement;
    if (!node || typeof node.closest !== 'function') return null;
    return node.closest(selector);
  }

  function selectedCryptoAsset() {
    var selected = document.querySelector('input[name=\"checkout_crypto_asset\"]:checked');
    return selected ? String(selected.value || 'USDT').toUpperCase() : 'USDT';
  }

  function selectedPayMethod() {
    if (!checkoutPayMethodSelect) {
      return 'card';
    }
    return checkoutPayMethodSelect.value === 'crypto' ? 'crypto' : 'card';
  }

  function applyCryptoAssetActiveState() {
    Array.prototype.forEach.call(cryptoAssetRadios, function (radio) {
      var wrap = radio.closest('.crypto-asset-option');
      if (!wrap) return;
      wrap.classList.toggle('active', !!radio.checked);
    });
  }

  function assetDisplayAmount(asset) {
    var rates = latestCryptoQuote && latestCryptoQuote.assets ? latestCryptoQuote.assets : {};
    var item = rates[asset];
    if (!item) {
      return asset + ' 0';
    }
    var formatted = item.amount_display_formatted || '';
    if (formatted === '') {
      var precision = asset === 'USDT' ? 2 : 8;
      formatted = Number(item.amount_display || 0).toFixed(precision);
    }
    return asset + ' ' + formatted;
  }

  function updateStickySummary(total, payCurrency) {
    latestSummaryTotal = total;
    latestSummaryCurrency = payCurrency;
    if (stickySummaryTotal) {
      stickySummaryTotal.textContent = money(total, payCurrency);
    }
    if (stickySummaryMethod) {
      stickySummaryMethod.textContent = selectedPayMethod() === 'crypto' ? 'Crypto' : 'Card / Transfer';
    }
    if (selectedPayMethod() === 'crypto') {
      if (stickyCryptoRow) {
        stickyCryptoRow.classList.remove('d-none');
      }
      if (stickyCryptoAmount) {
        stickyCryptoAmount.textContent = assetDisplayAmount(selectedCryptoAsset());
      }
    } else if (stickyCryptoRow) {
      stickyCryptoRow.classList.add('d-none');
    }
  }

  function renderCryptoQuote(quote) {
    if (!quote || typeof quote !== 'object') {
      return;
    }
    latestCryptoQuote = quote;
    if (toNumber(quote.fx_rate_used) > 0) {
      ngnPerUsd = toNumber(quote.fx_rate_used);
    }

    var assets = quote.assets || {};
    if (cryptoAmountUSDT && assets.USDT) {
      cryptoAmountUSDT.textContent = (assets.USDT.amount_display_formatted || Number(assets.USDT.amount_display || 0).toFixed(2)) + ' USDT';
    }
    if (cryptoAmountLTC && assets.LTC) {
      cryptoAmountLTC.textContent = (assets.LTC.amount_display_formatted || Number(assets.LTC.amount_display || 0).toFixed(8)) + ' LTC';
    }
    if (cryptoAmountBTC && assets.BTC) {
      cryptoAmountBTC.textContent = (assets.BTC.amount_display_formatted || Number(assets.BTC.amount_display || 0).toFixed(8)) + ' BTC';
    }

    if (cryptoRatesMeta) {
      var metaParts = [];
      if (quote.rates_last_updated_at) {
        metaParts.push('Rates last updated: ' + quote.rates_last_updated_at);
      }
      if (quote.rates_source) {
        metaParts.push('Source: ' + quote.rates_source);
      }
      cryptoRatesMeta.textContent = metaParts.join(' • ');
    }

    if (cryptoRateWarning) {
      if (quote.rates_warning) {
        cryptoRateWarning.textContent = quote.rates_warning;
        cryptoRateWarning.classList.remove('d-none');
      } else {
        cryptoRateWarning.classList.add('d-none');
        cryptoRateWarning.textContent = '';
      }
    }

    if (selectedPayMethod() === 'crypto') {
      updateStickySummary(toNumber(quote.payable_amount || 0), String(quote.pay_currency || 'NGN'));
    }
    syncPayCurrencyInput();
  }

  function refreshCryptoQuote(total, payCurrency) {
    var url = 'api/rates/?amount=' + encodeURIComponent(total.toFixed(2)) + '&currency=' + encodeURIComponent(payCurrency);
    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (resp) {
        if (!resp.ok) {
          throw new Error('Failed to load conversion rates.');
        }
        return resp.json();
      })
      .then(function (payload) {
        if (!payload || payload.success !== true) {
          throw new Error('Invalid conversion payload.');
        }
        renderCryptoQuote(payload);
      })
      .catch(function () {
        // Keep previous quote if realtime refresh fails.
      });
  }

  function queueCryptoRefresh(total, payCurrency) {
    if (quoteRefreshTimer) {
      window.clearTimeout(quoteRefreshTimer);
    }
    quoteRefreshTimer = window.setTimeout(function () {
      refreshCryptoQuote(total, payCurrency);
    }, 250);
  }

  function syncCryptoSelection() {
    var asset = selectedCryptoAsset();
    if (checkoutCryptoAssetInput) {
      checkoutCryptoAssetInput.value = asset;
    }

    if (cryptoNetworkRow) {
      cryptoNetworkRow.style.display = asset === 'USDT' ? '' : 'none';
    }
    if (checkoutCryptoNetworkInput) {
      checkoutCryptoNetworkInput.value = asset === 'USDT'
        ? String((checkoutCryptoNetworkSelect && checkoutCryptoNetworkSelect.value) || 'TRC20')
        : '';
    }
    applyCryptoAssetActiveState();
  }

  function togglePayMethodUI() {
    var method = selectedPayMethod();
    if (checkoutPayMethodInput) {
      checkoutPayMethodInput.value = method;
    }
    if (cryptoPanel) {
      cryptoPanel.classList.toggle('d-none', method !== 'crypto');
    }
    syncCryptoSelection();

    if (quotePollTimer) {
      window.clearInterval(quotePollTimer);
      quotePollTimer = null;
    }
    if (method === 'crypto') {
      quotePollTimer = window.setInterval(function () {
        refreshCryptoQuote(latestSummaryTotal, latestSummaryCurrency);
      }, 90000);
    }
  }

  function setCardSelected(card, checked) {
    var checkbox = card.querySelector('.service-selector');
    var toggleBtn = card.querySelector('.service-toggle-btn');
    if (checkbox) {
      checkbox.checked = !!checked;
    }
    card.classList.toggle('active', !!checked);
    card.setAttribute('aria-pressed', checked ? 'true' : 'false');
    if (toggleBtn) {
      var selectedText = toggleBtn.getAttribute('data-selected-text') || 'Remove from cart';
      var unselectedText = toggleBtn.getAttribute('data-unselected-text') || 'Add to cart';
      toggleBtn.textContent = checked ? selectedText : unselectedText;
      toggleBtn.classList.toggle('btn-primary', !checked);
      toggleBtn.classList.toggle('btn-outline-danger', !!checked);
    }
  }

  function selectedCards() {
    return Array.prototype.filter.call(cards, function (card) {
      var checkbox = card.querySelector('.service-selector');
      return !!(checkbox && checkbox.checked);
    });
  }

  function removeServiceById(serviceId) {
    Array.prototype.forEach.call(cards, function (card) {
      if (String(card.getAttribute('data-service-id') || '') !== String(serviceId)) {
        return;
      }
      setCardSelected(card, false);
    });
    updateSummary();
  }

  function clearCart() {
    Array.prototype.forEach.call(cards, function (card) {
      setCardSelected(card, false);
    });
    updateSummary();
  }

  function renderCartItems(selected, payCurrency) {
    if (!stickyCartItems) {
      return;
    }

    stickyCartItems.innerHTML = '';
    if (stickyCartHint) {
      stickyCartHint.classList.add('d-none');
    }

    if (!selected.length) {
      if (stickyCartEmpty) {
        stickyCartItems.appendChild(stickyCartEmpty);
      } else {
        var empty = document.createElement('div');
        empty.className = 'checkout-cart-empty';
        empty.textContent = 'No service selected yet.';
        stickyCartItems.appendChild(empty);
      }
      return;
    }

    if (stickyCartHint) {
      stickyCartHint.classList.remove('d-none');
    }

    selected.forEach(function (card) {
      var serviceName = card.getAttribute('data-service-name') || 'Service';
      var serviceId = card.getAttribute('data-service-id') || '';
      var basePrice = toNumber(card.getAttribute('data-price'));
      var serviceCurrency = (card.getAttribute('data-currency') || 'NGN').toUpperCase();
      var taxPercent = toNumber(card.getAttribute('data-tax'));
      var lineSubtotal = convertAmount(basePrice, serviceCurrency, payCurrency);

      var row = document.createElement('div');
      row.className = 'checkout-cart-item';
      row.setAttribute('data-remove-service', serviceId);
      row.setAttribute('role', 'button');
      row.setAttribute('tabindex', '0');
      row.setAttribute('title', 'Click to remove this service');

      var left = document.createElement('div');
      left.className = 'checkout-cart-item-main';

      var title = document.createElement('div');
      title.className = 'checkout-cart-item-title';
      title.textContent = serviceName;

      var meta = document.createElement('small');
      meta.className = 'checkout-cart-item-meta';
      meta.textContent = money(lineSubtotal, payCurrency) + ' • Tax ' + taxPercent.toFixed(2) + '%';

      left.appendChild(title);
      left.appendChild(meta);

      var removeLabel = document.createElement('span');
      removeLabel.className = 'checkout-cart-remove';
      removeLabel.textContent = 'Remove';

      row.appendChild(left);
      row.appendChild(removeLabel);
      stickyCartItems.appendChild(row);
    });
  }

  function readServiceDescriptionHtml(card) {
    var descriptionTemplate = card.querySelector('.service-description-template');
    if (descriptionTemplate) {
      var templateHtml = String(descriptionTemplate.innerHTML || '').trim();
      if (templateHtml !== '') {
        return templateHtml;
      }
    }

    var encodedDescription = card.getAttribute('data-description-html') || '';
    if (encodedDescription !== '') {
      try {
        var decoded = window.atob(encodedDescription);
        if (String(decoded || '').trim() !== '') {
          return decoded;
        }
      } catch (err) {
      }
    }

    return '<p>No details provided.</p>';
  }

  function decodeBase64Content(encodedValue) {
    var encoded = String(encodedValue || '').trim();
    if (encoded === '') {
      return '';
    }
    try {
      return window.atob(encoded);
    } catch (err) {
      return '';
    }
  }

  function showServiceDetailModal() {
    if (serviceDetailModal && typeof serviceDetailModal.show === 'function') {
      serviceDetailModal.show();
      return;
    }

    if (serviceDetailModalEl && window.bootstrap && window.bootstrap.Modal) {
      try {
        var fallbackModal = new window.bootstrap.Modal(serviceDetailModalEl);
        fallbackModal.show();
      } catch (error) {
      }
    }
  }

  function openServiceDetails(card) {
    var name = card.getAttribute('data-service-name') || 'Service details';
    var descriptionHtml = readServiceDescriptionHtml(card);
    var price = toNumber(card.getAttribute('data-price'));
    var currency = (card.getAttribute('data-currency') || 'NGN').toUpperCase();
    var taxPercent = toNumber(card.getAttribute('data-tax'));

    if (detailTitle) detailTitle.textContent = name;
    if (detailDescription) detailDescription.innerHTML = descriptionHtml;
    if (detailPrice) detailPrice.textContent = money(price, currency);
    if (detailTax) detailTax.textContent = taxPercent.toFixed(2) + '%';

    showServiceDetailModal();
  }

  function openServiceDetailsFromButton(button) {
    if (!button) {
      return;
    }
    if (detailTitle) {
      detailTitle.textContent = button.getAttribute('data-preview-title') || 'Service details';
    }
    if (detailDescription) {
      var html = decodeBase64Content(button.getAttribute('data-preview-description'));
      detailDescription.innerHTML = String(html || '').trim() !== '' ? html : '<p>No details provided.</p>';
    }
    if (detailPrice) {
      detailPrice.textContent = money(
        toNumber(button.getAttribute('data-preview-price')),
        (button.getAttribute('data-preview-currency') || 'NGN').toUpperCase()
      );
    }
    if (detailTax) {
      detailTax.textContent = toNumber(button.getAttribute('data-preview-tax')).toFixed(2) + '%';
    }
  }

  function updateSummary() {
    var selected = selectedCards();
    var payCurrency = activePayCurrency();
    var subtotal = 0;
    var taxAmount = 0;

    if (!selected.length) {
      renderCartItems([], payCurrency);
      if (stickySummarySubtotal) {
        stickySummarySubtotal.textContent = money(0, payCurrency);
      }
      if (stickySummaryTax) {
        stickySummaryTax.textContent = money(0, payCurrency);
      }
      if (serviceIdInput) {
        serviceIdInput.value = '';
      }
      if (orderTitleInput) {
        orderTitleInput.value = '';
      }
      if (stickyPayMethodWrap) {
        stickyPayMethodWrap.classList.add('d-none');
      }
      if (checkoutPayMethodSelect) {
        checkoutPayMethodSelect.value = 'card';
      }
      updateStickySummary(0, payCurrency);
      togglePayMethodUI();
      return;
    }

    selected.forEach(function (card) {
      var basePrice = toNumber(card.getAttribute('data-price'));
      var serviceCurrency = (card.getAttribute('data-currency') || 'NGN').toUpperCase();
      var taxPercent = toNumber(card.getAttribute('data-tax'));
      var lineSubtotal = convertAmount(basePrice, serviceCurrency, payCurrency);

      subtotal += lineSubtotal;
      taxAmount += lineSubtotal * (taxPercent / 100);
    });

    var total = subtotal + taxAmount;
    renderCartItems(selected, payCurrency);
    if (stickySummarySubtotal) {
      stickySummarySubtotal.textContent = money(subtotal, payCurrency);
    }
    if (stickySummaryTax) {
      stickySummaryTax.textContent = money(taxAmount, payCurrency);
    }
    updateStickySummary(total, payCurrency);
    if (serviceIdInput) {
      serviceIdInput.value = selected[0].getAttribute('data-service-id') || '';
    }
    if (orderTitleInput) {
      orderTitleInput.value = selected.length === 1
        ? (selected[0].getAttribute('data-service-name') || '')
        : ('Multi-service order (' + selected.length + ' items)');
    }
    if (stickyPayMethodWrap) {
      stickyPayMethodWrap.classList.remove('d-none');
    }

    if (selectedPayMethod() === 'crypto') {
      queueCryptoRefresh(total, payCurrency);
    }
  }

  Array.prototype.forEach.call(cards, function (card) {
    var detailBtn = card.querySelector('.service-detail-btn');
    var toggleBtn = card.querySelector('.service-toggle-btn');
    var checkbox = card.querySelector('.service-selector');

    card.addEventListener('click', function (event) {
      if (closestFromTarget(event.target, '.service-detail-btn')) {
        return;
      }
      if (closestFromTarget(event.target, '.service-toggle-btn')) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (checkbox) {
        setCardSelected(card, !checkbox.checked);
        updateSummary();
      }
    });

    card.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }
      event.preventDefault();
      if (checkbox) {
        setCardSelected(card, !checkbox.checked);
        updateSummary();
      }
    });

    if (toggleBtn) {
      toggleBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (checkbox) {
          setCardSelected(card, !checkbox.checked);
          updateSummary();
        }
      });
    }

    if (detailBtn) {
      detailBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openServiceDetailsFromButton(detailBtn);
        showServiceDetailModal();
      });
    }
  });

  if (serviceDetailModalEl) {
    serviceDetailModalEl.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      var button = trigger && trigger.closest ? trigger.closest('.service-detail-btn') : null;
      if (button) {
        openServiceDetailsFromButton(button);
      }
    });
  }

  if (stickyCartItems) {
    stickyCartItems.addEventListener('click', function (event) {
      var row = closestFromTarget(event.target, '[data-remove-service]');
      if (!row) return;
      event.preventDefault();
      removeServiceById(row.getAttribute('data-remove-service'));
    });

    stickyCartItems.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }
      var row = closestFromTarget(event.target, '[data-remove-service]');
      if (!row) return;
      event.preventDefault();
      removeServiceById(row.getAttribute('data-remove-service'));
    });
  }

  if (stickyClearCartBtn) {
    stickyClearCartBtn.addEventListener('click', function () {
      clearCart();
    });
  }

  if (stickyContinueBtn) {
    stickyContinueBtn.addEventListener('click', function () {
      var form = document.getElementById('jobCreateForm');
      if (!form) {
        return;
      }

      if (selectedCards().length === 0) {
        window.alert('Select at least one service before continuing.');
        return;
      }

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }

  if (payCurrencySelect) {
    payCurrencySelect.addEventListener('change', function () {
      syncPayCurrencyInput();
      updateSummary();
    });
  }

  if (checkoutPayMethodSelect) {
    checkoutPayMethodSelect.addEventListener('change', function () {
      togglePayMethodUI();
      updateSummary();
    });
  }

  if (checkoutCryptoNetworkSelect) {
    checkoutCryptoNetworkSelect.addEventListener('change', syncCryptoSelection);
  }

  Array.prototype.forEach.call(cryptoAssetRadios, function (radio) {
    radio.addEventListener('change', function () {
      syncCryptoSelection();
      updateStickySummary(latestSummaryTotal, latestSummaryCurrency);
    });
  }

  var form = document.getElementById('jobCreateForm');
  if (form) {
    form.addEventListener('submit', function (event) {
      if (selectedCards().length === 0) {
        event.preventDefault();
        window.alert('Select at least one service before continuing.');
      }
      syncPayCurrencyInput();
      syncCryptoSelection();
    });
  }

  Array.prototype.forEach.call(cards, function (card) {
    setCardSelected(card, false);
  });
  syncPayCurrencyInput();
  togglePayMethodUI();
  renderCryptoQuote(initialCryptoQuote);
  updateSummary();
  window.__iworksCartPrimaryReady = true;
})();
</script>

<script>
(function () {
  if (window.__iworksCartPrimaryReady === true) {
    return;
  }

  var form = document.getElementById('jobCreateForm');
  var grid = document.querySelector('.service-catalog-grid');
  if (!form || !grid) {
    return;
  }

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.service-product-card'));
  if (!cards.length) {
    return;
  }

  var ngnPerUsd = <?= json_encode((float) fx_ngn_per_usd()) ?>;
  var serviceIdInput = document.getElementById('selectedServiceId');
  var orderTitleInput = document.getElementById('orderTitleInput');
  var payCurrencySelect = document.getElementById('stickyPayCurrencySelect');
  var payCurrencyInput = document.getElementById('payCurrencyInput');
  var stickyFxRateLine = document.getElementById('stickyFxRateLine');
  var stickyCartItems = document.getElementById('stickyCartItems');
  var stickySummarySubtotal = document.getElementById('stickySummarySubtotal');
  var stickySummaryTax = document.getElementById('stickySummaryTax');
  var stickySummaryTotal = document.getElementById('stickySummaryTotal');
  var stickySummaryMethod = document.getElementById('stickySummaryMethod');
  var stickyPayMethodWrap = document.getElementById('stickyPayMethodWrap');
  var stickyClearCartBtn = document.getElementById('stickyClearCartBtn');
  var stickyContinueBtn = document.getElementById('stickyContinueBtn');
  var checkoutPayMethodSelect = document.getElementById('checkoutPayMethodSelect');
  var serviceDetailModalEl = document.getElementById('serviceDetailModal');
  var serviceDetailTitle = document.getElementById('serviceDetailTitle');
  var serviceDetailDescription = document.getElementById('serviceDetailDescription');
  var serviceDetailPrice = document.getElementById('serviceDetailPrice');
  var serviceDetailTax = document.getElementById('serviceDetailTax');

  function closestFromTarget(target, selector) {
    if (!target) return null;
    var node = target.nodeType === 1 ? target : target.parentElement;
    if (!node || typeof node.closest !== 'function') return null;
    return node.closest(selector);
  }

  function toNumber(value) {
    var n = parseFloat(value || '0');
    return Number.isFinite(n) ? n : 0;
  }

  function money(amount, currency) {
    return String(currency || 'NGN').toUpperCase() + ' ' + Number(amount || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function getPayCurrency() {
    return (payCurrencySelect && payCurrencySelect.value ? payCurrencySelect.value : 'NGN').toUpperCase();
  }

  function convertAmount(amount, fromCurrency, toCurrency) {
    var from = String(fromCurrency || 'NGN').toUpperCase();
    var to = String(toCurrency || 'NGN').toUpperCase();
    var base = toNumber(amount);
    if (from === to) return base;
    if (from === 'NGN' && to === 'USD') return base / (ngnPerUsd || 1);
    if (from === 'USD' && to === 'NGN') return base * (ngnPerUsd || 1);
    return base;
  }

  function decodeBase64(value) {
    var encoded = String(value || '').trim();
    if (encoded === '') return '';
    try {
      return window.atob(encoded);
    } catch (error) {
      return '';
    }
  }

  function setCardSelected(card, checked) {
    if (!card) return;
    var checkbox = card.querySelector('.service-selector');
    var toggleBtn = card.querySelector('.service-toggle-btn');
    if (checkbox) checkbox.checked = !!checked;
    card.classList.toggle('active', !!checked);
    card.setAttribute('aria-pressed', checked ? 'true' : 'false');
    if (toggleBtn) {
      var selectedText = toggleBtn.getAttribute('data-selected-text') || 'Remove from cart';
      var unselectedText = toggleBtn.getAttribute('data-unselected-text') || 'Add to cart';
      toggleBtn.textContent = checked ? selectedText : unselectedText;
      toggleBtn.classList.toggle('btn-primary', !checked);
      toggleBtn.classList.toggle('btn-outline-danger', !!checked);
    }
  }

  function selectedCards() {
    return cards.filter(function (card) {
      var checkbox = card.querySelector('.service-selector');
      return !!(checkbox && checkbox.checked);
    });
  }

  function renderCart() {
    var selected = selectedCards();
    var payCurrency = getPayCurrency();
    var subtotal = 0;
    var taxAmount = 0;

    if (payCurrencyInput) {
      payCurrencyInput.value = payCurrency;
    }
    if (stickyFxRateLine) {
      stickyFxRateLine.textContent = 'Conversion: 1 USD = ' + Number(ngnPerUsd || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }) + ' NGN';
    }

    cards.forEach(function (card) {
      var checkbox = card.querySelector('.service-selector');
      setCardSelected(card, !!(checkbox && checkbox.checked));
    });

    if (stickyCartItems) {
      stickyCartItems.innerHTML = '';
      if (!selected.length) {
        var empty = document.createElement('div');
        empty.className = 'checkout-cart-empty';
        empty.textContent = 'No service selected yet.';
        stickyCartItems.appendChild(empty);
      }
    }

    selected.forEach(function (card) {
      var basePrice = toNumber(card.getAttribute('data-price'));
      var serviceCurrency = (card.getAttribute('data-currency') || 'NGN').toUpperCase();
      var taxPercent = toNumber(card.getAttribute('data-tax'));
      var lineSubtotal = convertAmount(basePrice, serviceCurrency, payCurrency);

      subtotal += lineSubtotal;
      taxAmount += lineSubtotal * (taxPercent / 100);

      if (!stickyCartItems) return;
      var row = document.createElement('div');
      row.className = 'checkout-cart-item';
      row.setAttribute('data-remove-service', String(card.getAttribute('data-service-id') || ''));
      row.setAttribute('role', 'button');
      row.setAttribute('tabindex', '0');
      row.innerHTML = ''
        + '<div class="checkout-cart-item-main">'
        + '<div class="checkout-cart-item-title">' + String(card.getAttribute('data-service-name') || 'Service') + '</div>'
        + '<small class="checkout-cart-item-meta">' + money(lineSubtotal, payCurrency) + ' • Tax ' + taxPercent.toFixed(2) + '%</small>'
        + '</div>'
        + '<span class="checkout-cart-remove">Remove</span>';
      stickyCartItems.appendChild(row);
    });

    var total = subtotal + taxAmount;
    if (stickySummarySubtotal) stickySummarySubtotal.textContent = money(subtotal, payCurrency);
    if (stickySummaryTax) stickySummaryTax.textContent = money(taxAmount, payCurrency);
    if (stickySummaryTotal) stickySummaryTotal.textContent = money(total, payCurrency);
    if (stickySummaryMethod) {
      var method = checkoutPayMethodSelect ? String(checkoutPayMethodSelect.value || 'card') : 'card';
      stickySummaryMethod.textContent = method === 'crypto' ? 'Crypto' : 'Card / Transfer';
    }

    if (serviceIdInput) {
      serviceIdInput.value = selected.length ? String(selected[0].getAttribute('data-service-id') || '') : '';
    }
    if (orderTitleInput) {
      if (!selected.length) {
        orderTitleInput.value = '';
      } else if (selected.length === 1) {
        orderTitleInput.value = String(selected[0].getAttribute('data-service-name') || '');
      } else {
        orderTitleInput.value = 'Multi-service order (' + String(selected.length) + ' items)';
      }
    }
    if (stickyPayMethodWrap) {
      stickyPayMethodWrap.classList.toggle('d-none', selected.length === 0);
    }
  }

  function toggleCard(card) {
    if (!card) return;
    var checkbox = card.querySelector('.service-selector');
    if (!checkbox) return;
    setCardSelected(card, !checkbox.checked);
    renderCart();
  }

  function fillPreviewFromButton(button) {
    if (!button) return;
    if (serviceDetailTitle) {
      serviceDetailTitle.textContent = button.getAttribute('data-preview-title') || 'Service preview';
    }
    if (serviceDetailDescription) {
      var html = decodeBase64(button.getAttribute('data-preview-description'));
      serviceDetailDescription.innerHTML = String(html || '').trim() !== '' ? html : '<p>No details provided.</p>';
    }
    if (serviceDetailPrice) {
      serviceDetailPrice.textContent = money(
        toNumber(button.getAttribute('data-preview-price')),
        button.getAttribute('data-preview-currency') || 'NGN'
      );
    }
    if (serviceDetailTax) {
      serviceDetailTax.textContent = toNumber(button.getAttribute('data-preview-tax')).toFixed(2) + '%';
    }
  }

  grid.addEventListener('click', function (event) {
    var previewBtn = closestFromTarget(event.target, '.service-detail-btn');
    if (previewBtn) {
      fillPreviewFromButton(previewBtn);
      return;
    }

    var toggleBtn = closestFromTarget(event.target, '.service-toggle-btn');
    if (toggleBtn) {
      event.preventDefault();
      event.stopPropagation();
      toggleCard(closestFromTarget(toggleBtn, '.service-product-card'));
      return;
    }

    var card = closestFromTarget(event.target, '.service-product-card');
    if (!card) return;
    if (closestFromTarget(event.target, 'button, a, input, select, textarea')) return;
    event.preventDefault();
    toggleCard(card);
  });

  grid.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var card = closestFromTarget(event.target, '.service-product-card');
    if (!card) return;
    if (closestFromTarget(event.target, '.service-detail-btn')) return;
    event.preventDefault();
    toggleCard(card);
  });

  if (stickyCartItems) {
    stickyCartItems.addEventListener('click', function (event) {
      var row = closestFromTarget(event.target, '[data-remove-service]');
      if (!row) return;
      event.preventDefault();
      var removeId = row.getAttribute('data-remove-service') || '';
      cards.forEach(function (card) {
        if (String(card.getAttribute('data-service-id') || '') === String(removeId)) {
          setCardSelected(card, false);
        }
      });
      renderCart();
    });
  }

  if (stickyClearCartBtn) {
    stickyClearCartBtn.addEventListener('click', function (event) {
      event.preventDefault();
      cards.forEach(function (card) { setCardSelected(card, false); });
      renderCart();
    });
  }

  if (payCurrencySelect) {
    payCurrencySelect.addEventListener('change', renderCart);
  }
  if (checkoutPayMethodSelect) {
    checkoutPayMethodSelect.addEventListener('change', renderCart);
  }
  if (stickyContinueBtn) {
    stickyContinueBtn.addEventListener('click', function (event) {
      event.preventDefault();
      if (!selectedCards().length) {
        window.alert('Select at least one service before continuing.');
        return;
      }
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      if (selectedCards().length) return;
      event.preventDefault();
      window.alert('Select at least one service before continuing.');
    });
  }

  if (serviceDetailModalEl) {
    serviceDetailModalEl.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      var button = trigger && typeof trigger.closest === 'function' ? trigger.closest('.service-detail-btn') : null;
      if (button) {
        fillPreviewFromButton(button);
      }
    });
  }

  cards.forEach(function (card) {
    var checkbox = card.querySelector('.service-selector');
    setCardSelected(card, !!(checkbox && checkbox.checked));
  });
  renderCart();
})();
</script>
<?php endif; ?>
