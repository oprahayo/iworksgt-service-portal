(() => {
  const root = document.querySelector('[data-cert-wizard]');
  if (!root) return;
  const course = root.querySelector('[data-cert-course]');
  const name = root.querySelector('[data-cert-name]');
  const nameStep = root.querySelector('[data-cert-name-step]');
  const confirmStep = root.querySelector('[data-cert-confirm-step]');
  const output = root.querySelector('[data-cert-name-output]');
  const confirm = root.querySelector('[data-cert-confirm]');
  if (!course || !name || !nameStep || !confirmStep || !output || !confirm) return;
  function sync(reset) {
    const selected = Boolean(course.value);
    const filled = selected && name.value.trim().length >= 2;
    nameStep.hidden = !selected;
    name.disabled = !selected;
    confirmStep.hidden = !filled;
    confirm.disabled = !filled;
    output.textContent = name.value.trim();
    if (reset || !filled) confirm.checked = false;
  }
  course.addEventListener('change', () => sync(true));
  name.addEventListener('input', () => sync(true));
  root.closest('form').addEventListener('reset', () => setTimeout(() => sync(true), 0));
  // Reconcile browser-restored fields without retaining stale consent.
  window.addEventListener('pageshow', () => sync(true));
  sync(true);
})();
