<?php
$block = is_array($block ?? null) ? $block : [];
$blockData = is_array($block['data'] ?? null) ? $block['data'] : [];
$index = (int) ($index ?? 0);
$mediaAssets = is_array($mediaAssets ?? null) ? $mediaAssets : [];
$serviceOptions = list_services(true);
// iWorks offers business services, not travel packages. Legacy saved CMS
// blocks remain readable without importing an unavailable travel subsystem.
$packageOptions = [];
$renderBlockTemplates = (bool) ($renderBlockTemplates ?? false);

if (!function_exists('frontend_editor_media_options')) {
    function frontend_editor_media_options(array $mediaAssets, $selectedId): void
    {
        $selectedId = (int) $selectedId;
        echo '<option value="">None</option>';
        foreach ($mediaAssets as $asset) {
            $id = (int) ($asset['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $label = trim((string) ($asset['storage_key'] ?? 'Image #' . $id));
            if ($label === '') {
                $label = 'Image #' . $id;
            }
            $selected = $selectedId === $id ? 'selected' : '';
            echo '<option value="' . $id . '" ' . $selected . '>' . e($label) . '</option>';
        }
    }
}

if (!function_exists('frontend_editor_find_media_asset')) {
    function frontend_editor_find_media_asset(array $mediaAssets, $selectedId): ?array
    {
        $selectedId = (int) $selectedId;
        if ($selectedId < 1) {
            return null;
        }
        foreach ($mediaAssets as $asset) {
            if ((int) ($asset['id'] ?? 0) === $selectedId) {
                return $asset;
            }
        }
        return null;
    }
}

if (!function_exists('frontend_editor_media_picker')) {
    function frontend_editor_media_picker(
        array $mediaAssets,
        string $fieldName,
        $selectedId,
        string $emptyLabel = 'No image selected yet.',
        string $chooseLabel = 'Choose image'
    ): void {
        $selectedId = (int) $selectedId;
        $selectedAsset = frontend_editor_find_media_asset($mediaAssets, $selectedId);
        $selectedUrl = trim((string) ($selectedAsset['url'] ?? ''));
        $selectedLabel = trim((string) ($selectedAsset['storage_key'] ?? ''));
        if ($selectedLabel === '' && $selectedId > 0) {
            $selectedLabel = 'Image #' . $selectedId;
        }
        ?>
        <div class="frontend-media-picker" data-media-picker data-empty-label="<?= e($emptyLabel) ?>">
          <input type="hidden" name="<?= e($fieldName) ?>" value="<?= $selectedId > 0 ? $selectedId : '' ?>" data-media-input>
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
                <i class="bi bi-image me-1"></i><?= e($chooseLabel) ?>
              </button>
              <button class="btn btn-outline-secondary btn-sm" type="button" data-media-clear>Clear</button>
              <small class="text-muted" data-media-label><?= e($selectedLabel !== '' ? $selectedLabel : $emptyLabel) ?></small>
            </div>
          </div>
        </div>
        <?php
    }
}

if (!function_exists('frontend_editor_service_options')) {
    function frontend_editor_service_options(array $serviceOptions, array $selectedIds): void
    {
        foreach ($serviceOptions as $svc) {
            $svcId = (int) ($svc['id'] ?? 0);
            if ($svcId < 1) {
                continue;
            }
            $selected = in_array($svcId, $selectedIds, true) ? 'selected' : '';
            echo '<option value="' . $svcId . '" ' . $selected . '>' . e((string) ($svc['name'] ?? 'Service')) . '</option>';
        }
    }
}

if (!function_exists('frontend_editor_package_options')) {
    function frontend_editor_package_options(array $packageOptions, array $selectedIds): void
    {
        foreach ($packageOptions as $pkg) {
            $pkgId = (int) ($pkg['id'] ?? 0);
            if ($pkgId < 1) {
                continue;
            }
            $selected = in_array($pkgId, $selectedIds, true) ? 'selected' : '';
            $label = trim((string) ($pkg['title'] ?? 'Package'));
            $destination = trim((string) ($pkg['destination'] ?? ''));
            if ($destination !== '') {
                $label .= ' - ' . $destination;
            }
            echo '<option value="' . $pkgId . '" ' . $selected . '>' . e($label) . '</option>';
        }
    }
}

if (!function_exists('frontend_editor_links_to_text')) {
    function frontend_editor_links_to_text(array $links): string
    {
        $lines = [];
        foreach ($links as $link) {
            $label = trim((string) ($link['label'] ?? ''));
            $href = trim((string) ($link['href'] ?? ''));
            if ($label === '' && $href === '') {
                continue;
            }
            $lines[] = $label . '|' . $href;
        }
        return implode("\n", $lines);
    }
}

if (!function_exists('frontend_editor_column_links_text')) {
    function frontend_editor_column_links_text(array $columns, int $position): string
    {
        $column = $columns[$position] ?? [];
        $links = is_array($column['links'] ?? null) ? $column['links'] : [];
        return frontend_editor_links_to_text($links);
    }
}

if (!function_exists('render_frontend_editor_block_fields')) {
    function render_frontend_editor_block_fields(string $blockType, int|string $index, array $blockData, array $mediaAssets, array $serviceOptions, array $packageOptions): void
    {
        $selectedIds = array_map('intval', is_array($blockData['serviceIds'] ?? null) ? $blockData['serviceIds'] : []);
        $selectedPackageIds = array_map('intval', is_array($blockData['packageIds'] ?? null) ? $blockData['packageIds'] : []);
        $footerColumns = is_array($blockData['columns'] ?? null) ? $blockData['columns'] : [];

        switch ($blockType) {
            case 'header_config':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-12">
                      <label class="form-label">Home label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][homeLabel]" value="<?= e((string) ($blockData['homeLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Home link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][homeHref]" value="<?= e((string) ($blockData['homeHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Announcement bar text</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][announcement]" value="<?= e((string) ($blockData['announcement'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">About label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][aboutLabel]" value="<?= e((string) ($blockData['aboutLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">About link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][aboutHref]" value="<?= e((string) ($blockData['aboutHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Management label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][managementLabel]" value="<?= e((string) ($blockData['managementLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Management link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][managementHref]" value="<?= e((string) ($blockData['managementHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Case studies label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][useCasesLabel]" value="<?= e((string) ($blockData['useCasesLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Case studies link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][useCasesHref]" value="<?= e((string) ($blockData['useCasesHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Blog / insights label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][blogLabel]" value="<?= e((string) ($blockData['blogLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Blog / insights link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][blogHref]" value="<?= e((string) ($blockData['blogHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Contact label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][contactLabel]" value="<?= e((string) ($blockData['contactLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Contact link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][contactHref]" value="<?= e((string) ($blockData['contactHref'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Sign in label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][signInLabel]" value="<?= e((string) ($blockData['signInLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Primary CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaLabel]" value="<?= e((string) ($blockData['ctaLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Primary CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaHref]" value="<?= e((string) ($blockData['ctaHref'] ?? '')) ?>">
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'hero':
                $proofItems = is_array($blockData['proofItems'] ?? null) ? $blockData['proofItems'] : [['title' => '', 'text' => '']];
                $selectedHeroImageId = (int) ($blockData['imageId'] ?? 0);
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Headline</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][headline]" value="<?= e((string) ($blockData['headline'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Subheadline</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subheadline]" value="<?= e((string) ($blockData['subheadline'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Hero image</label>
                      <?php frontend_editor_media_picker(
                          $mediaAssets,
                          'blocks[' . $index . '][data][imageId]',
                          $selectedHeroImageId,
                          'No hero image selected yet.',
                          'Choose from media'
                      ); ?>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Primary CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][primaryCta][label]" value="<?= e((string) ($blockData['primaryCta']['label'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Primary CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][primaryCta][href]" value="<?= e((string) ($blockData['primaryCta']['href'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Secondary CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][secondaryCta][label]" value="<?= e((string) ($blockData['secondaryCta']['label'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Secondary CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][secondaryCta][href]" value="<?= e((string) ($blockData['secondaryCta']['href'] ?? '')) ?>">
                    </div>
                  </div>
                  <div class="repeater mt-2" data-repeater="proofItems">
                    <?php foreach ($proofItems as $i => $proof): ?>
                      <div class="repeater-item">
                        <input class="form-control" name="blocks[<?= $index ?>][data][proofItems][<?= $i ?>][title]" value="<?= e((string) ($proof['title'] ?? '')) ?>" placeholder="Highlight title">
                        <input class="form-control" name="blocks[<?= $index ?>][data][proofItems][<?= $i ?>][text]" value="<?= e((string) ($proof['text'] ?? '')) ?>" placeholder="Highlight text">
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add highlight</button>
                  </div>
                </div>
                <?php
                return;

            case 'founder_story':
                $sections = is_array($blockData['sections'] ?? null) ? $blockData['sections'] : [['title' => '', 'text' => '']];
                $socials = is_array($blockData['socials'] ?? null) ? $blockData['socials'] : [['network' => 'instagram', 'label' => '', 'href' => '']];
                $selectedFounderImageId = (int) ($blockData['imageId'] ?? 0);
                $socialOptions = [
                    'instagram' => 'Instagram',
                    'x' => 'X',
                    'linkedin' => 'LinkedIn',
                    'facebook' => 'Facebook',
                    'tiktok' => 'TikTok',
                    'youtube' => 'YouTube',
                    'whatsapp' => 'WhatsApp',
                    'website' => 'Website',
                ];
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-4">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? '')) ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Section subtitle</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][subtitle]" rows="2"><?= e((string) ($blockData['subtitle'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Founder name</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][name]" value="<?= e((string) ($blockData['name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Founder role</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][role]" value="<?= e((string) ($blockData['role'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Founder summary</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][summary]" rows="4"><?= e((string) ($blockData['summary'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Founder quote</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][quote]" rows="4"><?= e((string) ($blockData['quote'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Founder image</label>
                      <?php frontend_editor_media_picker(
                          $mediaAssets,
                          'blocks[' . $index . '][data][imageId]',
                          $selectedFounderImageId,
                          'No founder image selected yet. Upload or select a portrait.',
                          'Choose from media'
                      ); ?>
                    </div>
                  </div>

                  <div class="repeater mt-2" data-repeater="socials">
                    <?php foreach ($socials as $i => $social): ?>
                      <div class="repeater-item repeater-item-rich">
                        <select class="form-select" name="blocks[<?= $index ?>][data][socials][<?= $i ?>][network]">
                          <?php foreach ($socialOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected_if($value, (string) ($social['network'] ?? '')) ?>><?= e($label) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <input class="form-control" name="blocks[<?= $index ?>][data][socials][<?= $i ?>][label]" value="<?= e((string) ($social['label'] ?? '')) ?>" placeholder="Button label">
                        <input class="form-control" name="blocks[<?= $index ?>][data][socials][<?= $i ?>][href]" value="<?= e((string) ($social['href'] ?? '')) ?>" placeholder="https://...">
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add social link</button>
                  </div>

                  <div class="repeater mt-3" data-repeater="sections">
                    <?php foreach ($sections as $i => $section): ?>
                      <div class="repeater-item repeater-item-quote founder-story-repeater">
                        <input class="form-control" name="blocks[<?= $index ?>][data][sections][<?= $i ?>][title]" value="<?= e((string) ($section['title'] ?? '')) ?>" placeholder="Story section title">
                        <textarea class="form-control" name="blocks[<?= $index ?>][data][sections][<?= $i ?>][text]" rows="4" placeholder="Story section content"><?= e((string) ($section['text'] ?? '')) ?></textarea>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add story section</button>
                  </div>
                </div>
                <?php
                return;

            case 'management_profiles':
                $profiles = is_array($blockData['items'] ?? null) ? $blockData['items'] : [[
                    'name' => '',
                    'role' => '',
                    'summary' => '',
                    'href' => '',
                    'imageId' => null,
                ]];
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-3">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? '')) ?>">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Display style</label>
                      <select class="form-select" name="blocks[<?= $index ?>][data][displayStyle]">
                        <option value="full" <?= selected_if('full', (string) ($blockData['displayStyle'] ?? 'full')) ?>>Full page grid</option>
                        <option value="compact" <?= selected_if('compact', (string) ($blockData['displayStyle'] ?? '')) ?>>Compact homepage row</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Section subtitle</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][subtitle]" rows="2"><?= e((string) ($blockData['subtitle'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Section CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaLabel]" value="<?= e((string) ($blockData['ctaLabel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Section CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaHref]" value="<?= e((string) ($blockData['ctaHref'] ?? '')) ?>">
                    </div>
                  </div>

                  <div class="repeater mt-3" data-repeater="items">
                    <?php foreach ($profiles as $i => $profile): ?>
                      <div class="repeater-item repeater-item-quote">
                        <div class="row g-2">
                          <div class="col-md-6">
                            <label class="form-label">Name / profile title</label>
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][name]" value="<?= e((string) ($profile['name'] ?? '')) ?>" placeholder="Name or leadership title">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][role]" value="<?= e((string) ($profile['role'] ?? '')) ?>" placeholder="Role or focus area">
                          </div>
                          <div class="col-12">
                            <label class="form-label">Summary</label>
                            <textarea class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][summary]" rows="3" placeholder="Short management summary"><?= e((string) ($profile['summary'] ?? '')) ?></textarea>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Profile link</label>
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][href]" value="<?= e((string) ($profile['href'] ?? '')) ?>" placeholder="Link to profile or management page">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Profile image</label>
                            <?php frontend_editor_media_picker(
                                $mediaAssets,
                                'blocks[' . $index . '][data][items][' . $i . '][imageId]',
                                (int) ($profile['imageId'] ?? 0),
                                'No profile image selected yet.',
                                'Choose from media'
                            ); ?>
                          </div>
                        </div>
                        <button class="btn btn-outline-danger btn-sm repeater-remove mt-2" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add management profile</button>
                  </div>
                </div>
                <?php
                return;

            case 'featured_packages':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-4">
                      <label class="form-label">Section kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? 'Packages')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? 'Featured travel packages')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Section subtitle</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subtitle]" value="<?= e((string) ($blockData['subtitle'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaLabel]" value="<?= e((string) ($blockData['ctaLabel'] ?? 'View all packages')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaHref]" value="<?= e((string) ($blockData['ctaHref'] ?? public_url('start-now'))) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Number to show</label>
                      <input class="form-control" type="number" min="1" max="12" name="blocks[<?= $index ?>][data][limit]" value="<?= e((string) ($blockData['limit'] ?? '3')) ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Choose specific packages (optional)</label>
                      <select class="form-select" name="blocks[<?= $index ?>][data][packageIds][]" multiple size="5">
                        <?php frontend_editor_package_options($packageOptions, $selectedPackageIds); ?>
                      </select>
                      <small class="text-muted">Leave empty to automatically show packages marked as featured.</small>
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'trusted_logos':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [['title' => '', 'text' => '', 'imageId' => '']];
                ?>
                <div class="block-body">
                  <div class="mb-2">
                    <label class="form-label">Section title</label>
                    <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                  </div>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-rich">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>" placeholder="Logo title">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][text]" value="<?= e((string) ($item['text'] ?? '')) ?>" placeholder="Support text">
                        <?php frontend_editor_media_picker(
                            $mediaAssets,
                            'blocks[' . $index . '][data][items][' . $i . '][imageId]',
                            $item['imageId'] ?? null,
                            'No logo image selected.',
                            'Choose logo'
                        ); ?>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add logo</button>
                  </div>
                </div>
                <?php
                return;

            case 'features':
            case 'benefit_cards':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [['title' => '', 'text' => '', 'icon' => '']];
                ?>
                <div class="block-body">
                  <div class="mb-2">
                    <label class="form-label">Section title</label>
                    <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                  </div>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-rich">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>" placeholder="Title">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][text]" value="<?= e((string) ($item['text'] ?? '')) ?>" placeholder="Text">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][icon]" value="<?= e((string) ($item['icon'] ?? '')) ?>" placeholder="Bootstrap icon e.g. bi-lightning-charge">
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add item</button>
                  </div>
                </div>
                <?php
                return;

            case 'how_it_works':
                $steps = is_array($blockData['steps'] ?? null)
                    ? $blockData['steps']
                    : [['title' => '', 'duration' => '', 'text' => '', 'icon' => 'bi-ui-checks']];
                ?>
                <div class="block-body">
                  <div class="row g-2 mb-2">
                    <div class="col-md-4">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? 'How it works')) ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? 'From signup to operating in days')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Subtitle</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subtitle]" value="<?= e((string) ($blockData['subtitle'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Bottom note</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][footnote]" value="<?= e((string) ($blockData['footnote'] ?? '')) ?>" placeholder="Most businesses are processed quickly after complete submission.">
                    </div>
                  </div>
                  <div class="repeater" data-repeater="steps">
                    <?php foreach ($steps as $i => $step): ?>
                      <div class="repeater-item repeater-item-rich">
                        <input class="form-control" name="blocks[<?= $index ?>][data][steps][<?= $i ?>][title]" value="<?= e((string) ($step['title'] ?? '')) ?>" placeholder="Step title">
                        <input class="form-control" name="blocks[<?= $index ?>][data][steps][<?= $i ?>][duration]" value="<?= e((string) ($step['duration'] ?? '')) ?>" placeholder="Duration or short meta (e.g. 5 minutes)">
                        <input class="form-control" name="blocks[<?= $index ?>][data][steps][<?= $i ?>][text]" value="<?= e((string) ($step['text'] ?? '')) ?>" placeholder="Step text">
                        <input class="form-control" name="blocks[<?= $index ?>][data][steps][<?= $i ?>][icon]" value="<?= e((string) ($step['icon'] ?? 'bi-ui-checks')) ?>" placeholder="Bootstrap icon class (e.g. bi-ui-checks)">
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add step</button>
                  </div>
                </div>
                <?php
                return;

            case 'featured_services':
            case 'service_grid':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">Title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Subtitle (optional)</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subtitle]" value="<?= e((string) ($blockData['subtitle'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Top notice bar (optional)</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][notice]" value="<?= e((string) ($blockData['notice'] ?? '')) ?>" placeholder="Short message displayed in the green notice bar above the service list.">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Select services (leave empty for all public services)</label>
                      <select class="form-select" name="blocks[<?= $index ?>][data][serviceIds][]" multiple>
                        <?php frontend_editor_service_options($serviceOptions, $selectedIds); ?>
                      </select>
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'case_studies':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [[
                    'name' => '',
                    'category' => '',
                    'summary' => '',
                    'result' => '',
                    'link' => '',
                    'imageId' => '',
                ]];
                ?>
                <div class="block-body">
                  <div class="row g-2 mb-2">
                    <div class="col-md-4">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? 'Portfolio')) ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? 'Some of our recent projects')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Section subtitle</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subtitle]" value="<?= e((string) ($blockData['subtitle'] ?? '')) ?>" placeholder="Brief explanation of what these use cases represent">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA label (optional)</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaLabel]" value="<?= e((string) ($blockData['ctaLabel'] ?? '')) ?>" placeholder="Talk to our experts">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA link (optional)</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaHref]" value="<?= e((string) ($blockData['ctaHref'] ?? '')) ?>" placeholder="<?= e(public_url('case-studies')) ?>">
                    </div>
                  </div>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-rich">
                        <div class="row g-2">
                          <div class="col-md-6">
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][name]" value="<?= e((string) ($item['name'] ?? '')) ?>" placeholder="Project name">
                          </div>
                          <div class="col-md-6">
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][category]" value="<?= e((string) ($item['category'] ?? '')) ?>" placeholder="Category or service type">
                          </div>
                          <div class="col-md-12">
                            <textarea class="form-control" rows="2" name="blocks[<?= $index ?>][data][items][<?= $i ?>][summary]" placeholder="Two-line summary of the work delivered"><?= e((string) ($item['summary'] ?? '')) ?></textarea>
                          </div>
                          <div class="col-md-12">
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][result]" value="<?= e((string) ($item['result'] ?? '')) ?>" placeholder="Outcome, result, or quick client win">
                          </div>
                          <div class="col-md-12">
                            <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][link]" value="<?= e((string) ($item['link'] ?? '')) ?>" placeholder="https://project-link.com">
                          </div>
                        </div>
                        <?php frontend_editor_media_picker(
                            $mediaAssets,
                            'blocks[' . $index . '][data][items][' . $i . '][imageId]',
                            $item['imageId'] ?? null,
                            'No case study image selected.',
                            'Choose image'
                        ); ?>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add case study</button>
                  </div>
                </div>
                <?php
                return;

            case 'testimonials':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [['name' => '', 'role' => '', 'company' => '', 'location' => '', 'rating' => '5', 'imageId' => '', 'text' => '']];
                ?>
                <div class="block-body">
                  <div class="row g-2 mb-2">
                    <div class="col-md-4">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? 'Customer Reviews')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Rating value</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ratingValue]" value="<?= e((string) ($blockData['ratingValue'] ?? '4.8')) ?>" placeholder="4.8">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Review count</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ratingCount]" value="<?= e((string) ($blockData['ratingCount'] ?? '120')) ?>" placeholder="120">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Intro text</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>" placeholder="See what founders around the world are saying about their experience">
                    </div>
                  </div>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-quote">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][name]" value="<?= e((string) ($item['name'] ?? '')) ?>" placeholder="Name">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][role]" value="<?= e((string) ($item['role'] ?? '')) ?>" placeholder="Role">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][company]" value="<?= e((string) ($item['company'] ?? '')) ?>" placeholder="Company">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][location]" value="<?= e((string) ($item['location'] ?? '')) ?>" placeholder="Country / location">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][rating]" value="<?= e((string) ($item['rating'] ?? '5')) ?>" placeholder="Rating out of 5">
                        <?php frontend_editor_media_picker(
                            $mediaAssets,
                            'blocks[' . $index . '][data][items][' . $i . '][imageId]',
                            $item['imageId'] ?? null,
                            'No avatar selected.',
                            'Choose image'
                        ); ?>
                        <textarea class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][text]" rows="2" placeholder="Quote"><?= e((string) ($item['text'] ?? '')) ?></textarea>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add testimonial</button>
                  </div>
                </div>
                <?php
                return;

            case 'blog_highlights':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [['tag' => '', 'title' => '', 'text' => '', 'href' => '', 'imageId' => '']];
                ?>
                <div class="block-body">
                  <div class="row g-2 mb-2">
                    <div class="col-md-7">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label">Section CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaLabel]" value="<?= e((string) ($blockData['ctaLabel'] ?? 'Read all insights')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Section CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][ctaHref]" value="<?= e((string) ($blockData['ctaHref'] ?? public_url('blog'))) ?>" placeholder="<?= e(public_url('blog')) ?>">
                    </div>
                  </div>
                  <p class="text-muted small mb-2">Published blog posts automatically populate this section. Manual cards are only used when no published posts exist yet.</p>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-quote">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][tag]" value="<?= e((string) ($item['tag'] ?? '')) ?>" placeholder="Tag">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>" placeholder="Title">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][href]" value="<?= e((string) ($item['href'] ?? '')) ?>" placeholder="Link">
                        <?php frontend_editor_media_picker(
                            $mediaAssets,
                            'blocks[' . $index . '][data][items][' . $i . '][imageId]',
                            $item['imageId'] ?? null,
                            'No blog image selected.',
                            'Choose image'
                        ); ?>
                        <textarea class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][text]" rows="2" placeholder="Summary"><?= e((string) ($item['text'] ?? '')) ?></textarea>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add article</button>
                  </div>
                </div>
                <?php
                return;

            case 'faq':
                $items = is_array($blockData['items'] ?? null) ? $blockData['items'] : [['q' => '', 'a' => '']];
                ?>
                <div class="block-body">
                  <div class="row g-2 mb-2">
                    <div class="col-md-4">
                      <label class="form-label">Kicker</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][kicker]" value="<?= e((string) ($blockData['kicker'] ?? 'FAQ')) ?>" placeholder="FAQ">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Section title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? 'Common Questions')) ?>" placeholder="Common Questions">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Section subtitle</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][subtitle]" value="<?= e((string) ($blockData['subtitle'] ?? '')) ?>" placeholder="Everything you need to know...">
                    </div>
                  </div>
                  <div class="repeater" data-repeater="items">
                    <?php foreach ($items as $i => $item): ?>
                      <div class="repeater-item repeater-item-quote">
                        <input class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][q]" value="<?= e((string) ($item['q'] ?? '')) ?>" placeholder="Question">
                        <textarea class="form-control" name="blocks[<?= $index ?>][data][items][<?= $i ?>][a]" rows="2" placeholder="Answer"><?= e((string) ($item['a'] ?? '')) ?></textarea>
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add FAQ</button>
                  </div>
                </div>
                <?php
                return;

            case 'contact_form':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">Title</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Submit button label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][submitLabel]" value="<?= e((string) ($blockData['submitLabel'] ?? 'Send message')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Intro text</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][text]" rows="2"><?= e((string) ($blockData['text'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Support email</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][email]" value="<?= e((string) ($blockData['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Support phone</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][phone]" value="<?= e((string) ($blockData['phone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">WhatsApp link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][whatsapp]" value="<?= e((string) ($blockData['whatsapp'] ?? '')) ?>">
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'cta_banner':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">Headline</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][headline]" value="<?= e((string) ($blockData['headline'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Text</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][text]" value="<?= e((string) ($blockData['text'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA label</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][cta][label]" value="<?= e((string) ($blockData['cta']['label'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">CTA link</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][cta][href]" value="<?= e((string) ($blockData['cta']['href'] ?? '')) ?>">
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'service_categories':
                $categories = is_array($blockData['categories'] ?? null) ? $blockData['categories'] : [['name' => '', 'description' => '', 'href' => '#']];
                ?>
                <div class="block-body">
                  <div class="mb-2">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                  </div>
                  <div class="repeater" data-repeater="categories">
                    <?php foreach ($categories as $i => $category): ?>
                      <div class="repeater-item repeater-item-rich">
                        <input class="form-control" name="blocks[<?= $index ?>][data][categories][<?= $i ?>][name]" value="<?= e((string) ($category['name'] ?? '')) ?>" placeholder="Name">
                        <input class="form-control" name="blocks[<?= $index ?>][data][categories][<?= $i ?>][description]" value="<?= e((string) ($category['description'] ?? '')) ?>" placeholder="Description">
                        <input class="form-control" name="blocks[<?= $index ?>][data][categories][<?= $i ?>][href]" value="<?= e((string) ($category['href'] ?? '')) ?>" placeholder="Link">
                        <button class="btn btn-outline-danger btn-sm repeater-remove" type="button">Remove</button>
                      </div>
                    <?php endforeach; ?>
                    <button class="btn btn-outline-primary btn-sm repeater-add" type="button">Add category</button>
                  </div>
                </div>
                <?php
                return;

            case 'footer':
                ?>
                <div class="block-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">Brand / company name</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][companyName]" value="<?= e((string) ($blockData['companyName'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Support email</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][email]" value="<?= e((string) ($blockData['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Support phone</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][phone]" value="<?= e((string) ($blockData['phone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Address</label>
                      <input class="form-control" name="blocks[<?= $index ?>][data][address]" value="<?= e((string) ($blockData['address'] ?? '')) ?>">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Summary</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][summary]" rows="2"><?= e((string) ($blockData['summary'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Company column links</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][companyLinksText]" rows="5" placeholder="Label|Link"><?= e(frontend_editor_column_links_text($footerColumns, 0)) ?></textarea>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Services column links</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][servicesLinksText]" rows="5" placeholder="Label|Link"><?= e(frontend_editor_column_links_text($footerColumns, 1)) ?></textarea>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Legal column links</label>
                      <textarea class="form-control" name="blocks[<?= $index ?>][data][legalLinksText]" rows="5" placeholder="Label|Link"><?= e(frontend_editor_column_links_text($footerColumns, 2)) ?></textarea>
                    </div>
                  </div>
                </div>
                <?php
                return;

            case 'rich_text':
            default:
                ?>
                <div class="block-body">
                  <div class="mb-2">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="blocks[<?= $index ?>][data][title]" value="<?= e((string) ($blockData['title'] ?? '')) ?>">
                  </div>
                  <div class="wysiwyg" data-wysiwyg>
                    <div class="wysiwyg-toolbar">
                      <button type="button" data-action="bold"><i class="bi bi-type-bold"></i></button>
                      <button type="button" data-action="italic"><i class="bi bi-type-italic"></i></button>
                      <button type="button" data-action="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
                    </div>
                    <div class="wysiwyg-editor" contenteditable="true"><?= sanitize_limited_html((string) ($blockData['htmlOrDelta'] ?? '')) ?></div>
                    <input type="hidden" name="blocks[<?= $index ?>][data][htmlOrDelta]" value="<?= e((string) ($blockData['htmlOrDelta'] ?? '')) ?>">
                  </div>
                </div>
                <?php
                return;
        }
    }
}

if (!$renderBlockTemplates) {
    render_frontend_editor_block_fields((string) $blockType, $index, $blockData, $mediaAssets, $serviceOptions, $packageOptions);
    return;
}

$templateTypes = [
    'header_config',
    'hero',
    'founder_story',
    'management_profiles',
    'trusted_logos',
    'featured_services',
    'case_studies',
    'service_grid',
    'how_it_works',
    'features',
    'benefit_cards',
    'testimonials',
    'blog_highlights',
    'faq',
    'contact_form',
    'cta_banner',
    'footer',
    'service_categories',
    'rich_text',
];
?>
<?php foreach ($templateTypes as $templateType): ?>
  <template id="blockBody_<?= e($templateType) ?>">
    <?php render_frontend_editor_block_fields($templateType, '__index__', [], $mediaAssets, $serviceOptions, $packageOptions); ?>
  </template>
<?php endforeach; ?>
