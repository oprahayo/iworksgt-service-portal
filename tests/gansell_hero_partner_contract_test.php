<?php
declare(strict_types=1);
/** Owner-supplied hosting logos and real Gansell case study. */
$root=dirname(__DIR__);
$must=static function(bool $value,string $message):void {
    if(!$value) throw new RuntimeException($message);
};
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$orange=$read('assets/branding/orangeserver-logo.svg');
$rila=$read('assets/branding/rilahost-logo.svg');
$must(str_contains($orange,'viewBox="0 0 816 157"')
    && str_contains($orange,'#00a651') && str_contains($orange,'#f7941e')
    && str_contains($orange,'fill-rule="evenodd"'),
    'The supplied OrangeServer green/orange artwork must be reproduced.');
$must(str_contains($rila,'viewBox="0 0 357 55"')
    && str_contains($rila,'#ffffff') && str_contains($rila,'fill-rule="evenodd"'),
    'The supplied RilaHost logo must retain its original transparent white design.');
$home=$read('views/public-home-clean.php');
$css=$read('assets/css/public-clean.css');
foreach(['orangeserver-logo.svg','rilahost-logo.svg','iw-home-brand-ribbon',
         'iw-home-brand-mark','brand_asset',"'type'=>'provider'",
         'Client projects &amp; service platforms','iworks_editorial_cases(true, 6)'] as $needle){
    $must(str_contains($home,$needle),'Homepage hero case/brand integration missing: '.$needle);
}
$must(str_contains($css,'.iw-home-brand-mark.iw-home-brand-rilahost img')
    && str_contains($css,'background:#26343f')
    && str_contains($css,'.iw-home-brand-mark.iw-home-brand-orangeserver img')
    && !str_contains($css,'filter:none!important')
    && str_contains($css,'filter:grayscale(1)!important')
    && str_contains($css,'filter:grayscale(0)!important'),
    'Hosting logos must render legibly in the existing hero carousel.');
$manifest=json_decode($read('assets/branding/case-studies/gansell-website-project-capture.json'),true,512,JSON_THROW_ON_ERROR);
$must($manifest['http_status']===200
    && parse_url((string)$manifest['captured_url'],PHP_URL_HOST)==='gansell.com'
    && str_contains(strtolower((string)$manifest['page_title']),'gansell'),
    'Gansell screenshot must have real verified source and HTTP 200.');
$must(is_file($root.'/assets/branding/case-studies/gansell-website-project.webp')
    && filesize($root.'/assets/branding/case-studies/gansell-website-project.webp')>12000,
    'A real fully loaded Gansell screenshot is required, not a loading splash.');
function schema_table_exists(string $table):bool{return $table==='iworks_case_studies';}
function db_all(string $sql,array $params=[]):array{return $GLOBALS['mockEditorialRows']??[];}
function db_one(string $sql,array $params=[]):?array{return null;}
function public_absolute_url(string $relative):string{return '/'.ltrim($relative,'/');}
require_once $root.'/app/editorial.php';
require_once $root.'/app/launch_case_study_catalog.php';
require_once $root.'/app/gansell_case_study.php';
$GLOBALS['mockEditorialRows']=array_map(static fn(array $c):array=>[
    'id'=>100, 'slug'=>$c['slug'],'client_name'=>$c['client_name'],'image_id'=>0,'logo_id'=>0,
    'category'=>$c['category'],'summary'=>$c['summary'],'title'=>$c['title'],
    'outcome'=>$c['outcome'],'story_html'=>$c['story_html'],
],iworks_launch_case_studies());
$cases=iworks_editorial_cases(true,6);
$must(count($cases)===6,'Gansell must appear alongside all five existing published cases.');
$must(count(array_filter($cases,static fn(array $r):bool => $r['slug']==='gansell-website-project'))===1,
    'The Gansell entry must appear exactly once.');
$gansell=iworks_editorial_case_slug('gansell-website-project');
$must((string)$gansell['client_name']==='Gansell'
    && (string)$gansell['website']==='https://gansell.com/'
    && str_contains((string)$gansell['image_url'],'gansell-website-project.webp')
    && iworks_editorial_case_site('gansell-website-project')==='https://gansell.com/',
    'Gansell must have a public detail page, approved website and genuine image.');
$must(iworks_editorial_case_tags($gansell)===['web'],
    'Gansell belongs under digital/website services, not a fabricated industry.');
$GLOBALS['mockEditorialRows']=array_merge([[
    'id'=>99,'slug'=>'gansell-website-project','client_name'=>'Gansell',
    'image_id'=>0,'logo_id'=>0,'title'=>'CMS case','category'=>'Marketplace website',
    'summary'=>'CMS copy','outcome'=>'CMS outcome','story_html'=>'<p>CMS copy</p>'
]],$GLOBALS['mockEditorialRows']);
$cases=iworks_editorial_cases(true,6);
$must(count(array_filter($cases,static fn(array $r):bool => $r['slug']==='gansell-website-project'))===1,
    'A future CMS-published Gansell record must supersede the source fallback, not duplicate it.');
echo "gansell_hero_partner_contract_test passed\n";
