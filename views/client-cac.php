<?php
$stage = $cacStage ?? 'details';
$record = $cacApplication ?? null;
$data = is_array($cacData ?? null) ? $cacData : [];
$kind = cac_kind((string) ($data['kind'] ?? ($cacKind ?? 'business_name')));
$people = is_array($data['people'] ?? null) ? $data['people'] : [];
if (!$people) {
    $people = [[
        'full_name'=>(string) ($user['full_name'] ?? ''),
        'email'=>(string) ($user['email'] ?? ''),
        'phone'=>(string) ($user['phone'] ?? ''),
        'nationality'=>'Nigerian',
        'occupation'=>(string) ($user['occupation'] ?? ''),
        'date_of_birth'=>(string) ($user['date_of_birth'] ?? ''),
        'gender'=>(string) ($user['gender'] ?? ''),
        'address'=>(string) ($user['address_line'] ?? ''),
        'city'=>(string) ($user['city'] ?? ''),
        'state'=>(string) ($user['state_region'] ?? ''),
        'role'=>'both',
    ]];
}
$price = cac_quote($kind, $data+['people'=>$people], (string) ($data['currency'] ?? 'NGN'));
$uploaded = [];
foreach ($cacFiles ?? [] as $f) { $uploaded[(string) $f['field_key']] = $f; }
$labels = ['business_name'=>'Business Name Registration','company'=>'Private Limited Company'];
$postedKind = $kind;
$hasRecord = (int) ($record['id'] ?? 0) > 0;
?>
<div class="iw-client-heading iw-client-heading-row">
  <div><a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left"></i> Services</a>
    <span class="iw-client-eyebrow">CORPORATE AFFAIRS COMMISSION · NIGERIA</span>
    <h1><?= e($labels[$kind]) ?></h1>
    <p>Save your details, upload documents, review your quote and pay securely.</p>
  </div>
  <span class="iw-tm-ref"><?= $hasRecord ? 'CAC-'.(int) $record['id'] : 'New application' ?></span>
</div>
<div class="iw-cac-layout">
<aside class="iw-cac-sidebar">
  <ol class="iw-tm-steps">
    <?php foreach (['details'=>'Business details','review'=>'Review & confirm','payment'=>'Payment'] as $i=>$label): ?>
      <li class="<?= $stage===$i?'active':($stage==='payment'||($stage==='review'&&$i==='details')?'done':'pending') ?>">
        <span><?= ['details'=>1,'review'=>2,'payment'=>3][$i] ?></span><div><strong><?= e($label) ?></strong></div></li>
    <?php endforeach; ?>
  </ol>
  <div class="iw-tm-price-box" id="cacPriceBox">
    <span>Estimated service fee</span>
    <strong id="cacTotalLabel"><?= $price['total']>0 ? e(money($price['total'],$price['currency'])) : 'Quote pending' ?></strong>
    <div class="iw-tm-summary-row"><small>Base package</small><b id="cacBaseLabel"><?= e(money($price['base_ngn'],'NGN')) ?></b></div>
    <div class="iw-tm-summary-row"><small>Additional people</small><b id="cacExtraLabel"><?= e(money($price['additional_ngn'],'NGN')) ?></b></div>
    <p><?= $kind==='business_name' ? '₦26,000 base + ₦15,000 for each additional partner (admin adjustable).' : 'Director, shareholder and incorporation charges are configured by iWorks. Regulatory stamp duties may require a separate confirmation.' ?></p>
  </div>
</aside>

