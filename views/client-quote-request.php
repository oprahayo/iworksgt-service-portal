<?php
/** Self-service scope-first quotation intake for complex websites, NGO filings
 * and other genuinely non-fixed scope services. No invoice until approved. */
$quoteUser=current_user();
$quoteServiceName=trim((string)($quoteService['name']??'Service'));
$quotePackages=$quoteService['slug']==='website-design-development'
    ? iworks_launch_website_packages(iworks_launch_billing_country($quoteUser))
    : ($quoteService['slug']==='incorporated-trustees'
        ? ['ngo'=>[
            'name'=>'NGO / Incorporated Trustees',
            'description'=>'Review constitution, trustee details, filing and publication requirements.',
            'amount'=>iworks_launch_registration_options(iworks_launch_billing_country($quoteUser))['ngo']['price'],
            'currency'=>iworks_launch_account_currency($quoteUser),
            'pages'=>'Specialist review',
        ]]
        : []);
?>
<div class="iw-client-heading">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All services</a>
    <span class="iw-client-eyebrow">CUSTOM PROJECT · NO PAYMENT YET</span>
    <h1>Tell us what you need</h1>
    <p>Select your preferred package, describe the project and submit your request. A quote will be confirmed before any invoice is issued.</p>
  </div>
</div>
<section class="card" style="max-width:850px">
  <div class="card-body p-4">
    <h2 class="h5 mb-3"><?= e($quoteServiceName) ?></h2>
    <form method="post" action="<?= e(url('quote_request',['service'=>(string)$quoteService['slug']])) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="submit_quote">
      <input type="hidden" name="service_slug" value="<?= e((string)$quoteService['slug']) ?>">
      <?php if ($quotePackages !== []): ?>
      <fieldset class="mb-4">
        <legend class="fw-semibold fs-6">Choose a project package</legend>
        <div class="row g-2">
          <?php foreach($quotePackages as $key=>$package): ?>
            <div class="col-sm-6">
              <label class="border rounded-3 p-3 h-100 d-flex gap-2 align-items-start" style="cursor:pointer">
                <input type="radio" name="package" value="<?= e($key) ?>" required
                  <?= $key===($quotePackageSelection?:array_key_first($quotePackages))?'checked':'' ?>>
                <span><strong><?= e((string)($package['name']??$package['title']??'Custom service')) ?></strong>
                  <small class="d-block text-muted"><?= e((string)$package['description']) ?></small>
                  <small class="d-block mt-2 fw-semibold"><?= (float)$package['amount']>0
                      ? 'Indicative from '.e(money((float)$package['amount'],(string)$package['currency']))
                      : 'Quoted individually' ?></small>
                </span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <?php else: ?>
        <input type="hidden" name="package" value="custom">
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="quoteCompany">Company or project name (optional)</label>
        <input class="form-control" name="company" id="quoteCompany" maxlength="160" autocomplete="organization">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="quoteNeeds">What are you looking to build or register? *</label>
        <textarea class="form-control" id="quoteNeeds" name="requirements" required minlength="15" maxlength="3000" rows="4"
          placeholder="Briefly explain what you need, key functions, and any important context."></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="quoteTime">Preferred timing (optional)</label>
        <select class="form-select" id="quoteTime" name="timeline">
          <option value="">No deadline specified</option>
          <option value="within-month">Within one month</option>
          <option value="one-to-three">Within 1–3 months</option>
          <option value="flexible">Flexible</option>
        </select>
      </div>
      <p class="small text-muted">Prices shown are indicative, not government-approved charges or a payable invoice. Account billing country: <?= e(iworks_launch_billing_country($quoteUser)) ?>; quotation currency: <?= e(iworks_launch_account_currency($quoteUser)) ?>. The support team will respond through your account and email when notifications are enabled.</p>
      <button class="btn btn-primary" type="submit">Submit quotation request <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </form>
  </div>
</section>
