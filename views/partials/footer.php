<?php
$user = null;
try {
    $user = current_user();
} catch (Throwable $e) {
    $user = null;
}
$socials = social_links();
$socialIcons = [
    'facebook' => 'bi-facebook',
    'instagram' => 'bi-instagram',
    'x' => 'bi-twitter-x',
    'linkedin' => 'bi-linkedin',
    'tiktok' => 'bi-tiktok',
    'youtube' => 'bi-youtube',
];
?>
<?php if ($user): ?>
    </main>

    <footer class="site-footer py-3 border-top bg-white">
      <div class="portal-footer-inner d-flex flex-column flex-lg-row justify-content-between gap-2">
        <div class="small text-muted">&copy; <?= date('Y') ?> <?= e(footer_text()) ?>. All rights reserved.</div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <?php foreach ($socials as $network => $link): ?>
            <a class="small text-decoration-none text-muted" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer" title="<?= e(ucfirst($network)) ?>">
              <i class="bi <?= e($socialIcons[$network] ?? 'bi-link-45deg') ?>"></i>
            </a>
          <?php endforeach; ?>
          <span class="small text-muted">Powered by <?= e(app_name()) ?></span>
        </div>
      </div>
    </footer>
  </div>
</div>
<?php else: ?>
<?php if (($currentPage ?? '') === 'login'): ?>
</div>
</main>

<footer class="site-footer auth-page-footer py-3 border-top bg-white">
  <div class="container d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-center">
    <div class="small text-muted">&copy; <?= date('Y') ?> <?= e(footer_text()) ?>. All rights reserved.</div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <a class="small text-decoration-none text-muted" href="<?= e(public_url('privacy')) ?>">Privacy Policy</a>
      <a class="small text-decoration-none text-muted" href="<?= e(public_url('terms')) ?>">Terms & Conditions</a>
      <a class="small text-decoration-none text-muted" href="<?= e(public_url('home')) ?>">Back to home</a>
      <?php foreach ($socials as $network => $link): ?>
        <a class="small text-decoration-none text-muted" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer" title="<?= e(ucfirst($network)) ?>">
          <i class="bi <?= e($socialIcons[$network] ?? 'bi-link-45deg') ?>"></i>
        </a>
      <?php endforeach; ?>
      <span class="small text-muted">Powered by <?= e(app_name()) ?></span>
    </div>
  </div>
</footer>
<?php else: ?>
</main>

<footer class="site-footer mt-4 py-4 border-top bg-white">
  <div class="container d-flex flex-column flex-lg-row justify-content-between gap-2">
    <div class="small text-muted">&copy; <?= date('Y') ?> <?= e(footer_text()) ?>. All rights reserved.</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php foreach ($socials as $network => $link): ?>
        <a class="small text-decoration-none text-muted" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer" title="<?= e(ucfirst($network)) ?>">
          <i class="bi <?= e($socialIcons[$network] ?? 'bi-link-45deg') ?>"></i>
        </a>
      <?php endforeach; ?>
      <span class="small text-muted">Powered by <?= e(app_name()) ?></span>
    </div>
  </div>
</footer>
<?php endif; ?>
<?php endif; ?>

