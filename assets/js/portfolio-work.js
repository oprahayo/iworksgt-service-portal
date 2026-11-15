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

  // Pure geometry: screen-centered target works for the first/last slide,
  // responsive rail padding, and partially visible neighbouring previews.
  function centeredShowcaseOffset(track, slide, maxScroll) {
    var frame = track.getBoundingClientRect();
    var card = slide.getBoundingClientRect();
    var delta = (card.left + card.width / 2) - (frame.left + frame.width / 2);
    return Math.min(maxScroll, Math.max(0, track.scrollLeft + delta));
  }

  function initialShowcaseIndex(count) {
    // Show neighbours on both sides immediately when there are >= 3 stories.
    return count > 2 ? 1 : 0;
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
    var activeIndex = 0;
    var update = function () {
      var container = track.getBoundingClientRect();
      var center = container.left + container.width / 2;
      var nearest = 0, distance = Infinity;
      slides.forEach(function (slide, i) {
        var rect = slide.getBoundingClientRect();
        var d = Math.abs(rect.left + rect.width / 2 - center);
        if (d < distance) { distance = d; nearest = i; }
      });
      activeIndex = nearest;
      slides.forEach(function (slide, i) {
        var focused = i === nearest;
        slide.classList.toggle('is-active', focused);
        if (focused) slide.setAttribute('aria-current', 'true');
        else slide.removeAttribute('aria-current');
      });
      if (status) status.textContent = (nearest + 1) + ' of ' + slides.length;
      if (previous) previous.disabled = nearest === 0;
      if (next) next.disabled = nearest === slides.length - 1;
    };
    var centerSlide = function (index, behavior) {
      var target = slides[Math.max(0, Math.min(slides.length - 1, index))];
      if (!target) return;
      track.scrollTo({
        left: centeredShowcaseOffset(track, target, maxScroll()),
        behavior: behavior
      });
      if (behavior === 'auto') update();
    };
    var scrollStep = function (direction) {
      centerSlide(activeIndex + direction, prefersReduce ? 'auto' : 'smooth');
    };
    if (previous) previous.addEventListener('click', function () { scrollStep(-1); });
    if (next) next.addEventListener('click', function () { scrollStep(1); });
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', function () { centerSlide(activeIndex, 'auto'); }, { passive: true });

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
    track.addEventListener('dragstart', function (event) {
      event.preventDefault();
    });
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
      var pointerId = drag.pointerId;
      drag = null;
      if (track.releasePointerCapture && track.hasPointerCapture &&
          track.hasPointerCapture(pointerId)) {
        track.releasePointerCapture(pointerId);
      }
      track.classList.remove('is-dragging');
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
    // Align story two by default when available. This keeps the left and
    // right previews visible, matching the approved reference composition.
    centerSlide(initialShowcaseIndex(slides.length), 'auto');
    track.classList.add('is-ready');
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
    module.exports = {
      filterProjects:filterProjects,
      centeredShowcaseOffset:centeredShowcaseOffset,
      initialShowcaseIndex:initialShowcaseIndex
    };
  }
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
