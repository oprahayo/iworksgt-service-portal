/* Backend operational pages only. No client/public splash or network calls. */
(function () {
  'use strict';
  var overlay = document.getElementById('iwAdminTransition');
  if (!overlay) return;
  var root = document.documentElement;
  var leaveTimeout = null;
  var reduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  function hideOverlay() {
    root.classList.remove('iw-admin-transition-enter', 'iw-admin-transition-leave');
    if (leaveTimeout !== null) {
      window.clearTimeout(leaveTimeout);
      leaveTimeout = null;
    }
  }

  function revealPage() {
    window.setTimeout(hideOverlay, reduced ? 0 : 185);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', revealPage, { once: true });
  } else {
    revealPage();
  }

  // Browser back/forward cache must never reopen with a stuck overlay.
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) hideOverlay();
  });
  window.addEventListener('pagehide', hideOverlay);

  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var target = event.target;
    var anchor = target && target.closest ? target.closest('a[href]') : null;
    if (!anchor || anchor.hasAttribute('download') || anchor.hasAttribute('data-no-transition')) return;
    var targetAttr = (anchor.getAttribute('target') || '').toLowerCase();
    if (targetAttr && targetAttr !== '_self') return;
    var href = anchor.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#' || /^(?:javascript:|mailto:|tel:|data:)/i.test(href)) return;
    var destination;
    try { destination = new URL(anchor.href, window.location.href); } catch (error) { return; }
    if (!/^https?:$/.test(destination.protocol)) return;
    var ownHost = destination.hostname === window.location.hostname;
    // Include the intentional "View website" route out of the backend,
    // but keep the public landing page completely free of this overlay.
    var publicHost = destination.hostname === 'iworksgt.com' || destination.hostname === 'www.iworksgt.com';
    if (!ownHost && !publicHost) return;
    if (destination.href === window.location.href ||
      (destination.pathname === window.location.pathname &&
       destination.search === window.location.search &&
       destination.hash !== window.location.hash)) return;

    root.classList.remove('iw-admin-transition-enter');
    root.classList.add('iw-admin-transition-leave');
    // Never block navigation or leave the backend unusable if a click handler
    // cancels routing, or a request fails before page unload.
    if (leaveTimeout !== null) window.clearTimeout(leaveTimeout);
    leaveTimeout = window.setTimeout(hideOverlay, 1050);
  }, false);
})();