<div class="modal fade file-preview-modal" id="globalFilePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0">File preview</h5>
          <small class="text-muted" id="filePreviewMetaLabel">Preparing preview...</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close preview"></button>
      </div>
      <div class="modal-body">
        <div class="file-preview-loader" id="filePreviewLoader">
          <div class="file-preview-loader-spinner" aria-hidden="true"></div>
          <div class="file-preview-loader-text">
            <strong id="filePreviewLoaderTitle">Loading...</strong>
            <small id="filePreviewLoaderSub">Please wait</small>
          </div>
          <div class="file-preview-loader-bar"><span></span></div>
        </div>

        <div class="file-preview-stage d-none" id="filePreviewStage">
          <img class="file-preview-image d-none" id="filePreviewImage" alt="File preview image">
          <iframe class="file-preview-pdf d-none" id="filePreviewPdf" title="PDF preview" loading="lazy"></iframe>
          <video class="file-preview-video d-none" id="filePreviewVideo" controls preload="metadata" playsinline></video>
          <audio class="file-preview-audio d-none" id="filePreviewAudio" controls preload="metadata"></audio>
          <iframe class="file-preview-frame d-none" id="filePreviewFrame" title="File preview" loading="lazy"></iframe>
          <pre class="file-preview-text d-none" id="filePreviewText"></pre>
          <div class="file-preview-fallback d-none" id="filePreviewFallback">
            <div class="file-preview-fallback-icon"><i class="bi bi-file-earmark"></i></div>
            <h6 id="filePreviewName" class="mb-1">File</h6>
            <p id="filePreviewInfo" class="text-muted mb-0">Preview is not available for this file type.</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a class="btn btn-outline-primary" id="filePreviewDownloadBtn" href="#" download>Download</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php if ($user): ?>
<div class="notification-toast-stack" id="notificationToastStack" aria-live="polite" aria-atomic="true"></div>

<aside class="notification-center-drawer" id="notificationCenterDrawer" data-csrf="<?= e(csrf_token()) ?>" aria-hidden="true">
  <div class="notification-center-head">
    <strong>Notification Centre</strong>
    <button type="button" class="btn btn-sm btn-light" id="notificationDrawerClose" aria-label="Close notifications">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <div class="notification-center-actions">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="notificationDrawerMarkAll">Mark all read</button>
    <button type="button" class="btn btn-sm btn-outline-danger" id="notificationDrawerClearAll">Clear all</button>
  </div>
  <div class="notification-center-list" id="notificationCenterList"></div>
  <div class="notification-center-footer">
    <a class="small text-decoration-none" href="<?= e(url('notifications')) ?>">View all notifications</a>
  </div>
