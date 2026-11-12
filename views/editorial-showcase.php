<?php
$tab=in_array((string)($_GET['tab']??'cases'),['cases','feedback','comments'],true)?(string)($_GET['tab']??'cases'):'cases';
$case=$editCaseRecord??[];
$review=$editReviewRecord??[];
?>
<div class="iw-editorial-admin">
  <div class="iw-admin-page-heading"><div><small>BRAND & CONTENT</small>
    <h1>Stories & customer feedback</h1>
    <p>Publish verified case studies and consented customer reviews, and moderate article comments.</p>
  </div><a href="<?= e(public_url('case-studies')) ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener">View public stories <i class="bi bi-box-arrow-up-right"></i></a></div>
  <nav class="iw-editorial-tabs" aria-label="Content type">
    <a <?= $tab==='cases'?'aria-current="page"':'' ?> href="<?= e(url('editorial_showcase',['tab'=>'cases'])) ?>"><i class="bi bi-briefcase"></i> Case studies <span><?= count($caseRecords) ?></span></a>
    <a <?= $tab==='feedback'?'aria-current="page"':'' ?> href="<?= e(url('editorial_showcase',['tab'=>'feedback'])) ?>"><i class="bi bi-chat-square-quote"></i> Customer feedback <span><?= count($reviewRecords) ?></span></a>
    <a <?= $tab==='comments'?'aria-current="page"':'' ?> href="<?= e(url('editorial_showcase',['tab'=>'comments'])) ?>"><i class="bi bi-chat-left-text"></i> Comment moderation <span><?= count($pendingBlogComments) ?></span></a>
  </nav>
  <?php if($tab==='cases'): ?>
    <div class="iw-editorial-admin-grid">
      <section class="card"><div class="card-body">
        <h2 class="h5"><?= $case?'Edit case study':'Add a case study' ?></h2>
        <p class="text-muted small">Use client-approved material. Case studies remain drafts until published.</p>
        <form method="post" action="<?= e(url('editorial_showcase')) ?>" enctype="multipart/form-data" class="iw-editorial-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_case"><input type="hidden" name="tab" value="cases">
          <input type="hidden" name="id" value="<?= (int)($case['id']??0) ?>">
          <label>Story title <input class="form-control" name="title" required maxlength="240" value="<?= e((string)($case['title']??'')) ?>" placeholder="How a client launched in Nigeria"></label>
          <div class="row g-2">
            <label class="col-md-6">Client / brand name<input class="form-control" name="client_name" value="<?= e((string)($case['client_name']??'')) ?>"></label>
            <label class="col-md-6">Industry / category<input class="form-control" name="category" value="<?= e((string)($case['category']??'')) ?>" placeholder="Business registration"></label>
          </div>
          <label>Summary<textarea class="form-control" name="summary" rows="3" maxlength="1200"><?= e((string)($case['summary']??'')) ?></textarea></label>
          <label>Full story (headings, paragraphs and links)<textarea class="form-control" name="story_html" rows="8" required data-iw-rich-content><?= e((string)($case['story_html']??'')) ?></textarea></label>
          <label>Highlight / outcome<input class="form-control" name="outcome" value="<?= e((string)($case['outcome']??'')) ?>" placeholder="Registered and launched with iWorks support"></label>
          <div class="row g-2">
            <label class="col-md-6">Cover graphic <input class="form-control" type="file" name="cover_upload" accept=".jpg,.jpeg,.png,.webp"></label>
            <label class="col-md-6">Client logo <input class="form-control" type="file" name="logo_upload" accept=".jpg,.jpeg,.png,.webp"></label>
          </div>
          <div class="d-flex gap-2 align-items-end flex-wrap"><label class="flex-grow-1">Visibility
            <select class="form-select" name="status">
              <option value="draft" <?= ($case['status']??'draft')==='draft'?'selected':'' ?>>Draft</option>
              <option value="published" <?= ($case['status']??'')==='published'?'selected':'' ?>>Publish</option>
            </select></label>
            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-check"></i> Save story</button>
          </div>
        </form>
      </div></section>
      <section class="card"><div class="card-body"><h2 class="h5">Case studies</h2>
        <?php if(!$caseRecords): ?><p class="text-muted">No case studies created yet. Publish only verified client projects.</p><?php endif; ?>
        <?php foreach($caseRecords as $item): ?>
          <div class="iw-editorial-admin-item">
            <div><strong><?= e((string)$item['title']) ?></strong>
              <small><?= e((string)$item['client_name']) ?> · <?= e(ucfirst((string)$item['status'])) ?></small></div>
            <a href="<?= e(url('editorial_showcase',['tab'=>'cases','case_id'=>(int)$item['id']])) ?>">Edit</a>
            <form method="post" action="<?= e(url('editorial_showcase')) ?>" onsubmit="return confirm('Delete this case study?')">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="delete_case"><input type="hidden" name="tab" value="cases"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div></section>
    </div>
  <?php elseif($tab==='feedback'): ?>
    <div class="iw-editorial-admin-grid">
      <section class="card"><div class="card-body"><h2 class="h5"><?= $review?'Edit feedback':'Add customer feedback' ?></h2>
        <p class="text-muted small">Use authentic customer statements only. Written consent is required before publishing identifying details.</p>
        <form method="post" enctype="multipart/form-data" action="<?= e(url('editorial_showcase')) ?>" class="iw-editorial-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="save_review">
          <input type="hidden" name="id" value="<?= (int)($review['id']??0) ?>"><input type="hidden" name="tab" value="feedback">
          <div class="row g-2">
            <label class="col-md-6">Customer's name<input class="form-control" name="customer_name" maxlength="190" required value="<?= e((string)($review['customer_name']??'')) ?>"></label>
            <label class="col-md-6">Job title<input class="form-control" name="position" value="<?= e((string)($review['position']??'')) ?>"></label>
          </div>
          <label>Company<input class="form-control" name="company" value="<?= e((string)($review['company']??'')) ?>"></label>
          <label>Customer's own words<textarea class="form-control" name="quote_text" rows="5" maxlength="1800" required><?= e((string)($review['quote_text']??'')) ?></textarea></label>
          <div class="row g-2">
            <label class="col-md-6">Company logo <input class="form-control" type="file" name="logo_upload" accept=".jpg,.jpeg,.png,.webp"></label>
            <label class="col-md-6">Customer portrait <input class="form-control" type="file" name="portrait_upload" accept=".jpg,.jpeg,.png,.webp"></label>
          </div>
          <label class="form-check"><input type="checkbox" class="form-check-input" name="consent_confirmed" value="1" <?= !empty($review['consent_confirmed'])?'checked':'' ?>>
            <span class="form-check-label">The customer has authorised publication of their quote, identity and any uploaded images.</span></label>
          <div class="row g-2">
            <label class="col-md-6">Display order <input class="form-control" name="sort_order" type="number" value="<?= (int)($review['sort_order']??0) ?>"></label>
            <label class="col-md-6">Visibility <select class="form-select" name="status">
              <option value="draft" <?= ($review['status']??'draft')==='draft'?'selected':'' ?>>Draft</option>
              <option value="published" <?= ($review['status']??'')==='published'?'selected':'' ?>>Publish</option>
            </select></label>
          </div>
          <button type="submit" class="btn btn-primary">Save customer feedback</button>
        </form>
      </div></section>
      <section class="card"><div class="card-body"><h2 class="h5">Customer feedback</h2>
        <?php if(!$reviewRecords): ?><p class="text-muted">No customer feedback uploaded yet.</p><?php endif; ?>
        <?php foreach($reviewRecords as $item): ?>
          <div class="iw-editorial-admin-item"><div><strong><?= e((string)$item['customer_name']) ?></strong><small><?= e((string)$item['company']) ?> · <?= e(ucfirst((string)$item['status'])) ?></small></div>
            <a href="<?= e(url('editorial_showcase',['tab'=>'feedback','review_id'=>(int)$item['id']])) ?>">Edit</a>
            <form method="post" action="<?= e(url('editorial_showcase')) ?>" onsubmit="return confirm('Delete this feedback?')">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="delete_review"><input type="hidden" name="tab" value="feedback"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div></section>
    </div>
  <?php else: ?>
    <section class="card"><div class="card-body"><h2 class="h5">Comments awaiting approval</h2>
      <p class="text-muted small">Comments are held privately until approved. Reader email addresses remain private.</p>
      <?php if(!$pendingBlogComments): ?><p class="text-muted">No pending comments.</p><?php endif; ?>
      <?php foreach($pendingBlogComments as $comment): ?>
        <article class="iw-editorial-admin-comment">
          <strong><?= e((string)$comment['display_name']) ?></strong>
          <span>on <?= e((string)$comment['article_title']) ?></span>
          <p><?= nl2br(e((string)$comment['comment_text'])) ?></p>
          <div class="d-flex gap-2">
            <?php foreach(['approve_comment'=>'Approve','delete_comment'=>'Delete'] as $action=>$label): ?>
              <form method="post" action="<?= e(url('editorial_showcase')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="tab" value="comments">
                <input type="hidden" name="form_action" value="<?= e($action) ?>"><input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                <button class="btn btn-sm <?= $action==='approve_comment'?'btn-primary':'btn-outline-danger' ?>" type="submit"><?= e($label) ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div></section>
  <?php endif; ?>
</div>
