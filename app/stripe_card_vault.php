<?php
declare(strict_types=1);

/**
 * Stripe-managed, on-session saved card vault for authenticated clients.
 *
 * iWorks NEVER handles full PAN, CVV, card expiry input or raw card credentials.
 * Cards are collected by Stripe Checkout setup mode and remain in Stripe.
 * Our database stores only the customer-to-Stripe Customer ID mapping and
 * the customer's explicit consent time. No charge occurs during card setup.
 */

function stripe_vault_schema(): void
{
    static $ready = false;
    if ($ready) { return; }
    if (!schema_table_exists('stripe_customer_vault')) {
        db_execute("CREATE TABLE IF NOT EXISTS stripe_customer_vault (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            stripe_customer_id VARCHAR(90) NOT NULL,
            consent_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_stripe_vault_customer (stripe_customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $ready = true;
}

function stripe_vault_available(): bool
{
    $method = get_payment_method_by_code('stripe');
    return is_array($method)
        && (int) ($method['is_active'] ?? 0) === 1
        && str_starts_with(trim((string) ($method['secret_key'] ?? '')), 'sk_');
}

/** Stripe secret is used server-side only; never returned to a template. */
function stripe_vault_secret(): string
{
    $method = get_payment_method_by_code('stripe');
    if (!stripe_vault_available() || !$method) {
        throw new RuntimeException('Saved cards are not available until Stripe is enabled in iWorks payment settings.');
    }
    return trim((string) $method['secret_key']);
}

function stripe_vault_valid_id(string $value, string $prefix): bool
{
    $allowed = ['cus_', 'pm_', 'cs_', 'seti_'];
    if (!in_array($prefix, $allowed, true)) { return false; }
    return preg_match('/^' . preg_quote($prefix, '/') . '[A-Za-z0-9_]{8,220}$/D', $value) === 1;
}

/**
 * Request endpoint is built from constants / strict Stripe ID validation only.
 * No user-controlled URLs, redirects or arbitrary API paths are allowed.
 */
function stripe_vault_api(string $method, string $path, array $data = [], string $idempotency = ''): array
{
    $secret = stripe_vault_secret();
    if (!in_array($method, ['GET', 'POST'], true) || !str_starts_with($path, '/v1/')
        || preg_match('~^/v1/[A-Za-z0-9/_?&=.-]+$~D', $path) !== 1) {
        throw new RuntimeException('Invalid payment provider request.');
    }
    $headers = [
        'Authorization: Bearer ' . $secret,
        'Accept: application/json',
    ];
    if ($method === 'POST') { $headers[] = 'Content-Type: application/x-www-form-urlencoded'; }
    if ($idempotency !== '') {
        if (!preg_match('/^[a-z0-9-]{1,130}$/D', $idempotency)) {
            throw new RuntimeException('Invalid payment request identifier.');
        }
        $headers[] = 'Idempotency-Key: ' . $idempotency;
    }
    [$code, $body] = gateway_http_request(
        'https://api.stripe.com' . $path,
        $method,
        $headers,
        $method === 'POST' ? http_build_query($data) : null
    );
    $response = json_decode($body, true);
    if (!is_array($response)) { throw new RuntimeException('Invalid response from Stripe.'); }
    if ($code < 200 || $code >= 300) {
        // Avoid displaying sensitive gateway error details to site visitors.
        $message = trim((string) ($response['error']['message'] ?? ''));
        throw new RuntimeException('Stripe could not complete the request' .
            ($message !== '' ? ': ' . mb_safe_trim($message, 160) : '.') );
    }
    return $response;
}

function stripe_vault_customer_id(int $userId): string
{
    if ($userId <= 0 || !schema_table_exists('stripe_customer_vault')) { return ''; }
    $row = db_one(
        'SELECT stripe_customer_id FROM stripe_customer_vault WHERE user_id = :user LIMIT 1',
        ['user' => $userId]
    );
    $id = (string) ($row['stripe_customer_id'] ?? '');
    return stripe_vault_valid_id($id, 'cus_') ? $id : '';
}

/**
 * Creates a dedicated Stripe Customer once. Only authenticated clients call
 * this from an explicit CSRF-protected Add Card action.
 */
function stripe_vault_ensure_customer(array $user): string
{
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0 || (string) ($user['role'] ?? '') !== 'client') {
        throw new RuntimeException('Customer sign-in is required.');
    }
    stripe_vault_schema();
    $existing = stripe_vault_customer_id($userId);
    if ($existing !== '') { return $existing; }
    $response = stripe_vault_api('POST', '/v1/customers', [
        'email' => (string) ($user['email'] ?? ''),
        'name' => (string) ($user['full_name'] ?? ''),
        'metadata[iworks_user_id]' => (string) $userId,
    ], 'iworks-customer-' . $userId);
    $customerId = (string) ($response['id'] ?? '');
    if (!stripe_vault_valid_id($customerId, 'cus_')) {
        throw new RuntimeException('Could not create a Stripe customer for the card vault.');
    }
    db_execute(
        'INSERT INTO stripe_customer_vault (user_id, stripe_customer_id, consent_at, created_at, updated_at)
         VALUES (:user, :customer, :consent, :created, :updated)
         ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)',
        ['user' => $userId, 'customer' => $customerId,
         'consent' => now(), 'created' => now(), 'updated' => now()]
    );
    return stripe_vault_customer_id($userId);
}

/** The card itself is entered on Stripe-hosted checkout, not the iWorks site. */
function stripe_vault_start_setup(array $user, bool $consent, bool $returnToBilling = false): string
{
    if (!$consent) {
        throw new RuntimeException('Please confirm you want to save your card for future checkout.');
    }
    $userId = (int) ($user['id'] ?? 0);
    $customerId = stripe_vault_ensure_customer($user);
    $returnPage = $returnToBilling ? url('card_vault', ['billing'=>1]) : url('card_vault');
    $successUrl = portal_absolute_url($returnPage) . '&session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl = portal_absolute_url($returnPage);
    $response = stripe_vault_api('POST', '/v1/checkout/sessions', [
        'mode' => 'setup',
        'customer' => $customerId,
        'payment_method_types[0]' => 'card',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => 'iworks-client-' . $userId,
        'metadata[iworks_user_id]' => (string) $userId,
    ]);
    $url = (string) ($response['url'] ?? '');
    $id = (string) ($response['id'] ?? '');
    if (!stripe_vault_valid_id($id, 'cs_') ||
        !preg_match('~^https://checkout\.stripe\.com/~i', $url)) {
        throw new RuntimeException('Stripe did not return a secure card setup page.');
    }
    return $url;
}

function stripe_vault_session_valid_for_customer(array $session, string $customerId, int $userId): bool
{
    return (string) ($session['mode'] ?? '') === 'setup'
        && (string) ($session['status'] ?? '') === 'complete'
        && (string) ($session['customer'] ?? '') === $customerId
        && (string) ($session['client_reference_id'] ?? '') === 'iworks-client-' . $userId
        && (string) ($session['metadata']['iworks_user_id'] ?? '') === (string) $userId;
}

/** Verify completed setup with Stripe rather than trusting the success URL. */
function stripe_vault_confirm_setup(int $userId, string $sessionId): void
{
    $customerId = stripe_vault_customer_id($userId);
    if ($customerId === '' || !stripe_vault_valid_id($sessionId, 'cs_')) {
        throw new RuntimeException('Invalid card setup reference.');
    }
    $session = stripe_vault_api('GET', '/v1/checkout/sessions/' . $sessionId);
    if (!stripe_vault_session_valid_for_customer($session, $customerId, $userId)) {
        throw new RuntimeException('Card setup is not complete or does not belong to your account.');
    }
    $intentId = (string) ($session['setup_intent'] ?? '');
    if (!stripe_vault_valid_id($intentId, 'seti_')) {
        throw new RuntimeException('The card setup has not been confirmed by Stripe.');
    }
    $intent = stripe_vault_api('GET', '/v1/setup_intents/' . $intentId);
    if (($intent['status'] ?? '') !== 'succeeded'
        || (string) ($intent['customer'] ?? '') !== $customerId) {
        throw new RuntimeException('Stripe has not verified this saved card yet.');
    }
    $pmId = (string) ($intent['payment_method'] ?? '');
    if (!stripe_vault_valid_id($pmId, 'pm_')) {
        throw new RuntimeException('No saved card was returned by Stripe.');
    }
    // User explicitly initiated the Add Card flow; mark it redisplayable only
    // after verifying the setup session, SetupIntent and customer ownership.
    $pm = stripe_vault_api('GET', '/v1/payment_methods/' . $pmId);
    if (!stripe_vault_owns_payment_method($pm, $customerId)) {
        throw new RuntimeException('The saved card does not belong to your account.');
    }
    if (($pm['allow_redisplay'] ?? '') !== 'always') {
        stripe_vault_api('POST', '/v1/payment_methods/' . $pmId, ['allow_redisplay'=>'always']);
    }
}

function stripe_vault_owns_payment_method(array $method, string $customerId): bool
{
    return (string) ($method['customer'] ?? '') === $customerId
        && (string) ($method['type'] ?? '') === 'card'
        && stripe_vault_valid_id((string) ($method['id'] ?? ''), 'pm_');
}

function stripe_vault_list_cards(int $userId): array
{
    $customerId = stripe_vault_customer_id($userId);
    if ($customerId === '') { return []; }
    $response = stripe_vault_api('GET',
        '/v1/customers/' . $customerId . '/payment_methods?type=card&limit=100');
    $list = is_array($response['data'] ?? null) ? $response['data'] : [];
    $result = [];
    foreach ($list as $paymentMethod) {
        if (!is_array($paymentMethod) || !stripe_vault_owns_payment_method($paymentMethod, $customerId)) {
            continue;
        }
        $card = (array) ($paymentMethod['card'] ?? []);
        $last4 = (string) ($card['last4'] ?? '');
        $brand = (string) ($card['brand'] ?? '');
        if (!preg_match('/^[0-9]{4}$/D', $last4)) { continue; }
        $result[] = [
            'id' => (string) $paymentMethod['id'],
            'brand' => $brand,
            'last4' => $last4,
            'exp_month' => (int) ($card['exp_month'] ?? 0),
            'exp_year' => (int) ($card['exp_year'] ?? 0),
        ];
    }
    return $result;
}

function stripe_vault_remove_card(int $userId, string $paymentMethodId): void
{
    $customerId = stripe_vault_customer_id($userId);
    if ($customerId === '' || !stripe_vault_valid_id($paymentMethodId, 'pm_')) {
        throw new RuntimeException('Card not found.');
    }
    $pm = stripe_vault_api('GET', '/v1/payment_methods/' . $paymentMethodId);
    if (!stripe_vault_owns_payment_method($pm, $customerId)) {
        throw new RuntimeException('You do not have permission to remove this card.');
    }
    stripe_vault_api('POST', '/v1/payment_methods/' . $paymentMethodId . '/detach');
}
