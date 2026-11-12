<?php
/* Customer-specific, streamlined view of the existing operational job. */
$state = (string) ($job['status'] ?? 'pending');
$payment = (string) ($job['payment_status_effective'] ?? $job['payment_status'] ?? 'unpaid');
$uiState = iworks_client_state($payment, $state);
$isClosed = in_array($state, ['closed','cancelled'], true);
$canUpload = can_user_upload_files_to_job($user, $job);
$canManageFiles = can_user_manage_job_files($user, $job);
$clientThreadLocked = in_array($state, ['delivered','closed','cancelled'], true);
$clientCanRevise = can_client_request_job_revision($job);
$cacLinkedApplication = function_exists('cac_find_for_job') ? cac_find_for_job((int) $job['id'],(int) $user['id']) : null;
$visibleIntake = [];
$legacyTrademarkIntake = [];
foreach (is_array($intakeSubmissions ?? null) ? $intakeSubmissions : [] as $submission) {
    $duplicate = !empty($wizardTrademark)
        && strtolower(trim((string) ($submission['form_title'] ?? ''))) === 'trademark intake form';
    if ($duplicate) { $legacyTrademarkIntake[] = $submission; }
    else { $visibleIntake[] = $submission; }
}
$missingRequired = count(array_filter($visibleIntake, static fn(array $s): bool =>
    (int) ($s['is_required'] ?? 1) === 1 && !in_array((string) ($s['status'] ?? 'draft'), ['submitted','approved'], true)
)) > 0;
?>
<div class="iw-client-heading iw-client-heading-row">
  <div>
    <a class="iw-client-back" href="<?= e(url('applications')) ?>"><i class="bi bi-arrow-left"></i> My services</a>
    <span class="iw-client-eyebrow">SERVICE TRACKING · <?= e((string) ($job['job_code'] ?? '')) ?></span>
    <h1><?= e((string) ($job['title'] ?: $job['service_name'] ?: 'Service request')) ?></h1>
    <p><?= e((string) ($job['service_name'] ?? 'Service')) ?> · Created <?= e((string) ($job['created_at'] ?? '')) ?></p>
  </div>
  <span class="iw-client-status" data-state="<?= e($uiState) ?>"><?= e(iworks_client_state_label($uiState)) ?></span>
</div>
<section class="iw-client-order-summary" aria-label="Order summary">
  <div><span>Service status</span><strong><?= e(ucwords(str_replace('_',' ',$state))) ?></strong></div>
  <div><span>Payment</span><strong><?= e(ucwords(str_replace('_',' ',$payment))) ?></strong></div>
  <div><span>Total amount</span><strong><?= e(money((float) ($job['amount'] ?? 0), (string) ($job['currency'] ?? default_currency()))) ?></strong></div>
  <?php if (!empty($invoice)): ?>
    <a class="iw-client-button iw-client-button-soft" href="<?= e('invoice.php?id=' . (int) $invoice['id']) ?>">
      <i class="bi bi-receipt"></i> <?= $payment==='paid' ? 'View invoice' : 'Review & pay' ?>
    </a>
  <?php endif; ?>
</section>
<?php if ($payment !== 'paid' && !empty($invoice) && $state !== 'cancelled'): ?>
  <div class="iw-client-next-step">
    <i class="bi bi-wallet2"></i>
    <div><strong>Next step: complete payment</strong><p>Your application is saved. Once payment is verified, our team can begin processing it.</p></div>
    <a class="iw-client-button" href="<?= e('invoice.php?id=' . (int) $invoice['id']) ?>">Pay invoice <i class="bi bi-arrow-right"></i></a>
  </div>
<?php elseif ($state === 'delivered'): ?>
  <div class="iw-client-next-step">
    <i class="bi bi-check-circle"></i>
    <div><strong>Service delivered</strong><p>Review the completed work and let us know if a revision is needed.</p></div>
  </div>
<?php elseif ($missingRequired): ?>
  <div class="iw-client-next-step">
    <i class="bi bi-file-earmark-text"></i>
    <div><strong>Additional information needed</strong><p>Complete the requested form below so we can continue processing.</p></div>
  </div>
<?php endif; ?>

