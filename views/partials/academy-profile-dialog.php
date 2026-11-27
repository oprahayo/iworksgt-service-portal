<?php
declare(strict_types=1);
// Included only for the current authenticated student from the portal header.
if(($user['role']??'')==='student'): ?>
<dialog class="ac-profile-dialog" id="ac-profile-dialog" aria-labelledby="ac-profile-dialog-title">
  <div class="ac-profile-dialog-head"><div><h2 id="ac-profile-dialog-title">My profile</h2><p>Manage your personal information.</p></div><button type="button" data-profile-close aria-label="Close profile"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
  <form method="post" action="/academy.php?action=profile" data-profile-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Verified email<input type="email" value="<?= e((string)$user['email']) ?>" readonly></label>
    <label>Full name<input name="name" autocomplete="name" maxlength="150" required value="<?= e((string)$user['full_name']) ?>"></label>
    <label>Phone number<input type="tel" name="phone" autocomplete="tel" minlength="7" maxlength="40" required value="<?= e((string)($user['phone']??'')) ?>"></label>
    <p class="ac-profile-note">Profile updates do not change previously approved certificate names.</p>
    <p role="status" data-profile-status></p>
    <div class="ac-profile-dialog-actions"><button type="submit" class="btn btn-primary">Save profile</button><a href="/logout.php" class="btn btn-outline-secondary">Logout</a></div>
  </form>
</dialog>
<script src="/assets/js/academy-profile.js?v=1" defer></script>
<?php endif; ?>
