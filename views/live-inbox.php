<?php
/** @var bool $liveChatInstalled */
?>
<section class="iw-live-admin" id="iwLiveInbox"
  data-endpoint="<?= e(url('live_inbox_api')) ?>" data-csrf="<?= e(csrf_token()) ?>">
  <header class="iw-live-admin-head">
    <div><span class="iw-live-overline">CUSTOMER EXPERIENCE</span><h1>Website live chats</h1>
      <p>Reply to visitors, track conversations and see who is currently online.</p></div>
    <div class="iw-live-head-actions">
      <button type="button" class="btn btn-outline-secondary" id="iwLiveSound"><i class="bi bi-bell"></i> Enable sound alerts</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('messages')) ?>"><i class="bi bi-chat-dots"></i> Team messages</a>
      <?php if (has_role(['admin'])): ?><a class="btn btn-outline-secondary" href="<?= e(url('support')) ?>"><i class="bi bi-life-preserver"></i> Support tickets</a><?php endif; ?>
    </div>
  </header>
  <?php if (!$liveChatInstalled): ?>
    <div class="alert alert-warning" role="alert"><strong>Live chat database is not installed.</strong>
      After a database backup, run <code>php scripts/migrate_live_chat.php --apply</code> from the portal directory.
      Then refresh this page. No messages can be received until the tables exist.</div>
  <?php else: ?>
    <div class="iw-live-summary" aria-live="polite">
      <div><span class="iw-live-summary-dot"></span><strong id="iwLiveOnline">0</strong><small>Visitors active</small></div>
      <div><i class="bi bi-inboxes"></i><strong id="iwLiveOpen">0</strong><small>Open conversations</small></div>
      <div><i class="bi bi-reply-all"></i><strong id="iwLiveAwaiting">0</strong><small>Awaiting staff reply</small></div>
      <div><i class="bi bi-calendar2-check"></i><strong id="iwLiveToday">0</strong><small>New chats today</small></div>
      <div><i class="bi bi-people"></i><strong id="iwLiveAgents">0</strong><small>Staff available</small></div>
    </div>
    <div class="iw-live-activity-band">
      <div><strong>Message activity</strong><small>Last seven days</small></div>
      <div class="iw-live-activity-bars" id="iwLiveActivityBars" role="img" aria-label="Messages sent over the last seven days"></div>
      <a href="<?= e(url('dashboard')) ?>">View overview <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
    <div class="iw-live-layout">
      <aside class="iw-live-thread-panel" aria-label="Visitor conversations">
        <div class="iw-live-thread-title"><h2>Conversations</h2><span id="iwLiveTotal">0</span></div>
        <label class="iw-live-search-label"><i class="bi bi-search" aria-hidden="true"></i>
          <input id="iwLiveFilter" type="search" placeholder="Search name, email or reference" aria-label="Search conversations"></label>
        <div id="iwLiveThreads" class="iw-live-thread-list"><p class="iw-live-muted">Loading conversations…</p></div>
      </aside>
      <section class="iw-live-chat-panel" aria-labelledby="iwLiveChatHeading">
        <div class="iw-live-chat-bar">
          <div><h2 id="iwLiveChatHeading">Select a conversation</h2><p id="iwLiveChatMeta">Visitor details will appear here.</p></div>
          <button type="button" id="iwLiveClose" class="btn btn-outline-secondary btn-sm" disabled>Close chat</button>
        </div>
        <div class="iw-live-chat-messages" id="iwLiveMessages" role="log" aria-live="polite" aria-label="Live chat messages">
          <div class="iw-live-placeholder"><i class="bi bi-chat-left-text"></i><strong>No conversation selected</strong><p>New website enquiries appear here automatically.</p></div>
        </div>
        <form id="iwLiveReplyForm" class="iw-live-reply-form">
          <label for="iwLiveReply" class="visually-hidden">Reply to visitor</label>
          <textarea id="iwLiveReply" rows="2" maxlength="2000" placeholder="Choose a conversation to reply…" disabled required></textarea>
          <button type="submit" id="iwLiveSend" disabled><i class="bi bi-send-fill"></i><span>Send reply</span></button>
        </form>
        <p class="iw-live-status" id="iwLiveStatus" role="status">Connecting…</p>
      </section>
    </div>
    <p class="iw-live-privacy-note"><i class="bi bi-shield-lock"></i> This dashboard includes people who opened a website chat. Current public page paths are recorded while an initiated support chat is active; no URL query strings, full browsing history or precise addresses are collected. Country is an approximate server hint and network prefixes are masked. Support reply emails and closure receipts are transactional. Closed-case histories expire after 30 days of inactivity via the scheduled worker.</p>
  <?php endif; ?>
</section>
<?php if ($liveChatInstalled): ?>
<script defer src="assets/js/live-inbox.js?v=20261115-casehistory1"></script>
<?php endif; ?>
