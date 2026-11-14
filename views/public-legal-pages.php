<?php
/**
 * Authoritative, readable public legal notices. No CMS card containers.
 * All statements require a live processing/vendor audit before legal sign-off.
 * Pages are rendered in the shared public shell and do not update DB content.
 */
declare(strict_types=1);
$legalPage = (string) ($publicPageSlug ?? 'privacy');
$legalTitles = [
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms & Conditions',
    'cookies' => 'Cookie Policy',
];
if (!isset($legalTitles[$legalPage])) { return; }
?>
<main class="public-main iw-legal-page" id="main-content">
  <div class="container public-container iw-legal-width">
    <nav class="iw-legal-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(public_url('home')) ?>">Home</a><span aria-hidden="true">/</span>
      <span aria-current="page"><?= e($legalTitles[$legalPage]) ?></span>
    </nav>
    <header class="iw-legal-intro">
      <span class="iw-legal-eyebrow">iWORKS GLOBAL LTD · LEGAL INFORMATION</span>
      <h1><?= e($legalTitles[$legalPage]) ?></h1>
      <p>Last updated: 14 November 2026</p>
    </header>
    <article class="iw-legal-copy">
<?php if ($legalPage === 'privacy'): ?>
      <p class="iw-legal-lead">This notice explains how iWORKS GLOBAL LTD (“iWORKS”, “we” or “us”) handles personal information when you visit our website, open a portal account, request business-registration or digital services, contact support, or use our service tracking and payment features.</p>
      <h2>1. Who we are and how to contact us</h2>
      <p>iWORKS GLOBAL LTD provides business registration, regulatory documentation support, website development and related business services from Nigeria, including remote enquiries from customers abroad. You can contact our privacy team at <a href="mailto:support@iworksgt.com">support@iworksgt.com</a> or through <a href="<?= e(public_url('contact')) ?>">Contact</a>. Please mark rights requests “Privacy request”.</p>
      <h2>2. Information we collect</h2>
      <p>Depending on the service you use, this may include your name, business name, email, telephone, country, contact address, login/account identifiers, submitted forms, business activities, company and shareholder/director information, identity or other regulator-required documents, enquiries and chat messages, consultation requests, invoices, order status, payment references, correspondence and support history.</p>
      <p>We may also process technical information needed for security and service delivery, such as session identifiers, browser/device signals, approximate location inferred from network information, timestamps, masked network addresses and operational logs. We do not request precise GPS for ordinary customer chat. Please do not submit sensitive information unless it is specifically needed for a lawful service requirement.</p>
      <h2>3. Why and on what grounds we process data</h2>
      <p>We use personal information to respond to requests, verify eligibility and requirements, prepare and coordinate filings, provide online accounts, process orders and payments, send service notifications, support customers, protect the platform, prevent fraud, maintain accounting records, and comply with applicable laws. Depending on the activity and the law that applies, the legal basis may be performance of a contract or pre-contractual steps, compliance with a legal obligation, a legitimate interest subject to applicable balancing tests, or consent where required.</p>
      <h2>4. Information supplied about other people</h2>
      <p>If you supply director, shareholder, employee, co-founder or other third-party details, you must have lawful authority to do so and give those individuals appropriate notice where required. Their information is handled only to the extent needed for the services requested and relevant statutory obligations.</p>
      <h2>5. Who receives information</h2>
      <p>Access is limited to authorized iWORKS personnel and service partners needing it for the requested work. Depending on the transaction, information may be shared with public registries or regulators (including Nigeria’s Corporate Affairs Commission), identity-verification and payment providers, banking/payment infrastructure, email/hosting vendors, contracted professional advisers, and legal authorities where lawfully required. Payment-card details entered into a third-party checkout are handled through that provider’s payment environment; the iWORKS platform may receive transaction references and status information.</p>
      <p>Providers may process data under their own policies where they act as independent controllers. We do not sell personal information to data brokers or disclose client documents for advertising.</p>
      <h2>6. International customers and transfers</h2>
      <p>Customers may access iWORKS from Nigeria, the United Kingdom, United States, Canada, Germany, Australia and other countries. Data may need to be accessed or processed in Nigeria and in countries where relevant hosting, payment, communication or filing providers operate. We assess applicable cross-border restrictions and use lawful transfer arrangements where required. Local rights and additional safeguards can vary by location; no statement in this notice removes rights granted by applicable law.</p>
      <h2>7. Documents, account security and chat</h2>
      <p>Service files and communications may be accessible to designated account holders, assigned delivery personnel and administrators as necessary. We use access controls, authentication, role restrictions and protective operational measures. No internet transmission or storage arrangement is risk-free; contact us promptly if you suspect unauthorized access. Support chats and necessary local browser state may remain on your device until your session or stored data is cleared.</p>
      <h2>8. Retention and deletion</h2>
      <p>We retain information for the time needed to provide services, resolve disputes, maintain reliable business and payment records, respond to security incidents and meet statutory or regulatory obligations. Different records have different retention requirements; we do not apply one indefinite period to all data. Information is securely deleted, anonymized or archived with restricted access when no longer required, subject to legal holds or lawful exceptions.</p>
      <h2>9. Your choices and rights</h2>
      <p>Depending on the jurisdiction and circumstances, you may have rights to request access, correction, deletion, restriction, portability, objection to certain processing, withdrawal of consent where consent is the basis, and review or complaint through the relevant data-protection authority. We may need reasonable identity verification and may be required to retain some records. To request a review, email <a href="mailto:support@iworksgt.com">support@iworksgt.com</a>. We will respond within the timeframe required by applicable law.</p>
      <h2>10. Cookies, local storage and external resources</h2>
      <p>We use cookies or similar storage for login sessions, security, currency preferences and continuity of support chats. External services (for example, payment authentication or hosted site resources) may have separate policies. Read our <a href="<?= e(public_url('cookies')) ?>">Cookie Policy</a> for a description and browser-control options. Non-essential analytics or advertising storage must not be activated without any consent controls required by applicable law.</p>
      <h2>11. Marketing communication</h2>
      <p>Essential order messages, security notices and service updates are different from optional marketing. Where optional marketing is offered, we will provide choices and an unsubscribe or objection method as required. We do not treat a service purchase as unrestricted permission to send unrelated advertising.</p>
      <h2>12. Children</h2>
      <p>Our services are intended for adults and businesses, not for children to create accounts or submit personal records independently. If you believe a child’s information was supplied without proper authority, contact us so we can assess and act appropriately.</p>
      <h2>13. Automated systems</h2>
      <p>Online tools may assist with classification, routing, search or administrative checks. Regulatory authorities and service providers make their own decisions about filings and approvals; the website does not offer a guarantee of outcomes. Where applicable law creates rights relating to significant automated decisions, those rights remain available.</p>
      <h2>14. Updates and complaints</h2>
      <p>We may update this notice as our operations, service providers or applicable laws change. Material changes will be communicated where required. If you are concerned about how your information is used, please contact us first; you may also complain to the appropriate supervisory authority, including the <a href="https://ndpc.gov.ng/" rel="noopener" target="_blank">Nigeria Data Protection Commission</a> where applicable.</p>
