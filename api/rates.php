<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$amount = (float) ($_GET['amount'] ?? 0);
if ($amount < 0) {
    $amount = 0;
}

$currency = normalize_payment_currency((string) ($_GET['currency'] ?? active_pay_currency()));
$force = (int) ($_GET['force'] ?? 0) === 1;

try {
    $quote = get_crypto_checkout_quote($amount, $currency, $force);
    echo json_encode([
        'success' => true,
        'pay_currency' => $currency,
        'payable_amount' => (float) ($quote['payable_amount'] ?? 0),
        'fx_rate_used' => (float) ($quote['fx_rate_used'] ?? fx_ngn_per_usd()),
        'fx_last_updated_at' => (string) ($quote['fx_last_updated_at'] ?? ''),
        'usd_amount' => (float) ($quote['usd_amount'] ?? 0),
        'assets' => is_array($quote['assets'] ?? null) ? $quote['assets'] : [],
        'rates_source' => (string) ($quote['rates_source'] ?? 'fallback'),
        'rates_last_updated_at' => (string) ($quote['rates_last_updated_at'] ?? ''),
        'rates_warning' => (string) ($quote['rates_warning'] ?? ''),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}
