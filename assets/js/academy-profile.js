(() => {
 const dialog=document.getElementById('ac-profile-dialog');
 if(!dialog||typeof dialog.showModal!=='function')return;
 const form=dialog.querySelector('[data-profile-form]'),status=dialog.querySelector('[data-profile-status]');
 const name=form.elements.namedItem('name'),phone=form.elements.namedItem('phone'),save=form.querySelector('[type=submit]');
 let opener=null,busy=false,savedName=name.value,savedPhone=phone.value;
 document.addEventListener('click',event=>{
   const link=event.target.closest('a[href="/academy.php?action=profile"]');
   if(!link||event.defaultPrevented||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||event.button!==0)return;
   event.preventDefault();if(dialog.open)return;
   opener=link;name.value=savedName;phone.value=savedPhone;status.textContent='';
   dialog.showModal();
 });
 dialog.querySelector('[data-profile-close]').addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();});
 dialog.addEventListener('close',()=>{if(opener)opener.focus({preventScroll:true});opener=null;});
 form.addEventListener('submit',async event=>{
   event.preventDefault();if(busy||!form.reportValidity())return;
   busy=true;save.disabled=true;status.textContent='Saving…';
   try{
     const res=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'Accept':'application/json'},redirect:'error'});
     const result=await res.json();
     if(!res.ok||result.ok!==true){status.textContent=typeof result.message==='string'?result.message:'Unable to save profile. Sign in again if needed.';return;}
     savedName=result.name;savedPhone=result.phone;name.value=savedName;phone.value=savedPhone;
     document.querySelectorAll('[data-student-profile-name]').forEach(el=>{el.textContent=savedName;});
     const page=document.querySelector('.ac-profile-panel form');
     if(page){page.elements.namedItem('name').value=savedName;page.elements.namedItem('phone').value=savedPhone;}
     status.textContent='Profile saved.';
   }catch(_){status.textContent='Could not confirm the save. Check your connection and try again.';}
   finally{busy=false;save.disabled=false;}
 });
})();