</aside>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.6.0/build/js/intlTelInput.min.js"></script>
<script>
(function () {
  var modalEl = document.getElementById('globalFilePreviewModal');
  if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
    return;
  }

  var modal = new window.bootstrap.Modal(modalEl);
  var loader = document.getElementById('filePreviewLoader');
  var loaderTitle = document.getElementById('filePreviewLoaderTitle');
  var loaderSub = document.getElementById('filePreviewLoaderSub');
  var stage = document.getElementById('filePreviewStage');
  var metaLabel = document.getElementById('filePreviewMetaLabel');
  var imageEl = document.getElementById('filePreviewImage');
  var pdfEl = document.getElementById('filePreviewPdf');
  var videoEl = document.getElementById('filePreviewVideo');
  var audioEl = document.getElementById('filePreviewAudio');
  var frameEl = document.getElementById('filePreviewFrame');
  var textEl = document.getElementById('filePreviewText');
  var fallbackEl = document.getElementById('filePreviewFallback');
  var nameEl = document.getElementById('filePreviewName');
  var infoEl = document.getElementById('filePreviewInfo');
  var downloadBtn = document.getElementById('filePreviewDownloadBtn');
  var activeToken = 0;
  var metaAbort = null;
  var previewLoadTimer = null;

  function formatBytes(value) {
    var n = Number(value || 0);
    if (!Number.isFinite(n) || n <= 0) {
      return 'Unknown size';
    }
    var units = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = 0;
    while (n >= 1024 && i < units.length - 1) {
      n /= 1024;
      i += 1;
    }
    return n.toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
  }

  function extFromUrl(url) {
    var path = (url.pathname || '').toLowerCase();
    var parts = path.split('.');
    return parts.length > 1 ? parts.pop() : '';
  }

  function guessKind(mime, fileName, fallbackExt) {
    var type = String(mime || '').toLowerCase();
    var ext = String((fileName || '').split('.').pop() || fallbackExt || '').toLowerCase();
    var imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'avif'];
    var videoExt = ['mp4', 'webm', 'ogg', 'm4v', 'mov'];
    var audioExt = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
    var textExt = ['txt', 'csv', 'log', 'json', 'xml', 'yml', 'yaml', 'ini', 'conf', 'cfg', 'sql', 'md', 'js', 'ts', 'jsx', 'tsx', 'php', 'css', 'html', 'htm', 'sh', 'bat', 'py', 'java', 'go', 'rb', 'rs', 'c', 'cpp', 'h', 'hpp', 'swift'];
    var officeExt = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'];

    if (type.indexOf('video/') === 0 || videoExt.indexOf(ext) !== -1) {
      return 'video';
    }
    if (type.indexOf('audio/') === 0 || audioExt.indexOf(ext) !== -1) {
      return 'audio';
    }
    if (type.indexOf('image/') === 0 || ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'].indexOf(ext) !== -1) {
      return 'image';
    }
    if (type === 'application/pdf' || ext === 'pdf') {
      return 'pdf';
    }
    if (
      type.indexOf('text/') === 0 ||
      type.indexOf('json') !== -1 ||
      type.indexOf('xml') !== -1 ||
      type.indexOf('javascript') !== -1 ||
      type.indexOf('csv') !== -1 ||
      textExt.indexOf(ext) !== -1
    ) {
      return 'text';
    }
    if (
      type.indexOf('msword') !== -1 ||
      type.indexOf('officedocument') !== -1 ||
      type.indexOf('opendocument') !== -1 ||
      officeExt.indexOf(ext) !== -1
    ) {
      return 'office';
    }
    return 'other';
  }

  function safeNameFromHref(url) {
    var candidate = decodeURIComponent((url.pathname || '').split('/').pop() || 'attachment');
    return candidate && candidate !== 'download.php' && candidate !== 'preview.php' ? candidate : 'Attachment';
  }

  function toPreviewTarget(anchor) {
    if (!anchor || anchor.hasAttribute('data-no-preview')) {
      return null;
    }
    var href = anchor.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
      return null;
    }

    var linkUrl = new URL(href, window.location.href);
    if (linkUrl.origin !== window.location.origin) {
      return null;
    }

    var lowerPath = (linkUrl.pathname || '').toLowerCase();
    var isDownload = lowerPath.endsWith('/download.php');
    var isPreview = lowerPath.endsWith('/preview.php');
    var ext = extFromUrl(linkUrl);
    var knownDirect = [
      'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'avif',
      'pdf',
      'txt', 'csv', 'log', 'json', 'xml', 'yml', 'yaml', 'ini', 'conf', 'cfg', 'sql', 'md',
      'js', 'ts', 'jsx', 'tsx', 'css', 'sh', 'bat', 'py',
      'mp4', 'webm', 'ogg', 'm4v', 'mov', 'mp3', 'wav', 'm4a', 'aac', 'flac',
      'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf',
      'zip', 'rar', '7z'
    ].indexOf(ext) !== -1;
    if (!isDownload && !isPreview && !knownDirect && !anchor.hasAttribute('data-file-preview')) {
      return null;
    }

    var previewUrl = new URL(linkUrl.toString());
    if (isDownload) {
      previewUrl.pathname = previewUrl.pathname.replace(/download\.php$/i, 'preview.php');
    }
    previewUrl.searchParams.delete('mode');

    var metaUrl = null;
    if (isDownload || isPreview) {
      metaUrl = new URL(previewUrl.toString());
      metaUrl.searchParams.set('mode', 'meta');
    }

    return {
      downloadUrl: linkUrl.toString(),
      previewUrl: previewUrl.toString(),
      metaUrl: metaUrl ? metaUrl.toString() : null,
      fallbackName: (anchor.getAttribute('data-file-name') || anchor.textContent || '').trim() || safeNameFromHref(linkUrl),
      ext: ext,
    };
  }

  function resetStage() {
    if (metaAbort) {
      metaAbort.abort();
      metaAbort = null;
    }
    if (previewLoadTimer) {
      window.clearTimeout(previewLoadTimer);
      previewLoadTimer = null;
    }
    stage.classList.add('d-none');
    loader.classList.remove('d-none');
    imageEl.classList.add('d-none');
    imageEl.removeAttribute('src');
    pdfEl.classList.add('d-none');
    pdfEl.setAttribute('src', 'about:blank');
    videoEl.classList.add('d-none');
    videoEl.pause();
    videoEl.removeAttribute('src');
    videoEl.load();
    audioEl.classList.add('d-none');
    audioEl.pause();
    audioEl.removeAttribute('src');
    audioEl.load();
    frameEl.classList.add('d-none');
    frameEl.setAttribute('src', 'about:blank');
    textEl.classList.add('d-none');
    textEl.textContent = '';
    fallbackEl.classList.add('d-none');
    nameEl.textContent = 'File';
    infoEl.textContent = 'Preview is not available for this file type.';
    metaLabel.textContent = 'Preparing preview...';
    loaderTitle.textContent = 'Loading...';
    loaderSub.textContent = 'Please wait';
  }

  function showFallback(fileName, mime, sizeText, reason) {
    if (previewLoadTimer) {
      window.clearTimeout(previewLoadTimer);
      previewLoadTimer = null;
    }
    stage.classList.remove('d-none');
    loader.classList.add('d-none');
    fallbackEl.classList.remove('d-none');
    nameEl.textContent = fileName || 'Attachment';
    infoEl.textContent = (mime ? 'Type: ' + mime + ' • ' : '') + sizeText + '. ' + (reason || 'Preview not available.');
  }

  function startPreviewTimeout(token, fileName, mime, sizeText) {
    if (previewLoadTimer) {
      window.clearTimeout(previewLoadTimer);
    }
    previewLoadTimer = window.setTimeout(function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText);
    }, 7000);
  }

  function loadImage(target, fileName, mime, sizeText, token) {
    startPreviewTimeout(token, fileName, mime, sizeText);
    var probe = new Image();
    probe.onload = function () {
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      imageEl.classList.remove('d-none');
      imageEl.alt = fileName || 'Image preview';
      imageEl.src = target.previewUrl;
    };
    probe.onerror = function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText);
    };
    probe.src = target.previewUrl;
  }

  function loadPdf(target, token, fileName, mime, sizeText) {
    startPreviewTimeout(token, fileName, mime, sizeText);
    var revealed = false;
    var revealPdfStage = function () {
      if (revealed || token !== activeToken) return;
      revealed = true;
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      pdfEl.classList.remove('d-none');
      pdfEl.onload = null;
      pdfEl.onerror = null;
    };

    pdfEl.onload = function () {
      revealPdfStage();
    };
    pdfEl.onerror = function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText, 'PDF could not be rendered in this browser. Use download.');
      pdfEl.onload = null;
      pdfEl.onerror = null;
    };

    // Some browsers do not consistently fire iframe onload for inline PDFs.
    window.setTimeout(revealPdfStage, 500);

    var pdfUrl = target.previewUrl;
    pdfUrl += (pdfUrl.indexOf('#') === -1 ? '#' : '&') + 'toolbar=1&navpanes=0&scrollbar=1&view=FitH';
    pdfEl.src = pdfUrl;
  }

  function loadVideo(target, token, fileName, mime, sizeText) {
    startPreviewTimeout(token, fileName, mime, sizeText);
    videoEl.onloadeddata = function () {
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      videoEl.classList.remove('d-none');
      videoEl.onloadeddata = null;
      videoEl.onerror = null;
    };
    videoEl.onerror = function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText);
      videoEl.onloadeddata = null;
      videoEl.onerror = null;
    };
    videoEl.src = target.previewUrl;
    videoEl.load();
  }

  function loadAudio(target, token, fileName, mime, sizeText) {
    startPreviewTimeout(token, fileName, mime, sizeText);
    audioEl.onloadeddata = function () {
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      audioEl.classList.remove('d-none');
      audioEl.onloadeddata = null;
      audioEl.onerror = null;
    };
    audioEl.onerror = function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText);
      audioEl.onloadeddata = null;
      audioEl.onerror = null;
    };
    audioEl.src = target.previewUrl;
    audioEl.load();
  }

  function loadFrame(frameSrc, token, fileName, mime, sizeText) {
    startPreviewTimeout(token, fileName, mime, sizeText);
    frameEl.onload = function () {
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      frameEl.classList.remove('d-none');
      frameEl.onload = null;
    };
    frameEl.src = frameSrc;
  }

  function loadText(target, token, fileName, mime, sizeText, maxBytes) {
    var maxSize = Number(maxBytes || (3 * 1024 * 1024));
    startPreviewTimeout(token, fileName, mime, sizeText);
    fetch(target.previewUrl, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'text/plain, */*' },
    }).then(function (resp) {
      if (!resp.ok) {
        throw new Error('Failed to load text preview.');
      }
      return resp.text();
    }).then(function (body) {
      if (token !== activeToken) return;
      if (previewLoadTimer) {
        window.clearTimeout(previewLoadTimer);
        previewLoadTimer = null;
      }
      if (body.length > maxSize) {
        showFallback(fileName, mime, sizeText, 'Text file is too large for inline preview. Use download.');
        return;
      }
      stage.classList.remove('d-none');
      loader.classList.add('d-none');
      textEl.classList.remove('d-none');
      textEl.textContent = body || '(empty file)';
    }).catch(function () {
      if (token !== activeToken) return;
      showFallback(fileName, mime, sizeText);
    });
  }

  function buildOfficeEmbedUrl(target) {
    if (window.location.protocol !== 'https:') {
      return '';
    }
    var host = String(window.location.hostname || '').toLowerCase();
    if (host === 'localhost' || host === '127.0.0.1') {
      return '';
    }
    var publicUrl = '';
    try {
      publicUrl = new URL(target.previewUrl, window.location.href).toString();
    } catch (err) {
      return '';
    }
    return 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(publicUrl);
  }

  function applyMeta(meta, target) {
    var fileName = (meta && meta.name) ? String(meta.name) : target.fallbackName;
    var mime = (meta && meta.mime) ? String(meta.mime) : '';
    var sizeText = formatBytes(meta && meta.size ? meta.size : 0);
    var kind = (meta && meta.kind) ? String(meta.kind) : guessKind(mime, fileName, target.ext);
    var large = Number(meta && meta.size ? meta.size : 0) > (8 * 1024 * 1024);

    metaLabel.textContent = fileName + ' • ' + sizeText;
    loaderSub.textContent = large ? ('Large file • ' + sizeText) : sizeText;

    if (kind === 'image') {
      loaderTitle.textContent = 'Rendering image preview...';
      loadImage(target, fileName, mime, sizeText, activeToken);
      return;
    }
    if (kind === 'pdf') {
      loaderTitle.textContent = 'Loading PDF preview...';
      loadPdf(target, activeToken, fileName, mime, sizeText);
      return;
    }
    if (kind === 'video') {
      loaderTitle.textContent = 'Loading video preview...';
      loadVideo(target, activeToken, fileName, mime, sizeText);
      return;
    }
    if (kind === 'audio') {
      loaderTitle.textContent = 'Loading audio preview...';
      loadAudio(target, activeToken, fileName, mime, sizeText);
      return;
    }
    if (kind === 'text') {
      loaderTitle.textContent = 'Rendering text preview...';
      if (Number(meta && meta.size ? meta.size : 0) > (6 * 1024 * 1024)) {
        showFallback(fileName, mime, sizeText, 'Text file is too large for inline preview. Use download.');
      } else {
        loadText(target, activeToken, fileName, mime, sizeText, 4 * 1024 * 1024);
      }
      return;
    }
    if (kind === 'office') {
      loaderTitle.textContent = 'Opening office preview...';
      var officeUrl = buildOfficeEmbedUrl(target);
      if (officeUrl) {
        loadFrame(officeUrl, activeToken, fileName, mime, sizeText);
      } else {
        loaderSub.textContent = 'Trying browser office preview';
        loadFrame(target.previewUrl, activeToken, fileName, mime, sizeText);
      }
      return;
    }

    showFallback(fileName, mime, sizeText);
  }

  function openPreview(target) {
    activeToken += 1;
    var token = activeToken;
    resetStage();
    downloadBtn.href = target.downloadUrl;
    modal.show();

    if (target.metaUrl) {
      metaAbort = new AbortController();
      var metaTimeout = window.setTimeout(function () {
        if (metaAbort) {
          metaAbort.abort();
          metaAbort = null;
        }
      }, 2500);
      fetch(target.metaUrl, {
        credentials: 'same-origin',
        cache: 'no-store',
        signal: metaAbort.signal,
        headers: { 'Accept': 'application/json' },
      }).then(function (resp) {
        window.clearTimeout(metaTimeout);
        if (!resp.ok) {
          throw new Error('Failed to load file metadata.');
        }
        return resp.json();
      }).then(function (payload) {
        window.clearTimeout(metaTimeout);
        if (token !== activeToken) return;
        if (!payload || payload.success !== true) {
          throw new Error(payload && payload.error ? String(payload.error) : 'Invalid metadata response.');
        }
        applyMeta(payload, target);
      }).catch(function () {
        window.clearTimeout(metaTimeout);
        if (token !== activeToken) return;
        applyMeta(null, target);
      });
      return;
    }

    applyMeta(null, target);
  }

  modalEl.addEventListener('hidden.bs.modal', function () {
    activeToken += 1;
    resetStage();
  });

  document.addEventListener('click', function (event) {
    var anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!anchor) return;
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }
    if (anchor.target === '_blank' || anchor.hasAttribute('download')) {
      return;
    }

    var target = toPreviewTarget(anchor);
    if (!target) return;

    event.preventDefault();
    openPreview(target);
  }, true);
})();
</script>
<script>
(function () {
  var body = document.body;
  var openBtn = document.getElementById('sidebarOpenBtn');
  var closeBtn = document.getElementById('sidebarCloseBtn');
  var overlay = document.getElementById('sidebarOverlay');
  var collapseBtn = document.getElementById('sidebarCollapseBtn');
  var collapseKey = 'iworksgt_sidebar_collapsed';

  if (collapseBtn) {
    try {
      if (window.localStorage && localStorage.getItem(collapseKey) === '1') {
        body.classList.add('sidebar-collapsed');
      }
    } catch (err) {
      // Ignore storage access errors.
    }

    collapseBtn.addEventListener('click', function () {
      body.classList.toggle('sidebar-collapsed');
      try {
        if (window.localStorage) {
          localStorage.setItem(collapseKey, body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }
      } catch (err) {
        // Ignore storage access errors.
      }
    });
  }

  function closeSidebar() {
    body.classList.remove('sidebar-open');
  }

  if (openBtn) {
    openBtn.addEventListener('click', function () {
      body.classList.add('sidebar-open');
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeSidebar);
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  document.querySelectorAll('.sidebar-link').forEach(function (el) {
    el.addEventListener('click', function () {
      if (window.innerWidth < 992) {
        closeSidebar();
      }
    });
  });

  var unreadBadge = document.getElementById('topbarUnreadBadge');
  var topbarNotificationBtn = document.getElementById('topbarNotificationBtn');
  var toastStack = document.getElementById('notificationToastStack');
  var drawer = document.getElementById('notificationCenterDrawer');
  var drawerCloseBtn = document.getElementById('notificationDrawerClose');
  var drawerList = document.getElementById('notificationCenterList');
  var drawerMarkAllBtn = document.getElementById('notificationDrawerMarkAll');
  var drawerClearAllBtn = document.getElementById('notificationDrawerClearAll');
  var csrf = drawer ? (drawer.getAttribute('data-csrf') || '') : '';
  var latestSeen = unreadBadge ? parseInt(unreadBadge.getAttribute('data-latest') || '0', 10) : 0;
  var initialLoaded = false;
  var latestNotifications = [];
  var shownToastIds = Object.create(null);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function updateUnreadBadge(unreadCount, latestId) {
    if (!unreadBadge) {
      return;
    }

    unreadBadge.textContent = String(unreadCount);
    unreadBadge.setAttribute('data-unread', String(unreadCount));
    if (typeof latestId === 'number' && !Number.isNaN(latestId)) {
      unreadBadge.setAttribute('data-latest', String(latestId));
    }
    if (unreadCount > 0) {
      unreadBadge.classList.remove('d-none');
    } else {
      unreadBadge.classList.add('d-none');
    }
  }

  function postNotificationAction(formAction, extraFields) {
    if (!csrf) {
      return Promise.resolve(null);
    }

    var payload = new URLSearchParams();
    payload.set('csrf_token', csrf);
    payload.set('ajax', '1');
    payload.set('form_action', formAction);

    if (extraFields && typeof extraFields === 'object') {
      Object.keys(extraFields).forEach(function (key) {
        payload.set(key, String(extraFields[key]));
      });
    }

    return fetch('index.php?page=notifications', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      credentials: 'same-origin',
      body: payload.toString(),
    }).then(function (resp) {
      if (!resp.ok) {
        throw new Error('Notification action failed');
      }
      return resp.json();
    });
  }

  function openDrawer() {
    if (!drawer) {
      if (topbarNotificationBtn) {
        window.location.href = topbarNotificationBtn.getAttribute('href') || 'index.php?page=notifications';
      }
      return;
    }
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    body.classList.add('notification-center-open');
    renderNotificationCenter(latestNotifications);
  }

  function closeDrawerView() {
    if (!drawer) {
      return;
    }
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    body.classList.remove('notification-center-open');
  }

  function notificationOpenUrl(item) {
    if (item && item.open_url) {
      return item.open_url;
    }
    if (item && item.id) {
      return 'index.php?page=notifications&open=' + encodeURIComponent(String(item.id));
    }
    return 'index.php?page=notifications';
  }

  function spawnToast(item) {
    if (!toastStack || !item || !item.id) {
      return;
    }

    var id = String(item.id);
    if (shownToastIds[id]) {
      return;
    }
    shownToastIds[id] = true;

    var toast = document.createElement('button');
    toast.type = 'button';
    toast.className = 'notification-toast';
    toast.innerHTML =
      '<div class="notification-toast-title">' + escapeHtml(item.title || 'Notification') + '</div>' +
      '<div class="notification-toast-text">' + escapeHtml(item.message || '') + '</div>';
    toast.addEventListener('click', function () {
      openDrawer();
    });

    toastStack.prepend(toast);
    requestAnimationFrame(function () {
      toast.classList.add('show');
    });

    window.setTimeout(function () {
      toast.classList.remove('show');
      window.setTimeout(function () {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 260);
    }, 5200);
  }

  function renderNotificationCenter(items) {
    if (!drawerList) {
      return;
    }

    drawerList.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      drawerList.innerHTML = '<div class="notification-center-empty">No notifications yet.</div>';
      return;
    }

    items.forEach(function (item) {
      var row = document.createElement('div');
      row.className = 'notification-center-item' + (parseInt(String(item.is_read || 0), 10) === 0 ? ' unread' : '');
      row.setAttribute('role', 'button');
      row.tabIndex = 0;
      row.innerHTML =
        '<div class="notification-center-item-head">' +
          '<strong>' + escapeHtml(item.title || 'Notification') + '</strong>' +
          '<span>' + escapeHtml(item.created_at || '') + '</span>' +
        '</div>' +
        '<p class="mb-0">' + escapeHtml(item.message || '') + '</p>' +
        '<button type="button" class="notification-clear-one" title="Clear notification">' +
          '<i class="bi bi-x-lg"></i>' +
        '</button>';

      var clearBtn = row.querySelector('.notification-clear-one');
      if (clearBtn) {
        clearBtn.addEventListener('click', function (event) {
          event.stopPropagation();
          postNotificationAction('clear_one', { notification_id: item.id })
            .then(function () {
              latestNotifications = latestNotifications.filter(function (n) { return String(n.id) !== String(item.id); });
              renderNotificationCenter(latestNotifications);
              pollNotificationState();
            })
            .catch(function () {
              // Ignore clear errors.
            });
        });
      }

      var openTarget = function () {
        window.location.href = notificationOpenUrl(item);
      };

      row.addEventListener('click', openTarget);
      row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openTarget();
        }
      });

      drawerList.appendChild(row);
    });
  }

  function pollNotificationState() {
    if (!unreadBadge) {
      return;
    }

    fetch('index.php?page=realtime&scope=notifications', {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error('Realtime fetch failed');
        return resp.json();
      })
      .then(function (data) {
        if (!data || data.success !== true) {
          return;
        }

        var unread = parseInt(String(data.unread_count || 0), 10);
        if (Number.isNaN(unread)) {
          unread = 0;
        }

        var latestId = parseInt(String(data.latest_id || 0), 10);
        if (Number.isNaN(latestId)) {
          latestId = 0;
        }

        var notifications = Array.isArray(data.notifications) ? data.notifications : [];
        latestNotifications = notifications;

        if (!initialLoaded) {
          latestSeen = latestId;
          initialLoaded = true;
        } else if (latestId > latestSeen) {
          notifications.forEach(function (item) {
            var id = parseInt(String(item.id || 0), 10);
            if (!Number.isNaN(id) && id > latestSeen) {
              spawnToast(item);
            }
          });
          latestSeen = latestId;
        }

        updateUnreadBadge(unread, latestSeen);

        if (drawer && drawer.classList.contains('open')) {
          renderNotificationCenter(latestNotifications);
        }
      })
      .catch(function () {
        // Ignore polling errors.
      });
  }

  if (unreadBadge) {
    if (topbarNotificationBtn) {
      topbarNotificationBtn.addEventListener('click', function (event) {
        if (drawer) {
          event.preventDefault();
          if (drawer.classList.contains('open')) {
            closeDrawerView();
          } else {
            openDrawer();
          }
        }
      });
    }

    if (drawerCloseBtn) {
      drawerCloseBtn.addEventListener('click', closeDrawerView);
    }

    if (drawerMarkAllBtn) {
      drawerMarkAllBtn.addEventListener('click', function () {
        postNotificationAction('read_all')
          .then(function () {
            pollNotificationState();
          })
          .catch(function () {
            // Ignore mark-all errors.
          });
      });
    }

    if (drawerClearAllBtn) {
      drawerClearAllBtn.addEventListener('click', function () {
        if (!window.confirm('Clear all notifications?')) {
          return;
        }
        postNotificationAction('clear_all')
          .then(function () {
            latestNotifications = [];
            renderNotificationCenter([]);
            pollNotificationState();
          })
          .catch(function () {
            // Ignore clear-all errors.
          });
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeDrawerView();
      }
    });

    setTimeout(pollNotificationState, 1600);
    setInterval(pollNotificationState, 7000);
  }
})();
</script>
</body>
</html>
