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
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = {filterProjects:filterProjects};
  }
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
