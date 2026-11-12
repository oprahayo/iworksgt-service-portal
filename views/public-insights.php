<?php
$feed=iworks_blog_page((int)($_GET['p']??1),9);
$articles=$feed['items'];
$featured=$feed['page']===1 && $articles!==[] ? array_shift($articles) : null;
?>
<main class="public-main iw-editorial-public">
  <section class="iw-insights-intro"><div class="container public-container">
    <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right"></i><span>Business Insights</span></nav>
    <span class="iw-editorial-eyebrow">iWORKS JOURNAL</span>
    <h1>Business Insights</h1>
    <p>Practical advice on registering, protecting and growing businesses in Nigeria—wherever you are.</p>
  </div></section>
  <section class="iw-insights-feed"><div class="container public-container">
    <?php if($featured): ?>
      <?php $heroImage=trim((string)($featured['featured_image']['url']??'')); ?>
      <a class="iw-insights-feature" href="<?= e(public_url('blog_post',['slug'=>(string)$featured['slug']])) ?>">
        <div class="iw-insights-feature-media <?= $heroImage===''?'iw-editorial-abstract':'' ?>">
          <?php if($heroImage!==''): ?><img src="<?= e($heroImage) ?>" alt="<?= e((string)$featured['title']) ?>" fetchpriority="high">
          <?php else: ?><i class="bi bi-journal-richtext" aria-hidden="true"></i><?php endif; ?>
        </div>
        <div class="iw-insights-feature-body">
          <span class="iw-editorial-eyebrow">FEATURED INSIGHT · <?= e((string)($featured['tag']??'BUSINESS')) ?></span>
          <h2><?= e((string)$featured['title']) ?></h2>
          <p><?= e((string)($featured['excerpt']??'')) ?></p>
          <div class="iw-insights-feature-footer"><small><?= e((string)($featured['author_name']??'iWorks Team')) ?> · <?= e(substr((string)($featured['published_at']??''),0,10)) ?></small>
            <span>Read article <i class="bi bi-arrow-up-right"></i></span></div>
        </div>
      </a>
    <?php endif; ?>
    <div class="iw-editorial-section-head"><div><span>ARTICLES & GUIDES</span><h2>Explore the latest ideas</h2></div><small><?= (int)$feed['total'] ?> published articles</small></div>
    <?php if($articles===[] && !$featured): ?>
      <div class="iw-insights-empty"><i class="bi bi-journal-bookmark"></i><h2>Insights are on their way</h2><p>Our team is preparing helpful resources for entrepreneurs.</p></div>
    <?php else: ?>
      <div class="iw-insights-grid">
        <?php foreach($articles as $post): ?>
          <?php $cover=trim((string)($post['featured_image']['url']??'')); ?>
          <article class="iw-insights-card">
            <a href="<?= e(public_url('blog_post',['slug'=>(string)$post['slug']])) ?>">
              <div class="iw-insights-card-cover <?= $cover===''?'iw-editorial-abstract':'' ?>">
                <?php if($cover!==''): ?><img src="<?= e($cover) ?>" alt="" loading="lazy">
                <?php else: ?><i class="bi bi-journal-text" aria-hidden="true"></i><?php endif; ?>
                <span class="iw-editorial-read-overlay">Read article <i class="bi bi-arrow-up-right"></i></span>
              </div>
              <div class="iw-insights-card-copy">
                <span class="iw-editorial-tag"><?= e((string)($post['tag']?:'Insights')) ?></span>
                <h3><?= e((string)$post['title']) ?></h3>
                <p><?= e((string)($post['excerpt']??'')) ?></p>
                <div class="iw-insights-card-footer"><?= e((string)($post['author_name']??'iWorks Team')) ?>
                  <span><?= e(substr((string)($post['published_at']??''),0,10)) ?></span></div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if($feed['pages']>1): ?>
      <nav class="iw-insights-pagination" aria-label="Insight pages">
        <?php for($i=1;$i<=$feed['pages'];$i++): ?>
          <a href="<?= e(public_url('blog',$i>1?['p'=>$i]:[])) ?>" <?= $i===$feed['page']?'aria-current="page"':'' ?>><?= $i ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  </div></section>
</main>
