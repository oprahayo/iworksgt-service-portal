<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
require dirname(__DIR__) . '/app/bootstrap.php';
try {
    $column=db_one("SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role' LIMIT 1");
    if (!$column) throw new RuntimeException('User role column not found.');
    if (!str_contains((string)$column['type'], "'author'")) {
        db_execute("ALTER TABLE users MODIFY role ENUM('admin','agent','client','finance','author') NOT NULL DEFAULT 'client'");
    }
    db_execute("CREATE TABLE IF NOT EXISTS iworks_case_studies (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(190) NOT NULL UNIQUE,
        title VARCHAR(240) NOT NULL,
        client_name VARCHAR(190) NULL,
        category VARCHAR(100) NULL,
        summary TEXT NULL,
        story_html LONGTEXT NOT NULL,
        outcome VARCHAR(240) NULL,
        image_id INT UNSIGNED NULL,
        logo_id INT UNSIGNED NULL,
        status ENUM('draft','published') NOT NULL DEFAULT 'draft',
        author_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
        INDEX idx_case_public (status,updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db_execute("CREATE TABLE IF NOT EXISTS iworks_testimonials (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(190) NOT NULL,
        position VARCHAR(140) NULL,
        company VARCHAR(190) NULL,
        quote_text TEXT NOT NULL,
        company_logo_id INT UNSIGNED NULL,
        portrait_id INT UNSIGNED NULL,
        status ENUM('draft','published') NOT NULL DEFAULT 'draft',
        consent_confirmed TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
        INDEX idx_testimonial_public(status,sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db_execute("CREATE TABLE IF NOT EXISTS iworks_blog_comments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        display_name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        comment_text TEXT NOT NULL,
        visitor_hash CHAR(64) NOT NULL,
        status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        INDEX idx_comment_public(post_id,status,created_at),
        INDEX idx_comment_limit(visitor_hash,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db_execute("CREATE TABLE IF NOT EXISTS iworks_blog_likes (
        post_id INT UNSIGNED NOT NULL,
        visitor_hash CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY(post_id,visitor_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Editorial roles, case studies, testimonials and article engagement schema ready.\n";
} catch(Throwable $e) {
    fwrite(STDERR, "Editorial migration failed: ".$e->getMessage()."\n");
    exit(1);
}