<?php elseif ($legalPage === 'terms'): ?>
      <p class="iw-legal-lead">These Terms govern use of iWORKS GLOBAL LTD’s public website, customer portal and business-support services. Please read the service-specific description, quotation and checkout details alongside these Terms before placing an order.</p>
      <h2>1. About iWORKS</h2>
      <p>iWORKS GLOBAL LTD (“iWORKS”, “we”, “us”) provides assistance with business registration and compliance workflows, document preparation, website and digital services and related support. We are a private service provider, not Nigeria’s Corporate Affairs Commission (CAC), the US Internal Revenue Service, a government agency or a government-approval body.</p>
      <h2>2. Eligibility and account responsibilities</h2>
      <p>Customers must be legally able to enter the transaction, supply accurate contact and identification details, keep credentials confidential and promptly report suspicious activity. You are responsible for activity conducted through your credentials unless applicable law provides otherwise. We may request additional verification before accepting or continuing an order.</p>
      <h2>3. Ordering and scope</h2>
      <p>Each purchased service is governed by its published description and any written, accepted scope or quote. Work may depend on receipt of required documents, confirmations and payment. We may clarify or decline requests that cannot lawfully or practically be fulfilled and explain any applicable charges or refund options. Unrequested extras will not be charged without appropriate notice and authorization.</p>
      <h2>4. Government filings and third parties</h2>
      <p>Business names, Nigerian company incorporation, trademark filings and any US entity formation are subject to applicable registry, state and agency rules, identification requirements and review. Name availability, approval, EIN issuance, account opening, timelines and regulatory outcomes are not guaranteed. iWORKS cannot waive government requirements. Registry, government, registered-agent, stamp-duty, tax, authentication and third-party costs are included only when clearly itemized in your specific quote or checkout.</p>
      <h2>5. Cross-border orders</h2>
      <p>Customers located outside Nigeria can enquire about Nigerian incorporation and other remotely delivered services. Local address, directorship, identity, beneficial-ownership, tax, investment or professional requirements may still apply. US LLC formation enquiries from Nigerian customers are subject to the selected US state and federal reporting requirements; incorporation alone does not grant residence, work authorization, bank accounts or tax exemption. We may recommend an independent local lawyer or licensed tax adviser when the matter needs regulated advice.</p>
      <h2>6. Customer documents and instructions</h2>
      <p>You confirm that submitted information is accurate, authentic and supplied with authority. We rely on your timely responses to clarify registration details, corporate structure, shareholding, beneficial ownership and regulated activities. Delays or rejection caused by missing, inconsistent or inaccurate documents may require further work or additional official fees, subject to advance explanation where practicable.</p>
      <h2>7. Fees, currencies and payment</h2>
      <p>Prices are shown in the currency and scope selected during checkout or quotation. Available payment methods and NGN/USD options depend on the selected service and provider. Currency-conversion rates, bank charges and third-party processing fees may apply when clearly disclosed at checkout. Payment references and receipts may be stored to reconcile orders, prevent fraud and meet accounting obligations.</p>
      <h2>8. Refunds and cancellation</h2>
      <p>For an unstarted or cancelled request, contact support promptly with your order reference. Refund eligibility depends on work completed, fees irrevocably paid to government or third-party providers, any agreed service-specific cancellation terms and applicable consumer law. We will review requests individually and explain any deductions supported by the transaction record. Nothing here excludes a mandatory refund or other non-waivable right under applicable law.</p>
      <h2>9. Estimated delivery</h2>
      <p>All time estimates are indicative unless expressly agreed as binding. Registry processing, manual verification, provider outages, holidays, client revisions and third-party assessments may affect the schedule. We will provide updates through available portal status, communication and support channels.</p>
      <h2>10. Digital projects, revisions and intellectual property</h2>
      <p>For web development, design, content or hosting work, deliverables, technical requirements, revisions, milestone acceptance, third-party licences and transfer of project rights are defined in the accepted project scope. Unless otherwise agreed in writing, a customer’s pre-existing materials remain theirs; third-party software, stock photography, fonts and provider services remain subject to their own licences. Do not upload material you lack permission to use.</p>
      <h2>11. Security and acceptable use</h2>
      <p>Do not upload malicious code, attempt access to other clients’ data, misuse payment services, impersonate another person, use falsified documents or request unlawful activity. We may suspend access when reasonably necessary to prevent abuse or comply with law, subject to fair notice where possible.</p>
      <h2>12. Communications and electronic records</h2>
      <p>You consent to receive transaction-related notices through the contact methods you provide, subject to applicable law. Portal records, receipts and correspondence may be used to establish the scope and status of instructions. Please keep your email and phone information current.</p>
      <h2>13. Privacy and confidentiality</h2>
      <p>We handle personal information under our <a href="<?= e(public_url('privacy')) ?>">Privacy Policy</a> and <a href="<?= e(public_url('cookies')) ?>">Cookie Policy</a>. Business documents and client communications are not public case studies unless appropriately authorized. Confidentiality duties do not prevent disclosures required by law or regulator processes.</p>
      <h2>14. Responsibility and limitations</h2>
      <p>We will use reasonable care and skill in delivering the services we agree to provide. We are not responsible for the independent decisions or systems of a government agency or external provider, except to the extent the law or our own misconduct makes us responsible. Nothing in these Terms excludes liability that cannot legally be limited, including mandatory consumer protections.</p>
      <h2>15. Complaints and disputes</h2>
      <p>Please contact <a href="mailto:support@iworksgt.com">support@iworksgt.com</a> with your order reference and a clear description of the issue. We aim to review disputes and seek a fair resolution. Questions of governing law and jurisdiction will be handled under applicable Nigerian law and mandatory rules protecting consumers in their country where such rules apply; mandatory statutory rights are not waived.</p>
      <h2>16. Changes and contact</h2>
      <p>We may revise these Terms for future transactions. Where changes materially affect an existing agreement, we will follow applicable contractual and notice requirements; updates do not automatically erase rights acquired under an earlier contract. For enquiries, use our <a href="<?= e(public_url('contact')) ?>">Contact page</a>.</p>
