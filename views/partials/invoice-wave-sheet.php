<?php
/**
 * iWORKS invoice sheet. Layout follows the company's supplied historical
 * accounting invoices: orange full-width invoice band, billed-to/meta,
 * ITEMS | QUANTITY | PRICE | AMOUNT, right-aligned totals and quiet footer.
 *
 * The HTML is owned by iWORKS, not a Wave export. It uses actual invoice,
 * customer, tax and confirmed payment records without inventing charges.
 * This partial is shared by client invoice and staff preview; print only the
 * document itself, never the checkout controls.
 */
$iwCurrency = (string)($invoice['currency'] ?? default_currency());
$iwTotal = max(0.0, (float)($invoice['amount'] ?? 0));
$iwMoney = static function (float $amount) use ($iwCurrency): string {
    $prefix = $iwCurrency === 'NGN' ? '₦' : ($iwCurrency === 'USD' ? '
$iwSubtotal = (float)($invoice['subtotal_amount'] ?? $iwTotal);
$iwTax = (float)($invoice['tax_amount'] ?? 0);
$iwConfirmed = [];
$iwConfirmedSum = 0.0;
foreach ((array)($paymentHistory ?? []) as $iwPayment) {
    if ((string)($iwPayment['status'] ?? '') !== 'confirmed') {
        continue;
    }
    $iwConfirmed[] = $iwPayment;
    $iwConfirmedSum += max(0.0, (float)($iwPayment['amount'] ?? 0));
}
$iwDue = (string)($invoice['status'] ?? '') === 'paid'
    ? 0.0
    : max(0.0, $iwTotal - $iwConfirmedSum);
$iwTaxPct = $iwSubtotal > 0 && $iwTax > 0 ? round($iwTax * 100 / $iwSubtotal, 2) : 0.0;
$iwTaxLabel = $iwTaxPct > 0
    ? 'Tax ' . rtrim(rtrim(number_format($iwTaxPct, 2), '0'), '.') . '%'
    : 'Tax';
$iwDate = static function (?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') return '—';
    $timestamp = strtotime($raw);
    return $timestamp === false ? $raw : date('F j, Y', $timestamp);
};
$iwFooter = function_exists('get_public_footer_data') ? get_public_footer_data('home') : [];
$iwFooterName = trim((string)($iwFooter['companyName'] ?? ''));
if ($iwFooterName === '') {
    $iwFooterName = function_exists('company_legal_name')
        ? company_legal_name() : 'iWORKS GLOBAL LTD';
}
$iwFooterAddress = trim((string)($iwFooter['address'] ?? ''));
if (strcasecmp($iwFooterAddress, 'Lagos, Nigeria') === 0) {
    $iwFooterAddress = '';
}
$iwFooterPhone = trim((string)($iwFooter['phone'] ?? ''));
if (preg_match('/^\+?234\s*806\s*000\s*0000$/', $iwFooterPhone)) {
    $iwFooterPhone = '';
}
$iwFooterEmail = trim((string)($iwFooter['email'] ?? ''));
?>
<article class="iw-wave-sheet" aria-label="Invoice <?= e((string)($invoice['invoice_number'] ?? '')) ?>">
  <header class="iw-wave-banner">
    <div class="iw-wave-banner-title">INVOICE</div>
    <div class="iw-wave-banner-due">
      <span>Amount Due (<?= e($iwCurrency) ?>)</span>
      <strong><?= e($iwMoney($iwDue)) ?></strong>
    </div>
  </header>

  <div class="iw-wave-document">
    <section class="iw-wave-parties" aria-label="Billing details">
      <div class="iw-wave-billto">
        <span class="iw-wave-overline">BILL TO</span>
        <strong><?= e((string)($invoice['client_name'] ?? '')) ?></strong>
        <?php if (trim((string)($invoice['client_phone'] ?? '')) !== ''): ?>
          <span><?= e((string)$invoice['client_phone']) ?></span>
        <?php endif; ?>
        <?php if (trim((string)($invoice['client_email'] ?? '')) !== ''): ?>
          <span class="iw-wave-email"><?= e((string)$invoice['client_email']) ?></span>
        <?php endif; ?>
      </div>
      <dl class="iw-wave-invoice-meta">
        <div><dt>Invoice Number:</dt><dd><?= e((string)($invoice['invoice_number'] ?? '')) ?></dd></div>
        <div><dt>Invoice Date:</dt><dd><?= e($iwDate((string)($invoice['issued_at'] ?? ''))) ?></dd></div>
        <div><dt>Payment Due:</dt><dd><?= e($iwDate((string)($invoice['due_at'] ?? ''))) ?></dd></div>
        <div class="iw-wave-meta-due"><dt>Amount Due (<?= e($iwCurrency) ?>):</dt><dd><?= e($iwMoney($iwDue)) ?></dd></div>
      </dl>
    </section>

    <section class="iw-wave-items" aria-label="Items billed">
      <table>
        <colgroup><col class="iw-wave-col-item"><col class="iw-wave-col-qty"><col class="iw-wave-col-price"><col class="iw-wave-col-amount"></colgroup>
        <thead><tr>
          <th scope="col">ITEMS</th><th scope="col">QUANTITY</th>
          <th scope="col">PRICE</th><th scope="col">AMOUNT</th>
        </tr></thead>
        <tbody>
          <?php if (count($invoiceJobs ?? []) > 1): ?>
            <?php foreach ($invoiceJobs as $iwLine): ?>
              <?php $iwLinePrice = (float)($iwLine['subtotal_amount'] ?? 0); ?>
              <tr>
                <td>
                  <strong><?= e((string)($iwLine['service_name'] ?? 'Service')) ?></strong>
                  <?php if (!empty($iwLine['job_code'])): ?>
                    <small><?= e((string)$iwLine['job_code']) ?></small>
                  <?php endif; ?>
                </td>
                <td class="iw-wave-numeric">1</td>
                <td class="iw-wave-numeric"><?= e($iwMoney($iwLinePrice)) ?></td>
                <td class="iw-wave-numeric"><?= e($iwMoney($iwLinePrice)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td>
                <strong><?= e((string)($invoice['service_name'] ?? 'Service')) ?></strong>
                <?php if (trim((string)($invoice['job_description'] ?? '')) !== ''): ?>
                  <span class="iw-wave-line-description"><?= nl2br(e((string)$invoice['job_description'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($invoice['job_code'])): ?>
                  <small class="iw-wave-line-job"><?= e((string)$invoice['job_code']) ?></small>
                <?php endif; ?>
              </td>
              <td class="iw-wave-numeric">1</td>
              <td class="iw-wave-numeric"><?= e($iwMoney($iwSubtotal)) ?></td>
              <td class="iw-wave-numeric"><?= e($iwMoney($iwSubtotal)) ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="iw-wave-totals" aria-label="Invoice calculation">
      <div class="iw-wave-total-line"><span>Subtotal:</span><strong><?= e($iwMoney($iwSubtotal)) ?></strong></div>
      <div class="iw-wave-total-line"><span><?= e($iwTaxLabel) ?>:</span><strong><?= e($iwMoney($iwTax)) ?></strong></div>
      <div class="iw-wave-total-line iw-wave-total-strong"><span>Total:</span><strong><?= e($iwMoney($iwTotal)) ?></strong></div>
      <?php foreach ($iwConfirmed as $iwPayment): ?>
        <div class="iw-wave-total-line iw-wave-payment-line">
          <span>Payment on <?= e($iwDate((string)($iwPayment['created_at'] ?? ''))) ?>
            <?php if (trim((string)($iwPayment['method_name'] ?? '')) !== ''): ?>
              using <?= e((string)$iwPayment['method_name']) ?>
            <?php endif; ?>:
          </span>
          <strong>−<?= e($iwMoney((float)($iwPayment['amount'] ?? 0))) ?></strong>
        </div>
      <?php endforeach; ?>
      <div class="iw-wave-total-line iw-wave-balance"><span>Amount Due (<?= e($iwCurrency) ?>):</span><strong><?= e($iwMoney($iwDue)) ?></strong></div>
    </div>

    <?php if ($iwConfirmed !== [] && $iwDue <= 0): ?>
      <p class="iw-wave-paid-note">Payment received. Thank you for choosing iWORKS.</p>
    <?php endif; ?>

    <footer class="iw-wave-footer">
      <div class="iw-wave-footer-logo">
        <img src="<?= e($logo) ?>" alt="iWORKS GLOBAL" width="170" height="25">
      </div>
      <div class="iw-wave-footer-company">
        <strong><?= e($iwFooterName) ?></strong>
        <?php if ($iwFooterAddress !== ''): ?><span><?= e($iwFooterAddress) ?></span><?php endif; ?>
      </div>
      <div class="iw-wave-footer-contact">
        <strong>Contact Information</strong>
        <?php if ($iwFooterPhone !== ''): ?><span><?= e($iwFooterPhone) ?></span><?php endif; ?>
        <?php if ($iwFooterEmail !== ''): ?><span><?= e($iwFooterEmail) ?></span><?php endif; ?>
        <span>www.iworksgt.com</span>
      </div>
    </footer>
  </div>
</article>
<?php if (!empty($paymentHistory)): ?>
  <details class="iw-wave-transaction-records print-hide">
    <summary>Transaction details (<?= count($paymentHistory) ?>)</summary>
    <div class="iw-wave-transaction-list">
    <?php foreach ($paymentHistory as $iwPayment): ?>
      <div class="iw-wave-transaction-row">
        <span><strong><?= e((string)($iwPayment['method_name'] ?? 'Payment')) ?></strong>
          <small><?= e((string)($iwPayment['reference'] ?? '')) ?></small>
          <small><?= e((string)($iwPayment['created_at'] ?? '')) ?></small></span>
        <span><strong><?= e($iwMoney((float)($iwPayment['amount'] ?? 0))) ?></strong>
          <small><?= e(ucfirst((string)($iwPayment['status'] ?? 'submitted'))) ?></small></span>
      </div>
    <?php endforeach; ?>
    </div>
    <p>Only verified payments reduce the amount due. Pending submissions are not additional charges.</p>
  </details>
<?php endif; ?>
 : null);
    return $prefix !== null ? $prefix . number_format($amount, 2) : money($amount, $iwCurrency);
};
$iwSubtotal = (float)($invoice['subtotal_amount'] ?? $iwTotal);
$iwTax = (float)($invoice['tax_amount'] ?? 0);
$iwConfirmed = [];
$iwConfirmedSum = 0.0;
foreach ((array)($paymentHistory ?? []) as $iwPayment) {
    if ((string)($iwPayment['status'] ?? '') !== 'confirmed') {
        continue;
    }
    $iwConfirmed[] = $iwPayment;
    $iwConfirmedSum += max(0.0, (float)($iwPayment['amount'] ?? 0));
}
$iwDue = (string)($invoice['status'] ?? '') === 'paid'
    ? 0.0
    : max(0.0, $iwTotal - $iwConfirmedSum);
$iwTaxPct = $iwSubtotal > 0 && $iwTax > 0 ? round($iwTax * 100 / $iwSubtotal, 2) : 0.0;
$iwTaxLabel = $iwTaxPct > 0
    ? 'Tax ' . rtrim(rtrim(number_format($iwTaxPct, 2), '0'), '.') . '%'
    : 'Tax';
$iwDate = static function (?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') return '—';
    $timestamp = strtotime($raw);
    return $timestamp === false ? $raw : date('F j, Y', $timestamp);
};
$iwFooter = function_exists('get_public_footer_data') ? get_public_footer_data('home') : [];
$iwFooterName = trim((string)($iwFooter['companyName'] ?? ''));
if ($iwFooterName === '') {
    $iwFooterName = function_exists('company_legal_name')
        ? company_legal_name() : 'iWORKS GLOBAL LTD';
}
$iwFooterAddress = trim((string)($iwFooter['address'] ?? ''));
if (strcasecmp($iwFooterAddress, 'Lagos, Nigeria') === 0) {
    $iwFooterAddress = '';
}
$iwFooterPhone = trim((string)($iwFooter['phone'] ?? ''));
if (preg_match('/^\+?234\s*806\s*000\s*0000$/', $iwFooterPhone)) {
    $iwFooterPhone = '';
}
$iwFooterEmail = trim((string)($iwFooter['email'] ?? ''));
?>
<article class="iw-wave-sheet" aria-label="Invoice <?= e((string)($invoice['invoice_number'] ?? '')) ?>">
  <header class="iw-wave-banner">
    <div class="iw-wave-banner-title">INVOICE</div>
    <div class="iw-wave-banner-due">
      <span>Amount Due (<?= e($iwCurrency) ?>)</span>
      <strong><?= e($iwMoney($iwDue)) ?></strong>
    </div>
  </header>

  <div class="iw-wave-document">
    <section class="iw-wave-parties" aria-label="Billing details">
      <div class="iw-wave-billto">
        <span class="iw-wave-overline">BILL TO</span>
        <strong><?= e((string)($invoice['client_name'] ?? '')) ?></strong>
        <?php if (trim((string)($invoice['client_phone'] ?? '')) !== ''): ?>
          <span><?= e((string)$invoice['client_phone']) ?></span>
        <?php endif; ?>
        <?php if (trim((string)($invoice['client_email'] ?? '')) !== ''): ?>
          <span class="iw-wave-email"><?= e((string)$invoice['client_email']) ?></span>
        <?php endif; ?>
      </div>
      <dl class="iw-wave-invoice-meta">
        <div><dt>Invoice Number:</dt><dd><?= e((string)($invoice['invoice_number'] ?? '')) ?></dd></div>
        <div><dt>Invoice Date:</dt><dd><?= e($iwDate((string)($invoice['issued_at'] ?? ''))) ?></dd></div>
        <div><dt>Payment Due:</dt><dd><?= e($iwDate((string)($invoice['due_at'] ?? ''))) ?></dd></div>
        <div class="iw-wave-meta-due"><dt>Amount Due (<?= e($iwCurrency) ?>):</dt><dd><?= e($iwMoney($iwDue)) ?></dd></div>
      </dl>
    </section>

    <section class="iw-wave-items" aria-label="Items billed">
      <table>
        <colgroup><col class="iw-wave-col-item"><col class="iw-wave-col-qty"><col class="iw-wave-col-price"><col class="iw-wave-col-amount"></colgroup>
        <thead><tr>
          <th scope="col">ITEMS</th><th scope="col">QUANTITY</th>
          <th scope="col">PRICE</th><th scope="col">AMOUNT</th>
        </tr></thead>
        <tbody>
          <?php if (count($invoiceJobs ?? []) > 1): ?>
            <?php foreach ($invoiceJobs as $iwLine): ?>
              <?php $iwLinePrice = (float)($iwLine['subtotal_amount'] ?? 0); ?>
              <tr>
                <td>
                  <strong><?= e((string)($iwLine['service_name'] ?? 'Service')) ?></strong>
                  <?php if (!empty($iwLine['job_code'])): ?>
                    <small><?= e((string)$iwLine['job_code']) ?></small>
                  <?php endif; ?>
                </td>
                <td class="iw-wave-numeric">1</td>
                <td class="iw-wave-numeric"><?= e($iwMoney($iwLinePrice)) ?></td>
                <td class="iw-wave-numeric"><?= e($iwMoney($iwLinePrice)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td>
                <strong><?= e((string)($invoice['service_name'] ?? 'Service')) ?></strong>
                <?php if (trim((string)($invoice['job_description'] ?? '')) !== ''): ?>
                  <span class="iw-wave-line-description"><?= nl2br(e((string)$invoice['job_description'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($invoice['job_code'])): ?>
                  <small class="iw-wave-line-job"><?= e((string)$invoice['job_code']) ?></small>
                <?php endif; ?>
              </td>
              <td class="iw-wave-numeric">1</td>
              <td class="iw-wave-numeric"><?= e($iwMoney($iwSubtotal)) ?></td>
              <td class="iw-wave-numeric"><?= e($iwMoney($iwSubtotal)) ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="iw-wave-totals" aria-label="Invoice calculation">
      <div class="iw-wave-total-line"><span>Subtotal:</span><strong><?= e($iwMoney($iwSubtotal)) ?></strong></div>
      <div class="iw-wave-total-line"><span><?= e($iwTaxLabel) ?>:</span><strong><?= e($iwMoney($iwTax)) ?></strong></div>
      <div class="iw-wave-total-line iw-wave-total-strong"><span>Total:</span><strong><?= e($iwMoney($iwTotal)) ?></strong></div>
      <?php foreach ($iwConfirmed as $iwPayment): ?>
        <div class="iw-wave-total-line iw-wave-payment-line">
          <span>Payment on <?= e($iwDate((string)($iwPayment['created_at'] ?? ''))) ?>
            <?php if (trim((string)($iwPayment['method_name'] ?? '')) !== ''): ?>
              using <?= e((string)$iwPayment['method_name']) ?>
            <?php endif; ?>:
          </span>
          <strong>−<?= e($iwMoney((float)($iwPayment['amount'] ?? 0))) ?></strong>
        </div>
      <?php endforeach; ?>
      <div class="iw-wave-total-line iw-wave-balance"><span>Amount Due (<?= e($iwCurrency) ?>):</span><strong><?= e($iwMoney($iwDue)) ?></strong></div>
    </div>

    <?php if ($iwConfirmed !== [] && $iwDue <= 0): ?>
      <p class="iw-wave-paid-note">Payment received. Thank you for choosing iWORKS.</p>
    <?php endif; ?>

    <footer class="iw-wave-footer">
      <div class="iw-wave-footer-logo">
        <img src="<?= e($logo) ?>" alt="iWORKS GLOBAL" width="170" height="25">
      </div>
      <div class="iw-wave-footer-company">
        <strong><?= e($iwFooterName) ?></strong>
        <?php if ($iwFooterAddress !== ''): ?><span><?= e($iwFooterAddress) ?></span><?php endif; ?>
      </div>
      <div class="iw-wave-footer-contact">
        <strong>Contact Information</strong>
        <?php if ($iwFooterPhone !== ''): ?><span><?= e($iwFooterPhone) ?></span><?php endif; ?>
        <?php if ($iwFooterEmail !== ''): ?><span><?= e($iwFooterEmail) ?></span><?php endif; ?>
        <span>www.iworksgt.com</span>
      </div>
    </footer>
  </div>
</article>
<?php if (!empty($paymentHistory)): ?>
  <details class="iw-wave-transaction-records print-hide">
    <summary>Transaction details (<?= count($paymentHistory) ?>)</summary>
    <div class="iw-wave-transaction-list">
    <?php foreach ($paymentHistory as $iwPayment): ?>
      <div class="iw-wave-transaction-row">
        <span><strong><?= e((string)($iwPayment['method_name'] ?? 'Payment')) ?></strong>
          <small><?= e((string)($iwPayment['reference'] ?? '')) ?></small>
          <small><?= e((string)($iwPayment['created_at'] ?? '')) ?></small></span>
        <span><strong><?= e($iwMoney((float)($iwPayment['amount'] ?? 0))) ?></strong>
          <small><?= e(ucfirst((string)($iwPayment['status'] ?? 'submitted'))) ?></small></span>
      </div>
    <?php endforeach; ?>
    </div>
    <p>Only verified payments reduce the amount due. Pending submissions are not additional charges.</p>
  </details>
<?php endif; ?>
