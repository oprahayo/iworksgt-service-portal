<?php
/**
 * Public order tracking with the existing POST handler and status response.
 * Do not expose private order documents here.
 */
$trackHero = [];
foreach (is_array($layout ?? null) ? $layout : [] as $block) {
    if (is_array($block) && normalize_page_block_type((string) ($block['type'] ?? '')) === 'hero') {
        $trackHero = is_array($block['data'] ?? null) ? $block['data'] : [];
        break;
    }
}
$heading = trim((string) ($trackHero['headline'] ?? ''));
if ($heading === '' || $heading === 'Track your order progress in seconds.') {
    $heading = 'Track your order';
}
$intro = trim((string) ($trackHero['subheadline'] ?? ''));
if ($intro === '') {
    $intro = 'Enter your job ID and the email address or phone number used for your order.';
}
?>
<main class="public-main iw-track" id="main-content">
  <?php if ($message = flash('success')): ?><div class="container public-container pt-3"><div class="alert alert-success"><?= e($message) ?></div></div><?php endif; ?>
  <?php if ($error = flash('error')): ?><div class="container public-container pt-3"><div class="alert alert-danger"><?= e($error) ?></div></div><?php endif; ?>
  <section class="iw-inner-intro">
    <div class="container public-container">
      <nav class="iw-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(public_url('home')) ?>">Home</a><i class="bi bi-chevron-right" aria-hidden="true"></i><span aria-current="page">Track order</span></nav>
      <h1><?= e($heading) ?></h1>
      <p><?= e($intro) ?></p>
    </div>
  </section>
  <section class="iw-track-content">
    <div class="container public-container">
      <div class="iw-track-panel">
        <form method="post" action="<?= e(public_url('track')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="iw-track-fields">
            <div><label for="iwTrackCode">Job ID</label><input id="iwTrackCode" name="job_code" placeholder="JOB-2026XXXX" required autocomplete="off"></div>
            <div><label for="iwTrackIdentifier">Email or phone</label><input id="iwTrackIdentifier" name="identifier" placeholder="Used for this order" required autocomplete="off"></div>
          </div>
          <button type="submit">Track status <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
        <?php if (!empty($publicTrackResult)): ?>
          <div class="iw-track-result" aria-live="polite">
            <h2>Order status</h2>
            <dl>
              <div><dt>Job ID</dt><dd><?= e((string) ($publicTrackResult['job_code'] ?? '')) ?></dd></div>
              <div><dt>Service</dt><dd><?= e((string) ($publicTrackResult['service_name'] ?? '')) ?></dd></div>
              <div><dt>Status</dt><dd><?= e((string) ucfirst((string) ($publicTrackResult['status'] ?? 'pending'))) ?></dd></div>
              <div><dt>Payment</dt><dd><?= e((string) ucfirst((string) ($publicTrackResult['payment_status'] ?? 'unpaid'))) ?></dd></div>
            </dl>
            <a href="<?= e(portal_url('login')) ?>">Sign in for full details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        <?php endif; ?>
        <p class="iw-track-footer">Need to view messages, documents or completed work? <a href="<?= e(portal_url('login')) ?>">Sign in to your portal</a>.</p>
      </div>
    </div>
  </section>
</main>