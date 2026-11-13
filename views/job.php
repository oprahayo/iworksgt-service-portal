<?php
$user = current_user();
$jobStatus = (string) ($job['status'] ?? 'pending');
$isClientOwner = (string) ($user['role'] ?? '') === 'client' && (int) ($job['client_id'] ?? 0) === (int) ($user['id'] ?? 0);
$isClientThreadLocked = $isClientOwner && in_array($jobStatus, ['delivered', 'closed', 'cancelled'], true);
$canClientAcceptDelivery = $isClientOwner && $jobStatus === 'delivered';
$canClientRequestRevision = $isClientOwner && can_client_request_job_revision($job);
$revisionWindowDays = job_revision_window_days();
$paymentStatusLabels = ['partial' => 'Partially Paid'];
$jobPaymentStatus = (string) ($job['payment_status_effective'] ?? $job['payment_status'] ?? 'unpaid');
$canManageJobFiles = can_user_manage_job_files($user, $job);
$canUploadJobFiles = can_user_upload_files_to_job($user, $job);
$jobAssigneeLabel = 'Job pool';
if (!empty($job['managed_by_admin_name'])) {
    $jobAssigneeLabel = 'Admin: ' . (string) $job['managed_by_admin_name'];
} elseif (!empty($job['agent_name'])) {
    $jobAssigneeLabel = (string) $job['agent_name'];
}
?>
<div class="iw-job-workspace">
  <header class="iw-job-titlebar">
    <div class="iw-job-titlebar-copy">
      <span class="iw-job-eyebrow">SERVICE DELIVERY / JOB RECORD</span>
      <h1><?= e($job['service_name']) ?></h1>
      <div class="iw-job-metadata"><span class="iw-job-code"><?= e($job['job_code']) ?></span><span>Client: <?= e($job['client_name']) ?></span><span>Assigned to: <?= e($jobAssigneeLabel) ?></span></div>
    </div>
    <a class="btn btn-outline-secondary iw-job-back" href="<?= e(url('jobs')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All jobs</a>
  </header>
  <?php if (($user['role'] ?? '') !== 'client'): ?>
    <nav class="iw-job-jump-nav" aria-label="Job sections">
      <a href="#iwJobOverview"><i class="bi bi-info-circle" aria-hidden="true"></i> Overview</a>
      <a href="#iwJobDiscussion"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Discussion</a>
      <a href="#iwJobFiles"><i class="bi bi-folder2-open" aria-hidden="true"></i> Documents</a>
      <a href="#iwJobActions"><i class="bi bi-sliders" aria-hidden="true"></i> Management</a>
    </nav>
  <?php endif; ?>

<?php if (!empty($cacLinked) && $user['role'] !== 'client'): ?>
  <div class="alert alert-info d-flex justify-content-between gap-3 align-items-center flex-wrap">
    <span><i class="bi bi-file-earmark-lock"></i> Secure CAC registration details and proprietor/director documents are attached to this job.</span>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('cac_review',['id'=>(int)$cacLinked['id']])) ?>">Review CAC filing</a>
  </div>
<?php endif; ?>

<?php if ($user['role'] === 'client' && $jobPaymentStatus !== 'paid' && !empty($invoice)): ?>
  <div class="alert alert-warning">
    This order is waiting for payment. Please pay invoice <strong><?= e($job['invoice_number']) ?></strong> to activate processing.
    <a class="btn btn-sm btn-primary ms-2" target="_blank" href="invoice.php?id=<?= (int) $invoice['id'] ?>">Pay now</a>
  </div>
<?php endif; ?>

<?php if ($user['role'] === 'client' && is_array($intakeSummary ?? null) && (int) ($intakeSummary['required_total'] ?? 0) > 0 && !(bool) ($intakeSummary['all_submitted'] ?? false)): ?>
  <div class="alert alert-info">
    Action required: complete your required intake form(s) to enable smooth processing.
  </div>
<?php endif; ?>

