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

(() => {
  const dialog = document.getElementById('ac-cert-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const body = dialog.querySelector('[data-cert-dialog-body]');
  const title = document.getElementById('ac-cert-dialog-title');
  const meta = document.getElementById('ac-cert-dialog-meta');
  const close = dialog.querySelector('[data-cert-close]');
  if (!body || !title || !meta || !close) return;
  let active = null;
  function restore() {
    if (!active) return;
    active.record.appendChild(active.content);
    active.record.open = false;
    active.summary.focus({preventScroll:true});
    active = null;
  }
  close.addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', restore);
  document.querySelectorAll('[data-cert-record]').forEach(record => {
    const summary = record.querySelector(':scope > summary');
    const content = record.querySelector(':scope > [data-cert-details]');
    if (!summary || !content) return;
    summary.setAttribute('aria-haspopup','dialog');
    summary.addEventListener('click', event => {
      event.preventDefault();
      if (dialog.open) return;
      restore();
      title.textContent = summary.querySelector('strong').textContent;
      meta.textContent = Array.from(summary.querySelectorAll('small,.ac-request-state')).map(el=>el.textContent).join(' · ');
      active = {record,summary,content};
      body.appendChild(content);
      try { dialog.showModal(); } catch (_) { restore(); record.open = true; }
    });
  });
})();
