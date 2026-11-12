<?php
$user = current_user();
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$country = (string) ($user['country'] ?? 'Nigeria');
$state = (string) ($user['state_region'] ?? '');
$forcePasswordResetMode = (int) ($user['must_change_password'] ?? 0) === 1 || !empty($_GET['force_password']);
$activePayCurrency = active_pay_currency();
?>
<div class="iw-client-heading">
  <div><span class="iw-client-eyebrow">ACCOUNT SETTINGS</span><h1>My profile</h1>
    <p>Keep your contact details up to date for service applications and updates.</p></div>
</div>
<?php if ($forcePasswordResetMode): ?>
  <div class="alert alert-warning">Please set a new password to secure this account.</div>
<?php endif; ?>
<div class="iw-client-profile-grid">
  <div class="iw-client-profile-primary">
    <section class="iw-tm-panel">
      <div class="iw-client-detail-panel-title"><h2>Personal information</h2><span class="iw-client-small-muted">Only visible to you and authorised iWorks staff.</span></div>
      <form method="post" action="<?= e(url('profile')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="update_profile">
        <div class="iw-tm-fields">
          <div class="iw-tm-field iw-tm-field-full"><label for="cpFull">Full legal name <span>*</span></label>
            <input id="cpFull" type="text" name="full_name" value="<?= e((string) ($user['full_name'] ?? '')) ?>" required></div>
          <div class="iw-tm-field"><label for="cpFirst">First name</label>
            <input id="cpFirst" name="first_name" value="<?= e((string) ($user['first_name'] ?? '')) ?>"></div>
          <div class="iw-tm-field"><label for="cpLast">Last name</label>
            <input id="cpLast" name="last_name" value="<?= e((string) ($user['last_name'] ?? '')) ?>"></div>
          <div class="iw-tm-field"><label for="cpEmail">Email address</label>
            <input id="cpEmail" type="email" readonly value="<?= e((string) ($user['email'] ?? '')) ?>"><small>Your sign-in email is managed separately.</small></div>
          <div class="iw-tm-field"><label for="cpPhone">Phone number</label>
            <input id="cpPhone" name="phone" type="tel" value="<?= e((string) ($user['phone'] ?? '')) ?>"></div>
          <div class="iw-tm-field"><label for="cpGender">Gender (optional)</label>
            <select id="cpGender" name="gender"><option value="">Prefer not to say</option>
            <?php foreach (gender_options() as $code=>$label): ?>
              <option value="<?= e($code) ?>" <?= selected_if($code, (string) ($user['gender'] ?? '')) ?>><?= e($label) ?></option>
            <?php endforeach; ?></select></div>
          <div class="iw-tm-field"><label for="cpDob">Date of birth (optional)</label>
            <input id="cpDob" type="date" name="date_of_birth" value="<?= e((string) ($user['date_of_birth'] ?? '')) ?>"></div>
        </div>
        <div class="iw-client-profile-divider"></div>
        <h3>Contact address</h3>
        <div class="iw-tm-fields">
          <div class="iw-tm-field"><label for="cpCountry">Country</label>
            <select id="cpCountry" name="country">
              <?php foreach ($countryOptions as $iso=>$name): ?>
              <option value="<?= e($name) ?>" data-iso="<?= e(strtoupper((string) $iso)) ?>" <?= selected_if($name, $country) ?>><?= e($name) ?></option>
              <?php endforeach; ?></select></div>
          <div class="iw-tm-field"><label for="cpState">State / region</label>
            <select id="cpState" name="state_region" data-selected="<?= e($state) ?>"></select></div>
          <div class="iw-tm-field"><label for="cpCity">City</label>
            <input id="cpCity" name="city" value="<?= e((string) ($user['city'] ?? '')) ?>"></div>
          <div class="iw-tm-field"><label for="cpOccupation">Occupation (optional)</label>
            <input id="cpOccupation" name="occupation" value="<?= e((string) ($user['occupation'] ?? '')) ?>"></div>
          <div class="iw-tm-field iw-tm-field-full"><label for="cpAddress">Street address</label>
            <input id="cpAddress" name="address_line" value="<?= e((string) ($user['address_line'] ?? '')) ?>"></div>
        </div>
        <div class="iw-tm-form-actions"><span class="iw-client-small-muted">Changes will apply to future applications.</span>
          <button type="submit" class="iw-client-button">Save changes <i class="bi bi-check2"></i></button></div>
      </form>
    </section>
  </div>
  <aside class="iw-client-profile-side">
    <section class="iw-tm-panel">
      <div class="iw-client-detail-panel-title"><h2>Payment preference</h2></div>
      <p class="iw-client-small-muted">Select the currency you prefer when starting an order. Invoices already issued keep their original currency.</p>
      <form method="post" action="<?= e(url('profile')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="update_pay_currency">
        <div class="iw-tm-field"><label for="cpCurrency">Preferred currency</label><select name="pay_currency" id="cpCurrency">
          <?php foreach (payment_currency_options() as $currencyCode): ?>
            <option value="<?= e($currencyCode) ?>" <?= selected_if($currencyCode, $activePayCurrency) ?>><?= e($currencyCode) ?></option>
          <?php endforeach; ?></select></div>
        <button class="iw-client-button iw-client-button-soft" type="submit">Save preference</button>
      </form>
      <a href="<?= e(url('card_vault')) ?>" class="iw-client-account-link"><i class="bi bi-credit-card"></i> Manage payment methods <i class="bi bi-arrow-right"></i></a>
    </section>
    <section class="iw-tm-panel">
      <div class="iw-client-detail-panel-title"><h2><?= $forcePasswordResetMode?'Set a password':'Password & security' ?></h2></div>
      <form method="post" action="<?= e(url('profile')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="update_password">
        <?php if (!$forcePasswordResetMode): ?>
        <div class="iw-tm-field"><label for="cpCurrent">Current password</label>
          <input id="cpCurrent" type="password" name="current_password" autocomplete="current-password" required></div>
        <?php endif; ?>
        <div class="iw-tm-field"><label for="cpNew">New password</label>
          <input id="cpNew" type="password" name="new_password" autocomplete="new-password" minlength="6" required></div>
        <div class="iw-tm-field"><label for="cpConfirm">Confirm new password</label>
          <input id="cpConfirm" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required></div>
        <button type="submit" class="iw-client-button iw-client-button-soft"><?= $forcePasswordResetMode?'Set password':'Update password' ?></button>
      </form>
      <p class="iw-client-small-muted">Signed in with Google and don't have a password? Use the password recovery flow from the sign-in page.</p>
    </section>
  </aside>
</div>
<script>
(function(){
  const map=<?= json_encode($stateMap, JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  const country=document.getElementById('cpCountry'),region=document.getElementById('cpState');
  function states(){
    const option=country.options[country.selectedIndex];
    const code=(option?option.dataset.iso:'NG')||'NG';
    const current=region.dataset.selected||region.value||'';
    const choices=Array.isArray(map[code])?map[code]:[];
    region.replaceChildren(new Option('Select state / region',''));
    choices.forEach(function(name){region.add(new Option(name,name,name===current,name===current));});
    if(current&&!choices.includes(current))region.add(new Option(current,current,true,true));
    region.dataset.selected='';
  }
  country.addEventListener('change',function(){region.dataset.selected='';region.value='';states()});
  states();
})();
</script>
