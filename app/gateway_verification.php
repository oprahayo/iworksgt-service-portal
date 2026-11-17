<?php
declare(strict_types=1);

/** Paystack raw-body signatures, including timestamped signing-secret rotation. */
function iworks_paystack_signature_valid(string $raw, array $headers, string $apiSecret, string $signingSecret = ''): bool
{
    $normalized = array_change_key_case($headers, CASE_LOWER);
    $modern = (string)($normalized['paystack-signature'] ?? '');
    if ($signingSecret !== '') {
        if (!str_starts_with($signingSecret, 'whsec_') || $modern === '') { return false; }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $modern) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) { continue; }
            if ($pair[0] === 't') {
                if ($timestamp !== null || !ctype_digit($pair[1])) { return false; }
                $timestamp = $pair[1];
            } elseif ($pair[0] === 'v1' && preg_match('/^whk_[A-Za-z0-9]+:([a-f0-9]{128})$/D', $pair[1], $m)) {
                $signatures[] = $m[1];
            }
        }
        if ($timestamp === null || abs(time() - (int)$timestamp) > 300) { return false; }
        $expected = hash_hmac('sha512', $timestamp . '.' . $raw, $signingSecret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) { return true; }
        }
        return false;
    }
    // Existing integrations retain legacy verification until a signing secret is provisioned.
    $legacy = (string)($normalized['x-paystack-signature'] ?? '');
    return $apiSecret !== '' && preg_match('/^[a-f0-9]{128}$/D', $legacy) === 1
        && hash_equals(hash_hmac('sha512', $raw, $apiSecret), $legacy);
}

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
