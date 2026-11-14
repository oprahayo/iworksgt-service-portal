/* iWORKS portfolio: accessible progressive filtering, no external requests. */
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
    if (summary) summary.textContent = count + (count === 1 ? ' project shown' : ' projects shown');
    var empty = root.querySelector('[data-iw-work-empty]');
    if (empty) empty.hidden = count !== 0;
    return count;
  }
  function init() {
    var root = document.querySelector('.iw-portfolio-work');
    if (!root) return;
    var group = root.querySelector('[data-iw-work-filters]');
    if (!group) return;
    group.addEventListener('click', function (event) {
      var button = event.target.closest('[data-iw-filter]');
      if (!button || !group.contains(button)) return;
      filterProjects(root, button.getAttribute('data-iw-filter') || 'all');
    });
    filterProjects(root, 'all');
  }
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = {filterProjects:filterProjects};
  }
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
