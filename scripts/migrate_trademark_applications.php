<?php
declare(strict_types=1);
// CLI-only, idempotent migration for customer trademark application drafts.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
require dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) {
    fwrite(STDERR, "iWorks is not configured. Aborting trademark migration.\n");
    exit(1);
}
try {
    trademark_ensure_schema();
    $check = schema_table_exists('trademark_applications');
    if (!$check) { throw new RuntimeException('Trademark applications table not found after migration.'); }
    echo "Trademark applications schema ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Trademark migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
