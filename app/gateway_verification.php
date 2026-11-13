<?php
declare(strict_types=1);

/**
 * Compare independent provider verification with immutable payment values
 * stored when the server created the checkout. Never trust callback query
 * values, webhook amounts or browser-submitted invoice totals.
 *
 * Only NGN/USD (both two-decimal) are accepted by the current checkout
 * policy; add a currency-aware minor-unit table BEFORE enabling others.
 */
function iworks_gateway_verified_matches(string $provider, array $payment, array $verified): bool
{
    $provider = strtolower(trim($provider));
    $frozenProvider = strtolower(trim((string) ($payment['gateway_provider'] ?? '')));
    $reference = trim((string) ($payment['reference'] ?? ''));
    $currency = strtoupper(trim((string) ($payment['pay_currency'] ?? '')));
    $amount = $payment['amount'] ?? null;

    if (!in_array($provider, ['paystack', 'flutterwave', 'stripe'], true)
        || $frozenProvider !== $provider
        || $reference === ''
        || !in_array($currency, ['NGN', 'USD'], true)
        || !is_numeric($amount) || (float) $amount <= 0
        || (int) ($payment['invoice_id'] ?? 0) <= 0
        || (int) ($payment['client_id'] ?? 0) <= 0
        || (int) ($payment['job_id'] ?? 0) <= 0) {
        return false;
    }
    $expectedMinor = (int) round((float) $amount * 100);
    if ($expectedMinor <= 0) {
        return false;
    }

    if ($provider === 'paystack') {
        $data = is_array($verified['data'] ?? null) ? $verified['data'] : [];
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        if (empty($verified['status'])
            || strtolower((string) ($data['status'] ?? '')) !== 'success'
            || (string) ($data['reference'] ?? '') !== $reference
            || strtoupper((string) ($data['currency'] ?? '')) !== $currency
            || !is_numeric($data['amount'] ?? null)
            || (int) $data['amount'] !== $expectedMinor
            || !is_scalar($data['id'] ?? null) || (string) $data['id'] === '') {
            return false;
        }
    } elseif ($provider === 'flutterwave') {
        $data = is_array($verified['data'] ?? null) ? $verified['data'] : [];
        $metadata = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        if (strtolower((string) ($verified['status'] ?? '')) !== 'success'
            || strtolower((string) ($data['status'] ?? '')) !== 'successful'
            || (string) ($data['tx_ref'] ?? '') !== $reference
            || strtoupper((string) ($data['currency'] ?? '')) !== $currency
            || !is_numeric($data['amount'] ?? null)
            || (int) round((float) $data['amount'] * 100) !== $expectedMinor
            || !is_scalar($data['id'] ?? null) || (string) $data['id'] === '') {
            return false;
        }
    } else {
        if (($verified['id'] ?? '') !== (string) ($payment['gateway_session_id'] ?? '')
            || $verified['id'] === ''
            || strtolower((string) ($verified['mode'] ?? '')) !== 'payment'
            || strtolower((string) ($verified['status'] ?? '')) !== 'complete'
            || strtolower((string) ($verified['payment_status'] ?? '')) !== 'paid'
            || (string) ($verified['client_reference_id'] ?? '') !== $reference
            || strtoupper((string) ($verified['currency'] ?? '')) !== $currency
            || !is_numeric($verified['amount_total'] ?? null)
            || (int) $verified['amount_total'] !== $expectedMinor) {
            return false;
        }
        $metadata = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
    }

    // Metadata may be omitted by a provider, but when present it must not
    // contradict the frozen invoice, job and customer relationship.
    foreach (['invoice_id' => 'invoice_id', 'job_id' => 'job_id', 'client_id' => 'client_id'] as $key => $stored) {
        if (array_key_exists($key, $metadata)
            && (string) $metadata[$key] !== (string) $payment[$stored]) {
            return false;
        }
    }
    return true;
}
