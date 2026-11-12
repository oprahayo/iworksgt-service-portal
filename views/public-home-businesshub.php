<?php
/**
 * iWorks public homepage — service-first, guided by the approachable layout
 * patterns of The Business Hub. Original copy and independent iWorks styling.
 * All commercial offers come from the existing published portal catalog.
 */
require_once __DIR__ . '/../app/public-photo-config.php';
$hub = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (!is_array($block)) { continue; }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if ($type !== '' && !isset($hub[$type])) {
        $hub[$type] = is_array($block['data'] ?? null) ? $block['data'] : [];
    }
}
$hero = $hub['hero'] ?? [];
$featured = $hub['featured_services'] ?? [];
$benefits = $hub['benefit_cards'] ?? [];
$workflow = $hub['how_it_works'] ?? [];
$cases = $hub['case_studies'] ?? [];
$faq = $hub['faq'] ?? [];
$banner = $hub['cta_banner'] ?? [];

$heroHeadline = trim((string) ($hero['headline'] ?? 'Start and grow your business with iWorks Global.'));
$heroLead = trim((string) ($hero['subheadline'] ?? 'Register a business, protect your brand and build online with convenient support from one portal.'));
$heroKicker = trim((string) ($hero['kicker'] ?? 'BUSINESS SUPPORT, MADE SIMPLE'));
$heroAsset = !empty($hero['imageId']) ? get_media_asset((int) $hero['imageId']) : null;
$heroImage = trim((string) ($heroAsset['url'] ?? '')) ?: iworks_stock_photo_url('consultation', 1200);
$primary = is_array($hero['primaryCta'] ?? null) ? $hero['primaryCta'] : [];
$primaryHref = normalize_public_href((string) ($primary['href'] ?? public_url('services')), public_url('services'));
$primaryLabel = trim((string) ($primary['label'] ?? 'Explore services')) ?: 'Explore services';

