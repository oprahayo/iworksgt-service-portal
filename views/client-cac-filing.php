<?php
$filing = is_array($cacDraft['data'] ?? null) ? $cacDraft['data'] : [];
$people = is_array($filing['people'] ?? null) && $filing['people'] !== [] ? $filing['people'] : [[]];
$documents = is_array($cacDraft['docs'] ?? null) ? $cacDraft['docs'] : [];
$editable = !$cacDraft || ($cacDraft['status'] ?? '') === 'draft';
$business = $filingKind === 'business';
$virtualAddressAvailable = cac_virtual_address_eligible();
$virtualOfficePrice = cac_money_setting('cac_virtual_address_ngn',0);
$privacyPerDirector = cac_money_setting('cac_privacy_advisory_per_director_ngn',50000);
$nameScreen = cac_name_review((string)($filing['first_choice'] ?? ''),$filingKind);
$first = static fn(string $key): string => (string) ($filing[$key] ?? '');
?>
<div class="iw-client-heading iw-client-heading-row">
  <div>
    <a class="iw-client-back" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left"></i> All services</a>
    <span class="iw-client-eyebrow">CAC NIGERIA · <?= $business ? 'BUSINESS NAME' : 'COMPANY INCORPORATION' ?></span>
    <h1><?= $business ? 'Business name registration' : 'Company registration' ?></h1>
    <p>Answer a few questions at a time. Save your draft, add documents and review the final quote before paying.</p>
    <a class="iw-client-subtle-link" href="<?= e(url('cac_filing')) ?>">Change registration pathway</a>
  </div>
  <span class="iw-client-status" data-state="<?= $editable ? 'draft' : 'payment' ?>"><?= $editable ? 'Draft application' : 'Awaiting payment' ?></span>
</div>
<nav class="iw-cac-journey-progress" id="cacJourneyProgress" aria-label="Registration progress" hidden>
  <button type="button" data-cac-jump="1" aria-current="step"><span>1</span><strong>Business</strong></button>
  <button type="button" data-cac-jump="2"><span>2</span><strong>Contact</strong></button>
  <button type="button" data-cac-jump="3"><span>3</span><strong>Owners</strong></button>
  <button type="button" data-cac-jump="4" <?= !$cacDraft?'disabled':'' ?>><span>4</span><strong>Documents</strong></button>
  <button type="button" data-cac-jump="5" <?= !$cacDraft?'disabled':'' ?>><span>5</span><strong>Review</strong></button>
