<?php
/**
 * Fixed, public Gansell screenshot delivery for the split iworksgt.com /
 * portal.iworksgt.com document-root deployment.
 *
 * No arbitrary paths or user-supplied files can be served. Do not include
 * private application data, downloads, uploads, or checkout information.
 */
declare(strict_types=1);

function iworks_solutions_media_allowed(): array
{
    return [
        'gansell-home-desktop.png',
        'gansell-home-mobile.png',
        'ab-african-store-desktop.png',
        'ab-african-store-mobile.png',
    ];
}

function iworks_solutions_media_path(string $name, string $portalRoot): ?string
{
    if (!in_array($name, iworks_solutions_media_allowed(), true)) {
        return null;
    }
    $dir = realpath(rtrim($portalRoot, '/') . '/assets/branding/solutions');
    if ($dir === false || !is_dir($dir)) {
        return null;
    }
    $requested = $dir . DIRECTORY_SEPARATOR . $name;
    if (is_link($requested) || !is_file($requested) || !is_readable($requested)) {
        return null;
    }
    $path = realpath($requested);
    if ($path === false || dirname($path) !== $dir) {
        return null;
    }
    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return null;
    }
    $magic = fread($handle, 8);
    fclose($handle);
    return $magic === "\x89PNG\r\n\x1a\n" ? $path : null;
}

/**
 * Data-URI fallback for the four approved, real Gansell screenshots.
 * This is rendered from the same PHP source as the public page, so the
 * browser does not depend on an Apache rewrite, a separate asset web root,
 * a CDN, or a JS error handler to show the photographs.
 *
 * Screenshots total ~1.4 MB; this trade-off is intentional for a small
 * fixed product page that previously shipped with broken media URLs.
 * Images are real captures, NOT generated mockups.
 */
function iworks_solutions_embedded_image(string $name, string $portalRoot): string
{
    static $cached = [];
    if (array_key_exists($name, $cached)) {
        return $cached[$name];
    }
    $path = iworks_solutions_media_path($name, $portalRoot);
    if ($path === null) {
        return $cached[$name] = '';
    }
    $size = filesize($path);
    if ($size === false || $size < 10000 || $size > 2000000) {
        return $cached[$name] = '';
    }
    $bytes = file_get_contents($path);
    if ($bytes === false || strlen($bytes) !== $size) {
        return $cached[$name] = '';
    }
    return $cached[$name] = 'data:image/png;base64,' . base64_encode($bytes);
}

function iworks_serve_solutions_media(string $name, string $portalRoot): void
{
    $path = iworks_solutions_media_path($name, $portalRoot);
    if ($path === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo 'Image not found.';
        return;
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD');
        header('Cache-Control: no-store');
        return;
    }

    $etag = '"' . hash_file('sha256', $path) . '"';
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400, stale-while-revalidate=86400');
    header('ETag: ' . $etag);
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex');
    header('Content-Disposition: inline');
    if ((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        return;
    }

    $length = filesize($path);
    if ($length !== false) {
        header('Content-Length: ' . (int) $length);
    }
    if ($method === 'GET') {
        readfile($path);
    }
}
