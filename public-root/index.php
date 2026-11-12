<?php
/**
 * iWORKS GLOBAL LTD public-domain front controller.
 *
 * This tiny entry point delegates to the single installed portal application,
 * so the public site and authenticated portal share published CMS data without
 * duplicating config.php, database credentials, orders or customer files.
 */
declare(strict_types=1);

$publicRoot = __DIR__;
$portalCandidates = [
    $publicRoot . '/portal.iworksgt.com/index.php',
    dirname($publicRoot) . '/portal.iworksgt.com/index.php',
];
$portalIndex = null;
foreach ($portalCandidates as $candidate) {
    $candidate = realpath($candidate);
    if ($candidate !== false && $candidate !== realpath(__FILE__) && is_file($candidate)) {
        $portalIndex = $candidate;
        break;
    }
}
if ($portalIndex === null) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo "Public website temporarily unavailable. Please contact iWorks support.";
    exit;
}

require $portalIndex;
