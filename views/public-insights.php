<?php
require_once __DIR__ . '/../app/public-photo-config.php';
/**
 * iWorks Journal public archive. All article data comes from published CMS
 * records; no template-authored sample posts or pretend cover photographs.
 */
$feed = iworks_blog_page((int)($_GET['p'] ?? 1), 10);
$articles = is_array($feed['items'] ?? null) ? $feed['items'] : [];
$featured = (int)($feed['page'] ?? 1) === 1 && $articles !== [] ? array_shift($articles) : null;
$page = max(1, (int)($feed['page'] ?? 1));
$pages = max(1, (int)($feed['pages'] ?? 1));
$dateLabel = static function ($value): string {
    $timestamp = strtotime((string)$value);
    return $timestamp !== false ? date('j M Y', $timestamp) : '';
};
?>
<main class="public-main iw-editorial-public iw-journal iw-journal-index" id="main-content">
  <section class="iw-journal-intro" aria-labelledby="iw-journal-title">
    <div class="container public-container iw-journal-container">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Insights</span>
      </nav>
      <span class="iw-journal-kicker">THE iWORKS JOURNAL</span>
      <h1 id="iw-journal-title">Business Insights</h1>
      <p>Useful perspectives on starting, protecting and growing your business.</p>
    </div>
  </section>

  <section class="iw-journal-feed" aria-label="Published articles">
    <div class="container public-container iw-journal-container">
      <?php if ($featured): ?>
        <?php
          $heroImage = iworks_blog_photo_url($featured);
          $heroTag = trim((string)($featured['tag'] ?? '')) ?: 'Business';
          $heroTitle = (string)($featured['title'] ?? 'Business insight');
          $heroLink = public_url('blog_post', ['slug' => (string)($featured['slug'] ?? '')]);
        ?>
        <article class="iw-journal-featured">
          <a href="<?= e($heroLink) ?>" class="iw-journal-featured-link">
            <div class="iw-journal-featured-media <?= $heroImage === '' ? 'iw-journal-no-cover' : '' ?>">
              <?php if ($heroImage !== ''): ?>
                <img src="<?= e($heroImage) ?>" alt="" decoding="async" fetchpriority="high" width="1180" height="500">
              <?php else: ?>
                <div class="iw-journal-art" aria-hidden="true">
                  <span class="iw-journal-art-brand">iWORKS <em>GLOBAL</em></span>
                  <strong><?= e($heroTitle) ?></strong>
                  <span class="iw-journal-art-tag"><?= e($heroTag) ?></span>
                </div>
              <?php endif; ?>
              <span class="iw-journal-featured-label"><i class="bi bi-bookmark-star" aria-hidden="true"></i> Featured article</span>
            </div>
            <div class="iw-journal-featured-body">
              <div class="iw-journal-meta"><span><?= e($heroTag) ?></span><span aria-hidden="true">·</span>
                <time datetime="<?= e(substr((string)($featured['published_at'] ?? ''), 0, 10)) ?>"><?= e($dateLabel($featured['published_at'] ?? '')) ?></time></div>
              <h2><?= e($heroTitle) ?></h2>
              <?php if (trim((string)($featured['excerpt'] ?? '')) !== ''): ?>
                <p><?= e((string)$featured['excerpt']) ?></p>
              <?php endif; ?>
              <span class="iw-journal-read-more">Read article <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
            </div>
          </a>
        </article>
      <?php endif; ?>

      <div class="iw-journal-feed-heading">
        <div><span class="iw-journal-kicker">ARTICLES &amp; GUIDES</span><h2>Explore the latest ideas</h2></div>
        <p><?= (int)($feed['total'] ?? 0) ?> published <?= (int)($feed['total'] ?? 0) === 1 ? 'article' : 'articles' ?></p>
      </div>

      <?php if ($articles === [] && !$featured): ?>
        <div class="iw-journal-empty">
          <i class="bi bi-journal-text" aria-hidden="true"></i>
          <h2>New insights are coming</h2>
          <p>Our team is preparing practical business guides.</p>
        </div>
      <?php elseif ($articles !== []): ?>
        <div class="iw-journal-grid">
          <?php foreach ($articles as $index => $post): ?>
            <?php
              $cover = iworks_blog_photo_url($post);
              $tag = trim((string)($post['tag'] ?? '')) ?: 'Business';
              $title = (string)($post['title'] ?? 'Business insight');
              $date = substr((string)($post['published_at'] ?? ''), 0, 10);
            ?>
            <article class="iw-journal-card">
              <a href="<?= e(public_url('blog_post', ['slug' => (string)($post['slug'] ?? '')])) ?>">
                <div class="iw-journal-card-cover <?= $cover === '' ? 'iw-journal-no-cover' : '' ?>" data-art-variant="<?= (int)($index % 4) + 1 ?>">
                  <?php if ($cover !== ''): ?>
                    <img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async" width="600" height="344">
                  <?php else: ?>
                    <div class="iw-journal-art" aria-hidden="true">
                      <span class="iw-journal-art-brand">iWORKS <em>GLOBAL</em></span>
                      <strong><?= e($title) ?></strong>
                      <span class="iw-journal-art-tag"><?= e($tag) ?></span>
                    </div>
                  <?php endif; ?>
                  <span class="iw-journal-cover-action">Read article <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                </div>
                <div class="iw-journal-card-content">
                  <h3><?= e($title) ?></h3>
                  <div class="iw-journal-meta"><span><?= e($tag) ?></span><span aria-hidden="true">·</span>
                    <time datetime="<?= e($date) ?>"><?= e($dateLabel($post['published_at'] ?? '')) ?></time></div>
                  <?php if (trim((string)($post['excerpt'] ?? '')) !== ''): ?>
                    <p><?= e((string)$post['excerpt']) ?></p>
                  <?php endif; ?>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($pages > 1): ?>
        <nav class="iw-journal-pagination" aria-label="Article pagination">
          <?php if ($page > 1): ?>
            <a href="<?= e(public_url('blog', $page - 1 > 1 ? ['p'=>$page-1] : [])) ?>" class="iw-journal-page-nav" rel="prev"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Previous</span></a>
          <?php endif; ?>
          <?php if ($page > 3): ?>
            <a href="<?= e(public_url('blog')) ?>">1</a>
            <?php if ($page > 4): ?><span aria-hidden="true" class="iw-journal-ellipsis">…</span><?php endif; ?>
          <?php endif; ?>
          <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <a href="<?= e(public_url('blog', $i > 1 ? ['p'=>$i] : [])) ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page + 2 < $pages): ?>
            <?php if ($page + 3 < $pages): ?><span aria-hidden="true" class="iw-journal-ellipsis">…</span><?php endif; ?>
            <a href="<?= e(public_url('blog', ['p'=>$pages])) ?>"><?= $pages ?></a>
          <?php endif; ?>
          <?php if ($page < $pages): ?>
            <a href="<?= e(public_url('blog', ['p'=>$page+1])) ?>" class="iw-journal-page-nav" rel="next"><span>Next</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
  </section>
</main>
