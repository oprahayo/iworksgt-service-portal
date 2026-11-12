<?php
/** @var array $trademarkData */
$tmId = (int) ($trademarkApplication['id'] ?? 0);
$tmClasses = trademark_normalize_classes($trademarkData['classes'] ?? []);
$tmLabels = trademark_class_labels();
$tmPaid = ($trademarkApplication['payment_status'] ?? '') === 'paid';
$tmStoredCurrency = (string) ($trademarkApplication['pay_currency'] ?? ($trademarkData['currency'] ?? 'USD'));
$tmStoredTotal = (float) ($trademarkApplication['total_amount'] ?? $trademarkQuote['total']);
$tmClassCount = count($tmClasses);
$tmMark = (string) ($trademarkData['mark_name'] ?? '');
?>
<div class="iw-tm-intro">
  <a class="iw-client-back" href="<?= e(url('applications')) ?>"><i class="bi bi-arrow-left"></i> My applications</a>
  <div><span class="iw-client-eyebrow">TRADEMARK REGISTRATION · NIGERIA</span>
    <h1><?= $tmId > 0 ? 'Trademark application' : 'Start a trademark application' ?></h1>
    <p>Complete the details, review your application and choose how to pay.</p></div>
  <?php if ($tmId > 0): ?><span class="iw-tm-ref">TM-<?= $tmId ?></span><?php endif; ?>
