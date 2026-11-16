<?php
/**
 * Read-only overview of actual orders and payment transactions.
 * Separate live-chat conversations remain available in Website Live Chat.
 */
$transactionOverview = is_array($transactionOverview ?? null) ? $transactionOverview : [];
$transactionActivity = (array)($transactionOverview['activity'] ?? []);
$transactionPeak = max(1, ...array_map(
    static fn(array $item): int => max(0, (int)($item['total'] ?? 0)),
    $transactionActivity
));
?>
<section class="iw-transactions" id="iwTransactionsOverview" aria-labelledby="iwTransactionsHeading">
  <header class="iw-transactions-header">
    <div>
      <h2 id="iwTransactionsHeading">Payments &amp; orders</h2>
      <p>Actual transactions recorded in the service portal.</p>
    </div>
    <div class="iw-transactions-actions">
      <a href="<?= e(url('payments')) ?>">View payments <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      <a href="<?= e(url('invoices')) ?>">Invoices <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </header>

  <div class="iw-transactions-metrics" aria-label="Website transaction summary">
    <div class="iw-transactions-metric">
      <span><i class="bi bi-receipt" aria-hidden="true"></i> Orders</span>
      <strong><?= (int)($transactionOverview['orders'] ?? 0) ?></strong>
      <small>Issued and paid invoices</small>
    </div>
    <div class="iw-transactions-metric">
      <span><i class="bi bi-check-circle" aria-hidden="true"></i> Confirmed payments</span>
      <strong><?= (int)($transactionOverview['confirmed'] ?? 0) ?></strong>
      <small>Verified payment records</small>
    </div>
    <div class="iw-transactions-metric<?= !empty($transactionOverview['pending']) ? ' is-pending' : '' ?>">
      <span><i class="bi bi-hourglass-split" aria-hidden="true"></i> Pending payments</span>
      <strong><?= (int)($transactionOverview['pending'] ?? 0) ?></strong>
      <small>Submitted or awaiting confirmation</small>
    </div>
    <div class="iw-transactions-metric">
      <span><i class="bi bi-calendar2-check" aria-hidden="true"></i> Orders today</span>
      <strong><?= (int)($transactionOverview['orders_today'] ?? 0) ?></strong>
      <small>New issued and paid invoices</small>
    </div>
  </div>

  <div class="iw-transactions-details">
    <section class="iw-transactions-trend" aria-labelledby="iwTransactionTrendHeading">
      <div class="iw-transactions-section-title">
        <h3 id="iwTransactionTrendHeading">Confirmed payments</h3>
        <small>Last 7 days</small>
      </div>
      <div class="iw-transactions-bars" role="img"
           aria-label="Confirmed payment transactions by day over the last seven days">
        <?php foreach ($transactionActivity as $day):
          $volume = max(0,(int)($day['total'] ?? 0));
          $level = $volume > 0 ? max(6,(int)round($volume / $transactionPeak * 100)) : 0;
          $dayName = (string)($day['day'] ?? '');
        ?>
          <div class="iw-transactions-day" title="<?= e($dayName) ?>: <?= $volume ?> confirmed payments">
            <span class="iw-transactions-value"><?= $volume > 0 ? $volume : '' ?></span>
            <span class="iw-transactions-bar-track"><span class="iw-transactions-bar" style="height:<?= $level ?>%"></span></span>
            <small><?= e(substr($dayName,5)) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="iw-transactions-note">Only confirmed payments count toward this chart.</p>
    </section>

    <section class="iw-transactions-recent" aria-labelledby="iwTransactionRecentHeading">
      <div class="iw-transactions-section-title">
        <h3 id="iwTransactionRecentHeading">Recent payments</h3>
        <a href="<?= e(url('payments')) ?>">All payments</a>
      </div>
      <div class="iw-transactions-recent-list">
        <?php foreach ((array)($transactionOverview['recent'] ?? []) as $payment):
          $status = (string)($payment['status'] ?? 'submitted');
          if (!in_array($status,['confirmed','submitted','rejected'],true)) { $status='submitted'; }
          $currency = trim((string)($payment['currency'] ?? ''));
          if ($currency === '') { $currency='—'; }
        ?>
          <a href="<?= e(url('payments')) ?>" class="iw-transactions-row">
            <span class="iw-transactions-row-info">
              <strong><?= e((string)($payment['invoice_number'] ?? 'Invoice')) ?></strong>
              <small><?= e((string)($payment['method_name'] ?? 'Payment')) ?></small>
            </span>
            <span class="iw-transactions-row-end">
              <strong><?= e($currency) ?> <?= e(number_format((float)($payment['amount'] ?? 0),2)) ?></strong>
              <small class="iw-transaction-status is-<?= e($status) ?>"><?= e($status==='submitted'?'Pending':ucfirst($status)) ?></small>
            </span>
          </a>
        <?php endforeach; ?>
        <?php if (empty($transactionOverview['recent'])): ?>
          <p class="iw-transactions-empty">No payment transactions recorded yet.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <footer class="iw-transactions-footer">
    <span>Confirmed payment value by invoice currency</span>
    <div class="iw-transactions-receipts">
      <?php foreach ((array)($transactionOverview['receipts'] ?? []) as $group): ?>
        <span><strong><?= e((string)($group['currency'] ?? '')) ?> <?= e(number_format((float)($group['total'] ?? 0),2)) ?></strong>
        <small><?= (int)($group['transactions'] ?? 0) ?> transactions</small></span>
      <?php endforeach; ?>
      <?php if (empty($transactionOverview['receipts'])): ?>
        <span class="iw-transactions-no-receipts">No confirmed payments yet</span>
      <?php endif; ?>
    </div>
    <small>Figures use recorded invoices and payment statuses, not chat activity. Currencies are not combined or converted.</small>
  </footer>
</section>