</nav>
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
        <section class="iw-tm-panel iw-cac-journey-step" data-cac-stage="1">
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
              <input type="hidden" name="filing[company_type]" value="private_limited">
              <p class="iw-cac-explain">This guided application is for private companies limited by shares. Limited by Guarantee requires specialist review. <a href="<?= e(url('support')) ?>">Request help</a>.</p>
            </div>
            <div class="iw-tm-field"><label for="cacShareCapital">Issued share capital (NGN)</label><input id="cacShareCapital" type="number" min="1" step="1" name="filing[share_capital]" value="<?= e($first('share_capital')) ?>" placeholder="e.g. 1000000"></div>
          </div>
          <div class="iw-tm-field iw-tm-field-full">
            <label for="cacOwnership">Ownership arrangement</label>
            <select id="cacOwnership" name="filing[company_ownership]">
              <option value="single" <?= $first('company_ownership')!=='multiple'?'selected':'' ?>>One owner (one shareholder, at least one director)</option>
              <option value="multiple" <?= $first('company_ownership')==='multiple'?'selected':'' ?>>Multiple owners / shareholders and directors</option>
            </select>
          </div>
          <p class="iw-cac-explain">Nigerian private limited-by-shares company: record every director, shareholder and their shareholding. Persons with significant control (PSC) disclosures may be required. It is different from a US LLC. Other company types need specialist review.</p>
          <?php endif; ?>
          <div class="iw-tm-fields">
            <div class="iw-tm-field iw-tm-field-full">
              <label for="cacName1">Proposed name (first choice) <span>*</span></label>
              <input id="cacName1" name="filing[first_choice]" value="<?= e($first('first_choice')) ?>" required minlength="4" maxlength="190" autocomplete="organization" aria-describedby="cacNameHelp cacNameFeedback">
              <p class="iw-cac-explain" id="cacNameHelp">Use a distinctive name that reflects the activity. <?= $business ? 'Do not add Ltd or Limited to a Business Name.' : 'Use a Nigerian Limited/Ltd ending, not LLC.' ?> This is a screening aid, not an official name reservation.</p>
              <div id="cacNameFeedback" class="iw-cac-name-feedback" aria-live="polite"></div>
              <div class="iw-cac-name-suggestions" id="cacNameSuggestions" aria-label="Suggested name alternatives"></div>
              <a href="https://icrp.cac.gov.ng/public-search/" target="_blank" rel="noopener noreferrer" class="iw-cac-official-link"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Check existing names on the official CAC register</a>
            </div>
            <div class="iw-tm-field"><label for="cacName2">Alternative name (optional)</label><input id="cacName2" name="filing[second_choice]" value="<?= e($first('second_choice')) ?>"></div>
            <div class="iw-tm-field">
              <label for="cacNature">Nature of business / profession <span>*</span></label>
              <select id="cacNature" name="filing[nature_category]" required>
                <option value="">Choose your main industry</option>
                <?php foreach (cac_registration_natures() as $nature): ?>
                  <option value="<?= e($nature) ?>" <?= $first('nature_category')===$nature?'selected':'' ?>><?= e($nature) ?></option>
                <?php endforeach; ?>
                <?php if($first('nature_category')!=='' && !in_array($first('nature_category'),cac_registration_natures(),true)): ?>
                  <option value="<?= e($first('nature_category')) ?>" selected><?= e($first('nature_category')) ?> (previously saved)</option>
                <?php endif; ?>
              </select>
              <small class="iw-cac-explain">Indicative categories; iWorks matches the final specific activity to the current CAC iCRP list before filing.</small>
            </div>
            <div class="iw-tm-field">
              <label for="cacActivity">Specific nature of business <span>*</span></label>
              <input id="cacActivity" name="filing[business_activity]" value="<?= e($first('business_activity')) ?>" required maxlength="240" placeholder="e.g. Online retail of household goods">
            </div>
          </div>
        </section>
        <div class="iw-cac-journey-actions" data-cac-stage="1">
          <button type="button" class="iw-client-button" data-cac-next="2">Continue to contact details <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>
        <section class="iw-tm-panel iw-cac-journey-step" data-cac-stage="2">
          <div class="iw-tm-panel-head"><span class="iw-tm-section-number">2</span>
            <div><h2>Contact and registered address</h2><p>How should CAC and iWorks contact your business?</p></div>
          </div>
          <div class="iw-tm-fields">
            <div class="iw-tm-field"><label for="cacStart">Commencement date <span>*</span></label><input id="cacStart" type="date" name="filing[commencement_date]" value="<?= e($first('commencement_date')) ?>" required></div>
            <div class="iw-tm-field"><label for="cacEmail">Business email <span>*</span></label><input id="cacEmail" type="email" name="filing[business_email]" value="<?= e($first('business_email') ?: (string)($client['email']??'')) ?>" required></div>
            <div class="iw-tm-field"><label for="cacPhone">Business phone <span>*</span></label><input id="cacPhone" type="tel" name="filing[business_phone]" value="<?= e($first('business_phone') ?: (string)($client['phone']??'')) ?>" required></div>
            <div class="iw-tm-field iw-tm-field-full">
              <label for="cacAddressMode">Business / registered office address <span>*</span></label>
              <select id="cacAddressMode" name="filing[address_mode]">
                <option value="own" <?= $first('address_mode')!=='virtual'?'selected':'' ?>>Use my own traceable Nigerian office address</option>
                <?php if($virtualAddressAvailable): ?>
                  <option value="virtual" <?= $first('address_mode')==='virtual'?'selected':'' ?>>Use iWorks managed registered office (+₦<?= e(number_format($virtualOfficePrice,0)) ?>)</option>
                <?php endif; ?>
              </select>
              <textarea id="cacAddress" rows="2" name="filing[business_address]" required <?= $first('address_mode')==='virtual'?'readonly':'' ?>><?= e($first('business_address')) ?></textarea>
              <label class="iw-cac-checkbox" id="cacSameAddressWrap" <?= $first('address_mode')==='virtual'?'hidden':'' ?>><input type="checkbox" id="cacSameAddress"> Use first proprietor/director's residential address as business address</label>
              <small class="iw-cac-explain" id="cacAddressNote">
                <?php if ($virtualAddressAvailable): ?>
                  Managed-office service is only offered where a traceable eligible location and the necessary permission are available. It does not hide mandatory CAC public records.
                <?php else: ?>
                  Managed office not currently offered. Provide an actual, traceable address where official correspondence can be received.
                <?php endif; ?>
              </small>
            </div>
            <?php if (!$business): ?>
            <div class="iw-tm-field iw-tm-field-full iw-cac-advisory">
              <label class="iw-cac-checkbox">
                <input type="checkbox" name="filing[privacy_advisory]" value="1" <?= !empty($filing['privacy_advisory'])?'checked':'' ?>>
                Add optional director privacy and registered-address advisory (+₦<?= e(number_format($privacyPerDirector,0)) ?> per director)
              </label>
              <p class="iw-cac-explain">Advisory only: personal data minimisation and lawful address arrangements. This does <strong>not</strong> hide names or beneficial ownership information CAC must make public. No secrecy or registration outcome is guaranteed.</p>
            </div>
            <?php endif; ?>
            <div class="iw-tm-field"><label for="cacAv">CAC name reservation / AV code (if available)</label><input id="cacAv" name="filing[av_code]" value="<?= e($first('av_code')) ?>"></div>
            <div class="iw-tm-field"><label for="cacCurrency">Pay in</label>
              <select name="filing[currency]" id="cacCurrency">
                <option value="NGN" <?= $first('currency')!=='USD'?'selected':'' ?>>Nigerian naira (NGN)</option>
                <option value="USD" <?= $first('currency')==='USD'?'selected':'' ?>>US dollar (USD)</option>
              </select>
            </div>
          </div>
        </section>
        <div class="iw-cac-journey-actions" data-cac-stage="2">
          <button type="button" class="iw-client-button iw-client-button-soft" data-cac-back="1"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</button>
          <button type="button" class="iw-client-button" data-cac-next="3">Continue to owners <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>
        <section class="iw-tm-panel iw-cac-journey-step" data-cac-stage="3">
          <div class="iw-tm-panel-head"><span class="iw-tm-section-number">3</span>
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
                  'residential_address'=>'Residential address',
                  'id_type'=>'Government ID type','id_number'=>'ID / passport number',
                ] as $key=>$label): ?>
                  <div class="iw-tm-field <?= $key==='residential_address'?'iw-tm-field-full':'' ?>">
                    <label><?= e($label) ?><?= in_array($key,['full_name','dob','nationality','residential_address','id_type','id_number'],true) ? ' *' : '' ?></label>
                    <?php if ($key==='id_type'): ?>
                    <select name="filing[people][<?= (int)$i ?>][id_type]" data-cac-id-type required>
                      <option value="">Select identification</option>
                      <?php foreach (cac_id_type_options() as $idType): ?>
                        <option value="<?= e($idType) ?>" <?= (string)($person[$key]??'')===$idType?'selected':'' ?>><?= e($idType) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                      <input type="<?= $key==='dob'?'date':($key==='email'?'email':($key==='phone'?'tel':'text')) ?>"
                        name="filing[people][<?= (int)$i ?>][<?= e($key) ?>]"
                        <?= $key==='id_number'?'data-cac-id-number inputmode="text" autocomplete="off"':'' ?>
                        <?= $key==='nin'?'inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="NIN must be 11 digits"':'' ?>
                        value="<?= e((string)($person[$key]??'')) ?>"
                        <?= in_array($key,['full_name','dob','nationality','residential_address','id_number'],true) ? 'required' : '' ?>>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                <details class="iw-cac-advanced-options iw-tm-field-full">
                  <summary>Additional contact information (optional)</summary>
                  <div class="iw-tm-fields">
                    <?php foreach ([
                      'occupation'=>'Occupation','email'=>'Email address','phone'=>'Phone number',
                      'nin'=>'NIN (if applicable)',
                    ] as $key=>$label): ?>
                      <div class="iw-tm-field">
                        <label><?= e($label) ?></label>
                        <input type="<?= $key==='email'?'email':($key==='phone'?'tel':'text') ?>"
                          name="filing[people][<?= (int)$i ?>][<?= e($key) ?>]"
                          <?= $key==='nin'?'inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="NIN must be 11 digits"':'' ?>
                          value="<?= e((string)($person[$key]??'')) ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </details>
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
        <div class="iw-cac-save iw-cac-journey-actions" data-cac-stage="3">
          <button type="button" class="iw-client-button iw-client-button-soft" data-cac-back="2"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</button>
          <button type="submit" class="iw-client-button"><i class="bi bi-cloud-check"></i> Save details and continue</button>
          <span class="iw-client-small-muted">You can return to a saved draft later. No payment is taken yet.</span>
        </div>
      </form>
      <?php if ($cacDraft): ?>
      <section class="iw-tm-panel iw-cac-documents iw-cac-journey-step" data-cac-stage="4">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">4</span><div><h2>Secure documents</h2>
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
      <div class="iw-cac-journey-actions" data-cac-stage="4">
        <button type="button" class="iw-client-button iw-client-button-soft" data-cac-back="3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Edit owner details</button>
        <button type="button" class="iw-client-button" data-cac-next="5">Review application <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      </div>
      <section class="iw-tm-panel iw-cac-review iw-cac-journey-step" data-cac-stage="5">
        <div class="iw-tm-panel-head"><span class="iw-tm-section-number">5</span><div><h2>Review and checkout</h2>
          <p>Verify names and documents before generating an invoice. CAC filing starts only after payment verification.</p></div></div>
        <?php if ($cacQuote): ?>
          <div class="iw-cac-total"><span>Total iWorks service and selected add-ons</span><strong><?= e(money((float)$cacQuote['total'],$cacQuote['currency'])) ?></strong>
            <small>Base filing support: ₦<?= e(number_format((float)$cacQuote['base_ngn'],0)) ?><?php if(($cacQuote['extras_ngn']['registered_office'] ?? 0)>0): ?> · Registered office: ₦<?= e(number_format((float)$cacQuote['extras_ngn']['registered_office'],0)) ?><?php endif; ?><?php if(($cacQuote['extras_ngn']['privacy_advisory'] ?? 0)>0): ?> · Privacy advisory: ₦<?= e(number_format((float)$cacQuote['extras_ngn']['privacy_advisory'],0)) ?><?php endif; ?></small>
            <small><?= $business ? e(ucfirst((string)($filing['structure']??'sole'))) . ' · ' . count($people) . ' proprietor(s)' : count($people) . ' director(s)/owner(s)' ?></small>
          </div>
        <?php endif; ?>
        <div class="iw-cac-journey-actions iw-cac-review-actions">
          <button type="button" class="iw-client-button iw-client-button-soft" data-cac-back="4"><i class="bi bi-arrow-left"></i> Back to documents</button>
          <button type="button" class="iw-client-button iw-client-button-soft" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh document checks</button>
        </div>
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
      <div class="iw-cac-stage"><span>1</span> Business type and name</div>
      <div class="iw-cac-stage"><span>2</span> Contact and address</div>
      <div class="iw-cac-stage"><span>3</span> Owners and roles</div>
      <div class="iw-cac-stage"><span>4</span> Secure documents</div>
      <div class="iw-cac-stage"><span>5</span> Review and payment</div>
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
  var progress=document.getElementById('cacJourneyProgress');
  if(form&&progress){
    progress.hidden=false;
    var hasSavedDraft=<?= $cacDraft?'true':'false' ?>;
    var requestedStep=(new URLSearchParams(window.location.search)).get('step');
    var stepNumber=hasSavedDraft && requestedStep==='documents' ? 4 :
      (hasSavedDraft && requestedStep==='review' ? 5 : 1);
    var screen=document.querySelector('.iw-cac-layout');
    if(screen)screen.classList.add('iw-cac-journey-ready');
    function showStage(number){
      number=Math.max(1,Math.min(hasSavedDraft?5:3,Number(number)||1));
      stepNumber=number;
      document.querySelectorAll('.iw-cac-journey-step,[data-cac-stage].iw-cac-journey-actions').forEach(function(section){
        section.classList.toggle('iw-cac-step-active',Number(section.dataset.cacStage)===number);
      });
      progress.querySelectorAll('[data-cac-jump]').forEach(function(button){
        var stage=Number(button.dataset.cacJump);
        if(stage===number)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current');
        button.classList.toggle('iw-cac-progress-complete',stage<number);
      });
      if(screen && window.matchMedia('(max-width: 760px)').matches){
        progress.scrollIntoView({block:'start',behavior:'auto'});
      }
    }
    function validStage(number){
      var stage=document.querySelector('.iw-cac-journey-step[data-cac-stage="'+number+'"]');
      if(!stage)return true;
      for(var field of stage.querySelectorAll('input,select,textarea')){
        if(!field.checkValidity()){
          if(field.closest('details'))field.closest('details').open=true;
          field.reportValidity();
          return false;
        }
      }
      return true;
    }
    document.querySelectorAll('[data-cac-next],[data-cac-back]').forEach(function(button){
      button.addEventListener('click',function(){
        if(button.dataset.cacNext){
          if(!validStage(stepNumber))return;
          var next=Number(button.dataset.cacNext);
          if(next>=4 && !hasSavedDraft){form.requestSubmit();return;}
          showStage(next);
        } else showStage(Number(button.dataset.cacBack));
      });
    });
    progress.querySelectorAll('[data-cac-jump]').forEach(function(button){
      button.addEventListener('click',function(){
        var stage=Number(button.dataset.cacJump);
        if(stage<=stepNumber || stage<=3 && hasSavedDraft){
          showStage(stage);return;
        }
        if(!validStage(stepNumber))return;
        if(stage>=4 && !hasSavedDraft)return;
        if(stage===stepNumber+1)showStage(stage);
      });
    });
    showStage(stepNumber);
  }
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
    var addressMode=document.getElementById('cacAddressMode');
    var officeInput=document.getElementById('cacAddress');
    var originalAddress=officeInput ? officeInput.value : '';
    var managedOffice=<?= json_encode($virtualAddressAvailable ? setting('cac_virtual_address_details','') : '', JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    function updateOffice() {
      if(!addressMode||!officeInput)return;
      var virtual=addressMode.value==='virtual';
      if(virtual){ if(!officeInput.readOnly)originalAddress=officeInput.value; officeInput.value=managedOffice; }
      else if(officeInput.readOnly)officeInput.value=originalAddress;
      officeInput.readOnly=virtual;
      var sameWrap=document.getElementById('cacSameAddressWrap');
      if(sameWrap)sameWrap.hidden=virtual;
    }
    if(addressMode)addressMode.addEventListener('change',updateOffice);
    updateOffice();

    var nameInput=document.getElementById('cacName1');
    var suggestionHost=document.getElementById('cacNameSuggestions');
    var nameFeedback=document.getElementById('cacNameFeedback');
    var company=<?= $business?'false':'true' ?>;
    function suggestNames(){
      if(!nameInput||!suggestionHost)return;
      var name=nameInput.value.trim().replace(/\s+/g,' ');
      suggestionHost.replaceChildren();
      nameFeedback.textContent='';
      if(name.length<4)return;
      var suffix=/\b(?:Limited|Ltd|Enterprises?|Ventures?|Services|LLC)\.?$/i;
      var base=name.replace(suffix,'').trim()||name;
      if(!company && /\b(?:Limited|Ltd|LLC|PLC|Inc)\.?$/i.test(name)){
        nameFeedback.textContent='Business Names must not use company suffixes such as Ltd, Limited or LLC.';
      }else if(company && /\b(?:LLC|Inc|PLC)\.?$/i.test(name)){
        nameFeedback.textContent='For Nigerian private companies use Ltd or Limited, not LLC or Inc.';
      }else if(company && !/\b(?:LTD|LIMITED)\.?$/i.test(name)){
        nameFeedback.textContent='Tip: Nigerian private limited companies normally end with Ltd or Limited.';
      }else{
        nameFeedback.textContent='Preliminary suggestions only. Check the official CAC iCRP for actual availability.';
      }
      var alternatives=company?[base+' Limited',base+' Ventures Limited',base+' Services Limited']:[base+' Enterprises',base+' Ventures',base+' Services'];
      alternatives.forEach(function(candidate){
        if(candidate.toLowerCase()===name.toLowerCase()||candidate.length>190)return;
        var button=document.createElement('button');button.type='button';button.className='iw-cac-suggestion';
        button.textContent=candidate;
        button.title='Use as alternative proposed name';
        button.addEventListener('click',function(){document.getElementById('cacName2').value=candidate;});
        suggestionHost.appendChild(button);
      });
    }
    if(nameInput){nameInput.addEventListener('input',suggestNames);suggestNames();}

    function validateId(row){
      var type=row.querySelector('[data-cac-id-type]');
      var number=row.querySelector('[data-cac-id-number]');
      if(!type||!number)return;
      var normalized=number.value.trim().replace(/[\s-]+/g,'').toUpperCase();
      number.setCustomValidity('');
      if(!normalized)return;
      if(type.value==='NIN' && !/^\d{11}$/.test(normalized)){
        number.setCustomValidity('A NIN has exactly 11 digits.');
      }else if(type.value==='International passport'&&!/^[A-Z0-9]{6,16}$/.test(normalized)){
        number.setCustomValidity('Enter the document number printed on your passport (6–16 letters/digits).');
      }else if(type.value==='Driver licence'&&!/^[A-Z0-9]{6,22}$/.test(normalized)){
        number.setCustomValidity('Check the licence number printed on your document.');
      }else if(type.value==='Voter card'&&!/^[A-Z0-9]{8,25}$/.test(normalized)){
        number.setCustomValidity('Check the voter identification number printed on your card.');
      }
    }
    people.addEventListener('input',function(ev){if(ev.target.matches('[data-cac-id-number]'))validateId(ev.target.closest('[data-person]'));});
    people.addEventListener('change',function(ev){if(ev.target.matches('[data-cac-id-type]'))validateId(ev.target.closest('[data-person]'));});
    people.querySelectorAll('[data-person]').forEach(validateId);
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
