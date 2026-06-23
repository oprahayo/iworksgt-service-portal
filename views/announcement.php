<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Announcement</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('dashboard')) ?>">Back to dashboard</a>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
      <h2 class="h5 mb-0"><?= e($announcement['title']) ?></h2>
      <span class="badge text-bg-warning"><?= e(strtoupper((string) ($announcement['audience'] ?? 'all'))) ?></span>
    </div>
    <div class="small text-muted mb-3">Updated: <?= e($announcement['updated_at']) ?></div>

    <div class="announcement-body mb-3">
      <?php if (!empty($announcement['message_html'])): ?>
        <?= (string) $announcement['message_html'] ?>
      <?php else: ?>
        <?= nl2br(e((string) $announcement['message'])) ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($announcement['attachment_path'])): ?>
      <div class="mb-3">
        <a class="btn btn-sm btn-outline-primary" href="download.php?announcement_id=<?= (int) $announcement['id'] ?>">Download attachment</a>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('announcement', ['id' => (int) $announcement['id']])) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="acknowledge_announcement">
      <button class="btn btn-primary" type="submit">Mark as read</button>
    </form>
  </div>
</div>
