<?php
$blogPost = is_array($blogPost ?? null) ? $blogPost : [];
$featuredImage = trim((string) ($blogPost['featured_image']['url'] ?? ''));
$postTag = trim((string) ($blogPost['tag'] ?? 'Insights'));
$postTitle = trim((string) ($blogPost['title'] ?? 'Insight'));
$postExcerpt = trim((string) ($blogPost['excerpt'] ?? ''));
$postAuthor = trim((string) ($blogPost['author_name'] ?? 'iWorks Team'));
$postDate = trim((string) ($blogPost['published_at'] ?? $blogPost['updated_at'] ?? ''));
$postBody = sanitize_limited_html((string) ($blogPost['body_html'] ?? ''));
?>

<main class="public-main">
  <section class="public-section">
    <div class="container public-container">
      <div class="public-blog-detail-wrap">
        <div class="public-blog-detail-head">
          <a class="public-blog-back" href="<?= e(public_url('blog')) ?>">
            <i class="bi bi-arrow-left"></i>
            <span>Back to insights</span>
          </a>
          <?php if ($postTag !== ''): ?><span class="section-kicker"><?= e($postTag) ?></span><?php endif; ?>
          <h1 class="public-blog-detail-title"><?= e($postTitle) ?></h1>
          <div class="public-blog-detail-meta">
            <span><?= e($postAuthor) ?></span>
            <?php if ($postDate !== ''): ?><span><?= e($postDate) ?></span><?php endif; ?>
          </div>
          <?php if ($postExcerpt !== ''): ?><p class="public-blog-detail-excerpt"><?= e($postExcerpt) ?></p><?php endif; ?>
        </div>

        <?php if ($featuredImage !== ''): ?>
          <div class="public-blog-detail-media">
            <img src="<?= e($featuredImage) ?>" alt="<?= e($postTitle) ?>" loading="lazy">
          </div>
        <?php endif; ?>

        <article class="public-blog-detail-body rich-text">
          <?= $postBody ?>
        </article>

        <div class="public-blog-detail-footer">
          <a class="btn btn-outline-primary" href="<?= e(public_url('blog')) ?>">More insights</a>
          <a class="btn btn-primary" href="<?= e(public_url('services')) ?>">Explore services</a>
        </div>
      </div>
    </div>
  </section>
</main>
