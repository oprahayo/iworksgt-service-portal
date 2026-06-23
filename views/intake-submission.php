<?php
$submission = $intakeSubmission;
$schema = normalize_intake_schema(is_array($submission['schema_snapshot'] ?? null) ? $submission['schema_snapshot'] : []);
$steps = is_array($schema['steps'] ?? null) ? $schema['steps'] : [];
$answers = is_array($submission['data'] ?? null) ? $submission['data'] : [];
$files = is_array($submission['files'] ?? null) ? $submission['files'] : [];
$user = current_user();

$isClientOwner = (string) ($user['role'] ?? '') === 'client' && (int) ($submission['client_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
$canClientEdit = $isClientOwner && intake_client_can_edit($submission);
$showClientStatusOnly = $isClientOwner && !$canClientEdit && (string) ($submission['status'] ?? 'draft') === 'submitted';
$isAgentAdminViewer = role_has_admin_or_finance_access((string) ($user['role'] ?? '')) || (string) ($user['role'] ?? '') === 'agent';

$requiredDocFields = [];
foreach ($steps as $step) {
    $stepFields = is_array($step['fields'] ?? null) ? $step['fields'] : [];
    foreach ($stepFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = trim((string) ($field['key'] ?? ''));
        if ($fieldKey === '' || strtolower((string) ($field['type'] ?? 'text')) !== 'file' || empty($field['required'])) {
            continue;
        }
        $requiredDocFields[] = [
            'key' => $fieldKey,
            'label' => (string) ($field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey))),
        ];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h1 class="h4 mb-0"><?= e((string) ($submission['form_title'] ?? 'Intake Form')) ?></h1>
    <div class="small text-muted">Job: <?= e((string) ($submission['job_code'] ?? '')) ?> | Service: <?= e((string) ($submission['service_name'] ?? '')) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(url('job', ['id' => (int) $submission['job_id']])) ?>">Back to job</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">Intake status</h2>
          <?= status_badge((string) ($submission['status'] ?? 'draft')) ?>
        </div>

        <?php if (!empty($submission['update_request_note'])): ?>
          <div class="alert alert-warning small">
            <strong>Update requested:</strong> <?= e((string) $submission['update_request_note']) ?>
          </div>
        <?php endif; ?>

        <?php if ($showClientStatusOnly): ?>
          <div class="alert alert-success mb-0">
            Form attended to / submitted. Your submission has been received.
          </div>
        <?php elseif ($canClientEdit): ?>
          <form method="post" action="<?= e(url('intake_submission', ['id' => (int) $submission['id']])) ?>" enctype="multipart/form-data" id="intakeSubmissionForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="submit_intake" id="intakeFormActionField">
            <input type="hidden" name="ajax" value="0" id="intakeAjaxField">

            <?php if ($requiredDocFields !== []): ?>
              <div class="intake-doc-checklist mb-3" id="intakeRequiredDocsChecklist">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                  <div class="fw-semibold">Required documents checklist</div>
                  <span class="badge text-bg-light" id="intakeRequiredDocsRemaining">0 remaining</span>
                </div>
                <ul class="list-unstyled mb-0 small">
                  <?php foreach ($requiredDocFields as $docField): ?>
                    <li class="intake-doc-item" data-doc-key="<?= e((string) $docField['key']) ?>">
                      <span class="intake-doc-dot" aria-hidden="true"></span>
                      <span class="flex-grow-1"><?= e((string) $docField['label']) ?></span>
                      <span class="intake-doc-state badge text-bg-secondary">Pending</span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <div class="intake-step-progress mb-3">
              <?php foreach ($steps as $stepIndex => $step): ?>
                <span class="badge <?= $stepIndex === 0 ? 'text-bg-primary' : 'text-bg-light' ?> intake-step-chip" data-step-chip="<?= (int) $stepIndex ?>">
                  <?= (int) ($stepIndex + 1) ?>. <?= e((string) ($step['title'] ?? 'Step')) ?>
                </span>
              <?php endforeach; ?>
            </div>

            <?php foreach ($steps as $stepIndex => $step): ?>
              <?php $fields = is_array($step['fields'] ?? null) ? $step['fields'] : []; ?>
              <section class="intake-step-panel <?= $stepIndex === 0 ? '' : 'd-none' ?>" data-step-panel="<?= (int) $stepIndex ?>">
                <h3 class="h6"><?= e((string) ($step['title'] ?? ('Step ' . ($stepIndex + 1)))) ?></h3>
                <?php if (!empty($step['description'])): ?>
                  <p class="small text-muted"><?= e((string) $step['description']) ?></p>
                <?php endif; ?>

                <div class="row g-2">
                  <?php foreach ($fields as $field): ?>
                    <?php
                    $fieldKey = (string) ($field['key'] ?? '');
                    if ($fieldKey === '') {
                        continue;
                    }
                    $fieldType = strtolower((string) ($field['type'] ?? 'text'));
                    $fieldLabel = (string) ($field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)));
                    $fieldRequired = !empty($field['required']);
                    $fieldValue = $answers[$fieldKey] ?? '';
                    $fieldHelp = (string) ($field['helpText'] ?? '');
                    $fieldPlaceholder = (string) ($field['placeholder'] ?? '');
                    $fieldOptions = is_array($field['options'] ?? null) ? $field['options'] : [];
                    $fieldConditions = is_array($field['conditions'] ?? null) ? $field['conditions'] : [];
                    $fieldValidation = is_array($field['validation'] ?? null) ? $field['validation'] : [];
                    $fieldMultiple = $fieldType === 'file' ? !empty($field['multiple']) : false;
                    $showAsOptionalUpload = $fieldType === 'file' && !$fieldRequired;
                    $conditionsPayload = '';
                    $validationPayload = '';
                    if ($fieldConditions !== []) {
                        $conditionsPayload = (string) base64_encode((string) json_encode($fieldConditions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    }
                    if ($fieldValidation !== []) {
                        $validationPayload = (string) base64_encode((string) json_encode($fieldValidation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    }
                    if ($fieldHelp === '' && $fieldType === 'phone') {
                        $fieldHelp = 'Use +2348012345678 (E.164) or 08012345678 (Nigeria).';
                    } elseif ($fieldHelp === '' && $fieldType === 'email') {
                        $fieldHelp = 'Example: name@example.com';
                    } elseif ($fieldHelp === '' && $fieldType === 'date') {
                        $fieldHelp = 'Use a valid date.';
                    }
                    ?>
                    <div
                      class="col-12 intake-field-wrap<?= $showAsOptionalUpload ? ' intake-field-optional-upload' : '' ?>"
                      data-field-key="<?= e($fieldKey) ?>"
                      data-field-type="<?= e($fieldType) ?>"
                      data-required="<?= $fieldRequired ? '1' : '0' ?>"
                      data-label="<?= e($fieldLabel) ?>"
                      <?= $fieldMultiple ? ' data-multiple="1"' : ' data-multiple="0"' ?>
                      <?= $conditionsPayload !== '' ? ' data-conditions="' . e($conditionsPayload) . '"' : '' ?>
                      <?= $validationPayload !== '' ? ' data-validation="' . e($validationPayload) . '"' : '' ?>
                    >
                      <?php if ($showAsOptionalUpload): ?>
                        <details class="intake-optional-upload-toggle">
                          <summary>Optional upload: <?= e($fieldLabel) ?></summary>
                          <div class="pt-2">
                      <?php endif; ?>
                      <label class="form-label" for="intakeField_<?= e($fieldKey) ?>">
                        <?= e($fieldLabel) ?><?= $fieldRequired ? ' *' : '' ?>
                      </label>

                      <?php if ($fieldType === 'textarea'): ?>
                        <textarea
                          class="form-control intake-answer-input"
                          id="intakeField_<?= e($fieldKey) ?>"
                          name="answers[<?= e($fieldKey) ?>]"
                          rows="3"
                          placeholder="<?= e($fieldPlaceholder) ?>"
                          <?= $fieldRequired ? 'required data-required="1"' : '' ?>
                        ><?= e((string) $fieldValue) ?></textarea>
                      <?php elseif ($fieldType === 'select'): ?>
                        <select class="form-select intake-answer-input" id="intakeField_<?= e($fieldKey) ?>" name="answers[<?= e($fieldKey) ?>]" <?= $fieldRequired ? 'required data-required="1"' : '' ?>>
                          <option value="">Select option</option>
                          <?php foreach ($fieldOptions as $opt): ?>
                            <option value="<?= e((string) $opt) ?>" <?= selected_if((string) $opt, (string) $fieldValue) ?>><?= e((string) $opt) ?></option>
                          <?php endforeach; ?>
                        </select>
                      <?php elseif ($fieldType === 'multiselect'): ?>
                        <?php $selectedValues = is_array($fieldValue) ? $fieldValue : []; ?>
                        <select class="form-select intake-answer-input" id="intakeField_<?= e($fieldKey) ?>" name="answers[<?= e($fieldKey) ?>][]" multiple <?= $fieldRequired ? 'data-required="1"' : '' ?>>
                          <?php foreach ($fieldOptions as $opt): ?>
                            <option value="<?= e((string) $opt) ?>" <?= in_array((string) $opt, array_map('strval', $selectedValues), true) ? 'selected' : '' ?>><?= e((string) $opt) ?></option>
                          <?php endforeach; ?>
                        </select>
                      <?php elseif ($fieldType === 'radio'): ?>
                        <div class="d-flex flex-wrap gap-2">
                          <?php foreach ($fieldOptions as $idx => $opt): ?>
                            <div class="form-check">
                              <input
                                class="form-check-input intake-answer-input"
                                type="radio"
                                id="intakeField_<?= e($fieldKey) ?>_<?= (int) $idx ?>"
                                name="answers[<?= e($fieldKey) ?>]"
                                value="<?= e((string) $opt) ?>"
                                <?= selected_if((string) $opt, (string) $fieldValue) ?>
                                <?= $fieldRequired ? 'required data-required="1"' : '' ?>
                              >
                              <label class="form-check-label" for="intakeField_<?= e($fieldKey) ?>_<?= (int) $idx ?>"><?= e((string) $opt) ?></label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php elseif ($fieldType === 'checkbox'): ?>
                        <?php $valueList = is_array($fieldValue) ? $fieldValue : (trim((string) $fieldValue) !== '' ? [(string) $fieldValue] : []); ?>
                        <div class="d-flex flex-wrap gap-2">
                          <?php foreach ($fieldOptions as $idx => $opt): ?>
                            <div class="form-check">
                              <input
                                class="form-check-input intake-answer-input"
                                type="checkbox"
                                id="intakeField_<?= e($fieldKey) ?>_<?= (int) $idx ?>"
                                name="answers[<?= e($fieldKey) ?>][]"
                                value="<?= e((string) $opt) ?>"
                                <?= in_array((string) $opt, array_map('strval', $valueList), true) ? 'checked' : '' ?>
                                <?= $fieldRequired ? 'data-required="1"' : '' ?>
                              >
                              <label class="form-check-label" for="intakeField_<?= e($fieldKey) ?>_<?= (int) $idx ?>"><?= e((string) $opt) ?></label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php elseif ($fieldType === 'file'): ?>
                        <div class="intake-file-uploader" data-file-uploader data-field-key="<?= e($fieldKey) ?>" data-multiple="<?= $fieldMultiple ? '1' : '0' ?>">
                          <input
                            class="d-none intake-answer-input js-intake-file-input"
                            type="file"
                            id="intakeField_<?= e($fieldKey) ?>"
                            name="files[<?= e($fieldKey) ?>]<?= $fieldMultiple ? '[]' : '' ?>"
                            accept=".png,.jpg,.jpeg,.pdf"
                            <?= $fieldMultiple ? 'multiple' : '' ?>
                          >
                          <div class="intake-file-dropzone" data-file-dropzone tabindex="0" role="button" aria-controls="intakeField_<?= e($fieldKey) ?>">
                            <div class="fw-semibold small">Drag and drop <?= $fieldMultiple ? 'files' : 'a file' ?> here</div>
                            <div class="small text-muted">or click to browse (PNG, JPG, JPEG, PDF up to 12MB each)</div>
                          </div>
                          <div class="intake-upload-list mt-2" data-selected-file-list></div>

                          <?php if (!empty($files[$fieldKey]) && is_array($files[$fieldKey])): ?>
                            <div class="intake-existing-file-list mt-2" data-existing-file-list>
                              <div class="small text-muted mb-1">Existing uploads</div>
                              <?php foreach ($files[$fieldKey] as $fileIndex => $entry): ?>
                                <div class="intake-existing-file-row" data-existing-file-row>
                                  <a class="text-decoration-none flex-grow-1" href="download.php?intake_submission_id=<?= (int) $submission['id'] ?>&field_key=<?= urlencode((string) $fieldKey) ?>&file_index=<?= (int) $fileIndex ?>">
                                    <?= e((string) ($entry['original_name'] ?? ('File ' . ($fileIndex + 1)))) ?>
                                  </a>
                                  <label class="form-check-label small text-muted">
                                    <input
                                      class="form-check-input me-1 js-intake-remove-file"
                                      type="checkbox"
                                      name="remove_files[<?= e($fieldKey) ?>][]"
                                      value="<?= (int) $fileIndex ?>"
                                    >
                                    Remove
                                  </label>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php elseif ($fieldType === 'address'): ?>
                        <?php $addressValue = is_array($fieldValue) ? $fieldValue : []; ?>
                        <div class="row g-2">
                          <div class="col-md-6">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][line1]" value="<?= e((string) ($addressValue['line1'] ?? '')) ?>" placeholder="Address line 1" <?= $fieldRequired ? 'required data-required="1"' : '' ?>>
                          </div>
                          <div class="col-md-6">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][line2]" value="<?= e((string) ($addressValue['line2'] ?? '')) ?>" placeholder="Address line 2 (optional)">
                          </div>
                          <div class="col-md-4">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][city]" value="<?= e((string) ($addressValue['city'] ?? '')) ?>" placeholder="City">
                          </div>
                          <div class="col-md-4">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][state]" value="<?= e((string) ($addressValue['state'] ?? '')) ?>" placeholder="State / Region">
                          </div>
                          <div class="col-md-4">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][country]" value="<?= e((string) ($addressValue['country'] ?? '')) ?>" placeholder="Country">
                          </div>
                          <div class="col-md-4">
                            <input class="form-control intake-answer-input" type="text" name="answers[<?= e($fieldKey) ?>][postal_code]" value="<?= e((string) ($addressValue['postal_code'] ?? '')) ?>" placeholder="Postal code">
                          </div>
                        </div>
                      <?php elseif ($fieldType === 'repeating_group'): ?>
                        <?php
                        $groupFields = is_array($field['fields'] ?? null) && $field['fields'] !== [] ? $field['fields'] : [
                            ['key' => 'item', 'label' => 'Item', 'type' => 'text', 'required' => true],
                        ];
                        $groupRows = [];
                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $rowValue) {
                                if (is_array($rowValue)) {
                                    $groupRows[] = $rowValue;
                                }
                            }
                        }
                        if ($groupRows === []) {
                            $groupRows = [[]];
                        }
                        ?>
                        <div
                          class="intake-repeating-group"
                          data-group-key="<?= e($fieldKey) ?>"
                          data-group-required="<?= $fieldRequired ? '1' : '0' ?>"
                          data-group-template="<?= e((string) base64_encode((string) json_encode($groupFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))) ?>"
                        >
                          <div class="intake-repeating-rows" data-group-rows>
                            <?php foreach ($groupRows as $rowIndex => $rowValue): ?>
                              <div class="border rounded p-2 mb-2 repeating-group-row" data-group-row>
                                <div class="row g-2">
                                  <?php foreach ($groupFields as $groupField): ?>
                                    <?php
                                    $groupFieldKey = (string) ($groupField['key'] ?? '');
                                    if ($groupFieldKey === '') {
                                        continue;
                                    }
                                    $groupFieldType = strtolower((string) ($groupField['type'] ?? 'text'));
                                    $groupFieldLabel = (string) ($groupField['label'] ?? ucfirst(str_replace('_', ' ', $groupFieldKey)));
                                    $groupFieldRequired = !empty($groupField['required']);
                                    $groupFieldValue = is_array($rowValue) ? ($rowValue[$groupFieldKey] ?? '') : '';
                                    $groupFieldOptions = is_array($groupField['options'] ?? null) ? $groupField['options'] : [];
                                    ?>
                                    <div class="col-md-6">
                                      <label class="form-label"><?= e($groupFieldLabel) ?><?= $groupFieldRequired ? ' *' : '' ?></label>
                                      <?php if ($groupFieldType === 'textarea'): ?>
                                        <textarea class="form-control intake-answer-input" name="answers[<?= e($fieldKey) ?>][<?= (int) $rowIndex ?>][<?= e($groupFieldKey) ?>]" rows="2" <?= $groupFieldRequired ? 'required data-required="1"' : '' ?>><?= e((string) $groupFieldValue) ?></textarea>
                                      <?php elseif ($groupFieldType === 'select'): ?>
                                        <select class="form-select intake-answer-input" name="answers[<?= e($fieldKey) ?>][<?= (int) $rowIndex ?>][<?= e($groupFieldKey) ?>]" <?= $groupFieldRequired ? 'required data-required="1"' : '' ?>>
                                          <option value="">Select option</option>
                                          <?php foreach ($groupFieldOptions as $opt): ?>
                                            <option value="<?= e((string) $opt) ?>" <?= selected_if((string) $opt, (string) $groupFieldValue) ?>><?= e((string) $opt) ?></option>
                                          <?php endforeach; ?>
                                        </select>
                                      <?php else: ?>
                                        <?php $groupInputType = in_array($groupFieldType, ['date', 'email', 'number', 'tel'], true) ? $groupFieldType : ($groupFieldType === 'phone' ? 'tel' : 'text'); ?>
                                        <input class="form-control intake-answer-input" type="<?= e($groupInputType) ?>" name="answers[<?= e($fieldKey) ?>][<?= (int) $rowIndex ?>][<?= e($groupFieldKey) ?>]" value="<?= e((string) $groupFieldValue) ?>" <?= $groupFieldRequired ? 'required data-required="1"' : '' ?>>
                                      <?php endif; ?>
                                    </div>
                                  <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 js-remove-repeating-row">Remove row</button>
                              </div>
                            <?php endforeach; ?>
                          </div>
                          <button type="button" class="btn btn-sm btn-outline-secondary js-add-repeating-row">Add row</button>
                        </div>
                      <?php else: ?>
                        <?php $inputType = in_array($fieldType, ['date', 'email', 'number'], true) ? $fieldType : ($fieldType === 'phone' ? 'tel' : 'text'); ?>
                        <input
                          class="form-control intake-answer-input"
                          id="intakeField_<?= e($fieldKey) ?>"
                          type="<?= e($inputType) ?>"
                          name="answers[<?= e($fieldKey) ?>]"
                          value="<?= e(is_array($fieldValue) ? implode(', ', array_map('strval', $fieldValue)) : (string) $fieldValue) ?>"
                          placeholder="<?= e($fieldPlaceholder) ?>"
                          <?= $fieldRequired ? 'required data-required="1"' : '' ?>
                        >
                      <?php endif; ?>

                      <?php if ($fieldHelp !== ''): ?>
                        <small class="text-muted d-block intake-help-text"><?= e($fieldHelp) ?></small>
                      <?php endif; ?>
                      <div class="invalid-feedback d-block d-none intake-inline-error" data-inline-error></div>
                      <?php if ($showAsOptionalUpload): ?>
                          </div>
                        </details>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
              <div class="small text-muted" id="autosaveStatus">Draft autosave is enabled.</div>
              <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-secondary" type="button" id="intakePrevStepBtn">Back</button>
                <button class="btn btn-outline-primary" type="button" id="intakeNextStepBtn">Next</button>
                <button class="btn btn-outline-secondary" type="submit" data-submit-action="autosave_intake">Save draft</button>
                <button class="btn btn-primary" type="submit" data-submit-action="submit_intake">Submit form</button>
              </div>
            </div>
          </form>
        <?php else: ?>
          <div class="alert alert-info mb-0">
            This intake form is currently read-only.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <?php if ($isAgentAdminViewer): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6">Submission details</h2>
          <?php foreach ($steps as $step): ?>
            <?php $fields = is_array($step['fields'] ?? null) ? $step['fields'] : []; ?>
            <div class="mb-3">
              <div class="fw-semibold"><?= e((string) ($step['title'] ?? 'Step')) ?></div>
              <?php foreach ($fields as $field): ?>
                <?php
                $fieldKey = (string) ($field['key'] ?? '');
                if ($fieldKey === '') {
                    continue;
                }
                $value = $answers[$fieldKey] ?? null;
                ?>
                <div class="small mt-1">
                  <strong><?= e((string) ($field['label'] ?? $fieldKey)) ?>:</strong>
                  <?php if ($showClientStatusOnly): ?>
                    <span class="text-muted">Hidden for client view</span>
                  <?php elseif (strtolower((string) ($field['type'] ?? '')) === 'file'): ?>
                    <?php if (!empty($files[$fieldKey]) && is_array($files[$fieldKey])): ?>
                      <?php foreach ($files[$fieldKey] as $fileIndex => $entry): ?>
                        <a class="d-block text-decoration-none" href="download.php?intake_submission_id=<?= (int) $submission['id'] ?>&field_key=<?= urlencode((string) $fieldKey) ?>&file_index=<?= (int) $fileIndex ?>">
                          <?= e((string) ($entry['original_name'] ?? ('File ' . ($fileIndex + 1)))) ?>
                        </a>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-muted">No file uploaded.</span>
                    <?php endif; ?>
                  <?php elseif (strtolower((string) ($field['type'] ?? '')) === 'address'): ?>
                    <?php if (is_array($value) && $value !== []): ?>
                      <?php
                      $parts = array_filter([
                          trim((string) ($value['line1'] ?? '')),
                          trim((string) ($value['line2'] ?? '')),
                          trim((string) ($value['city'] ?? '')),
                          trim((string) ($value['state'] ?? '')),
                          trim((string) ($value['country'] ?? '')),
                          trim((string) ($value['postal_code'] ?? '')),
                      ], static fn($item): bool => $item !== '');
                      ?>
                      <?= e($parts ? implode(', ', $parts) : '-') ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  <?php elseif (strtolower((string) ($field['type'] ?? '')) === 'repeating_group'): ?>
                    <?php
                    $rows = [];
                    if (is_array($value)) {
                        foreach ($value as $rowValue) {
                            if (is_array($rowValue)) {
                                $rows[] = $rowValue;
                            }
                        }
                    }
                    ?>
                    <?php if ($rows): ?>
                      <?php foreach ($rows as $rowIndex => $rowValue): ?>
                        <?php $parts = []; ?>
                        <?php foreach ($rowValue as $rowKey => $rowVal): ?>
                          <?php
                          $rowText = is_array($rowVal)
                              ? implode(', ', array_map('strval', $rowVal))
                              : trim((string) $rowVal);
                          if ($rowText === '') {
                              continue;
                          }
                          $parts[] = ucfirst(str_replace('_', ' ', (string) $rowKey)) . ': ' . $rowText;
                          ?>
                        <?php endforeach; ?>
                        <div>Row <?= (int) ($rowIndex + 1) ?> - <?= e($parts ? implode(' | ', $parts) : '-') ?></div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-muted">No rows submitted.</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <?php if (is_array($value)): ?>
                      <?= e(implode(', ', array_map('strval', $value))) ?>
                    <?php else: ?>
                      <?= e((string) ($value ?? '-')) ?>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6">Request update</h2>
          <form method="post" action="<?= e(url('intake_submission', ['id' => (int) $submission['id']])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="request_update">
            <label class="form-label">Note to client</label>
            <textarea class="form-control" name="update_note" rows="3" placeholder="Specify what the client needs to correct."></textarea>
            <button class="btn btn-outline-primary mt-2" type="submit">Request update</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <h2 class="h6">Activity log</h2>
        <?php foreach ($intakeEvents as $event): ?>
          <div class="timeline-item">
            <div class="small text-muted"><?= e((string) ($event['created_at'] ?? '')) ?></div>
            <div><strong><?= e((string) ($event['actor_name'] ?? 'System')) ?></strong> <?= e((string) ($event['event_type'] ?? 'updated')) ?></div>
            <?php if (!empty($event['note'])): ?><div class="small"><?= e((string) $event['note']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$intakeEvents): ?>
          <div class="small text-muted">No activity yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canClientEdit): ?>
