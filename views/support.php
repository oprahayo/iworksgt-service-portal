<?php $user = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Support Center</h1>
  <?php if ($user['role'] === 'client'): ?>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createSupportTicketModal">
      <i class="bi bi-plus-lg"></i> New Ticket
    </button>
  <?php endif; ?>
</div>

<div class="support-layout">
  <aside class="card support-sidebar-card">
    <div class="card-body p-0">
      <div class="support-sidebar-head">
        <div>
          <div class="fw-semibold">Tickets</div>
          <small class="text-muted"><?= count($tickets) ?> found</small>
        </div>
        <?php if ($user['role'] === 'client'): ?>
          <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createSupportTicketModal">
            <i class="bi bi-plus-lg"></i>
          </button>
        <?php endif; ?>
      </div>

      <details class="support-filter-toggle">
        <summary>Filters</summary>
        <form class="row g-2 mt-2" method="get" action="index.php">
          <input type="hidden" name="page" value="support">
          <div class="col-12">
            <label class="form-label mb-1">Status</label>
            <select class="form-select form-select-sm" name="status">
              <option value="">All statuses</option>
              <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $status): ?>
                <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($ticketFilters['status'] ?? '')) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label mb-1">Priority</label>
            <select class="form-select form-select-sm" name="priority">
              <option value="">All priorities</option>
              <?php foreach (['low', 'normal', 'high', 'urgent'] as $priority): ?>
                <option value="<?= e($priority) ?>" <?= selected_if($priority, (string) ($ticketFilters['priority'] ?? '')) ?>><?= e(ucwords($priority)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label mb-1">Sort</label>
            <select class="form-select form-select-sm" name="sort">
              <?php
              $ticketSortOptions = [
                  'updated' => 'Latest activity',
                  'newest' => 'Newest first',
                  'oldest' => 'Oldest first',
                  'priority' => 'Priority order',
              ];
              foreach ($ticketSortOptions as $key => $label):
              ?>
                <option value="<?= e($key) ?>" <?= selected_if($key, (string) ($ticketFilters['sort'] ?? 'updated')) ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-sm btn-outline-primary" type="submit">Apply</button>
          </div>
        </form>
      </details>

      <div class="support-ticket-list">
        <?php foreach ($tickets as $ticket): ?>
          <a class="support-ticket-item <?= ($activeTicket && (int) $activeTicket['id'] === (int) $ticket['id']) ? 'active' : '' ?>" href="<?= e(url('support', ['ticket_id' => (int) $ticket['id']])) ?>">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
              <strong><?= e((string) $ticket['ticket_code']) ?></strong>
              <?= status_badge((string) $ticket['status']) ?>
            </div>
            <div class="small fw-semibold text-truncate"><?= e((string) $ticket['subject']) ?></div>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <small class="text-muted"><?= e((string) $ticket['client_name']) ?></small>
              <small><?= status_badge((string) $ticket['priority']) ?></small>
            </div>
          </a>
        <?php endforeach; ?>

        <?php if (!$tickets): ?>
          <div class="support-empty-list text-center">
            <div class="mb-2"><i class="bi bi-chat-left-text fs-4" style="color: var(--brand-primary);"></i></div>
            <div class="fw-semibold">No tickets found</div>
            <small class="text-muted">Create your first ticket to contact support.</small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <section class="card support-main-card">
    <div class="card-body">
      <?php if ($activeTicket): ?>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <h2 class="h5 mb-1"><?= e($activeTicket['ticket_code']) ?> - <?= e($activeTicket['subject']) ?></h2>
            <div class="small text-muted">Category: <?= e($activeTicket['category']) ?> | Priority: <?= e(ucwords((string) $activeTicket['priority'])) ?></div>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <?= status_badge((string) $activeTicket['status']) ?>
            <?php if (has_role(['admin', 'finance'])): ?>
              <form method="post" action="<?= e(url('support', ['ticket_id' => (int) $activeTicket['id']])) ?>" class="d-flex gap-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="update_ticket_status">
                <input type="hidden" name="ticket_id" value="<?= (int) $activeTicket['id'] ?>">
                <select class="form-select form-select-sm" name="status">
                  <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected_if($status, (string) ($activeTicket['status'] ?? 'open')) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="chat-body support-chat-body p-2 mb-3">
          <?php foreach ($ticketMessages as $message): ?>
            <?php $mine = (int) $message['sender_id'] === (int) $user['id']; ?>
            <div class="chat-row <?= $mine ? 'mine' : '' ?>">
              <div class="chat-bubble">
                <div class="small text-muted mb-1">
                  <?= e($message['sender_name']) ?>
                  <?php if (role_has_admin_access((string) ($message['sender_role'] ?? ''))): ?><span class="badge text-bg-dark">Admin</span><?php endif; ?>
                  <?php if (($message['sender_role'] ?? '') === 'finance'): ?><span class="badge text-bg-secondary">Support</span><?php endif; ?>
                </div>
                <?php if (!empty($message['message'])): ?><div><?= nl2br(e((string) $message['message'])) ?></div><?php endif; ?>
                <?php if (!empty($message['attachment_path'])): ?>
                  <div class="mt-2">
                    <a class="btn btn-sm btn-outline-secondary" href="download.php?support_message_id=<?= (int) $message['id'] ?>">Download attachment</a>
                  </div>
                <?php endif; ?>
                <div class="small text-muted mt-1"><?= e((string) $message['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$ticketMessages): ?>
            <div class="text-muted">No replies yet.</div>
          <?php endif; ?>
        </div>

        <?php if ($user['role'] === 'client' && in_array((string) ($activeTicket['status'] ?? ''), ['resolved', 'closed'], true)): ?>
          <div class="alert alert-warning mb-0">This ticket is currently closed. You can reply again once support reopens it.</div>
        <?php else: ?>
          <form method="post" action="<?= e(url('support', ['ticket_id' => (int) $activeTicket['id']])) ?>" enctype="multipart/form-data" class="row g-2" id="supportReplyForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="reply_ticket">
            <input type="hidden" name="ticket_id" value="<?= (int) $activeTicket['id'] ?>">
            <div class="col-12">
              <label class="form-label">Reply</label>
              <textarea class="form-control" name="message" rows="3" placeholder="Write your message" id="supportReplyInput"></textarea>
              <small class="form-text enter-send-hint" id="supportReplyHint">Press Enter to send. Shift+Enter for a new line.</small>
            </div>
            <div class="col-md-7"><input class="form-control" type="file" name="attachment" id="supportReplyAttachment"></div>
            <div class="col-md-5 d-grid"><button class="btn btn-primary" type="submit">Send reply</button></div>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <div class="support-empty-main">
          <div class="mb-2 fs-1">👋</div>
          <h2 class="h4 mb-2">Welcome to Support</h2>
          <p class="text-muted mb-3">Open a ticket from the list to view messages and send replies.</p>
          <?php if ($user['role'] === 'client'): ?>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createSupportTicketModal">Create First Ticket</button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php if ($user['role'] === 'client'): ?>
  <div class="modal fade" id="createSupportTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="h5 mb-0">Create Support Ticket</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" action="<?= e(url('support')) ?>" enctype="multipart/form-data" id="supportCreateForm">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="create_ticket">
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Category</label>
                <select class="form-select" name="category">
                  <?php foreach (support_category_options() as $categoryCode => $categoryLabel): ?>
                    <option value="<?= e($categoryCode) ?>"><?= e($categoryLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority">
                  <option value="normal">Normal</option>
                  <option value="low">Low</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="supportRelatedServices">Related services (optional)</label>
                <select class="form-select" id="supportRelatedServices" name="related_job_ids[]" multiple size="4" aria-describedby="supportRelatedHint">
                  <?php foreach ($supportServices ?? [] as $relatedJob): ?>
                    <option value="<?= (int) ($relatedJob['id'] ?? 0) ?>">
                      <?= e((string) ($relatedJob['job_code'] ?? '')) ?> · <?= e((string) ($relatedJob['service_name'] ?? $relatedJob['title'] ?? 'Service')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small id="supportRelatedHint" class="text-muted">Select one or more of your orders (Ctrl/Cmd + click on desktop). Our support team will see their references in your ticket.</small>
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input class="form-control" name="subject" id="supportCreateSubject" required>
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="message" rows="4" required id="supportCreateMessage"></textarea>
                <small class="form-text enter-send-hint" id="supportCreateHint">Press Enter to submit ticket. Shift+Enter for a new line.</small>
              </div>
              <div class="col-12">
                <label class="form-label">Attachment</label>
                <input class="form-control" type="file" name="attachment">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" type="submit">Create Ticket</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
(function () {
  function wireEnterSend(config) {
    var form = document.getElementById(config.formId);
    var textarea = document.getElementById(config.textareaId);
    var hint = document.getElementById(config.hintId);
    if (!form || !textarea) {
      return;
    }

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
        hint.textContent = config.defaultHint;
      }, 1800);
    }

    function hasPayload() {
      var hasText = (textarea.value || '').trim() !== '';
      if (hasText) return true;
      if (!config.fileId) return false;
      var fileInput = document.getElementById(config.fileId);
      return !!(fileInput && fileInput.files && fileInput.files.length > 0);
    }

    textarea.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
      }
      event.preventDefault();
      if (!hasPayload()) {
        showHint(config.emptyHint);
        return;
      }
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });

    form.addEventListener('submit', function (event) {
      if (hasPayload()) {
        return;
      }
      event.preventDefault();
      showHint(config.emptyHint);
    });
  }

  wireEnterSend({
    formId: 'supportReplyForm',
    textareaId: 'supportReplyInput',
    hintId: 'supportReplyHint',
    fileId: 'supportReplyAttachment',
    emptyHint: 'Write a reply or attach a file before sending.',
    defaultHint: 'Press Enter to send. Shift+Enter for a new line.'
  });

  wireEnterSend({
    formId: 'supportCreateForm',
    textareaId: 'supportCreateMessage',
    hintId: 'supportCreateHint',
    fileId: null,
    emptyHint: 'Please enter a ticket description before submitting.',
    defaultHint: 'Press Enter to submit ticket. Shift+Enter for a new line.'
  });
})();
</script>
