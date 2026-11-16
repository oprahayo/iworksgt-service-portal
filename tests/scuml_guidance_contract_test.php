<?php
declare(strict_types=1);
/**
 * One SCUML offer — source-only contract. No live database access or changes.
 * EFCC/SCUML issues the certificate free; iWORKS sells independent guidance.
 */
$root=dirname(__DIR__);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$ok=static function(bool $pass,string $why):void {if(!$pass)throw new RuntimeException($why);};
$publisher=$read('scripts/publish_scuml_guidance.php');
$release=$read('scripts/deploy_scuml_guidance.sh');
$detail=$read('views/public-service-detail.php');
$header=$read('views/partials/public-header.php');
$listing=$read('views/public-services-refined.php');
$sample=$read('assets/branding/services/scuml-document-example.svg');
$style=$read('assets/css/scuml-service.css');
$meta=$read('app/public_marketing.php');
$policy=$read('app/customer_service_policy.php');
$router=$read('index.php');

$ok(str_contains($publisher, '$canonical='."'scuml-document-support'")
    && str_contains($publisher, "'price'=>40000.00")
    && str_contains($publisher,'SCUML Registration')
    && !str_contains($publisher,"'price' => 35000.00")
    && !str_contains($publisher,"'price' => 45000.00"),
    'Only one canonical customer product at NGN 40,000.');
$ok(str_contains($publisher,'EFCC/SCUML registration and certificate are free')
    && str_contains($publisher,'not an authorised agent or representative')
    && str_contains($publisher,'the official EFCC portal')
    && str_contains($publisher,'scuml.efcc.gov.ng/registration-guideline/'),
    'No official certificate charge or EFCC affiliation claims.');
$ok(str_contains($publisher,'CAC') &&
    str_contains($publisher,'TIN') &&
    str_contains($publisher,'professional or sector') &&
    str_contains($publisher,'--backup-confirmed'),
    'Keep concise real requirements and protected publishing workflow.');
$ok(str_contains($publisher,'$oldSlugs=[')
    && str_contains($publisher,'scuml-business-name-document-support')
    && str_contains($publisher,'scuml-company-document-support')
    && str_contains($publisher,'UPDATE services SET is_public=0')
    && str_contains($publisher,'$pdo->beginTransaction();')
    && str_contains($publisher,'$pdo->rollBack();')
    && str_contains($publisher,'$pdo->commit();')
    && !str_contains($publisher,'DELETE FROM services')
    && !str_contains($publisher,'UPDATE jobs')
    && !str_contains($publisher,'UPDATE invoices'),
    'Legacy offers hidden from public listings without deleting job history or modifying invoices.');
$ok(str_contains($publisher,"'is_active'=>1") &&
    str_contains($publisher,"'is_public'=>1") &&
    str_contains($publisher,"'category'=>'compliance'") &&
    str_contains($publisher,"'tax_percent'=>7.50"),
    'Canonical service must be published in compliance category and use normal tax policy.');
$ok(str_contains($policy,"if ($"."slug === 'scuml-document-support')")
    && str_contains($policy,"'scuml-business-name-document-support'")
    && str_contains($policy,"'scuml-company-document-support'"),
    'Flat NGN listing should remain standard; duplicate old services must be suppressed.');
$ok(str_contains($router, "['slug'=>'scuml-document-support']")
    && str_contains($router, "301"),
    'Existing direct old URLs must redirect to one canonical page.');
$ok(str_contains($detail,"$"."isScumlSupport = $"."serviceSlug === 'scuml-document-support'") &&
    str_contains($detail,'iw-scuml-feature') &&
    str_contains($detail,'iw-scuml-price-block') &&
    str_contains($detail,'scuml-document-example.svg') &&
    str_contains($detail,'data:image/svg+xml;base64,') &&
    str_contains($detail,'₦80,000') &&
    str_contains($detail,'2× the standard iWORKS service fee') &&
    !str_contains($detail,'$scumlChoices') &&
    !str_contains($detail,'iw-scuml-structure-list'),
    'Use brand-native one-service layout, redacted example and separate power-sector quote.');
$ok(str_contains($listing,"'scuml' => 'bi-file-earmark-check'") &&
    str_contains($meta,"'scuml-document-support'") &&
    !str_contains($meta,"'scuml-business-name-document-support'") &&
    !str_contains($meta,"'scuml-company-document-support'"),
    'Catalogue icon and SEO must reflect single product.');
$ok(str_contains($header,"['scuml-document-support']") &&
    str_contains($header,'iw-scuml-service-css') &&
    str_contains($style,'.iw-scuml-feature') &&
    str_contains($style,'@media (max-width:640px)'),
    'Responsive CSS must be loaded on the canonical product, including split-root cPanel.');
$ok(str_contains($sample,'SAMPLE · NOT VALID') &&
    str_contains($sample,'NOT AN OFFICIAL CERTIFICATE') &&
    str_contains($sample,'barcode, seal, signature and dates intentionally omitted') &&
    !str_contains($sample,'IWORKS GLOBAL') &&
    !str_contains($sample,'310679'),
    'Certificate-format illustration must omit real personal identifiers, signatures and QR data.');
$ok(str_contains($release,'scuml-document-support')
    && !str_contains($release,'for slug in scuml-business-name-document-support')
    && str_contains($release,'app/customer_service_policy.php')
    && str_contains($release,'index.php')
    && str_contains($release,'check_remediation_release.php')
    && str_contains($release,'IWORKS_PRIVATE_DB_BACKUP_VERIFIED')
    && str_contains($release,'rollback()'),
    'Deployer must include canonical routing, preflight, private backup and single-product verification.');
echo "scuml_guidance_contract_test passed\n";
