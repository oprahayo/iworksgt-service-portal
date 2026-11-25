(() => {
  const dialog = document.getElementById('ac-welcome-guide');
  const help = document.getElementById('ac-guide-dashboard');
  if (!dialog || !help || typeof dialog.show !== 'function') return;
  const key = dialog.dataset.guideKey;
  let seen = false;
  try { seen = localStorage.getItem(key) === '1'; } catch (_) { /* browser storage unavailable */ }
  function markSeen() { seen = true; try { localStorage.setItem(key, '1'); } catch (_) {} }
  const summary = help.querySelector('summary');
  const launch = document.createElement('button');
  launch.type = 'button'; launch.className = 'ac-guide-replay'; launch.textContent = 'Open welcome guide';
  launch.addEventListener('click', () => { if (!dialog.open) dialog.show(); });
  help.querySelector('.ac-guide-body').appendChild(launch);
  dialog.querySelectorAll('[data-guide-close]').forEach(button => button.addEventListener('click', () => { markSeen(); dialog.close(); }));
  dialog.querySelectorAll('[data-guide-link]').forEach(link => link.addEventListener('click', markSeen));
  dialog.addEventListener('cancel', markSeen);
  document.addEventListener('pointerdown',event=>{if(dialog.open&&!dialog.contains(event.target)&&!launch.contains(event.target)){markSeen();dialog.close();}});
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && dialog.open && !document.querySelector('dialog[open]:not(#ac-welcome-guide)')) { markSeen(); dialog.close(); }
  });
  dialog.addEventListener('close', () => { markSeen(); if (summary) summary.focus({preventScroll:true}); });
  if (!seen && dialog.dataset.guideAuto === '1') dialog.show();
})();

(() => {
  const allowed = new Set(['#ac-new-certificate','#ac-certificate-history','#ac-explore-courses']);
  let timer;
  function pointToAction() {
    if (!allowed.has(location.hash)) return;
    const target=document.getElementById(location.hash.slice(1));
    if (!target) return;
    if (target.tagName==='DETAILS') target.open=true;
    document.querySelectorAll('.ac-guide-target').forEach(el=>el.classList.remove('ac-guide-target'));
    target.classList.add('ac-guide-target');
    const focusTarget=target.tagName==='DETAILS'?target.querySelector(':scope > summary'):target;
    if(focusTarget)focusTarget.focus({preventScroll:true});
    target.scrollIntoView({block:'nearest',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
    clearTimeout(timer);
    timer=setTimeout(()=>target.classList.remove('ac-guide-target'),1400);
  }
  window.addEventListener('hashchange',pointToAction);
  document.querySelectorAll('.ac-guide-links a[href^="#"]').forEach(a=>a.addEventListener('click',()=>{if(location.hash===a.hash)pointToAction();}));
  pointToAction();
})();
