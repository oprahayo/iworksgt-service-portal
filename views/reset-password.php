<?php $token = trim((string) ($_GET['token'] ?? '')); ?>
<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Reset password</h1>
        <p class="text-muted small mb-3">Enter your new password below.</p>
        <form method="post" action="<?= e(url('reset_password', ['token' => $token])) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="mb-3">
            <label class="form-label" for="new_password">New password</label>
            <input class="form-control" id="new_password" name="new_password" type="password" minlength="6" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm password</label>
            <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Update password</button>
        </form>
        <p class="small text-muted mt-3 mb-0"><a href="<?= e(url('login')) ?>">Back to login</a></p>
      </div>
    </div>
  </div>
</div>
