<?php
/**
 * Gansell — iWORKS product landing.
 *
 * Intentionally one product-led page, not a generic three-section SaaS
 * template or a directory of future products. Screenshot pixels come from
 * authentic public Gansell / AB ÁFRICAN FOOD AND MALL captures in the repo.
 */
require_once __DIR__ . '/../app/public_solutions_media.php';
$gansellSite = 'https://gansell.com/';
$vendorStore = 'https://gansell.com/@ab-african';
$screens = [];
foreach (iworks_solutions_media_allowed() as $filename) {
    $screens[$filename] = iworks_solutions_embedded_image($filename, dirname(__DIR__));
}
?>
<main class="public-main iw-solutions" id="iw-solutions">
  <section class="iw-gansell-stage" id="gansell" aria-labelledby="iw-gansell-heading">
    <div class="container public-container">
      <div class="iw-gansell-intro">
        <div class="iw-gansell-intro-heading">
          <p class="iw-gansell-overline">GANSELL <span aria-hidden="true">/</span> AN iWORKS PRODUCT</p>
          <h1 id="iw-gansell-heading">Put your business online with Gansell.</h1>
        </div>
        <div class="iw-gansell-intro-detail">
          <p>Build your own vendor storefront, list products or services, and share a direct shop link with customers. Gansell is free to start.</p>
          <div class="iw-gansell-actions">
            <a class="iw-gansell-button" href="<?= e($gansellSite) ?>" target="_blank" rel="noopener noreferrer">Create your free store <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a class="iw-gansell-quiet-link" href="#gansell-example">See a live store <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>

      <div class="iw-gansell-product-gallery" aria-label="Real Gansell desktop and mobile website captures">
        <div class="iw-gansell-browser-shot">
          <div class="iw-gansell-browser-top" aria-hidden="true">
            <span class="iw-gansell-browser-dots"><i></i><i></i><i></i></span>
            <span>gansell.com</span>
            <i class="bi bi-box-arrow-up-right"></i>
          </div>
          <?php if ($screens['gansell-home-desktop.png'] !== ''): ?>
            <img src="<?= e($screens['gansell-home-desktop.png']) ?>" width="1440" height="1000" loading="eager" decoding="async" fetchpriority="high" alt="Actual Gansell marketplace desktop screen with vendor search and category listings">
          <?php else: ?>
            <div class="iw-gansell-photo-unavailable">Website preview unavailable. <a href="<?= e($gansellSite) ?>">Open Gansell</a></div>
          <?php endif; ?>
        </div>
        <div class="iw-gansell-phone-shot">
          <div class="iw-gansell-phone-top" aria-hidden="true"></div>
          <?php if ($screens['gansell-home-mobile.png'] !== ''): ?>
            <img src="<?= e($screens['gansell-home-mobile.png']) ?>" width="390" height="844" loading="eager" decoding="async" alt="Actual mobile screen of Gansell marketplace, with category browsing and vendor search">
          <?php else: ?>
            <div class="iw-gansell-photo-unavailable">Mobile preview unavailable.</div>
          <?php endif; ?>
        </div>
        <span class="iw-gansell-free-seal" aria-label="Free vendor storefront"><span>FREE</span></span>
        <span class="iw-gansell-gallery-caption">Real website previews · Desktop &amp; mobile</span>
      </div>

      <div class="iw-gansell-functions">
        <h2>What you can do on Gansell</h2>
        <ul aria-label="Vendor features">
          <li>Create a personal storefront with your own shareable link</li>
          <li>List products and services with photos and prices</li>
          <li>Send shop and product links through WhatsApp</li>
          <li>Receive orders through a shopping cart and checkout</li>
          <li>Accept online payment where available to your store</li>
          <li>Let buyers provide a delivery location or arrange pickup</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="iw-gansell-real-store" id="gansell-example" aria-labelledby="iw-gansell-example-heading">
    <div class="container public-container">
      <div class="iw-gansell-example-intro">
        <div>
          <p class="iw-gansell-overline">SEE IT IN USE</p>
          <h2 id="iw-gansell-example-heading">A real store on Gansell.</h2>
        </div>
        <div class="iw-gansell-example-copy">
          <p><strong>AB ÁFRICAN FOOD AND MALL</strong> uses its own Gansell storefront to display products and let customers browse and order.</p>
          <a href="<?= e($vendorStore) ?>" target="_blank" rel="noopener noreferrer">gansell.com/@ab-african <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
      </div>
      <div class="iw-gansell-example-gallery" aria-label="Actual AB ÁFRICAN FOOD AND MALL storefront on desktop and mobile">
        <figure class="iw-gansell-example-wide">
          <?php if ($screens['ab-african-store-desktop.png'] !== ''): ?>
            <img src="<?= e($screens['ab-african-store-desktop.png']) ?>" width="1440" height="1000" loading="eager" decoding="async" alt="Desktop screenshot of AB ÁFRICAN FOOD AND MALL on Gansell, with real product pictures and prices">
          <?php else: ?>
            <div class="iw-gansell-photo-unavailable">Store screenshot unavailable. <a href="<?= e($vendorStore) ?>">Visit the live store</a></div>
          <?php endif; ?>
          <figcaption>Vendor page · Desktop</figcaption>
        </figure>
        <figure class="iw-gansell-example-phone">
          <?php if ($screens['ab-african-store-mobile.png'] !== ''): ?>
            <img src="<?= e($screens['ab-african-store-mobile.png']) ?>" width="390" height="844" loading="eager" decoding="async" alt="Mobile screenshot of AB ÁFRICAN FOOD AND MALL on Gansell, showing the vendor's catalogue">
          <?php else: ?>
            <div class="iw-gansell-photo-unavailable">Mobile screenshot unavailable.</div>
          <?php endif; ?>
          <figcaption>Vendor page · Mobile</figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section class="iw-gansell-next" aria-label="Start your storefront">
    <div class="container public-container iw-gansell-next-inner">
      <div>
        <strong>Your business can have its own Gansell link.</strong>
        <p>Free storefront setup and product listings. Payment-provider charges, where applicable, are separate.</p>
      </div>
      <a class="iw-gansell-button" href="<?= e($gansellSite) ?>" target="_blank" rel="noopener noreferrer">Start selling on Gansell <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>
