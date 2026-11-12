<?php $stories=iworks_editorial_cases(true,48); ?>
<main class="public-main iw-editorial-public iw-public-cases">
  <section class="iw-cases-intro"><div class="container public-container">
    <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right"></i><span>Case studies</span></nav>
    <span class="iw-editorial-eyebrow">CUSTOMER STORIES</span>
    <h1>Work that speaks for itself.</h1>
    <p>Real projects and business milestones supported by iWorks Global.</p>
  </div></section>
  <section class="iw-cases-content"><div class="container public-container">
    <?php if (!$stories): ?>
      <div class="iw-insights-empty">
        <i class="bi bi-briefcase"></i><h2>Stories are being prepared</h2>
        <p>Approved customer projects will appear here as they are published.</p>
      </div>
    <?php else: ?>
      <div class="iw-cases-grid">
        <?php foreach($stories as $story): ?>
          <article class="iw-cases-card">
            <a href="<?= e(public_url('case_study',['slug'=>(string)$story['slug']])) ?>">
              <div class="iw-cases-card-media <?= empty($story['image_url'])?'iw-editorial-abstract':'' ?>">
                <?php if(!empty($story['image_url'])): ?><img src="<?= e((string)$story['image_url']) ?>" alt="" loading="lazy">
                <?php else: ?><i class="bi bi-briefcase" aria-hidden="true"></i><?php endif; ?>
              </div>
              <div class="iw-cases-card-info">
                <span><?= e((string)($story['category']?:'CASE STUDY')) ?></span>
                <div class="iw-cases-card-brand">
                  <?php if(!empty($story['logo_url'])): ?><img src="<?= e((string)$story['logo_url']) ?>" alt="<?= e((string)$story['client_name']) ?>" loading="lazy"><?php endif; ?>
                  <strong><?= e((string)($story['client_name']?:'iWorks client')) ?></strong>
                </div>
                <h2><?= e((string)$story['title']) ?></h2>
                <p><?= e((string)($story['summary']??'')) ?></p>
                <b>View case study <i class="bi bi-arrow-up-right"></i></b>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="iw-cases-bottom-cta"><div><span>YOUR NEXT MILESTONE</span><h2>Ready to work with iWorks?</h2>
      <p>Register your business, protect your brand or bring a digital product to life.</p></div>
      <a href="<?= e(public_url('services')) ?>">Explore our services <i class="bi bi-arrow-up-right"></i></a></div>
  </div></section>
</main>
