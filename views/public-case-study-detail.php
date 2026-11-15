<?php
$record = $caseRecord;
$clientName = trim((string)($record['client_name']??'')) ?: 'iWORKS client';
$siteUrl = iworks_editorial_case_site((string)($record['slug']??''));
$serviceTags = iworks_editorial_case_tags($record);
$serviceNames = [
    'formation' => 'Business and institutional formation',
    'web' => 'Website and digital development',
    'hosting' => 'Hosting and ongoing support',
];
$delivered = [];
foreach ($serviceNames as $tag => $label) {
    if (in_array($tag, $serviceTags, true)) $delivered[] = $label;
}
?>
<main class="public-main iw-editorial-public iw-portfolio-detail iw-case-digital">
  <article class="iw-article-shell">
    <div class="container public-container iw-work-detail-width">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(public_url('case-studies')) ?>">Case studies</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span><?= e($clientName) ?></span>
      </nav>
      <header class="iw-work-detail-hero">
        <span class="iw-work-detail-client"><?= e($clientName) ?></span>
        <h1><?= e((string)$record['title']) ?></h1>
      </header>
      <?php if (!empty($record['image_url'])): ?>
        <figure class="iw-work-detail-cover" data-iw-reveal>
          <img src="<?= e((string)$record['image_url']) ?>" alt="Screenshot of the <?= e($clientName) ?> website" decoding="async" width="1440" height="900">
        </figure>
      <?php endif; ?>
      <div class="iw-case-project-layout" data-iw-reveal>
        <aside class="iw-case-project-facts" aria-label="Project information">
          <h2>Project details</h2>
          <dl>
            <div><dt>Client</dt><dd><?= e($clientName) ?></dd></div>
            <div><dt>Sector</dt><dd><?= e((string)($record['category']?:'Client project')) ?></dd></div>
            <?php if ($delivered !== []): ?>
              <div>
                <dt>Work delivered</dt>
                <dd><ul class="iw-case-service-list"><?php foreach ($delivered as $name): ?><li><?= e($name) ?></li><?php endforeach; ?></ul></dd>
              </div>
            <?php endif; ?>
          </dl>
          <?php if ($siteUrl !== ''): ?>
            <a href="<?= e($siteUrl) ?>" class="iw-case-project-site" target="_blank" rel="noopener noreferrer">Visit <?= e($clientName) ?> website</a>
          <?php endif; ?>
        </aside>
        <div class="iw-case-project-content">
          <section aria-labelledby="iw-case-overview">
            <h2 id="iw-case-overview">Overview</h2>
            <p><?= e((string)($record['summary']??'')) ?></p>
          </section>
          <?php if (!empty($record['outcome'])): ?>
            <section class="iw-case-project-outcome" aria-labelledby="iw-case-outcome">
              <h2 id="iw-case-outcome">Outcome</h2>
              <p><?= e((string)$record['outcome']) ?></p>
            </section>
          <?php endif; ?>
          <?php if (trim((string)($record['story_html']??'')) !== ''): ?>
            <details class="iw-case-project-notes">
              <summary>Read the full project details <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
              <div class="iw-article-body"><?= sanitize_limited_html((string)$record['story_html']) ?></div>
            </details>
          <?php endif; ?>
        </div>
      </div>
      <nav class="iw-work-detail-footer" aria-label="Case study links">
        <a href="<?= e(public_url('case-studies')) ?>" class="iw-work-return">All case studies</a>
        <?php if ($siteUrl !== ''): ?>
          <a href="<?= e($siteUrl) ?>" target="_blank" rel="noopener noreferrer" class="iw-work-client-website">Visit website</a>
        <?php endif; ?>
      </nav>
    </div>
  </article>
</main>
