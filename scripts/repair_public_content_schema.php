<?php
declare(strict_types=1);

/**
 * Safe content-schema audit and non-destructive repair for existing cPanel
 * installations. Never routes through setup.php or writes user records.
 *
 * php scripts/repair_public_content_schema.php --check
 * php scripts/repair_public_content_schema.php --apply
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true) || count($argv) > 2) {
    fwrite(STDERR, "Usage: php scripts/repair_public_content_schema.php [--check|--apply]\n");
    exit(2);
}
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (!is_configured()) {
    fwrite(STDERR, "Database is not configured. No changes made.\n");
    exit(1);
}
$needed = [
    'users' => ['id','full_name','first_name','last_name','email','role'],
    'services' => ['id','name','slug','is_active','is_public','category'],
    'pages' => ['slug','status','published_layout_json'],
    'media_assets' => ['id','url'],
    'blog_posts' => ['id','author_id','slug','title','tag','excerpt','body_html','featured_image_id','status','published_at','created_at','updated_at'],
    'iworks_case_studies' => ['id','slug','title','status','story_html'],
    'iworks_testimonials' => ['id','customer_name','status','consent_confirmed'],
    'iworks_blog_comments' => ['id','post_id','status'],
    'iworks_blog_likes' => ['post_id','visitor_hash'],
    'website_leads' => ['id','full_name','email','message','page_slug'],
];
function content_schema_status(array $expected): array
{
    $report = [];
    foreach ($expected as $table => $columns) {
        $rows = db_all(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table",
            ['table' => $table]
        );
        $found = array_column($rows, 'COLUMN_NAME');
        $report[$table] = [
            'exists' => $found !== [],
            'missing' => array_values(array_diff($columns, $found)),
        ];
    }
    return $report;
}
function print_content_schema_status(array $report): bool
{
    $ok = true;
    foreach ($report as $table => $state) {
        $missing = $state['missing'];
        if (!$state['exists']) {
            echo "[MISSING TABLE] {$table}\n";
            $ok = false;
        } elseif ($missing) {
            echo "[MISSING COLUMNS] {$table}: " . implode(', ', $missing) . "\n";
            $ok = false;
        } else {
            echo "[OK] {$table}\n";
        }
    }
    return $ok;
}

try {
    if ($mode === '--apply') {
        echo "Applying non-destructive editorial repairs...\n";
        db()->exec(
            "CREATE TABLE IF NOT EXISTS blog_posts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                author_id INT UNSIGNED NOT NULL,
                slug VARCHAR(190) NOT NULL UNIQUE,
                title VARCHAR(240) NOT NULL,
                tag VARCHAR(100) NULL,
                excerpt TEXT NULL,
                body_html LONGTEXT NOT NULL,
                featured_image_id INT UNSIGNED NULL,
                status ENUM('draft','published') NOT NULL DEFAULT 'draft',
                published_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_blog_public (status,published_at),
                KEY idx_blog_author (author_id,updated_at),
                CONSTRAINT fk_blog_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        // Reuse the established idempotent editorial migration (adds author
        // role and missing story, testimonial, comment and reaction tables).
        require __DIR__ . '/migrate_editorial_content.php';
    }
    $report = content_schema_status($needed);
    $allReady = print_content_schema_status($report);
    if (!$allReady) {
        fwrite(STDERR, "\nSome base tables/columns need a separate review. No existing records were deleted or overwritten.\n");
        exit(1);
    }
    echo "\nContent schema is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Content schema check/repair failed: " . $e->getMessage() . "\n");
    exit(1);
}
