<?php
$person = is_array($person ?? null) ? $person : [];
$fields = [
    'full_name'=>['Legal full name','text','As shown on government ID',true],
    'email'=>['Email address','email','',true],
    'phone'=>['Phone number','tel','',true],
    'date_of_birth'=>['Date of birth','date','',true],
    'nationality'=>['Nationality','text','e.g. Nigerian',true],
    'occupation'=>['Occupation','text','',true],
    'address'=>['Residential address','text','House number and street',true],
    'city'=>['City / town','text','',true],
    'lga'=>['Residential local government','text','',true],
    'state'=>['Residential state','text','',true],
    'id_number'=>['Government ID number','text','Enter exactly as shown',true],
];
$existingName=(string) ($person['full_name'] ?? '');
$personIndex=(string) $i;
?>
<fieldset class="iw-cac-person" data-cac-person data-person-index="<?= e($personIndex) ?>">
  <legend class="iw-cac-person-title">
    <span class="iw-cac-person-number">Applicant <span data-person-number><?= is_numeric($i) ? (int) $i + 1 : 1 ?></span><?= is_numeric($i) && (int) $i===0?' · Lead':'' ?></span>
    <button type="button" class="iw-cac-remove-person" data-remove-person <?= is_numeric($i) && (int) $i===0?'hidden':'' ?>><i class="bi bi-trash3"></i> Remove</button>
  </legend>
  <div class="iw-tm-fields">
    <?php foreach ($fields as $key=>$field): ?>
      <div class="iw-tm-field <?= in_array($key,['address'],true)?'iw-tm-field-full':'' ?>">
        <label for="cac_<?= e($personIndex.'_'.$key) ?>"><?= e($field[0]) ?><?= $field[3]?' <span>*</span>':'' ?></label>
        <input id="cac_<?= e($personIndex.'_'.$key) ?>" data-cac-person-field="<?= e($key) ?>"
          name="people[<?= e($personIndex) ?>][<?= e($key) ?>]"
          type="<?= e($field[1]) ?>" placeholder="<?= e($field[2]) ?>" maxlength="<?= $field[1]==='date'?10:450 ?>"
          value="<?= e((string) ($person[$key] ?? '')) ?>" <?= $field[3]?'required':'' ?>>
      </div>
    <?php endforeach; ?>
    <div class="iw-tm-field">
      <label for="cac_<?= e($personIndex) ?>_gender">Gender (if applicable)</label>
      <select id="cac_<?= e($personIndex) ?>_gender" name="people[<?= e($personIndex) ?>][gender]">
        <option value="">Select</option>
        <option value="male" <?= ($person['gender'] ?? '')==='male'?'selected':'' ?>>Male</option>
        <option value="female" <?= ($person['gender'] ?? '')==='female'?'selected':'' ?>>Female</option>
        <option value="other" <?= ($person['gender'] ?? '')==='other'?'selected':'' ?>>Other / specify to staff</option>
      </select>
    </div>
    <div class="iw-tm-field">
      <label for="cac_<?= e($personIndex) ?>_id_type">Means of identification <span>*</span></label>
      <select id="cac_<?= e($personIndex) ?>_id_type" name="people[<?= e($personIndex) ?>][id_type]" required>
        <option value="">Choose government ID</option>
        <?php foreach (['national_id'=>'National ID card','nin'=>'NIN / NIMC slip','passport'=>'International passport',
          'drivers_license'=>'Driver’s licence','voters_card'=>'Voter’s card'] as $code=>$label): ?>
          <option value="<?= e($code) ?>" <?= ($person['id_type'] ?? '')===$code?'selected':'' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($kind==='company'): ?>
      <div class="iw-tm-field">
        <label for="cac_<?= e($personIndex) ?>_role">Position in company <span>*</span></label>
        <select id="cac_<?= e($personIndex) ?>_role" name="people[<?= e($personIndex) ?>][role]" data-cac-role>
          <?php foreach (['both'=>'Director and shareholder','director'=>'Director only','shareholder'=>'Shareholder only'] as $r=>$label): ?>
          <option value="<?= e($r) ?>" <?= ($person['role'] ?? 'both')===$r?'selected':'' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="iw-tm-field">
        <label for="cac_<?= e($personIndex) ?>_shares">Shares allocated (where applicable)</label>
        <input id="cac_<?= e($personIndex) ?>_shares" name="people[<?= e($personIndex) ?>][shares]"
          type="number" min="0" step="1" value="<?= e((string) ($person['shares'] ?? '')) ?>">
      </div>
    <?php endif; ?>
  </div>
  <div class="iw-cac-uploads">
    <?php foreach (['id'=>'Government ID','photo'=>'Recent passport photograph','signature'=>'Signature'] as $docType=>$label): ?>
      <?php if ($docType==='photo' && $kind==='company') { continue; } ?>
      <?php $key='person_'.$personIndex.'_'.$docType; $attached=$uploaded[$key] ?? null; ?>
      <div class="iw-cac-upload-row" data-cac-file-field="<?= e($key) ?>">
        <div class="iw-cac-upload-title">
          <i class="bi <?= $docType==='signature'?'bi-pen':'bi-file-earmark-arrow-up' ?>" aria-hidden="true"></i>
          <div><strong><?= e($label) ?></strong>
          <small><?= $docType==='photo'?'JPG, PNG or WebP · 6 MB max':'JPG, PNG, WebP or PDF · 6 MB max' ?></small></div>
        </div>
        <div class="iw-cac-upload-control">
          <label class="iw-cac-upload-trigger" for="cacFile_<?= e($key) ?>"><i class="bi bi-upload"></i> <?= $docType==='signature'?'Upload signature':'Choose file' ?>
            <input type="file" id="cacFile_<?= e($key) ?>" data-cac-file-input
              data-cac-file-key="<?= e($key) ?>"
              accept="<?= $docType==='photo'?'image/jpeg,image/png,image/webp':'image/jpeg,image/png,image/webp,application/pdf' ?>">
          </label>
          <?php if ($docType==='signature'): ?>
          <button type="button" class="iw-cac-sign-open" data-sign-open><i class="bi bi-pencil"></i> Draw signature</button>
          <?php endif; ?>
        </div>
        <div class="iw-cac-upload-status <?= $attached?'is-uploaded':'' ?>" data-upload-status aria-live="polite">
          <?php if ($attached): ?><i class="bi bi-check-circle-fill"></i> Attached: <?= e((string) $attached['original_name']) ?> (<?= e(number_format((int) $attached['file_size']/1024,1)) ?> KB)
          <?php else: ?><i class="bi bi-clock"></i> Not uploaded yet<?php endif; ?>
        </div>
        <div class="iw-cac-upload-progress" data-upload-progress hidden><span></span></div>
        <?php if ($docType==='signature'): ?>
          <div class="iw-cac-sign-pad" data-sign-pad hidden>
            <p>Draw your signature using your finger, mouse or stylus.</p>
            <canvas width="550" height="190" data-sign-canvas aria-label="Signature drawing area"></canvas>
            <div class="iw-cac-sign-actions">
              <button type="button" data-sign-clear>Clear</button>
              <button type="button" data-sign-save class="iw-client-button">Save signature</button>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</fieldset>
