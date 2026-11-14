<?php
/**
 * iWorks public homepage — deliberately minimal.
 *
 * Uses the existing published CMS and service catalog. This view does not
 * create or alter orders, payments, customer accounts or service records.
 */
require_once __DIR__ . '/../app/public-photo-config.php';

$sections = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (!is_array($block)) {
        continue;
    }
    $type = normalize_page_block_type((string) ($block['type'] ?? ''));
    if ($type !== '' && !array_key_exists($type, $sections)) {
        $sections[$type] = is_array($block['data'] ?? null) ? $block['data'] : [];
    }
}

$hero = $sections['hero'] ?? [];
$featured = $sections['featured_services'] ?? [];
$how = $sections['how_it_works'] ?? [];
$faq = $sections['faq'] ?? [];
$banner = $sections['cta_banner'] ?? [];

$defaultHeadlines = [
    'Launch and grow your business remotely with iWorks Global.',
    'Launch and grow your business remotely with iWorks Global',
    'Start, protect and grow your business.',
];
$oldHeadline = trim((string) ($hero['headline'] ?? ''));
$headline = $oldHeadline !== '' && !in_array($oldHeadline, $defaultHeadlines, true)
    ? $oldHeadline
    : 'Business registration and website design in Nigeria.';

$defaultIntroductions = [
    'CAC registration, trademark filing, NAFDAC-related support, branding, growth services, and structured business operations handled in one portal.',
    'Business registration, trademark and digital services. Simple steps, helpful support and updates in one place.',
];
$oldIntroduction = trim((string) ($hero['subheadline'] ?? ''));
$introduction = $oldIntroduction !== '' && !in_array($oldIntroduction, $defaultIntroductions, true)
    ? $oldIntroduction
    : 'Register a business name or company in Nigeria with CAC filing support. Request website design, trademarks and digital services remotely, and track progress online.';

$heroAsset = !empty($hero['imageId']) ? get_media_asset((int) $hero['imageId']) : null;
$cmsHeroImage = trim((string) ($heroAsset['url'] ?? ''));
$heroImage = iworks_select_authentic_photo('hero-desktop', $cmsHeroImage) ?: iworks_stock_photo_url('consultation', 1100);
$heroMobileImage = $heroImage === iworks_authentic_photo_url('hero-desktop') ? iworks_authentic_photo_url('hero-mobile') : '';

$heroPrimary = is_array($hero['primaryCta'] ?? null) ? $hero['primaryCta'] : [];
$primaryLabel = trim((string) ($heroPrimary['label'] ?? ''));
$primaryLabel = in_array($primaryLabel, ['', 'Explore our services', 'Get Started Now'], true) ? 'Explore services' : $primaryLabel;
$primaryHref = normalize_public_href(
    trim((string) ($heroPrimary['href'] ?? public_url('services'))),
    public_url('services')
);
// Old CMS defaults pointed the public Explore Services CTA at the private
// portal host, which can render a management route instead of the catalogue.
$heroUrlHost = strtolower((string)(parse_url($primaryHref, PHP_URL_HOST) ?? ''));
$portalUrlHost = function_exists('portal_base_url')
    ? strtolower((string)(parse_url(portal_base_url(), PHP_URL_HOST) ?? ''))
    : 'portal.iworksgt.com';
$heroUrlPath = rtrim((string)(parse_url($primaryHref, PHP_URL_PATH) ?? ''), '/');
if ($heroUrlHost !== '' && $heroUrlHost === $portalUrlHost
    && in_array($heroUrlPath, ['/services','/index.php'], true)) {
    $primaryHref = public_url('services');
}


$catalog = array_values(array_filter(is_array($publicServices ?? null) ? $publicServices : [], static function ($service): bool {
    if (!is_array($service)) {
        return false;
    }
    $name = strtolower(trim((string) ($service['name'] ?? '')));
    $slug = trim((string) ($service['slug'] ?? ''));
    if ($name === '' || $slug === '') {
        return false;
    }
    if ($name === 'custom service' && (float) ($service['price'] ?? 0) <= 0) {
        return false;
    }
    foreach (['banking', 'bank account', 'loan', 'payment gateway', 'wallet'] as $excluded) {
        if (str_contains($name, $excluded)) {
            return false;
        }
    }
    return true;
}));