<div class="iw-client-detail-grid">
  <div class="iw-client-detail-main">
    <section class="iw-client-detail-panel">
      <div class="iw-client-detail-panel-title"><h2>Service information</h2>
        <?php if (!empty($wizardTrademark)): ?>
          <a href="<?= e(url('trademark',['id'=>(int) $wizardTrademark['id'],'step'=>'payment'])) ?>">View trademark application <i class="bi bi-arrow-up-right"></i></a>
        <?php elseif (!empty($cacLinkedApplication)): ?>
          <a href="<?= e(url('cac',['id'=>(int) $cacLinkedApplication['id'],'step'=>'payment'])) ?>">View CAC application <i class="bi bi-arrow-up-right"></i></a>
        <?php endif; ?>
      </div>
      <div class="iw-client-request-description"><?= nl2br(e((string) ($job['description'] ?: 'Details will appear here.'))) ?></div>
    </section>

    <?php if (!empty($visibleIntake) && !$isClosed): ?>
    <section class="iw-client-detail-panel">
      <div class="iw-client-detail-panel-title"><h2>Requested information</h2><span class="iw-client-small-muted"><?= count($visibleIntake) ?> form(s)</span></div>
      <div class="iw-client-form-list">
        <?php foreach ($visibleIntake as $form): ?>
          <div class="iw-client-form-row">
            <div><strong><?= e((string) ($form['form_title'] ?? 'Information form')) ?></strong>
              <small><?= e(ucfirst((string) ($form['status'] ?? 'draft'))) ?><?= !empty($form['update_request_note']) ? ' · Update requested' : '' ?></small>
              <?php if (!empty($form['update_request_note'])): ?><p><?= e((string) $form['update_request_note']) ?></p><?php endif; ?>
            </div>
            <a class="iw-client-button iw-client-button-soft" href="<?= e(url('intake_submission',['id'=>(int) $form['id']])) ?>"><?= intake_client_can_edit($form)?'Complete form':'View form' ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($legacyTrademarkIntake): ?>
      <details class="iw-client-detail-panel iw-client-legacy-intake">
        <summary>Older trademark intake record <i class="bi bi-chevron-down"></i></summary>
        <p>Your original trademark wizard already captured the information needed to create this request. An older, separate intake form is also attached to this order. It has been retained for staff review, not deleted.</p>
        <?php foreach ($legacyTrademarkIntake as $form): ?>
          <a href="<?= e(url('intake_submission',['id'=>(int) $form['id']])) ?>">View legacy form · <?= e((string) ($form['status'] ?? 'draft')) ?></a>
        <?php endforeach; ?>
      </details>
    <?php endif; ?>

    <section class="iw-client-detail-panel">
      <div class="iw-client-detail-panel-title"><h2>Messages</h2><span class="iw-client-small-muted">Updates about this service</span></div>
      <?php if (!$comments): ?><p class="iw-client-small-muted">No messages yet. You can ask a question below.</p><?php endif; ?>
      <div class="iw-client-comment-feed">
        <?php foreach ($comments as $comment): ?>
          <article class="iw-client-comment">
            <div><strong><?= e((string) ($comment['full_name'] ?? 'iWorks team')) ?></strong><small><?= e((string) ($comment['created_at'] ?? '')) ?></small></div>
            <p><?= nl2br(e((string) ($comment['comment'] ?? ''))) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if (!$clientThreadLocked): ?>
        <form method="post" action="<?= e(url('job',['id'=>(int) $job['id']])) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="add_comment">
          <label class="iw-client-field-label" for="clientJobMessage">Send a message</label>
          <textarea id="clientJobMessage" class="iw-client-textarea" name="comment" rows="3" placeholder="Ask a question or share an update" required></textarea>
          <button class="iw-client-button" type="submit"><i class="bi bi-send"></i> Send message</button>
        </form>
      <?php else: ?><p class="iw-client-small-muted">Messages are read-only for this delivered or closed service.</p><?php endif; ?>
    </section>

    <section class="iw-client-detail-panel">
      <div class="iw-client-detail-panel-title"><h2>Files</h2><span class="iw-client-small-muted">Uploaded documents and deliverables</span></div>
      <div class="iw-client-form-list">
        <?php foreach ($files as $file): ?>
          <div class="iw-client-form-row">
            <div><strong><?= e((string) ($file['file_name'] ?? 'File')) ?></strong><small>Uploaded <?= e((string) ($file['created_at'] ?? '')) ?></small></div>
            <a class="iw-client-button iw-client-button-soft" href="<?= e('download.php?id=' . (int) ($file['id'] ?? 0)) ?>">Download</a>
          </div>
        <?php endforeach; ?>
        <?php if (!$files): ?><p class="iw-client-small-muted">No files have been uploaded for this service.</p><?php endif; ?>
      </div>
      <?php if ($canUpload): ?>
        <form method="post" action="<?= e(url('job',['id'=>(int) $job['id']])) ?>" enctype="multipart/form-data" class="iw-client-file-upload">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="upload_file">
          <label class="iw-client-field-label" for="clientJobFile">Add documents</label>
          <input type="file" id="clientJobFile" name="attachment[]" multiple required>
          <button type="submit" class="iw-client-button iw-client-button-soft">Upload documents</button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($state === 'delivered'): ?>
      <section class="iw-client-detail-panel">
        <h2>Confirm delivery</h2>
        <div class="iw-client-delivery-actions">
          <form method="post" action="<?= e(url('job',['id'=>(int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="accept_delivery">
            <button type="submit" class="iw-client-button">Accept delivery</button>
          </form>
          <?php if ($clientCanRevise): ?>
          <form method="post" action="<?= e(url('job',['id'=>(int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="request_revision">
            <input class="iw-client-input" type="text" name="revision_note" placeholder="Describe the revision (optional)">
            <button class="iw-client-button iw-client-button-soft" type="submit">Request revision</button>
          </form>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($state==='closed'): ?>
      <section class="iw-client-detail-panel">
        <h2>Your feedback</h2>
        <?php if (!empty($clientOrderReview)): ?>
          <p>You rated this service <?= (int) ($clientOrderReview['rating'] ?? 0) ?>/10.</p>
          <p><?= nl2br(e((string) ($clientOrderReview['feedback'] ?? ''))) ?></p>
        <?php else: ?>
          <form method="post" action="<?= e(url('job',['id'=>(int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="submit_order_review">
            <label class="iw-client-field-label" for="clientRating">Rate this service (1–10)</label>
            <select class="iw-client-input" name="rating" id="clientRating" required>
              <?php for ($rating=10;$rating>=1;$rating--): ?><option value="<?= $rating ?>"><?= $rating ?> / 10</option><?php endfor; ?>
            </select>
            <label class="iw-client-field-label" for="clientFeedback">Comments (optional)</label>
            <textarea class="iw-client-textarea" name="feedback" id="clientFeedback" rows="3" maxlength="1400"></textarea>
            <label class="iw-client-consent"><input type="checkbox" name="publish_consent" value="1"> Allow iWorks to publish my feedback with an abbreviated name</label>
            <button class="iw-client-button" type="submit">Submit feedback</button>
          </form>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>

  <aside class="iw-client-detail-aside">
    <section class="iw-client-detail-panel">
      <h2>Service progress</h2>
      <ol class="iw-client-timeline">
        <?php if (!$logs): ?><li><span>Order created</span><small><?= e((string) ($job['created_at'] ?? '')) ?></small></li><?php endif; ?>
        <?php foreach ($logs as $log): ?>
          <li><strong><?= e(ucwords(str_replace('_',' ',(string) ($log['new_status'] ?? 'pending')))) ?></strong>
            <small><?= e((string) ($log['created_at'] ?? '')) ?></small></li>
        <?php endforeach; ?>
      </ol>
    </section>
    <section class="iw-client-detail-panel iw-client-support-panel">
      <i class="bi bi-headset"></i>
      <h2>Need assistance?</h2>
      <p>Use Messages for routine updates, or open a support ticket linked to this service.</p>
      <a class="iw-client-button iw-client-button-soft" href="<?= e(url('support',['related_job_id'=>(int) $job['id']])) ?>"><i class="bi bi-headset"></i> Open support ticket</a>
    </section>
  </aside>
</div>
