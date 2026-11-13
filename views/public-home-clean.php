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
$heroImage = trim((string) ($heroAsset['url'] ?? '')) ?: iworks_stock_photo_url('consultation', 1100);

$heroPrimary = is_array($hero['primaryCta'] ?? null) ? $hero['primaryCta'] : [];
$primaryLabel = trim((string) ($heroPrimary['label'] ?? ''));
$primaryLabel = in_array($primaryLabel, ['', 'Explore our services', 'Get Started Now'], true) ? 'Explore services' : $primaryLabel;
$primaryHref = normalize_public_href(
    trim((string) ($heroPrimary['href'] ?? public_url('services'))),
    public_url('services')
);

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

$businessUrl = $findService(['business name registration', 'company registration'], 'business-registration');
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

  <section class="iw-clean-hero" aria-labelledby="iw-clean-title">
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
        <img src="<?= e($heroImage) ?>" alt="Professionals reviewing business documents" width="640" height="510" fetchpriority="high" decoding="async">
      </figure>
    </div>
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

  <section class="iw-clean-section iw-clean-support" aria-labelledby="iw-clean-support-title">
    <div class="container public-container iw-clean-support-inner">
      <div class="iw-clean-support-photo">
        <img src="<?= e(iworks_stock_photo_url('strategy', 1000)) ?>" alt="Business professionals discussing documents" loading="lazy" decoding="async" width="560" height="420">
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
        <?php foreach ($featuredInsights as $insight): ?>
          <?php $insightImage = trim((string)($insight['featured_image']['url']??'')); ?>
          <article class="iw-home-insight">
            <a href="<?= e(public_url('blog_post',['slug'=>(string)($insight['slug']??'')])) ?>">
              <div class="iw-home-insight-media <?= $insightImage===''?'iw-editorial-abstract':'' ?>">
                <?php if($insightImage!==''): ?><img src="<?= e($insightImage) ?>" loading="lazy" alt="">
                <?php else: ?><i class="bi bi-journal-richtext" aria-hidden="true"></i><?php endif; ?>
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

  <?php if($homeCases): ?>
    <section class="iw-home-case-stories" aria-labelledby="iw-home-cases-title"><div class="container public-container">
      <div class="iw-home-case-head"><span>CASE STUDIES</span><h2 id="iw-home-cases-title">Stories of real progress.</h2></div>
      <div class="iw-home-case-carousel" id="iwHomeCases">
      <?php foreach($homeCases as $idx=>$story): ?>
        <article class="iw-home-case-slide" <?= $idx>0?'hidden':'' ?>>
          <div class="iw-home-case-copy"><span><?= e((string)$story['category']) ?></span>
            <h3><?= e((string)$story['title']) ?></h3>
            <p><?= e((string)$story['summary']) ?></p>
            <a href="<?= e(public_url('case_study',['slug'=>(string)$story['slug']])) ?>">Read case study <i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="iw-home-case-visual">
            <?php if(!empty($story['logo_url'])): ?><img src="<?= e((string)$story['logo_url']) ?>" alt="<?= e((string)$story['client_name']) ?>" loading="lazy">
            <?php else: ?><i class="bi bi-building-check" aria-hidden="true"></i><?php endif; ?>
            <small><?= e((string)$story['client_name']) ?></small>
            <?php if(!empty($story['outcome'])): ?><strong><?= e((string)$story['outcome']) ?></strong><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      </div>
      <div class="iw-home-case-actions"><a href="<?= e(public_url('case-studies')) ?>">All case studies <i class="bi bi-arrow-right"></i></a>
        <div><button type="button" data-iw-case-prev aria-label="Previous case study"><i class="bi bi-arrow-left"></i></button>
          <button type="button" data-iw-case-next aria-label="Next case study"><i class="bi bi-arrow-right"></i></button></div></div>
    </div></section>
    <script>
    (function(){
      var slides=Array.from(document.querySelectorAll('#iwHomeCases .iw-home-case-slide'));
      if(!slides.length)return;var at=0;
      function show(next){at=(next+slides.length)%slides.length;slides.forEach(function(slide,i){slide.hidden=i!==at;});}
      var prev=document.querySelector('[data-iw-case-prev]');
      var next=document.querySelector('[data-iw-case-next]');
      if(prev)prev.addEventListener('click',function(){show(at-1)});
      if(next)next.addEventListener('click',function(){show(at+1)});
    })();
    </script>
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