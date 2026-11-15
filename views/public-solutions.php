<?php
/**
 * First-party iWORKS Solutions and Gansell product introduction.
 * No invented vendor registration endpoint, pricing, checkout or credentials.
 * Gansell onboarding is delegated to its official homepage.
 */
$isGansellLanding = ($currentPublicSlug ?? '') === 'gansell';
$gansellUrl = 'https://gansell.com/';
$gansellCapture = public_absolute_url('assets/branding/case-studies/gansell-website-project.webp');
?>
<main class="public-main iw-solutions" id="iw-solutions">
  <?php if ($isGansellLanding): ?>
    <section class="iw-solution-intro iw-gansell-intro" aria-labelledby="iw-solution-title">
      <div class="container public-container">
        <nav class="iw-solution-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= e(public_url('home')) ?>">Home</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <a href="<?= e(public_url('solutions')) ?>">Our Solutions</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span>Gansell</span>
        </nav>
        <div class="iw-solution-hero-grid">
          <div class="iw-solution-copy">
            <span class="iw-solution-eyebrow">AN iWORKS DIGITAL SOLUTION</span>
            <span class="iw-gansell-wordmark">gansell<span aria-hidden="true">.</span></span>
            <h1 id="iw-solution-title">Your business deserves to be found.</h1>
            <p class="iw-solution-lead">Meet Gansell, a marketplace for discovering Nigerian vendors, products and services. Put your business in front of people looking for what you offer.</p>
            <div class="iw-solution-actions">
              <a class="iw-solution-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Get started on Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
              <a class="iw-solution-secondary" href="#how-gansell-works">How it works <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
            </div>
            <p class="iw-solution-footnote">Registration and any available listing or plan options are handled on Gansell, not on this website.</p>
          </div>
          <figure class="iw-gansell-preview">
            <div class="iw-gansell-preview-top" aria-hidden="true">
              <span class="iw-gansell-preview-dots"><i></i><i></i><i></i></span>
              <span>gansell.com</span>
              <i class="bi bi-box-arrow-up-right"></i>
            </div>
            <img src="<?= e($gansellCapture) ?>" alt="Captured view of the Gansell Nigerian marketplace with vendor, product and service discovery" width="1440" height="900" loading="eager" decoding="async">
            <figcaption>Explore the live marketplace at gansell.com</figcaption>
          </figure>
        </div>
      </div>
    </section>
    <section class="iw-solution-benefits" aria-labelledby="iw-gansell-benefits-title">
      <div class="container public-container">
        <header class="iw-solution-section-heading">
          <span class="iw-solution-eyebrow">BUILT FOR DISCOVERY</span>
          <h2 id="iw-gansell-benefits-title">A simpler way to connect businesses and buyers.</h2>
        </header>
        <div class="iw-solution-benefit-list">
          <article><span class="iw-benefit-number">01</span><h3>Put your business on the map</h3><p>Present your business in a marketplace where visitors browse vendors and listings.</p></article>
          <article><span class="iw-benefit-number">02</span><h3>Show what you sell</h3><p>Help prospective customers explore products and compare what is available.</p></article>
          <article><span class="iw-benefit-number">03</span><h3>Be discovered for your services</h3><p>Give people another place to find the services and expertise they need.</p></article>
        </div>
      </div>
    </section>
    <section class="iw-solution-steps" id="how-gansell-works" aria-labelledby="iw-gansell-steps-title">
      <div class="container public-container iw-solution-steps-grid">
        <div>
          <span class="iw-solution-eyebrow">FOR VENDORS</span>
          <h2 id="iw-gansell-steps-title">Start where your customers are searching.</h2>
          <p>Gansell provides a place for Nigerian vendors to be discovered. You can visit the marketplace to see the current vendor-listing options and begin from there.</p>
          <a class="iw-solution-text-link" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Open Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <ol>
          <li><strong>Visit Gansell</strong><span>Open the official marketplace and explore the available vendor options.</span></li>
          <li><strong>Choose your business listing path</strong><span>Follow the current prompts provided by Gansell for businesses and vendors.</span></li>
          <li><strong>Introduce what you offer</strong><span>Share your business, products or services through the marketplace's available listing features.</span></li>
        </ol>
      </div>
    </section>
    <section class="iw-solution-final" aria-labelledby="iw-gansell-final-title">
      <div class="container public-container iw-solution-final-inner">
        <div><span class="iw-solution-eyebrow">READY TO BE DISCOVERED?</span><h2 id="iw-gansell-final-title">Take your business to Gansell.</h2><p>Browse the marketplace or explore the available options for vendors.</p></div>
        <a class="iw-solution-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Explore Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </section>
  <?php else: ?>
    <section class="iw-solution-intro iw-solutions-overview" aria-labelledby="iw-solution-title">
      <div class="container public-container">
        <nav class="iw-solution-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= e(public_url('home')) ?>">Home</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span>Our Solutions</span>
        </nav>
        <div class="iw-solution-overview-head">
          <span class="iw-solution-eyebrow">DIGITAL PRODUCTS BY iWORKS</span>
          <h1 id="iw-solution-title">Tools for the businesses we help build.</h1>
          <p>We support entrepreneurs with company services and practical digital products. Our Solutions brings those products together, starting with Gansell.</p>
        </div>
      </div>
    </section>
    <section class="iw-solution-featured" aria-labelledby="iw-featured-solution-title">
      <div class="container public-container iw-featured-product">
        <div class="iw-featured-product-copy">
          <span class="iw-solution-eyebrow">FEATURED SOLUTION · MARKETPLACE</span>
          <span class="iw-gansell-wordmark">gansell<span aria-hidden="true">.</span></span>
          <h2 id="iw-featured-solution-title">A place for vendors, products and services to be discovered.</h2>
          <p>Gansell helps people find businesses and explore their offerings. It gives Nigerian vendors another way to introduce themselves to potential customers.</p>
          <div class="iw-solution-actions">
            <a class="iw-solution-primary" href="<?= e(public_url('gansell')) ?>">Discover Gansell <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <a class="iw-solution-secondary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Visit marketplace <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <a class="iw-featured-product-media" href="<?= e(public_url('gansell')) ?>" aria-label="Learn about Gansell">
          <div class="iw-gansell-preview-top" aria-hidden="true"><span class="iw-gansell-preview-dots"><i></i><i></i><i></i></span><span>gansell.com</span><i class="bi bi-arrow-up-right"></i></div>
          <img src="<?= e($gansellCapture) ?>" alt="Gansell marketplace website preview" width="1440" height="900" loading="eager" decoding="async">
        </a>
      </div>
    </section>
    <section class="iw-solutions-outlook" aria-labelledby="iw-solutions-outlook-title">
      <div class="container public-container">
        <span class="iw-solution-eyebrow">WHAT COMES NEXT</span>
        <h2 id="iw-solutions-outlook-title">More tools, one place to find them.</h2>
        <p>As new iWORKS digital products become available, we will introduce them here. Only available solutions are featured today.</p>
        <a class="iw-solution-text-link" href="<?= e(public_url('services')) ?>">Explore our business services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </section>
  <?php endif; ?>
</main>
