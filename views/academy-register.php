<?php if (empty($academyGuestRegistration)) { http_response_code(403); exit; } ?>
<section class="iw-auth-panel iw-auth-register" aria-labelledby="iwRegisterTitle">
  <a class="iw-auth-home" href="<?= e(public_url('home')) ?>" aria-label="Back to iWorks homepage"><i class="bi bi-house-door" aria-hidden="true"></i><span>Home</span></a>
  <div class="iw-auth-header">
    <h1 id="iwRegisterTitle">Create your student account</h1>
    <p>Apply for training, track your courses and request your certificate.</p>
    <?php if ($selectedCourse): ?><p><strong><?= e($selectedCourse['title']) ?></strong><br>Verify your account, then confirm your application.</p><?php endif; ?>
  </div>
  <?php if (google_auth_ready()): ?>
    <a class="iw-auth-google iw-auth-register-google" href="<?= e(portal_url('google_start',['academy'=>'1'])) ?>"><i class="bi bi-google" aria-hidden="true"></i> Continue with Google</a>
    <p class="iw-auth-small" style="text-align:center;margin:.8rem 0">Or register with your email</p>
  <?php endif; ?>
  <form class="iw-auth-form" method="post" action="/academy.php?action=register">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="iw-auth-fields">
      <div class="iw-auth-full"><label for="iwAcademyName">Full name *</label><input id="iwAcademyName" name="name" autocomplete="name" maxlength="150" required value="<?= e((string)old_input('name')) ?>"></div>
      <div class="iw-auth-full"><label for="iwAcademyEmail">Email *</label><input id="iwAcademyEmail" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= e((string)old_input('email')) ?>"></div>
      <div class="iw-auth-full"><label for="iwAcademyPhone">Phone *</label><input id="iwAcademyPhone" name="phone" type="tel" autocomplete="tel" maxlength="40" required placeholder="+234..." value="<?= e((string)old_input('phone')) ?>"></div>
      <div><label for="iwAcademyPassword">Password *</label><input id="iwAcademyPassword" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div>
      <div><label for="iwAcademyConfirm">Confirm password *</label><input id="iwAcademyConfirm" name="confirm_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div>
    </div>
    <p class="iw-auth-small">Use at least 12 characters. We will verify your email before you access the Academy.</p>
    <p class="iw-auth-small">By creating an account, you agree to the <a href="<?= e(public_url('terms')) ?>" target="_blank" rel="noopener">Terms &amp; Conditions</a> and acknowledge our <a href="<?= e(public_url('privacy')) ?>" target="_blank" rel="noopener">Privacy Policy</a>.</p>
    <button class="iw-auth-submit" type="submit">Create student account <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
  </form>
  <p class="iw-auth-switch">Already registered? <a href="<?= e(portal_url('login')) ?>">Sign in</a></p>
  <p class="iw-auth-switch"><a href="<?= e(public_url('service',['slug'=>'pro-training'])) ?>">Explore training courses</a></p>
</section>
