<?php

declare(strict_types=1);

require __DIR__ . '/../../../app/bootstrap.php';

header('Content-Type: application/json');

if (!method_is('POST')) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.'], JSON_UNESCAPED_SLASHES);
    exit;
}

require_roles(['admin']);

$raw = file_get_contents('php://input');
$payload = json_decode(is_string($raw) ? $raw : '', true);
if (!is_array($payload)) {
    $payload = [];
}

try {
    $output = ai_generate_intake_schema($payload);
    echo json_encode([
        'success' => true,
        'output' => $output,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}
