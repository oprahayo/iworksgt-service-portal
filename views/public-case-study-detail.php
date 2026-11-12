<?php $record=$caseRecord; ?>
<main class="public-main iw-editorial-public">
  <article class="iw-article-shell">
    <div class="container public-container iw-article-narrow">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right"></i>
        <a href="<?= e(public_url('case-studies')) ?>">Case studies</a><i class="bi bi-chevron-right"></i><span><?= e((string)$record['title']) ?></span>
      </nav>
      <header class="iw-article-head">
        <span class="iw-editorial-eyebrow"><?= e((string)($record['category']?:'CASE STUDY')) ?></span>
        <h1><?= e((string)$record['title']) ?></h1>
        <p><?= e((string)($record['summary']??'')) ?></p>
        <div class="iw-article-byline">
          <?php if(!empty($record['logo_url'])): ?><img src="<?= e((string)$record['logo_url']) ?>" alt="" loading="lazy"><?php endif; ?>
          <span><?= e((string)($record['client_name']?:'iWorks client')) ?></span>
        </div>
      </header>
      <?php if(!empty($record['image_url'])): ?><figure class="iw-article-cover"><img src="<?= e((string)$record['image_url']) ?>" alt="" loading="lazy"></figure><?php endif; ?>
      <?php if(!empty($record['outcome'])): ?><div class="iw-article-outcome"><span>PROJECT OUTCOME</span><strong><?= e((string)$record['outcome']) ?></strong></div><?php endif; ?>
      <div class="iw-article-body"><?= sanitize_limited_html((string)$record['story_html']) ?></div>
      <div class="iw-article-actions"><a href="<?= e(public_url('case-studies')) ?>"><i class="bi bi-arrow-left"></i> All case studies</a>
        <a href="<?= e(public_url('services')) ?>">Explore services <i class="bi bi-arrow-up-right"></i></a></div>
    </div>
  </article>
</main>
