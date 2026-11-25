(() => {
 const root=document.querySelector('.academy-workspace');if(!root)return;
 const dialog=document.createElement('dialog');dialog.className='ac-action-dialog';dialog.setAttribute('aria-labelledby','ac-action-title');
 dialog.innerHTML='<div class="ac-action-head"><h2 id="ac-action-title"></h2><button type="button" aria-label="Close action" data-action-close>×</button></div><div data-action-body></div>';
 const body=dialog.querySelector('[data-action-body]'),title=dialog.querySelector('h2');
 let active=null;
 function restore(){if(!active)return;active.record.appendChild(active.content);active.record.open=false;active.summary.focus({preventScroll:true});active=null;}
 if(typeof dialog.showModal==='function'){
  root.appendChild(dialog);
  dialog.querySelector('[data-action-close]').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('click',e=>{if(e.target!==dialog)return;const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();});
  dialog.addEventListener('close',restore);
  root.querySelectorAll('details[data-ac-popup]').forEach(record=>{
   const summary=record.querySelector(':scope > summary');if(!summary)return;
   const content=document.createElement('div');content.className='ac-action-content';
   while(summary.nextSibling)content.appendChild(summary.nextSibling);record.appendChild(content);
   summary.setAttribute('aria-haspopup','dialog');
   summary.addEventListener('click',e=>{
    e.preventDefault();if(dialog.open)return;restore();title.textContent=summary.textContent.trim();active={record,summary,content};body.appendChild(content);
    try{dialog.showModal();}catch(_){restore();record.open=true;}
   });
  });
 }
 root.querySelectorAll('[data-ac-author]').forEach(author=>{
  const select=author.querySelector('[data-ac-kind]'),form=author.closest('form'),exam=author.querySelector('[data-ac-exam]'),questions=author.querySelector('[data-ac-questions]');
  if(!select||!form||!exam||!questions)return;
  const instructions=form.elements.namedItem('instructions'),q=questions.querySelector('textarea'),minutes=exam.querySelector('input');
  const type=select.closest('label'),next=document.createElement('button');next.type='button';next.textContent='Continue';type.after(next);
  const fields=[...author.children].filter(el=>el!==type&&el!==next);
  const tail=[...form.children].filter(el=>el!==author&&el.type!=='hidden');
  let stage=false;
  function sync(){fields.forEach(el=>el.hidden=!stage);tail.forEach(el=>el.hidden=!stage);
   exam.hidden=!stage||select.value!=='exam';minutes.disabled=select.value!=='exam';
   questions.hidden=!stage||select.value==='assignment';q.disabled=select.value==='assignment';q.required=select.value!=='assignment';
   instructions.required=select.value==='assignment';next.hidden=stage;
  }
  next.addEventListener('click',()=>{stage=true;sync();form.elements.namedItem('title')?.focus();});
  select.addEventListener('change',()=>{stage=false;sync();});
  form.addEventListener('submit',e=>{if(!stage){e.preventDefault();next.focus();}});
  sync();
 });
 root.querySelectorAll('[data-ac-quiz]').forEach(quiz=>{
  const form=quiz.closest('form'),questions=[...quiz.querySelectorAll('fieldset')];if(!form||!questions.length)return;
  const submit=form.querySelector('button:not([type=button])');if(!submit)return;
  const progress=document.createElement('p');progress.className='ac-question-progress';progress.setAttribute('aria-live','polite');
  const nav=document.createElement('div');nav.className='ac-question-nav';
  const back=document.createElement('button'),next=document.createElement('button');
  back.type=next.type='button';back.textContent='Previous';next.textContent='Next question';nav.append(back,next);quiz.prepend(progress);quiz.append(nav);
  let current=0;
  function render(focus=false){questions.forEach((q,i)=>q.hidden=i!==current);progress.textContent='Question '+(current+1)+' of '+questions.length;back.disabled=current===0;next.hidden=current===questions.length-1;submit.hidden=current!==questions.length-1;if(focus)questions[current].querySelector('input')?.focus();}
  back.addEventListener('click',()=>{if(current>0){current--;render(true);}});
  next.addEventListener('click',()=>{if(!questions[current].querySelector('input:checked')){questions[current].querySelector('input').reportValidity();return;}if(current<questions.length-1){current++;render(true);}});
  form.addEventListener('invalid',e=>{const i=questions.findIndex(q=>q.contains(e.target));if(i>=0&&questions[current].querySelector('input:checked')){current=i;render();}},true);
  render();
 });
 root.querySelectorAll('[data-exam-seconds]').forEach(clock=>{
  const end=Date.now()+Number(clock.dataset.examSeconds)*1000;
  const form=clock.nextElementSibling;
  function tick(){const left=Math.max(0,Math.ceil((end-Date.now())/1000));clock.textContent=left?'Time remaining: '+Math.floor(left/60)+':'+String(left%60).padStart(2,'0'):'Exam time expired. Submission unavailable.';if(!left&&form?.tagName==='FORM'){form.querySelectorAll('button,input,textarea,select').forEach(el=>el.disabled=true);}}
  tick();const timer=setInterval(()=>{tick();if(Date.now()>=end)clearInterval(timer);},500);
 });
 const id=location.hash.slice(1);if(/^ac-assessment-[0-9]+$/.test(id)){const target=document.getElementById(id);if(target?.matches('details[data-ac-popup]'))target.querySelector(':scope > summary')?.click();}
})();