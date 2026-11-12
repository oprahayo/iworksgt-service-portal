<?php
/**
 * Contact page with the existing website_lead handler and CSRF protection.
 * Published CMS data remains the source for copy and contact methods.
 */
$contactHero = [];
$contactForm = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (!is_array($block)) { continue; }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if ($type === 'hero' && $contactHero === []) { $contactHero = is_array($block['data'] ?? null) ? $block['data'] : []; }
    if ($type === 'contact_form' && $contactForm === []) { $contactForm = is_array($block['data'] ?? null) ? $block['data'] : []; }
}
$heading = trim((string) ($contactHero['headline'] ?? ''));
$defaultHeadings = ['Talk to the iWorks team.', 'Contact us', 'Talk to our experts'];
if ($heading === '' || in_array($heading, $defaultHeadings, true)) { $heading = 'Contact iWorks'; }
$lead = trim((string) ($contactHero['subheadline'] ?? ''));
if ($lead === '') { $lead = 'Tell us what you need. We will help you find the right service.'; }
$email = trim((string) ($contactForm['email'] ?? 'support@iworksgt.com'));
$phone = trim((string) ($contactForm['phone'] ?? ''));
$whatsapp = trim((string) ($contactForm['whatsapp'] ?? ''));
if (preg_match('/^\+?234\s*806\s*000\s*0000$/', $phone)) { $phone = ''; }
if (str_contains($whatsapp, '2348060000000')) { $whatsapp = ''; }
$submitLabel = trim((string) ($contactForm['submitLabel'] ?? 'Send message')) ?: 'Send message';
?>
<main class="public-main iw-contact" id="main-content">
  <?php if ($message = flash('success')): ?><div class="container public-container pt-3"><div class="alert alert-success"><?= e($message) ?></div></div><?php endif; ?>
  <?php if ($error = flash('error')): ?><div class="container public-container pt-3"><div class="alert alert-danger"><?= e($error) ?></div></div><?php endif; ?>
  <section class="iw-inner-intro">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Contact</span></nav>
      <h1><?= e($heading) ?></h1>
      <p><?= e($lead) ?></p>
    </div>
  </section>
  <section class="iw-contact-content">
    <div class="container public-container iw-contact-grid">
      <div class="iw-contact-information">
        <h2>How can we help?</h2>
        <p>Questions about registration, trademarks or digital services? Send a message using the form.</p>
        <?php if ($email !== ''): ?><a href="mailto:<?= e($email) ?>"><i class="bi bi-envelope" aria-hidden="true"></i><?= e($email) ?></a><?php endif; ?>
        <?php if ($phone !== ''): ?><a href="tel:<?= e($phone) ?>"><i class="bi bi-telephone" aria-hidden="true"></i><?= e($phone) ?></a><?php endif; ?>
        <?php if ($whatsapp !== ''): ?><a href="<?= e($whatsapp) ?>" rel="noopener noreferrer" target="_blank"><i class="bi bi-whatsapp" aria-hidden="true"></i>WhatsApp</a><?php endif; ?>
        <p class="iw-contact-help">Already ordered? <a href="<?= e(public_url('track')) ?>">Track your job</a> or <a href="<?= e(portal_url('login')) ?>">sign in</a>.</p>
      </div>
      <div class="iw-contact-form-wrap">
        <h2>Send an enquiry</h2>
        <form method="post" action="<?= e(public_url('contact')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="website_lead">
          <div class="iw-contact-fields">
            <div><label for="iwLeadName">Full name <span aria-hidden="true">*</span></label><input id="iwLeadName" name="full_name" autocomplete="name" required maxlength="150"></div>
            <div><label for="iwLeadEmail">Email <span aria-hidden="true">*</span></label><input id="iwLeadEmail" name="email" type="email" autocomplete="email" required maxlength="254"></div>
            <div><label for="iwLeadPhone">Phone (optional)</label><input id="iwLeadPhone" name="phone" autocomplete="tel" type="tel"></div>
            <div><label for="iwLeadBusiness">Business name (optional)</label><input id="iwLeadBusiness" name="company_name" autocomplete="organization"></div>
            <div class="iw-contact-full"><label for="iwLeadMessage">How can we help? <span aria-hidden="true">*</span></label><textarea id="iwLeadMessage" name="message" rows="5" required maxlength="5000" placeholder="Tell us briefly what you need."></textarea></div>
          </div>
          <button type="submit"><?= e($submitLabel) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
    </div>
  </section>
</main>