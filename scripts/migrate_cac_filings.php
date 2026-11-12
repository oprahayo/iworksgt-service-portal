<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
require dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) { fwrite(STDERR,"Application configuration is incomplete.\n"); exit(1); }
try {
    cac_filing_schema();
    if (!schema_table_exists('cac_filing_applications')) {
        throw new RuntimeException('CAC prepayment draft table could not be verified.');
    }
    cac_ensure_private_storage();
    echo "CAC prepayment filing schema and private document storage ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "CAC schema migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
