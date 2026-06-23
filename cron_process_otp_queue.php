<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (!is_configured()) {
    fwrite(STDERR, "[otp] Portal is not configured. Update app/config.php first.\n");
    exit(1);
}

$limit = 20;
foreach ($argv ?? [] as $arg) {
    if (!is_string($arg)) {
        continue;
    }
    if (str_starts_with($arg, '--limit=')) {
        $raw = (int) substr($arg, 8);
        if ($raw > 0) {
            $limit = $raw;
        }
    }
}

try {
    $result = process_otp_dispatch_queue($limit);
    fwrite(
        STDOUT,
        '[otp] processed=' . (int) ($result['processed'] ?? 0)
        . ' sent=' . (int) ($result['sent'] ?? 0)
        . ' failed=' . (int) ($result['failed'] ?? 0)
        . "\n"
    );
} catch (Throwable $e) {
    fwrite(STDERR, "[otp] failed: " . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
