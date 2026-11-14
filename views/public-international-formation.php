<?php
/**
 * Substantive evergreen guidance for two distinct international search intents.
 * Not country-name doorway pages; no fabricated regulatory eligibility, fees,
 * testimonials, delivery times, tax advice, or guaranteed approvals.
 */
declare(strict_types=1);
$intent = (string) ($publicPageSlug ?? '');
$isNigeria = $intent === 'register-company-nigeria-from-abroad';
$isUs = $intent === 'us-llc-formation-from-nigeria';
if (!$isNigeria && !$isUs) { return; }
$availableServices = is_array($publicServices ?? null) ? $publicServices : [];
$serviceAvailable = static function (string $slug) use ($availableServices): bool {
    foreach ($availableServices as $record) {
        if (is_array($record) && (string)($record['slug'] ?? '') === $slug) { return true; }
    }
    return false;
};
$nigeriaCta = $serviceAvailable('company-registration')
    ? public_url('service',['slug'=>'company-registration']) : public_url('contact');
$usCta = $serviceAvailable('form-your-u-s-llc-in-minutes')
    ? public_url('service',['slug'=>'form-your-u-s-llc-in-minutes']) : public_url('contact');
?>
<main class="public-main iw-international-page" id="main-content">
  <div class="container public-container iw-international-width">
    <nav class="iw-legal-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(public_url('home')) ?>">Home</a><span aria-hidden="true">/</span>
      <a href="<?= e(public_url('services')) ?>">Services</a><span aria-hidden="true">/</span>
      <span aria-current="page"><?= $isNigeria ? 'Register a Nigerian company from abroad' : 'US LLC formation from Nigeria' ?></span>
    </nav>
    <header class="iw-international-hero">
      <span class="iw-legal-eyebrow">CROSS-BORDER BUSINESS SUPPORT</span>
      <?php if ($isNigeria): ?>
        <h1>Register a company in Nigeria while living abroad</h1>
        <p>Based in the UK, United States, Canada, Germany, Australia or elsewhere? Learn how to prepare for Nigerian CAC company incorporation remotely, what documents may be required, and where iWORKS can coordinate the process.</p>
      <?php else: ?>
        <h1>US LLC formation support for founders in Nigeria</h1>
        <p>Explore the practical steps Nigerian founders should review before forming a US limited liability company—including state rules, registered agents, IRS identification and ongoing tax or reporting obligations.</p>
      <?php endif; ?>
      <div class="iw-international-actions">
        <a class="iw-international-primary" href="<?= e($isNigeria ? $nigeriaCta : $usCta) ?>"><?= $isNigeria ? 'Explore Nigerian company registration' : 'Ask about US LLC formation' ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        <a href="<?= e(public_url('contact')) ?>">Ask a question <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </header>
    <article class="iw-international-copy">
<?php if ($isNigeria): ?>
      <h2>Can you set up a Nigerian company without living in Nigeria?</h2>
      <p>Nigeria’s Corporate Affairs Commission (CAC) provides an online registration process covering name searches, incorporation forms, documentation, filing fees and electronic certificates. Much of the preparation and coordination can be handled remotely. However, incorporation eligibility and any in-person, identity, registered-address or foreign-investment requirements depend on the proposed business and its owners. A remote service does not eliminate those statutory requirements.</p>
      <h2>What to prepare before starting</h2>
      <ol>
        <li><strong>Your legal structure.</strong> Decide whether a Nigerian private limited company or a different structure matches your plans. A business name is not equivalent to incorporating a limited company.</li>
        <li><strong>Proposed company names and activities.</strong> Prepare options for CAC name searches, a description of the business and any industry-regulatory permissions it may need.</li>
        <li><strong>Director and shareholder details.</strong> Obtain accurate legal names, contact details, nationality, identification and beneficial-ownership information requested for the filing.</li>
        <li><strong>A qualifying Nigerian registered office.</strong> Confirm the physical-address evidence and local contact information applicable to your case.</li>
        <li><strong>Payment and compliance planning.</strong> Understand government filing and stamp-duty charges, post-incorporation tax or reporting requirements, and any additional professional or foreign-investment review needed.</li>
      </ol>
      <h2>How remote filing support works</h2>
      <p>Begin with the <a href="<?= e($nigeriaCta) ?>">Nigerian company registration service</a> if it is published, or ask our team to review your situation. We help explain the service scope, organize the requested information, coordinate submissions and communicate updates through available portal workflows. The CAC alone decides whether an incorporation is approved. Official guidance: <a href="https://cac.gov.ng/services/company-registration" target="_blank" rel="noopener">CAC company registration</a> and <a href="https://icrp.cac.gov.ng/" target="_blank" rel="noopener">CAC online registration portal</a>.</p>
      <h2>For Nigerians and international founders in different countries</h2>
      <p>Customers based in <strong>the United Kingdom</strong>, <strong>Canada</strong>, <strong>the United States</strong>, <strong>Germany</strong>, <strong>Australia</strong> and other jurisdictions may start enquiries online. Your current country can affect how identity documents are certified, how payments are made, and whether you need local legal or tax advice. We confirm relevant requirements rather than offering identical answers or guaranteed outcomes for every location.</p>
      <h2>Frequently asked questions</h2>
      <h3>Do I have to travel to Nigeria?</h3>
      <p>Some tasks are suitable for remote preparation and online processing. Travel or additional verification may still be necessary in particular cases; confirm requirements before paying for a service.</p>
      <h3>Can a non-Nigerian own a Nigerian company?</h3>
      <p>Foreign ownership may be possible, subject to sector restrictions, minimum-capital, immigration, investment and other requirements. Business type and ownership structure should be reviewed with a qualified adviser before you commit.</p>
      <h3>Does CAC incorporation include banking, tax registration and licences?</h3>
      <p>Not automatically. Bank onboarding, tax registrations and sector licences are separate processes with their own eligibility checks and provider decisions.</p>
      <h3>How long does incorporation take?</h3>
      <p>It depends on complete documentation, name approval, regulator review and any required corrections. iWORKS does not promise an exact CAC approval date.</p>
