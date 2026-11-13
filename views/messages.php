<?php $currentUser = current_user(); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div><span class="iw-ops-eyebrow">COMMUNICATION</span><h1 class="h4 mb-0">Team messages</h1></div>
  <?php if (has_role(['admin','agent'])): ?><a class="btn btn-outline-primary btn-sm" href="<?= e(url('live_inbox')) ?>"><i class="bi bi-chat-left-text"></i> Website live chats</a><?php endif; ?>
</div>

<?php if (role_has_admin_access((string) ($currentUser['role'] ?? ''))): ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="post" action="<?= e(url('messages')) ?>" class="row g-2 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="start_chat">
      <div class="col-md-8">
        <label class="form-label mb-1">Start chat with agent</label>
        <select class="form-select" name="agent_id" required>
          <option value="">Select agent</option>
          <?php foreach ($adminAgentOptions as $agent): ?>
            <option value="<?= (int) $agent['id'] ?>"><?= e($agent['full_name']) ?> (<?= e($agent['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <button class="btn btn-outline-primary w-100" type="submit">Open chat</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100 chat-thread-card">
      <div class="card-body p-0">
        <div class="list-group list-group-flush chat-thread-list">
          <?php foreach ($threads as $thread): ?>
            <?php
              $isActive = $activeThread && (int) $activeThread['id'] === (int) $thread['id'];
              $isAdminViewer = role_has_admin_access((string) ($currentUser['role'] ?? ''));
              $label = $isAdminViewer ? ($thread['agent_name'] ?? 'Agent') : ($thread['admin_name'] ?? 'Admin');
              $online = $isAdminViewer
                ? is_user_online((string) ($thread['agent_last_seen_at'] ?? ''))
                : is_user_online((string) ($thread['admin_last_seen_at'] ?? ''));
            ?>
            <a class="list-group-item list-group-item-action chat-thread-item <?= $isActive ? 'active' : '' ?>" href="<?= e(url('messages', ['thread_id' => (int) $thread['id']])) ?>">
              <div class="fw-semibold d-flex align-items-center gap-2">
                <span class="online-dot <?= $online ? 'online' : 'offline' ?>"></span>
                <?= e($label) ?>
              </div>
              <?php if (!empty($thread['last_message'])): ?>
                <small class="d-block text-truncate"><?= e($thread['last_message']) ?></small>
              <?php else: ?>
                <small class="text-muted">No messages yet.</small>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if (!$threads): ?>
            <div class="p-3 text-muted">No chats yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card h-100 chat-main-card">
      <?php if ($activeThread): ?>
        <div class="card-header bg-white">
          <?php
            $activeOnline = role_has_admin_access((string) ($currentUser['role'] ?? ''))
              ? is_user_online((string) ($activeThread['agent_last_seen_at'] ?? ''))
              : is_user_online((string) ($activeThread['admin_last_seen_at'] ?? ''));
          ?>
          <strong class="d-flex align-items-center gap-2">
            <span class="online-dot <?= $activeOnline ? 'online' : 'offline' ?>"></span>
            <?= e(role_has_admin_access((string) ($currentUser['role'] ?? '')) ? $activeThread['agent_name'] : $activeThread['admin_name']) ?>
          </strong>
          <div class="small text-muted" id="chatTypingIndicator" style="display:none;">Typing...</div>
        </div>
        <div class="card-body chat-body whatsapp-chat-body" id="chatBody" data-thread-id="<?= (int) $activeThread['id'] ?>" data-current-user-id="<?= (int) $currentUser['id'] ?>">
          <?php foreach ($chatMessages as $message): ?>
            <?php $mine = (int) $message['sender_id'] === (int) $currentUser['id']; ?>
            <div class="chat-row <?= $mine ? 'mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
              <div class="chat-bubble whatsapp-bubble">
                <div class="small text-muted mb-1"><?= e($message['sender_name']) ?> • <?= e($message['created_at']) ?></div>
                <?php if (!empty($message['message'])): ?>
                  <div><?= nl2br(e($message['message'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($message['attachment_path'])): ?>
                  <div class="mt-2">
                    <a class="btn btn-sm btn-outline-secondary" href="download.php?chat_message_id=<?= (int) $message['id'] ?>">Download file</a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$chatMessages): ?>
            <div class="text-muted" id="chatEmptyState">No messages yet. Send the first message.</div>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
          <form method="post" action="<?= e(url('messages', ['thread_id' => (int) $activeThread['id']])) ?>" enctype="multipart/form-data" class="row g-2 align-items-end" id="chatComposeForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="send_message">
            <input type="hidden" name="thread_id" value="<?= (int) $activeThread['id'] ?>">
            <div class="col-md-9">
              <label class="form-label mb-1">Message</label>
              <textarea class="form-control chat-compose-input" name="message" id="chatMessageInput" rows="2" placeholder="Type message..."></textarea>
              <small class="form-text enter-send-hint" id="chatComposeHint">Press Enter to send. Shift+Enter for a new line.</small>
            </div>
            <div class="col-md-3">
              <label class="form-label mb-1">Attachment (optional)</label>
              <input class="form-control" type="file" name="attachment" id="chatAttachmentInput">
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Send</button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div class="card-body text-muted">Select a chat thread to begin messaging.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($activeThread): ?>
<script>
(function () {
  var chatBody = document.getElementById('chatBody');
  if (!chatBody) return;

  var threadId = parseInt(chatBody.getAttribute('data-thread-id') || '0', 10);
  var currentUserId = parseInt(chatBody.getAttribute('data-current-user-id') || '0', 10);
  var typingIndicator = document.getElementById('chatTypingIndicator');
  var composeForm = document.getElementById('chatComposeForm');
  var messageInput = document.getElementById('chatMessageInput');
  var attachmentInput = document.getElementById('chatAttachmentInput');
  var composeHint = document.getElementById('chatComposeHint');
  var polling = false;
  var audioUnlocked = false;
  var typingPolling = false;
  var lastTypingPing = 0;
  var composeHintTimer = null;

  function unlockAudio() {
    audioUnlocked = true;
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('keydown', unlockAudio);
  }

  function playMessageSound() {
    if (!audioUnlocked) return;
    var AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    try {
      var ctx = new AudioCtx();
      var gain = ctx.createGain();
      gain.gain.value = 0.12;
      gain.connect(ctx.destination);

      var o1 = ctx.createOscillator();
      o1.type = 'triangle';
      o1.frequency.value = 980;
      o1.connect(gain);
      o1.start();
      o1.stop(ctx.currentTime + 0.16);

      var o2 = ctx.createOscillator();
      o2.type = 'triangle';
      o2.frequency.value = 740;
      o2.connect(gain);
      o2.start(ctx.currentTime + 0.18);
      o2.stop(ctx.currentTime + 0.35);

      o2.onended = function () { ctx.close(); };
    } catch (err) {
      // Ignore sound errors.
    }
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function lastMessageId() {
    var rows = chatBody.querySelectorAll('[data-message-id]');
    if (!rows.length) return 0;
    return parseInt(rows[rows.length - 1].getAttribute('data-message-id') || '0', 10) || 0;
  }

  function appendMessage(message) {
    var emptyState = document.getElementById('chatEmptyState');
    if (emptyState) {
      emptyState.remove();
    }

    var mine = parseInt(message.sender_id || 0, 10) === currentUserId;
    var wrapper = document.createElement('div');
    wrapper.className = 'chat-row' + (mine ? ' mine' : '');
    wrapper.setAttribute('data-message-id', String(message.id || 0));

    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble';

    var meta = document.createElement('div');
    meta.className = 'small text-muted mb-1';
    meta.textContent = (message.sender_name || 'User') + ' • ' + (message.created_at || '');
    bubble.appendChild(meta);

    if (message.message) {
      var body = document.createElement('div');
      body.innerHTML = escapeHtml(message.message).replace(/\n/g, '<br>');
      bubble.appendChild(body);
    }

    if (message.attachment_url) {
      var fileWrap = document.createElement('div');
      fileWrap.className = 'mt-2';
      var link = document.createElement('a');
      link.className = 'btn btn-sm btn-outline-secondary';
      link.href = message.attachment_url;
      link.setAttribute('data-file-preview', '1');
      if (message.attachment_name) {
        link.setAttribute('data-file-name', String(message.attachment_name));
      }
      link.textContent = 'Download file';
      fileWrap.appendChild(link);
      bubble.appendChild(fileWrap);
    }

    wrapper.appendChild(bubble);
    chatBody.appendChild(wrapper);

    if (!mine) {
      playMessageSound();
    }
  }

  function pollChat() {
    if (polling || threadId <= 0) return;
    polling = true;

    var afterId = lastMessageId();
    fetch('index.php?page=realtime&scope=chat&thread_id=' + encodeURIComponent(threadId) + '&after_id=' + encodeURIComponent(afterId), {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error('Realtime chat failed');
        return resp.json();
      })
      .then(function (data) {
        if (!data || data.success !== true || !Array.isArray(data.messages)) return;
        data.messages.forEach(appendMessage);
        if (data.messages.length > 0) {
          chatBody.scrollTop = chatBody.scrollHeight;
        }
      })
      .catch(function () {
        // Ignore polling failures.
      })
      .finally(function () {
        polling = false;
      });
  }

  function showTyping(isTyping) {
    if (!typingIndicator) return;
    typingIndicator.style.display = isTyping ? 'block' : 'none';
  }

  function showComposeHint(message) {
    if (!composeHint) return;
    composeHint.textContent = message;
    composeHint.classList.add('active');
    if (composeHintTimer) {
      window.clearTimeout(composeHintTimer);
    }
    composeHintTimer = window.setTimeout(function () {
      composeHint.classList.remove('active');
      composeHint.textContent = 'Press Enter to send. Shift+Enter for a new line.';
    }, 1800);
  }

  function pingTyping(force) {
    if (threadId <= 0 || !messageInput) return;
    var message = (messageInput.value || '').trim();
    if (message === '') return;

    var nowMs = Date.now();
    if (!force && nowMs - lastTypingPing < 1400) return;
    lastTypingPing = nowMs;

    fetch('index.php?page=realtime&scope=chat_typing&thread_id=' + encodeURIComponent(threadId) + '&typing=1', {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    }).catch(function () {
      // Ignore typing ping errors.
    });
  }

  function pollTyping() {
    if (typingPolling || threadId <= 0) return;
    typingPolling = true;
    fetch('index.php?page=realtime&scope=chat_typing&thread_id=' + encodeURIComponent(threadId), {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error('typing failed');
        return resp.json();
      })
      .then(function (data) {
        if (!data || data.success !== true) return;
        showTyping(!!data.is_typing);
      })
      .catch(function () {
        // Ignore typing poll errors.
      })
      .finally(function () {
        typingPolling = false;
      });
  }

  document.addEventListener('click', unlockAudio, { once: false });
  document.addEventListener('keydown', unlockAudio, { once: false });
  if (messageInput) {
    messageInput.addEventListener('input', function () { pingTyping(false); });
    messageInput.addEventListener('focus', function () { pingTyping(true); });
    messageInput.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
      }

      event.preventDefault();
      var hasText = (messageInput.value || '').trim() !== '';
      var hasFile = !!(attachmentInput && attachmentInput.files && attachmentInput.files.length > 0);
      if (!hasText && !hasFile) {
        showComposeHint('Type a message or attach a file before sending.');
        return;
      }
      if (composeForm) {
        if (typeof composeForm.requestSubmit === 'function') {
          composeForm.requestSubmit();
        } else {
          composeForm.submit();
        }
      }
    });
  }
  if (composeForm) {
    composeForm.addEventListener('submit', function (event) {
      var hasText = !!(messageInput && (messageInput.value || '').trim() !== '');
      var hasFile = !!(attachmentInput && attachmentInput.files && attachmentInput.files.length > 0);
      if (hasText || hasFile) {
        return;
      }
      event.preventDefault();
      showComposeHint('Type a message or attach a file before sending.');
    });
  }
  chatBody.scrollTop = chatBody.scrollHeight;
  setInterval(pollChat, 3500);
  setInterval(pollTyping, 1800);
})();
</script>
<?php endif; ?>
