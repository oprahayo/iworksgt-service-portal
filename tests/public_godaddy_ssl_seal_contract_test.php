<?php
declare(strict_types=1);
/**
 * Prevent duplicated, fabricated or cross-domain SSL certificate seals.
 * The provided GoDaddy snippet belongs only to the apex marketing footer.
 */
$root = dirname(__DIR__);
$footer = (string)file_get_contents($root.'/views/partials/public-footer.php');
$css = (string)file_get_contents($root.'/assets/css/site-launch-polish.css');
$publisher = (string)file_get_contents($root.'/scripts/deploy_solutions_ui_refresh.sh');
$assert = static function (bool $okay, string $message): void {
    if (!$okay) { throw new RuntimeException($message); }
};
$sealId = 'A5oMK5nZAZkvd3jSvzwT3oEd8fDjJjUipTnwExIj2h3fKve5G9Wks8KQdZHy';
$script = 'https://seal.godaddy.com/getSealBasic?sealID='.$sealId;
$assert(substr_count($footer, 'id="siteseal"') === 1,
    'GoDaddy seal must appear exactly once in the footer template.');
$assert(substr_count($footer, $script) === 1,
    'Use the exact approved GoDaddy external seal code, never a fake image.');
$assert(str_contains($footer,'<script async type="text/javascript" src="'.$script.'"></script>'),
    'Keep the supplied async script with its exact GoDaddy seal ID.');
$assert(str_contains($footer,"['iworksgt.com', 'www.iworksgt.com']") &&
    str_contains($footer,'if ($iwPublicSealEnabled)'),
    'Only the public website host should receive this SSL certificate seal.');
$assert(!str_contains($footer,"'portal.iworksgt.com', 'www.iworksgt.com'"),
    'Do not imply the SSL seal covers the separate portal hostname.');
$brand = strpos($footer,'class="public-footer-brand-row"');
$seal = strpos($footer,'class="public-footer-ssl-seal"');
$copyright = strpos($footer,'class="public-footer-bottom"');
$assert($brand !== false && $seal !== false && $copyright !== false &&
    $brand < $seal && $seal < $copyright,
    'Position SSL seal after brand/socials and before copyright, not over content.');
$assert(str_contains($css,'body.page-public .public-footer-ssl-seal') &&
    str_contains($css,'justify-content:center;') &&
    str_contains($css,'#siteseal'),
    'Responsive public footer must center the real externally supplied seal.');
$assert(str_contains($publisher,'public_godaddy_ssl_seal_contract_test.php') &&
    str_contains($publisher,'GoDaddy SSL site seal missing') &&
    str_contains($publisher,'rollback()'),
    'Publisher must test the approved seal and roll back if live rendering lacks its snippet.');
echo "public_godaddy_ssl_seal_contract_test passed\n";
