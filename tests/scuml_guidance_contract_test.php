<?php
declare(strict_types=1);
/**
 * SCUML user-protection, publication and checkout contract.
 * Avoid unofficial EFCC agency claims or charging a government "certificate fee."
 */
$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$ok=static function(bool $pass,string $label):void {
    if (!$pass) throw new RuntimeException($label);
};
$publisher=$read('scripts/publish_scuml_guidance.php');
$release=$read('scripts/deploy_scuml_guidance.sh');
$detail=$read('views/public-service-detail.php');
$header=$read('views/partials/public-header.php');
$index=$read('views/public-services-refined.php');
$svg=$read('assets/branding/services/scuml-document-example.svg');
$style=$read('assets/css/scuml-service.css');
$meta=$read('app/public_marketing.php');
$policy=$read('app/customer_service_policy.php');

$ok(str_contains($publisher,'scuml-business-name-document-support')
    && str_contains($publisher,'scuml-company-document-support')
    && str_contains($publisher,'35000.00') && str_contains($publisher,'45000.00'),
    'Business Name and Limited Company preparation fees must be separate and explicit.');
$ok(str_contains($publisher,'EFCC/SCUML registration and certification are completely free')
    && str_contains($publisher,'not an authorised EFCC/SCUML agent')
    && str_contains($publisher,'The customer applies through the official government portal')
    && str_contains($publisher,'scuml.efcc.gov.ng/registration-guideline/'),
    'Government zero-fee and no-official-agency disclaimers must be unambiguous.');
$ok(str_contains($publisher,'Certificate') && str_contains($publisher,'BN-01')
    && str_contains($publisher,'MEMART') && str_contains($publisher,'TIN printout')
    && str_contains($publisher,'sector licence'),
    'Publish EFCC core CAC, tax and sector-specific document guidance.');
$ok(str_contains($publisher,'--backup-confirmed')
    && str_contains($publisher,'$pdo->beginTransaction();')
    && str_contains($publisher,'$pdo->rollBack();')
    && str_contains($publisher,'$pdo->commit();')
    && str_contains($publisher,'if (!$toCreate)')
    && !str_contains($publisher,'ON DUPLICATE KEY UPDATE')
    && !str_contains($publisher,'UPDATE services SET'),
    'Publishing must insert only missing service slugs, never modify existing orders/prices.');
$ok(str_contains($publisher,"'is_active' => 1")
    && str_contains($publisher,"'is_public' => 1")
    && str_contains($publisher,"'category' => 'compliance'")
    && str_contains($publisher,"'tax_percent' => 7.50"),
    'New products must be visible in catalogue and use configured normal service tax.');
$ok(str_contains($detail,'$isScumlSupport') &&
    str_contains($detail,'iw-scuml-service') &&
    str_contains($detail,'scuml-document-example.svg') &&
    str_contains($detail,'data:image/svg+xml;base64,') &&
    str_contains($detail,'$scumlChoices') &&
    str_contains($detail,"get_service_by_slug($slug)") &&
    str_contains($detail,'EFCC/SCUML') &&
    str_contains($detail,'we are not affiliated') === false,
    'Product detail must use actual database offer prices and display illustrative document and accurate disclaimers.');
$ok(str_contains($index,"'scuml' => 'bi-file-earmark-check'"),
    'SCUML service must have a relevant catalogue icon.');
$ok(str_contains($meta,'scuml-business-name-document-support')
    && str_contains($meta,'scuml-company-document-support'),
    'Both service products need accurate SEO metadata.');
$ok(str_contains($header,'iw-scuml-service-css')
    && str_contains($header,"assets/css/scuml-service.css"),
    'SCUML page must load its responsive styling with split-root fallback.');
$ok(str_contains($style,'@media(max-width:560px)'),
    'Product sample, price comparison and requirements must work on phones.');
$ok(str_contains($svg,'NOT AN OFFICIAL CERTIFICATE')
    && str_contains($svg,'ILLUSTRATION FOR SERVICE EXPLANATION ONLY')
    && !str_contains($svg,'sealID=')
    && !str_contains($svg,'efcc.gov.ng/'),
    'The example cannot masquerade as a genuine issued government certificate.');
$ok(!str_contains($policy,'scuml-business-name-document-support')
    && !str_contains($policy,'scuml-company-document-support'),
    'SCUML documents retain the established standard catalogue/invoice route.');
$ok(str_contains($release,'check_remediation_release.php')
    && str_contains($release,'IWORKS_PRIVATE_DB_BACKUP_VERIFIED')
    && str_contains($release,'--backup-confirmed')
    && str_contains($release,'rollback()')
    && str_contains($release,'iw_scuml_release=')
    && str_contains($release,'scripts/publish_scuml_guidance.php'),
    'Release must dry-run, require verified backup, insert safely, and verify both live product pages.');
echo "scuml_guidance_contract_test passed\n";
