<?php
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$oldCountryValue = (string) old_input('country', 'Nigeria');
$oldStateValue = (string) old_input('state_region');
?>
<section class="iw-auth-panel iw-auth-register" aria-labelledby="iwRegisterTitle">
  <div class="iw-auth-header">
    <?php if (logo_path() !== ''): ?><img src="<?= e(logo_path()) ?>" alt="<?= e(brand_name()) ?>" class="iw-auth-logo"><?php endif; ?>
    <h1 id="iwRegisterTitle">Create an account</h1>
    <p>One account to order services, share documents and track your work.</p>
  </div>
  <form class="iw-auth-form" method="post" action="<?= e(url('register')) ?>" target="_top">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="iw-auth-fields">
      <div><label for="iwFirstName">First name *</label><input id="iwFirstName" name="first_name" autocomplete="given-name" value="<?= e(old_input('first_name')) ?>" required maxlength="100"></div>
      <div><label for="iwLastName">Last name *</label><input id="iwLastName" name="last_name" autocomplete="family-name" value="<?= e(old_input('last_name')) ?>" required maxlength="100"></div>
      <div class="iw-auth-full"><label for="iwRegisterEmail">Email *</label><input id="iwRegisterEmail" type="email" name="email" autocomplete="email" value="<?= e(old_input('email')) ?>" required maxlength="190"></div>
      <div class="iw-auth-full"><label for="iwPhone">Phone *</label><input id="iwPhone" type="tel" name="phone" autocomplete="tel" value="<?= e(old_input('phone')) ?>" required placeholder="+234..." maxlength="30"></div>
      <div><label for="iwCountry">Country *</label><select id="iwCountry" name="country" required>
        <?php foreach ($countryOptions as $iso => $country): ?><option value="<?= e($country) ?>" data-iso="<?= e((string) $iso) ?>" <?= selected_if($country, $oldCountryValue) ?>><?= e($country) ?></option><?php endforeach; ?>
      </select></div>
      <div><label for="iwState">State / region *</label><select id="iwState" name="state_region" data-previous="<?= e($oldStateValue) ?>" required></select></div>
      <div><label for="iwCity">City *</label><input id="iwCity" name="city" value="<?= e(old_input('city')) ?>" autocomplete="address-level2" required></div>
      <div><label for="iwAddress">Street address *</label><input id="iwAddress" name="address_line" value="<?= e(old_input('address_line')) ?>" autocomplete="street-address" required></div>
      <div><label for="iwPassword">Password *</label><input id="iwPassword" type="password" name="password" autocomplete="new-password" minlength="6" required></div>
      <div><label for="iwConfirmPassword">Confirm password *</label><input id="iwConfirmPassword" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required></div>
    </div>
    <p class="iw-auth-small">A verification code will be sent to your email if email verification is enabled.</p>
    <button class="iw-auth-submit" type="submit">Create account <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
  </form>
  <p class="iw-auth-switch">Already registered? <a href="<?= e(url('login', !empty($_GET['embed']) ? ['embed'=>'1'] : [])) ?>">Sign in</a></p>
</section>
<script>
(function () {
  var countries = <?= json_encode($stateMap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var country = document.getElementById('iwCountry');
  var state = document.getElementById('iwState');
  if (!country || !state) return;
  function setStates() {
    var option = country.options[country.selectedIndex];
    var iso = (option ? option.getAttribute('data-iso') : '') || 'NG';
    var available = countries[String(iso).toUpperCase()] || [];
    var previous = state.getAttribute('data-previous') || state.value || '';
    state.textContent = '';
    var prompt = document.createElement('option');
    prompt.value = '';
    prompt.textContent = 'Select state / region';
    state.appendChild(prompt);
    if (!available.length) {
      var custom = document.createElement('option');
      custom.value = 'Other';
      custom.textContent = 'Other / not listed';
      state.appendChild(custom);
    }
    available.forEach(function (name) {
      var entry = document.createElement('option');
      entry.value = name;
      entry.textContent = name;
      entry.selected = name === previous;
      state.appendChild(entry);
    });
  }
  setStates();
  country.addEventListener('change', function () {
    state.setAttribute('data-previous', '');
    setStates();
  });
})();
</script>