<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

require_login();
$user = current_user();

function send_download_file(string $path, string $fileName = '', string $mime = ''): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('Stored file missing.');
    }

    $name = trim($fileName) !== '' ? $fileName : basename($path);
    $type = trim($mime) !== '' ? $mime : (mime_content_type($path) ?: 'application/octet-stream');

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $type);
    header('Content-Disposition: attachment; filename="' . basename($name) . '"');
    header('Content-Length: ' . filesize($path));
    header('Pragma: public');
    header('Cache-Control: private, max-age=0, must-revalidate');

    readfile($path);
    exit;
}

try {
    $target = resolve_portal_file_request($user, $_GET);
    send_download_file(
        (string) ($target['path'] ?? ''),
        (string) ($target['name'] ?? ''),
        (string) ($target['mime'] ?? '')
    );
} catch (Throwable $e) {
    $status = (int) $e->getCode();
    if ($status < 400 || $status > 599) {
        $status = 400;
    }
    http_response_code($status);
    exit($e->getMessage());
}
