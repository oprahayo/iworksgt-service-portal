<?php
$blogPost=is_array($blogPost??null)?$blogPost:[];
$cover=trim((string)($blogPost['featured_image']['url']??''));
$title=(string)($blogPost['title']??'Business insight');
$tag=trim((string)($blogPost['tag']??'Insights'));
$author=trim((string)($blogPost['author_name']??'iWorks Team'));
$published=substr((string)($blogPost['published_at']??''),0,10);
$publishedTimestamp=$published!==''?strtotime($published):false;
$link=public_url('blog_post',['slug'=>(string)($blogPost['slug']??'')]);
$related=array_slice(array_values($relatedBlogPosts??[]),0,3);
?>
<main class="public-main iw-editorial-public iw-journal iw-journal-article iw-reading-article" id="main-content">
  <article class="iw-article-shell">
    <header class="iw-reading-masthead">
      <div class="container public-container iw-reading-width">
        <nav class="iw-editorial-breadcrumb iw-reading-breadcrumb" aria-label="Breadcrumb">
          <a href="<?= e(public_url('home')) ?>">Home</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <a href="<?= e(public_url('blog')) ?>">Insights</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span aria-current="page"><?= e($title) ?></span>
        </nav>
        <div class="iw-article-head iw-reading-heading">
          <span class="iw-editorial-eyebrow"><?= e($tag) ?></span>
          <h1><?= e($title) ?></h1>
          <?php if(trim((string)($blogPost['excerpt']??''))!==''): ?><p><?= e((string)$blogPost['excerpt']) ?></p><?php endif; ?>
          <div class="iw-article-byline iw-reading-meta">
            <span class="iw-reading-author"><?= e($author) ?></span>
            <span aria-hidden="true" class="iw-reading-dot">·</span>
            <?php if($published!==''): ?><time datetime="<?= e($published) ?>"><?= e($publishedTimestamp!==false?date('j F Y',$publishedTimestamp):$published) ?></time><?php endif; ?>
            <span aria-hidden="true" class="iw-reading-dot">·</span>
            <span><?= max(1,(int)ceil(str_word_count(strip_tags((string)($blogPost['body_html']??'')))/220)) ?> min read</span>
          </div>
        </div>
      </div>
    </header>
    <div class="container public-container iw-reading-width iw-reading-content">
      <?php if($cover!==''): ?>
        <figure class="iw-article-cover iw-reading-cover">
          <img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async" width="960" height="480">
        </figure>
      <?php endif; ?>
      <div class="iw-reading-column">
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
        <details class="iw-reading-discussion" <?= !empty($blogComments)||!empty($success)||!empty($error)?'open':'' ?>>
          <summary><span>Read comments or leave a comment</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
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
        </details>
      </section>
      </div>
    </div>
  </article>
  <?php if($related): ?>
    <section class="iw-related-insights"><div class="container public-container iw-journal-container">
      <div class="iw-editorial-section-head"><div><span>KEEP READING</span><h2>Related insights</h2></div>
        <a href="<?= e(public_url('blog')) ?>">View all <i class="bi bi-arrow-right"></i></a></div>
      <div class="iw-journal-grid">
        <?php foreach ($related as $index => $post): ?>
          <?php
            $img = trim((string)($post['featured_image']['url'] ?? ''));
            $relatedTitle = (string)($post['title'] ?? 'Business insight');
            $relatedTag = trim((string)($post['tag'] ?? '')) ?: 'Business';
          ?>
          <article class="iw-journal-card">
            <a href="<?= e(public_url('blog_post', ['slug'=>(string)($post['slug'] ?? '')])) ?>">
              <div class="iw-journal-card-cover <?= $img === '' ? 'iw-journal-no-cover' : '' ?>" data-art-variant="<?= (int)($index % 4) + 1 ?>">
                <?php if ($img !== ''): ?>
                  <img src="<?= e($img) ?>" alt="" loading="lazy" decoding="async" width="600" height="344">
                <?php else: ?>
                  <div class="iw-journal-art" aria-hidden="true">
                    <span class="iw-journal-art-brand">iWORKS <em>GLOBAL</em></span>
                    <strong><?= e($relatedTitle) ?></strong>
                    <span class="iw-journal-art-tag"><?= e($relatedTag) ?></span>
                  </div>
                <?php endif; ?>
                <span class="iw-journal-cover-action">Read article <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
              </div>
              <div class="iw-journal-card-content">
                <h3><?= e($relatedTitle) ?></h3>
                <div class="iw-journal-meta"><span><?= e($relatedTag) ?></span>
                <span aria-hidden="true">·</span>
                <time datetime="<?= e(substr((string)($post['published_at'] ?? ''),0,10)) ?>"><?= e(substr((string)($post['published_at'] ?? ''),0,10)) ?></time></div>
                <?php if (trim((string)($post['excerpt'] ?? '')) !== ''): ?>
                  <p><?= e((string)$post['excerpt']) ?></p>
                <?php endif; ?>
              </div>
            </a>
          </article>
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
