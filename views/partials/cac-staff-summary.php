<?php
/** Internal staff-only summary for an existing Nigerian CAC registration job. */
$cacStaffApplication = cac_find_for_job((int) $job['id'],(int) $job['client_id']);
if (!$cacStaffApplication) { return; }
$filing = $cacStaffApplication['data'] ?? [];
$files = cac_list_files((int) $cacStaffApplication['id'],(int) $job['client_id']);
$filesByKey = [];
foreach ($files as $file) { $filesByKey[(string) $file['field_key']]=$file; }
?>
<section class="card mb-3" aria-label="CAC filing information">
  <div class="card-body">
    <h2 class="h5"><i class="bi bi-building me-2"></i> CAC registration application · CAC-<?= (int) $cacStaffApplication['id'] ?></h2>
    <p class="text-muted small">Nigerian <?= $cacStaffApplication['kind']==='company'?'private company incorporation':'business name registration' ?>. Documents remain in private storage and can be downloaded by authorised staff only.</p>
    <dl class="row g-2 small">
      <dt class="col-sm-4">Proposed name</dt><dd class="col-sm-8"><?= e((string) ($filing['name1'] ?? '')) ?></dd>
      <dt class="col-sm-4">Alternative name</dt><dd class="col-sm-8"><?= e((string) ($filing['name2'] ?? '')) ?></dd>
      <dt class="col-sm-4">Structure</dt><dd class="col-sm-8"><?= e((string) ($filing['structure'] ?? 'Private LTD')) ?></dd>
      <dt class="col-sm-4">CAC AV code</dt><dd class="col-sm-8"><?= e((string) ($filing['reservation_code'] ?? 'Not yet reserved')) ?></dd>
      <dt class="col-sm-4">Nature of business</dt><dd class="col-sm-8"><?= e((string) ($filing['activity'] ?? '')) ?></dd>
      <dt class="col-sm-4">Registered address</dt>
      <dd class="col-sm-8"><?= e((string) ($filing['business_address'] ?? '')) ?>, <?= e((string) ($filing['business_city'] ?? '')) ?>, <?= e((string) ($filing['business_lga'] ?? '')) ?>, <?= e((string) ($filing['business_state'] ?? '')) ?></dd>
      <?php if ($cacStaffApplication['kind']==='company'): ?>
      <dt class="col-sm-4">Issued share capital</dt><dd class="col-sm-8"><?= e((string) ($filing['share_capital'] ?? '')) ?></dd>
      <?php endif; ?>
    </dl>
    <?php foreach (is_array($filing['people'] ?? null)?$filing['people']:[] as $i=>$person): ?>
      <div class="border rounded p-3 mt-2">
        <h3 class="h6 mb-2">Applicant <?= $i+1 ?> · <?= e((string) ($person['full_name'] ?? '')) ?></h3>
        <div class="row g-2 small">
          <div class="col-md-6">Email: <?= e((string) ($person['email'] ?? '')) ?></div>
          <div class="col-md-6">Phone: <?= e((string) ($person['phone'] ?? '')) ?></div>
          <div class="col-md-6">Nationality: <?= e((string) ($person['nationality'] ?? '')) ?></div>
          <div class="col-md-6">Role: <?= e((string) ($person['role'] ?? 'Proprietor')) ?></div>
          <div class="col-md-6">Identification: <?= e((string) ($person['id_type'] ?? '')) ?></div>
          <div class="col-md-6">ID number: <?= e((string) ($person['id_number'] ?? '')) ?></div>
          <div class="col-md-12">Address: <?= e((string) ($person['address'] ?? '')) ?>, <?= e((string) ($person['city'] ?? '')) ?>, <?= e((string) ($person['state'] ?? '')) ?></div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
          <?php foreach (['id'=>'Government ID','photo'=>'Passport photo','signature'=>'Signature'] as $type=>$label): ?>
            <?php $file=$filesByKey['person_'.$i.'_'.$type] ?? null; ?>
            <?php if ($file): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('cac_file',['id'=>(int) $file['id']])) ?>">
              <i class="bi bi-download"></i> <?= e($label) ?></a><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
