<?php
declare(strict_types=1);

/**
 * Assisted CAC registration rules: screening only, not CAC name reservation,
 * identity verification, professional licensing, or government integration.
 */
function cac_registration_natures(): array
{
    // Representative industry categories for intake. Final "specific nature"
    // must be matched by staff to the current official iCRP catalogue.
    return [
        'Agriculture, farming and agribusiness',
        'Animal husbandry, poultry and livestock',
        'Architecture, surveying and built environment',
        'Arts, media, entertainment and publishing',
        'Automotive, vehicle sales and maintenance',
        'Beauty, hairdressing and personal care',
        'Business advisory and management consulting',
        'Cleaning, sanitation and facility management',
        'Construction, civil engineering and property development',
        'Creative design, photography and advertising',
        'Education, training and tutoring',
        'Electrical, electronics and appliances',
        'Energy, oil, gas and renewable power',
        'Engineering and industrial services',
        'Event management and hospitality services',
        'Fashion, tailoring and textiles',
        'Financial services and insurance (regulated)',
        'Food processing, catering, restaurants and beverages',
        'General contracts and supply',
        'General merchandise, wholesale and retail',
        'Health, clinics, pharmacy and medical services (regulated)',
        'Information technology, software and digital services',
        'Import, export, trading and distribution',
        'Legal, accounting and other regulated professions',
        'Logistics, delivery and transportation',
        'Manufacturing, production and packaging',
        'Mining, quarrying and solid minerals (regulated)',
        'Nonprofit, charity and social enterprise',
        'Printing, stationery and office supplies',
        'Professional services and administration',
        'Real estate, leasing and property management',
        'Security and investigation services (regulated)',
        'Telecommunications and internet services (regulated)',
        'Travel, tourism and accommodation',
        'Warehousing, storage and cold-chain services',
        'Water, waste and environmental services',
        'Other (describe the activity clearly)',
    ];
}

function cac_registration_privacy_fees(): array
{
    return [
        'virtual_address_ngn' => cac_money_setting('cac_virtual_address_ngn', 0),
        'privacy_advisory_per_director_ngn' => cac_money_setting('cac_privacy_advisory_per_director_ngn', 50000),
    ];
}

function cac_virtual_address_eligible(): bool
{
    return setting('cac_virtual_address_enabled', '0') === '1'
        && trim(setting('cac_virtual_address_details', '')) !== ''
        && cac_money_setting('cac_virtual_address_ngn', 0) > 0;
}

function cac_registered_office(string $choice, string $provided): string
{
    if ($choice === 'virtual') {
        if (!cac_virtual_address_eligible()) {
            throw new RuntimeException('Our registered-office service is not currently available. Choose your own verifiable address or contact support.');
        }
        return trim(setting('cac_virtual_address_details', ''));
    }
    if ($choice !== 'own') {
        throw new RuntimeException('Choose your own registered address or the available managed office.');
    }
    return trim($provided);
}

function cac_id_type_options(): array
{
    return ['NIN','National ID','National ID card','International passport','Driver licence','Voter card'];
}

function cac_validate_id_number(string $type, string $value): ?string
{
    $raw = strtoupper(trim($value));
    $normal = preg_replace('/[\s-]+/', '', $raw) ?? '';
    if ($normal === '' || !in_array($type, cac_id_type_options(), true)) {
        return 'Select a valid ID type and enter its number.';
    }
    if ($type === 'NIN') {
        return preg_match('/^[0-9]{11}$/D', $normal) ? null : 'NIN must contain exactly 11 digits (NIMC format).';
    }
    if ($type === 'National ID' || $type === 'National ID card') {
        // Older national card numbers and the NIN are not interchangeable.
        return preg_match('/^[A-Z0-9]{6,24}$/D', $normal) ? null
            : 'Enter the alphanumeric number printed on the National ID (6–24 characters). Select NIN for an 11-digit NIN.';
    }
    if ($type === 'International passport') {
        return preg_match('/^[A-Z0-9]{6,16}$/D', $normal) ? null
            : 'Enter the passport document number (6–16 letters or digits); do not enter your name or passport expiry.';
    }
    if ($type === 'Driver licence') {
        return preg_match('/^[A-Z0-9]{6,22}$/D', $normal) ? null
            : 'Enter the FRSC licence number as printed (6–22 letters/digits).';
    }
    return preg_match('/^[A-Z0-9]{8,25}$/D', $normal) ? null
        : 'Enter the voter-card identification number as printed (8–25 letters/digits).';
}

