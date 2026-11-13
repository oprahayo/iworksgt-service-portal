<?php
declare(strict_types=1);

// Exercise the REAL legacy schema entrypoints, stub only I/O and the
// deployment authority flag. No production config or network required.
$GLOBALS['ddlAttempts'] = 0;
function iworks_schema_mutation_allowed(): bool { return false; }
function is_configured(): bool { return true; }
function schema_table_exists(string $table): bool { return false; }
function db_execute(string $query, array $params = []): bool
{
    $GLOBALS['ddlAttempts']++;
    throw new RuntimeException('DDL unexpectedly attempted.');
}
require __DIR__ . '/../app/domain.php';
require __DIR__ . '/../app/job_reviews.php';
require __DIR__ . '/../app/cac_filing.php';
require __DIR__ . '/../app/trademark_applications.php';
require __DIR__ . '/../app/stripe_card_vault.php';
ensure_crypto_schema_upgrades();
ensure_media_schema_upgrades();
foreach ([
    static fn() => ensure_job_review_schema(),
    static fn() => cac_filing_schema(),
    static fn() => trademark_ensure_schema(),
    static fn() => stripe_vault_schema(),
] as $fn) {
    try {
        $fn();
        throw new RuntimeException('Missing schema must fail closed for web requests.');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'controlled database schema update')) {
            throw $e;
        }
    }
}
if ($GLOBALS['ddlAttempts'] !== 0) {
    throw new RuntimeException('A read-only request executed an ALTER or CREATE.');
}
$bootstrap = file_get_contents(__DIR__ . '/../app/bootstrap.php');
if (str_contains($bootstrap, 'ensure_crypto_schema_upgrades();')
    || str_contains($bootstrap, 'ensure_media_schema_upgrades();')
    || str_contains($bootstrap, 'ensure_job_review_schema();')) {
    throw new RuntimeException('Bootstrap must not silently perform schema upgrades.');
}
$policy = file_get_contents(__DIR__ . '/../app/helpers.php');
if (!str_contains($policy, "PHP_SAPI === 'cli'")
    || !str_contains($policy, "'IWORKS_SCHEMA_MIGRATION'")) {
    throw new RuntimeException('Schema DDL must require explicit CLI authorization.');
}
$script = file_get_contents(__DIR__ . '/../scripts/migrate_legacy_schema.php');
if (!str_contains($script, "'--backup-confirmed'")
    || !str_contains($script, "define('IWORKS_SCHEMA_MIGRATION', true);")) {
    throw new RuntimeException('No backup-gated CLI migration exists.');
}
echo "f22_controlled_schema_migrations_test passed\n";
