<?php
declare(strict_types=1);
/**
 * Static visual preview only: never connects to an actual database.
 * Render: php tests/fixtures/render_portfolio_preview.php [work|detail]
 */
$base = dirname(__DIR__, 2);
require $base.'/app/editorial.php';
require_once $base.'/app/launch_case_study_catalog.php';
function schema_table_exists(string $name): bool { return $name==='iworks_case_studies'; }
function db_all(string $sql, array $params=[]): array { return $GLOBALS['workMockRows']; }
function e(string $value):string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function public_absolute_url(string $relative):string { return '/'.ltrim($relative,'/'); }
function public_url(string $page,array $args=[]):string {
    if ($page==='home') return '/';
    if ($page==='case_study') return '/case-studies/'.rawurlencode((string)($args['slug']??''));
    if ($page==='case-studies') return '/portfolio-preview.html';
    return '/'.str_replace('_','-',$page);
}
$GLOBALS['workMockRows']=array_map(static function(array $c):array {
    return [
        'slug'=>$c['slug'],'title'=>$c['title'],'client_name'=>$c['client_name'],
        'category'=>$c['category'],'summary'=>$c['summary'],
        'outcome'=>$c['outcome'],'story_html'=>$c['story_html'],
        'image_id'=>0,'logo_id'=>0,'status'=>'published',
    ];
},iworks_launch_case_studies());
$view=(string)($argv[1]??'work');
$caseRecord=$GLOBALS['workMockRows'][1];
$caseRecord['image_url']=iworks_editorial_case_cover($caseRecord);
$caseRecord['logo_url']='';
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>iWORKS Portfolio Visual Test</title>
<link rel="stylesheet" href="/assets/css/editorial-brand.css">
<link rel="stylesheet" href="/assets/css/public-editorial-refinement.css">
<link rel="stylesheet" href="/assets/css/portfolio-work.css">
<style>
*{box-sizing:border-box}html{font-family:Inter,system-ui,sans-serif}
body{margin:0;background:#fffaf6}
.container.public-container{width:min(1150px,calc(100% - 36px));max-width:1150px;margin:0 auto}
.preview-site-header{height:70px;background:#fff;border-bottom:1px solid #eee;display:flex;align-items:center;padding:0 max(18px,calc((100vw - 1150px)/2));font-weight:800;color:#222;letter-spacing:-.02em}
.preview-site-header span{color:#df6423}
.preview-site-footer{background:#fff;padding:38px 20px;color:#666;text-align:center;font-size:12px}
</style>
</head>
<body class="page-public">
<header class="preview-site-header"><span>◢</span> iWORKS GLOBAL <small style="margin-left:auto;color:#777;font-size:12px">Services &nbsp;&nbsp; About &nbsp;&nbsp; Case Studies</small></header>
<?php
if($view==='detail') {
    require $base.'/views/public-case-study-detail.php';
} else {
    require $base.'/views/public-case-studies-refined.php';
}
?>
<footer class="preview-site-footer">iWORKS GLOBAL — portfolio visual regression preview</footer>
<script src="/assets/js/portfolio-work.js"></script>
</body></html>
