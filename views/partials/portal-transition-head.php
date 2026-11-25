<?php if (!empty($user)): ?>
<link rel="stylesheet" href="/assets/css/admin-transition.css?v=single1">
<script>
(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.documentElement.classList.add('iw-admin-transition-enter');
  window.setTimeout(function () {
    document.documentElement.classList.remove('iw-admin-transition-enter');
  }, 700);
})();
</script>
<?php endif; ?>
