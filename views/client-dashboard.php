<?php
/** Shared customer catalog: same published service records as public website. */
$categoryNames = [];
foreach ($clientCatalogue as $catalogItem) {
    $category = trim((string) ($catalogItem['category'] ?? 'general')) ?: 'general';
    $categoryNames[$category] = ucwords(str_replace(['-','_'], ' ', $category));
}
?>
<div class="iw-client-heading">
  <div>
    <span class="iw-client-eyebrow">NEW REQUEST</span>
    <h1>What would you like to do?</h1>
    <p>Choose a service, see the requirements and follow its progress here.</p>
  </div>
  <a class="iw-client-subtle-link" href="<?= e(url('applications')) ?>">My services <i class="bi bi-arrow-up-right"></i></a>
</div>
<div class="iw-client-catalog-tools">
  <label class="iw-client-catalog-search"><i class="bi bi-search" aria-hidden="true"></i>
    <span class="visually-hidden">Search services</span>
    <input id="clientServiceSearch" type="search" placeholder="Search services" autocomplete="off" aria-controls="clientServiceGrid"></label>
  <label class="iw-client-catalog-filter"><span class="visually-hidden">Filter by category</span>
    <select id="clientServiceCategory">
      <option value="">All categories (<?= count($clientCatalogue) ?>)</option>
      <?php foreach ($categoryNames as $category => $label): ?>
        <option value="<?= e($category) ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>
<div class="iw-client-catalog-head">
  <h2>Available services</h2><span id="clientServiceCount" aria-live="polite"><?= count($clientCatalogue) ?> services</span>
</div>
<div class="iw-client-services iw-client-catalog" id="clientServiceGrid">
<?php foreach ($clientCatalogue as $index=>$service): ?>
  <?php
    $slug = trim((string) ($service['slug'] ?? ''));
    $name = trim((string) ($service['name'] ?? 'Service'));
    $category = trim((string) ($service['category'] ?? 'general')) ?: 'general';
    $description = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($service['short_description'] ?? ''))));
    // Retired bundles are filtered by the shared catalogue policy, not
    // cosmetically renamed on customer-facing cards.
    $description = trim((string) preg_replace('/\b(?:banking|bank account opening)\b\s*,?\s*/i', '', $description));
    if ($description === '') { $description = 'View the requirements, fees and next steps for this service.'; }
    $priceDetails = iworks_customer_product_price($service);
    $pricing = (string) $priceDetails['display'];
    $checkoutMode = (string) $priceDetails['checkout_mode'];
    $needsEnquiry = (bool) $priceDetails['is_enquiry'];
    // Unpriced and specialist services must not enter the generic invoice
    // route. CAC/Trademark keep their established guided application flows.
    $startUrl = $needsEnquiry
        ? public_url('service', ['slug'=>$slug])
        : ($slug === 'cac-business-name'
            ? url('cac_filing', ['type'=>'business'])
            : ($slug === 'trademark'
                ? url('trademark')
                : ($slug === 'company-registration'
                    ? url('cac_filing')
                    : url('jobs', ['action'=>'new','service'=>$slug]))));
    $startLabel = $needsEnquiry ? 'Enquire' : 'Start';
    $searchValue = strtolower($name . ' ' . $description . ' ' . ($categoryNames[$category] ?? ''));
  ?>
  <article class="iw-client-service iw-client-catalog-item" data-search="<?= e($searchValue) ?>" data-category="<?= e($category) ?>" data-tone="<?= (int) ($index % 4) ?>">
    <span class="iw-client-service-icon"><i class="bi <?= e(iworks_client_service_icon($service)) ?>" aria-hidden="true"></i></span>
    <span class="iw-client-service-category"><?= e($categoryNames[$category] ?? 'Services') ?></span>
    <h3><?= e($name) ?></h3>
    <p><?= e($description) ?></p>
    <div class="iw-client-service-bottom">
      <span class="iw-client-catalog-price"><?= e($pricing) ?></span>
      <div class="iw-client-catalog-actions">
        <a href="<?= e(public_url('service',['slug'=>$slug])) ?>" class="iw-client-catalog-learn" aria-label="Learn more about <?= e($name) ?>">Details</a>
        <a href="<?= e($startUrl) ?>" class="iw-client-service-action"><?= e($startLabel) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>
<div class="iw-client-empty iw-client-catalog-empty" id="clientServiceEmpty" hidden>
  <i class="bi bi-search"></i><div><strong>No matching services</strong><p>Try another search or browse all categories.</p></div>
</div>
<section class="iw-client-recent" aria-label="Recent customer services">
  <div class="iw-client-section-top">
    <div><h2>Recent services</h2><p>Continue a draft, complete payment or check an active request.</p></div>
    <a href="<?= e(url('applications')) ?>">View all</a>
  </div>
  <?php if (empty($clientRecent)): ?>
    <div class="iw-client-empty"><i class="bi bi-file-earmark-text"></i>
      <div><strong>No services started yet</strong><p>Select a service above and it will appear here.</p></div>
    </div>
  <?php else: ?>
    <?php foreach ($clientRecent as $recent): ?>
      <a class="iw-client-draft-line" href="<?= e((string) $recent['detail_url']) ?>">
        <span><strong><?= e((string) $recent['name']) ?></strong>
        <small><?= e((string) $recent['service']) ?> · <?= e((string) $recent['reference']) ?></small></span>
        <span class="iw-client-status" data-state="<?= e((string) $recent['status']) ?>"><?= e(iworks_client_state_label((string) $recent['status'])) ?></span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<script>
(function(){
  const search=document.getElementById('clientServiceSearch');
  const cat=document.getElementById('clientServiceCategory');
  const rows=Array.from(document.querySelectorAll('#clientServiceGrid [data-search]'));
  const empty=document.getElementById('clientServiceEmpty');
  const count=document.getElementById('clientServiceCount');
  function filter(){
    const q=(search.value||'').toLowerCase().trim();let n=0;
    rows.forEach(function(item){
      const ok=(!q||item.dataset.search.includes(q))&&(!cat.value||item.dataset.category===cat.value);
      item.hidden=!ok;if(ok)n++;
    });
    count.textContent=n+' '+(n===1?'service':'services');
    empty.hidden=n!==0;
  }
  search.addEventListener('input',filter);
  cat.addEventListener('change',filter);
})();
</script>
