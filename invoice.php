<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

try {
    ensure_crypto_schema_upgrades();
} catch (Throwable $e) {
    // Keep invoice view running even when schema auto patch cannot run.
}

require_login();

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = get_invoice($invoiceId);

if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found.');
}

$user = current_user();

if ($user['role'] === 'client' && (int) $invoice['client_id'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Not allowed.');
}

if (!in_array($user['role'], ['client', 'admin', 'finance'], true)) {
    http_response_code(403);
    exit('Not allowed.');
}

if (method_is('POST') && $user['role'] === 'client') {
    require_csrf();

    try {
        if (($_POST['form_action'] ?? '') === 'submit_payment') {
            $methodId = (int) ($_POST['payment_method_id'] ?? 0);
            $method = get_payment_method($methodId);
            if (!$method || (int) $method['is_active'] !== 1) {
                throw new RuntimeException('Select a valid active payment method.');
            }

            $methodCode = (string) ($method['method_code'] ?? '');
            if (is_gateway_payment_method($methodCode)) {
                $checkoutUrl = start_gateway_checkout($invoiceId, (int) $user['id'], $methodId);
                redirect($checkoutUrl);
            }

            submit_invoice_payment(
                $invoiceId,
                (int) $user['id'],
                $methodId,
                trim((string) ($_POST['payment_reference'] ?? '')),
                trim((string) ($_POST['payment_note'] ?? '')),
                $_FILES['payment_proof'] ?? null,
                [
                    'pay_currency' => (string) ($invoice['currency'] ?? default_currency()),
                    'crypto_asset' => (string) ($_POST['crypto_asset'] ?? 'USDT'),
                    'crypto_network' => (string) ($_POST['crypto_network'] ?? ''),
                ]
            );

            flash('success', 'Payment submitted successfully. Finance team will confirm shortly.');
            redirect('invoice.php?id=' . $invoiceId);
        }
    } catch (Throwable $e) {
        // Never display provider responses containing Stripe API credentials,
        // even masked keys. Keep client errors safe and actionable.
        if (isset($methodCode) && is_gateway_payment_method((string) $methodCode)) {
            error_log('iWorks invoice gateway checkout failed; verify active provider credentials and configuration.');
            flash('error', 'Secure payment is temporarily unavailable through this provider. Please contact support or select another available method.');
        } else {
            flash('error', iworks_client_safe_payment_error($e->getMessage()));
        }
        redirect('invoice.php?id=' . $invoiceId);
    }
}

$invoice = get_invoice($invoiceId);
$methods = list_available_payment_methods_for_currency((string) ($invoice['currency'] ?? default_currency()));
$payModePref = strtolower(trim((string) ($_GET['pay_mode'] ?? ($invoice['pay_method'] ?? 'card'))));
if (!in_array($payModePref, ['card', 'bank', 'crypto', 'card_transfer'], true)) {
    $payModePref = 'card';
}
if ($payModePref === 'card_transfer') {
    $payModePref = 'card';
}
$cryptoAssetPref = normalize_crypto_asset((string) ($_GET['crypto_asset'] ?? ($invoice['crypto_asset'] ?? 'USDT')));
$cryptoNetworkPref = normalize_crypto_network($cryptoAssetPref, (string) ($_GET['crypto_network'] ?? ($invoice['crypto_network'] ?? 'TRC20')));
$cryptoQuote = get_crypto_checkout_quote((float) ($invoice['amount'] ?? 0), normalize_payment_currency((string) ($invoice['currency'] ?? default_currency())), false);
$cardLikeMethods = [];
$cryptoMethods = [];
foreach ($methods as $methodRow) {
    $code = strtolower(trim((string) ($methodRow['method_code'] ?? '')));
    if ($code === 'crypto_wallet') {
        $cryptoMethods[] = $methodRow;
    } else {
        $cardLikeMethods[] = $methodRow;
    }
}
$hasCryptoMethods = count($cryptoMethods) > 0;
$hasCardLikeMethods = count($cardLikeMethods) > 0;
if ($payModePref === 'crypto' && !$hasCryptoMethods) {
    $payModePref = 'card';
}
if ($payModePref !== 'crypto' && !$hasCardLikeMethods && $hasCryptoMethods) {
    $payModePref = 'crypto';
}
$initialMethodPool = $payModePref === 'crypto' ? $cryptoMethods : $cardLikeMethods;
if (!$initialMethodPool) {
    $initialMethodPool = $methods;
}
$initialMethodId = (int) (($initialMethodPool[0]['id'] ?? 0));
$paymentHistory = list_payments_for_invoice($invoiceId);
$invoiceJobs = list_invoice_jobs($invoiceId);
$hasMethods = count($methods) > 0;
$logo = logo_path();
$favicon = favicon_path();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($invoice['invoice_number']) ?> | iWorks billing</title>
  <meta name="description" content="<?= e(seo_description()) ?>">
  <?php if ($favicon !== ''): ?>
    <link rel="icon" type="image/png" href="<?= e($favicon) ?>">
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand-primary: <?= e(brand_primary_color()) ?>;
      --brand-primary-dark: <?= e(brand_primary_dark_color()) ?>;
      --brand-accent: <?= e(brand_accent_color()) ?>;
    }
    body {
      font-family: 'Outfit', sans-serif;
      margin: 0;
      background: #f3f4f6;
      color: #111827;
      font-size: 13px;
      line-height: 1.45;
    }
    .wrap {
      max-width: 860px;
      margin: 16px auto;
      background: #fff;
      padding: 16px;
      border-radius: 10px;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }
    .head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 10px;
    }
    .head-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .brand {
      font-weight: 700;
      font-size: 1.05rem;
      color: var(--brand-primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .brand img { height: 30px; width: auto; border-radius: 5px; }
    h3 { font-size: 0.96rem; margin: 0 0 6px; }
    .muted { color: #6b7280; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    th, td { border-bottom: 1px solid #e5e7eb; text-align: left; padding: 6px 0; vertical-align: top; }
    th { color: #6b7280; font-weight: 600; width: 34%; }
    .total { font-size: 1.02rem; font-weight: 700; }
    .badge { padding: 2px 8px; border-radius: 999px; font-size: 10px; background: #eef2ff; font-weight: 600; }
    .method { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 8px; }
    .method.hidden { display: none; }
    .method-option { border: 1px solid #d1d5db; border-radius: 8px; padding: 8px; margin-bottom: 6px; cursor: pointer; }
    .method-option.active { border-color: var(--brand-primary); box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.22); }
    .method-option input { width: auto; margin-right: 8px; }
    .method-mode-switch { display: flex; gap: 8px; flex-wrap: wrap; }
    .method-mode-pill { border: 1px solid #d1d5db; border-radius: 999px; padding: 6px 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .method-mode-pill input { width: auto; margin: 0; }
    .method-mode-pill.active { border-color: var(--brand-primary); background: #fff4eb; }
    .gateway-note { background: #fff4eb; border: 1px solid #ffd3b9; color: #9a3412; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; }
    .manual-fields.hidden { display: none; }
    .hidden { display: none !important; }
    .btn { border: 0; border-radius: 7px; padding: 8px 12px; cursor: pointer; font-size: 12.5px; }
    .btn-primary { background: var(--brand-primary); color: white; }
    .btn-secondary { background: #e5e7eb; color: #111827; }
    input, select, textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 8px; font-family: inherit; font-size: 12.5px; }
    label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; }
    .alert { padding: 8px 10px; border-radius: 8px; margin-bottom: 10px; }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-danger { background: #fee2e2; color: #991b1b; }
    .print-hide { display: inline-block; }
    .mt-3 { margin-top: 10px; }
    .mb-2 { margin-bottom: 6px; }
    .mb-3 { margin-bottom: 10px; }
    .small { font-size: 11.5px; }
    .link-btn { color: var(--brand-primary); text-decoration: none; }
    @media (max-width: 900px) {
      .wrap { margin: 8px; padding: 12px; }
      .grid { grid-template-columns: 1fr; }
      table { font-size: 12px; }
      .head { align-items: flex-start; flex-wrap: wrap; }
      .head-actions { width: 100%; justify-content: flex-start; }
    }
    @media print {
      .print-hide { display: none !important; }
      body { background: #fff; }
      .wrap {
        box-shadow: none;
        margin: 0;
        max-width: none;
        border-radius: 0;
        padding: 10mm 8mm;
      }
      @page {
        margin: 8mm;
        size: A4;
      }
    }
  </style>
  <link rel="stylesheet" href="assets/css/client-checkout.css?v=20261112a">
</head>
<body class="<?= $user['role'] === 'client' ? 'iw-invoice-client' : '' ?>">
  <div class="wrap">
    <div class="head">
      <div>
        <div class="brand">
          <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="Logo"><?php else: ?><?= e(brand_name()) ?> Portal<?php endif; ?>
        </div>
        <div class="muted">Service invoice</div>
      </div>
      <div class="print-hide head-actions">
        <button
          class="btn btn-secondary"
          type="button"
          id="invoiceBackBtn"
          data-fallback-url="<?= e($user['role'] === 'client' ? url('applications') : url('invoices')) ?>"
        >Back</button>
        <button class="btn btn-secondary" onclick="window.print()">Print</button>
      </div>
    </div>

    <?php if ($user['role'] === 'client'): ?>
      <div class="iw-invoice-hero">
        <div><span class="iw-invoice-eyebrow">SECURE PAYMENT</span>
          <h1>Review your invoice</h1>
          <p><?= e((string) ($invoice['service_name'] ?? 'iWorks service')) ?> · <?= e((string) ($invoice['invoice_number'] ?? '')) ?></p>
        </div>
        <div class="iw-invoice-hero-amount">
          <span>Amount due</span><strong><?= e(money((float) ($invoice['amount'] ?? 0), (string) ($invoice['currency'] ?? default_currency()))) ?></strong>
          <small><?= e(ucfirst((string) ($invoice['status'] ?? 'issued'))) ?></small>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($user['role'] === 'client'): ?>
      <details class="iw-invoice-customer-details"><summary>Invoice details <span>Reference, dates and billing contact</span></summary>
    <?php endif; ?>
    <div class="grid mb-3">
      <div>
        <table>
          <tr><th>Invoice</th><td><?= e($invoice['invoice_number']) ?></td></tr>
          <tr><th>Issued at</th><td><?= e($invoice['issued_at']) ?></td></tr>
          <tr><th>Due at</th><td><?= e($invoice['due_at']) ?></td></tr>
          <tr><th>Status</th><td><span class="badge"><?= e(strtoupper($invoice['status'])) ?></span></td></tr>
        </table>
      </div>
      <div>
        <table>
          <tr><th>Client</th><td><?= e($invoice['client_name']) ?></td></tr>
          <tr><th>Email</th><td><?= e($invoice['client_email']) ?></td></tr>
          <tr><th>Phone</th><td><?= e($invoice['client_phone']) ?></td></tr>
        </table>
      </div>
    </div>

    <?php if ($user['role'] === 'client'): ?></details><?php endif; ?>

    <div class="iw-invoice-main-grid">
    <section class="iw-invoice-order">
    <h3>Service summary</h3>
    <?php if (count($invoiceJobs) > 1): ?>
      <table class="mb-3">
        <thead>
          <tr><th>Job</th><th>Service</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($invoiceJobs as $line): ?>
            <tr>
              <td><?= e((string) $line['job_code']) ?></td>
              <td><?= e((string) $line['service_name']) ?></td>
              <td><?= e(money((float) ($line['subtotal_amount'] ?? 0), $invoice['currency'] ?? default_currency())) ?></td>
              <td><?= e(money((float) ($line['tax_amount'] ?? 0), $invoice['currency'] ?? default_currency())) ?> (<?= e(number_format((float) ($line['tax_percent'] ?? 0), 2)) ?>%)</td>
              <td><?= e(money((float) ($line['amount'] ?? 0), $invoice['currency'] ?? default_currency())) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table class="mb-3">
        <tr><th>Job</th><td><?= e($invoice['job_code']) ?></td></tr>
        <tr><th>Service</th><td><?= e($invoice['service_name']) ?></td></tr>
        <tr><th>Description</th><td><?= e($invoice['job_description'] ?: '-') ?></td></tr>
      </table>
    <?php endif; ?>
    <table class="mb-3">
      <tr><th>Subtotal</th><td><?= e(money((float) ($invoice['subtotal_amount'] ?? $invoice['amount']), $invoice['currency'] ?? default_currency())) ?></td></tr>
      <tr><th>Tax</th><td><?= e(money((float) ($invoice['tax_amount'] ?? 0), $invoice['currency'] ?? default_currency())) ?></td></tr>
      <tr><th>Total</th><td class="total"><?= e(money((float) $invoice['amount'], $invoice['currency'] ?? default_currency())) ?></td></tr>
    </table>

    <?php if ((string) ($invoice['pay_method'] ?? '') === 'crypto' && (float) ($invoice['crypto_amount_due'] ?? 0) > 0): ?>
      <table class="mb-3">
        <tr><th>Locked payment mode</th><td>Crypto</td></tr>
        <tr><th>Send amount</th><td><?= e(number_format((float) $invoice['crypto_amount_due'], crypto_asset_precision((string) ($invoice['crypto_asset'] ?? 'USDT'), true), '.', '')) ?> <?= e((string) ($invoice['crypto_asset'] ?? 'USDT')) ?></td></tr>
        <tr><th>Network</th><td><?= e((string) ($invoice['crypto_network'] ?? '-')) ?></td></tr>
        <tr><th>Rate used</th><td>1 <?= e((string) ($invoice['crypto_asset'] ?? 'CRYPTO')) ?> = USD <?= e(number_format((float) ($invoice['crypto_rate_used'] ?? 0), 8)) ?></td></tr>
        <tr><th>Locked at</th><td><?= e((string) ($invoice['rate_locked_at'] ?? '')) ?></td></tr>
      </table>
    <?php endif; ?>

    </section>
    <section class="iw-invoice-checkout">
    <?php if ($user['role'] === 'client' && $invoice['status'] !== 'paid'): ?>
      <h3>Choose how to pay</h3>
      <p class="muted small">Available options depend on the invoice currency. Your payment details are entered with the provider, not iWorks.</p>
      <?php if (strtoupper((string) ($invoice['currency'] ?? '')) === 'USD'): ?>
        <p class="iw-invoice-saved-cards"><i class="bi bi-lock-fill" aria-hidden="true"></i>
          Saved a card with Stripe? It can appear during Stripe checkout.
          <a href="<?= e(url('card_vault')) ?>">Manage saved cards</a></p>
      <?php endif; ?>

      <?php if (!$hasMethods): ?>
        <div class="alert alert-danger">No payment method is active for <?= e($invoice['currency'] ?? default_currency()) ?>. Contact admin.</div>
      <?php endif; ?>

      <form method="post" action="invoice.php?id=<?= (int) $invoiceId ?>" enctype="multipart/form-data" id="invoicePaymentForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="submit_payment">
        <input type="hidden" name="crypto_asset" id="invoiceCryptoAsset" value="<?= e($cryptoAssetPref) ?>">
        <input type="hidden" name="crypto_network" id="invoiceCryptoNetwork" value="<?= e((string) ($cryptoNetworkPref ?? 'TRC20')) ?>">

        <div class="method-mode-switch mb-3" id="methodModeSwitch">
          <label class="method-mode-pill <?= $payModePref !== 'crypto' ? 'active' : '' ?>">
            <input type="radio" name="method_pool" value="card" <?= $payModePref !== 'crypto' ? 'checked' : '' ?> <?= $hasCardLikeMethods ? '' : 'disabled' ?>>
            Card / Transfer
          </label>
          <label class="method-mode-pill <?= $payModePref === 'crypto' ? 'active' : '' ?>">
            <input type="radio" name="method_pool" value="crypto" <?= $payModePref === 'crypto' ? 'checked' : '' ?> <?= $hasCryptoMethods ? '' : 'disabled' ?>>
            Crypto
          </label>
        </div>

        <label class="mb-2">Payment method</label>
        <div id="methodOptions">
          <?php foreach ($methods as $index => $method): ?>
            <?php $methodCode = (string) ($method['method_code'] ?? ''); ?>
            <?php $isGateway = is_gateway_payment_method($methodCode); ?>
            <?php $pool = strtolower($methodCode) === 'crypto_wallet' ? 'crypto' : 'card'; ?>
            <?php
              $checked = (int) $method['id'] === $initialMethodId;
              $hiddenByPool = $payModePref === 'crypto' ? $pool !== 'crypto' : $pool === 'crypto';
            ?>
            <label class="method-option <?= $checked ? 'active' : '' ?> <?= $hiddenByPool ? 'hidden' : '' ?>" data-method-pool="<?= e($pool) ?>">
              <input
                type="radio"
                name="payment_method_id"
                value="<?= (int) $method['id'] ?>"
                data-method-id="<?= (int) $method['id'] ?>"
                data-method-code="<?= e($methodCode) ?>"
                data-method-pool="<?= e($pool) ?>"
                data-is-gateway="<?= $isGateway ? '1' : '0' ?>"
                data-requires-proof="<?= (int) ($method['requires_proof'] ?? 1) === 1 ? '1' : '0' ?>"
                <?= $checked ? 'checked' : '' ?>
                required
              >
              <strong><?= e($method['display_name']) ?></strong>
            </label>
          <?php endforeach; ?>
        </div>

        <?php foreach ($methods as $index => $method): ?>
          <?php $methodCode = (string) ($method['method_code'] ?? ''); ?>
          <?php $isGateway = is_gateway_payment_method($methodCode); ?>
          <?php $pool = strtolower($methodCode) === 'crypto_wallet' ? 'crypto' : 'card'; ?>
          <?php $isVisible = (int) $method['id'] === $initialMethodId; ?>
          <div class="method <?= $isVisible ? '' : 'hidden' ?>" data-method-panel="<?= (int) $method['id'] ?>" data-method-pool="<?= e($pool) ?>">
            <strong><?= e($method['display_name']) ?></strong>
            <div class="small muted mb-2"><?= nl2br(e($method['details'] ?? 'No instructions configured yet.')) ?></div>
            <?php if ($isGateway): ?>
              <div class="gateway-note">Continue to the payment provider. Your service starts after payment is verified.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="method <?= $payModePref === 'crypto' ? '' : 'hidden' ?>" id="cryptoQuotePanel">
          <strong>Crypto conversion quote</strong>
          <div class="small muted mb-2">Send exactly this amount. Network fees are separate.</div>
          <div class="small muted mb-2" id="invoiceRatesMeta">
            Rates last updated: <?= e((string) ($cryptoQuote['rates_last_updated_at'] ?? '')) ?>
          </div>
          <div class="gateway-note <?= (string) ($cryptoQuote['rates_warning'] ?? '') !== '' ? '' : 'hidden' ?>" id="invoiceRatesWarning"><?= e((string) ($cryptoQuote['rates_warning'] ?? '')) ?></div>
          <label class="method-option <?= $cryptoAssetPref === 'USDT' ? 'active' : '' ?>">
            <input type="radio" name="invoice_crypto_asset_choice" value="USDT" <?= $cryptoAssetPref === 'USDT' ? 'checked' : '' ?>>
            <span>USDT: Send = <strong id="invoiceAmountUSDT"><?= e((string) (($cryptoQuote['assets']['USDT']['amount_display_formatted'] ?? '0.00'))) ?></strong></span>
          </label>
          <label class="method-option <?= $cryptoAssetPref === 'LTC' ? 'active' : '' ?>">
            <input type="radio" name="invoice_crypto_asset_choice" value="LTC" <?= $cryptoAssetPref === 'LTC' ? 'checked' : '' ?>>
            <span>LTC: Send = <strong id="invoiceAmountLTC"><?= e((string) (($cryptoQuote['assets']['LTC']['amount_display_formatted'] ?? '0.00000000'))) ?></strong></span>
          </label>
          <label class="method-option <?= $cryptoAssetPref === 'BTC' ? 'active' : '' ?>">
            <input type="radio" name="invoice_crypto_asset_choice" value="BTC" <?= $cryptoAssetPref === 'BTC' ? 'checked' : '' ?>>
            <span>BTC: Send = <strong id="invoiceAmountBTC"><?= e((string) (($cryptoQuote['assets']['BTC']['amount_display_formatted'] ?? '0.00000000'))) ?></strong></span>
          </label>
          <div class="mt-2" id="invoiceCryptoNetworkWrap">
            <label for="invoiceCryptoNetworkSelect">USDT network</label>
            <select id="invoiceCryptoNetworkSelect">
              <option value="TRC20" <?= (($cryptoNetworkPref ?? 'TRC20') === 'TRC20') ? 'selected' : '' ?>>TRC20 (default)</option>
              <option value="ERC20" <?= (($cryptoNetworkPref ?? 'TRC20') === 'ERC20') ? 'selected' : '' ?>>ERC20</option>
            </select>
          </div>
        </div>

        <div id="manualFields" class="manual-fields hidden">
          <label for="payment_reference">Payment reference / transaction ID</label>
          <input type="text" id="payment_reference" name="payment_reference" class="mb-3" placeholder="Enter transfer reference, TX ID, or gateway reference">

          <label for="payment_proof" id="paymentProofLabel">Proof of payment (required)</label>
          <div id="proofFieldWrap">
            <input type="file" id="payment_proof" name="payment_proof" class="mb-3">
          </div>

          <label for="payment_note">Payment note (optional)</label>
          <textarea id="payment_note" name="payment_note" rows="2" class="mb-3" placeholder="Any note for finance/admin"></textarea>
        </div>

        <button class="btn btn-primary" id="paySubmitBtn" type="submit" <?= !$hasMethods ? 'disabled' : '' ?>>Continue securely &rarr;</button>
      </form>
    <?php endif; ?>

    </section>
    </div>
    <?php if ($paymentHistory): ?>
      <h3 class="mt-3">Payment history</h3>
      <table>
        <thead><tr><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th><th>Proof</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($paymentHistory as $payment): ?>
            <tr>
              <td><?= e($payment['method_name']) ?></td>
              <td><?= e($payment['reference']) ?></td>
              <td><?= e(money((float) $payment['amount'], $invoice['currency'] ?? default_currency())) ?></td>
              <td><?= e(ucwords($payment['status'])) ?></td>
              <td><?php if (!empty($payment['proof_path'])): ?><a class="link-btn" href="download.php?payment_id=<?= (int) $payment['id'] ?>">Download</a><?php else: ?>-<?php endif; ?></td>
              <td><?= e($payment['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <script>
    (function () {
      var backBtn = document.getElementById('invoiceBackBtn');
      if (!backBtn) return;

      backBtn.addEventListener('click', function () {
        var fallbackUrl = backBtn.getAttribute('data-fallback-url') || 'index.php?page=invoices';
        if (window.self !== window.top) {
          try {
            window.parent.postMessage({ type: 'iworksgt_invoice_preview_close' }, window.location.origin);
            return;
          } catch (err) {
          }
        }
        window.location.href = fallbackUrl;
      });
    })();

    (function () {
      var options = document.querySelectorAll('#methodOptions input[type=\"radio\"]');
      if (!options.length) return;

      var paymentForm = document.getElementById('invoicePaymentForm');
      var modeRadios = document.querySelectorAll('#methodModeSwitch input[name=\"method_pool\"]');
      var panels = document.querySelectorAll('[data-method-panel]');
      var manualFields = document.getElementById('manualFields');
      var refInput = document.getElementById('payment_reference');
      var proofInput = document.getElementById('payment_proof');
      var proofFieldWrap = document.getElementById('proofFieldWrap');
      var proofLabel = document.getElementById('paymentProofLabel');
      var submitBtn = document.getElementById('paySubmitBtn');
      var cryptoQuotePanel = document.getElementById('cryptoQuotePanel');
      var cryptoAssetChoices = document.querySelectorAll('input[name=\"invoice_crypto_asset_choice\"]');
      var cryptoAssetInput = document.getElementById('invoiceCryptoAsset');
      var cryptoNetworkInput = document.getElementById('invoiceCryptoNetwork');
      var cryptoNetworkWrap = document.getElementById('invoiceCryptoNetworkWrap');
      var cryptoNetworkSelect = document.getElementById('invoiceCryptoNetworkSelect');
      var ratesMeta = document.getElementById('invoiceRatesMeta');
      var ratesWarning = document.getElementById('invoiceRatesWarning');
      var amountUSDT = document.getElementById('invoiceAmountUSDT');
      var amountLTC = document.getElementById('invoiceAmountLTC');
      var amountBTC = document.getElementById('invoiceAmountBTC');
      var invoiceAmount = <?= json_encode((float) ($invoice['amount'] ?? 0)) ?>;
      var invoiceCurrency = <?= json_encode(normalize_payment_currency((string) ($invoice['currency'] ?? default_currency()))) ?>;
      var pollTimer = null;

      function syncFormTarget(isGateway) {
        if (!paymentForm) return;
        if (isGateway && window.self !== window.top) {
          paymentForm.setAttribute('target', '_top');
        } else {
          paymentForm.setAttribute('target', '_self');
        }
      }

      function activePool() {
        var selected = document.querySelector('#methodModeSwitch input[name=\"method_pool\"]:checked');
        return selected ? selected.value : 'card';
      }

      function selectedCryptoAsset() {
        var selected = document.querySelector('input[name=\"invoice_crypto_asset_choice\"]:checked');
        return selected ? String(selected.value || 'USDT').toUpperCase() : 'USDT';
      }

      function syncCryptoSelection() {
        var asset = selectedCryptoAsset();
        if (cryptoAssetInput) {
          cryptoAssetInput.value = asset;
        }
        if (cryptoNetworkWrap) {
          cryptoNetworkWrap.style.display = asset === 'USDT' ? '' : 'none';
        }
        if (cryptoNetworkInput) {
          cryptoNetworkInput.value = asset === 'USDT'
            ? String((cryptoNetworkSelect && cryptoNetworkSelect.value) || 'TRC20')
            : '';
        }

        document.querySelectorAll('label.method-option').forEach(function (label) {
          if (label.querySelector('input[name=\"invoice_crypto_asset_choice\"]')) {
            label.classList.remove('active');
          }
        });
        var selectedChoice = document.querySelector('input[name=\"invoice_crypto_asset_choice\"]:checked');
        if (selectedChoice && selectedChoice.closest('label.method-option')) {
          selectedChoice.closest('label.method-option').classList.add('active');
        }
      }

      function renderQuote(data) {
        if (!data || typeof data !== 'object') {
          return;
        }

        var assets = data.assets || {};
        if (amountUSDT && assets.USDT) amountUSDT.textContent = assets.USDT.amount_display_formatted || '0.00';
        if (amountLTC && assets.LTC) amountLTC.textContent = assets.LTC.amount_display_formatted || '0.00000000';
        if (amountBTC && assets.BTC) amountBTC.textContent = assets.BTC.amount_display_formatted || '0.00000000';

        if (ratesMeta) {
          var parts = [];
          if (data.rates_last_updated_at) parts.push('Rates last updated: ' + data.rates_last_updated_at);
          if (data.rates_source) parts.push('Source: ' + data.rates_source);
          ratesMeta.textContent = parts.join(' • ');
        }

        if (ratesWarning) {
          if (data.rates_warning) {
            ratesWarning.textContent = data.rates_warning;
            ratesWarning.classList.remove('hidden');
          } else {
            ratesWarning.textContent = '';
            ratesWarning.classList.add('hidden');
          }
        }
      }

      function refreshQuote() {
        if (activePool() !== 'crypto') {
          return;
        }
        fetch('api/rates/?amount=' + encodeURIComponent(invoiceAmount.toFixed(2)) + '&currency=' + encodeURIComponent(invoiceCurrency), {
          credentials: 'same-origin',
          cache: 'no-store'
        })
          .then(function (resp) {
            if (!resp.ok) throw new Error('Rates fetch failed.');
            return resp.json();
          })
          .then(function (data) {
            if (!data || data.success !== true) return;
            renderQuote(data);
          })
          .catch(function () {
            // Keep existing quote when refresh fails.
          });
      }

      function setActive() {
        var selected = document.querySelector('#methodOptions input[type=\"radio\"]:checked');
        var pool = activePool();

        document.querySelectorAll('#methodModeSwitch .method-mode-pill').forEach(function (pill) {
          pill.classList.remove('active');
        });
        var activePoolRadio = document.querySelector('#methodModeSwitch input[name=\"method_pool\"]:checked');
        if (activePoolRadio && activePoolRadio.closest('.method-mode-pill')) {
          activePoolRadio.closest('.method-mode-pill').classList.add('active');
        }

        var visibleOptions = [];
        options.forEach(function (opt) {
          var label = opt.closest('.method-option');
          var optPool = opt.getAttribute('data-method-pool') || 'card';
          var shouldShow = (pool === 'crypto') ? (optPool === 'crypto') : (optPool !== 'crypto');
          if (label) {
            label.classList.toggle('hidden', !shouldShow);
          }
          if (shouldShow) {
            visibleOptions.push(opt);
          } else {
            opt.checked = false;
          }
        });

        if (!selected || !selected.checked || selected.closest('.method-option').classList.contains('hidden')) {
          selected = visibleOptions[0] || null;
          if (selected) {
            selected.checked = true;
          }
        }
        if (!selected) {
          if (cryptoQuotePanel) {
            cryptoQuotePanel.classList.add('hidden');
          }
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'No method available';
          }
          syncFormTarget(false);
          return;
        }
        if (submitBtn) {
          submitBtn.disabled = false;
        }

        var selectedId = selected.getAttribute('data-method-id');
        var isGateway = selected.getAttribute('data-is-gateway') === '1';
        var requiresProof = selected.getAttribute('data-requires-proof') === '1';
        var methodCode = selected.getAttribute('data-method-code') || '';
        var isCryptoMethod = methodCode.toLowerCase() === 'crypto_wallet';

        document.querySelectorAll('.method-option').forEach(function (label) {
          if (label.querySelector('input[name=\"payment_method_id\"]')) {
            label.classList.remove('active');
          }
        });
        selected.closest('.method-option').classList.add('active');

        panels.forEach(function (panel) {
          if (panel.getAttribute('data-method-panel') === selectedId) {
            panel.classList.remove('hidden');
          } else {
            panel.classList.add('hidden');
          }
        });

        if (cryptoQuotePanel) {
          cryptoQuotePanel.classList.toggle('hidden', !(pool === 'crypto' && isCryptoMethod));
        }

        if (manualFields) {
          if (isGateway) {
            manualFields.classList.add('hidden');
            if (refInput) refInput.required = false;
            if (proofInput) proofInput.required = false;
            if (proofFieldWrap) proofFieldWrap.style.display = 'none';
            if (proofLabel) proofLabel.style.display = 'none';
            if (submitBtn) submitBtn.textContent = 'Pay now';
          } else {
            manualFields.classList.remove('hidden');
            if (refInput) refInput.required = true;
            if (proofFieldWrap) proofFieldWrap.style.display = requiresProof ? '' : 'none';
            if (proofInput) proofInput.required = requiresProof;
            if (proofLabel) proofLabel.style.display = requiresProof ? '' : 'none';
            if (submitBtn) {
              submitBtn.textContent = isCryptoMethod ? 'Submit crypto payment proof' : (requiresProof ? 'Submit payment proof' : 'Submit payment');
            }
          }
        }

        syncFormTarget(isGateway);

        syncCryptoSelection();
        if (pool === 'crypto') {
          refreshQuote();
          if (pollTimer) {
            window.clearInterval(pollTimer);
          }
          pollTimer = window.setInterval(refreshQuote, 90000);
        } else if (pollTimer) {
          window.clearInterval(pollTimer);
          pollTimer = null;
        }
      }

      modeRadios.forEach(function (radio) {
        radio.addEventListener('change', setActive);
      });
      options.forEach(function (opt) {
        opt.addEventListener('change', setActive);
      });
      cryptoAssetChoices.forEach(function (choice) {
        choice.addEventListener('change', syncCryptoSelection);
      });
      if (cryptoNetworkSelect) {
        cryptoNetworkSelect.addEventListener('change', syncCryptoSelection);
      }

      setActive();
    })();
  </script>
</body>
</html>
