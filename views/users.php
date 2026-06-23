<?php
$countryOptions = portal_country_options();
$stateMap = portal_state_options_map();
$serviceCatalogOptions = is_array($serviceOptions ?? null) ? $serviceOptions : [];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Manage Users</h1>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">Add user</button>
    <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="send_reset_links_all">
      <input type="hidden" name="user_id" value="0">
      <button class="btn btn-sm btn-outline-secondary" type="submit">Send Reset Link To All</button>
    </form>
    <div class="btn-group" role="group" aria-label="Role filter">
      <a class="btn btn-sm <?= $roleFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('users', ['role' => 'all'])) ?>">All</a>
      <a class="btn btn-sm <?= $roleFilter === 'client' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('users', ['role' => 'client'])) ?>">Clients</a>
      <a class="btn btn-sm <?= $roleFilter === 'agent' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('users', ['role' => 'agent'])) ?>">Agents</a>
    </div>
  </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="h5 mb-0">Add user account</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="create_user">
        <input type="hidden" name="user_id" value="0">
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Role to add</label>
              <select class="form-select" name="role" id="createUserRole" required>
                <option value="client">Client</option>
                <option value="agent">Agent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Temporary password</label>
              <div class="input-group">
                <input class="form-control" name="temporary_password" id="temporaryPasswordField" minlength="8" required>
                <button class="btn btn-outline-secondary" type="button" id="generateTempPasswordBtn">Generate</button>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label d-block">Force first login update</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="force_first_login_update" id="forceFirstLoginUpdate">
                <label class="form-check-label" for="forceFirstLoginUpdate">Require password reset on first login</label>
              </div>
            </div>

            <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" required></div>
            <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control js-intl-phone" name="phone" data-country-select="#newUserCountry"></div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender">
                <option value="">Select</option>
                <?php foreach (gender_options() as $genderCode => $genderLabel): ?>
                  <option value="<?= e($genderCode) ?>"><?= e($genderLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth"></div>
            <div class="col-md-4"><label class="form-label">Occupation</label><input class="form-control" name="occupation"></div>
            <div class="col-md-4">
              <label class="form-label">Country</label>
              <select class="form-select user-country-select" name="country" id="newUserCountry" data-user-country="1" data-state-target="#newUserState">
                <?php foreach ($countryOptions as $countryIso => $countryName): ?>
                  <option value="<?= e($countryName) ?>" data-iso="<?= e(strtolower($countryIso)) ?>" <?= selected_if($countryName, 'Nigeria') ?>><?= e($countryName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">State/Region</label><select class="form-select" name="state_region" id="newUserState"></select></div>
            <div class="col-md-4"><label class="form-label">City</label><input class="form-control" name="city"></div>
            <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address_line"></div>

            <div class="col-12 d-none" id="newUserAgentBankWrap">
              <div class="border rounded p-2">
                <div class="fw-semibold mb-2">Agent bank details</div>
                <div class="row g-2">
                  <div class="col-md-6"><label class="form-label">Bank name</label><input class="form-control" name="bank_name"></div>
                  <div class="col-md-6"><label class="form-label">Account name</label><input class="form-control" name="bank_account_name"></div>
                  <div class="col-md-4"><label class="form-label">Account number</label><input class="form-control" name="bank_account_number"></div>
                  <div class="col-md-4"><label class="form-label">SWIFT / Sort code</label><input class="form-control" name="bank_swift"></div>
                  <div class="col-md-4"><label class="form-label">Bank currency</label><input class="form-control" name="bank_currency" value="<?= e(default_currency()) ?>"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit">Create user</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-mobile-cards table-sm align-middle mb-0">
      <thead>
      <tr>
        <th>User</th>
        <th>Role</th>
        <th>Status</th>
        <th>Contact</th>
        <th>Joined</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $managed): ?>
        <tr>
          <td data-label="User">
            <div class="fw-semibold d-flex align-items-center gap-2">
              <span class="online-dot <?= is_user_online((string) ($managed['last_seen_at'] ?? '')) ? 'online' : 'offline' ?>"></span>
              <?= e($managed['full_name']) ?>
            </div>
            <small class="text-muted"><?= e($managed['email']) ?></small>
          </td>
          <td data-label="Role"><?= e(role_label((string) $managed['role'])) ?></td>
          <td data-label="Status"><?= status_badge((string) ($managed['account_state'] ?? 'active')) ?></td>
          <td data-label="Contact">
            <div><?= e((string) ($managed['phone'] ?: '-')) ?></div>
            <small class="text-muted"><?= e(trim((string) (($managed['country'] ?? '') . ' ' . ($managed['city'] ?? ''))) ?: '-') ?></small>
          </td>
          <td data-label="Joined"><small><?= e((string) $managed['created_at']) ?></small></td>
          <td data-label="Action" class="text-end cell-actions">
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#userManageModal<?= (int) $managed['id'] ?>">Manage</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?>
        <tr class="table-empty-row"><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach ($users as $managed): ?>
  <?php $modalId = 'userManageModal' . (int) $managed['id']; ?>
  <div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="h5 mb-0">Manage <?= e($managed['full_name']) ?></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="userEditForm<?= (int) $managed['id'] ?>" method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="save_user">
            <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">

            <div class="col-md-6"><label class="form-label">Full name</label><input class="form-control form-control-sm" name="full_name" value="<?= e($managed['full_name']) ?>" required></div>
            <div class="col-md-3"><label class="form-label">First name</label><input class="form-control form-control-sm" name="first_name" value="<?= e($managed['first_name'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Last name</label><input class="form-control form-control-sm" name="last_name" value="<?= e($managed['last_name'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control form-control-sm" type="email" name="email" value="<?= e($managed['email']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control form-control-sm js-intl-phone" name="phone" value="<?= e($managed['phone'] ?? '') ?>" data-country-select="#userCountry<?= (int) $managed['id'] ?>"></div>
            <div class="col-md-4">
              <label class="form-label">Gender</label>
              <select class="form-select form-select-sm" name="gender">
                <option value="">Select</option>
                <?php foreach (gender_options() as $genderCode => $genderLabel): ?>
                  <option value="<?= e($genderCode) ?>" <?= selected_if($genderCode, (string) ($managed['gender'] ?? '')) ?>><?= e($genderLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Date of birth</label><input class="form-control form-control-sm" type="date" name="date_of_birth" value="<?= e((string) ($managed['date_of_birth'] ?? '')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Occupation</label><input class="form-control form-control-sm" name="occupation" value="<?= e($managed['occupation'] ?? '') ?>"></div>
            <div class="col-md-4">
              <label class="form-label">Country</label>
              <select
                class="form-select form-select-sm user-country-select"
                name="country"
                id="userCountry<?= (int) $managed['id'] ?>"
                data-user-country="1"
                data-state-target="#userState<?= (int) $managed['id'] ?>"
              >
                <?php foreach ($countryOptions as $countryIso => $countryName): ?>
                  <option value="<?= e($countryName) ?>" data-iso="<?= e(strtolower($countryIso)) ?>" <?= selected_if($countryName, (string) ($managed['country'] ?? '')) ?>><?= e($countryName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">State/Region</label>
              <select
                class="form-select form-select-sm"
                name="state_region"
                id="userState<?= (int) $managed['id'] ?>"
                data-selected="<?= e((string) ($managed['state_region'] ?? '')) ?>"
              ></select>
            </div>
            <div class="col-md-4"><label class="form-label">City</label><input class="form-control form-control-sm" name="city" value="<?= e($managed['city'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Address</label><input class="form-control form-control-sm" name="address_line" value="<?= e($managed['address_line'] ?? '') ?>"></div>

            <?php if ((string) $managed['role'] === 'agent'): ?>
              <div class="col-12"><hr class="my-2"></div>
              <div class="col-md-6"><label class="form-label">Bank name</label><input class="form-control form-control-sm" name="bank_name" value="<?= e($managed['bank_name'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label">Account name</label><input class="form-control form-control-sm" name="bank_account_name" value="<?= e($managed['bank_account_name'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label">Account number</label><input class="form-control form-control-sm" name="bank_account_number" value="<?= e($managed['bank_account_number'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label">SWIFT / sort code</label><input class="form-control form-control-sm" name="bank_swift" value="<?= e($managed['bank_swift'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label">Bank currency</label><input class="form-control form-control-sm" name="bank_currency" value="<?= e($managed['bank_currency'] ?? default_currency()) ?>"></div>
            <?php endif; ?>
          </form>

          <hr class="my-3">

          <div class="row g-2">
            <div class="col-lg-6">
              <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="set_user_state">
                <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">
                <div class="col-8">
                  <label class="form-label">Account state</label>
                  <select class="form-select form-select-sm" name="account_state" required>
                    <?php foreach (['active', 'suspended', 'blocked'] as $state): ?>
                      <option value="<?= e($state) ?>" <?= selected_if($state, (string) ($managed['account_state'] ?? 'active')) ?>><?= e(ucwords($state)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-4 d-grid">
                  <button class="btn btn-sm btn-outline-warning mt-4" type="submit">Apply</button>
                </div>
              </form>
            </div>
            <div class="col-lg-6 d-flex align-items-end gap-2 flex-wrap">
              <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="send_reset_link">
                <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Send Reset Link</button>
              </form>
              <?php if ((string) ($managed['role'] ?? '') === 'agent'): ?>
                <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="open_agent_chat">
                  <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">
                  <button class="btn btn-sm btn-outline-primary" type="submit">Direct Message</button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>" onsubmit="return confirm('Delete this account? This will deactivate login.');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete Account</button>
              </form>
            </div>
          </div>

          <?php if ((string) ($managed['role'] ?? '') === 'client'): ?>
            <hr class="my-3">
            <div class="border rounded p-3 bg-body-tertiary">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">Add custom service order</h3>
                <small class="text-muted">Auto creates invoice in client account</small>
              </div>
              <form method="post" action="<?= e(url('users', ['role' => $roleFilter])) ?>" class="row g-2 js-custom-service-order-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="add_custom_service_order">
                <input type="hidden" name="user_id" value="<?= (int) $managed['id'] ?>">

                <div class="col-md-6">
                  <label class="form-label">Service (optional)</label>
                  <select class="form-select form-select-sm js-custom-service-select" name="service_id">
                    <option value="">Use custom service name</option>
                    <?php foreach ($serviceCatalogOptions as $serviceOption): ?>
                      <option
                        value="<?= (int) $serviceOption['id'] ?>"
                        data-price="<?= e((string) ($serviceOption['price'] ?? '')) ?>"
                        data-currency="<?= e((string) ($serviceOption['currency'] ?? default_currency())) ?>"
                        data-tax="<?= e((string) ($serviceOption['tax_percent'] ?? '0')) ?>"
                      >
                        <?= e((string) $serviceOption['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted d-block mt-1">Select from catalog or leave empty and use custom service name.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Custom service name</label>
                  <input class="form-control form-control-sm js-custom-service-name" name="custom_service_name" placeholder="e.g. Urgent CAC amendment">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Custom amount</label>
                  <input class="form-control form-control-sm js-custom-amount" type="number" min="0.01" step="0.01" name="custom_amount" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Pay currency</label>
                  <select class="form-select form-select-sm js-custom-currency" name="pay_currency">
                    <?php foreach (payment_currency_options() as $currencyOption): ?>
                      <option value="<?= e($currencyOption) ?>"><?= e($currencyOption) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tax (%)</label>
                  <input class="form-control form-control-sm js-custom-tax" type="number" min="0" max="100" step="0.01" name="tax_percent" placeholder="Use service default">
                </div>
                <div class="col-md-5">
                  <label class="form-label">Tax handling</label>
                  <select class="form-select form-select-sm" name="tax_mode">
                    <option value="exclusive">Customer pays tax on top</option>
                    <option value="inclusive">Remove tax from amount (included in amount)</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="form-label">Order title (optional)</label>
                  <input class="form-control form-control-sm" name="title" placeholder="Defaults to service name">
                </div>
                <div class="col-12">
                  <label class="form-label">Description / requirements (optional)</label>
                  <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Add custom job notes for the client and assigned agent."></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button class="btn btn-sm btn-primary" type="submit">Create custom invoice</button>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit" form="userEditForm<?= (int) $managed['id'] ?>">Save profile changes</button>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script>
(function () {
  var stateMap = <?= json_encode($stateMap, JSON_UNESCAPED_SLASHES) ?>;

  function populateStates(countrySelect) {
    var targetSelector = countrySelect.getAttribute('data-state-target') || '';
    var stateSelect = targetSelector ? document.querySelector(targetSelector) : null;
    if (!stateSelect) return;

    var option = countrySelect.options[countrySelect.selectedIndex];
    var iso = option ? (option.getAttribute('data-iso') || 'ng') : 'ng';
    var states = stateMap[String(iso).toUpperCase()] || [];
    var selected = stateSelect.getAttribute('data-selected') || stateSelect.value || '';

    stateSelect.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select state/region';
    stateSelect.appendChild(placeholder);

    states.forEach(function (stateName) {
      var stateOption = document.createElement('option');
      stateOption.value = stateName;
      stateOption.textContent = stateName;
      if (selected === stateName) {
        stateOption.selected = true;
      }
      stateSelect.appendChild(stateOption);
    });
  }

  document.querySelectorAll('[data-user-country="1"]').forEach(function (countrySelect) {
    populateStates(countrySelect);
    countrySelect.addEventListener('change', function () {
      populateStates(countrySelect);
    });
  });

  if (typeof window.intlTelInput === 'function') {
    document.querySelectorAll('.js-intl-phone').forEach(function (input) {
      var countrySelector = document.querySelector(input.getAttribute('data-country-select') || '');
      var iso = 'ng';
      if (countrySelector) {
        var selectedOption = countrySelector.options[countrySelector.selectedIndex];
        iso = selectedOption ? (selectedOption.getAttribute('data-iso') || 'ng') : 'ng';
      }

      var iti = window.intlTelInput(input, {
        initialCountry: iso,
        separateDialCode: true,
        nationalMode: false,
        autoPlaceholder: 'polite',
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.6.0/build/js/utils.js',
      });

      if (countrySelector) {
        countrySelector.addEventListener('change', function () {
          var selectedOption = countrySelector.options[countrySelector.selectedIndex];
          var selectedIso = selectedOption ? (selectedOption.getAttribute('data-iso') || 'ng') : 'ng';
          iti.setCountry(selectedIso);
          populateStates(countrySelector);
        });

        input.addEventListener('countrychange', function () {
          var data = iti.getSelectedCountryData();
          if (!data || !data.iso2) return;
          var match = Array.prototype.find.call(countrySelector.options, function (optionRow) {
            return (optionRow.getAttribute('data-iso') || '').toLowerCase() === String(data.iso2).toLowerCase();
          });
          if (match) {
            countrySelector.value = match.value;
            populateStates(countrySelector);
          }
        });
      }
    });
  }

  var roleSelect = document.getElementById('createUserRole');
  var bankWrap = document.getElementById('newUserAgentBankWrap');
  function syncCreateRole() {
    if (!roleSelect || !bankWrap) return;
    var isAgent = roleSelect.value === 'agent';
    bankWrap.classList.toggle('d-none', !isAgent);
  }
  if (roleSelect) {
    roleSelect.addEventListener('change', syncCreateRole);
    syncCreateRole();
  }

  var generateBtn = document.getElementById('generateTempPasswordBtn');
  var tempPasswordField = document.getElementById('temporaryPasswordField');
  if (generateBtn && tempPasswordField) {
    generateBtn.addEventListener('click', function () {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%';
      var out = '';
      for (var i = 0; i < 12; i += 1) {
        out += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      tempPasswordField.value = out;
    });
  }

  document.querySelectorAll('.js-custom-service-order-form').forEach(function (form) {
    var serviceSelect = form.querySelector('.js-custom-service-select');
    var customServiceNameInput = form.querySelector('.js-custom-service-name');
    var amountInput = form.querySelector('.js-custom-amount');
    var currencySelect = form.querySelector('.js-custom-currency');
    var taxInput = form.querySelector('.js-custom-tax');

    if (!serviceSelect) {
      return;
    }

    function syncCustomServiceRequirement() {
      if (!customServiceNameInput) return;
      var usingCatalog = serviceSelect.value !== '';
      customServiceNameInput.required = !usingCatalog;
      if (usingCatalog) {
        customServiceNameInput.removeAttribute('aria-invalid');
      }
    }

    function setDefaults(force) {
      var selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
      if (!selectedOption || !selectedOption.value) {
        syncCustomServiceRequirement();
        return;
      }

      var priceValue = selectedOption.getAttribute('data-price') || '';
      var currencyValue = (selectedOption.getAttribute('data-currency') || '').toUpperCase();
      var taxValue = selectedOption.getAttribute('data-tax') || '';

      if (amountInput && (force || amountInput.value.trim() === '')) {
        if (priceValue !== '' && !Number.isNaN(Number(priceValue))) {
          amountInput.value = Number(priceValue).toFixed(2);
        }
      }

      if (currencySelect && currencyValue !== '') {
        var match = Array.prototype.find.call(currencySelect.options, function (optionItem) {
          return optionItem.value === currencyValue;
        });
        if (match) {
          currencySelect.value = currencyValue;
        }
      }

      if (taxInput && (force || taxInput.value.trim() === '')) {
        taxInput.value = taxValue;
      }

      syncCustomServiceRequirement();
    }

    serviceSelect.addEventListener('change', function () {
      setDefaults(true);
    });

    if (customServiceNameInput) {
      form.addEventListener('submit', function (event) {
        syncCustomServiceRequirement();
        if (customServiceNameInput.required && customServiceNameInput.value.trim() === '') {
          customServiceNameInput.setAttribute('aria-invalid', 'true');
          customServiceNameInput.focus();
          event.preventDefault();
        }
      });
      customServiceNameInput.addEventListener('input', function () {
        if (customServiceNameInput.value.trim() !== '') {
          customServiceNameInput.removeAttribute('aria-invalid');
        }
      });
    }

    setDefaults(false);
    syncCustomServiceRequirement();
  });
})();
</script>
