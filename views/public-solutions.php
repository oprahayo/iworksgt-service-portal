<?php
/**
 * One iWORKS solutions hub: Gansell is the first featured digital product.
 * Authentic screen captures (when available) come from public Chromium visits.
 * No user checkout/session data, unverified payment pricing or fabricated sales.
 */
$gansellHome = 'https://gansell.com/';
$sampleVendor = 'https://gansell.com/@ab-african';
$solutionImg = static function(string $basename, string $fallback = ''): string {
    $path = 'assets/branding/solutions/'.$basename;
    return is_file(__DIR__.'/../'.$path)
        ? public_absolute_url($path)
        : ($fallback !== '' ? public_absolute_url($fallback) : '');
};
$marketDesktop = $solutionImg('gansell-home-desktop.png', 'assets/branding/case-studies/gansell-website-project.webp');
$marketMobile = $solutionImg('gansell-home-mobile.png');
$vendorDesktop = $solutionImg('ab-african-store-desktop.png');
$vendorMobile = $solutionImg('ab-african-store-mobile.png');
?>
<main class="public-main iw-solutions" id="iw-solutions">
  <section class="iw-solutions-intro" aria-labelledby="iw-solutions-title">
    <div class="container public-container">
      <nav class="iw-solutions-crumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span>Our Solutions</span>
      </nav>
      <div class="iw-solutions-title-grid">
        <div>
          <span class="iw-solutions-eyebrow">iWORKS DIGITAL PRODUCTS</span>
          <h1 id="iw-solutions-title">Big possibilities for every small business.</h1>
        </div>
        <div class="iw-solutions-intro-side">
          <p>Our Solutions brings together practical products that help entrepreneurs build, sell and grow. First up: Gansell, a marketplace and free storefront for Nigerian vendors.</p>
          <a href="#gansell" class="iw-solutions-inline-cta">Discover Gansell <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
        </div>
      </div>
    </div>
  </section>

  <section class="iw-gansell-hero" id="gansell" aria-labelledby="iw-gansell-title">
    <div class="container public-container iw-gansell-hero-grid">
      <div class="iw-gansell-hero-copy">
        <div class="iw-gansell-wordmark" aria-label="Gansell">gansell<span>.</span></div>
        <span class="iw-gansell-free"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Free to create your vendor storefront</span>
        <h2 id="iw-gansell-title">Your shop deserves more than a WhatsApp status.</h2>
        <p>Give customers a proper place to discover your business, browse your products and services, add to cart and check out. Gansell brings your own vendor page together with a wider marketplace of Nigerian businesses.</p>
        <div class="iw-gansell-hero-actions">
          <a class="iw-gansell-cta" href="<?= e($gansellHome) ?>" target="_blank" rel="noopener noreferrer">Create your free store <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          <a class="iw-gansell-secondary" href="#gansell-demo">See a real vendor store <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <p class="iw-gansell-note">Storefront setup and product listing are free. Available payment methods and any payment-provider charges depend on the checkout.</p>
      </div>
      <div class="iw-gansell-hero-media" aria-label="Gansell desktop and mobile experiences">
        <figure class="iw-gansell-desktop-shot">
          <div class="iw-gansell-browser-chrome" aria-hidden="true"><span class="iw-gansell-dotrow"><i></i><i></i><i></i></span><span>gansell.com</span><i class="bi bi-arrows-fullscreen"></i></div>
          <img src="<?= e($marketDesktop) ?>" alt="Authentic captured desktop view of the Gansell marketplace" width="1440" height="900" loading="eager" decoding="async">
          <figcaption>Marketplace · desktop</figcaption>
        </figure>
        <figure class="iw-gansell-phone-shot">
          <div class="iw-gansell-phone-shell">
            <div class="iw-gansell-phone-sensor" aria-hidden="true"></div>
            <?php if ($vendorMobile !== ''): ?>
              <img src="<?= e($vendorMobile) ?>" alt="Authentic mobile screenshot of the AB ÁFRICAN FOOD AND MALL vendor storefront on Gansell" width="390" height="844" loading="eager" decoding="async">
            <?php elseif ($marketMobile !== ''): ?>
              <img src="<?= e($marketMobile) ?>" alt="Authentic mobile screenshot of the Gansell marketplace" width="390" height="844" loading="eager" decoding="async">
            <?php else: ?>
              <div class="iw-gansell-phone-fallback" aria-label="Text-based demonstration of a vendor storefront">
                <div class="iw-gansell-fb-brand">gansell.</div>
                <div class="iw-gansell-fb-banner"><small>VENDOR STORE</small><strong>AB ÁFRICAN FOOD AND MALL</strong><span>Food · Home · Beauty</span></div>
                <div class="iw-gansell-fb-browse"><span>Products</span><span>Share store</span></div>
                <div class="iw-gansell-fb-tiles"><span>Food &amp; Drinks</span><span>Home &amp; Living</span><span>Beauty</span></div>
                <div class="iw-gansell-fb-cart"><i class="bi bi-bag"></i> Shopping cart &amp; checkout</div>
              </div>
            <?php endif; ?>
          </div>
          <figcaption><?= $vendorMobile !== '' ? 'AB ÁFRICAN FOOD AND MALL · real mobile capture' : ($marketMobile !== '' ? 'Gansell · real mobile capture' : 'Storefront illustration · see live example below') ?></figcaption>
        </figure>
      </div>
    </div>
    <div class="container public-container">
      <div class="iw-gansell-feature-rail" aria-label="Gansell highlights">
        <span><i class="bi bi-shop" aria-hidden="true"></i> Your own storefront</span>
        <span><i class="bi bi-box-seam" aria-hidden="true"></i> Products &amp; services</span>
        <span><i class="bi bi-share" aria-hidden="true"></i> Shareable links</span>
        <span><i class="bi bi-cart-check" aria-hidden="true"></i> Cart &amp; checkout</span>
      </div>
    </div>
  </section>

  <section class="iw-gansell-benefits" aria-labelledby="iw-gansell-benefits-title">
    <div class="container public-container">
      <div class="iw-gansell-section-head">
        <span class="iw-solutions-eyebrow">SELL SMARTER, NOT HARDER</span>
        <h2 id="iw-gansell-benefits-title">One business. Its own website. Many ways to sell.</h2>
        <p>From the product you post on WhatsApp to an order placed by someone across town, Gansell gives your business a proper online home.</p>
      </div>
      <div class="iw-gansell-benefit-grid">
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-globe2" aria-hidden="true"></i></div><h3>A storefront with your name</h3><p>Your vendor page works like a personal business website, with its own link you can send to customers anytime.</p></article>
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-tags" aria-hidden="true"></i></div><h3>List everything you offer</h3><p>Showcase products with photos, prices and categories, or introduce services your customers can discover.</p></article>
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-whatsapp" aria-hidden="true"></i></div><h3>Share directly to buyers</h3><p>Send a store or product link through WhatsApp, Instagram, Facebook or wherever your customers find you.</p></article>
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></div><h3>A real shopping cart</h3><p>Shoppers can browse, pick quantities, add items to cart and continue to checkout without a separate buyer account.</p></article>
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></div><h3>Flexible checkout</h3><p>Customers can complete an order using the payment options available from that seller, including online payment where enabled.</p></article>
        <article><div class="iw-gansell-benefit-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></div><h3>Delivery on the map</h3><p>Buyers can enter an address, use their live location or arrange pickup and delivery directly with the vendor.</p></article>
      </div>
    </div>
  </section>

  <section class="iw-gansell-proof" id="gansell-demo" aria-labelledby="iw-gansell-demo-title">
    <div class="container public-container iw-gansell-proof-layout">
      <div class="iw-gansell-proof-copy">
        <span class="iw-solutions-eyebrow">SEE A REAL GANSELL STORE</span>
        <h2 id="iw-gansell-demo-title">Not just a listing. A shop customers can actually use.</h2>
        <p>Meet <strong>AB ÁFRICAN FOOD AND MALL</strong>, a live Gansell vendor. Its own shareable shop link brings customers straight to a catalogue with categories, product details, cart actions and checkout.</p>
        <div class="iw-gansell-proof-link">
          <small>SHAREABLE STOREFRONT LINK</small>
          <code>gansell.com/@ab-african</code>
          <a href="<?= e($sampleVendor) ?>" target="_blank" rel="noopener noreferrer">Visit live store <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <p class="iw-gansell-evidence">Public storefront and checkout flows reviewed on 15 November 2026. Product availability and checkout options may change.</p>
      </div>
      <div class="iw-gansell-proof-media">
        <?php if ($vendorDesktop !== ''): ?>
          <figure class="iw-gansell-proof-desktop"><img src="<?= e($vendorDesktop) ?>" alt="Actual desktop screenshot of AB ÁFRICAN FOOD AND MALL Gansell storefront" width="1440" height="1000" loading="lazy" decoding="async"><figcaption>Live vendor storefront · desktop</figcaption></figure>
        <?php else: ?>
          <a class="iw-gansell-proof-link-card" href="<?= e($sampleVendor) ?>" target="_blank" rel="noopener noreferrer"><span class="iw-gansell-proof-card-eyebrow">LIVE SHOP LINK</span><strong>AB ÁFRICAN FOOD AND MALL</strong><span>Browse products · Add to cart · Checkout</span><span class="iw-gansell-proof-link-cta">Open the real storefront <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></a>
        <?php endif; ?>
        <?php if ($vendorMobile !== ''): ?>
          <figure class="iw-gansell-proof-mobile"><img src="<?= e($vendorMobile) ?>" alt="Actual 390-pixel mobile screenshot of the AB ÁFRICAN FOOD AND MALL Gansell vendor store" width="390" height="844" loading="lazy" decoding="async"><figcaption>Live vendor storefront · mobile</figcaption></figure>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="iw-gansell-journey" aria-labelledby="iw-gansell-journey-title">
    <div class="container public-container">
      <div class="iw-gansell-section-head">
        <span class="iw-solutions-eyebrow">MADE FOR NIGERIAN ENTREPRENEURS</span>
        <h2 id="iw-gansell-journey-title">From “send me your price list” to “here's my store.”</h2>
      </div>
      <ol class="iw-gansell-journey-grid">
        <li><span>01</span><h3>Create your vendor page</h3><p>Get started on Gansell and introduce your business. Your storefront is created as part of the vendor setup.</p></li>
        <li><span>02</span><h3>List your products or services</h3><p>Add images, pricing and details that make it easy for buyers to understand what you sell.</p></li>
        <li><span>03</span><h3>Share your link. Receive orders.</h3><p>Put your link on WhatsApp Status or social profiles. Customers browse, add to cart and proceed to checkout.</p></li>
      </ol>
    </div>
  </section>

  <section class="iw-gansell-cost" aria-labelledby="iw-gansell-cost-title">
    <div class="container public-container iw-gansell-cost-layout">
      <div>
        <span class="iw-solutions-eyebrow">A STORE SHOULD NOT BREAK THE BANK</span>
        <h2 id="iw-gansell-cost-title">Start selling online for free.</h2>
        <p>Gansell offers free vendor setup and listings, so you can put your business online without paying for a separate website just to start. Payment-processing charges, if applicable, are separate from storefront setup.</p>
      </div>
      <div class="iw-gansell-cost-number" aria-label="Zero naira storefront setup"><span>₦0</span><small>STOREFRONT SETUP</small></div>
    </div>
  </section>

  <section class="iw-gansell-final" aria-labelledby="iw-gansell-final-title">
    <div class="container public-container iw-gansell-final-layout">
      <div><span class="iw-solutions-eyebrow">EVERYONE CAN SELL.</span><h2 id="iw-gansell-final-title">Your next customer needs a link, not another catalogue photo.</h2><p>Start with Gansell. Make your business easier to find, shop and share.</p></div>
      <a class="iw-gansell-cta" href="<?= e($gansellHome) ?>" target="_blank" rel="noopener noreferrer">Explore Gansell for free <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="iw-solutions-next" aria-labelledby="iw-solutions-next-title">
    <div class="container public-container iw-solutions-next-layout">
      <span class="iw-solutions-eyebrow">OUR SOLUTIONS, GROWING WITH YOU</span>
      <div><h2 id="iw-solutions-next-title">More ways to do business are coming.</h2><p>As new iWORKS products launch, this same page will introduce them. Gansell is the first digital solution currently featured.</p></div>
      <a href="<?= e(public_url('services')) ?>">Explore business services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>
</main>
