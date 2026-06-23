<section class="login-v3-layout" aria-label="<?= e(brand_name()) ?> login">
  <div class="login-v3-grid">
    <aside class="login-v3-info" aria-label="How to use the portal">
      <div class="login-v3-logos">
        <?php if (logo_path() !== ''): ?>
          <img src="<?= e(logo_path()) ?>" alt="<?= e(brand_name()) ?>" class="login-v3-logo-main">
        <?php else: ?>
          <div class="login-v3-logo-fallback"><?= e(brand_name()) ?></div>
        <?php endif; ?>
        <div class="login-v3-logo-divider"></div>
        <div class="login-v3-logo-pill">
          <i class="bi bi-patch-check"></i>
          <span><?= e(app_name()) ?></span>
        </div>
      </div>
      <p class="small text-muted mb-3"><?= e(business_support_summary()) ?></p>

      <div class="login-v3-step-card">
        <div class="login-v3-step-icon"><i class="bi bi-person-check"></i></div>
        <div>
          <h3>Create account or sign in</h3>
          <p>Use one secure login and the system will open the right dashboard for your role automatically.</p>
        </div>
      </div>

      <div class="login-v3-step-card">
        <div class="login-v3-step-icon is-dark"><i class="bi bi-credit-card-2-front"></i></div>
        <div>
          <h3>Request travel service or package</h3>
          <p>Choose flights, hotels, visa help, transfers, tours, or packages and continue with payment or quote flow.</p>
        </div>
      </div>

      <div class="login-v3-step-card">
        <div class="login-v3-step-icon is-gray"><i class="bi bi-truck"></i></div>
        <div>
          <h3>Track updates and manage your request</h3>
          <p>Follow status, upload documents, respond to agents, and keep everything in one mobile-friendly dashboard.</p>
        </div>
      </div>
    </aside>

    <article class="login-v3-auth-card" aria-labelledby="loginTitle">
      <h1 class="login-v3-title" id="loginTitle">Welcome Back</h1>
      <p class="login-v3-subtitle">Sign in to your account</p>

      <form method="post" action="<?= e(url('login')) ?>" class="login-v3-form" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div>
          <label class="form-label" for="email">Email</label>
          <input class="form-control" id="email" name="email" type="email" required value="<?= e(old_input('email')) ?>" autocomplete="email" placeholder="you@example.com">
        </div>

        <div>
          <label class="form-label" for="password">Password</label>
          <div class="login-v3-password-group">
            <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter password">
            <button class="login-v3-password-toggle" id="toggleLoginPassword" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember_30days" name="remember_30days" value="1" <?= checked_if(!empty($_POST['remember_30days'])) ?>>
            <label class="form-check-label small" for="remember_30days">Trust this device for 30 days</label>
          </div>
          <a class="small text-decoration-none" href="<?= e(url('forgot_password')) ?>">Forgot your password?</a>
        </div>

        <button class="btn btn-primary w-100 login-v3-submit" type="submit">Sign In</button>

        <?php if (google_auth_ready()): ?>
          <a class="btn btn-outline-dark w-100 auth-google-btn" href="<?= e(url('google_start')) ?>">
            <i class="bi bi-google"></i> Continue with Google
          </a>
        <?php endif; ?>
      </form>

      <div class="login-v3-links">
        <a href="<?= e(url('register')) ?>">Don&apos;t have an account? Sign up</a>
      </div>
    </article>
  </div>

  <div class="login-v3-mobile-badge" aria-hidden="true">
    <?php if (logo_path() !== ''): ?>
      <img src="<?= e(logo_path()) ?>" alt="<?= e(brand_name()) ?>" class="login-v3-mobile-logo">
    <?php endif; ?>
  </div>
</section>

<script>
(function () {
  var form = document.getElementById('loginForm');
  var btn = document.getElementById('toggleLoginPassword');
  var input = document.getElementById('password');
  if (form) {
    form.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      var target = event.target;
      if (!target || target.tagName !== 'INPUT') return;
      if (target.type === 'checkbox' || target.type === 'button') return;
      event.preventDefault();
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }
  if (!btn || !input) return;

  btn.addEventListener('click', function () {
    var toText = input.type === 'password';
    input.type = toText ? 'text' : 'password';
    btn.innerHTML = toText ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    btn.setAttribute('aria-label', toText ? 'Hide password' : 'Show password');
    btn.setAttribute('aria-pressed', toText ? 'true' : 'false');
  });
})();
</script>
