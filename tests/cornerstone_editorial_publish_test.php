<?php
declare(strict_types=1);

require dirname(__DIR__) . '/scripts/publish_cornerstonetl_case_study.php';

$verify = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};

$p = iworks_cornerstone_publication();
$verify($p['status'] === 'published', 'Publisher must explicitly request published status.');
$verify($p['slug'] === 'cornerstonetl-senior-care-website-design-hosting', 'Fixed human-readable URL required.');
$verify($p['client_name'] === 'Cornerstonetl, LLC', 'Verified client name required.');
$verify(str_contains($p['summary'], 'website'), 'Case study lacks website delivery.');
$verify(str_contains($p['summary'], 'hosting'), 'Case study lacks documented hosting.');
$verify(str_contains($p['story_html'], '2025'), 'Maintenance renewal date omitted.');
$verify(!preg_match('/HIPAA compliant|guaranteed uptime|hundreds of patients|ongoing since 2026|live right now/i', $p['story_html']), 'Unsupported marketing claim.');
[$mode,$portal,$confirm,$author] = iworks_cornerstone_options(['publisher.php','--check','/tmp/test']);
$verify($mode === '--check' && $portal === '/tmp/test' && !$confirm && $author === 0, 'Read-only check parsing broken.');
[$mode,$portal,$confirm,$author] = iworks_cornerstone_options(['publisher.php','--publish','/tmp/test','--confirm-public','--author-id=24']);
$verify($mode === '--publish' && $confirm && $author === 24, 'Explicit publish confirmation parsing broken.');
$source = (string)file_get_contents(dirname(__DIR__) . '/scripts/publish_cornerstonetl_case_study.php');
$verify(str_contains($source, "PHP_SAPI !== 'cli'"), 'HTTP access must be blocked.');
$verify(str_contains($source, 'if ($mode === \'--publish\' && !$confirmed)'), 'Publication must require explicit confirmation.');
$verify(str_contains($source, 'iworks_editorial_ready('), 'Publication requires existing schema.');
$verify(str_contains($source, 'WHERE slug LIKE :slug OR LOWER(client_name) LIKE :client'), 'Duplicate protection required.');
$verify(str_contains($source, '$pdo->beginTransaction()') && str_contains($source, '$pdo->rollBack()'), 'Publication must be transactional.');
echo "cornerstone_editorial_publish_test passed\n";
