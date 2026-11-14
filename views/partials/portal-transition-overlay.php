<?php
/** Same non-interactive iWORKS loading mark for every signed-in portal role. */
if (!empty($user)):
?>
<div id="iwAdminTransition" class="iw-admin-transition" aria-hidden="true">
  <div class="iw-admin-transition-lockup">
    <img src="<?= e(portal_absolute_url('assets/branding/iworks-compact.svg')) ?>?v=20261114-pulse2"
         class="iw-admin-transition-mark" width="132" height="68" alt="" decoding="async">
    <span class="iw-admin-transition-name">iWORKS</span>
  </div>
</div>
<?php endif; ?>
