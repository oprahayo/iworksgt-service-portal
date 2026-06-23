<?php
$mediaAssets = is_array($mediaAssets ?? null) ? $mediaAssets : [];

if (!function_exists('media_format_bytes')) {
    function media_format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $pow);
        return number_format($value, $pow === 0 ? 0 : 1) . ' ' . $units[$pow];
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Media Library</h1>
    <small class="text-muted">Browse, upload, copy, select, and delete media files.</small>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('media')) ?>">Refresh</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="post" action="<?= e(url('media')) ?>" enctype="multipart/form-data" class="media-dropzone" data-media-dropzone>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="upload_media">
      <input type="hidden" name="return_to" value="<?= e(url('media')) ?>">
      <label class="media-dropzone__label">
        <div>
          <div class="fw-semibold">Drag & drop images here</div>
          <small class="text-muted">or click to choose multiple files (max 5MB each).</small>
        </div>
        <span class="media-dropzone__count" data-dropzone-count>No files selected</span>
        <input class="media-dropzone__input" type="file" name="media_files[]" accept="image/*" multiple required>
      </label>
      <button class="btn btn-primary media-dropzone__btn" type="submit">Upload to library</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="media-library-toolbar mb-3">
      <div class="fw-semibold">All uploaded media</div>
      <div class="media-library-toolbar-actions">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-selected-urls>Copy selected URLs</button>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-selected-ids>Copy selected IDs</button>
        <span class="badge text-bg-light" data-selected-count>0 selected</span>
        <input class="form-control form-control-sm media-library-search" type="search" id="mediaLibrarySearch" placeholder="Search files...">
        <span class="badge text-bg-light"><?= count($mediaAssets) ?> files</span>
      </div>
    </div>

    <div class="media-library-grid" id="mediaLibraryGrid">
      <?php foreach ($mediaAssets as $asset): ?>
        <?php
          $assetId = (int) ($asset['id'] ?? 0);
          $assetUrl = (string) ($asset['url'] ?? '');
          $assetLabel = trim((string) ($asset['label'] ?? ''));
          if ($assetLabel === '') {
              $assetLabel = trim((string) ($asset['storage_key'] ?? ''));
          }
          if ($assetLabel === '') {
              $assetLabel = 'Image #' . $assetId;
          }
        ?>
        <article class="media-library-card" data-media-item data-media-url="<?= e($assetUrl) ?>" data-media-id="<?= $assetId ?>" data-media-keywords="<?= e(strtolower($assetLabel . ' ' . (string) ($asset['mime'] ?? ''))) ?>">
          <div class="media-library-card-preview">
            <?php if ($assetUrl !== ''): ?>
              <img src="<?= e($assetUrl) ?>" alt="<?= e($assetLabel) ?>" loading="lazy">
            <?php else: ?>
              <div class="media-library-empty">No preview</div>
            <?php endif; ?>
            <label class="media-library-select">
              <input type="checkbox" data-media-select>
              <span>Select</span>
            </label>
          </div>
          <div class="media-library-card-body">
            <div class="media-library-card-title"><?= e($assetLabel) ?></div>
            <div class="media-library-card-meta">
              <span>ID <?= $assetId ?></span>
              <span><?= e((string) ($asset['mime'] ?? '')) ?></span>
              <span><?= e(media_format_bytes((int) ($asset['size_bytes'] ?? 0))) ?></span>
              <span><?= e((string) ($asset['created_at'] ?? '')) ?></span>
            </div>
            <div class="media-library-card-actions">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-media-url="<?= e($assetUrl) ?>">Copy URL</button>
              <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-media-id="<?= $assetId ?>">Copy ID</button>
              <?php if ($assetUrl !== ''): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e($assetUrl) ?>" target="_blank" rel="noopener">Preview</a>
              <?php endif; ?>
              <?php if (has_role(['admin'])): ?>
                <form method="post" action="<?= e(url('media')) ?>" onsubmit="return confirm('Delete this media file?');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="form_action" value="delete_media">
                  <input type="hidden" name="media_id" value="<?= $assetId ?>">
                  <input type="hidden" name="return_to" value="<?= e(url('media')) ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
            </div>
            <?php if (has_role(['admin'])): ?>
              <form method="post" action="<?= e(url('media')) ?>" class="media-library-rename">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="rename_media">
                <input type="hidden" name="media_id" value="<?= $assetId ?>">
                <input type="hidden" name="return_to" value="<?= e(url('media')) ?>">
                <div class="input-group input-group-sm">
                  <input class="form-control" type="text" name="media_label" value="<?= e($assetLabel) ?>" placeholder="Rename file">
                  <button class="btn btn-outline-secondary" type="submit">Save</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if (!$mediaAssets): ?>
      <div class="text-muted small">No media files yet. Upload your first image above.</div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var search = document.getElementById('mediaLibrarySearch');
  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-media-item]'));
  var dropzone = document.querySelector('[data-media-dropzone]');
  var dropzoneInput = dropzone ? dropzone.querySelector('.media-dropzone__input') : null;
  var dropzoneCount = dropzone ? dropzone.querySelector('[data-dropzone-count]') : null;
  var selectedCount = document.querySelector('[data-selected-count]');

  search?.addEventListener('input', function () {
    var query = String(search.value || '').trim().toLowerCase();
    cards.forEach(function (card) {
      var hay = card.getAttribute('data-media-keywords') || '';
      card.classList.toggle('d-none', query !== '' && hay.indexOf(query) === -1);
    });
  });

  function updateSelectedCount() {
    if (!selectedCount) return;
    var checked = document.querySelectorAll('[data-media-select]:checked').length;
    selectedCount.textContent = checked + ' selected';
  }

  document.querySelectorAll('[data-media-select]').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
      var card = checkbox.closest('[data-media-item]');
      if (card) {
        card.classList.toggle('is-selected', checkbox.checked);
      }
      updateSelectedCount();
    });
  });

  updateSelectedCount();

  var copySelected = function (type) {
    var selected = Array.prototype.slice.call(document.querySelectorAll('[data-media-select]:checked'))
      .map(function (checkbox) {
        var card = checkbox.closest('[data-media-item]');
        if (!card) return '';
        return type === 'url' ? (card.getAttribute('data-media-url') || '') : (card.getAttribute('data-media-id') || '');
      })
      .filter(Boolean);
    if (!selected.length || !navigator.clipboard) return;
    navigator.clipboard.writeText(selected.join('\n'));
  };

  document.querySelector('[data-copy-selected-urls]')?.addEventListener('click', function () {
    copySelected('url');
  });

  document.querySelector('[data-copy-selected-ids]')?.addEventListener('click', function () {
    copySelected('id');
  });

  document.querySelectorAll('[data-copy-media-url]').forEach(function (button) {
    button.addEventListener('click', function () {
      var value = button.getAttribute('data-copy-media-url') || '';
      if (!value || !navigator.clipboard) return;
      navigator.clipboard.writeText(value);
      button.textContent = 'Copied';
      window.setTimeout(function () { button.textContent = 'Copy URL'; }, 1000);
    });
  });

  document.querySelectorAll('[data-copy-media-id]').forEach(function (button) {
    button.addEventListener('click', function () {
      var value = button.getAttribute('data-copy-media-id') || '';
      if (!value || !navigator.clipboard) return;
      navigator.clipboard.writeText(value);
      button.textContent = 'Copied';
      window.setTimeout(function () { button.textContent = 'Copy ID'; }, 1000);
    });
  });

  if (dropzone && dropzoneInput) {
    var updateDropCount = function () {
      if (!dropzoneCount) return;
      var count = dropzoneInput.files ? dropzoneInput.files.length : 0;
      dropzoneCount.textContent = count > 0 ? (count + ' file(s) ready') : 'No files selected';
    };

    dropzone.addEventListener('dragover', function (event) {
      event.preventDefault();
      dropzone.classList.add('is-dragover');
    });

    dropzone.addEventListener('dragleave', function () {
      dropzone.classList.remove('is-dragover');
    });

    dropzone.addEventListener('drop', function (event) {
      event.preventDefault();
      dropzone.classList.remove('is-dragover');
      if (event.dataTransfer && event.dataTransfer.files && dropzoneInput) {
        dropzoneInput.files = event.dataTransfer.files;
        updateDropCount();
      }
    });

    dropzoneInput.addEventListener('change', updateDropCount);
    updateDropCount();
  }
})();
</script>
