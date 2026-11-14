<?php
/** Staff-only compact live engagement overview, above the dashboard shortcuts. */
$liveOverview = is_array($liveOverview ?? null) ? $liveOverview : [];
?>
<section class="iw-live-overview" id="iwLiveOverview"
    data-endpoint="<?= e(url('live_inbox_api')) ?>" data-csrf="<?= e(csrf_token()) ?>"
    aria-labelledby="iwLiveOverviewHeading">
  <header class="iw-live-overview-head">
    <div>
      <span class="iw-live-overview-eyebrow"><span class="iw-live-overview-led" aria-hidden="true"></span> CUSTOMER EXPERIENCE · LIVE</span>
      <h2 id="iwLiveOverviewHeading">Visitor conversations</h2>
      <p>See active support visitors, incoming questions and replies at a glance.</p>
    </div>
    <a href="<?= e(url('live_inbox')) ?>" class="iw-live-overview-link">
      Open live inbox <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
    </a>
  </header>
  <div class="iw-live-overview-stats" aria-label="Current website chat activity">
    <div class="iw-live-stat"><span><i class="bi bi-broadcast"></i> Active now</span><strong data-live-metric="online"><?= (int)($liveOverview['online']??0) ?></strong><small>Chat visitors active recently</small></div>
    <div class="iw-live-stat"><span><i class="bi bi-chat-left-dots"></i> Open chats</span><strong data-live-metric="open"><?= (int)($liveOverview['open']??0) ?></strong><small>Conversations in progress</small></div>
    <div class="iw-live-stat is-urgent"><span><i class="bi bi-reply"></i> Need a reply</span><strong data-live-metric="unanswered"><?= (int)($liveOverview['unanswered']??0) ?></strong><small>Visitor messages awaiting staff</small></div>
    <div class="iw-live-stat"><span><i class="bi bi-calendar2-check"></i> New today</span><strong data-live-metric="new_today"><?= (int)($liveOverview['new_today']??0) ?></strong><small>Conversations started today</small></div>
  </div>
  <div class="iw-live-overview-bottom">
    <div class="iw-live-overview-trend">
      <div class="iw-live-overview-section-title"><h3>Message activity</h3><small>Last 7 days</small></div>
      <div class="iw-live-overview-bars" id="iwLiveOverviewBars" role="img" aria-label="Last seven days of support chat message volume">
        <?php foreach ((array)($liveOverview['activity']??[]) as $day): ?>
          <span class="iw-live-overview-bar" title="<?= e((string)($day['day']??'')) ?>: <?= (int)($day['total']??0) ?> messages" style="--bar-level:<?= min(100,max(4,(int)($day['total']??0)*10)) ?>%">
            <i></i><small><?= e(substr((string)($day['day']??''),5)) ?></small>
          </span>
        <?php endforeach; ?>
      </div>
      <p class="iw-live-overview-legend">Live figures refresh while this dashboard is open.</p>
    </div>
    <div class="iw-live-overview-recent">
      <div class="iw-live-overview-section-title"><h3>Recent conversations</h3><a href="<?= e(url('live_inbox')) ?>">All chats</a></div>
      <div id="iwLiveOverviewRecent" class="iw-live-overview-recent-list">
        <?php foreach ((array)($liveOverview['recent']??[]) as $visitor): ?>
          <div><span class="iw-live-overview-presence<?= !empty($visitor['online'])?' is-online':'' ?>" aria-hidden="true"></span>
            <strong><?= e((string)($visitor['name']??'Visitor')) ?></strong><small><?= e((string)($visitor['page']??'/')) ?></small>
          </div>
        <?php endforeach; ?>
        <?php if (empty($liveOverview['recent'])): ?><p>No support conversations yet.</p><?php endif; ?>
      </div>
    </div>
  </div>
  <p class="iw-live-overview-disclosure">Online counts include visitors who opened a support conversation, not everyone browsing the website. Page names appear only with permission. No precise addresses or GPS are collected.</p>
</section>
<script defer src="assets/js/dashboard-live-visitors.js?v=20261114-visit1"></script>
