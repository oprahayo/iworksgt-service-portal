<?php
declare(strict_types=1);

/**
 * One pending public service, stored only in the portal session.
 * Never accepts a caller-controlled redirect URL or creates a paid order.
 */
function iworks_order_slug(mixed $value): string
{
    if (!is_string($value)) { return ''; }
    $slug = trim($value);
    return preg_match('/^[a-z0-9][a-z0-9-]{0,120}$/D', $slug) === 1 ? $slug : '';
}

function iworks_order_intent_capture(array $query): void
{
    if (!array_key_exists('service', $query)) { return; }
    $slug = iworks_order_slug($query['service']);
    if ($slug === '' || !get_service_by_slug($slug)) {
        iworks_order_intent_clear();
        return;
    }
    $_SESSION['iworks_pending_order'] = ['service' => $slug, 'at' => time()];
}

function iworks_order_intent_peek(): string
{
    $value = $_SESSION['iworks_pending_order'] ?? null;
    if (!is_array($value) || !isset($value['service'], $value['at'])) { return ''; }
    $slug = iworks_order_slug($value['service']);
    $created = (int) $value['at'];
    if ($slug === '' || $created > time() + 30 || $created < time() - 3600) {
        iworks_order_intent_clear();
        return '';
    }
    return $slug;
}

function iworks_order_intent_clear(): void
{
    unset($_SESSION['iworks_pending_order']);
}

/** Called after password, OTP, account creation and Google OAuth login. */
function iworks_post_login_destination(?array $user): string
{
    $slug = iworks_order_intent_peek();
    if ($slug === 'trademark' && (string) ($user['role'] ?? '') === 'client') {
        return url('trademark');
    }
    if ((string) ($user['role'] ?? '') === 'client') {
        if ($slug === 'business-name-registration-cac-nigeria') {
            return url('cac_filing', ['type' => 'business']);
        }
        if ($slug === 'company-registration') {
            return url('cac_filing', ['type' => 'company']);
        }
    }
    if ($slug !== '' && (string) ($user['role'] ?? '') === 'client') {
        return url('jobs', ['action' => 'new', 'service' => $slug]);
    }
    return url('dashboard');
}

/**
 * Only published, active services that are also available in the customer's
 * checkout catalog may be selected. Private/inactive IDs are never accepted.
 */
function iworks_order_catalog_selection(mixed $value, array $serviceOptions): ?array
{
    $slug = iworks_order_slug($value);
    if ($slug === '') { return null; }
    $service = get_service_by_slug($slug);
    if (!$service || (int) ($service['id'] ?? 0) <= 0) { return null; }
    foreach ($serviceOptions as $item) {
        if ((int) ($item['id'] ?? 0) === (int) $service['id']) {
            return ['id' => (int) $service['id'], 'slug' => $slug, 'name' => (string) ($service['name'] ?? '')];
        }
    }
    return null;
}
