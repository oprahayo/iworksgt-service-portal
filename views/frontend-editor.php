<?php
$pages = is_array($pages ?? null) ? $pages : [];
$pageData = $pageData ?? [];
$mediaAssets = is_array($mediaAssets ?? null) ? $mediaAssets : [];
$pageRevisions = is_array($pageRevisions ?? null) ? $pageRevisions : [];
$websiteLeads = is_array($websiteLeads ?? null) ? $websiteLeads : [];

$layout = is_array($pageData['layout'] ?? null) ? $pageData['layout'] : [];
$seo = is_array($pageData['seo'] ?? null) ? $pageData['seo'] : [];
$slug = (string) ($pageData['slug'] ?? 'home');
$titleValue = (string) ($pageData['title'] ?? 'Home');

$blockOptions = [
  'header_config' => 'Header config',
  'hero' => 'Hero',
  'founder_story' => 'Founder story',
  'management_profiles' => 'Management profiles',
  'trusted_logos' => 'Trusted logos',
  'featured_services' => 'Featured services',
  'case_studies' => 'Case studies',
  'service_grid' => 'Service grid',
  'how_it_works' => 'How it works',
  'featured_packages' => 'Featured packages',
  'features' => 'Feature cards',
  'benefit_cards' => 'Benefit cards',
  'testimonials' => 'Testimonials',
  'blog_highlights' => 'Blog highlights',
  'faq' => 'FAQ',
  'contact_form' => 'Contact form',
  'cta_banner' => 'CTA banner',
  'footer' => 'Footer',
  'rich_text' => 'Rich text',
  'service_categories' => 'Service categories (legacy)',
];