<?php else: ?>
      <h2>Can a founder living in Nigeria form a US LLC?</h2>
      <p>Foreign founders can generally explore forming a company under a US state’s business-registration rules, but eligibility, identity, address, licensing and ownership details depend on the state and activity. An LLC is created under state law; its tax treatment and filing duties are separate federal and state questions. US formation alone does not grant immigration status, work permission, banking approval or tax-free business operations.</p>
      <h2>Important decisions before you file</h2>
      <ol>
        <li><strong>Choose a state for genuine business reasons.</strong> Compare filing and annual costs, registered-agent requirements, publication rules if any, and business nexus. No state is universally optimal.</li>
        <li><strong>Confirm a permitted name and registered agent.</strong> A US state generally requires a registered agent or office for legal notices and may require additional organizer/owner details.</li>
        <li><strong>Understand federal tax classifications.</strong> A single-member LLC can be disregarded for some US tax purposes by default, but that does not mean there are no tax returns or reporting obligations.</li>
        <li><strong>Review IRS identifiers and filings.</strong> Foreign-owned US disregarded entities may have Form 5472 and pro forma Form 1120 obligations when applicable. EIN procedures and documentation vary by applicant situation.</li>
        <li><strong>Plan continuing obligations.</strong> State annual reports, registered-agent renewals, applicable beneficial-ownership reporting rules, licensing and local and cross-border tax requirements can change.</li>
      </ol>
      <h2>What iWORKS can help you review</h2>
      <p>Our business-support workflow can help you outline the intended activity, gather essential application details, identify the chosen state’s published filing steps, coordinate practical service requirements and communicate with relevant partners. We do not represent ourselves as the US government, IRS, a US state regulator, or your tax or legal adviser. Availability and the exact scope of US formation services must be confirmed before ordering.</p>
      <p>Use the <a href="<?= e($usCta) ?>">US LLC formation enquiry</a> or <a href="<?= e(public_url('contact')) ?>">contact iWORKS</a> to discuss your situation. Official reference: <a href="https://www.irs.gov/instructions/i5472" target="_blank" rel="noopener">IRS Form 5472 instructions</a>. Consult the particular state’s Secretary of State or filing authority for current rules.</p>
      <h2>Frequently asked questions</h2>
      <h3>Does registering an LLC guarantee a US business bank account?</h3>
      <p>No. Bank and payment-provider onboarding is separate and subject to eligibility, location, identity and compliance review.</p>
      <h3>Is a US LLC automatically tax-free for nonresidents?</h3>
      <p>No. Tax classification, income source, operations, ownership and cross-border facts determine obligations. Obtain qualified US and Nigerian tax advice.</p>
      <h3>What is a registered agent?</h3>
      <p>A state-designated person or service that receives legal notices for the company at a qualifying in-state address, subject to state rules.</p>
      <h3>Can I run everything remotely?</h3>
      <p>Many steps can be coordinated online, but identity, compliance, banking and provider requirements vary. Confirm exactly what can be completed without travel.</p>
<?php endif; ?>
    </article>
    <div class="iw-international-next">
      <h2>Need guidance for your situation?</h2>
      <p>Tell us your country of residence, business activity and preferred structure. We will confirm what services are available and what outside advice or official approvals may be required.</p>
      <a href="<?= e(public_url('contact')) ?>">Contact iWORKS <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      <p>Also explore: <a href="<?= e(public_url($isNigeria ? 'us-llc-formation-from-nigeria' : 'register-company-nigeria-from-abroad')) ?>"><?= $isNigeria ? 'US LLC formation for Nigerians' : 'Nigerian company registration from abroad' ?></a></p>
    </div>
  </div>
</main>