<script>
(function () {
  var form = document.getElementById('intakeSubmissionForm');
  if (!form) return;

  var panels = Array.prototype.slice.call(document.querySelectorAll('[data-step-panel]'));
  var chips = Array.prototype.slice.call(document.querySelectorAll('[data-step-chip]'));
  var fieldWraps = Array.prototype.slice.call(form.querySelectorAll('.intake-field-wrap'));
  var prevBtn = document.getElementById('intakePrevStepBtn');
  var nextBtn = document.getElementById('intakeNextStepBtn');
  var autosaveStatus = document.getElementById('autosaveStatus');
  var formActionField = document.getElementById('intakeFormActionField');
  var ajaxField = document.getElementById('intakeAjaxField');
  var requiredDocChecklist = document.getElementById('intakeRequiredDocsChecklist');
  var requiredDocsRemaining = document.getElementById('intakeRequiredDocsRemaining');
  var currentStep = 0;
  var autosaveTimer = null;
  var uploaderStates = {};

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function decodePayload(payload) {
    if (!payload) return null;
    try {
      var decoded = window.atob(payload);
      var parsed = JSON.parse(decoded);
      return parsed;
    } catch (err) {
      return null;
    }
  }

  function formatFileSize(bytes) {
    var size = Number(bytes || 0);
    if (!isFinite(size) || size <= 0) return '0 KB';
    if (size < 1024) return size + ' B';
    if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB';
    return (size / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function makeDataTransfer() {
    try {
      return new DataTransfer();
    } catch (err) {
      return null;
    }
  }

  function collectSimpleValues() {
    var values = {};
    var body = new FormData(form);
    body.forEach(function (value, key) {
      var match = String(key || '').match(/^answers\[([^\]]+)\](\[\])?$/);
      if (!match) return;
      var fieldKey = String(match[1] || '');
      if (fieldKey === '') return;

      if (Object.prototype.hasOwnProperty.call(values, fieldKey)) {
        if (!Array.isArray(values[fieldKey])) {
          values[fieldKey] = [values[fieldKey]];
        }
        values[fieldKey].push(value);
      } else {
        values[fieldKey] = value;
      }
    });

    return values;
  }

  function fieldValueIsEmpty(value) {
    if (Array.isArray(value)) {
      if (!value.length) return true;
      return value.every(function (item) { return fieldValueIsEmpty(item); });
    }
    if (value && typeof value === 'object') {
      return Object.keys(value).every(function (key) { return fieldValueIsEmpty(value[key]); });
    }
    return String(value === undefined || value === null ? '' : value).trim() === '';
  }

  function normalizeArray(input) {
    if (Array.isArray(input)) return input;
    if (input === null || input === undefined || input === '') return [];
    return [input];
  }

  function evaluateRule(rule, values) {
    if (!rule || typeof rule !== 'object') return true;
    var key = String(rule.field || rule.key || rule.dependsOn || rule.fieldKey || '');
    if (key === '') return true;
    var operator = String(rule.operator || rule.op || 'equals').toLowerCase();
    var expected = rule.value;
    var actual = values[key];

    if (Array.isArray(actual)) {
      if (operator === 'equals') return actual.indexOf(expected) !== -1;
      if (operator === 'not_equals') return actual.indexOf(expected) === -1;
      if (operator === 'in') return normalizeArray(expected).some(function (candidate) { return actual.indexOf(candidate) !== -1; });
      if (operator === 'not_in') return normalizeArray(expected).every(function (candidate) { return actual.indexOf(candidate) === -1; });
      if (operator === 'contains') return actual.join(' ').toLowerCase().indexOf(String(expected || '').toLowerCase()) !== -1;
      if (operator === 'empty') return actual.length === 0;
      if (operator === 'not_empty' || operator === 'truthy') return actual.length > 0;
      if (operator === 'falsy') return actual.length === 0;
      return true;
    }

    var actualString = actual === undefined || actual === null ? '' : String(actual);
    var expectedArray = normalizeArray(expected).map(function (item) { return String(item); });
    if (operator === 'equals') return actualString === String(expected);
    if (operator === 'not_equals') return actualString !== String(expected);
    if (operator === 'in') return expectedArray.indexOf(actualString) !== -1;
    if (operator === 'not_in') return expectedArray.indexOf(actualString) === -1;
    if (operator === 'contains') return actualString.toLowerCase().indexOf(String(expected || '').toLowerCase()) !== -1;
    if (operator === 'empty' || operator === 'falsy') return actualString === '';
    if (operator === 'not_empty' || operator === 'truthy') return actualString !== '';
    return true;
  }

  function evaluateConditions(conditionSet, values) {
    if (!conditionSet) return true;

    if (Array.isArray(conditionSet)) {
      return conditionSet.every(function (rule) { return evaluateRule(rule, values); });
    }

    if (typeof conditionSet === 'object') {
      if (Array.isArray(conditionSet.all)) {
        return conditionSet.all.every(function (rule) { return evaluateRule(rule, values); });
      }
      if (Array.isArray(conditionSet.any)) {
        return conditionSet.any.some(function (rule) { return evaluateRule(rule, values); });
      }
      return evaluateRule(conditionSet, values);
    }

    return true;
  }

  function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(String(value || ''));
    }
    return String(value || '').replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function getFieldWrapByKey(fieldKey) {
    return form.querySelector('.intake-field-wrap[data-field-key="' + escapeSelector(fieldKey) + '"]');
  }

  function getFieldValidation(wrap) {
    var payload = wrap.getAttribute('data-validation') || '';
    var parsed = decodePayload(payload);
    if (parsed && typeof parsed === 'object') {
      return parsed;
    }
    return {};
  }

  function setInlineError(wrap, message) {
    var errorNode = wrap.querySelector('[data-inline-error]');
    if (!errorNode) return;
    if (!message) {
      errorNode.textContent = '';
      errorNode.classList.add('d-none');
      wrap.classList.remove('intake-field-invalid');
      wrap.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.classList.remove('is-invalid');
      });
      return;
    }

    errorNode.textContent = String(message);
    errorNode.classList.remove('d-none');
    wrap.classList.add('intake-field-invalid');
    wrap.querySelectorAll('input, select, textarea').forEach(function (input) {
      if (input.type === 'hidden' || input.disabled) return;
      input.classList.add('is-invalid');
    });
  }

  function getVisibleExistingFileCount(wrap) {
    var rows = wrap.querySelectorAll('[data-existing-file-row]');
    var count = 0;
    rows.forEach(function (row) {
      var checkbox = row.querySelector('.js-intake-remove-file');
      if (checkbox && checkbox.checked) return;
      count += 1;
    });
    return count;
  }

  function getSelectedFileCount(fieldKey) {
    var state = uploaderStates[fieldKey];
    if (!state) return 0;
    if (state.transfer) {
      return state.transfer.files.length;
    }
    if (state.input && state.input.files) {
      return state.input.files.length;
    }
    return 0;
  }

  function updateExistingFileVisualState(wrap) {
    wrap.querySelectorAll('[data-existing-file-row]').forEach(function (row) {
      var checkbox = row.querySelector('.js-intake-remove-file');
      row.classList.toggle('is-removing', !!(checkbox && checkbox.checked));
    });
  }

  function syncUploaderInput(state) {
    if (!state || !state.input || !state.transfer) return;
    state.input.files = state.transfer.files;
  }

  function renderUploaderList(state) {
    if (!state || !state.list) return;
    var files = state.transfer ? Array.prototype.slice.call(state.transfer.files) : Array.prototype.slice.call(state.input.files || []);
    if (!files.length) {
      state.list.innerHTML = '';
      return;
    }

    var html = '';
    files.forEach(function (file, index) {
      html += '<div class="intake-upload-item">';
      html += '<div class="d-flex justify-content-between align-items-center gap-2">';
      html += '<span class="intake-upload-name">' + escapeHtml(file.name || 'file') + '</span>';
      html += '<button class="btn btn-sm btn-outline-danger py-0 px-2 js-remove-selected-file" type="button" data-remove-index="' + index + '">Remove</button>';
      html += '</div>';
      html += '<div class="small text-muted">' + escapeHtml(formatFileSize(file.size || 0)) + '</div>';
      html += '<div class="progress intake-upload-progress mt-1"><div class="progress-bar bg-success" role="progressbar" style="width:100%"></div></div>';
      html += '</div>';
    });
    state.list.innerHTML = html;
  }

  function addFilesToUploader(wrap, fileList) {
    if (!fileList || !fileList.length) return;
    var fieldKey = wrap.getAttribute('data-field-key') || '';
    var state = uploaderStates[fieldKey];
    if (!state) return;

    var accepted = ['png', 'jpg', 'jpeg', 'pdf'];
    var maxBytes = 12 * 1024 * 1024;

    if (!state.transfer) {
      return;
    }

    Array.prototype.forEach.call(fileList, function (file) {
      if (!file || !file.name) return;
      var ext = String(file.name).split('.').pop().toLowerCase();
      if (accepted.indexOf(ext) === -1) {
        setInlineError(wrap, 'Only PNG, JPG, JPEG, and PDF files are allowed.');
        return;
      }
      if (Number(file.size || 0) > maxBytes) {
        setInlineError(wrap, 'Each file must be 12MB or less.');
        return;
      }

      if (!state.multiple) {
        state.transfer = makeDataTransfer();
        if (!state.transfer) {
          return;
        }
        state.transfer.items.add(file);
        wrap.querySelectorAll('.js-intake-remove-file').forEach(function (checkbox) {
          checkbox.checked = true;
        });
      } else {
        state.transfer.items.add(file);
      }
    });

    syncUploaderInput(state);
    renderUploaderList(state);
    updateExistingFileVisualState(wrap);
    setInlineError(wrap, '');
    updateRequiredDocsChecklist();
    queueAutosave();
  }

  function initFileUploader(wrap) {
    var fieldKey = wrap.getAttribute('data-field-key') || '';
    var uploader = wrap.querySelector('[data-file-uploader]');
    if (!fieldKey || !uploader) return;
    var input = uploader.querySelector('.js-intake-file-input');
    var dropzone = uploader.querySelector('[data-file-dropzone]');
    var list = uploader.querySelector('[data-selected-file-list]');
    if (!input || !dropzone || !list) return;

    var transfer = makeDataTransfer();
    if (transfer && input.files && input.files.length) {
      Array.prototype.forEach.call(input.files, function (file) {
        transfer.items.add(file);
      });
    }

    uploaderStates[fieldKey] = {
      input: input,
      dropzone: dropzone,
      list: list,
      transfer: transfer,
      multiple: uploader.getAttribute('data-multiple') === '1'
    };

    renderUploaderList(uploaderStates[fieldKey]);

    input.addEventListener('change', function () {
      addFilesToUploader(wrap, input.files);
      input.value = '';
    });

    dropzone.addEventListener('click', function () {
      input.click();
    });

    dropzone.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        input.click();
      }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone.addEventListener('drop', function (event) {
      var droppedFiles = event.dataTransfer ? event.dataTransfer.files : null;
      addFilesToUploader(wrap, droppedFiles);
    });

    list.addEventListener('click', function (event) {
      var removeBtn = event.target.closest('.js-remove-selected-file');
      if (!removeBtn) return;
      var removeIndex = parseInt(removeBtn.getAttribute('data-remove-index') || '-1', 10);
      if (removeIndex < 0) return;

      var state = uploaderStates[fieldKey];
      if (!state || !state.transfer) return;
      if (state.transfer.items && typeof state.transfer.items.remove === 'function') {
        state.transfer.items.remove(removeIndex);
      } else {
        var rebuild = makeDataTransfer();
        if (rebuild) {
          Array.prototype.forEach.call(state.transfer.files, function (file, idx) {
            if (idx !== removeIndex) rebuild.items.add(file);
          });
          state.transfer = rebuild;
        }
      }
      syncUploaderInput(state);
      renderUploaderList(state);
      updateRequiredDocsChecklist();
      queueAutosave();
    });
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
  }

  function isValidPhone(value) {
    var cleaned = String(value || '').trim().replace(/[\s\-\(\)]/g, '');
    if (!cleaned) return false;
    if (/^\+[1-9]\d{7,14}$/.test(cleaned)) return true;
    if (/^0\d{10}$/.test(cleaned)) return true;
    if (/^(?:\+234|234)\d{10}$/.test(cleaned)) return true;
    return false;
  }

  function isValidDate(value) {
    var text = String(value || '').trim();
    if (!text) return false;
    var match = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return false;
    var y = Number(match[1]);
    var m = Number(match[2]);
    var d = Number(match[3]);
    var date = new Date(Date.UTC(y, m - 1, d));
    return date.getUTCFullYear() === y && (date.getUTCMonth() + 1) === m && date.getUTCDate() === d;
  }

  function getFieldRawValue(wrap, fieldType) {
    if (fieldType === 'radio') {
      var selectedRadio = wrap.querySelector('input[type="radio"]:checked');
      return selectedRadio ? selectedRadio.value : '';
    }
    if (fieldType === 'checkbox') {
      return Array.prototype.slice.call(wrap.querySelectorAll('input[type="checkbox"]:checked')).map(function (input) { return input.value; });
    }
    if (fieldType === 'multiselect') {
      var select = wrap.querySelector('select');
      return select ? Array.prototype.slice.call(select.selectedOptions).map(function (option) { return option.value; }) : [];
    }
    if (fieldType === 'address') {
      var out = {};
      wrap.querySelectorAll('input, textarea, select').forEach(function (input) {
        var name = input.getAttribute('name') || '';
        var match = name.match(/\[([^\]]+)\]$/);
        if (!match) return;
        out[match[1]] = input.value;
      });
      return out;
    }
    if (fieldType === 'repeating_group') {
      var rows = [];
      wrap.querySelectorAll('[data-group-row]').forEach(function (row) {
        var rowValue = {};
        row.querySelectorAll('input, textarea, select').forEach(function (input) {
          var name = input.getAttribute('name') || '';
          var match = name.match(/\[([^\]]+)\]$/);
          if (!match) return;
          rowValue[match[1]] = input.value;
        });
        rows.push(rowValue);
      });
      return rows;
    }
    var input = wrap.querySelector('input:not([type="checkbox"]):not([type="radio"]), select, textarea');
    return input ? input.value : '';
  }

  function getFieldTotalFileCount(wrap) {
    var fieldKey = wrap.getAttribute('data-field-key') || '';
    var selectedCount = getSelectedFileCount(fieldKey);
    var existingCount = getVisibleExistingFileCount(wrap);
    return selectedCount + existingCount;
  }

  function validateField(wrap, focusOnError) {
    if (!wrap || wrap.classList.contains('d-none')) {
      setInlineError(wrap, '');
      return true;
    }

    var fieldType = String(wrap.getAttribute('data-field-type') || 'text').toLowerCase();
    var required = wrap.getAttribute('data-required') === '1';
    var label = wrap.getAttribute('data-label') || 'This field';
    var rules = getFieldValidation(wrap);
    var value = getFieldRawValue(wrap, fieldType);

    var errorMessage = '';
    if (fieldType === 'file') {
      var fileCount = getFieldTotalFileCount(wrap);
      var multiple = wrap.getAttribute('data-multiple') === '1';
      if (required && fileCount === 0) {
        errorMessage = label + ' is required.';
      } else if (!multiple && fileCount > 1) {
        errorMessage = label + ' accepts only one file.';
      }
    } else if (fieldType === 'checkbox' || fieldType === 'multiselect') {
      if (required && (!Array.isArray(value) || value.length === 0)) {
        errorMessage = label + ' is required.';
      }
    } else if (fieldType === 'radio') {
      if (required && String(value || '').trim() === '') {
        errorMessage = label + ' is required.';
      }
    } else if (fieldType === 'address') {
      var line1 = value && typeof value === 'object' ? String(value.line1 || '').trim() : '';
      if (required && line1 === '') {
        errorMessage = label + ' is required.';
      }
    } else if (fieldType === 'repeating_group') {
      if (required) {
        var hasRowData = Array.isArray(value) && value.some(function (row) { return !fieldValueIsEmpty(row); });
        if (!hasRowData) {
          errorMessage = label + ' is required.';
        }
      }
    } else {
      var textValue = String(value || '').trim();
      if (required && textValue === '') {
        errorMessage = label + ' is required.';
      } else if (textValue !== '') {
        if (fieldType === 'email' && !isValidEmail(textValue)) {
          errorMessage = label + ' must be a valid email address.';
        } else if (fieldType === 'phone' && !isValidPhone(textValue)) {
          errorMessage = label + ' must match +2348012345678 or 08012345678.';
        } else if (fieldType === 'date' && !isValidDate(textValue)) {
          errorMessage = label + ' must be a valid date.';
        } else if (fieldType === 'date') {
          var minDate = String((rules.minDate || rules.min || '')).trim();
          var maxDate = String((rules.maxDate || rules.max || '')).trim();
          if (minDate && isValidDate(minDate) && textValue < minDate) {
            errorMessage = label + ' must be on or after ' + minDate + '.';
          } else if (maxDate && isValidDate(maxDate) && textValue > maxDate) {
            errorMessage = label + ' must be on or before ' + maxDate + '.';
          }
        } else if (fieldType === 'number') {
          var normalized = textValue.replace(/,/g, '');
          if (isNaN(Number(normalized))) {
            errorMessage = label + ' must be a valid number.';
          } else if (rules.min !== undefined && rules.min !== '' && Number(normalized) < Number(rules.min)) {
            errorMessage = label + ' must be at least ' + rules.min + '.';
          } else if (rules.max !== undefined && rules.max !== '' && Number(normalized) > Number(rules.max)) {
            errorMessage = label + ' must be at most ' + rules.max + '.';
          }
        }

        if (!errorMessage && rules.minLength !== undefined && rules.minLength !== '' && textValue.length < Number(rules.minLength)) {
          errorMessage = label + ' must be at least ' + rules.minLength + ' characters.';
        }
        if (!errorMessage && rules.maxLength !== undefined && rules.maxLength !== '' && textValue.length > Number(rules.maxLength)) {
          errorMessage = label + ' must be at most ' + rules.maxLength + ' characters.';
        }
        if (!errorMessage && rules.pattern) {
          try {
            var regex = new RegExp(String(rules.pattern));
            if (!regex.test(textValue)) {
              errorMessage = label + ' format is invalid.';
            }
          } catch (err) {
            // Ignore malformed regex from schema on client side.
          }
        }
      }
    }

    setInlineError(wrap, errorMessage);
    if (errorMessage && focusOnError) {
      var focusTarget = wrap.querySelector('.is-invalid, input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [data-file-dropzone]');
      if (focusTarget && typeof focusTarget.focus === 'function') {
        focusTarget.focus();
      }
    }
    return !errorMessage;
  }

  function validateStep(stepIndex, focusOnError) {
    var panel = panels[stepIndex];
    if (!panel) return true;
    var wraps = Array.prototype.slice.call(panel.querySelectorAll('.intake-field-wrap'));
    var firstInvalid = null;

    wraps.forEach(function (wrap) {
      var valid = validateField(wrap, false);
      if (!valid && !firstInvalid) {
        firstInvalid = wrap;
      }
    });

    if (firstInvalid && focusOnError) {
      validateField(firstInvalid, true);
      return false;
    }
    return !firstInvalid;
  }

  function validateAllSteps() {
    var firstInvalidStep = -1;
    for (var i = 0; i < panels.length; i += 1) {
      if (!validateStep(i, false)) {
        firstInvalidStep = i;
        break;
      }
    }

    if (firstInvalidStep >= 0) {
      setStep(firstInvalidStep);
      validateStep(firstInvalidStep, true);
      if (autosaveStatus) autosaveStatus.textContent = 'Please fix the highlighted fields before submitting.';
      return false;
    }

    return true;
  }

  function updateRequiredDocsChecklist() {
    if (!requiredDocChecklist) return;
    var items = requiredDocChecklist.querySelectorAll('[data-doc-key]');
    var remaining = 0;
    items.forEach(function (item) {
      var fieldKey = item.getAttribute('data-doc-key') || '';
      var wrap = getFieldWrapByKey(fieldKey);
      var hidden = !wrap || wrap.classList.contains('d-none');
      var complete = hidden ? true : getFieldTotalFileCount(wrap) > 0;
      var stateBadge = item.querySelector('.intake-doc-state');
      var dot = item.querySelector('.intake-doc-dot');
      if (!complete) {
        remaining += 1;
      }
      item.classList.toggle('is-complete', complete);
      if (stateBadge) {
        stateBadge.textContent = complete ? 'Ready' : 'Pending';
        stateBadge.classList.toggle('text-bg-success', complete);
        stateBadge.classList.toggle('text-bg-secondary', !complete);
      }
      if (dot) {
        dot.classList.toggle('is-complete', complete);
      }
    });

    if (requiredDocsRemaining) {
      requiredDocsRemaining.textContent = remaining + ' remaining';
      requiredDocsRemaining.classList.toggle('text-bg-success', remaining === 0);
      requiredDocsRemaining.classList.toggle('text-bg-light', remaining > 0);
    }
  }

  function applyConditionalVisibility() {
    var values = collectSimpleValues();
    fieldWraps.forEach(function (wrap) {
      var payload = wrap.getAttribute('data-conditions') || '';
      var conditions = decodePayload(payload);
      var visible = payload ? evaluateConditions(conditions, values) : true;
      wrap.classList.toggle('d-none', !visible);

      wrap.querySelectorAll('input, select, textarea, button').forEach(function (input) {
        if (!input.dataset.requiredOriginal) {
          input.dataset.requiredOriginal = input.required ? '1' : '0';
        }

        if (input.type !== 'hidden') {
          input.disabled = !visible;
        }

        if (!visible) {
          input.required = false;
          input.classList.remove('is-invalid');
        } else if (input.dataset.requiredOriginal === '1') {
          input.required = true;
        }
      });

      if (!visible) {
        setInlineError(wrap, '');
      } else {
        validateField(wrap, false);
      }
    });
    updateRequiredDocChecklist();
  }

  function reindexRepeatingRows(group) {
    var rows = group.querySelectorAll('[data-group-row]');
    rows.forEach(function (row, rowIndex) {
      row.querySelectorAll('[name]').forEach(function (input) {
        var name = input.getAttribute('name') || '';
        var match = name.match(/^answers\[([^\]]+)\]\[\d+\]\[([^\]]+)\]$/);
        if (!match) return;
        input.setAttribute('name', 'answers[' + match[1] + '][' + rowIndex + '][' + match[2] + ']');
      });

      var removeBtn = row.querySelector('.js-remove-repeating-row');
      if (removeBtn) {
        removeBtn.disabled = rows.length <= 1;
      }
    });
  }

  function addRepeatingRow(group) {
    var groupKey = group.getAttribute('data-group-key') || '';
    if (!groupKey) return;

    var templateFields = [];
    var templatePayload = group.getAttribute('data-group-template') || '';
    if (!templatePayload) return;
    try {
      templateFields = JSON.parse(window.atob(templatePayload));
    } catch (err) {
      templateFields = [];
    }
    if (!Array.isArray(templateFields) || templateFields.length === 0) return;

    var rowsWrap = group.querySelector('[data-group-rows]');
    if (!rowsWrap) return;
    var nextIndex = rowsWrap.querySelectorAll('[data-group-row]').length;

    var html = '<div class="border rounded p-2 mb-2 repeating-group-row" data-group-row><div class="row g-2">';
    templateFields.forEach(function (field) {
      var key = String(field && field.key ? field.key : '');
      if (!key) return;
      var type = String(field && field.type ? field.type : 'text').toLowerCase();
      var label = String(field && field.label ? field.label : key.replace(/_/g, ' '));
      var requiredAttr = field && field.required ? ' required data-required="1"' : '';
      var inputName = 'answers[' + groupKey + '][' + nextIndex + '][' + key + ']';

      html += '<div class="col-md-6"><label class="form-label">' + escapeHtml(label) + (field && field.required ? ' *' : '') + '</label>';
      if (type === 'textarea') {
        html += '<textarea class="form-control intake-answer-input" rows="2" name="' + escapeHtml(inputName) + '"' + requiredAttr + '></textarea>';
      } else if (type === 'select') {
        html += '<select class="form-select intake-answer-input" name="' + escapeHtml(inputName) + '"' + requiredAttr + '>';
        html += '<option value="">Select option</option>';
        var opts = Array.isArray(field.options) ? field.options : [];
        opts.forEach(function (opt) {
          html += '<option value="' + escapeHtml(String(opt)) + '">' + escapeHtml(String(opt)) + '</option>';
        });
        html += '</select>';
      } else {
        var inputType = (type === 'phone' ? 'tel' : (['date', 'email', 'number', 'tel'].indexOf(type) !== -1 ? type : 'text'));
        html += '<input class="form-control intake-answer-input" type="' + escapeHtml(inputType) + '" name="' + escapeHtml(inputName) + '"' + requiredAttr + '>';
      }
      html += '</div>';
    });
    html += '</div><button type="button" class="btn btn-sm btn-outline-danger mt-2 js-remove-repeating-row">Remove row</button></div>';

    rowsWrap.insertAdjacentHTML('beforeend', html);
    reindexRepeatingRows(group);
    applyConditionalVisibility();
    queueAutosave();
  }

  function setStep(step) {
    currentStep = Math.max(0, Math.min(step, panels.length - 1));
    panels.forEach(function (panel, idx) {
      panel.classList.toggle('d-none', idx !== currentStep);
    });
    chips.forEach(function (chip, idx) {
      chip.classList.toggle('text-bg-primary', idx === currentStep);
      chip.classList.toggle('text-bg-light', idx !== currentStep);
    });

    if (prevBtn) prevBtn.disabled = currentStep === 0;
    if (nextBtn) nextBtn.disabled = currentStep >= panels.length - 1;
  }

  function queueAutosave() {
    if (autosaveTimer) {
      window.clearTimeout(autosaveTimer);
    }
    autosaveTimer = window.setTimeout(function () {
      runAutosave();
    }, 900);
  }

  function runAutosave() {
    if (!formActionField || !ajaxField) return;
    formActionField.value = 'autosave_intake';
    ajaxField.value = '1';

    var body = new FormData(form);
    Array.from(body.keys()).forEach(function (key) {
      if (String(key).indexOf('files[') === 0) {
        body.delete(key);
      }
      if (String(key).indexOf('remove_files[') === 0) {
        body.delete(key);
      }
    });

    fetch(form.action, {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    })
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        if (data && data.success) {
          if (autosaveStatus) autosaveStatus.textContent = 'Draft autosaved at ' + new Date().toLocaleTimeString();
        } else if (autosaveStatus) {
          autosaveStatus.textContent = (data && data.message) ? data.message : 'Autosave failed.';
        }
      })
      .catch(function () {
        if (autosaveStatus) autosaveStatus.textContent = 'Autosave failed. Check your connection.';
      })
      .finally(function () {
        formActionField.value = 'submit_intake';
        ajaxField.value = '0';
      });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      setStep(currentStep - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (!validateStep(currentStep, true)) {
        if (autosaveStatus) autosaveStatus.textContent = 'Please fix highlighted fields before moving to the next step.';
        return;
      }
      setStep(currentStep + 1);
    });
  }

  chips.forEach(function (chip, idx) {
    chip.addEventListener('click', function () {
      if (idx > currentStep && !validateStep(currentStep, true)) {
        if (autosaveStatus) autosaveStatus.textContent = 'Please fix highlighted fields before moving ahead.';
        return;
      }
      setStep(idx);
    });
  });

  fieldWraps.filter(function (wrap) {
    return String(wrap.getAttribute('data-field-type') || '').toLowerCase() === 'file';
  }).forEach(function (wrap) {
    initFileUploader(wrap);
    updateExistingFileVisualState(wrap);
  });

  form.querySelectorAll('.intake-repeating-group').forEach(function (group) {
    reindexRepeatingRows(group);
  });

  form.addEventListener('input', function (event) {
    var wrap = event.target ? event.target.closest('.intake-field-wrap') : null;
    if (event.target && event.target.classList.contains('intake-answer-input')) {
      queueAutosave();
      applyConditionalVisibility();
      if (wrap) validateField(wrap, false);
    }
  });

  form.addEventListener('change', function (event) {
    var wrap = event.target ? event.target.closest('.intake-field-wrap') : null;
    if (event.target && event.target.classList.contains('intake-answer-input')) {
      queueAutosave();
      applyConditionalVisibility();
      if (wrap) validateField(wrap, false);
    }
    if (event.target && event.target.classList.contains('js-intake-remove-file')) {
      if (wrap) {
        updateExistingFileVisualState(wrap);
        validateField(wrap, false);
      }
      updateRequiredDocChecklist();
      queueAutosave();
    }
  });

  form.addEventListener('click', function (event) {
    var addBtn = event.target.closest('.js-add-repeating-row');
    if (addBtn) {
      var addGroup = addBtn.closest('.intake-repeating-group');
      if (addGroup) {
        addRepeatingRow(addGroup);
      }
      return;
    }

    var removeBtn = event.target.closest('.js-remove-repeating-row');
    if (!removeBtn) return;
    var row = removeBtn.closest('[data-group-row]');
    var group = removeBtn.closest('.intake-repeating-group');
    if (!row || !group) return;
    var rowsWrap = group.querySelector('[data-group-rows]');
    if (!rowsWrap) return;

    var rows = rowsWrap.querySelectorAll('[data-group-row]');
    if (rows.length <= 1) {
      row.querySelectorAll('input, textarea, select').forEach(function (input) {
        if (input.tagName === 'SELECT') {
          input.selectedIndex = 0;
        } else {
          input.value = '';
        }
      });
      queueAutosave();
      return;
    }

    row.remove();
    reindexRepeatingRows(group);
    applyConditionalVisibility();
    queueAutosave();
  });

  form.querySelectorAll('[data-submit-action]').forEach(function (button) {
    button.addEventListener('click', function () {
      var action = button.getAttribute('data-submit-action') || 'submit_intake';
      if (formActionField) formActionField.value = action;
      if (ajaxField) ajaxField.value = '0';
    });
  });

  form.addEventListener('submit', function (event) {
    var action = formActionField ? formActionField.value : 'submit_intake';
    if (action === 'submit_intake' && !validateAllSteps()) {
      event.preventDefault();
      return;
    }
    if (ajaxField) ajaxField.value = '0';
  });

  setStep(0);
  applyConditionalVisibility();
  updateRequiredDocChecklist();
})();
</script>
<?php endif; ?>
