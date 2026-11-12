<?php
$filing = is_array($cacDraft['data'] ?? null) ? $cacDraft['data'] : [];
$people = is_array($filing['people'] ?? null) && $filing['people'] !== [] ? $filing['people'] : [[]];
$documents = is_array($cacDraft['docs'] ?? null) ? $cacDraft['docs'] : [];
$editable = !$cacDraft || ($cacDraft['status'] ?? '') === 'draft';
$business = $filingKind === 'business';
$first = static fn(string $key): string => (string) ($filing[$key] ?? '');
?>
<div class="iw-client-heading iw-client-heading-row">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left"></i> All services</a>
    <span class="iw-client-eyebrow">CAC NIGERIA · <?= $business ? 'BUSINESS NAME' : 'COMPANY INCORPORATION' ?></span>
    <h1><?= $business ? 'Business name registration' : 'Company registration' ?></h1>
    <p>Enter your information, attach documents, review pricing, then proceed to payment.</p>
  </div>
  <span class="iw-client-status" data-state="<?= $editable ? 'draft' : 'payment' ?>"><?= $editable ? 'Draft application' : 'Awaiting payment' ?></span>
</div>
<div class="iw-cac-layout">
  <div class="iw-cac-main">
    <?php if (!$editable): ?>
      <section class="iw-tm-panel">
        <h2>Application submitted to iWorks</h2>
        <p>Your registration request has been saved for processing. Payment is required before iWorks begins filing.</p>
        <?php if (!empty($cacDraft['invoice_id'])): ?>
          <a href="<?= e('invoice.php?id=' . (int)$cacDraft['invoice_id']) ?>" class="iw-client-button">Review and pay invoice</a>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <form method="post" id="cacFilingForm" action="<?= e(url('cac_filing',$cacDraft?['id'=>(int)$cacDraft['id']]:['type'=>$filingKind])) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="save_draft">
        <section class="iw-tm-panel">
          <div class="iw-tm-panel-head"><span class="iw-tm-section-number">1</span>
            <div><h2>Registration details</h2><p>Prepare the details needed for filing with CAC Nigeria.</p></div>
          </div>
          <?php if ($business): ?>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="cacStructure">Business structure</label>
            <select id="cacStructure" name="filing[structure]">
              <option value="sole" <?= $first('structure')!=='partnership'?'selected':'' ?>>Sole proprietorship — 1 proprietor</option>
              <option value="partnership" <?= $first('structure')==='partnership'?'selected':'' ?>>Partnership — 2 or more proprietors</option>
            </select>
          </div>
          <p class="iw-cac-explain">Partnership pricing adds ₦<?= e(number_format(cac_money_setting('cac_business_partner_extra_ngn',15000),0)) ?> for each additional proprietor after the first. A sole proprietor cannot add partners.</p>
          <?php else: ?>
          <div class="iw-tm-fields">
            <div class="iw-tm-field"><label for="cacCompanyType">Company type</label>
              <select name="filing[company_type]" id="cacCompanyType">
                <option value="private_limited" <?= $first('company_type')!=='limited_by_guarantee'?'selected':'' ?>>Private company limited by shares</option>
                <option value="limited_by_guarantee" <?= $first('company_type')==='limited_by_guarantee'?'selected':'' ?>>Company limited by guarantee (special review)</option>
              </select>
            </div>
            <div class="iw-tm-field"><label for="cacShareCapital">Issued share capital (NGN)</label><input id="cacShareCapital" type="number" min="1" step="1" name="filing[share_capital]" value="<?= e($first('share_capital')) ?>" placeholder="e.g. 1000000"></div>
          </div>
          <p class="iw-cac-explain">Company registration may require additional ownership, PSC, regulatory and consent information. iWorks will review these before CAC submission.</p>
          <?php endif; ?>
          <div class="iw-tm-fields">
            <div class="iw-tm-field"><label for="cacName1">Proposed name (first choice) <span>*</span></label><input id="cacName1" name="filing[first_choice]" value="<?= e($first('first_choice')) ?>" required></div>
            <div class="iw-tm-field"><label for="cacName2">Alternative name (optional)</label><input id="cacName2" name="filing[second_choice]" value="<?= e($first('second_choice')) ?>"></div>
            <div class="iw-tm-field"><label for="cacActivity">Nature of business <span>*</span></label><input id="cacActivity" name="filing[business_activity]" value="<?= e($first('business_activity')) ?>" required placeholder="e.g. Retail and distribution"></div>
            <div class="iw-tm-field"><label for="cacStart">Commencement date <span>*</span></label><input id="cacStart" type="date" name="filing[commencement_date]" value="<?= e($first('commencement_date')) ?>" required></div>
            <div class="iw-tm-field"><label for="cacEmail">Business email <span>*</span></label><input id="cacEmail" type="email" name="filing[business_email]" value="<?= e($first('business_email') ?: (string)($client['email']??'')) ?>" required></div>
            <div class="iw-tm-field"><label for="cacPhone">Business phone <span>*</span></label><input id="cacPhone" type="tel" name="filing[business_phone]" value="<?= e($first('business_phone') ?: (string)($client['phone']??'')) ?>" required></div>
            <div class="iw-tm-field iw-tm-field-full">
              <label for="cacAddress">Business / registered office address <span>*</span></label>
              <textarea id="cacAddress" rows="2" name="filing[business_address]" required><?= e($first('business_address')) ?></textarea>
              <label class="iw-cac-checkbox"><input type="checkbox" id="cacSameAddress"> Use first proprietor/director's residential address as business address</label>
            </div>
            <div class="iw-tm-field"><label for="cacAv">CAC name reservation / AV code (if available)</label><input id="cacAv" name="filing[av_code]" value="<?= e($first('av_code')) ?>"></div>
            <div class="iw-tm-field"><label for="cacCurrency">Pay in</label>
              <select name="filing[currency]" id="cacCurrency">
                <option value="NGN" <?= $first('currency')!=='USD'?'selected':'' ?>>Nigerian naira (NGN)</option>
                <option value="USD" <?= $first('currency')==='USD'?'selected':'' ?>>US dollar (USD)</option>
              </select>
            </div>
          </div>
        </section>
        <section class="iw-tm-panel">
          <div class="iw-tm-panel-head"><span class="iw-tm-section-number">2</span>
            <div><h2><?= $business ? 'Proprietor details' : 'Directors and owners' ?></h2>
              <p>Use the exact legal names shown on government ID documents.</p></div>
          </div>
          <div id="cacPeople">
            <?php foreach ($people as $i=>$person): ?>
              <fieldset class="iw-cac-person" data-person="<?= (int)$i ?>">
                <legend><?= $business ? 'Proprietor ' : 'Director / shareholder ' ?><span data-person-label><?= (int)$i+1 ?></span></legend>
                <div class="iw-tm-fields">
                <?php foreach ([
                  'full_name'=>'Full legal name','dob'=>'Date of birth','nationality'=>'Nationality',
                  'occupation'=>'Occupation','email'=>'Email address','phone'=>'Phone number',
                  'residential_address'=>'Residential address','nin'=>'NIN (if applicable)',
                  'id_type'=>'Government ID type','id_number'=>'ID / passport number',
                ] as $key=>$label): ?>
                  <div class="iw-tm-field <?= $key==='residential_address'?'iw-tm-field-full':'' ?>">
                    <label><?= e($label) ?><?= in_array($key,['full_name','dob','nationality','residential_address','id_type','id_number'],true) ? ' *' : '' ?></label>
                    <?php if ($key==='id_type'): ?>
                    <select name="filing[people][<?= (int)$i ?>][id_type]">
                      <option value="">Select identification</option>
                      <?php foreach (['National ID','International passport','Driver licence','Voter card'] as $idType): ?>
                        <option value="<?= e($idType) ?>" <?= (string)($person[$key]??'')===$idType?'selected':'' ?>><?= e($idType) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                      <input type="<?= $key==='dob'?'date':($key==='email'?'email':($key==='phone'?'tel':'text')) ?>"
                        name="filing[people][<?= (int)$i ?>][<?= e($key) ?>]"
                        value="<?= e((string)($person[$key]??'')) ?>"
                        <?= in_array($key,['full_name','dob','nationality','residential_address','id_number'],true) ? 'required' : '' ?>>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                <?php if (!$business): ?>
                  <div class="iw-tm-field"><label>Role</label><select name="filing[people][<?= (int)$i ?>][role]">
                    <?php foreach (['Director and shareholder','Director','Shareholder'] as $role): ?>
                      <option value="<?= e($role) ?>" <?= ($person['role']??'')===$role?'selected':'' ?>><?= e($role) ?></option>
                    <?php endforeach; ?>
                  </select></div>
                  <div class="iw-tm-field"><label>Shareholding % (if applicable)</label>
                    <input type="number" min="0" max="100" step="0.01" name="filing[people][<?= (int)$i ?>][share_percent]" value="<?= e((string)($person['share_percent']??'')) ?>"></div>
                <?php endif; ?>
                </div>
                <button type="button" class="iw-client-delete iw-cac-remove-person"><i class="bi bi-x-lg"></i> Remove person</button>
              </fieldset>
            <?php endforeach; ?>
          </div>
          <button type="button" class="iw-client-button iw-client-button-soft" id="cacAddPerson"><i class="bi bi-person-plus"></i> Add another <?= $business?'partner':'director / owner' ?></button>
          <p class="iw-cac-explain">Every person must supply their own valid ID, passport photograph and signature. Do not sign on another person's behalf.</p>
        </section>
        <div class="iw-cac-save">
          <button type="submit" class="iw-client-button"><i class="bi bi-cloud-check"></i> Save details and continue</button>
          <span class="iw-client-small-muted">You can return to a saved draft later. No payment is taken yet.</span>
        </div>
      </form>
      <?php if ($cacDraft): ?>
      <section class="iw-tm-panel iw-cac-documents">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">3</span><div><h2>Identification and signatures</h2>
          <p>Attach an ID, a passport photograph and a signed image for each person. Documents save immediately.</p></div></div>
        <?php foreach ($people as $i=>$person): ?>
          <div class="iw-cac-doc-person">
            <h3><?= e((string)($person['full_name']??('Person '.($i+1)))) ?> <small>Person <?= $i+1 ?></small></h3>
            <div class="iw-cac-doc-grid">
              <?php foreach (['id'=>'Government ID','photo'=>'Passport photograph','signature'=>'Signature image'] as $type=>$label): ?>
                <?php $saved=$documents[$i][$type]??null; ?>
                <label class="iw-cac-doc-label">
                  <span><i class="bi <?= $type==='id'?'bi-person-vcard':($type==='photo'?'bi-person-square':'bi-pen') ?>"></i> <?= e($label) ?></span>
                  <input type="file" accept="<?= $type==='id'?'.jpg,.jpeg,.png,.pdf':'.jpg,.jpeg,.png' ?>"
                    data-cac-upload data-person="<?= $i ?>" data-type="<?= e($type) ?>" aria-label="<?= e($label) ?> for person <?= $i+1 ?>">
                  <small data-cac-file-status="<?= $i ?>-<?= e($type) ?>" class="<?= $saved?'iw-cac-success':'' ?>">
                    <?= $saved ? '✓ Saved: '.e((string)($saved['name']??'Document')) : 'Choose a file · max 5MB' ?>
                  </small>
                </label>
              <?php endforeach; ?>
            </div>
            <details class="iw-cac-draw" data-cac-draw data-person="<?= $i ?>">
              <summary><i class="bi bi-vector-pen"></i> Or draw your signature on screen</summary>
              <p>Sign using a mouse, finger or stylus. Only the person named above should provide their signature.</p>
              <canvas width="540" height="175" aria-label="Draw signature for person <?= $i+1 ?>"></canvas>
              <div class="iw-cac-sign-actions">
                <button type="button" class="iw-client-button iw-client-button-soft" data-cac-clear>Clear</button>
                <button type="button" class="iw-client-button" data-cac-save-signature>Use this signature</button>
              </div>
              <small data-cac-sign-status></small>
            </details>
          </div>
        <?php endforeach; ?>
      </section>
      <section class="iw-tm-panel iw-cac-review">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">4</span><div><h2>Review and checkout</h2>
          <p>Verify names and documents before generating an invoice. CAC filing starts only after payment verification.</p></div></div>
        <?php if ($cacQuote): ?>
          <div class="iw-cac-total"><span>iWorks service fee</span><strong><?= e(money((float)$cacQuote['total'],$cacQuote['currency'])) ?></strong>
            <small><?= $business ? e(ucfirst((string)($filing['structure']??'sole'))) . ' · ' . count($people) . ' proprietor(s)' : count($people) . ' director(s)/owner(s)' ?></small>
          </div>
        <?php endif; ?>
        <button type="button" class="iw-client-button iw-client-button-soft mb-3" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Check uploaded documents & refresh review</button>
        <?php if ($cacErrors !== []): ?>
          <div class="iw-cac-validation" role="status"><strong>Before checkout:</strong>
            <ul><?php foreach (array_slice($cacErrors,0,15) as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul>
          </div>
        <?php else: ?>
          <p class="iw-cac-success">✓ The required information and documents are attached.</p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('cac_filing',['id'=>(int)$cacDraft['id']])) ?>"
          onsubmit="return confirm('Submit this CAC registration request to iWorks and generate a payable invoice?');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="checkout">
          <label class="iw-cac-checkbox"><input type="checkbox" required>
            I confirm that the information is correct, each person consented to their documents and signature, and I understand iWorks will prepare the CAC submission after payment.</label>
          <button type="submit" class="iw-client-button" <?= $cacErrors!==[]?'disabled':'' ?>>Review completed · Continue to payment <i class="bi bi-arrow-right"></i></button>
        </form>
        <form method="post" action="<?= e(url('cac_filing',['id'=>(int)$cacDraft['id']])) ?>"
          onsubmit="return confirm('Delete this draft and its uploaded documents? This cannot be undone.');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="delete_draft">
          <button type="submit" class="iw-client-delete">Delete draft</button>
        </form>
      </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <aside class="iw-cac-sidebar">
    <div class="iw-tm-panel">
      <h2><i class="bi bi-shield-check"></i> CAC Nigeria filing</h2>
      <p>Have your proposed business name, details of each owner/director, a valid ID, passport photo and signature ready.</p>
      <div class="iw-cac-stage"><span>1</span> Application details</div>
      <div class="iw-cac-stage"><span>2</span> People and roles</div>
      <div class="iw-cac-stage"><span>3</span> Secure documents</div>
      <div class="iw-cac-stage"><span>4</span> Review and payment</div>
      <p class="iw-client-small-muted mt-3">You are preparing an order with iWorks, not filing directly on the official CAC website.</p>
    </div>
    <div class="iw-tm-panel">
      <h2>Need help?</h2>
      <p>Contact support if a business has unusual ownership, regulated activities or foreign participants.</p>
      <a href="<?= e(url('support')) ?>" class="iw-client-button iw-client-button-soft"><i class="bi bi-headset"></i> Contact support</a>
    </div>
  </aside>
