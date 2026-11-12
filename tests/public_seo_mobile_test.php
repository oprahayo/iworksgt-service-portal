<?php
declare(strict_types=1);
// Fully offline contracts: XML discovery, canonical URL, structured data,
// robots instructions and private-route indexability.
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page = 'home', array $params = []): string {
    $base = 'https://portal.iworksgt.com/';
    if ($page === 'service') {
        return $base . 'services/' . rawurlencode((string) ($params['slug'] ?? ''));
    }
    if ($page === 'blog_post') {
        return $base . 'blog/' . rawurlencode((string) ($params['slug'] ?? ''));
    }
    return $base . ($page === 'home' ? '' : $page) . ($params !== [] ? '?' . http_build_query($params) : '');
}
function public_absolute_url(string $path): string { return 'https://portal.iworksgt.com/' . ltrim($path, '/'); }
function check(bool $pass, string $reason): void { if (!$pass) throw new RuntimeException($reason); }
require dirname(__DIR__) . '/app/public_seo.php';
require dirname(__DIR__) . '/app/public_marketing.php';
$snippet = iworks_seo_excerpt('<h3>Corporate Affairs Commission registration</h3><p>Custom website and remote filing support.</p>');
check(!str_contains($snippet, '<'), 'Description must not contain HTML.');
check(iworks_seo_excerpt(str_repeat('business ', 35)) !== str_repeat('business ', 35), 'Long descriptions must be shortened.');
check(iworks_seo_canonical('services', ['utm_source'=>'ads']) === 'https://portal.iworksgt.com/services', 'Tracking parameters must not pollute canonical URLs.');
check(iworks_seo_canonical('services', ['category'=>'growth']) === 'https://portal.iworksgt.com/services?category=growth', 'Category canonical must be stable.');
check(iworks_seo_canonical('service', ['slug'=>'incorporated-trustees','utm'=>'ads']) === 'https://portal.iworksgt.com/services/incorporated-trustees', 'Public service URL should be canonical.');
check(iworks_seo_canonical('service', ['slug'=>'<malicious>']) === '', 'Invalid service slugs must not enter canonical tags.');
$service = ['name' => 'Incorporated Trustees', 'short_description' => 'Register a trust or association with iWorks', 'price' => 180000, 'currency' => 'NGN'];
$structured = json_decode(iworks_seo_jsonld('service', $service, public_url('service',['slug'=>'incorporated-trustees'])), true);
check(is_array($structured) && $structured['@type'] === 'Service', 'Published service must have Schema.org Service metadata.');
check($structured['offers']['priceCurrency'] === 'NGN' && $structured['offers']['price'] === '180000.00', 'Actual backend currency and price must be reflected.');
check($structured['provider']['name'] === 'iWORKS GLOBAL LTD', 'Structured data must identify the business.');
check(!isset($structured['aggregateRating']), 'Do not invent review schema.');
check(!isset($structured['areaServed']), 'Do not incorrectly restrict international services.');
$article = json_decode(iworks_seo_jsonld('blog_post', ['title'=>'Business formation explained', 'published_at'=>'2026-10-12'], 'https://portal.iworksgt.com/blog/formation'), true);
check(($article['@type'] ?? '') === 'BlogPosting', 'Articles must use BlogPosting type.');

$xml = iworks_seo_sitemap_xml(
  [['slug'=>'incorporated-trustees','updated_at'=>'2026-11-12'], ['slug'=>'trademark','updated_at'=>'2026-11-11']],
  [['slug'=>'about','updated_at'=>'2026-10-01'],['slug'=>'track','updated_at'=>'2026-10-01'],['slug'=>'home','updated_at'=>'2026-10-01']],
  [['slug'=>'how-to-register','published_at'=>'2026-11-01']]
);
check(str_contains($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'), 'Valid XML namespace missing.');
check(substr_count($xml, '<loc>https://portal.iworksgt.com/') === 6, 'Expected home, services, about, 2 services and 1 article only.');
check(str_contains($xml, '/services/incorporated-trustees</loc>'), 'Service omitted from sitemap.');
check(str_contains($xml, '/blog/how-to-register</loc>'), 'Published article omitted from sitemap.');
check(!str_contains($xml, 'https://portal.iworksgt.com/track'), 'Private job lookup must not be indexed.');
check(substr_count($xml, '<loc>https://portal.iworksgt.com/</loc>') === 1, 'Homepage canonical must not be duplicated.');
$robots = iworks_seo_robots_text();
check(str_contains($robots, 'Sitemap: https://portal.iworksgt.com/sitemap.xml'), 'Robots should advertise live sitemap.');
check(!str_contains($robots, 'Disallow: /services'), 'Published services must remain crawlable.');

$header = file_get_contents(dirname(__DIR__) . '/views/partials/public-header.php');
$router = file_get_contents(dirname(__DIR__) . '/index.php');
$rewrite = file_get_contents(dirname(__DIR__) . '/.htaccess');
$guest = file_get_contents(dirname(__DIR__) . '/views/partials/public-footer.php');
check(str_contains($header, 'rel="canonical"'), 'Missing canonical link in public header.');
check(str_contains($header, 'application/ld+json'), 'Missing JSON-LD in public header.');
check(str_contains($header, 'name="robots" content="noindex,follow"'), 'Private pages must be noindexed.');
check(str_contains($router, "if (\$page === 'seo_sitemap')"), 'Missing sitemap route.');
check(str_contains($rewrite, 'sitemap\\.xml'), 'Apache must rewrite /sitemap.xml.');
check(str_contains($rewrite, 'robots\\.txt'), 'Apache must rewrite /robots.txt.');
check(str_contains($guest, 'iwEmbeddedPublicAuth'), 'Popup authentication headings must not add extra public H1 tags.');
echo "public_seo_mobile_test passed\n";

$marketing = iworks_search_service_meta(['slug'=>'business-name-registration-cac-nigeria']);
check(str_contains($marketing['title'], 'Register a Business Name in Nigeria'), 'Business name search intent missing.');
check(str_contains($marketing['description'], 'CAC'), 'Business registration snippet missing CAC context.');
$website = iworks_search_service_meta(['slug'=>'website-design-development']);
check(str_contains($website['title'], 'Website Design in Nigeria'), 'Remote web design title missing.');
check(iworks_search_service_meta(['slug'=>'unknown-catalog-item']) === [], 'Unknown services should retain CMS metadata.');
$guidance = iworks_search_service_guide(['slug'=>'business-name-registration-cac-nigeria']);
check(count($guidance['steps']) === 3 && count($guidance['questions']) === 2, 'CAC service guide must be substantive.');
check(str_starts_with($guidance['official'], 'https://cac.gov.ng/'), 'CAC source must use official website.');
$header = file_get_contents(dirname(__DIR__) . '/views/partials/public-header.php');
check(str_contains($header, 'iworks_search_service_meta'), 'Public header must load service-specific metadata.');
$detail = file_get_contents(dirname(__DIR__) . '/views/public-service-detail.php');
check(str_contains($detail, 'iw-search-guide'), 'Service detail must include search guidance.');
