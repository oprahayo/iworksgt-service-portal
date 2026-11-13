<?php
declare(strict_types=1);

/**
 * The single public/customer product policy.
 *
 * Operational services and historic invoices retain their real IDs and
 * original prices; this function only shapes NEW, published customer offers.
 * Admin and agent service management must continue to read the unmodified
 * list_services()/get_service() records.
 */
function iworks_customer_published_service(array $service): ?array
{
    $slug = strtolower(trim((string)($service['slug'] ?? '')));
    $name = trim((string)($service['name'] ?? ''));
    if ($slug === '' || $name === '') return null;
    if (isset($service['is_active']) && (int)$service['is_active'] !== 1) return null;
    if (isset($service['is_public']) && (int)$service['is_public'] !== 1) return null;

    // Historical BN records remain intact for prior orders, but CAC BN and
    // Nigerian private Ltd are now one canonical registration product.
    if (in_array($slug, [
        'business-name-registration',
        'business-name-registration-cac-nigeria',
        'custom-service-system',
        'launch-a-company-package',
    ], true)) return null;

    $normalized = strtolower($name);
    if ($normalized === 'launch a company package'
        || ($normalized === 'custom service' && (float)($service['price'] ?? 0) <= 0)) return null;

    // These offerings are deliberately outside iWorks' published service
    // scope; exclude by service name/category, never by incidental mentions
    // of bank accounts in unrelated service descriptions.
    $category = strtolower(trim((string)($service['category'] ?? '')));
    foreach (['banking', 'bank account', 'loan', 'wallet', 'payment gateway'] as $term) {
        if (str_contains($normalized, $term) || str_contains($category, $term)) return null;
    }

    // One canonical CAC product, with structure-specific quote at intake,
    // NOT the fixed historical Ltd service price.
    if ($slug === 'company-registration') {
        if (function_exists('cac_public_service_record')) {
            $service = cac_public_service_record($service);
            if ($service === null) return null;
        }
        $service['_iworks_checkout_mode'] = 'cac';
        return $service;
    }
    if ($slug === 'trademark') {
        $service['_iworks_checkout_mode'] = 'trademark';
        return $service;
    }

    // Incorporated trustees and Ltd by Guarantee need their own legal
    // intake/review; never mis-sell them as a standard self-serve Ltd.
    $specialist = in_array($slug, [
        'incorporated-trustees',
        'social-enterprise-ltd-by-guarantee',
    ], true) || str_contains($normalized, 'incorporated trustee')
        || str_contains($normalized, 'limited by guarantee')
        || str_contains($normalized, 'ltd by guarantee');
    $service['_iworks_checkout_mode'] = $specialist ? 'consultation' : 'standard';
    return $service;
}

/** Reusable, deduplicated published catalogue for both public and portal. */
function iworks_customer_published_catalogue(array $services): array
{
    $visible = [];
    foreach ($services as $service) {
        if (!is_array($service)) continue;
        $item = iworks_customer_published_service($service);
        if ($item === null) continue;
        $slug = (string)($item['slug'] ?? '');
        if (!isset($visible[$slug])) $visible[$slug] = $item;
    }
    return array_values($visible);
}

/** True only for an ordinary product that may use the standard invoice flow. */
function iworks_customer_can_standard_checkout(array $service): bool
{
    $visible = iworks_customer_published_service($service);
    return $visible !== null && ($visible['_iworks_checkout_mode'] ?? '') === 'standard'
        && (float)($service['price'] ?? 0) > 0;
}
