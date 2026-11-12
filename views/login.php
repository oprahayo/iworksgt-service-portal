<section class="iw-auth-panel" aria-labelledby="iwAuthTitle">
  <div class="iw-auth-header">
    <?php if (logo_path() !== ''): ?><img src="<?= e(logo_path()) ?>" alt="<?= e(brand_name()) ?>" class="iw-auth-logo"><?php endif; ?>
    <h1 id="iwAuthTitle">Sign in</h1>
    <p>Access your orders, messages and documents.</p>
  </div>
  <form class="iw-auth-form" method="post" action="<?= e(url('login')) ?>" target="_top">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div><label for="iwLoginEmail">Email address</label><input id="iwLoginEmail" name="email" type="email" autocomplete="email" value="<?= e(old_input('email')) ?>" placeholder="you@example.com" required></div>
    <div><label for="iwLoginPassword">Password</label><div class="iw-auth-password"><input id="iwLoginPassword" name="password" type="password" autocomplete="current-password" placeholder="Your password" required><button type="button" data-iw-password="iwLoginPassword" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
    <div class="iw-auth-form-options">
      <label class="iw-auth-remember"><input type="checkbox" name="remember_30days" value="1" <?= checked_if(!empty($_POST['remember_30days'])) ?>> Remember this device</label>
      <a href="<?= e(url('forgot_password')) ?>" target="_top">Forgot password?</a>
    </div>
    <button class="iw-auth-submit" type="submit">Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    <?php if (google_auth_ready()): ?><a class="iw-auth-google" href="<?= e(url('google_start')) ?>" target="_top"><i class="bi bi-google" aria-hidden="true"></i> Continue with Google</a><?php endif; ?>
  </form>
  <p class="iw-auth-switch">New to iWorks? <a href="<?= e(url('register', !empty($_GET['embed']) ? ['embed'=>'1'] : [])) ?>"<?= !empty($_GET['embed']) ? ' data-iw-auth-switch="register"' : '' ?>>Create an account</a></p>
</section>
<script>
document.querySelectorAll('[data-iw-password]').forEach(function (button) {
  button.addEventListener('click', function () {
    var input = document.getElementById(button.getAttribute('data-iw-password'));
    if (!input) return;
    var visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    button.innerHTML = visible ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>' : '<i class="bi bi-eye" aria-hidden="true"></i>';
  });
});
document.querySelectorAll('[data-iw-auth-switch]').forEach(function (link) {
  link.addEventListener('click', function (event) {
    if (window.parent === window) return;
    try {
      if (window.parent.location.origin !== window.location.origin) return;
      event.preventDefault();
      window.parent.postMessage({ iwAuthSwitch: link.getAttribute('data-iw-auth-switch') }, window.location.origin);
    } catch (_err) { /* Cross-origin parent: use normal navigation in frame. */ }
  });
});
</script>