<?php
declare(strict_types=1);
/**
 * Controlled iWORKS launch price activation. CLI only, narrowly scoped.
 * --check: read-only display of exact source price changes.
 * --apply: update future catalogue/service fees after creating private
 *         row-level rollback snapshot. Never touches jobs or invoices.
 *
 * This is NOT a substitute for a verified full private DB backup.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode=(string)($argv[1]??'--check');
if (!in_array($mode,['--check','--apply'],true) || count($argv)!==2) {
    fwrite(STDERR,"Usage: php scripts/activate_launch_prices.php --check|--apply\n");
    exit(2);
}
require_once dirname(__DIR__).'/app/bootstrap.php';
if (!is_configured()) { fwrite(STDERR,"Database is unconfigured.\n"); exit(3); }
$servicePrices=[
    'company-registration'=>75000.00,
    'incorporated-trustees'=>180000.00,
    'website-design-development'=>350000.00,
    'seo-smm-digital-marketing'=>150000.00,
    'domain-web-hosting'=>55000.00,
];
$settingPrices=[
    'cac_business_base_ngn'=>'35000.00',
    'cac_company_growth_ngn'=>'150000.00',
    'diaspora_cac_service_multiplier'=>'1.60',
];
try {
    $rows=db_all("SELECT id,slug,name,price,currency,is_active,is_public FROM services
      WHERE slug IN ('company-registration','incorporated-trustees','website-design-development',
        'seo-smm-digital-marketing','domain-web-hosting') ORDER BY slug");
    $bySlug=[];
    foreach($rows as $row)$bySlug[(string)$row['slug']]=$row;
    if (!isset($bySlug['company-registration'])) {
        throw new RuntimeException('Canonical CAC product is missing; nothing updated.');
    }
    $oldSettings=db_all(
        "SELECT setting_key,setting_value FROM app_settings WHERE setting_key IN
        ('cac_business_base_ngn','cac_company_growth_ngn','diaspora_cac_service_multiplier')"
    );
    $settingsByKey=[];
    foreach($oldSettings as $row)$settingsByKey[(string)$row['setting_key']]=$row['setting_value'];
    $snapshot=[
        'captured_at'=>gmdate('c'),
        'services'=>array_values($rows),
        'settings'=>$settingsByKey,
        'target_services'=>$servicePrices,
        'target_settings'=>$settingPrices,
    ];
    foreach($servicePrices as $slug=>$price){
        if (!isset($bySlug[$slug])) {
            echo "NOT FOUND $slug — no new product will be invented.\n";
            continue;
        }
        $current=$bySlug[$slug];
        echo sprintf("%s: %s %.2f -> NGN %.2f\n",
            $slug,(string)$current['currency'],(float)$current['price'],$price);
    }
    foreach($settingPrices as $key=>$value){
        echo $key.': '.(string)($settingsByKey[$key]??'(not set)').' -> '.$value."\n";
    }
    echo 'Historical invoices, customer orders, all unrelated services and uploads are excluded.'."\n";
    if($mode==='--check'){echo "READ-ONLY CHECK complete.\n";exit(0);}

    $home=getenv('HOME')?:'';
    if($home==='' || !is_dir($home))throw new RuntimeException('HOME private backup directory is unavailable.');
    $parent=$home.'/iworks-deployment-backups';
    if(!is_dir($parent) && !mkdir($parent,0700,true) && !is_dir($parent)){
        throw new RuntimeException('Cannot create private backup root.');
    }
    chmod($parent,0700);
    $backup=$parent.'/launch-prices-'.gmdate('Ymd\THis\Z').'-'.getmypid();
    if(!mkdir($backup,0700))throw new RuntimeException('Cannot make private price backup.');
    $snapshotFile=$backup.'/original-catalogue-prices.json';
    $json=json_encode($snapshot,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if(file_put_contents($snapshotFile,$json,LOCK_EX)===false)throw new RuntimeException('Snapshot write failed.');
    chmod($snapshotFile,0600);
    if(!is_readable($snapshotFile) || !is_file($snapshotFile) || filesize($snapshotFile)<100){
        throw new RuntimeException('Private rollback file failed integrity checks.');
    }
    $pdo=db();
    if($pdo->inTransaction())throw new RuntimeException('Unexpected active transaction.');
    $pdo->beginTransaction();
    try{
        foreach($servicePrices as $slug=>$price){
            if(!isset($bySlug[$slug]))continue;
            db_execute("UPDATE services SET price=:price,currency='NGN' WHERE id=:id AND slug=:slug",
                ['price'=>$price,'id'=>(int)$bySlug[$slug]['id'],'slug'=>$slug]);
        }
        foreach($settingPrices as $key=>$price){
            db_execute("INSERT INTO app_settings (setting_key,setting_value,updated_at)
              VALUES (:k,:v,:at)
              ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),
                updated_at=VALUES(updated_at)",
                ['k'=>$key,'v'=>$price,'at'=>date('Y-m-d H:i:s')]);
        }
        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        throw $e;
    }
    echo "ACTIVATED: future service fees updated. Private price rollback snapshot: $snapshotFile\n";
    echo "The existing customer invoices were NOT recalculated. Verify all new quotes before opening checkout.\n";
}catch(Throwable $e){
    fwrite(STDERR,"Launch price activation blocked: ".$e->getMessage()."\n");
    exit(1);
}
