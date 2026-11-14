<?php
declare(strict_types=1);

/**
 * Launch catalogue — new transactions only. The country recorded on the
 * customer's account determines the commercial tier and invoice currency.
 * IP-geolocation is only an anonymous display hint, never checkout authority.
 *
 * Standalone regional prices are commercially set, NOT FX conversions. Extra
 * regulated charges and unusual scope must be disclosed before purchase.
 */
function iworks_launch_country_code(string $country): ?string
{
    $value = strtolower(trim($country));
    if ($value === '') return null;
    $aliases = [
        'nigeria'=>'NG','ng'=>'NG','united kingdom'=>'GB','uk'=>'GB','gb'=>'GB',
        'united states'=>'US','united states of america'=>'US','usa'=>'US','us'=>'US',
        'canada'=>'CA','ca'=>'CA','germany'=>'DE','de'=>'DE',
        'australia'=>'AU','au'=>'AU','spain'=>'ES','es'=>'ES',
        'france'=>'FR','fr'=>'FR','ghana'=>'GH','gh'=>'GH',
        'kenya'=>'KE','ke'=>'KE','south africa'=>'ZA','za'=>'ZA',
        'united arab emirates'=>'AE','ae'=>'AE','india'=>'IN','in'=>'IN',
        'netherlands'=>'NL','nl'=>'NL','ireland'=>'IE','ie'=>'IE',
    ];
    if (isset($aliases[$value])) return $aliases[$value];
    if (function_exists('portal_country_options')) {
        foreach (portal_country_options() as $iso=>$label) {
            if (strtolower((string)$label)===$value) return (string)$iso;
        }
    }
    // Unknown account countries cannot silently fall into the Nigeria tier.
    if (preg_match('/^[a-z]{2}$/',$value)) return strtoupper($value);
    return null;
}

function iworks_launch_billing_country(array $user): string
{
    $country=iworks_launch_country_code((string)($user['country']??''));
    if ($country===null) {
        throw new RuntimeException('Confirm your billing country in your account before ordering.');
    }
    return $country;
}
function iworks_launch_currency_for_country(string $country): string
{
    $code=iworks_launch_country_code($country);
    if ($code===null) throw new RuntimeException('A valid billing country is required.');
    return $code==='NG' ? 'NGN' : 'USD';
}
function iworks_launch_display_country(): string
{
    if (function_exists('is_logged_in') && is_logged_in() && function_exists('current_user')) {
        $user=current_user();
        if (is_array($user)) {
            $known=iworks_launch_country_code((string)($user['country']??''));
            if ($known!==null) return $known;
        }
    }
    // Cloudflare header is UI guidance only, not a verified address. Reverse
    // proxies can forward this as an untrusted request header.
    $edge=(string)($_SERVER['HTTP_CF_IPCOUNTRY']??'');
    if (preg_match('/^[A-Z]{2}$/D',$edge) && !in_array($edge,['XX','T1'],true)) return $edge;
    return 'NG';
}
function iworks_launch_account_currency(array $user): string
{
    return iworks_launch_currency_for_country(iworks_launch_billing_country($user));
}

function iworks_launch_amount(string $key, float $fallback): float
{
    $raw=function_exists('setting') ? setting($key,(string)$fallback) : (string)$fallback;
    if (!is_numeric($raw) || !is_finite((float)$raw)) return $fallback;
    $v=(float)$raw;
    return ($v>0 && $v<=100000000) ? round($v,2) : $fallback;
}
function iworks_launch_registration_options(?string $country=null): array
{
    $currency=iworks_launch_currency_for_country($country??iworks_launch_display_country());
    $international=$currency==='USD';
    return [
        'business'=>[
            'key'=>'business','title'=>'Business Name','subtitle'=>'Sole proprietor or partnership',
            'description'=>'A trading name for small businesses and self-employed founders; not a limited company.',
            'price'=>iworks_launch_amount($international?'launch_bn_usd':'launch_bn_ngn',$international?95:49000),
            'currency'=>$currency,'mode'=>'cac','type'=>'business','package'=>'business','badge'=>'Popular',
        ],
        'company-small'=>[
            'key'=>'company-small','title'=>'Limited Company — up to ₦1m share capital',
            'subtitle'=>'Private company limited by shares',
            'description'=>'For a company with issued share capital up to ₦1,000,000.',
            'price'=>iworks_launch_amount($international?'launch_company_small_usd':'launch_company_small_ngn',$international?175:60000),
            'currency'=>$currency,'mode'=>'cac','type'=>'company','package'=>'company-small','badge'=>'',
        ],
        'company-large'=>[
            'key'=>'company-large','title'=>'Limited Company — ₦2m to ₦5m share capital',
            'subtitle'=>'Private company limited by shares',
            'description'=>'For larger share capital; any amount above ₦1m and up to ₦5m uses this package.',
            'price'=>iworks_launch_amount($international?'launch_company_large_usd':'launch_company_large_ngn',$international?325:150000),
            'currency'=>$currency,'mode'=>'cac','type'=>'company','package'=>'company-large','badge'=>'',
        ],
        'ngo'=>[
            'key'=>'ngo','title'=>'NGO / Incorporated Trustees',
            'subtitle'=>'Associations, charities and nonprofits',
            'description'=>'More extensive constitution/trustee and regulatory checks; subject to specialist review.',
            'price'=>iworks_launch_amount($international?'launch_ngo_usd':'launch_ngo_ngn',$international?495:180000),
            'currency'=>$currency,'mode'=>'quote','type'=>'ngo','package'=>'ngo','badge'=>'Specialist review',
        ],
    ];
}
function iworks_launch_website_packages(?string $country=null): array
{
    $currency=iworks_launch_currency_for_country($country??iworks_launch_display_country());
    $usd=$currency==='USD';
    return [
        'starter'=>[
            'name'=>'Starter website','pages'=>'Up to 5 pages',
            'description'=>'Mobile-responsive business presentation, enquiry form, basic SEO, SSL configuration.',
            'amount'=>iworks_launch_amount($usd?'launch_web_starter_usd':'launch_web_starter_ngn',$usd?480:250000),
            'currency'=>$currency,
        ],
        'business'=>[
            'name'=>'Business website','pages'=>'Up to 10 pages',
            'description'=>'Editable CMS, service pages, contact forms, analytics-ready setup and launch assistance.',
            'amount'=>iworks_launch_amount($usd?'launch_web_business_usd':'launch_web_business_ngn',$usd?950:500000),
            'currency'=>$currency,
        ],
        'store'=>[
            'name'=>'Online store','pages'=>'Up to 30 products',
            'description'=>'Product catalogue, shopping cart, configured supported payment gateway and handover.',
            'amount'=>iworks_launch_amount($usd?'launch_web_store_usd':'launch_web_store_ngn',$usd?1900:900000),
            'currency'=>$currency,
        ],
        'custom'=>[
            'name'=>'Custom platform / large project','pages'=>'Scoped individually',
            'description'=>'Marketplace, membership, dashboards, complex workflows or bespoke integrations.',
            'amount'=>0.0,'currency'=>$currency,
        ],
    ];
}