$mediaPickerAssets = [];
foreach ($mediaAssets as $asset) {
    $assetId = (int) ($asset['id'] ?? 0);
    $assetUrl = trim((string) ($asset['url'] ?? ''));
    if ($assetId < 1 || $assetUrl === '') {
        continue;
    }
    $assetLabel = trim((string) ($asset['storage_key'] ?? ''));
    if ($assetLabel === '') {
        $assetLabel = 'Image #' . $assetId;
    }
    $mediaPickerAssets[] = [
        'id' => $assetId,
        'url' => $assetUrl,
        'label' => $assetLabel,
    ];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Frontend Editor</h1>
    <small class="text-muted">Edit public pages and publish updates.</small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('frontend_preview', ['slug' => $slug])) ?>" target="_blank">Preview</a>
    <form method="post" action="<?= e(url('frontend', ['slug' => $slug])) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="publish_page">
      <input type="hidden" name="slug" value="<?= e($slug) ?>">
      <button class="btn btn-primary btn-sm" type="submit">Publish</button>
    </form>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-3">
    <div class="card mb-3">
      <div class="card-body">
        <h2 class="h6">Pages</h2>
        <div class="list-group list-group-flush">
          <?php foreach ($pages as $p): ?>
            <a class="list-group-item list-group-item-action <?= $slug === $p['slug'] ? 'active' : '' ?>" href="<?= e(url('frontend', ['slug' => (string) $p['slug']])) ?>">
              <div class="d-flex justify-content-between">
                <span><?= e((string) $p['title']) ?></span>
                <span class="badge text-bg-light"><?= e((string) $p['status']) ?></span>
              </div>
              <small class="text-muted"><?= e((string) $p['slug']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card mb-3" id="frontendUploadMedia">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h6 mb-0">Upload media</h2>
          <a class="small text-decoration-none" href="<?= e(url('media')) ?>">Open library</a>
        </div>
        <form method="post" action="<?= e(url('media')) ?>" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="upload_media">
          <input type="hidden" name="return_to" value="<?= e(url('frontend', ['slug' => $slug])) ?>">
          <input class="form-control form-control-sm mb-2" type="file" name="media_file" accept="image/*" required>
          <button class="btn btn-outline-primary btn-sm w-100" type="submit">Upload image</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="h6">Media library</h2>
        <div class="media-grid">
          <?php foreach ($mediaAssets as $asset): ?>
            <div class="media-item">
              <img src="<?= e((string) $asset['url']) ?>" alt="">
              <small>ID <?= (int) $asset['id'] ?></small>
            </div>
          <?php endforeach; ?>
          <?php if (!$mediaAssets): ?>
            <div class="text-muted small">No images uploaded yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <form method="post" action="<?= e(url('frontend', ['slug' => $slug])) ?>" id="frontendEditorForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_action" value="save_page_draft">
      <input type="hidden" name="slug" value="<?= e($slug) ?>">

      <div class="card mb-3">
        <div class="card-body">
          <h2 class="h6">Page settings</h2>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input class="form-control" name="title" value="<?= e($titleValue) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Meta title</label>
              <input class="form-control" name="seo_meta_title" value="<?= e((string) ($seo['metaTitle'] ?? '')) ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Meta description</label>
              <textarea class="form-control" name="seo_meta_description" rows="2"><?= e((string) ($seo['metaDescription'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">OG image (URL)</label>
              <input class="form-control" name="seo_og_image" value="<?= e((string) ($seo['ogImage'] ?? '')) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">Page blocks</h2>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" id="blockTypeSelect">
                <?php foreach ($blockOptions as $key => $label): ?>
                  <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-outline-primary btn-sm" type="button" id="addBlockBtn">Add block</button>
            </div>
          </div>

          <div id="blocksContainer">
            <?php foreach ($layout as $index => $block): ?>
              <?php $blockType = (string) ($block['type'] ?? ''); ?>
              <div class="block-card" data-index="<?= (int) $index ?>">
                <div class="block-card-head">
                  <strong><?= e($blockOptions[$blockType] ?? $blockType) ?></strong>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary block-move-up" type="button">↑</button>
                    <button class="btn btn-sm btn-outline-secondary block-move-down" type="button">↓</button>
                    <button class="btn btn-sm btn-outline-danger block-remove" type="button">Remove</button>
                  </div>
                </div>
                <input type="hidden" name="blocks[<?= (int) $index ?>][type]" value="<?= e($blockType) ?>">
                <?php $renderBlockTemplates = false; ?>
                <?php include __DIR__ . '/frontend-editor-blocks.php'; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <?php $renderBlockTemplates = true; ?>
          <?php include __DIR__ . '/frontend-editor-blocks.php'; ?>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap justify-content-end">
        <button class="btn btn-outline-primary" type="submit">Save draft</button>
      </div>
    </form>

    <div class="card mt-3">
      <div class="card-body">
        <h2 class="h6">Revisions</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Label</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pageRevisions as $revision): ?>
                <tr>
                  <td><?= e((string) ($revision['label'] ?? 'Published copy')) ?></td>
                  <td><?= e((string) ($revision['created_at'] ?? '')) ?></td>
                  <td class="text-end">
                    <form method="post" action="<?= e(url('frontend', ['slug' => $slug])) ?>">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="form_action" value="rollback_page">
                      <input type="hidden" name="slug" value="<?= e($slug) ?>">
                      <input type="hidden" name="revision_id" value="<?= (int) $revision['id'] ?>">
                      <button class="btn btn-sm btn-outline-warning" type="submit">Rollback</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$pageRevisions): ?>
                <tr><td colspan="3" class="text-muted">No revisions yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <h2 class="h6">Recent Website Enquiries</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Page</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($websiteLeads as $lead): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= e((string) ($lead['full_name'] ?? '')) ?></div>
                    <?php if (!empty($lead['phone'])): ?><div class="small text-muted"><?= e((string) $lead['phone']) ?></div><?php endif; ?>
                  </td>
                  <td><?= e((string) ($lead['email'] ?? '')) ?></td>
                  <td><?= e((string) ($lead['page_slug'] ?? 'contact')) ?></td>
                  <td><?= e((string) ($lead['created_at'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$websiteLeads): ?>
                <tr><td colspan="4" class="text-muted">No website enquiries yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<template id="blockTemplate">
  <div class="block-card" data-index="__index__">
    <div class="block-card-head">
      <strong>__label__</strong>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-secondary block-move-up" type="button">↑</button>
        <button class="btn btn-sm btn-outline-secondary block-move-down" type="button">↓</button>
        <button class="btn btn-sm btn-outline-danger block-remove" type="button">Remove</button>
      </div>
    </div>
    <input type="hidden" name="blocks[__index__][type]" value="__type__">
    __body__
  </div>
</template>

<div class="frontend-media-modal" id="frontendMediaModal" hidden>
  <div class="frontend-media-modal-backdrop" data-media-modal-close></div>
  <div class="frontend-media-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="frontendMediaModalTitle">
    <div class="frontend-media-modal-header">
      <div>
        <h2 id="frontendMediaModalTitle" class="h5 mb-1">Media Library</h2>
        <p class="text-muted mb-0">Select existing images or upload a new one.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('media')) ?>">Open full library</a>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-media-modal-close>
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>
    <div class="frontend-media-modal-body">
      <form class="frontend-media-modal-upload" method="post" action="<?= e(url('media')) ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_action" value="upload_media">
        <input type="hidden" name="return_to" value="<?= e(url('frontend', ['slug' => $slug])) ?>">
        <label class="form-label mb-1">Upload new image</label>
        <div class="frontend-media-modal-upload-row">
          <input class="form-control form-control-sm" type="file" name="media_file" accept="image/*" required>
          <button class="btn btn-primary btn-sm" type="submit">Upload</button>
        </div>
      </form>
      <div class="frontend-media-modal-grid" data-media-library-grid>
        <?php if (!$mediaPickerAssets): ?>
          <div class="text-muted small">No images uploaded yet.</div>
        <?php endif; ?>
        <?php foreach ($mediaPickerAssets as $asset): ?>
          <div class="frontend-media-modal-tile-wrap">
            <button class="frontend-media-modal-tile" type="button" data-library-item data-media-id="<?= (int) $asset['id'] ?>" data-media-url="<?= e((string) $asset['url']) ?>" data-media-label="<?= e((string) $asset['label']) ?>">
              <img src="<?= e((string) $asset['url']) ?>" alt="<?= e((string) $asset['label']) ?>" loading="lazy">
              <span><?= e((string) $asset['label']) ?></span>
            </button>
            <?php if (has_role(['admin'])): ?>
              <form method="post" action="<?= e(url('media')) ?>" class="frontend-media-modal-delete" onsubmit="return confirm('Delete this image from media library?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="delete_media">
                <input type="hidden" name="media_id" value="<?= (int) $asset['id'] ?>">
                <input type="hidden" name="return_to" value="<?= e(url('frontend', ['slug' => $slug])) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="frontend-media-modal-preview is-empty" data-media-modal-preview>
        No image selected.
      </div>
    </div>
    <div class="frontend-media-modal-footer">
      <button class="btn btn-outline-secondary" type="button" data-media-modal-close>Cancel</button>
      <button class="btn btn-primary" type="button" data-media-modal-apply disabled>Use selected image</button>
    </div>
  </div>
</div>

<script>
(function () {
  var blockTemplate = document.getElementById('blockTemplate');
  var blocksContainer = document.getElementById('blocksContainer');
  var editorForm = document.getElementById('frontendEditorForm');
  var addBlockBtn = document.getElementById('addBlockBtn');
  var blockTypeSelect = document.getElementById('blockTypeSelect');
  var blockBodies = <?= json_encode($blockOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var mediaPickerAssets = <?= json_encode($mediaPickerAssets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var mediaAssetMap = {};
  mediaPickerAssets.forEach(function (asset) {
    mediaAssetMap[String(asset.id)] = asset;
  });

  var mediaModal = document.getElementById('frontendMediaModal');
  var mediaModalGrid = mediaModal?.querySelector('[data-media-library-grid]') || null;
  var mediaModalPreview = mediaModal?.querySelector('[data-media-modal-preview]') || null;
  var mediaModalApply = mediaModal?.querySelector('[data-media-modal-apply]') || null;
  var activeMediaPicker = null;
  var modalSelectedId = '';

  function syncWysiwyg(container) {
    if (!container) return;
    var editor = container.querySelector('.wysiwyg-editor');
    var input = container.querySelector('input[type="hidden"]');
    if (editor && input) {
      input.value = editor.innerHTML.trim();
    }
  }

  function bindWysiwyg(scope) {
    scope.querySelectorAll('[data-wysiwyg]').forEach(function (wrap) {
      if (wrap.dataset.bound === '1') return;
      wrap.dataset.bound = '1';

      var editor = wrap.querySelector('.wysiwyg-editor');
      var input = wrap.querySelector('input[type="hidden"]');
      if (editor && input && !editor.innerHTML.trim()) {
        editor.innerHTML = input.value || '';
      }

      wrap.querySelectorAll('[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var action = btn.getAttribute('data-action');
          if (!action) return;
          document.execCommand(action, false);
          syncWysiwyg(wrap);
        });
      });

      editor?.addEventListener('input', function () { syncWysiwyg(wrap); });
      editor?.addEventListener('blur', function () { syncWysiwyg(wrap); });
    });
  }

  function replaceRepeaterIndex(name, nextIndex) {
    return name.replace(
      /(\[(?:items|steps|categories|links|testimonials|faqs|proofItems|sections|socials)\])\[\d+\](\[[^\]]+\])$/,
      '$1[' + nextIndex + ']$2'
    );
  }

  function bindRepeaters(scope) {
    scope.querySelectorAll('.repeater').forEach(function (repeater) {
      if (repeater.dataset.bound === '1') return;
      repeater.dataset.bound = '1';

      repeater.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('.repeater-remove');
        if (!removeBtn || !repeater.contains(removeBtn)) return;
        var item = removeBtn.closest('.repeater-item');
        if (!item) return;
        var count = repeater.querySelectorAll('.repeater-item').length;
        if (count <= 1) {
          item.querySelectorAll('input, textarea, select').forEach(function (field) {
            if (field.tagName === 'SELECT') {
              field.selectedIndex = 0;
            } else {
              field.value = '';
            }
          });
          bindMediaPickers(item);
          return;
        }
        item.remove();
      });

      var addBtn = repeater.querySelector('.repeater-add');
      addBtn?.addEventListener('click', function () {
        var items = repeater.querySelectorAll('.repeater-item');
        if (items.length === 0) return;

        var nextIndex = items.length;
        var clone = items[0].cloneNode(true);
        clone.querySelectorAll('input, textarea, select').forEach(function (field) {
          if (field.name) {
            field.name = replaceRepeaterIndex(field.name, nextIndex);
          }
          if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
          } else {
            field.value = '';
          }
        });
        repeater.insertBefore(clone, addBtn);
        bindBlockActions(scope.closest('.block-card') || scope);
      });
    });
  }

  function renderMediaPicker(picker) {
    if (!picker) return;
    var input = picker.querySelector('[data-media-input]');
    var preview = picker.querySelector('[data-media-preview]');
    var label = picker.querySelector('[data-media-label]');
    if (!input || !preview) return;

    var selectedId = String(input.value || '');
    var emptyLabel = picker.getAttribute('data-empty-label') || 'No image selected yet.';
    var asset = selectedId !== '' ? mediaAssetMap[selectedId] : null;

    if (asset && asset.url) {
      preview.classList.remove('is-empty');
      preview.innerHTML = '';
      var image = document.createElement('img');
      image.src = asset.url;
      image.alt = asset.label || 'Selected image';
      image.loading = 'lazy';
      preview.appendChild(image);
      if (label) label.textContent = asset.label || ('Image #' + selectedId);
      return;
    }

    preview.classList.add('is-empty');
    preview.textContent = emptyLabel;
    if (label) label.textContent = emptyLabel;
    input.value = '';
  }

  function renderModalPreview(selectedId) {
    if (!mediaModalPreview) return;
    var asset = selectedId !== '' ? mediaAssetMap[String(selectedId)] : null;
    if (!asset || !asset.url) {
      mediaModalPreview.classList.add('is-empty');
      mediaModalPreview.textContent = 'No image selected.';
      return;
    }
    mediaModalPreview.classList.remove('is-empty');
    mediaModalPreview.innerHTML = '';
    var image = document.createElement('img');
    image.src = asset.url;
    image.alt = asset.label || 'Selected image';
    image.loading = 'lazy';
    var caption = document.createElement('span');
    caption.textContent = asset.label || ('Image #' + selectedId);
    mediaModalPreview.appendChild(image);
    mediaModalPreview.appendChild(caption);
  }

  function selectModalTile(selectedId) {
    modalSelectedId = selectedId ? String(selectedId) : '';
    mediaModal?.querySelectorAll('[data-library-item]').forEach(function (tile) {
      var tileId = tile.getAttribute('data-media-id') || '';
      tile.classList.toggle('is-selected', tileId === modalSelectedId);
    });
    if (mediaModalApply) {
      mediaModalApply.disabled = modalSelectedId === '';
    }
    renderModalPreview(modalSelectedId);
  }

  function openMediaModal(picker) {
    if (!mediaModal) return;
    activeMediaPicker = picker;
    var selectedInput = picker?.querySelector('[data-media-input]');
    selectModalTile(selectedInput?.value || '');
    mediaModal.hidden = false;
    document.body.classList.add('modal-open');
  }

  function closeMediaModal() {
    if (!mediaModal) return;
    mediaModal.hidden = true;
    document.body.classList.remove('modal-open');
    activeMediaPicker = null;
    modalSelectedId = '';
  }

  function bindMediaPickers(scope) {
    scope.querySelectorAll('[data-media-picker]').forEach(function (picker) {
      if (picker.dataset.bound === '1') {
        renderMediaPicker(picker);
        return;
      }
      picker.dataset.bound = '1';

      var openBtn = picker.querySelector('[data-media-open]');
      var clearBtn = picker.querySelector('[data-media-clear]');
      var hiddenInput = picker.querySelector('[data-media-input]');

      openBtn?.addEventListener('click', function () {
        openMediaModal(picker);
      });

      clearBtn?.addEventListener('click', function () {
        if (hiddenInput) {
          hiddenInput.value = '';
        }
        renderMediaPicker(picker);
      });

      renderMediaPicker(picker);
    });
  }

  function bindMediaModal() {
    if (!mediaModal) return;
    mediaModal.querySelectorAll('[data-media-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        closeMediaModal();
      });
    });

    mediaModalGrid?.addEventListener('click', function (event) {
      var tile = event.target.closest('[data-library-item]');
      if (!tile || !mediaModalGrid.contains(tile)) return;
      selectModalTile(tile.getAttribute('data-media-id') || '');
    });

    mediaModalApply?.addEventListener('click', function () {
      if (!activeMediaPicker) return;
      var hiddenInput = activeMediaPicker.querySelector('[data-media-input]');
      if (!hiddenInput) return;
      hiddenInput.value = modalSelectedId;
      renderMediaPicker(activeMediaPicker);
      closeMediaModal();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && mediaModal && !mediaModal.hidden) {
        closeMediaModal();
      }
    });
  }

  function bindBlockActions(block) {
    bindRepeaters(block);
    bindWysiwyg(block);
    bindMediaPickers(block);
    if (block.dataset.actionsBound === '1') return;
    block.dataset.actionsBound = '1';

    block.querySelector('.block-remove')?.addEventListener('click', function () {
      block.remove();
      reindexBlocks();
    });
    block.querySelector('.block-move-up')?.addEventListener('click', function () {
      var prev = block.previousElementSibling;
      if (prev) {
        blocksContainer.insertBefore(block, prev);
        reindexBlocks();
      }
    });
    block.querySelector('.block-move-down')?.addEventListener('click', function () {
      var next = block.nextElementSibling;
      if (next) {
        blocksContainer.insertBefore(next, block);
        reindexBlocks();
      }
    });
  }

  function reindexBlocks() {
    blocksContainer.querySelectorAll('.block-card').forEach(function (block, idx) {
      block.dataset.index = idx;
      block.querySelectorAll('[name^="blocks"]').forEach(function (input) {
        var name = input.getAttribute('name');
        if (!name) return;
        input.setAttribute('name', name.replace(/blocks\\[\\d+\\]/, 'blocks[' + idx + ']'));
      });
    });
  }

  function addBlock(type) {
    var index = blocksContainer.querySelectorAll('.block-card').length;
    var body = document.getElementById('blockBody_' + type);
    if (!body) return;
    var html = blockTemplate.innerHTML
      .replace(/__index__/g, index)
      .replace(/__label__/g, blockBodies[type] || type)
      .replace(/__type__/g, type)
      .replace('__body__', body.innerHTML.replace(/__index__/g, index));
    var wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    var block = wrapper.firstElementChild;
    blocksContainer.appendChild(block);
    bindBlockActions(block);
    reindexBlocks();
  }

  blocksContainer.querySelectorAll('.block-card').forEach(bindBlockActions);
  bindWysiwyg(document);
  bindMediaPickers(document);
  bindMediaModal();

  addBlockBtn?.addEventListener('click', function () {
    addBlock(blockTypeSelect.value);
  });

  editorForm?.addEventListener('submit', function () {
    document.querySelectorAll('[data-wysiwyg]').forEach(syncWysiwyg);
  });
})();
</script>
