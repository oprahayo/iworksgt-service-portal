<?php

if (!function_exists('admin_media_asset_label')) {
    function admin_media_asset_label(array $asset): string
    {
        $label = trim((string) ($asset['label'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($asset['storage_key'] ?? ''));
        }
        if ($label === '') {
            $label = trim((string) ($asset['original_name'] ?? ''));
        }
        if ($label === '') {
            $label = 'Image #' . (int) ($asset['id'] ?? 0);
        }
        return $label;
    }
}

if (!function_exists('admin_media_picker_assets')) {
    function admin_media_picker_assets(array $mediaAssets): array
    {
        $items = [];
        foreach ($mediaAssets as $asset) {
            $id = (int) ($asset['id'] ?? 0);
            $url = trim((string) ($asset['url'] ?? ''));
            if ($id < 1 || $url === '') {
                continue;
            }

            $items[] = [
                'id' => $id,
                'url' => $url,
                'label' => admin_media_asset_label($asset),
                'mime' => trim((string) ($asset['mime'] ?? '')),
                'size_bytes' => (int) ($asset['size_bytes'] ?? 0),
                'created_at' => (string) ($asset['created_at'] ?? ''),
            ];
        }
        return $items;
    }
}

if (!function_exists('admin_media_asset_by_id')) {
    function admin_media_asset_by_id(array $mediaAssets, int $selectedId): ?array
    {
        foreach ($mediaAssets as $asset) {
            if ((int) ($asset['id'] ?? 0) === $selectedId) {
                return $asset;
            }
        }
        return null;
    }
}

if (!function_exists('render_admin_media_picker')) {
    function render_admin_media_picker(
        array $mediaAssets,
        string $fieldName,
        int|string|null $selectedId = null,
        string $emptyLabel = 'No image selected yet.',
        string $chooseLabel = 'Choose image'
    ): void {
        $selectedIdInt = (int) $selectedId;
        $selectedAsset = $selectedIdInt > 0 ? admin_media_asset_by_id($mediaAssets, $selectedIdInt) : null;
        $selectedUrl = trim((string) ($selectedAsset['url'] ?? ''));
        $selectedLabel = $selectedAsset ? admin_media_asset_label($selectedAsset) : '';
        ?>
        <div class="frontend-media-picker" data-media-picker data-empty-label="<?= e($emptyLabel) ?>">
          <input type="hidden" name="<?= e($fieldName) ?>" value="<?= $selectedIdInt > 0 ? $selectedIdInt : '' ?>" data-media-input>
          <div class="frontend-media-picker-row">
            <div class="frontend-media-picker-preview<?= $selectedUrl === '' ? ' is-empty' : '' ?>" data-media-preview>
              <?php if ($selectedUrl !== ''): ?>
                <img src="<?= e($selectedUrl) ?>" alt="<?= e($selectedLabel !== '' ? $selectedLabel : 'Selected image') ?>" loading="lazy">
              <?php else: ?>
                <span><?= e($emptyLabel) ?></span>
              <?php endif; ?>
            </div>
            <div class="frontend-media-picker-actions">
              <button class="btn btn-outline-primary btn-sm" type="button" data-media-open>
                <i class="bi bi-images me-1"></i><?= e($chooseLabel) ?>
              </button>
              <button class="btn btn-outline-secondary btn-sm" type="button" data-media-clear>Clear</button>
              <small class="text-muted" data-media-label><?= e($selectedLabel !== '' ? $selectedLabel : $emptyLabel) ?></small>
            </div>
          </div>
        </div>
        <?php
    }
}

if (!function_exists('render_admin_media_modal')) {
    function render_admin_media_modal(array $mediaAssets, array $options = []): void
    {
        $modalId = trim((string) ($options['modal_id'] ?? 'adminMediaModal'));
        if ($modalId === '') {
            $modalId = 'adminMediaModal';
        }

        $uploadAction = trim((string) ($options['upload_action'] ?? url('media')));
        if ($uploadAction === '') {
            $uploadAction = url('media');
        }

        $returnTo = trim((string) ($options['return_to'] ?? (string) ($_SERVER['REQUEST_URI'] ?? '')));
        if ($returnTo === '') {
            $returnTo = url('media');
        }

        $assets = admin_media_picker_assets($mediaAssets);
        ?>
        <div class="frontend-media-modal" id="<?= e($modalId) ?>" hidden>
          <div class="frontend-media-modal-backdrop" data-media-modal-close></div>
          <div class="frontend-media-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= e($modalId) ?>Title">
            <div class="frontend-media-modal-header">
              <div>
                <h2 id="<?= e($modalId) ?>Title" class="h5 mb-1">Media Library</h2>
                <p class="text-muted mb-0">Browse existing media or upload a new image.</p>
              </div>
              <div class="d-flex gap-2 align-items-center">
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('media')) ?>">Open full library</a>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-media-modal-close>
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <div class="frontend-media-modal-body">
              <form class="frontend-media-modal-upload" method="post" action="<?= e($uploadAction) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="upload_media">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <label class="form-label mb-1">Upload new image(s)</label>
                <div class="frontend-media-modal-upload-row">
                  <input class="form-control form-control-sm" type="file" name="media_files[]" accept="image/*" multiple required>
                  <button class="btn btn-primary btn-sm" type="submit">Upload</button>
                </div>
              </form>

              <div class="frontend-media-modal-grid" data-media-library-grid>
                <?php if (!$assets): ?>
                  <div class="text-muted small">No media uploaded yet.</div>
                <?php endif; ?>

                <?php foreach ($assets as $asset): ?>
                  <div class="frontend-media-modal-tile-wrap">
                    <button
                      class="frontend-media-modal-tile"
                      type="button"
                      data-library-item
                      data-media-id="<?= (int) $asset['id'] ?>"
                      data-media-url="<?= e((string) $asset['url']) ?>"
                      data-media-label="<?= e((string) $asset['label']) ?>"
                    >
                      <img src="<?= e((string) $asset['url']) ?>" alt="<?= e((string) $asset['label']) ?>" loading="lazy">
                      <span><?= e((string) $asset['label']) ?></span>
                    </button>
                    <?php if (has_role(['admin'])): ?>
                      <form method="post" action="<?= e(url('media')) ?>" class="frontend-media-modal-delete" onsubmit="return confirm('Delete this media file?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="form_action" value="delete_media">
                        <input type="hidden" name="media_id" value="<?= (int) $asset['id'] ?>">
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="frontend-media-modal-preview is-empty" data-media-modal-preview>No image selected.</div>
            </div>

            <div class="frontend-media-modal-footer">
              <button class="btn btn-outline-secondary" type="button" data-media-modal-close>Cancel</button>
              <button class="btn btn-primary" type="button" data-media-modal-apply disabled>Use selected image</button>
            </div>
          </div>
        </div>

        <script>
        (function () {
          var modal = document.getElementById(<?= json_encode($modalId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
          if (!modal || modal.dataset.bound === '1') return;
          modal.dataset.bound = '1';

          var grid = modal.querySelector('[data-media-library-grid]');
          var preview = modal.querySelector('[data-media-modal-preview]');
          var applyBtn = modal.querySelector('[data-media-modal-apply]');
          var activePicker = null;
          var selectedId = '';

          var assets = <?= json_encode($assets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
          var map = {};
          assets.forEach(function (item) { map[String(item.id)] = item; });

          function renderPicker(picker) {
            if (!picker) return;
            var input = picker.querySelector('[data-media-input]');
            var previewNode = picker.querySelector('[data-media-preview]');
            var labelNode = picker.querySelector('[data-media-label]');
            var emptyLabel = picker.getAttribute('data-empty-label') || 'No image selected yet.';
            if (!input || !previewNode) return;

            var id = String(input.value || '');
            var asset = id ? map[id] : null;
            if (asset && asset.url) {
              previewNode.classList.remove('is-empty');
              previewNode.innerHTML = '';
              var image = document.createElement('img');
              image.src = asset.url;
              image.alt = asset.label || ('Image #' + id);
              image.loading = 'lazy';
              previewNode.appendChild(image);
              if (labelNode) labelNode.textContent = asset.label || ('Image #' + id);
              return;
            }

            previewNode.classList.add('is-empty');
            previewNode.textContent = emptyLabel;
            if (labelNode) labelNode.textContent = emptyLabel;
            input.value = '';
          }

          function renderModalPreview(id) {
            var asset = id ? map[String(id)] : null;
            if (!asset || !asset.url) {
              preview.classList.add('is-empty');
              preview.textContent = 'No image selected.';
              return;
            }
            preview.classList.remove('is-empty');
            preview.innerHTML = '';
            var image = document.createElement('img');
            image.src = asset.url;
            image.alt = asset.label || ('Image #' + id);
            image.loading = 'lazy';
            var caption = document.createElement('span');
            caption.textContent = asset.label || ('Image #' + id);
            preview.appendChild(image);
            preview.appendChild(caption);
          }

          function selectTile(id) {
            selectedId = id ? String(id) : '';
            modal.querySelectorAll('[data-library-item]').forEach(function (tile) {
              tile.classList.toggle('is-selected', (tile.getAttribute('data-media-id') || '') === selectedId);
            });
            applyBtn.disabled = selectedId === '';
            renderModalPreview(selectedId);
          }

          function openModal(picker) {
            activePicker = picker;
            var selectedInput = picker ? picker.querySelector('[data-media-input]') : null;
            selectTile(selectedInput ? selectedInput.value : '');
            modal.hidden = false;
            document.body.classList.add('modal-open');
          }

          function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            activePicker = null;
            selectedId = '';
          }

          function bindPickers(scope) {
            var root = scope || document;
            root.querySelectorAll('[data-media-picker]').forEach(function (picker) {
              if (picker.dataset.bound === '1') {
                renderPicker(picker);
                return;
              }
              picker.dataset.bound = '1';
              var openBtn = picker.querySelector('[data-media-open]');
              var clearBtn = picker.querySelector('[data-media-clear]');
              var input = picker.querySelector('[data-media-input]');

              if (openBtn) {
                openBtn.addEventListener('click', function () {
                  openModal(picker);
                });
              }

              if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                  if (input) input.value = '';
                  renderPicker(picker);
                });
              }

              renderPicker(picker);
            });
          }

          modal.querySelectorAll('[data-media-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', closeModal);
          });

          grid?.addEventListener('click', function (event) {
            var tile = event.target.closest('[data-library-item]');
            if (!tile || !grid.contains(tile)) return;
            selectTile(tile.getAttribute('data-media-id') || '');
          });

          applyBtn?.addEventListener('click', function () {
            if (!activePicker) return;
            var input = activePicker.querySelector('[data-media-input]');
            if (!input) return;
            input.value = selectedId;
            renderPicker(activePicker);
            closeModal();
          });

          document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
              closeModal();
            }
          });

          window.AdminMediaPicker = window.AdminMediaPicker || {};
          window.AdminMediaPicker.bind = bindPickers;
          bindPickers(document);
        })();
        </script>
        <?php
    }
}
