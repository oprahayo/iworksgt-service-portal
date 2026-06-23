<?php
$user = current_user();
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$profileCountryValue = (string) ($user['country'] ?? 'Nigeria');
$profileStateValue = (string) ($user['state_region'] ?? '');
$activePayCurrency = active_pay_currency();
$forcePasswordResetMode = (int) ($user['must_change_password'] ?? 0) === 1 || !empty($_GET['force_password']);
?>
<?php if ($forcePasswordResetMode): ?>
  <div class="alert alert-warning">
    Security action required: change your temporary password before using other dashboard features.
  </div>
<?php endif; ?>
<div class="row g-3">
  <div class="col-lg-<?= $user['role'] === 'client' ? '12' : '7' ?>">
    <div class="card">
      <div class="card-body">
        <h1 class="h4">My profile</h1>
        <form method="post" action="<?= e(url('profile')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="update_profile">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?= e($user['full_name']) ?>"></div>
            <div class="col-md-4"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?= e($user['first_name'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?= e($user['last_name'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control js-intl-phone" name="phone" value="<?= e($user['phone'] ?? '') ?>" data-country-select="#profileCountry"></div>
            <div class="col-md-4"><label class="form-label">Gender</label>
              <select class="form-select" name="gender">
                <option value="">Select</option>
                <?php foreach (gender_options() as $genderCode => $genderLabel): ?>
                  <option value="<?= e($genderCode) ?>" <?= selected_if($genderCode, (string) ($user['gender'] ?? '')) ?>><?= e($genderLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="<?= e((string) ($user['date_of_birth'] ?? '')) ?>"></div>
            <div class="col-md-3">
              <label class="form-label">Country</label>
              <select class="form-select" name="country" id="profileCountry">
                <?php foreach ($countryOptions as $countryIso => $countryName): ?>
                  <option value="<?= e($countryName) ?>" data-iso="<?= e(strtolower($countryIso)) ?>" <?= selected_if($countryName, $profileCountryValue) ?>><?= e($countryName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">State/Region</label>
              <select class="form-select" name="state_region" id="profileStateRegion" data-selected="<?= e($profileStateValue) ?>"></select>
            </div>
            <div class="col-md-3"><label class="form-label">City</label><input class="form-control" name="city" value="<?= e($user['city'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Occupation</label><input class="form-control" name="occupation" value="<?= e($user['occupation'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Address</label><input class="form-control" name="address_line" value="<?= e($user['address_line'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" disabled value="<?= e($user['email']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Role</label><input class="form-control" disabled value="<?= e(role_label($user['role'])) ?>"></div>
          </div>
          <button class="btn btn-primary mt-3" type="submit">Save profile</button>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <h2 class="h5"><?= $forcePasswordResetMode ? 'Set new password' : 'Change password' ?></h2>
        <?php if ($forcePasswordResetMode): ?>
          <p class="small text-muted mb-3">Current password is not required for this first-login security update.</p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('profile')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="update_password">
          <div class="row g-3">
            <?php if (!$forcePasswordResetMode): ?>
              <div class="col-md-4">
                <label class="form-label">Current password</label>
                <div class="input-group">
                  <input class="form-control" id="profile_current_password" type="password" name="current_password" required>
                  <button class="btn btn-outline-secondary profile-password-toggle" type="button" data-target="profile_current_password" aria-label="Show password">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
            <?php endif; ?>
            <div class="<?= $forcePasswordResetMode ? 'col-md-6' : 'col-md-4' ?>">
              <label class="form-label">New password</label>
              <div class="input-group">
                <input class="form-control" id="profile_new_password" type="password" name="new_password" minlength="6" required>
                <button class="btn btn-outline-secondary profile-password-toggle" type="button" data-target="profile_new_password" aria-label="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="<?= $forcePasswordResetMode ? 'col-md-6' : 'col-md-4' ?>">
              <label class="form-label">Confirm new password</label>
              <div class="input-group">
                <input class="form-control" id="profile_confirm_password" type="password" name="confirm_password" minlength="6" required>
                <button class="btn btn-outline-secondary profile-password-toggle" type="button" data-target="profile_confirm_password" aria-label="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <button class="btn btn-outline-primary mt-3" type="submit"><?= $forcePasswordResetMode ? 'Set password and continue' : 'Update password' ?></button>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <h2 class="h5">Payment preference</h2>
        <form method="post" action="<?= e(url('profile')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="update_pay_currency">
          <div class="row g-3 align-items-end">
            <div class="col-md-6">
              <label class="form-label">Preferred pay currency</label>
              <select class="form-select" name="pay_currency">
                <?php foreach (payment_currency_options() as $currencyCode): ?>
                  <option value="<?= e($currencyCode) ?>" <?= selected_if($currencyCode, $activePayCurrency) ?>><?= e($currencyCode) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <button class="btn btn-outline-primary w-100 w-md-auto" type="submit">Save pay currency</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($user['role'] !== 'client'): ?>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <h2 class="h5">Bank payout details</h2>
        <p class="small text-muted">Used for agent commissions. Finance/Admin can view full details.</p>
        <form method="post" action="<?= e(url('profile')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="update_bank">
          <div class="mb-2"><label class="form-label">Bank name</label><input class="form-control" name="bank_name" value="<?= e($user['bank_name'] ?? '') ?>"></div>
          <div class="mb-2"><label class="form-label">Account name</label><input class="form-control" name="bank_account_name" value="<?= e($user['bank_account_name'] ?? '') ?>"></div>
          <div class="mb-2"><label class="form-label">Account number</label><input class="form-control" name="bank_account_number" value="<?= e($user['bank_account_number'] ?? '') ?>"></div>
          <div class="mb-2"><label class="form-label">SWIFT / sort code</label><input class="form-control" name="bank_swift" value="<?= e($user['bank_swift'] ?? '') ?>"></div>
          <div class="mb-2"><label class="form-label">Currency</label><input class="form-control" name="bank_currency" value="<?= e($user['bank_currency'] ?? default_currency()) ?>"></div>
          <button class="btn btn-outline-primary" type="submit">Save bank details</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
(function () {
  var stateMap = <?= json_encode($stateMap, JSON_UNESCAPED_SLASHES) ?>;
  var countrySelect = document.getElementById('profileCountry');
  var stateSelect = document.getElementById('profileStateRegion');

  function buildStates() {
    if (!countrySelect || !stateSelect) return;
    var selectedOption = countrySelect.options[countrySelect.selectedIndex];
    var iso = selectedOption ? (selectedOption.getAttribute('data-iso') || 'ng') : 'ng';
    var states = stateMap[String(iso).toUpperCase()] || [];
    var selectedState = stateSelect.getAttribute('data-selected') || stateSelect.value || '';

    stateSelect.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select state/region';
    stateSelect.appendChild(placeholder);

    states.forEach(function (stateName) {
      var option = document.createElement('option');
      option.value = stateName;
      option.textContent = stateName;
      if (selectedState === stateName) {
        option.selected = true;
      }
      stateSelect.appendChild(option);
    });
  }

  buildStates();
  if (countrySelect) {
    countrySelect.addEventListener('change', buildStates);
  }

  if (typeof window.intlTelInput === 'function') {
    document.querySelectorAll('.js-intl-phone').forEach(function (input) {
      var countrySelector = document.querySelector(input.getAttribute('data-country-select') || '');
      var initialIso = 'ng';
      if (countrySelector) {
        var option = countrySelector.options[countrySelector.selectedIndex];
        initialIso = option ? (option.getAttribute('data-iso') || 'ng') : 'ng';
      }

      var iti = window.intlTelInput(input, {
        initialCountry: initialIso,
        separateDialCode: true,
        nationalMode: false,
        autoPlaceholder: 'polite',
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.6.0/build/js/utils.js',
      });

      if (countrySelector) {
        countrySelector.addEventListener('change', function () {
          var selectedOption = countrySelector.options[countrySelector.selectedIndex];
          var iso = selectedOption ? (selectedOption.getAttribute('data-iso') || 'ng') : 'ng';
          iti.setCountry(iso);
          buildStates();
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

  document.querySelectorAll('.profile-password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var input = targetId ? document.getElementById(targetId) : null;
      if (!input) return;

      var toText = input.type === 'password';
      input.type = toText ? 'text' : 'password';
      btn.innerHTML = toText ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      btn.setAttribute('aria-label', toText ? 'Hide password' : 'Show password');
    });
  });
})();
</script>
