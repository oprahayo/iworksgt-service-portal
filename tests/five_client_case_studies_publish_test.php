<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/launch_case_study_catalog.php';
require dirname(__DIR__).'/scripts/publish_all_launch_case_studies.php';
require dirname(__DIR__).'/app/editorial.php';

$ok=static function(bool $v,string $message):void {
    if (!$v) throw new RuntimeException($message);
};
$catalog=iworks_launch_case_studies();
$ok(count($catalog)===5,'All five client projects must be included.');
$slugs=array_column($catalog,'slug');
$ok(count(array_unique($slugs))===5,'Slugs must be unique.');
$expected=['Amandla Institute','Triply Travels','Restoration LLC','Karis Health Services','Cornerstonetl'];
foreach ($expected as $name) {
    $ok((bool)array_filter($catalog,static fn(array $c):bool=>str_contains($c['client_name'],$name)),
        'Missing requested project: '.$name);
}
foreach ($catalog as $c) {
    $ok(strlen($c['summary'])<=1200 && strlen($c['outcome'])<=240,'CMS field too long: '.$c['slug']);
    $ok(trim(strip_tags($c['story_html']))!=='' && substr_count($c['story_html'],'<h2>')>=3,
        'Insufficient narrative: '.$c['slug']);
    $ok(str_starts_with($c['website'],'https://'),'HTTPS client URL required.');
    $ok(preg_match('/^[a-z0-9-]+$/D',$c['slug'])===1,'Unsafe slug.');
}
$findSlug=static fn(string $slug):?array => null;
$findClient=static fn(string $name):array => [];
$actions=iworks_launch_case_actions($catalog,$findSlug,$findClient);
$ok(count($actions)===5 && count(array_filter($actions,static fn($a)=>$a['action']==='insert'))===5,
    'First-run publisher should insert all projects.');
$existing=$catalog[0];
$actions=iworks_launch_case_actions($catalog,
    static fn(string $slug):?array => $slug===$existing['slug']
        ? ['id'=>10,'slug'=>$slug,'client_name'=>$existing['client_name'],'status'=>'published'] : null,
    static fn(string $name):array => $name===$existing['client_name']
        ? [['id'=>10,'slug'=>$existing['slug'],'client_name'=>$name,'status'=>'published']] : []);
$ok($actions[0]['action']==='skip-published' && $actions[1]['action']==='insert',
    'Existing published stories must remain untouched.');
$threw=false;
try {
    iworks_launch_case_actions($catalog,$findSlug,
        static fn(string $name):array => $name=== 'Restoration LLC'
            ? [['id'=>3,'slug'=>'restoration-another-story','client_name'=>$name,'status'=>'draft']] : []);
} catch (RuntimeException $e) { $threw=true; }
$ok($threw,'Conflicting client records must block publication.');
$threw=false;
try { iworks_launch_publication_mode(['cmd','--publish','/tmp/portal']); }
catch (InvalidArgumentException $e) { $threw=true; }
$ok($threw,'Live writes require a verified backup flag.');
[$mode,$root,$backup,$author]=iworks_launch_publication_mode(['cmd','--publish','/tmp/portal','--backup-confirmed','--author-id=2']);
$ok($mode==='--publish' && $backup && $author===2,'Explicit live publishing flags must parse.');
$source=(string)file_get_contents(dirname(__DIR__).'/scripts/publish_all_launch_case_studies.php');
$ok(str_contains($source,"PHP_SAPI !== 'cli'") && str_contains($source,'$pdo->beginTransaction()')
    && str_contains($source,'$pdo->rollBack()') && str_contains($source,'--backup-confirmed'),
    'Safe CLI + transactional safeguards required.');
$editorial=(string)file_get_contents(dirname(__DIR__).'/app/editorial.php');
$ok(str_contains($editorial,'iworks_editorial_case_cover($row)')
    && str_contains($editorial,'assets/branding/case-studies/')
    && str_contains($editorial,'preg_match('),
    'Screenshot fallback must be safely scoped to local real screenshots.');
echo "five_client_case_studies_publish_test passed\n";