/** Optional explicit launch SKU rate; absent rates are quote-only outside Nigeria. */
function iworks_launch_service_rate(array $service, string $country): ?array
{
    $slug=strtolower(trim((string)($service['slug']??'')));
    $currency=iworks_launch_currency_for_country($country);
    $regional=[
      'graphics-design'=>[95000,195],
      'seo-smm-digital-marketing'=>[180000,370],
      'domain-web-hosting'=>[55000,115],
      'email-list-smtp'=>[95000,195],
      'pro-training'=>[90000,190],
    ];
    if ($slug==='website-design-development') {
        $package=iworks_launch_website_packages($country)['starter'];
        return ['amount'=>$package['amount'],'currency'=>$currency,'mode'=>'quote'];
    }
    if ($slug==='incorporated-trustees') {
        $item=iworks_launch_registration_options($country)['ngo'];
        return ['amount'=>$item['price'],'currency'=>$currency,'mode'=>'quote'];
    }
    if (in_array($slug,['vehicles-assets-tracking','social-enterprise-ltd-by-guarantee','form-your-u-s-llc-in-minutes'],true)) {
        return ['amount'=>0.0,'currency'=>$currency,'mode'=>'quote'];
    }
    if (isset($regional[$slug])) {
        [$ngn,$usd]=$regional[$slug];
        $amount=iworks_launch_amount('launch_'.$slug.'_'.strtolower($currency),(float)($currency==='NGN'?$ngn:$usd));
        return ['amount'=>$amount,'currency'=>$currency,'mode'=>'fixed'];
    }
    if ($currency==='USD' && !in_array($slug,['company-registration','cac-business-name','trademark'],true)) {
        // International price not negotiated for this SKU: never create an
        // FX-converted Nigerian-price invoice and mislabel it international.
        return ['amount'=>0.0,'currency'=>'USD','mode'=>'quote'];
    }
    return null;
}


/** After an invoice is committed, queue the customer's email and alert the
 * processing team. Agent alerts contain no ID documents/client contact data. */
function iworks_launch_notify_new_order(int $jobId): void
{
    if ($jobId<=0) return;
    $job=get_job($jobId);
    if (!is_array($job)) return;
    $invoice=get_invoice_by_job_id($jobId);
    $code=(string)($job['job_code']??('#'.$jobId));
    $service=(string)($job['title']??'Service');
    $amount=(float)($invoice['amount']??$job['amount']??0);
    $currency=(string)($invoice['currency']??$job['currency']??'NGN');
    $reference=$invoice ? (string)($invoice['invoice_number']??'') : '';
    $link=$invoice ? 'invoice.php?id='.(int)$invoice['id'] : url('job',['id'=>$jobId]);
    $details="Order reference: ".$code."\n"
        ."Service: ".$service."\n"
        ."Invoice: ".$reference."\n"
        ."Amount due: ".money($amount,$currency)."\n"
        ."Status: Awaiting payment. Processing starts after confirmation."."\n"
        ."You can review the invoice securely from your iWORKS account.";
    // The outbox may be pending when delivery has not yet been configured.
    create_notification((int)$job['client_id'],
        'Order received: '.$code,
        'Your '.$service.' order was created. Invoice '.$reference.' is awaiting payment.',
        $link,
        ['subject'=>'iWORKS order confirmation — '.$code,'plain'=>$details]
    );
    notify_roles(['admin','finance'],
        'New order awaiting payment: '.$code,
        'A new '.$service.' order ('.$code.') has been placed. Review the invoice and allocate an agent.',
        url('job',['id'=>$jobId])
    );
    notify_roles(['agent'],
        'New customer order: '.$code,
        'A new order has been received. Administrators will allocate the work when payment is verified.',
        url('dashboard')
    );
}
