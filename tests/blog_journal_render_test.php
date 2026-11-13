<?php
declare(strict_types=1);
/**
 * Render-only Journal regression with fixtures. No DB, uploads or live writes.
 */
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string {
    $base = match ($page) {
        'home' => '/',
        'blog' => '/blog',
        'blog_post' => '/blog/' . rawurlencode((string)($params['slug'] ?? '')),
        default => '/' . $page,
    };
    return $page === 'blog' && $params ? $base . '?' . http_build_query($params) : $base;
}
function iworks_blog_page(int $page, int $per): array {
    $GLOBALS['requestedPerPage'] = $per;
    return $GLOBALS['fixtureFeed'];
}
function csrf_token(): string { return 'csrf-test'; }
function flash(string $type): ?string { return null; }
function sanitize_limited_html(string $html): string { return $html; }
function check_journal(bool $condition, string $description): void {
    if (!$condition) { throw new RuntimeException($description); }
}
$root = dirname(__DIR__);
$post = static function (string $slug, string $title, string $cover = ''): array {
    return [
        'id' => 42, 'slug' => $slug, 'title' => $title,
        'excerpt' => 'Practical steps for founders, from research to registration.',
        'tag' => 'Business setup', 'author_name' => 'iWorks Editorial',
        'published_at' => '2026-11-12 09:30:00',
        'featured_image' => $cover === '' ? null : ['url' => $cover],
        'body_html' => '<h2>Before you begin</h2><p>Prepare documents and research the market.</p>',
    ];
};
$fixtureFeed = [
    'items' => [
        $post('featured-post', 'Doing business in Nigeria', '/uploads/feature.webp'),
        $post('second-post', 'When to register a business'),
        $post('third-post', 'Protecting your brand', '/uploads/brand.webp'),
        $post('fourth-post', 'Grow & scale <carefully>'),
    ],
    'total'=>32,'page'=>1,'pages'=>4,
];
$_GET = [];
ob_start();
require $root . '/views/public-insights.php';
$listing = (string)ob_get_clean();
check_journal($GLOBALS['requestedPerPage'] === 10, 'Archive must use 10 stories per page for one featured plus nine in a full first page.');
check_journal(str_contains($listing, 'iw-journal-featured') && str_contains($listing,'/uploads/feature.webp'),
    'Page one must show a wide featured story with actual CMS media.');
check_journal(substr_count($listing, '<article class="iw-journal-card">') === 3,
    'Every non-featured article should occupy a grid card.');
check_journal(str_contains($listing, 'iw-journal-art-brand') && str_contains($listing, 'iw-journal-no-cover'),
    'Missing images need professionally branded fallback visuals, not broken thumbnails.');
check_journal(str_contains($listing, '/blog/second-post') && str_contains($listing, '/blog/fourth-post'),
    'Every published card must link directly to the correct article.');
check_journal(str_contains($listing, 'Grow &amp; scale &lt;carefully&gt;'),
    'Titles must be HTML escaped.');
check_journal(str_contains($listing,'/blog?p=2') && str_contains($listing,'aria-current="page"'),
    'Pagination must link to actual pages and identify current page.');
check_journal(str_contains($listing,'<time datetime="2026-11-12">'),
    'Article dates must have machine-readable time elements.');
$fixtureFeed = [
    'items' => [$post('later-post', 'How to prepare a filing')],
    'total'=>32,'page'=>2,'pages'=>4,
];
$_GET = ['p'=>'2'];
ob_start();
require $root . '/views/public-insights.php';
$pageTwo = (string)ob_get_clean();
check_journal(!str_contains($pageTwo,'iw-journal-featured-link'),
    'Page two must not displace the first article with a featured card.');
check_journal(str_contains($pageTwo,'/blog/later-post') &&
    str_contains($pageTwo,'rel="prev"') && str_contains($pageTwo,'rel="next"'),
    'Subsequent pages must retain articles and previous/next navigation.');
$blogPost = $post('featured-post', 'Doing business in Nigeria', '/uploads/feature.webp');
$relatedBlogPosts = [$post('second-post','When to register a business')];
$blogComments = [];
$blogLiked = false;
$blogLikes = 3;
ob_start();
require $root . '/views/public-blog-detail.php';
$detail = (string)ob_get_clean();
foreach (['iw-journal-article', 'Before you begin', '/uploads/feature.webp',
          'blog_like', 'blog_comment', 'Related insights', 'iw-journal-card',
          'data-iw-share', 'csrf-test', '/blog/second-post'] as $needle) {
    check_journal(str_contains($detail,$needle), 'Article page missing ' . $needle);
}
check_journal(!str_contains($detail, 'https://norebase.com'), 'Do not copy competitor links or assets.');
$css = file_get_contents($root . '/assets/css/blog-journal.css');
$header = file_get_contents($root . '/views/partials/public-header.php');
$editor = file_get_contents($root . '/views/blog.php');
check_journal(str_contains($header,"['blog', 'blog_post']") && str_contains($header,'blog-journal.css'),
    'Both blog pages must load the dedicated stylesheet.');
check_journal(str_contains($header,'file_get_contents($iwJournalCss)'),
    'Blog CSS must render on cPanel split-domain installations.');
check_journal(str_contains($css,'grid-template-columns:repeat(3,minmax(0,1fr))') &&
    str_contains($css,'grid-template-columns:repeat(2,minmax(0,1fr))') &&
    str_contains($css,'grid-template-columns:minmax(0,1fr)'),
    'Grid must adapt from desktop to tablet to mobile.');
check_journal(str_contains($css,'Georgia') && str_contains($css,'#e86720'),
    'Archive and article headings require balanced serif typography and orange accents.');
check_journal(str_contains($css,'prefers-reduced-motion:reduce') &&
    str_contains($css,'focus-visible'), 'Reduced motion and keyboard focus must be supported.');
check_journal(str_contains($editor,'recommended 1200 × 630 px'),
    'CMS editors must see recommended cover proportions.');
echo "blog_journal_render_test passed\n";
