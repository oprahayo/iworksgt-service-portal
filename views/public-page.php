<?php
$pageData = $pageData ?? [];
$layout = is_array($pageData['layout'] ?? null) ? $pageData['layout'] : [];
$publicServices = is_array($publicServices ?? null) ? $publicServices : [];
$publicServiceCategories = is_array($publicServiceCategories ?? null) ? $publicServiceCategories : [];
$publicPageSlug = (string) ($publicPageSlug ?? '');
$selectedPublicCategory = trim((string) ($_GET['category'] ?? ''));

if (!function_exists('public_reorder_home_layout')) {
    function public_reorder_home_layout(array $layout): array
    {
        $priority = [
            'header_config' => 0,
            'hero' => 10,
            'trusted_logos' => 20,
            'how_it_works' => 30,
            'featured_services' => 40,
            'benefit_cards' => 50,
            'case_studies' => 55,
            'management_profiles' => 57,
            'testimonials' => 60,
            'blog_highlights' => 70,
            'faq' => 80,
            'contact_form' => 90,
            'cta_banner' => 100,
            'footer' => 999,
        ];

        $decorated = [];
        foreach ($layout as $index => $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = normalize_page_block_type((string) ($block['type'] ?? ''));
            $decorated[] = [
                'weight' => $priority[$type] ?? 500,
                'index' => (int) $index,
                'block' => $block,
            ];
        }

        usort($decorated, static function (array $left, array $right): int {
            $weightCompare = $left['weight'] <=> $right['weight'];
            if ($weightCompare !== 0) {
                return $weightCompare;
            }
            return $left['index'] <=> $right['index'];
        });

        return array_map(static fn(array $item): array => $item['block'], $decorated);
    }
}

if ($publicPageSlug === 'home') {
    $layout = public_reorder_home_layout($layout);
}

if (!function_exists('public_layout_has_block')) {
    function public_layout_has_block(array $layout, string $type): bool
    {
        foreach ($layout as $block) {
            if (normalize_page_block_type((string) ($block['type'] ?? '')) === $type) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('public_service_bullets')) {
    function public_service_bullets(array $service): array
    {
        $bullets = array_values(array_filter(array_map('trim', is_array($service['featured_bullets'] ?? null) ? $service['featured_bullets'] : [])));
        if ($bullets !== []) {
            return $bullets;
        }
        return [
            'Structured service scope and requirements',
            'Remote document submission flow',
            'Portal-based updates and delivery',
        ];
    }
}

if (!function_exists('public_render_service_media')) {
    function public_render_service_media(array $service): void
    {
        $image = is_array($service['featured_image'] ?? null) ? $service['featured_image'] : null;
        if (!empty($image['url'])) {
            echo '<img src="' . e((string) $image['url']) . '" alt="' . e((string) ($service['name'] ?? 'Service')) . '" loading="lazy">';
            return;
        }

        echo '<div class="marketing-service-icon"><i class="bi bi-briefcase"></i></div>';
    }
}

if (!function_exists('public_service_icon_class')) {
    function public_service_icon_class(array $service): string
    {
        $haystack = strtolower(trim((string) (($service['name'] ?? '') . ' ' . ($service['category'] ?? ''))));
        $map = [
            'renew' => 'bi-arrow-repeat',
            'withdraw' => 'bi-x-circle',
            'appeal' => 'bi-info-circle',
            'agent' => 'bi-people',
            'claim' => 'bi-file-earmark-text',
            'search' => 'bi-search',
            'update' => 'bi-pencil-square',
            'publication' => 'bi-card-text',
            'registration' => 'bi-file-earmark-plus',
            'filing' => 'bi-file-earmark-plus',
            'payment' => 'bi-cash-coin',
            'bank' => 'bi-bank',
            'hosting' => 'bi-globe',
            'website' => 'bi-window',
            'domain' => 'bi-globe2',
            'graphic' => 'bi-palette',
            'training' => 'bi-mortarboard',
            'trademark' => 'bi-shield-check',
            'compliance' => 'bi-patch-check',
        ];
        foreach ($map as $needle => $icon) {
            if (str_contains($haystack, $needle)) {
                return $icon;
            }
        }
        return 'bi-grid';
    }
}

if (!function_exists('public_category_icon_class')) {
    function public_category_icon_class(string $slugOrLabel): string
    {
        $value = strtolower(trim($slugOrLabel));
        $map = [
            'all' => 'bi-grid-3x3-gap',
            'filing' => 'bi-file-earmark-plus',
            'agent' => 'bi-people',
            'payment' => 'bi-cash-coin',
            'business setup' => 'bi-briefcase',
            'business-setup' => 'bi-briefcase',
            'digital growth' => 'bi-graph-up-arrow',
            'digital-growth' => 'bi-graph-up-arrow',
            'operations' => 'bi-gear',
            'training' => 'bi-mortarboard',
            'compliance' => 'bi-patch-check',
            'trademark' => 'bi-shield-check',
        ];
        foreach ($map as $needle => $icon) {
            if ($value === $needle || str_contains($value, $needle)) {
                return $icon;
            }
        }
        return 'bi-grid';
    }
}

if (!function_exists('public_testimonial_initials')) {
    function public_testimonial_initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'IW';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letter = function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
            $letters .= strtoupper((string) $letter);
        }
        return $letters !== '' ? $letters : 'IW';
    }
}

if (!function_exists('public_testimonial_star_states')) {
    function public_testimonial_star_states(float $rating, int $max = 5): array
    {
        $rating = max(0, min($rating, (float) $max));
        $full = (int) floor($rating);
        $fraction = $rating - $full;
        $states = [];
        for ($i = 1; $i <= $max; $i++) {
            if ($i <= $full) {
                $states[] = 'full';
            } elseif ($i === $full + 1 && $fraction >= 0.2) {
                $states[] = $fraction >= 0.8 ? 'full' : 'partial';
            } else {
                $states[] = 'empty';
            }
        }
        return $states;
    }
}

if (!function_exists('public_faq_items_enriched')) {
    function public_faq_items_enriched(array $items): array
    {
        $items = array_values(array_filter($items, static function ($item): bool {
            return is_array($item) && (trim((string) ($item['q'] ?? '')) !== '' || trim((string) ($item['a'] ?? '')) !== '');
        }));

        $defaults = function_exists('default_portal_faq_items') ? default_portal_faq_items() : [];
        if (count($items) >= 8 || $defaults === []) {
            return $items;
        }

        $seen = [];
        foreach ($items as $item) {
            $question = strtolower(trim((string) ($item['q'] ?? '')));
            if ($question !== '') {
                $seen[$question] = true;
            }
        }

        foreach ($defaults as $fallback) {
            $question = strtolower(trim((string) ($fallback['q'] ?? '')));
            if ($question === '' || isset($seen[$question])) {
                continue;
            }
            $items[] = $fallback;
            $seen[$question] = true;
            if (count($items) >= 8) {
                break;
            }
        }

        return $items;
    }
}

if (!function_exists('public_social_icon_class')) {
    function public_social_icon_class(string $network): string
    {
        $network = strtolower(trim($network));
        $map = [
            'instagram' => 'bi-instagram',
            'x' => 'bi-twitter-x',
            'twitter' => 'bi-twitter-x',
            'linkedin' => 'bi-linkedin',
            'facebook' => 'bi-facebook',
            'tiktok' => 'bi-tiktok',
            'youtube' => 'bi-youtube',
            'whatsapp' => 'bi-whatsapp',
            'website' => 'bi-globe2',
        ];

        return $map[$network] ?? 'bi-link-45deg';
    }
}

if (!function_exists('public_founder_story_content_blocks')) {
    function public_founder_story_content_blocks(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $chunks = preg_split("/\n\s*\n/", $text) ?: [];
        $blocks = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            $lines = array_values(array_filter(array_map('trim', preg_split("/\r?\n/", $chunk) ?: []), static fn(string $line): bool => $line !== ''));
            if ($lines === []) {
                continue;
            }

            $isList = true;
            $items = [];
            foreach ($lines as $line) {
                if (preg_match('/^(?:[-*•]\s+)(.+)$/u', $line, $matches) !== 1) {
                    $isList = false;
                    break;
                }
                $items[] = trim((string) ($matches[1] ?? ''));
            }

            if ($isList && $items !== []) {
                $blocks[] = ['type' => 'list', 'items' => $items];
                continue;
            }

            $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $lines)];
        }

        return $blocks;
    }
}

