<?php
declare(strict_types=1);
function editorial_check(bool $ok,string $error):void {if(!$ok)throw new RuntimeException($error);}
$root=dirname(__DIR__);
function editorial_src(string $file):string {
    global $root;
    $path=$root.'/'.$file;
    if(!is_file($path))throw new RuntimeException('Required file missing: '.$file);
    return file_get_contents($path);
}
require $root.'/app/editorial.php';
editorial_check(function_exists('iworks_editorial_save_case'),'Case study persistence required.');
editorial_check(function_exists('iworks_editorial_save_review'),'Testimonial persistence required.');
editorial_check(function_exists('iworks_blog_submit_comment'),'Moderated comments required.');
editorial_check(function_exists('iworks_blog_toggle_like'),'One-like-per-session toggle required.');
editorial_check(function_exists('iworks_blog_page'),'Real article pagination required.');
$schema=editorial_src('scripts/migrate_editorial_content.php');
foreach(['iworks_case_studies','iworks_testimonials','iworks_blog_comments','iworks_blog_likes',"'author'"] as $token)
    editorial_check(str_contains($schema,$token),'Schema missing '.$token);
$domain=editorial_src('app/domain.php');
editorial_check(str_contains($domain,"['client', 'agent', 'author']"),'Admin must create author users explicitly.');
$users=editorial_src('views/users.php');
editorial_check(str_contains($users,'value="author"'),'Admin user form must expose Author.');
$index=editorial_src('index.php');
editorial_check(str_contains($index,"case 'editorial_showcase':"),'Editorial admin route missing.');
editorial_check(str_contains($index,"require_roles(['admin']);"),'Admin-only editorial route.');
editorial_check(str_contains($index,"require_roles(['admin', 'author']);"),'Author blog route missing.');
editorial_check(str_contains($index,"user_role() === 'author'"),'Author role must have explicit allowlist.');
editorial_check(str_contains($index,"case 'public_auth_status':"),'Safe cross-subdomain session check missing.');
editorial_check(str_contains($index,'iworks_blog_submit_comment'), 'Public post must process comments.');
editorial_check(str_contains($index,"case 'case_study'")===false || str_contains($index,'iworks_editorial_case_slug'), 'Public case detail route missing.');
$admin=editorial_src('views/editorial-showcase.php');
editorial_check(str_contains($admin,'consent_confirmed'),'Testimonial publication must confirm permission.');
editorial_check(str_contains($admin,'approve_comment'),'Comment moderation must be accessible to admin.');
$blog=editorial_src('views/blog.php');
editorial_check(str_contains($blog,'iwBlogEditor') && str_contains($blog,'data-iw-blog-link'),'Writing toolbar and link formatting required.');
editorial_check(str_contains($blog,'sandbox=""'),'Preview must be sandboxed.');
$public=editorial_src('views/public-blog-detail.php');
foreach(['blog_like','blog_comment','data-iw-share','iw-article-comments','Related insights','public_url(\'home\')'] as $marker)
    editorial_check(str_contains($public,$marker),'Public article feature missing: '.$marker);
$header=editorial_src('views/partials/public-header.php');
$footer=editorial_src('views/partials/public-footer.php');
editorial_check(str_contains($header,'iw-public-auth-status')&&str_contains($header,'data-iw-theme-toggle'),'Public sign-in state and theme missing.');
editorial_check(str_contains($footer,'public_auth_status') && str_contains($footer,'data-iw-to-top'),'Auth status and back-to-top missing.');
editorial_check(!str_contains(editorial_src('views/frontend-editor-blocks.php'),'$packageOptions = list_travel_package_options(true);'),'Unrelated travel call must be removed.');
$cases=editorial_src('views/public-case-studies-refined.php');
editorial_check(str_contains($cases,'iworks_editorial_cases(true'),'Public case studies only show published records.');
$home=editorial_src('views/public-home-clean.php');
editorial_check(str_contains($home,'iworks_editorial_reviews(true')&&str_contains($home,'iw-home-benefits'),'Home has authentic feedback and illustrated benefits.');
$css=editorial_src('assets/css/editorial-brand.css');
editorial_check(str_contains($css,'data-iw-theme="dark"')&&str_contains($css,'prefers-reduced-motion'),'Dark colors and accessible motion setting required.');
echo "editorial_launch_contract_test passed\n";
