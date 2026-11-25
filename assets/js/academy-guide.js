(() => {
  const dialog = document.getElementById('ac-welcome-guide');
  const help = document.getElementById('ac-guide-dashboard');
  if (!dialog || !help || typeof dialog.showModal !== 'function') return;
  const key = dialog.dataset.guideKey;
  let seen = false;
  try { seen = localStorage.getItem(key) === '1'; } catch (_) { /* browser storage unavailable */ }
  function markSeen() { seen = true; try { localStorage.setItem(key, '1'); } catch (_) {} }
  const summary = help.querySelector('summary');
  const launch = document.createElement('button');
  launch.type = 'button'; launch.className = 'ac-guide-replay'; launch.textContent = 'Open welcome guide';
  launch.addEventListener('click', () => { if (!dialog.open) dialog.showModal(); });
  help.querySelector('.ac-guide-body').appendChild(launch);
  dialog.querySelectorAll('[data-guide-close]').forEach(button => button.addEventListener('click', () => { markSeen(); dialog.close(); }));
  dialog.querySelectorAll('[data-guide-link]').forEach(link => link.addEventListener('click', markSeen));
  dialog.addEventListener('cancel', markSeen);
  dialog.addEventListener('close', () => { markSeen(); if (summary) summary.focus({preventScroll:true}); });
  if (!seen && dialog.dataset.guideAuto === '1') dialog.showModal();
})();
