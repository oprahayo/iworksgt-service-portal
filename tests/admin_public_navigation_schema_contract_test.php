<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$src=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$ok=static function(bool $b,string $why):void{if(!$b)throw new RuntimeException($why);};
$index=$src('index.php');
$publicHeader=$src('views/partials/public-header.php');
$publicFooter=$src('views/partials/public-footer.php');
$adminHeader=$src('views/partials/header.php');
$assist=$src('assets/js/public-assistance.js');
$database=$src('database.sql');
$repair=$src('scripts/repair_public_content_schema.php');
$services=$src('views/services.php');

$ok(str_contains($index,'$portalManagementPage') && str_contains($index,'$isPublicPageRoute'),'Public and admin routes must be distinct.');
$ok(str_contains($index,"array_key_exists('page', \$_GET)"),'Query-based portal routing must explicitly select admin route.');
$ok(str_contains($index,"case 'blog':") && str_contains($index,"require_roles(['admin', 'agent', 'author']);"),'Insights must accept admins, agents and authors.');
$ok(str_contains($index,"case 'services':") && str_contains($index,"require_roles(['admin', 'finance']);"),'Services management must remain restricted.');
$ok(str_contains($index,"case 'system_health':") && str_contains($index,"'SELECT COLUMN_NAME FROM information_schema.COLUMNS"),'Admin schema diagnostics must be read-only.');
$ok(str_contains($adminHeader,'system_health') && str_contains($adminHeader,'iw-ops-site-link'),'Admin layout needs maintenance and public links.');
$ok(str_contains($adminHeader,'admin-workspace-refresh.css'),'Admin refresh styles must load.');
$ok(str_contains($publicHeader,'iw-mobile-menu-group') && str_contains($publicHeader,'iw-mobile-menu-book'),'Mobile grouped navigation and booking button required.');
$ok(str_contains($assist,"booking && !root.contains(booking)"),'External mobile booking control must open consultation drawer.');
$ok(!str_contains($publicHeader,'data-iw-theme-toggle'),'Dark mode control must be removed.');
$ok(str_contains($publicHeader,"dataset.iwTheme='light'"),'Old dark theme setting must be ignored.');
$ok(!str_contains($publicFooter,"var modes=['system','light','dark']"),'Dark mode switching script must be removed.');
$ok(str_contains($publicFooter,'iw-sitewide-cta') && str_contains($publicFooter,'Ready to launch and grow your business?'),'Public sitewide CTA required.');
$ok(str_contains($publicFooter,"public_url('services')"),'Primary CTA needs a working service destination.');
$ok(str_contains($services,"public_url('service', ['slug'=>(string)\$service['slug']])"),'Services manager needs public preview per published item.');
foreach(['blog_posts','iworks_case_studies','iworks_testimonials','iworks_blog_comments','iworks_blog_likes'] as $table) {
    $ok(str_contains($database,'CREATE TABLE IF NOT EXISTS '.$table),'Fresh database is missing '.$table);
}
$ok(str_contains($repair,"'--check'") && str_contains($repair,"'--apply'"),'Safe maintenance check/apply options required.');
$ok(str_contains($src('views/blog.php'),'blogSchemaReady'),'Insights editor must explain missing schema.');
$ok(str_contains($src('views/system-health.php'),'Database readiness'),'Admin diagnostics view required.');
echo "admin_public_navigation_schema_contract_test passed\n";
