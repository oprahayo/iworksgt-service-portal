<div class="iw-client-heading iw-client-heading-row">
  <div><span class="iw-client-eyebrow">BILLING</span><h1>Billing & payments</h1><p>Review invoices and complete payments securely.</p></div>
  <a href="<?= e(url('dashboard')) ?>" class="iw-client-button">New application <i class="bi bi-plus"></i></a>
</div>
<div class="iw-billing-layout">
<div class="iw-billing-invoice-column">
<section class="iw-client-billing-notice" aria-label="Payment security">
  <i class="bi bi-lock-fill" aria-hidden="true"></i>
  <div><strong>Secure, provider-hosted payments</strong></div>
</section>
<div class="iw-client-section-top"><div><h2>Invoices</h2><p>New trademark invoices will appear here after review.</p></div></div>
<div class="iw-client-application-list">
<?php if ($invoices === []): ?>
  <div class="iw-client-empty iw-client-empty-large"><i class="bi bi-receipt"></i><div><strong>No invoices yet</strong><p>You'll see your bills here when you submit an application for payment.</p></div></div>
<?php else: ?>
  <?php foreach ($invoices as $invoice): ?>
  <article class="iw-client-application">
    <div class="iw-client-application-main">
      <div class="iw-client-application-meta"><span class="iw-client-country"><?= e(strtoupper((string) ($invoice['currency'] ?? 'USD'))) ?></span>
      <span class="iw-client-status"><?= e(ucfirst((string) ($invoice['status'] ?? 'issued'))) ?></span></div>
      <small><?= e((string) ($invoice['invoice_number'] ?? 'Invoice')) ?></small>
      <h2><?= e((string) ($invoice['service_name'] ?? $invoice['job_title'] ?? 'Service invoice')) ?></h2>
      <p><?= e((string) ($invoice['currency'] ?? 'USD')) ?> <?= e(number_format((float) ($invoice['amount'] ?? 0), 2)) ?></p>
    </div>
    <a href="<?= e('invoice.php?id=' . (int) ($invoice['id'] ?? 0)) ?>" class="iw-client-button iw-client-button-soft"><?= ($invoice['status'] ?? '') === 'paid' ? 'View receipt' : 'Continue to payment' ?> <i class="bi bi-arrow-right"></i></a>
  </article>
  <?php endforeach; ?>
<?php endif; ?>
</div>
</div>
<aside class="iw-billing-methods" aria-label="Saved payment methods">
  <div class="iw-tm-panel">
    <div class="iw-client-detail-panel-title"><h2><i class="bi bi-credit-card-2-front"></i> Payment methods</h2></div>
    <?php if (!($billingVaultAvailable ?? false)): ?>
      <p class="iw-client-small-muted">Saved card management will be available when Stripe is enabled.</p>
    <?php elseif (empty($billingCards)): ?>
      <p class="iw-client-small-muted">No saved cards yet. Add a card securely with Stripe for future USD checkout.</p>
    <?php else: ?>
      <?php foreach ($billingCards as $card): ?>
        <div class="iw-billing-card"><i class="bi bi-credit-card"></i><span>
          <strong><?= e(ucfirst((string) $card['brand'])) ?> •••• <?= e((string) $card['last4']) ?></strong>
          <small>Expires <?= e(sprintf('%02d',(int) $card['exp_month'])) ?>/<?= (int) $card['exp_year'] ?></small>
        </span></div>
      <?php endforeach; ?>
    <?php endif; ?>
    <a href="<?= e(url('card_vault')) ?>" class="iw-client-button iw-client-button-soft iw-billing-manage"><i class="bi bi-shield-lock"></i> Manage payment methods</a>
    <p class="iw-client-small-muted mt-3 mb-0">Cards are held by Stripe, not stored on iWorks. Naira gateway cards are managed separately.</p>
  </div>
</aside>
</div>
