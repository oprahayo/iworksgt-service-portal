<?php
declare(strict_types=1);
// The latest operator instruction removes the repeated branded page flashes.
$root=dirname(__DIR__);
function check_transition(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
function e(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function portal_absolute_url(string $path):string{return 'https://portal.iworksgt.com/'.ltrim($path,'/');}
foreach(['admin','agent','finance','author','client','student',null] as $role){
    $user=$role===null?null:['id'=>99,'role'=>$role];
    ob_start();
    require $root.'/views/partials/portal-transition-head.php';
    require $root.'/views/partials/portal-transition-overlay.php';
    $html=ob_get_clean();
    check_transition($role===null?trim($html)==='':substr_count($html,'id="iwAdminTransition"')===1&&str_contains($html,'iw-admin-transition-enter')&&!str_contains($html,'iw-admin-transition-leave'),'One arrival overlay for '.($role??'guest'));
}
$header=file_get_contents($root.'/views/partials/header.php');
$academy=file_get_contents($root.'/academy.php');
$style=strpos($header,'/assets/css/academy.css?v=15');
check_transition($style!==false&&$style<strpos($header,'</head>'),'Academy CSS loads before first paint');
check_transition(!str_contains($academy,'<link rel="stylesheet" href="/assets/css/academy.css'), 'No late Academy CSS injection');
$view=file_get_contents($root.'/views/academy-admin.php');
check_transition(!str_contains($view,'class="ac-stats"'),'Remove repeated dashboard cards');
check_transition(strpos($view,'Approve completed training')<strpos($view,'Course and certificate history'),'Primary approval visible before history');
echo "Portal single arrival animation and simplified Academy layout contract passed.\n";