$findService = static function (array $terms, string $category) use ($catalog): string {
    foreach ($terms as $term) {
        foreach ($catalog as $service) {
            if (str_contains(strtolower((string) ($service['name'] ?? '')), $term)) {
                return public_url('service', ['slug' => (string) $service['slug']]);
            }
        }
    }
    return public_url('services', ['category' => $category]);
};

$businessUrl = $findService(['business registration','company registration','business name registration'], 'business-registration');
$trademarkUrl = $findService(['trademark'], 'trademark');
$websiteUrl = $findService(['website design', 'domain & web hosting'], 'growth');

// Admin-selected featured service IDs can update category destinations
// without expanding the homepage into another long product grid.
$selectedIds = array_values(array_filter(array_map('intval', is_array($featured['serviceIds'] ?? null) ? $featured['serviceIds'] : [])));
foreach ($selectedIds as $id) {
    foreach ($catalog as $service) {
        if ((int) ($service['id'] ?? 0) !== $id) {
            continue;
        }
        $category = strtolower((string) ($service['category'] ?? ''));
        $link = public_url('service', ['slug' => (string) $service['slug']]);
        if ($category === 'business-registration') { $businessUrl = $link; }
        if ($category === 'trademark') { $trademarkUrl = $link; }
        if ($category === 'growth') { $websiteUrl = $link; }
        break;
    }
}

$serviceHeading = trim((string) ($featured['title'] ?? ''));
if ($serviceHeading === '' || $serviceHeading === 'Services entrepreneurs use most') {
    $serviceHeading = 'What do you need help with?';
}

$steps = array_values(array_filter(is_array($how['steps'] ?? null) ? $how['steps'] : [], 'is_array'));
if ($steps === []) {
    $steps = [
        ['title' => 'Choose a service', 'text' => 'Pick the service that suits your business.'],
        ['title' => 'Submit your details', 'text' => 'Complete checkout and upload the required information.'],
        ['title' => 'Follow progress', 'text' => 'Get updates and final files in your portal.'],
    ];
}
$faqs = array_values(array_filter(is_array($faq['items'] ?? null) ? $faq['items'] : [], 'is_array'));
// Keep CMS questions first, then fill gaps with common remote-business questions.
$moreFaqs = [
    ['q' => 'Can I use iWorks if I live outside Nigeria?', 'a' => 'Yes. We support customers abroad who need to register, manage or grow a Nigerian business remotely. We will explain any document or regulator requirements before proceeding.'],
    ['q' => 'Can I complete the process remotely?', 'a' => 'Our services are designed for remote ordering, document submission and progress tracking. We will notify you if a specific authority requires additional verification.'],
    ['q' => 'Can I pay in naira or US dollars?', 'a' => 'iWorks supports NGN and USD where the selected service and checkout gateway allow. You will see the available currency and payment options before confirming your order.'],
    ['q' => 'How do I check my order status?', 'a' => 'Enter your job ID and the email or phone used for your order. You can also sign in to view updates and documents.'],
    ['q' => 'What do I need before ordering?', 'a' => 'Each service page explains its requirements. You can provide your information and upload documents during the ordering process.'],
];
$existingQuestions = [];
foreach ($faqs as $item) {
    $q = trim((string) ($item['q'] ?? $item['question'] ?? ''));
    if ($q !== '') { $existingQuestions[] = strtolower($q); }
}
foreach ($moreFaqs as $item) {
    if (!in_array(strtolower($item['q']), $existingQuestions, true)) {
        $faqs[] = $item;
        $existingQuestions[] = strtolower($item['q']);
    }
}
$faqs = array_slice($faqs, 0, 8);