<?php if ($jobStatus === 'delivered'): ?>
  <div class="alert alert-success">
    <div class="fw-semibold mb-1">This job has been delivered.</div>
    <div class="small text-muted mb-2">
      Delivered<?= !empty($job['delivered_at']) ? ' on ' . e((string) $job['delivered_at']) : '' ?>
      <?= !empty($job['delivered_by_name']) ? ' by ' . e((string) $job['delivered_by_name']) : '' ?>.
    </div>
    <?php if ($canClientAcceptDelivery): ?>
      <div class="d-flex flex-column gap-2">
        <div class="d-flex flex-wrap gap-2">
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="accept_delivery">
            <button class="btn btn-sm btn-success" type="submit">Accept delivery / Close job</button>
          </form>
          <?php if ($canClientRequestRevision): ?>
            <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>" class="d-flex gap-2 align-items-center flex-wrap">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="request_revision">
              <input type="text" class="form-control form-control-sm" name="revision_note" placeholder="Revision note (optional)" style="min-width: 220px;">
              <button class="btn btn-sm btn-outline-dark" type="submit">Request revision</button>
            </form>
          <?php endif; ?>
        </div>
        <?php if (!$canClientRequestRevision): ?>
          <small class="text-muted">Revision window has expired (<?= (int) $revisionWindowDays ?> day(s)). Contact admin for reopening.</small>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php elseif ($jobStatus === 'revision_requested'): ?>
  <div class="alert alert-warning">
    Revision requested. This job has been reopened for additional updates.
  </div>
<?php elseif ($jobStatus === 'closed'): ?>
  <div class="alert alert-secondary">
    This job is closed<?= !empty($job['accepted_at']) ? ' on ' . e((string) $job['accepted_at']) : '' ?>.
  </div>
<?php endif; ?>


<?php if ($isClientOwner && $jobStatus === 'closed'): ?>
  <section class="iw-job-feedback mb-3" aria-labelledby="iw-job-feedback-title">
    <?php if (!empty($clientOrderReview)): ?>
      <h2 class="h5" id="iw-job-feedback-title">Your review</h2>
      <p class="mb-2 fw-semibold">You rated this completed order <?= (int) ($clientOrderReview['rating'] ?? 0) ?>/10.</p>
      <?php if (trim((string) ($clientOrderReview['feedback'] ?? '')) !== ''): ?>
        <p class="mb-2"><?= nl2br(e((string) $clientOrderReview['feedback'])) ?></p>
      <?php endif; ?>
      <p class="small text-muted mb-0">
        <?= !empty($clientOrderReview['is_public'])
          ? 'You allowed this review to appear on the iWorks website with an abbreviated name.'
          : 'This feedback is private and will not appear on the public website.' ?>
      </p>
    <?php else: ?>
      <h2 class="h5" id="iw-job-feedback-title">How was your experience?</h2>
      <p class="small text-muted">Your order is complete. Please rate the service from 1 to 10. Your feedback helps us improve.</p>
      <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="submit_order_review">
        <fieldset class="iw-review-rating-fieldset">
          <legend class="small fw-semibold mb-2">Your rating</legend>
          <div class="iw-review-rating-options">
            <?php for ($rating = 1; $rating <= 10; $rating++): ?>
              <label><input type="radio" name="rating" value="<?= $rating ?>" <?= $rating === 10 ? 'required' : '' ?>>
                <span><?= $rating ?></span>
              </label>
            <?php endfor; ?>
          </div>
          <p class="small text-muted mt-2">1 = Very poor, 10 = Excellent</p>
        </fieldset>
        <label class="form-label fw-semibold small" for="iwJobReviewText">Your feedback</label>
        <textarea id="iwJobReviewText" class="form-control" name="feedback" rows="3" maxlength="1400" placeholder="How did this service go?"></textarea>
        <label class="iw-review-publish-consent">
          <input type="checkbox" name="publish_consent" value="1">
          <span>I agree to display my feedback and a shortened version of my name on the iWorks homepage and About page. Leave unchecked to send private feedback only.</span>
        </label>
        <button type="submit" class="btn btn-primary mt-3">Submit review</button>
      </form>
    <?php endif; ?>
  </section>
<?php endif; ?>