<?php else: ?>
      <p class="iw-legal-lead">This page explains the cookies and similar technologies used to operate the iWORKS public website and customer portal, and how you can manage them.</p>
      <h2>1. What cookies and local storage do</h2>
      <p>Cookies are small identifiers stored by your browser and sent with certain requests. Browser session storage and local storage can also keep preferences or support-chat state on your device. These technologies can help the site recognize a session, protect login operations, remember a currency selection and keep a conversation available while you navigate.</p>
      <h2>2. Necessary website and account storage</h2>
      <p>We use strictly necessary session and security mechanisms for authentication, CSRF protection, fraud prevention and safe portal access. Disabling these features may prevent login, payments or service tracking from operating correctly.</p>
      <h2>3. Functional preferences and chat continuity</h2>
      <p>Where used, a currency preference such as <code>pay_currency</code> can remember an available display or payment currency. Our support widget may use browser storage to maintain a chat identifier, saved conversation state or dismissed consultation suggestion. These features support an action you initiate; browser settings can clear the stored data.</p>
      <h2>4. Third-party providers</h2>
      <p>Some features, including payment gateways, external sign-in, embedded media or externally hosted fonts, may involve requests to third-party services. Those services may set their own necessary cookies or process device/network information when used. Check the provider’s own privacy information during its service flow.</p>
      <h2>5. Analytics and advertising technologies</h2>
      <p>We do not describe optional analytics or advertising cookies as essential. If non-essential tracking is introduced, it must be subject to clear information, prior consent where required, and usable refusal and withdrawal mechanisms before activation. A policy page alone does not grant consent to such tracking.</p>
      <h2>6. How long information lasts</h2>
      <p>Session cookies normally expire with the browser session or server-configured session lifecycle. A functional preference or support-chat item may remain until its programmed expiration, your browser removes it or you clear site data. Third-party services have their own retention and expiration policies.</p>
      <h2>7. Your controls</h2>
      <p>Most browsers allow you to inspect, delete or block cookies and clear site storage through their privacy settings. Blocking necessary cookies can interrupt requested account functions. Where consent-based tracking is introduced, an in-site mechanism will be provided to reject or change that choice as required by law.</p>
      <h2>8. International visitors</h2>
      <p>Applicable rules can differ in Nigeria, the UK, EU/EEA, US, Canada, Australia and elsewhere. We will assess which rules govern each use of optional storage and consent. This policy does not remove rights given by mandatory local law.</p>
      <h2>9. Related policies and contact</h2>
      <p>See our <a href="<?= e(public_url('privacy')) ?>">Privacy Policy</a> for personal-data processing and our <a href="<?= e(public_url('terms')) ?>">Terms &amp; Conditions</a> for service use. For questions about cookies or data requests, email <a href="mailto:support@iworksgt.com">support@iworksgt.com</a>.</p>
<?php endif; ?>
    </article>
    <nav class="iw-legal-related" aria-label="Other legal documents">
      <span>Related policies:</span>
      <?php foreach ($legalTitles as $key => $label): ?>
        <?php if ($key !== $legalPage): ?><a href="<?= e(public_url($key)) ?>"><?= e($label) ?></a><?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </div>
</main>
