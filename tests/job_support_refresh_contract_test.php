<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$src=static function(string $path) use ($root):string {
    $file=$root.'/'.$path;
    if (!is_file($file)) throw new RuntimeException('Missing '.$path);
    return (string)file_get_contents($file);
};
$check=static function(bool $condition,string $message):void {
    if (!$condition) throw new RuntimeException($message);
};
$job=$src('views/job.php');
$jobCss=$src('assets/css/job-workspace.css');
$dashboard=$src('views/dashboard.php');
$adminHeader=$src('views/partials/header.php');
$chatCss=$src('assets/css/public-assistance.css');
$chatJs=$src('assets/js/public-assistance.js');
$publicHeader=$src('views/partials/public-header.php');
$publicFooter=$src('views/partials/public-footer.php');
$router=$src('index.php');

$check(!str_contains($dashboard,'role_has_admin_or_finance_access('),'Dashboard must not call an undefined access helper.');
$check(str_contains($dashboard,"in_array((string) (\$user['role'] ?? ''), ['admin', 'finance', 'super_admin'], true)"),'Admin/finance dashboard must use defined role check.');
$check(str_contains($job,'iw-job-workspace') && str_contains($job,'iw-job-titlebar'),'Job page must have a distinct task-oriented shell.');
$check(str_contains($job,'iw-job-metrics') && str_contains($job,'iw-job-content'),'Job metrics and task layout must be structured.');
foreach(['iwJobOverview','iwJobDiscussion','iwJobFiles','iwJobActions'] as $anchor) {
    $check(str_contains($job,$anchor),'Missing accessible job section '.$anchor);
}
foreach(['assign_admin_owner','assign_agent','update_status','update_finance','add_comment','upload_file'] as $action) {
    $check(str_contains($job,$action),'Original job function action missing: '.$action);
}
$check(str_contains($adminHeader,'job-workspace.css'),'New job styles must be included on portal job pages.');
$check(str_contains($jobCss,'@media(max-width:600px)') && str_contains($jobCss,'grid-template-columns:minmax(0,1fr)'),'Mobile job screen must collapse to a single column.');
$check(str_contains($chatCss,'var(--brand-primary,#f16a21)'),'Support widget must derive from the iWorks brand primary colour.');
$check(!str_contains($chatCss,'#1157e4'),'Vendor-blue hex value must not be reintroduced.');
$check(str_contains($chatJs,'visibilitychange') && str_contains($chatJs,'offerNudgeWhenReady'),'Delayed consultation invitation must recover after backgrounding.');
$check(str_contains($chatJs,"redirect: 'error'") && str_contains($chatJs,'Refresh this page'),'AJAX failures must be actionable.');
$check(str_contains($chatJs,"root.dataset.assistReady = '1'"),'Widget must prevent duplicate listener binding.');
$check(str_contains($publicHeader,"file_get_contents(\$iwAssistCssPath)"),'Public support must not depend solely on static CSS mirror.');
$check(str_contains($publicFooter,"file_get_contents(\$iwAssistJsPath)"),'Public support must not depend solely on static JS mirror.');
$check(str_contains($publicFooter,'assets/js/public-assistance.js'),'Fallback script URL must remain available.');
$check(str_contains($router,'require_csrf();') && str_contains($router,"'website_lead'"),'Chat must retain CSRF validation and lead persistence.');
echo "job_support_refresh_contract_test passed\n";