<div class="row g-3 mb-3 iw-job-metrics" aria-label="Job at a glance">
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="kicker">Status</small><div><?= status_badge($job['status']) ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="kicker">Payment</small><div><?= status_badge($jobPaymentStatus) ?></div></div></div></div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <small class="kicker">Amount</small>
        <div class="fw-semibold"><?= e(money((float) $job['amount'], $job['currency'] ?? default_currency())) ?></div>
        <div class="small text-muted">
          Subtotal <?= e(money((float) ($job['subtotal_amount'] ?? $job['amount']), $job['currency'] ?? default_currency())) ?>,
          Tax <?= e(number_format((float) ($job['tax_percent'] ?? 0), 2)) ?>%
        </div>
      </div>
    </div>
  </div>
  <?php if ($user['role'] !== 'client'): ?>
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="kicker">Commission</small><div class="fw-semibold"><?= e(number_format((float) $job['commission_percent'], 2)) ?>% (<?= e(money((float) $job['commission_amount'], $job['currency'] ?? default_currency())) ?>)</div></div></div></div>
  <?php endif; ?>
</div>

<div class="row g-3 iw-job-content">
  <div class="col-lg-8 iw-job-primary">
    <div class="card mb-3">
      <div class="card-body">
        <h2 class="h5" id="iwJobOverview"><i class="bi bi-card-text" aria-hidden="true"></i> Job overview</h2>
        <p class="mb-0"><?= nl2br(e($job['description'] ?: 'No description.')) ?></p>
      </div>
    </div>

    <?php if ($jobStatus !== 'closed' && is_array($intakeSubmissions ?? null) && $intakeSubmissions): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h5">Intake Forms</h2>
          <div class="table-responsive">
            <table class="table table-mobile-cards table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Form</th>
                  <th>Status</th>
                  <th>Last updated</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($intakeSubmissions as $submission): ?>
                  <tr>
                    <td data-label="Form">
                      <div class="fw-semibold"><?= e((string) ($submission['form_title'] ?? 'Intake form')) ?></div>
                      <?php if (!empty($submission['update_request_note'])): ?>
                        <div class="small text-muted"><?= e((string) $submission['update_request_note']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td data-label="Status"><?= status_badge((string) ($submission['status'] ?? 'draft')) ?></td>
                    <td data-label="Last updated"><?= e((string) ($submission['updated_at'] ?? '')) ?></td>
                    <td data-label="Action" class="text-end cell-actions">
                      <a class="btn btn-sm btn-outline-primary" href="<?= e(url('intake_submission', ['id' => (int) $submission['id']])) ?>">
                        <?php if ($user['role'] === 'client'): ?>
                          <?= intake_client_can_edit($submission) ? 'Fill form' : 'View status' ?>
                        <?php else: ?>
                          Open
                        <?php endif; ?>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <h2 class="h5" id="iwJobDiscussion"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Discussion</h2>
        <?php foreach ($comments as $comment): ?>
          <div class="comment-item">
            <div class="d-flex justify-content-between">
              <strong><?= e((string) ($comment['full_name'] ?? 'System')) ?></strong>
              <small class="text-muted"><?= e((string) ($comment['created_at'] ?? '')) ?></small>
            </div>
            <?php if ((string) ($comment['comment_type'] ?? 'user') === 'system'): ?>
              <div class="small text-muted mb-1">System update</div>
            <?php else: ?>
              <div class="small text-muted mb-1"><?= e(role_label((string) ($comment['role'] ?? 'client'))) ?></div>
            <?php endif; ?>
            <?php
            $commentText = nl2br(e((string) ($comment['comment'] ?? '')));
            $commentText = (string) preg_replace(
                '/(https?:\/\/[^\s<]+)/i',
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                $commentText
            );
            ?>
            <div><?= $commentText ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
          <p class="text-muted">No comments yet.</p>
        <?php endif; ?>

        <?php if ($isClientThreadLocked): ?>
          <div class="alert alert-warning mb-0">
            This job has been delivered. Client comments are read-only until you request revision or an admin reopens the job.
          </div>
        <?php else: ?>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>" id="jobCommentForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="add_comment">
            <label class="form-label">Add comment</label>
            <textarea class="form-control" name="comment" id="jobCommentInput" rows="3" required></textarea>
            <small class="form-text enter-send-hint" id="jobCommentHint">Press Enter to post. Shift+Enter for a new line.</small>
            <button class="btn btn-primary btn-sm mt-2" type="submit">Post comment</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="h5" id="iwJobFiles"><i class="bi bi-folder2-open" aria-hidden="true"></i> Documents</h2>
        <?php if ($canUploadJobFiles): ?>
          <form
            method="post"
            action="<?= e(url('job', ['id' => (int) $job['id']])) ?>"
            enctype="multipart/form-data"
            class="row g-2 align-items-end mb-3"
            id="jobFileUploadForm"
          >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="upload_file">
            <div class="col-12">
              <label class="form-label">Upload files</label>
              <input class="form-control" type="file" name="attachment[]" id="jobFileAttachmentInput" multiple>
              <small class="form-text text-muted">Choose multiple files, review staged list, remove any file you do not want, then submit.</small>
              <div class="job-upload-selected-list d-none" id="jobUploadSelectedList"></div>
              <small class="form-text enter-send-hint" id="jobUploadHint">Choose at least one file.</small>
            </div>
            <div class="col-12 job-upload-submit-wrap">
              <button class="btn btn-outline-primary job-upload-submit-btn" type="submit" id="jobUploadSubmitBtn">Submit uploaded files</button>
            </div>
          </form>
        <?php elseif ($isClientOwner && $jobStatus === 'closed'): ?>
          <div class="alert alert-secondary">This job is closed. Client file uploads are no longer available.</div>
        <?php endif; ?>
        <div class="table-responsive">
          <table class="table table-mobile-cards table-sm align-middle mb-0">
            <thead><tr><th>File</th><th>Uploaded by</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $file): ?>
              <tr>
                <td data-label="File"><?= e($file['file_name']) ?></td>
                <td data-label="Uploaded by"><?= e($file['full_name']) ?></td>
                <td data-label="Date"><?= e($file['created_at']) ?></td>
                <td data-label="Action" class="text-end cell-actions">
                  <div class="d-inline-flex gap-1 align-items-center flex-wrap justify-content-end">
                    <a class="btn btn-sm btn-outline-secondary" href="download.php?id=<?= (int) $file['id'] ?>">Download</a>
                    <?php if ($canManageJobFiles): ?>
                      <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>" class="d-inline" onsubmit="return confirm('Remove this file?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="form_action" value="delete_job_file">
                        <input type="hidden" name="file_id" value="<?= (int) $file['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$files): ?>
              <tr class="table-empty-row"><td colspan="4" class="text-muted text-center py-3">No uploaded files yet.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4 iw-job-actions" id="iwJobActions">
    <div class="iw-job-actions-header"><span class="iw-job-eyebrow">OPERATIONS</span><h2>Manage this job</h2><p>Assignment, progress, payments and activity.</p></div>
    <?php if (has_role(['agent']) && $job['agent_id'] === null && empty($job['managed_by_admin_id']) && $jobPaymentStatus === 'paid'): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6">Claim from job pool</h2>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="claim_job">
            <button class="btn btn-warning w-100" type="submit">Claim this job</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (has_role(['admin'])): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6"><i class="bi bi-person-workspace" aria-hidden="true"></i> Admin ownership</h2>
          <?php if (!empty($job['managed_by_admin_name'])): ?>
            <div class="small text-muted mb-2">Currently handled by <?= e((string) $job['managed_by_admin_name']) ?>.</div>
          <?php else: ?>
            <div class="small text-muted mb-2">Move this job out of the agent pool and handle it directly from admin.</div>
          <?php endif; ?>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="assign_admin_owner">
            <button class="btn btn-outline-dark w-100" type="submit">Assign to admin</button>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6"><i class="bi bi-person-plus" aria-hidden="true"></i> Assign an agent</h2>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="assign_agent">
            <select class="form-select mb-2" name="agent_id" required>
              <option value="">Select agent</option>
              <?php foreach ($agentOptions as $agent): ?>
                <option value="<?= (int) $agent['id'] ?>" <?= selected_if((string) $agent['id'], $job['agent_id'] ? (string) $job['agent_id'] : null) ?>><?= e($agent['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary w-100" type="submit">Save assignment</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (has_role(['admin']) || ($user['role'] === 'agent' && (int) $job['agent_id'] === (int) $user['id'])): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Update progress</h2>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="update_status">
            <?php $statusOptions = has_role(['admin']) ? job_status_options() : agent_job_status_options(); ?>
            <select class="form-select mb-2" name="status" required>
              <?php foreach ($statusOptions as $status): ?>
                <option value="<?= e($status) ?>" <?= selected_if($status, $job['status']) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary w-100" type="submit">Update status</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (has_role(['admin']) && in_array($jobStatus, ['delivered', 'closed', 'revision_requested', 'cancelled'], true)): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6">Reopen job</h2>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="reopen_job">
            <label class="form-label">Reason (optional)</label>
            <input class="form-control mb-2" type="text" name="reopen_note" placeholder="Reason for reopening">
            <button class="btn btn-outline-dark w-100" type="submit">Reopen to in progress</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (has_role(['admin', 'finance'])): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6"><i class="bi bi-receipt" aria-hidden="true"></i> Billing and commission</h2>
          <form method="post" action="<?= e(url('job', ['id' => (int) $job['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="update_finance">
            <div class="mb-2">
              <label class="form-label">Subtotal</label>
              <input class="form-control" name="subtotal_amount" type="number" step="0.01" min="0" value="<?= e((string) ($job['subtotal_amount'] ?? $job['amount'])) ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Currency</label>
              <select class="form-select" name="currency">
                <?php foreach (currency_options() as $currency): ?>
                  <option value="<?= e($currency) ?>" <?= selected_if($currency, $job['currency'] ?? default_currency()) ?>><?= e($currency) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Commission %</label>
              <input class="form-control" name="commission_percent" type="number" step="0.01" min="0" max="100" value="<?= e($job['commission_percent']) ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Tax %</label>
              <input class="form-control" name="tax_percent" type="number" step="0.01" min="0" max="100" value="<?= e((string) ($job['tax_percent'] ?? '0')) ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Payment status</label>
              <select class="form-select" name="payment_status">
                <?php foreach (['unpaid', 'partial', 'paid', 'refunded'] as $status): ?>
                  <option value="<?= e($status) ?>" <?= selected_if($status, $jobPaymentStatus) ?>><?= e($paymentStatusLabels[$status] ?? ucwords($status)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Payment reference</label>
              <input class="form-control" name="payment_reference" value="<?= e($job['payment_reference'] ?? '') ?>">
            </div>
            <button class="btn btn-outline-primary w-100" type="submit">Save finance details</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <h2 class="h6"><i class="bi bi-clock-history" aria-hidden="true"></i> Activity timeline</h2>
        <?php if (!empty($job['delivered_at'])): ?>
          <div class="timeline-item">
            <div class="small text-muted">Delivered record</div>
            <div>
              Delivered at <strong><?= e((string) $job['delivered_at']) ?></strong>
              <?= !empty($job['delivered_by_name']) ? ' by <strong>' . e((string) $job['delivered_by_name']) . '</strong>' : '' ?>.
            </div>
          </div>
        <?php endif; ?>
        <?php if (!empty($job['accepted_at'])): ?>
          <div class="timeline-item">
            <div class="small text-muted">Acceptance record</div>
            <div>
              Accepted/closed at <strong><?= e((string) $job['accepted_at']) ?></strong>
              <?= !empty($job['accepted_by_name']) ? ' by <strong>' . e((string) $job['accepted_by_name']) . '</strong>' : '' ?>.
            </div>
          </div>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
          <div class="timeline-item">
            <div class="small text-muted"><?= e($log['created_at']) ?></div>
            <div><strong><?= e($log['full_name']) ?></strong> changed <code><?= e((string) $log['old_status']) ?></code> to <code><?= e($log['new_status']) ?></code></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
          <div class="text-muted small">No status updates yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div><!-- /.iw-job-workspace -->

<script>
(function () {
  var form = document.getElementById('jobCommentForm');
  var input = document.getElementById('jobCommentInput');
  var hint = document.getElementById('jobCommentHint');
  if (!form || !input) return;

  var hintTimer = null;
  function showHint(message) {
    if (!hint) return;
    hint.textContent = message;
    hint.classList.add('active');
    if (hintTimer) {
      window.clearTimeout(hintTimer);
    }
    hintTimer = window.setTimeout(function () {
      hint.classList.remove('active');
      hint.textContent = 'Press Enter to post. Shift+Enter for a new line.';
    }, 1800);
  }

  input.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
      return;
    }
    event.preventDefault();
    if ((input.value || '').trim() === '') {
      showHint('Please write a comment before sending.');
      return;
    }
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  });

  form.addEventListener('submit', function (event) {
    if ((input.value || '').trim() !== '') {
      return;
    }
    event.preventDefault();
    showHint('Please write a comment before sending.');
  });
})();

(function () {
  var form = document.getElementById('jobFileUploadForm');
  var input = document.getElementById('jobFileAttachmentInput');
  var dropzone = document.getElementById('jobFileDropzone');
  var list = document.getElementById('jobUploadSelectedList');
  var hint = document.getElementById('jobUploadHint');
  var submitButton = document.getElementById('jobUploadSubmitBtn');
  if (!form || !input || !list) return;

  var hintTimer = null;
  var uploadQueue = [];
  var fileCounter = 0;
  var isUploading = false;
  var maxBytes = 20 * 1024 * 1024;
  var blockedExt = ['php', 'phtml', 'phar', 'exe', 'sh', 'pl', 'cgi', 'js'];

  function showHint(message) {
    if (!hint) return;
    hint.textContent = message;
    hint.classList.add('active');
    if (hintTimer) {
      window.clearTimeout(hintTimer);
    }
    hintTimer = window.setTimeout(function () {
      hint.classList.remove('active');
      hint.textContent = 'Choose at least one file.';
    }, 2200);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatBytes(bytes) {
    var size = Number(bytes || 0);
    if (size <= 0) return '0 B';
    var units = ['B', 'KB', 'MB', 'GB'];
    var unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
      size = size / 1024;
      unitIndex += 1;
    }
    var fixed = size >= 100 || unitIndex === 0 ? 0 : 1;
    return size.toFixed(fixed) + ' ' + units[unitIndex];
  }

  function fileIsBlocked(name) {
    var ext = String(name || '').toLowerCase().split('.').pop();
    return blockedExt.indexOf(ext) !== -1;
  }

  function renderList() {
    if (!uploadQueue.length) {
      list.innerHTML = '';
      list.classList.add('d-none');
      if (submitButton) {
        submitButton.disabled = false;
      }
      return;
    }

    var html = '';
    uploadQueue.forEach(function (entry) {
      var rowClass = 'job-upload-selected-item';
      if (entry.status === 'uploading') rowClass += ' is-uploading';
      if (entry.status === 'done') rowClass += ' is-done';
      if (entry.status === 'error') rowClass += ' is-error';

      html += '<div class="' + rowClass + '">';
      html += '<div class="job-upload-selected-main">';
      html += '<span class="job-upload-selected-name">' + escapeHtml(entry.file.name || 'file') + '</span>';
      html += '<span class="job-upload-selected-meta">' + escapeHtml(formatBytes(entry.file.size || 0)) + '</span>';
      html += '<div class="job-upload-progress"><span style="width:' + String(Math.max(0, Math.min(100, entry.progress || 0))) + '%"></span></div>';
      if (entry.status === 'error' && entry.errorMessage) {
        html += '<span class="job-upload-selected-error">' + escapeHtml(entry.errorMessage) + '</span>';
      }
      html += '</div>';
      if (entry.status !== 'uploading' && entry.status !== 'done') {
        html += '<button class="btn btn-sm btn-outline-danger py-0 px-2" type="button" data-remove-file-id="' + entry.id + '">Remove</button>';
      } else {
        html += '<span class="job-upload-status-badge">' + escapeHtml(entry.status === 'done' ? 'Uploaded' : 'Uploading...') + '</span>';
      }
      html += '</div>';
    });
    list.innerHTML = html;
    list.classList.remove('d-none');

    if (submitButton) {
      submitButton.disabled = isUploading;
    }
  }

  function queueFiles(fileList) {
    if (!fileList || !fileList.length) {
      return;
    }

    var added = 0;
    Array.prototype.forEach.call(fileList, function (file) {
      if (!file || !file.name) return;
      if (fileIsBlocked(file.name)) {
        showHint('One or more files are blocked for security reasons.');
        return;
      }
      if (Number(file.size || 0) > maxBytes) {
        showHint('Each file must be 20MB or less.');
        return;
      }

      var duplicate = uploadQueue.some(function (existing) {
        return existing.file.name === file.name
          && Number(existing.file.size || 0) === Number(file.size || 0)
          && Number(existing.file.lastModified || 0) === Number(file.lastModified || 0);
      });
      if (duplicate) {
        return;
      }

      fileCounter += 1;
      uploadQueue.push({
        id: 'f_' + String(fileCounter),
        file: file,
        progress: 0,
        status: 'queued',
        errorMessage: ''
      });
      added += 1;
    });

    if (added > 0) {
      showHint(added === 1 ? '1 file staged. Click submit to upload.' : (String(added) + ' files staged. Click submit to upload.'));
    }
    renderList();
  }

  function removeQueuedFile(fileId) {
    if (isUploading) return;
    uploadQueue = uploadQueue.filter(function (entry) {
      return entry.id !== fileId;
    });
    renderList();
  }

  function buildRequestPayload(file) {
    var data = new FormData();
    var csrf = form.querySelector('input[name=\"csrf_token\"]');
    var action = form.querySelector('input[name=\"form_action\"]');
    data.append('csrf_token', csrf ? csrf.value : '');
    data.append('form_action', action ? action.value : 'upload_file');
    data.append('ajax', '1');
    data.append('attachment[]', file, file.name || 'upload.bin');
    return data;
  }

  function uploadSingle(entry) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.getAttribute('action') || window.location.href, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.addEventListener('progress', function (event) {
        if (!event.lengthComputable) return;
        var percent = Math.round((event.loaded / event.total) * 100);
        entry.progress = Math.max(4, Math.min(98, percent));
        renderList();
      });

      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        var payload = null;
        try {
          payload = JSON.parse(xhr.responseText || '{}');
        } catch (error) {
          payload = null;
        }

        if (xhr.status >= 200 && xhr.status < 300 && payload && payload.success) {
          entry.status = 'done';
          entry.progress = 100;
          entry.errorMessage = '';
          renderList();
          resolve(payload);
          return;
        }

        entry.status = 'error';
        entry.progress = 0;
        entry.errorMessage = (payload && payload.message) ? String(payload.message) : 'Upload failed.';
        renderList();
        reject(new Error(entry.errorMessage));
      };

      entry.status = 'uploading';
      entry.progress = 3;
      entry.errorMessage = '';
      renderList();
      xhr.send(buildRequestPayload(entry.file));
    });
  }

  async function uploadAll() {
    if (isUploading) return;
    if (!uploadQueue.length) {
      showHint('Please stage one or more files before upload.');
      return;
    }

    isUploading = true;
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Uploading...';
    }

    var successCount = 0;
    for (var i = 0; i < uploadQueue.length; i += 1) {
      var entry = uploadQueue[i];
      if (!entry || entry.status === 'done') {
        continue;
      }
      try {
        await uploadSingle(entry);
        successCount += 1;
      } catch (error) {
      }
    }

    isUploading = false;
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = 'Submit uploaded files';
    }

    var failedCount = uploadQueue.filter(function (entry) {
      return entry.status === 'error';
    }).length;

    if (failedCount === 0 && successCount > 0) {
      showHint(successCount === 1 ? '1 file uploaded successfully.' : (String(successCount) + ' files uploaded successfully.'));
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
      return;
    }

    if (successCount > 0 && failedCount > 0) {
      showHint(String(successCount) + ' uploaded, ' + String(failedCount) + ' failed. Remove failed files and retry.');
      uploadQueue = uploadQueue.filter(function (entry) {
        return entry.status !== 'done';
      });
      renderList();
      return;
    }

    showHint('Upload failed. Please review files and try again.');
  }

  if (dropzone) {
    dropzone.addEventListener('click', function () {
      input.click();
    });

    dropzone.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }
      event.preventDefault();
      input.click();
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        if (isUploading) return;
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone.addEventListener('drop', function (event) {
      if (isUploading) return;
      var dropped = event.dataTransfer ? event.dataTransfer.files : null;
      queueFiles(dropped);
    });
  }

  input.addEventListener('change', function () {
    if (isUploading) return;
    queueFiles(input.files);
    input.value = '';
  });

  list.addEventListener('click', function (event) {
    var node = event.target && event.target.nodeType === 1 ? event.target : event.target && event.target.parentElement;
    var removeButton = node && typeof node.closest === 'function' ? node.closest('[data-remove-file-id]') : null;
    if (!removeButton) return;
    removeQueuedFile(removeButton.getAttribute('data-remove-file-id') || '');
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    uploadAll();
  });

  renderList();
})();
</script>