if (!function_exists('public_selected_category_meta')) {
    function public_selected_category_meta(array $categories, string $selectedSlug, string $defaultTitle, string $defaultSubtitle): array
    {
        foreach ($categories as $category) {
            if ((string) ($category['slug'] ?? '') !== $selectedSlug) {
                continue;
            }
            $label = trim((string) ($category['label'] ?? 'Services'));
            $count = (int) ($category['count'] ?? 0);
            $subtitleMap = [
                'filing' => 'Handle fresh filings and submission-based services remotely.',
                'agent' => 'Manage representation, updates, and account-linked support services.',
                'payment' => 'Resolve payment-linked tasks and financial processing services.',
                'business setup' => 'Register, structure, and launch your business with guided support.',
                'digital growth' => 'Set up your web presence, brand assets, and digital growth stack.',
                'operations' => 'Keep operational support, tracking, and delivery workflows moving.',
                'training' => 'Access guided training, onboarding, and operational knowledge support.',
                'compliance' => 'Stay compliant through renewals, updates, and filing support.',
                'trademark' => 'Register, protect, and manage your brand identity.',
            ];
            $subtitle = $subtitleMap[strtolower($label)] ?? ('Explore ' . strtolower($label) . ' services available through iWorks.');

            return [
                'title' => $label . ' Services',
                'count' => $count,
                'subtitle' => $subtitle,
                'icon' => public_category_icon_class((string) ($category['slug'] ?? $label)),
            ];
        }

        return [
            'title' => $defaultTitle,
            'count' => count($categories) > 0 ? array_sum(array_map(static fn(array $row): int => (int) ($row['count'] ?? 0), $categories)) : 0,
            'subtitle' => $defaultSubtitle,
            'icon' => public_category_icon_class('all'),
        ];
    }
}

