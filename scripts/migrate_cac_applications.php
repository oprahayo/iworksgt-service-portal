<?php
declare(strict_types=1);
// CLI-only, idempotent schema additions. Existing jobs and invoices are intact.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
require dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) { fwrite(STDERR,"iWorks is not configured.\n"); exit(1); }
try {
    cac_ensure_schema();
    support_links_ensure_schema();
    if (!schema_table_exists('cac_applications')
        || !schema_table_exists('cac_application_files')
        || !schema_table_exists('support_ticket_jobs')) {
        throw new RuntimeException('Registration or support link schema missing.');
    }
    echo "CAC private application records, document mapping and support associations ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"CAC schema migration failed: ".$e->getMessage()."\n");
    exit(1);
}