</div>
<div class="iw-tm-grid">
  <aside class="iw-tm-aside" aria-label="Application progress">
    <ol class="iw-tm-steps">
      <li class="<?= $trademarkStage === 'details' ? 'active' : 'done' ?>"><span>1</span><div><strong>Trademark information</strong><small>Details & classification</small></div></li>
      <li class="<?= $trademarkStage === 'review' ? 'active' : ($trademarkStage === 'payment' ? 'done' : 'pending') ?>"><span>2</span><div><strong>Review & confirm</strong><small>Check your information</small></div></li>
      <li class="<?= $trademarkStage === 'payment' ? 'active' : 'pending' ?>"><span>3</span><div><strong>Payment</strong><small>Complete your order</small></div></li>
    </ol>
    <div class="iw-tm-price-box" aria-live="polite">
      <span>Price per class</span>
      <strong>$<?= e(number_format($trademarkUnit, 2)) ?></strong>
      <div class="iw-tm-summary-row"><small>Selected classes</small><b id="tmCount"><?= $tmClassCount ?></b></div>
      <div class="iw-tm-summary-row"><small>Total (USD)</small><b id="tmUsdTotal">$<?= e(number_format((float) $trademarkQuote['usd_total'], 2)) ?></b></div>
      <div class="iw-tm-summary-row"><small>Payable</small><b id="tmPayTotal"><?= e($trademarkQuote['currency']) ?> <?= e(number_format((float) $trademarkQuote['total'], 2)) ?></b></div>
      <p>Each class is billed separately. Your total is the price per class multiplied by the number of classes selected.</p>
    </div>
  </aside>

  <div class="iw-tm-workspace">
  <?php if ($trademarkStage === 'details'): ?>
    <form method="post" action="<?= e(url('trademark', $tmId ? ['id'=>$tmId] : [])) ?>" enctype="multipart/form-data" id="tmApplicationForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= $tmId ?>">
      <section class="iw-tm-panel">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">01</span><div><h2>About your trademark</h2><p>We'll use this information to prepare your trademark service request.</p></div></div>
        <div class="iw-tm-fields">
          <div class="iw-tm-field">
            <label for="tmCountry">Country of registration</label>
            <input id="tmCountry" value="Nigeria" readonly aria-describedby="tmCountryHint">
            <small id="tmCountryHint">This first release covers Nigerian trademark applications. Additional countries will be added separately.</small>
          </div>
          <div class="iw-tm-field">
            <label for="tmType">Trademark type</label>
            <select name="mark_type" id="tmType">
              <?php foreach (['word'=>'Word / brand name', 'logo'=>'Logo', 'slogan'=>'Slogan', 'combined'=>'Name and logo'] as $value=>$label): ?>
                <option value="<?= e($value) ?>" <?= ($trademarkData['mark_type'] ?? 'word') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="tmName">Trademark name <span>*</span></label>
            <input id="tmName" name="mark_name" maxlength="180" placeholder="Your brand or product name" value="<?= e($tmMark) ?>">
          </div>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="tmDescription">Description / what your brand does</label>
            <textarea id="tmDescription" name="description" rows="3" maxlength="2500" placeholder="Briefly describe the goods or services you want to protect"><?= e((string) ($trademarkData['description'] ?? '')) ?></textarea>
          </div>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="tmLogo">Logo file (required for logo applications)</label>
            <input id="tmLogo" name="mark_logo" type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
            <small>JPG, PNG or WebP up to 4 MB. Files stay private to your account.</small>
            <?php if (!empty($trademarkApplication['logo_filename'])): ?>
              <a class="iw-tm-file-link" href="<?= e(url('trademark_file', ['id'=>$tmId])) ?>" target="_blank" rel="noopener">View uploaded logo <i class="bi bi-box-arrow-up-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </section>
      <section class="iw-tm-panel">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">02</span><div><h2>Goods and services classes</h2><p>Select every Nice Classification class that covers your trademark.</p></div></div>
        <div class="iw-tm-class-top">
          <p><strong>$<?= e(number_format($trademarkUnit, 2)) ?> per class</strong> · Selecting 3 classes costs <strong>$<?= e(number_format($trademarkUnit * 3, 2)) ?></strong> before currency conversion.</p>
          <a href="https://www.wipo.int/en/web/classification-nice/" target="_blank" rel="noopener noreferrer">See WIPO's classification guidance <i class="bi bi-box-arrow-up-right"></i></a>
        </div>
        <label class="iw-tm-search-label" for="tmSearch">Search classes</label>
        <input id="tmSearch" class="iw-tm-class-search" type="search" placeholder="Search a class number or type of business" autocomplete="off">
        <div class="iw-tm-class-list" id="tmClassList">
          <?php foreach ($tmLabels as $number=>$label): ?>
            <label class="iw-tm-class-option" data-label="<?= e(strtolower('class '.$number.' '.$label)) ?>">
              <input type="checkbox" name="classes[]" value="<?= $number ?>" <?= in_array($number, $tmClasses, true) ? 'checked' : '' ?>>
              <span class="iw-tm-class-num"><?= $number ?></span>
              <span class="iw-tm-class-label"><?= e($label) ?></span>
              <span class="iw-tm-class-amount">$<?= e(number_format($trademarkUnit, 2)) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="iw-tm-help">Class headings are simplified guides, not legal advice. Check the official classification before filing.</p>
      </section>
      <section class="iw-tm-panel">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">03</span><div><h2>Trademark owner</h2><p>Enter the applicant's legal contact details.</p></div></div>
        <div class="iw-tm-fields">
          <div class="iw-tm-field"><label for="tmOwnerName">Full name / company name <span>*</span></label>
            <input id="tmOwnerName" name="owner_name" maxlength="180" value="<?= e((string) ($trademarkData['owner_name'] ?? '')) ?>" placeholder="Full legal name"></div>
          <div class="iw-tm-field"><label for="tmOwnerEmail">Email address <span>*</span></label>
            <input id="tmOwnerEmail" name="owner_email" type="email" maxlength="190" value="<?= e((string) ($trademarkData['owner_email'] ?? ($user['email'] ?? ''))) ?>" placeholder="owner@example.com"></div>
          <div class="iw-tm-field"><label for="tmOwnerPhone">Phone number <span>*</span></label>
            <input id="tmOwnerPhone" name="owner_phone" type="tel" maxlength="50" value="<?= e((string) ($trademarkData['owner_phone'] ?? ($user['phone'] ?? ''))) ?>" placeholder="+234…"></div>
          <div class="iw-tm-field"><label for="tmCurrency">Payment currency</label>
            <select id="tmCurrency" name="currency">
              <option value="USD" <?= ($trademarkData['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD — US dollars</option>
              <option value="NGN" <?= ($trademarkData['currency'] ?? 'USD') === 'NGN' ? 'selected' : '' ?>>NGN — Nigerian naira</option>
            </select>
            <small>Naira is calculated using the rate set in iWorks billing settings.</small>
          </div>
          <div class="iw-tm-field iw-tm-field-full"><label for="tmOwnerAddress">Full address <span>*</span></label>
            <textarea id="tmOwnerAddress" name="owner_address" rows="2" maxlength="500" placeholder="Street, city, region and country"><?= e((string) ($trademarkData['owner_address'] ?? '')) ?></textarea></div>
        </div>
      </section>
      <div class="iw-tm-form-actions">
        <button class="iw-client-button iw-client-button-soft" name="form_action" value="save_draft" type="submit"><i class="bi bi-cloud-check"></i> Save draft</button>
        <button class="iw-client-button" name="form_action" value="review_draft" type="submit">Save and continue <i class="bi bi-arrow-right"></i></button>
      </div>
    </form>
    <script>
    (function(){
      const list=document.getElementById('tmClassList'),search=document.getElementById('tmSearch'),currency=document.getElementById('tmCurrency');
      const unit=<?= json_encode((float) $trademarkUnit) ?>,fx=<?= json_encode((float) $trademarkFxRate) ?>;
      function update(){
        const count=list.querySelectorAll('input:checked').length;
        const usd=count*unit;
        const cur=currency.value;
        document.getElementById('tmCount').textContent=String(count);
        document.getElementById('tmUsdTotal').textContent='$'+usd.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        document.getElementById('tmPayTotal').textContent=cur+' '+(cur==='NGN'?usd*fx:usd).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
      }
      list.addEventListener('change',update);currency.addEventListener('change',update);
      search.addEventListener('input',function(){const q=search.value.toLowerCase().trim();list.querySelectorAll('.iw-tm-class-option').forEach(function(item){item.hidden=!item.dataset.label.includes(q);});});
      update();
    })();
    </script>

  <?php elseif ($trademarkStage === 'review'): ?>
    <section class="iw-tm-panel iw-tm-review">
      <div class="iw-tm-panel-head"><span class="iw-tm-section-number">02</span><div><h2>Review & confirm</h2><p>Confirm everything is correct before creating an invoice.</p></div></div>
      <div class="iw-tm-review-section">
        <div class="iw-tm-review-head"><h3>Trademark information</h3><a href="<?= e(url('trademark', ['id'=>$tmId])) ?>">Edit <i class="bi bi-pencil"></i></a></div>
        <dl class="iw-tm-review-list">
          <div><dt>Trademark</dt><dd><?= e($tmMark) ?></dd></div>
          <div><dt>Type</dt><dd><?= e(ucfirst((string) ($trademarkData['mark_type'] ?? 'word'))) ?></dd></div>
          <div><dt>Country</dt><dd>Nigeria</dd></div>
          <div><dt>Classes (<?= $tmClassCount ?>)</dt><dd><?= e(implode(', ', array_map(static fn($number)=>'Class '.$number.' — '.($tmLabels[$number] ?? ''), $tmClasses))) ?></dd></div>
          <div><dt>Owner</dt><dd><?= e((string) ($trademarkData['owner_name'] ?? '')) ?></dd></div>
          <div><dt>Email</dt><dd><?= e((string) ($trademarkData['owner_email'] ?? '')) ?></dd></div>
          <div><dt>Phone</dt><dd><?= e((string) ($trademarkData['owner_phone'] ?? '')) ?></dd></div>
          <div><dt>Address</dt><dd><?= e((string) ($trademarkData['owner_address'] ?? '')) ?></dd></div>
          <?php if (!empty($trademarkApplication['logo_filename'])): ?><div><dt>Logo</dt><dd><a href="<?= e(url('trademark_file', ['id'=>$tmId])) ?>" target="_blank" rel="noopener">View uploaded file</a></dd></div><?php endif; ?>
        </dl>
      </div>
      <div class="iw-tm-review-total">
        <div><strong><?= $tmClassCount ?> <?= $tmClassCount === 1 ? 'class' : 'classes' ?> × $<?= e(number_format($trademarkUnit, 2)) ?></strong>
        <small>USD <?= e(number_format((float) $trademarkQuote['usd_total'], 2)) ?><?= $trademarkQuote['currency'] === 'NGN' ? ' · Conversion rate ₦' . number_format($trademarkFxRate, 2) . ' / USD' : '' ?></small></div>
        <strong><?= e($trademarkQuote['currency']) ?> <?= e(number_format((float) $trademarkQuote['total'], 2)) ?></strong>
      </div>
      <p class="iw-tm-help">Submitting creates an unpaid invoice, not a completed filing. Payment and any required document checks happen afterward.</p>
      <form method="post" action="<?= e(url('trademark', ['id'=>$tmId])) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $tmId ?>">
        <input type="hidden" name="form_action" value="submit_for_payment">
        <div class="iw-tm-form-actions">
          <a class="iw-client-button iw-client-button-soft" href="<?= e(url('trademark', ['id'=>$tmId])) ?>"><i class="bi bi-arrow-left"></i> Back to edit</a>
          <button type="submit" class="iw-client-button">Confirm and continue to payment <i class="bi bi-arrow-right"></i></button>
        </div>
      </form>
    </section>

  <?php else: ?>
    <section class="iw-tm-panel iw-tm-payment">
      <div class="iw-tm-panel-head"><span class="iw-tm-section-number">03</span><div><h2><?= $tmPaid ? 'Payment received' : 'Choose how to pay' ?></h2><p>Your application details are saved. <?= $tmPaid ? 'You can track progress in My Orders.' : 'Pay securely through an available gateway.' ?></p></div></div>
      <div class="iw-tm-review-total">
        <div><strong><?= e($tmMark) ?></strong><small><?= $tmClassCount ?> <?= $tmClassCount === 1 ? 'class' : 'classes' ?> · USD <?= e(number_format((float) ($trademarkApplication['price_usd'] ?? 0), 2)) ?></small></div>
        <strong><?= e($tmStoredCurrency) ?> <?= e(number_format($tmStoredTotal, 2)) ?></strong>
      </div>
      <?php if ($tmPaid): ?>
        <div class="alert alert-success">Payment is recorded for this application. Further status updates are available in My Orders.</div>
      <?php else: ?>
        <?php $availableGateways = array_values(array_filter($trademarkMethods,
          static fn(array $method): bool => is_gateway_payment_method((string) ($method['method_code'] ?? '')))); ?>
        <?php if ($availableGateways): ?>
          <div class="iw-tm-methods">
            <?php foreach ($availableGateways as $method): ?>
            <form method="post" action="<?= e('invoice.php?id=' . (int) ($trademarkApplication['invoice_id'] ?? 0)) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="submit_payment">
              <input type="hidden" name="payment_method_id" value="<?= (int) $method['id'] ?>">
              <button class="iw-tm-method" type="submit"><span><i class="bi bi-credit-card"></i> Pay with <?= e((string) ($method['name'] ?? $method['method_name'] ?? ucfirst((string) $method['method_code']))) ?></span><i class="bi bi-arrow-right"></i></button>
            </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <a class="iw-client-button iw-client-button-soft iw-tm-invoice-link" href="<?= e('invoice.php?id=' . (int) ($trademarkApplication['invoice_id'] ?? 0)) ?>">Open invoice and all payment options <i class="bi bi-arrow-up-right"></i></a>
        <p class="iw-tm-help">USD and NGN are available according to your selected currency and configured provider. Card details are entered with the payment provider and are not stored by iWorks.</p>
      <?php endif; ?>
      <div class="iw-tm-form-actions"><a href="<?= e(url('applications')) ?>" class="iw-client-button iw-client-button-soft">Back to my applications</a></div>
    </section>
  <?php endif; ?>
  </div>
</div>
