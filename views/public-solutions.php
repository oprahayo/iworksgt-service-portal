<?php
/**
 * iWORKS Our Solutions — Gansell feature.
 * All four images come from actual public desktop/mobile website captures.
 * The canonical /solutions/media/ route reads from the shared portal source,
 * independent of the separate apex-domain static assets directory.
 */
$gansellUrl = 'https://gansell.com/';
$vendorUrl = 'https://gansell.com/@ab-african';
$gansellImage = static function (string $file): string {
    return public_absolute_url('solutions/media/' . $file . '?v=20261115-images3');
};
?>
<main class="public-main iw-solutions" id="iw-solutions">
  <section class="iw-solutions-header" aria-labelledby="iw-solutions-title">
    <div class="container public-container">
      <nav class="iw-solutions-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><span aria-hidden="true">/</span><span>Our Solutions</span>
      </nav>
      <p class="iw-solutions-kicker">OUR SOLUTIONS</p>
      <h1 id="iw-solutions-title">Digital tools for business.</h1>
      <p>Products developed by iWORKS to help entrepreneurs sell and operate online. Starting with Gansell.</p>
    </div>
  </section>

  <section class="iw-gansell-showcase" id="gansell" aria-labelledby="iw-gansell-title">
    <div class="container public-container iw-gansell-showcase-layout">
      <div class="iw-gansell-details">
        <div class="iw-gansell-title-row">
          <span class="iw-gansell-product-name">Gansell</span>
          <span class="iw-gansell-free-label">Free storefront</span>
        </div>
        <h2 id="iw-gansell-title">Create a store. Share your link. Take orders.</h2>
        <p class="iw-gansell-description">Gansell is a multi-vendor marketplace where businesses can list products and services, manage their own storefront and receive customer orders.</p>

        <h3 class="iw-gansell-checklist-title">What you can do</h3>
        <ul class="iw-gansell-checklist">
          <li><strong>Create your own storefront</strong> with a shareable Gansell web address.</li>
          <li><strong>List products and services</strong> with photos, categories and prices.</li>
          <li><strong>Share store or product links</strong> on WhatsApp and social media.</li>
          <li><strong>Reach marketplace visitors</strong> searching for vendors and products.</li>
          <li><strong>Accept shopping-cart orders</strong> through customer checkout.</li>
          <li><strong>Offer payment options</strong> supported by your store, including online payment where enabled.</li>
          <li><strong>Arrange delivery or pickup</strong> using an address or the buyer's live location.</li>
          <li><strong>Start for free</strong> with no storefront setup or product-listing fee.</li>
        </ul>

        <div class="iw-gansell-actions">
          <a class="iw-gansell-action-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Start on Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          <a class="iw-gansell-action-secondary" href="#gansell-example">View example store <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
        </div>
        <p class="iw-gansell-disclaimer">Storefront setup and listings are free. Payment-provider charges, where applicable, are separate.</p>
      </div>

      <div class="iw-gansell-device-gallery" aria-label="Actual Gansell screenshots">
        <figure class="iw-gansell-display-desktop">
          <div class="iw-gansell-browser-bar" aria-hidden="true"><span class="iw-gansell-browser-dots"><i></i><i></i><i></i></span><span>gansell.com</span></div>
          <img src="<?= e($gansellImage('gansell-home-desktop.png')) ?>" alt="Gansell marketplace desktop website showing vendor search and product categories" width="1440" height="1000" loading="eager" decoding="async" fetchpriority="high">
          <figcaption>Marketplace · desktop</figcaption>
        </figure>
        <figure class="iw-gansell-display-mobile">
          <div class="iw-gansell-phone">
            <div class="iw-gansell-phone-notch" aria-hidden="true"></div>
            <img src="<?= e($gansellImage('gansell-home-mobile.png')) ?>" alt="Gansell marketplace mobile website showing vendor search and product categories" width="390" height="844" loading="eager" decoding="async">
          </div>
          <figcaption>Marketplace · mobile</figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section class="iw-gansell-example" id="gansell-example" aria-labelledby="iw-gansell-example-title">
    <div class="container public-container">
      <div class="iw-gansell-example-top">
        <div>
          <p class="iw-solutions-kicker">REAL VENDOR EXAMPLE</p>
          <h2 id="iw-gansell-example-title">AB ÁFRICAN FOOD AND MALL</h2>
          <p>See how a vendor storefront looks on desktop and mobile. Visitors can browse the catalogue, choose products, use the cart and continue to checkout.</p>
        </div>
        <a class="iw-gansell-example-link" href="<?= e($vendorUrl) ?>" target="_blank" rel="noopener noreferrer">Visit this store <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
      <div class="iw-gansell-example-gallery">
        <figure class="iw-gansell-example-desktop">
          <img src="<?= e($gansellImage('ab-african-store-desktop.png')) ?>" alt="Real desktop screenshot of AB ÁFRICAN FOOD AND MALL storefront, showing products and prices" width="1440" height="1000" loading="lazy" decoding="async">
          <figcaption>AB ÁFRICAN FOOD AND MALL · desktop</figcaption>
        </figure>
        <figure class="iw-gansell-example-mobile">
          <img src="<?= e($gansellImage('ab-african-store-mobile.png')) ?>" alt="Real mobile screenshot of AB ÁFRICAN FOOD AND MALL storefront showing product categories" width="390" height="844" loading="lazy" decoding="async">
          <figcaption>AB ÁFRICAN FOOD AND MALL · mobile</figcaption>
        </figure>
      </div>
      <p class="iw-gansell-example-note">Example storefront: <a href="<?= e($vendorUrl) ?>" target="_blank" rel="noopener noreferrer">gansell.com/@ab-african</a>. Screenshots captured from public pages on 15 November 2026.</p>
    </div>
  </section>

  <section class="iw-gansell-steps" aria-labelledby="iw-gansell-steps-title">
    <div class="container public-container">
      <h2 id="iw-gansell-steps-title">How to get started</h2>
      <ol>
        <li><span>01</span><div><strong>Create your vendor store.</strong><p>Open Gansell and follow the vendor setup steps.</p></div></li>
        <li><span>02</span><div><strong>Add products or services.</strong><p>Upload photos, enter prices and organise your listings.</p></div></li>
        <li><span>03</span><div><strong>Share your store link.</strong><p>Send it to customers or add it to your social profiles.</p></div></li>
      </ol>
      <a class="iw-gansell-action-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Explore Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="iw-solutions-future">
    <div class="container public-container">
      <h2>More solutions will be added here.</h2>
      <p>New iWORKS digital products will appear as they launch.</p>
    </div>
  </section>
</main>
