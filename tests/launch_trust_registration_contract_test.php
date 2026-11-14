<?php
declare(strict_types=1);
/**
 * Launch transparency: no spoofed country, unverified Google score or
 * regulator/vendor logo misrepresented as a client reference.
 */
$root = dirname(__DIR__);
$assert = static function (bool $ok, string $reason): void {
    if (!$ok) throw new RuntimeException($reason);
};
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function portal_country_options(): array {
    return ['NG'=>'Nigeria', 'ES'=>'Spain'];
}
function portal_state_options_map(): array {
    return ['NG'=>['Lagos'], 'ES'=>['Catalonia']];
}
function old_input(string $field, string $default = ''): string {
    return $default;
}
function public_url(string $slug): string { return 'https://iworksgt.com/'.$slug; }
function url(string $slug): string { return 'index.php?page='.$slug; }
function csrf_token(): string { return 'synthetic_csrf'; }
function selected_if(string $value, string $selected): string {
    return $value === $selected ? ' selected' : '';
}
function google_auth_ready(): bool { return false; }
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'ES';
ob_start();
require $root.'/views/register.php';
$registration = (string)ob_get_clean();
$assert(str_contains($registration, 'value="" selected disabled'),
    'A new customer must explicitly select a residence country instead of silently using an IP hint.');
$assert(str_contains($registration, 'Country of residence *'),
    'The country field must explain that it governs residence, not current IP.');
$assert(str_contains($registration, '/terms') && str_contains($registration, '/privacy'),
    'Account creation must show Terms and Privacy beside its action.');
$assert(str_contains($registration, 'state.disabled = !iso'),
    'Dependent state dropdown must remain disabled until a real country is selected.');
$router = (string)file_get_contents($root.'/index.php');
$assert(str_contains($router, '!in_array($country, array_values(portal_country_options()), true)'),
    'Unsupported residence-country values must be rejected server-side before creating an account.');
$home = (string)file_get_contents($root.'/views/public-home-clean.php');
$assert(str_contains($home, 'Selected client projects') && str_contains($home, 'corporate')
    && str_contains($home, 'go\\s*daddy'),
    'The homepage client ribbon must not pass off CAC or GoDaddy as client companies.');
$reviews = (string)file_get_contents($root.'/views/partials/public-review-carousel.php');
$assert(!str_contains($reviews, '4.8 <span') && str_contains($reviews,'View our Google listing'),
    'Google ratings must never be hard-coded before independent verification.');
echo "launch_trust_registration_contract_test passed\\n";
