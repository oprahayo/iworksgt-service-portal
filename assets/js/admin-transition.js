/* One arrival animation only; never animate the outgoing page. */
(function () {
  'use strict';
  if (!document.getElementById('iwAdminTransition')) return;
  var root = document.documentElement;
  var reduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  function hide() {
    root.classList.remove('iw-admin-transition-enter', 'iw-admin-transition-leave');
  }
  function reveal() {
    window.setTimeout(hide, reduced ? 0 : 160);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', reveal, { once: true });
  } else { reveal(); }
  window.addEventListener('pagehide', hide);
  window.addEventListener('pageshow', function (event) { if (event.persisted) hide(); });
})();
