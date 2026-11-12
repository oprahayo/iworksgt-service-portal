<?php

declare(strict_types=1);

$__settings_cache = null;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_name(): string
{
    $configured = app_config()['app']['name'] ?? 'Service Portal';
    return trim(setting('app_name', $configured)) ?: $configured;
}

function brand_name(): string
{
    $configured = app_config()['app']['brand_name'] ?? app_name();
    return trim(setting('brand_name', $configured)) ?: $configured;
}

function company_legal_name(): string
{
    return trim(setting('company_legal_name', 'iWorks Global Limited'));
}

function business_support_summary(): string
{
    return trim(setting(
        'business_support_summary',
        'Business support tools to help entrepreneurs scale their business with our services.'
    ));
}

function base_url(): string
{
    $base = app_config()['app']['base_url'] ?? '';
    return rtrim($base, '/');
}

function request_base_url(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim(dirname($scriptName), '/');
    if ($dir === '.' || $dir === '/') {
        $dir = '';
    }

    return $scheme . '://' . $host . $dir;
}

function request_host(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    return strtolower((string) preg_replace('~:\d+$~', '', $host));
}

function configured_host(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return trim($host);
}

function is_local_request_host(?string $host = null): bool
{
    $host = strtolower(trim((string) ($host ?? request_host())));
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function should_enforce_split_domain(): bool
{
    $requestHost = request_host();
    if ($requestHost === '' || is_local_request_host($requestHost)) {
        return false;
    }

    $publicHost = configured_host(public_base_url());
    $portalHost = configured_host(portal_base_url());
    if ($publicHost === '' || $portalHost === '') {
        return false;
    }

    return true;
}

function portal_base_url(): string
{
    $base = trim((string) (app_config()['app']['portal_base_url'] ?? base_url()));
    // The live installation has two virtual hosts. Keep all authenticated
    // actions and Google OAuth callbacks on the isolated portal origin.
    if (in_array(request_host(), ['iworksgt.com', 'www.iworksgt.com', 'portal.iworksgt.com'], true)
        && in_array(configured_host($base), ['iworksgt.com', 'portal.iworksgt.com'], true)) {
        return 'https://portal.iworksgt.com';
    }
    return rtrim($base, '/');
}

function public_base_url(): string
{
    $configured = trim((string) (app_config()['app']['public_base_url'] ?? ''));
    // The apex is the sole canonical origin for the company website. This
    // also corrects an older saved public_base_url pointing at the portal.
    if (in_array(request_host(), ['iworksgt.com', 'www.iworksgt.com', 'portal.iworksgt.com'], true)
        || configured_host(portal_base_url()) === 'portal.iworksgt.com') {
        return 'https://iworksgt.com';
    }
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    return request_base_url();
}

function runtime_base_url(): string
{
    $request = request_base_url();
    if ($request !== '') {
        return $request;
    }

    $configured = portal_base_url();
    if ($configured !== '') {
        return $configured;
    }

    return '';
}

function url(string $page = '', array $params = []): string
{
    $query = [];

    if ($page !== '') {
        $query['page'] = $page;
    }

    foreach ($params as $key => $value) {
        if ($value !== null && $value !== '') {
            $query[$key] = $value;
        }
    }

    $queryString = http_build_query($query);

    if ($queryString === '') {
        return 'index.php';
    }

    return 'index.php?' . $queryString;
}

function public_url(string $page = 'home', array $params = []): string
{
    $page = strtolower(trim($page));
    $path = '';

    if ($page === '' || $page === 'home') {
        $path = '';
    } elseif ($page === 'insights') {
        $path = 'blog';
    } elseif ($page === 'service') {
        $slug = trim((string) ($params['slug'] ?? ''));
        $path = 'services' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
        unset($params['slug']);
    } elseif ($page === 'case_study') {
        $slug = trim((string)($params['slug'] ?? ''));
        $path = 'case-studies' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
        unset($params['slug']);
    } elseif ($page === 'insight_post') {
        $slug = trim((string) ($params['slug'] ?? ''));
        $path = 'blog' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
        unset($params['slug']);
    } elseif ($page === 'blog_post') {
        $slug = trim((string) ($params['slug'] ?? ''));
        $path = 'blog' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
        unset($params['slug']);
    } else {
        $path = $page;
    }

    $base = public_base_url();
    $url = rtrim($base, '/');
    $url .= '/' . ltrim($path, '/');
    if ($url === '') {
        $url = '/';
    }

    $queryString = http_build_query($params);
    if ($queryString !== '') {
        $url .= '?' . $queryString;
    }

    return $url;
}

function portal_url(string $page = '', array $params = []): string
{
    $target = url($page, $params);
    return portal_absolute_url($target);
}

function asset_url(string $path): string
{
    $normalized = ltrim(trim($path), '/');
    if ($normalized === '') {
        return public_url('home');
    }

    $base = public_base_url();
    if ($base === '') {
        return '/' . $normalized;
    }

    return rtrim($base, '/') . '/' . $normalized;
}

function redirect(string $target): void
{
    header('Location: ' . $target);
    exit;
}

function method_is(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        http_response_code(419);
        exit('Invalid CSRF token. Refresh and retry.');
    }
}

