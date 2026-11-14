<?php
$record = $caseRecord;
$clientName = trim((string)($record['client_name']??'')) ?: 'iWORKS client';
$siteUrl = iworks_editorial_case_site((string)($record['slug']??''));
$siteHost = $siteUrl !== '' ? (string)(parse_url($siteUrl,PHP_URL_HOST) ?: '') : '';
?>
<main class="public-main iw-editorial-public iw-portfolio-detail">
  <article class="iw-article-shell">
    <div class="container public-container iw-work-detail-width">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(public_url('case-studies')) ?>">Our Work</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span><?= e($clientName) ?></span>
      </nav>
      <header class="iw-work-detail-hero">
        <span class="iw-work-eyebrow">CLIENT CASE STUDY</span>
        <div class="iw-work-detail-heading">
          <div>
            <p class="iw-work-detail-client"><?= e($clientName) ?> <span aria-hidden="true">/</span> <?= e((string)($record['category']?:'Client project')) ?></p>
            <h1><?= e((string)$record['title']) ?></h1>
            <p class="iw-work-detail-summary"><?= e((string)($record['summary']??'')) ?></p>
          </div>
          <?php if ($siteUrl !== ''): ?>
            <a href="<?= e($siteUrl) ?>" class="iw-work-detail-visit" target="_blank" rel="noopener noreferrer">Visit client website <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
          <?php endif; ?>
        </div>
      </header>
      <?php if (!empty($record['image_url'])): ?>
      <figure class="iw-work-detail-cover">
        <div class="iw-work-browser">
          <div class="iw-work-browser-bar" aria-hidden="true">
            <span class="iw-work-browser-dots"><i></i><i></i><i></i></span>
            <span class="iw-work-browser-domain"><?= e($siteHost !== ''?$siteHost:$clientName) ?></span>
          </div>
          <img src="<?= e((string)$record['image_url']) ?>" alt="Website screenshot of <?= e($clientName) ?>" decoding="async" width="1440" height="900">
        </div>
        <figcaption>Website preview for <?= e($clientName) ?></figcaption>
      </figure>
      <?php endif; ?>
      <div class="iw-work-detail-story">
        <?php if (!empty($record['outcome'])): ?>
          <aside class="iw-work-detail-outcome"><span>PROJECT OUTCOME</span><p><?= e((string)$record['outcome']) ?></p></aside>
        <?php endif; ?>
        <div class="iw-article-body"><?= sanitize_limited_html((string)$record['story_html']) ?></div>
      </div>
      <div class="iw-work-detail-footer">
        <a href="<?= e(public_url('case-studies')) ?>" class="iw-work-return"><i class="bi bi-arrow-left" aria-hidden="true"></i> Explore more work</a>
        <?php if ($siteUrl !== ''): ?>
          <a href="<?= e($siteUrl) ?>" target="_blank" rel="noopener noreferrer" class="iw-work-client-website">Visit website <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </article>
</main>
