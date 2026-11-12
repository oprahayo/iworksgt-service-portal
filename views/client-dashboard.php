<?php
$customerName = trim((string) ($user['first_name'] ?? ''));
if ($customerName === '') { $customerName = explode(' ', trim((string) ($user['full_name'] ?? 'Customer')))[0]; }
$recentDrafts = array_slice(array_filter($trademarkApplications ?? [],
    static fn(array $app): bool => ($app['status'] ?? '') === 'draft'), 0, 3);
?>
<div class="iw-client-heading">
  <div>
    <span class="iw-client-eyebrow">YOUR WORKSPACE</span>
    <h1>What would you like to do?</h1>
    <p>Start an application, save your progress and pay when you're ready.</p>
  </div>
  <a class="iw-client-subtle-link" href="<?= e(url('applications')) ?>">My applications <i class="bi bi-arrow-up-right"></i></a>
</div>
<div class="iw-client-services">
  <a class="iw-client-service iw-client-service-feature" href="<?= e(url('trademark')) ?>">
    <span class="iw-client-service-icon"><i class="bi bi-patch-check"></i></span>
    <h2>Trademark registration</h2>
    <p>Protect your brand name or logo in Nigeria. Choose the classes you need.</p>
    <div class="iw-client-service-bottom">
      <span>From <strong>$<?= e(number_format(trademark_price_usd(), 2)) ?></strong> per class</span>
      <span class="iw-client-service-action">Start <i class="bi bi-arrow-right"></i></span>
    </div>
  </a>
  <a class="iw-client-service" href="<?= e(url('jobs', ['action'=>'new', 'service'=>'company-registration'])) ?>">
    <span class="iw-client-service-icon"><i class="bi bi-buildings"></i></span>
    <h2>Business registration</h2>
    <p>Form a business or company with CAC filing support.</p>
    <div class="iw-client-service-bottom"><span>Current ordering flow</span><span class="iw-client-service-action">Proceed <i class="bi bi-arrow-right"></i></span></div>
  </a>
  <a class="iw-client-service" href="<?= e(url('jobs', ['action'=>'new', 'service'=>'website-design-development'])) ?>">
    <span class="iw-client-service-icon"><i class="bi bi-window"></i></span>
    <h2>Web & digital services</h2>
    <p>Plan your website, branding or digital business support.</p>
    <div class="iw-client-service-bottom"><span>Custom requirements</span><span class="iw-client-service-action">Proceed <i class="bi bi-arrow-right"></i></span></div>
  </a>
  <a class="iw-client-service" href="<?= e(url('jobs', ['action'=>'new'])) ?>">
    <span class="iw-client-service-icon"><i class="bi bi-grid"></i></span>
    <h2>All other services</h2>
    <p>Explore our complete catalogue and choose another product.</p>
    <div class="iw-client-service-bottom"><span>Explore catalogue</span><span class="iw-client-service-action">Browse <i class="bi bi-arrow-right"></i></span></div>
  </a>
</div>
<section class="iw-client-recent">
  <div class="iw-client-section-top">
    <div><h2>Continue where you left off</h2><p>Your unpaid drafts are saved securely.</p></div>
    <a href="<?= e(url('applications')) ?>">View all applications</a>
  </div>
  <?php if ($recentDrafts === []): ?>
    <div class="iw-client-empty"><i class="bi bi-file-earmark-text"></i><div><strong>No drafts yet</strong><p>Start a trademark application and come back whenever you need.</p></div></div>
  <?php else: ?>
    <?php foreach ($recentDrafts as $draft): ?>
    <a class="iw-client-draft-line" href="<?= e(url('trademark', ['id'=>(int) $draft['id']])) ?>">
      <span><strong><?= e((string) (($draft['data']['mark_name'] ?? '') ?: 'Untitled trademark')) ?></strong>
      <small>Trademark · Updated <?= e((string) $draft['updated_at']) ?></small></span>
      <span class="iw-client-status">Draft</span><i class="bi bi-arrow-right"></i>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
