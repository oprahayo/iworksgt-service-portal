<?php
$businessFrom = cac_money_setting('cac_business_base_ngn', 26000);
$companyService = get_service_by_slug('company-registration');
$companyFrom = $companyService ? cac_company_quote(1) : 0;
?>
<div class="iw-client-heading">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All services</a>
    <span class="iw-client-eyebrow">CAC NIGERIA / BUSINESS REGISTRATION</span>
    <h1>What would you like to register?</h1>
    <p>One guided registration service. Choose the legal structure that matches your business.</p>
  </div>
</div>
<div class="row g-3" style="max-width:1050px">
  <div class="col-lg-6">
    <article class="card h-100">
      <div class="card-body p-4">
        <div class="iw-cac-pathway-icon"><i class="bi bi-person-workspace" aria-hidden="true"></i></div>
        <p class="iw-cac-pathway-kicker">BUSINESS NAME</p>
        <h2 class="h4">Enterprise or Business Name</h2>
        <p class="text-muted">For individuals and partnerships trading under a registered business name. Choose a single proprietor or multiple partners in the next step. This is not a Limited Company.</p>
        <p class="fw-semibold mt-3 mb-2">From ₦<?= e(number_format($businessFrom,0)) ?></p>
        <a class="btn btn-primary w-100" href="<?= e(url('cac_filing',['type'=>'business'])) ?>">Continue with Business Name <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </article>
  </div>
  <div class="col-lg-6">
    <article class="card h-100">
      <div class="card-body p-4">
        <div class="iw-cac-pathway-icon"><i class="bi bi-buildings" aria-hidden="true"></i></div>
        <p class="iw-cac-pathway-kicker">PRIVATE COMPANY LIMITED BY SHARES</p>
        <h2 class="h4">Limited Company (Ltd)</h2>
        <p class="text-muted">For one or more owners using a separate company structure. Add directors, shareholders, share allocation and beneficial ownership particulars. A Nigerian Ltd is not a US LLC.</p>
        <p class="fw-semibold mt-3 mb-2"><?= $companyFrom > 0 ? 'From ₦'.e(number_format($companyFrom,0)) : 'Contact support for pricing' ?></p>
        <a class="btn btn-primary w-100" href="<?= e(url('cac_filing',['type'=>'company'])) ?>">Continue with Limited Company <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </article>
  </div>
</div>
<div class="alert alert-light border mt-3" style="max-width:1050px">
  <strong>Name reservation is not guaranteed.</strong> We screen names and assist with CAC filing, but the final decision comes from the Corporate Affairs Commission. Some professions require regulator consent or special licences.
  <a href="https://icrp.cac.gov.ng/public-search/" target="_blank" rel="noopener noreferrer">Search the official CAC registry</a>.
</div>
