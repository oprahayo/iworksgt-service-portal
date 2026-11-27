<?php
$filters=$inbox['filters'];$filters['p']=$inbox['page'];
$leadStates=['new'=>'New','reviewed'=>'Reviewed','closed'=>'Closed'];
?>
<section class="iw-communications">
  <header class="iw-comm-heading"><div><h1>Communications</h1><p>Manage website enquiries, conversations and support from one workspace.</p></div></header>
  <?php require __DIR__.'/partials/communications-nav.php'; ?>
  <div class="iw-comm-counts">
  <?php foreach($leadStates as $value=>$label): ?>
    <a href="<?= e(url('website_inbox',['status'=>$value])) ?>" class="<?= $filters['status']===$value?'is-active':'' ?>"><span><?= e($label) ?></span><strong><?= (int)$inbox['counts'][$value] ?></strong></a>
  <?php endforeach; ?>
  </div>
  <div class="iw-enquiry-grid<?= $leadId?' has-selection':'' ?>">
    <aside class="iw-enquiry-list">
      <div class="iw-enquiry-list-head"><h2>Website enquiries</h2><a href="<?= e(url('website_inbox')) ?>">All (<?= array_sum($inbox['counts']) ?>)</a></div>
      <form class="iw-enquiry-search" method="get" action="index.php">
        <input type="hidden" name="page" value="website_inbox"><label class="visually-hidden" for="enquirySearch">Search enquiries</label>
        <input id="enquirySearch" class="form-control" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search name, email or message" maxlength="120">
        <label class="visually-hidden" for="enquiryStatus">Status</label>
        <select id="enquiryStatus" class="form-select" name="status"><option value="">All statuses</option><?php foreach($leadStates as $k=>$v): ?><option value="<?= e($k) ?>"<?= $filters['status']===$k?' selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <button class="btn btn-outline-secondary" aria-label="Apply filters"><i class="bi bi-search" aria-hidden="true"></i></button>
      </form>
      <div class="iw-enquiry-rows">
        <?php foreach($inbox['rows'] as $lead): ?>
          <a class="iw-enquiry-row<?= $selectedLead&&(int)$selectedLead['id']===(int)$lead['id']?' is-active':'' ?>" href="<?= e(url('website_inbox',$filters+['lead_id'=>(int)$lead['id']])) ?>">
            <span class="iw-enquiry-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
            <span class="iw-enquiry-copy"><strong><?= e((string)$lead['full_name']) ?></strong><small><?= e((string)$lead['email']) ?></small><span><?= e((string)$lead['preview']) ?></span><small><?= e((string)$lead['created_at']) ?> · <?= e((string)$lead['page_slug']) ?></small></span>
            <span class="iw-enquiry-state state-<?= e((string)$lead['status']) ?>"><?= e($leadStates[$lead['status']]??'Unknown') ?></span>
          </a>
        <?php endforeach; ?>
        <?php if(!$inbox['rows']): ?><p class="iw-enquiry-empty">No enquiries match these filters.</p><?php endif; ?>
      </div>
      <div class="iw-enquiry-pagination"><span><?= (int)$inbox['total'] ?> results · <?= (int)$inbox['page'] ?>/<?= (int)$inbox['pages'] ?></span><div>
        <?php if($inbox['page']>1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('website_inbox',array_replace($filters,['p'=>$inbox['page']-1]))) ?>">Previous</a><?php endif; ?>
        <?php if($inbox['page']<$inbox['pages']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('website_inbox',array_replace($filters,['p'=>$inbox['page']+1]))) ?>">Next</a><?php endif; ?>
      </div></div>
    </aside>
    <article class="iw-enquiry-detail">
      <?php if($selectedLead): $lead=$selectedLead; $id=(int)$lead['id']; $email=(string)$lead['email']; ?>
        <a class="iw-enquiry-back" href="<?= e(url('website_inbox',$filters)) ?>"><i class="bi bi-arrow-left"></i> Back to enquiries</a>
        <div class="iw-enquiry-detail-head"><div><small>ENQUIRY #<?= $id ?></small><h2><?= e((string)$lead['full_name']) ?></h2><p><?= e((string)$lead['created_at']) ?> · <?= e((string)$lead['page_slug']) ?></p></div><span class="iw-enquiry-state state-<?= e((string)$lead['status']) ?>"><?= e($leadStates[$lead['status']]??'Unknown') ?></span></div>
        <dl class="iw-enquiry-contact"><div><dt>Email</dt><dd><?= e($email) ?></dd></div><div><dt>Phone</dt><dd><?= e((string)($lead['phone']?:'Not supplied')) ?></dd></div><div><dt>Company</dt><dd><?= e((string)($lead['company_name']?:'Not supplied')) ?></dd></div></dl>
        <h3>Original message</h3><div class="iw-enquiry-message"><?= e((string)$lead['message']) ?></div>
        <div class="iw-enquiry-actions">
        <?php if(filter_var($email,FILTER_VALIDATE_EMAIL)): ?><a class="btn btn-primary" href="<?= e('mailto:'.str_replace('%40','@',rawurlencode($email)).'?subject='.rawurlencode('Re: iWorks website enquiry #'.$id)) ?>"><i class="bi bi-reply"></i> Reply by email</a><?php endif; ?>
        <form method="post" action="<?= e(url('website_inbox',$filters+['lead_id'=>$id])) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="lead_id" value="<?= $id ?>">
          <label class="visually-hidden" for="leadState">Enquiry status</label><select id="leadState" class="form-select" name="status"><?php foreach($leadStates as $k=>$v): ?><option value="<?= e($k) ?>"<?= $lead['status']===$k?' selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
          <button class="btn btn-outline-secondary">Save status</button>
        </form></div><small class="text-muted">Email replies open your email application. Mark the enquiry reviewed or closed when handled.</small>
      <?php else: ?><?php if($leadId): ?><a class="iw-enquiry-back" href="<?= e(url('website_inbox',$filters)) ?>"><i class="bi bi-arrow-left"></i> Back to enquiries</a><?php endif; ?><div class="iw-enquiry-placeholder"><i class="bi bi-envelope-open"></i><h2><?= $leadId?'Enquiry not found':'Select an enquiry' ?></h2><p>Open a message to see the full enquiry and contact details.</p></div><?php endif; ?>
    </article>
  </div>
</section>
