<?php
$purposeTitle = $otpPurpose === 'verify_email' ? 'Verify your email address' : 'Two-factor verification';
$purposeDescription = $otpPurpose === 'verify_email'
    ? 'Enter the OTP sent to your email to activate your account.'
    : 'Enter the OTP sent to your email to finish login.';
$otpQueueStatus = is_array($otpQueueDispatch ?? null) ? (string) ($otpQueueDispatch['status'] ?? '') : '';
$otpQueueUpdatedAt = is_array($otpQueueDispatch ?? null) ? (string) ($otpQueueDispatch['updated_at'] ?? '') : '';
$otpCooldown = max(0, (int) ($otpResendCooldownRemaining ?? 0));
?>
<div class="row justify-content-center">
  <div class="col-lg-5 col-md-8">
    <div class="brand-hero mb-4">
      <small class="kicker text-white-50">Email authentication</small>
      <h2 class="h4 mt-2 mb-1"><?= e($purposeTitle) ?></h2>
      <p class="mb-0 text-white-50"><?= e($purposeDescription) ?></p>
    </div>

    <div class="card">
      <div class="card-body p-4">
        <h1 class="h5 mb-2">Enter 6-digit OTP</h1>
        <p class="text-muted small">Code sent to <strong><?= e($maskedEmail) ?></strong>. It expires in <?= (int) otp_expiry_minutes() ?> minutes.</p>
        <p class="text-muted small">Email delivery may vary by provider. Use the newest code you receive. You can stay on this screen and request a new code when the resend button becomes available.</p>
        <?php if (in_array($otpQueueStatus, ['queued', 'processing', 'retrying'], true)): ?>
          <div class="alert alert-info py-2 small">
            OTP delivery is in progress. Please wait a few seconds.
            <?php if ($otpQueueUpdatedAt !== ''): ?>
              <span class="d-block text-muted">Last queue update: <?= e($otpQueueUpdatedAt) ?></span>
            <?php endif; ?>
          </div>
        <?php elseif (in_array($otpQueueStatus, ['failed'], true)): ?>
          <div class="alert alert-warning py-2 small">
            OTP delivery is delayed. You can resend or use fallback delivery.
          </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('otp')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="otp_challenge_csrf" value="<?= e(otp_recovery_form_token()) ?>">
          <input type="hidden" name="form_action" value="verify_otp">

          <div class="mb-3">
            <label class="form-label" for="otp_code">OTP code</label>
            <input class="form-control" id="otp_code" name="otp_code" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" required placeholder="000000" autocomplete="one-time-code">
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="remember_30days" name="remember_30days" value="1" <?= checked_if(($_SESSION['pending_otp_remember'] ?? '0') === '1') ?>>
            <label class="form-check-label" for="remember_30days">Trust this device for 30 days (skip OTP)</label>
          </div>

          <button class="btn btn-primary w-100" type="submit">Verify and continue</button>
        </form>

        <div class="d-flex flex-wrap gap-2 mt-3">
          <form method="post" action="<?= e(url('otp')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="otp_challenge_csrf" value="<?= e(otp_recovery_form_token()) ?>">
            <input type="hidden" name="form_action" value="resend_otp">
            <button
              class="btn btn-outline-secondary btn-sm"
              type="submit"
              id="otpResendBtn"
              data-cooldown-seconds="<?= (int) $otpCooldown ?>"
              <?= $otpCooldown > 0 ? 'disabled' : '' ?>
            >
              <?= $otpCooldown > 0 ? 'Resend in ' . (int) $otpCooldown . 's' : 'Resend code' ?>
            </button>
          </form>
          <?php if (!empty($otpFallbackAllowed)): ?>
            <form method="post" action="<?= e(url('otp')) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="otp_challenge_csrf" value="<?= e(otp_recovery_form_token()) ?>">
              <input type="hidden" name="form_action" value="fallback_otp">
              <button class="btn btn-outline-warning btn-sm" type="submit">Send via fallback</button>
            </form>
          <?php endif; ?>
          <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('login')) ?>">Back to login</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var resendBtn = document.getElementById('otpResendBtn');
  if (!resendBtn) {
    return;
  }

  var seconds = parseInt(resendBtn.getAttribute('data-cooldown-seconds') || '0', 10);
  if (Number.isNaN(seconds) || seconds <= 0) {
    return;
  }

  function tick() {
    if (seconds <= 0) {
      resendBtn.disabled = false;
      resendBtn.textContent = 'Resend code';
      return;
    }

    resendBtn.disabled = true;
    resendBtn.textContent = 'Resend in ' + seconds + 's';
    seconds -= 1;
    window.setTimeout(tick, 1000);
  }

  tick();
})();
</script>
