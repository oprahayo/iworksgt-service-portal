<?php
$user = current_user();
if (($user['role'] ?? '') === 'author') {
    require __DIR__ . '/author-dashboard.php';
    return;
}
$heroKicker = 'Workspace';
$heroMessage = 'Book services, track progress, and keep delivery fully remote.';

if (($user['role'] ?? '') === 'agent') {
    $heroKicker = 'Agent Workspace';
    $heroMessage = 'Claim paid jobs from the pool, deliver updates, and grow your earnings.';
}

if (role_has_admin_or_finance_access((string) ($user['role'] ?? ''))) {
    $heroKicker = 'Operations Hub';
    $heroMessage = 'Manage jobs, teams, payouts, and platform activity from one dashboard.';
}

if (($user['role'] ?? '') === 'super_admin') {
    $heroKicker = 'Super Admin Hub';
    $heroMessage = 'Manage registrations, customer services, editorial content and platform operations.';
}
?>
<?php if (!empty($blockingAnnouncement)): ?>
<div class="announcement-lock">
  <div class="announcement-card">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
      <h2 class="h5 mb-0"><?= e($blockingAnnouncement['title']) ?></h2>
      <span class="badge text-bg-warning">Announcement</span>
    </div>
    <div class="announcement-body mb-3">
      <?php if (!empty($blockingAnnouncement['message_html'])): ?>
        <?= (string) $blockingAnnouncement['message_html'] ?>
      <?php else: ?>
        <?= nl2br(e((string) ($blockingAnnouncement['message'] ?? ''))) ?>
      <?php endif; ?>
    </div>
    <?php if (!empty($blockingAnnouncement['attachment_path'])): ?>
      <div class="mb-3">
        <a class="btn btn-sm btn-outline-primary" href="download.php?announcement_id=<?= (int) $blockingAnnouncement['id'] ?>">Download attachment</a>
      </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('dashboard')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="acknowledge_announcement">
      <input type="hidden" name="announcement_id" value="<?= (int) $blockingAnnouncement['id'] ?>">
      <button class="btn btn-primary" type="submit">Read and close</button>
    </form>
  </div>
</div>
<script>document.body.classList.add('announcement-open');</script>
<?php endif; ?>

<div class="dashboard-page<?= $user['role'] === 'client' ? ' dashboard-page-client' : '' ?><?= $user['role']==='admin'?' iw-admin-workspace':'' ?>">
<div class="brand-hero mb-4">
  <small class="kicker text-white-50"><?= e($heroKicker) ?></small>
  <h1 class="h4 mt-2 mb-1">Hello, <?= e($user['full_name']) ?></h1>
  <p class="mb-0 text-white-50"><?= e($heroMessage) ?></p>
</div>

<?php if (in_array((string)($user['role'] ?? ''), ['admin','agent','finance'], true)): ?>
  <nav class="iw-ops-dashboard-links" aria-label="Common workspace actions">
    <a href="<?= e(url('jobs')) ?>"><i class="bi bi-briefcase" aria-hidden="true"></i> Service delivery</a>
    <?php if (has_role(['admin','agent'])): ?>
      <a href="<?= e(url('messages')) ?>"><i class="bi bi-chat-left-dots" aria-hidden="true"></i> Team messages</a>
      <a href="<?= e(url('blog')) ?>"><i class="bi bi-journal-richtext" aria-hidden="true"></i> Manage Insights</a>
    <?php endif; ?>
    <?php if (has_role(['admin','finance'])): ?>
      <a href="<?= e(url('services')) ?>"><i class="bi bi-grid" aria-hidden="true"></i> Manage services</a>
    <?php endif; ?>
    <a href="<?= e(public_url('home')) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> View live website</a>
    <?php if (has_role(['admin'])): ?><a href="<?= e(url('system_health')) ?>"><i class="bi bi-heart-pulse" aria-hidden="true"></i> System health</a><?php endif; ?>
  </nav>
<?php endif; ?>

