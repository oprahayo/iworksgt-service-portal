<?php $user = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Agent Community</h1>
</div>

<div class="community-layout">
  <section class="community-feed">
    <article class="card community-composer-card mb-3">
      <div class="card-body">
        <form method="post" action="<?= e(url('community')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="create_post">
          <div class="d-flex gap-2">
            <div class="community-avatar"><?= e(strtoupper(substr((string) ($user['full_name'] ?? 'U'), 0, 1))) ?></div>
            <div class="flex-grow-1">
              <input class="form-control form-control-sm mb-2" name="title" placeholder="Title (optional)">
              <textarea class="form-control community-composer-input" name="body" rows="3" placeholder="Share an update with agents..." required></textarea>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <small class="text-muted">Posts are visible to agents and admin.</small>
            <button class="btn btn-primary btn-sm" type="submit">Publish</button>
          </div>
        </form>
      </div>
    </article>

    <?php foreach ($communityPosts as $post): ?>
      <?php
        $postId = (int) $post['id'];
        $commentCollapseId = 'postComments' . $postId;
        $authorName = trim((string) ($post['author_name'] ?? 'Agent'));
        $avatarText = strtoupper(substr($authorName, 0, 1));
      ?>
      <article class="card community-feed-card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <div class="community-avatar"><?= e($avatarText) ?></div>
              <div>
                <div class="fw-semibold">
                  <?= e($authorName) ?>
                  <?php if (role_has_admin_access((string) ($post['author_role'] ?? ''))): ?><span class="badge text-bg-dark ms-1">Admin</span><?php endif; ?>
                  <?php if (($post['author_role'] ?? '') === 'agent'): ?><span class="badge text-bg-primary ms-1">Agent</span><?php endif; ?>
                </div>
                <div class="small text-muted"><?= e((string) $post['created_at']) ?></div>
              </div>
            </div>

            <?php if (has_role(['admin'])): ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Manage</button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <form method="post" action="<?= e(url('community')) ?>">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="form_action" value="set_post_visibility">
                      <input type="hidden" name="post_id" value="<?= $postId ?>">
                      <input type="hidden" name="is_active" value="<?= (int) $post['is_active'] === 1 ? '0' : '1' ?>">
                      <button class="dropdown-item" type="submit"><?= (int) $post['is_active'] === 1 ? 'Hide post' : 'Show post' ?></button>
                    </form>
                  </li>
                  <li>
                    <form method="post" action="<?= e(url('community')) ?>" onsubmit="return confirm('Delete this post?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="form_action" value="delete_post">
                      <input type="hidden" name="post_id" value="<?= $postId ?>">
                      <button class="dropdown-item text-danger" type="submit">Delete post</button>
                    </form>
                  </li>
                </ul>
              </div>
            <?php endif; ?>
          </div>

          <?php if (trim((string) ($post['title'] ?? '')) !== ''): ?>
            <h3 class="h6 mb-2"><?= e((string) $post['title']) ?></h3>
          <?php endif; ?>

          <div class="community-post-body mb-2"><?= nl2br(e((string) $post['body'])) ?></div>

          <?php if ((int) $post['is_active'] !== 1): ?>
            <div class="alert alert-warning py-2 mb-2">This post is hidden from agents. Only admin can see it.</div>
          <?php endif; ?>

          <div class="small text-muted community-post-meta">
            <span><?= (int) ($post['like_count'] ?? 0) ?> likes</span>
            <span><?= (int) ($post['comment_count'] ?? 0) ?> comments</span>
          </div>

          <div class="community-actions">
            <form method="post" action="<?= e(url('community')) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="toggle_like">
              <input type="hidden" name="post_id" value="<?= $postId ?>">
              <button class="btn btn-sm <?= (int) ($post['viewer_liked'] ?? 0) > 0 ? 'btn-primary' : 'btn-outline-primary' ?>" type="submit">
                <i class="bi bi-hand-thumbs-up"></i> <?= (int) ($post['viewer_liked'] ?? 0) > 0 ? 'Liked' : 'Like' ?>
              </button>
            </form>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($commentCollapseId) ?>" aria-expanded="false" aria-controls="<?= e($commentCollapseId) ?>">
              <i class="bi bi-chat-left-text"></i> Comments
            </button>
          </div>

          <?php $comments = $communityPostComments[$postId] ?? []; ?>
          <div class="collapse community-comments-wrap" id="<?= e($commentCollapseId) ?>">
            <div class="community-comments mt-2">
              <?php foreach ($comments as $comment): ?>
                <div class="community-comment-item">
                  <div class="small text-muted">
                    <?= e((string) $comment['user_name']) ?>
                    <?php if (role_has_admin_access((string) ($comment['user_role'] ?? ''))): ?><span class="badge text-bg-dark">Admin</span><?php endif; ?>
                    <?php if (($comment['user_role'] ?? '') === 'agent'): ?><span class="badge text-bg-primary">Agent</span><?php endif; ?>
                    <span class="ms-1"><?= e((string) $comment['created_at']) ?></span>
                  </div>
                  <div><?= nl2br(e((string) $comment['comment'])) ?></div>
                </div>
              <?php endforeach; ?>
              <?php if (!$comments): ?>
                <p class="text-muted small mb-2">No comments yet.</p>
              <?php endif; ?>
            </div>

            <?php if ((int) $post['is_active'] === 1 || has_role(['admin'])): ?>
              <form method="post" action="<?= e(url('community')) ?>" class="row g-2 mt-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="add_comment">
                <input type="hidden" name="post_id" value="<?= $postId ?>">
                <div class="col-9">
                  <input class="form-control form-control-sm" name="comment" placeholder="Write a comment..." required>
                </div>
                <div class="col-3 d-grid">
                  <button class="btn btn-sm btn-outline-primary" type="submit">Send</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (!$communityPosts): ?>
      <div class="card">
        <div class="card-body text-muted">No community posts yet.</div>
      </div>
    <?php endif; ?>
  </section>

  <aside class="community-right">
    <div class="card">
      <div class="card-body">
        <h2 class="h6 mb-2">Community Guide</h2>
        <div class="small text-muted mb-2">Use this space to ask delivery questions and share templates/process tips.</div>
        <ul class="small ps-3 mb-0">
          <li>Keep posts work-focused.</li>
          <li>Attach job references where needed.</li>
          <li>Admin can moderate or hide posts.</li>
        </ul>
      </div>
    </div>
  </aside>
</div>