</div>
<script>
(function(){
  var form=document.getElementById('cacFilingForm');
  if(form){
    var people=document.getElementById('cacPeople');
    var first=people.querySelector('[data-person]');
    var structure=document.getElementById('cacStructure');
    var add=document.getElementById('cacAddPerson');
    var business=<?= $business?'true':'false' ?>;
    function reindex(){
      var rows=Array.from(people.querySelectorAll('[data-person]'));
      rows.forEach(function(row,i){
        row.dataset.person=String(i);
        var number=row.querySelector('[data-person-label]');if(number)number.textContent=String(i+1);
        row.querySelectorAll('[name]').forEach(function(field){
          field.name=field.name.replace(/filing\[people\]\[\d+\]/,'filing[people]['+i+']');
        });
        var remove=row.querySelector('.iw-cac-remove-person');
        if(remove)remove.disabled=i===0&&rows.length===1;
      });
      if(structure&&structure.value==='sole'&&rows.length>1){
        add.disabled=true;
      }else add.disabled=false;
    }
    if(add)add.addEventListener('click',function(){
      var count=people.querySelectorAll('[data-person]').length;
      if(count>=20||business&&structure&&structure.value==='sole')return;
      var row=first.cloneNode(true);
      row.querySelectorAll('input,select').forEach(function(field){
        if(field.tagName==='SELECT')field.selectedIndex=0;else field.value='';
      });
      people.append(row);reindex();row.scrollIntoView({block:'center',behavior:'smooth'});
    });
    people.addEventListener('click',function(event){
      var button=event.target.closest('.iw-cac-remove-person');if(!button)return;
      var row=button.closest('[data-person]');if(!row||people.querySelectorAll('[data-person]').length===1)return;
      if(confirm('Remove this person? Save the form afterwards.')){row.remove();reindex();}
    });
    if(structure)structure.addEventListener('change',function(){
      var count=people.querySelectorAll('[data-person]').length;
      if(structure.value==='sole'&&count>1){
        alert('A sole proprietorship has one proprietor. Remove additional partners, or keep partnership selected.');
        structure.value='partnership';
      }
      reindex();
    });
    var same=document.getElementById('cacSameAddress');
    if(same)same.addEventListener('change',function(){
      if(!same.checked)return;
      var residence=people.querySelector('[name="filing[people][0][residential_address]"]');
      var businessField=document.getElementById('cacAddress');
      if(residence&&residence.value.trim())businessField.value=residence.value;
      else { same.checked=false; alert('Enter the first person’s residential address first.'); }
    });
    reindex();
  }

  var activeUploads=0;
  function uploadMessage(el,msg,ok){
    if(!el)return;
    el.textContent=msg;el.classList.toggle('iw-cac-success',ok);el.classList.toggle('iw-cac-failed',!ok);
  }
  async function postDocument(action,person,type,file,data){
    var fd=new FormData();
    fd.append('csrf_token',<?= json_encode(csrf_token()) ?>);
    fd.append('form_action',action);
    fd.append('person_index',String(person));
    if(action==='draw_signature')fd.append('signature_png',data);
    else{fd.append('doc_type',type);fd.append('document',file,file.name);}
    var response=await fetch(<?= json_encode(url('cac_filing',$cacDraft?['id'=>(int)$cacDraft['id']]:['type'=>$filingKind])) ?>,{method:'POST',credentials:'same-origin',body:fd});
    var result=await response.json();
    if(!response.ok||!result.success)throw new Error(result.message||'Upload was not saved.');
    return result;
  }
  document.querySelectorAll('[data-cac-upload]').forEach(function(input){
    input.addEventListener('change',async function(){
      var file=input.files&&input.files[0];if(!file)return;
      var person=Number(input.dataset.person),type=input.dataset.type;
      var status=document.querySelector('[data-cac-file-status="'+person+'-'+type+'"]');
      if(file.size>5*1024*1024){uploadMessage(status,'File exceeds the 5MB limit.',false);input.value='';return;}
      activeUploads++;uploadMessage(status,'Uploading securely…',false);input.disabled=true;
      try{
        var result=await postDocument('upload_document',person,type,file,'');
        uploadMessage(status,'✓ Saved successfully: '+result.file.name,true);
      }catch(e){uploadMessage(status,'Upload failed: '+e.message,false);}
      finally{input.disabled=false;input.value='';activeUploads--;}
    });
  });
  document.querySelectorAll('[data-cac-draw]').forEach(function(section){
    var canvas=section.querySelector('canvas'),ctx=canvas.getContext('2d'),drawing=false,ink=false;
    var state=section.querySelector('[data-cac-sign-status]');
    ctx.strokeStyle='#22303a';ctx.lineWidth=2.4;ctx.lineJoin='round';ctx.lineCap='round';
    function coords(ev){var r=canvas.getBoundingClientRect();return {x:(ev.clientX-r.left)*canvas.width/r.width,y:(ev.clientY-r.top)*canvas.height/r.height};}
    canvas.addEventListener('pointerdown',function(ev){drawing=true;ink=true;canvas.setPointerCapture(ev.pointerId);var p=coords(ev);ctx.beginPath();ctx.moveTo(p.x,p.y);});
    canvas.addEventListener('pointermove',function(ev){if(!drawing)return;var p=coords(ev);ctx.lineTo(p.x,p.y);ctx.stroke();});
    function end(){drawing=false;}canvas.addEventListener('pointerup',end);canvas.addEventListener('pointercancel',end);
    section.querySelector('[data-cac-clear]').addEventListener('click',function(){ctx.clearRect(0,0,canvas.width,canvas.height);ink=false;state.textContent='';});
    section.querySelector('[data-cac-save-signature]').addEventListener('click',async function(){
      if(!ink){state.textContent='Please sign before saving.';return;}
      var button=this;button.disabled=true;state.textContent='Saving signature…';
      activeUploads++;
      try{await postDocument('draw_signature',Number(section.dataset.person),'signature',null,canvas.toDataURL('image/png'));
        state.textContent='✓ Signature saved securely.';state.classList.add('iw-cac-success');
        var badge=document.querySelector('[data-cac-file-status="'+section.dataset.person+'-signature"]');
        uploadMessage(badge,'✓ Drawn signature saved successfully',true);
      }catch(e){state.textContent='Signature upload failed: '+e.message;}
      finally{button.disabled=false;activeUploads--;}
    });
  });
  window.addEventListener('beforeunload',function(ev){
    if(activeUploads>0){ev.preventDefault();ev.returnValue='';}
  });
})();
</script>
