<div class="iw-client-heading iw-client-heading-row">
  <div><span class="iw-client-eyebrow">BILLING</span><h1>Billing & payments</h1><p>Invoices and payment methods in one place.</p></div>
</div>
<section class="iw-client-billing-notice" aria-label="Payment security">
  <i class="bi bi-lock-fill" aria-hidden="true"></i><div><strong>Secure, provider-hosted payments</strong></div>
</section>
<div class="iw-billing-layout">
  <main class="iw-billing-main">
    <div class="iw-client-section-top"><div><h2>Invoices</h2><p>Review and pay for your services.</p></div></div>
    <div class="iw-client-application-list">
    <?php if ($invoices===[]): ?>
      <div class="iw-client-empty iw-client-empty-large"><i class="bi bi-receipt"></i>
        <div><strong>No invoices yet</strong><p>Start an application and an invoice will appear after review.</p></div></div>
    <?php else: ?>
      <?php foreach ($invoices as $invoice): ?>
        <article class="iw-client-application">
          <div class="iw-client-application-main">
            <div class="iw-client-application-meta"><span class="iw-client-country"><?= e(strtoupper((string) ($invoice['currency'] ?? 'NGN'))) ?></span>
              <span class="iw-client-status"><?= e(ucfirst((string) ($invoice['status'] ?? 'issued'))) ?></span></div>
            <small><?= e((string) ($invoice['invoice_number'] ?? 'Invoice')) ?></small>
            <h2><?= e((string) ($invoice['service_name'] ?? $invoice['job_title'] ?? 'Service invoice')) ?></h2>
            <p><?= e(money((float) ($invoice['amount'] ?? 0),(string) ($invoice['currency'] ?? default_currency()))) ?></p>
          </div>
          <a href="<?= e('invoice.php?id=' . (int) ($invoice['id'] ?? 0)) ?>" class="iw-client-button iw-client-button-soft"><?= ($invoice['status'] ?? '')==='paid'?'View receipt':'Review & pay' ?> <i class="bi bi-arrow-right"></i></a>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </main>
  <aside class="iw-billing-side" id="payment-methods">
    <a class="iw-client-button" href="<?= e(url('dashboard')) ?>"><i class="bi bi-plus-lg"></i> New application</a>
    <section class="iw-tm-panel">
      <div class="iw-client-detail-panel-title"><h2>Payment methods</h2><i class="bi bi-credit-card" aria-hidden="true"></i></div>
      <p class="iw-client-small-muted">Cards saved with Stripe can be reused during eligible USD checkout. We don't store full card numbers or CVVs.</p>
      <?php if (!$vaultEnabled): ?>
        <div class="iw-client-empty"><i class="bi bi-lock"></i><div><strong>Saved cards unavailable</strong><p>The Stripe gateway must be enabled by an administrator.</p></div></div>
      <?php elseif ($vaultNotice!==''): ?>
        <p class="iw-client-small-muted"><?= e($vaultNotice) ?></p>
      <?php else: ?>
        <?php if (!$savedCards): ?><p class="iw-client-small-muted">No payment methods saved yet.</p><?php endif; ?>
        <?php foreach ($savedCards as $card): ?>
        <div class="iw-billing-card">
          <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
          <div><strong><?= e(ucfirst((string) ($card['brand'] ?? 'Card'))) ?> ···· <?= e((string) $card['last4']) ?></strong>
            <small>Expires <?= e(sprintf('%02d',(int) $card['exp_month'])) ?>/<?= (int) $card['exp_year'] ?></small></div>
          <form action="<?= e(url('card_vault')) ?>" method="post" onsubmit="return confirm('Remove this saved card?');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="remove_card">
            <input type="hidden" name="return_to" value="invoices">
            <input type="hidden" name="payment_method_id" value="<?= e((string) $card['id']) ?>">
            <button type="submit" aria-label="Remove card ending <?= e((string) $card['last4']) ?>"><i class="bi bi-trash3"></i></button>
          </form>
        </div>
        <?php endforeach; ?>
        <form class="iw-billing-add-card" method="post" action="<?= e(url('card_vault')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_card">
          <input type="hidden" name="return_to" value="invoices">
          <label><input type="checkbox" value="1" name="save_card_consent" required>
            <span>I agree to save a card securely with Stripe for future iWorks payments. Each charge still requires my confirmation.</span></label>
          <button type="submit" class="iw-client-button iw-client-button-soft"><i class="bi bi-plus-circle"></i> Add a card</button>
        </form>
      <?php endif; ?>
      <div class="iw-billing-provider"><i class="bi bi-info-circle"></i>
        NGN card reuse through Flutterwave or Paystack is not yet connected. A card saved with Stripe is not transferred to another provider.</div>
    </section>
  </aside>
</div>
