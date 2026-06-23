<?php
$intakeFormsList = is_array($intakeFormsList ?? null) ? $intakeFormsList : [];
$servicesForAttachment = is_array($servicesForAttachment ?? null) ? $servicesForAttachment : [];
$formStatusFilter = (string) ($formStatusFilter ?? 'all');
$formSearch = (string) ($formSearch ?? '');
$editIntakeForm = is_array($editIntakeForm ?? null) ? $editIntakeForm : null;
$formAction = (string) ($formAction ?? '');

$editing = $formAction === 'new' || $formAction === 'edit';
$attachedServices = [];
if ($editIntakeForm) {
    $attachedServices = list_attached_services_for_form((int) $editIntakeForm['id']);
}
$attachedByServiceId = [];
foreach ($attachedServices as $attachedService) {
    $attachedByServiceId[(int) ($attachedService['service_id'] ?? 0)] = $attachedService;
}

$defaultSchema = default_intake_schema_for_service('Service Intake');
$schemaPayload = $editIntakeForm
    ? (string) json_encode(normalize_intake_schema(is_array($editIntakeForm['schema'] ?? null) ? $editIntakeForm['schema'] : []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    : (string) json_encode($defaultSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Intake Forms</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(url('forms')) ?>">Forms list</a>
    <a class="btn btn-primary" href="<?= e(url('forms', ['action' => 'new'])) ?>">New intake form</a>
  </div>
</div>

<?php if ($editing): ?>
<div class="card mb-3">
  <div class="card-body">
    <h2 class="h5 mb-3"><?= $editIntakeForm ? 'Edit intake form' : 'Create intake form' ?></h2>

    <div class="border rounded p-3 mb-3 bg-light-subtle">
      <h3 class="h6 mb-2">AI Generate</h3>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Service name</label>
          <input class="form-control form-control-sm" id="aiServiceName" placeholder="Trademark Processing">
        </div>
        <div class="col-md-3">
          <label class="form-label">Jurisdiction</label>
          <input class="form-control form-control-sm" id="aiJurisdiction" value="Nigeria">
        </div>
        <div class="col-md-3">
          <label class="form-label">Target applicants</label>
          <input class="form-control form-control-sm" id="aiTargetApplicants" value="individual/company">
        </div>
        <div class="col-md-2">
          <label class="form-label">Steps (optional)</label>
          <input class="form-control form-control-sm" id="aiStepsCount" type="number" min="2" max="8">
        </div>
        <div class="col-12">
          <label class="form-label">Description / keywords</label>
          <textarea class="form-control form-control-sm" id="aiDescription" rows="2" placeholder="e.g. Nigeria trademark filing for individuals and SMEs"></textarea>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
          <button type="button" class="btn btn-sm btn-primary" id="aiGenerateBtn">Generate schema</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="aiSuggestBtn">Suggest field changes</button>
          <span class="small text-muted align-self-center">AI output can be edited before saving.</span>
        </div>
      </div>
    </div>

    <form method="post" action="<?= e(url('forms')) ?>" id="intakeFormBuilder">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="save_intake_form">
      <?php if ($editIntakeForm): ?>
        <input type="hidden" name="intake_form_id" value="<?= (int) $editIntakeForm['id'] ?>">
      <?php endif; ?>

      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <label class="form-label">Form title</label>
          <input class="form-control" name="title" id="intakeFormTitle" required value="<?= e((string) ($editIntakeForm['title'] ?? '')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <?php foreach (['active', 'draft', 'archived'] as $formStatus): ?>
              <option value="<?= e($formStatus) ?>" <?= selected_if($formStatus, (string) ($editIntakeForm['status'] ?? 'draft')) ?>><?= e(ucfirst($formStatus)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-grid">
          <button class="btn btn-outline-secondary mt-md-4" type="button" id="formatSchemaBtn">Format JSON</button>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="intakeFormDescription" rows="2"><?= e((string) ($editIntakeForm['description'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-7">
          <label class="form-label">Schema JSON</label>
          <textarea class="form-control font-monospace" name="schema_json" id="schemaJsonField" rows="24" required><?= e($schemaPayload) ?></textarea>
          <small class="text-muted">Supported types: text, textarea, select, multiselect, radio, checkbox, date, phone, email, number, file, repeating_group, address. For file fields, set <code>"multiple": true</code> to allow multiple uploads.</small>
        </div>
        <div class="col-lg-5">
          <div class="border rounded p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="h6 mb-0">Live preview</h3>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="refreshSchemaPreview">Refresh</button>
            </div>
            <div id="schemaPreviewWrap" class="small text-muted">No preview yet.</div>
          </div>
        </div>
      </div>

      <div class="border rounded p-3 mt-3">
        <h3 class="h6 mb-2">Attach to services</h3>
        <input type="hidden" name="attach_service_ids[]" value="">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Attach</th>
                <th>Service</th>
                <th>Required</th>
                <th>Order</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($servicesForAttachment as $service): ?>
                <?php $attached = $attachedByServiceId[(int) $service['id']] ?? null; ?>
                <tr>
                  <td>
                    <input
                      class="form-check-input form-attach-toggle"
                      type="checkbox"
                      name="attach_service_ids[]"
                      value="<?= (int) $service['id'] ?>"
                      <?= checked_if($attached !== null) ?>
                    >
                  </td>
                  <td>
                    <div class="fw-semibold"><?= e((string) $service['name']) ?></div>
                    <div class="small text-muted"><?= e(money((float) $service['price'], (string) ($service['currency'] ?? default_currency()))) ?></div>
                  </td>
                  <td>
                    <input
                      class="form-check-input"
                      type="checkbox"
                      name="attach_required[<?= (int) $service['id'] ?>]"
                      value="1"
                      <?= checked_if($attached ? (int) ($attached['is_required'] ?? 1) === 1 : true) ?>
                    >
                  </td>
                  <td><input class="form-control form-control-sm" style="max-width:92px;" type="number" min="1" name="attach_sort[<?= (int) $service['id'] ?>]" value="<?= e((string) ($attached['sort_order'] ?? 1)) ?>"></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$servicesForAttachment): ?>
                <tr><td colspan="4" class="text-muted text-center py-3">No services available yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit">Save form</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('forms')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form class="row g-2 align-items-end mb-3" method="get" action="index.php">
      <input type="hidden" name="page" value="forms">
      <div class="col-md-5">
        <label class="form-label">Search form</label>
        <input class="form-control" name="q" value="<?= e($formSearch) ?>" placeholder="Search by title/description">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="all" <?= selected_if('all', $formStatusFilter) ?>>All statuses</option>
          <option value="active" <?= selected_if('active', $formStatusFilter) ?>>Active</option>
          <option value="draft" <?= selected_if('draft', $formStatusFilter) ?>>Draft</option>
          <option value="archived" <?= selected_if('archived', $formStatusFilter) ?>>Archived</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-outline-primary" type="submit">Apply</button>
      </div>
      <div class="col-md-2 d-grid">
        <a class="btn btn-outline-secondary" href="<?= e(url('forms')) ?>">Reset</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-mobile-cards align-middle mb-0">
        <thead>
          <tr>
            <th>Form</th>
            <th>Status</th>
            <th>Attached Services</th>
            <th>Updated</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($intakeFormsList as $row): ?>
            <?php $attachedCount = (int) (db_one('SELECT COUNT(*) AS c FROM service_intake_forms WHERE intake_form_id = :id', ['id' => (int) $row['id']])['c'] ?? 0); ?>
            <tr>
              <td data-label="Form">
                <div class="fw-semibold"><?= e((string) ($row['title'] ?? '')) ?></div>
                <div class="small text-muted"><?= e((string) ($row['description'] ?? '')) ?></div>
              </td>
              <td data-label="Status"><?= status_badge((string) ($row['status'] ?? 'draft')) ?></td>
              <td data-label="Attached Services"><?= (int) $attachedCount ?></td>
              <td data-label="Updated"><small><?= e((string) ($row['updated_at'] ?? '')) ?></small></td>
              <td data-label="Action" class="text-end cell-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('forms', ['action' => 'edit', 'id' => (int) $row['id']])) ?>">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$intakeFormsList): ?>
            <tr class="table-empty-row"><td colspan="5" class="text-center text-muted py-4">No intake forms found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($editing): ?>
<script>
(function () {
  var schemaField = document.getElementById('schemaJsonField');
  var previewWrap = document.getElementById('schemaPreviewWrap');
  var formatBtn = document.getElementById('formatSchemaBtn');
  var refreshBtn = document.getElementById('refreshSchemaPreview');
  var aiGenerateBtn = document.getElementById('aiGenerateBtn');
  var aiSuggestBtn = document.getElementById('aiSuggestBtn');
  var aiServiceName = document.getElementById('aiServiceName');
  var aiJurisdiction = document.getElementById('aiJurisdiction');
  var aiTargetApplicants = document.getElementById('aiTargetApplicants');
  var aiDescription = document.getElementById('aiDescription');
  var aiStepsCount = document.getElementById('aiStepsCount');
  var formTitle = document.getElementById('intakeFormTitle');
  var formDescription = document.getElementById('intakeFormDescription');

  function parseSchema() {
    if (!schemaField) return null;
    try {
      return JSON.parse(schemaField.value || '{}');
    } catch (err) {
      return null;
    }
  }

  function renderPreview() {
    if (!previewWrap) return;
    var schema = parseSchema();
    if (!schema || !Array.isArray(schema.steps)) {
      previewWrap.innerHTML = '<div class="text-danger">Schema JSON is invalid.</div>';
      return;
    }

    if (!schema.steps.length) {
      previewWrap.innerHTML = '<div class="text-muted">No steps in schema.</div>';
      return;
    }

    var html = '';
    schema.steps.forEach(function (step, idx) {
      var fields = Array.isArray(step.fields) ? step.fields : [];
      html += '<div class="border rounded p-2 mb-2">';
      html += '<div class="fw-semibold">Step ' + (idx + 1) + ': ' + String(step.title || ('Step ' + (idx + 1))) + '</div>';
      if (step.description) {
        html += '<div class="text-muted small mb-1">' + String(step.description) + '</div>';
      }
      if (!fields.length) {
        html += '<div class="small text-muted">No fields.</div>';
      } else {
        html += '<ul class="small mb-0">';
        fields.forEach(function (field) {
          html += '<li><strong>' + String(field.label || field.key || 'Field') + '</strong> ';
          html += '<span class="text-muted">(' + String(field.type || 'text') + (field.required ? ', required' : '') + ')</span></li>';
        });
        html += '</ul>';
      }
      html += '</div>';
    });

    previewWrap.innerHTML = html;
  }

  function formatSchema() {
    var schema = parseSchema();
    if (!schema || !schemaField) return;
    schemaField.value = JSON.stringify(schema, null, 2);
    renderPreview();
  }

  function postJson(url, payload) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload || {})
    }).then(function (resp) {
      return resp.json().then(function (data) {
        if (!resp.ok || !data || data.success === false) {
          throw new Error((data && data.message) ? data.message : 'Request failed');
        }
        return data;
      });
    });
  }

  if (formatBtn) formatBtn.addEventListener('click', formatSchema);
  if (refreshBtn) refreshBtn.addEventListener('click', renderPreview);

  if (aiGenerateBtn) {
    aiGenerateBtn.addEventListener('click', function () {
      var payload = {
        serviceName: aiServiceName ? aiServiceName.value : '',
        jurisdiction: aiJurisdiction ? aiJurisdiction.value : 'Nigeria',
        description: aiDescription ? aiDescription.value : '',
        targetApplicantTypes: aiTargetApplicants ? aiTargetApplicants.value : 'individual/company',
        desiredStepsCount: aiStepsCount && aiStepsCount.value ? parseInt(aiStepsCount.value, 10) : null
      };

      aiGenerateBtn.disabled = true;
      postJson('api/ai/forms/generate-intake-schema/', payload)
        .then(function (data) {
          var output = data.output || {};
          if (formTitle && output.title) formTitle.value = output.title;
          if (formDescription && output.description) formDescription.value = output.description;
          if (schemaField && output.schema_json) {
            schemaField.value = JSON.stringify(output.schema_json, null, 2);
          }
          renderPreview();
        })
        .catch(function (err) {
          window.alert(err.message || 'AI generation failed.');
        })
        .finally(function () {
          aiGenerateBtn.disabled = false;
        });
    });
  }

  if (aiSuggestBtn) {
    aiSuggestBtn.addEventListener('click', function () {
      var schema = parseSchema();
      if (!schema) {
        window.alert('Fix schema JSON before requesting suggestions.');
        return;
      }

      var payload = {
        serviceName: aiServiceName ? aiServiceName.value : '',
        existingSchema: schema
      };

      aiSuggestBtn.disabled = true;
      postJson('api/ai/forms/suggest-fields/', payload)
        .then(function (data) {
          var diff = data.output && data.output.suggestedDiff ? data.output.suggestedDiff : {};
          var lines = [];
          if (Array.isArray(diff.additions) && diff.additions.length) {
            lines.push('Suggested additions:');
            diff.additions.forEach(function (row) {
              lines.push('- ' + (row.field || 'field') + ': ' + (row.reason || ''));
            });
          }
          if (Array.isArray(diff.notes) && diff.notes.length) {
            lines.push('');
            lines.push('Notes:');
            diff.notes.forEach(function (note) { lines.push('- ' + note); });
          }
          window.alert(lines.length ? lines.join('\n') : 'No suggestions returned.');
        })
        .catch(function (err) {
          window.alert(err.message || 'AI suggestion failed.');
        })
        .finally(function () {
          aiSuggestBtn.disabled = false;
        });
    });
  }

  renderPreview();
})();
</script>
<?php endif; ?>
