<?php
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$oldCountryValue = (string) old_input('country', 'Nigeria');
$oldStateValue = (string) old_input('state_region');
?>
<section class="iw-auth-panel iw-auth-register" aria-labelledby="iwRegisterTitle">
  <div class="iw-auth-header">
    <h1 id="iwRegisterTitle">Create an account</h1>
    <p>One account to order services, share documents and track your work.</p>
  </div>
  <form class="iw-auth-form" id="iwRegisterForm" method="post" action="<?= e(url('register')) ?>" target="_top">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <p class="iw-register-progress" id="iwRegisterProgress" aria-live="polite">Your details</p>

    <fieldset class="iw-auth-step" data-register-step="1">
      <legend class="iw-auth-step-title">Your account details</legend>
      <div class="iw-auth-fields">
        <div><label for="iwFirstName">First name *</label><input id="iwFirstName" name="first_name" autocomplete="given-name" value="<?= e(old_input('first_name')) ?>" required maxlength="100"></div>
        <div><label for="iwLastName">Last name *</label><input id="iwLastName" name="last_name" autocomplete="family-name" value="<?= e(old_input('last_name')) ?>" required maxlength="100"></div>
        <div class="iw-auth-full"><label for="iwRegisterEmail">Email *</label><input id="iwRegisterEmail" type="email" name="email" autocomplete="email" value="<?= e(old_input('email')) ?>" required maxlength="190"></div>
        <div class="iw-auth-full"><label for="iwPhone">Phone *</label><input id="iwPhone" type="tel" name="phone" autocomplete="tel" value="<?= e(old_input('phone')) ?>" required maxlength="30" placeholder="+234..."></div>
        <div><label for="iwPassword">Password *</label><input id="iwPassword" type="password" name="password" autocomplete="new-password" minlength="6" required></div>
        <div><label for="iwConfirmPassword">Confirm password *</label><input id="iwConfirmPassword" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required></div>
      </div>
    </fieldset>

    <fieldset class="iw-auth-step" data-register-step="2">
      <legend class="iw-auth-step-title">Location and contact</legend>
      <div class="iw-auth-fields">
        <div><label for="iwCountry">Country *</label><select id="iwCountry" name="country" required>
          <?php foreach ($countryOptions as $iso => $country): ?><option value="<?= e($country) ?>" data-iso="<?= e((string) $iso) ?>" <?= selected_if($country, $oldCountryValue) ?>><?= e($country) ?></option><?php endforeach; ?>
        </select></div>
        <div><label for="iwState">State / region *</label><select id="iwState" name="state_region" data-previous="<?= e($oldStateValue) ?>" required></select></div>
        <div><label for="iwCity">City *</label><input id="iwCity" name="city" value="<?= e(old_input('city')) ?>" autocomplete="address-level2" required></div>
        <div><label for="iwAddress">Street address *</label><input id="iwAddress" name="address_line" value="<?= e(old_input('address_line')) ?>" autocomplete="street-address" required></div>
      </div>
    </fieldset>
    <p class="iw-auth-small">Your email may need a verification code before the account is ready.</p>
    <div class="iw-register-controls">
      <button class="iw-auth-back" id="iwRegisterBack" type="button" hidden><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</button>
      <button class="iw-auth-submit iw-auth-next" id="iwRegisterNext" type="button" hidden>Continue <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      <button class="iw-auth-submit iw-auth-register-submit" id="iwRegisterSubmit" type="submit">Create account <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
  </form>
  <p class="iw-auth-switch">Already registered? <a href="<?= e(url('login', !empty($_GET['embed']) ? ['embed'=>'1'] : [])) ?>"<?= !empty($_GET['embed']) ? ' data-iw-auth-switch="login"' : '' ?>>Sign in</a></p>
</section>
<script>
(function () {
  'use strict';
  var countries = <?= json_encode($stateMap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var form = document.getElementById('iwRegisterForm');
  var country = document.getElementById('iwCountry');
  var state = document.getElementById('iwState');
  function setStates() {
    if (!country || !state) return;
    var selected = country.options[country.selectedIndex];
    var iso = (selected && selected.getAttribute('data-iso')) || 'NG';
    var options = countries[String(iso).toUpperCase()] || [];
    var previous = state.getAttribute('data-previous') || state.value || '';
    state.textContent = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select state or region';
    state.appendChild(placeholder);
    if (!options.length) options = ['Other'];
    options.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === previous;
      state.appendChild(option);
    });
  }
  setStates();
  if (country) country.addEventListener('change', function () {
    state.setAttribute('data-previous', '');
    setStates();
  });

  var panel = document.querySelector('.iw-auth-register');
  var first = form && form.querySelector('[data-register-step="1"]');
  var second = form && form.querySelector('[data-register-step="2"]');
  var next = document.getElementById('iwRegisterNext');
  var back = document.getElementById('iwRegisterBack');
  var submit = document.getElementById('iwRegisterSubmit');
  var progress = document.getElementById('iwRegisterProgress');
  var password = document.getElementById('iwPassword');
  var confirm = document.getElementById('iwConfirmPassword');

  function passwordsMatch() {
    if (!confirm || !password) return true;
    var matches = confirm.value === password.value;
    confirm.setCustomValidity(matches ? '' : 'Passwords must match.');
    return matches;
  }
  if (confirm) confirm.addEventListener('input', passwordsMatch);
  if (password) password.addEventListener('input', passwordsMatch);

  if (panel && first && second && next && back && submit && progress) {
    panel.classList.add('js-stepped');
    function showStep(step) {
      first.hidden = step !== 1;
      second.hidden = step !== 2;
      next.hidden = step !== 1;
      back.hidden = step !== 2;
      submit.hidden = step !== 2;
      progress.textContent = 'Step ' + step + ' of 2';
    }
    next.addEventListener('click', function () {
      passwordsMatch();
      var fields = first.querySelectorAll('input, select');
      for (var i = 0; i < fields.length; i++) {
        if (!fields[i].checkValidity()) {
          fields[i].reportValidity();
          return;
        }
      }
      showStep(2);
    });
    back.addEventListener('click', function () { showStep(1); });
    form.addEventListener('submit', function (event) {
      passwordsMatch();
      if (!form.checkValidity()) {
        event.preventDefault();
        if (!first.checkValidity()) showStep(1);
        form.reportValidity();
      }
    });
    showStep(1);
  }
  document.querySelectorAll('[data-iw-auth-switch]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      if (window.parent === window || !document.referrer) return;
      try {
        var targetOrigin = new URL(document.referrer).origin;
        event.preventDefault();
        window.parent.postMessage({ iwAuthSwitch: 'login' }, targetOrigin);
      } catch (err) { /* Use regular navigation as fallback. */ }
    });
  });
})();
</script>