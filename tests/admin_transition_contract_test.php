<?php
declare(strict_types=1);
// The latest operator instruction removes the repeated branded page flashes.
$root=dirname(__DIR__);
function check_transition(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
foreach(['admin','agent','finance','author','client','student',null] as $role){
    $user=$role===null?null:['id'=>99,'role'=>$role];
    ob_start();
    require $root.'/views/partials/portal-transition-head.php';
    require $root.'/views/partials/portal-transition-overlay.php';
    $html=ob_get_clean();
    check_transition(trim($html)==='','No enter/leave splash markup for '.($role??'guest'));
}
$header=file_get_contents($root.'/views/partials/header.php');
$academy=file_get_contents($root.'/academy.php');
$style=strpos($header,'/assets/css/academy.css?v=3');
check_transition($style!==false&&$style<strpos($header,'</head>'),'Academy CSS loads before first paint');
check_transition(!str_contains($academy,'<link rel="stylesheet" href="/assets/css/academy.css'), 'No late Academy CSS injection');
$view=file_get_contents($root.'/views/academy-admin.php');
check_transition(!str_contains($view,'class="ac-stats"'),'Remove repeated dashboard cards');
check_transition(strpos($view,'Approve completed training')<strpos($view,'Course and certificate history'),'Primary approval visible before history');
echo "Portal native navigation and simplified Academy layout contract passed.\n";
