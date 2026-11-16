<?php
declare(strict_types=1);
/**
 * Current official iWORKS social share identity.
 * Static source-only check; no access to private server settings needed.
 */
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/views/partials/public-header.php');
$svg=(string)file_get_contents($root.'/assets/branding/iworks-social-share.svg');
$deployer=(string)file_get_contents($root.'/scripts/deploy_invoice_payment_layout.sh');
$launcher=(string)file_get_contents($root.'/b.sh');
$png=$root.'/assets/branding/iworks-social-share.png';
$assert=static function(bool $yes,string $message):void {if(!$yes)throw new RuntimeException($message);};

$assert(is_file($png) && is_readable($png) && filesize($png)>10000,
    'Social-sharing PNG missing. Render official vector source to image before merging.');
$info=getimagesize($png);
$assert(is_array($info) && $info[0]===1200 && $info[1]===630 &&
    (int)($info[2]??0)===IMAGETYPE_PNG,
    'Social preview must be a compatible 1200x630 PNG (not SVG or an icon).');
$assert(str_contains($svg,'viewBox="0 0 1338 123"')
    && str_contains($svg,'iworksgt.com')
    && str_contains($svg,'#f56613'),
    'Share card must contain the approved current orange-and-black iWORKS vector logo.');
$assert(str_contains($header,"public_absolute_url('assets/branding/iworks-social-share.png')")
    && str_contains($header,"$"."seoPageType === 'home'")===false
    && str_contains($header,'$ogImageIsBrandCard')
    && str_contains($header,'og:image:width')
    && str_contains($header,'og:image:height')
    && str_contains($header,'og:image:secure_url')
    && str_contains($header,'twitter:image')
    && str_contains($header,'summary_large_image')
    && str_contains($header,'iWORKS GLOBAL'),
    'All normal public pages must emit complete, correct Open Graph and Twitter metadata.');
$assert(str_contains($header,"['blog_post','case_study']")
    && str_contains($header,'$hasEditorialShareImage'),
    'Blog and case study share previews should preserve intentionally published editorial images.');
$assert(!str_contains($header,'$ogImage = $logo ?: $favicon'),
    'Old uploaded logo must never be used as default social preview.');
$assert(str_contains($deployer,'assets/branding/iworks-social-share.png')
    && str_contains($deployer,'views/partials/public-header.php')
    && str_contains($deployer,'views/payments.php')
    && str_contains($deployer,'views/partials/header.php')
    && str_contains($deployer,'assets/css/payment-review-mobile.css')
    && str_contains($deployer,'assets/branding/iworks-desktop.svg')
    && str_contains($deployer,'scuml_single_title_contract_test.php')
    && str_contains($deployer,'social_share_preview_contract_test.php')
    && str_contains($launcher,'scripts/deploy_invoice_payment_layout.sh'),
    'Phone-friendly cPanel publisher must publish image and metadata with invoice and SCUML changes.');
echo "social_share_preview_contract_test passed\n";
