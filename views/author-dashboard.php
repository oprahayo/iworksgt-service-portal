<?php
/** Author: the editorial inbox, nothing from the operational dashboard. */
$posts=list_blog_posts_manage((int)$user['id'],false);
$drafts=count(array_filter($posts,static fn(array $p):bool=>(string)$p['status']==='draft'));
$published=count($posts)-$drafts;
?>
<div class="iw-admin-workspace iw-author-workspace">
  <div class="iw-admin-hero"><span>EDITORIAL WORKSPACE</span>
    <h1>Welcome, <?= e((string)$user['full_name']) ?></h1>
    <p>Create useful business insights, manage your drafts and review your articles.</p>
    <a class="btn btn-primary" href="<?= e(url('blog')) ?>"><i class="bi bi-pencil-square"></i> Write an insight</a>
  </div>
  <div class="iw-admin-stats">
    <div><i class="bi bi-journal-text"></i><small>Your articles</small><strong><?= count($posts) ?></strong></div>
    <div><i class="bi bi-file-earmark"></i><small>Drafts</small><strong><?= $drafts ?></strong></div>
    <div><i class="bi bi-check2-circle"></i><small>Published</small><strong><?= $published ?></strong></div>
  </div>
  <section class="iw-admin-section"><div class="iw-admin-section-head"><h2>Recent insights</h2><a href="<?= e(url('blog')) ?>">Manage posts</a></div>
    <?php if (!$posts): ?><p class="text-muted">Your published articles and drafts will appear here.</p><?php endif; ?>
    <?php foreach (array_slice($posts,0,8) as $post): ?>
      <a class="iw-admin-row" href="<?= e(url('blog',['action'=>'edit','id'=>(int)$post['id']])) ?>">
        <span><strong><?= e((string)$post['title']) ?></strong><small><?= e((string)$post['updated_at']) ?></small></span>
        <em><?= e(ucfirst((string)$post['status'])) ?></em><i class="bi bi-arrow-right"></i>
      </a>
    <?php endforeach; ?>
  </section>
</div>
