<?php
declare(strict_types=1);
function cac_ui_assert(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
$root=dirname(__DIR__);
$wizard=file_get_contents($root.'/views/client-cac-filing.php');
$chooser=file_get_contents($root.'/views/client-cac-pathway.php');
$clientHeader=file_get_contents($root.'/views/partials/client-header.php');
$css=file_get_contents($root.'/assets/css/cac-registration.css');
$router=file_get_contents($root.'/index.php');
$orderIntent=file_get_contents($root.'/app/order_intent.php');
$detail=file_get_contents($root.'/views/public-service-detail.php');
$policy=file_get_contents($root.'/app/customer_service_policy.php');
cac_ui_assert(str_contains($clientHeader,'customer-focused-checkout.css'),
    'Focused checkout CSS must load in the real client shell.');
cac_ui_assert(str_contains($chooser,'Enterprise / Business Name') && str_contains($chooser,'Limited Company (Ltd)'),
    'Separate Business Name and Ltd customer choices must be clear.');
cac_ui_assert(str_contains($chooser,"['type'=>'business']") && str_contains($chooser,"['type'=>'company']"),
    'Pathway choices must each open the appropriate intake.');
foreach([1,2,3,4,5] as $i){
    cac_ui_assert(str_contains($wizard,'data-cac-stage="'.$i.'"'),
        'CAC wizard must have a stage '.$i);
    cac_ui_assert(str_contains($wizard,'data-cac-jump="'.$i.'"'),
        'Step '.$i.' must be represented in the progress control.');
}
cac_ui_assert(str_contains($wizard,'validStage(')
    && str_contains($wizard,'field.reportValidity()')
    && str_contains($wizard,'data-cac-next="2"')
    && str_contains($wizard,'data-cac-next="3"'),
    'Advance buttons must validate current visible step.');
cac_ui_assert(str_contains($wizard,'data-cac-next="5"')
    && str_contains($wizard,'data-cac-back="4"'),
    'Saved documents must lead to review and allow backtracking.');
cac_ui_assert(str_contains($router,"'step'=>'documents'"),
    'Saving CAC details must land on the document-upload step.');
cac_ui_assert(str_contains($wizard,'name="filing[company_type]"')
    && !str_contains($wizard,'<option value="limited_by_guarantee"'),
    'Self-serve company intake may not offer Ltd by Guarantee.');
cac_ui_assert(str_contains($wizard,'Additional contact information (optional)'),
    'Nonessential owner contact fields should be collapsible.');
cac_ui_assert(str_contains($wizard,'data-cac-id-type') && str_contains($wizard,'data-cac-id-number')
    && str_contains($wizard,'cacNameFeedback'),
    'Existing identity-format and name-screen guidance must remain.');
cac_ui_assert(str_contains($css,'.iw-cac-journey-progress') &&
    str_contains($css,'.iw-cac-journey-ready [data-cac-stage]') &&
    str_contains($css,'@media(max-width:650px)'),
    'Progressive stages must be styled and responsive.');
cac_ui_assert(str_contains($policy,'iworks_customer_registration_variants(')
    && str_contains($policy,"'cac-business-name'"),
    'Customer catalogue must expose two guided virtual variants.');
cac_ui_assert(str_contains($router,"$"."publicSlug === 'cac-business-name'") &&
    str_contains($orderIntent,"'cac-business-name'"),
    'Public detail and login flow must resolve virtual Enterprise slug.');
cac_ui_assert(str_contains($detail,"$"."registrationType === 'business'"),
    'Public buying CTA must name the selected CAC pathway.');
echo "cac_registration_choice_ui_test passed\n";
