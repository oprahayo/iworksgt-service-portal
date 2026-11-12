<?php
/**
 * About iWorks: concise company introduction with CMS copy and founder details.
 */
require_once __DIR__ . '/../app/public-photo-config.php';
$about = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (!is_array($block)) { continue; }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if (!isset($about[$type])) {
        $about[$type] = is_array($block['data'] ?? null) ? $block['data'] : [];
    }
}
$hero = $about['hero'] ?? [];
$founder = $about['founder_story'] ?? [];
$banner = $about['cta_banner'] ?? [];
$headline = trim((string) ($hero['headline'] ?? ''));
if ($headline === '') { $headline = 'Business support, built on practical experience.'; }
$description = trim((string) ($hero['subheadline'] ?? ''));
if ($description === '') { $description = 'iWorks Global helps businesses with registration, compliance, branding and digital services.'; }
$heroImage = !empty($hero['imageId']) ? get_media_asset((int) $hero['imageId']) : null;
$heroImageUrl = trim((string) ($heroImage['url'] ?? '')) ?: iworks_stock_photo_url('meeting', 1100);
$founderName = trim((string) ($founder['name'] ?? ''));
$founderRole = trim((string) ($founder['role'] ?? 'Founder'));
$founderSummary = trim((string) ($founder['summary'] ?? ''));
$founderImage = !empty($founder['imageId']) ? get_media_asset((int) $founder['imageId']) : null;
$founderImageUrl = trim((string) ($founderImage['url'] ?? ''));
$founderQuote = trim((string) ($founder['quote'] ?? ''));
$bannerTitle = trim((string) ($banner['headline'] ?? ''));
if ($bannerTitle === '' || str_contains(strtolower($bannerTitle), 'launch faster')) { $bannerTitle = 'How can we help your business?'; }
?>
<main class="public-main iw-about-min" id="main-content">
  <section class="iw-inner-intro iw-about-min-intro">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">About</span></nav>
      <div class="iw-about-min-hero">
        <div>
          <span class="iw-about-min-eyebrow">ABOUT iWORKS</span>
          <h1><?= e($headline) ?></h1>
          <p><?= e($description) ?></p>
          <a href="<?= e(public_url('services')) ?>" class="iw-inner-primary">Explore services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <figure>
          <img src="<?= e($heroImageUrl) ?>" width="560" height="420" alt="Business collaboration" fetchpriority="high" decoding="async">
          <?php if (empty($heroImage['url'])): ?><figcaption>Illustrative business photography</figcaption><?php endif; ?>
        </figure>
      </div>
    </div>
  </section>

  <section class="iw-about-min-story">
    <div class="container public-container iw-about-min-story-grid">
      <h2>What we're about</h2>
      <div>
        <p>iWorks began as a technology and business-services operation in 2016 and now operates as iWORKS GLOBAL LTD. We help entrepreneurs and organisations start, operate and grow their businesses, including Nigerian founders at home and abroad.</p>
        <p>Our portal brings service requirements, payments, updates and deliverables into one place. Clients can work with us remotely, submit documents and follow each request through delivery.</p>
      </div>
    </div>
  </section>

  <section class="iw-about-min-services">
    <div class="container public-container">
      <h2>What we do</h2>
      <div class="iw-about-min-service-grid">
        <a href="<?= e(public_url('services', ['category' => 'business-registration'])) ?>"><i class="bi bi-building" aria-hidden="true"></i><h3>Business formation &amp; registration</h3><p>Business names, companies and Corporate Affairs Commission filings in Nigeria.</p><span>Explore <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></a>
        <a href="<?= e(public_url('services', ['category' => 'trademark'])) ?>"><i class="bi bi-patch-check" aria-hidden="true"></i><h3>Licences &amp; compliance</h3><p>Trademarks, NAFDAC documentation, export licence and BPP-related filing support.</p><span>Explore <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></a>
        <a href="<?= e(public_url('services', ['category' => 'growth'])) ?>"><i class="bi bi-window-stack" aria-hidden="true"></i><h3>Web &amp; digital</h3><p>Websites, development, hosting, branding and digital marketing.</p><span>Explore <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></a>
      </div>
    </div>
  </section>

  <?php if ($founderName !== '' || $founderSummary !== ''): ?>
    <section class="iw-about-min-founder">
      <div class="container public-container iw-about-min-founder-grid">
        <div>
          <span class="iw-about-min-eyebrow">FROM OUR FOUNDER</span>
          <h2><?= e(trim((string) ($founder['title'] ?? '')) ?: 'We focus on getting work done clearly.') ?></h2>
          <?php if ($founderSummary !== ''): ?><p><?= e($founderSummary) ?></p><?php endif; ?>
          <?php if ($founderQuote !== ''): ?><blockquote><?= e($founderQuote) ?></blockquote><?php endif; ?>
          <?php if ($founderName !== ''): ?><p class="iw-about-min-founder-name"><strong><?= e($founderName) ?></strong><span><?= e($founderRole) ?></span></p><?php endif; ?>
        </div>
        <?php if ($founderImageUrl !== ''): ?>
          <img src="<?= e($founderImageUrl) ?>" alt="<?= e($founderName) ?>" loading="lazy" width="420" height="420">
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="iw-inner-last">
    <div class="container public-container">
      <div><h2><?= e($bannerTitle) ?></h2><p>Explore our services or contact our team before ordering.</p></div>
      <a href="<?= e(public_url('contact')) ?>">Contact iWorks <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>