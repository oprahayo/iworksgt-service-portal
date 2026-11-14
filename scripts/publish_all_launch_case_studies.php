<?php
declare(strict_types=1);

/**
 * Publish exactly the five owner-requested case studies through the existing CMS.
 * Never run from a web request. No migrations, no arbitrary SQL file imports.
 *
 * php scripts/publish_all_launch_case_studies.php --check PORTAL_ROOT
 * php scripts/publish_all_launch_case_studies.php --publish PORTAL_ROOT --backup-confirmed
 *
 * Requires a verified private database backup before --publish.
 * Idempotent: existing published records are left untouched; matching drafts
 * are completed; conflicts (same client with different slug) fail closed.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function iworks_launch_publication_mode(array $argv): array
{
    $mode = (string)($argv[1] ?? '');
    $root = (string)($argv[2] ?? '');
    $extras = array_slice($argv, 3);
    $backup = in_array('--backup-confirmed', $extras, true);
    $authorId = 0;
    foreach ($extras as $argument) {
        if ($argument === '--backup-confirmed') continue;
        if (preg_match('/^--author-id=([1-9][0-9]*)$/D', $argument, $m)) {
            $authorId = (int)$m[1];
            continue;
        }
        throw new InvalidArgumentException('Unexpected argument supplied.');
    }
    if (!in_array($mode, ['--check', '--publish'], true) || $root === '') {
        throw new InvalidArgumentException('Usage: --check|--publish PORTAL_ROOT [--backup-confirmed] [--author-id=ID]');
    }
    if ($mode === '--publish' && !$backup) {
        throw new InvalidArgumentException('Refusing publication without a verified private database backup (--backup-confirmed).');
    }
    return [$mode,$root,$backup,$authorId];
}

function iworks_launch_case_actions(array $catalog, callable $findBySlug, callable $findByClient): array
{
    $actions = [];
    foreach ($catalog as $case) {
        $slug = (string)$case['slug'];
        $name = (string)$case['client_name'];
        $exact = $findBySlug($slug);
        $names = $findByClient($name);
        foreach ($names as $row) {
            if ((string)$row['slug'] !== $slug) {
                throw new RuntimeException('Client already has a different editorial case-study slug: '.$name);
            }
        }
        if ($exact && strcasecmp((string)$exact['client_name'], $name) !== 0) {
            throw new RuntimeException('Existing case-study slug is assigned to a different client: '.$slug);
        }
        $status = (string)($exact['status'] ?? '');
        if ($exact && !in_array($status, ['draft','published'], true)) {
            throw new RuntimeException('Unexpected case-study status: '.$slug);
        }
        $actions[] = [
            'case'=>$case,
            'action'=>$exact ? ($status === 'published' ? 'skip-published' : 'publish-draft') : 'insert',
            'id'=>(int)($exact['id'] ?? 0),
        ];
    }
    return $actions;
}

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    try {
        [$mode,$root,$backup,$authorId] = iworks_launch_publication_mode($argv);
        $portal = realpath($root);
        if (!$portal || !is_dir($portal)
            || !is_file($portal.'/app/bootstrap.php')
            || !is_file($portal.'/app/config.php')
            || !is_file($portal.'/app/launch_case_study_catalog.php')) {
            throw new RuntimeException('A configured iWORKS portal root with the launch catalog is required.');
        }
        require_once $portal.'/app/bootstrap.php';
        require_once $portal.'/app/launch_case_study_catalog.php';
        if (!iworks_editorial_ready('iworks_case_studies')) {
            throw new RuntimeException('Editorial case-study table is unavailable; no schema changes will be attempted.');
        }
        $catalog = iworks_launch_case_studies();
        if (count($catalog) !== 5) throw new RuntimeException('Expected exactly five launch case studies.');
        $pdo = db();
        $actions = iworks_launch_case_actions(
            $catalog,
            static fn(string $slug): ?array => db_one(
                'SELECT id,client_name,slug,status FROM iworks_case_studies WHERE slug=:slug LIMIT 1',
                ['slug'=>$slug]
            ),
            static fn(string $name): array => db_all(
                'SELECT id,client_name,slug,status FROM iworks_case_studies WHERE LOWER(client_name)=LOWER(:client) LIMIT 20',
                ['client'=>$name]
            )
        );
        $users = db_all(
            "SELECT id,role FROM users WHERE role IN ('admin','author') AND status='active' ORDER BY id ASC LIMIT 100"
        );
        $ids = array_map('intval',array_column($users,'id'));
        if ($authorId === 0 && count($ids) === 1) $authorId = $ids[0];
        foreach ($actions as $step) {
            echo strtoupper($step['action']).': '.$step['case']['client_name']
                .' /case-studies/'.$step['case']['slug']."\n";
        }
        if ($mode === '--check') {
            echo 'Eligible active editorial author IDs: '.($ids ? implode(', ',$ids) : '(none)')."\n";
            echo "CHECK ONLY: no website or database content changed.\n";
            exit($ids ? 0 : 3);
        }
        $requiresWrite = array_filter($actions, static fn(array $step): bool => $step['action'] !== 'skip-published');
        if ($requiresWrite && !in_array($authorId,$ids,true)) {
            throw new RuntimeException('Choose an eligible author with --author-id=ID from the --check results.');
        }
        $pdo->beginTransaction();
        try {
            // Re-validate under the same transaction. Existing entries are never deleted.
            $fresh = iworks_launch_case_actions(
                $catalog,
                static fn(string $slug): ?array => db_one(
                    'SELECT id,client_name,slug,status FROM iworks_case_studies WHERE slug=:slug LIMIT 1',
                    ['slug'=>$slug]
                ),
                static fn(string $name): array => db_all(
                    'SELECT id,client_name,slug,status FROM iworks_case_studies WHERE LOWER(client_name)=LOWER(:client) LIMIT 20',
                    ['client'=>$name]
                )
            );
            foreach ($fresh as $step) {
                $data = $step['case'];
                if ($step['action'] === 'skip-published') {
                    echo 'SKIP ALREADY PUBLISHED: '.$data['slug']."\n";
                    continue;
                }
                $data['status'] = 'published';
                if ($step['action'] === 'publish-draft') $data['id'] = $step['id'];
                $id = iworks_editorial_save_case($data, [], $authorId);
                $row = db_one(
                    "SELECT id,status FROM iworks_case_studies WHERE id=:id AND slug=:slug",
                    ['id'=>$id,'slug'=>$data['slug']]
                );
                if (!$row || $row['status'] !== 'published') {
                    throw new RuntimeException('Could not verify published case '.$data['slug']);
                }
                echo 'PUBLISHED: #'.$id.' '.$data['slug']."\n";
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
        echo "SUCCESS: five case studies are published or already published. No other project or invoice records were changed.\n";
        echo "NEXT: Verify https://iworksgt.com/case-studies and each public detail page.\n";
    } catch (Throwable $error) {
        fwrite(STDERR,'BLOCK: publication did not complete: '.$error->getMessage()."\n");
        exit(1);
    }
}
