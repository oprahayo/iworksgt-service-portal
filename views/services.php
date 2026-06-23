<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Service Catalog</h1>
  <?php if (has_role(['admin'])): ?>
    <a class="btn btn-primary" href="<?= e(url('services', ['action' => 'new'])) ?>">Add service</a>
  <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/partials/admin-media-picker.php';

$intakeForms = is_array($intakeForms ?? null) ? $intakeForms : [];
$serviceIntakeAttachments = is_array($serviceIntakeAttachments ?? null) ? $serviceIntakeAttachments : [];
$mediaAssets = is_array($mediaAssets ?? null) ? $mediaAssets : [];
$attachedByFormId = [];
foreach ($serviceIntakeAttachments as $attachmentRow) {
    $attachedByFormId[(int) ($attachmentRow['intake_form_id'] ?? 0)] = $attachmentRow;
}
$servicesReturnUrl = url('services');
if ($serviceFormMode && $editService && !empty($editService['id'])) {
    $servicesReturnUrl = url('services', ['action' => 'edit', 'id' => (int) $editService['id']]);
}
?>

<?php if ($serviceFormMode && has_role(['admin'])): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><?= $editService ? 'Edit service' : 'New service' ?></h2>
    <form method="post" action="<?= e(url('services')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="save_service">
      <?php if ($editService): ?>
        <input type="hidden" name="service_id" value="<?= (int) $editService['id'] ?>">
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Service name</label>
          <input class="form-control" name="name" required value="<?= e($editService['name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Price</label>
          <input class="form-control" name="price" type="number" min="0" step="0.01" required value="<?= e($editService['price'] ?? '0') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Currency</label>
          <select class="form-select" name="currency" required>
            <?php $currentCurrency = $editService['currency'] ?? default_currency(); ?>
            <?php foreach (currency_options() as $currency): ?>
              <option value="<?= e($currency) ?>" <?= selected_if($currency, $currentCurrency) ?>><?= e($currency) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Commission %</label>
          <input class="form-control" name="commission_percent" type="number" min="0" max="100" step="0.01" required value="<?= e($editService['commission_percent'] ?? '0') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Tax %</label>
          <input class="form-control" name="tax_percent" type="number" min="0" max="100" step="0.01" required value="<?= e($editService['tax_percent'] ?? '0') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Public category</label>
          <input class="form-control" name="category" value="<?= e($editService['category'] ?? 'general') ?>" placeholder="business-setup">
          <small class="text-muted">Used for the public frontend service grouping.</small>
        </div>
        <div class="col-md-9">
          <label class="form-label">Short description</label>
          <input class="form-control" name="short_description" maxlength="255" value="<?= e($editService['short_description'] ?? '') ?>" placeholder="Short summary shown on the website and catalog cards.">
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="5" placeholder="<p>Short intro</p><ul><li>Feature one</li><li>Feature two</li></ul>"><?= e($editService['description'] ?? '') ?></textarea>
          <small class="text-muted">Supports clean HTML formatting: paragraph, list, bold, links.</small>
        </div>
        <div class="col-md-8">
          <label class="form-label">Featured bullets</label>
          <textarea class="form-control" name="featured_bullets_text" rows="4" placeholder="Fast filing support&#10;Dedicated account guidance&#10;Remote document handling"><?= e(implode("\n", is_array($editService['featured_bullets'] ?? null) ? $editService['featured_bullets'] : [])) ?></textarea>
          <small class="text-muted">One bullet per line. Used on the website feature cards and service highlights.</small>
        </div>
        <div class="col-md-4">
          <label class="form-label">Featured image</label>
          <?php render_admin_media_picker(
              $mediaAssets,
              'featured_image_id',
              (int) ($editService['featured_image_id'] ?? 0),
              'No featured image selected.',
              'Open media library'
          ); ?>
          <small class="text-muted">Select from media library. Upload new images from the same popup.</small>
        </div>
        <div class="col-12">
          <div class="row g-2">
            <div class="col-sm-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveService" <?= checked_if((bool) ($editService['is_active'] ?? true)) ?>>
                <label class="form-check-label" for="isActiveService">Active service</label>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_public" id="isPublicService" <?= checked_if((bool) ($editService['is_public'] ?? true)) ?>>
                <label class="form-check-label" for="isPublicService">Visible on public website</label>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedService" <?= checked_if((bool) ($editService['is_featured'] ?? false)) ?>>
                <label class="form-check-label" for="isFeaturedService">Feature on home page</label>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Intake forms</label>
          <?php if ($intakeForms): ?>
            <div class="border rounded p-2">
              <div class="small text-muted mb-2">Attach one or more dynamic intake forms required for this service.</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                  <tr>
                    <th>Attach</th>
                    <th>Form</th>
                    <th>Required</th>
                    <th>Order</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($intakeForms as $form): ?>
                    <?php $attached = $attachedByFormId[(int) $form['id']] ?? null; ?>
                    <tr>
                      <td>
                        <input
                          class="form-check-input intake-attach-toggle"
                          type="checkbox"
                          name="intake_form_ids[]"
                          value="<?= (int) $form['id'] ?>"
                          id="intakeAttach<?= (int) $form['id'] ?>"
                          <?= checked_if($attached !== null) ?>
                          data-row-target="#intakeRow<?= (int) $form['id'] ?>"
                        >
                      </td>
                      <td>
                        <label class="form-check-label fw-semibold" for="intakeAttach<?= (int) $form['id'] ?>"><?= e((string) $form['title']) ?></label>
                        <div class="small text-muted"><?= e((string) ($form['description'] ?? '')) ?></div>
                      </td>
                      <td>
                        <input
                          class="form-check-input intake-required-check"
                          type="checkbox"
                          name="intake_required[<?= (int) $form['id'] ?>]"
                          value="1"
                          <?= checked_if($attached ? (int) ($attached['is_required'] ?? 1) === 1 : true) ?>
                        >
                      </td>
                      <td>
                        <input
                          class="form-control form-control-sm"
                          style="max-width:92px;"
                          type="number"
                          name="intake_sort[<?= (int) $form['id'] ?>]"
                          value="<?= e((string) ($attached['sort_order'] ?? 1)) ?>"
                          min="1"
                        >
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mb-0">
              No intake forms yet.
              <a href="<?= e(url('forms', ['action' => 'new'])) ?>" class="alert-link">Create an intake form first</a>.
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary" type="submit">Save service</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('services')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php if (has_role(['admin'])): ?>
  <?php render_admin_media_modal($mediaAssets, [
      'return_to' => $servicesReturnUrl,
  ]); ?>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
  document.querySelectorAll('.intake-attach-toggle').forEach(function (toggle) {
    function syncRow() {
      var row = toggle.closest('tr');
      if (!row) return;
      var required = row.querySelector('.intake-required-check');
      if (required) {
        required.disabled = !toggle.checked;
      }
      row.classList.toggle('opacity-50', !toggle.checked);
    }

    toggle.addEventListener('change', syncRow);
    syncRow();
  });
})();
</script>

