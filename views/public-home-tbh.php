<?php
/**
 * Fast, CMS-backed, service-first public homepage.
 * All service links use the existing published catalogue and portal routes.
 */
$home = [];
foreach ($layout as $block) {
    if (!is_array($block)) { continue; }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if (!isset($home[$type])) {
        $home[$type] = is_array($block['data'] ?? null) ? $block['data'] : [];
    }
}
$hero = $home['hero'] ?? [];
$process = $home['how_it_works'] ?? [];
$faq = $home['faq'] ?? [];
$featured = $home['featured_services'] ?? [];
$banner = $home['cta_banner'] ?? [];
$caseData = $home['case_studies'] ?? [];
$caseItems = [];
foreach (is_array($caseData['items'] ?? null) ? $caseData['items'] : [] as $case) {
    if (!is_array($case)) { continue; }
    $name = trim((string) ($case['name'] ?? ''));
    $imageId = (int) ($case['imageId'] ?? 0);
    $image = $imageId > 0 ? get_media_asset($imageId) : null;
    if ($name === '' || empty($image['url'])) { continue; }
    $caseItems[] = [
        'name' => $name,
        'category' => trim((string) ($case['category'] ?? 'Project')),
        'image' => (string) $image['url'],
        'link' => trim((string) ($case['link'] ?? '')),
    ];
    if (count($caseItems) >= 3) { break; }
}
$headline = trim((string) ($hero['headline'] ?? 'Launch and grow your business with iWorks Global.'));
$subheadline = trim((string) ($hero['subheadline'] ?? 'Business registration, trademark and digital services delivered remotely.'));
$heroMedia = (int) ($hero['imageId'] ?? 0) > 0 ? get_media_asset((int) $hero['imageId']) : null;
$primary = is_array($hero['primaryCta'] ?? null) ? $hero['primaryCta'] : [];
$primaryHref = normalize_public_href((string) ($primary['href'] ?? public_url('services')), public_url('services'));
$primaryLabel = trim((string) ($primary['label'] ?? 'Browse services')) ?: 'Browse services';

