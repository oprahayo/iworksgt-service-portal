<?php
declare(strict_types=1);
/**
 * Indicative 2026 website design packages, NOT payable invoice prices.
 * A confirmed written quotation and scope are required for every build.
 * Separate, independently researched domestic and overseas price lists:
 * USD figures are not NGN/FX conversions.
 */
function iworks_website_package_options(string $currency): array
{
    $international = strtoupper(trim($currency)) !== 'NGN';
    $rates = $international
        ? ['landing'=>320,'business'=>650,'commerce'=>1400,'custom'=>3000]
        : ['landing'=>180000,'business'=>350000,'commerce'=>750000,'custom'=>1500000];
    $money = static function (int $amount) use ($international): string {
        return ($international ? 'USD ' : 'NGN ') . number_format($amount, 0);
    };
    return [
        [
            'key'=>'landing', 'title'=>'Launch Landing Page',
            'price'=>$rates['landing'], 'display'=>'From '.$money($rates['landing']),
            'scope'=>'One-page mobile-first campaign or launch website, one lead form, on-page SEO basics and handover.',
            'type'=>'quote',
        ],
        [
            'key'=>'business', 'title'=>'Business Website',
            'price'=>$rates['business'], 'display'=>'From '.$money($rates['business']),
            'scope'=>'Up to 5 content pages, mobile-friendly layout, contact/enquiry form, essential SEO and basic training.',
            'type'=>'quote',
        ],
        [
            'key'=>'commerce', 'title'=>'Online Store',
            'price'=>$rates['commerce'], 'display'=>'From '.$money($rates['commerce']),
            'scope'=>'Catalogue and checkout for an agreed small store; payment gateway/provider charges and product uploads quoted separately.',
            'type'=>'quote',
        ],
        [
            'key'=>'custom', 'title'=>'Custom Portal / Enterprise',
            'price'=>$rates['custom'], 'display'=>'Custom quotation · Typical projects from '.$money($rates['custom']),
            'scope'=>'Portals, dashboards, booking engines, integrations, memberships and custom workflows require discovery and written scope.',
            'type'=>'custom',
        ],
    ];
}


/**
 * Paid ordinary product prices for non-Nigerian residents, independent from
 * the NGN exchange rate. Admin/cms domestic prices remain authoritative.
 * Unknown overseas services require a custom quote; never silently convert a
 * discounted Nigeria-only tariff into USD at market FX.
 */
function iworks_international_standard_rate(string $slug): ?float
{
    $rates = [
        'seo-smm-digital-marketing' => 450.00,
        'domain-web-hosting' => 180.00,
    ];
    return $rates[$slug] ?? null;
}
