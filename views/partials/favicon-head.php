<?php
/** Shared public/portal/client favicon markup, with a safe CMS override. */
$iwFaviconHref = favicon_path();
$iwOfficialFavicon = iworks_official_favicon_active()
    || $iwFaviconHref === iworks_official_favicon_url();
?>
<?php if ($iwOfficialFavicon): ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(iworks_official_favicon_url('iworks-favicon-32.png')) ?>">
  <link rel="icon" type="image/svg+xml" sizes="any" href="<?= e(iworks_official_favicon_url()) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(iworks_official_favicon_url('iworks-favicon-180.png')) ?>">
<?php elseif ($iwFaviconHref !== ''): ?>
  <link rel="icon" type="<?= e(favicon_mime_type($iwFaviconHref)) ?>" href="<?= e($iwFaviconHref) ?>" sizes="any">
  <?php if (favicon_mime_type($iwFaviconHref) !== 'image/svg+xml'): ?>
    <link rel="apple-touch-icon" href="<?= e($iwFaviconHref) ?>">
  <?php endif; ?>
<?php endif; ?>