$services = array_values(array_filter(is_array($publicServices ?? null) ? $publicServices : [], static function ($service): bool {
    if (!is_array($service)) { return false; }
    $name = strtolower(trim((string) ($service['name'] ?? '')));
    $desc = strtolower(trim((string) ($service['short_description'] ?? '')));
    $slug = trim((string) ($service['slug'] ?? ''));
    if ($name === '' || $slug === '') { return false; }
    if ($name === 'custom service' && (str_contains($desc, 'placeholder') || (float) ($service['price'] ?? 0) <= 0)) { return false; }
    foreach (['bank account', 'banking', 'loan', 'credit facility', 'payment gateway', 'payment processing', 'wallet'] as $banned) {
        if (str_contains($name, $banned)) { return false; }
    }
    return true;
}));
$priority = ['business name registration', 'company registration', 'trademark', 'website design', 'domain', 'graphics design', 'seo'];
usort($services, static function (array $a, array $b) use ($priority): int {
    $rank = static function (array $item) use ($priority): int {
        $name = strtolower((string) ($item['name'] ?? ''));
        foreach ($priority as $i => $needle) { if (str_contains($name, $needle)) { return $i; } }
        return 99;
    };
    return ($rank($a) <=> $rank($b)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});
$featuredIds = array_values(array_filter(array_map('intval', is_array($featured['serviceIds'] ?? null) ? $featured['serviceIds'] : [])));
if ($featuredIds !== []) {
    $picked = [];
    foreach ($featuredIds as $id) {
        foreach ($services as $service) {
            if ((int) ($service['id'] ?? 0) === $id) { $picked[$id] = $service; break; }
        }
    }
    $pickedSet = array_fill_keys(array_keys($picked), true);
    $services = array_merge(
        array_values($picked),
        array_values(array_filter(
            $services,
            static fn(array $service): bool => !isset($pickedSet[(int) ($service['id'] ?? 0)])
        ))
    );
}
$findServiceUrl = static function (string $query, string $fallbackCategory = '') use ($services): string {
    foreach ($services as $service) {
        if (str_contains(strtolower((string) ($service['name'] ?? '')), $query)) {
            return public_url('service', ['slug' => (string) $service['slug']]);
        }
    }
    return $fallbackCategory !== '' ? public_url('services', ['category' => $fallbackCategory]) : public_url('services');
};
$bnLink = $findServiceUrl('business name registration', 'business-registration');
$companyLink = $findServiceUrl('company registration', 'business-registration');
$trademarkLink = $findServiceUrl('trademark', 'trademark');
$webLink = $findServiceUrl('website design', 'growth');
$domainLink = $findServiceUrl('domain', 'growth');

$photosForService = static function (array $service): string {
    $name = strtolower((string) ($service['name'] ?? '') . ' ' . (string) ($service['category'] ?? ''));
    if (str_contains($name, 'trademark') || str_contains($name, 'compliance')) { return 'strategy'; }
    if (str_contains($name, 'web') || str_contains($name, 'hosting') || str_contains($name, 'domain') || str_contains($name, 'graphic') || str_contains($name, 'seo') || str_contains($name, 'digital')) { return 'digital'; }
    if (str_contains($name, 'training')) { return 'meeting'; }
    if (str_contains($name, 'vehicle') || str_contains($name, 'tracking')) { return 'tracking'; }
    return 'registration';
};
$benefitItems = array_values(array_filter(is_array($benefits['items'] ?? null) ? $benefits['items'] : [], 'is_array'));
if ($benefitItems === []) {
    $benefitItems = [
        ['title' => 'One place to start', 'text' => 'Choose services and see the information you need before ordering.', 'icon' => 'bi-ui-checks'],
        ['title' => 'Simple online process', 'text' => 'Submit details, documents and payment through your portal account.', 'icon' => 'bi-laptop'],
        ['title' => 'Keep track of progress', 'text' => 'See the status of your request, conversations and final documents.', 'icon' => 'bi-clipboard-check'],
    ];
}
$processSteps = array_values(array_filter(is_array($workflow['steps'] ?? null) ? $workflow['steps'] : [], 'is_array'));
if ($processSteps === []) {
    $processSteps = [
        ['title' => 'Choose your service', 'text' => 'Browse the catalog and see what your request includes.'],
        ['title' => 'Send what we need', 'text' => 'Complete checkout and submit the relevant details.'],
        ['title' => 'Follow through online', 'text' => 'Track messages, updates and final delivery from your account.'],
    ];
}
$caseItems = [];
foreach (is_array($cases['items'] ?? null) ? $cases['items'] : [] as $case) {
    if (!is_array($case)) { continue; }
    $asset = !empty($case['imageId']) ? get_media_asset((int) $case['imageId']) : null;
    if (empty($asset['url']) || trim((string) ($case['name'] ?? '')) === '') { continue; }
    $caseItems[] = [
        'name' => trim((string) $case['name']),
        'image' => (string) $asset['url'],
        'type' => trim((string) ($case['category'] ?? 'Client project')),
        'link' => normalize_public_href(trim((string) ($case['link'] ?? '')), public_url('case-studies')),
    ];
    if (count($caseItems) >= 3) { break; }
}
$faqs = array_slice(array_values(array_filter(is_array($faq['items'] ?? null) ? $faq['items'] : [], 'is_array')), 0, 6);
?>
<main class="public-main iworks-bhub" id="main-content">
  <?php if ($msg = flash('success')): ?><div class="container public-container pt-3"><div class="alert alert-success"><?= e($msg) ?></div></div><?php endif; ?>
  <?php if ($msg = flash('error')): ?><div class="container public-container pt-3"><div class="alert alert-danger"><?= e($msg) ?></div></div><?php endif; ?>

  <div class="bhub-topline">
    <div class="container public-container bhub-topline-inner">
      <span><i class="bi bi-building-check" aria-hidden="true"></i> Want to register your business? Start with the right CAC service.</span>
      <a href="<?= e($bnLink) ?>">Start registration <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </div>

  <section class="bhub-hero" aria-labelledby="bhub-title">
    <div class="container public-container bhub-hero-layout">
      <div class="bhub-hero-copy">
        <span class="bhub-eyebrow"><?= e($heroKicker) ?></span>
        <h1 id="bhub-title"><?= e($heroHeadline) ?></h1>
        <p class="bhub-hero-description"><?= e($heroLead) ?></p>
        <div class="bhub-hero-actions">
          <a href="<?= e($bnLink) ?>" class="bhub-button bhub-button-orange">Register your business <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <a href="<?= e($primaryHref) ?>" class="bhub-button bhub-button-outline"><?= e($primaryLabel) ?></a>
        </div>
        <div class="bhub-hero-foot"><span><i class="bi bi-check2-circle"></i> Online ordering</span><span><i class="bi bi-check2-circle"></i> Document uploads</span><span><i class="bi bi-check2-circle"></i> Job tracking</span></div>
      </div>
      <div class="bhub-hero-visual">
        <div class="bhub-hero-blob" aria-hidden="true"></div>
        <img src="<?= e($heroImage) ?>" alt="Business professionals discussing registration and business documents" fetchpriority="high" decoding="async" width="780" height="780">
        <div class="bhub-hero-sticker"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span><strong>Simple from the start</strong><small>Submit your request online</small></span></div>
      </div>
    </div>
  </section>

  <section class="bhub-start" aria-labelledby="bhub-start-title">
    <div class="container public-container">
      <div class="bhub-section-head">
        <span class="bhub-eyebrow">BUSINESS REGISTRATION</span>
        <h2 id="bhub-start-title">What would you like to register?</h2>
        <p>Select a starting point. We’ll guide you to the service details and requirements.</p>
      </div>
      <div class="bhub-start-options">
        <a href="<?= e($bnLink) ?>"><span class="bhub-icon"><i class="bi bi-shop-window"></i></span><strong>Business Name</strong><small>For independent businesses and sole proprietors</small><i class="bi bi-arrow-up-right bhub-option-arrow"></i></a>
        <a href="<?= e($companyLink) ?>"><span class="bhub-icon"><i class="bi bi-buildings"></i></span><strong>Limited Company</strong><small>For incorporated businesses and growing teams</small><i class="bi bi-arrow-up-right bhub-option-arrow"></i></a>
        <a href="<?= e(public_url('services', ['category' => 'business-registration'])) ?>"><span class="bhub-icon"><i class="bi bi-diagram-3"></i></span><strong>Other CAC Services</strong><small>Trustees, non-profits and other structures</small><i class="bi bi-arrow-up-right bhub-option-arrow"></i></a>
      </div>
    </div>
  </section>

  <section class="bhub-bouquet" aria-labelledby="bhub-bouquet-title">
    <div class="container public-container">
      <div class="bhub-section-head bhub-head-center">
        <span class="bhub-eyebrow">THE iWORKS ADVANTAGE</span>
        <h2 id="bhub-bouquet-title">Business support, made less complicated.</h2>
        <p>Starting a business is just the beginning. Find support to register, build your brand, and move forward with clarity.</p>
      </div>
      <div class="bhub-benefit-grid">
        <?php foreach (array_slice($benefitItems, 0, 3) as $index => $item): ?>
          <?php
          $icons = ['bi-file-earmark-check', 'bi-person-check', 'bi-laptop'];
          $icon = (string) ($item['icon'] ?? $icons[$index] ?? 'bi-check-circle');
          if (!preg_match('/^bi-[a-z0-9-]+$/', $icon)) { $icon = 'bi-check-circle'; }
          ?>
          <article><span class="bhub-benefit-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span><h3><?= e((string) ($item['title'] ?? 'Support')) ?></h3><p><?= e((string) ($item['text'] ?? '')) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="bhub-feature bhub-registration" aria-labelledby="bhub-reg-title">
    <div class="container public-container bhub-feature-grid">
      <div class="bhub-feature-photo">
        <img src="<?= e(iworks_stock_photo_url('registration', 1200)) ?>" alt="Paperwork and laptop on a business office desk" width="640" height="520" loading="lazy" decoding="async">
        <span class="bhub-image-caption">Illustrative business photography</span>
      </div>
      <div class="bhub-feature-copy">
        <span class="bhub-eyebrow">LET'S MAKE IT OFFICIAL</span>
        <h2 id="bhub-reg-title">Register your business with less stress.</h2>
        <p>Choose a business registration service, review the documents needed, and submit your details through iWorks. Your request stays connected to your portal account for updates and delivery.</p>
        <ul><li><i class="bi bi-check-circle-fill"></i> Business names and company registration</li><li><i class="bi bi-check-circle-fill"></i> Requirements and documentation support</li><li><i class="bi bi-check-circle-fill"></i> Status updates in your portal</li></ul>
        <a href="<?= e($bnLink) ?>" class="bhub-button bhub-button-orange">Start registration <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </section>

  <section class="bhub-services" id="services" aria-labelledby="bhub-services-title">
    <div class="container public-container">
      <div class="bhub-section-heading-row">
        <div><span class="bhub-eyebrow">OUR SERVICES</span><h2 id="bhub-services-title"><?= e(trim((string) ($featured['title'] ?? '')) ?: 'Everything your business needs') ?></h2></div>
        <p><?= e(trim((string) ($featured['subtitle'] ?? '')) ?: 'Practical services to help you launch, protect and grow your business.') ?></p>
      </div>
      <?php if ($services !== []): ?>
        <div class="bhub-service-grid">
          <?php foreach (array_slice($services, 0, 6) as $service): ?>
            <?php
            $name = trim((string) ($service['name'] ?? ''));
            $description = trim((string) strip_tags((string) ($service['short_description'] ?? '')));
            $description = preg_replace('/\s+/', ' ', $description) ?? $description;
            if (str_contains(strtolower($name), 'launch a company package')) {
                $description = 'Company formation, compliance documentation, branding and launch support.';
            } elseif (stripos($description, 'banking') !== false) {
                $description = trim((string) str_ireplace(['banking,', ', banking', 'banking'], '', $description));
            }
            if ($description === '') { $description = 'Explore the requirements and included deliverables for this service.'; }
            $url = public_url('service', ['slug' => (string) $service['slug']]);
            $asset = !empty($service['featured_image_id']) ? get_media_asset((int) $service['featured_image_id']) : null;
            $photo = !empty($asset['url']) ? (string) $asset['url'] : iworks_stock_photo_url($photosForService($service), 760);
            ?>
            <article class="bhub-service-card">
              <a class="bhub-service-media" href="<?= e($url) ?>" aria-label="View <?= e($name) ?>"><img src="<?= e($photo) ?>" alt="" width="640" height="420" loading="lazy" decoding="async"></a>
              <div class="bhub-service-body"><h3><a href="<?= e($url) ?>"><?= e($name) ?></a></h3><p><?= e($description) ?></p><a href="<?= e($url) ?>" class="bhub-service-cta">Explore service <i class="bi bi-arrow-up-right"></i></a></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="bhub-no-services">Services are being prepared. <a href="<?= e(public_url('contact')) ?>">Contact our team</a> for guidance.</p>
      <?php endif; ?>
      <div class="bhub-service-more"><a href="<?= e(public_url('services')) ?>" class="bhub-button bhub-button-outline">Browse all services <i class="bi bi-arrow-right"></i></a></div>
    </div>
  </section>

  <section class="bhub-feature bhub-digital" aria-labelledby="bhub-digital-title">
    <div class="container public-container bhub-feature-grid bhub-feature-reverse">
      <div class="bhub-feature-copy">
        <span class="bhub-eyebrow">YOUR DIGITAL FOUNDATION</span>
        <h2 id="bhub-digital-title">Build a business people can find and trust.</h2>
        <p>Go beyond registration. Establish your online presence with a professional website, domain, hosting, brand identity and ongoing digital support.</p>
        <ul><li><i class="bi bi-check-circle-fill"></i> Websites and online storefronts</li><li><i class="bi bi-check-circle-fill"></i> Domain names and web hosting</li><li><i class="bi bi-check-circle-fill"></i> Brand design and marketing services</li></ul>
        <a href="<?= e($webLink) ?>" class="bhub-button bhub-button-orange">Explore digital services <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="bhub-feature-photo">
        <img src="<?= e(iworks_stock_photo_url('digital', 1200)) ?>" alt="Real web developer workplace with laptop and mobile phone" width="640" height="520" loading="lazy" decoding="async">
        <span class="bhub-image-caption">Illustrative business photography</span>
      </div>
    </div>
  </section>

  <section class="bhub-how" id="how-it-works" aria-labelledby="bhub-how-title">
    <div class="container public-container">
      <div class="bhub-section-head bhub-head-center">
        <span class="bhub-eyebrow">HOW IT WORKS</span>
        <h2 id="bhub-how-title">Simple from first click to final delivery.</h2>
        <p>Make a request in minutes and track each stage online. Actual processing times depend on the service.</p>
      </div>
      <div class="bhub-how-grid">
        <?php foreach (array_slice($processSteps, 0, 3) as $index => $step): ?>
          <article class="bhub-how-step"><span class="bhub-step-num"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e((string) ($step['title'] ?? '')) ?></h3><p><?= e((string) ($step['text'] ?? '')) ?></p></article>
        <?php endforeach; ?>
      </div>
      <div class="bhub-how-action"><a href="<?= e(public_url('track')) ?>">Already placed an order? Track it here <i class="bi bi-arrow-right"></i></a></div>
    </div>
  </section>

  <?php if ($caseItems !== []): ?>
  <section class="bhub-work" aria-labelledby="bhub-work-title">
    <div class="container public-container">
      <div class="bhub-section-heading-row"><div><span class="bhub-eyebrow">OUR WORK</span><h2 id="bhub-work-title"><?= e(trim((string) ($cases['title'] ?? '')) ?: 'Some projects we have worked on.') ?></h2></div><a href="<?= e(public_url('case-studies')) ?>" class="bhub-text-link">More projects <i class="bi bi-arrow-up-right"></i></a></div>
      <div class="bhub-work-grid">
        <?php foreach ($caseItems as $case): ?>
          <article><a href="<?= e($case['link']) ?>"><img src="<?= e($case['image']) ?>" alt="<?= e($case['name']) ?>" loading="lazy" width="520" height="340"><span><?= e($case['type']) ?></span><h3><?= e($case['name']) ?></h3></a></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="bhub-reassure" aria-labelledby="bhub-reassure-title">
    <div class="container public-container bhub-reassure-layout">
      <div><span class="bhub-eyebrow">SUPPORT THROUGH EVERY STEP</span><h2 id="bhub-reassure-title">You don't need to figure everything out alone.</h2><p>Ask questions before you start. See what is required when you order. Follow conversations and updates until your request is completed.</p><a class="bhub-text-link" href="<?= e(public_url('about')) ?>">Get to know iWorks <i class="bi bi-arrow-up-right"></i></a></div>
      <div class="bhub-reassure-photo"><img src="<?= e(iworks_stock_photo_url('strategy', 1100)) ?>" alt="Business professionals reviewing paperwork together" width="600" height="470" loading="lazy"></div>
    </div>
  </section>

  <?php if ($faqs !== []): ?>
  <section class="bhub-faq" id="faqs" aria-labelledby="bhub-faq-title">
    <div class="container public-container bhub-faq-grid">
      <div><span class="bhub-eyebrow">FREQUENTLY ASKED QUESTIONS</span><h2 id="bhub-faq-title"><?= e(trim((string) ($faq['title'] ?? '')) ?: 'Questions? We have answers.') ?></h2><p>Need more information? Our team can point you in the right direction.</p><a href="<?= e(public_url('contact')) ?>" class="bhub-text-link">Talk to us <i class="bi bi-arrow-up-right"></i></a></div>
      <div class="bhub-faq-items">
        <?php foreach ($faqs as $item): ?>
          <?php $question = trim((string) ($item['q'] ?? '')); $answer = trim((string) ($item['a'] ?? '')); if ($question === '') { continue; } ?>
          <details><summary><?= e($question) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary><p><?= e($answer) ?></p></details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="bhub-end">
    <div class="container public-container bhub-end-grid">
      <div><span class="bhub-eyebrow">READY WHEN YOU ARE</span><h2><?= e(trim((string) ($banner['headline'] ?? '')) ?: 'Let’s build what’s next for your business.') ?></h2><p><?= e(trim((string) ($banner['text'] ?? '')) ?: 'Choose a service, submit your request and follow progress in one place.') ?></p></div>
      <div class="bhub-end-action"><a href="<?= e(public_url('services')) ?>" class="bhub-button bhub-button-orange">Get started now <i class="bi bi-arrow-right"></i></a><a href="<?= e(public_url('contact')) ?>" class="bhub-end-contact">Need guidance? Contact us</a></div>
    </div>
  </section>
</main>