<?php
$record = $caseRecord;
$clientName = trim((string)($record['client_name']??'')) ?: 'iWORKS client';
$siteUrl = iworks_editorial_case_site((string)($record['slug']??''));
?>
<main class="public-main iw-editorial-public iw-portfolio-detail">
  <article class="iw-article-shell">
    <div class="container public-container iw-work-detail-width">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(public_url('case-studies')) ?>">Case studies</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span><?= e($clientName) ?></span>
      </nav>
      <header class="iw-work-detail-hero">
        <span class="iw-work-detail-client"><?= e($clientName) ?> <span aria-hidden="true">·</span> <?= e((string)($record['category']?:'Client project')) ?></span>
        <h1><?= e((string)$record['title']) ?></h1>
        <p class="iw-work-detail-summary"><?= e((string)($record['summary']??'')) ?></p>
      </header>
      <?php if (!empty($record['image_url'])): ?>
        <figure class="iw-work-detail-cover" data-iw-reveal>
          <img src="<?= e((string)$record['image_url']) ?>" alt="Screenshot of the <?= e($clientName) ?> website" decoding="async" width="1440" height="900">
        </figure>
      <?php endif; ?>
      <div class="iw-work-detail-story" data-iw-reveal>
        <div class="iw-article-body"><?= sanitize_limited_html((string)$record['story_html']) ?></div>
        <?php if (!empty($record['outcome'])): ?>
          <section class="iw-work-detail-outcome" aria-label="Project outcome">
            <h2>Outcome</h2>
            <p><?= e((string)$record['outcome']) ?></p>
          </section>
        <?php endif; ?>
      </div>
      <nav class="iw-work-detail-footer" aria-label="Case study links">
        <a href="<?= e(public_url('case-studies')) ?>" class="iw-work-return">All case studies</a>
        <?php if ($siteUrl !== ''): ?>
          <a href="<?= e($siteUrl) ?>" target="_blank" rel="noopener noreferrer" class="iw-work-client-website">Visit <?= e($clientName) ?> website</a>
        <?php endif; ?>
      </nav>
    </div>
  </article>
</main>
