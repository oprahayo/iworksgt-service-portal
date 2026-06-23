<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body p-4">
        <?php if (!empty($paymentResult['success'])): ?>
          <h1 class="h4 text-success">Payment Successful</h1>
          <p><?= e((string) ($paymentResult['message'] ?? 'Your payment has been verified successfully.')) ?></p>
        <?php else: ?>
          <h1 class="h4 text-danger">Payment Failed</h1>
          <p><?= e((string) ($paymentResult['message'] ?? 'We could not verify your payment yet.')) ?></p>
        <?php endif; ?>

        <?php if (!empty($paymentResult['redirect_url'])): ?>
          <a class="btn btn-primary" href="<?= e((string) $paymentResult['redirect_url']) ?>">Continue</a>
        <?php else: ?>
          <a class="btn btn-primary" href="<?= e(url('invoices')) ?>">Back to invoices</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
