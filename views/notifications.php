<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Notifications</h1>
  <div class="d-flex gap-2">
    <form method="post" action="<?= e(url('notifications')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="read_all">
      <button class="btn btn-sm btn-outline-secondary" type="submit">Mark all as read</button>
    </form>
    <form method="post" action="<?= e(url('notifications')) ?>" onsubmit="return confirm('Clear all notifications?');">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="clear_all">
      <button class="btn btn-sm btn-outline-danger" type="submit">Clear all</button>
    </form>
  </div>
</div>

<div class="card notification-center-card">
  <div class="card-body p-0">
    <?php foreach ($notifications as $notification): ?>
      <?php
        $isUnread = (int) ($notification['is_read'] ?? 0) === 0;
        $title = trim((string) ($notification['title'] ?? ''));
        $message = trim((string) ($notification['message'] ?? ''));
        if ($title === '') {
            $title = 'Notification';
        }
      ?>
      <div class="notification-row <?= $isUnread ? 'notification-unread' : '' ?>">
        <a class="notification-main-link" href="<?= e(url('notifications', ['open' => (int) $notification['id']])) ?>">
          <div class="notification-row-head">
            <strong class="notification-row-title"><?= e($title) ?></strong>
            <span class="notification-row-time"><?= e((string) ($notification['created_at'] ?? '')) ?></span>
          </div>
          <p class="notification-row-text mb-0"><?= e($message) ?></p>
        </a>
        <div class="notification-row-actions">
          <?php if ($isUnread): ?>
            <form method="post" action="<?= e(url('notifications')) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="read_one">
              <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
              <button class="btn btn-sm btn-light" type="submit" title="Mark as read">
                <i class="bi bi-check2"></i>
              </button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= e(url('notifications')) ?>" onsubmit="return confirm('Remove this notification?');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="clear_one">
            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit" title="Clear notification">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!$notifications): ?>
      <div class="p-4 text-center text-muted">No notifications yet.</div>
    <?php endif; ?>
  </div>
</div>
