<?php
$paidLinkedJobs = [];
foreach ($trademarkApplications as $app) {
    if (!empty($app['job_id'])) { $paidLinkedJobs[(int) $app['job_id']] = true; }
}
?>
<div class="iw-client-heading iw-client-heading-row">
  <div><span class="iw-client-eyebrow">APPLICATIONS</span><h1>My applications</h1>
    <p>View drafts, resume an application or continue to payment.</p></div>
  <a class="iw-client-button" href="<?= e(url('dashboard')) ?>"><i class="bi bi-plus"></i> New application</a>
</div>
<section class="iw-client-application-list" aria-label="Trademark applications">
  <?php if ($trademarkApplications === [] && empty($existingOrders['items'])): ?>
  <div class="iw-client-empty iw-client-empty-large"><i class="bi bi-inbox"></i>
    <div><strong>No applications here yet</strong><p>When you start an application it will appear here.</p>
      <a class="iw-client-button" href="<?= e(url('trademark')) ?>">Start trademark application</a></div></div>
  <?php endif; ?>
  <?php foreach ($trademarkApplications as $application): ?>
    <?php
      $appData = $application['data'] ?? [];
      $draft = $application['status'] === 'draft';
      $paid = ($application['payment_status'] ?? '') === 'paid';
      $status = $draft ? 'Draft' : ($paid ? 'Paid' : 'Awaiting payment');
      $target = url('trademark', ['id'=>(int) $application['id'], 'step'=>$draft ? 'details':'payment']);
    ?>
    <article class="iw-client-application">
      <div class="iw-client-application-main">
        <div class="iw-client-application-meta"><span class="iw-client-country">Nigeria</span><span class="iw-client-status"><?= e($status) ?></span></div>
        <small>Trademark · TM-<?= (int) $application['id'] ?></small>
        <h2><?= e((string) (($appData['mark_name'] ?? '') ?: 'Untitled trademark application')) ?></h2>
        <p><?= count($appData['classes'] ?? []) ?> selected class(es) · Updated <?= e((string) $application['updated_at']) ?></p>
      </div>
      <div class="iw-client-application-actions">
        <a class="iw-client-button <?= $draft ? '' : 'iw-client-button-soft' ?>" href="<?= e($target) ?>"><?= $draft ? 'Continue application' : 'View payment' ?> <i class="bi bi-arrow-right"></i></a>
        <?php if ($draft): ?>
        <form method="post" action="<?= e(url('trademark', ['id'=>(int) $application['id']])) ?>" onsubmit="return confirm('Delete this draft application? This cannot be undone.');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="delete_draft">
          <input type="hidden" name="id" value="<?= (int) $application['id'] ?>">
          <button type="submit" class="iw-client-delete"><i class="bi bi-trash3"></i> Delete draft</button>
        </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php foreach (is_array($existingOrders['items'] ?? null) ? $existingOrders['items'] : [] as $order): ?>
    <?php if (isset($paidLinkedJobs[(int) ($order['id'] ?? 0)])) { continue; } ?>
    <article class="iw-client-application">
      <div class="iw-client-application-main">
        <div class="iw-client-application-meta"><span class="iw-client-country">Service order</span><span class="iw-client-status"><?= e(ucfirst((string) ($order['payment_status'] ?? 'unpaid'))) ?></span></div>
        <small><?= e((string) ($order['job_code'] ?? '')) ?></small>
        <h2><?= e((string) ($order['title'] ?? $order['service_name'] ?? 'Service')) ?></h2>
        <p>Created <?= e((string) ($order['created_at'] ?? '')) ?></p>
      </div>
      <a class="iw-client-button iw-client-button-soft" href="<?= e(url('job', ['id'=>(int) $order['id']])) ?>">View order <i class="bi bi-arrow-right"></i></a>
    </article>
  <?php endforeach; ?>
</section>
