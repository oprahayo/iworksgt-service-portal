<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Forgot password</h1>
        <p class="text-muted small mb-3">Enter your account email and we will send a reset link.</p>
        <form method="post" action="<?= e(url('forgot_password')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" required value="<?= e(old_input('email')) ?>">
          </div>
          <button class="btn btn-primary w-100" type="submit">Send reset link</button>
        </form>
        <p class="small text-muted mt-3 mb-0"><a href="<?= e(url('login')) ?>">Back to login</a></p>
      </div>
    </div>
  </div>
</div>
