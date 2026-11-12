<?php
$details=is_array($cacRow['data']??null)?$cacRow['data']:[];
$people=is_array($details['people']??null)?$details['people']:[];
$docs=is_array($cacRow['docs']??null)?$cacRow['docs']:[];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div><h1 class="h4 mb-1">CAC registration #<?= (int)$cacRow['id'] ?></h1>
    <p class="small text-muted mb-0"><?= e((string)$details['first_choice']) ?> · <?= e((string)$cacRow['kind']) ?> · <?= e((string)$cacRow['status']) ?></p></div>
  <a class="btn btn-outline-secondary" href="<?= e(url('job',['id'=>(int)$cacRow['job_id']])) ?>">Back to service job</a>
</div>
<div class="alert alert-info"><i class="bi bi-lock-fill"></i> Restricted filing information. Use only for authorised CAC preparation; do not share applicant IDs outside the approved filing process.</div>
<section class="card mb-3"><div class="card-body">
  <h2 class="h5">Registration details</h2>
  <dl class="row mb-0">
  <?php foreach ([
    'first_choice'=>'Preferred business name','second_choice'=>'Alternative name',
    'structure'=>'Structure','company_type'=>'Company type','share_capital'=>'Issued share capital',
    'av_code'=>'Reservation code','business_activity'=>'Business activity',
    'business_email'=>'Email','business_phone'=>'Phone',
    'business_address'=>'Registered address','commencement_date'=>'Commencement'
  ] as $key=>$label): ?>
    <?php if(trim((string)($details[$key]??''))==='')continue; ?>
    <dt class="col-sm-3 text-muted small"><?= e($label) ?></dt>
    <dd class="col-sm-9"><?= nl2br(e((string)$details[$key])) ?></dd>
  <?php endforeach; ?>
  </dl>
</div></section>
<?php foreach ($people as $i=>$person): ?>
<section class="card mb-3"><div class="card-body">
  <h2 class="h6">Person <?= (int)$i+1 ?> · <?= e((string)($person['full_name']??'Not named')) ?></h2>
  <div class="row">
  <?php foreach ([
    'role'=>'Role','dob'=>'Date of birth','nationality'=>'Nationality','occupation'=>'Occupation',
    'email'=>'Email','phone'=>'Phone','residential_address'=>'Residential address',
    'nin'=>'NIN','id_type'=>'ID type','id_number'=>'ID number','share_percent'=>'Shareholding %',
  ] as $key=>$label): ?>
    <?php if(trim((string)($person[$key]??''))==='')continue; ?>
    <div class="col-md-6 mb-2"><small class="text-muted d-block"><?= e($label) ?></small>
      <strong class="small"><?= e((string)$person[$key]) ?></strong></div>
  <?php endforeach; ?>
  </div>
  <div class="d-flex gap-2 flex-wrap mt-2">
    <?php foreach (['id'=>'View government ID','photo'=>'View photograph','signature'=>'View signature'] as $type=>$label): ?>
      <?php if(!empty($docs[$i][$type]['path'])): ?>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('cac_document',['id'=>(int)$cacRow['id'],'person'=>(int)$i,'type'=>$type])) ?>">
          <i class="bi bi-file-earmark-lock"></i> <?= e($label) ?></a>
      <?php else: ?>
        <span class="badge text-bg-warning"><?= e($label) ?> missing</span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endforeach; ?>
