<?php
/** Four registration choices, all routed to one existing CAC intake system or
 * the existing quotation/agent workflow. No duplicate products or invoices. */
$billingCountry=iworks_launch_billing_country($client);
$registrationOptions=iworks_launch_registration_options($billingCountry);
?>
<div class="iw-client-heading">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All services</a>
    <span class="iw-client-eyebrow">CAC NIGERIA · CHOOSE A PACKAGE</span>
    <h1>Register a business</h1>
    <p>Select the structure and price range that matches your needs. The process is guided, with draft saving and secure document uploads.</p>
    <p class="iw-cac-explain">Your billing location is <?= e($billingCountry) ?>; prices and invoices are shown in <?= e(iworks_launch_account_currency($client)) ?>. Nigerian and international packages are priced separately, not converted at checkout.</p>
  </div>
</div>
<div class="iw-cac-path-choice iw-cac-launch-options" aria-label="Choose a Nigerian registration package">
<?php foreach ($registrationOptions as $option): ?>
<?php
    $isQuote = $option['mode']==='quote';
    $action = $isQuote
        ? url('quote_request',['service'=>'incorporated-trustees','package'=>'ngo'])
        : url('cac_filing',['type'=>$option['type'],'package'=>$option['package']]);
?>
  <article class="iw-cac-launch-option">
    <div class="iw-cac-pathway-icon"><i class="bi <?= $option['type']==='business'?'bi-shop':($option['type']==='ngo'?'bi-people':'bi-buildings') ?>" aria-hidden="true"></i></div>
    <span class="iw-cac-pathway-kicker"><?= e((string)$option['subtitle']) ?></span>
    <h2><?= e((string)$option['title']) ?></h2>
    <p><?= e((string)$option['description']) ?></p>
    <p class="iw-cac-price"><?= $isQuote?'Indicative from ':'From ' ?><?= e(money((float)$option['price'],(string)$option['currency'])) ?></p>
    <?php if ($isQuote): ?><p class="iw-cac-explain">Free enquiry first. A written quotation is required before any payment.</p><?php endif; ?>
    <a class="iw-cac-path-link" href="<?= e($action) ?>"><?= $isQuote?'Request an NGO quotation':'Start registration' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </article>
<?php endforeach; ?>
</div>
<div class="alert alert-light border mt-3">
  <strong>Know before you pay:</strong> The advertised package covers the standard assisted filing scope for the selected structure. Exceptional or regulated applications, additional official charges and unusual name reservations must be itemised and agreed before any additional payment. CAC makes the final approval decision.
  <a href="https://cac.gov.ng/services/company-registration" target="_blank" rel="noopener noreferrer">Check official CAC guidance</a>.
</div>
