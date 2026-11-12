/* iWorks public interactions — no framework or animation library required. */
(function () {
  'use strict';
  function init() {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // The Services page owns its list/grid controls, so it works independently.
    document.querySelectorAll('[data-iw-reviews]').forEach(function (carousel) {
      var viewport = carousel.querySelector('[data-iw-review-viewport]');
      var prev = carousel.querySelector('[data-iw-review-prev]');
      var next = carousel.querySelector('[data-iw-review-next]');
      if (!viewport || !prev || !next) return;
      function move(direction) {
        var card = viewport.querySelector('.iw-review-slide');
        if (!card) return;
        var width = card.getBoundingClientRect().width;
        var gap = parseFloat(window.getComputedStyle(viewport).columnGap || 16) || 16;
        var maxLeft = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
        var target = viewport.scrollLeft + direction * (width + gap);
        if (target > maxLeft + 8) target = 0;
        if (target < -8) target = maxLeft;
        viewport.scrollTo({ left: target, behavior: reduceMotion ? 'instant' : 'smooth' });
      }
      prev.addEventListener('click', function () { move(-1); });
      next.addEventListener('click', function () { move(1); });
    });

    // Authentication renders in the same document; no iframe message bridge.
    var help = document.getElementById('iwPublicHelpDialog');
    var helpForm = help && help.querySelector('form');
    var feedback = document.getElementById('iwHelpFeedback');
    if (help && helpForm && feedback) {
      document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-iw-help-open]');
        if (!trigger) return;
        helpForm.hidden = false;
        feedback.hidden = true;
        feedback.textContent = '';
        var name = (trigger.getAttribute('data-iw-service') || '').trim();
        var message = helpForm.querySelector('textarea[name="message"]');
        if (message && name && !message.value.trim()) {
          message.value = 'I would like to ask about ' + name + '.\n';
        }
      });
      helpForm.addEventListener('submit', async function (event) {
        if (!window.fetch || !window.FormData) return;
        event.preventDefault();
        if (!helpForm.reportValidity()) return;
        var button = helpForm.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        feedback.hidden = true;
        feedback.className = 'iw-help-feedback';
        try {
          var response = await window.fetch(helpForm.action, {
            method:'POST',
            credentials:'same-origin',
            headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
            body:new window.FormData(helpForm)
          });
          var payload = await response.json();
          if (!response.ok || !payload || !payload.ok) throw new Error(payload && payload.message || 'Unable to send the enquiry.');
          feedback.textContent = payload.message || 'Your enquiry was sent successfully.';
          feedback.classList.add('is-success');
          feedback.hidden = false;
          helpForm.hidden = true;
          helpForm.reset();
        } catch (error) {
          feedback.textContent = error.message || 'Unable to send your enquiry. Please try again.';
          feedback.classList.add('is-error');
          feedback.hidden = false;
        } finally {
          if (button) button.disabled = false;
        }
      });
      help.addEventListener('close', function () {
        feedback.hidden = true;
        feedback.textContent = '';
        helpForm.hidden = false;
      });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();