$featuredInsights = [];
try { $featuredInsights = list_blog_posts_public(4); } catch (Throwable $e) { $featuredInsights = []; }
$homeCases = function_exists('iworks_editorial_cases') ? iworks_editorial_cases(true, 5) : [];
$homeFeedback = function_exists('iworks_editorial_reviews') ? iworks_editorial_reviews(true, 3) : [];

// A neutral, non-clickable client-logo strip: only actual published case
// studies and consented customer feedback may supply brands. Never fabricate
// clients or promote drafts. Duplicate logos are filtered by normalized name.
$homeBrandLogos = [];
// Restore the original Website Editor > Trusted Logos content to the active
// homepage. Admin-curated logos are displayed before derived case/review logos.
$trustedLogoItems = is_array(($sections['trusted_logos']['items'] ?? null))
    ? $sections['trusted_logos']['items'] : [];
foreach ($trustedLogoItems as $item) {
    if (!is_array($item)) { continue; }
    $name = trim((string) ($item['title'] ?? ''));
    $imageId = (int) ($item['imageId'] ?? 0);
    $asset = $imageId > 0 ? get_media_asset($imageId) : null;
    $logo = trim((string) ($asset['url'] ?? ''));
    if ($name !== '' && $logo !== '') {
        $homeBrandLogos[strtolower($name)] = ['name' => $name, 'logo' => $logo];
    }
}
$logoCaseRecords = function_exists('iworks_editorial_cases') ? iworks_editorial_cases(true, 48) : [];
$logoReviewRecords = function_exists('iworks_editorial_reviews') ? iworks_editorial_reviews(true, 48) : [];
foreach (array_merge($logoCaseRecords, $logoReviewRecords) as $record) {
    if (!is_array($record)) { continue; }
    $name = trim((string) ($record['client_name'] ?? $record['company'] ?? ''));
    $logo = trim((string) ($record['logo_url'] ?? ''));
    $key = strtolower($name);
    if ($name !== '' && $logo !== '' && !isset($homeBrandLogos[$key])) {
        $homeBrandLogos[$key] = ['name' => $name, 'logo' => $logo];
    }
}
$homeBrandLogos = array_values($homeBrandLogos);

