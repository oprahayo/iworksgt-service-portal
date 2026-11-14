<?php
/**
 * Authenticated portal loading transition shared by staff and clients.
 * Called from the staff and customer heads; never from public or auth shells.
 */
if (!empty($user)):
?>
<link rel="stylesheet" href="assets/css/admin-transition.css?v=20261114-pulse2">
<script>
  // Show a short iWORKS pulse before the portal's first paint.
  // Always release the overlay even when the deferred script cannot load.
  (function () {
    document.documentElement.classList.add('iw-admin-transition-enter');
    window.setTimeout(function () {
      document.documentElement.classList.remove('iw-admin-transition-enter');
    }, 900);
  })();
</script>
<?php endif; ?>