if (!function_exists('public_blog_highlight_items')) {
    function public_blog_highlight_items(array $items, int $limit): array
    {
        $posts = function_exists('list_blog_posts_public') ? list_blog_posts_public($limit) : [];
        if ($posts !== []) {
            return array_map(static function (array $post): array {
                return [
                    'tag' => trim((string) ($post['tag'] ?? 'Insights')),
                    'title' => trim((string) ($post['title'] ?? 'Insight')),
                    'text' => trim((string) ($post['excerpt'] ?? '')),
                    'href' => (string) ($post['url'] ?? public_url('blog')),
                    'imageUrl' => trim((string) ($post['featured_image']['url'] ?? '')),
                ];
            }, $posts);
        }

        $clean = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $clean[] = [
                'tag' => trim((string) ($item['tag'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
                'href' => trim((string) ($item['href'] ?? public_url('blog'))),
                'imageUrl' => '',
                'imageId' => (int) ($item['imageId'] ?? 0),
            ];
        }

        return array_slice($clean, 0, max(1, $limit));
    }
}

if (!function_exists('public_management_profile_items')) {
    function public_management_profile_items(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $role = trim((string) ($item['role'] ?? ''));
            $summary = trim((string) ($item['summary'] ?? ''));
            $href = trim((string) ($item['href'] ?? ''));
            $imageId = (int) ($item['imageId'] ?? 0);
            if ($name === '' && $role === '' && $summary === '' && $href === '' && $imageId < 1) {
                continue;
            }
            $clean[] = [
                'name' => $name,
                'role' => $role,
                'summary' => $summary,
                'href' => $href,
                'imageId' => $imageId,
            ];
        }

        if ($clean !== []) {
            return $clean;
        }

        return function_exists('default_management_profile_items') ? default_management_profile_items() : [];
    }
}

if (!function_exists('render_public_block')) {
    function render_public_block(array $block, array $publicServices, array $publicServiceCategories, string $publicPageSlug, string $selectedPublicCategory): void
    {
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        if ($type === 'header_config' || $type === 'footer') {
            return;
        }

        if ($publicPageSlug === 'services' && in_array($type, ['hero', 'service_categories'], true)) {
            return;
        }

        if ($type === 'hero') {
            $kicker = trim((string) ($data['kicker'] ?? ''));
            $headline = trim((string) ($data['headline'] ?? ''));
            $subheadline = trim((string) ($data['subheadline'] ?? ''));
            $primary = is_array($data['primaryCta'] ?? null) ? $data['primaryCta'] : [];
            $secondary = is_array($data['secondaryCta'] ?? null) ? $data['secondaryCta'] : [];
            $proofItems = is_array($data['proofItems'] ?? null) ? $data['proofItems'] : [];
            $imageId = (int) ($data['imageId'] ?? 0);
            $image = $imageId > 0 ? get_media_asset($imageId) : null;
            $fallbackHeroImage = 'https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&w=1400&q=80';
            $isHomeHero = $publicPageSlug === 'home';
            ?>
            <?php if ($isHomeHero): ?>
              <section class="public-hero public-hero-fullscreen">
                <div class="container public-container">
                  <div class="norebase-hero">
                    <div class="norebase-hero-copy">
                      <?php if ($kicker !== ''): ?><span class="hero-chip"><?= e($kicker) ?></span><?php endif; ?>
                      <h1><?= e($headline !== '' ? $headline : 'Launch and grow your business remotely.') ?></h1>
                      <?php if ($subheadline !== ''): ?><p class="lead"><?= e($subheadline) ?></p><?php endif; ?>
                      <div class="hero-actions">
                        <?php if (!empty($primary['label'])): ?><a class="btn btn-primary" href="<?= e((string) ($primary['href'] ?? '#')) ?>"><?= e((string) $primary['label']) ?></a><?php endif; ?>
                        <?php if (!empty($secondary['label'])): ?><a class="btn btn-outline-primary" href="<?= e((string) ($secondary['href'] ?? '#')) ?>"><?= e((string) $secondary['label']) ?></a><?php endif; ?>
                      </div>
                      <?php if ($proofItems !== []): ?>
                        <div class="norebase-hero-points">
                          <?php foreach ($proofItems as $proof): ?>
                            <?php
                            $proofTitle = trim((string) ($proof['title'] ?? ''));
                            $proofText = trim((string) ($proof['text'] ?? ''));
                            if ($proofTitle === '' && $proofText === '') {
                                continue;
                            }
                            ?>
                            <div class="norebase-point-item">
                              <i class="bi bi-check-circle-fill"></i>
                              <span><?= e($proofTitle !== '' ? $proofTitle : $proofText) ?></span>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="norebase-hero-visual">
                      <div class="norebase-hero-circle"></div>
                      <?php if (!empty($image['url'])): ?>
                        <img src="<?= e((string) $image['url']) ?>" alt="<?= e($headline) ?>" loading="lazy" class="norebase-hero-image">
                      <?php else: ?>
                        <img src="<?= e($fallbackHeroImage) ?>" alt="Global business connection support" loading="lazy" class="norebase-hero-image norebase-hero-image-fallback">
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </section>
            <?php else: ?>
              <section class="public-page-crumb-hero">
                <div class="container public-container">
                  <div class="public-page-crumb-shell">
                    <?php if ($kicker !== ''): ?><span class="public-page-crumb-kicker"><?= e($kicker) ?></span><?php endif; ?>
                    <h1 class="public-page-crumb-title"><?= e($headline !== '' ? $headline : ucfirst($publicPageSlug)) ?></h1>
                    <?php if ($subheadline !== ''): ?><p class="public-page-crumb-subtitle"><?= e($subheadline) ?></p><?php endif; ?>
                    <?php if (!empty($primary['label']) || !empty($secondary['label'])): ?>
                      <div class="public-page-crumb-actions">
                        <?php if (!empty($primary['label'])): ?><a class="btn btn-primary" href="<?= e((string) ($primary['href'] ?? '#')) ?>"><?= e((string) $primary['label']) ?></a><?php endif; ?>
                        <?php if (!empty($secondary['label'])): ?><a class="btn btn-outline-light" href="<?= e((string) ($secondary['href'] ?? '#')) ?>"><?= e((string) $secondary['label']) ?></a><?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </section>
            <?php endif; ?>
            <?php
            return;
        }

        if ($type === 'trusted_logos') {
            $title = trim((string) ($data['title'] ?? 'Trusted by ambitious founders and businesses'));
            $items = website_partner_logos();
            if ($items === []) {
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            }
            if ($items === []) {
                $items = [
                    ['title' => 'Skuad'],
                    ['title' => 'Quicket'],
                    ['title' => 'WeWire'],
                    ['title' => 'Workpay'],
                    ['title' => 'Microtraction'],
                    ['title' => 'Bitnob'],
                    ['title' => 'Wakanow'],
                    ['title' => 'Waza'],
                ];
            }
            ?>
            <section class="public-section public-section-tight">
              <div class="container public-container">
                <div class="marketing-strip">
                  <span class="strip-title"><?= e($title) ?></span>
                  <div class="trusted-logos-marquee">
                    <div class="trusted-logos-track">
                      <?php foreach ($items as $item): ?>
                        <?php $image = !empty($item['imageId']) ? get_media_asset((int) $item['imageId']) : null; ?>
                        <article class="trusted-logo-card">
                          <?php if (!empty($image['url'])): ?>
                            <img src="<?= e((string) $image['url']) ?>" alt="<?= e((string) ($item['title'] ?? '')) ?>" loading="lazy">
                          <?php else: ?>
                            <span class="trusted-logo-pill"><?= e((string) ($item['title'] ?? 'iWorks partner')) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['text'])): ?><small><?= e((string) $item['text']) ?></small><?php endif; ?>
                        </article>
                      <?php endforeach; ?>
                      <?php foreach ($items as $item): ?>
                        <?php $image = !empty($item['imageId']) ? get_media_asset((int) $item['imageId']) : null; ?>
                        <article class="trusted-logo-card" aria-hidden="true">
                          <?php if (!empty($image['url'])): ?>
                            <img src="<?= e((string) $image['url']) ?>" alt="" loading="lazy">
                          <?php else: ?>
                            <span class="trusted-logo-pill"><?= e((string) ($item['title'] ?? 'iWorks partner')) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['text'])): ?><small><?= e((string) $item['text']) ?></small><?php endif; ?>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'how_it_works') {
            $steps = is_array($data['steps'] ?? null) ? $data['steps'] : [];
            $kicker = trim((string) ($data['kicker'] ?? 'How it works'));
            $title = trim((string) ($data['title'] ?? 'From signup to operating in days'));
            $subtitle = trim((string) ($data['subtitle'] ?? 'We handle the paperwork so you can focus on building your business'));
            $footnote = trim((string) ($data['footnote'] ?? 'Most service requests are processed quickly once required details are complete.'));
            if ($steps === []) {
                $steps = [
                    [
                        'title' => 'Choose your service',
                        'duration' => '5 minutes',
                        'text' => 'Select the right service, add your requirements, and proceed to checkout.',
                        'icon' => 'bi-ui-checks',
                    ],
                    [
                        'title' => 'We file everything',
                        'duration' => '1-3 business days',
                        'text' => 'Our team processes your paperwork and submits the required filings.',
                        'icon' => 'bi-file-earmark-text',
                    ],
                    [
                        'title' => "You're ready",
                        'duration' => 'Instant updates',
                        'text' => 'Track progress and receive your final delivery directly in your portal.',
                        'icon' => 'bi-patch-check',
                    ],
                ];
            }
            ?>
            <section class="public-section muted workflow-section">
              <div class="container public-container">
                <div class="workflow-heading">
                  <?php if ($kicker !== ''): ?><span class="workflow-kicker"><?= e($kicker) ?></span><?php endif; ?>
                  <?php if ($title !== ''): ?><h2 class="workflow-title"><?= e($title) ?></h2><?php endif; ?>
                  <?php if ($subtitle !== ''): ?><p class="workflow-subtitle"><?= e($subtitle) ?></p><?php endif; ?>
                </div>
                <div class="workflow-grid">
                  <?php foreach ($steps as $index => $step): ?>
                    <?php
                    $stepTitle = trim((string) ($step['title'] ?? ''));
                    $stepText = trim((string) ($step['text'] ?? ''));
                    if ($stepTitle === '' && $stepText === '') {
                        continue;
                    }
                    $stepDuration = trim((string) ($step['duration'] ?? ''));
                    $stepIcon = trim((string) ($step['icon'] ?? 'bi-check2-circle'));
                    if (!preg_match('/^bi-[a-z0-9-]+$/', $stepIcon)) {
                        $stepIcon = 'bi-check2-circle';
                    }
                    ?>
                    <article class="workflow-card">
                      <div class="workflow-card-icon-wrap">
                        <span class="workflow-card-icon"><i class="bi <?= e($stepIcon) ?>"></i></span>
                        <span class="workflow-card-index"><?= (int) ($index + 1) ?></span>
                      </div>
                      <?php if ($stepTitle !== ''): ?><h3><?= e($stepTitle) ?></h3><?php endif; ?>
                      <?php if ($stepDuration !== ''): ?><p class="workflow-card-meta"><?= e($stepDuration) ?></p><?php endif; ?>
                      <?php if ($stepText !== ''): ?><p class="workflow-card-text"><?= e($stepText) ?></p><?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
                <?php if ($footnote !== ''): ?>
                  <div class="workflow-footnote">
                    <span class="workflow-pill"><i class="bi bi-clock-history"></i><?= e($footnote) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'founder_story') {
            $kicker = trim((string) ($data['kicker'] ?? 'About the founder'));
            $title = trim((string) ($data['title'] ?? 'A practical support system for founders who need action, not confusion.'));
            $subtitle = trim((string) ($data['subtitle'] ?? ''));
            $name = trim((string) ($data['name'] ?? 'Founder'));
            $role = trim((string) ($data['role'] ?? ''));
            $summary = trim((string) ($data['summary'] ?? ''));
            $quote = trim((string) ($data['quote'] ?? ''));
            $sections = array_values(array_filter(is_array($data['sections'] ?? null) ? $data['sections'] : [], static function ($item): bool {
                return is_array($item)
                    && (trim((string) ($item['title'] ?? '')) !== '' || trim((string) ($item['text'] ?? '')) !== '');
            }));
            $socials = array_values(array_filter(is_array($data['socials'] ?? null) ? $data['socials'] : [], static function ($item): bool {
                return is_array($item) && trim((string) ($item['href'] ?? '')) !== '';
            }));
            $imageId = (int) ($data['imageId'] ?? 0);
            $image = $imageId > 0 ? get_media_asset($imageId) : null;
            $imageUrl = trim((string) ($image['url'] ?? ''));
            ?>
            <section class="public-section founder-story-section">
              <div class="container public-container">
                <div class="founder-story-shell">
                  <div class="founder-story-head">
                    <?php if ($kicker !== ''): ?><span class="founder-story-kicker"><?= e($kicker) ?></span><?php endif; ?>
                    <?php if ($title !== ''): ?><h2 class="founder-story-title"><?= e($title) ?></h2><?php endif; ?>
                    <?php if ($subtitle !== ''): ?><p class="founder-story-subtitle"><?= e($subtitle) ?></p><?php endif; ?>
                  </div>
                  <div class="founder-story-grid">
                    <aside class="founder-story-sidebar">
                      <article class="founder-story-card">
                        <div class="founder-story-photo<?= $imageUrl === '' ? ' is-empty' : '' ?>">
                          <?php if ($imageUrl !== ''): ?>
                            <img src="<?= e($imageUrl) ?>" alt="<?= e($name !== '' ? $name : 'Founder') ?>" loading="lazy">
                          <?php else: ?>
                            <span><i class="bi bi-person-circle"></i></span>
                          <?php endif; ?>
                          <?php if ($quote !== ''): ?>
                            <blockquote class="founder-story-photo-quote">“<?= e($quote) ?>”</blockquote>
                          <?php endif; ?>
                        </div>
                        <div class="founder-story-identity">
                          <?php if ($name !== ''): ?><h3 class="founder-story-name"><?= e($name) ?></h3><?php endif; ?>
                          <?php if ($role !== ''): ?><p class="founder-story-role"><?= e($role) ?></p><?php endif; ?>
                        </div>
                        <?php if ($summary !== ''): ?><p class="founder-story-summary"><?= e($summary) ?></p><?php endif; ?>
                        <?php if ($socials !== []): ?>
                          <div class="founder-story-socials" aria-label="Founder social links">
                            <?php foreach ($socials as $social): ?>
                              <?php
                              $network = trim((string) ($social['network'] ?? 'website'));
                              $href = trim((string) ($social['href'] ?? ''));
                              $label = trim((string) ($social['label'] ?? ucfirst($network)));
                              if ($href === '') {
                                  continue;
                              }
                              ?>
                              <a class="founder-story-social" href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>">
                                <i class="bi <?= e(public_social_icon_class($network)) ?>"></i>
                                <span class="visually-hidden"><?= e($label !== '' ? $label : ucfirst($network)) ?></span>
                              </a>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </article>
                    </aside>
                    <div class="founder-story-content">
                      <?php foreach ($sections as $index => $section): ?>
                        <?php
                        $sectionTitle = trim((string) ($section['title'] ?? ''));
                        $sectionText = trim((string) ($section['text'] ?? ''));
                        $contentBlocks = public_founder_story_content_blocks($sectionText);
                        ?>
                        <article class="founder-story-panel">
                          <?php if ($sectionTitle !== ''): ?><h3><?= e($sectionTitle) ?></h3><?php endif; ?>
                          <?php if ($contentBlocks === [] && $sectionText !== ''): ?>
                            <p><?= nl2br(e($sectionText)) ?></p>
                          <?php endif; ?>
                          <?php foreach ($contentBlocks as $contentBlock): ?>
                            <?php if (($contentBlock['type'] ?? '') === 'paragraph'): ?>
                              <p><?= e((string) ($contentBlock['text'] ?? '')) ?></p>
                            <?php elseif (($contentBlock['type'] ?? '') === 'list'): ?>
                              <ul class="founder-story-list">
                                <?php foreach (($contentBlock['items'] ?? []) as $item): ?>
                                  <li><?= e((string) $item) ?></li>
                                <?php endforeach; ?>
                              </ul>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'management_profiles') {
            $kicker = trim((string) ($data['kicker'] ?? 'Management'));
            $title = trim((string) ($data['title'] ?? 'Management profiles'));
            $subtitle = trim((string) ($data['subtitle'] ?? ''));
            $ctaLabel = trim((string) ($data['ctaLabel'] ?? ''));
            $ctaHref = trim((string) ($data['ctaHref'] ?? ''));
            $displayStyle = (string) ($data['displayStyle'] ?? 'full');
            if (!in_array($displayStyle, ['compact', 'full'], true)) {
                $displayStyle = 'full';
            }
            $profiles = public_management_profile_items(is_array($data['items'] ?? null) ? $data['items'] : []);
            if ($displayStyle === 'compact') {
                $profiles = array_slice($profiles, 0, 3);
            }
            ?>
            <section class="public-section management-section<?= $displayStyle === 'compact' ? ' management-section-compact' : '' ?>">
              <div class="container public-container">
                <div class="section-shell management-shell">
                  <div class="section-heading section-heading-split management-heading">
                    <div>
                      <?php if ($kicker !== ''): ?><span class="section-kicker"><?= e($kicker) ?></span><?php endif; ?>
                      <?php if ($title !== ''): ?><h2 class="section-title management-title"><?= e($title) ?></h2><?php endif; ?>
                    </div>
                    <?php if ($subtitle !== ''): ?><p class="section-copy management-copy"><?= e($subtitle) ?></p><?php endif; ?>
                  </div>
                  <div class="management-grid<?= $displayStyle === 'compact' ? ' is-compact' : '' ?>">
                    <?php foreach ($profiles as $profile): ?>
                      <?php
                      $name = trim((string) ($profile['name'] ?? ''));
                      $role = trim((string) ($profile['role'] ?? ''));
                      $summary = trim((string) ($profile['summary'] ?? ''));
                      $href = trim((string) ($profile['href'] ?? ''));
                      $imageId = (int) ($profile['imageId'] ?? 0);
                      $image = $imageId > 0 ? get_media_asset($imageId) : null;
                      $tag = $href !== '' ? 'a' : 'article';
                      ?>
                      <<?= $tag ?> class="management-card<?= $href !== '' ? ' has-link' : '' ?>"<?= $href !== '' ? ' href="' . e($href) . '"' : '' ?>>
                        <div class="management-card-media<?= empty($image['url']) ? ' is-empty' : '' ?>">
                          <?php if (!empty($image['url'])): ?>
                            <img src="<?= e((string) $image['url']) ?>" alt="<?= e($name !== '' ? $name : 'Management profile') ?>" loading="lazy">
                          <?php else: ?>
                            <span><i class="bi bi-people-fill"></i></span>
                          <?php endif; ?>
                        </div>
                        <div class="management-card-body">
                          <?php if ($role !== ''): ?><span class="management-card-role"><?= e($role) ?></span><?php endif; ?>
                          <?php if ($name !== ''): ?><h3><?= e($name) ?></h3><?php endif; ?>
                          <?php if ($summary !== ''): ?><p><?= e($summary) ?></p><?php endif; ?>
                          <?php if ($href !== ''): ?><span class="management-card-link">View details <i class="bi bi-arrow-up-right"></i></span><?php endif; ?>
                        </div>
                      </<?= $tag ?>>
                    <?php endforeach; ?>
                  </div>
                  <?php if ($ctaLabel !== '' && $ctaHref !== ''): ?>
                    <div class="management-actions">
                      <a class="btn btn-outline-primary" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'service_categories') {
            $title = trim((string) ($data['title'] ?? 'Turning Ambitions into Global Realities'));
            $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
            ?>
            <section class="public-section norebase-service-categories">
              <div class="container public-container">
                <h2 class="norebase-service-categories-title"><?= e($title) ?></h2>
                <div class="norebase-service-categories-grid">
                  <?php foreach ($categories as $category): ?>
                    <?php
                    $name = trim((string) ($category['name'] ?? ''));
                    $description = trim((string) ($category['description'] ?? ''));
                    $href = trim((string) ($category['href'] ?? public_url('services')));
                    if ($name === '' && $description === '') {
                        continue;
                    }
                    ?>
                    <a class="norebase-service-category-card" href="<?= e($href) ?>">
                      <h3><?= e($name !== '' ? $name : 'Service Category') ?></h3>
                      <?php if ($description !== ''): ?><p><?= e($description) ?></p><?php endif; ?>
                      <span class="norebase-service-category-arrow"><i class="bi bi-arrow-right"></i></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'features' || $type === 'benefit_cards') {
            $title = trim((string) ($data['title'] ?? 'Why iWorks'));
            $items = [];
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $sectionId = $type === 'benefit_cards' ? 'why-iworks' : '';
            ?>
            <section class="public-section"<?= $sectionId !== '' ? ' id="' . e($sectionId) . '"' : '' ?>>
              <div class="container public-container">
                <div class="section-shell">
                  <?php if ($title !== ''): ?><h2 class="section-title"><?= e($title) ?></h2><?php endif; ?>
                  <div class="feature-grid marketing-feature-grid">
                    <?php foreach ($items as $item): ?>
                      <?php $href = trim((string) ($item['href'] ?? '')); ?>
                      <?php $tag = $href !== '' ? 'a' : 'article'; ?>
                      <<?= $tag ?> class="feature-card marketing-feature-card"<?= $href !== '' ? ' href="' . e($href) . '"' : '' ?>>
                        <span class="feature-icon"><i class="bi <?= e((string) ($item['icon'] ?? 'bi-stars')) ?>"></i></span>
                        <h3><?= e((string) ($item['title'] ?? '')) ?></h3>
                        <p><?= e((string) ($item['text'] ?? '')) ?></p>
                      </<?= $tag ?>>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'featured_services') {
            $title = trim((string) ($data['title'] ?? 'Featured services'));
            $subtitle = trim((string) ($data['subtitle'] ?? ''));
            $selectedIds = array_map('intval', is_array($data['serviceIds'] ?? null) ? $data['serviceIds'] : []);
            $services = featured_services_for_home($selectedIds, 4);
            ?>
            <section class="public-section">
              <div class="container public-container">
                <div class="section-shell">
                  <div class="section-heading section-heading-split">
                    <div>
                      <span class="section-kicker">Our Services</span>
                      <h2 class="section-title"><?= e($title) ?></h2>
                    </div>
                    <?php if ($subtitle !== ''): ?><p class="section-copy"><?= e($subtitle) ?></p><?php endif; ?>
                  </div>
                  <div class="featured-services-grid">
                    <?php foreach ($services as $service): ?>
                      <article class="marketing-service-card">
                        <div class="marketing-service-media">
                          <?php public_render_service_media($service); ?>
                        </div>
                        <div class="marketing-service-content">
                          <div class="marketing-service-meta">
                            <span class="marketing-service-category"><?= e(ucwords(str_replace(['-', '_'], ' ', (string) ($service['category'] ?? 'general')))) ?></span>
                            <span class="service-price"><?= e((string) ($service['currency'] ?? default_currency())) ?> <?= number_format((float) ($service['price'] ?? 0), 2) ?></span>
                          </div>
                          <h3><?= e((string) ($service['name'] ?? 'Service')) ?></h3>
                          <p><?= e((string) ($service['short_description'] ?? '')) ?></p>
                          <ul class="marketing-bullets">
                            <?php foreach (array_slice(public_service_bullets($service), 0, 4) as $bullet): ?>
                              <li><?= e($bullet) ?></li>
                            <?php endforeach; ?>
                          </ul>
                          <div class="service-public-actions">
                            <a class="btn btn-outline-primary btn-sm" href="<?= e(public_url('service', ['slug' => (string) ($service['slug'] ?? '')])) ?>">View details</a>
                            <a class="btn btn-primary btn-sm" href="<?= e(portal_url('login')) ?>">Order now</a>
                          </div>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'service_grid') {
            static $serviceGridCounter = 0;
            $serviceGridCounter++;
            $gridId = 'publicServicesGrid' . $serviceGridCounter;
            $title = trim((string) ($data['title'] ?? 'All Services'));
            $subtitle = trim((string) ($data['subtitle'] ?? 'Browse and order business support services remotely.'));
            $notice = trim((string) ($data['notice'] ?? ''));
            $selectedIds = array_map('intval', is_array($data['serviceIds'] ?? null) ? $data['serviceIds'] : []);
            $services = $selectedIds !== [] ? featured_services_for_home($selectedIds, 1000) : $publicServices;
            $selectedCategoryMeta = public_selected_category_meta($publicServiceCategories, $selectedPublicCategory, $title, $subtitle);
            $headingTitle = (string) ($selectedCategoryMeta['title'] ?? $title);
            $headingSubtitle = (string) ($selectedCategoryMeta['subtitle'] ?? $subtitle);
            $headingIcon = (string) ($selectedCategoryMeta['icon'] ?? 'bi-grid');
            $allCount = count($services);
            $noticeText = $notice !== '' ? $notice : 'Browse the right service, review the scope, and continue through the portal remotely.';
            $filtersId = $gridId . '_filters';
            ?>
            <section class="public-section">
              <div class="container public-container">
                <div class="service-directory">
                  <div class="service-directory-banner"><?= e($noticeText) ?></div>
                  <div class="service-directory-shell">
                    <div class="service-directory-top">
                      <div class="service-directory-main">
                        <button class="service-directory-back" type="button" data-back-button data-fallback="<?= e(public_url('home')) ?>">
                          <i class="bi bi-arrow-left"></i>
                          <span>Back</span>
                        </button>
                        <div class="service-directory-heading">
                          <div class="service-directory-heading-row">
                            <span class="service-directory-heading-icon"><i class="bi <?= e($headingIcon) ?>"></i></span>
                            <div>
                              <h2 class="service-directory-title"><?= e($headingTitle) ?></h2>
                              <?php if ($headingSubtitle !== ''): ?><p class="service-directory-subtitle"><?= e($headingSubtitle) ?></p><?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="service-directory-controls">
                        <button type="button" class="service-filter-toggle" data-service-filter-toggle data-target="<?= e($filtersId) ?>" aria-expanded="false">
                          <i class="bi bi-list"></i>
                          <span>Filter</span>
                        </button>
                        <input class="form-control form-control-sm service-directory-search" type="search" placeholder="Search services..." data-service-search data-target="<?= e($gridId) ?>">
                        <div class="service-directory-view-switch" role="group" aria-label="Service view mode">
                          <button type="button" class="service-view-button active" data-service-view="grid" data-target="<?= e($gridId) ?>">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            <span>Grid</span>
                          </button>
                          <button type="button" class="service-view-button" data-service-view="list" data-target="<?= e($gridId) ?>">
                            <i class="bi bi-list-ul"></i>
                            <span>List</span>
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="service-directory-filters" id="<?= e($filtersId) ?>" data-service-filters>
                      <a class="service-directory-chip <?= $selectedPublicCategory === '' ? 'active' : '' ?>" href="<?= e(public_url('services')) ?>">
                        <i class="bi <?= e(public_category_icon_class('all')) ?>"></i>
                        <span>All Services (<?= $allCount ?>)</span>
                      </a>
                      <?php foreach ($publicServiceCategories as $category): ?>
                        <a class="service-directory-chip <?= $selectedPublicCategory === (string) ($category['slug'] ?? '') ? 'active' : '' ?>" href="<?= e(public_url('services', ['category' => (string) ($category['slug'] ?? '')])) ?>">
                          <i class="bi <?= e(public_category_icon_class((string) ($category['slug'] ?? ''))) ?>"></i>
                          <span><?= e((string) ($category['label'] ?? 'Category')) ?> (<?= (int) ($category['count'] ?? 0) ?>)</span>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <div class="service-directory-grid" id="<?= e($gridId) ?>" data-service-grid>
                    <?php foreach ($services as $service): ?>
                      <?php
                      $serviceCategory = (string) ($service['category'] ?? 'general');
                      $hidden = $selectedPublicCategory !== '' && $selectedPublicCategory !== $serviceCategory;
                      $serviceUrl = public_url('service', ['slug' => (string) ($service['slug'] ?? '')]);
                      $summary = trim((string) ($service['short_description'] ?? ''));
                      if ($summary === '') {
                          $summary = 'View service scope, requirements, processing steps, and order options.';
                      }
                      ?>
                      <article class="service-directory-card<?= $hidden ? ' d-none' : '' ?>" data-category="<?= e($serviceCategory) ?>" data-category-hidden="<?= $hidden ? '1' : '0' ?>" data-search="<?= e(strtolower((string) ($service['name'] ?? '') . ' ' . $summary)) ?>" data-service-card>
                        <a class="stretched-link" href="<?= e($serviceUrl) ?>" aria-label="View <?= e((string) ($service['name'] ?? 'service')) ?>"></a>
                        <div class="service-directory-card-head">
                          <span class="service-directory-card-icon"><i class="bi <?= e(public_service_icon_class($service)) ?>"></i></span>
                          <div class="service-directory-card-price-wrap">
                            <div class="service-directory-card-price"><?= e((string) ($service['currency'] ?? default_currency())) ?><?= number_format((float) ($service['price'] ?? 0), 2) ?></div>
                            <span class="service-directory-availability">Available</span>
                          </div>
                        </div>
                        <div class="service-directory-card-body">
                          <h3 class="service-directory-card-title"><?= e((string) ($service['name'] ?? 'Service')) ?></h3>
                          <p class="service-directory-card-summary"><?= e($summary) ?></p>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                  <div class="service-directory-empty d-none" data-service-empty>
                    No services are available in this category yet.
                  </div>
                </div>
              </div>
              <script>
                (function () {
                  var grid = document.getElementById(<?= json_encode($gridId) ?>);
                  if (!grid) return;

                  var storageKey = 'iworks-public-service-view';
                  var buttons = document.querySelectorAll('[data-target="<?= e($gridId) ?>"][data-service-view]');
                  var emptyState = grid.parentElement ? grid.parentElement.querySelector('[data-service-empty]') : null;
                  var filterToggle = document.querySelector('[data-service-filter-toggle][data-target="<?= e($filtersId) ?>"]');
                  var filters = document.getElementById(<?= json_encode($filtersId) ?>);
                  var searchInput = document.querySelector('[data-service-search][data-target="<?= e($gridId) ?>"]');

                  function syncEmptyState() {
                    if (!emptyState) return;
                    var hasVisible = Array.prototype.some.call(grid.querySelectorAll('[data-service-card]'), function (card) {
                      return !card.classList.contains('d-none');
                    });
                    emptyState.classList.toggle('d-none', hasVisible);
                  }

                  function applySearch() {
                    if (!searchInput) {
                      syncEmptyState();
                      return;
                    }
                    var query = String(searchInput.value || '').trim().toLowerCase();
                    Array.prototype.forEach.call(grid.querySelectorAll('[data-service-card]'), function (card) {
                      var hiddenByCategory = card.getAttribute('data-category-hidden') === '1';
                      var haystack = (card.getAttribute('data-search') || '').toLowerCase();
                      var matches = query === '' || haystack.indexOf(query) !== -1;
                      card.classList.toggle('d-none', hiddenByCategory || !matches);
                    });
                    syncEmptyState();
                  }

                  function setView(view) {
                    grid.classList.toggle('is-list-view', view === 'list');
                    buttons.forEach(function (button) {
                      var active = button.getAttribute('data-service-view') === view;
                      button.classList.toggle('active', active);
                      button.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });
                    try { window.localStorage.setItem(storageKey, view); } catch (error) {}
                  }

                  var savedView = 'grid';
                  try { savedView = window.localStorage.getItem(storageKey) || 'grid'; } catch (error) {}
                  setView(savedView === 'list' ? 'list' : 'grid');
                  applySearch();

                  buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                      setView(button.getAttribute('data-service-view') || 'grid');
                    });
                  });

                  if (searchInput) {
                    searchInput.addEventListener('input', applySearch);
                  }

                  if (filterToggle && filters) {
                    filterToggle.addEventListener('click', function () {
                      var isOpen = filters.classList.toggle('is-open');
                      filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });

                    filters.querySelectorAll('a').forEach(function (link) {
                      link.addEventListener('click', function () {
                        filters.classList.remove('is-open');
                        filterToggle.setAttribute('aria-expanded', 'false');
                      });
                    });
                  }

                  document.querySelectorAll('[data-back-button]').forEach(function (button) {
                    button.addEventListener('click', function () {
                      var fallback = button.getAttribute('data-fallback') || '/';
                      if (window.history.length > 1) {
                        window.history.back();
                        return;
                      }
                      window.location.href = fallback;
                    });
                  });
                })();
              </script>
            </section>
            <?php
            return;
        }

        if ($type === 'case_studies') {
            $kicker = trim((string) ($data['kicker'] ?? 'Portfolio'));
            $title = trim((string) ($data['title'] ?? 'Some of our recent projects'));
            $subtitle = trim((string) ($data['subtitle'] ?? ''));
            $ctaLabel = trim((string) ($data['ctaLabel'] ?? ''));
            $ctaHref = trim((string) ($data['ctaHref'] ?? ''));
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $isCaseStudiesPage = $publicPageSlug === 'case-studies';

            $cleanItems = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $projectName = trim((string) ($item['name'] ?? ''));
                $projectCategory = trim((string) ($item['category'] ?? ''));
                $projectSummary = trim((string) ($item['summary'] ?? ''));
                $projectResult = trim((string) ($item['result'] ?? ''));
                $projectLink = trim((string) ($item['link'] ?? ''));
                $imageId = (int) ($item['imageId'] ?? 0);
                $image = $imageId > 0 ? get_media_asset($imageId) : null;
                $imageUrl = trim((string) ($image['url'] ?? ''));
                if ($projectName === '' && $projectCategory === '' && $projectSummary === '' && $projectResult === '' && $projectLink === '' && $imageUrl === '') {
                    continue;
                }
                $cleanItems[] = [
                    'name' => $projectName !== '' ? $projectName : 'Project',
                    'category' => $projectCategory,
                    'summary' => $projectSummary,
                    'result' => $projectResult,
                    'link' => $projectLink,
                    'image' => $imageUrl,
                ];
            }

            if (!$isCaseStudiesPage) {
                $cleanItems = array_slice($cleanItems, 0, 4);
            }
            ?>
            <section class="public-section case-studies-section<?= $isCaseStudiesPage ? ' is-directory' : '' ?>">
              <div class="container public-container">
                <div class="section-shell case-studies-shell">
                  <?php if ($isCaseStudiesPage): ?>
                    <div class="case-studies-head is-centered">
                      <?php if ($kicker !== ''): ?><span class="case-studies-kicker"><?= e($kicker) ?></span><?php endif; ?>
                      <?php if ($title !== ''): ?><h2 class="case-studies-title"><?= e($title) ?></h2><?php endif; ?>
                      <?php if ($subtitle !== ''): ?><p class="case-studies-subtitle"><?= e($subtitle) ?></p><?php endif; ?>
                    </div>
                    <?php if ($cleanItems !== []): ?>
                      <div class="case-studies-grid is-directory">
                        <?php foreach ($cleanItems as $item): ?>
                          <?php
                          $projectName = (string) ($item['name'] ?? 'Project');
                          $projectCategory = trim((string) ($item['category'] ?? ''));
                          $projectSummary = trim((string) ($item['summary'] ?? ''));
                          $projectResult = trim((string) ($item['result'] ?? ''));
                          $projectLink = (string) ($item['link'] ?? '');
                          $imageUrl = (string) ($item['image'] ?? '');
                          ?>
                          <article class="case-study-card">
                            <?php if ($projectLink !== ''): ?>
                              <a class="case-study-card-anchor" href="<?= e($projectLink) ?>" target="_blank" rel="noopener" aria-label="Open case study for <?= e($projectName) ?>"></a>
                            <?php endif; ?>
                            <?php if ($imageUrl !== ''): ?>
                              <div class="case-study-card-media">
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($projectName) ?>" loading="lazy">
                              </div>
                            <?php else: ?>
                              <div class="case-study-card-media case-study-card-media-placeholder">
                                <span>Project image coming soon</span>
                              </div>
                            <?php endif; ?>
                            <div class="case-study-card-body">
                              <div class="case-study-card-meta">
                                <?php if ($projectCategory !== ''): ?><span class="case-study-card-pill"><?= e($projectCategory) ?></span><?php endif; ?>
                                <?php if ($projectLink !== ''): ?>
                                  <a class="case-study-card-link" href="<?= e($projectLink) ?>" target="_blank" rel="noopener" aria-label="Open case study for <?= e($projectName) ?>">
                                    <i class="bi bi-arrow-up-right"></i>
                                  </a>
                                <?php else: ?>
                                  <span class="case-study-card-link is-disabled" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                                <?php endif; ?>
                              </div>
                              <h3><?= e($projectName) ?></h3>
                              <?php if ($projectSummary !== ''): ?><p class="case-study-card-summary"><?= e($projectSummary) ?></p><?php endif; ?>
                              <?php if ($projectResult !== ''): ?>
                                <div class="case-study-card-result">
                                  <span class="case-study-card-result-label">Outcome</span>
                                  <p><?= e($projectResult) ?></p>
                                </div>
                              <?php endif; ?>
                            </div>
                          </article>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="case-studies-empty">
                        <div class="case-studies-empty-icon"><i class="bi bi-briefcase"></i></div>
                        <h3>No case studies published yet</h3>
                        <p>Your published project work will appear here once it is added from the admin Website editor.</p>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="case-studies-layout">
                      <div class="case-studies-intro">
                        <?php if ($kicker !== ''): ?><span class="case-studies-kicker"><?= e($kicker) ?></span><?php endif; ?>
                        <?php if ($title !== ''): ?><h2 class="case-studies-title"><?= e($title) ?></h2><?php endif; ?>
                        <?php if ($subtitle !== ''): ?><p class="case-studies-subtitle"><?= e($subtitle) ?></p><?php endif; ?>
                        <?php if ($ctaLabel !== '' && $ctaHref !== ''): ?>
                          <a class="btn btn-primary case-studies-cta" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
                        <?php endif; ?>
                        <div class="case-studies-intro-note">
                          <span class="case-studies-intro-chip"><?= count($cleanItems) > 0 ? count($cleanItems) : 0 ?> live stories</span>
                          <p>Use this section to spotlight real client work, execution context, and outcomes handled by iWorks.</p>
                        </div>
                      </div>
                      <?php if ($cleanItems !== []): ?>
                        <div class="case-studies-grid">
                          <?php foreach ($cleanItems as $item): ?>
                            <?php
                            $projectName = (string) ($item['name'] ?? 'Project');
                            $projectCategory = trim((string) ($item['category'] ?? ''));
                            $projectSummary = trim((string) ($item['summary'] ?? ''));
                            $projectResult = trim((string) ($item['result'] ?? ''));
                            $projectLink = (string) ($item['link'] ?? '');
                            $imageUrl = (string) ($item['image'] ?? '');
                            ?>
                            <article class="case-study-card">
                              <?php if ($projectLink !== ''): ?>
                                <a class="case-study-card-anchor" href="<?= e($projectLink) ?>" target="_blank" rel="noopener" aria-label="Open case study for <?= e($projectName) ?>"></a>
                              <?php endif; ?>
                              <?php if ($imageUrl !== ''): ?>
                                <div class="case-study-card-media">
                                  <img src="<?= e($imageUrl) ?>" alt="<?= e($projectName) ?>" loading="lazy">
                                </div>
                              <?php else: ?>
                                <div class="case-study-card-media case-study-card-media-placeholder">
                                  <span>Project image coming soon</span>
                                </div>
                              <?php endif; ?>
                              <div class="case-study-card-body">
                                <div class="case-study-card-meta">
                                  <?php if ($projectCategory !== ''): ?><span class="case-study-card-pill"><?= e($projectCategory) ?></span><?php endif; ?>
                                  <?php if ($projectLink !== ''): ?>
                                    <a class="case-study-card-link" href="<?= e($projectLink) ?>" target="_blank" rel="noopener" aria-label="Open case study for <?= e($projectName) ?>">
                                      <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                  <?php else: ?>
                                    <span class="case-study-card-link is-disabled" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                                  <?php endif; ?>
                                </div>
                                <h3><?= e($projectName) ?></h3>
                                <?php if ($projectSummary !== ''): ?><p class="case-study-card-summary"><?= e($projectSummary) ?></p><?php endif; ?>
                                <?php if ($projectResult !== ''): ?>
                                  <div class="case-study-card-result">
                                    <span class="case-study-card-result-label">Outcome</span>
                                    <p><?= e($projectResult) ?></p>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </article>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <div class="case-studies-empty">
                          <div class="case-studies-empty-icon"><i class="bi bi-briefcase"></i></div>
                          <h3>Publish your first use case</h3>
                          <p>Go to the Website editor in admin, add project images and links, and your use cases will appear here automatically.</p>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'testimonials') {
            $title = trim((string) ($data['title'] ?? 'See what founders around the world are saying about their experience'));
            $kicker = trim((string) ($data['kicker'] ?? 'Customer Reviews'));
            $ratingValue = (float) ($data['ratingValue'] ?? 4.8);
            $ratingScale = (float) ($data['ratingScale'] ?? 5);
            $ratingCount = (int) ($data['ratingCount'] ?? 120);
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $summaryStars = public_testimonial_star_states($ratingValue, 5);
            ?>
            <section class="public-section muted testimonials-showcase">
              <div class="container public-container">
                <div class="section-shell testimonials-shell">
                  <div class="testimonials-summary">
                    <?php if ($kicker !== ''): ?><span class="testimonials-kicker"><?= e($kicker) ?></span><?php endif; ?>
                    <div class="testimonials-stars" aria-label="<?= e(number_format($ratingValue, 1)) ?> out of <?= e(number_format($ratingScale, 0)) ?>">
                      <?php foreach ($summaryStars as $state): ?>
                        <span class="testimonials-star-box <?= e($state) ?>"><i class="bi bi-star-fill"></i></span>
                      <?php endforeach; ?>
                    </div>
                    <div class="testimonials-score-line">
                      <strong><?= e(number_format($ratingValue, 1)) ?></strong>
                      <span>out of <?= e(number_format($ratingScale, 0)) ?></span>
                    </div>
                    <p class="testimonials-count">Based on <strong><?= e(number_format($ratingCount)) ?></strong> reviews</p>
                    <?php if ($title !== ''): ?><p class="testimonials-intro"><?= e($title) ?></p><?php endif; ?>
                  </div>
                  <div class="testimonials-rail" role="list" aria-label="Client testimonials">
                    <?php foreach ($items as $item): ?>
                      <?php
                      $name = trim((string) ($item['name'] ?? ''));
                      $role = trim((string) ($item['role'] ?? ''));
                      $company = trim((string) ($item['company'] ?? ''));
                      $location = trim((string) ($item['location'] ?? ''));
                      $text = trim((string) ($item['text'] ?? ''));
                      $itemRating = (float) ($item['rating'] ?? 5);
                      $image = !empty($item['imageId']) ? get_media_asset((int) $item['imageId']) : null;
                      $imageUrl = trim((string) ($image['url'] ?? ''));
                      $cardStars = public_testimonial_star_states($itemRating, 5);
                      $metaParts = array_values(array_filter([$company, $role]));
                      ?>
                      <article class="testimonial-review-card" role="listitem">
                        <div class="testimonial-review-stars" aria-hidden="true">
                          <?php foreach ($cardStars as $state): ?>
                            <span class="testimonial-review-star <?= e($state) ?>"><i class="bi bi-star-fill"></i></span>
                          <?php endforeach; ?>
                        </div>
                        <?php if ($text !== ''): ?><p class="testimonial-review-text">"<?= e($text) ?>"</p><?php endif; ?>
                        <div class="testimonial-review-person">
                          <span class="testimonial-review-avatar">
                            <?php if ($imageUrl !== ''): ?>
                              <img src="<?= e($imageUrl) ?>" alt="<?= e($name !== '' ? $name : 'Client') ?>" loading="lazy">
                            <?php else: ?>
                              <?= e(public_testimonial_initials($name)) ?>
                            <?php endif; ?>
                          </span>
                          <div class="testimonial-review-meta">
                            <strong><?= e($name !== '' ? $name : 'Client') ?></strong>
                            <?php if ($metaParts !== []): ?><span><?= e(implode(' · ', $metaParts)) ?></span><?php endif; ?>
                            <?php if ($location !== ''): ?><small><?= e($location) ?></small><?php endif; ?>
                          </div>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'blog_highlights') {
            $title = trim((string) ($data['title'] ?? 'Insights'));
            $ctaLabel = trim((string) ($data['ctaLabel'] ?? ($publicPageSlug === 'blog' ? '' : 'Read all insights')));
            $ctaHref = trim((string) ($data['ctaHref'] ?? public_url('blog')));
            $limit = $publicPageSlug === 'blog' ? 12 : 3;
            $items = public_blog_highlight_items(is_array($data['items'] ?? null) ? $data['items'] : [], $limit);
            $fallbackImages = [
                'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80',
            ];
            ?>
            <section class="public-section marketing-blog-section" id="insights">
              <div class="container public-container">
                <div class="marketing-blog-head">
                  <div>
                    <span class="section-kicker">Insights</span>
                    <h2 class="section-title marketing-blog-title"><?= e($title) ?></h2>
                  </div>
                  <?php if ($ctaLabel !== '' && $ctaHref !== '' && $publicPageSlug !== 'blog'): ?>
                    <a class="btn btn-outline-primary btn-sm marketing-blog-head-cta" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
                  <?php endif; ?>
                </div>
                <div class="blog-grid marketing-blog-grid">
                  <?php foreach ($items as $index => $item): ?>
                    <?php
                    $href = trim((string) ($item['href'] ?? public_url('blog')));
                    if ($href === '') {
                        $href = public_url('blog');
                    }
                    $tag = trim((string) ($item['tag'] ?? ''));
                    $cardTitle = trim((string) ($item['title'] ?? ''));
                    $cardText = trim((string) ($item['text'] ?? ''));
                    $imageUrl = trim((string) ($item['imageUrl'] ?? ''));
                    if ($imageUrl === '' && !empty($item['imageId'])) {
                        $image = get_media_asset((int) $item['imageId']);
                        $imageUrl = trim((string) ($image['url'] ?? ''));
                    }
                    if ($imageUrl === '') {
                        $imageUrl = $fallbackImages[$index % count($fallbackImages)];
                    }
                    ?>
                    <article class="blog-card marketing-blog-card">
                      <a class="marketing-blog-link-wrap" href="<?= e($href) ?>">
                        <div class="marketing-blog-media">
                          <img src="<?= e($imageUrl) ?>" alt="<?= e($cardTitle !== '' ? $cardTitle : 'Blog highlight') ?>" loading="lazy">
                          <?php if ($tag !== ''): ?><span class="marketing-blog-tag"><?= e($tag) ?></span><?php endif; ?>
                        </div>
                        <div class="marketing-blog-body">
                          <h3><?= e($cardTitle !== '' ? $cardTitle : 'Business support insight') ?></h3>
                          <?php if ($cardText !== ''): ?><p><?= e($cardText) ?></p><?php endif; ?>
                          <span class="marketing-blog-arrow"><i class="bi bi-arrow-right"></i></span>
                        </div>
                      </a>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'faq') {
            static $faqCounter = 0;
            $faqCounter++;
            $faqWrapId = 'publicFaqs' . $faqCounter;
            $items = public_faq_items_enriched(is_array($data['items'] ?? null) ? $data['items'] : []);
            $faqKicker = trim((string) ($data['kicker'] ?? 'FAQ'));
            $faqTitle = trim((string) ($data['title'] ?? 'Common Questions'));
            $faqSubtitle = trim((string) ($data['subtitle'] ?? 'Everything you need to know about iWorks business support services.'));
            ?>
            <section class="public-section public-faq-section">
              <div class="container public-container">
                <div class="marketing-faq-shell">
                  <div class="marketing-faq-head text-center">
                    <?php if ($faqKicker !== ''): ?><span class="marketing-faq-kicker"><?= e($faqKicker) ?></span><?php endif; ?>
                    <h2 class="marketing-faq-title"><?= e($faqTitle !== '' ? $faqTitle : 'Common Questions') ?></h2>
                    <?php if ($faqSubtitle !== ''): ?><p class="marketing-faq-subtitle"><?= e($faqSubtitle) ?></p><?php endif; ?>
                  </div>
                  <div class="accordion public-faq marketing-faq-list" id="<?= e($faqWrapId) ?>">
                    <?php foreach ($items as $index => $item): ?>
                      <?php
                      $question = trim((string) ($item['q'] ?? ''));
                      $answer = trim((string) ($item['a'] ?? ''));
                      if ($question === '' && $answer === '') {
                          continue;
                      }
                      ?>
                      <?php $collapseId = $faqWrapId . '_' . $index; ?>
                      <div class="accordion-item marketing-faq-item">
                        <h3 class="accordion-header" id="heading<?= e((string) $collapseId) ?>">
                          <button class="accordion-button marketing-faq-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapseId) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                            <span class="marketing-faq-question"><?= e($question !== '' ? $question : 'Question') ?></span>
                            <span class="marketing-faq-icon"><i class="bi bi-chevron-down"></i></span>
                          </button>
                        </h3>
                        <div id="<?= e($collapseId) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#<?= e($faqWrapId) ?>">
                          <div class="accordion-body marketing-faq-body"><?= nl2br(e($answer)) ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'contact_form') {
            $title = trim((string) ($data['title'] ?? 'Contact iWorks'));
            $text = trim((string) ($data['text'] ?? ''));
            $submitLabel = trim((string) ($data['submitLabel'] ?? 'Send message')) ?: 'Send message';
            $email = trim((string) ($data['email'] ?? ''));
            $phone = trim((string) ($data['phone'] ?? ''));
            $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
            ?>
            <section class="public-section muted">
              <div class="container public-container">
                <div class="contact-section-shell">
                  <div class="contact-section-copy">
                    <span class="section-kicker">Contact</span>
                    <h2 class="section-title"><?= e($title) ?></h2>
                    <?php if ($text !== ''): ?><p class="section-copy"><?= e($text) ?></p><?php endif; ?>
                    <div class="contact-points">
                      <?php if ($email !== ''): ?><a href="mailto:<?= e($email) ?>"><i class="bi bi-envelope"></i> <?= e($email) ?></a><?php endif; ?>
                      <?php if ($phone !== ''): ?><a href="tel:<?= e($phone) ?>"><i class="bi bi-telephone"></i> <?= e($phone) ?></a><?php endif; ?>
                      <?php if ($whatsapp !== ''): ?><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
                    </div>
                  </div>
                  <div class="contact-form-card">
                    <form method="post" action="<?= e(public_url($publicPageSlug === '' ? 'contact' : $publicPageSlug)) ?>" class="row g-2">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="form_action" value="website_lead">
                      <div class="col-md-6">
                        <label class="form-label">Full name</label>
                        <input class="form-control" name="full_name" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Company name</label>
                        <input class="form-control" name="company_name">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                      </div>
                      <div class="col-12 d-grid">
                        <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'cta_banner') {
            $headline = trim((string) ($data['headline'] ?? ''));
            $text = trim((string) ($data['text'] ?? ''));
            $cta = is_array($data['cta'] ?? null) ? $data['cta'] : [];
            ?>
            <section class="public-section">
              <div class="container public-container">
                <div class="cta-banner marketing-cta-banner">
                  <div>
                    <h3><?= e($headline) ?></h3>
                    <p><?= e($text) ?></p>
                  </div>
                  <?php if (!empty($cta['label'])): ?><a class="btn btn-primary" href="<?= e((string) ($cta['href'] ?? '#')) ?>"><?= e((string) $cta['label']) ?></a><?php endif; ?>
                </div>
              </div>
            </section>
            <?php
            return;
        }

        if ($type === 'rich_text') {
            $title = trim((string) ($data['title'] ?? ''));
            $html = sanitize_limited_html((string) ($data['htmlOrDelta'] ?? ''));
            ?>
            <section class="public-section">
              <div class="container public-container">
                <div class="section-shell">
                  <?php if ($title !== ''): ?><h2 class="section-title"><?= e($title) ?></h2><?php endif; ?>
                  <article class="rich-text"><?= $html ?></article>
                </div>
              </div>
            </section>
            <?php
        }
    }
}
?>

