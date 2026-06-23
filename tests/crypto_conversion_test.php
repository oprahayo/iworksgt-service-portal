<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function assert_same(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ' failed. Expected: ' . var_export($expected, true) . ', got: ' . var_export($actual, true));
    }
}

function assert_close(float $expected, float $actual, float $tolerance, string $label): void
{
    if (abs($expected - $actual) > $tolerance) {
        throw new RuntimeException($label . ' failed. Expected: ' . $expected . ', got: ' . $actual);
    }
}

$rates = [
    'USDT' => 1.0,
    'LTC' => 80.0,
    'BTC' => 64000.0,
];

$quote = crypto_quote_from_rates(160000.0, 'NGN', $rates, 1600.0);
assert_close(100.0, (float) $quote['usd_amount'], 0.000001, 'USD conversion');
assert_close(100.0, (float) $quote['assets']['USDT']['amount_internal'], 0.000001, 'USDT amount');
assert_close(1.25, (float) $quote['assets']['LTC']['amount_internal'], 0.000001, 'LTC amount');
assert_close(0.0015625, (float) $quote['assets']['BTC']['amount_internal'], 0.00000001, 'BTC amount');

assert_same('TRC20', normalize_crypto_network('USDT', ''), 'USDT default network');
assert_same(null, normalize_crypto_network('BTC', 'TRC20'), 'BTC network');

$roundedBtc = crypto_amount_from_usd(100.0, 'BTC', 64000.0);
assert_close(0.0015625, $roundedBtc, 0.00000001, 'BTC rounding');

$lockContext = build_crypto_lock_context_from_quote($quote, 'USDT', '');
assert_same('crypto', (string) $lockContext['pay_method'], 'Lock pay method');
assert_same('USDT', (string) $lockContext['crypto_asset'], 'Lock asset');
assert_same('TRC20', (string) $lockContext['crypto_network'], 'Lock default network');
assert_close(100.0, (float) $lockContext['crypto_amount_due'], 0.000001, 'Lock amount due');

echo "crypto_conversion_test passed\n";