function cac_name_review(string $raw, string $kind): array
{
    $name = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
    $flags = [];
    if (mb_safe_trim($name, 190) !== $name || mb_strlen($name, 'UTF-8') < 4) {
        $flags[] = 'Choose a distinct proposed name of at least four characters (maximum 190).';
    }
    if (!preg_match('/[\p{L}]/u', $name)) $flags[] = 'The name should contain letters.';
    if (preg_match('/(?:\b(?:LLC|L\.L\.C\.|PLC|INC|INCORPORATED|LIMITED|LTD)\.?)$/i', $name) && $kind === 'business') {
        $flags[] = 'Business names cannot be presented as a limited company. Remove Ltd, Limited, LLC, PLC or Inc.';
    }
    if ($kind === 'company' && preg_match('/\b(?:LLC|L\.L\.C\.|PLC|INC|INCORPORATED)\.?$/i', $name)) {
        $flags[] = 'For a Nigerian private company, use Limited or Ltd rather than a foreign LLC/Inc/PLC suffix.';
    }
    $restricted = ['BANK','BANKING','INSURANCE','UNIVERSITY','FEDERAL','NATIONAL','GOVERNMENT','COMMISSION','TRUST','COOPERATIVE','GROUP','HOLDING'];
    $upper = strtoupper($name);
    $warnings = [];
    foreach ($restricted as $word) {
        if (preg_match('/\b'.preg_quote($word,'/').'\b/i', $upper)) {
            $warnings[] = $word . ' may require specific CAC consent or licensing. Confirm through official iCRP.';
        }
    }
    if ($kind === 'company' && !preg_match('/\b(?:LTD|LIMITED)\.?$/i', $name)) {
        $warnings[] = 'A Nigerian private company normally uses a Limited/Ltd suffix. Confirm the correct ending with CAC.';
    }
    $base = trim(preg_replace('/\b(?:LIMITED|LTD|ENTERPRISES?|VENTURES?|SERVICES|LLC)\.?$/i', '', $name) ?? $name);
    if ($base === '') $base = $name;
    $suffixes = $kind === 'business'
        ? [' Enterprises',' Ventures',' Services']
        : [' Limited',' Ventures Limited',' Services Limited'];
    $suggestions = [];
    foreach ($suffixes as $suffix) {
        $candidate = trim($base . $suffix);
        if (strcasecmp($candidate, $name) !== 0 && strlen($candidate) <= 190) $suggestions[] = $candidate;
    }
    return ['errors'=>array_values(array_unique($flags)),
        'warnings'=>array_values(array_unique($warnings)),
        'suggestions'=>array_values(array_unique($suggestions))];
}

/** One visible service, while legacy service IDs remain intact for existing jobs. */
function cac_public_service_record(array $service): ?array
{
    $slug = (string)($service['slug'] ?? '');
    if (in_array($slug, ['business-name-registration', 'business-name-registration-cac-nigeria'], true)) return null;
    if ($slug !== 'company-registration') return $service;
    $service['name'] = 'Business Registration';
    $service['short_description'] = 'CAC Business Name (sole proprietor or partners) and private Limited company formation in one guided process.';
    $service['description'] = '<p>Choose Business Name or Private Limited Company, provide a proposed name, ownership details and a traceable registered address. Final fees depend on the pathway and selected add-ons.</p>';
    $service['price'] = cac_money_setting('cac_business_base_ngn', 26000);
    $service['currency'] = 'NGN';
    return $service;
}