$bannerTitle = trim((string) ($banner['headline'] ?? ''));
if ($bannerTitle === '' || $bannerTitle === 'Launch faster with the right support stack.') {
    $bannerTitle = 'Ready to get started?';
}
?>
<main class="public-main iw-clean-home" id="main-content">
  <?php if ($message = flash('success')): ?>
    <div class="container public-container pt-3"><div class="alert alert-success"><?= e($message) ?></div></div>
  <?php endif; ?>
  <?php if ($message = flash('error')): ?>
    <div class="container public-container pt-3"><div class="alert alert-danger"><?= e($message) ?></div></div>
  <?php endif; ?>

  <section class="iw-clean-hero<?= $homeBrandLogos !== [] ? ' has-client-logos' : '' ?>" aria-labelledby="iw-clean-title">
    <div class="container public-container iw-clean-hero-inner">
      <div class="iw-clean-hero-copy">
        <span class="iw-clean-overline">iWORKS GLOBAL</span>
        <h1 id="iw-clean-title"><?= e($headline) ?></h1>
        <p><?= e($introduction) ?></p>
        <div class="iw-clean-actions">
          <a class="iw-clean-btn iw-clean-btn-primary" href="<?= e($primaryHref) ?>"><?= e($primaryLabel) ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          <a class="iw-clean-text-link" href="<?= e($businessUrl) ?>">Register a business <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <p class="iw-clean-detail">Order online <span aria-hidden="true">·</span> Submit documents <span aria-hidden="true">·</span> Track your request</p>
      </div>
      <figure class="iw-clean-hero-media">
        <picture>
          <?php if ($heroMobileImage !== ''): ?><source media="(max-width: 767px)" srcset="<?= e($heroMobileImage) ?>"><?php endif; ?>
          <img src="<?= e($heroImage) ?>" alt="<?= e($heroMobileImage !== '' ? 'Black business professional working on a laptop in an office' : 'Professionals reviewing business documents') ?>" width="640" height="510" fetchpriority="high" decoding="async">
        </picture>
      </figure>
    </div>
    <?php if ($homeBrandLogos !== []): ?>
      <div class="container public-container iw-home-brand-ribbon" aria-label="Brands we have worked with">
        <p class="iw-home-brand-caption">Selected brands we've worked with</p>
        <div class="iw-home-brand-window">
          <div class="iw-home-brand-track <?= count($homeBrandLogos) >= 4 ? 'is-scrolling' : '' ?>">
            <div class="iw-home-brand-sequence">
              <?php foreach ($homeBrandLogos as $brand): ?>
                <span class="iw-home-brand-mark" title="<?= e($brand['name']) ?>">
                  <img src="<?= e($brand['logo']) ?>" alt="<?= e($brand['name']) ?>" loading="lazy" decoding="async">
                </span>
              <?php endforeach; ?>
            </div>
            <?php if (count($homeBrandLogos) >= 4): ?>
              <div class="iw-home-brand-sequence" aria-hidden="true">
                <?php foreach ($homeBrandLogos as $brand): ?>
                  <span class="iw-home-brand-mark">
                    <img src="<?= e($brand['logo']) ?>" alt="" loading="lazy" decoding="async">
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <section class="iw-clean-section iw-clean-services" aria-labelledby="iw-clean-services-title">
    <div class="container public-container">
      <div class="iw-clean-section-head">
        <h2 id="iw-clean-services-title"><?= e($serviceHeading) ?></h2>
        <p>Choose a starting point. Each service connects to your iWorks account.</p>
      </div>
      <div class="iw-clean-service-grid">
        <a class="iw-clean-service" href="<?= e($businessUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
          <h3>Business registration</h3>
          <p>Business names, companies and Corporate Affairs Commission filings in Nigeria.</p>
          <span>Explore registration <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="iw-clean-service" href="<?= e($trademarkUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
          <h3>Licences &amp; compliance</h3>
          <p>Trademark, NAFDAC documentation, export licensing and regulatory filings.</p>
          <span>Explore services <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
        <a class="iw-clean-service" href="<?= e($websiteUrl) ?>">
          <span class="iw-clean-service-icon"><i class="bi bi-window" aria-hidden="true"></i></span>
          <h3>Web &amp; digital services</h3>
          <p>Web development, hosting, design and marketing.</p>
          <span>Explore digital <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
      </div>
      <a class="iw-clean-all-services" href="<?= e(public_url('services')) ?>">View all services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      <p class="iw-home-compliance-extra">Looking for something else? <a href="<?= e(public_url('services')) ?>">Explore the full service catalogue <i class="bi bi-arrow-right" aria-hidden="true"></i></a></p>
    </div>
  </section>

  <section class="iw-clean-section iw-clean-process" aria-labelledby="iw-clean-process-title">
    <div class="container public-container">
      <div class="iw-clean-section-head">
        <h2 id="iw-clean-process-title">How it works</h2>
        <p>Choose a service, submit your details and track progress in your portal.</p>
      </div>
      <div class="iw-clean-step-grid">
        <?php foreach (array_slice($steps, 0, 3) as $index => $step): ?>
          <div class="iw-clean-step">
            <span>Step <?= $index + 1 ?></span>
            <h3><?= e((string) ($step['title'] ?? '')) ?></h3>
            <p><?= e((string) ($step['text'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <section class="iw-home-case-stories" aria-labelledby="iw-home-cases-title">
    <div class="container public-container">
      <div class="iw-home-case-head">
        <span>SELECTED WORK</span>
        <h2 id="iw-home-cases-title">Real projects. Real progress.</h2>
        <p>Explore how we support businesses with registration, digital products, brand protection and ongoing services.</p>
      </div>
      <?php if ($homeCases !== []): ?>
        <div class="iw-home-case-grid">
          <?php foreach (array_slice($homeCases, 0, 3) as $story): ?>
            <article class="iw-home-case-card">
              <a href="<?= e(public_url('case_study', ['slug' => (string) $story['slug']])) ?>">
                <div class="iw-home-case-cover">
                  <?php if (trim((string) ($story['image_url'] ?? '')) !== ''): ?>
                    <img src="<?= e((string) $story['image_url']) ?>" alt="" loading="lazy" decoding="async" width="540" height="330">
                  <?php elseif (trim((string) ($story['logo_url'] ?? '')) !== ''): ?>
                    <img class="iw-home-case-logo-only" src="<?= e((string) $story['logo_url']) ?>" alt="" loading="lazy" decoding="async" width="540" height="330">
                  <?php else: ?>
                    <div class="iw-home-case-no-image" aria-hidden="true"><i class="bi bi-briefcase"></i></div>
                  <?php endif; ?>
                </div>
                <div class="iw-home-case-card-copy">
                  <small><?= e((string) (($story['category'] ?? '') ?: 'CLIENT PROJECT')) ?></small>
                  <h3><?= e((string) $story['title']) ?></h3>
                  <p><?= e((string) ($story['summary'] ?? '')) ?></p>
                  <span>Read case study <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="iw-home-case-pending">We are preparing documented client projects for publication. Only approved case studies will appear here.</p>
      <?php endif; ?>
      <a class="iw-home-case-all" href="<?= e(public_url('case-studies')) ?>">Explore case studies <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="iw-clean-section iw-clean-support" aria-labelledby="iw-clean-support-title">
    <div class="container public-container iw-clean-support-inner">
      <div class="iw-clean-support-photo">
        <img src="<?= e(iworks_authentic_photo_url('support-agent') ?: iworks_stock_photo_url('strategy', 1000)) ?>" alt="Black customer support professional wearing a headset while working on a laptop" loading="lazy" decoding="async" width="560" height="420">
      </div>
      <div class="iw-clean-support-copy">
        <span class="iw-clean-overline">WHY iWORKS</span>
        <h2 id="iw-clean-support-title">Support that stays with you.</h2>
        <p>Find what you need, send your details and follow your job without juggling messages and files across different platforms.</p>
        <form class="iw-home-track" method="post" action="<?= e(public_url('home')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="public_track">
          <label for="iwHomeJobId">Job ID</label>
          <input id="iwHomeJobId" name="job_code" required maxlength="80" placeholder="Your job ID" autocomplete="off">
          <label for="iwHomeIdentifier">Email or phone used for the order</label>
          <input id="iwHomeIdentifier" name="identifier" required maxlength="254" placeholder="Email or phone" autocomplete="off">
          <button class="iw-clean-btn iw-clean-btn-primary" type="submit">Track an existing order <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
        <a class="iw-home-track-alt" href="<?= e(public_url('track')) ?>">Open the full tracking page <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        <?php if (!empty($publicTrackResult)): ?>
          <div class="iw-home-track-result" role="status">
            <strong><?= e((string) ($publicTrackResult['service_name'] ?? 'Service')) ?></strong>
            <span>Job <?= e((string) ($publicTrackResult['job_code'] ?? '')) ?> · <?= e(ucfirst((string) ($publicTrackResult['status'] ?? 'pending'))) ?></span>
            <a href="<?= e(portal_url('login')) ?>" data-iw-auth="login">Sign in to see full details</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($faqs !== []): ?>
    <section class="iw-clean-section iw-clean-faq" aria-labelledby="iw-clean-faq-title">
      <div class="container public-container iw-clean-faq-inner">
        <div class="iw-clean-faq-intro">
          <img src="https://images.pexels.com/photos/8866777/pexels-photo-8866777.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=900" alt="Customer support professional available to answer business enquiries" width="780" height="960" loading="lazy" decoding="async">
          <div class="iw-clean-faq-intro-content">
            <h2 id="iw-clean-faq-title">Common questions</h2>
            <p>Need something else? <a href="mailto:support@iworksgt.com" class="iw-home-help-link" data-iw-help-open>Talk to our team <i class="bi bi-chat-dots" aria-hidden="true"></i></a></p>
          </div>
        </div>
        <div class="iw-clean-faq-items">
          <?php foreach ($faqs as $item): ?>
            <?php
            $question = trim((string) ($item['q'] ?? $item['question'] ?? ''));
            $answer = trim((string) ($item['a'] ?? $item['answer'] ?? ''));
            if ($question === '') { continue; }
            ?>
            <details>
              <summary><?= e($question) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
              <p><?= e($answer) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($featuredInsights !== []): ?>
  <section class="iw-clean-section iw-home-insights" aria-labelledby="iw-home-insights-title">
    <div class="container public-container">
      <div class="iw-home-insights-head">
        <div><span class="iw-clean-overline">BUSINESS INSIGHTS</span>
          <h2 id="iw-home-insights-title">Ideas for your next move</h2>
          <p>Practical guidance on registration, compliance and growing your business.</p>
        </div>
        <a href="<?= e(public_url('blog')) ?>">All insights <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
      <div class="iw-home-insights-carousel" id="iwHomeInsights" tabindex="0" aria-label="Recent insights" aria-roledescription="carousel">
        <?php foreach ($featuredInsights as $index => $insight): ?>
          <?php
            $insightImage = iworks_blog_photo_url($insight);
            $insightTitle = trim((string)($insight['title']??'Business insight'));
            $insightTag = trim((string)($insight['tag']??'')) ?: 'Insights';
          ?>
          <article class="iw-home-insight">
            <a href="<?= e(public_url('blog_post',['slug'=>(string)($insight['slug']??'')])) ?>">
              <div class="iw-home-insight-media <?= $insightImage===''?'iw-home-journal-no-cover':'' ?>" data-art-variant="<?= (int)($index % 4) + 1 ?>">
                <?php if($insightImage!==''): ?>
                  <img src="<?= e($insightImage) ?>" loading="lazy" decoding="async" alt="">
                <?php else: ?>
                  <div class="iw-home-journal-art" aria-hidden="true">
                    <span class="iw-home-journal-brand">iWORKS <em>GLOBAL</em></span>
                    <strong><?= e($insightTitle) ?></strong>
                    <span class="iw-home-journal-tag"><?= e($insightTag) ?></span>
                  </div>
                <?php endif; ?>
                <span class="iw-home-read-overlay">Read article <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
              </div>
              <div class="iw-home-insight-copy">
                <span class="iw-home-insight-category"><?= e((string)($insight['tag']?:'INSIGHTS')) ?></span>
                <h3><?= e((string)($insight['title']??'Business insight')) ?></h3>
                <p><?= e(mb_safe_trim(trim((string)($insight['excerpt']??'')),125)) ?></p>
                <strong>Read insight <i class="bi bi-arrow-right" aria-hidden="true"></i></strong>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="iw-home-insights-controls" aria-label="Insight carousel controls">
        <button type="button" data-iw-insights-prev aria-label="Previous insight"><i class="bi bi-arrow-left"></i></button>
        <button type="button" data-iw-insights-next aria-label="Next insight"><i class="bi bi-arrow-right"></i></button>
      </div>
    </div>
  </section>
  <script>
  (function(){
    var rail=document.getElementById('iwHomeInsights');
    if(!rail||rail.children.length<2)return;
    var pause=false,delay=6500;
    function move(dir){
      var card=rail.querySelector('.iw-home-insight');if(!card)return;
      var amount=card.getBoundingClientRect().width+16;
      var atEnd=rail.scrollLeft+rail.clientWidth>=rail.scrollWidth-amount*.65;
      if(dir>0&&atEnd)rail.scrollTo({left:0,behavior:'smooth'});
      else rail.scrollBy({left:dir*amount,behavior:'smooth'});
    }
    var prev=document.querySelector('[data-iw-insights-prev]');
    var next=document.querySelector('[data-iw-insights-next]');
    if(prev)prev.addEventListener('click',function(){move(-1)});
    if(next)next.addEventListener('click',function(){move(1)});
    rail.addEventListener('mouseenter',function(){pause=true});
    rail.addEventListener('mouseleave',function(){pause=false});
    rail.addEventListener('focusin',function(){pause=true});
    rail.addEventListener('focusout',function(){pause=false});
    if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      window.setInterval(function(){if(!pause&&!document.hidden)move(1)},delay);
    }
  })();
  </script>
  <?php endif; ?>

  <section class="iw-home-benefits" aria-labelledby="iw-benefits-title">
    <div class="container public-container">
      <div class="iw-home-benefits-head"><span>WHY iWORKS</span><h2 id="iw-benefits-title">Business support that moves with you.</h2></div>
      <div class="iw-home-benefits-grid">
        <article><div class="iw-benefit-art iw-benefit-art-document" aria-hidden="true">
          <span class="iw-benefit-art-orbit"></span><i class="bi bi-file-earmark-check"></i><span class="iw-benefit-art-stamp"><i class="bi bi-patch-check-fill"></i></span></div>
          <h3>Guided registrations</h3><p>A clear application process, with everything needed for your filing in one place.</p></article>
        <article><div class="iw-benefit-art iw-benefit-art-progress" aria-hidden="true">
          <span class="iw-benefit-art-orbit"></span><i class="bi bi-clipboard-data"></i><span class="iw-benefit-art-stamp"><i class="bi bi-chat-square-dots"></i></span></div>
          <h3>Visible progress</h3><p>Track your order, review updates and communicate with the team handling your request.</p></article>
        <article><div class="iw-benefit-art iw-benefit-art-payment" aria-hidden="true">
          <span class="iw-benefit-art-orbit"></span><i class="bi bi-credit-card-2-front"></i><span class="iw-benefit-art-stamp"><i class="bi bi-shield-lock-fill"></i></span></div>
          <h3>Flexible payment options</h3><p>Pay through the supported provider for your invoice currency and manage billing online.</p></article>
      </div>
    </div>
  </section>

  <?php if($homeFeedback): ?>
    <section class="iw-home-feedback" aria-labelledby="iw-home-feedback-title"><div class="container public-container">
      <div class="iw-editorial-section-head"><div><span>CLIENT VOICES</span><h2 id="iw-home-feedback-title">Feedback from our customers</h2></div></div>
      <div class="iw-home-feedback-grid">
        <?php foreach($homeFeedback as $feedback): ?>
          <article class="iw-home-feedback-card">
            <div class="iw-feedback-logo">
              <?php if(!empty($feedback['logo_url'])): ?><img src="<?= e((string)$feedback['logo_url']) ?>" alt="<?= e((string)$feedback['company']) ?>" loading="lazy">
              <?php else: ?><strong><?= e((string)($feedback['company']?:'iWorks client')) ?></strong><?php endif; ?>
            </div>
            <blockquote><?= e((string)$feedback['quote_text']) ?></blockquote>
            <div class="iw-feedback-person">
              <span class="iw-feedback-avatar">
                <?php if(!empty($feedback['portrait_url'])): ?><img src="<?= e((string)$feedback['portrait_url']) ?>" alt="" loading="lazy">
                <?php else: ?><i class="bi bi-person" aria-hidden="true"></i><?php endif; ?>
              </span>
              <span><strong><?= e((string)$feedback['customer_name']) ?></strong>
                <small><?= e(trim((string)$feedback['position'].' · '.(string)$feedback['company'],' ·')) ?></small></span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div></section>
  <?php endif; ?>


  <section class="iw-clean-last" aria-labelledby="iw-clean-last-title">
    <div class="container public-container iw-clean-last-inner">
      <div>
        <h2 id="iw-clean-last-title"><?= e($bannerTitle) ?></h2>
        <p>Browse a service or speak with our team.</p>
      </div>
      <a class="iw-clean-btn iw-clean-btn-primary" href="<?= e(public_url('services')) ?>">Explore services <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <?php require __DIR__ . '/partials/public-review-carousel.php'; ?>
</main>