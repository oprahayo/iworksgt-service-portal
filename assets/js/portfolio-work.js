/* Case studies: progressive category filtering and restrained scroll reveals.
 * Retains content if JavaScript or motion features are disabled. */
(function () {
  'use strict';

  function filterProjects(root, filter) {
    var projects = Array.prototype.slice.call(root.querySelectorAll('[data-iw-work-project]'));
    var buttons = root.querySelectorAll('[data-iw-filter]');
    var count = 0;
    projects.forEach(function (project) {
      var tokens = (project.getAttribute('data-iw-work-categories') || '').split(/\s+/).filter(Boolean);
      var visible = filter === 'all' || tokens.indexOf(filter) !== -1;
      project.hidden = !visible;
      if (visible) count++;
    });
    buttons.forEach(function (button) {
      var active = button.getAttribute('data-iw-filter') === filter;
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      button.classList.toggle('is-active', active);
    });
    var summary = root.querySelector('[data-iw-work-count]');
    if (summary) summary.textContent = count + (count === 1 ? ' case study' : ' case studies');
    var empty = root.querySelector('[data-iw-work-empty]');
    if (empty) empty.hidden = count !== 0;
    return count;
  }

  function initReveal() {
    var sections = document.querySelectorAll('.iw-portfolio-work, .iw-portfolio-detail, .iw-home-case-stories');
    if (!sections.length) return;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!('IntersectionObserver' in window) || reduced) return;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {threshold:0.08, rootMargin:'0px 0px -20px 0px'});
    sections.forEach(function (section) {
      var targets = section.querySelectorAll('[data-iw-reveal]');
      if (!targets.length) return;
      section.classList.add('iw-cases-motion-ready');
      targets.forEach(function (target) {observer.observe(target);});
    });
  }

  function initShowcase() {
    var showcase = document.querySelector('[data-iw-showcase]');
    if (!showcase) return;
    var track = showcase.querySelector('[data-iw-showcase-track]');
    var previous = showcase.querySelector('[data-iw-showcase-prev]');
    var next = showcase.querySelector('[data-iw-showcase-next]');
    var status = showcase.querySelector('[data-iw-showcase-status]');
    if (!track) return;
    var slides = Array.prototype.slice.call(track.querySelectorAll('[data-iw-showcase-slide]'));
    if (!slides.length) return;
    var prefersReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var maxScroll = function () {
      return Math.max(0, track.scrollWidth - track.clientWidth);
    };
    var stepSize = function () {
      var first = slides[0];
      if (!first) return track.clientWidth * 0.85;
      var second = slides[1];
      if (second) return Math.max(100, second.offsetLeft - first.offsetLeft);
      return Math.max(100, first.getBoundingClientRect().width);
    };
    var scrollStep = function (direction) {
      var end = Math.min(maxScroll(), Math.max(0, track.scrollLeft + direction * stepSize()));
      track.scrollTo({ left: end, behavior: prefersReduce ? 'auto' : 'smooth' });
    };
    var update = function () {
      var container = track.getBoundingClientRect();
      var center = container.left + container.width / 2;
      var nearest = 0, distance = Infinity;
      slides.forEach(function (slide, i) {
        var rect = slide.getBoundingClientRect();
        var d = Math.abs(rect.left + rect.width / 2 - center);
        if (d < distance) { distance = d; nearest = i; }
      });
      if (status) status.textContent = (nearest + 1) + ' of ' + slides.length;
      if (previous) previous.disabled = track.scrollLeft <= 3;
      if (next) next.disabled = track.scrollLeft >= maxScroll() - 3;
    };
    if (previous) previous.addEventListener('click', function () { scrollStep(-1); });
    if (next) next.addEventListener('click', function () { scrollStep(1); });
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });

    // Native trackpad horizontal scroll works normally. A vertical wheel is
    // converted only while the pointer is over a rail that can still move,
    // so the overall page is never trapped at the left/right end.
    track.addEventListener('wheel', function (event) {
      if (event.ctrlKey || event.shiftKey || event.deltaX !== 0 || Math.abs(event.deltaY) < 2) return;
      var delta = event.deltaY;
      var current = track.scrollLeft;
      var limit = maxScroll();
      if ((delta < 0 && current <= 1) || (delta > 0 && current >= limit - 1) || limit < 2) return;
      event.preventDefault();
      track.scrollLeft = Math.min(limit, Math.max(0, current + delta));
    }, { passive: false });

    // Desktop mouse-drag. Touch devices keep native browser swipe behavior.
    var drag = null;
    var blockClick = false;
    track.addEventListener('pointerdown', function (event) {
      if (event.pointerType !== 'mouse' || event.button !== 0) return;
      drag = {
        pointerId: event.pointerId,
        x: event.clientX,
        left: track.scrollLeft,
        moved: false
      };
    });
    track.addEventListener('pointermove', function (event) {
      if (!drag || event.pointerId !== drag.pointerId) return;
      var dx = event.clientX - drag.x;
      if (!drag.moved && Math.abs(dx) < 7) return;
      if (!drag.moved) {
        drag.moved = true;
        track.classList.add('is-dragging');
        if (track.setPointerCapture) track.setPointerCapture(event.pointerId);
      }
      track.scrollLeft = drag.left - dx;
      event.preventDefault();
    });
    var endDrag = function () {
      if (!drag) return;
      if (drag.moved) {
        blockClick = true;
        window.setTimeout(function () { blockClick = false; }, 160);
      }
      if (track.releasePointerCapture && track.hasPointerCapture &&
          track.hasPointerCapture(drag.pointerId)) {
        track.releasePointerCapture(drag.pointerId);
      }
      track.classList.remove('is-dragging');
      drag = null;
      update();
    };
    track.addEventListener('pointerup', endDrag);
    track.addEventListener('pointercancel', endDrag);
    track.addEventListener('lostpointercapture', endDrag);
    track.addEventListener('click', function (event) {
      if (!blockClick) return;
      if (event.target.closest('a')) {
        event.preventDefault();
        event.stopPropagation();
        blockClick = false;
      }
    }, true);
    track.addEventListener('keydown', function (event) {
      if (event.target !== track) return;
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault();
        scrollStep(event.key === 'ArrowRight' ? 1 : -1);
      }
    });
    update();
  }

  function init() {
    var root = document.querySelector('.iw-portfolio-work');
    if (root) {
      var group = root.querySelector('[data-iw-work-filters]');
      if (group) {
        group.addEventListener('click', function (event) {
          var button = event.target.closest('[data-iw-filter]');
          if (!button || !group.contains(button)) return;
          filterProjects(root, button.getAttribute('data-iw-filter') || 'all');
        });
        filterProjects(root, 'all');
      }
    }
    initReveal();
    initShowcase();
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = {filterProjects:filterProjects};
  }
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
