<?php
$businessFrom = cac_money_setting('cac_business_base_ngn', 26000);
$companyService = get_service_by_slug('company-registration');
$companyFrom = 0;
$companyGrowthFrom = 0;
if ($companyService) {
    try {
        $companyFrom = cac_company_quote(1);
        $companyGrowthFrom = cac_company_quote(1, 2000000);
    } catch (Throwable $error) { $companyFrom = 0; $companyGrowthFrom = 0; }
}
?>
<div class="iw-client-heading">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All services</a>
    <span class="iw-client-eyebrow">CAC NIGERIA · START REGISTRATION</span>
    <h1>What would you like to register?</h1>
    <p>Choose your structure. Prices shown are iWORKS service starting fees for Nigeria, not automatic CAC statutory charges. Your billing country, share capital, directors and selected services determine the final quotation.</p>
  </div>
</div>
<div class="iw-cac-path-choice" aria-label="Choose registration type">
  <article>
    <div class="iw-cac-pathway-icon"><i class="bi bi-shop" aria-hidden="true"></i></div>
    <span class="iw-cac-pathway-kicker">OPTION 1 · BUSINESS NAME</span>
    <h2>Enterprise / Business Name</h2>
    <p>Register a trading name for yourself or with partners. This does not create a separate limited company.</p>
    <ul>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> One proprietor or multiple partners</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> For independent businesses and everyday trading</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> No shareholders or share capital form</li>
    </ul>
    <p class="iw-cac-price">From ₦<?= e(number_format($businessFrom,0)) ?></p>
    <a class="iw-cac-path-link" href="<?= e(url('cac_filing',['type'=>'business'])) ?>">Continue with Business Name <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </article>
  <article>
    <div class="iw-cac-pathway-icon"><i class="bi bi-buildings" aria-hidden="true"></i></div>
    <span class="iw-cac-pathway-kicker">OPTION 2 · PRIVATE LIMITED COMPANY</span>
    <h2>Limited Company (Ltd)</h2>
    <p>Form a Nigerian company limited by shares, with directors and shareholders. A Nigerian Ltd is different from a U.S. LLC.</p>
    <ul>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> One or more shareholders</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Separate company structure and ownership records</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Directors, share capital and ownership details</li>
    </ul>
    <p class="iw-cac-price"><?= $companyFrom > 0 ? 'From ₦'.e(number_format($companyFrom,0)) : 'Request a company quote' ?></p>
    <a class="iw-cac-path-link" href="<?= e(url('cac_filing',['type'=>'company'])) ?>">Continue with Limited Company <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </article>
  <article>
    <div class="iw-cac-pathway-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></div>
    <span class="iw-cac-pathway-kicker">OPTION 3 · GROWTH COMPANY</span>
    <h2>Limited Company · ₦2m–₦5m capital</h2>
    <p>For businesses using issued share capital above ₦1 million and up to ₦5 million. The applicable filing rules, owners and final fees are confirmed in the guided form.</p>
    <ul>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Multiple directors/shareholders as needed</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Higher-capital service pricing calculated on the server</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Step-by-step documents and application tracking</li>
    </ul>
    <p class="iw-cac-price"><?= $companyGrowthFrom > 0 ? 'From ₦'.e(number_format($companyGrowthFrom,0)) : 'Request a growth company quote' ?></p>
    <a class="iw-cac-path-link" href="<?= e(url('cac_filing',['type'=>'company','tier'=>'growth'])) ?>">Continue with growth company <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </article>
  <article>
    <div class="iw-cac-pathway-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
    <span class="iw-cac-pathway-kicker">OPTION 4 · NONPROFIT</span>
    <h2>NGO / Incorporated Trustees</h2>
    <p>For associations, charities, churches and community organisations. Names, trustees, constitutions and statutory publications need a specialist review.</p>
    <ul>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Structure and trustee eligibility screening</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Document, publication and filing guidance</li>
      <li><i class="bi bi-check2-circle" aria-hidden="true"></i> No payment until your written quote is approved</li>
    </ul>
    <p class="iw-cac-price">Specialist quote · indicative from ₦180,000</p>
    <a class="iw-cac-path-link" href="<?= e(public_url('service',['slug'=>'incorporated-trustees'])) ?>">Request NGO registration quote <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </article>
</div>
<div class="alert alert-light border mt-3" style="max-width:1060px">
  <strong>Unsure which to choose?</strong> A Business Name is not a Limited Company. Special structures such as Incorporated Trustees, LLP and company limited by guarantee require a separate consultation. <a href="<?= e(url('support')) ?>">Ask iWorks support</a>.
  <p class="mb-0 mt-2 small">Name suggestions are preliminary. Only the official CAC portal can confirm name availability and final approval. <a href="https://icrp.cac.gov.ng/public-search/" target="_blank" rel="noopener noreferrer">Check the CAC public search</a>.</p>
</div>