<main class="public-main<?= $publicPageSlug === 'home' ? ' homepage-main' : '' ?>">
  <?php if ($message = flash('success')): ?>
    <div class="container public-container mt-3"><div class="alert alert-success mb-0"><?= e($message) ?></div></div>
  <?php endif; ?>
  <?php if ($error = flash('error')): ?>
    <div class="container public-container mt-3"><div class="alert alert-danger mb-0"><?= e($error) ?></div></div>
  <?php endif; ?>

  <?php foreach ($layout as $block): ?>
    <?php render_public_block($block, $publicServices, $publicServiceCategories, $publicPageSlug, $selectedPublicCategory); ?>
  <?php endforeach; ?>

  <?php if ($publicPageSlug === 'track'): ?>
    <section class="public-section muted">
      <div class="container public-container">
        <div class="track-card">
          <span class="section-kicker">Track order</span>
          <h2 class="section-title mb-3">Check the current status of your job.</h2>
          <form method="post" action="<?= e(public_url('track')) ?>" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="col-md-4">
              <label class="form-label">Order ID</label>
              <input class="form-control" name="job_code" placeholder="JOB-2026XXXX" required>
            </div>
            <div class="col-md-5">
              <label class="form-label">Email or Phone</label>
              <input class="form-control" name="identifier" placeholder="you@example.com or +234..." required>
            </div>
            <div class="col-md-3 d-grid">
              <label class="form-label">&nbsp;</label>
              <button class="btn btn-primary" type="submit">Track status</button>
            </div>
          </form>

          <?php if (!empty($publicTrackResult)): ?>
            <div class="track-result mt-3">
              <div><strong>Job:</strong> <?= e((string) ($publicTrackResult['job_code'] ?? '')) ?></div>
              <div><strong>Service:</strong> <?= e((string) ($publicTrackResult['service_name'] ?? '')) ?></div>
              <div><strong>Status:</strong> <?= e((string) ucfirst((string) ($publicTrackResult['status'] ?? 'pending'))) ?></div>
              <div><strong>Payment:</strong> <?= e((string) ucfirst((string) ($publicTrackResult['payment_status'] ?? 'unpaid'))) ?></div>
              <div class="mt-2"><a href="<?= e(portal_url('login')) ?>" class="btn btn-outline-primary btn-sm">Login for full details</a></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($publicPageSlug === 'contact' && !public_layout_has_block($layout, 'contact_form')): ?>
    <section class="public-section muted">
      <div class="container public-container">
        <div class="contact-card">
          <h2 class="section-title mb-3">Need assistance?</h2>
          <p class="mb-3">Login to open a support ticket, or email our team for pre-order questions.</p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="<?= e(portal_url('login')) ?>">Login to portal</a>
            <a class="btn btn-outline-primary" href="mailto:support@iworksgt.com">Email support</a>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>
