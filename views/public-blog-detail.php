<?php
$blogPost=is_array($blogPost??null)?$blogPost:[];
$cover=trim((string)($blogPost['featured_image']['url']??''));
$title=(string)($blogPost['title']??'Business insight');
$tag=trim((string)($blogPost['tag']??'Insights'));
$author=trim((string)($blogPost['author_name']??'iWorks Team'));
$published=substr((string)($blogPost['published_at']??''),0,10);
$link=public_url('blog_post',['slug'=>(string)($blogPost['slug']??'')]);
$related=array_slice(array_values($relatedBlogPosts??[]),0,3);
?>
<main class="public-main iw-editorial-public">
  <article class="iw-article-shell">
    <div class="container public-container iw-article-narrow">
      <nav class="iw-editorial-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right"></i>
        <a href="<?= e(public_url('blog')) ?>">Insights</a><i class="bi bi-chevron-right"></i>
        <span><?= e($title) ?></span>
      </nav>
      <header class="iw-article-head">
        <span class="iw-editorial-eyebrow"><?= e($tag) ?> · BUSINESS INSIGHTS</span>
        <h1><?= e($title) ?></h1>
        <?php if(!empty($blogPost['excerpt'])): ?><p><?= e((string)$blogPost['excerpt']) ?></p><?php endif; ?>
        <div class="iw-article-byline"><span class="iw-article-author-avatar"><i class="bi bi-person"></i></span>
          <div><strong><?= e($author) ?></strong><small><?= e($published) ?></small></div>
          <span class="iw-article-read-time"><?= max(1,(int)ceil(str_word_count(strip_tags((string)($blogPost['body_html']??'')))/220)) ?> min read</span>
        </div>
      </header>
      <?php if($cover!==''): ?><figure class="iw-article-cover"><img src="<?= e($cover) ?>" alt="<?= e($title) ?>" decoding="async" fetchpriority="high"></figure>
      <?php else: ?><div class="iw-article-graphic iw-editorial-abstract"><i class="bi bi-journal-richtext" aria-hidden="true"></i></div><?php endif; ?>

      <div class="iw-article-body rich-text">
        <?= sanitize_limited_html((string)($blogPost['body_html']??'')) ?>
      </div>
      <?php if($tag!==''): ?><div class="iw-article-tags"><span>TOPIC</span><span><?= e($tag) ?></span></div><?php endif; ?>
      <div class="iw-article-actions">
        <form method="post" action="<?= e($link) ?>" class="iw-article-like-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="blog_like">
          <button type="submit" aria-label="<?= !empty($blogLiked)?'Unlike':'Like' ?> this article" aria-pressed="<?= !empty($blogLiked)?'true':'false' ?>">
            <i class="bi bi-heart<?= !empty($blogLiked)?'-fill':'' ?>"></i> <?= (int)($blogLikes??0) ?> <?= (int)($blogLikes??0)===1?'like':'likes' ?></button>
        </form>
        <button class="iw-article-share" type="button" data-iw-share data-url="<?= e($link) ?>" data-title="<?= e($title) ?>"><i class="bi bi-share"></i> Share article</button>
        <a href="<?= e(public_url('blog')) ?>"><i class="bi bi-arrow-left"></i> All insights</a>
      </div>
      <?php if ($success=flash('success')): ?><div class="iw-editorial-feedback" role="status"><?= e($success) ?></div><?php endif; ?>
      <?php if ($error=flash('error')): ?><div class="iw-editorial-feedback iw-editorial-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <section class="iw-article-comments" aria-labelledby="iw-comments-heading">
        <div class="iw-editorial-section-head"><div><span>DISCUSSION</span>
          <h2 id="iw-comments-heading">Join the conversation</h2></div>
          <small><?= count($blogComments??[]) ?> approved comments</small></div>
        <div class="iw-article-comments-grid">
          <div class="iw-article-comment-list">
            <?php if(empty($blogComments)): ?><p>No comments yet. Share your perspective below.</p><?php endif; ?>
            <?php foreach(($blogComments??[]) as $comment): ?>
              <article class="iw-article-comment">
                <span class="iw-article-author-avatar"><i class="bi bi-person"></i></span>
                <div><strong><?= e((string)$comment['display_name']) ?></strong>
                  <small><?= e(substr((string)$comment['created_at'],0,10)) ?></small>
                  <p><?= nl2br(e((string)$comment['comment_text'])) ?></p></div>
              </article>
            <?php endforeach; ?>
          </div>
          <div class="iw-article-comment-form">
            <h3>Leave a comment</h3>
            <p>Comments are reviewed before appearing. Your email is never displayed.</p>
            <form method="post" action="<?= e($link) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="blog_comment">
              <label>Name <input name="name" required maxlength="120" autocomplete="name"></label>
              <label>Email <input type="email" name="email" required maxlength="190" autocomplete="email"></label>
              <label class="iw-editorial-honeypot" aria-hidden="true">Website <input tabindex="-1" autocomplete="off" name="website"></label>
              <label>Comment <textarea name="comment" rows="4" required minlength="10" maxlength="1300"></textarea></label>
              <button type="submit">Submit comment <i class="bi bi-arrow-right"></i></button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </article>
  <?php if($related): ?>
    <section class="iw-related-insights"><div class="container public-container">
      <div class="iw-editorial-section-head"><div><span>KEEP READING</span><h2>Related insights</h2></div>
        <a href="<?= e(public_url('blog')) ?>">View all <i class="bi bi-arrow-right"></i></a></div>
      <div class="iw-insights-grid">
        <?php foreach($related as $post): ?>
          <?php $img=trim((string)($post['featured_image']['url']??'')); ?>
          <article class="iw-insights-card"><a href="<?= e(public_url('blog_post',['slug'=>(string)$post['slug']])) ?>">
            <div class="iw-insights-card-cover <?= $img===''?'iw-editorial-abstract':'' ?>">
              <?php if($img!==''): ?><img src="<?= e($img) ?>" loading="lazy" alt=""><?php else: ?><i class="bi bi-journal-text"></i><?php endif; ?>
              <span class="iw-editorial-read-overlay">Read article <i class="bi bi-arrow-up-right"></i></span>
            </div><div class="iw-insights-card-copy"><span class="iw-editorial-tag"><?= e((string)($post['tag']?:'Insights')) ?></span>
              <h3><?= e((string)$post['title']) ?></h3><p><?= e((string)($post['excerpt']??'')) ?></p></div>
          </a></article>
        <?php endforeach; ?>
      </div>
    </div></section>
  <?php endif; ?>
</main>
<script>
document.addEventListener('click',function(e){
  var button=e.target.closest('[data-iw-share]');if(!button)return;
  var url=button.getAttribute('data-url'),title=button.getAttribute('data-title');
  if(navigator.share){navigator.share({title:title,url:url}).catch(function(){});return;}
  if(navigator.clipboard&&window.isSecureContext){
    navigator.clipboard.writeText(url).then(function(){button.textContent='Link copied';}).catch(function(){window.prompt('Copy article link:',url);});
  }else window.prompt('Copy article link:',url);
});
</script>
