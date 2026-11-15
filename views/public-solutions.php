<?php
/**
 * Gansell — an iWORKS digital product.
 * Keep the current /solutions URL, but use it as a focused product page until
 * other iWORKS solutions actually launch. No separate "all solutions" screen.
 *
 * These four screenshots are genuine public captures of gansell.com and the
 * AB ÁFRICAN FOOD AND MALL storefront. Inline verified PNGs so cPanel's split
 * public/portal document roots cannot leave the browser with missing images.
 */
require_once __DIR__ . '/../app/public_solutions_media.php';
$gansellUrl = 'https://gansell.com/';
$vendorUrl = 'https://gansell.com/@ab-african';
$gansellScreens = [];
foreach (iworks_solutions_media_allowed() as $screenName) {
    $gansellScreens[$screenName] = iworks_solutions_embedded_image($screenName, dirname(__DIR__));
}
?>
<main class="public-main iw-solutions" id="iw-solutions">
  <section class="iw-gansell-product" id="gansell" aria-labelledby="iw-gansell-title">
    <div class="container public-container">
      <div class="iw-gansell-product-layout">
        <div class="iw-gansell-product-copy">
          <p class="iw-gansell-product-overline">AN iWORKS DIGITAL PRODUCT</p>
          <h1 id="iw-gansell-title">Gansell</h1>
          <p class="iw-gansell-product-tagline">Your shop online. Your link to share.</p>
          <p class="iw-gansell-product-lead">A multi-vendor marketplace that lets you set up your own storefront, list what you sell and take orders online—without paying to create a shop.</p>

          <ul class="iw-gansell-feature-list" aria-label="Gansell features">
            <li>Personal storefront with a shareable shop link</li>
            <li>Product and service listings with photos and prices</li>
            <li>Store and product links for WhatsApp or social media</li>
            <li>Discovery through the Gansell marketplace</li>
            <li>Shopping cart and customer checkout</li>
            <li>Online payments where supported by the seller</li>
            <li>Delivery address, location selection and pickup options</li>
          </ul>

          <div class="iw-gansell-product-actions">
            <a class="iw-gansell-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Open Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a class="iw-gansell-secondary" href="#gansell-vendor">See a real vendor store <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
          </div>
          <p class="iw-gansell-product-note">Creating a store and listing products is free. Payment-provider charges, if applicable, are separate.</p>
        </div>

        <div class="iw-gansell-product-visual" aria-label="Real Gansell marketplace screenshots">
          <span class="iw-gansell-free-sticker" aria-label="Free storefront">FREE</span>
          <div class="iw-gansell-screenshot-pair">
            <figure class="iw-gansell-screenshot iw-gansell-screenshot-desktop">
              <?php if ($gansellScreens['gansell-home-desktop.png'] !== ''): ?>
                <img src="<?= e($gansellScreens['gansell-home-desktop.png']) ?>" alt="Real desktop capture of the Gansell Nigeria marketplace" width="1440" height="1000" loading="eager" decoding="async">
              <?php else: ?>
                <div class="iw-gansell-image-error">Marketplace preview unavailable. <a href="<?= e($gansellUrl) ?>">View Gansell</a></div>
              <?php endif; ?>
              <figcaption>Desktop</figcaption>
            </figure>
            <figure class="iw-gansell-screenshot iw-gansell-screenshot-mobile">
              <?php if ($gansellScreens['gansell-home-mobile.png'] !== ''): ?>
                <img src="<?= e($gansellScreens['gansell-home-mobile.png']) ?>" alt="Real mobile capture of the Gansell Nigeria marketplace" width="390" height="844" loading="eager" decoding="async">
              <?php else: ?>
                <div class="iw-gansell-image-error">Mobile preview unavailable. <a href="<?= e($gansellUrl) ?>">View Gansell</a></div>
              <?php endif; ?>
              <figcaption>Mobile</figcaption>
            </figure>
          </div>
          <p class="iw-gansell-screenshot-credit">Actual Gansell website views</p>
        </div>
      </div>
    </div>
  </section>

  <section class="iw-gansell-vendor" id="gansell-vendor" aria-labelledby="iw-gansell-vendor-title">
    <div class="container public-container iw-gansell-vendor-layout">
      <div class="iw-gansell-vendor-visual">
        <figure class="iw-gansell-screenshot iw-gansell-vendor-desktop">
          <?php if ($gansellScreens['ab-african-store-desktop.png'] !== ''): ?>
            <img src="<?= e($gansellScreens['ab-african-store-desktop.png']) ?>" alt="Real desktop capture of AB ÁFRICAN FOOD AND MALL's Gansell storefront, showing its catalogue" width="1440" height="1000" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="iw-gansell-image-error">Store preview unavailable. <a href="<?= e($vendorUrl) ?>">Visit the store</a></div>
          <?php endif; ?>
          <figcaption>Vendor storefront · desktop</figcaption>
        </figure>
        <figure class="iw-gansell-screenshot iw-gansell-vendor-mobile">
          <?php if ($gansellScreens['ab-african-store-mobile.png'] !== ''): ?>
            <img src="<?= e($gansellScreens['ab-african-store-mobile.png']) ?>" alt="Real mobile capture of AB ÁFRICAN FOOD AND MALL's Gansell storefront" width="390" height="844" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="iw-gansell-image-error">Mobile preview unavailable. <a href="<?= e($vendorUrl) ?>">Visit the store</a></div>
          <?php endif; ?>
          <figcaption>Mobile</figcaption>
        </figure>
      </div>
      <div class="iw-gansell-vendor-copy">
        <p class="iw-gansell-product-overline">A LIVE GANSELL STORE</p>
        <h2 id="iw-gansell-vendor-title">AB ÁFRICAN FOOD AND MALL</h2>
        <p>See a working vendor page with its own link, product catalogue and cart. This is the storefront your customers can visit directly from WhatsApp or your social profile.</p>
        <div class="iw-gansell-vendor-link">gansell.com/@ab-african</div>
        <a class="iw-gansell-secondary iw-gansell-vendor-cta" href="<?= e($vendorUrl) ?>" target="_blank" rel="noopener noreferrer">Visit this store <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <section class="iw-gansell-bottom-cta">
    <div class="container public-container iw-gansell-bottom-inner">
      <p>Put your business on Gansell.</p>
      <a class="iw-gansell-primary" href="<?= e($gansellUrl) ?>" target="_blank" rel="noopener noreferrer">Get started <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>