function settings_cache(): array
{
    global $__settings_cache;

    if (is_array($__settings_cache)) {
        return $__settings_cache;
    }

    $__settings_cache = [];

    if (!is_configured()) {
        return $__settings_cache;
    }

    try {
        $rows = db_all('SELECT setting_key, setting_value FROM app_settings');
        foreach ($rows as $row) {
            $__settings_cache[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
    } catch (Throwable $e) {
        // Table may not exist yet during first setup.
    }

    return $__settings_cache;
}

function setting(string $key, string $default = ''): string
{
    $cache = settings_cache();

    if (!array_key_exists($key, $cache)) {
        return $default;
    }

    return (string) $cache[$key];
}

function set_setting(string $key, string $value): void
{
    db_execute(
        'INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, :updated_at)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
        [
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_at' => now(),
        ]
    );

    global $__settings_cache;
    if (!is_array($__settings_cache)) {
        $__settings_cache = [];
    }
    $__settings_cache[$key] = $value;
}

function reload_settings_cache(): void
{
    global $__settings_cache;
    $__settings_cache = null;
}

function color_setting(string $key, string $fallback): string
{
    $color = trim(setting($key, $fallback));

    return is_valid_color_hex($color) ? $color : $fallback;
}

function brand_primary_color(): string
{
    return color_setting('brand_primary', '#ff6600');
}

function brand_primary_dark_color(): string
{
    return color_setting('brand_primary_dark', '#111111');
}

function brand_accent_color(): string
{
    return color_setting('brand_accent', '#6b7280');
}

function footer_text(): string
{
    return setting('brand_footer_text', company_legal_name());
}

function normalize_public_asset_path(?string $path): string
{
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }

    if (preg_match('~^(?:https?:)?//~i', $value) === 1 || str_starts_with($value, 'data:')) {
        return $value;
    }

    return public_absolute_url(ltrim($value, '/'));
}

function logo_path(): string
{
    return normalize_public_asset_path(setting('logo_path', ''));
}

function favicon_path(): string
{
    return normalize_public_asset_path(setting('favicon_path', ''));
}

function seo_description(): string
{
    return trim(setting(
        'seo_description',
        business_support_summary() !== ''
            ? business_support_summary()
            : 'iWorks Global business support portal for booking, tracking, and delivery.'
    ));
}

function seo_tags(): string
{
    return trim(setting('seo_tags', 'iworksglobal, business support, company registration, remote portal'));
}

function social_links(): array
{
    $map = [
        'facebook' => trim(setting('social_facebook', '')),
        'instagram' => trim(setting('social_instagram', '')),
        'x' => trim(setting('social_x', '')),
        'linkedin' => trim(setting('social_linkedin', '')),
        'tiktok' => trim(setting('social_tiktok', '')),
        'youtube' => trim(setting('social_youtube', '')),
    ];

    $out = [];
    foreach ($map as $key => $value) {
        if ($value === '') {
            continue;
        }

        if (!preg_match('~^https?://~i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }
        $out[$key] = $value;
    }

    return $out;
}

function default_currency(): string
{
    return strtoupper(setting('default_currency', 'NGN'));
}

function money(float $amount, ?string $currency = null): string
{
    $code = strtoupper($currency ?: default_currency());

    return $code . ' ' . number_format($amount, 2);
}

function currency_options(): array
{
    return ['NGN', 'USD', 'EUR', 'GBP', 'KES', 'GHS'];
}

function gender_options(): array
{
    return [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_say' => 'Prefer not to say',
    ];
}

function support_category_options(): array
{
    return [
        'general' => 'General help',
        'service' => 'Service delivery',
        'billing' => 'Billing & invoice',
        'payment' => 'Payment issue',
        'technical' => 'Technical support',
        'account' => 'Account access',
    ];
}

function portal_country_options(): array
{
    return [
        'NG' => 'Nigeria',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'GH' => 'Ghana',
        'KE' => 'Kenya',
        'ZA' => 'South Africa',
        'AE' => 'United Arab Emirates',
        'IN' => 'India',
        'DE' => 'Germany',
        'FR' => 'France',
    ];
}

function portal_country_iso(string $value): string
{
    $value = strtoupper(trim($value));
    $countries = portal_country_options();

    if (isset($countries[$value])) {
        return $value;
    }

    $needle = strtolower(trim($value));
    foreach ($countries as $iso => $label) {
        if (strtolower($label) === $needle) {
            return $iso;
        }
    }

    return 'NG';
}

function portal_state_options_map(): array
{
    return [
        'NG' => [
            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
            'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT Abuja', 'Gombe',
            'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos',
            'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
            'Taraba', 'Yobe', 'Zamfara',
        ],
        'US' => [
            'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware',
            'District of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
            'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
            'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico',
            'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
            'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
            'West Virginia', 'Wisconsin', 'Wyoming',
        ],
        'GB' => ['England', 'Scotland', 'Wales', 'Northern Ireland'],
        'CA' => [
            'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
            'Nova Scotia', 'Ontario', 'Prince Edward Island', 'Quebec', 'Saskatchewan',
        ],
        'GH' => [
            'Ahafo', 'Ashanti', 'Bono', 'Bono East', 'Central', 'Eastern', 'Greater Accra', 'North East',
            'Northern', 'Oti', 'Savannah', 'Upper East', 'Upper West', 'Volta', 'Western', 'Western North',
        ],
        'KE' => [
            'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay', 'Isiolo',
            'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii', 'Kisumu', 'Kitui',
            'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera', 'Marsabit', 'Meru', 'Migori',
            'Mombasa', 'Muranga', 'Nairobi', 'Nakuru', 'Nandi', 'Narok', 'Nyamira', 'Nyandarua', 'Nyeri',
            'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River', 'Tharaka-Nithi', 'Trans Nzoia', 'Turkana',
            'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
        ],
        'ZA' => ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'],
        'AE' => ['Abu Dhabi', 'Ajman', 'Dubai', 'Fujairah', 'Ras Al Khaimah', 'Sharjah', 'Umm Al Quwain'],
        'IN' => [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana',
            'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
            'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana',
            'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi',
        ],
        'DE' => [
            'Baden-Wurttemberg', 'Bavaria', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg', 'Hesse',
            'Lower Saxony', 'Mecklenburg-Vorpommern', 'North Rhine-Westphalia', 'Rhineland-Palatinate',
            'Saarland', 'Saxony', 'Saxony-Anhalt', 'Schleswig-Holstein', 'Thuringia',
        ],
        'FR' => [
            'Auvergne-Rhone-Alpes', 'Bourgogne-Franche-Comte', 'Brittany', 'Centre-Val de Loire', 'Corsica',
            'Grand Est', 'Hauts-de-France', 'Ile-de-France', 'Normandy', 'Nouvelle-Aquitaine',
            'Occitanie', 'Pays de la Loire', 'Provence-Alpes-Cote d Azur',
        ],
    ];
}

function portal_state_options(string $country): array
{
    $iso = portal_country_iso($country);
    $map = portal_state_options_map();
    return $map[$iso] ?? [];
}

function name_first_token(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $parts = preg_split('/\s+/', $value);
    return is_array($parts) && !empty($parts[0]) ? (string) $parts[0] : '';
}

function payment_currency_options(): array
{
    return ['NGN', 'USD'];
}

function normalize_payment_currency(string $currency): string
{
    $currency = strtoupper(trim($currency));
    if (!in_array($currency, payment_currency_options(), true)) {
        $currency = default_currency();
    }
    if (!in_array($currency, payment_currency_options(), true)) {
        $currency = 'NGN';
    }

    return $currency;
}

function active_pay_currency(): string
{
    $sessionCurrency = $_SESSION['pay_currency'] ?? '';
    if (is_string($sessionCurrency) && $sessionCurrency !== '') {
        return normalize_payment_currency($sessionCurrency);
    }

    $cookieCurrency = $_COOKIE['pay_currency'] ?? '';
    if (is_string($cookieCurrency) && $cookieCurrency !== '') {
        $normalized = normalize_payment_currency($cookieCurrency);
        $_SESSION['pay_currency'] = $normalized;
        return $normalized;
    }

    return normalize_payment_currency(default_currency());
}

function set_active_pay_currency(string $currency): string
{
    $normalized = normalize_payment_currency($currency);
    $_SESSION['pay_currency'] = $normalized;

    if (!headers_sent()) {
        setcookie(
            'pay_currency',
            $normalized,
            [
                'expires' => time() + (60 * 60 * 24 * 30),
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }

    return $normalized;
}

function setting_updated_at(string $key): ?string
{
    if (!is_configured()) {
        return null;
    }

    try {
        $row = db_one('SELECT updated_at FROM app_settings WHERE setting_key = :setting_key LIMIT 1', ['setting_key' => $key]);
        return $row['updated_at'] ?? null;
    } catch (Throwable $e) {
        return null;
    }
}

function fx_ngn_per_usd(): float
{
    $rate = (float) setting('fx_ngn_per_usd', '1600');
    return $rate > 0 ? $rate : 1600.0;
}

function convert_amount(float $amount, string $fromCurrency, string $toCurrency): float
{
    $from = strtoupper(trim($fromCurrency));
    $to = strtoupper(trim($toCurrency));

    if ($from === $to) {
        return round($amount, 2);
    }

    $rate = fx_ngn_per_usd();

    if ($from === 'NGN' && $to === 'USD') {
        return round($amount / $rate, 2);
    }

    if ($from === 'USD' && $to === 'NGN') {
        return round($amount * $rate, 2);
    }

    return round($amount, 2);
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning',
        'in_progress' => 'dark',
        'waiting_client' => 'secondary',
        'revision_requested' => 'warning',
        'delivered' => 'success',
        'closed' => 'success',
        'cancelled' => 'danger',
        'unpaid' => 'danger',
        'paid' => 'success',
        'partial' => 'warning',
        'refunded' => 'dark',
        'accrued' => 'warning',
        'approved' => 'warning',
        'rejected' => 'danger',
        'draft' => 'secondary',
        'issued' => 'secondary',
        'submitted' => 'warning',
        'confirmed' => 'success',
        'void' => 'dark',
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'warning',
        'blocked' => 'danger',
        'open' => 'warning',
        'resolved' => 'success',
        'low' => 'secondary',
        'normal' => 'secondary',
        'high' => 'warning',
        'urgent' => 'danger',
    ];
    $labels = [
        'partial' => 'Partially Paid',
        'in_progress' => 'In Progress',
        'waiting_client' => 'Waiting Client',
        'revision_requested' => 'Revision Requested',
    ];

    $class = $map[$status] ?? 'secondary';
    $label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));

    return '<span class="badge text-bg-' . $class . '">' . e($label) . '</span>';
}

function mask_account_number(?string $accountNumber): string
{
    if (!$accountNumber) {
        return '';
    }

    $len = strlen($accountNumber);
    if ($len <= 4) {
        return str_repeat('*', $len);
    }

    return str_repeat('*', $len - 4) . substr($accountNumber, -4);
}

function selected_if(string $value, ?string $current): string
{
    return $value === $current ? 'selected' : '';
}

function checked_if(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function old_input(string $key, string $fallback = ''): string
{
    return $_POST[$key] ?? $fallback;
}

function is_configured(): bool
{
    $db = app_config()['db'] ?? [];

    return !empty($db['host'])
        && !empty($db['name'])
        && !empty($db['user'])
        && !empty($db['pass'])
        && $db['pass'] !== 'change-this-password';
}

function upload_directory(): string
{
    return app_config()['files']['upload_dir'] ?? (__DIR__ . '/../storage/uploads');
}

function max_upload_size(): int
{
    return (int) (app_config()['files']['max_size_bytes'] ?? (20 * 1024 * 1024));
}

function ensure_upload_directory(): void
{
    $dir = upload_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function public_upload_directory(): string
{
    return __DIR__ . '/../assets/uploads';
}

function ensure_public_upload_directory(): void
{
    $dir = public_upload_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function normalize_uploaded_files(?array $files): array
{
    if (!$files || !isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }

    return $normalized;
}

function save_uploaded_image(array $file, string $folder = 'branding'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image too large. Max 5MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $mime = $tmp !== '' ? (mime_content_type($tmp) ?: '') : '';

    if (!str_starts_with($mime, 'image/')) {
        throw new RuntimeException('Only image files are allowed.');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $original = $file['name'] ?? 'image.bin';
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Invalid image format. Allowed: jpg, jpeg, png, webp, gif.');
    }

    ensure_public_upload_directory();

    $folderSafe = preg_replace('/[^a-z0-9_-]/i', '', $folder) ?: 'branding';
    $targetDir = public_upload_directory() . '/' . $folderSafe;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $storedName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $targetDir . '/' . $storedName;

    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Failed to save image.');
    }

    return 'assets/uploads/' . $folderSafe . '/' . $storedName;
}

function sanitize_limited_html(string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h3><h4><blockquote><code>';
    $clean = strip_tags($html, $allowed);

    // Remove inline event handlers.
    $clean = preg_replace('/\son[a-z]+\s*=\s*(\"[^\"]*\"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';
    // Neutralize javascript: URLs in href attributes.
    $clean = preg_replace('/href\s*=\s*([\"\'])\s*javascript:[^\"\']*\1/i', 'href="#"', $clean) ?? '';

    return trim($clean);
}

function absolute_url(string $path): string
{
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $base = runtime_base_url();
    if ($base === '') {
        return '/' . ltrim($path, '/');
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function portal_absolute_url(string $path): string
{
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $base = portal_base_url();
    if ($base === '') {
        return '/' . ltrim($path, '/');
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function public_absolute_url(string $path): string
{
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $base = public_base_url();
    if ($base === '') {
        return '/' . ltrim($path, '/');
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function current_query_params_without_page(array $exclude = []): array
{
    $params = $_GET;
    unset($params['page']);

    foreach ($exclude as $key) {
        unset($params[$key]);
    }

    $clean = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $clean[$key] = $value;
    }

    return $clean;
}

function is_portal_route_page(string $page): bool
{
    return in_array(strtolower(trim($page)), [
        'login',
        'logout',
        'dashboard',
        'jobs',
        'job',
        'invoices',
        'invoice',
        'support',
        'messages',
        'community',
        'payments',
        'payouts',
        'withdrawals',
        'users',
        'settings',
        'profile',
        'forgot_password',
        'reset_password',
        'otp',
        'google_callback',
        'payment_callback',
        'payment_webhook',
        'intake_submission',
    ], true);
}

function normalize_public_href(string $href, string $fallback = ''): string
{
    $href = trim($href);
    $fallback = trim($fallback);

    if ($href === '' || $href === '#') {
        return $fallback;
    }

    $lower = strtolower($href);
    if (
        str_starts_with($lower, 'javascript:')
        || str_starts_with($lower, 'data:')
        || str_starts_with($lower, 'vbscript:')
    ) {
        return $fallback;
    }

    if (preg_match('~^(mailto:|tel:)~i', $href)) {
        return $href;
    }

    if (str_starts_with($href, '//')) {
        return 'https:' . $href;
    }

    if (preg_match('~^https?://~i', $href)) {
        return $href;
    }

    if (preg_match('~^www\.~i', $href)) {
        return 'https://' . $href;
    }

    if (preg_match('~^[a-z0-9.-]+\.[a-z]{2,}(/.*)?$~i', $href)) {
        return 'https://' . $href;
    }

    if (str_starts_with($href, '?')) {
        parse_str(ltrim($href, '?'), $queryParams);
        $page = trim((string) ($queryParams['page'] ?? ''));
        if ($page !== '' && is_portal_route_page($page)) {
            return portal_absolute_url('index.php' . $href);
        }

        return public_absolute_url('index.php' . $href);
    }

    if (preg_match('~^(index\.php)(\?.*)?$~i', $href, $matches)) {
        $query = (string) ($matches[2] ?? '');
        $page = '';
        if ($query !== '') {
            parse_str(ltrim($query, '?'), $queryParams);
            $page = trim((string) ($queryParams['page'] ?? ''));
        }

        if ($page !== '' && is_portal_route_page($page)) {
            return portal_absolute_url($href);
        }

        return public_absolute_url($href);
    }

    if (preg_match('~^(invoice|download|export|logout)\.php(\?.*)?$~i', $href)) {
        return portal_absolute_url($href);
    }

    return public_absolute_url($href);
}

function href_is_external(string $href): bool
{
    $href = trim($href);
    if ($href === '') {
        return false;
    }

    $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    $urlHost = strtolower((string) parse_url($href, PHP_URL_HOST));
    if ($urlHost === '') {
        return false;
    }

    $appHost = strtolower((string) parse_url(runtime_base_url(), PHP_URL_HOST));
    if ($appHost === '') {
        return false;
    }

    return $urlHost !== $appHost;
}

function is_valid_color_hex(string $color): bool
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
}

function int_setting(string $key, int $fallback): int
{
    $value = setting($key, (string) $fallback);
    if (!is_numeric($value)) {
        return $fallback;
    }

    return (int) $value;
}

function email_enabled(): bool
{
    return setting('mail_enabled', '0') === '1';
}

function email_notifications_enabled(): bool
{
    return setting('email_notifications_enabled', '1') === '1';
}

function google_auth_enabled(): bool
{
    return setting('google_auth_enabled', '0') === '1';
}

function google_client_id(): string
{
    return trim(setting('google_client_id', ''));
}

function google_client_secret(): string
{
    return trim(setting('google_client_secret', ''));
}

function google_redirect_uri_override(): string
{
    return trim(setting('google_redirect_uri', ''));
}

function google_redirect_uri(): string
{
    $override = google_redirect_uri_override();
    if ($override !== '' && preg_match('~^https?://~i', $override)) {
        return $override;
    }

    return portal_absolute_url(url('google_callback'));
}

function google_oauth_redirect_problems(?string $redirect = null, ?string $portalBase = null): array
{
    $redirect = trim($redirect ?? google_redirect_uri());
    $portalBase = trim($portalBase ?? portal_base_url());
    $issues = [];
    $parts = parse_url($redirect);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $path = (string) ($parts['path'] ?? '');
    if ($host === '' || !in_array($scheme, ['https', 'http'], true)) {
        return ['Google callback must be a valid absolute URL.'];
    }
    $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    if ($scheme !== 'https' && !$local) {
        $issues[] = 'Google callback must use HTTPS on the live website.';
    }
    $portalHost = strtolower((string) parse_url($portalBase, PHP_URL_HOST));
    if ($portalHost !== '' && $host !== $portalHost) {
        $issues[] = 'The Google callback host must match the portal host so its OAuth session can be verified.';
    }
    if (basename($path) !== 'index.php') {
        $issues[] = 'The Google callback must point to the portal index.php handler.';
    }
    $query = [];
    parse_str((string) ($parts['query'] ?? ''), $query);
    if (($query['page'] ?? null) !== 'google_callback') {
        $issues[] = 'The callback query must include page=google_callback.';
    }
    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
        $issues[] = 'The Google callback URL must not contain user information or a fragment.';
    }
    return $issues;
}

function google_auth_ready(): bool
{
    return google_auth_enabled() && google_client_id() !== '' && google_client_secret() !== '';
}

function ai_enabled(): bool
{
    return setting('ai_enabled', '0') === '1';
}

function ai_provider(): string
{
    $provider = strtolower(trim(setting('ai_provider', 'openai')));
    return $provider !== '' ? $provider : 'openai';
}

function ai_model(): string
{
    $model = trim(setting('ai_model', 'gpt-4o-mini'));
    return $model !== '' ? $model : 'gpt-4o-mini';
}

function ai_api_key(): string
{
    return trim(setting('ai_api_key', ''));
}

function ai_ready(): bool
{
    return ai_enabled() && ai_provider() === 'openai' && ai_api_key() !== '';
}

function mail_log_path(): string
{
    return __DIR__ . '/../storage/mail.log';
}

function log_email_message(string $to, string $subject, string $message): void
{
    $line = '[' . now() . '] TO=' . $to . ' SUBJECT=' . $subject . ' BODY=' . str_replace(["\r", "\n"], ' ', $message) . PHP_EOL;
    @file_put_contents(mail_log_path(), $line, FILE_APPEND);
}

function send_email(string $to, string $subject, string $message): bool
{
    $to = trim($to);
    if ($to === '') {
        return false;
    }

    log_email_message($to, $subject, $message);

    if (!email_enabled()) {
        return false;
    }

    $fromEmail = trim(setting('mail_from_email', 'no-reply@iworksgt.com'));
    $fromName = trim(setting('mail_from_name', app_name()));
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;

    return @mail($to, $subject, $message, implode("\r\n", $headers));
}

function sso_secret(): string
{
    return app_config()['security']['sso_shared_secret'] ?? '';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function is_user_online(?string $lastSeenAt, int $withinSeconds = 180): bool
{
    if (!$lastSeenAt) {
        return false;
    }

    $last = strtotime($lastSeenAt);
    if ($last === false) {
        return false;
    }

    return (time() - $last) <= max(15, $withinSeconds);
}

function users_last_seen_enabled(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    try {
        db_one('SELECT last_seen_at FROM users LIMIT 1');
        $available = true;
    } catch (Throwable $e) {
        $available = false;
    }

    return $available;
}