<?php if($user['role']==='admin'): ?>
  <section class="iw-admin-launchpad" aria-label="Administration shortcuts">
    <div class="iw-admin-section-head"><div><span>QUICK ACCESS</span><h2>Manage iWorks</h2></div></div>
    <div class="iw-admin-launchpad-grid">
      <?php foreach([
        ['Jobs & fulfilment','Track service orders and delivery','jobs','bi-briefcase'],
        ['Payments & invoices','Keep collections and billing organised','payments','bi-credit-card'],
        ['Customers & authors','Manage account access and authors','users','bi-people'],
        ['Insights','Write and publish business articles','blog','bi-journal-richtext'],
        ['Case studies & feedback','Publish genuine client success stories','editorial_showcase','bi-collection'],
        ['Website editor','Update public page sections and graphics','frontend','bi-layout-text-window-reverse']
      ] as $shortcut): ?>
        <a href="<?= e(url($shortcut[2])) ?>" class="iw-admin-launchpad-link">
          <span class="iw-admin-launchpad-icon"><i class="bi <?= e($shortcut[3]) ?>"></i></span>
          <strong><?= e($shortcut[0]) ?></strong><small><?= e($shortcut[1]) ?></small>
          <i class="bi bi-arrow-up-right iw-admin-launchpad-arrow"></i>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $value): ?>
    <div class="col-6 col-lg-3">
      <div class="card metric">
        <div class="card-body">
          <small class="kicker"><?= e(ucwords(str_replace('_', ' ', $label))) ?></small>
          <h5><?= is_numeric($value) ? e((string) (is_float($value) ? money((float) $value) : $value)) : e((string) $value) ?></h5>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($user['role'] === 'agent' && $agentSummary): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="h5 mb-2">Agent earnings</h2>
    <div class="row g-3">
      <div class="col-md-3"><small class="kicker">Total earned</small><div class="fw-semibold"><?= e(money((float) $agentSummary['total_earned'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Available</small><div class="fw-semibold"><?= e(money((float) $agentSummary['available_balance'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Minimum request</small><div class="fw-semibold"><?= e(money((float) $agentSummary['minimum_threshold'])) ?></div></div>
      <div class="col-md-3"><small class="kicker">Request interval</small><div class="fw-semibold">Every <?= (int) $agentSummary['cooldown_days'] ?> days</div></div>
    </div>
    <?php if (!$agentSummary['can_request'] && !empty($agentSummary['next_request_at'])): ?>
      <div class="text-muted small mt-2">Next withdrawal request available after <?= e($agentSummary['next_request_at']) ?>.</div>
    <?php endif; ?>
    <a class="btn btn-outline-primary btn-sm mt-3" href="<?= e(url('withdrawals')) ?>">Open withdrawals</a>
  </div>
</div>
<?php endif; ?>

<?php if($user['role']!=='admin'): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="h5">Quick actions</h2>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-primary" href="<?= e(url('jobs')) ?>"><?= $user['role'] === 'client' ? 'Open orders' : 'Open jobs' ?></a>
      <?php if (has_role(['client', 'admin'])): ?>
        <a class="btn btn-outline-primary" href="<?= e(url('jobs', ['action' => 'new'])) ?>"><?= $user['role'] === 'client' ? 'Order New Service' : 'Create New Job' ?></a>
      <?php endif; ?>
      <?php if (has_role(['admin', 'finance'])): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('services')) ?>">Manage services</a>

        <a class="btn btn-outline-secondary" href="<?= e(url('payouts')) ?>">Commission payouts</a>
      <?php endif; ?>
      <?php if ($user['role'] === 'agent'): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('withdrawals')) ?>">Request withdrawal</a>
      <?php endif; ?>
      <?php if (has_role(['client', 'admin', 'finance'])): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('invoices')) ?>">Invoices</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('support')) ?>">Support</a>
      <?php endif; ?>
      <?php if (has_role(['agent', 'admin'])): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('community')) ?>">Community</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'client'): ?>
<section class="dashboard-banner-strip-wrap">
  <div class="card dashboard-banner-strip-card mb-2">
    <div class="card-body">
      <div class="dashboard-banner-strip-head">
        <h2 class="h6 mb-0">Sponsored Updates</h2>
        <small class="text-muted">Ad carousel</small>
      </div>

      <?php if ($banners): ?>
        <div id="clientBannerCarousel" class="carousel slide dashboard-banner-carousel" data-bs-ride="carousel" data-bs-interval="7000">
          <div class="carousel-indicators">
            <?php foreach ($banners as $index => $banner): ?>
              <button type="button" data-bs-target="#clientBannerCarousel" data-bs-slide-to="<?= (int) $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= (int) ($index + 1) ?>"></button>
            <?php endforeach; ?>
          </div>
          <div class="carousel-inner banner-carousel">
            <?php foreach ($banners as $index => $banner): ?>
              <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <?php if (!empty($banner['link_url'])): ?><a href="<?= e($banner['link_url']) ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                <picture>
                  <?php if (!empty($banner['mobile_image_path'])): ?>
                    <source media="(max-width: 767px)" srcset="<?= e($banner['mobile_image_path']) ?>">
                  <?php endif; ?>
                  <img src="<?= e($banner['image_path']) ?>" class="d-block w-100 banner-slide-image" alt="<?= e($banner['title']) ?>">
                </picture>
                <?php if (!empty($banner['link_url'])): ?></a><?php endif; ?>
                <div class="carousel-caption d-none d-md-block dashboard-banner-caption">
                  <h5><?= e($banner['title']) ?></h5>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#clientBannerCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#clientBannerCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0 small">No banner ads published yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
</div>