<div class="card service-catalog-card">
  <div class="table-responsive">
    <table class="table table-mobile-cards table-hover align-middle mb-0 table-borderless service-catalog-table">
      <thead>
        <tr>
          <th>Service</th>
          <th>Category</th>
          <th>Price</th>
          <th>Tax</th>
          <th>Commission</th>
          <th>Status</th>
          <th>Website</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($services as $service): ?>
        <tr>
          <td data-label="Service">
            <div class="fw-semibold">
              <?php if (has_role(['admin'])): ?>
                <a class="service-title-link" href="<?= e(url('services', ['action' => 'edit', 'id' => (int) $service['id']])) ?>"><?= e($service['name']) ?></a>
              <?php else: ?>
                <?= e($service['name']) ?>
              <?php endif; ?>
            </div>
            <div class="text-muted small"><?= e((string) ($service['short_description'] ?: (strlen(trim(strip_tags((string) $service['description']))) > 90 ? substr(trim(strip_tags((string) $service['description'])), 0, 87) . '...' : trim(strip_tags((string) $service['description']))))) ?></div>
          </td>
          <td data-label="Category"><?= e(ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')))) ?></td>
          <td data-label="Price"><?= e(money((float) $service['price'], $service['currency'] ?? default_currency())) ?></td>
          <td data-label="Tax"><?= e(number_format((float) ($service['tax_percent'] ?? 0), 2)) ?>%</td>
          <td data-label="Commission"><?= e(number_format((float) $service['commission_percent'], 2)) ?>%</td>
          <td data-label="Status"><?= status_badge((int) $service['is_active'] === 1 ? 'active' : 'inactive') ?></td>
          <td data-label="Website">
            <div class="d-flex flex-wrap gap-1">
              <?= status_badge((int) ($service['is_public'] ?? 1) === 1 ? 'public' : 'private') ?>
              <?php if ((int) ($service['is_featured'] ?? 0) === 1): ?>
                <?= status_badge('featured') ?>
              <?php endif; ?>
            </div>
          </td>
          <td data-label="Action" class="text-end cell-actions">
            <?php if (has_role(['admin'])): ?>
              <div class="d-inline-flex gap-1 align-items-center flex-wrap justify-content-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('services', ['action' => 'edit', 'id' => (int) $service['id']])) ?>">Edit</a>
                <form method="post" action="<?= e(url('services')) ?>" onsubmit="return confirm('Delete this service? This cannot be undone.');" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_service">
                  <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$services): ?>
        <tr class="table-empty-row"><td colspan="8" class="text-center text-muted py-4">No services yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
