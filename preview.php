<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

require_login();
$user = current_user();

function send_preview_error(Throwable $e): never
{
    $status = (int) $e->getCode();
    if ($status < 400 || $status > 599) {
        $status = 400;
    }

    $isMeta = strtolower(trim((string) ($_GET['mode'] ?? ''))) === 'meta';
    if ($isMeta) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code($status);
    exit($e->getMessage());
}

function etag_for_file(string $path): string
{
    $mtime = @filemtime($path) ?: time();
    $size = @filesize($path) ?: 0;
    return '"' . sha1($path . '|' . $mtime . '|' . $size) . '"';
}

function maybe_send_not_modified(string $etag, int $mtime): bool
{
    $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
        http_response_code(304);
        return true;
    }

    $ifModifiedSince = trim((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? ''));
    if ($ifModifiedSince !== '') {
        $clientTs = strtotime($ifModifiedSince);
        if ($clientTs !== false && $clientTs >= $mtime) {
            http_response_code(304);
            return true;
        }
    }

    return false;
}

function send_inline_preview_file(string $path, string $fileName = '', string $mime = ''): never
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('Stored file missing.');
    }

    $name = trim($fileName) !== '' ? $fileName : basename($path);
    $type = trim($mime) !== '' ? $mime : (mime_content_type($path) ?: 'application/octet-stream');
    $size = (int) filesize($path);
    $mtime = (int) filemtime($path);
    $etag = etag_for_file($path);
    $start = 0;
    $end = max(0, $size - 1);
    $isPartial = false;

    $rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));
    if ($size > 0 && $rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $m)) {
        $rangeStart = $m[1] !== '' ? (int) $m[1] : null;
        $rangeEnd = $m[2] !== '' ? (int) $m[2] : null;

        if ($rangeStart === null && $rangeEnd !== null) {
            $start = max(0, $size - $rangeEnd);
            $end = $size - 1;
            $isPartial = true;
        } elseif ($rangeStart !== null && $rangeEnd === null) {
            $start = max(0, $rangeStart);
            $end = $size - 1;
            $isPartial = true;
        } elseif ($rangeStart !== null && $rangeEnd !== null) {
            $start = max(0, $rangeStart);
            $end = min($size - 1, $rangeEnd);
            $isPartial = true;
        }

        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
    }

    $contentLength = ($end - $start) + 1;

    header('Content-Type: ' . $type);
    header('Content-Disposition: inline; filename="' . basename($name) . '"');
    header('Content-Length: ' . $contentLength);
    header('Cache-Control: private, max-age=900, must-revalidate');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('ETag: ' . $etag);
    header('X-Content-Type-Options: nosniff');
    header('Accept-Ranges: bytes');

    if ($isPartial) {
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    if (maybe_send_not_modified($etag, $mtime)) {
        exit;
    }

    if (!$isPartial) {
        readfile($path);
        exit;
    }

    $fp = fopen($path, 'rb');
    if ($fp === false) {
        http_response_code(500);
        exit('Could not open file for preview.');
    }

    try {
        fseek($fp, $start);
        $remaining = $contentLength;
        while ($remaining > 0 && !feof($fp)) {
            $chunkSize = min(8192, $remaining);
            $chunk = fread($fp, $chunkSize);
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
        }
    } finally {
        fclose($fp);
    }

    exit;
}

try {
    $target = resolve_portal_file_request($user, $_GET);
    $path = (string) ($target['path'] ?? '');
    $name = (string) ($target['name'] ?? basename($path));
    $mime = (string) ($target['mime'] ?? '');

    if (!is_file($path)) {
        throw file_access_error('Stored file missing.', 404);
    }

    $mode = strtolower(trim((string) ($_GET['mode'] ?? '')));
    if ($mode === 'meta') {
        $resolvedMime = $mime !== '' ? $mime : (mime_content_type($path) ?: 'application/octet-stream');
        $size = (int) filesize($path);
        $mtime = (int) filemtime($path);
        $etag = etag_for_file($path);

        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: private, max-age=180, must-revalidate');
        echo json_encode([
            'success' => true,
            'name' => $name,
            'mime' => $resolvedMime,
            'size' => $size,
            'kind' => detect_preview_kind($resolvedMime, $name),
            'updated_at' => gmdate('c', $mtime),
            'etag' => $etag,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    send_inline_preview_file($path, $name, $mime);
} catch (Throwable $e) {
    send_preview_error($e);
}
