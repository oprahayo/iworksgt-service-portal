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
    // Presentation-only catalogue child. Never eligible for generic invoices.
    if ($slug === 'cac-business-name') {
        $service['_iworks_checkout_mode'] = 'cac';
        $service['_iworks_registration_type'] = 'business';
        return $service;
    }


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
        // The client catalogue consumes already decorated public offers.
        // Do not overwrite the actual Ltd price/name with Enterprise pricing.
        if (($service['_iworks_registration_type'] ?? '') === 'company') {
            $service['_iworks_checkout_mode'] = 'cac';
            return $service;
        }
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


/**
 * Two separate customer-facing CAC offers, one underlying service/price editor.
 * The Enterprise slug is virtual: it does not create a duplicate DB service.
 * Company pricing remains based on the underlying Ltd record; Business Name
 * pricing comes from the dedicated admin CAC settings.
 */
function iworks_customer_registration_variants(array $service): array
{
    if ((string)($service['slug'] ?? '') !== 'company-registration') return [];
    $base = iworks_customer_published_service($service);
    if ($base === null) return [];

    $business = $base;
    $business['slug'] = 'cac-business-name';
    $business['name'] = 'Business Name (Enterprise)';
    $business['short_description'] =
        'For sole proprietors and partnerships. Register your trading name without forming a separate limited company.';
    $business['_iworks_registration_type'] = 'business';
    $business['description'] = '<p>Register an Enterprise or Business Name with CAC. Choose one proprietor or a partnership, then provide a proposed name, nature of business, registered address and valid identification.</p>';
    $business['featured_bullets'] = [
        'Sole proprietorship or partnership',
        'Preliminary proposed-name screening',
        'Traceable registered business address',
        'Owner identification and document checklist',
    ];

    $company = $base;
    $company['slug'] = 'company-registration';
    $company['name'] = 'Private Limited Company (Ltd)';
    $company['short_description'] =
        'For a registered company limited by shares, with one or more directors and shareholders.';
    $company['_iworks_registration_type'] = 'company';
    $company['description'] = '<p>Register a private Nigerian company limited by shares (Ltd). Provide its name, business activity, registered address, director/shareholder details and share capital. Specialist legal structures require a separate consultation.</p>';
    $company['featured_bullets'] = [
        'Private company limited by shares',
        'Directors and shareholders',
        'Share capital and ownership details',
        'Guided CAC filing documentation',
    ];
    $companyAmount = max(0, (float)($service['price'] ?? 0));
    if (function_exists('cac_company_quote')) {
        try { $companyAmount = cac_company_quote(1); }
        catch (Throwable $error) { $companyAmount = 0; }
    }
    $company['price'] = $companyAmount;
    $company['currency'] = 'NGN';
    if ($companyAmount <= 0) {
        // A missing price must never crash the public site or show a
        // misleading free-company offer.
        $company['_iworks_checkout_mode'] = 'consultation';
    }

    return [$business, $company];
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
        if ($slug === 'company-registration') {
            // Two distinct customer choices backed by one existing CAC
            // service ID. Do not clone or renumber historical DB records.
            foreach (iworks_customer_registration_variants($service) as $choice) {
                $choiceSlug = (string)$choice['slug'];
                $visible[$choiceSlug] = $choice;
            }
            continue;
        }
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
