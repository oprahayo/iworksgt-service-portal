<?php
require_once __DIR__ . '/partials/admin-media-picker.php';

$editingPost = is_array($editBlogPost ?? null) ? $editBlogPost : null;
$formPost = $editingPost ?: [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'tag' => '',
    'excerpt' => '',
    'body_html' => '',
    'status' => 'draft',
    'featured_image_id' => 0,
    'featured_image' => null,
];
$blogReturnParams = [];
if ($editingPost && !empty($formPost['id'])) {
    $blogReturnParams = ['action' => 'edit', 'id' => (int) $formPost['id']];
}
$blogReturnUrl = url('blog', $blogReturnParams);
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h1 class="h4 mb-1">Insights</h1>
    <div class="text-muted small">
      <?= $isAdminBlog ? 'Admins can manage all published and draft articles.' : 'Create and manage your own public insights posts.' ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(public_url('blog')) ?>" target="_blank" rel="noopener">Open public insights</a>
    <a class="btn btn-primary btn-sm" href="<?= e(url('blog')) ?>">New post</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0"><?= $editingPost ? 'Edit insight' : 'Write new insight' ?></h2>
          <?php if ($editingPost): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('blog')) ?>">Clear</a>
          <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('blog')) ?>" enctype="multipart/form-data" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_blog_post">
          <input type="hidden" name="post_id" value="<?= (int) ($formPost['id'] ?? 0) ?>">

          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="<?= e((string) ($formPost['title'] ?? '')) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tag</label>
            <input class="form-control" name="tag" value="<?= e((string) ($formPost['tag'] ?? '')) ?>" placeholder="Business setup">
          </div>

          <div class="col-md-8">
            <label class="form-label">Slug (optional)</label>
            <input class="form-control" name="slug" value="<?= e((string) ($formPost['slug'] ?? '')) ?>" placeholder="auto-generated-from-title">
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="draft" <?= (string) ($formPost['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= (string) ($formPost['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Excerpt</label>
            <textarea class="form-control" name="excerpt" rows="3" placeholder="Short summary shown in the public insights cards."><?= e((string) ($formPost['excerpt'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Featured image</label>
            <?php render_admin_media_picker(
                $mediaAssets,
                'featured_image_id',
                (int) ($formPost['featured_image_id'] ?? 0),
                'No featured image selected.',
                'Open media library'
            ); ?>
            <input class="form-control mt-2" type="file" name="featured_image_upload" accept=".jpg,.jpeg,.png,.webp">
            <small class="text-muted">Use library or upload directly here.</small>
          </div>

          <?php if (!empty($formPost['featured_image']['url'])): ?>
            <div class="col-12">
              <div class="border rounded-4 p-2 bg-light">
                <img src="<?= e((string) $formPost['featured_image']['url']) ?>" alt="Featured image preview" class="img-fluid rounded-3" style="max-height: 220px; object-fit: cover;">
              </div>
            </div>
          <?php endif; ?>

          <div class="col-12">
            <label class="form-label">Article content</label>
            <textarea class="form-control" name="body_html" rows="14" placeholder="<h3>What founders should know</h3><p>Use simple HTML headings, paragraphs, lists, and links.</p>" required><?= e((string) ($formPost['body_html'] ?? '')) ?></textarea>
            <div class="form-text">Allowed formatting is sanitized automatically. Use headings, paragraphs, lists, bold text, and links.</div>
          </div>

          <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
              Published posts appear on the public Insights page and on the homepage insights section automatically.
            </div>
            <div class="d-flex gap-2">
              <?php if ($editingPost): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('blog')) ?>">Cancel</a>
              <?php endif; ?>
              <button class="btn btn-primary" type="submit"><?= $editingPost ? 'Update insight' : 'Save insight' ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0"><?= $isAdminBlog ? 'All insights' : 'Your insights' ?></h2>
          <span class="badge text-bg-light"><?= count($blogPosts) ?> posts</span>
        </div>

        <div class="d-grid gap-3">
          <?php foreach ($blogPosts as $post): ?>
            <article class="border rounded-4 p-3 bg-white">
              <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                <div>
                  <div class="fw-semibold"><?= e((string) ($post['title'] ?? 'Untitled')) ?></div>
                  <div class="small text-muted">
                    <?= e((string) ($post['author_name'] ?? '')) ?>
                    <?php if (!empty($post['published_at'])): ?>
                      · <?= e((string) $post['published_at']) ?>
                    <?php else: ?>
                      · <?= e((string) ($post['updated_at'] ?? $post['created_at'] ?? '')) ?>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="badge <?= (string) ($post['status'] ?? 'draft') === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                  <?= e(ucfirst((string) ($post['status'] ?? 'draft'))) ?>
                </span>
              </div>

              <?php if (!empty($post['tag'])): ?>
                <div class="small fw-semibold text-uppercase mb-2" style="color: var(--brand-primary); letter-spacing: .04em;">
                  <?= e((string) $post['tag']) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($post['excerpt'])): ?>
                <p class="text-muted small mb-3"><?= e((string) $post['excerpt']) ?></p>
              <?php endif; ?>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('blog', ['action' => 'edit', 'id' => (int) ($post['id'] ?? 0)])) ?>">Edit</a>
                  <?php if ((string) ($post['status'] ?? '') === 'published'): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e((string) ($post['url'] ?? public_url('blog'))) ?>" target="_blank" rel="noopener">View</a>
                  <?php endif; ?>
                </div>
                <form method="post" action="<?= e(url('blog')) ?>" onsubmit="return confirm('Delete this insight?');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_blog_post">
                  <input type="hidden" name="post_id" value="<?= (int) ($post['id'] ?? 0) ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>

          <?php if (!$blogPosts): ?>
            <div class="text-muted small">No insights yet. Create your first post from the editor on the left.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php render_admin_media_modal($mediaAssets, [
    'return_to' => $blogReturnUrl,
]); ?>
