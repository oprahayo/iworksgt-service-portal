<?php
declare(strict_types=1);
// DB-free render and SEO contracts for new legal pages and international guides.
$root=dirname(__DIR__);
function e(string $s):string {return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function public_url(string $page='home',array $params=[]):string {
    if($page==='home')return 'https://iworksgt.com/';
    if($page==='service')return 'https://iworksgt.com/services/'.rawurlencode((string)($params['slug']??''));
    return 'https://iworksgt.com/'.$page.($params!==[]?'?'.http_build_query($params):'');
}
function international_check(bool $value,string $error):void {if(!$value)throw new RuntimeException($error);}
require $root.'/app/public_seo.php';
require $root.'/app/public_marketing.php';
function render_official_page(string $slug,array $services=[]):string {
    global $root;
    $publicPageSlug=$slug;
    $publicServices=$services;
    ob_start();
    require $root.($slug==='privacy'||$slug==='terms'||$slug==='cookies'
      ?'/views/public-legal-pages.php':'/views/public-international-formation.php');
    return (string) ob_get_clean();
}
foreach(['privacy'=>'Privacy Policy','terms'=>'Terms &amp; Conditions','cookies'=>'Cookie Policy'] as $slug=>$label) {
    $html=render_official_page($slug);
    international_check(str_contains($html,'class="iw-legal-page"'),$slug.' dedicated flat public view required.');
    international_check(str_contains($html,'<article class="iw-legal-copy">'),$slug.' semantic content missing.');
    international_check(str_contains($html,'Last updated: 14 November 2026'),$slug.' policy revision date missing.');
    international_check(substr_count($html,'<h1>')===1,$slug.' must have exactly one H1.');
    international_check(substr_count($html,'<h2>')>=8,$slug.' must include substantive chapters.');
    international_check(!str_contains($html,'section-shell')&&!str_contains($html,'card shadow'),
      $slug.' must not use legacy boxed CMS section shell.');
    international_check(str_contains($html,'support@iworksgt.com'),$slug.' rights and contact address missing.');
    international_check(str_contains($html,'/privacy')&&str_contains($html,'/terms')&&str_contains($html,'/cookies'),
      $slug.' related policy navigation missing.');
}
$privacy=render_official_page('privacy');
foreach(['International customers and transfers','Your choices and rights','Retention and deletion',
'Cookies, local storage and external resources','Information supplied about other people'] as $phrase) {
    international_check(str_contains($privacy,$phrase),'Privacy rights and transfers incomplete: '.$phrase);
}
$terms=render_official_page('terms');
foreach(['Refunds and cancellation','Cross-border orders','Fees, currencies and payment',
'Digital projects, revisions and intellectual property','Government filings and third parties'] as $phrase) {
    international_check(str_contains($terms,$phrase),'Terms incomplete: '.$phrase);
}
$cookies=render_official_page('cookies');
international_check(str_contains($cookies,'pay_currency')&&str_contains($cookies,'consent')&&str_contains($cookies,'local storage'),
  'Cookie policy must cover actual storage and optional tracking consent.');
$nigeria=render_official_page('register-company-nigeria-from-abroad');
$usa=render_official_page('us-llc-formation-from-nigeria');
foreach(['United Kingdom','Canada','United States','Germany','Australia'] as $country) {
    international_check(str_contains($nigeria,$country),'Overseas intent missing country: '.$country);
}
international_check(str_contains($nigeria,'https://cac.gov.ng/services/company-registration') &&
    str_contains($nigeria,'https://icrp.cac.gov.ng/'),
    'Nigerian guide must reference official CAC sources.');
international_check(str_contains($usa,'https://www.irs.gov/instructions/i5472') &&
    str_contains($usa,'registered agent') && str_contains($usa,'tax'),
    'US formation guide must include official IRS and tax obligations.');
international_check(str_contains($usa,'https://iworksgt.com/contact'),
    'Do not promote unavailable US product as published.');
$publishedUs=render_official_page('us-llc-formation-from-nigeria',
    [['slug'=>'form-your-u-s-llc-in-minutes']]);
international_check(str_contains($publishedUs,'/services/form-your-u-s-llc-in-minutes'),
    'Published US LLC product should connect to the current catalogue.');
foreach(['register-company-nigeria-from-abroad','us-llc-formation-from-nigeria'] as $slug) {
    $meta=iworks_search_page_meta($slug);
    international_check(str_contains($meta['title'],'|')&&strlen($meta['description'])>90,
       'Indexable international guide metadata missing.');
    international_check(iworks_seo_canonical($slug,['utm'=>'paid'])===public_url($slug),
       'International landing canonical must ignore ad tracking parameters.');
    $schema=json_decode(iworks_seo_jsonld($slug,[],public_url($slug),$meta['description']),true);
    international_check(is_array($schema)&&($schema['@type']??'')==='WebPage'&&
       ($schema['publisher']['name']??'')==='iWORKS GLOBAL LTD',
       'New landing page must have truthful WebPage structured data.');
    international_check(!isset($schema['aggregateRating'])&&!isset($schema['offers']),
       'Do not invent ratings or selling prices in international structured data.');
    $breadcrumb=json_decode(iworks_seo_breadcrumb_jsonld($slug,[],public_url($slug)),true);
    international_check(($breadcrumb['itemListElement'][1]['name']??'')==='Services',
       'Breadcrumb should point to Services for international guides.');
}
$router=(string)file_get_contents($root.'/index.php');
$footer=(string)file_get_contents($root.'/views/partials/public-footer.php');
$header=(string)file_get_contents($root.'/views/partials/public-header.php');
$home=(string)file_get_contents($root.'/views/public-home-clean.php');
$seo=(string)file_get_contents($root.'/app/public_seo.php');
$css=(string)file_get_contents($root.'/assets/css/public-legal-international.css');
$installer=(string)file_get_contents($root.'/scripts/deploy_international_legal_seo.sh');
international_check(str_contains($router,"'/views/public-legal-pages.php'") &&
    str_contains($router,"'/views/public-international-formation.php'"),
    'Real public router must serve legal and overseas guide templates.');
international_check(str_contains($seo,'$pages[] = [\'slug\' => $sitePage]'),
    'All first-party legal and guide routes must be included in sitemap.');
international_check(str_contains($footer,"'Cookie Policy'") &&
    str_contains($footer,"public_url('cookies')"),
    'Cookie Policy must be discoverable from footer.');
international_check(str_contains($home,"public_url('register-company-nigeria-from-abroad')") &&
    str_contains($home,"public_url('us-llc-formation-from-nigeria')"),
    'Homepage needs meaningful internal links to both overseas guides.');
international_check(str_contains($header,'public-legal-international.css'),
    'Shared header must load legal and guide styling.');
international_check(str_contains($css,'background:none!important;border:0!important;box-shadow:none!important'),
    'Policies must be directly on the website background without extra cards.');
international_check(str_contains($installer,'check_remediation_release.php') &&
    str_contains($installer,'rollback()') &&
    !str_contains($installer,'mysqldump'),
    'Release must be fail-closed, reversible and database-neutral.');
echo "international_legal_seo_contract_test passed\n";
