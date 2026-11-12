<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
require dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) {
    fwrite(STDERR, "iWorks is not configured. Card vault migration aborted.\n");
    exit(1);
}
try {
    stripe_vault_schema();
    if (!schema_table_exists('stripe_customer_vault')) {
        throw new RuntimeException('Stripe customer vault table was not created.');
    }
    echo "Stripe customer vault mapping ready. No raw payment card data is stored.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Card vault migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
