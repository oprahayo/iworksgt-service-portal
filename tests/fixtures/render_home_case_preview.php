<?php
declare(strict_types=1);
/**
 * Renders the actual homepage case-study PHP fragment with five approved
 * real screenshot fixtures. No database access or site state mutation.
 */
$root = dirname(__DIR__,2);
require_once $root.'/app/editorial.php';
require_once $root.'/app/launch_case_study_catalog.php';
function e(string $value):string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function public_url(string $page,array $args=[]):string {
  if ($page==='case_study') return '/case-studies/'.rawurlencode((string)($args['slug']??''));
  if ($page==='case-studies') return '/case-studies';
  return $page==='home' ? '/' : '/'.str_replace('_','-',$page);
}
$homeCases=array_map(static function(array $c):array {
    return [
      'client_name'=>$c['client_name'],
      'category'=>$c['category'],
      'slug'=>$c['slug'],
      'image_url'=>'/assets/branding/case-studies/'.$c['slug'].'.webp',
      'logo_url'=>'',
    ];
},iworks_launch_case_studies());
$source=(string)file_get_contents($root.'/views/public-home-clean.php');
$begin=strpos($source,'  <section class="iw-home-case-stories" aria-labelledby="iw-home-cases-title">');
$end=strpos($source,'  <section class="iw-clean-section iw-clean-support"',$begin);
if($begin===false||$end===false)throw new RuntimeException('Homepage carousel section missing');
$fragment=substr($source,$begin,$end-$begin);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>iWORKS Case Studies — Interactive Homepage QA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portfolio-work.css">
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;font-family:Inter,system-ui,sans-serif;background:#fff}
.container.public-container{max-width:1180px;width:calc(100% - 36px);margin:0 auto}
.preview-nav{display:flex;justify-content:space-between;align-items:center;min-height:78px;padding:0 max(18px,calc((100vw - 1180px)/2));font-size:14px;color:#27313b}
.preview-brand{font-weight:800;font-size:19px}.preview-brand span{color:#e06825}
.preview-nav a{color:#3f4852;text-decoration:none;margin-left:20px}
.preview-step{background:#fff6ee;padding:35px 0 20px;color:#27313b}
.preview-step h2{font-size:30px;font-weight:760;text-align:center;margin:10px 0 0}
.preview-footer{height:140px;background:#fff}
</style>
</head>
<body class="page-public iw-clean-home">
<header class="preview-nav"><span class="preview-brand"><span>i</span>WORKS GLOBAL</span>
  <nav><a href="/services">Services</a><a href="/case-studies">Case Studies</a><a href="/about">About</a></nav></header>
<section class="preview-step"><div class="container public-container"><h2>How It Works</h2></div></section>
<?php eval('?>'.$fragment); ?>
<div class="preview-footer"></div>
<script src="/assets/js/portfolio-work.js"></script>
</body>
</html>
