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
  <section class="iw-work-intro">
    <div class="container public-container">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>Case studies</span>
      </nav>
      <h1>Case studies</h1>
      <p>Businesses and organisations we've worked with, from registration and websites to hosting and long-term support.</p>
    </div>
  </section>

  <section class="iw-work-content" aria-label="Client case studies">
    <div class="container public-container">
      <?php if (!$stories): ?>
        <div class="iw-work-empty">
          <h2>Case studies are being prepared</h2>
          <p>Published client projects will appear here.</p>
        </div>
      <?php else: ?>
        <div class="iw-work-filters-row">
          <div class="iw-work-filterbar" aria-label="Filter case studies by service" role="group" data-iw-work-filters>
            <button type="button" class="iw-work-filter is-active" data-iw-filter="all" aria-pressed="true">All case studies</button>
            <?php foreach($workFilters as $filter=>$label): ?>
              <?php if ($workCounts[$filter]>0): ?>
                <button type="button" class="iw-work-filter" data-iw-filter="<?= e($filter) ?>" aria-pressed="false"><?= e($label) ?></button>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <p class="iw-work-count" data-iw-work-count role="status" aria-live="polite"><?= count($stories) ?> <?= count($stories)===1?'case study':'case studies' ?></p>
        </div>
        <div class="iw-work-grid" id="iwWorkGrid">
          <?php foreach($stories as $story): ?>
            <?php
            $clientName = trim((string)($story['client_name']??'')) ?: 'iWORKS client';
            $caseLink = public_url('case_study',['slug'=>(string)$story['slug']]);
            $siteUrl = iworks_editorial_case_site((string)$story['slug']);
            $categories = implode(' ',iworks_editorial_case_tags($story));
            ?>
            <article class="iw-work-card" data-iw-work-project data-iw-work-categories="<?= e($categories) ?>" data-iw-reveal>
              <a class="iw-work-card-preview" href="<?= e($caseLink) ?>" aria-label="Read the <?= e($clientName) ?> case study">
                <?php if (!empty($story['image_url'])): ?>
                  <img src="<?= e((string)$story['image_url']) ?>" alt="Screenshot of the <?= e($clientName) ?> website" loading="lazy" decoding="async" width="1440" height="900">
                <?php else: ?>
                  <span class="iw-work-no-image" aria-hidden="true"><i class="bi bi-image"></i></span>
                <?php endif; ?>
              </a>
              <div class="iw-work-card-info">
                <span class="iw-work-card-category"><?= e((string)($story['category']?:'Client project')) ?></span>
                <h2><a href="<?= e($caseLink) ?>"><?= e($clientName) ?></a></h2>
                <p><?= e((string)($story['summary']??'')) ?></p>
                <div class="iw-work-card-actions">
                  <a href="<?= e($caseLink) ?>" class="iw-work-read">Read case study</a>
                  <?php if ($siteUrl!==''): ?>
                    <a href="<?= e($siteUrl) ?>" class="iw-work-visit" target="_blank" rel="noopener noreferrer" aria-label="Visit <?= e($clientName) ?> website (opens in a new tab)">Visit website</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <p class="iw-work-no-results" data-iw-work-empty hidden>No case studies match this category.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