<div class="iw-cac-content">
<?php if ($stage==='payment' && $record): ?>
  <div class="iw-tm-panel">
    <div class="iw-tm-panel-head"><span class="iw-tm-section-number"><i class="bi bi-check2-circle"></i></span>
      <div><h2>Application submitted for checkout</h2><p>We have saved your intake documents and created an invoice. Processing starts after payment verification.</p></div></div>
    <div class="iw-cac-review-line"><span>Registration</span><strong><?= e($labels[$kind]) ?></strong></div>
    <div class="iw-cac-review-line"><span>Proposed name</span><strong><?= e((string) ($data['name1'] ?? '')) ?></strong></div>
    <div class="iw-cac-review-line"><span>Amount</span><strong><?= e(money((float) ($record['total_amount'] ?? 0),(string) ($record['quote_currency'] ?? 'NGN'))) ?></strong></div>
    <div class="iw-cac-review-actions">
      <a class="iw-client-button" href="<?= e('invoice.php?id=' . (int) ($record['invoice_id'] ?? 0)) ?>"><i class="bi bi-credit-card"></i> Open invoice & pay</a>
      <a class="iw-client-button iw-client-button-soft" href="<?= e(url('applications')) ?>">My services</a>
    </div>
  </div>
<?php elseif ($stage==='review' && $record): ?>
  <section class="iw-tm-panel">
    <div class="iw-tm-panel-head"><span class="iw-tm-section-number"><i class="bi bi-clipboard-check"></i></span>
      <div><h2>Review your application</h2><p>Payment follows only after you confirm the information below.</p></div></div>
    <div class="iw-cac-review-line"><span>Proposed names</span><strong><?= e((string) ($data['name1'] ?? '')) ?><?= !empty($data['name2'])?' / '.e((string) $data['name2']):'' ?></strong></div>
    <div class="iw-cac-review-line"><span>Registration type</span><strong><?= e($labels[$kind]) ?><?= $kind==='business_name'?' · '.ucfirst((string) ($data['structure'] ?? 'sole')):'' ?></strong></div>
    <div class="iw-cac-review-line"><span>Business address</span><strong><?= e((string) ($data['business_address'] ?? '')) ?>, <?= e((string) ($data['business_city'] ?? '')) ?>, <?= e((string) ($data['business_state'] ?? '')) ?></strong></div>
    <div class="iw-cac-review-line"><span>Applicants</span><strong><?= count($people) ?> · <?= e(implode(', ',array_column($people,'full_name'))) ?></strong></div>
    <div class="iw-cac-review-line"><span>Documents verified by portal</span><strong><?= count($uploaded) ?> securely attached</strong></div>
    <div class="iw-cac-review-line"><span>Price</span><strong><?= e(money($price['total'],$price['currency'])) ?></strong></div>
    <p class="iw-client-small-muted">Name reservation and CAC approval are subject to the authority's checks. This quote is for the selected iWorks filing service; special statutory requirements may be separately confirmed before filing.</p>
    <div class="iw-cac-review-actions">
      <a class="iw-client-button iw-client-button-soft" href="<?= e(url('cac',['id'=>(int) $record['id']])) ?>"><i class="bi bi-pencil"></i> Edit details</a>
      <form action="<?= e(url('cac',['id'=>(int) $record['id']])) ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="submit_for_payment">
        <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
        <button class="iw-client-button" type="submit">Confirm & create invoice <i class="bi bi-arrow-right"></i></button>
      </form>
    </div>
  </section>
