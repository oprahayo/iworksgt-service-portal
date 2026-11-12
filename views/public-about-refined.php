<?php
/**
 * iWorks About page: editorial, concise and CMS-backed.
 * Only public, published CMS content is consumed.
 */
require_once __DIR__ . '/../app/public-photo-config.php';
$aboutBlocks = [];
foreach ($layout as $block) {
    if (!is_array($block)) { continue; }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if (!isset($aboutBlocks[$type])) { $aboutBlocks[$type] = is_array($block['data'] ?? null) ? $block['data'] : []; }
}
$hero = $aboutBlocks['hero'] ?? [];
$founder = $aboutBlocks['founder_story'] ?? [];
$banner = $aboutBlocks['cta_banner'] ?? [];
$headline = trim((string) ($hero['headline'] ?? 'Business support, built around getting things done.'));
$description = trim((string) ($hero['subheadline'] ?? 'iWorks Global helps people start, manage and grow businesses with straightforward services and an online portal they can follow.'));
$founderName = trim((string) ($founder['name'] ?? ''));
$founderRole = trim((string) ($founder['role'] ?? 'Founder'));
$founderSummary = trim((string) ($founder['summary'] ?? ''));
$founderQuote = trim((string) ($founder['quote'] ?? ''));
$founderSections = is_array($founder['sections'] ?? null) ? $founder['sections'] : [];
$founderImage = (int) ($founder['imageId'] ?? 0) > 0 ? get_media_asset((int) $founder['imageId']) : null;
$aboutHeroImage = (int) ($hero['imageId'] ?? 0) > 0 ? get_media_asset((int) $hero['imageId']) : null;
?>
<main class="public-main iw-about-page" id="main-content">
  <section class="iw-page-intro iw-about-intro">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">About</span></nav>
      <div class="iw-about-hero">
        <div class="iw-about-hero-copy">
          <span class="iw-kicker">ABOUT iWORKS GLOBAL</span>
          <h1><?= e($headline) ?></h1>
          <p><?= e($description) ?></p>
          <div class="iw-inline-actions">
            <a class="iw-action-primary" href="<?= e(public_url('services')) ?>">Explore our services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <a class="iw-action-link" href="<?= e(public_url('contact')) ?>">Talk to our team <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <div class="iw-about-hero-visual">
          <?php if (!empty($aboutHeroImage['url'])): ?>
            <img src="<?= e((string) $aboutHeroImage['url']) ?>" alt="iWorks Global business support" width="800" height="560" fetchpriority="high">
          <?php else: ?>
            <img src="<?= e(iworks_stock_photo_url('meeting', 1200)) ?>" alt="Professionals collaborating around an office meeting table" width="800" height="560" fetchpriority="high">
          <?php endif; ?>
          <div class="iw-about-hero-caption"><i class="bi bi-check-circle-fill"></i><span>Business services with progress you can follow</span></div>
        </div>
      </div>
    </div>
  </section>

  <div class="container public-container"><p class="iw-photo-disclosure">Illustrative business photography. People shown are not identified as iWorks staff or clients.</p></div>
  <section class="iw-about-principles">
    <div class="container public-container iw-about-principles-grid">
      <div><span>01</span><h2>Simple to start</h2><p>Find your service, see what's required, and make your request online.</p></div>
      <div><span>02</span><h2>Clear as we work</h2><p>Keep payment, intake documents, updates and conversations together.</p></div>
      <div><span>03</span><h2>Accountable delivery</h2><p>Track your job and access the final files through your account.</p></div>
    </div>
  </section>

  <section class="iw-about-story">
    <div class="container public-container iw-about-story-grid">
      <div class="iw-about-story-visual"><img src="<?= e(iworks_stock_photo_url('strategy', 1080)) ?>" alt="Business professionals reviewing documents together" width="800" height="560" loading="lazy"><div class="iw-story-note"><strong>Business + technology</strong><span>Practical services, supported by a digital workflow.</span></div></div>
      <div class="iw-about-story-copy">
        <span class="iw-kicker">WHY WE EXIST</span>
        <h2>Less back-and-forth. More work completed.</h2>
        <p>iWorks began as a technology and business-services operation in 2016, and now operates as iWORKS GLOBAL LTD. We support founders, organisations and growing teams through business setup, compliance filings, brand protection and digital delivery.</p>
        <p>Our approach is straightforward: be clear about what a service includes, collect the right information, and give clients a place to follow their work instead of chasing scattered messages.</p>
        <a href="<?= e(public_url('services')) ?>" class="iw-text-arrow">See how we can help <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <section class="iw-about-expertise">
    <div class="container public-container">
      <div class="iw-about-heading"><div><span class="iw-kicker">WHAT WE DO</span><h2>Support for every stage of business.</h2></div><p>Pick the right starting point. Each service connects to the same ordering and tracking system.</p></div>
      <div class="iw-expertise-grid">
        <a href="<?= e(public_url('services', ['category' => 'business-registration'])) ?>"><img src="<?= e(iworks_stock_photo_url('registration', 900)) ?>" alt="" width="800" height="560" loading="lazy"><span>01 · REGISTER</span><h3>Company &amp; CAC services</h3><p>Business names, companies and related filing support.</p><i class="bi bi-arrow-up-right"></i></a>
        <a href="<?= e(public_url('services', ['category' => 'trademark'])) ?>"><img src="<?= e(iworks_stock_photo_url('consultation', 900)) ?>" alt="" width="800" height="560" loading="lazy"><span>02 · PROTECT</span><h3>Trademark &amp; compliance</h3><p>Brand protection and structured compliance support.</p><i class="bi bi-arrow-up-right"></i></a>
        <a href="<?= e(public_url('services', ['category' => 'growth'])) ?>"><img src="<?= e(iworks_stock_photo_url('digital', 900)) ?>" alt="" width="800" height="560" loading="lazy"><span>03 · GROW</span><h3>Web &amp; digital solutions</h3><p>Websites, hosting, identity and digital marketing.</p><i class="bi bi-arrow-up-right"></i></a>
      </div>
    </div>
  </section>

  <?php if ($founderName !== '' || $founderSummary !== ''): ?>
  <section class="iw-about-founder">
    <div class="container public-container">
      <div class="iw-founder-copy">
        <span class="iw-kicker">A NOTE FROM OUR FOUNDER</span>
        <h2><?= e(trim((string) ($founder['title'] ?? '')) ?: 'Built around clients who need clear results.') ?></h2>
        <?php if ($founderSummary !== ''): ?><p><?= e($founderSummary) ?></p><?php endif; ?>
        <?php if ($founderQuote !== ''): ?><blockquote>“<?= e($founderQuote) ?>”</blockquote><?php endif; ?>
        <?php if ($founderName !== ''): ?><p class="iw-founder-sig"><strong><?= e($founderName) ?></strong><span><?= e($founderRole) ?></span></p><?php endif; ?>
      </div>
      <?php if (!empty($founderImage['url'])): ?>
        <div class="iw-founder-photo"><img src="<?= e((string) $founderImage['url']) ?>" alt="<?= e($founderName) ?>" loading="lazy"></div>
      <?php else: ?>
        <div class="iw-founder-visual"><img src="<?= e(iworks_stock_photo_url('digital', 880)) ?>" alt="Software development workspace" loading="lazy" width="800" height="560"></div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($founderSections !== []): ?>
  <section class="iw-about-values">
    <div class="container public-container">
      <div class="iw-about-heading"><div><span class="iw-kicker">HOW WE WORK</span><h2>A process that puts clarity first.</h2></div></div>
      <div class="iw-values-grid">
        <?php foreach (array_slice($founderSections, 0, 3) as $section): ?>
          <?php $sectionTitle = trim((string) ($section['title'] ?? '')); $sectionText = trim((string) ($section['text'] ?? '')); if ($sectionTitle === '') { continue; } ?>
          <article><i class="bi bi-check2-circle"></i><h3><?= e($sectionTitle) ?></h3><p><?= nl2br(e($sectionText)) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="iw-about-next">
    <div class="container public-container"><div><span class="iw-kicker">START WITH iWORKS</span><h2><?= e(trim((string) ($banner['headline'] ?? '')) ?: 'Ready to move your business forward?') ?></h2><p><?= e(trim((string) ($banner['text'] ?? '')) ?: 'Choose a service, send your details and follow progress from one portal.') ?></p></div><a class="iw-action-primary" href="<?= e(public_url('services')) ?>">Browse services <i class="bi bi-arrow-right"></i></a></div>
  </section>
</main>
