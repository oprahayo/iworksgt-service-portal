<?php
$filters = [
  'all'=>'All', 'draft'=>'Drafts', 'payment'=>'Awaiting payment',
  'progress'=>'In progress', 'completed'=>'Completed', 'cancelled'=>'Cancelled',
];
$visibleItems = $activeClientStatus === 'all' ? $clientServiceItems : array_values(array_filter(
  $clientServiceItems, static fn(array $item): bool => $item['status'] === $activeClientStatus
));
$countByStatus = array_fill_keys(array_keys($filters), 0);
$countByStatus['all'] = count($clientServiceItems);
foreach ($clientServiceItems as $item) { $countByStatus[$item['status']] = ($countByStatus[$item['status']] ?? 0) + 1; }
?>
<div class="iw-client-heading iw-client-heading-row">
  <div><span class="iw-client-eyebrow">YOUR WORKSPACE</span><h1>My services</h1>
    <p>All your applications, orders and projects in one place. Each request appears once.</p>
  </div>
  <a class="iw-client-button" href="<?= e(url('dashboard')) ?>"><i class="bi bi-plus-lg"></i> New service</a>
</div>
<nav class="iw-client-status-filters" aria-label="Filter services by status">
  <?php foreach ($filters as $status=>$label): ?>
    <a href="<?= e(url('applications', $status==='all'?[]:['status'=>$status])) ?>"
      <?= $activeClientStatus === $status ? 'aria-current="page"' : '' ?>>
      <?= e($label) ?><span><?= (int) ($countByStatus[$status] ?? 0) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
<section class="iw-client-application-list" aria-label="Your service requests">
<?php if ($visibleItems === []): ?>
  <div class="iw-client-empty iw-client-empty-large">
    <i class="bi bi-inbox" aria-hidden="true"></i>
    <div><strong><?= $activeClientStatus === 'all' ? 'No services yet' : 'Nothing in this category' ?></strong>
      <p>Start a service whenever you're ready; it will appear here.</p>
      <a class="iw-client-button" href="<?= e(url('dashboard')) ?>">Explore services</a>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($visibleItems as $item): ?>
    <article class="iw-client-application">
      <div class="iw-client-application-main">
        <div class="iw-client-application-meta">
          <span class="iw-client-country"><?= e((string) $item['service']) ?></span>
          <span class="iw-client-status" data-state="<?= e((string) $item['status']) ?>"><?= e(iworks_client_state_label((string) $item['status'])) ?></span>
        </div>
        <small><?= e((string) $item['reference']) ?> · Updated <?= e((string) $item['updated']) ?></small>
        <h2><?= e((string) $item['name']) ?></h2>
      </div>
      <div class="iw-client-application-actions">
        <a class="iw-client-button <?= $item['status']==='payment'?'':'iw-client-button-soft' ?>"
           href="<?= e((string) $item['detail_url']) ?>"><?= e((string) $item['action']) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <?php if (($item['kind'] ?? '') === 'job' && ($item['status'] ?? '') === 'payment'): ?>
          <form method="post" action="<?= e(url('applications')) ?>" onsubmit="return confirm('Delete this pending unpaid service request? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="delete_pending_service">
            <input type="hidden" name="job_id" value="<?= (int) $item['id'] ?>">
            <button class="iw-client-delete" type="submit"><i class="bi bi-trash3"></i> Delete pending request</button>
          </form>
        <?php endif; ?>
        <?php if (!empty($item['can_delete_draft'])): ?>
          <form method="post" action="<?= e(url('trademark',['id'=>(int) $item['id']])) ?>" onsubmit="return confirm('Delete this draft application? This cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="delete_draft">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <button class="iw-client-delete" type="submit"><i class="bi bi-trash3"></i> Delete draft</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
</section>
<?php if ((int) ($existingOrders['pagination']['total'] ?? 0) > 100): ?>
  <p class="iw-client-archive-note">This overview displays the latest 100 service orders. Older records remain preserved in your account; contact support if you need an older order.</p>
<?php endif; ?>
