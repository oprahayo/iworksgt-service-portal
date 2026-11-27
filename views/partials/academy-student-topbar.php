<?php
if(($user['role']??'')!=='student')return;
?>
<header class="portal-topbar ac-student-topbar">
  <div class="ac-topbar-leading">
    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarOpenBtn" aria-label="Open menu"><i class="bi bi-list" aria-hidden="true"></i></button>
    <button class="btn btn-sm btn-outline-secondary d-none d-lg-inline-flex" type="button" id="sidebarCollapseBtn" aria-label="Toggle sidebar"><i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i></button>
    <div class="topbar-title"><?= e($pageHeading) ?></div>
  </div>
  <div class="ac-topbar-actions">
    <a class="btn btn-sm btn-outline-secondary position-relative" id="topbarNotificationBtn" href="<?= e(url('notifications')) ?>" title="Notifications" aria-label="Notifications"><i class="bi bi-bell" aria-hidden="true"></i><span id="topbarUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger <?= $unreadCount > 0 ? '' : 'd-none' ?>" data-unread="<?= (int)$unreadCount ?>" data-latest="<?= (int)$latestNotificationId ?>"><?= (int)$unreadCount ?></span></a>
    <a class="btn btn-sm btn-outline-secondary ac-profile-trigger" href="/academy.php?action=profile" aria-label="My profile" title="My profile"><i class="bi bi-person-circle" aria-hidden="true"></i><span data-student-profile-name><?= e((string)$user['full_name']) ?></span></a>
  </div>
</header>
<?php require __DIR__.'/academy-profile-dialog.php'; ?>
