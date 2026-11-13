<?php
/** Focused checkout for one published, non-guided iWorks product. */
$checkoutService = is_array($checkoutService ?? null) ? $checkoutService : [];
$checkoutId = (int)($checkoutService['id'] ?? 0);
$checkoutName = trim((string)($checkoutService['name'] ?? 'Service'));
$checkoutSummary = trim((string)preg_replace('/\s+/', ' ', strip_tags((string)($checkoutService['short_description'] ?? $checkoutService['description'] ?? ''))));
$checkoutPrice = (float)($checkoutService['price'] ?? 0);
$checkoutCurrency = strtoupper((string)($checkoutService['currency'] ?? default_currency()));
$checkoutTaxPercent = max(0.0, min(100.0, (float)($checkoutService['tax_percent'] ?? 0)));
$checkoutTax = round($checkoutPrice * $checkoutTaxPercent / 100, 2);
$checkoutTotal = round($checkoutPrice + $checkoutTax, 2);
?>
<main class="iw-focused-checkout" id="main-content">
  <div class="iw-focused-checkout-heading">
    <a class="iw-focused-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All services</a>
    <span class="iw-client-eyebrow">NEW SERVICE REQUEST</span>
    <h1>Review your service</h1>
    <p>Confirm the scope below, provide any requirements and continue to your invoice.</p>
  </div>
  <div class="iw-focused-checkout-grid">
    <section class="iw-focused-main" aria-labelledby="iwFocusedServiceTitle">
      <div class="iw-focused-category"><i class="bi <?= e(iworks_client_service_icon($checkoutService)) ?>" aria-hidden="true"></i><span><?= e(ucwords(str_replace(['-', '_'], ' ', (string)($checkoutService['category'] ?? 'Services')))) ?></span></div>
      <h2 id="iwFocusedServiceTitle"><?= e($checkoutName) ?></h2>
      <?php if ($checkoutSummary !== ''): ?><p class="iw-focused-summary"><?= e($checkoutSummary) ?></p><?php endif; ?>
      <?php if (!empty($checkoutService['featured_bullets']) && is_array($checkoutService['featured_bullets'])): ?>
        <ul class="iw-focused-benefits">
          <?php foreach (array_slice($checkoutService['featured_bullets'],0,6) as $benefit): ?>
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?= e((string)$benefit) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <a class="iw-focused-details-link" href="<?= e(public_url('service',['slug'=>(string)($checkoutService['slug'] ?? '')])) ?>" target="_blank" rel="noopener noreferrer">Full service details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      <div class="iw-focused-next">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <p>You will receive an invoice before payment. Work begins according to the service requirements and payment status. To request another service, return to the catalogue.</p>
      </div>
    </section>
    <aside class="iw-focused-summary-card" aria-labelledby="iwFocusedOrderSummary">
      <h2 id="iwFocusedOrderSummary">Order summary</h2>
      <dl class="iw-focused-prices">
        <div><dt>Base price</dt><dd><?= e(money($checkoutPrice,$checkoutCurrency)) ?></dd></div>
        <div><dt>Tax (<?= e(number_format($checkoutTaxPercent,2)) ?>%)</dt><dd><?= e(money($checkoutTax,$checkoutCurrency)) ?></dd></div>
        <div class="iw-focused-grand"><dt>Estimated total</dt><dd><?= e(money($checkoutTotal,$checkoutCurrency)) ?></dd></div>
      </dl>
      <form method="post" action="<?= e(url('jobs')) ?>" id="iwFocusedOrderForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="create_job">
        <input type="hidden" name="service_id" value="<?= $checkoutId ?>">
        <input type="hidden" name="title" value="<?= e($checkoutName) ?>">
        <label for="iwFocusedRequirements">Your requirements <span class="iw-focused-optional">(optional)</span></label>
        <textarea name="description" id="iwFocusedRequirements" rows="4" maxlength="4000" placeholder="Tell us what you need, timelines and any important context."></textarea>
        <label for="iwFocusedCurrency">Invoice currency</label>
        <select name="pay_currency" id="iwFocusedCurrency" required>
          <?php foreach (payment_currency_options() as $currency): ?>
            <option value="<?= e($currency) ?>" <?= $currency === $checkoutCurrency ? 'selected' : '' ?>><?= e($currency) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="iw-focused-fx-note">The figures above are in <?= e($checkoutCurrency) ?>. If you select another invoice currency, the server recalculates the amount at the prevailing checkout exchange rate. Review the final invoice before paying.</p>
        <label for="iwFocusedPayMethod">Preferred payment method</label>
        <select id="iwFocusedPayMethod" name="checkout_pay_method">
          <option value="card">Card</option>
          <option value="bank">Bank transfer</option>
          <option value="crypto">Cryptocurrency (where supported)</option>
        </select>
        <button type="submit" class="iw-focused-submit">Continue to invoice <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        <a class="iw-focused-cancel" href="<?= e(url('dashboard')) ?>">Choose another service</a>
      </form>
    </aside>
  </div>
</main>
