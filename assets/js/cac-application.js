(function () {
  'use strict';
  const form = document.getElementById('cacForm');
  const configEl = document.getElementById('cacConfig');
  if (!form || !configEl) return;
  let cfg;
  try { cfg = JSON.parse(configEl.textContent || '{}'); } catch (_) { return; }
  const status = document.getElementById('cacSaveStatus');
  const idInput = document.getElementById('cacApplicationId');
  const people = document.getElementById('cacPeople');
  const extraHint = document.getElementById('cacPartnerHint');
  const saveBtn = document.getElementById('cacSaveBtn');
  const reviewBtn = document.getElementById('cacReviewBtn');
  const currency = document.getElementById('cacCurrency');
  let saving = null;
  let uploading = 0;
  const uploadedFields = new Set(cfg.existingFiles || []);

  function tell(message, error) {
    if (!status) return;
    status.textContent = message;
    status.style.color = error ? '#b3432d' : '#39775a';
  }
  function money(value, code) {
    return (code === 'USD' ? 'USD ' : 'NGN ') + Number(value).toLocaleString('en-NG', {minimumFractionDigits:2,maximumFractionDigits:2});
  }
  function countPeople() { return people.querySelectorAll('[data-cac-person]').length; }
  function updatePrice() {
    const num = countPeople();
    const kind = cfg.kind;
    let base = kind === 'company' ? Number(cfg.companyBase || 0) : Number(cfg.base || 26000);
    let extras = 0;
    const structure = document.getElementById('cacStructure');
    if (kind === 'business_name') {
      if (structure && structure.value === 'partnership') extras = Math.max(0, num-1) * Number(cfg.extra || 15000);
      if (extraHint) extraHint.textContent = structure && structure.value === 'partnership'
        ? (num < 2 ? 'Add at least one partner to register a partnership.' : (num-1)+' additional partner(s) included.')
        : 'Sole proprietor requires exactly one applicant.';
    } else {
      let directors = 0, shareholders = 0;
      people.querySelectorAll('[data-cac-role]').forEach(function (select) {
        if (select.value === 'director' || select.value === 'both') directors++;
        if (select.value === 'shareholder' || select.value === 'both') shareholders++;
      });
      extras = Math.max(0,directors-1)*Number(cfg.directorExtra || 0)
        + Math.max(0,shareholders-1)*Number(cfg.shareholderExtra || 0);
      if (extraHint) extraHint.textContent = directors+' director(s) · '+shareholders+' shareholder(s)';
    }
    const selected = currency.value;
    const ngnTotal = base + extras;
    const total = selected === 'USD' && Number(cfg.fx)>0 ? ngnTotal/Number(cfg.fx) : ngnTotal;
    document.getElementById('cacBaseLabel').textContent = money(base,'NGN');
    document.getElementById('cacExtraLabel').textContent = money(extras,'NGN');
    document.getElementById('cacTotalLabel').textContent = base > 0 ? money(total,selected) : 'Pricing pending';
  }
  function updateRemove() {
    const persons = Array.from(people.querySelectorAll('[data-cac-person]'));
    persons.forEach(function (person, i) {
      const btn = person.querySelector('[data-remove-person]');
      if (btn) btn.hidden = i === 0 || i !== persons.length - 1;
    });
    updatePrice();
  }
  function syncAddress() {
    const toggle = document.getElementById('cacSameAddress');
    if (!toggle || !toggle.checked) return;
    const first = people.querySelector('[data-cac-person]');
    if (!first) return;
    [['address','cacAddress'],['city','cacCity'],['lga','cacLga'],['state','cacState']]
      .forEach(function (pair) {
        const source = first.querySelector('[data-cac-person-field="'+pair[0]+'"]');
        const target = document.getElementById(pair[1]);
        if (source && target) target.value = source.value;
      });
  }
  async function saveDraft() {
    if (saving) return saving;
    saving = (async function () {
      syncAddress();
      const fd = new FormData(form);
      fd.set('form_action','save_draft');
      fd.set('ajax','1');
      fd.set('id',idInput.value || '0');
      tell('Saving your application...',false);
      const resp = await fetch(form.getAttribute('action') || cfg.draftUrl, {
        method:'POST',body:fd,credentials:'same-origin',headers:{'Accept':'application/json'}
      });
      let body = {};
      try { body = await resp.json(); } catch (_) {}
      if (!resp.ok || !body.success || !body.id) {
        throw new Error(body.message || 'Could not save. Please retry.');
      }
      // Server owns the list of attached documents. A changed identity or
      // removed partner invalidates the old files, never silently reuses them.
      if (Array.isArray(body.uploaded_keys)) {
        const current = new Set(body.uploaded_keys);
        Array.from(uploadedFields).forEach(function(key) {
          if (current.has(key)) return;
          uploadedFields.delete(key);
          const row = Array.from(document.querySelectorAll('[data-cac-file-field]'))
            .find(function(el){return el.dataset.cacFileField === key;});
          if (row) uploadedState(row,'Document needs re-upload after applicant details changed.',false);
        });
        current.forEach(function(key){ uploadedFields.add(key); });
      }
      idInput.value = String(body.id);
      cfg.id = Number(body.id);
      const locationUrl = 'index.php?page=cac&id='+encodeURIComponent(body.id);
      history.replaceState(null,'',locationUrl);
      form.setAttribute('action',locationUrl);
      tell('Draft saved successfully.',false);
      return body.id;
    })().finally(function () { saving=null; });
    return saving;
  }
  function uploadedState(row, text, success) {
    const target = row && row.querySelector('[data-upload-status]');
    if (!target) return;
    target.classList.toggle('is-uploaded',success);
    target.classList.toggle('is-error',!success);
    target.innerHTML = '';
    const icon = document.createElement('i');
    icon.className = 'bi ' + (success?'bi-check-circle-fill':'bi-exclamation-circle');
    target.appendChild(icon);
    target.appendChild(document.createTextNode(' '+text));
  }
  async function uploadDocument(key, file, row) {
    if (!file || !row) return;
    const progress = row.querySelector('[data-upload-progress]');
    const progressBar = progress.querySelector('span');
    uploading++;
    if (saveBtn) saveBtn.disabled=true;
    if (reviewBtn) reviewBtn.disabled=true;
    try {
      const id = await saveDraft();
      const fd = new FormData();
      fd.set('csrf_token',document.getElementById('cacCsrf').value);
      fd.set('form_action','upload_file');
      fd.set('id',String(id));
      fd.set('field_key',key);
      fd.set('document',file,file.name);
      progress.hidden=false;
      progressBar.style.width='2%';
      uploadedState(row,'Uploading and checking '+file.name+'…',false);
      const body = await new Promise(function (resolve,reject) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST','index.php?page=cac&id='+encodeURIComponent(id),true);
        xhr.setRequestHeader('Accept','application/json');
        xhr.upload.addEventListener('progress',function (evt) {
          if(evt.lengthComputable) progressBar.style.width=Math.round(evt.loaded/evt.total*90)+'%';
        });
        xhr.addEventListener('error',function(){reject(new Error('Network interrupted during upload. Try again.'));});
        xhr.addEventListener('load',function() {
          let result={};
          try{result=JSON.parse(xhr.responseText);}catch(_){}
          if(xhr.status>=200&&xhr.status<300&&result.success&&result.uploaded){
            resolve(result);
          }else reject(new Error(result.message||'Upload was not verified. Please try again.'));
        });
        xhr.send(fd);
      });
      progressBar.style.width='100%';
      uploadedFields.add(key);
      const kb=(Number(body.size||file.size)/1024).toFixed(1);
      uploadedState(row,'Successfully attached: '+(body.filename||file.name)+' ('+kb+' KB)',true);
      tell('Document securely uploaded and attached to your draft.',false);
    } catch(err) {
      uploadedFields.delete(key);
      uploadedState(row,String(err.message||'Upload failed. Please retry.'),false);
      tell('Document could not be attached. Please retry.',true);
    } finally {
      uploading=Math.max(0,uploading-1);
      if (progress) progress.hidden=true;
      if (saveBtn) saveBtn.disabled=uploading>0;
      if (reviewBtn) reviewBtn.disabled=uploading>0;
    }
  }

  people.addEventListener('change',function(event) {
    const f = event.target.closest('[data-cac-file-input]');
    if (f && f.files && f.files[0]) {
      const row=f.closest('[data-cac-file-field]');
      uploadDocument(f.dataset.cacFileKey, f.files[0], row);
    }
    updatePrice();
  });
  form.addEventListener('input',function(event){
    if (event.target.closest('[data-cac-person]') && document.getElementById('cacSameAddress').checked) syncAddress();
  });
  const structure=document.getElementById('cacStructure');
  if (structure) structure.addEventListener('change',updatePrice);
  currency.addEventListener('change',updatePrice);
  const same=document.getElementById('cacSameAddress');
  if (same) same.addEventListener('change',syncAddress);

  document.getElementById('cacAddPerson').addEventListener('click',function(){
    const n=countPeople(); if(n>=25){tell('Contact support if you need more than 25 applicants.',true);return;}
    const template=document.getElementById('cacPersonTemplate');
    const markup=template.innerHTML.replaceAll('__INDEX__',String(n));
    const wrap=document.createElement('div');
    wrap.innerHTML=markup;
    const added=wrap.querySelector('[data-cac-person]');
    if(!added)return;
    added.querySelectorAll('input:not([type=file]),textarea').forEach(function(x){x.value='';});
    added.querySelectorAll('select').forEach(function(x){x.selectedIndex=0;});
    added.querySelectorAll('[data-upload-status]').forEach(function(x){
      x.classList.remove('is-uploaded');
      x.textContent='Not uploaded yet';
    });
    const number=added.querySelector('[data-person-number]');
    if(number)number.textContent=String(n+1);
    people.appendChild(added);
    updateRemove();
    added.scrollIntoView({block:'nearest',behavior:'smooth'});
  });
  people.addEventListener('click',function(e){
    const remove=e.target.closest('[data-remove-person]');
    if(remove){
      const member=remove.closest('[data-cac-person]');
      if(member && member===people.lastElementChild && countPeople()>1){
        if(window.confirm('Remove this applicant? You will need to save the draft again.')){
          const index = Number(member.dataset.personIndex);
          ['id','photo','signature'].forEach(function(doc){ uploadedFields.delete('person_'+index+'_'+doc); });
          member.remove();updateRemove();tell('Applicant removed. Save the draft to keep this change.',false);
        }
      }
    }
    const open=e.target.closest('[data-sign-open]');
    if(open){
      const pad=open.closest('[data-cac-file-field]').querySelector('[data-sign-pad]');
      if(pad){pad.hidden=!pad.hidden; if(!pad.hidden)open.setAttribute('aria-expanded','true');else open.setAttribute('aria-expanded','false');}
    }
    const clear=e.target.closest('[data-sign-clear]');
    if(clear){
      const pad=clear.closest('[data-sign-pad]');
      const canvas=pad.querySelector('canvas');
      const ctx=canvas.getContext('2d');
      ctx.clearRect(0,0,canvas.width,canvas.height);
      canvas.dataset.ink='0';
    }
    const save=e.target.closest('[data-sign-save]');
    if(save){
      const pad=save.closest('[data-sign-pad]');
      const canvas=pad.querySelector('canvas');
      if(canvas.dataset.ink!=='1'){tell('Draw a signature before saving.',true);return;}
      const row=save.closest('[data-cac-file-field]');
      const key=row.dataset.cacFileField;
      canvas.toBlob(function(blob) {
        if(!blob){tell('Could not prepare signature image.',true);return;}
        const file=new File([blob],'drawn-signature.png',{type:'image/png'});
        uploadDocument(key,file,row).then(function(){
          if(uploadedFields.has(key))pad.hidden=true;
        });
      },'image/png');
    }
  });

  people.addEventListener('pointerdown',function(e){
    const canvas=e.target.closest('[data-sign-canvas]');
    if(!canvas)return;
    e.preventDefault();
    const ctx=canvas.getContext('2d');
    const bounds=canvas.getBoundingClientRect();
    const x=(e.clientX-bounds.left)*canvas.width/bounds.width;
    const y=(e.clientY-bounds.top)*canvas.height/bounds.height;
    ctx.strokeStyle='#1c2e3b';ctx.lineWidth=2.5;ctx.lineCap='round';ctx.lineJoin='round';
    ctx.beginPath();ctx.moveTo(x,y);
    canvas.dataset.drawing='1';canvas.dataset.ink='1';
    try{canvas.setPointerCapture(e.pointerId);}catch(_){}
  });
  people.addEventListener('pointermove',function(e){
    const canvas=e.target.closest('[data-sign-canvas]');
    if(!canvas || canvas.dataset.drawing!=='1')return;
    e.preventDefault();
    const b=canvas.getBoundingClientRect();
    const ctx=canvas.getContext('2d');
    ctx.lineTo((e.clientX-b.left)*canvas.width/b.width,(e.clientY-b.top)*canvas.height/b.height);
    ctx.stroke();
  });
  ['pointerup','pointercancel'].forEach(function(name){
    people.addEventListener(name,function(e){
      const canvas=e.target.closest('[data-sign-canvas]');
      if(canvas)canvas.dataset.drawing='0';
    });
  });

  saveBtn.addEventListener('click',function(){
    saveBtn.disabled=true;
    saveDraft().catch(function(error){tell(error.message||'Save failed.',true);})
      .finally(function(){saveBtn.disabled=false;});
  });
  reviewBtn.addEventListener('click',async function(){
    if(uploading>0){tell('Please wait for all files to finish uploading.',true);return;}
    syncAddress();
    if (!form.reportValidity()) { tell('Complete the highlighted required fields.',true);return; }
    const kind=cfg.kind;
    const n=countPeople();
    if(kind==='business_name'){
      const partnership=structure && structure.value==='partnership';
      if(partnership && n<2){tell('Add at least one additional partner or choose sole proprietor.',true);return;}
      if(!partnership && n!==1){tell('Choose partnership for more than one proprietor.',true);return;}
    }
    const keys=[];
    people.querySelectorAll('[data-cac-person]').forEach(function(_,i){
      keys.push('person_'+i+'_id','person_'+i+'_signature');
      if(kind==='business_name')keys.push('person_'+i+'_photo');
    });
    const missing=keys.find(key=>!uploadedFields.has(key));
    if(missing){tell('Please upload and wait for successful confirmation of all required documents (missing '+missing.replaceAll('_',' ')+').',true);return;}
    try{
      reviewBtn.disabled=true;
      await saveDraft();
      document.getElementById('cacAction').value='review_draft';
      form.submit();
    }catch(error){
      tell(error.message||'Cannot continue without saving.',true);
      reviewBtn.disabled=false;
    }
  });
  updateRemove();
  updatePrice();
})();
