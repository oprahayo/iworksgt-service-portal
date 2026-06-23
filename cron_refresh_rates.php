<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (!is_configured()) {
    fwrite(STDERR, "[rates] Portal is not configured. Update app/config.php first.\n");
    exit(1);
}

$force = in_array('--force', $argv ?? [], true);

try {
    $state = refresh_crypto_rates_cache($force);
    $source = (string) ($state['source'] ?? 'fallback');
    $updated = (string) ($state['last_updated_at'] ?? now());
    $rates = is_array($state['rates'] ?? null) ? $state['rates'] : [];

    save_daily_crypto_rate_snapshot($rates, $source);

    fwrite(STDOUT, "[rates] source={$source} updated={$updated}\n");
    foreach (crypto_supported_assets() as $asset) {
        $rate = (float) ($rates[$asset] ?? 0);
        if ($rate <= 0) {
            continue;
        }
        fwrite(STDOUT, "[rates] {$asset}=USD " . number_format($rate, 10, '.', '') . "\n");
    }

    if (!empty($state['warning'])) {
        fwrite(STDOUT, "[rates] warning=" . (string) $state['warning'] . "\n");
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[rates] failed: " . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