// iWorks does not provide banking products. Exclude those from homepage promotions.
$homeServices = array_values(array_filter($publicServices, static function ($service): bool {
    $description = strtolower(implode(' ', [(string) ($service['name'] ?? ''), (string) ($service['short_description'] ?? '')]));
    foreach (['banking', 'bank account', 'account opening', 'business loan', 'quick loan', 'wallet service', 'payment gateway'] as $term) {
        if (str_contains($description, $term)) { return false; }
    }
    return true;
}));
$priorityTerms = ['business name registration', 'company registration', 'trademark', 'website design', 'domain', 'graphics design', 'seo'];
usort($homeServices, static function (array $a, array $b) use ($priorityTerms): int {
    $rank = static function ($service) use ($priorityTerms): int {
        $name = strtolower((string) ($service['name'] ?? ''));
        foreach ($priorityTerms as $i => $term) {
            if (str_contains($name, $term)) { return $i; }
        }
        return 100;
    };
    return ($rank($a) <=> $rank($b)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});
$featuredIds = array_values(array_filter(array_map('intval', is_array($featured['serviceIds'] ?? null) ? $featured['serviceIds'] : [])));
if ($featuredIds !== []) {
    $selectedSet = array_flip($featuredIds);
    $selectedServices = array_values(array_filter($homeServices, static fn(array $service): bool => isset($selectedSet[(int) ($service['id'] ?? 0)])));
    if ($selectedServices !== []) {
        $homeServices = array_merge($selectedServices, array_values(array_filter($homeServices, static fn(array $service): bool => !isset($selectedSet[(int) ($service['id'] ?? 0)]))));
    }
}
$visibleServices = array_slice($homeServices, 0, 6);
$registrationLink = public_url('services', ['category' => 'business-registration']);
foreach ($homeServices as $service) {
    if (str_contains(strtolower((string) ($service['name'] ?? '')), 'business name registration')) {
        $registrationLink = public_url('service', ['slug' => (string) $service['slug']]);
        break;
    }
}
$steps = is_array($process['steps'] ?? null) ? array_slice($process['steps'], 0, 3) : [];
if ($steps === []) {
    $steps = [
        ['title' => 'Choose your service', 'text' => 'Pick what you need from our service catalogue.'],
        ['title' => 'Share details and pay', 'text' => 'Checkout securely and submit the relevant requirements.'],
        ['title' => 'Track and receive delivery', 'text' => 'Follow your job progress and receive your documents online.'],
    ];
}
$faqItems = is_array($faq['items'] ?? null) ? array_slice($faq['items'], 0, 5) : [];
?>
<main class="public-main iworks-launch-home" id="main-content">
  <?php if ($message = flash('success')): ?><div class="container public-container pt-3"><div class="alert alert-success mb-0"><?= e($message) ?></div></div><?php endif; ?>
  <?php if ($error = flash('error')): ?><div class="container public-container pt-3"><div class="alert alert-danger mb-0"><?= e($error) ?></div></div><?php endif; ?>
  <section class="launch-hero" aria-labelledby="launch-headline">
    <div class="container public-container launch-hero-grid">
      <div class="launch-hero-copy">
        <p class="launch-eyebrow">Business services, made simpler</p>
        <h1 id="launch-headline"><?= e($headline) ?></h1>
        <p class="launch-intro"><?= e($subheadline) ?></p>
        <div class="launch-hero-actions">
          <a class="launch-btn launch-btn-primary" href="<?= e($primaryHref) ?>"><?= e($primaryLabel) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <a class="launch-btn launch-btn-text" href="<?= e(public_url('track')) ?>">Track your job <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <p class="launch-hero-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Clear requirements, secure payment and remote delivery.</p>
      </div>
      <div class="launch-hero-art" aria-hidden="true">
        <?php if (!empty($heroMedia['url'])): ?>
          <img class="launch-hero-photo" src="<?= e((string) $heroMedia['url']) ?>" alt="" fetchpriority="high" width="640" height="530" decoding="async">
        <?php else: ?>
          <div class="launch-ui-preview">
            <div class="launch-preview-top"><span class="launch-preview-symbol">i<span>W</span></span><span class="launch-preview-dots"><b></b><b></b><b></b></span></div>
            <div class="launch-preview-body">
              <span class="launch-preview-kicker">YOUR BUSINESS, SORTED</span>
              <strong>One place for every next step.</strong>
              <div class="launch-preview-row"><i class="bi bi-building-check"></i><span>Business registration</span><i class="bi bi-check-circle-fill"></i></div>
              <div class="launch-preview-row"><i class="bi bi-patch-check"></i><span>Trademark &amp; compliance</span><i class="bi bi-arrow-up-right"></i></div>
              <div class="launch-preview-row"><i class="bi bi-window-stack"></i><span>Web &amp; digital solutions</span><i class="bi bi-arrow-up-right"></i></div>
            </div>
          </div>
        <?php endif; ?>
        <div class="launch-progress-card"><i class="bi bi-check-circle-fill"></i><span><strong>Stay informed</strong><small>Track your request online</small></span></div>
      </div>
    </div>
  </section>

  <section class="launch-evidence" aria-label="Service process highlights">
    <div class="container public-container launch-evidence-grid">
      <div><span class="launch-evidence-icon"><i class="bi bi-ui-checks"></i></span><span><strong>Know what to prepare</strong><small>Clear requirements for each service</small></span></div>
      <div><span class="launch-evidence-icon"><i class="bi bi-lock"></i></span><span><strong>Checkout through the portal</strong><small>Use the existing payment process</small></span></div>
      <div><span class="launch-evidence-icon"><i class="bi bi-activity"></i></span><span><strong>Follow your job</strong><small>Status, updates and documents online</small></span></div>
    </div>
  </section>

  <section class="launch-register" aria-labelledby="launch-register-title">
    <div class="container public-container launch-register-inner">
      <div><span class="launch-small-label">START HERE</span><h2 id="launch-register-title">Ready to register your business?</h2><p>Business name, limited company and other CAC filings — choose the service you need.</p></div>
      <a class="launch-btn launch-btn-light" href="<?= e($registrationLink) ?>">Start registration <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="launch-section" id="our-services" aria-labelledby="launch-services-title">
    <div class="container public-container">
      <div class="launch-heading">
        <div><span class="launch-small-label">OUR SERVICES</span><h2 id="launch-services-title"><?= e(trim((string) ($featured['title'] ?? '')) ?: 'Everything your business needs to move forward.') ?></h2></div>
        <p><?= e(trim((string) ($featured['subtitle'] ?? '')) ?: 'From registration and compliance to websites and brand growth, choose the support you need.') ?></p>
      </div>
      <div class="launch-categories" aria-label="Explore service areas">
        <a href="<?= e(public_url('services', ['category' => 'business-registration'])) ?>"><i class="bi bi-building"></i> Business registration</a>
        <a href="<?= e(public_url('services', ['category' => 'trademark'])) ?>"><i class="bi bi-patch-check"></i> Trademark &amp; compliance</a>
        <a href="<?= e(public_url('services', ['category' => 'growth'])) ?>"><i class="bi bi-laptop"></i> Websites &amp; digital</a>
        <a href="<?= e(public_url('services')) ?>"><i class="bi bi-grid"></i> All services</a>
      </div>
      <?php if ($visibleServices !== []): ?>
        <div class="launch-services-grid">
          <?php foreach ($visibleServices as $service): ?>
            <?php
            $name = trim((string) ($service['name'] ?? 'Service'));
            $slug = trim((string) ($service['slug'] ?? ''));
            if ($slug === '') { continue; }
            $category = strtolower((string) ($service['category'] ?? ''));
            $search = strtolower($name);
            $icon = 'bi-briefcase';
            if (str_contains($search, 'trademark') || str_contains($category, 'trademark')) { $icon = 'bi-patch-check'; }
            elseif (str_contains($search, 'registration') || str_contains($search, 'company')) { $icon = 'bi-building-add'; }
            elseif (str_contains($search, 'website') || str_contains($search, 'hosting')) { $icon = 'bi-window-stack'; }
            elseif (str_contains($search, 'design')) { $icon = 'bi-palette'; }
            elseif (str_contains($search, 'marketing') || str_contains($search, 'seo')) { $icon = 'bi-graph-up-arrow'; }
            ?>
            <a class="launch-service-card" href="<?= e(public_url('service', ['slug' => $slug])) ?>">
              <span class="launch-service-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span>
              <h3><?= e($name) ?></h3>
              <p><?= e(trim((string) ($service['short_description'] ?? '')) ?: 'View service details and start your request online.') ?></p>
              <span class="launch-card-link">View service <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="launch-centered-action"><a href="<?= e(public_url('services')) ?>">Explore all services <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
    </div>
  </section>

  <?php if ($caseItems !== []): ?>
  <section class="launch-section launch-work" aria-labelledby="launch-work-title">
    <div class="container public-container">
      <div class="launch-heading">
        <div><span class="launch-small-label">SELECTED CLIENT WORK</span><h2 id="launch-work-title"><?= e(trim((string) ($caseData['title'] ?? '')) ?: 'A little of what we have built.') ?></h2></div>
        <a class="launch-work-more" href="<?= e(public_url('case-studies')) ?>">Explore case studies <i class="bi bi-arrow-up-right"></i></a>
      </div>
      <div class="launch-work-grid">
        <?php foreach ($caseItems as $case): ?>
          <article class="launch-work-card">
            <div class="launch-work-picture"><img src="<?= e($case['image']) ?>" alt="<?= e($case['name']) ?>" loading="lazy" width="480" height="320"></div>
            <div class="launch-work-meta"><div><small><?= e($case['category']) ?></small><h3><?= e($case['name']) ?></h3></div>
              <?php if ($case['link'] !== ''): ?><a href="<?= e($case['link']) ?>" rel="noopener noreferrer" target="_blank" aria-label="See project: <?= e($case['name']) ?>"><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="launch-section launch-how" id="how-it-works" aria-labelledby="launch-how-title">
    <div class="container public-container">
      <div class="launch-heading launch-heading-compact"><div><span class="launch-small-label">HOW IT WORKS</span><h2 id="launch-how-title"><?= e(trim((string) ($process['title'] ?? '')) ?: 'Simple from start to finish.') ?></h2></div></div>
      <div class="launch-step-grid">
        <?php foreach ($steps as $i => $step): ?>
          <article class="launch-step"><span class="launch-step-number"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e((string) ($step['title'] ?? '')) ?></h3><p><?= e((string) ($step['text'] ?? '')) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ($faqItems !== []): ?>
    <section class="launch-section launch-faq" id="faq" aria-labelledby="launch-faq-title">
      <div class="container public-container launch-faq-inner">
        <div><span class="launch-small-label">FREQUENTLY ASKED</span><h2 id="launch-faq-title"><?= e(trim((string) ($faq['title'] ?? '')) ?: 'Questions? We have answers.') ?></h2><p>Need more help? <a href="<?= e(public_url('contact')) ?>">Contact our team</a>.</p></div>
        <div class="launch-faq-list">
          <?php foreach ($faqItems as $item): ?>
            <?php $question = trim((string) ($item['q'] ?? $item['question'] ?? '')); $answer = trim((string) ($item['a'] ?? $item['answer'] ?? '')); if ($question === '') { continue; } ?>
            <details><summary><?= e($question) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary><p><?= e($answer) ?></p></details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
  <section class="launch-bottom-cta">
    <div class="container public-container launch-bottom-inner">
      <div><h2><?= e(trim((string) ($banner['headline'] ?? '')) ?: "Let's get your next business task done.") ?></h2><p><?= e(trim((string) ($banner['text'] ?? '')) ?: 'Browse a service, submit your request and track everything from your iWorks account.') ?></p></div>
      <a class="launch-btn launch-btn-primary" href="<?= e(public_url('services')) ?>">Get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>
