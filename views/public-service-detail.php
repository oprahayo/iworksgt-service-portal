<?php
/**
 * One editorial product detail layout for every published service. The
 * routed $service comes from iworks_customer_published_service(), including
 * real admin-managed CAC price variants and the existing checkout policy.
 *
 * No duplicate pricing, product IDs, or client-side order calculations.
 */
require_once __DIR__ . '/../app/public_marketing.php';
require_once __DIR__ . '/../app/public-photo-config.php';
$service = is_array($service ?? null) ? $service : [];
$serviceName = trim((string) ($service['name'] ?? 'Service'));
$serviceSlug = trim((string) ($service['slug'] ?? ''));
$categoryName = ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')));
$shortDescription = trim((string) ($service['short_description'] ?? ''));
$description = (string) ($service['description'] ?? '');
if (strcasecmp($serviceName, 'Launch a Company Package') === 0) {
    // Older CMS fixture text must not present banking as a current service.
    $description = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $description);
    $shortDescription = str_ireplace(['banking, ', ', banking', 'bank account opening', 'banking'], '', $shortDescription);
}
$safeDescription = sanitize_limited_html($description);
$included = array_values(array_filter(array_map(
    static fn($value): string => trim(is_string($value) ? $value : ''),
    is_array($service['featured_bullets'] ?? null) ? $service['featured_bullets'] : []
)));
$guide = iworks_search_service_guide($service);
$steps = is_array($guide['steps'] ?? null) ? $guide['steps'] : [];
$questions = is_array($guide['questions'] ?? null) ? $guide['questions'] : [];
$pricing = iworks_customer_product_price($service);
$isEnquiry = (bool) $pricing['is_enquiry'];
$registrationType = (string) ($service['_iworks_registration_type'] ?? '');
$actionText = $isEnquiry
    ? 'Request a specialist quote'
    : ($registrationType === 'business'
        ? 'Start Business Name registration'
        : ($registrationType === 'company'
            ? 'Start Limited Company registration'
            : ($serviceSlug === 'trademark' ? 'Start trademark application' : 'Start this service')));
$actionUrl = $isEnquiry
    ? public_url('contact')
    : portal_url('login', ['service' => $serviceSlug]);
$actionHelp = $isEnquiry;
$isWebsiteBuild = $serviceSlug === 'website-design-development';
$isScumlSupport = $serviceSlug === 'scuml-document-support';
if ($isWebsiteBuild) {
    $actionText = 'Choose a website package';
    $actionUrl = '#iwWebsitePackagesTitle';
    $actionHelp = false;
} elseif ($isEnquiry) {
    $actionText = 'Request a tailored quote';
    $actionUrl = '#iwSpecialistQuoteForm';
    $actionHelp = false;
}
if ($isWebsiteBuild) {
    require_once __DIR__ . '/../app/launch_packages.php';
    // A public network hint only affects which price examples appear.
    // Actual paid invoices use the signed-in customer's country.
    $countryHint = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    $customer = function_exists('current_user') ? current_user() : null;
    $hintCurrency = $customer && is_array($customer)
        ? (iworks_client_country_currency($customer) ?: 'NGN')
        : ($countryHint !== '' && $countryHint !== 'NG' && $countryHint !== 'XX' ? 'USD' : 'NGN');
    $websitePackages = iworks_website_package_options($hintCurrency);
}
$scumlExampleData = '';
if ($isScumlSupport) {
    $examplePath = dirname(__DIR__) . '/assets/branding/services/scuml-document-example.svg';
    if (is_file($examplePath) && is_readable($examplePath)) {
        $scumlExampleData = 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($examplePath));
    }
}
$featured = !empty($service['featured_image_id'])
    ? get_media_asset((int) $service['featured_image_id'])
    : null;
