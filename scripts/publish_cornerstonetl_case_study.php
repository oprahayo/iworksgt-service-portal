<?php
declare(strict_types=1);

/**
 * One-client editorial publisher, explicitly invoked from cPanel CLI.
 * Usage:
 *   php publish_cornerstonetl_case_study.php --check PORTAL_ROOT
 *   php publish_cornerstonetl_case_study.php --publish PORTAL_ROOT --confirm-public [--author-id=123]
 *
 * This script is deliberately NOT a website route, migration, or automatic deployment.
 * No app data changes occur unless --publish and --confirm-public are both supplied.
 * Refuses to overwrite or duplicate any existing Cornerstone case study.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function iworks_cornerstone_publication(): array
{
    $story = <<<'HTML'
<h2>Designing a Website for Compassionate Senior-Care Support</h2>
<p>For families who cannot always be physically present, finding trustworthy assistance for older relatives is an important decision. Cornerstonetl, LLC works to support seniors with daily tasks and practical care needs, helping families learn about available assistance.</p>
<p>iWORKS GLOBAL designed a dedicated website to help Cornerstonetl communicate its services and professional identity online.</p>
<h2>The Challenge</h2>
<p>Senior-care organisations need to explain their services clearly, respectfully and in terms families can understand. Cornerstonetl needed an online presence that described its approach to everyday senior assistance and the practical help it offers.</p>
<p>Website delivery also requires the technical foundations behind the visible design: domain services, hosting, SSL and maintenance.</p>
<h2>What iWORKS GLOBAL Delivered</h2>
<h3>Website Design and Development</h3>
<p>We designed a dedicated website for Cornerstonetl, LLC, presenting its compassionate approach to senior assistance and daily-living support.</p>
<h3>Domain, Hosting and SSL</h3>
<p>Our documented service relationship also includes domain, hosting and SSL services to support the website's digital infrastructure.</p>
<h3>Website Maintenance</h3>
<p>iWORKS' records show an annual professional website-maintenance renewal in 2025, supporting the client beyond the initial design engagement.</p>
<h2>The Outcome</h2>
<p>Cornerstonetl gained a dedicated web presence to explain its senior-care offering and present its approach to supporting families and older adults. The digital project was supported by documented website design, domain, hosting, SSL and maintenance services.</p>
<h2>Our Approach</h2>
<p>iWORKS GLOBAL combines purposeful website design with the technical services that support a client's online presence.</p>
<p><strong>Designed with care. Supported by dependable digital infrastructure.</strong></p>
HTML;
    return [
        'title' => 'Cornerstonetl, LLC: A Purpose-Built Website for Senior-Care Support',
        'slug' => 'cornerstonetl-senior-care-website-design-hosting',
        'client_name' => 'Cornerstonetl, LLC',
        'category' => 'Senior Care / Website Design & Hosting',
        'summary' => "iWORKS GLOBAL designed Cornerstonetl, LLC's website to present its senior-care and everyday-living support services. The engagement also included domain, hosting, SSL and annual website maintenance services.",
        'story_html' => $story,
        'outcome' => "A purpose-built senior-care website supported by design, hosting, SSL and maintenance services.",
        'status' => 'published',
    ];
}

function iworks_cornerstone_options(array $args): array
{
    $mode = (string)($args[1] ?? '');
    $portal = (string)($args[2] ?? '');
    $extra = array_slice($args, 3);
    $confirm = in_array('--confirm-public', $extra, true);
    $authorId = 0;
    foreach ($extra as $arg) {
        if (str_starts_with($arg, '--author-id=')) {
            $n = substr($arg, strlen('--author-id='));
            if (!ctype_digit($n) || (int)$n < 1) {
                throw new InvalidArgumentException('Invalid author ID.');
            }
            $authorId = (int)$n;
        } elseif ($arg !== '--confirm-public') {
            throw new InvalidArgumentException('Unexpected argument.');
        }
    }
    if (!in_array($mode, ['--check', '--publish'], true) || !$portal) {
        throw new InvalidArgumentException('Usage: php publish_cornerstonetl_case_study.php --check|--publish PORTAL_ROOT [--confirm-public] [--author-id=ID]');
    }
    return [$mode, $portal, $confirm, $authorId];
}

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    try {
        [$mode, $root, $confirmed, $authorId] = iworks_cornerstone_options($argv);
        if ($mode === '--publish' && !$confirmed) {
            fwrite(STDERR, "BLOCK: --confirm-public required for live publication.\n");
            exit(2);
        }
        $portal = realpath($root);
        if (!$portal || !is_dir($portal)
            || !is_file($portal.'/app/bootstrap.php')
            || !is_file($portal.'/app/config.php')
            || !is_file($portal.'/app/editorial.php')) {
            fwrite(STDERR, "BLOCK: valid configured portal root required.\n");
            exit(2);
        }
        require_once $portal.'/app/bootstrap.php';
        if (!iworks_editorial_ready('iworks_case_studies')) {
            fwrite(STDERR, "BLOCK: editorial case-study table unavailable; no changes.\n");
            exit(3);
        }
        $payload = iworks_cornerstone_publication();
        $existing = db_all(
            "SELECT id,slug,status FROM iworks_case_studies
             WHERE slug LIKE :slug OR LOWER(client_name) LIKE :client
             ORDER BY id ASC LIMIT 12",
            ['slug'=>'%cornerstone%', 'client'=>'%cornerstone%']
        );
        if ($existing) {
            foreach ($existing as $entry) {
                echo 'EXISTS: case #'.(int)$entry['id']
                    .' slug='.(string)$entry['slug']
                    .' status='.(string)$entry['status']."\n";
            }
            fwrite(STDERR, "BLOCK: Cornerstone case study already exists. Review/edit it in the Editorial admin interface; duplicates and overwrites are not allowed.\n");
            exit(3);
        }
        $authors = db_all(
            "SELECT id,role FROM users
             WHERE role IN ('admin','author') AND status='active'
             ORDER BY id ASC LIMIT 20"
        );
        $allowedIds = array_map('intval', array_column($authors, 'id'));
        if ($authorId === 0 && count($allowedIds) === 1) $authorId = $allowedIds[0];
        if ($mode === '--check') {
            echo "PASS: editorial table exists; no matching Cornerstone publication.\n";
            echo 'Eligible active editorial author IDs: '.(implode(', ', $allowedIds) ?: '(none)')."\n";
            echo "CHECK ONLY: no database records changed.\n";
            echo "Planned public URL: https://iworksgt.com/case-studies/".$payload['slug']."\n";
            exit($allowedIds ? 0 : 3);
        }
        if ($authorId <= 0 || !in_array($authorId, $allowedIds, true)) {
            fwrite(STDERR, "BLOCK: choose an eligible active editorial user with --author-id=ID from --check output.\n");
            exit(3);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $id = iworks_editorial_save_case($payload, [], $authorId);
            $published = db_one(
                "SELECT id,status FROM iworks_case_studies WHERE id=:id AND slug=:slug",
                ['id'=>$id, 'slug'=>$payload['slug']]
            );
            if (!$published || $published['status'] !== 'published') {
                throw new RuntimeException('Editorial publish verification failed.');
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
        echo "PUBLISHED: Cornerstonetl, LLC; case #".$id."\n";
        echo "PUBLIC URL: https://iworksgt.com/case-studies/".$payload['slug']."\n";
        echo "NEXT: HTTP-check the public URL and editorial listing; do not use unapproved client photos, logos or testimonials.\n";
    } catch (Throwable $error) {
        fwrite(STDERR, "BLOCK: publication failed; inspect configuration and editorial records privately.\n");
        exit(1);
    }
}
