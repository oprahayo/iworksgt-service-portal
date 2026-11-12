<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Portal Settings</h1>
</div>

<?php $partnerLogos = website_partner_logos(); ?>

<div class="accordion settings-accordion" id="settingsAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#settingBrand" aria-expanded="true" aria-controls="settingBrand">
        Brand & Theme
      </button>
    </h2>
    <div id="settingBrand" class="accordion-collapse collapse show" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <p class="small text-muted mb-2">Default palette uses Orange + Gray + Black (Primary: <code>#FF6600</code>).</p>
        <form method="post" action="<?= e(url('settings')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_brand">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Brand name</label>
              <input class="form-control" name="brand_name" value="<?= e(brand_name()) ?>" placeholder="iWORKS GLOBAL">
            </div>
            <div class="col-md-6">
              <label class="form-label">Website title</label>
              <input class="form-control" name="app_name" value="<?= e(app_name()) ?>" placeholder="iWorks Global Business Support Portal">
            </div>
            <div class="col-md-6">
              <label class="form-label">Company legal name</label>
              <input class="form-control" name="company_legal_name" value="<?= e(company_legal_name()) ?>" placeholder="iWorks Global Limited">
            </div>
            <div class="col-md-6">
              <label class="form-label">Footer text</label>
              <input class="form-control" name="brand_footer_text" value="<?= e(footer_text()) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Business support summary</label>
              <textarea class="form-control" rows="2" name="business_support_summary" placeholder="Business support tools to help entrepreneurs scale their business with our services."><?= e(business_support_summary()) ?></textarea>
              <small class="text-muted">This summary appears across portal identity content and SEO defaults.</small>
            </div>
            <div class="col-md-4"><label class="form-label">Primary color</label><input class="form-control form-control-color w-100" type="color" name="brand_primary" value="<?= e(brand_primary_color()) ?>"></div>
            <div class="col-md-4"><label class="form-label">Primary dark</label><input class="form-control form-control-color w-100" type="color" name="brand_primary_dark" value="<?= e(brand_primary_dark_color()) ?>"></div>
            <div class="col-md-4"><label class="form-label">Accent color</label><input class="form-control form-control-color w-100" type="color" name="brand_accent" value="<?= e(brand_accent_color()) ?>"></div>
            <div class="col-md-4">
              <label class="form-label">Default currency</label>
              <select class="form-select" name="default_currency">
                <?php foreach (currency_options() as $currency): ?>
                  <option value="<?= e($currency) ?>" <?= selected_if($currency, default_currency()) ?>><?= e($currency) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">NGN per 1 USD</label>
              <input class="form-control" type="number" min="1" step="0.01" name="fx_ngn_per_usd" value="<?= e((string) fx_ngn_per_usd()) ?>">
            </div>
          </div>
          <button class="btn btn-primary mt-3" type="submit">Save brand settings</button>
        </form>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingLogo" aria-expanded="false" aria-controls="settingLogo">
        Logo & Favicon
      </button>
    </h2>
    <div id="settingLogo" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <p class="small text-muted mb-2">When logo is uploaded, text title is hidden in sidebar.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <?php if (logo_path() !== ''): ?><img src="<?= e(logo_path()) ?>" alt="Logo" class="settings-logo-preview mb-2"><?php endif; ?>
            <form method="post" action="<?= e(url('settings')) ?>" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="upload_logo">
              <label class="form-label">Upload logo</label>
              <input class="form-control" type="file" name="logo_file" required>
              <button class="btn btn-outline-primary mt-2" type="submit">Upload logo</button>
            </form>
          </div>
          <div class="col-md-6">
            <?php if (favicon_path() !== ''): ?><div class="mb-2"><img src="<?= e(favicon_path()) ?>" alt="Favicon" style="width:32px;height:32px;border-radius:6px;border:1px solid #e2e8f0;padding:4px;"></div><?php endif; ?>
            <form method="post" action="<?= e(url('settings')) ?>" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="upload_favicon">
              <label class="form-label">Upload favicon</label>
              <input class="form-control" type="file" name="favicon_file" required>
              <button class="btn btn-outline-primary mt-2" type="submit">Upload favicon</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingRules" aria-expanded="false" aria-controls="settingRules">
        Business Rules
      </button>
    </h2>
    <div id="settingRules" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_rules">
          <div class="col-md-3"><label class="form-label">Withdrawal minimum</label><input class="form-control" type="number" name="withdrawal_minimum" min="1" step="0.01" value="<?= e(setting('withdrawal_minimum', '20000')) ?>"></div>
          <div class="col-md-3"><label class="form-label">Withdrawal cooldown (days)</label><input class="form-control" type="number" name="withdrawal_cooldown_days" min="1" step="1" value="<?= e(setting('withdrawal_cooldown_days', '14')) ?>"></div>
          <div class="col-md-3"><label class="form-label">Crypto refresh (minutes)</label><input class="form-control" type="number" name="crypto_rates_refresh_minutes" min="5" max="30" step="1" value="<?= e(setting('crypto_rates_refresh_minutes', '10')) ?>"></div>
          <div class="col-md-3"><label class="form-label" for="trademarkPriceUsd">Trademark fee (USD / class)</label><input id="trademarkPriceUsd" class="form-control" type="number" name="trademark_price_usd_per_class" min="1" max="100000" step="0.01" required value="<?= e(number_format(trademark_price_usd(), 2, '.', '')) ?>"><small class="text-muted">Default $185.00. Applies to new, unsubmitted trademark quotes.</small></div>
          <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">Save rules</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingPartnerLogos" aria-expanded="false" aria-controls="settingPartnerLogos">
        Partner Logos
      </button>
    </h2>
    <div id="settingPartnerLogos" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <p class="small text-muted mb-2">Upload partner or client brand logos for the public trust strip. Frontend output is automatically converted to grayscale.</p>
        <form method="post" action="<?= e(url('settings')) ?>" enctype="multipart/form-data" class="border rounded p-3 mb-3 settings-method-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="upload_partner_logos">
          <div class="row g-2">
            <div class="col-md-9">
              <label class="form-label">Upload logos</label>
              <input class="form-control" type="file" name="partner_logo_files[]" accept="image/*" multiple required>
              <small class="text-muted">Use transparent PNG, JPG, WEBP, or GIF where possible for clean grayscale output.</small>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button class="btn btn-outline-primary w-100" type="submit">Upload logos</button>
            </div>
          </div>
        </form>

        <div class="row g-3">
          <?php foreach ($partnerLogos as $logo): ?>
            <?php $asset = !empty($logo['imageId']) ? get_media_asset((int) $logo['imageId']) : null; ?>
            <?php if (!$asset || empty($asset['url'])) { continue; } ?>
            <div class="col-sm-6 col-lg-4">
              <div class="partner-logo-settings-card">
                <div class="partner-logo-settings-preview">
                  <img src="<?= e((string) $asset['url']) ?>" alt="<?= e((string) ($logo['title'] ?? 'Partner logo')) ?>">
                </div>
                <div class="small text-muted text-truncate"><?= e((string) ($logo['title'] ?? 'Partner logo')) ?></div>
                <form method="post" action="<?= e(url('settings')) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="remove_partner_logo">
                  <input type="hidden" name="media_id" value="<?= (int) ($logo['imageId'] ?? 0) ?>">
                  <button class="btn btn-sm btn-outline-danger w-100 mt-2" type="submit">Remove</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$partnerLogos): ?>
            <div class="col-12">
              <div class="text-muted small border rounded p-3">No partner logos uploaded yet. Once uploaded here, they automatically replace the text placeholders in the public trust strip.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingEmail" aria-expanded="false" aria-controls="settingEmail">
        Email Notifications & OTP
      </button>
    </h2>
    <div id="settingEmail" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_mail">
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="mail_enabled" name="mail_enabled" value="1" <?= checked_if(email_enabled()) ?>>
              <label class="form-check-label" for="mail_enabled">Enable outgoing email</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled" value="1" <?= checked_if(email_notifications_enabled()) ?>>
              <label class="form-check-label" for="email_notifications_enabled">Email activity notifications</label>
            </div>
          </div>
          <div class="col-md-4"><label class="form-label">OTP expiry (minutes)</label><input class="form-control" type="number" name="otp_expiry_minutes" min="1" step="1" value="<?= e(setting('otp_expiry_minutes', '10')) ?>"></div>
          <div class="col-md-6"><label class="form-label">From name</label><input class="form-control" name="mail_from_name" value="<?= e(setting('mail_from_name', app_name())) ?>"></div>
          <div class="col-md-6"><label class="form-label">From email</label><input class="form-control" type="email" name="mail_from_email" value="<?= e(setting('mail_from_email', 'no-reply@iworksgt.com')) ?>"></div>
          <div class="col-12"><button class="btn btn-outline-primary" type="submit">Save email settings</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingGoogle" aria-expanded="false" aria-controls="settingGoogle">
        Google Sign-In
      </button>
    </h2>
    <div id="settingGoogle" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_google_auth">
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="google_auth_enabled" name="google_auth_enabled" value="1" <?= checked_if(google_auth_enabled()) ?>>
              <label class="form-check-label" for="google_auth_enabled">Enable Google login</label>
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label">Authorized redirect URI (copy exactly into Google Cloud)</label>
            <input class="form-control" value="<?= e(google_redirect_uri()) ?>" readonly aria-label="Google callback URL">
          </div>
          <div class="col-12">
            <?php $googleIssues = google_oauth_redirect_problems(); ?>
            <?php if ($googleIssues !== []): ?>
              <div class="alert alert-warning small" role="alert">
                <strong>Google callback needs attention:</strong>
                <?= e(implode(' ', $googleIssues)) ?>
              </div>
            <?php elseif (!google_auth_ready()): ?>
              <div class="alert alert-info small" role="status">Google sign-in is not ready. Enable it and save a Web application Client ID and Client Secret.</div>
            <?php else: ?>
              <p class="small text-success mb-1">Local Google OAuth settings are present and the callback format is valid. Google Cloud authorization must still match.</p>
            <?php endif; ?>
            <p class="small text-muted mb-2">In <a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">Google Auth Platform → Clients</a>, select your Web application OAuth client and add the exact URI above under <strong>Authorized redirect URIs</strong>. For an app in Testing, add permitted test users under Audience, or publish it when appropriate. Save changes there before testing the sign-in button.</p>
          </div>
          <div class="col-md-6"><label class="form-label">Google Client ID</label><input class="form-control" name="google_client_id" value="<?= e(google_client_id()) ?>" placeholder="xxxxxxxx.apps.googleusercontent.com"></div>
          <div class="col-md-6"><label class="form-label">Google Client Secret</label><input class="form-control" name="google_client_secret" type="password" value="" autocomplete="new-password" placeholder="<?= google_client_secret() !== '' ? 'Saved — leave blank to keep it' : 'Paste client secret' ?>"></div>
          <div class="col-12"><label class="form-label">Redirect URL override (optional)</label><input class="form-control" name="google_redirect_uri" value="<?= e(google_redirect_uri_override()) ?>" placeholder="Leave blank to use the generated redirect URL"></div>
          <div class="col-12"><button class="btn btn-outline-primary" type="submit">Save Google settings</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingPayments" aria-expanded="false" aria-controls="settingPayments">
        Payment Methods
      </button>
    </h2>
    <div id="settingPayments" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <p class="small text-muted mb-2">Currency rules: <strong>NGN</strong> => Paystack/Flutterwave/Bank Transfer/Crypto. <strong>USD</strong> => Stripe/Crypto.</p>
        <?php foreach ($paymentMethods as $method): ?>
          <?php
            $methodCode = (string) ($method['method_code'] ?? '');
            $showGatewayKeys = in_array($methodCode, ['stripe', 'paystack', 'flutterwave'], true);
            $forceProofUpload = in_array($methodCode, ['crypto_wallet', 'bank_transfer'], true);
            $webhookEndpoint = $showGatewayKeys ? portal_absolute_url(url('payment_webhook', ['provider' => $methodCode])) : '';
          ?>
          <form method="post" action="<?= e(url('settings')) ?>" class="border rounded p-3 mb-3 settings-method-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="save_payment_method">
            <input type="hidden" name="method_id" value="<?= (int) $method['id'] ?>">
            <div class="row g-2">
              <div class="col-md-8"><label class="form-label">Method name</label><input class="form-control" name="display_name" value="<?= e($method['display_name']) ?>"></div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mb-2 me-3">
                  <input class="form-check-input" type="checkbox" name="is_active" id="methodActive<?= (int) $method['id'] ?>" <?= checked_if((int) $method['is_active'] === 1) ?>>
                  <label class="form-check-label" for="methodActive<?= (int) $method['id'] ?>">Active</label>
                </div>
                <?php if ($showGatewayKeys): ?>
                  <input type="hidden" name="requires_proof" value="0">
                <?php elseif ($forceProofUpload): ?>
                  <input type="hidden" name="requires_proof" value="1">
                <?php else: ?>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="requires_proof" id="methodProof<?= (int) $method['id'] ?>" <?= checked_if((int) ($method['requires_proof'] ?? 1) === 1) ?>>
                    <label class="form-check-label" for="methodProof<?= (int) $method['id'] ?>">Proof required</label>
                  </div>
                <?php endif; ?>
              </div>
              <div class="col-12"><label class="form-label">Payment details/instructions</label><textarea class="form-control" rows="2" name="details"><?= e($method['details'] ?? '') ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Post-success redirect URL</label><input class="form-control" name="callback_url" value="<?= e($method['callback_url'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label">Gateway webhook URL</label><input class="form-control" name="webhook_url" value="<?= e($method['webhook_url'] ?? '') ?>" placeholder="<?= $showGatewayKeys ? e($webhookEndpoint) : '' ?>"></div>
              <?php if ($showGatewayKeys): ?>
                <div class="col-md-4"><label class="form-label">Live Public Key</label><input class="form-control" name="public_key" value="<?= e($method['public_key'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Live Secret Key</label><input class="form-control" name="secret_key" value="<?= e($method['secret_key'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Private/Extra Key</label><input class="form-control" name="private_key" value="<?= e($method['private_key'] ?? '') ?>"></div>
                <div class="col-12 small text-muted">System webhook endpoint: <code><?= e($webhookEndpoint) ?></code></div>
              <?php else: ?>
                <input type="hidden" name="public_key" value="<?= e($method['public_key'] ?? '') ?>">
                <input type="hidden" name="secret_key" value="<?= e($method['secret_key'] ?? '') ?>">
                <input type="hidden" name="private_key" value="<?= e($method['private_key'] ?? '') ?>">
              <?php endif; ?>
              <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Save method</button></div>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingAi" aria-expanded="false" aria-controls="settingAi">
        AI Form Builder
      </button>
    </h2>
    <div id="settingAi" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_ai">
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="ai_enabled" id="ai_enabled" value="1" <?= checked_if(ai_enabled()) ?>>
              <label class="form-check-label" for="ai_enabled">Enable AI generation</label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Provider</label>
            <select class="form-select" name="ai_provider">
              <option value="openai" <?= selected_if('openai', ai_provider()) ?>>OpenAI</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Model</label>
            <input class="form-control" name="ai_model" value="<?= e(ai_model()) ?>" placeholder="gpt-4o-mini">
          </div>
          <div class="col-md-3">
            <label class="form-label">API key</label>
            <input class="form-control" name="ai_api_key" value="<?= e(ai_api_key()) ?>" placeholder="sk-...">
          </div>
          <div class="col-12">
            <small class="text-muted">This key powers Admin &gt; Forms AI schema generation and field suggestions.</small>
          </div>
          <div class="col-12"><button class="btn btn-outline-primary" type="submit">Save AI settings</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingBanners" aria-expanded="false" aria-controls="settingBanners">
        Dashboard Banners
      </button>
    </h2>
    <div id="settingBanners" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <p class="small text-muted">Upload both versions: Desktop 1600x500 and Mobile 1080x1350.</p>
        <form method="post" action="<?= e(url('settings')) ?>" enctype="multipart/form-data" class="border rounded p-3 mb-3">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_banner">
          <?php if ($editBanner): ?><input type="hidden" name="banner_id" value="<?= (int) $editBanner['id'] ?>"><?php endif; ?>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" required value="<?= e($editBanner['title'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Link URL</label><input class="form-control" name="link_url" value="<?= e($editBanner['link_url'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= e((string) ($editBanner['sort_order'] ?? 0)) ?>"></div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="bannerActive" <?= checked_if((bool) ($editBanner['is_active'] ?? true)) ?>>
                <label class="form-check-label" for="bannerActive">Active</label>
              </div>
            </div>
            <div class="col-md-3"><label class="form-label">Desktop image</label><input class="form-control" type="file" name="banner_image_desktop" <?= $editBanner ? '' : 'required' ?>></div>
            <div class="col-md-3"><label class="form-label">Mobile image</label><input class="form-control" type="file" name="banner_image_mobile" <?= $editBanner ? '' : 'required' ?>></div>
            <div class="col-12 d-flex gap-2">
              <button class="btn btn-sm btn-outline-primary" type="submit"><?= $editBanner ? 'Update banner' : 'Add banner' ?></button>
              <?php if ($editBanner): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('settings')) ?>">Cancel edit</a><?php endif; ?>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-mobile-cards table-sm align-middle mb-0">
            <thead><tr><th>Desktop</th><th>Mobile</th><th>Title</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($banners as $banner): ?>
              <tr>
                <td data-label="Desktop"><img src="<?= e($banner['image_path']) ?>" alt="<?= e($banner['title']) ?>" class="banner-thumb"></td>
                <td data-label="Mobile"><?php if (!empty($banner['mobile_image_path'])): ?><img src="<?= e($banner['mobile_image_path']) ?>" alt="<?= e($banner['title']) ?>" class="banner-thumb"><?php else: ?>-<?php endif; ?></td>
                <td data-label="Title"><?= e($banner['title']) ?></td>
                <td data-label="Status"><?= status_badge((int) $banner['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td data-label="Action" class="text-end cell-actions">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('settings', ['action' => 'edit-banner', 'id' => (int) $banner['id']])) ?>">Edit</a>
                  <form method="post" action="<?= e(url('settings')) ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="form_action" value="delete_banner">
                    <input type="hidden" name="banner_id" value="<?= (int) $banner['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$banners): ?><tr class="table-empty-row"><td colspan="5" class="text-muted text-center py-3">No banners uploaded yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingAnnouncements" aria-expanded="false" aria-controls="settingAnnouncements">
        Announcements
      </button>
    </h2>
    <div id="settingAnnouncements" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" enctype="multipart/form-data" class="border rounded p-3 mb-3">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_announcement">
          <?php if ($editAnnouncement): ?><input type="hidden" name="announcement_id" value="<?= (int) $editAnnouncement['id'] ?>"><?php endif; ?>
          <div class="row g-2">
            <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required value="<?= e($editAnnouncement['title'] ?? '') ?>"></div>
            <div class="col-md-6">
              <label class="form-label">Audience</label>
              <select class="form-select" name="audience">
                <?php foreach (['all' => 'All users', 'client' => 'Clients only', 'agent' => 'Agents only'] as $aud => $label): ?>
                  <option value="<?= e($aud) ?>" <?= selected_if($aud, (string) ($editAnnouncement['audience'] ?? 'all')) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Attachment</label><input class="form-control" type="file" name="announcement_attachment"></div>
            <div class="col-12"><label class="form-label">Plain message</label><textarea class="form-control" rows="2" name="message" required><?= e($editAnnouncement['message'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Formatted message (HTML allowed)</label><textarea class="form-control" rows="4" name="message_html"><?php if (!empty($editAnnouncement['message_html'])): ?><?= e($editAnnouncement['message_html']) ?><?php endif; ?></textarea></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="announcementActive" <?= checked_if((bool) ($editAnnouncement['is_active'] ?? true)) ?>><label class="form-check-label" for="announcementActive">Active</label></div></div>
            <div class="col-md-9 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="require_ack" id="announcementRequireAck" <?= checked_if((bool) ($editAnnouncement['require_ack'] ?? true)) ?>><label class="form-check-label" for="announcementRequireAck">Block dashboard until read</label></div></div>
            <div class="col-12 d-flex gap-2">
              <button class="btn btn-sm btn-outline-primary" type="submit"><?= $editAnnouncement ? 'Update announcement' : 'Publish announcement' ?></button>
              <?php if ($editAnnouncement): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('settings')) ?>">Cancel edit</a><?php endif; ?>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-mobile-cards table-sm align-middle mb-0">
            <thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Read lock</th><th>Attachment</th><th>Updated</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($announcements as $announcement): ?>
              <tr>
                <td data-label="Title"><?= e($announcement['title']) ?></td>
                <td data-label="Audience"><?= e(ucfirst((string) ($announcement['audience'] ?? 'all'))) ?></td>
                <td data-label="Status"><?= status_badge((int) $announcement['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td data-label="Read lock"><?= (int) $announcement['require_ack'] === 1 ? 'Yes' : 'No' ?></td>
                <td data-label="Attachment"><?php if (!empty($announcement['attachment_path'])): ?><a class="btn btn-sm btn-outline-secondary" href="download.php?announcement_id=<?= (int) $announcement['id'] ?>">File</a><?php else: ?>-<?php endif; ?></td>
                <td data-label="Updated"><?= e($announcement['updated_at']) ?></td>
                <td data-label="Action" class="text-end cell-actions">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('settings', ['action' => 'edit-announcement', 'id' => (int) $announcement['id']])) ?>">Edit</a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('announcement', ['id' => (int) $announcement['id']])) ?>">Open</a>
                  <form method="post" action="<?= e(url('settings')) ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="form_action" value="delete_announcement">
                    <input type="hidden" name="announcement_id" value="<?= (int) $announcement['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$announcements): ?><tr class="table-empty-row"><td colspan="7" class="text-muted text-center py-3">No announcements published yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#settingSeo" aria-expanded="false" aria-controls="settingSeo">
        SEO & Social Links
      </button>
    </h2>
    <div id="settingSeo" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
      <div class="accordion-body">
        <form method="post" action="<?= e(url('settings')) ?>" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_seo_social">
          <div class="col-12"><label class="form-label">SEO description</label><textarea class="form-control" name="seo_description" rows="2"><?= e(seo_description()) ?></textarea></div>
          <div class="col-12"><label class="form-label">SEO tags</label><input class="form-control" name="seo_tags" value="<?= e(seo_tags()) ?>"></div>
          <div class="col-md-4"><label class="form-label">Facebook URL</label><input class="form-control" name="social_facebook" value="<?= e(setting('social_facebook', '')) ?>"></div>
          <div class="col-md-4"><label class="form-label">Instagram URL</label><input class="form-control" name="social_instagram" value="<?= e(setting('social_instagram', '')) ?>"></div>
          <div class="col-md-4"><label class="form-label">X (Twitter) URL</label><input class="form-control" name="social_x" value="<?= e(setting('social_x', '')) ?>"></div>
          <div class="col-md-4"><label class="form-label">LinkedIn URL</label><input class="form-control" name="social_linkedin" value="<?= e(setting('social_linkedin', '')) ?>"></div>
          <div class="col-md-4"><label class="form-label">TikTok URL</label><input class="form-control" name="social_tiktok" value="<?= e(setting('social_tiktok', '')) ?>"></div>
          <div class="col-md-4"><label class="form-label">YouTube URL</label><input class="form-control" name="social_youtube" value="<?= e(setting('social_youtube', '')) ?>"></div>
          <div class="col-12"><button class="btn btn-outline-primary" type="submit">Save SEO & social settings</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
