<?php
$stories = iworks_editorial_cases(true,80);
$workFilters = [
    'formation' => 'Business formation',
    'web' => 'Web & digital',
    'hosting' => 'Hosting & support',
    'healthcare' => 'Healthcare',
    'travel' => 'Travel',
    'nonprofit' => 'Institutions',
];
$workCounts = array_fill_keys(array_keys($workFilters), 0);
foreach ($stories as $item) {
    foreach (iworks_editorial_case_tags($item) as $tag) {
        if (isset($workCounts[$tag])) $workCounts[$tag]++;
    }
}
?>
<main class="public-main iw-editorial-public iw-public-cases iw-portfolio-work">
  <section class="iw-work-intro" aria-labelledby="iw-work-title">
    <div class="container public-container">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>Our Work</span>
      </nav>
      <div class="iw-work-heading">
        <div>
          <span class="iw-work-eyebrow">SELECTED CLIENT PROJECTS</span>
          <h1 id="iw-work-title">Our Work<span class="iw-work-period">.</span></h1>
          <p>From business formation to websites and long-term digital support. Explore real projects we've delivered with our clients.</p>
        </div>
        <?php if ($stories !== []): ?>
          <span class="iw-work-overview-count"><?= count($stories) ?> <?= count($stories)===1?'project':'projects' ?></span>
        <?php endif; ?>
      </div>
      <?php if ($stories !== []): ?>
      <div class="iw-work-filterbar" aria-label="Filter client projects by service" role="group" data-iw-work-filters>
        <button type="button" class="iw-work-filter is-active" data-iw-filter="all" aria-pressed="true">All work</button>
        <?php foreach($workFilters as $filter=>$label): ?>
          <?php if ($workCounts[$filter] > 0): ?>
            <button type="button" class="iw-work-filter" data-iw-filter="<?= e($filter) ?>" aria-pressed="false"><?= e($label) ?></button>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="iw-work-content" aria-label="Our client projects">
    <div class="container public-container">
      <?php if (!$stories): ?>
        <div class="iw-work-empty">
          <i class="bi bi-journal-richtext" aria-hidden="true"></i>
          <h2>Project stories are being prepared</h2>
          <p>Our published client work will appear here once approved.</p>
        </div>
      <?php else: ?>
        <p class="iw-work-count" data-iw-work-count role="status" aria-live="polite"><?= count($stories) ?> <?= count($stories)===1?'project':'projects' ?> shown</p>
        <div class="iw-work-grid" id="iwWorkGrid">
          <?php foreach($stories as $story): ?>
            <?php
            $clientName = trim((string)($story['client_name']??'')) ?: 'iWORKS client';
            $caseLink = public_url('case_study', ['slug'=>(string)$story['slug']]);
            $siteUrl = iworks_editorial_case_site((string)$story['slug']);
            $siteHost = $siteUrl !== '' ? (string)(parse_url($siteUrl,PHP_URL_HOST) ?: '') : '';
            $categories = implode(' ', iworks_editorial_case_tags($story));
            ?>
            <article class="iw-work-card" data-iw-work-project data-iw-work-categories="<?= e($categories) ?>">
              <a class="iw-work-card-preview" href="<?= e($caseLink) ?>" aria-label="View case study for <?= e($clientName) ?>">
                <span class="iw-work-browser">
                  <span class="iw-work-browser-bar" aria-hidden="true">
                    <span class="iw-work-browser-dots"><i></i><i></i><i></i></span>
                    <span class="iw-work-browser-domain"><?= e($siteHost !== '' ? $siteHost : $clientName) ?></span>
                  </span>
                  <?php if (!empty($story['image_url'])): ?>
                    <img src="<?= e((string)$story['image_url']) ?>" alt="Website preview for <?= e($clientName) ?>" loading="lazy" decoding="async" width="1440" height="900">
                  <?php else: ?>
                    <span class="iw-work-no-image" aria-hidden="true"><i class="bi bi-layout-text-window-reverse"></i></span>
                  <?php endif; ?>
                </span>
                <span class="iw-work-preview-overlay" aria-hidden="true">View project <i class="bi bi-arrow-up-right"></i></span>
              </a>
              <div class="iw-work-card-info">
                <span class="iw-work-card-category"><?= e((string)($story['category']?:'CLIENT PROJECT')) ?></span>
                <h2><a href="<?= e($caseLink) ?>"><?= e($clientName) ?></a></h2>
                <p><?= e((string)($story['summary']??'')) ?></p>
                <div class="iw-work-card-actions">
                  <a href="<?= e($caseLink) ?>" class="iw-work-read">View case study <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                  <?php if ($siteUrl !== ''): ?>
                    <a href="<?= e($siteUrl) ?>" class="iw-work-visit" target="_blank" rel="noopener noreferrer" aria-label="Visit <?= e($clientName) ?> website (opens in a new tab)">Visit website <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <p class="iw-work-no-results" data-iw-work-empty hidden>No projects in this category yet. Choose another filter.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="iw-work-cta" aria-labelledby="iw-work-cta-title">
    <div class="container public-container">
      <div class="iw-work-cta-inner">
        <div>
          <span>YOUR PROJECT COULD BE NEXT</span>
          <h2 id="iw-work-cta-title">Let’s build something that works.</h2>
          <p>Business registration, digital products, hosting and ongoing support—tell us what you need.</p>
        </div>
        <a href="<?= e(public_url('contact')) ?>" class="iw-work-cta-button">Start a conversation <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>
</main>
