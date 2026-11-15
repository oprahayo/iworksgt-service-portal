<?php
declare(strict_types=1);
/* Source-level visual QA fixture. Never deployed to production. */
function e(string $s): string {return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');}
function public_url(string $page = 'home'): string {
  return 'https://iworksgt.com/'.($page === 'home' ? '' : $page);
}
ob_start();
require dirname(__DIR__).'/views/public-solutions.php';
$body=(string)ob_get_clean();
?>
<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>Gansell / iWORKS — visual QA</title>
 <link rel="stylesheet" href="/assets/css/solutions.css">
 <style>
 *{box-sizing:border-box}
 body.page-public{margin:0;color:#20242a;background:#fff;font-family:Inter,Arial,Helvetica,sans-serif}
 body.page-public .public-container{max-width:1140px;width:100%;margin:0 auto;padding:0 21px}
 .qa-header{height:70px;background:white;border-bottom:1px solid #eee;display:flex;align-items:center}
 .qa-header-inner{display:flex;align-items:center;justify-content:space-between;gap:25px}
 .qa-logo{font-size:21px;font-weight:800;letter-spacing:-.05em;white-space:nowrap}
 .qa-logo b{color:#f56613}
 .qa-nav{display:flex;align-items:center;gap:22px;color:#343c45;font-size:13px;white-space:nowrap}
 @media(max-width:650px){.qa-header{height:65px}.qa-nav span:nth-child(n+2){display:none}}
 </style>
</head>
<body class="page-public">
 <header class="qa-header">
  <div class="public-container qa-header-inner">
   <span class="qa-logo"><b>iW</b> iWORKS GLOBAL</span>
   <nav class="qa-nav" aria-label="Visual preview"><span>Gansell</span><span>Services</span><span>About</span></nav>
  </div>
 </header>
 <?= $body ?>
</body></html>