<?php else: ?>
  <form method="post" action="<?= e(url('cac', $hasRecord ? ['id'=>(int) $record['id']] : ['kind'=>$kind])) ?>" id="cacForm" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" id="cacCsrf">
    <input type="hidden" name="form_action" value="save_draft" id="cacAction">
    <input type="hidden" name="id" id="cacApplicationId" value="<?= (int) ($record['id'] ?? 0) ?>">
    <input type="hidden" name="kind" value="<?= e($kind) ?>" id="cacKind">
    <section class="iw-tm-panel">
      <div class="iw-tm-panel-head"><span class="iw-tm-section-number">01</span>
        <div><h2>Business information</h2><p>We collect the details needed to prepare your Nigerian CAC filing.</p></div></div>
      <div class="iw-tm-fields">
        <?php if ($kind==='business_name'): ?>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="cacStructure">Business structure <span>*</span></label>
            <select name="structure" id="cacStructure">
              <option value="sole" <?= ($data['structure'] ?? 'sole')==='sole'?'selected':'' ?>>Sole proprietor · one proprietor</option>
              <option value="partnership" <?= ($data['structure'] ?? '')==='partnership'?'selected':'' ?>>Partnership · two or more proprietors</option>
            </select><small>Each additional partner beyond the first adds a fee. No partners? Choose sole proprietor.</small>
          </div>
        <?php else: ?>
          <div class="iw-tm-field iw-tm-field-full"><label>Entity type</label>
            <input readonly value="Private Company Limited by Shares (LTD)">
            <small>Other structures, regulated companies and foreign participation require separate professional review.</small></div>
        <?php endif; ?>
        <div class="iw-tm-field"><label for="cacName1">Preferred business name <span>*</span></label>
          <input id="cacName1" name="name1" maxlength="190" required value="<?= e((string) ($data['name1'] ?? '')) ?>" placeholder="First choice"></div>
        <div class="iw-tm-field"><label for="cacName2">Alternative business name</label>
          <input id="cacName2" name="name2" maxlength="190" value="<?= e((string) ($data['name2'] ?? '')) ?>" placeholder="Second choice (recommended)"></div>
        <div class="iw-tm-field"><label for="cacReservation">CAC availability code (if already reserved)</label>
          <input id="cacReservation" name="reservation_code" maxlength="70" value="<?= e((string) ($data['reservation_code'] ?? '')) ?>" placeholder="Optional AV code"></div>
        <div class="iw-tm-field"><label for="cacCommencement">Commencement date (if trading already)</label>
          <input id="cacCommencement" name="commencement" type="date" value="<?= e((string) ($data['commencement'] ?? '')) ?>"></div>
        <div class="iw-tm-field iw-tm-field-full"><label for="cacActivity">Nature of business <span>*</span></label>
          <textarea id="cacActivity" name="activity" rows="3" required placeholder="Describe the actual business activity"><?= e((string) ($data['activity'] ?? '')) ?></textarea></div>
        <?php if ($kind==='company'): ?>
          <div class="iw-tm-field"><label for="cacCapital">Proposed issued share capital (NGN) <span>*</span></label>
            <input id="cacCapital" name="share_capital" type="number" min="1" step="1" value="<?= e((string) ($data['share_capital'] ?? '')) ?>"></div>
          <div class="iw-tm-field"><label for="cacShares">Number of issued shares</label>
            <input id="cacShares" name="share_count" type="number" min="1" step="1" value="<?= e((string) ($data['share_count'] ?? '')) ?>"></div>
        <?php endif; ?>
        <div class="iw-tm-field iw-tm-field-full">
          <label class="iw-cac-check"><input name="same_address" value="1" type="checkbox" id="cacSameAddress" <?= !empty($data['same_address'])?'checked':'' ?>>
            Use the lead applicant's residential address as the business address</label></div>
        <div class="iw-tm-field iw-tm-field-full"><label for="cacAddress">Business address in Nigeria <span>*</span></label>
          <input id="cacAddress" name="business_address" value="<?= e((string) ($data['business_address'] ?? '')) ?>" required placeholder="House number and street"></div>
        <div class="iw-tm-field"><label for="cacCity">City / town <span>*</span></label>
          <input id="cacCity" name="business_city" value="<?= e((string) ($data['business_city'] ?? '')) ?>" required></div>
        <div class="iw-tm-field"><label for="cacLga">Local government area <span>*</span></label>
          <input id="cacLga" name="business_lga" value="<?= e((string) ($data['business_lga'] ?? '')) ?>" required></div>
        <div class="iw-tm-field"><label for="cacState">State <span>*</span></label>
          <input id="cacState" name="business_state" value="<?= e((string) ($data['business_state'] ?? '')) ?>" required></div>
        <div class="iw-tm-field"><label for="cacBusinessEmail">Business contact email</label>
          <input type="email" id="cacBusinessEmail" name="registered_business_email" value="<?= e((string) ($data['registered_business_email'] ?? '')) ?>"></div>
        <div class="iw-tm-field"><label for="cacBusinessPhone">Business contact phone</label>
          <input id="cacBusinessPhone" name="registered_business_phone" type="tel" value="<?= e((string) ($data['registered_business_phone'] ?? '')) ?>"></div>
        <div class="iw-tm-field"><label for="cacCurrency">Payment currency</label>
          <select id="cacCurrency" name="currency"><option value="NGN" <?= ($data['currency'] ?? 'NGN')==='NGN'?'selected':'' ?>>Nigerian naira (NGN)</option>
            <option value="USD" <?= ($data['currency'] ?? '')==='USD'?'selected':'' ?>>US dollars (USD)</option></select></div>
      </div>
    </section>

    <section class="iw-tm-panel">
      <div class="iw-tm-panel-head"><span class="iw-tm-section-number">02</span>
        <div><h2><?= $kind==='company'?'Directors and shareholders':'Proprietor information' ?></h2>
          <p>One lead applicant; add additional <?= $kind==='company'?'directors/shareholders':'partners' ?> if needed. Each person's documents are attached separately.</p></div></div>
      <div id="cacPeople">
        <?php foreach ($people as $i=>$person): ?>
          <?php require __DIR__ . '/partials/cac-person.php'; ?>
        <?php endforeach; ?>
      </div>
      <div class="iw-cac-people-actions">
        <button class="iw-client-button iw-client-button-soft" type="button" id="cacAddPerson"><i class="bi bi-person-plus"></i> Add <?= $kind==='company'?'person':'partner' ?></button>
        <span id="cacPartnerHint" class="iw-client-small-muted"></span>
      </div>
    </section>
    <section class="iw-tm-panel">
      <div class="iw-tm-panel-head"><span class="iw-tm-section-number">03</span>
        <div><h2>Declaration and consent</h2><p>Review all details, and confirm that everyone has authorised this filing.</p></div></div>
      <label class="iw-cac-check">
        <input type="checkbox" name="declaration" value="1" required <?= !empty($data['declaration'])?'checked':'' ?>>
        <span>I confirm that the information is accurate, that the named persons have authorised this application, and that iWorks may process the submitted personal documents for this Nigerian CAC filing.</span>
      </label>
      <p class="iw-client-small-muted">A drawn signature or uploaded signature is evidence provided for this intake and may not replace any official CAC-generated form or additional wet-signature requirement.</p>
    </section>
    <div class="iw-cac-form-actions">
      <div id="cacSaveStatus" role="status" aria-live="polite">Your details are saved when you select Save draft or Review.</div>
      <div>
        <button type="button" id="cacSaveBtn" class="iw-client-button iw-client-button-soft"><i class="bi bi-cloud-arrow-up"></i> Save draft</button>
        <button type="button" id="cacReviewBtn" class="iw-client-button">Review application <i class="bi bi-arrow-right"></i></button>
      </div>
    </div>
  </form>
  <template id="cacPersonTemplate">
    <?php $i = '__INDEX__'; $person = []; require __DIR__ . '/partials/cac-person.php'; ?>
  </template>
<?php endif; ?>
</div>
</div>
<?php if ($stage==='details'): ?>
  <script id="cacConfig" type="application/json"><?= json_encode([
      'uploadUrl'=>url('cac',['id'=>0]),'draftUrl'=>url('cac'),
      'clientId'=>(int) ($user['id'] ?? 0), 'kind'=>$kind,
      'base'=>cac_price_setting('cac_business_base_ngn',26000),
      'extra'=>cac_price_setting('cac_business_extra_partner_ngn',15000),
      'companyBase'=>cac_company_default_ngn(),
      'directorExtra'=>cac_price_setting('cac_company_extra_director_ngn',0),
      'shareholderExtra'=>cac_price_setting('cac_company_extra_shareholder_ngn',0),
      'fx'=>(float) fx_ngn_per_usd(),
      'id'=>(int) ($record['id'] ?? 0),
      'existingFiles'=>array_keys($uploaded)
    ],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES) ?></script>
  <script defer src="assets/js/cac-application.js?v=20261112a"></script>
<?php endif; ?>
