<?php
declare(strict_types=1);

/**
 * Public indexability helpers. Only published pages, public active services
 * and published articles may be included in XML sitemaps.
 */
function iworks_seo_excerpt(string $text, int $max = 155): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($text === '') { return ''; }
    $max = max(50, min(240, $max));
    $len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($len <= $max) { return $text; }
    $prefix = function_exists('mb_substr') ? mb_substr($text, 0, $max - 1, 'UTF-8') : substr($text, 0, $max - 1);
    $prefix = preg_replace('/\s+\S*$/u', '', $prefix) ?: $prefix;
    return rtrim($prefix, " \t\n\r\0\x0B,.;:") . '…';
}

function iworks_seo_allowed_slug(string $slug): bool
{
    return preg_match('/^[a-z0-9][a-z0-9-]{0,120}$/D', $slug) === 1;
}

function iworks_seo_canonical(string $page, array $params = []): string
{
    $page = trim($page);
    if ($page === 'service' || $page === 'blog_post') {
        $slug = trim((string) ($params['slug'] ?? ''));
        if (!iworks_seo_allowed_slug($slug)) { return ''; }
        return public_url($page, ['slug' => $slug]);
    }
    if ($page === 'services') {
        $category = trim((string) ($params['category'] ?? ''));
        if (iworks_seo_allowed_slug($category)) { return public_url('services', ['category' => $category]); }
    }
    return public_url($page === '' ? 'home' : $page);
}

function iworks_seo_jsonld(string $type, array $record, string $canonical, string $description = ''): string
{
    if ($canonical === '') { return ''; }
    $provider = ['@type' => 'Organization', 'name' => 'iWORKS GLOBAL LTD', 'url' => public_url('home')];
    if ($type === 'service') {
        $name = trim((string) ($record['name'] ?? ''));
        if ($name === '') { return ''; }
        $obj = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $name,
            'serviceType' => $name,
            'description' => $description !== '' ? $description : iworks_seo_excerpt((string) ($record['short_description'] ?? $record['description'] ?? '')),
            'url' => $canonical,
            'provider' => $provider,
        ];
        $price = (float) ($record['price'] ?? 0);
        $currency = strtoupper(trim((string) ($record['currency'] ?? 'NGN')));
        if ($price > 0 && preg_match('/^[A-Z]{3}$/D', $currency)) {
            $obj['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => $currency,
                'price' => number_format($price, 2, '.', ''),
                'url' => $canonical,
            ];
        }
    } elseif ($type === 'blog_post') {
        $name = trim((string) ($record['title'] ?? ''));
        if ($name === '') { return ''; }
        $obj = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $name,
            'description' => $description,
            'url' => $canonical,
            'mainEntityOfPage' => $canonical,
            'publisher' => $provider,
        ];
        foreach (['datePublished' => 'published_at', 'dateModified' => 'updated_at'] as $key => $field) {
            $time = strtotime((string) ($record[$field] ?? ''));
            if ($time) { $obj[$key] = gmdate('c', $time); }
        }
    } elseif ($type === 'home') {
        $obj = $provider + ['@context' => 'https://schema.org'];
    } else {
        return '';
    }
    return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '';
}

/**
 * Visible navigation breadcrumb structured data for human-facing service
 * detail and published article pages. Does not invent prices or reviews.
 */
function iworks_seo_breadcrumb_jsonld(string $type, array $record, string $canonical): string
{
    if ($canonical === '' || !in_array($type, ['service', 'blog_post'], true)) { return ''; }
    $title = trim((string) ($record[$type === 'service' ? 'name' : 'title'] ?? ''));
    if ($title === '') { return ''; }
    $items = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => public_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $type === 'service' ? 'Services' : 'Insights', 'item' => public_url($type === 'service' ? 'services' : 'blog')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $canonical],
    ];
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
       | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '';
}

function iworks_seo_sitemap_xml(array $services, array $pages, array $posts): string
{
    $urls = [];
    $add = static function (string $url, string $date = '') use (&$urls): void {
        if ($url === '' || isset($urls[$url])) { return; }
        $lastmod = '';
        $stamp = $date !== '' ? strtotime($date) : false;
        if ($stamp !== false) { $lastmod = gmdate('Y-m-d', $stamp); }
        $urls[$url] = $lastmod;
    };
    $add(public_url('home'));
    $add(public_url('services'));
    foreach ($pages as $record) {
        $slug = trim((string) ($record['slug'] ?? ''));
        // The tracking form and authenticated account routes are deliberately not indexed.
        if (!iworks_seo_allowed_slug($slug) || in_array($slug, ['home','track','login'], true)) { continue; }
        $add(public_url($slug), (string) ($record['updated_at'] ?? ''));
    }
    foreach ($services as $record) {
        $slug = trim((string) ($record['slug'] ?? ''));
        if (!iworks_seo_allowed_slug($slug)) { continue; }
        $add(public_url('service', ['slug' => $slug]), (string) ($record['updated_at'] ?? ''));
    }
    foreach ($posts as $record) {
        $slug = trim((string) ($record['slug'] ?? ''));
        if (!iworks_seo_allowed_slug($slug)) { continue; }
        $add(public_url('blog_post', ['slug' => $slug]), (string) ($record['updated_at'] ?? $record['published_at'] ?? ''));
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $url => $lastmod) {
        $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
        if ($lastmod !== '') { $xml .= '<lastmod>' . $lastmod . '</lastmod>'; }
        $xml .= "</url>\n";
    }
    return $xml . "</urlset>\n";
}

function iworks_seo_public_sitemap(): string
{
    $services = list_public_services_catalog();
    // The site has a published page CMS; only published pages are ever surfaced.
    $pages = db_all("SELECT slug, updated_at FROM pages WHERE status = 'published'");
    $posts = schema_table_exists('blog_posts')
        ? db_all("SELECT slug, updated_at, published_at FROM blog_posts WHERE status = 'published'")
        : [];
    return iworks_seo_sitemap_xml($services, $pages, $posts);
}

function iworks_seo_robots_text(): string
{
    return "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /setup.php\n"
        . "Disallow: /uploads/private/\n"
        . "Sitemap: " . public_absolute_url('sitemap.xml') . "\n";
}