$imageUrl = iworks_service_photo_url($service, trim((string) ($featured['url'] ?? '')));
$relatedServices = [];
foreach (is_array($publicServices ?? null) ? $publicServices : [] as $item) {
    if (!is_array($item)
        || (string) ($item['slug'] ?? '') === $serviceSlug
        || (string) ($item['category'] ?? '') !== (string) ($service['category'] ?? '')
        || empty($item['slug'])
        || empty($item['name'])) {
        continue;
    }
    $relatedServices[] = $item;
    if (count($relatedServices) === 3) {
        break;
    }
}
?>
<main class="public-main iw-detail iw-product" id="main-content">
  <div class="container public-container iw-product-shell">
    <nav class="iw-product-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(public_url('home')) ?>">Home</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="<?= e(public_url('services')) ?>">Services</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span aria-current="page"><?= e($serviceName) ?></span>
    </nav>

    <header class="iw-product-heading">
      <span class="iw-product-category"><?= e($categoryName) ?></span>
      <h1><?= e($serviceName) ?></h1>
      <?php if ($shortDescription !== ''): ?>
        <p class="iw-product-lead"><?= e($shortDescription) ?></p>
      <?php endif; ?>
    </header>

    <?php if (in_array($registrationType, ['business', 'company'], true)): ?>
      <?php
        $domesticBusiness = function_exists('cac_money_setting')
            ? cac_money_setting('cac_business_base_ngn', 35000) : 35000;
        $companyRecord = function_exists('get_service_by_slug')
            ? get_service_by_slug('company-registration') : null;
        $domesticBasic = (float)(($companyRecord['price'] ?? null) ?? 75000);
        $domesticGrowth = max($domesticBasic, function_exists('cac_money_setting')
            ? cac_money_setting('cac_company_growth_ngn', 150000) : 150000);
        $cacChoices = [
            ['key'=>'business','label'=>'Business Name','detail'=>'Sole proprietor or partnership, without a separate limited company.','ngn'=>$domesticBusiness,'href'=>portal_url('login',['service'=>'cac-business-name'])],
            ['key'=>'basic','label'=>'Limited Company · up to ₦1m capital','detail'=>'Private company limited by shares, with guided directors and ownership details.','ngn'=>$domesticBasic,'href'=>portal_url('login',['service'=>'company-registration'])],
            ['key'=>'growth','label'=>'Limited Company · ₦2m–₦5m capital','detail'=>'Higher-capital company registration with guided CAC documentation.','ngn'=>$domesticGrowth,'href'=>portal_url('login',['service'=>'company-registration','tier'=>'growth'])],
            ['key'=>'ngo','label'=>'NGO / Incorporated Trustees','detail'=>'Nonprofits and associations need tailored document and eligibility review.','ngn'=>180000,'href'=>public_url('service',['slug'=>'incorporated-trustees'])],
        ];
        $market = function_exists('iworks_catalogue_market_currency') ? iworks_catalogue_market_currency() : 'NGN';
        $fx = function_exists('fx_ngn_per_usd') ? max(1.0,fx_ngn_per_usd()) : 1600.0;
        $premium = function_exists('setting') ? max(1.0,(float)setting('diaspora_cac_service_multiplier','1.6')) : 1.6;
      ?>
      <section class="iw-website-launch-packages iw-cac-public-choices" aria-labelledby="iwCacPublicChoicesTitle">
        <div class="iw-website-launch-heading">
          <h2 id="iwCacPublicChoicesTitle">Choose your registration type</h2>
          <p>All four options are available to explore before signing in. Final fees depend on your registered country, company structure, selected add-ons and official filing charges.</p>
        </div>
        <div class="iw-website-launch-grid">
          <?php foreach ($cacChoices as $option): ?>
            <article class="iw-website-launch-choice">
              <strong><?= e($option['label']) ?></strong>
              <?php if ($option['key']==='ngo'): ?>
                <span class="iw-website-launch-price">Request a specialist quote</span>
              <?php else: ?>
                <?php $example = $market==='USD'
                    ? round((float)$option['ngn']*$premium/$fx,2)
                    : (float)$option['ngn']; ?>
                <span class="iw-website-launch-price">From <?= e(money($example,$market)) ?></span>
              <?php endif; ?>
              <small><?= e($option['detail']) ?></small>
              <a href="<?= e($option['href']) ?>" class="iw-cac-public-choose">Choose this option <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </article>
          <?php endforeach; ?>
        </div>
        <p class="iw-website-launch-notice">These are iWORKS service-fee estimates, not a guarantee of CAC approval or inclusion of all statutory fees, stamp duties or specialist charges. Review the full quote before payment.</p>
      </section>
    <?php endif; ?>

    <?php if ($isEnquiry && !$isWebsiteBuild): ?>
      <section class="iw-website-launch-packages" aria-labelledby="iwSpecialistTitle">
        <div class="iw-website-launch-heading">
          <h2 id="iwSpecialistTitle">Request a tailored quote</h2>
          <p>Submit your requirements in one step. An iWORKS specialist will review the scope and provide a written quotation before any payment is requested.</p>
        </div>
        <form id="iwSpecialistQuoteForm" method="post" action="<?= e(public_url('contact')) ?>" class="iw-website-launch-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="website_lead">
          <input type="hidden" name="company_name" value="<?= e('Service enquiry: '.$serviceName) ?>">
          <div class="iw-website-launch-fields">
            <label>Full name <input required autocomplete="name" name="full_name" maxlength="150"></label>
            <label>Email address <input required type="email" autocomplete="email" name="email" maxlength="254"></label>
            <label>Phone (optional) <input autocomplete="tel" type="tel" name="phone" maxlength="40"></label>
          </div>
          <label class="iw-website-launch-brief">What do you need? <textarea name="message" required minlength="10" maxlength="4000" rows="3" placeholder="Describe your request, deadlines and relevant requirements…"></textarea></label>
          <p class="iw-website-launch-notice">This requests a quotation and creates no paid order. We will confirm fees and any official charges before proceeding. See <a href="<?= e(public_url('terms')) ?>">Terms</a> and <a href="<?= e(public_url('privacy')) ?>">Privacy</a>.</p>
          <button type="submit" class="iw-product-cta">Send quote request <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </section>
    <?php endif; ?>

    <?php if ($isWebsiteBuild): ?>
      <section class="iw-website-launch-packages" aria-labelledby="iwWebsitePackagesTitle">
        <div class="iw-website-launch-heading">
          <h2 id="iwWebsitePackagesTitle">Choose the website you need</h2>
          <p>Start with an indicative package, tell us your requirements and receive a written scope and final price before payment. These are <?= e($hintCurrency) ?> examples, not an instant charge.</p>
        </div>
        <div class="iw-website-launch-grid">
          <?php foreach ($websitePackages as $webPackage): ?>
            <label class="iw-website-launch-choice">
              <input type="radio" form="iwWebsitePackageRequest" name="website_package" value="<?= e($webPackage['key']) ?>" <?= $webPackage['key']==='business'?'checked':'' ?>>
              <strong><?= e($webPackage['title']) ?></strong>
              <span class="iw-website-launch-price"><?= e($webPackage['display']) ?></span>
              <small><?= e($webPackage['scope']) ?></small>
            </label>
          <?php endforeach; ?>
        </div>
        <form id="iwWebsitePackageRequest" method="post" action="<?= e(public_url('contact')) ?>" class="iw-website-launch-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="website_lead">
          <input type="hidden" name="company_name" value="Website package enquiry">
          <div class="iw-website-launch-fields">
            <label>Full name <input required autocomplete="name" name="full_name" maxlength="150"></label>
            <label>Email address <input required type="email" autocomplete="email" name="email" maxlength="254"></label>
            <label>Phone (optional) <input type="tel" autocomplete="tel" name="phone" maxlength="40"></label>
          </div>
          <label class="iw-website-launch-brief">Tell us what you want to build <textarea name="message" required minlength="10" maxlength="4000" rows="3" placeholder="Business type, features, target launch date, example websites…"></textarea></label>
          <p class="iw-website-launch-notice">Submitting requests a quote, not a payment. The final amount depends on the project scope, delivery requirements and your billing country. See our <a href="<?= e(public_url('terms')) ?>">Terms</a> and <a href="<?= e(public_url('privacy')) ?>">Privacy Policy</a>.</p>
          <button class="iw-product-cta" type="submit">Request a project quote <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </section>
    <?php endif; ?>


    <?php if ($isScumlSupport): ?>
    <section class="iw-scuml-service" aria-label="SCUML compliance document guidance">
      <div class="iw-scuml-feature">
        <div class="iw-scuml-feature-copy">
          <span class="iw-scuml-eyebrow">SCUML · COMPLIANCE SUPPORT</span>
          <h2>Review your documents. Know what to submit.</h2>
          <p>iWORKS provides independent document checks and guidance for businesses preparing for SCUML registration. The certificate is issued by EFCC after its own review—not by iWORKS.</p>
          <div class="iw-scuml-price-block">
            <span>iWORKS standard guidance fee</span>
            <strong><?= e((string)$pricing['display']) ?></strong>
            <?php if ($pricing['tax_note'] !== ''): ?><small><?= e((string)$pricing['tax_note']) ?></small><?php endif; ?>
            <a class="iw-product-cta" href="<?= e($actionUrl) ?>">Start SCUML document support <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <small>EFCC application &amp; certificate: <strong>FREE</strong>. The iWORKS fee is independent of government registration.</small>
          </div>
          <p class="iw-scuml-independence">iWORKS is not an EFCC/SCUML agent or representative. Customers submit the official application with their own valid business contact details. Approval is not guaranteed.</p>
        </div>
        <?php if ($scumlExampleData !== ''): ?>
          <figure class="iw-scuml-document-example">
            <img src="<?= e($scumlExampleData) ?>" width="900" height="635" decoding="async" loading="eager" alt="SCUML registration certificate format illustration with redacted details and prominent SAMPLE NOT VALID watermark">
            <figcaption>Illustrative redacted certificate layout based on a supplied reference. Not an issued or usable document.</figcaption>
          </figure>
        <?php endif; ?>
      </div>
      <div class="iw-scuml-guidance">
        <div>
          <h3>Documents for review</h3>
          <ul>
            <li>CAC registration or incorporation certificate</li>
            <li>CAC status report (BN-01 for Business Names; CAC 1.1 or equivalent for companies)</li>
            <li>TIN printout; company Memorandum &amp; Articles where applicable</li>
            <li>Relevant business-sector licence or professional evidence, if required</li>
          </ul>
          <a href="https://scuml.efcc.gov.ng/registration-guideline/" target="_blank" rel="noopener noreferrer">Check official requirements <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <div>
          <h3>Special business sectors</h3>
          <p>Required documents depend on what your business actually does, not simply whether it is a Business Name or Ltd.</p>
          <p><strong>Power / energy work:</strong> Enhanced review can be quoted at <strong>₦80,000</strong> (2× the standard iWORKS service fee) if the additional scope is confirmed. Contact iWORKS before ordering. This is not an EFCC fee or an official SCUML price category.</p>
          <a href="<?= e(public_url('contact')) ?>">Discuss a special-sector case <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
      </div>
    </section>
    <?php else: ?>

    <div class="iw-product-layout">
      <aside class="iw-product-side" aria-label="Service pricing and next step">
        <div class="iw-product-order">
          <span class="iw-product-price-caption"><?= e((string) $pricing['label']) ?></span>
          <div class="iw-product-price"><?= e((string) $pricing['display']) ?></div>
          <?php if ($pricing['tax_note'] !== ''): ?>
            <p class="iw-product-tax"><?= e((string) $pricing['tax_note']) ?></p>
          <?php endif; ?>
          <?php if ($actionHelp): ?>
            <a class="iw-product-cta" href="<?= e($actionUrl) ?>" data-iw-help-open data-iw-service="<?= e($serviceName) ?>">
              <?= e($actionText) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          <?php else: ?>
            <a class="iw-product-cta" href="<?= e($actionUrl) ?>">
              <?= e($actionText) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          <?php endif; ?>
          <p class="iw-product-order-note"><?= $isEnquiry
              ? 'We will review your requirements and confirm the scope and fees before proceeding.'
              : 'Enter your requirements securely and track each update in your iWorks account.' ?></p>
          <div class="iw-product-order-help">
            <span>Questions before you begin?</span>
            <a href="<?= e(public_url('contact')) ?>" data-iw-help-open data-iw-service="<?= e($serviceName) ?>">Talk to our team <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </div>
        </div>
      </aside>

      <article class="iw-product-content">
        <?php if ($imageUrl !== ''): ?>
          <figure class="iw-product-media iw-photo-feature iw-photo-feature-product">
            <img src="<?= e($imageUrl) ?>" alt="<?= e(str_starts_with($imageUrl, '/assets/photography/') ? 'Illustrative photograph for ' . $serviceName : $serviceName) ?>" width="960" height="540" loading="eager" decoding="async">
          </figure>
        <?php endif; ?>

        <section class="iw-product-section" id="product-overview" aria-labelledby="product-overview-title">
          <h2 id="product-overview-title">Overview</h2>
          <div class="iw-product-description">
            <?php if ($safeDescription !== ''): ?>
              <?= $safeDescription ?>
            <?php else: ?>
              <p>Review the service scope, share your requirements and manage progress from your account. We will confirm any external requirements before processing begins.</p>
            <?php endif; ?>
          </div>
        </section>

        <?php if ($included !== []): ?>
          <section class="iw-product-section" id="product-included" aria-labelledby="product-included-title">
            <h2 id="product-included-title">What's included</h2>
            <ul class="iw-product-included">
              <?php foreach ($included as $feature): ?>
                <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span><?= e($feature) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ($guide !== []): ?>
          <section class="iw-product-section" id="product-process" aria-labelledby="product-process-title">
            <h2 id="product-process-title"><?= e((string) ($guide['heading'] ?? 'How this service works')) ?></h2>
            <?php if (!empty($guide['intro'])): ?>
              <p class="iw-product-process-intro"><?= e((string) $guide['intro']) ?></p>
            <?php endif; ?>
            <?php if ($steps !== []): ?>
              <ol class="iw-product-steps">
                <?php foreach ($steps as $index => $step): ?>
                  <?php if (!is_array($step)) { continue; } ?>
                  <li>
                    <span class="iw-product-step-number" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <div><h3><?= e((string) ($step[0] ?? 'Step ' . ($index + 1))) ?></h3>
                      <p><?= e((string) ($step[1] ?? '')) ?></p></div>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>
          </section>
        <?php endif; ?>

        <?php if ($questions !== []): ?>
          <section class="iw-product-section" id="product-faq" aria-labelledby="product-faq-title">
            <h2 id="product-faq-title">Frequently asked questions</h2>
            <div class="iw-product-faq">
              <?php foreach ($questions as $question): ?>
                <?php if (!is_array($question)) { continue; } ?>
                <details>
                  <summary><?= e((string) ($question[0] ?? 'Question')) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
                  <p><?= e((string) ($question[1] ?? '')) ?></p>
                </details>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if (!empty($guide['official'])): ?>
          <p class="iw-product-source">Official requirements and decisions remain with the relevant regulator.
            <a href="<?= e((string) $guide['official']) ?>" target="_blank" rel="noopener noreferrer">Read the official guidance <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </p>
        <?php endif; ?>
      </article>
    </div>

    <?php endif; // End generic service layout; SCUML uses the iWORKS product showcase. ?>

    <?php if ($relatedServices !== []): ?>
      <nav class="iw-product-related" aria-label="Related services">
        <div class="iw-product-related-heading">
          <h2>Explore related services</h2>
          <a href="<?= e(public_url('services')) ?>">All services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <ul>
          <?php foreach ($relatedServices as $related): ?>
            <li><a href="<?= e(public_url('service', ['slug' => (string) $related['slug']])) ?>">
              <?= e((string) $related['name']) ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</main>
