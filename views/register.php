<?php
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$oldCountryValue = (string) old_input('country', 'Nigeria');
$oldCountryIso = portal_country_iso($oldCountryValue);
$oldStateValue = (string) old_input('state_region');
?>
<div class="row justify-content-center">
  <div class="col-xl-10">
    <div class="card">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Create client account</h1>
        <p class="text-muted small mb-3">Complete your biodata. A 6-digit email verification code will be sent after signup.</p>
        <form method="post" action="<?= e(url('register')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label" for="first_name">First name *</label>
              <input class="form-control" id="first_name" name="first_name" required value="<?= e(old_input('first_name')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="last_name">Last name *</label>
              <input class="form-control" id="last_name" name="last_name" required value="<?= e(old_input('last_name')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="gender">Gender</label>
              <select class="form-select" id="gender" name="gender">
                <option value="">Select</option>
                <?php foreach (gender_options() as $genderCode => $genderLabel): ?>
                  <option value="<?= e($genderCode) ?>" <?= selected_if($genderCode, old_input('gender')) ?>><?= e($genderLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="date_of_birth">Date of birth</label>
              <input class="form-control" id="date_of_birth" name="date_of_birth" type="date" value="<?= e(old_input('date_of_birth')) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label" for="email">Email *</label>
              <input class="form-control" id="email" name="email" type="email" required value="<?= e(old_input('email')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="phone">Phone *</label>
              <input class="form-control js-intl-phone" id="phone" name="phone" required value="<?= e(old_input('phone')) ?>" data-country-select="#country">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="occupation">Occupation</label>
              <input class="form-control" id="occupation" name="occupation" value="<?= e(old_input('occupation')) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label" for="country">Country *</label>
              <select class="form-select" id="country" name="country" required>
                <?php foreach ($countryOptions as $countryIso => $countryName): ?>
                  <option
                    value="<?= e($countryName) ?>"
                    data-iso="<?= e(strtolower($countryIso)) ?>"
                    <?= selected_if($countryName, $oldCountryValue) ?>
                  ><?= e($countryName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="state_region">State/Region *</label>
              <select class="form-select" id="state_region" name="state_region" required data-selected="<?= e($oldStateValue) ?>"></select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="city">City *</label>
              <input class="form-control" id="city" name="city" required value="<?= e(old_input('city')) ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="address_line">Address *</label>
              <input class="form-control" id="address_line" name="address_line" required value="<?= e(old_input('address_line')) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="password">Password *</label>
              <div class="input-group">
                <input class="form-control" id="password" name="password" type="password" minlength="6" required>
                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password" aria-label="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="confirm_password">Confirm password *</label>
              <div class="input-group">
                <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" required>
                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="confirm_password" aria-label="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-12 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_30days" name="remember_30days" value="1" <?= checked_if(!empty($_POST['remember_30days'])) ?>>
                <label class="form-check-label" for="remember_30days">Trust this device for 30 days after verification</label>
              </div>
            </div>
          </div>
          <button class="btn btn-primary mt-3" type="submit">Create account</button>
        </form>
        <p class="text-muted small mt-3 mb-0">Already registered? <a href="<?= e(url('login')) ?>">Login</a></p>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var stateMap = <?= json_encode($stateMap, JSON_UNESCAPED_SLASHES) ?>;
  var countrySelect = document.getElementById('country');
  var stateSelect = document.getElementById('state_region');

  function toIso(countryName) {
    if (!countrySelect) return 'ng';
    var found = Array.prototype.find.call(countrySelect.options, function (option) {
      return option.value === countryName;
    });
    return found ? (found.getAttribute('data-iso') || 'ng') : 'ng';
  }

  function buildStates() {
    if (!countrySelect || !stateSelect) return;
    var selectedOption = countrySelect.options[countrySelect.selectedIndex];
    var iso = selectedOption ? (selectedOption.getAttribute('data-iso') || 'ng') : 'ng';
    var states = stateMap[String(iso).toUpperCase()] || [];
    var current = stateSelect.getAttribute('data-selected') || stateSelect.value || '';

    stateSelect.innerHTML = '';
    if (!states.length) {
      var fallbackOption = document.createElement('option');
      fallbackOption.value = '';
      fallbackOption.textContent = 'Select state/region';
      stateSelect.appendChild(fallbackOption);
      return;
    }

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select state/region';
    stateSelect.appendChild(placeholder);

    states.forEach(function (stateName) {
      var option = document.createElement('option');
      option.value = stateName;
      option.textContent = stateName;
      if (current === stateName) {
        option.selected = true;
      }
      stateSelect.appendChild(option);
    });
  }

  buildStates();
  if (countrySelect) {
    countrySelect.addEventListener('change', buildStates);
  }

  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var input = targetId ? document.getElementById(targetId) : null;
      if (!input) return;
      var toText = input.type === 'password';
      input.type = toText ? 'text' : 'password';
      btn.innerHTML = toText ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
  });

  if (typeof window.intlTelInput === 'function') {
    document.querySelectorAll('.js-intl-phone').forEach(function (input) {
      var countrySelector = document.querySelector(input.getAttribute('data-country-select') || '');
      var selectedCountry = countrySelector && countrySelector.value ? countrySelector.value : 'Nigeria';
      var initialIso = toIso(selectedCountry);

      var iti = window.intlTelInput(input, {
        initialCountry: initialIso || 'ng',
        separateDialCode: true,
        nationalMode: false,
        autoPlaceholder: 'polite',
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.6.0/build/js/utils.js',
      });

      if (countrySelector) {
        countrySelector.addEventListener('change', function () {
          var option = countrySelector.options[countrySelector.selectedIndex];
          var iso = option ? (option.getAttribute('data-iso') || 'ng') : 'ng';
          iti.setCountry(iso);
        });

        input.addEventListener('countrychange', function () {
          var data = iti.getSelectedCountryData();
          if (!data || !data.iso2) return;
          var match = Array.prototype.find.call(countrySelector.options, function (option) {
            return (option.getAttribute('data-iso') || '').toLowerCase() === String(data.iso2).toLowerCase();
          });
          if (match) {
            countrySelector.value = match.value;
            buildStates();
          }
        });
      }
    });
  }
})();
</script>
