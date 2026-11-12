<?php

declare(strict_types=1);

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

    return trim($value, '-');
}

function uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function schema_table_exists(string $table): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table_name',
        ['table_name' => $table]
    );

    return (int) ($row['c'] ?? 0) > 0;
}

function schema_column_exists(string $table, string $column): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name',
        ['table_name' => $table, 'column_name' => $column]
    );

    return (int) ($row['c'] ?? 0) > 0;
}

function schema_column_type(string $table, string $column): ?string
{
    $row = db_one(
        'SELECT COLUMN_TYPE AS column_type
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name
         LIMIT 1',
        ['table_name' => $table, 'column_name' => $column]
    );

    if (!$row || !isset($row['column_type'])) {
        return null;
    }

    return (string) $row['column_type'];
}

function ensure_crypto_schema_upgrades(): void
{
    static $done = false;
    if ($done || !is_configured()) {
        return;
    }

    $done = true;

    if (schema_table_exists('jobs')) {
        $statusType = schema_column_type('jobs', 'status');
        if ($statusType !== null && stripos($statusType, "'revision_requested'") === false) {
            db_execute("ALTER TABLE jobs MODIFY COLUMN status ENUM('pending', 'in_progress', 'waiting_client', 'revision_requested', 'delivered', 'closed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
        if (!schema_column_exists('jobs', 'managed_by_admin_id')) {
            db_execute("ALTER TABLE jobs ADD COLUMN managed_by_admin_id INT UNSIGNED NULL AFTER agent_id");
        }
        if (!schema_column_exists('jobs', 'pay_currency')) {
            db_execute("ALTER TABLE jobs ADD COLUMN pay_currency VARCHAR(10) NULL AFTER currency");
        }
        if (!schema_column_exists('jobs', 'pay_method')) {
            db_execute("ALTER TABLE jobs ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency");
        }
        if (!schema_column_exists('jobs', 'fx_rate_used')) {
            db_execute("ALTER TABLE jobs ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method");
        }
        if (!schema_column_exists('jobs', 'crypto_asset')) {
            db_execute("ALTER TABLE jobs ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used");
        }
        if (!schema_column_exists('jobs', 'crypto_rate_used')) {
            db_execute("ALTER TABLE jobs ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset");
        }
        if (!schema_column_exists('jobs', 'crypto_amount_due')) {
            db_execute("ALTER TABLE jobs ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used");
        }
        if (!schema_column_exists('jobs', 'crypto_network')) {
            db_execute("ALTER TABLE jobs ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due");
        }
        if (!schema_column_exists('jobs', 'rate_locked_at')) {
            db_execute("ALTER TABLE jobs ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network");
        }
        if (!schema_column_exists('jobs', 'delivered_by')) {
            db_execute("ALTER TABLE jobs ADD COLUMN delivered_by INT UNSIGNED NULL AFTER delivered_at");
        }
        if (!schema_column_exists('jobs', 'accepted_by')) {
            db_execute("ALTER TABLE jobs ADD COLUMN accepted_by INT UNSIGNED NULL AFTER delivered_by");
        }
        if (!schema_column_exists('jobs', 'accepted_at')) {
            db_execute("ALTER TABLE jobs ADD COLUMN accepted_at DATETIME NULL AFTER accepted_by");
        }
    }

    if (schema_table_exists('invoices')) {
        if (!schema_column_exists('invoices', 'pay_currency')) {
            db_execute("ALTER TABLE invoices ADD COLUMN pay_currency VARCHAR(10) NULL AFTER currency");
        }
        if (!schema_column_exists('invoices', 'pay_method')) {
            db_execute("ALTER TABLE invoices ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency");
        }
        if (!schema_column_exists('invoices', 'fx_rate_used')) {
            db_execute("ALTER TABLE invoices ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method");
        }
        if (!schema_column_exists('invoices', 'crypto_asset')) {
            db_execute("ALTER TABLE invoices ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used");
        }
        if (!schema_column_exists('invoices', 'crypto_rate_used')) {
            db_execute("ALTER TABLE invoices ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset");
        }
        if (!schema_column_exists('invoices', 'crypto_amount_due')) {
            db_execute("ALTER TABLE invoices ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used");
        }
        if (!schema_column_exists('invoices', 'crypto_network')) {
            db_execute("ALTER TABLE invoices ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due");
        }
        if (!schema_column_exists('invoices', 'rate_locked_at')) {
            db_execute("ALTER TABLE invoices ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network");
        }
    }

    if (schema_table_exists('job_payments')) {
        if (!schema_column_exists('job_payments', 'pay_currency')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN pay_currency VARCHAR(10) NULL AFTER amount");
        }
        if (!schema_column_exists('job_payments', 'pay_method')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency");
        }
        if (!schema_column_exists('job_payments', 'fx_rate_used')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method");
        }
        if (!schema_column_exists('job_payments', 'crypto_asset')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used");
        }
        if (!schema_column_exists('job_payments', 'crypto_rate_used')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset");
        }
        if (!schema_column_exists('job_payments', 'crypto_amount_due')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used");
        }
        if (!schema_column_exists('job_payments', 'crypto_network')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due");
        }
        if (!schema_column_exists('job_payments', 'rate_locked_at')) {
            db_execute("ALTER TABLE job_payments ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network");
        }
    }

    db_execute(
        "CREATE TABLE IF NOT EXISTS crypto_rates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            base_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            asset_symbol VARCHAR(12) NOT NULL,
            rate_usd_per_asset DECIMAL(24,10) NOT NULL,
            source VARCHAR(40) NOT NULL DEFAULT 'fallback',
            last_updated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_crypto_rate_pair (base_currency, asset_symbol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS crypto_rate_snapshots (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            snapshot_date DATE NOT NULL,
            base_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            asset_symbol VARCHAR(12) NOT NULL,
            rate_usd_per_asset DECIMAL(24,10) NOT NULL,
            source VARCHAR(40) NOT NULL DEFAULT 'snapshot',
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_crypto_snapshot_day_asset (snapshot_date, base_currency, asset_symbol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS pages (
            id CHAR(36) PRIMARY KEY,
            slug VARCHAR(120) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
            seo_json LONGTEXT NULL,
            layout_json LONGTEXT NULL,
            published_layout_json LONGTEXT NULL,
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_pages_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_pages_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS page_blocks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id CHAR(36) NOT NULL,
            type VARCHAR(64) NOT NULL,
            content_json LONGTEXT NOT NULL,
            order_index INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_page_blocks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            KEY idx_page_blocks_page_order (page_id, order_index)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS page_revisions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id CHAR(36) NOT NULL,
            layout_json LONGTEXT NOT NULL,
            seo_json LONGTEXT NULL,
            label VARCHAR(190) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_page_revision_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            CONSTRAINT fk_page_revision_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS media_library (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            storage_key VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            mime VARCHAR(120) NOT NULL,
            size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
            width INT UNSIGNED NULL,
            height INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_media_library_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS website_leads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(60) NULL,
            company_name VARCHAR(190) NULL,
            message TEXT NOT NULL,
            page_slug VARCHAR(60) NOT NULL DEFAULT 'contact',
            status ENUM('new', 'reviewed', 'closed') NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_website_leads_status_created (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id INT UNSIGNED NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            title VARCHAR(220) NOT NULL,
            tag VARCHAR(120) NULL,
            excerpt TEXT NULL,
            body_html LONGTEXT NOT NULL,
            featured_image_id INT UNSIGNED NULL,
            status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_blog_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_blog_posts_featured_image FOREIGN KEY (featured_image_id) REFERENCES media_library(id) ON DELETE SET NULL,
            KEY idx_blog_posts_status_published (status, published_at),
            KEY idx_blog_posts_author (author_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Backward compatibility for previous builds.
    db_execute(
        "CREATE TABLE IF NOT EXISTS media_assets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            storage_key VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            mime VARCHAR(120) NOT NULL,
            size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
            width INT UNSIGNED NULL,
            height INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    db_execute(
        "CREATE TABLE IF NOT EXISTS otp_dispatch_queue (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            purpose ENUM('login', 'verify_email') NOT NULL,
            to_email VARCHAR(190) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            otp_code VARCHAR(12) NOT NULL,
            status ENUM('queued', 'processing', 'retrying', 'sent', 'sent_fallback', 'failed', 'superseded') NOT NULL DEFAULT 'queued',
            attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
            max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
            next_attempt_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_otp_queue_status_next (status, next_attempt_at),
            KEY idx_otp_queue_user_purpose (user_id, purpose),
            CONSTRAINT fk_otp_dispatch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if (schema_table_exists('payment_methods')) {
        $crypto = db_one(
            'SELECT id FROM payment_methods WHERE method_code = :method_code LIMIT 1',
            ['method_code' => 'crypto_wallet']
        );
        if (!$crypto) {
            db_execute(
                'INSERT INTO payment_methods (
                    method_code, display_name, details, public_key, secret_key, private_key,
                    callback_url, webhook_url, requires_proof, is_active, created_at
                ) VALUES (
                    :method_code, :display_name, :details, :public_key, :secret_key, :private_key,
                    :callback_url, :webhook_url, :requires_proof, :is_active, :created_at
                )',
                [
                    'method_code' => 'crypto_wallet',
                    'display_name' => 'Crypto Wallet',
                    'details' => 'List accepted wallet addresses by network.',
                    'public_key' => '',
                    'secret_key' => '',
                    'private_key' => '',
                    'callback_url' => '',
                    'webhook_url' => '',
                    'requires_proof' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                ]
            );
        }
    }

    if (schema_table_exists('jobs')) {
        db_execute("UPDATE jobs SET pay_currency = currency WHERE pay_currency IS NULL OR pay_currency = ''");
        db_execute("UPDATE jobs SET pay_method = 'card' WHERE pay_method IS NULL OR pay_method = ''");
    }

    if (schema_table_exists('invoices')) {
        db_execute("UPDATE invoices SET pay_currency = currency WHERE pay_currency IS NULL OR pay_currency = ''");
        db_execute("UPDATE invoices SET pay_method = 'card' WHERE pay_method IS NULL OR pay_method = ''");
    }

    if (schema_table_exists('app_settings') && trim(setting('crypto_rates_refresh_minutes', '')) === '') {
        set_setting('crypto_rates_refresh_minutes', '10');
    }

    if (schema_table_exists('app_settings') && trim(setting('otp_resend_cooldown_seconds', '')) === '') {
        set_setting('otp_resend_cooldown_seconds', '45');
    }
    if (schema_table_exists('app_settings') && trim(setting('otp_queue_retry_seconds', '')) === '') {
        set_setting('otp_queue_retry_seconds', '20');
    }
    if (schema_table_exists('app_settings') && trim(setting('otp_queue_max_attempts', '')) === '') {
        set_setting('otp_queue_max_attempts', '3');
    }
    if (schema_table_exists('app_settings') && setting('mail_default_disabled_v29', '') !== '1') {
        // Keep OTP email disabled by default after upgrades unless admin explicitly re-enables it.
        set_setting('mail_enabled', '0');
        set_setting('mail_default_disabled_v29', '1');
    }

    if (
        schema_table_exists('app_settings')
        && schema_table_exists('jobs')
        && schema_table_exists('invoices')
        && schema_table_exists('job_payments')
        && setting('payment_partial_fix_v2', '') !== '1'
    ) {
        // Legacy correction: keep "partial" only for rows with at least one confirmed payment.
        db_execute(
            "UPDATE jobs j
             SET j.payment_status = 'unpaid'
             WHERE j.payment_status = 'partial'
               AND NOT EXISTS (
                   SELECT 1
                   FROM invoices i
                   JOIN job_payments p
                     ON p.invoice_id = i.id
                    AND p.status = 'confirmed'
                   WHERE (
                           (COALESCE(j.payment_group, '') <> '' AND i.payment_group = j.payment_group)
                        OR (COALESCE(j.payment_group, '') = '' AND i.job_id = j.id)
                   )
               )"
        );
        set_setting('payment_partial_fix_v2', '1');
    }

    if (schema_table_exists('pages')) {
        ensure_default_pages();
        apply_public_page_presets_v29_if_pristine();
        apply_public_page_presets_v32_if_pristine();
        ensure_management_public_page_sync();
    }

    if (schema_table_exists('pages') && schema_table_exists('page_blocks')) {
        sync_page_blocks_from_pages_if_needed();
    }

    if (schema_table_exists('media_library') && schema_table_exists('media_assets')) {
        sync_media_library_from_assets_if_needed();
    }

    if (schema_table_exists('services')) {
        if (!schema_column_exists('services', 'category')) {
            db_execute("ALTER TABLE services ADD COLUMN category VARCHAR(80) NOT NULL DEFAULT 'general' AFTER tax_percent");
        }
        if (!schema_column_exists('services', 'short_description')) {
            db_execute("ALTER TABLE services ADD COLUMN short_description VARCHAR(255) NULL AFTER description");
        }
        if (!schema_column_exists('services', 'is_public')) {
            db_execute("ALTER TABLE services ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
            db_execute("UPDATE services SET is_public = 1 WHERE is_public IS NULL");
        }
        if (!schema_column_exists('services', 'is_featured')) {
            db_execute("ALTER TABLE services ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_public");
        }
        if (!schema_column_exists('services', 'featured_bullets')) {
            db_execute("ALTER TABLE services ADD COLUMN featured_bullets LONGTEXT NULL AFTER short_description");
        }
        if (!schema_column_exists('services', 'featured_image_id')) {
            db_execute("ALTER TABLE services ADD COLUMN featured_image_id INT UNSIGNED NULL AFTER featured_bullets");
        }
        seed_service_marketing_defaults_if_needed();
    }

    if (schema_table_exists('blog_posts')) {
        seed_default_blog_posts_if_needed();
    }
}

function ensure_media_schema_upgrades(): void
{
    static $done = false;
    if ($done || !is_configured()) {
        return;
    }

    $done = true;

    if (schema_table_exists('media_library') && !schema_column_exists('media_library', 'label')) {
        db_execute("ALTER TABLE media_library ADD COLUMN label VARCHAR(255) NULL AFTER storage_key");
    }

    if (schema_table_exists('media_assets') && !schema_column_exists('media_assets', 'label')) {
        db_execute("ALTER TABLE media_assets ADD COLUMN label VARCHAR(255) NULL AFTER storage_key");
    }
}

function ensure_default_pages(): void
{
    foreach (default_pages_manifest() as $slug => $pageMeta) {
        $title = (string) ($pageMeta['title'] ?? ucfirst($slug));
        $layout = normalize_page_layout(is_array($pageMeta['layout'] ?? null) ? $pageMeta['layout'] : []);
        $layoutJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seo = [
            'metaTitle' => $title . ' - ' . brand_name(),
            'metaDescription' => business_support_summary(),
            'ogImage' => '',
        ];
        $existing = db_one('SELECT id FROM pages WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if ($existing) {
            continue;
        }

        db_execute(
            'INSERT INTO pages (id, slug, title, status, seo_json, layout_json, published_layout_json, created_by, updated_by, published_at, created_at, updated_at)
             VALUES (:id, :slug, :title, :status, :seo_json, :layout_json, :published_layout_json, :created_by, :updated_by, :published_at, :created_at, :updated_at)',
            [
                'id' => uuid_v4(),
                'slug' => $slug,
                'title' => $title,
                'status' => 'published',
                'seo_json' => json_encode($seo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'layout_json' => $layoutJson,
                'published_layout_json' => $layoutJson,
                'created_by' => null,
                'updated_by' => null,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

function apply_public_page_presets_v29_if_pristine(): void
{
    if (setting('public_frontend_preset_v29_applied', '') === '1') {
        return;
    }

    foreach (default_pages_manifest() as $slug => $pageMeta) {
        $page = db_one('SELECT id, slug, updated_by FROM pages WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if (!$page) {
            continue;
        }

        if (!empty($page['updated_by'])) {
            continue;
        }

        $revisionRow = db_one('SELECT COUNT(*) AS c FROM page_revisions WHERE page_id = :page_id', ['page_id' => (string) $page['id']]);
        if ((int) ($revisionRow['c'] ?? 0) > 0) {
            continue;
        }

        $title = (string) ($pageMeta['title'] ?? ucfirst((string) $slug));
        $layout = normalize_page_layout(is_array($pageMeta['layout'] ?? null) ? $pageMeta['layout'] : []);
        $layoutJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seo = [
            'metaTitle' => $title . ' - ' . brand_name(),
            'metaDescription' => business_support_summary(),
            'ogImage' => '',
        ];

        db_execute(
            'UPDATE pages
             SET title = :title,
                 status = :status,
                 seo_json = :seo_json,
                 layout_json = :layout_json,
                 published_layout_json = :published_layout_json,
                 published_at = :published_at,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'title' => $title,
                'status' => 'published',
                'seo_json' => json_encode($seo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'layout_json' => $layoutJson,
                'published_layout_json' => $layoutJson,
                'published_at' => now(),
                'updated_at' => now(),
                'id' => (string) $page['id'],
            ]
        );

        save_page_blocks_for_page((string) $page['id'], $layout);
    }

    set_setting('public_frontend_preset_v29_applied', '1');
}

function apply_public_page_presets_v32_if_pristine(): void
{
    if (setting('public_frontend_preset_v32_applied', '') === '1') {
        return;
    }

    foreach (default_pages_manifest() as $slug => $pageMeta) {
        $page = db_one('SELECT id, slug, updated_by FROM pages WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if (!$page) {
            continue;
        }

        if (!empty($page['updated_by'])) {
            continue;
        }

        $revisionRow = db_one('SELECT COUNT(*) AS c FROM page_revisions WHERE page_id = :page_id', ['page_id' => (string) $page['id']]);
        if ((int) ($revisionRow['c'] ?? 0) > 0) {
            continue;
        }

        $title = (string) ($pageMeta['title'] ?? ucfirst((string) $slug));
        $layout = normalize_page_layout(is_array($pageMeta['layout'] ?? null) ? $pageMeta['layout'] : []);
        $layoutJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seo = [
            'metaTitle' => $title . ' - ' . brand_name(),
            'metaDescription' => business_support_summary(),
            'ogImage' => '',
        ];

        db_execute(
            'UPDATE pages
             SET title = :title,
                 status = :status,
                 seo_json = :seo_json,
                 layout_json = :layout_json,
                 published_layout_json = :published_layout_json,
                 published_at = :published_at,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'title' => $title,
                'status' => 'published',
                'seo_json' => json_encode($seo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'layout_json' => $layoutJson,
                'published_layout_json' => $layoutJson,
                'published_at' => now(),
                'updated_at' => now(),
                'id' => (string) $page['id'],
            ]
        );

        save_page_blocks_for_page((string) $page['id'], $layout);
    }

    set_setting('public_frontend_preset_v32_applied', '1');
}

function ensure_management_public_page_sync(): void
{
    $managementPage = db_one('SELECT id, updated_at FROM pages WHERE slug = :slug LIMIT 1', ['slug' => 'management']);
    if (!$managementPage) {
        return;
    }

    $homePage = get_page_admin_by_slug('home');
    if (!$homePage) {
        return;
    }

    $layout = normalize_page_layout(is_array($homePage['layout'] ?? null) ? $homePage['layout'] : []);
    foreach ($layout as $block) {
        if (normalize_page_block_type((string) ($block['type'] ?? '')) === 'management_profiles') {
            return;
        }
    }

    $managementBlock = [
        'type' => 'management_profiles',
        'data' => [
            'kicker' => 'Management',
            'title' => 'Meet the management team',
            'subtitle' => 'Share the leadership structure, responsibilities, and the people shaping standards across the organisation.',
            'displayStyle' => 'compact',
            'ctaLabel' => 'View management',
            'ctaHref' => public_url('management'),
            'items' => default_management_profile_items(),
        ],
    ];

    $insertAt = count($layout);
    foreach ($layout as $index => $block) {
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        if (in_array($type, ['cta_banner', 'footer'], true)) {
            $insertAt = $index;
            break;
        }
    }

    array_splice($layout, $insertAt, 0, [$managementBlock]);
    $layout = normalize_page_layout($layout);
    $layoutJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    db_execute(
        'UPDATE pages
         SET layout_json = :layout_json,
             published_layout_json = :published_layout_json,
             updated_at = :updated_at
         WHERE id = :id',
        [
            'layout_json' => $layoutJson,
            'published_layout_json' => $layoutJson,
            'updated_at' => now(),
            'id' => (string) ($homePage['id'] ?? ''),
        ]
    );

    save_page_blocks_for_page((string) ($homePage['id'] ?? ''), $layout);
    invalidate_page_cache('home');
    invalidate_page_cache('management');
}

function default_pages_manifest(): array
{
    return [
        'home' => [
            'title' => 'Home',
            'layout' => default_page_layout_home(),
        ],
        'management' => [
            'title' => 'Management',
            'layout' => default_page_layout_management(),
        ],
        'about' => [
            'title' => 'About',
            'layout' => default_page_layout_about(),
        ],
        'services' => [
            'title' => 'Services',
            'layout' => default_page_layout_services(),
        ],
        'case-studies' => [
            'title' => 'Case Studies',
            'layout' => default_page_layout_case_studies(),
        ],
        'pricing' => [
            'title' => 'Pricing',
            'layout' => default_page_layout_pricing(),
        ],
        'track' => [
            'title' => 'Track Order',
            'layout' => default_page_layout_track(),
        ],
        'blog' => [
            'title' => 'Insights',
            'layout' => default_page_layout_blog(),
        ],
        'faq' => [
            'title' => 'FAQ',
            'layout' => default_page_layout_faq(),
        ],
        'contact' => [
            'title' => 'Contact',
            'layout' => default_page_layout_contact(),
        ],
        'terms' => [
            'title' => 'Terms & Conditions',
            'layout' => default_page_layout_terms(),
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'layout' => default_page_layout_privacy(),
        ],
    ];
}

function default_footer_block_data(): array
{
    return [
        'companyName' => brand_name(),
        'summary' => business_support_summary(),
        'address' => 'Lagos, Nigeria',
        'phone' => '+234 806 000 0000',
        'email' => 'support@iworksgt.com',
        'columns' => [
            [
                'title' => 'Company',
                'links' => [
                    ['label' => 'Home', 'href' => public_url('home')],
                    ['label' => 'Management', 'href' => public_url('management')],
                    ['label' => 'About', 'href' => public_url('about')],
                    ['label' => 'Case Studies', 'href' => public_url('case-studies')],
                    ['label' => 'Insights', 'href' => public_url('blog')],
                    ['label' => 'Contact', 'href' => public_url('contact')],
                    ['label' => 'FAQ', 'href' => public_url('faq')],
                ],
            ],
            [
                'title' => 'Services',
                'links' => [
                    ['label' => 'Business Registration', 'href' => public_url('services', ['category' => 'business-registration'])],
                    ['label' => 'Trademark', 'href' => public_url('services', ['category' => 'trademark'])],
                    ['label' => 'Growth Support', 'href' => public_url('services', ['category' => 'growth'])],
                ],
            ],
            [
                'title' => 'Legal',
                'links' => [
                    ['label' => 'Terms & Conditions', 'href' => public_url('terms')],
                    ['label' => 'Privacy Policy', 'href' => public_url('privacy')],
                    ['label' => 'Track Order', 'href' => public_url('track')],
                    ['label' => 'Portal Login', 'href' => portal_url('login')],
                ],
            ],
        ],
    ];
}

function default_header_config_block_data(): array
{
    return [
        'announcement' => 'Operate remotely with structured business support, tracking, and transparent delivery.',
        'aboutLabel' => 'About',
        'aboutHref' => public_url('about'),
        'managementLabel' => 'Management',
        'managementHref' => public_url('management'),
        'useCasesLabel' => 'Case Studies',
        'useCasesHref' => public_url('case-studies'),
        'blogLabel' => 'Insights',
        'blogHref' => public_url('blog'),
        'contactLabel' => 'Contact Us',
        'contactHref' => public_url('contact'),
        'signInLabel' => 'Sign In',
        'ctaLabel' => 'Talk to our experts',
        'ctaHref' => public_url('services'),
    ];
}

function default_founder_story_social_links(): array
{
    return [
        ['network' => 'instagram', 'label' => 'Instagram', 'href' => 'https://instagram.com/oprahayo'],
        ['network' => 'x', 'label' => 'X', 'href' => 'https://x.com/oprahayo'],
        ['network' => 'linkedin', 'label' => 'LinkedIn', 'href' => 'https://linkedin.com/in/oprahayo'],
        ['network' => 'facebook', 'label' => 'Facebook', 'href' => 'https://facebook.com/oprahayo'],
        ['network' => 'tiktok', 'label' => 'TikTok', 'href' => 'https://tiktok.com/@oprahayo'],
        ['network' => 'youtube', 'label' => 'YouTube', 'href' => 'https://youtube.com/@oprahayo'],
    ];
}

function default_management_profile_items(): array
{
    return [
        [
            'name' => 'Executive leadership',
            'role' => 'Strategic direction',
            'summary' => 'Use this profile space to introduce the leadership voice, strategic priorities, and the people guiding the organisation.',
            'imageId' => null,
            'href' => public_url('management'),
        ],
        [
            'name' => 'Operations & administration',
            'role' => 'Daily execution',
            'summary' => 'Publish the management information responsible for administration, compliance, planning, and day-to-day coordination.',
            'imageId' => null,
            'href' => public_url('management'),
        ],
        [
            'name' => 'Academy & programme oversight',
            'role' => 'Player pathway support',
            'summary' => 'Highlight the leaders overseeing programme delivery, academy standards, welfare, and long-term development planning.',
            'imageId' => null,
            'href' => public_url('management'),
        ],
    ];
}

function default_page_layout_about(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'About iWorks Global',
                'headline' => 'Structured business support built for founders who want clarity and execution.',
                'subheadline' => 'iWorks Global helps entrepreneurs launch, file, protect, and grow their businesses remotely with a process they can actually follow.',
                'primaryCta' => ['label' => 'Browse services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Track order', 'href' => public_url('track')],
                'proofItems' => [
                    ['title' => 'Remote-first support', 'text' => 'Service delivery, document collection, and updates handled in one portal.'],
                    ['title' => 'Clear communication', 'text' => 'Clients always know the current stage, next requirement, and final outcome.'],
                    ['title' => 'Built for momentum', 'text' => 'We reduce the friction that slows founders down during setup and execution.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'founder_story',
            'data' => [
                'kicker' => 'About the founder',
                'title' => 'A practical support system for founders who need action, not confusion.',
                'subtitle' => 'Built from real execution gaps founders face when business support is scattered and hard to track.',
                'name' => 'Opeyemi Afolayan',
                'role' => 'Founder, iWorks Global',
                'summary' => 'I built iWorks Global around one idea: business support should be structured, transparent, and easy to access remotely.',
                'quote' => 'Founders move faster when paperwork, communication, and delivery live in one accountable workflow.',
                'imageId' => null,
                'socials' => default_founder_story_social_links(),
                'sections' => [
                    [
                        'title' => 'The challenge',
                        'text' => 'Founders often know exactly what they need, but the delivery path is fragmented. Registration updates happen in one chat, payments in another place, and required documents get lost across follow-ups. That slows execution and creates avoidable confusion.',
                    ],
                    [
                        'title' => 'The decision',
                        'text' => 'iWorks Global was built to turn that scattered process into one visible workflow. Clients should be able to request a service, pay, upload requirements, track progress, and receive final delivery in one place.',
                    ],
                    [
                        'title' => 'The builder',
                        'text' => "- Structured ordering and payment flows\n- Centralised comments, files, and delivery updates\n- Clear status tracking for every active request\n- A shared operating view for clients, agents, and admin",
                    ],
                    [
                        'title' => 'The standard',
                        'text' => 'Clarity, speed, and accountability matter. Every service should have a visible next step, a defined owner, and a documented outcome. That operating standard shapes how iWorks communicates, assigns work, and delivers.',
                    ],
                    [
                        'title' => 'The mission',
                        'text' => 'The goal is simple: make iWorks Global the support layer founders can rely on whenever they need structured business execution, without the stress of chasing updates or losing visibility.',
                    ],
                ],
            ],
        ],
        [
            'type' => 'cta_banner',
            'data' => [
                'headline' => 'Ready to work with iWorks Global?',
                'text' => 'Browse the public catalog, open the right service, and move into the portal flow with clear next steps.',
                'cta' => ['label' => 'Explore services', 'href' => public_url('services')],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_home(): array
{
    return [
        [
            'type' => 'header_config',
            'data' => default_header_config_block_data(),
        ],
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Business setup, filing, compliance, and growth support',
                'headline' => 'Launch and grow your business remotely with iWorks Global.',
                'subheadline' => 'CAC registration, trademark filing, NAFDAC-related support, branding, growth services, and structured business operations handled in one portal.',
                'primaryCta' => ['label' => 'Explore our services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Track job', 'href' => public_url('track')],
                'proofItems' => [
                    ['title' => 'Register your business', 'text' => 'CAC filing support for business names, companies, trustees, and limited by guarantee entities.'],
                    ['title' => 'Protect your brand', 'text' => 'Trademark filing, intellectual property support, and brand identity execution.'],
                    ['title' => 'Scale confidently', 'text' => 'Business support services, digital presence, and operational delivery tracked online.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'trusted_logos',
            'data' => [
                'title' => 'Trusted by founders, operators, and growing businesses',
                'items' => [
                    ['title' => 'CAC filings', 'text' => 'Business registration support'],
                    ['title' => 'Trademark support', 'text' => 'Protect your intellectual property'],
                    ['title' => 'Operations support', 'text' => 'Manage jobs and delivery remotely'],
                    ['title' => 'Digital growth', 'text' => 'Web, design, and marketing execution'],
                ],
            ],
        ],
        [
            'type' => 'how_it_works',
            'data' => [
                'kicker' => 'How it works',
                'title' => 'From signup to operating in days',
                'subtitle' => 'We handle the paperwork so you can focus on building your business.',
                'footnote' => 'Most businesses are processed quickly once all required details are complete.',
                'steps' => [
                    [
                        'title' => 'Choose your service',
                        'duration' => '5 minutes',
                        'text' => 'Select your service, submit basic details, and complete checkout.',
                        'icon' => 'bi-ui-checks',
                    ],
                    [
                        'title' => 'We file everything',
                        'duration' => '1-3 business days',
                        'text' => 'We prepare and submit your formation documents so you can operate quickly.',
                        'icon' => 'bi-file-earmark-text',
                    ],
                    [
                        'title' => "You're ready",
                        'duration' => 'Instant access',
                        'text' => 'Receive updates, documents, and final delivery directly inside your portal.',
                        'icon' => 'bi-patch-check',
                    ],
                ],
            ],
        ],
        [
            'type' => 'featured_services',
            'data' => [
                'title' => 'Services entrepreneurs use most',
                'subtitle' => 'Featured business setup and support services connected directly to your portal catalog.',
                'serviceIds' => [],
            ],
        ],
        [
            'type' => 'case_studies',
            'data' => [
                'kicker' => 'Portfolio',
                'title' => 'Some of our recent projects',
                'subtitle' => 'Explore selected client projects and implementation outcomes delivered by the iWorks team.',
                'ctaLabel' => 'More projects',
                'ctaHref' => public_url('case-studies'),
                'defaultLink' => public_url('services'),
                'items' => [],
            ],
        ],
        [
            'type' => 'benefit_cards',
            'data' => [
                'items' => [
                    ['title' => 'Fast processing flow', 'text' => 'Structured service delivery that reduces follow-up delays and keeps work moving.', 'icon' => 'bi-lightning-charge'],
                    ['title' => 'Transparent execution', 'text' => 'Clear invoice, job, and comment timelines so clients always know the current stage.', 'icon' => 'bi-eye'],
                    ['title' => 'Flexible payments', 'text' => 'NGN and USD support with configured gateway options and remote submission flow.', 'icon' => 'bi-credit-card-2-front'],
                ],
            ],
        ],
        [
            'type' => 'management_profiles',
            'data' => [
                'kicker' => 'Management',
                'title' => 'Meet the management team',
                'subtitle' => 'Share the leadership structure, responsibilities, and the people shaping standards across the organisation.',
                'displayStyle' => 'compact',
                'ctaLabel' => 'View management',
                'ctaHref' => public_url('management'),
                'items' => default_management_profile_items(),
            ],
        ],
        [
            'type' => 'testimonials',
            'data' => [
                'kicker' => 'Customer Reviews',
                'title' => 'See what founders around the world are saying about their experience',
                'ratingValue' => '4.8',
                'ratingCount' => '372',
                'items' => [
                    ['name' => 'Lucas Silva', 'role' => 'E-commerce', 'company' => 'Nova Digital', 'location' => 'Brazil', 'rating' => '5', 'text' => 'I was worried about opening a US bank account from Brazil. The iWorks team walked me through the entire process remotely.'],
                    ['name' => 'James Wilson', 'role' => 'Investment', 'company' => 'Atlantic Ventures', 'location' => 'United Kingdom', 'rating' => '5', 'text' => 'Transparent pricing with no hidden fees. What you see is what you pay, and the delivery flow stayed clear from start to finish.'],
                    ['name' => 'Fatima Al-Hassan', 'role' => 'Retail', 'company' => 'Oasis Trading', 'location' => 'Saudi Arabia', 'rating' => '4.5', 'text' => 'Their support team answered all my questions patiently, even the basic ones. They made me feel confident about starting the process.'],
                ],
            ],
        ],
        [
            'type' => 'blog_highlights',
            'data' => [
                'title' => 'Insights for founders and operators',
                'ctaLabel' => 'Read all insights',
                'ctaHref' => public_url('blog'),
                'items' => [
                    ['tag' => 'Business setup', 'title' => 'What to prepare before CAC registration', 'text' => 'A practical checklist for founders getting ready to formalise their business.', 'href' => public_url('blog_post', ['slug' => 'what-to-prepare-before-cac-registration'])],
                    ['tag' => 'Trademark', 'title' => 'When your brand should file for trademark protection', 'text' => 'Understand the right timing, documentation, and filing workflow for your mark.', 'href' => public_url('blog_post', ['slug' => 'when-your-brand-should-file-for-trademark-protection'])],
                    ['tag' => 'Operations', 'title' => 'How remote service delivery reduces execution delays', 'text' => 'Why structured comments, files, and invoice flow improve service turnaround.', 'href' => public_url('blog_post', ['slug' => 'how-remote-service-delivery-reduces-execution-delays'])],
                ],
            ],
        ],
        [
            'type' => 'faq',
            'data' => [
                'kicker' => 'FAQ',
                'title' => 'Common Questions',
                'subtitle' => 'Everything you need to know about iWorks business support and service delivery.',
                'items' => default_portal_faq_items(),
            ],
        ],
        [
            'type' => 'contact_form',
            'data' => [
                'title' => 'Need help choosing the right service?',
                'text' => 'Send a message and our team will guide you before you place an order.',
                'submitLabel' => 'Send message',
                'email' => 'support@iworksgt.com',
                'phone' => '+234 806 000 0000',
                'whatsapp' => 'https://wa.me/2348060000000',
            ],
        ],
        [
            'type' => 'cta_banner',
            'data' => [
                'headline' => 'Launch faster with the right support stack.',
                'text' => 'Start with business registration, brand protection, or operational support and track execution from one dashboard.',
                'cta' => ['label' => 'Get Started Now', 'href' => public_url('services')],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_management(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Leadership & oversight',
                'headline' => 'Management information and leadership structure.',
                'subheadline' => 'Use this page to publish the leadership profiles, operational responsibilities, and management updates that give visitors confidence in the organisation.',
                'primaryCta' => ['label' => 'Contact us', 'href' => public_url('contact')],
                'secondaryCta' => ['label' => 'Back home', 'href' => public_url('home')],
                'proofItems' => [
                    ['title' => 'Leadership visibility', 'text' => 'Show the people responsible for strategic direction and operational delivery.'],
                    ['title' => 'Clear responsibilities', 'text' => 'Present role focus areas so visitors understand who oversees what.'],
                    ['title' => 'Easy future updates', 'text' => 'Update this page at any time through the existing frontend editor.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'management_profiles',
            'data' => [
                'kicker' => 'Management',
                'title' => 'Management team overview',
                'subtitle' => 'Publish official bios, leadership responsibilities, and role summaries for the people leading the organisation.',
                'displayStyle' => 'full',
                'ctaLabel' => 'Make an enquiry',
                'ctaHref' => public_url('contact'),
                'items' => default_management_profile_items(),
            ],
        ],
        [
            'type' => 'rich_text',
            'data' => [
                'title' => 'What to publish here',
                'htmlOrDelta' => '<p>This page is designed for official management information. Use it to present leadership bios, position titles, areas of responsibility, and the operational context that helps visitors understand how the organisation is led.</p><p>As management information evolves, this page can be updated with new profile images, fresh summaries, and expanded organisational notes.</p>',
            ],
        ],
        [
            'type' => 'cta_banner',
            'data' => [
                'headline' => 'Need to speak with the management team?',
                'text' => 'Use the contact page to direct business, partnership, or organisational enquiries to the right channel.',
                'cta' => ['label' => 'Contact us', 'href' => public_url('contact')],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_blog(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Insights',
                'headline' => 'Clear business insights for founders, operators, and growing teams.',
                'subheadline' => 'Read practical guides on registration, trademark protection, operations, remote delivery, and structured business support.',
                'primaryCta' => ['label' => 'Browse services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Contact us', 'href' => public_url('contact')],
                'proofItems' => [
                    ['title' => 'Registration guidance', 'text' => 'What to prepare before filing or placing your order.'],
                    ['title' => 'Operational advice', 'text' => 'How to keep delivery, documents, and communication structured.'],
                    ['title' => 'Growth support', 'text' => 'Practical ideas for compliance, branding, and execution.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'blog_highlights',
            'data' => [
                'title' => 'Latest insights',
                'ctaLabel' => '',
                'ctaHref' => '',
                'items' => [],
            ],
        ],
        [
            'type' => 'cta_banner',
            'data' => [
                'headline' => 'Need support beyond the article?',
                'text' => 'Open the right service and let iWorks handle the paperwork, filing, and delivery remotely.',
                'cta' => ['label' => 'Explore services', 'href' => public_url('services')],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_portal_faq_items(): array
{
    return [
        [
            'q' => 'How do I place an order on the iWorks portal?',
            'a' => "1. Open the Services page and choose the service you need.\n2. Review the service details and click Order or Get Started.\n3. Add your description or requirements in the order summary.\n4. Continue to invoice and complete payment.\n5. After payment, your job is created and you can track it from your dashboard.",
        ],
        [
            'q' => 'When does processing start after I make payment?',
            'a' => "1. Your invoice must show as Paid or Confirmed.\n2. The system creates or activates the job in your portal.\n3. If the service requires an intake form, you will be prompted to complete it.\n4. Once the required details are complete, an agent or admin starts processing.\n5. Status updates then move from Pending to Processing and onward.",
        ],
        [
            'q' => 'How do I submit my business documents and requirements?',
            'a' => "1. Open your job from the dashboard.\n2. Complete any required intake form attached to that service.\n3. Use the file upload area to drag in or choose your documents.\n4. Review the staged files and remove anything you do not want to send.\n5. Submit the uploaded files so the assigned team can review them.",
        ],
        [
            'q' => 'How do I track the status of my service request?',
            'a' => "1. Sign in to your portal account.\n2. Open Jobs from the left menu.\n3. Select the job you want to monitor.\n4. Check the status badge, comments, uploaded files, and timeline.\n5. If needed, use the Track Order page publicly with your order ID and contact details for a quick status summary.",
        ],
        [
            'q' => 'What happens if more information or corrections are needed?',
            'a' => "1. The admin or assigned agent will request an update.\n2. You will receive a notification and see the note inside your job or form.\n3. Reopen the form or job request that needs correction.\n4. Update the missing details or upload the requested documents.\n5. Resubmit so processing can continue without delay.",
        ],
        [
            'q' => 'Can I pay in NGN or USD, and how is the payment method selected?',
            'a' => "1. Choose your preferred pay currency where the service allows it.\n2. The portal shows the supported payment methods for that currency.\n3. NGN-based orders typically show local gateway or transfer options.\n4. USD-based orders typically show the international payment options configured by admin.\n5. After payment is successful, your invoice and job update automatically.",
        ],
        [
            'q' => 'How do I communicate with support, admin, or the assigned agent?',
            'a' => "1. Open the job or support area in your portal.\n2. Use the comment or messaging section to send your question.\n3. Press Enter to send, or Shift+Enter for a new line where supported.\n4. Attach files if your message needs supporting documents.\n5. You will receive notifications when there is a reply or status change.",
        ],
        [
            'q' => 'How will I receive my final delivery?',
            'a' => "1. When the job is complete, the status moves to Delivered.\n2. Final files or documents are uploaded to the job.\n3. Review the delivery inside the portal dashboard.\n4. If everything is correct, accept delivery or close the job where enabled.\n5. If a revision is allowed, use the provided request path before the job is fully closed.",
        ],
    ];
}

function default_page_layout_services(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Services',
                'headline' => 'Choose the support service your business needs next.',
                'subheadline' => 'Explore iWorks services by category, compare service scope, and move directly into the portal order flow.',
                'primaryCta' => ['label' => 'Get started now', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Track Order', 'href' => public_url('track')],
                'proofItems' => [
                    ['title' => 'Search and filter services', 'text' => 'Browse services by category and business need.'],
                    ['title' => 'Review service scope quickly', 'text' => 'See short descriptions, prices, and preview details before ordering.'],
                    ['title' => 'Move directly into execution', 'text' => 'Order in the portal and continue with structured intake and tracking.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'service_categories',
            'data' => [
                'title' => 'Browse by category',
                'categories' => [
                    ['name' => 'Business Registration', 'description' => 'CAC business names, companies, trustees, and launch packages.', 'href' => public_url('services', ['category' => 'business-registration'])],
                    ['name' => 'Trademark & IP', 'description' => 'Brand protection, filing support, and intellectual property workflow.', 'href' => public_url('services', ['category' => 'trademark'])],
                    ['name' => 'Growth & Digital', 'description' => 'Web, design, hosting, marketing, and operational scale support.', 'href' => public_url('services', ['category' => 'growth'])],
                ],
            ],
        ],
        [
            'type' => 'service_grid',
            'data' => [
                'title' => 'Public Service Catalog',
                'serviceIds' => [],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_case_studies(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Recent projects',
                'headline' => 'Use cases shaped by real delivery work',
                'subheadline' => 'Explore selected iWorks projects, execution stories, and practical outcomes across business support, filing, compliance, and digital growth.',
                'primaryCta' => ['label' => 'Browse services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Contact us', 'href' => public_url('contact')],
                'proofItems' => [],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'case_studies',
            'data' => [
                'kicker' => 'Portfolio',
                'title' => 'Selected case studies from the iWorks delivery desk',
                'subtitle' => 'Each entry can link directly to a live project, external case study page, or portfolio destination managed from your admin dashboard.',
                'ctaLabel' => 'Talk to our experts',
                'ctaHref' => public_url('contact'),
                'items' => [],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_pricing(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'headline' => 'Simple pricing. No hidden process.',
                'subheadline' => 'Review service pricing before checkout. Final totals include service tax settings.',
                'primaryCta' => ['label' => 'Browse Services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Login', 'href' => portal_url('login')],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'rich_text',
            'data' => [
                'title' => 'Pricing notes',
                'htmlOrDelta' => '<p>Each service has clear pricing, tax handling, and delivery scope. Review a service detail page for exact requirements.</p>',
            ],
        ],
        [
            'type' => 'service_grid',
            'data' => [
                'title' => 'Services and pricing',
                'serviceIds' => [],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_track(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Order tracking',
                'headline' => 'Track your order progress in seconds.',
                'subheadline' => 'Enter your Job ID with the email or phone used for the order to see a quick status summary.',
                'primaryCta' => ['label' => 'Login', 'href' => portal_url('login')],
                'secondaryCta' => ['label' => 'Browse Services', 'href' => public_url('services')],
                'proofItems' => [
                    ['title' => 'Quick status summary', 'text' => 'View job and payment progress without opening the full portal.'],
                    ['title' => 'Secure access check', 'text' => 'Tracking works only when the contact matches the order record.'],
                    ['title' => 'Continue in portal', 'text' => 'Use login for full files, comments, and delivery details.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'rich_text',
            'data' => [
                'title' => 'How tracking works',
                'htmlOrDelta' => '<p>Tracking returns your latest status and payment summary. Sign in to view files, comments, and full job details.</p>',
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_faq(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'headline' => 'Frequently asked questions',
                'subheadline' => 'Answers about service ordering, payment, and delivery workflow.',
                'primaryCta' => ['label' => 'Browse Services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Track Order', 'href' => public_url('track')],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'faq',
            'data' => [
                'kicker' => 'FAQ',
                'title' => 'Common Questions',
                'subtitle' => 'Everything you need to know before placing or tracking an iWorks service order.',
                'items' => default_portal_faq_items(),
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_contact(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Contact iWorks Global',
                'headline' => 'Talk to the iWorks team.',
                'subheadline' => 'Need guidance before you order? Send your business question and we will point you to the right service or next step.',
                'primaryCta' => ['label' => 'Browse Services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Login', 'href' => portal_url('login')],
                'proofItems' => [
                    ['title' => 'Pre-order guidance', 'text' => 'Get help selecting the right business support service.'],
                    ['title' => 'Custom requests', 'text' => 'We support special operational, filing, and execution requests.'],
                    ['title' => 'Fast response flow', 'text' => 'Your message is stored, routed, and visible to the admin team quickly.'],
                ],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'contact_form',
            'data' => [
                'title' => 'Send a message',
                'text' => 'Tell us what you need and we will respond with the right service path.',
                'submitLabel' => 'Send enquiry',
                'email' => 'support@iworksgt.com',
                'phone' => '+234 806 000 0000',
                'whatsapp' => 'https://wa.me/2348060000000',
            ],
        ],
        [
            'type' => 'benefit_cards',
            'data' => [
                'items' => [
                    ['title' => 'Email support', 'text' => 'support@iworksgt.com', 'icon' => 'bi-envelope'],
                    ['title' => 'Phone support', 'text' => '+234 806 000 0000', 'icon' => 'bi-telephone'],
                    ['title' => 'Business hours', 'text' => 'Mon - Fri, 9:00 AM to 6:00 PM', 'icon' => 'bi-clock'],
                ],
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_terms(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Legal',
                'headline' => 'Terms & Conditions',
                'subheadline' => 'Please review these terms before using iWorks Global services and portal features.',
                'primaryCta' => ['label' => 'Browse services', 'href' => public_url('services')],
                'secondaryCta' => ['label' => 'Contact support', 'href' => public_url('contact')],
                'proofItems' => [],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'rich_text',
            'data' => [
                'title' => 'Service Terms',
                'htmlOrDelta' => '<h3>1. Scope of services</h3><p>iWorks Global provides business support services including business registration, trademark filing support, compliance workflow, operations support, and related administrative services delivered through our portal.</p><h3>2. Orders and payments</h3><p>Service processing starts after payment confirmation and required information/documents are submitted. Quoted fees apply only to the selected service scope and may exclude government fees unless clearly stated.</p><h3>3. Client responsibilities</h3><p>Clients must provide accurate information, valid documents, and timely responses to update requests. Delays caused by incomplete or inaccurate submissions may affect delivery timelines.</p><h3>4. Timelines and delivery</h3><p>Any timeline provided is an estimate based on normal processing flow. Government or third-party review timelines are outside iWorks Global direct control.</p><h3>5. Revisions and updates</h3><p>Where additional information is required, your submission may be reopened with an update note. Service resumes once updated information is resubmitted.</p><h3>6. Fees and refunds</h3><p>Administrative and consultation efforts already rendered are generally non-refundable. Where a refund is approved, it is processed according to the selected payment method and applicable provider rules.</p><h3>7. Acceptable use</h3><p>You agree not to misuse the portal, upload unlawful content, or attempt unauthorized access to other users, services, files, or data.</p><h3>8. Limitation</h3><p>iWorks Global does not guarantee government approvals. We provide structured preparation, filing support, and delivery management.</p><h3>9. Changes to these terms</h3><p>We may update these terms from time to time. Continued use of the platform indicates acceptance of the latest version.</p><h3>10. Contact</h3><p>For legal or service questions, contact us through the official support channels listed on the Contact page.</p>',
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function default_page_layout_privacy(): array
{
    return [
        [
            'type' => 'hero',
            'data' => [
                'kicker' => 'Legal',
                'headline' => 'Privacy Policy',
                'subheadline' => 'This policy explains how iWorks Global collects, uses, and protects your information.',
                'primaryCta' => ['label' => 'Contact support', 'href' => public_url('contact')],
                'secondaryCta' => ['label' => 'Browse services', 'href' => public_url('services')],
                'proofItems' => [],
                'imageId' => null,
            ],
        ],
        [
            'type' => 'rich_text',
            'data' => [
                'title' => 'Data Privacy',
                'htmlOrDelta' => '<h3>1. Information we collect</h3><p>We collect account details, contact information, service order data, submitted documents, communication records, and payment-related references needed to deliver requested services.</p><h3>2. How we use your information</h3><p>Your information is used to process orders, verify requirements, communicate updates, provide support, improve service quality, and meet legal obligations.</p><h3>3. File and document handling</h3><p>Uploaded files are stored in secured locations and are only accessible to authorized roles involved in your job workflow (client, assigned agent, and admin as applicable).</p><h3>4. Payment and security</h3><p>Payment processing may involve third-party gateways. Sensitive payment credentials are handled by configured payment providers according to their security standards.</p><h3>5. Sharing of information</h3><p>We do not sell personal data. Information is shared only when required for service delivery, legal compliance, fraud prevention, or with trusted processing partners.</p><h3>6. Retention</h3><p>We retain records for operational, compliance, and audit purposes for as long as reasonably necessary, or as required by law.</p><h3>7. Your rights</h3><p>You may request corrections to inaccurate account data and update profile/contact records through the portal or support channels.</p><h3>8. Cookies and analytics</h3><p>We may use essential cookies/session tools for authentication, security, and user experience improvements.</p><h3>9. Policy updates</h3><p>This policy may be updated periodically. The latest version is always published on this page.</p><h3>10. Contact</h3><p>For privacy concerns or requests, please contact iWorks Global support through the Contact page.</p>',
            ],
        ],
        [
            'type' => 'footer',
            'data' => default_footer_block_data(),
        ],
    ];
}

function normalize_page_block_type(string $type): string
{
    $type = strtolower(trim($type));
    $legacyMap = [
        'trust_bar' => 'trusted_logos',
        'trust_badges' => 'benefit_cards',
        'faqs' => 'faq',
    ];
    return $legacyMap[$type] ?? $type;
}

function allowed_page_block_types(): array
{
    return [
        'header_config',
        'hero',
        'founder_story',
        'management_profiles',
        'case_studies',
        'service_grid',
        'featured_services',
        'trusted_logos',
        'how_it_works',
        'features',
        'benefit_cards',
        'testimonials',
        'blog_highlights',
        'faq',
        'contact_form',
        'cta_banner',
        'rich_text',
        'footer',
        // Backward-compatible type still supported in editor and renderer.
        'service_categories',
    ];
}

function normalize_page_layout(array $layout): array
{
    $normalized = [];
    foreach ($layout as $block) {
        if (!is_array($block)) {
            continue;
        }
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        if ($type === '') {
            continue;
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        if ($type === 'rich_text') {
            $data['htmlOrDelta'] = sanitize_limited_html((string) ($data['htmlOrDelta'] ?? ''));
        }
        if ($type === 'management_profiles') {
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $normalizedItems = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = trim((string) ($item['name'] ?? ''));
                $role = trim((string) ($item['role'] ?? ''));
                $summary = trim((string) ($item['summary'] ?? ''));
                $href = trim((string) ($item['href'] ?? ''));
                $imageId = (int) ($item['imageId'] ?? 0);
                if ($name === '' && $role === '' && $summary === '' && $href === '' && $imageId < 1) {
                    continue;
                }
                $normalizedItems[] = [
                    'name' => $name,
                    'role' => $role,
                    'summary' => $summary,
                    'href' => $href,
                    'imageId' => $imageId > 0 ? $imageId : null,
                ];
            }
            $data['items'] = $normalizedItems;
            $data['displayStyle'] = in_array((string) ($data['displayStyle'] ?? ''), ['compact', 'full'], true)
                ? (string) $data['displayStyle']
                : 'full';
        }
        if (in_array($type, ['service_grid', 'featured_services'], true)) {
            $data['serviceIds'] = array_values(array_filter(array_map('intval', is_array($data['serviceIds'] ?? null) ? $data['serviceIds'] : [])));
        }
        if ($type === 'footer') {
            $data['columns'] = [
                [
                    'title' => 'Company',
                    'links' => parse_link_lines((string) ($data['companyLinksText'] ?? '')),
                ],
                [
                    'title' => 'Services',
                    'links' => parse_link_lines((string) ($data['servicesLinksText'] ?? '')),
                ],
                [
                    'title' => 'Legal',
                    'links' => parse_link_lines((string) ($data['legalLinksText'] ?? '')),
                ],
            ];
            unset($data['companyLinksText'], $data['servicesLinksText'], $data['legalLinksText']);
        }
        if ($type === 'case_studies') {
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $normalizedItems = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = trim((string) ($item['name'] ?? ''));
                $category = trim((string) ($item['category'] ?? ''));
                $summary = trim((string) ($item['summary'] ?? ''));
                $result = trim((string) ($item['result'] ?? ''));
                $link = trim((string) ($item['link'] ?? ''));
                $imageId = (int) ($item['imageId'] ?? 0);
                if ($name === '' && $category === '' && $summary === '' && $result === '' && $link === '' && $imageId < 1) {
                    continue;
                }
                $normalizedItems[] = [
                    'name' => $name,
                    'category' => $category,
                    'summary' => $summary,
                    'result' => $result,
                    'link' => $link,
                    'imageId' => $imageId > 0 ? $imageId : null,
                ];
            }
            $data['items'] = $normalizedItems;
        }
        $normalized[] = [
            'type' => $type,
            'data' => $data,
        ];
    }
    return $normalized;
}

function validate_page_layout(array $layout): array
{
    $errors = [];
    $allowed = allowed_page_block_types();

    foreach ($layout as $index => $block) {
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        if ($type === '' || !in_array($type, $allowed, true)) {
            $errors[] = 'Block #' . ($index + 1) . ' has invalid type.';
            continue;
        }
        if (!is_array($block['data'] ?? null)) {
            $errors[] = 'Block #' . ($index + 1) . ' is missing data.';
        }
    }

    return $errors;
}

function list_pages_admin(): array
{
    return db_all('SELECT * FROM pages ORDER BY slug ASC');
}

function load_page_blocks_for_page(string $pageId): array
{
    if (!schema_table_exists('page_blocks')) {
        return [];
    }

    $rows = db_all(
        'SELECT type, content_json, order_index
         FROM page_blocks
         WHERE page_id = :page_id
         ORDER BY order_index ASC, id ASC',
        ['page_id' => $pageId]
    );
    if (!$rows) {
        return [];
    }

    $layout = [];
    foreach ($rows as $row) {
        $layout[] = [
            'type' => normalize_page_block_type((string) ($row['type'] ?? '')),
            'data' => page_decode_json((string) ($row['content_json'] ?? '')),
        ];
    }
    return normalize_page_layout($layout);
}

function save_page_blocks_for_page(string $pageId, array $layout): void
{
    if (!schema_table_exists('page_blocks')) {
        return;
    }

    db_execute('DELETE FROM page_blocks WHERE page_id = :page_id', ['page_id' => $pageId]);

    foreach ($layout as $index => $block) {
        db_execute(
            'INSERT INTO page_blocks (page_id, type, content_json, order_index, created_at, updated_at)
             VALUES (:page_id, :type, :content_json, :order_index, :created_at, :updated_at)',
            [
                'page_id' => $pageId,
                'type' => normalize_page_block_type((string) ($block['type'] ?? '')),
                'content_json' => json_encode($block['data'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'order_index' => (int) $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

function sync_page_blocks_from_pages_if_needed(): void
{
    $pages = db_all('SELECT id, layout_json FROM pages');
    foreach ($pages as $page) {
        $pageId = (string) ($page['id'] ?? '');
        if ($pageId === '') {
            continue;
        }
        $existing = (int) (db_one(
            'SELECT COUNT(*) AS c FROM page_blocks WHERE page_id = :page_id',
            ['page_id' => $pageId]
        )['c'] ?? 0);
        if ($existing > 0) {
            continue;
        }
        $layout = normalize_page_layout(page_decode_json((string) ($page['layout_json'] ?? '')));
        if ($layout !== []) {
            save_page_blocks_for_page($pageId, $layout);
        }
    }
}

function sync_media_library_from_assets_if_needed(): void
{
    $countLibrary = (int) (db_one('SELECT COUNT(*) AS c FROM media_library')['c'] ?? 0);
    if ($countLibrary > 0) {
        return;
    }
    $countAssets = (int) (db_one('SELECT COUNT(*) AS c FROM media_assets')['c'] ?? 0);
    if ($countAssets < 1) {
        return;
    }

    db_execute(
        'INSERT INTO media_library (id, user_id, storage_key, url, mime, size_bytes, width, height, created_at)
         SELECT id, user_id, storage_key, url, mime, size_bytes, width, height, created_at
         FROM media_assets
         ORDER BY id ASC'
    );
}

function page_cache_directory(): string
{
    return __DIR__ . '/../storage/cache/pages';
}

function page_cache_file(string $slug): string
{
    $safeSlug = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($slug)) ?: 'page';
    return page_cache_directory() . '/' . $safeSlug . '.json';
}

function save_page_cache(string $slug, array $payload): void
{
    $dir = page_cache_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    @file_put_contents(page_cache_file($slug), json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function load_page_cache(string $slug): ?array
{
    $path = page_cache_file($slug);
    if (!is_file($path)) {
        return null;
    }
    $decoded = json_decode((string) @file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

function invalidate_page_cache(string $slug): void
{
    $path = page_cache_file($slug);
    if (is_file($path)) {
        @unlink($path);
    }
}

function get_page_admin_by_slug(string $slug): ?array
{
    $page = db_one('SELECT * FROM pages WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    if (!$page) {
        return null;
    }

    $layoutFromBlocks = load_page_blocks_for_page((string) $page['id']);
    $page['layout'] = $layoutFromBlocks !== []
        ? $layoutFromBlocks
        : normalize_page_layout(page_decode_json((string) ($page['layout_json'] ?? '')));
    $page['published_layout'] = normalize_page_layout(page_decode_json((string) ($page['published_layout_json'] ?? '')));
    $page['seo'] = page_decode_json((string) ($page['seo_json'] ?? ''));

    return $page;
}

function get_page_published_by_slug(string $slug): ?array
{
    $page = db_one(
        "SELECT *
         FROM pages
         WHERE slug = :slug AND status = 'published'
         LIMIT 1",
        ['slug' => $slug]
    );

    if (!$page) {
        return null;
    }

    $cache = load_page_cache($slug);
    if (is_array($cache) && (string) ($cache['updated_at'] ?? '') === (string) ($page['updated_at'] ?? '')) {
        return $cache;
    }

    $layout = page_decode_json((string) ($page['published_layout_json'] ?? ''));
    $page['layout'] = normalize_page_layout($layout);
    $page['seo'] = page_decode_json((string) ($page['seo_json'] ?? ''));

    save_page_cache($slug, $page);

    return $page;
}

function page_decode_json(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function extract_footer_from_layout(array $layout): ?array
{
    foreach ($layout as $block) {
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        if ($type !== 'footer') {
            continue;
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        return [
            'companyName' => trim((string) ($data['companyName'] ?? brand_name())),
            'summary' => trim((string) ($data['summary'] ?? business_support_summary())),
            'address' => trim((string) ($data['address'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'links' => is_array($data['links'] ?? null) ? $data['links'] : [],
            'columns' => is_array($data['columns'] ?? null) ? $data['columns'] : [],
        ];
    }

    return null;
}

function get_public_footer_data(string $slug = 'home'): array
{
    $footerPage = get_page_published_by_slug('contact');
    $footerLayout = is_array($footerPage['layout'] ?? null) ? $footerPage['layout'] : [];
    $footer = extract_footer_from_layout($footerLayout);
    if ($footer !== null) {
        return $footer;
    }

    $page = get_page_published_by_slug($slug);
    $layout = is_array($page['layout'] ?? null) ? $page['layout'] : [];
    $footer = extract_footer_from_layout($layout);
    if ($footer !== null) {
        return $footer;
    }
    return default_footer_block_data();
}

function extract_header_config_from_layout(array $layout): ?array
{
    foreach ($layout as $block) {
        $type = normalize_page_block_type((string) ($block['type'] ?? ''));
        if ($type !== 'header_config') {
            continue;
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        return [
            'announcement' => trim((string) ($data['announcement'] ?? '')),
            'aboutLabel' => trim((string) ($data['aboutLabel'] ?? '')),
            'aboutHref' => trim((string) ($data['aboutHref'] ?? '')),
            'managementLabel' => trim((string) ($data['managementLabel'] ?? '')),
            'managementHref' => trim((string) ($data['managementHref'] ?? '')),
            'useCasesLabel' => trim((string) ($data['useCasesLabel'] ?? '')),
            'useCasesHref' => trim((string) ($data['useCasesHref'] ?? '')),
            'blogLabel' => trim((string) ($data['blogLabel'] ?? '')),
            'blogHref' => trim((string) ($data['blogHref'] ?? '')),
            'contactLabel' => trim((string) ($data['contactLabel'] ?? '')),
            'contactHref' => trim((string) ($data['contactHref'] ?? '')),
            'signInLabel' => trim((string) ($data['signInLabel'] ?? '')),
            'ctaLabel' => trim((string) ($data['ctaLabel'] ?? '')),
            'ctaHref' => trim((string) ($data['ctaHref'] ?? '')),
        ];
    }

    return null;
}

function get_public_header_config(): array
{
    $defaults = default_header_config_block_data();
    $homePage = get_page_published_by_slug('home');
    $layout = is_array($homePage['layout'] ?? null) ? $homePage['layout'] : [];
    $config = array_merge($defaults, extract_header_config_from_layout($layout) ?? []);

    $aboutHref = trim((string) ($config['aboutHref'] ?? ''));
    if ($aboutHref === '' || str_starts_with($aboutHref, '#')) {
        $config['aboutHref'] = public_url('about');
    }

    $useCasesHref = trim((string) ($config['useCasesHref'] ?? ''));
    if ($useCasesHref === '' || str_starts_with($useCasesHref, '#')) {
        $config['useCasesHref'] = public_url('case-studies');
    }

    $managementHref = trim((string) ($config['managementHref'] ?? ''));
    if ($managementHref === '' || str_starts_with($managementHref, '#')) {
        $config['managementHref'] = public_url('management');
    }

    $blogHref = trim((string) ($config['blogHref'] ?? ''));
    if ($blogHref === '' || str_starts_with($blogHref, '#')) {
        $config['blogHref'] = public_url('blog');
    }

    $contactHref = trim((string) ($config['contactHref'] ?? ''));
    if ($contactHref === '' || str_starts_with($contactHref, '#')) {
        $config['contactHref'] = public_url('contact');
    }

    if (trim((string) ($config['blogLabel'] ?? '')) === '') {
        $config['blogLabel'] = 'Insights';
    }
    if (trim((string) ($config['aboutLabel'] ?? '')) === '') {
        $config['aboutLabel'] = 'About';
    }
    if (trim((string) ($config['useCasesLabel'] ?? '')) === '') {
        $config['useCasesLabel'] = 'Case Studies';
    }
    if (trim((string) ($config['managementLabel'] ?? '')) === '') {
        $config['managementLabel'] = 'Management';
    }
    if (trim((string) ($config['contactLabel'] ?? '')) === '') {
        $config['contactLabel'] = 'Contact Us';
    }
    if (trim((string) ($config['signInLabel'] ?? '')) === '') {
        $config['signInLabel'] = 'Sign In';
    }
    if (trim((string) ($config['ctaLabel'] ?? '')) === '') {
        $config['ctaLabel'] = 'Talk to our experts';
    }
    $ctaHref = trim((string) ($config['ctaHref'] ?? ''));
    if ($ctaHref === '' || str_starts_with($ctaHref, '#')) {
        $config['ctaHref'] = public_url('services');
    }

    return $config;
}

function decode_json_array_field(mixed $value): array
{
    if (is_array($value)) {
        return $value;
    }
    if (!is_string($value) || trim($value) === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function parse_link_lines(string $raw): array
{
    $links = [];
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        [$label, $href] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if ($label === '' && $href === '') {
            continue;
        }
        if ($href === '') {
            $href = '#';
        }
        $links[] = ['label' => $label !== '' ? $label : $href, 'href' => $href];
    }
    return $links;
}

function service_marketing_payload(array $service): array
{
    $service['short_description'] = trim((string) ($service['short_description'] ?? ''));
    if ($service['short_description'] === '') {
        $service['short_description'] = trim((string) strip_tags((string) ($service['description'] ?? '')));
    }
    $service['featured_bullets'] = array_values(array_filter(array_map(
        static fn(mixed $item): string => trim((string) (is_array($item) ? ($item['text'] ?? '') : $item)),
        decode_json_array_field($service['featured_bullets'] ?? [])
    )));
    $service['featured_image'] = null;
    $imageId = (int) ($service['featured_image_id'] ?? 0);
    if ($imageId > 0) {
        $service['featured_image'] = get_media_asset($imageId);
    }
    $service['category'] = trim((string) ($service['category'] ?? 'general')) ?: 'general';
    $service['is_public'] = (int) ($service['is_public'] ?? 1);
    $service['is_featured'] = (int) ($service['is_featured'] ?? 0);
    return $service;
}

function infer_service_category(array $service): string
{
    $haystack = strtolower(trim((string) (($service['slug'] ?? '') . ' ' . ($service['name'] ?? ''))));
    if ($haystack === '') {
        return 'general';
    }

    if (str_contains($haystack, 'trademark') || str_contains($haystack, 'patent') || str_contains($haystack, 'ip')) {
        return 'trademark';
    }
    if (
        str_contains($haystack, 'website')
        || str_contains($haystack, 'graphics')
        || str_contains($haystack, 'seo')
        || str_contains($haystack, 'digital')
        || str_contains($haystack, 'domain')
        || str_contains($haystack, 'smtp')
        || str_contains($haystack, 'hosting')
    ) {
        return 'growth';
    }
    if (str_contains($haystack, 'training')) {
        return 'training';
    }
    if (str_contains($haystack, 'tracking')) {
        return 'operations';
    }
    if (
        str_contains($haystack, 'registration')
        || str_contains($haystack, 'company')
        || str_contains($haystack, 'trustees')
        || str_contains($haystack, 'launch')
        || str_contains($haystack, 'enterprise')
        || str_contains($haystack, 'business')
    ) {
        return 'business-registration';
    }

    return 'general';
}

function seed_service_marketing_defaults_if_needed(): void
{
    if (!schema_table_exists('services') || setting('service_marketing_seed_v1', '') === '1') {
        return;
    }

    $services = db_all('SELECT id, slug, name, description, short_description, category, is_public, is_featured, featured_bullets FROM services');
    foreach ($services as $service) {
        $shortDescription = trim((string) ($service['short_description'] ?? ''));
        if ($shortDescription === '') {
            $shortDescription = trim((string) strip_tags((string) ($service['description'] ?? '')));
            if ($shortDescription !== '') {
                $shortDescription = mb_substr($shortDescription, 0, 210);
            }
        }

        $category = trim((string) ($service['category'] ?? ''));
        if ($category === '' || $category === 'general') {
            $category = infer_service_category($service);
        }

        $bullets = decode_json_array_field($service['featured_bullets'] ?? []);
        if ($bullets === []) {
            $bullets = [
                'Structured remote submission',
                'Clear requirements and status updates',
                'Delivered through your portal dashboard',
            ];
        }

        $isFeatured = (int) ($service['is_featured'] ?? 0);
        if ($isFeatured === 0 && in_array((string) ($service['slug'] ?? ''), [
            'business-name-registration',
            'company-registration',
            'trademark',
            'launch-a-company-package',
        ], true)) {
            $isFeatured = 1;
        }

        db_execute(
            'UPDATE services
             SET short_description = :short_description,
                 category = :category,
                 is_public = 1,
                 is_featured = :is_featured,
                 featured_bullets = :featured_bullets
             WHERE id = :id',
            [
                'short_description' => $shortDescription,
                'category' => $category,
                'is_featured' => $isFeatured,
                'featured_bullets' => json_encode($bullets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'id' => (int) $service['id'],
            ]
        );
    }

    set_setting('service_marketing_seed_v1', '1');
}

function list_public_services_catalog(): array
{
    $rows = db_all(
        'SELECT * FROM services
         WHERE is_active = 1
           AND (is_public = 1 OR is_public IS NULL)
         ORDER BY is_featured DESC, name ASC'
    );

    return array_map('service_marketing_payload', $rows);
}

function list_public_service_categories(): array
{
    $rows = list_public_services_catalog();
    $groups = [];
    foreach ($rows as $service) {
        $category = (string) ($service['category'] ?? 'general');
        if (!isset($groups[$category])) {
            $groups[$category] = [
                'slug' => $category,
                'label' => ucwords(str_replace(['-', '_'], ' ', $category)),
                'count' => 0,
                'services' => [],
            ];
        }
        $groups[$category]['count']++;
        $groups[$category]['services'][] = $service;
    }

    uasort($groups, static function (array $left, array $right): int {
        return strcmp((string) $left['label'], (string) $right['label']);
    });

    return array_values($groups);
}

function featured_services_for_home(array $selectedIds = [], int $limit = 4): array
{
    $services = list_public_services_catalog();
    if ($selectedIds !== []) {
        $selectedMap = array_flip(array_map('intval', $selectedIds));
        $services = array_values(array_filter($services, static fn(array $row): bool => isset($selectedMap[(int) ($row['id'] ?? 0)])));
    } else {
        $featured = array_values(array_filter($services, static fn(array $row): bool => (int) ($row['is_featured'] ?? 0) === 1));
        if ($featured !== []) {
            $services = $featured;
        }
    }

    return array_slice($services, 0, max(1, $limit));
}

function admin_user_ids(): array
{
    $rows = db_all("SELECT id FROM users WHERE role = 'admin' AND status = 'active' AND account_state = 'active' AND deleted_at IS NULL ORDER BY id ASC");
    return array_map(static fn(array $row): int => (int) $row['id'], $rows);
}

function save_website_lead(array $input): int
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? ''));
    $companyName = trim((string) ($input['company_name'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $pageSlug = trim((string) ($input['page_slug'] ?? 'contact')) ?: 'contact';

    if ($fullName === '' || $email === '' || $message === '') {
        throw new RuntimeException('Name, email, and message are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid email address.');
    }

    db_execute(
        'INSERT INTO website_leads (full_name, email, phone, company_name, message, page_slug, status, created_at, updated_at)
         VALUES (:full_name, :email, :phone, :company_name, :message, :page_slug, :status, :created_at, :updated_at)',
        [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'company_name' => $companyName,
            'message' => $message,
            'page_slug' => $pageSlug,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $leadId = db_last_id();
    $targetUrl = url('frontend', ['slug' => 'contact']);
    notify_users(
        admin_user_ids(),
        'New website enquiry',
        $fullName . ' submitted a frontend contact enquiry.',
        $targetUrl
    );

    return $leadId;
}

function blog_post_author_name(array $row): string
{
    $fullName = trim((string) ($row['author_full_name'] ?? $row['full_name'] ?? ''));
    if ($fullName !== '') {
        return $fullName;
    }

    $firstName = trim((string) ($row['author_first_name'] ?? $row['first_name'] ?? ''));
    $lastName = trim((string) ($row['author_last_name'] ?? $row['last_name'] ?? ''));
    $combined = trim($firstName . ' ' . $lastName);
    if ($combined !== '') {
        return $combined;
    }

    $email = trim((string) ($row['author_email'] ?? $row['email'] ?? ''));
    if ($email !== '') {
        return $email;
    }

    return 'iWorks Team';
}

function blog_post_excerpt_from_html(string $html, int $limit = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($text) > $limit) {
        return rtrim((string) mb_substr($text, 0, $limit - 1)) . '…';
    }

    if (strlen($text) > $limit) {
        return rtrim(substr($text, 0, $limit - 1)) . '…';
    }

    return $text;
}

function make_unique_blog_slug(string $title, ?int $ignoreId = null): string
{
    $base = slugify($title);
    if ($base === '') {
        $base = 'insight';
    }

    $slug = $base;
    $counter = 2;
    while (true) {
        $params = ['slug' => $slug];
        $sql = 'SELECT id FROM blog_posts WHERE slug = :slug';
        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $existing = db_one($sql, $params);
        if (!$existing) {
            return $slug;
        }
        $slug = $base . '-' . $counter;
        $counter++;
    }
}

function blog_post_payload(array $row): array
{
    $row['author_name'] = blog_post_author_name($row);
    $row['tag'] = trim((string) ($row['tag'] ?? ''));
    $row['excerpt'] = trim((string) ($row['excerpt'] ?? ''));
    if ($row['excerpt'] === '') {
        $row['excerpt'] = blog_post_excerpt_from_html((string) ($row['body_html'] ?? ''));
    }
    $row['featured_image'] = null;
    $row['featured_image_id'] = (int) ($row['featured_image_id'] ?? 0);
    if ($row['featured_image_id'] > 0) {
        $row['featured_image'] = get_media_asset((int) $row['featured_image_id']);
    }
    $row['url'] = public_url('blog_post', ['slug' => (string) ($row['slug'] ?? '')]);
    return $row;
}

function list_blog_posts_public(int $limit = 12): array
{
    if (!schema_table_exists('blog_posts')) {
        return [];
    }

    $limit = max(1, min(60, $limit));
    $rows = db_all(
        'SELECT b.*, u.full_name AS author_full_name, u.first_name AS author_first_name,
                u.last_name AS author_last_name, u.email AS author_email
         FROM blog_posts b
         LEFT JOIN users u ON u.id = b.author_id
         WHERE b.status = :status
         ORDER BY COALESCE(b.published_at, b.updated_at, b.created_at) DESC, b.id DESC
         LIMIT ' . $limit,
        ['status' => 'published']
    );

    return array_map('blog_post_payload', $rows);
}

function get_blog_post_public_by_slug(string $slug): ?array
{
    if (!schema_table_exists('blog_posts')) {
        return null;
    }

    $row = db_one(
        'SELECT b.*, u.full_name AS author_full_name, u.first_name AS author_first_name,
                u.last_name AS author_last_name, u.email AS author_email
         FROM blog_posts b
         LEFT JOIN users u ON u.id = b.author_id
         WHERE b.slug = :slug AND b.status = :status
         LIMIT 1',
        [
            'slug' => trim($slug),
            'status' => 'published',
        ]
    );

    return $row ? blog_post_payload($row) : null;
}

function list_blog_posts_manage(int $userId, bool $isAdmin): array
{
    if (!schema_table_exists('blog_posts')) {
        return [];
    }

    $sql = 'SELECT b.*, u.full_name AS author_full_name, u.first_name AS author_first_name,
                   u.last_name AS author_last_name, u.email AS author_email
            FROM blog_posts b
            LEFT JOIN users u ON u.id = b.author_id';
    $params = [];
    if (!$isAdmin) {
        $sql .= ' WHERE b.author_id = :author_id';
        $params['author_id'] = $userId;
    }
    $sql .= ' ORDER BY COALESCE(b.published_at, b.updated_at, b.created_at) DESC, b.id DESC';

    return array_map('blog_post_payload', db_all($sql, $params));
}

function get_blog_post_manage(int $id, int $userId, bool $isAdmin): ?array
{
    if (!schema_table_exists('blog_posts')) {
        return null;
    }

    $sql = 'SELECT b.*, u.full_name AS author_full_name, u.first_name AS author_first_name,
                   u.last_name AS author_last_name, u.email AS author_email
            FROM blog_posts b
            LEFT JOIN users u ON u.id = b.author_id
            WHERE b.id = :id';
    $params = ['id' => $id];
    if (!$isAdmin) {
        $sql .= ' AND b.author_id = :author_id';
        $params['author_id'] = $userId;
    }
    $sql .= ' LIMIT 1';

    $row = db_one($sql, $params);
    return $row ? blog_post_payload($row) : null;
}

function save_blog_post(array $input, int $userId, bool $isAdmin, ?array $uploadFile = null): int
{
    if (!schema_table_exists('blog_posts')) {
        throw new RuntimeException('Blog module is not available.');
    }

    $postId = (int) ($input['post_id'] ?? 0);
    $existing = $postId > 0 ? get_blog_post_manage($postId, $userId, $isAdmin) : null;
    if ($postId > 0 && !$existing) {
        throw new RuntimeException('Blog post not found or access denied.');
    }

    $title = trim((string) ($input['title'] ?? ''));
    $tag = trim((string) ($input['tag'] ?? ''));
    $excerpt = trim((string) ($input['excerpt'] ?? ''));
    $bodyHtml = sanitize_limited_html((string) ($input['body_html'] ?? ''));
    $slugInput = trim((string) ($input['slug'] ?? ''));
    $status = in_array((string) ($input['status'] ?? 'draft'), ['draft', 'published'], true)
        ? (string) ($input['status'] ?? 'draft')
        : 'draft';

    if ($title === '') {
        throw new RuntimeException('Post title is required.');
    }

    if (trim(strip_tags($bodyHtml)) === '') {
        throw new RuntimeException('Post content is required.');
    }

    $slug = make_unique_blog_slug($slugInput !== '' ? $slugInput : $title, $postId > 0 ? $postId : null);
    if ($excerpt === '') {
        $excerpt = blog_post_excerpt_from_html($bodyHtml, 200);
    }

    $featuredImageId = (int) ($input['featured_image_id'] ?? 0);
    if (is_array($uploadFile) && (($uploadFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
        $featuredImageId = store_media_asset($userId, $uploadFile);
    }

    $now = now();
    $publishedAt = null;
    if ($status === 'published') {
        $publishedAt = $existing && trim((string) ($existing['published_at'] ?? '')) !== ''
            ? (string) $existing['published_at']
            : $now;
    }

    if ($existing) {
        db_execute(
            'UPDATE blog_posts
             SET slug = :slug,
                 title = :title,
                 tag = :tag,
                 excerpt = :excerpt,
                 body_html = :body_html,
                 featured_image_id = :featured_image_id,
                 status = :status,
                 published_at = :published_at,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'slug' => $slug,
                'title' => $title,
                'tag' => $tag !== '' ? $tag : null,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'body_html' => $bodyHtml,
                'featured_image_id' => $featuredImageId > 0 ? $featuredImageId : null,
                'status' => $status,
                'published_at' => $publishedAt,
                'updated_at' => $now,
                'id' => $postId,
            ]
        );
        return $postId;
    }

    db_execute(
        'INSERT INTO blog_posts (
            author_id, slug, title, tag, excerpt, body_html, featured_image_id, status, published_at, created_at, updated_at
         ) VALUES (
            :author_id, :slug, :title, :tag, :excerpt, :body_html, :featured_image_id, :status, :published_at, :created_at, :updated_at
         )',
        [
            'author_id' => $userId,
            'slug' => $slug,
            'title' => $title,
            'tag' => $tag !== '' ? $tag : null,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'body_html' => $bodyHtml,
            'featured_image_id' => $featuredImageId > 0 ? $featuredImageId : null,
            'status' => $status,
            'published_at' => $publishedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    return db_last_id();
}

function delete_blog_post(int $postId, int $userId, bool $isAdmin): void
{
    $post = get_blog_post_manage($postId, $userId, $isAdmin);
    if (!$post) {
        throw new RuntimeException('Blog post not found or access denied.');
    }

    db_execute('DELETE FROM blog_posts WHERE id = :id', ['id' => $postId]);
}

function seed_default_blog_posts_if_needed(): void
{
    if (!schema_table_exists('blog_posts')) {
        return;
    }

    if (setting('blog_posts_seed_v1', '') === '1') {
        return;
    }

    $count = (int) (db_one('SELECT COUNT(*) AS c FROM blog_posts')['c'] ?? 0);
    if ($count > 0) {
        set_setting('blog_posts_seed_v1', '1');
        return;
    }

    $authorId = admin_user_ids()[0] ?? 0;
    if ($authorId < 1) {
        return;
    }

    $defaults = [
        [
            'tag' => 'Business setup',
            'title' => 'What to prepare before CAC registration',
            'excerpt' => 'A practical checklist for founders getting ready to formalise their business before submitting a registration request.',
            'body_html' => '<p>Before you start a CAC filing, prepare the basic information that will reduce delays and back-and-forth corrections.</p><ol><li>Decide the business name or company name variants you want to submit.</li><li>Prepare the founder details, address, phone number, and email that will be used on the filing.</li><li>Confirm the business activity so the service description and tax treatment are accurate.</li><li>Upload any identification or supporting documents requested inside your portal job.</li><li>Pay the invoice and monitor comments in the job thread for any update request.</li></ol><p>Once the required details are complete, the iWorks team can move quickly from payment to processing.</p>',
        ],
        [
            'tag' => 'Trademark',
            'title' => 'When your brand should file for trademark protection',
            'excerpt' => 'Understand the right timing, required information, and submission flow for protecting your business name and mark.',
            'body_html' => '<p>Trademark filing works best when you are already clear about the name, logo, or phrase you want to protect.</p><ol><li>Confirm the exact name or visual mark to be filed.</li><li>Clarify the class or category your product or service falls under.</li><li>Prepare the applicant details that will appear on the filing.</li><li>Check whether supporting documents or authorisations are required.</li><li>Open the service in your portal and upload the requested files before submission.</li></ol><p>Starting early helps you avoid brand conflicts and keeps your growth assets aligned.</p>',
        ],
        [
            'tag' => 'Operations',
            'title' => 'How remote service delivery reduces execution delays',
            'excerpt' => 'Why structured comments, file uploads, invoice flow, and assigned ownership improve turnaround for service delivery.',
            'body_html' => '<p>Remote delivery works when every part of the process is visible and structured.</p><ol><li>Each service creates a clear job and invoice trail inside the portal.</li><li>Clients upload requirements in one place instead of scattering them across channels.</li><li>Agents and admins use comments and status changes to keep the client informed.</li><li>Update requests reopen only the missing part of the process instead of restarting everything.</li><li>Final delivery stays attached to the job for review, download, and closure.</li></ol><p>This reduces confusion, makes handovers easier, and gives both the client and the team one source of truth.</p>',
        ],
    ];

    foreach ($defaults as $post) {
        save_blog_post($post + ['status' => 'published'], $authorId, true, null);
    }

    set_setting('blog_posts_seed_v1', '1');
}

function list_website_leads(int $limit = 20): array
{
    $limit = max(1, min(100, $limit));
    return db_all('SELECT * FROM website_leads ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
}

function save_page_draft(string $slug, string $title, array $layout, array $seo, int $userId): void
{
    $page = get_page_admin_by_slug($slug);
    if (!$page) {
        throw new RuntimeException('Page not found.');
    }

    $layout = normalize_page_layout($layout);
    $errors = validate_page_layout($layout);
    if ($errors !== []) {
        throw new RuntimeException('Layout validation failed: ' . implode(' ', $errors));
    }

    $layoutJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $seoJson = json_encode($seo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    db_execute(
        'UPDATE pages SET title = :title, layout_json = :layout_json, seo_json = :seo_json, updated_by = :updated_by, updated_at = :updated_at
         WHERE slug = :slug',
        [
            'title' => $title,
            'layout_json' => $layoutJson,
            'seo_json' => $seoJson,
            'updated_by' => $userId,
            'updated_at' => now(),
            'slug' => $slug,
        ]
    );

    save_page_blocks_for_page((string) $page['id'], $layout);
    invalidate_page_cache($slug);
}

function publish_page(string $slug, int $userId, string $label = ''): void
{
    $page = get_page_admin_by_slug($slug);
    if (!$page) {
        throw new RuntimeException('Page not found.');
    }

    if (trim((string) ($page['published_layout_json'] ?? '')) !== '') {
        db_execute(
            'INSERT INTO page_revisions (page_id, layout_json, seo_json, label, created_by, created_at)
             VALUES (:page_id, :layout_json, :seo_json, :label, :created_by, :created_at)',
            [
                'page_id' => (string) $page['id'],
                'layout_json' => (string) ($page['published_layout_json'] ?? ''),
                'seo_json' => (string) ($page['seo_json'] ?? ''),
                'label' => $label !== '' ? $label : 'Published copy',
                'created_by' => $userId,
                'created_at' => now(),
            ]
        );
    }

    db_execute(
        "UPDATE pages
         SET status = 'published',
             published_layout_json = layout_json,
             published_at = :published_at,
             updated_by = :updated_by,
             updated_at = :updated_at
         WHERE slug = :slug",
        [
            'published_at' => now(),
            'updated_by' => $userId,
            'updated_at' => now(),
            'slug' => $slug,
        ]
    );

    save_page_blocks_for_page((string) $page['id'], is_array($page['layout'] ?? null) ? $page['layout'] : []);
    invalidate_page_cache($slug);
}

function list_page_revisions(string $pageId, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    return db_all(
        'SELECT * FROM page_revisions WHERE page_id = :page_id ORDER BY created_at DESC, id DESC LIMIT ' . $limit,
        ['page_id' => $pageId]
    );
}

function rollback_page_revision(string $slug, int $revisionId, int $userId): void
{
    $page = get_page_admin_by_slug($slug);
    if (!$page) {
        throw new RuntimeException('Page not found.');
    }

    $revision = db_one(
        'SELECT * FROM page_revisions WHERE id = :id AND page_id = :page_id LIMIT 1',
        ['id' => $revisionId, 'page_id' => (string) $page['id']]
    );
    if (!$revision) {
        throw new RuntimeException('Revision not found.');
    }

    db_execute(
        "UPDATE pages
         SET status = 'published',
             layout_json = :layout_json,
             published_layout_json = :published_layout_json,
             seo_json = :seo_json,
             published_at = :published_at,
             updated_by = :updated_by,
             updated_at = :updated_at
         WHERE slug = :slug",
        [
            'layout_json' => (string) ($revision['layout_json'] ?? ''),
            'published_layout_json' => (string) ($revision['layout_json'] ?? ''),
            'seo_json' => (string) ($revision['seo_json'] ?? ''),
            'published_at' => now(),
            'updated_by' => $userId,
            'updated_at' => now(),
            'slug' => $slug,
        ]
    );

    $layout = normalize_page_layout(page_decode_json((string) ($revision['layout_json'] ?? '')));
    save_page_blocks_for_page((string) $page['id'], $layout);
    invalidate_page_cache($slug);
}

function list_media_assets(int $limit = 200): array
{
    $limit = max(1, min(500, $limit));
    if (schema_table_exists('media_library')) {
        $rows = db_all('SELECT * FROM media_library ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        return array_map('normalize_media_asset_record', $rows);
    }
    $rows = db_all('SELECT * FROM media_assets ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    return array_map('normalize_media_asset_record', $rows);
}

function normalize_media_asset_record(?array $asset): ?array
{
    if (!$asset) {
        return null;
    }

    $storageKey = trim((string) ($asset['storage_key'] ?? ''));
    $url = trim((string) ($asset['url'] ?? ''));
    $relativeUrl = '';

    if ($url !== '') {
        $parsedPath = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if ($parsedPath !== '' && !preg_match('#^https?://#i', $url)) {
            $relativeUrl = ltrim($url, '/');
        } elseif ($parsedPath !== '') {
            $relativeUrl = ltrim($parsedPath, '/');
        }
    }

    if ($relativeUrl === '' && $storageKey !== '') {
        $relativeUrl = 'assets/uploads/' . ltrim($storageKey, '/');
    }

    if ($relativeUrl !== '' && !preg_match('#^https?://#i', $url)) {
        $url = public_absolute_url($relativeUrl);
    }

    $asset['relative_url'] = $relativeUrl;
    $asset['url'] = $url;

    return $asset;
}

function store_media_asset(int $userId, array $file): int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Media upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image too large. Max 5MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $mime = $tmp !== '' ? (mime_content_type($tmp) ?: '') : '';
    if (!str_starts_with($mime, 'image/')) {
        throw new RuntimeException('Only image files are allowed.');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $original = $file['name'] ?? 'image.bin';
    $label = trim(pathinfo($original, PATHINFO_FILENAME));
    if ($label === '') {
        $label = trim($original);
    }
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Invalid image format. Allowed: jpg, jpeg, png, webp, gif.');
    }

    ensure_public_upload_directory();
    $targetDir = public_upload_directory() . '/frontend';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $storedName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $targetDir . '/' . $storedName;

    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Failed to save image.');
    }

    $dimensions = @getimagesize($destination);
    $width = $dimensions ? (int) ($dimensions[0] ?? 0) : null;
    $height = $dimensions ? (int) ($dimensions[1] ?? 0) : null;

    $storageKey = 'frontend/' . $storedName;
    $url = 'assets/uploads/frontend/' . $storedName;

    $payload = [
        'user_id' => $userId,
        'storage_key' => $storageKey,
        'label' => $label,
        'url' => $url,
        'mime' => $mime,
        'size_bytes' => (int) ($file['size'] ?? 0),
        'width' => $width,
        'height' => $height,
        'created_at' => now(),
    ];

    $hasLabelColumn = schema_table_exists('media_library') && schema_column_exists('media_library', 'label');
    if (schema_table_exists('media_library')) {
        db_execute(
            $hasLabelColumn
                ? 'INSERT INTO media_library (user_id, storage_key, label, url, mime, size_bytes, width, height, created_at)
                   VALUES (:user_id, :storage_key, :label, :url, :mime, :size_bytes, :width, :height, :created_at)'
                : 'INSERT INTO media_library (user_id, storage_key, url, mime, size_bytes, width, height, created_at)
                   VALUES (:user_id, :storage_key, :url, :mime, :size_bytes, :width, :height, :created_at)',
            $hasLabelColumn ? $payload : array_diff_key($payload, ['label' => true])
        );
    } else {
        $hasLegacyLabel = schema_column_exists('media_assets', 'label');
        db_execute(
            $hasLegacyLabel
                ? 'INSERT INTO media_assets (user_id, storage_key, label, url, mime, size_bytes, width, height, created_at)
                   VALUES (:user_id, :storage_key, :label, :url, :mime, :size_bytes, :width, :height, :created_at)'
                : 'INSERT INTO media_assets (user_id, storage_key, url, mime, size_bytes, width, height, created_at)
                   VALUES (:user_id, :storage_key, :url, :mime, :size_bytes, :width, :height, :created_at)',
            $hasLegacyLabel ? $payload : array_diff_key($payload, ['label' => true])
        );
    }

    return db_last_id();
}

function update_media_label(int $id, string $label): void
{
    if ($id < 1) {
        throw new RuntimeException('Invalid media selection.');
    }

    $label = trim($label);
    if ($label === '') {
        throw new RuntimeException('Media name cannot be empty.');
    }

    if (schema_table_exists('media_library') && schema_column_exists('media_library', 'label')) {
        db_execute('UPDATE media_library SET label = :label WHERE id = :id', ['label' => $label, 'id' => $id]);
        return;
    }

    if (schema_table_exists('media_assets') && schema_column_exists('media_assets', 'label')) {
        db_execute('UPDATE media_assets SET label = :label WHERE id = :id', ['label' => $label, 'id' => $id]);
    }
}

function get_media_asset(int $id): ?array
{
    if (schema_table_exists('media_library')) {
        return normalize_media_asset_record(
            db_one('SELECT * FROM media_library WHERE id = :id LIMIT 1', ['id' => $id])
        );
    }
    return normalize_media_asset_record(
        db_one('SELECT * FROM media_assets WHERE id = :id LIMIT 1', ['id' => $id])
    );
}

function delete_media_asset(int $id): void
{
    if ($id < 1) {
        throw new RuntimeException('Invalid media selection.');
    }

    $asset = get_media_asset($id);
    if (!$asset) {
        throw new RuntimeException('Media file not found.');
    }

    $candidateFiles = [];
    $storageKey = trim((string) ($asset['storage_key'] ?? ''));
    $relativeUrl = trim((string) ($asset['relative_url'] ?? ''));
    $url = trim((string) ($asset['url'] ?? ''));

    if ($storageKey !== '') {
        $candidateFiles[] = public_upload_directory() . '/' . ltrim($storageKey, '/');
    }

    if ($relativeUrl === '' && $url !== '') {
        $relativeUrl = ltrim((string) (parse_url($url, PHP_URL_PATH) ?? ''), '/');
    }

    if ($relativeUrl !== '' && str_starts_with($relativeUrl, 'assets/uploads/')) {
        $candidateFiles[] = __DIR__ . '/../' . ltrim($relativeUrl, '/');
    }

    foreach (array_values(array_unique($candidateFiles)) as $candidatePath) {
        if ($candidatePath === '' || !is_file($candidatePath)) {
            continue;
        }
        @unlink($candidatePath);
    }

    if (schema_table_exists('media_library')) {
        db_execute('DELETE FROM media_library WHERE id = :id', ['id' => $id]);
    } else {
        db_execute('DELETE FROM media_assets WHERE id = :id', ['id' => $id]);
    }

    $partnerLogoIds = website_partner_logo_media_ids();
    if (in_array($id, $partnerLogoIds, true)) {
        $partnerLogoIds = array_values(array_filter($partnerLogoIds, static fn (int $mediaId): bool => $mediaId !== $id));
        set_setting('website_partner_logo_media_ids', json_encode($partnerLogoIds, JSON_UNESCAPED_SLASHES));
    }
}

function website_partner_logo_media_ids(): array
{
    $decoded = decode_json_array_field(setting('website_partner_logo_media_ids', '[]'));
    $ids = [];

    foreach ($decoded as $item) {
        $mediaId = 0;
        if (is_array($item)) {
            $mediaId = (int) ($item['imageId'] ?? $item['media_id'] ?? $item['id'] ?? 0);
        } else {
            $mediaId = (int) $item;
        }

        if ($mediaId > 0) {
            $ids[] = $mediaId;
        }
    }

    return array_values(array_unique($ids));
}

function website_partner_logos(): array
{
    $items = [];

    foreach (website_partner_logo_media_ids() as $mediaId) {
        $asset = get_media_asset($mediaId);
        if (!$asset) {
            continue;
        }

        $source = (string) ($asset['storage_key'] ?? $asset['url'] ?? ('logo-' . $mediaId));
        $label = trim((string) pathinfo(basename($source), PATHINFO_FILENAME));
        $label = str_replace(['-', '_'], ' ', $label);

        $items[] = [
            'imageId' => $mediaId,
            'title' => $label !== '' ? ucwords($label) : ('Partner Logo ' . $mediaId),
            'text' => '',
        ];
    }

    return $items;
}

function add_website_partner_logos(int $userId, ?array $rawFiles): int
{
    $files = normalize_job_uploaded_files_payload($rawFiles);
    if ($files === []) {
        throw new RuntimeException('Select at least one logo image to upload.');
    }

    $ids = website_partner_logo_media_ids();
    $added = 0;

    foreach ($files as $file) {
        $mediaId = store_media_asset($userId, $file);
        if ($mediaId <= 0) {
            continue;
        }

        $ids[] = $mediaId;
        $added++;
    }

    if ($added <= 0) {
        throw new RuntimeException('No logos were uploaded.');
    }

    set_setting(
        'website_partner_logo_media_ids',
        (string) json_encode(array_values(array_unique(array_map('intval', $ids))), JSON_UNESCAPED_SLASHES)
    );

    return $added;
}

function remove_website_partner_logo(int $mediaId): void
{
    if ($mediaId <= 0) {
        throw new RuntimeException('Invalid logo selected.');
    }

    $ids = array_values(array_filter(
        website_partner_logo_media_ids(),
        static fn(int $existingId): bool => $existingId !== $mediaId
    ));

    set_setting(
        'website_partner_logo_media_ids',
        (string) json_encode($ids, JSON_UNESCAPED_SLASHES)
    );
}

function get_service_by_slug(string $slug): ?array
{
    $service = db_one(
        'SELECT * FROM services WHERE slug = :slug AND is_active = 1 AND (is_public = 1 OR is_public IS NULL) LIMIT 1',
        ['slug' => $slug]
    );
    return $service ? service_marketing_payload($service) : null;
}

function find_public_job_track(string $jobCode, string $identifier): ?array
{
    $identifier = strtolower(trim($identifier));
    if ($identifier === '') {
        return null;
    }

    $job = db_one(
        'SELECT j.*, s.name AS service_name, c.full_name AS client_name, c.email AS client_email, c.phone AS client_phone
         FROM jobs j
         JOIN services s ON s.id = j.service_id
         JOIN users c ON c.id = j.client_id
         WHERE j.job_code = :job_code
         LIMIT 1',
        ['job_code' => strtoupper(trim($jobCode))]
    );

    if (!$job) {
        return null;
    }

    $matchEmail = strtolower((string) ($job['client_email'] ?? '')) === $identifier;
    $matchPhone = preg_replace('/\D+/', '', (string) ($job['client_phone'] ?? '')) === preg_replace('/\D+/', '', $identifier);

    if (!$matchEmail && !$matchPhone) {
        return null;
    }

    return $job;
}

function crypto_supported_assets(): array
{
    return ['USDT', 'LTC', 'BTC'];
}

function normalize_crypto_asset(string $asset): string
{
    $asset = strtoupper(trim($asset));
    if (!in_array($asset, crypto_supported_assets(), true)) {
        return 'USDT';
    }

    return $asset;
}

function crypto_asset_precision(string $asset, bool $display = false): int
{
    $asset = normalize_crypto_asset($asset);
    if ($asset === 'USDT') {
        return $display ? 2 : 6;
    }

    return 8;
}

function normalize_pay_method(string $payMethod): string
{
    $payMethod = strtolower(trim($payMethod));
    if (!in_array($payMethod, ['card', 'bank', 'crypto'], true)) {
        return 'card';
    }

    return $payMethod;
}

function pay_method_from_method_code(string $methodCode): string
{
    $methodCode = strtolower(trim($methodCode));
    if ($methodCode === 'bank_transfer') {
        return 'bank';
    }
    if ($methodCode === 'crypto_wallet') {
        return 'crypto';
    }

    return 'card';
}

function normalize_crypto_network(string $asset, ?string $network): ?string
{
    $asset = normalize_crypto_asset($asset);
    if ($asset !== 'USDT') {
        return null;
    }

    $network = strtoupper(trim((string) $network));
    if (!in_array($network, ['TRC20', 'ERC20'], true)) {
        return 'TRC20';
    }

    return $network;
}

function crypto_rate_refresh_minutes(): int
{
    $value = (int) setting('crypto_rates_refresh_minutes', '10');
    return max(5, min(30, $value));
}

function crypto_default_rate_usd_per_asset(string $asset): float
{
    return match (normalize_crypto_asset($asset)) {
        'BTC' => 65000.0,
        'LTC' => 90.0,
        default => 1.0,
    };
}

function crypto_fallback_rates(): array
{
    $rates = [];
    foreach (crypto_supported_assets() as $asset) {
        $rates[$asset] = crypto_default_rate_usd_per_asset($asset);
    }

    return $rates;
}

function fetch_coingecko_crypto_rates_usd(): array
{
    [$statusCode, $responseBody] = gateway_http_request(
        'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,litecoin,tether&vs_currencies=usd',
        'GET',
        ['Accept: application/json'],
        null,
        15
    );

    if ($statusCode >= 400) {
        throw new RuntimeException('CoinGecko rate endpoint unavailable.');
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid CoinGecko response.');
    }

    $mapped = [
        'BTC' => (float) ($decoded['bitcoin']['usd'] ?? 0),
        'LTC' => (float) ($decoded['litecoin']['usd'] ?? 0),
        'USDT' => (float) ($decoded['tether']['usd'] ?? 0),
    ];

    $rates = [];
    foreach (crypto_supported_assets() as $asset) {
        $rate = (float) ($mapped[$asset] ?? 0);
        if ($rate > 0) {
            $rates[$asset] = $rate;
        }
    }

    if (!$rates) {
        throw new RuntimeException('CoinGecko did not return valid rates.');
    }

    return $rates;
}

function fetch_cryptocompare_crypto_rates_usd(): array
{
    [$statusCode, $responseBody] = gateway_http_request(
        'https://min-api.cryptocompare.com/data/pricemulti?fsyms=BTC,LTC,USDT&tsyms=USD',
        'GET',
        ['Accept: application/json'],
        null,
        15
    );

    if ($statusCode >= 400) {
        throw new RuntimeException('CryptoCompare rate endpoint unavailable.');
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid CryptoCompare response.');
    }

    $rates = [];
    foreach (crypto_supported_assets() as $asset) {
        $rate = (float) ($decoded[$asset]['USD'] ?? 0);
        if ($rate > 0) {
            $rates[$asset] = $rate;
        }
    }

    if (!$rates) {
        throw new RuntimeException('CryptoCompare did not return valid rates.');
    }

    return $rates;
}

function upsert_crypto_rates(array $rates, string $source): void
{
    ensure_crypto_schema_upgrades();

    foreach (crypto_supported_assets() as $asset) {
        $assetRate = (float) ($rates[$asset] ?? 0);
        if ($assetRate <= 0) {
            continue;
        }

        db_execute(
            'INSERT INTO crypto_rates (base_currency, asset_symbol, rate_usd_per_asset, source, last_updated_at, created_at, updated_at)
             VALUES (:base_currency, :asset_symbol, :rate_usd_per_asset, :source, :last_updated_at, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
               rate_usd_per_asset = VALUES(rate_usd_per_asset),
               source = VALUES(source),
               last_updated_at = VALUES(last_updated_at),
               updated_at = VALUES(updated_at)',
            [
                'base_currency' => 'USD',
                'asset_symbol' => $asset,
                'rate_usd_per_asset' => $assetRate,
                'source' => $source,
                'last_updated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

function list_cached_crypto_rates(): array
{
    ensure_crypto_schema_upgrades();

    $rows = db_all(
        'SELECT asset_symbol, rate_usd_per_asset, source, last_updated_at
         FROM crypto_rates
         WHERE base_currency = :base_currency',
        ['base_currency' => 'USD']
    );

    $rates = [];
    $lastUpdatedAt = null;
    $source = 'cache';
    foreach ($rows as $row) {
        $asset = normalize_crypto_asset((string) ($row['asset_symbol'] ?? ''));
        $rate = (float) ($row['rate_usd_per_asset'] ?? 0);
        if ($rate <= 0) {
            continue;
        }
        $rates[$asset] = $rate;

        $candidate = (string) ($row['last_updated_at'] ?? '');
        if ($candidate !== '' && ($lastUpdatedAt === null || strcmp($candidate, $lastUpdatedAt) > 0)) {
            $lastUpdatedAt = $candidate;
        }

        $rowSource = trim((string) ($row['source'] ?? ''));
        if ($rowSource !== '') {
            $source = $rowSource;
        }
    }

    return [
        'rates' => $rates,
        'last_updated_at' => $lastUpdatedAt,
        'source' => $source,
    ];
}

function save_daily_crypto_rate_snapshot(array $rates, string $source): void
{
    ensure_crypto_schema_upgrades();
    $today = date('Y-m-d');

    foreach ($rates as $asset => $rate) {
        $asset = normalize_crypto_asset((string) $asset);
        $rate = (float) $rate;
        if ($rate <= 0) {
            continue;
        }

        db_execute(
            'INSERT INTO crypto_rate_snapshots (snapshot_date, base_currency, asset_symbol, rate_usd_per_asset, source, created_at)
             VALUES (:snapshot_date, :base_currency, :asset_symbol, :rate_usd_per_asset, :source, :created_at)
             ON DUPLICATE KEY UPDATE rate_usd_per_asset = VALUES(rate_usd_per_asset), source = VALUES(source)',
            [
                'snapshot_date' => $today,
                'base_currency' => 'USD',
                'asset_symbol' => $asset,
                'rate_usd_per_asset' => $rate,
                'source' => $source,
                'created_at' => now(),
            ]
        );
    }
}

function refresh_crypto_rates_cache(bool $force = false): array
{
    ensure_crypto_schema_upgrades();

    $refreshWindow = crypto_rate_refresh_minutes() * 60;
    $cached = list_cached_crypto_rates();
    $cachedRates = $cached['rates'];
    $cachedLastUpdated = (string) ($cached['last_updated_at'] ?? '');
    $cachedTimestamp = $cachedLastUpdated !== '' ? strtotime($cachedLastUpdated) : false;
    $hasAllCached = true;
    foreach (crypto_supported_assets() as $asset) {
        if (!isset($cachedRates[$asset]) || (float) $cachedRates[$asset] <= 0) {
            $hasAllCached = false;
            break;
        }
    }

    $cacheFresh = false;
    if ($cachedTimestamp !== false && $hasAllCached) {
        $cacheFresh = (time() - (int) $cachedTimestamp) <= $refreshWindow;
    }

    if (!$force && $cacheFresh) {
        return [
            'rates' => $cachedRates,
            'source' => 'cache',
            'last_updated_at' => $cachedLastUpdated,
            'warning' => '',
        ];
    }

    $providerErrors = [];
    $liveRates = [];
    $source = '';

    try {
        $liveRates = fetch_coingecko_crypto_rates_usd();
        $source = 'coingecko';
    } catch (Throwable $e) {
        $providerErrors[] = $e->getMessage();
    }

    if (!$liveRates) {
        try {
            $liveRates = fetch_cryptocompare_crypto_rates_usd();
            $source = 'cryptocompare';
        } catch (Throwable $e) {
            $providerErrors[] = $e->getMessage();
        }
    }

    foreach (crypto_supported_assets() as $asset) {
        if (isset($liveRates[$asset]) && (float) $liveRates[$asset] > 0) {
            continue;
        }

        if (isset($cachedRates[$asset]) && (float) $cachedRates[$asset] > 0) {
            $liveRates[$asset] = (float) $cachedRates[$asset];
        } else {
            $liveRates[$asset] = crypto_default_rate_usd_per_asset($asset);
        }
    }

    if ($source !== '') {
        upsert_crypto_rates($liveRates, $source);
        save_daily_crypto_rate_snapshot($liveRates, $source);

        return [
            'rates' => $liveRates,
            'source' => 'live',
            'last_updated_at' => now(),
            'warning' => '',
        ];
    }

    if ($hasAllCached) {
        $warning = 'Live crypto rates unavailable. Showing last cached rates.';
        if ($cachedLastUpdated !== '') {
            $warning .= ' Last updated: ' . $cachedLastUpdated . '.';
        }
        if ($providerErrors) {
            $warning .= ' Provider errors: ' . implode(' | ', $providerErrors);
        }

        return [
            'rates' => $cachedRates,
            'source' => 'stale_cache',
            'last_updated_at' => $cachedLastUpdated,
            'warning' => $warning,
        ];
    }

    $fallback = crypto_fallback_rates();
    upsert_crypto_rates($fallback, 'fallback');
    save_daily_crypto_rate_snapshot($fallback, 'fallback');

    return [
        'rates' => $fallback,
        'source' => 'fallback',
        'last_updated_at' => now(),
        'warning' => 'Live crypto rates unavailable. Using fallback reference rates.',
    ];
}

function usd_amount_from_payable(float $amount, string $payCurrency, ?float $fxRate = null): float
{
    $payCurrency = normalize_payment_currency($payCurrency);
    $fxRate = $fxRate !== null && $fxRate > 0 ? $fxRate : fx_ngn_per_usd();

    if ($payCurrency === 'NGN') {
        return round($amount / $fxRate, 6);
    }

    return round($amount, 6);
}

function crypto_amount_from_usd(float $usdAmount, string $asset, float $rateUsdPerAsset): float
{
    $asset = normalize_crypto_asset($asset);
    if ($rateUsdPerAsset <= 0) {
        $rateUsdPerAsset = crypto_default_rate_usd_per_asset($asset);
    }

    $raw = $usdAmount / $rateUsdPerAsset;
    $precision = crypto_asset_precision($asset, false);
    return round($raw, $precision);
}

function crypto_quote_from_rates(float $payableAmount, string $payCurrency, array $rates, ?float $fxRate = null): array
{
    $payCurrency = normalize_payment_currency($payCurrency);
    $fxRateUsed = $fxRate !== null && $fxRate > 0 ? $fxRate : fx_ngn_per_usd();
    $usdAmount = usd_amount_from_payable($payableAmount, $payCurrency, $fxRateUsed);

    $assets = [];
    foreach (crypto_supported_assets() as $asset) {
        $rate = (float) ($rates[$asset] ?? 0);
        if ($rate <= 0) {
            $rate = crypto_default_rate_usd_per_asset($asset);
        }
        $amountInternal = crypto_amount_from_usd($usdAmount, $asset, $rate);
        $displayPrecision = crypto_asset_precision($asset, true);
        $assets[$asset] = [
            'asset' => $asset,
            'rate_usd_per_asset' => round($rate, 10),
            'amount_internal' => $amountInternal,
            'amount_display' => round($amountInternal, $displayPrecision),
            'amount_display_formatted' => number_format($amountInternal, $displayPrecision, '.', ''),
            'network_options' => $asset === 'USDT' ? ['TRC20', 'ERC20'] : [],
        ];
    }

    return [
        'payable_amount' => round($payableAmount, 2),
        'pay_currency' => $payCurrency,
        'fx_rate_used' => round($fxRateUsed, 6),
        'usd_amount' => round($usdAmount, 6),
        'assets' => $assets,
    ];
}

function get_crypto_checkout_quote(float $payableAmount, string $payCurrency, bool $forceRefresh = false): array
{
    $rateState = refresh_crypto_rates_cache($forceRefresh);
    $quote = crypto_quote_from_rates($payableAmount, $payCurrency, is_array($rateState['rates'] ?? null) ? $rateState['rates'] : []);

    return $quote + [
        'rates_source' => (string) ($rateState['source'] ?? 'fallback'),
        'rates_last_updated_at' => (string) ($rateState['last_updated_at'] ?? ''),
        'rates_warning' => (string) ($rateState['warning'] ?? ''),
        'fx_last_updated_at' => (string) (setting_updated_at('fx_ngn_per_usd') ?? ''),
    ];
}

function apply_invoice_rate_context(int $invoiceId, array $context): void
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    $payload = [
        'pay_currency' => normalize_payment_currency((string) ($context['pay_currency'] ?? ($invoice['currency'] ?? default_currency()))),
        'pay_method' => normalize_pay_method((string) ($context['pay_method'] ?? 'card')),
        'fx_rate_used' => isset($context['fx_rate_used']) ? (float) $context['fx_rate_used'] : null,
        'crypto_asset' => isset($context['crypto_asset']) && (string) $context['crypto_asset'] !== ''
            ? normalize_crypto_asset((string) $context['crypto_asset'])
            : null,
        'crypto_rate_used' => isset($context['crypto_rate_used']) ? (float) $context['crypto_rate_used'] : null,
        'crypto_amount_due' => isset($context['crypto_amount_due']) ? (float) $context['crypto_amount_due'] : null,
        'crypto_network' => isset($context['crypto_network']) && (string) $context['crypto_network'] !== ''
            ? (string) $context['crypto_network']
            : null,
        'rate_locked_at' => (string) ($context['rate_locked_at'] ?? now()),
    ];

    if ($payload['pay_method'] !== 'crypto') {
        $payload['crypto_asset'] = null;
        $payload['crypto_rate_used'] = null;
        $payload['crypto_amount_due'] = null;
        $payload['crypto_network'] = null;
    }

    db_execute(
        'UPDATE invoices
         SET pay_currency = :pay_currency, pay_method = :pay_method, fx_rate_used = :fx_rate_used,
             crypto_asset = :crypto_asset, crypto_rate_used = :crypto_rate_used, crypto_amount_due = :crypto_amount_due,
             crypto_network = :crypto_network, rate_locked_at = :rate_locked_at
         WHERE id = :id',
        [
            'pay_currency' => $payload['pay_currency'],
            'pay_method' => $payload['pay_method'],
            'fx_rate_used' => $payload['fx_rate_used'],
            'crypto_asset' => $payload['crypto_asset'],
            'crypto_rate_used' => $payload['crypto_rate_used'],
            'crypto_amount_due' => $payload['crypto_amount_due'],
            'crypto_network' => $payload['crypto_network'],
            'rate_locked_at' => $payload['rate_locked_at'],
            'id' => $invoiceId,
        ]
    );

    $jobUpdateParams = [
        'pay_currency' => $payload['pay_currency'],
        'pay_method' => $payload['pay_method'],
        'fx_rate_used' => $payload['fx_rate_used'],
        'crypto_asset' => $payload['crypto_asset'],
        'crypto_rate_used' => $payload['crypto_rate_used'],
        'crypto_amount_due' => $payload['crypto_amount_due'],
        'crypto_network' => $payload['crypto_network'],
        'rate_locked_at' => $payload['rate_locked_at'],
        'updated_at' => now(),
    ];

    $group = trim((string) ($invoice['payment_group'] ?? ''));
    if ($group !== '') {
        db_execute(
            'UPDATE jobs
             SET pay_currency = :pay_currency, pay_method = :pay_method, fx_rate_used = :fx_rate_used,
                 crypto_asset = :crypto_asset, crypto_rate_used = :crypto_rate_used, crypto_amount_due = :crypto_amount_due,
                 crypto_network = :crypto_network, rate_locked_at = :rate_locked_at, updated_at = :updated_at
             WHERE payment_group = :payment_group',
            $jobUpdateParams + ['payment_group' => $group]
        );
        return;
    }

    db_execute(
        'UPDATE jobs
         SET pay_currency = :pay_currency, pay_method = :pay_method, fx_rate_used = :fx_rate_used,
             crypto_asset = :crypto_asset, crypto_rate_used = :crypto_rate_used, crypto_amount_due = :crypto_amount_due,
             crypto_network = :crypto_network, rate_locked_at = :rate_locked_at, updated_at = :updated_at
         WHERE id = :id',
        $jobUpdateParams + ['id' => (int) $invoice['job_id']]
    );
}

function build_crypto_lock_context_from_quote(array $quote, string $asset, ?string $network = null): array
{
    $asset = normalize_crypto_asset($asset);
    $assetQuote = is_array($quote['assets'][$asset] ?? null) ? $quote['assets'][$asset] : null;
    if (!$assetQuote) {
        throw new RuntimeException('Crypto quote for selected asset is not available.');
    }

    return [
        'pay_currency' => normalize_payment_currency((string) ($quote['pay_currency'] ?? 'NGN')),
        'pay_method' => 'crypto',
        'fx_rate_used' => (float) ($quote['fx_rate_used'] ?? fx_ngn_per_usd()),
        'crypto_asset' => $asset,
        'crypto_rate_used' => (float) ($assetQuote['rate_usd_per_asset'] ?? 0),
        'crypto_amount_due' => (float) ($assetQuote['amount_internal'] ?? 0),
        'crypto_network' => normalize_crypto_network($asset, $network),
        'rate_locked_at' => now(),
    ];
}

function lock_invoice_crypto_context(int $invoiceId, string $asset, ?string $network = null): array
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    $payCurrency = normalize_payment_currency((string) ($invoice['currency'] ?? default_currency()));
    $quote = get_crypto_checkout_quote((float) ($invoice['amount'] ?? 0), $payCurrency, false);
    $context = build_crypto_lock_context_from_quote($quote, $asset, $network);

    apply_invoice_rate_context($invoiceId, $context);

    return $context + [
        'usd_amount' => (float) ($quote['usd_amount'] ?? 0),
        'rates_source' => (string) ($quote['rates_source'] ?? ''),
        'rates_last_updated_at' => (string) ($quote['rates_last_updated_at'] ?? ''),
        'rates_warning' => (string) ($quote['rates_warning'] ?? ''),
    ];
}

function list_services(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM services';
    $params = [];

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY is_featured DESC, name ASC';

    return array_map('service_marketing_payload', db_all($sql, $params));
}

function get_service(int $id): ?array
{
    $service = db_one('SELECT * FROM services WHERE id = :id LIMIT 1', ['id' => $id]);
    return $service ? service_marketing_payload($service) : null;
}

function ensure_system_custom_service(): int
{
    $existing = db_one('SELECT id FROM services WHERE slug = :slug LIMIT 1', ['slug' => 'custom-service-system']);
    if ($existing) {
        return (int) $existing['id'];
    }

    db_execute(
        'INSERT INTO services (
            name, slug, description, short_description, price, currency, commission_percent, tax_percent,
            category, featured_bullets, featured_image_id, is_active, is_public, is_featured, created_at
         ) VALUES (
            :name, :slug, :description, :short_description, :price, :currency, :commission_percent, :tax_percent,
            :category, :featured_bullets, :featured_image_id, :is_active, :is_public, :is_featured, :created_at
         )',
        [
            'name' => 'Custom Service',
            'slug' => 'custom-service-system',
            'description' => 'System placeholder for admin-created custom service orders.',
            'short_description' => 'System placeholder for admin-created custom service orders.',
            'price' => 0,
            'currency' => default_currency(),
            'commission_percent' => 0,
            'tax_percent' => 0,
            'category' => 'custom',
            'featured_bullets' => json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'featured_image_id' => null,
            'is_active' => 1,
            'is_public' => 0,
            'is_featured' => 0,
            'created_at' => now(),
        ]
    );

    return db_last_id();
}

function save_service(array $input, ?int $id = null): int
{
    $currency = strtoupper(trim($input['currency'] ?? default_currency()));
    if (!in_array($currency, currency_options(), true)) {
        $currency = default_currency();
    }

    $payload = [
        'name' => trim($input['name']),
        'slug' => slugify($input['name']),
        'description' => sanitize_limited_html((string) ($input['description'] ?? '')),
        'short_description' => trim((string) ($input['short_description'] ?? '')),
        'price' => (float) ($input['price'] ?? 0),
        'currency' => $currency,
        'commission_percent' => (float) ($input['commission_percent'] ?? 0),
        'tax_percent' => max(0.0, min(100.0, (float) ($input['tax_percent'] ?? 0))),
        'category' => trim((string) ($input['category'] ?? 'general')) ?: 'general',
        'featured_bullets' => json_encode(
            array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($input['featured_bullets_text'] ?? '')) ?: []))),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ),
        'featured_image_id' => (int) ($input['featured_image_id'] ?? 0) ?: null,
        'is_active' => !empty($input['is_active']) ? 1 : 0,
        'is_public' => !empty($input['is_public']) ? 1 : 0,
        'is_featured' => !empty($input['is_featured']) ? 1 : 0,
    ];

    if ($id === null) {
        db_execute(
            'INSERT INTO services (
                name, slug, description, short_description, price, currency, commission_percent, tax_percent,
                category, featured_bullets, featured_image_id, is_active, is_public, is_featured, created_at
             ) VALUES (
                :name, :slug, :description, :short_description, :price, :currency, :commission_percent, :tax_percent,
                :category, :featured_bullets, :featured_image_id, :is_active, :is_public, :is_featured, :created_at
             )',
            $payload + ['created_at' => now()]
        );

        $serviceId = db_last_id();
        sync_service_intake_forms(
            $serviceId,
            is_array($input['intake_form_ids'] ?? null) ? $input['intake_form_ids'] : [],
            is_array($input['intake_required'] ?? null) ? $input['intake_required'] : [],
            is_array($input['intake_sort'] ?? null) ? $input['intake_sort'] : []
        );

        return $serviceId;
    }

    db_execute(
        'UPDATE services
         SET name = :name,
             slug = :slug,
             description = :description,
             short_description = :short_description,
             price = :price,
             currency = :currency,
             commission_percent = :commission_percent,
             tax_percent = :tax_percent,
             category = :category,
             featured_bullets = :featured_bullets,
             featured_image_id = :featured_image_id,
             is_active = :is_active,
             is_public = :is_public,
             is_featured = :is_featured
         WHERE id = :id',
        $payload + ['id' => $id]
    );

    sync_service_intake_forms(
        $id,
        is_array($input['intake_form_ids'] ?? null) ? $input['intake_form_ids'] : [],
        is_array($input['intake_required'] ?? null) ? $input['intake_required'] : [],
        is_array($input['intake_sort'] ?? null) ? $input['intake_sort'] : []
    );

    return $id;
}

function delete_service(int $serviceId): void
{
    $service = get_service($serviceId);
    if (!$service) {
        throw new RuntimeException('Service not found.');
    }

    $jobCount = (int) (db_one(
        'SELECT COUNT(*) AS c FROM jobs WHERE service_id = :service_id',
        ['service_id' => $serviceId]
    )['c'] ?? 0);

    if ($jobCount > 0) {
        throw new RuntimeException('This service has existing jobs and cannot be deleted.');
    }

    $submissionCount = (int) (db_one(
        'SELECT COUNT(*) AS c FROM intake_submissions WHERE service_id = :service_id',
        ['service_id' => $serviceId]
    )['c'] ?? 0);

    if ($submissionCount > 0) {
        throw new RuntimeException('This service has intake submissions and cannot be deleted.');
    }

    db_execute('DELETE FROM services WHERE id = :id', ['id' => $serviceId]);
}

function normalize_intake_schema(array $schema): array
{
    $steps = [];
    $schemaSteps = $schema['steps'] ?? [];
    if (!is_array($schemaSteps)) {
        $schemaSteps = [];
    }

    foreach ($schemaSteps as $stepIndex => $step) {
        if (!is_array($step)) {
            continue;
        }
        $stepTitle = trim((string) ($step['title'] ?? 'Step ' . ((int) $stepIndex + 1)));
        $stepDescription = trim((string) ($step['description'] ?? ''));
        $fields = [];
        $rawFields = $step['fields'] ?? [];
        if (!is_array($rawFields)) {
            $rawFields = [];
        }

        foreach ($rawFields as $fieldIndex => $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                $key = slugify((string) ($field['label'] ?? 'field_' . ((int) $fieldIndex + 1)));
            }
            if ($key === '') {
                $key = 'field_' . ((int) $fieldIndex + 1);
            }

            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            $allowedTypes = [
                'text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox',
                'date', 'phone', 'email', 'number', 'file', 'repeating_group', 'address',
            ];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }

            $normalizedField = [
                'key' => $key,
                'label' => trim((string) ($field['label'] ?? ucfirst(str_replace('_', ' ', $key)))),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'helpText' => trim((string) ($field['helpText'] ?? '')),
                'required' => !empty($field['required']),
                'type' => $type,
                'multiple' => $type === 'file' ? !empty($field['multiple']) : false,
                'defaultValue' => $field['defaultValue'] ?? '',
                'validation' => is_array($field['validation'] ?? null) ? $field['validation'] : [],
                'conditions' => is_array($field['conditions'] ?? null) ? $field['conditions'] : [],
                'options' => [],
                'agentNotes' => trim((string) ($field['agentNotes'] ?? '')),
            ];

            $rawOptions = $field['options'] ?? [];
            if (is_array($rawOptions)) {
                foreach ($rawOptions as $option) {
                    $optionText = trim((string) $option);
                    if ($optionText !== '') {
                        $normalizedField['options'][] = $optionText;
                    }
                }
            }

            if (in_array($type, ['select', 'multiselect', 'radio', 'checkbox'], true) && !$normalizedField['options']) {
                $normalizedField['options'] = ['Option 1', 'Option 2'];
            }

            $fields[] = $normalizedField;
        }

        $steps[] = [
            'title' => $stepTitle !== '' ? $stepTitle : 'Step',
            'description' => $stepDescription,
            'fields' => $fields,
        ];
    }

    if (!$steps) {
        $steps = [
            [
                'title' => 'Basic information',
                'description' => '',
                'fields' => [
                    [
                        'key' => 'full_name',
                        'label' => 'Full name',
                        'placeholder' => 'Enter applicant full name',
                        'helpText' => '',
                        'required' => true,
                        'type' => 'text',
                        'multiple' => false,
                        'defaultValue' => '',
                        'validation' => [],
                        'conditions' => [],
                        'options' => [],
                        'agentNotes' => '',
                    ],
                ],
            ],
        ];
    }

    return [
        'steps' => $steps,
        'meta' => is_array($schema['meta'] ?? null) ? $schema['meta'] : [],
    ];
}

function default_intake_schema_for_service(string $serviceName = ''): array
{
    $serviceName = trim($serviceName) !== '' ? trim($serviceName) : 'Service Intake';

    return normalize_intake_schema([
        'meta' => [
            'serviceName' => $serviceName,
            'jurisdiction' => 'Nigeria',
            'version' => 1,
        ],
        'steps' => [
            [
                'title' => 'Applicant details',
                'description' => 'Provide applicant details exactly as official records.',
                'fields' => [
                    [
                        'key' => 'applicant_type',
                        'label' => 'Applicant type',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['Individual', 'Company'],
                        'helpText' => 'Select who the application is for.',
                    ],
                    [
                        'key' => 'applicant_full_name',
                        'label' => 'Full legal name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Enter full legal name',
                        'helpText' => 'Use the exact spelling on your official ID.',
                    ],
                    [
                        'key' => 'email',
                        'label' => 'Email address',
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => 'you@example.com',
                    ],
                    [
                        'key' => 'phone',
                        'label' => 'Phone number',
                        'type' => 'phone',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Service requirements',
                'description' => 'Provide specific details for ' . $serviceName . '.',
                'fields' => [
                    [
                        'key' => 'service_objective',
                        'label' => 'Objective',
                        'type' => 'textarea',
                        'required' => true,
                        'helpText' => 'Summarize what outcome you need.',
                        'agentNotes' => 'Confirm objective aligns with service scope before processing.',
                    ],
                    [
                        'key' => 'priority_level',
                        'label' => 'Priority level',
                        'type' => 'select',
                        'required' => true,
                        'options' => ['Standard', 'Urgent'],
                    ],
                    [
                        'key' => 'supporting_documents',
                        'label' => 'Supporting documents',
                        'type' => 'file',
                        'required' => false,
                        'helpText' => 'Upload PNG/JPG/PDF documents only.',
                    ],
                ],
            ],
            [
                'title' => 'Review and consent',
                'description' => 'Confirm all information is accurate.',
                'fields' => [
                    [
                        'key' => 'consent_confirmation',
                        'label' => 'I confirm all information provided is accurate.',
                        'type' => 'checkbox',
                        'required' => true,
                        'options' => ['Confirmed'],
                        'helpText' => 'Inaccurate data may delay processing.',
                    ],
                ],
            ],
        ],
    ]);
}

function ai_openai_chat_json(array $messages): array
{
    if (!ai_ready()) {
        throw new RuntimeException('AI generation is disabled or API key is not configured.');
    }

    $payload = [
        'model' => ai_model(),
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object'],
        'messages' => $messages,
    ];

    [$statusCode, $responseBody] = gateway_http_request(
        'https://api.openai.com/v1/chat/completions',
        'POST',
        [
            'Authorization: Bearer ' . ai_api_key(),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        40
    );

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('AI response was not valid JSON.');
    }

    if ($statusCode >= 400) {
        $errorMessage = (string) ($decoded['error']['message'] ?? $decoded['message'] ?? 'AI request failed.');
        throw new RuntimeException($errorMessage);
    }

    $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    $json = json_decode($content, true);
    if (!is_array($json)) {
        throw new RuntimeException('AI did not return structured JSON output.');
    }

    return $json;
}

function ai_fallback_intake_schema(string $serviceName, string $jurisdiction = 'Nigeria', string $description = '', string $targetApplicants = 'individual/company', ?int $stepsCount = null): array
{
    $serviceName = trim($serviceName) !== '' ? trim($serviceName) : 'Service Intake';
    $description = trim($description);
    $targetApplicants = trim($targetApplicants) !== '' ? trim($targetApplicants) : 'individual/company';
    $jurisdiction = trim($jurisdiction) !== '' ? trim($jurisdiction) : 'Nigeria';

    $schema = default_intake_schema_for_service($serviceName);
    $meta = [
        'serviceName' => $serviceName,
        'jurisdiction' => $jurisdiction,
        'targetApplicantTypes' => $targetApplicants,
        'generator' => 'fallback',
        'generatedAt' => now(),
    ];
    $schema['meta'] = $meta;

    $serviceKey = strtolower($serviceName);
    $extraFields = [];
    if (str_contains($serviceKey, 'trademark')) {
        $extraFields = [
            ['key' => 'mark_name', 'label' => 'Trademark name', 'type' => 'text', 'required' => true, 'helpText' => 'Use the exact mark text to file.'],
            ['key' => 'trademark_class', 'label' => 'Trademark class', 'type' => 'text', 'required' => true, 'helpText' => 'Provide class number(s).'],
            ['key' => 'logo_file', 'label' => 'Trademark logo', 'type' => 'file', 'required' => false, 'helpText' => 'Upload logo image if applicable.'],
        ];
    } elseif (str_contains($serviceKey, 'cac') || str_contains($serviceKey, 'company') || str_contains($serviceKey, 'registration')) {
        $extraFields = [
            ['key' => 'proposed_name_1', 'label' => 'Proposed business/company name (1)', 'type' => 'text', 'required' => true, 'helpText' => 'Provide at least 2 name options.'],
            ['key' => 'proposed_name_2', 'label' => 'Proposed business/company name (2)', 'type' => 'text', 'required' => true],
            ['key' => 'business_activity', 'label' => 'Business activity', 'type' => 'textarea', 'required' => true],
            ['key' => 'id_document', 'label' => 'Government ID document', 'type' => 'file', 'required' => true],
        ];
    } elseif (str_contains($serviceKey, 'nafdac')) {
        $extraFields = [
            ['key' => 'product_name', 'label' => 'Product name', 'type' => 'text', 'required' => true],
            ['key' => 'product_category', 'label' => 'Product category', 'type' => 'select', 'required' => true, 'options' => ['Food', 'Drug', 'Cosmetic', 'Medical device', 'Other']],
            ['key' => 'lab_report', 'label' => 'Laboratory report (if available)', 'type' => 'file', 'required' => false],
        ];
    }

    if ($extraFields) {
        $schema['steps'][1]['fields'] = array_merge($schema['steps'][1]['fields'], $extraFields);
    }

    if (is_int($stepsCount) && $stepsCount > 0 && $stepsCount < count($schema['steps'])) {
        $schema['steps'] = array_slice($schema['steps'], 0, $stepsCount);
    }

    $schema = normalize_intake_schema($schema);

    return [
        'title' => $serviceName . ' Intake Form',
        'description' => $description !== '' ? $description : 'Generated intake form for ' . $serviceName . '.',
        'schema_json' => $schema,
    ];
}

function ai_generate_intake_schema(array $input): array
{
    $serviceName = trim((string) ($input['serviceName'] ?? ''));
    if ($serviceName === '') {
        throw new RuntimeException('Service name is required for AI generation.');
    }

    $jurisdiction = trim((string) ($input['jurisdiction'] ?? 'Nigeria'));
    $description = trim((string) ($input['description'] ?? ''));
    $targetApplicantTypes = trim((string) ($input['targetApplicantTypes'] ?? 'individual/company'));
    $stepsCountRaw = (int) ($input['desiredStepsCount'] ?? 0);
    $stepsCount = $stepsCountRaw > 0 ? max(2, min(8, $stepsCountRaw)) : null;

    if (!ai_ready()) {
        return ai_fallback_intake_schema($serviceName, $jurisdiction, $description, $targetApplicantTypes, $stepsCount);
    }

    $systemPrompt = 'You generate structured JSON for service intake forms. Never guarantee government approval. Keep practical required fields only, include file upload fields where useful, include client helper tips and internal agent notes. Output JSON object: {title, description, schema_json}. schema_json must contain {meta, steps:[{title,description,fields:[{key,label,type,required,placeholder,helpText,options,validation,conditions,agentNotes}]}]}.';
    $userPrompt = "Generate an intake form schema for service: {$serviceName}\nJurisdiction: {$jurisdiction}\nDescription: {$description}\nTarget applicants: {$targetApplicantTypes}\nDesired steps count: " . ($stepsCount ?? 'auto');

    $aiJson = ai_openai_chat_json([
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt],
    ]);

    $title = trim((string) ($aiJson['title'] ?? ''));
    $desc = trim((string) ($aiJson['description'] ?? ''));
    $schemaDecoded = $aiJson['schema_json'] ?? [];

    if (!is_array($schemaDecoded)) {
        return ai_fallback_intake_schema($serviceName, $jurisdiction, $description, $targetApplicantTypes, $stepsCount);
    }

    $schema = normalize_intake_schema($schemaDecoded);
    if ($stepsCount !== null && count($schema['steps']) > $stepsCount) {
        $schema['steps'] = array_slice($schema['steps'], 0, $stepsCount);
    }
    $schema['meta'] = array_merge(is_array($schema['meta'] ?? null) ? $schema['meta'] : [], [
        'serviceName' => $serviceName,
        'jurisdiction' => $jurisdiction,
        'targetApplicantTypes' => $targetApplicantTypes,
        'generator' => 'ai',
        'generatedAt' => now(),
    ]);

    return [
        'title' => $title !== '' ? $title : ($serviceName . ' Intake Form'),
        'description' => $desc !== '' ? $desc : ($description !== '' ? $description : 'AI generated intake form for ' . $serviceName . '.'),
        'schema_json' => $schema,
    ];
}

function ai_suggest_intake_fields(array $input): array
{
    $serviceName = trim((string) ($input['serviceName'] ?? ''));
    $existingSchema = $input['existingSchema'] ?? [];
    if (!is_array($existingSchema)) {
        $existingSchema = [];
    }

    $normalized = normalize_intake_schema($existingSchema);
    $suggestions = [
        'additions' => [],
        'removals' => [],
        'notes' => [],
    ];

    $serviceKey = strtolower($serviceName);
    if (str_contains($serviceKey, 'trademark')) {
        $suggestions['additions'][] = ['step' => 'Service requirements', 'field' => 'trademark_class', 'reason' => 'Trademark class is typically required for filing.'];
        $suggestions['additions'][] = ['step' => 'Service requirements', 'field' => 'mark_description', 'reason' => 'Mark description helps agent review filing strategy.'];
    }
    if (str_contains($serviceKey, 'cac') || str_contains($serviceKey, 'registration')) {
        $suggestions['additions'][] = ['step' => 'Service requirements', 'field' => 'proposed_name_3', 'reason' => 'Third business name option improves approval chances.'];
    }
    if (str_contains($serviceKey, 'nafdac')) {
        $suggestions['additions'][] = ['step' => 'Service requirements', 'field' => 'manufacturing_address', 'reason' => 'Regulatory submissions usually require manufacturing address.'];
    }

    if (!$suggestions['additions']) {
        $suggestions['notes'][] = 'Current schema already covers a practical baseline. Consider adding at least one file upload field for supporting documents.';
    }

    if (ai_ready()) {
        try {
            $aiJson = ai_openai_chat_json([
                ['role' => 'system', 'content' => 'Suggest schema diff for intake form as JSON object with keys additions/removals/notes. No markdown.'],
                ['role' => 'user', 'content' => 'Service: ' . $serviceName . "\nExisting schema: " . (string) json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
            ]);
            if (is_array($aiJson)) {
                $suggestions = array_merge($suggestions, $aiJson);
            }
        } catch (Throwable $e) {
            // Keep deterministic fallback suggestions if AI call fails.
        }
    }

    return ['suggestedDiff' => $suggestions];
}

function intake_form_statuses(): array
{
    return ['active', 'draft', 'archived'];
}

function normalize_intake_form_status(string $status): string
{
    $status = strtolower(trim($status));
    if (!in_array($status, intake_form_statuses(), true)) {
        return 'draft';
    }

    return $status;
}

function list_intake_forms(string $status = 'all', string $search = ''): array
{
    $status = strtolower(trim($status));
    $search = trim($search);

    $sql = 'SELECT f.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
            FROM intake_forms f
            LEFT JOIN users cu ON cu.id = f.created_by
            LEFT JOIN users uu ON uu.id = f.updated_by
            WHERE 1=1';
    $params = [];

    if (in_array($status, intake_form_statuses(), true)) {
        $sql .= ' AND f.status = :status';
        $params['status'] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (f.title LIKE :search OR f.description LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY f.updated_at DESC, f.id DESC';
    return db_all($sql, $params);
}

function get_intake_form(int $id): ?array
{
    $row = db_one(
        'SELECT f.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
         FROM intake_forms f
         LEFT JOIN users cu ON cu.id = f.created_by
         LEFT JOIN users uu ON uu.id = f.updated_by
         WHERE f.id = :id LIMIT 1',
        ['id' => $id]
    );

    if (!$row) {
        return null;
    }

    $schema = json_decode((string) ($row['schema_json'] ?? ''), true);
    $row['schema'] = is_array($schema) ? normalize_intake_schema($schema) : default_intake_schema_for_service((string) ($row['title'] ?? 'Service Intake'));

    return $row;
}

function intake_schema_hash(array $schema): string
{
    return hash('sha256', (string) json_encode(normalize_intake_schema($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function save_intake_form(array $input, int $actorId, ?int $id = null): int
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new RuntimeException('Form title is required.');
    }

    $description = trim((string) ($input['description'] ?? ''));
    $status = normalize_intake_form_status((string) ($input['status'] ?? 'draft'));
    $schemaRaw = trim((string) ($input['schema_json'] ?? ''));
    if ($schemaRaw === '') {
        throw new RuntimeException('Form schema JSON is required.');
    }

    $schemaDecoded = json_decode($schemaRaw, true);
    if (!is_array($schemaDecoded)) {
        throw new RuntimeException('Schema JSON must be valid structured JSON.');
    }
    $schema = normalize_intake_schema($schemaDecoded);
    $schemaJson = (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($id === null) {
        db_execute(
            'INSERT INTO intake_forms (title, description, schema_json, status, created_by, updated_by, created_at, updated_at)
             VALUES (:title, :description, :schema_json, :status, :created_by, :updated_by, :created_at, :updated_at)',
            [
                'title' => $title,
                'description' => $description,
                'schema_json' => $schemaJson,
                'status' => $status,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $id = db_last_id();
    } else {
        $existing = get_intake_form($id);
        if (!$existing) {
            throw new RuntimeException('Intake form not found.');
        }

        db_execute(
            'UPDATE intake_forms
             SET title = :title, description = :description, schema_json = :schema_json, status = :status, updated_by = :updated_by, updated_at = :updated_at
             WHERE id = :id',
            [
                'title' => $title,
                'description' => $description,
                'schema_json' => $schemaJson,
                'status' => $status,
                'updated_by' => $actorId,
                'updated_at' => now(),
                'id' => $id,
            ]
        );
    }

    if (array_key_exists('attach_service_ids', $input) && is_array($input['attach_service_ids'])) {
        db_execute('DELETE FROM service_intake_forms WHERE intake_form_id = :intake_form_id', ['intake_form_id' => $id]);
        attach_intake_form_to_services(
            $id,
            $input['attach_service_ids'],
            is_array($input['attach_required'] ?? null) ? $input['attach_required'] : [],
            is_array($input['attach_sort'] ?? null) ? $input['attach_sort'] : []
        );
    }

    return $id;
}

function list_service_intake_forms(int $serviceId): array
{
    return db_all(
        'SELECT sif.*, f.title, f.description, f.status, f.schema_json
         FROM service_intake_forms sif
         JOIN intake_forms f ON f.id = sif.intake_form_id
         WHERE sif.service_id = :service_id
         ORDER BY sif.sort_order ASC, sif.id ASC',
        ['service_id' => $serviceId]
    );
}

function list_intake_forms_for_service(int $serviceId, bool $onlyActive = false): array
{
    $sql = 'SELECT sif.*, f.title, f.description, f.status, f.schema_json
            FROM service_intake_forms sif
            JOIN intake_forms f ON f.id = sif.intake_form_id
            WHERE sif.service_id = :service_id';
    $params = ['service_id' => $serviceId];

    if ($onlyActive) {
        $sql .= " AND f.status = 'active'";
    }

    $sql .= ' ORDER BY sif.sort_order ASC, sif.id ASC';
    return db_all($sql, $params);
}

function sync_service_intake_forms(int $serviceId, array $formIds, array $requiredMap = [], array $sortMap = []): void
{
    $clean = [];
    foreach ($formIds as $raw) {
        $id = (int) $raw;
        if ($id > 0) {
            $clean[] = $id;
        }
    }
    $clean = array_values(array_unique($clean));

    db_execute('DELETE FROM service_intake_forms WHERE service_id = :service_id', ['service_id' => $serviceId]);

    foreach ($clean as $position => $formId) {
        $required = 1;
        if (array_key_exists((string) $formId, $requiredMap)) {
            $required = !empty($requiredMap[(string) $formId]) ? 1 : 0;
        } elseif (array_key_exists($formId, $requiredMap)) {
            $required = !empty($requiredMap[$formId]) ? 1 : 0;
        }

        $sortOrder = $position + 1;
        if (array_key_exists((string) $formId, $sortMap) && is_numeric($sortMap[(string) $formId])) {
            $sortOrder = (int) $sortMap[(string) $formId];
        } elseif (array_key_exists($formId, $sortMap) && is_numeric($sortMap[$formId])) {
            $sortOrder = (int) $sortMap[$formId];
        }

        db_execute(
            'INSERT INTO service_intake_forms (service_id, intake_form_id, is_required, sort_order, created_at)
             VALUES (:service_id, :intake_form_id, :is_required, :sort_order, :created_at)',
            [
                'service_id' => $serviceId,
                'intake_form_id' => $formId,
                'is_required' => $required,
                'sort_order' => $sortOrder,
                'created_at' => now(),
            ]
        );
    }
}

function attach_intake_form_to_services(int $formId, array $serviceIds, array $requiredMap = [], array $sortMap = []): void
{
    $clean = [];
    foreach ($serviceIds as $raw) {
        $id = (int) $raw;
        if ($id > 0) {
            $clean[] = $id;
        }
    }
    $clean = array_values(array_unique($clean));

    foreach ($clean as $serviceId) {
        $existing = db_one(
            'SELECT id FROM service_intake_forms WHERE service_id = :service_id AND intake_form_id = :intake_form_id LIMIT 1',
            ['service_id' => $serviceId, 'intake_form_id' => $formId]
        );

        $required = 1;
        if (array_key_exists((string) $serviceId, $requiredMap)) {
            $required = !empty($requiredMap[(string) $serviceId]) ? 1 : 0;
        } elseif (array_key_exists($serviceId, $requiredMap)) {
            $required = !empty($requiredMap[$serviceId]) ? 1 : 0;
        }

        $sortOrder = 1;
        if (array_key_exists((string) $serviceId, $sortMap) && is_numeric($sortMap[(string) $serviceId])) {
            $sortOrder = (int) $sortMap[(string) $serviceId];
        } elseif (array_key_exists($serviceId, $sortMap) && is_numeric($sortMap[$serviceId])) {
            $sortOrder = (int) $sortMap[$serviceId];
        } else {
            $maxSort = db_one('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM service_intake_forms WHERE service_id = :service_id', ['service_id' => $serviceId]);
            $sortOrder = ((int) ($maxSort['max_sort'] ?? 0)) + 1;
        }

        if ($existing) {
            db_execute(
                'UPDATE service_intake_forms SET is_required = :is_required, sort_order = :sort_order WHERE id = :id',
                ['is_required' => $required, 'sort_order' => $sortOrder, 'id' => (int) $existing['id']]
            );
        } else {
            db_execute(
                'INSERT INTO service_intake_forms (service_id, intake_form_id, is_required, sort_order, created_at)
                 VALUES (:service_id, :intake_form_id, :is_required, :sort_order, :created_at)',
                [
                    'service_id' => $serviceId,
                    'intake_form_id' => $formId,
                    'is_required' => $required,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                ]
            );
        }
    }
}

function list_services_with_intake_forms(): array
{
    $services = list_services(false);
    foreach ($services as &$service) {
        $service['intake_forms'] = list_service_intake_forms((int) $service['id']);
    }
    unset($service);

    return $services;
}

function list_users_by_role(string $role): array
{
    return db_all(
        "SELECT * FROM users
         WHERE role = :role
           AND status = 'active'
           AND account_state = 'active'
           AND deleted_at IS NULL
         ORDER BY full_name ASC",
        ['role' => $role]
    );
}

function admin_finance_user_ids(): array
{
    $rows = db_all("SELECT id FROM users WHERE role IN ('admin', 'finance') AND status = 'active' AND account_state = 'active' AND deleted_at IS NULL");

    return array_map(static fn(array $row): int => (int) $row['id'], $rows);
}

function job_status_options(): array
{
    return ['pending', 'in_progress', 'waiting_client', 'revision_requested', 'delivered', 'closed', 'cancelled'];
}

function agent_job_status_options(): array
{
    return ['pending', 'in_progress', 'waiting_client', 'revision_requested', 'delivered'];
}

function job_revision_window_days(): int
{
    $days = (int) trim((string) setting('job_revision_window_days', '7'));
    return max(1, $days);
}

function job_auto_close_days(): int
{
    $days = (int) trim((string) setting('job_auto_close_days', '7'));
    return $days < 0 ? 0 : $days;
}

function can_client_request_job_revision(array $job): bool
{
    if ((string) ($job['status'] ?? '') !== 'delivered') {
        return false;
    }

    $windowDays = job_revision_window_days();
    if ($windowDays <= 0) {
        return false;
    }

    $deliveredAtRaw = trim((string) ($job['delivered_at'] ?? ''));
    if ($deliveredAtRaw === '') {
        return true;
    }

    $deliveredTs = strtotime($deliveredAtRaw);
    if ($deliveredTs === false) {
        return true;
    }

    $windowSeconds = $windowDays * 86400;
    return (time() - $deliveredTs) <= $windowSeconds;
}

function can_user_comment_on_job(array $user, array $job): bool
{
    if ((string) ($user['role'] ?? '') !== 'client') {
        return true;
    }

    if ((int) ($job['client_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
        return false;
    }

    return !in_array((string) ($job['status'] ?? ''), ['delivered', 'closed', 'cancelled'], true);
}

function can_user_upload_files_to_job(array $user, array $job): bool
{
    $role = (string) ($user['role'] ?? '');
    $userId = (int) ($user['id'] ?? 0);
    $jobStatus = (string) ($job['status'] ?? '');

    if ($role === 'admin') {
        return true;
    }

    if ($role === 'client') {
        return (int) ($job['client_id'] ?? 0) === $userId
            && !in_array($jobStatus, ['closed', 'cancelled'], true);
    }

    if ($role === 'agent') {
        return (int) ($job['agent_id'] ?? 0) === $userId
            && !in_array($jobStatus, ['closed', 'cancelled'], true);
    }

    return false;
}

function default_admin_actor_id(): ?int
{
    static $cached = null;
    static $resolved = false;

    if ($resolved) {
        return $cached;
    }

    $resolved = true;
    $row = db_one(
        "SELECT id
         FROM users
         WHERE role = 'admin'
           AND status = 'active'
           AND account_state = 'active'
           AND deleted_at IS NULL
         ORDER BY id ASC
         LIMIT 1"
    );
    $cached = $row ? (int) $row['id'] : null;

    return $cached;
}

function record_job_status_change(int $jobId, ?string $oldStatus, string $newStatus, int $changedBy): void
{
    db_execute(
        'INSERT INTO job_status_logs (job_id, old_status, new_status, changed_by, created_at)
         VALUES (:job_id, :old_status, :new_status, :changed_by, :created_at)',
        [
            'job_id' => $jobId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]
    );
}

function maybe_auto_close_delivered_jobs(): void
{
    static $ran = false;
    if ($ran || !is_configured()) {
        return;
    }
    $ran = true;

    $days = job_auto_close_days();
    if ($days <= 0) {
        return;
    }

    $actorId = default_admin_actor_id();
    if (!$actorId) {
        return;
    }

    $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
    $jobs = db_all(
        "SELECT id, job_code, status, delivered_at, client_id, agent_id
         FROM jobs
         WHERE status = 'delivered'
           AND delivered_at IS NOT NULL
           AND delivered_at <= :cutoff",
        ['cutoff' => $cutoff]
    );

    foreach ($jobs as $job) {
        $jobId = (int) ($job['id'] ?? 0);
        if ($jobId <= 0) {
            continue;
        }

        db_execute(
            'UPDATE jobs
             SET status = :status, accepted_by = :accepted_by, accepted_at = :accepted_at, updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => 'closed',
                'accepted_by' => $actorId,
                'accepted_at' => now(),
                'updated_at' => now(),
                'id' => $jobId,
            ]
        );

        record_job_status_change($jobId, 'delivered', 'closed', $actorId);
        add_system_job_comment(
            $jobId,
            $actorId,
            'Job auto-closed after ' . $days . ' day(s) in delivered state.',
            true
        );
        maybe_accrue_commission($jobId);
        create_notification(
            (int) $job['client_id'],
            'Your order is complete — leave a review',
            'Order ' . (string) ($job['job_code'] ?? '') . ' has been closed. Rate the experience on your order page.',
            url('job', ['id' => $jobId])
        );
    }
}

function confirmed_payment_exists_sql_for_job(string $jobAlias = 'j', string $invoiceAlias = 'i2', string $paymentAlias = 'p2'): string
{
    $jobAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $jobAlias) ?: 'j';
    $invoiceAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $invoiceAlias) ?: 'i2';
    $paymentAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $paymentAlias) ?: 'p2';

    return "EXISTS (
        SELECT 1
        FROM invoices {$invoiceAlias}
        JOIN job_payments {$paymentAlias}
          ON {$paymentAlias}.invoice_id = {$invoiceAlias}.id
         AND {$paymentAlias}.status = 'confirmed'
        WHERE (
                (COALESCE({$jobAlias}.payment_group, '') <> '' AND {$invoiceAlias}.payment_group = {$jobAlias}.payment_group)
             OR (COALESCE({$jobAlias}.payment_group, '') = '' AND {$invoiceAlias}.job_id = {$jobAlias}.id)
        )
    )";
}

function normalize_unconfirmed_partial_jobs(?int $clientId = null): void
{
    if (!schema_table_exists('jobs') || !schema_table_exists('invoices') || !schema_table_exists('job_payments')) {
        return;
    }

    $sql = "UPDATE jobs j
            SET j.payment_status = 'unpaid'
            WHERE j.payment_status = 'partial'
              AND NOT " . confirmed_payment_exists_sql_for_job('j', 'i3', 'p3');
    $params = [];

    if ($clientId !== null && $clientId > 0) {
        $sql .= ' AND j.client_id = :client_id';
        $params['client_id'] = $clientId;
    }

    db_execute($sql, $params);
}

function list_accessible_jobs(array $user, array $filters = []): array
{
    maybe_auto_close_delivered_jobs();

    $role = (string) ($user['role'] ?? '');
    if ($role === 'client') {
        normalize_unconfirmed_partial_jobs((int) ($user['id'] ?? 0));
    } else {
        normalize_unconfirmed_partial_jobs();
    }

    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $paymentStatus = strtolower(trim((string) ($filters['payment_status'] ?? '')));
    $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = (int) ($filters['per_page'] ?? 10);

    $allowedStatus = job_status_options();
    $allowedPayment = ['unpaid', 'partial', 'paid', 'refunded'];
    $allowedSort = ['newest', 'oldest', 'amount_high', 'amount_low', 'status', 'payment'];
    $allowedPerPage = [10, 25, 50, 100];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }
    if (!in_array($paymentStatus, $allowedPayment, true)) {
        $paymentStatus = '';
    }
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'newest';
    }
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    $confirmedPaymentExistsSql = confirmed_payment_exists_sql_for_job('j', 'i2', 'p2');
    $effectivePaymentStatusSql = "CASE WHEN j.payment_status = 'partial' AND NOT {$confirmedPaymentExistsSql} THEN 'unpaid' ELSE j.payment_status END";

    $sortSqlMap = [
        'newest' => 'j.updated_at DESC, j.created_at DESC',
        'oldest' => 'j.created_at ASC',
        'amount_high' => 'j.amount DESC, j.updated_at DESC',
        'amount_low' => 'j.amount ASC, j.updated_at DESC',
        'status' => 'j.status ASC, j.updated_at DESC',
        'payment' => $effectivePaymentStatusSql . ' ASC, j.updated_at DESC',
    ];

    $fromSql = ' FROM jobs j
                 JOIN services s ON s.id = j.service_id
                 JOIN users c ON c.id = j.client_id
                 LEFT JOIN users a ON a.id = j.agent_id
                 LEFT JOIN users am ON am.id = j.managed_by_admin_id
                 WHERE 1=1';
    $params = [];

    if ($role === 'client') {
        $fromSql .= ' AND j.client_id = :client_id';
        $params['client_id'] = (int) $user['id'];
    } elseif ($role === 'agent') {
        $fromSql .= " AND j.payment_status = 'paid' AND (j.agent_id = :agent_id OR (j.agent_id IS NULL AND j.managed_by_admin_id IS NULL))";
        $params['agent_id'] = (int) $user['id'];
    }

    if ($status !== '') {
        $fromSql .= ' AND j.status = :status';
        $params['status'] = $status;
    }

    if ($paymentStatus !== '') {
        $fromSql .= ' AND ' . $effectivePaymentStatusSql . ' = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }

    $totalRow = db_one('SELECT COUNT(*) AS total' . $fromSql, $params);
    $total = (int) ($totalRow['total'] ?? 0);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $orderBy = $sortSqlMap[$sort];
    if ($role === 'agent') {
        $orderBy = 'CASE WHEN j.agent_id IS NULL AND j.managed_by_admin_id IS NULL THEN 0 ELSE 1 END ASC, ' . $orderBy;
    }

    $items = db_all(
        'SELECT j.*, s.name AS service_name, c.full_name AS client_name, a.full_name AS agent_name, am.full_name AS managed_by_admin_name,
                ' . $effectivePaymentStatusSql . ' AS payment_status_effective,
                CASE WHEN ' . $confirmedPaymentExistsSql . ' THEN 1 ELSE 0 END AS has_confirmed_payment'
        . $fromSql
        . ' ORDER BY ' . $orderBy
        . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
        $params
    );

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? ($offset + 1) : 0,
            'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
        ],
    ];
}

function job_code(): string
{
    return 'JOB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function invoice_number(): string
{
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function payment_group_code(): string
{
    return 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function calc_commission_amount(float $amount, float $percent): float
{
    return round(($amount * $percent) / 100, 2);
}

function create_job(array $input, array $trustedOptions = []): int
{
    $service = get_service((int) $input['service_id']);

    if (!$service) {
        throw new RuntimeException('Invalid service selected.');
    }

    $hasCustomAmount = isset($input['amount']) && $input['amount'] !== '';
    $serviceCurrency = strtoupper(trim((string) ($service['currency'] ?? default_currency())));
    $currency = strtoupper(trim((string) ($input['pay_currency'] ?? $input['currency'] ?? $serviceCurrency)));
    if (!in_array($currency, currency_options(), true)) {
        $currency = default_currency();
    }

    $subtotalAmount = $hasCustomAmount
        ? (float) $input['amount']
        : (float) $service['price'];

    if (
        !$hasCustomAmount
        && $currency !== $serviceCurrency
        && in_array($currency, payment_currency_options(), true)
        && in_array($serviceCurrency, payment_currency_options(), true)
    ) {
        $subtotalAmount = convert_amount($subtotalAmount, $serviceCurrency, $currency);
    }

    $payCurrency = in_array($currency, payment_currency_options(), true)
        ? normalize_payment_currency($currency)
        : normalize_payment_currency(default_currency());
    $payMethod = normalize_pay_method((string) ($input['checkout_pay_method'] ?? $input['pay_method'] ?? 'card'));
    $fxRateUsed = (
        $currency !== $serviceCurrency
        && in_array($currency, payment_currency_options(), true)
        && in_array($serviceCurrency, payment_currency_options(), true)
    ) ? fx_ngn_per_usd() : null;

    $taxPercent = isset($input['tax_percent']) && $input['tax_percent'] !== ''
        ? (float) $input['tax_percent']
        : (float) ($service['tax_percent'] ?? 0);
    $taxPercent = max(0.0, min(100.0, $taxPercent));
    $taxMode = strtolower(trim((string) ($input['tax_mode'] ?? 'exclusive')));
    if (!in_array($taxMode, ['exclusive', 'inclusive'], true)) {
        $taxMode = 'exclusive';
    }

    if ($taxPercent > 0 && $taxMode === 'inclusive') {
        $inclusiveTotal = round($subtotalAmount, 2);
        $exclusiveSubtotal = round($inclusiveTotal / (1 + ($taxPercent / 100)), 2);
        $taxAmount = round($inclusiveTotal - $exclusiveSubtotal, 2);
        $subtotalAmount = $exclusiveSubtotal;
        $amount = $inclusiveTotal;
    } else {
        $taxAmount = round(($subtotalAmount * $taxPercent) / 100, 2);
        $amount = round($subtotalAmount + $taxAmount, 2);
    }

    $commissionPercent = isset($input['commission_percent']) && $input['commission_percent'] !== ''
        ? (float) $input['commission_percent']
        : (float) $service['commission_percent'];

    $commissionAmount = calc_commission_amount($subtotalAmount, $commissionPercent);
    $invoiceNo = invoice_number();

    $paymentGroup = trim((string) ($input['payment_group'] ?? ''));
    if ($paymentGroup === '') {
        $paymentGroup = null;
    }

    db_execute(
        'INSERT INTO jobs (
            job_code, client_id, service_id, agent_id, title, description, status, subtotal_amount, tax_percent, tax_amount, amount, currency,
            pay_currency, pay_method, fx_rate_used, crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
            commission_percent, commission_amount, payment_status, payment_reference,
            invoice_number, payment_group, created_at, updated_at
         ) VALUES (
            :job_code, :client_id, :service_id, :agent_id, :title, :description, :status, :subtotal_amount, :tax_percent, :tax_amount, :amount, :currency,
            :pay_currency, :pay_method, :fx_rate_used, :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
            :commission_percent, :commission_amount, :payment_status, :payment_reference,
            :invoice_number, :payment_group, :created_at, :updated_at
         )',
        [
            'job_code' => job_code(),
            'client_id' => (int) $input['client_id'],
            'service_id' => (int) $input['service_id'],
            'agent_id' => isset($input['agent_id']) && $input['agent_id'] !== '' ? (int) $input['agent_id'] : null,
            'title' => trim($input['title'] ?? $service['name']),
            'description' => trim($input['description'] ?? ''),
            'status' => 'pending',
            'subtotal_amount' => $subtotalAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'amount' => $amount,
            'currency' => $currency,
            'pay_currency' => $payCurrency,
            'pay_method' => $payMethod,
            'fx_rate_used' => $fxRateUsed,
            'crypto_asset' => null,
            'crypto_rate_used' => null,
            'crypto_amount_due' => null,
            'crypto_network' => null,
            'rate_locked_at' => null,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'payment_status' => $input['payment_status'] ?? 'unpaid',
            'payment_reference' => trim($input['payment_reference'] ?? ''),
            'invoice_number' => $invoiceNo,
            'payment_group' => $paymentGroup,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $jobId = db_last_id();

    db_execute(
        'INSERT INTO invoices (
            invoice_number, job_id, payment_group, client_id, subtotal_amount, tax_percent, tax_amount, amount, currency,
            pay_currency, pay_method, fx_rate_used, crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
            status, issued_at, due_at, created_at
         )
         VALUES (
            :invoice_number, :job_id, :payment_group, :client_id, :subtotal_amount, :tax_percent, :tax_amount, :amount, :currency,
            :pay_currency, :pay_method, :fx_rate_used, :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
            :status, :issued_at, :due_at, :created_at
         )',
        [
            'invoice_number' => $invoiceNo,
            'job_id' => $jobId,
            'payment_group' => $paymentGroup,
            'client_id' => (int) $input['client_id'],
            'subtotal_amount' => $subtotalAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'amount' => $amount,
            'currency' => $currency,
            'pay_currency' => $payCurrency,
            'pay_method' => $payMethod,
            'fx_rate_used' => $fxRateUsed,
            'crypto_asset' => null,
            'crypto_rate_used' => null,
            'crypto_amount_due' => null,
            'crypto_network' => null,
            'rate_locked_at' => null,
            'status' => ($input['payment_status'] ?? 'unpaid') === 'paid' ? 'paid' : 'issued',
            'issued_at' => now(),
            'due_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'created_at' => now(),
        ]
    );

    db_execute(
        'INSERT INTO job_status_logs (job_id, old_status, new_status, changed_by, created_at)
         VALUES (:job_id, :old_status, :new_status, :changed_by, :created_at)',
        [
            'job_id' => $jobId,
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => (int) $input['created_by'],
            'created_at' => now(),
        ]
    );

    maybe_accrue_commission($jobId);
    // Trademark wizard already captures all core trademark filing fields.
    // Only server-side callers can pass this option; it is NOT read from POST.
    if (empty($trustedOptions['skip_duplicate_intake'])) {
        maybe_prepare_job_intake_requirements($jobId, (int) $input['created_by'], 'job_created');
    }

    return $jobId;
}

function create_client_order_bundle(array $input): array
{
    $clientId = (int) ($input['client_id'] ?? 0);
    $createdBy = (int) ($input['created_by'] ?? $clientId);
    $serviceIdsRaw = $input['service_ids'] ?? [];
    $serviceIds = [];

    if (is_array($serviceIdsRaw)) {
        foreach ($serviceIdsRaw as $sid) {
            $id = (int) $sid;
            if ($id > 0) {
                $serviceIds[] = $id;
            }
        }
    } else {
        $single = (int) ($input['service_id'] ?? 0);
        if ($single > 0) {
            $serviceIds[] = $single;
        }
    }

    $serviceIds = array_values(array_unique($serviceIds));
    if (!$serviceIds) {
        throw new RuntimeException('Select at least one service before checkout.');
    }

    $currency = strtoupper(trim((string) ($input['pay_currency'] ?? $input['currency'] ?? default_currency())));
    if (!in_array($currency, currency_options(), true)) {
        $currency = default_currency();
    }

    $payCurrency = in_array($currency, payment_currency_options(), true)
        ? normalize_payment_currency($currency)
        : normalize_payment_currency(default_currency());
    $payMethod = normalize_pay_method((string) ($input['checkout_pay_method'] ?? $input['pay_method'] ?? 'card'));

    $paymentGroup = payment_group_code();
    $invoiceNo = invoice_number();
    $jobIds = [];
    $totalSubtotal = 0.0;
    $totalTax = 0.0;
    $totalAmount = 0.0;
    $anchorJobId = 0;
    $description = trim((string) ($input['description'] ?? ''));
    $bundleFxRateUsed = null;

    db()->beginTransaction();
    try {
        foreach ($serviceIds as $index => $serviceId) {
            $service = get_service($serviceId);
            if (!$service) {
                throw new RuntimeException('One of the selected services is invalid.');
            }

            $serviceCurrency = strtoupper(trim((string) ($service['currency'] ?? default_currency())));
            $subtotalAmount = (float) $service['price'];
            if (
                $currency !== $serviceCurrency
                && in_array($currency, payment_currency_options(), true)
                && in_array($serviceCurrency, payment_currency_options(), true)
            ) {
                $subtotalAmount = convert_amount($subtotalAmount, $serviceCurrency, $currency);
                $bundleFxRateUsed = fx_ngn_per_usd();
            }

            $taxPercent = max(0.0, min(100.0, (float) ($service['tax_percent'] ?? 0)));
            $taxAmount = round(($subtotalAmount * $taxPercent) / 100, 2);
            $amount = round($subtotalAmount + $taxAmount, 2);
            $commissionPercent = max(0.0, min(100.0, (float) ($service['commission_percent'] ?? 0)));
            $commissionAmount = calc_commission_amount($subtotalAmount, $commissionPercent);

            $title = trim((string) ($service['name'] ?? 'Service Order'));
            if ($index === 0 && trim((string) ($input['title'] ?? '')) !== '') {
                $title = trim((string) $input['title']);
            }

            db_execute(
                'INSERT INTO jobs (
                    job_code, client_id, service_id, agent_id, title, description, status,
                    subtotal_amount, tax_percent, tax_amount, amount, currency,
                    pay_currency, pay_method, fx_rate_used, crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
                    commission_percent, commission_amount, payment_status, payment_reference,
                    invoice_number, payment_group, created_at, updated_at
                 ) VALUES (
                    :job_code, :client_id, :service_id, :agent_id, :title, :description, :status,
                    :subtotal_amount, :tax_percent, :tax_amount, :amount, :currency,
                    :pay_currency, :pay_method, :fx_rate_used, :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
                    :commission_percent, :commission_amount, :payment_status, :payment_reference,
                    :invoice_number, :payment_group, :created_at, :updated_at
                 )',
                [
                    'job_code' => job_code(),
                    'client_id' => $clientId,
                    'service_id' => $serviceId,
                    'agent_id' => null,
                    'title' => $title,
                    'description' => $description,
                    'status' => 'pending',
                    'subtotal_amount' => $subtotalAmount,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'amount' => $amount,
                    'currency' => $currency,
                    'pay_currency' => $payCurrency,
                    'pay_method' => $payMethod,
                    'fx_rate_used' => $bundleFxRateUsed,
                    'crypto_asset' => null,
                    'crypto_rate_used' => null,
                    'crypto_amount_due' => null,
                    'crypto_network' => null,
                    'rate_locked_at' => null,
                    'commission_percent' => $commissionPercent,
                    'commission_amount' => $commissionAmount,
                    'payment_status' => 'unpaid',
                    'payment_reference' => '',
                    'invoice_number' => $invoiceNo,
                    'payment_group' => $paymentGroup,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $jobId = db_last_id();
            if ($anchorJobId === 0) {
                $anchorJobId = $jobId;
            }

            $jobIds[] = $jobId;
            $totalSubtotal += $subtotalAmount;
            $totalTax += $taxAmount;
            $totalAmount += $amount;

            db_execute(
                'INSERT INTO job_status_logs (job_id, old_status, new_status, changed_by, created_at)
                 VALUES (:job_id, :old_status, :new_status, :changed_by, :created_at)',
                [
                    'job_id' => $jobId,
                    'old_status' => null,
                    'new_status' => 'pending',
                    'changed_by' => $createdBy,
                    'created_at' => now(),
                ]
            );
        }

        db_execute(
            'INSERT INTO invoices (
                invoice_number, job_id, payment_group, client_id, subtotal_amount, tax_percent, tax_amount, amount, currency,
                pay_currency, pay_method, fx_rate_used, crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
                status, issued_at, due_at, created_at
            ) VALUES (
                :invoice_number, :job_id, :payment_group, :client_id, :subtotal_amount, :tax_percent, :tax_amount, :amount, :currency,
                :pay_currency, :pay_method, :fx_rate_used, :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
                :status, :issued_at, :due_at, :created_at
            )',
            [
                'invoice_number' => $invoiceNo,
                'job_id' => $anchorJobId,
                'payment_group' => $paymentGroup,
                'client_id' => $clientId,
                'subtotal_amount' => round($totalSubtotal, 2),
                'tax_percent' => 0,
                'tax_amount' => round($totalTax, 2),
                'amount' => round($totalAmount, 2),
                'currency' => $currency,
                'pay_currency' => $payCurrency,
                'pay_method' => $payMethod,
                'fx_rate_used' => $bundleFxRateUsed,
                'crypto_asset' => null,
                'crypto_rate_used' => null,
                'crypto_amount_due' => null,
                'crypto_network' => null,
                'rate_locked_at' => null,
                'status' => 'issued',
                'issued_at' => now(),
                'due_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'created_at' => now(),
            ]
        );
        $invoiceId = db_last_id();

        db()->commit();

        foreach ($jobIds as $jobId) {
            maybe_prepare_job_intake_requirements($jobId, $createdBy, 'job_created');
        }

        return [
            'invoice_id' => $invoiceId,
            'job_ids' => $jobIds,
            'payment_group' => $paymentGroup,
        ];
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function get_job(int $id): ?array
{
    return db_one(
        'SELECT j.*, s.name AS service_name, c.full_name AS client_name, c.email AS client_email, c.phone AS client_phone,
                a.full_name AS agent_name,
                am.full_name AS managed_by_admin_name,
                d.full_name AS delivered_by_name,
                ac.full_name AS accepted_by_name,
                CASE
                    WHEN j.payment_status = \'partial\' AND NOT ' . confirmed_payment_exists_sql_for_job('j', 'i4', 'p4') . '
                    THEN \'unpaid\'
                    ELSE j.payment_status
                END AS payment_status_effective
         FROM jobs j
         JOIN services s ON s.id = j.service_id
         JOIN users c ON c.id = j.client_id
         LEFT JOIN users a ON a.id = j.agent_id
         LEFT JOIN users am ON am.id = j.managed_by_admin_id
         LEFT JOIN users d ON d.id = j.delivered_by
         LEFT JOIN users ac ON ac.id = j.accepted_by
         WHERE j.id = :id LIMIT 1',
        ['id' => $id]
    );
}

function get_invoice_by_job_id(int $jobId): ?array
{
    $invoice = db_one('SELECT * FROM invoices WHERE job_id = :job_id LIMIT 1', ['job_id' => $jobId]);
    if ($invoice) {
        return $invoice;
    }

    $job = db_one('SELECT payment_group FROM jobs WHERE id = :id LIMIT 1', ['id' => $jobId]);
    $paymentGroup = trim((string) ($job['payment_group'] ?? ''));
    if ($paymentGroup === '') {
        return null;
    }

    return db_one(
        'SELECT * FROM invoices WHERE payment_group = :payment_group ORDER BY id ASC LIMIT 1',
        ['payment_group' => $paymentGroup]
    );
}

function can_access_job(array $user, array $job): bool
{
    if (in_array($user['role'], ['admin', 'finance'], true)) {
        return true;
    }

    if ($user['role'] === 'client') {
        return (int) $job['client_id'] === (int) $user['id'];
    }

    if ($user['role'] === 'agent') {
        $paymentStatus = (string) ($job['payment_status_effective'] ?? $job['payment_status'] ?? 'unpaid');
        if ($paymentStatus !== 'paid') {
            return false;
        }

        return (int) $job['agent_id'] === (int) $user['id']
            || ($job['agent_id'] === null && empty($job['managed_by_admin_id']));
    }

    return false;
}

function assign_job_agent(int $jobId, int $agentId, int $changedBy): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    if ($job['payment_status'] !== 'paid') {
        throw new RuntimeException('Agent cannot be assigned before payment is confirmed.');
    }

    db_execute(
        'UPDATE jobs
         SET agent_id = :agent_id,
             managed_by_admin_id = NULL,
             updated_at = :updated_at
         WHERE id = :id',
        ['agent_id' => $agentId, 'updated_at' => now(), 'id' => $jobId]
    );
    sync_intake_submission_agent_from_job($jobId);

    add_system_job_comment($jobId, $changedBy, 'Agent assignment updated.');

    create_notification($agentId, 'New assigned job', 'A new paid job has been assigned to you.', url('job', ['id' => $jobId]));
    create_notification((int) $job['client_id'], 'Agent assigned', 'An agent has been assigned to your order.', url('job', ['id' => $jobId]));

    maybe_accrue_commission($jobId);
}

function assign_job_to_admin(int $jobId, int $adminId): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $admin = find_user_by_id($adminId);
    if (!$admin || (string) ($admin['role'] ?? '') !== 'admin') {
        throw new RuntimeException('Admin account not found.');
    }

    db_execute(
        'UPDATE jobs
         SET agent_id = NULL,
             managed_by_admin_id = :admin_id,
             updated_at = :updated_at
         WHERE id = :id',
        [
            'admin_id' => $adminId,
            'updated_at' => now(),
            'id' => $jobId,
        ]
    );
    sync_intake_submission_agent_from_job($jobId);

    add_system_job_comment($jobId, $adminId, 'Job assignment updated. This job is now being handled directly by admin.');
    create_notification((int) $job['client_id'], 'Job assigned to admin', 'Your order is now being handled directly by admin.', url('job', ['id' => $jobId]));
}

function update_job_status(int $jobId, string $newStatus, int $changedBy, ?string $actorRole = null, ?string $statusNote = null): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $newStatus = strtolower(trim($newStatus));
    $allowed = job_status_options();
    if (!in_array($newStatus, $allowed, true)) {
        throw new RuntimeException('Invalid status.');
    }

    if ($job['status'] === $newStatus) {
        return;
    }

    if ($job['payment_status'] !== 'paid' && $newStatus !== 'pending') {
        throw new RuntimeException('Payment is required before processing this service.');
    }

    $actorRole = strtolower(trim((string) $actorRole));
    if ($actorRole === '') {
        $actor = find_user_by_id($changedBy);
        $actorRole = strtolower(trim((string) ($actor['role'] ?? '')));
    }

    if ($actorRole === 'agent') {
        if ((int) ($job['agent_id'] ?? 0) !== $changedBy) {
            throw new RuntimeException('Only the assigned agent can update this job.');
        }
        if (!in_array($newStatus, agent_job_status_options(), true)) {
            throw new RuntimeException('Agents can only move jobs through active delivery states.');
        }
        if (in_array((string) $job['status'], ['closed', 'cancelled'], true)) {
            throw new RuntimeException('Closed or cancelled jobs can only be reopened by admin.');
        }
    } elseif ($actorRole === 'client') {
        if ((int) ($job['client_id'] ?? 0) !== $changedBy) {
            throw new RuntimeException('Only the job owner can update this job.');
        }
        $allowedClientTransitions = [
            'delivered' => ['closed', 'revision_requested'],
        ];
        $currentStatus = (string) ($job['status'] ?? '');
        $allowedTargets = $allowedClientTransitions[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowedTargets, true)) {
            throw new RuntimeException('Client can only accept delivery or request revision after delivery.');
        }
        if ($newStatus === 'revision_requested' && !can_client_request_job_revision($job)) {
            throw new RuntimeException('Revision window has expired for this delivery.');
        }
    } elseif ($actorRole !== 'admin') {
        throw new RuntimeException('You cannot update this job status.');
    }

    $deliveredAt = $job['delivered_at'] ?? null;
    $deliveredBy = $job['delivered_by'] ?? null;
    $acceptedAt = $job['accepted_at'] ?? null;
    $acceptedBy = $job['accepted_by'] ?? null;

    if ($newStatus === 'delivered') {
        $deliveredAt = now();
        $deliveredBy = $changedBy;
        $acceptedAt = null;
        $acceptedBy = null;
    } elseif ($newStatus === 'closed') {
        if (!$deliveredAt) {
            $deliveredAt = now();
        }
        if (!$deliveredBy) {
            $deliveredBy = $changedBy;
        }
        $acceptedAt = now();
        $acceptedBy = $changedBy;
    } elseif (in_array($newStatus, ['pending', 'in_progress', 'waiting_client', 'revision_requested', 'cancelled'], true)) {
        $acceptedAt = null;
        $acceptedBy = null;
    }

    db_execute(
        'UPDATE jobs
         SET status = :status,
             updated_at = :updated_at,
             delivered_at = :delivered_at,
             delivered_by = :delivered_by,
             accepted_by = :accepted_by,
             accepted_at = :accepted_at
         WHERE id = :id',
        [
            'status' => $newStatus,
            'updated_at' => now(),
            'delivered_at' => $deliveredAt,
            'delivered_by' => $deliveredBy,
            'accepted_by' => $acceptedBy,
            'accepted_at' => $acceptedAt,
            'id' => $jobId,
        ]
    );

    record_job_status_change($jobId, (string) $job['status'], $newStatus, $changedBy);

    $targets = array_unique(array_filter([
        (int) $job['client_id'],
        $job['agent_id'] ? (int) $job['agent_id'] : null,
    ]));
    foreach ($targets as $targetId) {
        if ($targetId === (int) $job['client_id'] && $newStatus === 'closed') {
            create_notification(
                $targetId,
                'Your order is complete — leave a review',
                'Order ' . (string) ($job['job_code'] ?? '') . ' is complete. Please rate your experience on its order page.',
                url('job', ['id' => $jobId])
            );
        } elseif ($targetId === (int) $job['client_id'] && $newStatus === 'delivered') {
            create_notification(
                $targetId,
                'Your order has been delivered',
                'Your work is ready. Check your order to accept delivery or request a revision.',
                url('job', ['id' => $jobId])
            );
        } else {
            create_notification($targetId, 'Job status updated', 'Status changed to ' . str_replace('_', ' ', $newStatus) . '.', url('job', ['id' => $jobId]));
        }
    }

    if ($statusNote === null || trim($statusNote) === '') {
        $statusNote = 'Job status updated to ' . ucwords(str_replace('_', ' ', $newStatus)) . '.';
    }
    add_system_job_comment($jobId, $changedBy, trim($statusNote));

    maybe_accrue_commission($jobId);
}

function client_accept_job_delivery(int $jobId, int $clientId): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }
    if ((int) ($job['client_id'] ?? 0) !== $clientId) {
        throw new RuntimeException('You cannot accept this delivery.');
    }
    if ((string) ($job['status'] ?? '') !== 'delivered') {
        throw new RuntimeException('Only delivered jobs can be accepted.');
    }

    update_job_status($jobId, 'closed', $clientId, 'client', 'Client accepted delivery and closed the job.');
}

function client_request_job_revision(int $jobId, int $clientId, string $note = ''): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }
    if ((int) ($job['client_id'] ?? 0) !== $clientId) {
        throw new RuntimeException('You cannot request revision for this job.');
    }
    if ((string) ($job['status'] ?? '') !== 'delivered') {
        throw new RuntimeException('Revision can only be requested after delivery.');
    }
    if (!can_client_request_job_revision($job)) {
        throw new RuntimeException('Revision window has expired for this delivery.');
    }

    $note = trim($note);
    $statusNote = 'Client requested revision on the delivered work.';
    if ($note !== '') {
        $statusNote .= ' Note: ' . $note;
    }

    update_job_status($jobId, 'revision_requested', $clientId, 'client', $statusNote);
    notify_roles(
        ['admin'],
        'Revision requested',
        'A client requested revision on job ' . (string) ($job['job_code'] ?? '') . '.',
        url('job', ['id' => $jobId])
    );
}

function admin_reopen_job(int $jobId, int $adminId, string $note = ''): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    if (!in_array((string) ($job['status'] ?? ''), ['delivered', 'closed', 'revision_requested', 'cancelled'], true)) {
        throw new RuntimeException('Only delivered/closed/revision/cancelled jobs can be reopened.');
    }

    $note = trim($note);
    $statusNote = 'Job reopened by admin for further processing.';
    if ($note !== '') {
        $statusNote .= ' Note: ' . $note;
    }

    update_job_status($jobId, 'in_progress', $adminId, 'admin', $statusNote);
}

function update_job_finance(int $jobId, array $input): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $baseSubtotal = (float) ($job['subtotal_amount'] ?? 0);
    if ($baseSubtotal <= 0) {
        $baseSubtotal = (float) $job['amount'];
    }

    $subtotalAmount = isset($input['subtotal_amount']) && $input['subtotal_amount'] !== ''
        ? (float) $input['subtotal_amount']
        : (isset($input['amount']) && $input['amount'] !== '' ? (float) $input['amount'] : $baseSubtotal);

    $taxPercent = isset($input['tax_percent']) && $input['tax_percent'] !== ''
        ? (float) $input['tax_percent']
        : (float) ($job['tax_percent'] ?? 0);
    $taxPercent = max(0.0, min(100.0, $taxPercent));
    $taxAmount = round(($subtotalAmount * $taxPercent) / 100, 2);
    $amount = round($subtotalAmount + $taxAmount, 2);

    $commissionPercent = isset($input['commission_percent']) && $input['commission_percent'] !== ''
        ? (float) $input['commission_percent']
        : (float) $job['commission_percent'];

    $currency = strtoupper(trim($input['currency'] ?? $job['currency'] ?? default_currency()));
    if (!in_array($currency, currency_options(), true)) {
        $currency = default_currency();
    }

    $commissionAmount = calc_commission_amount($subtotalAmount, $commissionPercent);
    $paymentStatus = $input['payment_status'] ?? $job['payment_status'];

    db_execute(
        'UPDATE jobs SET subtotal_amount = :subtotal_amount, tax_percent = :tax_percent, tax_amount = :tax_amount,
         amount = :amount, currency = :currency, pay_currency = :pay_currency, commission_percent = :commission_percent, commission_amount = :commission_amount,
         payment_status = :payment_status, payment_reference = :payment_reference, updated_at = :updated_at WHERE id = :id',
        [
            'subtotal_amount' => $subtotalAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'amount' => $amount,
            'currency' => $currency,
            'pay_currency' => in_array($currency, payment_currency_options(), true)
                ? normalize_payment_currency($currency)
                : normalize_payment_currency(default_currency()),
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'payment_status' => $paymentStatus,
            'payment_reference' => trim($input['payment_reference'] ?? $job['payment_reference'] ?? ''),
            'updated_at' => now(),
            'id' => $jobId,
        ]
    );

    db_execute(
        'UPDATE invoices SET subtotal_amount = :subtotal_amount, tax_percent = :tax_percent, tax_amount = :tax_amount,
         amount = :amount, currency = :currency, pay_currency = :pay_currency, status = :status WHERE job_id = :job_id',
        [
            'subtotal_amount' => $subtotalAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'amount' => $amount,
            'currency' => $currency,
            'pay_currency' => in_array($currency, payment_currency_options(), true)
                ? normalize_payment_currency($currency)
                : normalize_payment_currency(default_currency()),
            'status' => $paymentStatus === 'paid' ? 'paid' : 'issued',
            'job_id' => $jobId,
        ]
    );

    create_notification((int) $job['client_id'], 'Payment status updated', 'Payment status changed to ' . $paymentStatus . '.', url('job', ['id' => $jobId]));

    maybe_accrue_commission($jobId);
    if ($paymentStatus === 'paid') {
        maybe_prepare_job_intake_requirements($jobId, (int) ($input['changed_by'] ?? $job['client_id']), 'payment_confirmed');
    }
}

function add_job_comment_internal(int $jobId, int $userId, string $comment, string $commentType = 'user', bool $notifyTargets = true): void
{
    $comment = trim($comment);
    if ($comment === '') {
        throw new RuntimeException('Comment cannot be empty.');
    }

    if (!in_array($commentType, ['user', 'system'], true)) {
        $commentType = 'user';
    }

    if ($userId <= 0 || !find_user_by_id($userId)) {
        $fallback = db_one(
            "SELECT id FROM users WHERE role = 'admin' AND status = 'active' AND account_state = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1"
        );
        if ($fallback) {
            $userId = (int) $fallback['id'];
        } else {
            $jobRef = db_one('SELECT client_id FROM jobs WHERE id = :id LIMIT 1', ['id' => $jobId]);
            $userId = (int) ($jobRef['client_id'] ?? 0);
        }
    }

    db_execute(
        'INSERT INTO job_comments (job_id, user_id, comment_type, comment, created_at)
         VALUES (:job_id, :user_id, :comment_type, :comment, :created_at)',
        [
            'job_id' => $jobId,
            'user_id' => $userId,
            'comment_type' => $commentType,
            'comment' => $comment,
            'created_at' => now(),
        ]
    );

    if (!$notifyTargets) {
        return;
    }

    $job = get_job($jobId);
    if (!$job) {
        return;
    }

    $targets = array_unique(array_filter([
        (int) $job['client_id'],
        $job['agent_id'] ? (int) $job['agent_id'] : null,
    ]));

    foreach ($targets as $targetId) {
        if ($targetId === $userId) {
            continue;
        }

        $title = $commentType === 'system' ? 'Job update' : 'New comment on job';
        $message = $commentType === 'system'
            ? $comment
            : 'A new comment was added to ' . $job['job_code'] . '.';
        create_notification($targetId, $title, $message, url('job', ['id' => $jobId]));
    }
}

function add_comment(int $jobId, int $userId, string $comment): void
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    if (!can_user_comment_on_job($user, $job)) {
        throw new RuntimeException('This job has been delivered. Use "Accept delivery" or "Request revision" to continue.');
    }

    add_job_comment_internal($jobId, $userId, $comment, 'user', true);
}

function add_system_job_comment(int $jobId, int $actorUserId, string $comment, bool $notifyTargets = true): void
{
    add_job_comment_internal($jobId, $actorUserId, $comment, 'system', $notifyTargets);
}

function list_comments(int $jobId): array
{
    return db_all(
        'SELECT c.*, u.full_name, u.role
         FROM job_comments c
         LEFT JOIN users u ON u.id = c.user_id
         WHERE c.job_id = :job_id
         ORDER BY c.created_at ASC',
        ['job_id' => $jobId]
    );
}

function intake_decode_json_array(?string $value): array
{
    $decoded = json_decode((string) ($value ?? ''), true);
    return is_array($decoded) ? $decoded : [];
}

function list_attached_services_for_form(int $formId): array
{
    return db_all(
        'SELECT sif.*, s.name AS service_name
         FROM service_intake_forms sif
         JOIN services s ON s.id = sif.service_id
         WHERE sif.intake_form_id = :intake_form_id
         ORDER BY s.name ASC',
        ['intake_form_id' => $formId]
    );
}

function get_intake_submission(int $submissionId): ?array
{
    $row = db_one(
        'SELECT sub.*, f.title AS form_title, f.description AS form_description,
                s.name AS service_name, j.job_code, j.status AS job_status, j.payment_status, j.agent_id, j.client_id
         FROM intake_submissions sub
         JOIN intake_forms f ON f.id = sub.intake_form_id
         JOIN services s ON s.id = sub.service_id
         JOIN jobs j ON j.id = sub.job_id
         WHERE sub.id = :id LIMIT 1',
        ['id' => $submissionId]
    );

    if (!$row) {
        return null;
    }

    $row['schema_snapshot'] = normalize_intake_schema(intake_decode_json_array((string) ($row['schema_snapshot_json'] ?? '')));
    $row['data'] = intake_decode_json_array((string) ($row['data_json'] ?? ''));
    $row['files'] = intake_decode_json_array((string) ($row['files_json'] ?? ''));

    return $row;
}

function find_intake_submission_file(int $submissionId, string $fieldKey, int $fileIndex = 0): ?array
{
    $submission = get_intake_submission($submissionId);
    if (!$submission) {
        return null;
    }

    $fieldKey = trim($fieldKey);
    if ($fieldKey === '') {
        return null;
    }

    $files = intake_decode_json_array((string) ($submission['files_json'] ?? ''));
    if (!isset($files[$fieldKey]) || !is_array($files[$fieldKey])) {
        return null;
    }

    $entry = $files[$fieldKey][$fileIndex] ?? null;
    if (!is_array($entry) || empty($entry['storage_key'])) {
        return null;
    }

    return [
        'file' => $entry,
        'submission' => $submission,
        'field_key' => $fieldKey,
        'file_index' => $fileIndex,
    ];
}

function list_intake_submission_events(int $submissionId): array
{
    return db_all(
        'SELECT e.*, u.full_name AS actor_name, u.role AS actor_role
         FROM audit_log_intake_submission_events e
         LEFT JOIN users u ON u.id = e.actor_user_id
         WHERE e.submission_id = :submission_id
         ORDER BY e.created_at ASC, e.id ASC',
        ['submission_id' => $submissionId]
    );
}

function add_intake_submission_event(int $submissionId, ?int $actorUserId, string $eventType, string $note = ''): void
{
    db_execute(
        'INSERT INTO audit_log_intake_submission_events (submission_id, actor_user_id, event_type, note, created_at)
         VALUES (:submission_id, :actor_user_id, :event_type, :note, :created_at)',
        [
            'submission_id' => $submissionId,
            'actor_user_id' => $actorUserId,
            'event_type' => trim($eventType),
            'note' => trim($note),
            'created_at' => now(),
        ]
    );
}

function can_access_intake_submission(array $user, array $submission): bool
{
    $role = (string) ($user['role'] ?? '');
    if (in_array($role, ['admin', 'finance'], true)) {
        return true;
    }

    if ($role === 'client') {
        return (int) ($submission['client_user_id'] ?? 0) === (int) ($user['id'] ?? 0)
            && (string) ($submission['job_status'] ?? '') !== 'closed';
    }

    if ($role === 'agent') {
        $agentId = (int) ($user['id'] ?? 0);
        $assignedInSubmission = (int) ($submission['assigned_agent_user_id'] ?? 0) === $agentId;
        $assignedInJob = (int) ($submission['agent_id'] ?? 0) === $agentId;
        return $assignedInSubmission || $assignedInJob;
    }

    return false;
}

function list_job_intake_submissions(int $jobId): array
{
    $rows = db_all(
        'SELECT sub.*, f.title AS form_title, f.description AS form_description, j.agent_id
         FROM intake_submissions sub
         JOIN intake_forms f ON f.id = sub.intake_form_id
         JOIN jobs j ON j.id = sub.job_id
         WHERE sub.job_id = :job_id
         ORDER BY sub.id ASC',
        ['job_id' => $jobId]
    );

    foreach ($rows as &$row) {
        $row['schema_snapshot'] = normalize_intake_schema(intake_decode_json_array((string) ($row['schema_snapshot_json'] ?? '')));
        $row['data'] = intake_decode_json_array((string) ($row['data_json'] ?? ''));
        $row['files'] = intake_decode_json_array((string) ($row['files_json'] ?? ''));
    }
    unset($row);

    return $rows;
}

function list_job_intake_submissions_for_user(int $jobId, array $user): array
{
    $all = list_job_intake_submissions($jobId);
    $out = [];
    foreach ($all as $submission) {
        if (can_access_intake_submission($user, $submission)) {
            $out[] = $submission;
        }
    }

    return $out;
}

function intake_job_completion_summary(int $jobId): array
{
    $rows = list_job_intake_submissions($jobId);
    if (!$rows) {
        return [
            'required_total' => 0,
            'submitted_total' => 0,
            'pending_total' => 0,
            'all_submitted' => true,
        ];
    }

    $requiredTotal = 0;
    $submittedTotal = 0;

    foreach ($rows as $row) {
        $required = true;
        $pivot = db_one(
            'SELECT is_required FROM service_intake_forms WHERE service_id = :service_id AND intake_form_id = :intake_form_id LIMIT 1',
            ['service_id' => (int) $row['service_id'], 'intake_form_id' => (int) $row['intake_form_id']]
        );
        if ($pivot && (int) ($pivot['is_required'] ?? 1) !== 1) {
            $required = false;
        }
        if (!$required) {
            continue;
        }

        $requiredTotal++;
        if ((string) ($row['status'] ?? 'draft') === 'submitted') {
            $submittedTotal++;
        }
    }

    return [
        'required_total' => $requiredTotal,
        'submitted_total' => $submittedTotal,
        'pending_total' => max(0, $requiredTotal - $submittedTotal),
        'all_submitted' => $requiredTotal === 0 ? true : $submittedTotal >= $requiredTotal,
    ];
}

function create_intake_submission_draft(array $job, array $serviceForm, int $actorUserId): int
{
    $form = get_intake_form((int) ($serviceForm['intake_form_id'] ?? 0));
    if (!$form) {
        throw new RuntimeException('Attached intake form could not be found.');
    }

    $schema = normalize_intake_schema(is_array($form['schema'] ?? null) ? $form['schema'] : intake_decode_json_array((string) ($form['schema_json'] ?? '')));
    $schemaJson = (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hash = intake_schema_hash($schema);

    db_execute(
        'INSERT INTO intake_submissions (
            intake_form_id, service_id, job_id, client_user_id, assigned_agent_user_id,
            form_version_hash, schema_snapshot_json, data_json, files_json, status, update_request_note, submitted_at, created_at, updated_at
         ) VALUES (
            :intake_form_id, :service_id, :job_id, :client_user_id, :assigned_agent_user_id,
            :form_version_hash, :schema_snapshot_json, :data_json, :files_json, :status, :update_request_note, :submitted_at, :created_at, :updated_at
         )',
        [
            'intake_form_id' => (int) $form['id'],
            'service_id' => (int) $job['service_id'],
            'job_id' => (int) $job['id'],
            'client_user_id' => (int) $job['client_id'],
            'assigned_agent_user_id' => isset($job['agent_id']) && $job['agent_id'] !== null ? (int) $job['agent_id'] : null,
            'form_version_hash' => $hash,
            'schema_snapshot_json' => $schemaJson,
            'data_json' => '{}',
            'files_json' => '{}',
            'status' => 'draft',
            'update_request_note' => null,
            'submitted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $submissionId = db_last_id();
    add_intake_submission_event($submissionId, $actorUserId, 'created', 'Draft intake submission created.');

    return $submissionId;
}

function sync_intake_submission_agent_from_job(int $jobId): void
{
    $job = get_job($jobId);
    if (!$job) {
        return;
    }

    db_execute(
        'UPDATE intake_submissions
         SET assigned_agent_user_id = :assigned_agent_user_id, updated_at = :updated_at
         WHERE job_id = :job_id',
        [
            'assigned_agent_user_id' => isset($job['agent_id']) && $job['agent_id'] !== null ? (int) $job['agent_id'] : null,
            'updated_at' => now(),
            'job_id' => $jobId,
        ]
    );
}

function maybe_prepare_job_intake_requirements(int $jobId, int $actorUserId, string $trigger = 'job_created'): void
{
    $job = get_job($jobId);
    if (!$job) {
        return;
    }

    $serviceForms = list_intake_forms_for_service((int) $job['service_id'], true);
    if (!$serviceForms) {
        return;
    }

    $requiredForms = [];
    foreach ($serviceForms as $serviceForm) {
        if ((int) ($serviceForm['is_required'] ?? 1) === 1) {
            $requiredForms[] = $serviceForm;
        }
    }
    if (!$requiredForms) {
        return;
    }

    $createdCount = 0;
    foreach ($requiredForms as $serviceForm) {
        $existing = db_one(
            'SELECT id FROM intake_submissions WHERE job_id = :job_id AND intake_form_id = :intake_form_id LIMIT 1',
            [
                'job_id' => $jobId,
                'intake_form_id' => (int) $serviceForm['intake_form_id'],
            ]
        );

        if ($existing) {
            continue;
        }

        create_intake_submission_draft($job, $serviceForm, $actorUserId);
        $createdCount++;
    }

    if ($createdCount <= 0) {
        sync_intake_submission_agent_from_job($jobId);
        return;
    }

    sync_intake_submission_agent_from_job($jobId);

    $submissionRows = list_job_intake_submissions($jobId);
    $submissionLinks = [];
    foreach ($submissionRows as $submissionRow) {
        $pivot = db_one(
            'SELECT is_required FROM service_intake_forms WHERE service_id = :service_id AND intake_form_id = :intake_form_id LIMIT 1',
            [
                'service_id' => (int) ($submissionRow['service_id'] ?? 0),
                'intake_form_id' => (int) ($submissionRow['intake_form_id'] ?? 0),
            ]
        );
        if ($pivot && (int) ($pivot['is_required'] ?? 1) !== 1) {
            continue;
        }

        $submissionLinks[] = '- ' . (string) ($submissionRow['form_title'] ?? 'Intake form')
            . ': ' . portal_absolute_url(url('intake_submission', ['id' => (int) $submissionRow['id']]));
    }

    $message = 'Action required: Please complete the intake form(s) to enable processing.';
    if ($submissionLinks !== []) {
        $message .= "\n" . implode("\n", $submissionLinks);
    }

    add_system_job_comment($jobId, $actorUserId, $message, true);

    create_notification(
        (int) $job['client_id'],
        'Intake form required',
        'Please complete the required intake form(s) for job ' . (string) $job['job_code'] . '.',
        url('job', ['id' => $jobId])
    );

    notify_roles(
        ['admin', 'finance'],
        'Intake forms ready',
        'Required intake forms are now available for job ' . (string) $job['job_code'] . '.',
        url('job', ['id' => $jobId])
    );
}

function sanitize_intake_answer_value(mixed $value): mixed
{
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = sanitize_intake_answer_value($v);
        }
        return $out;
    }

    if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
        return $value;
    }

    return trim((string) $value);
}

function normalize_intake_uploaded_files_payload(array $rawFiles): array
{
    $names = $rawFiles['name'] ?? null;
    if (!is_array($names)) {
        return [];
    }

    $types = is_array($rawFiles['type'] ?? null) ? $rawFiles['type'] : [];
    $tmpNames = is_array($rawFiles['tmp_name'] ?? null) ? $rawFiles['tmp_name'] : [];
    $errors = is_array($rawFiles['error'] ?? null) ? $rawFiles['error'] : [];
    $sizes = is_array($rawFiles['size'] ?? null) ? $rawFiles['size'] : [];

    $normalized = [];
    foreach ($names as $fieldKeyRaw => $nameNode) {
        $fieldKey = trim((string) $fieldKeyRaw);
        if ($fieldKey === '') {
            continue;
        }

        $typeNode = $types[$fieldKeyRaw] ?? '';
        $tmpNode = $tmpNames[$fieldKeyRaw] ?? '';
        $errorNode = $errors[$fieldKeyRaw] ?? UPLOAD_ERR_NO_FILE;
        $sizeNode = $sizes[$fieldKeyRaw] ?? 0;

        if (is_array($nameNode)) {
            foreach ($nameNode as $index => $nameValue) {
                $normalized[$fieldKey][] = [
                    'name' => (string) $nameValue,
                    'type' => is_array($typeNode) ? (string) ($typeNode[$index] ?? '') : '',
                    'tmp_name' => is_array($tmpNode) ? (string) ($tmpNode[$index] ?? '') : '',
                    'error' => is_array($errorNode) ? (int) ($errorNode[$index] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
                    'size' => is_array($sizeNode) ? (int) ($sizeNode[$index] ?? 0) : 0,
                ];
            }
            continue;
        }

        $normalized[$fieldKey][] = [
            'name' => (string) $nameNode,
            'type' => (string) $typeNode,
            'tmp_name' => (string) $tmpNode,
            'error' => (int) $errorNode,
            'size' => (int) $sizeNode,
        ];
    }

    return $normalized;
}

function normalize_intake_remove_file_indexes(array $rawRemove): array
{
    $normalized = [];
    foreach ($rawRemove as $fieldKeyRaw => $indexes) {
        $fieldKey = trim((string) $fieldKeyRaw);
        if ($fieldKey === '') {
            continue;
        }
        $list = is_array($indexes) ? $indexes : [$indexes];
        foreach ($list as $idx) {
            if ($idx === '' || $idx === null) {
                continue;
            }
            $normalized[$fieldKey][] = max(0, (int) $idx);
        }

        if (isset($normalized[$fieldKey])) {
            $normalized[$fieldKey] = array_values(array_unique($normalized[$fieldKey]));
        }
    }

    return $normalized;
}

function intake_schema_fields_map(array $schema): array
{
    $map = [];
    $steps = is_array($schema['steps'] ?? null) ? $schema['steps'] : [];
    foreach ($steps as $step) {
        $fields = is_array($step['fields'] ?? null) ? $step['fields'] : [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $map[$key] = $field;
        }
    }

    return $map;
}

function intake_answer_is_empty(mixed $value): bool
{
    if (is_array($value)) {
        foreach ($value as $entry) {
            if (!intake_answer_is_empty($entry)) {
                return false;
            }
        }
        return true;
    }

    if (is_bool($value)) {
        return $value === false;
    }

    if (is_int($value) || is_float($value)) {
        return false;
    }

    return trim((string) $value) === '';
}

function intake_normalize_condition_values(mixed $value): array
{
    if (is_array($value)) {
        return array_map(static fn(mixed $entry): string => (string) $entry, $value);
    }
    if ($value === null || $value === '') {
        return [];
    }
    return [(string) $value];
}

function intake_evaluate_condition_rule(array $rule, array $answers): bool
{
    $key = trim((string) ($rule['field'] ?? $rule['key'] ?? $rule['dependsOn'] ?? $rule['fieldKey'] ?? ''));
    if ($key === '') {
        return true;
    }

    $operator = strtolower(trim((string) ($rule['operator'] ?? $rule['op'] ?? 'equals')));
    $expected = $rule['value'] ?? null;
    $actual = $answers[$key] ?? null;

    if (is_array($actual)) {
        $actualList = array_map(static fn(mixed $entry): string => (string) $entry, $actual);
        if ($operator === 'equals') {
            return in_array((string) $expected, $actualList, true);
        }
        if ($operator === 'not_equals') {
            return !in_array((string) $expected, $actualList, true);
        }
        if ($operator === 'in') {
            foreach (intake_normalize_condition_values($expected) as $candidate) {
                if (in_array($candidate, $actualList, true)) {
                    return true;
                }
            }
            return false;
        }
        if ($operator === 'not_in') {
            foreach (intake_normalize_condition_values($expected) as $candidate) {
                if (in_array($candidate, $actualList, true)) {
                    return false;
                }
            }
            return true;
        }
        if ($operator === 'contains') {
            return stripos(implode(' ', $actualList), (string) $expected) !== false;
        }
        if ($operator === 'empty' || $operator === 'falsy') {
            return $actualList === [];
        }
        if ($operator === 'not_empty' || $operator === 'truthy') {
            return $actualList !== [];
        }
        return true;
    }

    $actualString = trim((string) ($actual ?? ''));
    $expectedString = (string) ($expected ?? '');
    $expectedList = intake_normalize_condition_values($expected);

    return match ($operator) {
        'equals' => $actualString === $expectedString,
        'not_equals' => $actualString !== $expectedString,
        'in' => in_array($actualString, $expectedList, true),
        'not_in' => !in_array($actualString, $expectedList, true),
        'contains' => stripos($actualString, $expectedString) !== false,
        'empty', 'falsy' => $actualString === '',
        'not_empty', 'truthy' => $actualString !== '',
        default => true,
    };
}

function intake_evaluate_conditions(mixed $conditionSet, array $answers): bool
{
    if ($conditionSet === null || $conditionSet === [] || $conditionSet === '') {
        return true;
    }

    if (is_array($conditionSet) && isset($conditionSet['all']) && is_array($conditionSet['all'])) {
        foreach ($conditionSet['all'] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if (!intake_evaluate_condition_rule($rule, $answers)) {
                return false;
            }
        }
        return true;
    }

    if (is_array($conditionSet) && isset($conditionSet['any']) && is_array($conditionSet['any'])) {
        if ($conditionSet['any'] === []) {
            return true;
        }
        foreach ($conditionSet['any'] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if (intake_evaluate_condition_rule($rule, $answers)) {
                return true;
            }
        }
        return false;
    }

    if (is_array($conditionSet)) {
        foreach ($conditionSet as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if (!intake_evaluate_condition_rule($rule, $answers)) {
                return false;
            }
        }
        return true;
    }

    return true;
}

function intake_validate_phone_value(string $value): bool
{
    $normalized = preg_replace('/[\s\-\(\)]/', '', trim($value));
    if ($normalized === null || $normalized === '') {
        return false;
    }

    if ((bool) preg_match('/^\+[1-9]\d{7,14}$/', $normalized)) {
        return true;
    }

    if ((bool) preg_match('/^0\d{10}$/', $normalized)) {
        return true;
    }

    if ((bool) preg_match('/^(?:\+234|234)\d{10}$/', $normalized)) {
        return true;
    }

    return false;
}

function intake_validate_date_value(string $value): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date) {
        return false;
    }

    return $date->format('Y-m-d') === $value;
}

function intake_collect_non_empty_uploads(array $fileEntries): array
{
    $out = [];
    foreach ($fileEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (($entry['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = $entry;
    }
    return $out;
}

function intake_remove_stored_file(array $fileEntry): void
{
    $storageKey = trim((string) ($fileEntry['storage_key'] ?? ''));
    if ($storageKey === '') {
        return;
    }

    $realFile = realpath($storageKey);
    $uploadRoot = realpath(upload_directory());
    if ($realFile === false || $uploadRoot === false) {
        return;
    }

    if (!str_starts_with($realFile, $uploadRoot)) {
        return;
    }

    if (is_file($realFile)) {
        @unlink($realFile);
    }
}

function intake_validate_submission_payload(array $schema, array $answers, array $files): array
{
    $errors = [];
    $steps = is_array($schema['steps'] ?? null) ? $schema['steps'] : [];

    foreach ($steps as $step) {
        $fields = is_array($step['fields'] ?? null) ? $step['fields'] : [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $isVisible = intake_evaluate_conditions($field['conditions'] ?? [], $answers);
            if (!$isVisible) {
                continue;
            }

            $fieldLabel = trim((string) ($field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey))));
            $fieldType = strtolower(trim((string) ($field['type'] ?? 'text')));
            $required = !empty($field['required']);
            $validation = is_array($field['validation'] ?? null) ? $field['validation'] : [];
            $value = $answers[$fieldKey] ?? null;

            if ($fieldType === 'file') {
                $currentFiles = isset($files[$fieldKey]) && is_array($files[$fieldKey]) ? array_values($files[$fieldKey]) : [];
                if ($required && $currentFiles === []) {
                    $errors[$fieldKey] = $fieldLabel . ' is required.';
                    continue;
                }
                if (empty($field['multiple']) && count($currentFiles) > 1) {
                    $errors[$fieldKey] = $fieldLabel . ' accepts only one file.';
                }
                continue;
            }

            if (in_array($fieldType, ['checkbox', 'multiselect'], true)) {
                $selected = [];
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $itemText = trim((string) $item);
                        if ($itemText !== '') {
                            $selected[] = $itemText;
                        }
                    }
                } elseif (trim((string) ($value ?? '')) !== '') {
                    $selected[] = trim((string) $value);
                }

                if ($required && $selected === []) {
                    $errors[$fieldKey] = $fieldLabel . ' is required.';
                    continue;
                }
            } elseif ($fieldType === 'address') {
                $line1 = '';
                if (is_array($value)) {
                    $line1 = trim((string) ($value['line1'] ?? ''));
                }
                if ($required && $line1 === '') {
                    $errors[$fieldKey] = $fieldLabel . ' is required.';
                    continue;
                }
            } elseif ($fieldType === 'repeating_group') {
                $rows = is_array($value) ? $value : [];
                $hasContent = false;
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    if (!intake_answer_is_empty($row)) {
                        $hasContent = true;
                        break;
                    }
                }
                if ($required && !$hasContent) {
                    $errors[$fieldKey] = $fieldLabel . ' is required.';
                    continue;
                }
            } else {
                $valueText = trim((string) ($value ?? ''));
                if ($required && $valueText === '') {
                    $errors[$fieldKey] = $fieldLabel . ' is required.';
                    continue;
                }

                if ($valueText === '') {
                    continue;
                }

                if ($fieldType === 'email' && !filter_var($valueText, FILTER_VALIDATE_EMAIL)) {
                    $errors[$fieldKey] = $fieldLabel . ' must be a valid email address.';
                    continue;
                }

                if ($fieldType === 'phone' && !intake_validate_phone_value($valueText)) {
                    $errors[$fieldKey] = $fieldLabel . ' must be a valid phone number (example: +2348012345678 or 08012345678).';
                    continue;
                }

                if ($fieldType === 'date') {
                    if (!intake_validate_date_value($valueText)) {
                        $errors[$fieldKey] = $fieldLabel . ' must be a valid date.';
                        continue;
                    }
                    $minDate = trim((string) ($validation['min'] ?? $validation['minDate'] ?? ''));
                    if ($minDate !== '' && intake_validate_date_value($minDate) && strcmp($valueText, $minDate) < 0) {
                        $errors[$fieldKey] = $fieldLabel . ' must be on or after ' . $minDate . '.';
                        continue;
                    }
                    $maxDate = trim((string) ($validation['max'] ?? $validation['maxDate'] ?? ''));
                    if ($maxDate !== '' && intake_validate_date_value($maxDate) && strcmp($valueText, $maxDate) > 0) {
                        $errors[$fieldKey] = $fieldLabel . ' must be on or before ' . $maxDate . '.';
                        continue;
                    }
                }

                if ($fieldType === 'number') {
                    $normalizedNumber = str_replace(',', '', $valueText);
                    if (!is_numeric($normalizedNumber)) {
                        $errors[$fieldKey] = $fieldLabel . ' must be a valid number.';
                        continue;
                    }
                    if (isset($validation['min']) && $validation['min'] !== '' && (float) $normalizedNumber < (float) $validation['min']) {
                        $errors[$fieldKey] = $fieldLabel . ' must be at least ' . $validation['min'] . '.';
                        continue;
                    }
                    if (isset($validation['max']) && $validation['max'] !== '' && (float) $normalizedNumber > (float) $validation['max']) {
                        $errors[$fieldKey] = $fieldLabel . ' must be at most ' . $validation['max'] . '.';
                        continue;
                    }
                }

                if (isset($validation['minLength']) && $validation['minLength'] !== '' && mb_strlen($valueText) < (int) $validation['minLength']) {
                    $errors[$fieldKey] = $fieldLabel . ' must be at least ' . (int) $validation['minLength'] . ' characters.';
                    continue;
                }

                if (isset($validation['maxLength']) && $validation['maxLength'] !== '' && mb_strlen($valueText) > (int) $validation['maxLength']) {
                    $errors[$fieldKey] = $fieldLabel . ' must be at most ' . (int) $validation['maxLength'] . ' characters.';
                    continue;
                }

                $pattern = trim((string) ($validation['pattern'] ?? ''));
                if ($pattern !== '') {
                    $regex = $pattern;
                    $delimiter = substr($regex, 0, 1);
                    $hasDelimiter = $delimiter !== '' && strrpos($regex, $delimiter) !== 0;
                    if (!$hasDelimiter) {
                        $regex = '/' . str_replace('/', '\/', $pattern) . '/';
                    }
                    $matched = @preg_match($regex, $valueText);
                    if ($matched === 0) {
                        $errors[$fieldKey] = $fieldLabel . ' format is invalid.';
                        continue;
                    }
                }
            }
        }
    }

    return $errors;
}

function validate_intake_upload_file(array $file): void
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('One of the intake files failed to upload.');
    }

    if ((int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('Intake file too large. Max 12MB per file.');
    }

    $original = (string) ($file['name'] ?? 'file.bin');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'], true)) {
        throw new RuntimeException('Only PNG, JPG, JPEG, and PDF files are allowed.');
    }
}

function store_intake_submission_file(array $file, int $submissionId, string $fieldKey): array
{
    validate_intake_upload_file($file);

    ensure_upload_directory();
    $dir = upload_directory() . '/intake/submission_' . $submissionId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $original = (string) ($file['name'] ?? 'file.bin');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $stored = time() . '_' . bin2hex(random_bytes(4)) . '_' . slugify($fieldKey) . '.' . $ext;
    $path = $dir . '/' . $stored;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $path)) {
        throw new RuntimeException('Could not save uploaded intake file.');
    }

    return [
        'storage_key' => $path,
        'original_name' => $original,
        'mime' => mime_content_type($path) ?: 'application/octet-stream',
        'size' => (int) ($file['size'] ?? 0),
        'uploaded_at' => now(),
    ];
}

function save_intake_submission_draft(int $submissionId, int $clientUserId, array $answers, array $files = [], bool $isResubmit = false, array $removeFiles = []): void
{
    $submission = get_intake_submission($submissionId);
    if (!$submission) {
        throw new RuntimeException('Intake submission not found.');
    }

    if ((int) $submission['client_user_id'] !== $clientUserId) {
        throw new RuntimeException('You cannot update this intake submission.');
    }

    $status = (string) ($submission['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'update_requested'], true)) {
        throw new RuntimeException('This intake submission is locked.');
    }

    $schemaSnapshot = normalize_intake_schema(intake_decode_json_array((string) ($submission['schema_snapshot_json'] ?? '')));
    $fieldMap = intake_schema_fields_map($schemaSnapshot);

    $cleanAnswers = [];
    foreach ($answers as $key => $value) {
        $fieldKey = trim((string) $key);
        if ($fieldKey === '' || !isset($fieldMap[$fieldKey])) {
            continue;
        }
        $cleanAnswers[$fieldKey] = sanitize_intake_answer_value($value);
    }

    $existingFiles = intake_decode_json_array((string) ($submission['files_json'] ?? ''));
    foreach ($existingFiles as $fieldKey => $entries) {
        if (!is_array($entries)) {
            $existingFiles[$fieldKey] = [];
            continue;
        }
        $existingFiles[$fieldKey] = array_values(array_filter($entries, static fn(mixed $entry): bool => is_array($entry)));
    }

    foreach ($removeFiles as $fieldKeyRaw => $indexes) {
        $fieldKey = trim((string) $fieldKeyRaw);
        if ($fieldKey === '' || !isset($existingFiles[$fieldKey]) || !is_array($existingFiles[$fieldKey])) {
            continue;
        }
        $indexList = is_array($indexes) ? $indexes : [$indexes];
        foreach ($indexList as $idx) {
            $index = max(0, (int) $idx);
            if (!isset($existingFiles[$fieldKey][$index]) || !is_array($existingFiles[$fieldKey][$index])) {
                continue;
            }
            intake_remove_stored_file($existingFiles[$fieldKey][$index]);
            unset($existingFiles[$fieldKey][$index]);
        }
        $existingFiles[$fieldKey] = array_values($existingFiles[$fieldKey]);
    }

    foreach ($files as $fieldKeyRaw => $fileEntries) {
        $fieldKey = trim((string) $fieldKeyRaw);
        if ($fieldKey === '' || !isset($fieldMap[$fieldKey])) {
            continue;
        }

        $fieldConfig = $fieldMap[$fieldKey];
        $fieldType = strtolower((string) ($fieldConfig['type'] ?? 'text'));
        if ($fieldType !== 'file') {
            continue;
        }

        $entries = is_array($fileEntries) ? $fileEntries : [$fileEntries];
        $uploads = intake_collect_non_empty_uploads($entries);
        if ($uploads === []) {
            continue;
        }

        $allowMultiple = !empty($fieldConfig['multiple']);
        if (!$allowMultiple && count($uploads) > 1) {
            throw new RuntimeException((string) ($fieldConfig['label'] ?? 'This file field') . ' accepts only one file.');
        }

        if (!isset($existingFiles[$fieldKey]) || !is_array($existingFiles[$fieldKey])) {
            $existingFiles[$fieldKey] = [];
        }

        if (!$allowMultiple) {
            foreach ($existingFiles[$fieldKey] as $storedEntry) {
                if (is_array($storedEntry)) {
                    intake_remove_stored_file($storedEntry);
                }
            }
            $existingFiles[$fieldKey] = [];
            $uploads = [reset($uploads)];
        }

        foreach ($uploads as $uploadEntry) {
            if (!is_array($uploadEntry)) {
                continue;
            }
            $stored = store_intake_submission_file($uploadEntry, $submissionId, $fieldKey);
            $existingFiles[$fieldKey][] = $stored;
        }
    }

    if ($isResubmit) {
        $errors = intake_validate_submission_payload($schemaSnapshot, $cleanAnswers, $existingFiles);
        if ($errors !== []) {
            $firstError = reset($errors);
            if (!is_string($firstError) || trim($firstError) === '') {
                $firstError = 'Please complete all required fields correctly before submitting.';
            }
            throw new RuntimeException($firstError);
        }
    }

    $newStatus = $isResubmit ? 'submitted' : 'draft';
    $updateNote = $isResubmit ? null : (string) ($submission['update_request_note'] ?? null);

    db_execute(
        'UPDATE intake_submissions
         SET data_json = :data_json, files_json = :files_json, status = :status, update_request_note = :update_request_note,
             submitted_at = :submitted_at, updated_at = :updated_at
         WHERE id = :id',
        [
            'data_json' => (string) json_encode($cleanAnswers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'files_json' => (string) json_encode($existingFiles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'status' => $newStatus,
            'update_request_note' => $updateNote,
            'submitted_at' => $isResubmit ? now() : (string) ($submission['submitted_at'] ?: null),
            'updated_at' => now(),
            'id' => $submissionId,
        ]
    );

    add_intake_submission_event(
        $submissionId,
        $clientUserId,
        $isResubmit ? 'submitted' : 'draft_saved',
        $isResubmit ? 'Client submitted intake form.' : 'Client saved intake draft.'
    );

    if ($isResubmit) {
        add_system_job_comment((int) $submission['job_id'], $clientUserId, 'Intake form submitted: ' . (string) $submission['form_title'] . '.', true);
        notify_roles(
            ['admin', 'finance'],
            'Intake form submitted',
            (string) $submission['form_title'] . ' was submitted for job ' . (string) $submission['job_code'] . '.',
            url('job', ['id' => (int) $submission['job_id']])
        );
        if ((int) ($submission['assigned_agent_user_id'] ?? 0) > 0) {
            create_notification(
                (int) $submission['assigned_agent_user_id'],
                'Intake form submitted',
                (string) $submission['form_title'] . ' is now submitted.',
                url('job', ['id' => (int) $submission['job_id']])
            );
        }
    }
}

function request_intake_submission_update(int $submissionId, int $actorUserId, string $note = ''): void
{
    $submission = get_intake_submission($submissionId);
    if (!$submission) {
        throw new RuntimeException('Intake submission not found.');
    }

    $actor = find_user_by_id($actorUserId);
    if (!$actor) {
        throw new RuntimeException('Invalid actor account.');
    }

    if (!in_array((string) ($actor['role'] ?? ''), ['admin', 'finance', 'agent'], true)) {
        throw new RuntimeException('Only admin/finance/agent can request intake updates.');
    }

    if ((string) ($actor['role'] ?? '') === 'agent' && (int) ($submission['agent_id'] ?? 0) !== $actorUserId && (int) ($submission['assigned_agent_user_id'] ?? 0) !== $actorUserId) {
        throw new RuntimeException('You can only request updates for your assigned job submissions.');
    }

    $note = trim($note);
    if ($note === '') {
        $note = 'Please review the intake details and update the required fields.';
    }

    db_execute(
        'UPDATE intake_submissions
         SET status = :status, update_request_note = :update_request_note, updated_at = :updated_at
         WHERE id = :id',
        [
            'status' => 'update_requested',
            'update_request_note' => $note,
            'updated_at' => now(),
            'id' => $submissionId,
        ]
    );

    add_intake_submission_event($submissionId, $actorUserId, 'update_requested', $note);
    add_system_job_comment(
        (int) $submission['job_id'],
        $actorUserId,
        'Update requested on intake form: ' . (string) $submission['form_title'] . '. ' . $note,
        true
    );

    create_notification(
        (int) $submission['client_user_id'],
        'Update requested on intake form',
        'Please review and resubmit: ' . (string) $submission['form_title'] . '.',
        url('intake_submission', ['id' => $submissionId])
    );
}

function intake_client_can_edit(array $submission): bool
{
    return in_array((string) ($submission['status'] ?? 'draft'), ['draft', 'update_requested'], true);
}

function list_job_files(int $jobId): array
{
    return db_all(
        'SELECT f.*, u.full_name
         FROM job_files f
         JOIN users u ON u.id = f.user_id
         WHERE f.job_id = :job_id
         ORDER BY f.created_at DESC',
        ['job_id' => $jobId]
    );
}

function can_user_manage_job_files(array $user, array $job): bool
{
    $role = (string) ($user['role'] ?? '');
    $userId = (int) ($user['id'] ?? 0);

    if ($role === 'admin') {
        return true;
    }

    return $role === 'agent' && (int) ($job['agent_id'] ?? 0) === $userId;
}

function delete_job_file(int $jobId, int $fileId, int $actorUserId): void
{
    if ($jobId <= 0 || $fileId <= 0 || $actorUserId <= 0) {
        throw new RuntimeException('Invalid file delete request.');
    }

    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $actor = find_user_by_id($actorUserId);
    if (!$actor) {
        throw new RuntimeException('Invalid account.');
    }

    if (!can_user_manage_job_files($actor, $job)) {
        throw new RuntimeException('You are not allowed to delete files from this job.');
    }

    $file = db_one(
        'SELECT f.*, u.full_name AS uploaded_by_name
         FROM job_files f
         LEFT JOIN users u ON u.id = f.user_id
         WHERE f.id = :id AND f.job_id = :job_id
         LIMIT 1',
        [
            'id' => $fileId,
            'job_id' => $jobId,
        ]
    );

    if (!$file) {
        throw new RuntimeException('File not found.');
    }

    db_execute('DELETE FROM job_files WHERE id = :id AND job_id = :job_id', [
        'id' => $fileId,
        'job_id' => $jobId,
    ]);

    $rawPath = (string) ($file['file_path'] ?? '');
    if ($rawPath !== '') {
        $realPath = realpath($rawPath);
        $uploadRoot = realpath(upload_directory());
        $allowed = false;

        if ($realPath !== false && $uploadRoot !== false) {
            $prefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $allowed = str_starts_with($realPath, $prefix) || $realPath === rtrim($uploadRoot, DIRECTORY_SEPARATOR);
        } elseif (is_file($rawPath)) {
            $allowed = true;
            $realPath = $rawPath;
        }

        if ($allowed && $realPath && is_file($realPath)) {
            @unlink($realPath);
        }
    }

    $actorName = trim((string) ($actor['full_name'] ?? 'Admin'));
    $uploadedBy = trim((string) ($file['uploaded_by_name'] ?? 'Unknown user'));
    $fileName = trim((string) ($file['file_name'] ?? 'file'));
    add_system_job_comment(
        $jobId,
        $actorUserId,
        $actorName . ' removed file "' . $fileName . '" (uploaded by ' . $uploadedBy . ').',
        true
    );
}

function list_status_logs(int $jobId): array
{
    return db_all(
        'SELECT l.*, u.full_name
         FROM job_status_logs l
         JOIN users u ON u.id = l.changed_by
         WHERE l.job_id = :job_id
         ORDER BY l.created_at DESC',
        ['job_id' => $jobId]
    );
}

function normalize_job_uploaded_files_payload(?array $rawFiles): array
{
    if (!$rawFiles || !isset($rawFiles['name'])) {
        return [];
    }

    $names = $rawFiles['name'];
    $types = $rawFiles['type'] ?? [];
    $tmpNames = $rawFiles['tmp_name'] ?? [];
    $errors = $rawFiles['error'] ?? [];
    $sizes = $rawFiles['size'] ?? [];

    $normalized = [];
    if (is_array($names)) {
        foreach ($names as $index => $nameValue) {
            $normalized[] = [
                'name' => (string) $nameValue,
                'type' => is_array($types) ? (string) ($types[$index] ?? '') : '',
                'tmp_name' => is_array($tmpNames) ? (string) ($tmpNames[$index] ?? '') : '',
                'error' => is_array($errors) ? (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
                'size' => is_array($sizes) ? (int) ($sizes[$index] ?? 0) : 0,
            ];
        }
    } else {
        $normalized[] = [
            'name' => (string) $names,
            'type' => is_array($types) ? '' : (string) $types,
            'tmp_name' => is_array($tmpNames) ? '' : (string) $tmpNames,
            'error' => is_array($errors) ? UPLOAD_ERR_NO_FILE : (int) $errors,
            'size' => is_array($sizes) ? 0 : (int) $sizes,
        ];
    }

    return array_values(array_filter(
        $normalized,
        static function (array $file): bool {
            $name = trim((string) ($file['name'] ?? ''));
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return $name !== '' && $error !== UPLOAD_ERR_NO_FILE;
        }
    ));
}

function store_uploaded_files(int $jobId, int $userId, array $files): int
{
    $job = get_job($jobId);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    if (!can_user_upload_files_to_job($user, $job)) {
        throw new RuntimeException('File uploads are not allowed for this job at its current stage.');
    }

    $uploaded = 0;
    foreach ($files as $file) {
        if (!is_array($file)) {
            continue;
        }
        store_uploaded_file($jobId, $userId, $file, false);
        $uploaded++;
    }

    if ($uploaded <= 0) {
        throw new RuntimeException('Select at least one file to upload.');
    }

    if ($job) {
        create_notification(
            (int) $job['client_id'],
            'New files uploaded',
            'New file(s) have been added to your order.',
            url('job', ['id' => $jobId])
        );
        if ($job['agent_id']) {
            create_notification(
                (int) $job['agent_id'],
                'New files uploaded',
                'New file(s) were added to job ' . $job['job_code'] . '.',
                url('job', ['id' => $jobId])
            );
        }
    }

    return $uploaded;
}

function store_uploaded_file(int $jobId, int $userId, array $file, bool $notify = true): void
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    if ((int) $file['size'] > max_upload_size()) {
        throw new RuntimeException('File too large. Max 20MB.');
    }

    $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'pl', 'cgi', 'js'];
    $original = $file['name'] ?? 'upload.bin';
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (in_array($extension, $blocked, true)) {
        throw new RuntimeException('This file type is blocked for security reasons.');
    }

    ensure_upload_directory();

    $jobDir = upload_directory() . '/job_' . $jobId;
    if (!is_dir($jobDir)) {
        mkdir($jobDir, 0775, true);
    }

    $storedName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
    $destination = $jobDir . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    $mime = mime_content_type($destination) ?: 'application/octet-stream';

    db_execute(
        'INSERT INTO job_files (job_id, user_id, file_name, stored_name, file_path, file_size, mime_type, created_at)
         VALUES (:job_id, :user_id, :file_name, :stored_name, :file_path, :file_size, :mime_type, :created_at)',
        [
            'job_id' => $jobId,
            'user_id' => $userId,
            'file_name' => $original,
            'stored_name' => $storedName,
            'file_path' => $destination,
            'file_size' => (int) $file['size'],
            'mime_type' => $mime,
            'created_at' => now(),
        ]
    );

    if ($notify) {
        $job = get_job($jobId);
        if ($job) {
            create_notification((int) $job['client_id'], 'New file uploaded', 'A new file has been added to your order.', url('job', ['id' => $jobId]));
            if ($job['agent_id']) {
                create_notification((int) $job['agent_id'], 'New file uploaded', 'A new file was added to job ' . $job['job_code'] . '.', url('job', ['id' => $jobId]));
            }
        }
    }
}

function find_file(int $fileId): ?array
{
    return db_one(
        'SELECT f.*, j.client_id, j.agent_id, j.payment_status FROM job_files f
         JOIN jobs j ON j.id = f.job_id
         WHERE f.id = :id LIMIT 1',
        ['id' => $fileId]
    );
}

function list_invoices_for_user(array $user, array $filters = []): array
{
    $role = (string) ($user['role'] ?? '');
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = (int) ($filters['per_page'] ?? 10);
    $allowedPerPage = [10, 25, 50, 100];
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    if ($role === 'agent') {
        return [
            'items' => [],
            'pagination' => [
                'page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ],
        ];
    }

    if ($role === 'client') {
        normalize_unconfirmed_partial_jobs((int) ($user['id'] ?? 0));
    } else {
        normalize_unconfirmed_partial_jobs();
    }

    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $paymentStatus = strtolower(trim((string) ($filters['payment_status'] ?? '')));
    $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));

    $allowedStatus = ['draft', 'issued', 'paid', 'void'];
    $allowedPayment = ['unpaid', 'partial', 'paid', 'refunded'];
    $allowedSort = ['newest', 'oldest', 'amount_high', 'amount_low', 'status', 'payment'];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }
    if (!in_array($paymentStatus, $allowedPayment, true)) {
        $paymentStatus = '';
    }
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'newest';
    }

    $confirmedPaymentExistsSql = confirmed_payment_exists_sql_for_job('j', 'i2', 'p2');
    $effectivePaymentStatusSql = "CASE WHEN j.payment_status = 'partial' AND NOT {$confirmedPaymentExistsSql} THEN 'unpaid' ELSE j.payment_status END";

    $sortSqlMap = [
        'newest' => 'i.created_at DESC',
        'oldest' => 'i.created_at ASC',
        'amount_high' => 'i.amount DESC, i.created_at DESC',
        'amount_low' => 'i.amount ASC, i.created_at DESC',
        'status' => 'i.status ASC, i.created_at DESC',
        'payment' => $effectivePaymentStatusSql . ' ASC, i.created_at DESC',
    ];

    $fromSql = ' FROM invoices i
                 JOIN jobs j ON j.id = i.job_id
                 JOIN services s ON s.id = j.service_id
                 JOIN users c ON c.id = i.client_id
                 WHERE 1=1';
    $params = [];

    if ($role === 'client') {
        $fromSql .= ' AND i.client_id = :client_id';
        $params['client_id'] = (int) $user['id'];
    }

    if ($status !== '') {
        $fromSql .= ' AND i.status = :status';
        $params['status'] = $status;
    }

    if ($paymentStatus !== '') {
        $fromSql .= ' AND ' . $effectivePaymentStatusSql . ' = :payment_status';
        $params['payment_status'] = $paymentStatus;
    }

    $totalRow = db_one('SELECT COUNT(*) AS total' . $fromSql, $params);
    $total = (int) ($totalRow['total'] ?? 0);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $items = db_all(
        'SELECT i.*, j.job_code, j.title AS job_title, j.description AS job_description, j.payment_status, s.name AS service_name, c.full_name AS client_name,
                ' . $effectivePaymentStatusSql . ' AS payment_status_effective,
                CASE WHEN ' . $confirmedPaymentExistsSql . ' THEN 1 ELSE 0 END AS has_confirmed_payment'
        . $fromSql
        . ' ORDER BY ' . $sortSqlMap[$sort]
        . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
        $params
    );

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? ($offset + 1) : 0,
            'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
        ],
    ];
}

function get_invoice(int $id): ?array
{
    return db_one(
        'SELECT i.*, j.job_code, j.description AS job_description, j.status AS job_status,
                j.payment_status, j.payment_reference, j.agent_id,
                CASE
                    WHEN j.payment_status = \'partial\' AND NOT ' . confirmed_payment_exists_sql_for_job('j', 'i5', 'p5') . '
                    THEN \'unpaid\'
                    ELSE j.payment_status
                END AS payment_status_effective,
                s.name AS service_name, c.full_name AS client_name, c.email AS client_email, c.phone AS client_phone
         FROM invoices i
         JOIN jobs j ON j.id = i.job_id
         JOIN services s ON s.id = j.service_id
         JOIN users c ON c.id = i.client_id
         WHERE i.id = :id LIMIT 1',
        ['id' => $id]
    );
}

function update_unpaid_invoice_for_client(int $invoiceId, int $clientId, string $jobTitle, string $jobDescription): void
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    if ((int) $invoice['client_id'] !== $clientId) {
        throw new RuntimeException('You cannot edit this invoice.');
    }

    $invoicePaymentStatus = (string) ($invoice['payment_status_effective'] ?? $invoice['payment_status'] ?? 'unpaid');
    if ((string) ($invoice['status'] ?? '') === 'paid' || $invoicePaymentStatus === 'paid') {
        throw new RuntimeException('Paid invoices cannot be edited.');
    }

    $jobTitle = trim($jobTitle);
    $jobDescription = trim($jobDescription);
    if ($jobTitle === '') {
        throw new RuntimeException('Order title is required.');
    }

    db_execute(
        'UPDATE jobs SET title = :title, description = :description, updated_at = :updated_at WHERE id = :id AND client_id = :client_id',
        [
            'title' => $jobTitle,
            'description' => $jobDescription !== '' ? $jobDescription : null,
            'updated_at' => now(),
            'id' => (int) $invoice['job_id'],
            'client_id' => $clientId,
        ]
    );
}

function delete_unpaid_invoice_for_client(int $invoiceId, int $clientId): void
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    if ((int) $invoice['client_id'] !== $clientId) {
        throw new RuntimeException('You cannot delete this invoice.');
    }

    $invoicePaymentStatus = (string) ($invoice['payment_status_effective'] ?? $invoice['payment_status'] ?? 'unpaid');
    if ((string) ($invoice['status'] ?? '') === 'paid' || $invoicePaymentStatus === 'paid') {
        throw new RuntimeException('Paid invoices cannot be deleted.');
    }

    $confirmedCount = (int) (db_one(
        "SELECT COUNT(*) AS c FROM job_payments WHERE invoice_id = :invoice_id AND status = 'confirmed'",
        ['invoice_id' => $invoiceId]
    )['c'] ?? 0);
    if ($confirmedCount > 0) {
        throw new RuntimeException('This invoice has confirmed payments and cannot be deleted.');
    }

    db_execute('DELETE FROM jobs WHERE id = :id AND client_id = :client_id', [
        'id' => (int) $invoice['job_id'],
        'client_id' => $clientId,
    ]);
}

function delete_unpaid_job_for_client(int $jobId, int $clientId): void
{
    $job = db_one('SELECT * FROM jobs WHERE id = :id LIMIT 1', ['id' => $jobId]);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    if ((int) $job['client_id'] !== $clientId) {
        throw new RuntimeException('You cannot delete this job.');
    }

    $paymentGroup = trim((string) ($job['payment_group'] ?? ''));
    if ($paymentGroup !== '') {
        $hasConfirmedPayment = (bool) db_one(
            'SELECT 1 AS ok
             FROM invoices i
             JOIN job_payments p ON p.invoice_id = i.id AND p.status = :status
             WHERE i.payment_group = :payment_group
             LIMIT 1',
            [
                'status' => 'confirmed',
                'payment_group' => $paymentGroup,
            ]
        );
    } else {
        $hasConfirmedPayment = (bool) db_one(
            'SELECT 1 AS ok
             FROM invoices i
             JOIN job_payments p ON p.invoice_id = i.id AND p.status = :status
             WHERE i.job_id = :job_id
             LIMIT 1',
            [
                'status' => 'confirmed',
                'job_id' => $jobId,
            ]
        );
    }

    $effectivePaymentStatus = (string) ($job['payment_status'] ?? 'unpaid');
    if ($effectivePaymentStatus === 'partial' && !$hasConfirmedPayment) {
        $effectivePaymentStatus = 'unpaid';
    }

    if ($effectivePaymentStatus !== 'unpaid' || $hasConfirmedPayment) {
        throw new RuntimeException('Only unpaid jobs without confirmed payments can be deleted.');
    }

    db()->beginTransaction();
    try {
        $invoice = null;
        if ($paymentGroup !== '') {
            $invoice = db_one(
                'SELECT * FROM invoices WHERE payment_group = :payment_group ORDER BY id ASC LIMIT 1',
                ['payment_group' => $paymentGroup]
            );
        } else {
            $invoice = db_one(
                'SELECT * FROM invoices WHERE job_id = :job_id ORDER BY id ASC LIMIT 1',
                ['job_id' => $jobId]
            );
        }

        if ($invoice && (int) $invoice['client_id'] !== $clientId) {
            throw new RuntimeException('You cannot delete this job.');
        }

        if ($paymentGroup !== '') {
            $groupJobs = db_all(
                'SELECT id FROM jobs WHERE payment_group = :payment_group ORDER BY id ASC',
                ['payment_group' => $paymentGroup]
            );

            if ($invoice && count($groupJobs) > 1 && (int) $invoice['job_id'] === $jobId) {
                $replacement = 0;
                foreach ($groupJobs as $groupJob) {
                    $candidateId = (int) ($groupJob['id'] ?? 0);
                    if ($candidateId > 0 && $candidateId !== $jobId) {
                        $replacement = $candidateId;
                        break;
                    }
                }

                if ($replacement <= 0) {
                    throw new RuntimeException('Could not find replacement order anchor.');
                }

                db_execute(
                    'UPDATE invoices SET job_id = :job_id WHERE id = :id',
                    [
                        'job_id' => $replacement,
                        'id' => (int) $invoice['id'],
                    ]
                );
            }
        }

        db_execute(
            'DELETE FROM jobs WHERE id = :id AND client_id = :client_id',
            [
                'id' => $jobId,
                'client_id' => $clientId,
            ]
        );

        if ($paymentGroup !== '' && $invoice) {
            $remaining = db_all(
                'SELECT id FROM jobs WHERE payment_group = :payment_group ORDER BY id ASC',
                ['payment_group' => $paymentGroup]
            );

            if (!$remaining) {
                db_execute('DELETE FROM invoices WHERE id = :id', ['id' => (int) $invoice['id']]);
            } else {
                $totals = db_one(
                    'SELECT
                        COALESCE(SUM(subtotal_amount), 0) AS subtotal_amount,
                        COALESCE(SUM(tax_amount), 0) AS tax_amount,
                        COALESCE(SUM(amount), 0) AS amount
                     FROM jobs
                     WHERE payment_group = :payment_group',
                    ['payment_group' => $paymentGroup]
                ) ?? ['subtotal_amount' => 0, 'tax_amount' => 0, 'amount' => 0];

                $subtotal = (float) ($totals['subtotal_amount'] ?? 0);
                $tax = (float) ($totals['tax_amount'] ?? 0);
                $amount = (float) ($totals['amount'] ?? 0);
                $taxPercent = $subtotal > 0 ? round(($tax / $subtotal) * 100, 2) : 0.0;
                $anchorJobId = (int) ($remaining[0]['id'] ?? 0);

                db_execute(
                    'UPDATE invoices
                     SET job_id = :job_id,
                         subtotal_amount = :subtotal_amount,
                         tax_percent = :tax_percent,
                         tax_amount = :tax_amount,
                         amount = :amount,
                         status = :status
                     WHERE id = :id',
                    [
                        'job_id' => $anchorJobId,
                        'subtotal_amount' => $subtotal,
                        'tax_percent' => $taxPercent,
                        'tax_amount' => $tax,
                        'amount' => $amount,
                        'status' => 'issued',
                        'id' => (int) $invoice['id'],
                    ]
                );
            }
        }

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function delete_job_for_admin(int $jobId): void
{
    $job = db_one('SELECT * FROM jobs WHERE id = :id LIMIT 1', ['id' => $jobId]);
    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $paymentGroup = trim((string) ($job['payment_group'] ?? ''));

    db()->beginTransaction();
    try {
        $invoice = null;
        if ($paymentGroup !== '') {
            $invoice = db_one(
                'SELECT * FROM invoices WHERE payment_group = :payment_group ORDER BY id ASC LIMIT 1',
                ['payment_group' => $paymentGroup]
            );
        } else {
            $invoice = db_one(
                'SELECT * FROM invoices WHERE job_id = :job_id ORDER BY id ASC LIMIT 1',
                ['job_id' => $jobId]
            );
        }

        if ($paymentGroup !== '') {
            $groupJobs = db_all(
                'SELECT id FROM jobs WHERE payment_group = :payment_group ORDER BY id ASC',
                ['payment_group' => $paymentGroup]
            );

            if ($invoice && count($groupJobs) > 1 && (int) $invoice['job_id'] === $jobId) {
                $replacement = 0;
                foreach ($groupJobs as $groupJob) {
                    $candidateId = (int) ($groupJob['id'] ?? 0);
                    if ($candidateId > 0 && $candidateId !== $jobId) {
                        $replacement = $candidateId;
                        break;
                    }
                }

                if ($replacement <= 0) {
                    throw new RuntimeException('Could not find replacement order anchor.');
                }

                db_execute(
                    'UPDATE invoices SET job_id = :job_id WHERE id = :id',
                    [
                        'job_id' => $replacement,
                        'id' => (int) $invoice['id'],
                    ]
                );
            }
        }

        db_execute('DELETE FROM jobs WHERE id = :id', ['id' => $jobId]);

        if ($paymentGroup !== '' && $invoice) {
            $remaining = db_all(
                'SELECT id FROM jobs WHERE payment_group = :payment_group ORDER BY id ASC',
                ['payment_group' => $paymentGroup]
            );

            if (!$remaining) {
                db_execute('DELETE FROM invoices WHERE id = :id', ['id' => (int) $invoice['id']]);
            } else {
                $totals = db_one(
                    'SELECT
                        COALESCE(SUM(subtotal_amount), 0) AS subtotal_amount,
                        COALESCE(SUM(tax_amount), 0) AS tax_amount,
                        COALESCE(SUM(amount), 0) AS amount
                     FROM jobs
                     WHERE payment_group = :payment_group',
                    ['payment_group' => $paymentGroup]
                ) ?? ['subtotal_amount' => 0, 'tax_amount' => 0, 'amount' => 0];

                $subtotal = (float) ($totals['subtotal_amount'] ?? 0);
                $tax = (float) ($totals['tax_amount'] ?? 0);
                $amount = (float) ($totals['amount'] ?? 0);
                $taxPercent = $subtotal > 0 ? round(($tax / $subtotal) * 100, 2) : 0.0;
                $anchorJobId = (int) ($remaining[0]['id'] ?? 0);

                db_execute(
                    'UPDATE invoices
                     SET job_id = :job_id,
                         subtotal_amount = :subtotal_amount,
                         tax_percent = :tax_percent,
                         tax_amount = :tax_amount,
                         amount = :amount,
                         status = :status
                     WHERE id = :id',
                    [
                        'job_id' => $anchorJobId,
                        'subtotal_amount' => $subtotal,
                        'tax_percent' => $taxPercent,
                        'tax_amount' => $tax,
                        'amount' => $amount,
                        'status' => 'issued',
                        'id' => (int) $invoice['id'],
                    ]
                );
            }
        }

        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function bulk_manage_jobs_as_admin(array $jobIds, string $bulkAction, int $adminId): array
{
    $ids = [];
    foreach ($jobIds as $rawId) {
        $id = (int) $rawId;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    $ids = array_values($ids);

    if ($ids === []) {
        throw new RuntimeException('Select at least one job.');
    }

    $bulkAction = strtolower(trim($bulkAction));
    $isDelete = $bulkAction === 'delete';
    $statusTarget = null;

    if (!$isDelete) {
        if (!str_starts_with($bulkAction, 'status:')) {
            throw new RuntimeException('Invalid bulk action selected.');
        }
        $statusTarget = trim(substr($bulkAction, 7));
        if (!in_array($statusTarget, job_status_options(), true)) {
            throw new RuntimeException('Invalid target status selected.');
        }
    }

    $updated = 0;
    $failed = 0;
    $errors = [];

    foreach ($ids as $jobId) {
        try {
            if ($isDelete) {
                delete_job_for_admin($jobId);
            } else {
                update_job_status(
                    $jobId,
                    (string) $statusTarget,
                    $adminId,
                    'admin',
                    'Bulk action: status changed to ' . str_replace('_', ' ', (string) $statusTarget) . '.'
                );
            }
            $updated++;
        } catch (Throwable $e) {
            $failed++;
            if (count($errors) < 5) {
                $errors[] = 'Job #' . $jobId . ': ' . $e->getMessage();
            }
        }
    }

    return [
        'updated' => $updated,
        'failed' => $failed,
        'errors' => $errors,
    ];
}

function list_invoice_jobs(int $invoiceId): array
{
    $invoice = db_one('SELECT id, job_id, payment_group FROM invoices WHERE id = :id LIMIT 1', ['id' => $invoiceId]);
    if (!$invoice) {
        return [];
    }

    $paymentGroup = trim((string) ($invoice['payment_group'] ?? ''));
    if ($paymentGroup !== '') {
        return db_all(
            'SELECT j.*, s.name AS service_name
             FROM jobs j
             JOIN services s ON s.id = j.service_id
             WHERE j.payment_group = :payment_group
             ORDER BY j.id ASC',
            ['payment_group' => $paymentGroup]
        );
    }

    return db_all(
        'SELECT j.*, s.name AS service_name
         FROM jobs j
         JOIN services s ON s.id = j.service_id
         WHERE j.id = :job_id
         ORDER BY j.id ASC',
        ['job_id' => (int) $invoice['job_id']]
    );
}

function list_payouts_admin(array $filters = []): array
{
    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = (int) ($filters['per_page'] ?? 10);

    $allowedStatus = ['accrued', 'approved', 'paid'];
    $allowedSort = ['newest', 'oldest', 'amount_high', 'amount_low', 'status', 'agent'];
    $allowedPerPage = [10, 25, 50, 100];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'newest';
    }
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

    $sortSqlMap = [
        'newest' => 'p.created_at DESC',
        'oldest' => 'p.created_at ASC',
        'amount_high' => 'p.commission_amount DESC, p.created_at DESC',
        'amount_low' => 'p.commission_amount ASC, p.created_at DESC',
        'status' => 'p.status ASC, p.created_at DESC',
        'agent' => 'u.full_name ASC, p.created_at DESC',
    ];

    $fromSql = ' FROM commission_payouts p
                 JOIN users u ON u.id = p.agent_id
                 JOIN jobs j ON j.id = p.job_id
                 JOIN services s ON s.id = j.service_id
                 WHERE 1=1';
    $params = [];

    if ($status !== '') {
        $fromSql .= ' AND p.status = :status';
        $params['status'] = $status;
    }

    $totalRow = db_one('SELECT COUNT(*) AS total' . $fromSql, $params);
    $total = (int) ($totalRow['total'] ?? 0);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $items = db_all(
        'SELECT p.*, u.full_name AS agent_name, u.bank_name, u.bank_account_name, u.bank_account_number,
                u.bank_swift, u.bank_currency, j.job_code, j.currency, s.name AS service_name'
        . $fromSql
        . ' ORDER BY ' . $sortSqlMap[$sort]
        . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
        $params
    );

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? ($offset + 1) : 0,
            'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
        ],
    ];
}

function list_payouts(): array
{
    return db_all(
        'SELECT p.*, u.full_name AS agent_name, u.bank_name, u.bank_account_name, u.bank_account_number,
                u.bank_swift, u.bank_currency, j.job_code, j.currency, s.name AS service_name
         FROM commission_payouts p
         JOIN users u ON u.id = p.agent_id
         JOIN jobs j ON j.id = p.job_id
         JOIN services s ON s.id = j.service_id
         ORDER BY p.created_at DESC'
    );
}

function update_payout_status(int $payoutId, string $status, int $adminId): void
{
    $allowed = ['accrued', 'approved', 'paid'];
    if (!in_array($status, $allowed, true)) {
        throw new RuntimeException('Invalid payout status.');
    }

    $payout = db_one('SELECT * FROM commission_payouts WHERE id = :id LIMIT 1', ['id' => $payoutId]);
    if (!$payout) {
        throw new RuntimeException('Payout record not found.');
    }

    db_execute(
        "UPDATE commission_payouts SET status = :status,
         approved_by = CASE WHEN :status2 IN ('approved', 'paid') THEN :approved_by ELSE approved_by END,
         approved_at = CASE WHEN :status3 IN ('approved', 'paid') THEN :approved_at ELSE approved_at END,
         paid_at = CASE WHEN :status4 = 'paid' THEN :paid_at ELSE paid_at END
         WHERE id = :id",
        [
            'status' => $status,
            'status2' => $status,
            'status3' => $status,
            'status4' => $status,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'paid_at' => now(),
            'id' => $payoutId,
        ]
    );

    create_notification((int) $payout['agent_id'], 'Commission payout updated', 'Your commission payout status is now ' . $status . '.', url('withdrawals'));
}

function bulk_update_payout_status(array $payoutIds, string $status, int $adminId): int
{
    $allowed = ['accrued', 'approved', 'paid'];
    if (!in_array($status, $allowed, true)) {
        throw new RuntimeException('Invalid payout status.');
    }

    $ids = array_values(array_unique(array_filter(array_map(static fn($id): int => (int) $id, $payoutIds), static fn(int $id): bool => $id > 0)));
    if (!$ids) {
        throw new RuntimeException('Select at least one payout request.');
    }

    $idPlaceholders = [];
    $idParams = [];
    foreach ($ids as $idx => $id) {
        $key = 'id_' . $idx;
        $idPlaceholders[] = ':' . $key;
        $idParams[$key] = $id;
    }

    $rows = db_all(
        'SELECT id, agent_id
         FROM commission_payouts
         WHERE id IN (' . implode(',', $idPlaceholders) . ')',
        $idParams
    );
    if (!$rows) {
        throw new RuntimeException('Selected payout records were not found.');
    }

    $updateParams = $idParams + [
        'status' => $status,
        'status2' => $status,
        'status3' => $status,
        'status4' => $status,
        'approved_by' => $adminId,
        'approved_at' => now(),
        'paid_at' => now(),
    ];

    db_execute(
        "UPDATE commission_payouts
         SET status = :status,
             approved_by = CASE WHEN :status2 IN ('approved', 'paid') THEN :approved_by ELSE approved_by END,
             approved_at = CASE WHEN :status3 IN ('approved', 'paid') THEN :approved_at ELSE approved_at END,
             paid_at = CASE WHEN :status4 = 'paid' THEN :paid_at ELSE paid_at END
         WHERE id IN (" . implode(',', $idPlaceholders) . ')',
        $updateParams
    );

    $agentIds = array_values(array_unique(array_map(static fn(array $row): int => (int) ($row['agent_id'] ?? 0), $rows)));
    foreach ($agentIds as $agentId) {
        if ($agentId <= 0) {
            continue;
        }
        create_notification($agentId, 'Commission payout updated', 'Your commission payout status is now ' . $status . '.', url('withdrawals'));
    }

    return count($rows);
}

function maybe_accrue_commission(int $jobId): void
{
    $job = get_job($jobId);
    if (!$job) {
        return;
    }

    if (!$job['agent_id']) {
        return;
    }

    $isDeliverable = in_array($job['status'], ['delivered', 'closed'], true);
    $isPaid = $job['payment_status'] === 'paid';

    if (!$isDeliverable || !$isPaid) {
        return;
    }

    $existing = db_one('SELECT * FROM commission_payouts WHERE job_id = :job_id LIMIT 1', ['job_id' => $jobId]);

    if ($existing) {
        if ($existing['status'] !== 'paid') {
            db_execute(
                'UPDATE commission_payouts SET commission_amount = :commission_amount WHERE id = :id',
                ['commission_amount' => $job['commission_amount'], 'id' => $existing['id']]
            );
        }

        return;
    }

    db_execute(
        'INSERT INTO commission_payouts (agent_id, job_id, commission_amount, status, created_at)
         VALUES (:agent_id, :job_id, :commission_amount, :status, :created_at)',
        [
            'agent_id' => $job['agent_id'],
            'job_id' => $jobId,
            'commission_amount' => $job['commission_amount'],
            'status' => 'accrued',
            'created_at' => now(),
        ]
    );

    create_notification((int) $job['agent_id'], 'Commission accrued', 'A new commission has been generated from a delivered paid job.', url('withdrawals'));
}

function counts_for_dashboard(array $user): array
{
    $role = $user['role'];

    if ($role === 'client') {
        return [
            'jobs_total' => (int) (db_one('SELECT COUNT(*) AS c FROM jobs WHERE client_id = :id', ['id' => $user['id']])['c'] ?? 0),
            'jobs_open' => (int) (db_one("SELECT COUNT(*) AS c FROM jobs WHERE client_id = :id AND status NOT IN ('closed', 'cancelled')", ['id' => $user['id']])['c'] ?? 0),
            'invoices_due' => (int) (db_one("SELECT COUNT(*) AS c FROM invoices WHERE client_id = :id AND status = 'issued'", ['id' => $user['id']])['c'] ?? 0),
        ];
    }

    if ($role === 'agent') {
        $summary = agent_withdrawal_summary((int) $user['id']);

        return [
            'job_pool' => (int) (db_one("SELECT COUNT(*) AS c FROM jobs WHERE payment_status = 'paid' AND agent_id IS NULL AND managed_by_admin_id IS NULL AND status NOT IN ('closed', 'cancelled')")['c'] ?? 0),
            'my_jobs' => (int) (db_one("SELECT COUNT(*) AS c FROM jobs WHERE payment_status = 'paid' AND agent_id = :id AND status NOT IN ('closed', 'cancelled')", ['id' => $user['id']])['c'] ?? 0),
            'earned_total' => (float) $summary['total_earned'],
            'available_balance' => (float) $summary['available_balance'],
        ];
    }

    return [
        'clients' => (int) (db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'client' AND deleted_at IS NULL")['c'] ?? 0),
        'agents' => (int) (db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'agent' AND deleted_at IS NULL")['c'] ?? 0),
        'jobs_open' => (int) (db_one("SELECT COUNT(*) AS c FROM jobs WHERE status NOT IN ('closed', 'cancelled')")['c'] ?? 0),
        'revenue_paid' => (float) (db_one("SELECT COALESCE(SUM(amount), 0) AS total FROM jobs WHERE payment_status = 'paid'")['total'] ?? 0),
    ];
}

function portal_next_step_hint(array $user, string $currentPage): ?array
{
    $role = (string) ($user['role'] ?? '');

    if ($role === 'client') {
        $unpaid = (int) (db_one(
            "SELECT COUNT(*) AS c FROM invoices WHERE client_id = :client_id AND status = 'issued'",
            ['client_id' => (int) $user['id']]
        )['c'] ?? 0);
        if ($unpaid > 0) {
            return [
                'title' => 'Next Step',
                'message' => 'You have unpaid invoice(s). Complete payment to activate service processing.',
                'url' => url('invoices'),
                'button' => 'Open invoices',
            ];
        }

        $jobs = (int) (db_one(
            'SELECT COUNT(*) AS c FROM jobs WHERE client_id = :client_id',
            ['client_id' => (int) $user['id']]
        )['c'] ?? 0);
        if ($jobs === 0 || $currentPage !== 'jobs') {
            return [
                'title' => 'How To Start',
                'message' => 'Order one or multiple services, then pay from the generated invoice to begin.',
                'url' => url('jobs', ['action' => 'new']),
                'button' => 'Order service',
            ];
        }

        return [
            'title' => 'Keep Progress Moving',
            'message' => 'Open your jobs to check updates, reply to comments, and download completed files.',
            'url' => url('jobs'),
            'button' => 'Open jobs',
        ];
    }

    if ($role === 'agent') {
        $pool = (int) (db_one(
            "SELECT COUNT(*) AS c FROM jobs WHERE payment_status = 'paid' AND agent_id IS NULL AND managed_by_admin_id IS NULL AND status NOT IN ('closed', 'cancelled')"
        )['c'] ?? 0);
        if ($pool > 0) {
            return [
                'title' => 'Job Pool',
                'message' => 'There are paid unassigned jobs waiting in the pool. Claim one and start delivery.',
                'url' => url('jobs'),
                'button' => 'Open job pool',
            ];
        }

        return [
            'title' => 'Team Collaboration',
            'message' => 'Use Community to ask questions and share delivery tips with other agents.',
            'url' => url('community'),
            'button' => 'Open community',
        ];
    }

    if (in_array($role, ['admin', 'finance'], true)) {
        return [
            'title' => 'Operations Guide',
            'message' => 'Monitor payments, assign agents, and keep announcement/support channels updated.',
            'url' => url('dashboard'),
            'button' => 'Open dashboard',
        ];
    }

    return null;
}

function payment_method_codes_for_currency(string $currency): array
{
    $currency = strtoupper(trim($currency));

    if ($currency === 'NGN') {
        return ['paystack', 'flutterwave', 'bank_transfer', 'crypto_wallet'];
    }

    if ($currency === 'USD') {
        return ['stripe', 'crypto_wallet'];
    }

    return ['crypto_wallet'];
}

function list_payment_methods(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM payment_methods';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY id ASC';

    return db_all($sql);
}

function list_available_payment_methods_for_currency(string $currency): array
{
    $codes = payment_method_codes_for_currency($currency);
    if (!$codes) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($codes), '?'));

    return db_query(
        "SELECT * FROM payment_methods WHERE is_active = 1 AND method_code IN ($placeholders) ORDER BY id ASC",
        $codes
    )->fetchAll();
}

function get_payment_method(int $id): ?array
{
    return db_one('SELECT * FROM payment_methods WHERE id = :id LIMIT 1', ['id' => $id]);
}

function get_payment_method_by_code(string $methodCode): ?array
{
    return db_one('SELECT * FROM payment_methods WHERE method_code = :method_code LIMIT 1', ['method_code' => $methodCode]);
}

function save_payment_method(array $input): void
{
    $methodId = (int) ($input['method_id'] ?? 0);
    if ($methodId <= 0) {
        throw new RuntimeException('Invalid payment method.');
    }

    $method = get_payment_method($methodId);
    if (!$method) {
        throw new RuntimeException('Payment method not found.');
    }

    $methodCode = (string) ($method['method_code'] ?? '');
    $requiresProof = !empty($input['requires_proof']) ? 1 : 0;
    if (is_gateway_payment_method($methodCode)) {
        $requiresProof = 0;
    }
    if (in_array($methodCode, ['crypto_wallet', 'bank_transfer'], true)) {
        $requiresProof = 1;
    }

    $displayName = trim((string) ($input['display_name'] ?? ''));
    if ($displayName === '') {
        $displayName = (string) $method['display_name'];
    }

    db_execute(
        'UPDATE payment_methods
         SET display_name = :display_name, details = :details,
             public_key = :public_key, secret_key = :secret_key, private_key = :private_key,
             callback_url = :callback_url, webhook_url = :webhook_url,
             requires_proof = :requires_proof, is_active = :is_active
         WHERE id = :id',
        [
            'display_name' => $displayName,
            'details' => trim($input['details'] ?? ''),
            'public_key' => trim($input['public_key'] ?? ''),
            'secret_key' => trim($input['secret_key'] ?? ''),
            'private_key' => trim($input['private_key'] ?? ''),
            'callback_url' => trim($input['callback_url'] ?? ''),
            'webhook_url' => trim($input['webhook_url'] ?? ''),
            'requires_proof' => $requiresProof,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'id' => $methodId,
        ]
    );
}

function is_gateway_payment_method(string $methodCode): bool
{
    return in_array($methodCode, ['paystack', 'flutterwave', 'stripe'], true);
}

function payment_reference(string $prefix = 'PAY'): string
{
    return strtoupper($prefix) . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function gateway_http_request(
    string $url,
    string $method = 'GET',
    array $headers = [],
    ?string $body = null,
    int $timeout = 25
): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is required for payment gateway API calls.');
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Could not initialize gateway request.');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $resp = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new RuntimeException('Gateway request failed: ' . $err);
    }

    return [$httpCode, $resp];
}

function gateway_json_post(string $url, array $payload, string $secretKey): array
{
    [$code, $resp] = gateway_http_request(
        $url,
        'POST',
        [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        json_encode($payload, JSON_UNESCAPED_SLASHES)
    );

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid gateway response.');
    }

    if ($code >= 400) {
        $message = $data['message'] ?? $data['error']['message'] ?? ('Gateway HTTP error: ' . $code);
        throw new RuntimeException((string) $message);
    }

    return $data;
}

function gateway_json_get(string $url, string $secretKey): array
{
    [$code, $resp] = gateway_http_request(
        $url,
        'GET',
        [
            'Authorization: Bearer ' . $secretKey,
            'Accept: application/json',
        ]
    );

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid gateway response.');
    }

    if ($code >= 400) {
        $message = $data['message'] ?? ('Gateway HTTP error: ' . $code);
        throw new RuntimeException((string) $message);
    }

    return $data;
}

function gateway_form_post(string $url, array $payload, string $secretKey): array
{
    [$code, $resp] = gateway_http_request(
        $url,
        'POST',
        [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        http_build_query($payload)
    );

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid Stripe response.');
    }

    if ($code >= 400) {
        $message = $data['error']['message'] ?? ('Stripe HTTP error: ' . $code);
        throw new RuntimeException((string) $message);
    }

    return $data;
}

function gateway_form_get(string $url, string $secretKey): array
{
    [$code, $resp] = gateway_http_request(
        $url,
        'GET',
        [
            'Authorization: Bearer ' . $secretKey,
            'Accept: application/json',
        ]
    );

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid Stripe response.');
    }

    if ($code >= 400) {
        $message = $data['error']['message'] ?? ('Stripe HTTP error: ' . $code);
        throw new RuntimeException((string) $message);
    }

    return $data;
}

function mark_invoice_jobs_payment_state(array $invoice, string $paymentStatus, string $reference = ''): void
{
    $paymentStatus = strtolower(trim($paymentStatus));
    if (!in_array($paymentStatus, ['unpaid', 'partial', 'paid', 'refunded'], true)) {
        $paymentStatus = 'unpaid';
    }

    $paymentGroup = trim((string) ($invoice['payment_group'] ?? ''));
    if ($paymentGroup !== '') {
        $jobs = db_all('SELECT id FROM jobs WHERE payment_group = :payment_group', ['payment_group' => $paymentGroup]);
        db_execute(
            'UPDATE jobs
             SET payment_status = :payment_status,
                 payment_reference = CASE WHEN :payment_reference_a <> "" THEN :payment_reference_b ELSE payment_reference END,
                 updated_at = :updated_at
             WHERE payment_group = :payment_group',
            [
                'payment_status' => $paymentStatus,
                'payment_reference_a' => trim($reference),
                'payment_reference_b' => trim($reference),
                'updated_at' => now(),
                'payment_group' => $paymentGroup,
            ]
        );

        if ($paymentStatus === 'paid') {
            foreach ($jobs as $jobRow) {
                maybe_prepare_job_intake_requirements((int) $jobRow['id'], (int) ($invoice['client_id'] ?? 0), 'payment_confirmed');
            }
        }
        return;
    }

    $jobId = (int) ($invoice['job_id'] ?? 0);
    db_execute(
        'UPDATE jobs
         SET payment_status = :payment_status,
             payment_reference = CASE WHEN :payment_reference_a <> "" THEN :payment_reference_b ELSE payment_reference END,
             updated_at = :updated_at
         WHERE id = :id',
        [
            'payment_status' => $paymentStatus,
            'payment_reference_a' => trim($reference),
            'payment_reference_b' => trim($reference),
            'updated_at' => now(),
            'id' => $jobId,
        ]
    );

    if ($paymentStatus === 'paid' && $jobId > 0) {
        maybe_prepare_job_intake_requirements($jobId, (int) ($invoice['client_id'] ?? 0), 'payment_confirmed');
    }
}

function create_gateway_payment_record(array $invoice, int $clientId, int $methodId, string $provider, string $reference, ?string $sessionId = null): int
{
    $payMethod = normalize_pay_method((string) ($invoice['pay_method'] ?? 'card'));
    if ($payMethod === 'crypto') {
        $payMethod = 'card';
    }

    db_execute(
        'INSERT INTO job_payments (
            job_id, invoice_id, client_id, payment_method_id, reference, amount, pay_currency, pay_method, fx_rate_used,
            crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
            gateway_provider, gateway_transaction_id, gateway_session_id, gateway_payload,
            proof_path, note, rejection_reason, status, reviewed_by, created_at, reviewed_at, confirmed_at
         ) VALUES (
            :job_id, :invoice_id, :client_id, :payment_method_id, :reference, :amount, :pay_currency, :pay_method, :fx_rate_used,
            :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
            :gateway_provider, :gateway_transaction_id, :gateway_session_id, :gateway_payload,
            :proof_path, :note, :rejection_reason, :status, :reviewed_by, :created_at, :reviewed_at, :confirmed_at
         )',
        [
            'job_id' => (int) $invoice['job_id'],
            'invoice_id' => (int) $invoice['id'],
            'client_id' => $clientId,
            'payment_method_id' => $methodId,
            'reference' => $reference,
            'amount' => (float) $invoice['amount'],
            'pay_currency' => normalize_payment_currency((string) ($invoice['pay_currency'] ?? $invoice['currency'] ?? default_currency())),
            'pay_method' => $payMethod,
            'fx_rate_used' => isset($invoice['fx_rate_used']) && $invoice['fx_rate_used'] !== null
                ? (float) $invoice['fx_rate_used']
                : fx_ngn_per_usd(),
            'crypto_asset' => null,
            'crypto_rate_used' => null,
            'crypto_amount_due' => null,
            'crypto_network' => null,
            'rate_locked_at' => (string) ($invoice['rate_locked_at'] ?? now()),
            'gateway_provider' => $provider,
            'gateway_transaction_id' => null,
            'gateway_session_id' => $sessionId,
            'gateway_payload' => null,
            'proof_path' => null,
            'note' => 'Gateway checkout initiated.',
            'rejection_reason' => null,
            'status' => 'submitted',
            'reviewed_by' => null,
            'created_at' => now(),
            'reviewed_at' => null,
            'confirmed_at' => null,
        ]
    );

    // Keep invoice/job unpaid until gateway confirmation callback succeeds.
    mark_invoice_jobs_payment_state($invoice, 'unpaid', $reference);

    return db_last_id();
}

function default_gateway_callback_url(string $provider, int $invoiceId): string
{
    return portal_absolute_url(url('payment_callback', ['provider' => $provider, 'invoice_id' => $invoiceId]));
}

function start_gateway_checkout(int $invoiceId, int $clientId, int $methodId): string
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    if ((int) $invoice['client_id'] !== $clientId) {
        throw new RuntimeException('You cannot pay this invoice.');
    }

    if ($invoice['status'] === 'paid') {
        throw new RuntimeException('This invoice is already paid.');
    }

    $method = get_payment_method($methodId);
    if (!$method || (int) $method['is_active'] !== 1) {
        throw new RuntimeException('Selected payment method is not active.');
    }

    $provider = (string) ($method['method_code'] ?? '');
    if (!is_gateway_payment_method($provider)) {
        throw new RuntimeException('Automatic gateway payment is not enabled for this method.');
    }

    $allowedCodes = payment_method_codes_for_currency((string) ($invoice['currency'] ?? default_currency()));
    if (!in_array($provider, $allowedCodes, true)) {
        throw new RuntimeException('This payment method is not supported for this invoice currency.');
    }

    apply_invoice_rate_context($invoiceId, [
        'pay_currency' => normalize_payment_currency((string) ($invoice['currency'] ?? default_currency())),
        'pay_method' => pay_method_from_method_code($provider),
        'fx_rate_used' => isset($invoice['fx_rate_used']) && $invoice['fx_rate_used'] !== null
            ? (float) $invoice['fx_rate_used']
            : fx_ngn_per_usd(),
        'rate_locked_at' => now(),
    ]);
    $invoice = get_invoice($invoiceId) ?? $invoice;

    $secretKey = trim((string) ($method['secret_key'] ?? ''));
    if ($secretKey === '') {
        throw new RuntimeException('Gateway secret key is missing in admin settings.');
    }

    $client = find_user_by_id($clientId);
    if (!$client) {
        throw new RuntimeException('Client account not found.');
    }

    $amount = (float) $invoice['amount'];
    $currency = strtoupper((string) ($invoice['currency'] ?? default_currency()));
    $callback = default_gateway_callback_url($provider, (int) $invoiceId);
    $reference = payment_reference(strtoupper($provider));
    $checkoutTitle = (string) ($invoice['service_name'] ?? 'Service order');
    $group = trim((string) ($invoice['payment_group'] ?? ''));
    if ($group !== '') {
        $count = (int) (db_one('SELECT COUNT(*) AS c FROM jobs WHERE payment_group = :payment_group', ['payment_group' => $group])['c'] ?? 0);
        if ($count > 1) {
            $checkoutTitle = 'Multi-service order (' . $count . ' items)';
        }
    }

    if ($provider === 'paystack') {
        $payload = [
            'email' => (string) $client['email'],
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callback,
            'metadata' => [
                'invoice_id' => (int) $invoiceId,
                'job_id' => (int) $invoice['job_id'],
                'client_id' => $clientId,
            ],
        ];

        $resp = gateway_json_post('https://api.paystack.co/transaction/initialize', $payload, $secretKey);
        if (empty($resp['status']) || empty($resp['data']['authorization_url'])) {
            throw new RuntimeException('Unable to initialize Paystack payment.');
        }

        create_gateway_payment_record($invoice, $clientId, $methodId, $provider, $reference, null);
        return (string) $resp['data']['authorization_url'];
    }

    if ($provider === 'flutterwave') {
        $payload = [
            'tx_ref' => $reference,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'redirect_url' => $callback,
            'customer' => [
                'email' => (string) $client['email'],
                'name' => (string) ($client['full_name'] ?? 'Client'),
                'phonenumber' => (string) ($client['phone'] ?? ''),
            ],
            'customizations' => [
                'title' => 'Invoice ' . (string) $invoice['invoice_number'],
                'description' => 'Payment for ' . $checkoutTitle,
            ],
            'meta' => [
                'invoice_id' => (int) $invoiceId,
                'job_id' => (int) $invoice['job_id'],
                'client_id' => $clientId,
            ],
        ];

        $resp = gateway_json_post('https://api.flutterwave.com/v3/payments', $payload, $secretKey);
        $status = strtolower((string) ($resp['status'] ?? ''));
        $link = (string) ($resp['data']['link'] ?? '');
        if ($status !== 'success' || $link === '') {
            throw new RuntimeException((string) ($resp['message'] ?? 'Unable to initialize Flutterwave payment.'));
        }

        create_gateway_payment_record($invoice, $clientId, $methodId, $provider, $reference, null);
        return $link;
    }

    if ($provider === 'stripe') {
        $successUrl = $callback . '&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = portal_absolute_url('invoice.php?id=' . (int) $invoiceId);

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $reference,
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][product_data][name]' => $checkoutTitle,
            'line_items[0][price_data][unit_amount]' => (string) ((int) round($amount * 100)),
            'line_items[0][quantity]' => '1',
        ];
        // A customer who explicitly saved a card can select it during
        // Stripe-hosted checkout. Others retain the existing guest flow.
        $vaultCustomerId = function_exists('stripe_vault_customer_id')
            ? stripe_vault_customer_id($clientId) : '';
        if ($vaultCustomerId !== '') {
            $payload['customer'] = $vaultCustomerId;
            $payload['saved_payment_method_options[payment_method_remove]'] = 'enabled';
        } else {
            $payload['customer_email'] = (string) $client['email'];
        }

        $resp = gateway_form_post('https://api.stripe.com/v1/checkout/sessions', $payload, $secretKey);
        $checkoutUrl = (string) ($resp['url'] ?? '');
        $sessionId = (string) ($resp['id'] ?? '');
        if ($checkoutUrl === '' || $sessionId === '') {
            throw new RuntimeException('Unable to initialize Stripe checkout.');
        }

        create_gateway_payment_record($invoice, $clientId, $methodId, $provider, $reference, $sessionId);
        return $checkoutUrl;
    }

    throw new RuntimeException('Unsupported gateway.');
}

function find_gateway_payment_by_reference(int $invoiceId, string $provider, string $reference): ?array
{
    return db_one(
        'SELECT p.*, pm.callback_url AS method_callback_url
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         WHERE p.invoice_id = :invoice_id AND p.gateway_provider = :provider AND p.reference = :reference
         ORDER BY p.id DESC LIMIT 1',
        [
            'invoice_id' => $invoiceId,
            'provider' => $provider,
            'reference' => $reference,
        ]
    );
}

function find_gateway_payment_by_session_id(int $invoiceId, string $provider, string $sessionId): ?array
{
    return db_one(
        'SELECT p.*, pm.callback_url AS method_callback_url
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         WHERE p.invoice_id = :invoice_id AND p.gateway_provider = :provider AND p.gateway_session_id = :session_id
         ORDER BY p.id DESC LIMIT 1',
        [
            'invoice_id' => $invoiceId,
            'provider' => $provider,
            'session_id' => $sessionId,
        ]
    );
}

function find_gateway_payment_by_reference_any(string $provider, string $reference): ?array
{
    return db_one(
        'SELECT p.*, pm.callback_url AS method_callback_url
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         WHERE p.gateway_provider = :provider AND p.reference = :reference
         ORDER BY p.id DESC LIMIT 1',
        [
            'provider' => $provider,
            'reference' => $reference,
        ]
    );
}

function find_gateway_payment_by_session_id_any(string $provider, string $sessionId): ?array
{
    return db_one(
        'SELECT p.*, pm.callback_url AS method_callback_url
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         WHERE p.gateway_provider = :provider AND p.gateway_session_id = :session_id
         ORDER BY p.id DESC LIMIT 1',
        [
            'provider' => $provider,
            'session_id' => $sessionId,
        ]
    );
}

function header_value(array $headers, string $name): string
{
    foreach ($headers as $key => $value) {
        if (strtolower((string) $key) !== strtolower($name)) {
            continue;
        }

        if (is_array($value)) {
            return trim((string) ($value[0] ?? ''));
        }

        return trim((string) $value);
    }

    return '';
}

function verify_stripe_webhook_signature(string $payload, string $signatureHeader, string $secret): bool
{
    if ($signatureHeader === '' || $secret === '') {
        return false;
    }

    $parts = array_map('trim', explode(',', $signatureHeader));
    $timestamp = '';
    $candidates = [];

    foreach ($parts as $part) {
        if (!str_contains($part, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $part, 2);
        $k = trim($k);
        $v = trim($v);

        if ($k === 't') {
            $timestamp = $v;
        }

        if ($k === 'v1' && $v !== '') {
            $candidates[] = $v;
        }
    }

    if ($timestamp === '' || !$candidates) {
        return false;
    }

    if (!ctype_digit($timestamp)) {
        return false;
    }

    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($candidates as $candidate) {
        if (hash_equals($expected, $candidate)) {
            return true;
        }
    }

    return false;
}

function mark_gateway_payment_confirmed(int $paymentId, ?string $transactionId, array $payload): void
{
    $payment = db_one('SELECT * FROM job_payments WHERE id = :id LIMIT 1', ['id' => $paymentId]);
    if (!$payment) {
        throw new RuntimeException('Payment record not found.');
    }

    if ($payment['status'] === 'confirmed') {
        return;
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
    db_execute(
        'UPDATE job_payments
         SET status = :status, reviewed_by = :reviewed_by, reviewed_at = :reviewed_at, confirmed_at = :confirmed_at,
             rejection_reason = :rejection_reason, gateway_transaction_id = :gateway_transaction_id, gateway_payload = :gateway_payload
         WHERE id = :id',
        [
            'status' => 'confirmed',
            'reviewed_by' => null,
            'reviewed_at' => now(),
            'confirmed_at' => now(),
            'rejection_reason' => null,
            'gateway_transaction_id' => $transactionId,
            'gateway_payload' => $payloadJson,
            'id' => $paymentId,
        ]
    );

    $invoice = db_one('SELECT * FROM invoices WHERE id = :id LIMIT 1', ['id' => (int) $payment['invoice_id']]);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found for this payment.');
    }

    mark_invoice_jobs_payment_state($invoice, 'paid', (string) $payment['reference']);
    db_execute("UPDATE invoices SET status = 'paid' WHERE id = :id", ['id' => (int) $payment['invoice_id']]);

    add_comment((int) $payment['job_id'], (int) $payment['client_id'], 'Gateway payment confirmed. Reference: ' . (string) $payment['reference']);
    create_notification((int) $payment['client_id'], 'Payment confirmed', 'Your payment was confirmed automatically and your order is now active.', 'invoice.php?id=' . (int) $payment['invoice_id']);
    notify_roles(['admin', 'finance'], 'Payment auto-confirmed', 'A gateway payment has been confirmed for invoice #' . (int) $payment['invoice_id'] . '.', url('payments'));

    if (!empty($invoice['payment_group'])) {
        $jobs = db_all('SELECT id FROM jobs WHERE payment_group = :payment_group', ['payment_group' => (string) $invoice['payment_group']]);
        foreach ($jobs as $row) {
            maybe_accrue_commission((int) $row['id']);
        }
    } else {
        maybe_accrue_commission((int) $payment['job_id']);
    }
}

function mark_gateway_payment_rejected(int $paymentId, string $reason, array $payload): void
{
    $payment = db_one('SELECT * FROM job_payments WHERE id = :id LIMIT 1', ['id' => $paymentId]);
    if (!$payment) {
        throw new RuntimeException('Payment record not found.');
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
    db_execute(
        'UPDATE job_payments
         SET status = :status, reviewed_by = :reviewed_by, reviewed_at = :reviewed_at, rejection_reason = :rejection_reason, gateway_payload = :gateway_payload
         WHERE id = :id',
        [
            'status' => 'rejected',
            'reviewed_by' => null,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'gateway_payload' => $payloadJson,
            'id' => $paymentId,
        ]
    );

    $confirmedCount = (int) (db_one(
        "SELECT COUNT(*) AS c FROM job_payments WHERE invoice_id = :invoice_id AND status = 'confirmed'",
        ['invoice_id' => (int) $payment['invoice_id']]
    )['c'] ?? 0);

    if ($confirmedCount === 0) {
        $invoice = db_one('SELECT * FROM invoices WHERE id = :id LIMIT 1', ['id' => (int) $payment['invoice_id']]);
        if ($invoice) {
            mark_invoice_jobs_payment_state($invoice, 'unpaid');
        }
        db_execute("UPDATE invoices SET status = 'issued' WHERE id = :id", ['id' => (int) $payment['invoice_id']]);
    }

    create_notification((int) $payment['client_id'], 'Payment failed', 'Automatic payment verification failed. Please try again or use another method.', 'invoice.php?id=' . (int) $payment['invoice_id']);
}

function gateway_return_redirect_url(array $payment, bool $success): string
{
    $callback = trim((string) ($payment['method_callback_url'] ?? ''));
    if ($callback === '') {
        return 'invoice.php?id=' . (int) $payment['invoice_id'];
    }

    $sep = str_contains($callback, '?') ? '&' : '?';
    return $callback . $sep . http_build_query([
        'status' => $success ? 'success' : 'failed',
        'reference' => (string) ($payment['reference'] ?? ''),
        'invoice' => (int) ($payment['invoice_id'] ?? 0),
    ]);
}

function handle_gateway_callback(string $provider, int $invoiceId, array $query): array
{
    $provider = strtolower(trim($provider));

    if (!in_array($provider, ['paystack', 'flutterwave', 'stripe'], true)) {
        throw new RuntimeException('Unsupported payment callback provider.');
    }

    if ($provider === 'paystack') {
        $reference = trim((string) ($query['reference'] ?? ''));
        if ($reference === '') {
            throw new RuntimeException('Missing Paystack payment reference.');
        }

        $payment = find_gateway_payment_by_reference($invoiceId, $provider, $reference);
        if (!$payment) {
            throw new RuntimeException('Gateway payment record not found.');
        }

        $method = get_payment_method((int) $payment['payment_method_id']);
        $secret = trim((string) ($method['secret_key'] ?? ''));
        if ($secret === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $verify = gateway_json_get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference), $secret);
        $ok = !empty($verify['status']) && strtolower((string) ($verify['data']['status'] ?? '')) === 'success';
        if ($ok) {
            $txId = (string) ($verify['data']['id'] ?? '');
            mark_gateway_payment_confirmed((int) $payment['id'], $txId !== '' ? $txId : null, $verify);
            return ['success' => true, 'message' => 'Payment verified successfully.', 'redirect_url' => gateway_return_redirect_url($payment, true)];
        }

        $reason = (string) ($verify['message'] ?? 'Payment could not be verified.');
        mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
        return ['success' => false, 'message' => $reason, 'redirect_url' => gateway_return_redirect_url($payment, false)];
    }

    if ($provider === 'flutterwave') {
        $reference = trim((string) ($query['tx_ref'] ?? ''));
        $transactionId = trim((string) ($query['transaction_id'] ?? ''));
        if ($reference === '' || $transactionId === '') {
            throw new RuntimeException('Missing Flutterwave callback parameters.');
        }

        $payment = find_gateway_payment_by_reference($invoiceId, $provider, $reference);
        if (!$payment) {
            throw new RuntimeException('Gateway payment record not found.');
        }

        $method = get_payment_method((int) $payment['payment_method_id']);
        $secret = trim((string) ($method['secret_key'] ?? ''));
        if ($secret === '') {
            throw new RuntimeException('Flutterwave secret key is not configured.');
        }

        $verify = gateway_json_get('https://api.flutterwave.com/v3/transactions/' . rawurlencode($transactionId) . '/verify', $secret);
        $status = strtolower((string) ($verify['data']['status'] ?? ''));
        $refMatches = (string) ($verify['data']['tx_ref'] ?? '') === $reference;
        $ok = strtolower((string) ($verify['status'] ?? '')) === 'success' && $status === 'successful' && $refMatches;

        if ($ok) {
            mark_gateway_payment_confirmed((int) $payment['id'], $transactionId, $verify);
            return ['success' => true, 'message' => 'Payment verified successfully.', 'redirect_url' => gateway_return_redirect_url($payment, true)];
        }

        $reason = (string) ($verify['message'] ?? 'Payment verification failed.');
        mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
        return ['success' => false, 'message' => $reason, 'redirect_url' => gateway_return_redirect_url($payment, false)];
    }

    // Stripe callback.
    $sessionId = trim((string) ($query['session_id'] ?? ''));
    if ($sessionId === '') {
        throw new RuntimeException('Missing Stripe session ID.');
    }

    $payment = find_gateway_payment_by_session_id($invoiceId, $provider, $sessionId);
    if (!$payment) {
        throw new RuntimeException('Gateway payment record not found.');
    }

    $method = get_payment_method((int) $payment['payment_method_id']);
    $secret = trim((string) ($method['secret_key'] ?? ''));
    if ($secret === '') {
        throw new RuntimeException('Stripe secret key is not configured.');
    }

    $verify = gateway_form_get('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId), $secret);
    $ok = strtolower((string) ($verify['payment_status'] ?? '')) === 'paid' && strtolower((string) ($verify['status'] ?? '')) === 'complete';

    if ($ok) {
        $txId = (string) ($verify['payment_intent'] ?? '');
        mark_gateway_payment_confirmed((int) $payment['id'], $txId !== '' ? $txId : $sessionId, $verify);
        return ['success' => true, 'message' => 'Payment verified successfully.', 'redirect_url' => gateway_return_redirect_url($payment, true)];
    }

    $reason = 'Stripe payment was not completed.';
    mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
    return ['success' => false, 'message' => $reason, 'redirect_url' => gateway_return_redirect_url($payment, false)];
}

function handle_gateway_webhook(string $provider, string $rawPayload, array $headers): array
{
    $provider = strtolower(trim($provider));
    if (!in_array($provider, ['paystack', 'flutterwave', 'stripe'], true)) {
        return ['success' => false, 'status_code' => 400, 'message' => 'Unsupported webhook provider.'];
    }

    $payload = json_decode($rawPayload, true);
    if (!is_array($payload)) {
        return ['success' => false, 'status_code' => 400, 'message' => 'Invalid webhook payload.'];
    }

    $method = get_payment_method_by_code($provider);
    if (!$method || (int) ($method['is_active'] ?? 0) !== 1) {
        return ['success' => false, 'status_code' => 400, 'message' => 'Payment method is disabled or missing.'];
    }

    $secret = trim((string) ($method['secret_key'] ?? ''));
    if ($secret === '') {
        return ['success' => false, 'status_code' => 500, 'message' => 'Gateway secret key is not configured.'];
    }

    if ($provider === 'paystack') {
        $signature = header_value($headers, 'x-paystack-signature');
        $expected = hash_hmac('sha512', $rawPayload, $secret);
        if ($signature === '' || !hash_equals($expected, $signature)) {
            return ['success' => false, 'status_code' => 403, 'message' => 'Invalid Paystack signature.'];
        }

        $event = strtolower((string) ($payload['event'] ?? ''));
        if ($event !== 'charge.success') {
            return ['success' => true, 'status_code' => 200, 'message' => 'Event ignored.'];
        }

        $reference = trim((string) ($payload['data']['reference'] ?? ''));
        if ($reference === '') {
            return ['success' => false, 'status_code' => 400, 'message' => 'Missing Paystack reference.'];
        }

        $payment = find_gateway_payment_by_reference_any($provider, $reference);
        if (!$payment) {
            return ['success' => true, 'status_code' => 200, 'message' => 'No matching payment record.'];
        }

        $verify = gateway_json_get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference), $secret);
        $ok = !empty($verify['status']) && strtolower((string) ($verify['data']['status'] ?? '')) === 'success';
        if ($ok) {
            $txId = (string) ($verify['data']['id'] ?? '');
            mark_gateway_payment_confirmed((int) $payment['id'], $txId !== '' ? $txId : null, $verify);
            return ['success' => true, 'status_code' => 200, 'message' => 'Paystack payment confirmed.'];
        }

        $reason = (string) ($verify['message'] ?? 'Payment could not be verified.');
        mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
        return ['success' => true, 'status_code' => 200, 'message' => 'Paystack payment marked rejected.'];
    }

    if ($provider === 'flutterwave') {
        $webhookHash = trim((string) ($method['private_key'] ?? ''));
        if ($webhookHash !== '') {
            $incomingHash = header_value($headers, 'verif-hash');
            if ($incomingHash === '' || !hash_equals($webhookHash, $incomingHash)) {
                return ['success' => false, 'status_code' => 403, 'message' => 'Invalid Flutterwave webhook hash.'];
            }
        }

        $reference = trim((string) ($payload['data']['tx_ref'] ?? ''));
        $transactionId = trim((string) ($payload['data']['id'] ?? ''));
        if ($reference === '' || $transactionId === '') {
            return ['success' => true, 'status_code' => 200, 'message' => 'Event ignored.'];
        }

        $payment = find_gateway_payment_by_reference_any($provider, $reference);
        if (!$payment) {
            return ['success' => true, 'status_code' => 200, 'message' => 'No matching payment record.'];
        }

        $verify = gateway_json_get('https://api.flutterwave.com/v3/transactions/' . rawurlencode($transactionId) . '/verify', $secret);
        $status = strtolower((string) ($verify['data']['status'] ?? ''));
        $refMatches = (string) ($verify['data']['tx_ref'] ?? '') === $reference;
        $ok = strtolower((string) ($verify['status'] ?? '')) === 'success' && $status === 'successful' && $refMatches;

        if ($ok) {
            mark_gateway_payment_confirmed((int) $payment['id'], $transactionId, $verify);
            return ['success' => true, 'status_code' => 200, 'message' => 'Flutterwave payment confirmed.'];
        }

        $reason = (string) ($verify['message'] ?? 'Flutterwave payment verification failed.');
        mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
        return ['success' => true, 'status_code' => 200, 'message' => 'Flutterwave payment marked rejected.'];
    }

    $webhookSecret = trim((string) ($method['private_key'] ?? ''));
    if ($webhookSecret !== '') {
        $signatureHeader = header_value($headers, 'stripe-signature');
        if (!verify_stripe_webhook_signature($rawPayload, $signatureHeader, $webhookSecret)) {
            return ['success' => false, 'status_code' => 403, 'message' => 'Invalid Stripe signature.'];
        }
    }

    $eventType = strtolower((string) ($payload['type'] ?? ''));
    if (!in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed'], true)) {
        return ['success' => true, 'status_code' => 200, 'message' => 'Event ignored.'];
    }

    $sessionId = trim((string) ($payload['data']['object']['id'] ?? ''));
    if ($sessionId === '') {
        return ['success' => false, 'status_code' => 400, 'message' => 'Missing Stripe session id.'];
    }

    $payment = find_gateway_payment_by_session_id_any($provider, $sessionId);
    if (!$payment) {
        return ['success' => true, 'status_code' => 200, 'message' => 'No matching payment record.'];
    }

    $verify = gateway_form_get('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId), $secret);
    $ok = strtolower((string) ($verify['payment_status'] ?? '')) === 'paid' && strtolower((string) ($verify['status'] ?? '')) === 'complete';

    if ($ok) {
        $txId = (string) ($verify['payment_intent'] ?? '');
        mark_gateway_payment_confirmed((int) $payment['id'], $txId !== '' ? $txId : $sessionId, $verify);
        return ['success' => true, 'status_code' => 200, 'message' => 'Stripe payment confirmed.'];
    }

    $reason = 'Stripe payment was not completed.';
    mark_gateway_payment_rejected((int) $payment['id'], $reason, $verify);
    return ['success' => true, 'status_code' => 200, 'message' => 'Stripe payment marked rejected.'];
}

function store_payment_proof(array $file, int $invoiceId): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Payment proof upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 6 * 1024 * 1024) {
        throw new RuntimeException('Payment proof is too large. Max 6MB.');
    }

    $original = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Invalid proof format. Allowed: jpg, jpeg, png, webp, pdf.');
    }

    ensure_upload_directory();
    $dir = upload_directory() . '/payment_proofs';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original) ?: 'proof.' . $extension;
    $stored = 'inv_' . $invoiceId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
    $path = $dir . '/' . $stored;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('Could not save payment proof.');
    }

    return $path;
}

function list_payments_for_invoice(int $invoiceId): array
{
    return db_all(
        'SELECT p.*, pm.display_name AS method_name
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         WHERE p.invoice_id = :invoice_id
         ORDER BY p.created_at DESC',
        ['invoice_id' => $invoiceId]
    );
}

function list_payments_admin(array $filters = []): array
{
    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $sort = strtolower(trim((string) ($filters['sort'] ?? 'newest')));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = (int) ($filters['per_page'] ?? 10);
    $methodId = (int) ($filters['method_id'] ?? 0);

    $allowedStatus = ['submitted', 'confirmed', 'rejected'];
    $allowedSort = ['newest', 'oldest', 'amount_high', 'amount_low', 'status', 'method'];
    $allowedPerPage = [10, 25, 50, 100];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'newest';
    }
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }
    if ($methodId < 1) {
        $methodId = 0;
    }

    $sortSqlMap = [
        'newest' => 'p.created_at DESC',
        'oldest' => 'p.created_at ASC',
        'amount_high' => 'p.amount DESC, p.created_at DESC',
        'amount_low' => 'p.amount ASC, p.created_at DESC',
        'status' => 'p.status ASC, p.created_at DESC',
        'method' => 'pm.display_name ASC, p.created_at DESC',
    ];

    $fromSql = ' FROM job_payments p
                 JOIN payment_methods pm ON pm.id = p.payment_method_id
                 JOIN users c ON c.id = p.client_id
                 JOIN invoices i ON i.id = p.invoice_id
                 JOIN jobs j ON j.id = p.job_id
                 LEFT JOIN users r ON r.id = p.reviewed_by
                 WHERE 1=1';
    $params = [];

    if ($status !== '') {
        $fromSql .= ' AND p.status = :status';
        $params['status'] = $status;
    }
    if ($methodId > 0) {
        $fromSql .= ' AND p.payment_method_id = :method_id';
        $params['method_id'] = $methodId;
    }

    $totalRow = db_one('SELECT COUNT(*) AS total' . $fromSql, $params);
    $total = (int) ($totalRow['total'] ?? 0);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $items = db_all(
        'SELECT p.*, pm.display_name AS method_name, c.full_name AS client_name, i.invoice_number, i.currency AS invoice_currency,
                j.job_code, r.full_name AS reviewer_name'
        . $fromSql
        . ' ORDER BY ' . $sortSqlMap[$sort]
        . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
        $params
    );

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? ($offset + 1) : 0,
            'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
        ],
    ];
}

function list_all_payments(): array
{
    return db_all(
        'SELECT p.*, pm.display_name AS method_name, c.full_name AS client_name, i.invoice_number, i.currency AS invoice_currency,
                j.job_code, r.full_name AS reviewer_name
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         JOIN users c ON c.id = p.client_id
         JOIN invoices i ON i.id = p.invoice_id
         JOIN jobs j ON j.id = p.job_id
         LEFT JOIN users r ON r.id = p.reviewed_by
         ORDER BY p.created_at DESC'
    );
}

function get_payment(int $paymentId): ?array
{
    return db_one(
        'SELECT p.*, pm.display_name AS method_name, c.full_name AS client_name, c.email AS client_email,
                i.invoice_number, i.status AS invoice_status, i.client_id AS invoice_client_id, i.currency AS invoice_currency,
                j.job_code, j.id AS job_id
         FROM job_payments p
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         JOIN users c ON c.id = p.client_id
         JOIN invoices i ON i.id = p.invoice_id
         JOIN jobs j ON j.id = p.job_id
         WHERE p.id = :id LIMIT 1',
        ['id' => $paymentId]
    );
}

function submit_invoice_payment(
    int $invoiceId,
    int $clientId,
    int $methodId,
    string $reference,
    string $note = '',
    ?array $proofFile = null,
    array $options = []
): void
{
    $invoice = get_invoice($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    if ((int) $invoice['client_id'] !== $clientId) {
        throw new RuntimeException('You cannot pay this invoice.');
    }

    if ($invoice['status'] === 'paid') {
        throw new RuntimeException('This invoice is already paid.');
    }

    $method = get_payment_method($methodId);
    if (!$method || (int) $method['is_active'] !== 1) {
        throw new RuntimeException('Selected payment method is not active.');
    }

    $allowedCodes = payment_method_codes_for_currency((string) ($invoice['currency'] ?? default_currency()));
    if (!in_array((string) $method['method_code'], $allowedCodes, true)) {
        throw new RuntimeException('This payment method is not supported for invoice currency.');
    }

    if (trim($reference) === '') {
        throw new RuntimeException('Enter payment reference/transaction ID.');
    }

    $requiresProof = (int) ($method['requires_proof'] ?? 1) === 1;
    if ($requiresProof && (!$proofFile || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
        throw new RuntimeException('Please upload payment proof before submitting.');
    }

    $proofPath = null;
    if ($proofFile && ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $proofPath = store_payment_proof($proofFile, $invoiceId);
    }

    $methodCode = (string) ($method['method_code'] ?? '');
    $payMethod = pay_method_from_method_code($methodCode);
    $payCurrency = normalize_payment_currency((string) ($options['pay_currency'] ?? $invoice['currency'] ?? default_currency()));
    $fxRateUsed = isset($options['fx_rate_used']) && is_numeric($options['fx_rate_used'])
        ? (float) $options['fx_rate_used']
        : (isset($invoice['fx_rate_used']) && is_numeric($invoice['fx_rate_used']) ? (float) $invoice['fx_rate_used'] : fx_ngn_per_usd());

    $cryptoAsset = null;
    $cryptoRateUsed = null;
    $cryptoAmountDue = null;
    $cryptoNetwork = null;
    $lockedAt = now();

    if ($payMethod === 'crypto') {
        $cryptoAsset = normalize_crypto_asset((string) ($options['crypto_asset'] ?? $invoice['crypto_asset'] ?? 'USDT'));
        $cryptoNetwork = normalize_crypto_network($cryptoAsset, (string) ($options['crypto_network'] ?? $invoice['crypto_network'] ?? ''));
        $lock = lock_invoice_crypto_context($invoiceId, $cryptoAsset, $cryptoNetwork);
        $fxRateUsed = (float) ($lock['fx_rate_used'] ?? $fxRateUsed);
        $cryptoRateUsed = (float) ($lock['crypto_rate_used'] ?? 0);
        $cryptoAmountDue = (float) ($lock['crypto_amount_due'] ?? 0);
        $lockedAt = (string) ($lock['rate_locked_at'] ?? now());
        $invoice = get_invoice($invoiceId) ?? $invoice;
    } else {
        apply_invoice_rate_context($invoiceId, [
            'pay_currency' => $payCurrency,
            'pay_method' => $payMethod,
            'fx_rate_used' => $fxRateUsed,
            'rate_locked_at' => $lockedAt,
        ]);
        $invoice = get_invoice($invoiceId) ?? $invoice;
    }

    db_execute(
        'INSERT INTO job_payments (
            job_id, invoice_id, client_id, payment_method_id, reference, amount, pay_currency, pay_method, fx_rate_used,
            crypto_asset, crypto_rate_used, crypto_amount_due, crypto_network, rate_locked_at,
            proof_path, note, rejection_reason, status, reviewed_by, created_at, reviewed_at, confirmed_at
         )
         VALUES (
            :job_id, :invoice_id, :client_id, :payment_method_id, :reference, :amount, :pay_currency, :pay_method, :fx_rate_used,
            :crypto_asset, :crypto_rate_used, :crypto_amount_due, :crypto_network, :rate_locked_at,
            :proof_path, :note, :rejection_reason, :status, :reviewed_by, :created_at, :reviewed_at, :confirmed_at
         )',
        [
            'job_id' => (int) $invoice['job_id'],
            'invoice_id' => $invoiceId,
            'client_id' => $clientId,
            'payment_method_id' => $methodId,
            'reference' => trim($reference),
            'amount' => (float) $invoice['amount'],
            'pay_currency' => $payCurrency,
            'pay_method' => $payMethod,
            'fx_rate_used' => $fxRateUsed,
            'crypto_asset' => $cryptoAsset,
            'crypto_rate_used' => $cryptoRateUsed,
            'crypto_amount_due' => $cryptoAmountDue,
            'crypto_network' => $cryptoNetwork,
            'rate_locked_at' => $lockedAt,
            'proof_path' => $proofPath,
            'note' => trim($note),
            'rejection_reason' => null,
            'status' => 'submitted',
            'reviewed_by' => null,
            'created_at' => now(),
            'reviewed_at' => null,
            'confirmed_at' => null,
        ]
    );

    // Submitted proof is pending review; payment status remains unpaid until confirmed.
    mark_invoice_jobs_payment_state($invoice, 'unpaid', trim($reference));

    $comment = 'Payment submitted for review using ' . $method['display_name'] . '. Ref: ' . trim($reference);
    if ($payMethod === 'crypto' && $cryptoAsset !== null && $cryptoAmountDue !== null) {
        $comment .= ' Locked quote: ' . number_format((float) $cryptoAmountDue, crypto_asset_precision($cryptoAsset, true), '.', '') . ' ' . $cryptoAsset . '.';
    }

    add_comment((int) $invoice['job_id'], $clientId, $comment);
    notify_roles(['admin', 'finance'], 'Payment proof submitted', 'Client submitted payment proof for invoice ' . $invoice['invoice_number'] . '.', url('payments'));
}

function review_payment(int $paymentId, string $status, int $reviewerId, string $note = ''): void
{
    $payment = get_payment($paymentId);
    if (!$payment) {
        throw new RuntimeException('Payment record not found.');
    }

    if (!in_array($status, ['confirmed', 'rejected'], true)) {
        throw new RuntimeException('Invalid payment review status.');
    }

    if ($status === 'rejected' && trim($note) === '') {
        throw new RuntimeException('Enter a rejection reason before rejecting this payment.');
    }

    if ($payment['status'] === 'confirmed' && $status === 'confirmed') {
        return;
    }

    db_execute(
        'UPDATE job_payments SET status = :status, reviewed_by = :reviewed_by, reviewed_at = :reviewed_at,
         confirmed_at = :confirmed_at, rejection_reason = :rejection_reason WHERE id = :id',
        [
            'status' => $status,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'confirmed_at' => $status === 'confirmed' ? now() : null,
            'rejection_reason' => $status === 'rejected' ? trim($note) : null,
            'id' => $paymentId,
        ]
    );

    if ($status === 'confirmed') {
        $invoice = db_one('SELECT * FROM invoices WHERE id = :id LIMIT 1', ['id' => (int) $payment['invoice_id']]);
        if (!$invoice) {
            throw new RuntimeException('Invoice not found for this payment.');
        }

        mark_invoice_jobs_payment_state($invoice, 'paid', (string) $payment['reference']);

        db_execute("UPDATE invoices SET status = 'paid' WHERE id = :id", ['id' => $payment['invoice_id']]);
        add_comment((int) $payment['job_id'], $reviewerId, 'Payment confirmed by finance/admin.');
        create_notification((int) $payment['client_id'], 'Payment confirmed', 'Your payment was confirmed and service processing is now active.', 'invoice.php?id=' . (int) $payment['invoice_id']);
        if (!empty($invoice['payment_group'])) {
            $jobs = db_all('SELECT id FROM jobs WHERE payment_group = :payment_group', ['payment_group' => (string) $invoice['payment_group']]);
            foreach ($jobs as $row) {
                maybe_accrue_commission((int) $row['id']);
            }
        } else {
            maybe_accrue_commission((int) $payment['job_id']);
        }
        return;
    }

    $confirmedCount = (int) (db_one(
        "SELECT COUNT(*) AS c FROM job_payments WHERE invoice_id = :invoice_id AND status = 'confirmed'",
        ['invoice_id' => $payment['invoice_id']]
    )['c'] ?? 0);

    if ($confirmedCount === 0) {
        $invoice = db_one('SELECT * FROM invoices WHERE id = :id LIMIT 1', ['id' => (int) $payment['invoice_id']]);
        if ($invoice) {
            mark_invoice_jobs_payment_state($invoice, 'unpaid');
        }
        db_execute("UPDATE invoices SET status = 'issued' WHERE id = :id", ['id' => $payment['invoice_id']]);
    }

    create_notification((int) $payment['client_id'], 'Payment rejected', 'Your payment proof was rejected. Please resubmit with correct details.', 'invoice.php?id=' . (int) $payment['invoice_id']);
}

function find_payment_for_download(int $paymentId): ?array
{
    return db_one(
        'SELECT p.id, p.proof_path, p.client_id, i.client_id AS invoice_client_id
         FROM job_payments p
         JOIN invoices i ON i.id = p.invoice_id
         WHERE p.id = :id LIMIT 1',
        ['id' => $paymentId]
    );
}

function list_banners(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM banners';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id DESC';

    return db_all($sql);
}

function save_banner(array $input, ?array $desktopFile = null, ?array $mobileFile = null): void
{
    $title = trim($input['title'] ?? '');
    if ($title === '') {
        throw new RuntimeException('Banner title is required.');
    }

    $bannerId = !empty($input['banner_id']) ? (int) $input['banner_id'] : null;
    $existing = $bannerId ? db_one('SELECT * FROM banners WHERE id = :id LIMIT 1', ['id' => $bannerId]) : null;

    $imagePath = $existing['image_path'] ?? '';
    if ($desktopFile && ($desktopFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $imagePath = save_uploaded_image($desktopFile, 'banners');
    }

    $mobileImagePath = $existing['mobile_image_path'] ?? '';
    if ($mobileFile && ($mobileFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $mobileImagePath = save_uploaded_image($mobileFile, 'banners');
    }

    if ($imagePath === '') {
        throw new RuntimeException('Desktop banner image is required.');
    }

    if ($mobileImagePath === '') {
        throw new RuntimeException('Mobile banner image is required.');
    }

    $payload = [
        'title' => $title,
        'image_path' => $imagePath,
        'mobile_image_path' => $mobileImagePath,
        'link_url' => trim($input['link_url'] ?? ''),
        'is_active' => !empty($input['is_active']) ? 1 : 0,
        'sort_order' => (int) ($input['sort_order'] ?? 0),
    ];

    if ($bannerId === null) {
        db_execute(
            'INSERT INTO banners (title, image_path, mobile_image_path, link_url, is_active, sort_order, created_at)
             VALUES (:title, :image_path, :mobile_image_path, :link_url, :is_active, :sort_order, :created_at)',
            $payload + ['created_at' => now()]
        );
        return;
    }

    db_execute(
        'UPDATE banners SET title = :title, image_path = :image_path, mobile_image_path = :mobile_image_path, link_url = :link_url,
         is_active = :is_active, sort_order = :sort_order WHERE id = :id',
        $payload + ['id' => $bannerId]
    );
}

function delete_banner(int $bannerId): void
{
    db_execute('DELETE FROM banners WHERE id = :id', ['id' => $bannerId]);
}

function list_active_banners(): array
{
    return list_banners(true);
}

function create_notification(int $userId, string $title, string $message, string $targetUrl = ''): void
{
    db_execute(
        'INSERT INTO notifications (user_id, title, message, target_url, is_read, created_at)
         VALUES (:user_id, :title, :message, :target_url, :is_read, :created_at)',
        [
            'user_id' => $userId,
            'title' => trim($title),
            'message' => trim($message),
            'target_url' => trim($targetUrl),
            'is_read' => 0,
            'created_at' => now(),
        ]
    );

    if (email_notifications_enabled()) {
        $user = find_user_by_id($userId);
        if ($user && !empty($user['email'])) {
            $mailMessage = $message;
            if ($targetUrl !== '') {
                $mailMessage .= "\n\nOpen: " . portal_absolute_url($targetUrl);
            }
            send_email((string) $user['email'], $title, $mailMessage);
        }
    }
}

function notify_users(array $userIds, string $title, string $message, string $targetUrl = ''): void
{
    $unique = array_unique(array_filter(array_map('intval', $userIds)));
    foreach ($unique as $userId) {
        create_notification($userId, $title, $message, $targetUrl);
    }
}

function notify_roles(array $roles, string $title, string $message, string $targetUrl = ''): void
{
    if (!$roles) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $rows = db_query(
        "SELECT id FROM users WHERE status = 'active' AND account_state = 'active' AND deleted_at IS NULL AND role IN (" . $placeholders . ')',
        array_values($roles)
    )->fetchAll();

    $ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);
    notify_users($ids, $title, $message, $targetUrl);
}

function notify_all_users(string $title, string $message, string $targetUrl = ''): void
{
    $rows = db_all("SELECT id FROM users WHERE status = 'active' AND account_state = 'active' AND deleted_at IS NULL");
    $ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);
    notify_users($ids, $title, $message, $targetUrl);
}

function unread_notification_count(int $userId): int
{
    return (int) (db_one(
        'SELECT COUNT(*) AS c FROM notifications WHERE user_id = :user_id AND is_read = 0',
        ['user_id' => $userId]
    )['c'] ?? 0);
}

function list_notifications_for_user(int $userId, int $limit = 50): array
{
    return db_all(
        'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT ' . (int) $limit,
        ['user_id' => $userId]
    );
}

function get_notification_for_user(int $notificationId, int $userId): ?array
{
    return db_one(
        'SELECT * FROM notifications WHERE id = :id AND user_id = :user_id LIMIT 1',
        ['id' => $notificationId, 'user_id' => $userId]
    );
}

function notification_realtime_state(int $userId): array
{
    $row = db_one(
        'SELECT
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
            MAX(id) AS latest_id
         FROM notifications
         WHERE user_id = :user_id',
        ['user_id' => $userId]
    );

    return [
        'unread_count' => (int) ($row['unread_count'] ?? 0),
        'latest_id' => (int) ($row['latest_id'] ?? 0),
    ];
}

function mark_notification_read(int $notificationId, int $userId): void
{
    db_execute(
        'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id',
        ['id' => $notificationId, 'user_id' => $userId]
    );
}

function mark_all_notifications_read(int $userId): void
{
    db_execute('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id', ['user_id' => $userId]);
}

function clear_notification(int $notificationId, int $userId): void
{
    db_execute('DELETE FROM notifications WHERE id = :id AND user_id = :user_id', [
        'id' => $notificationId,
        'user_id' => $userId,
    ]);
}

function clear_all_notifications(int $userId): void
{
    db_execute('DELETE FROM notifications WHERE user_id = :user_id', ['user_id' => $userId]);
}

function list_announcements(bool $activeOnly = false): array
{
    $sql = 'SELECT a.*, u.full_name AS created_by_name
            FROM announcements a
            LEFT JOIN users u ON u.id = a.created_by';

    if ($activeOnly) {
        $sql .= ' WHERE a.is_active = 1';
    }

    $sql .= ' ORDER BY a.updated_at DESC, a.id DESC';

    return db_all($sql);
}

function get_announcement(int $id): ?array
{
    return db_one('SELECT * FROM announcements WHERE id = :id LIMIT 1', ['id' => $id]);
}

function store_announcement_attachment(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Announcement attachment upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('Attachment too large. Max 12MB.');
    }

    $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'pl', 'cgi', 'js'];
    $original = (string) ($file['name'] ?? 'attachment.bin');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (in_array($ext, $blocked, true)) {
        throw new RuntimeException('This file type is blocked.');
    }

    ensure_upload_directory();
    $dir = upload_directory() . '/announcements';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original) ?: 'announcement.bin';
    $stored = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
    $path = $dir . '/' . $stored;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('Could not save attachment.');
    }

    return $path;
}

function audience_roles(string $audience): array
{
    if ($audience === 'client') {
        return ['client'];
    }

    if ($audience === 'agent') {
        return ['agent'];
    }

    return ['admin', 'finance', 'client', 'agent'];
}

function save_announcement(array $input, int $adminId, ?array $attachment = null): int
{
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    if ($title === '' || $message === '') {
        throw new RuntimeException('Announcement title and message are required.');
    }

    $audience = trim((string) ($input['audience'] ?? 'all'));
    if (!in_array($audience, ['all', 'client', 'agent'], true)) {
        $audience = 'all';
    }

    $messageHtml = sanitize_limited_html((string) ($input['message_html'] ?? $message));
    if ($messageHtml === '') {
        $messageHtml = nl2br(e($message));
    }

    $payload = [
        'title' => $title,
        'message' => $message,
        'message_html' => $messageHtml,
        'audience' => $audience,
        'is_active' => !empty($input['is_active']) ? 1 : 0,
        'require_ack' => !empty($input['require_ack']) ? 1 : 0,
        'updated_at' => now(),
    ];

    $id = !empty($input['announcement_id']) ? (int) $input['announcement_id'] : null;
    $existing = $id ? get_announcement($id) : null;
    $attachmentPath = (string) ($existing['attachment_path'] ?? '');
    if ($attachment && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $attachmentPath = store_announcement_attachment($attachment);
    }

    if ($id === null) {
        db_execute(
            'INSERT INTO announcements (title, message, message_html, attachment_path, audience, is_active, require_ack, created_by, created_at, updated_at)
             VALUES (:title, :message, :message_html, :attachment_path, :audience, :is_active, :require_ack, :created_by, :created_at, :updated_at)',
            $payload + [
                'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
                'created_by' => $adminId,
                'created_at' => now(),
            ]
        );
        $id = db_last_id();
    } else {
        db_execute(
            'UPDATE announcements
             SET title = :title, message = :message, message_html = :message_html, attachment_path = :attachment_path,
                 audience = :audience, is_active = :is_active, require_ack = :require_ack, updated_at = :updated_at
             WHERE id = :id',
            $payload + [
                'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
                'id' => $id,
            ]
        );
    }

    if ($payload['is_active'] === 1) {
        $roles = audience_roles($audience);
        notify_roles($roles, 'New announcement', $title, url('announcement', ['id' => (int) $id]));
    }

    return (int) $id;
}

function delete_announcement(int $id): void
{
    db_execute('DELETE FROM announcements WHERE id = :id', ['id' => $id]);
}

function get_blocking_announcement_for_user(int $userId): ?array
{
    $user = find_user_by_id($userId);
    if (!$user) {
        return null;
    }

    return db_one(
        'SELECT a.* FROM announcements a
         LEFT JOIN announcement_reads r ON r.announcement_id = a.id AND r.user_id = :user_id
         WHERE a.is_active = 1
           AND a.require_ack = 1
           AND r.id IS NULL
           AND (a.audience = :all_audience OR a.audience = :user_role)
         ORDER BY a.updated_at DESC, a.id DESC
         LIMIT 1',
        [
            'user_id' => $userId,
            'all_audience' => 'all',
            'user_role' => (string) $user['role'],
        ]
    );
}

function acknowledge_announcement(int $announcementId, int $userId): void
{
    db_execute(
        'INSERT INTO announcement_reads (announcement_id, user_id, read_at)
         VALUES (:announcement_id, :user_id, :read_at)
         ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)',
        [
            'announcement_id' => $announcementId,
            'user_id' => $userId,
            'read_at' => now(),
        ]
    );
}

function can_access_announcement(array $user, array $announcement): bool
{
    if (in_array((string) ($user['role'] ?? ''), ['admin', 'finance'], true)) {
        return true;
    }

    $audience = (string) ($announcement['audience'] ?? 'all');
    if ($audience === 'all') {
        return true;
    }

    return $audience === (string) ($user['role'] ?? '');
}

function list_announcements_for_user(array $user, bool $activeOnly = true): array
{
    $sql = 'SELECT a.* FROM announcements a WHERE 1=1';
    $params = [];

    if ($activeOnly) {
        $sql .= ' AND a.is_active = 1';
    }

    $sql .= ' AND (a.audience = :audience_all OR a.audience = :audience_role)';
    $params['audience_all'] = 'all';
    $params['audience_role'] = (string) $user['role'];

    $sql .= ' ORDER BY a.updated_at DESC, a.id DESC';

    return db_all($sql, $params);
}

function list_manageable_users(string $role = 'all'): array
{
    $sql = "SELECT * FROM users WHERE deleted_at IS NULL";
    $params = [];

    if ($role !== 'all') {
        $sql .= ' AND role = :role';
        $params['role'] = $role;
    } else {
        $sql .= " AND role IN ('client', 'agent', 'author')";
    }

    $sql .= ' ORDER BY created_at DESC';

    return db_all($sql, $params);
}

function generate_temporary_password(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%';
    $max = strlen($alphabet) - 1;
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }
    return $out;
}

function admin_create_manageable_user(array $input, int $adminId): int
{
    $role = strtolower(trim((string) ($input['role'] ?? 'client')));
    if (!in_array($role, ['client', 'agent', 'author'], true)) {
        throw new RuntimeException('Role must be client, agent or author.');
    }

    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if ($email === '') {
        throw new RuntimeException('Email is required.');
    }
    if (find_user_by_email($email)) {
        throw new RuntimeException('Email already exists.');
    }

    $firstName = trim((string) ($input['first_name'] ?? ''));
    $lastName = trim((string) ($input['last_name'] ?? ''));
    $fullName = trim((string) ($input['full_name'] ?? ''));
    if ($fullName === '') {
        $fullName = trim($firstName . ' ' . $lastName);
    }
    if ($fullName === '') {
        $emailPrefix = strstr($email, '@', true);
        $fullName = $emailPrefix !== false && $emailPrefix !== '' ? $emailPrefix : 'Portal User';
    }

    $temporaryPassword = trim((string) ($input['temporary_password'] ?? ''));
    if (strlen($temporaryPassword) < 8) {
        throw new RuntimeException('Temporary password must be at least 8 characters.');
    }

    $forceFirstLoginUpdate = !empty($input['force_first_login_update']);

    $payload = [
        'full_name' => $fullName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => trim((string) ($input['phone'] ?? '')),
        'gender' => trim((string) ($input['gender'] ?? '')),
        'date_of_birth' => trim((string) ($input['date_of_birth'] ?? '')),
        'country' => trim((string) ($input['country'] ?? 'Nigeria')),
        'state_region' => trim((string) ($input['state_region'] ?? '')),
        'city' => trim((string) ($input['city'] ?? '')),
        'address_line' => trim((string) ($input['address_line'] ?? '')),
        'occupation' => trim((string) ($input['occupation'] ?? '')),
        'password' => $temporaryPassword,
        'role' => $role,
        'email_verified' => true,
        'must_change_password' => $forceFirstLoginUpdate ? 1 : 0,
    ];

    if ($role === 'agent') {
        $payload['bank_name'] = trim((string) ($input['bank_name'] ?? ''));
        $payload['bank_account_name'] = trim((string) ($input['bank_account_name'] ?? ''));
        $payload['bank_account_number'] = trim((string) ($input['bank_account_number'] ?? ''));
        $payload['bank_swift'] = trim((string) ($input['bank_swift'] ?? ''));
        $payload['bank_currency'] = trim((string) ($input['bank_currency'] ?? default_currency()));
    }

    $newUserId = create_user($payload);
    $newUserMessage = 'Your account was created by admin. Use your temporary password to login.';
    if ($forceFirstLoginUpdate) {
        $newUserMessage .= ' You must set a new password at first login.';
    }
    create_notification($newUserId, 'Account created', $newUserMessage, url('profile'));
    notify_users([$adminId], 'User created', ucfirst($role) . ' account was created successfully.', url('users'));

    return $newUserId;
}

function admin_create_custom_service_order_for_user(int $adminId, int $userId, array $input): int
{
    $targetUser = get_manageable_user($userId);
    if (!$targetUser) {
        throw new RuntimeException('User account not found.');
    }

    if ((string) ($targetUser['role'] ?? '') !== 'client') {
        throw new RuntimeException('Custom service orders can only be assigned to client accounts.');
    }

    $serviceId = (int) ($input['service_id'] ?? 0);
    $customServiceName = trim((string) ($input['custom_service_name'] ?? ''));
    $service = null;
    if ($serviceId > 0) {
        $service = get_service($serviceId);
        if (!$service || (int) ($service['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Select a valid active service.');
        }
    }

    if ($service === null && $customServiceName === '') {
        throw new RuntimeException('Select a service or enter a custom service name.');
    }

    if ($service === null) {
        $serviceId = ensure_system_custom_service();
        $service = get_service($serviceId);
    }

    $amountRaw = trim((string) ($input['custom_amount'] ?? ''));
    if ($amountRaw === '' || !is_numeric($amountRaw)) {
        throw new RuntimeException('Enter a valid custom amount.');
    }

    $customAmount = round((float) $amountRaw, 2);
    if ($customAmount <= 0) {
        throw new RuntimeException('Custom amount must be greater than zero.');
    }

    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        $title = $customServiceName !== ''
            ? $customServiceName
            : (string) ($service['name'] ?? 'Custom service');
    }

    $description = trim((string) ($input['description'] ?? ''));
    $payCurrency = normalize_payment_currency((string) ($input['pay_currency'] ?? ($service['currency'] ?? default_currency())));

    $payload = [
        'client_id' => $userId,
        'service_id' => $serviceId,
        'title' => $title,
        'description' => $description,
        'amount' => $customAmount,
        'currency' => $payCurrency,
        'pay_currency' => $payCurrency,
        'pay_method' => 'card',
        'checkout_pay_method' => 'card',
        'payment_status' => 'unpaid',
        'created_by' => $adminId,
        'tax_mode' => strtolower(trim((string) ($input['tax_mode'] ?? 'exclusive'))) === 'inclusive' ? 'inclusive' : 'exclusive',
    ];

    $taxPercentRaw = trim((string) ($input['tax_percent'] ?? ''));
    if ($taxPercentRaw !== '') {
        if (!is_numeric($taxPercentRaw)) {
            throw new RuntimeException('Tax must be a number between 0 and 100.');
        }
        $taxPercent = (float) $taxPercentRaw;
        if ($taxPercent < 0 || $taxPercent > 100) {
            throw new RuntimeException('Tax must be a number between 0 and 100.');
        }
        $payload['tax_percent'] = $taxPercent;
    }

    $jobId = create_job($payload);
    $invoice = get_invoice_by_job_id($jobId);
    $serviceName = (string) ($service['name'] ?? 'service');

    add_system_job_comment($jobId, $adminId, 'Custom service order created by admin. Awaiting client payment.');
    create_notification(
        $userId,
        'New custom invoice',
        'A custom "' . $serviceName . '" order was added to your account. Complete payment to activate processing.',
        $invoice ? ('invoice.php?id=' . (int) $invoice['id']) : url('jobs')
    );

    return $jobId;
}

function get_manageable_user(int $userId): ?array
{
    return db_one(
        "SELECT * FROM users WHERE id = :id AND deleted_at IS NULL AND role IN ('client', 'agent') LIMIT 1",
        ['id' => $userId]
    );
}

function update_user_profile_admin(int $userId, array $input): void
{
    $user = get_manageable_user($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $fullName = trim((string) ($input['full_name'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if ($fullName === '' || $email === '') {
        throw new RuntimeException('Full name and email are required.');
    }

    $emailOwner = db_one('SELECT id FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1', ['email' => $email]);
    if ($emailOwner && (int) $emailOwner['id'] !== $userId) {
        throw new RuntimeException('Email already belongs to another user.');
    }

    db_execute(
        'UPDATE users
         SET full_name = :full_name,
             first_name = :first_name,
             last_name = :last_name,
             phone = :phone, email = :email,
             gender = :gender, date_of_birth = :date_of_birth,
             country = :country, state_region = :state_region, city = :city, address_line = :address_line, occupation = :occupation,
             bank_name = :bank_name, bank_account_name = :bank_account_name,
             bank_account_number = :bank_account_number, bank_swift = :bank_swift, bank_currency = :bank_currency
         WHERE id = :id',
        [
            'full_name' => $fullName,
            'first_name' => trim((string) ($input['first_name'] ?? '')) ?: null,
            'last_name' => trim((string) ($input['last_name'] ?? '')) ?: null,
            'phone' => $phone,
            'email' => $email,
            'gender' => trim((string) ($input['gender'] ?? '')) ?: null,
            'date_of_birth' => trim((string) ($input['date_of_birth'] ?? '')) ?: null,
            'country' => trim((string) ($input['country'] ?? '')) ?: null,
            'state_region' => trim((string) ($input['state_region'] ?? '')) ?: null,
            'city' => trim((string) ($input['city'] ?? '')) ?: null,
            'address_line' => trim((string) ($input['address_line'] ?? '')) ?: null,
            'occupation' => trim((string) ($input['occupation'] ?? '')) ?: null,
            'bank_name' => trim((string) ($input['bank_name'] ?? '')),
            'bank_account_name' => trim((string) ($input['bank_account_name'] ?? '')),
            'bank_account_number' => trim((string) ($input['bank_account_number'] ?? '')),
            'bank_swift' => trim((string) ($input['bank_swift'] ?? '')),
            'bank_currency' => trim((string) ($input['bank_currency'] ?? default_currency())),
            'id' => $userId,
        ]
    );

    create_notification($userId, 'Profile updated', 'Admin updated your account profile details.', url('profile'));
}

function set_user_account_state_admin(int $userId, string $state): void
{
    $user = get_manageable_user($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    if (!in_array($state, ['active', 'suspended', 'blocked'], true)) {
        throw new RuntimeException('Invalid account state.');
    }

    db_execute(
        'UPDATE users
         SET account_state = :account_state, status = :status
         WHERE id = :id',
        [
            'account_state' => $state,
            'status' => $state === 'active' ? 'active' : 'inactive',
            'id' => $userId,
        ]
    );

    create_notification($userId, 'Account status updated', 'Your account state is now ' . $state . '.', url('profile'));
}

function soft_delete_user_admin(int $userId): void
{
    $user = get_manageable_user($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $newEmail = 'deleted+' . $userId . '+' . time() . '@local.invalid';

    db_execute(
        'UPDATE users
         SET deleted_at = :deleted_at, status = :status, account_state = :account_state, email = :email
         WHERE id = :id',
        [
            'deleted_at' => now(),
            'status' => 'inactive',
            'account_state' => 'blocked',
            'email' => $newEmail,
            'id' => $userId,
        ]
    );
}

function ensure_chat_thread(int $adminId, int $agentId): int
{
    $thread = db_one(
        'SELECT * FROM chat_threads WHERE admin_id = :admin_id AND agent_id = :agent_id LIMIT 1',
        ['admin_id' => $adminId, 'agent_id' => $agentId]
    );

    if ($thread) {
        return (int) $thread['id'];
    }

    db_execute(
        'INSERT INTO chat_threads (admin_id, agent_id, created_at, updated_at)
         VALUES (:admin_id, :agent_id, :created_at, :updated_at)',
        [
            'admin_id' => $adminId,
            'agent_id' => $agentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    return db_last_id();
}

function list_chat_threads_for_user(array $user): array
{
    $agentSeenSelect = users_last_seen_enabled() ? 'a.last_seen_at AS agent_last_seen_at' : 'NULL AS agent_last_seen_at';
    $adminSeenSelect = users_last_seen_enabled() ? 'ad.last_seen_at AS admin_last_seen_at' : 'NULL AS admin_last_seen_at';

    if ($user['role'] === 'admin') {
        return db_all(
            'SELECT t.*, a.full_name AS agent_name, ' . $agentSeenSelect . ', m.message AS last_message, m.created_at AS last_message_at
             FROM chat_threads t
             JOIN users a ON a.id = t.agent_id
             LEFT JOIN chat_messages m ON m.id = (
                 SELECT cm.id FROM chat_messages cm WHERE cm.thread_id = t.id ORDER BY cm.id DESC LIMIT 1
             )
             ORDER BY COALESCE(m.created_at, t.updated_at) DESC'
        );
    }

    if ($user['role'] === 'agent') {
        return db_all(
            'SELECT t.*, ad.full_name AS admin_name, ' . $adminSeenSelect . ', m.message AS last_message, m.created_at AS last_message_at
             FROM chat_threads t
             JOIN users ad ON ad.id = t.admin_id
             LEFT JOIN chat_messages m ON m.id = (
                 SELECT cm.id FROM chat_messages cm WHERE cm.thread_id = t.id ORDER BY cm.id DESC LIMIT 1
             )
             WHERE t.agent_id = :agent_id
             ORDER BY COALESCE(m.created_at, t.updated_at) DESC',
            ['agent_id' => (int) $user['id']]
        );
    }

    return [];
}

function get_chat_thread(int $threadId): ?array
{
    $adminSeenSelect = users_last_seen_enabled() ? 'ad.last_seen_at AS admin_last_seen_at' : 'NULL AS admin_last_seen_at';
    $agentSeenSelect = users_last_seen_enabled() ? 'ag.last_seen_at AS agent_last_seen_at' : 'NULL AS agent_last_seen_at';

    return db_one(
        'SELECT t.*, ad.full_name AS admin_name, ' . $adminSeenSelect . ', ag.full_name AS agent_name, ' . $agentSeenSelect . '
         FROM chat_threads t
         JOIN users ad ON ad.id = t.admin_id
         JOIN users ag ON ag.id = t.agent_id
         WHERE t.id = :id LIMIT 1',
        ['id' => $threadId]
    );
}

function can_access_chat_thread(array $user, array $thread): bool
{
    if ($user['role'] === 'admin') {
        return (int) $thread['admin_id'] === (int) $user['id'];
    }

    if ($user['role'] === 'agent') {
        return (int) $thread['agent_id'] === (int) $user['id'];
    }

    return false;
}

function list_chat_messages(int $threadId): array
{
    return db_all(
        'SELECT m.*, u.full_name AS sender_name, u.role AS sender_role
         FROM chat_messages m
         JOIN users u ON u.id = m.sender_id
         WHERE m.thread_id = :thread_id
         ORDER BY m.created_at ASC',
        ['thread_id' => $threadId]
    );
}

function list_chat_messages_after(int $threadId, int $afterId): array
{
    return db_all(
        'SELECT m.*, u.full_name AS sender_name, u.role AS sender_role
         FROM chat_messages m
         JOIN users u ON u.id = m.sender_id
         WHERE m.thread_id = :thread_id AND m.id > :after_id
         ORDER BY m.id ASC',
        [
            'thread_id' => $threadId,
            'after_id' => max(0, $afterId),
        ]
    );
}

function touch_chat_typing(int $threadId, int $userId): void
{
    db_execute(
        'INSERT INTO chat_typing_status (thread_id, user_id, last_typed_at)
         VALUES (:thread_id, :user_id, :last_typed_at)
         ON DUPLICATE KEY UPDATE last_typed_at = VALUES(last_typed_at)',
        [
            'thread_id' => $threadId,
            'user_id' => $userId,
            'last_typed_at' => now(),
        ]
    );
}

function chat_other_user_typing(int $threadId, int $viewerId, int $withinSeconds = 6): bool
{
    $threshold = date('Y-m-d H:i:s', time() - max(1, $withinSeconds));
    $row = db_one(
        'SELECT COUNT(*) AS c
         FROM chat_typing_status
         WHERE thread_id = :thread_id
           AND user_id <> :viewer_id
           AND last_typed_at >= :threshold',
        [
            'thread_id' => $threadId,
            'viewer_id' => $viewerId,
            'threshold' => $threshold,
        ]
    );

    return (int) ($row['c'] ?? 0) > 0;
}

function store_chat_attachment(array $file, int $threadId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Chat file upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('Chat file is too large. Max 12MB.');
    }

    $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'pl', 'cgi', 'js'];
    $original = (string) ($file['name'] ?? 'chat_file.bin');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (in_array($ext, $blocked, true)) {
        throw new RuntimeException('This file type is blocked.');
    }

    ensure_upload_directory();
    $dir = upload_directory() . '/chat_files/thread_' . $threadId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original) ?: 'chat_file.bin';
    $stored = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
    $path = $dir . '/' . $stored;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('Could not save chat file.');
    }

    $mime = mime_content_type($path) ?: 'application/octet-stream';
    return [
        'path' => $path,
        'name' => $original,
        'mime' => $mime,
    ];
}

function send_chat_message(int $threadId, int $senderId, string $message = '', ?array $attachment = null): void
{
    $thread = get_chat_thread($threadId);
    if (!$thread) {
        throw new RuntimeException('Chat thread not found.');
    }

    $message = trim($message);
    $attachmentData = null;
    if ($attachment && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $attachmentData = store_chat_attachment($attachment, $threadId);
    }

    if ($message === '' && !$attachmentData) {
        throw new RuntimeException('Enter a message or attach a file.');
    }

    db_execute(
        'INSERT INTO chat_messages (thread_id, sender_id, message, attachment_path, attachment_name, attachment_mime, created_at)
         VALUES (:thread_id, :sender_id, :message, :attachment_path, :attachment_name, :attachment_mime, :created_at)',
        [
            'thread_id' => $threadId,
            'sender_id' => $senderId,
            'message' => $message !== '' ? $message : null,
            'attachment_path' => $attachmentData['path'] ?? null,
            'attachment_name' => $attachmentData['name'] ?? null,
            'attachment_mime' => $attachmentData['mime'] ?? null,
            'created_at' => now(),
        ]
    );

    db_execute('UPDATE chat_threads SET updated_at = :updated_at WHERE id = :id', ['updated_at' => now(), 'id' => $threadId]);

    $recipientId = (int) $thread['admin_id'] === $senderId ? (int) $thread['agent_id'] : (int) $thread['admin_id'];
    $sender = find_user_by_id($senderId);
    $senderLabel = '';
    if ($sender) {
        $senderLabel = trim((string) ($sender['first_name'] ?? ''));
        if ($senderLabel === '') {
            $senderLabel = name_first_token((string) ($sender['full_name'] ?? ''));
        }
    }
    if ($senderLabel === '') {
        $senderLabel = 'New message';
    }

    $preview = $message !== '' ? $message : 'Sent an attachment.';
    if (function_exists('mb_substr')) {
        $preview = mb_substr($preview, 0, 140);
    } else {
        $preview = substr($preview, 0, 140);
    }
    create_notification($recipientId, $senderLabel, $preview, url('messages', ['thread_id' => $threadId]));
}

function get_chat_message(int $messageId): ?array
{
    return db_one(
        'SELECT m.*, t.admin_id, t.agent_id
         FROM chat_messages m
         JOIN chat_threads t ON t.id = m.thread_id
         WHERE m.id = :id LIMIT 1',
        ['id' => $messageId]
    );
}

function withdrawal_minimum(): float
{
    $value = setting('withdrawal_minimum', '20000');
    return (float) ($value !== '' ? $value : '20000');
}

function withdrawal_cooldown_days(): int
{
    $days = int_setting('withdrawal_cooldown_days', 14);
    return $days > 0 ? $days : 14;
}

function agent_withdrawal_summary(int $agentId): array
{
    $totalEarned = (float) (db_one(
        'SELECT COALESCE(SUM(commission_amount), 0) AS total FROM commission_payouts WHERE agent_id = :agent_id',
        ['agent_id' => $agentId]
    )['total'] ?? 0);

    $totalWithdrawn = (float) (db_one(
        "SELECT COALESCE(SUM(amount), 0) AS total FROM withdrawal_requests WHERE agent_id = :agent_id AND status IN ('approved', 'paid')",
        ['agent_id' => $agentId]
    )['total'] ?? 0);

    $pendingWithdrawal = (float) (db_one(
        "SELECT COALESCE(SUM(amount), 0) AS total FROM withdrawal_requests WHERE agent_id = :agent_id AND status = 'pending'",
        ['agent_id' => $agentId]
    )['total'] ?? 0);

    $available = max($totalEarned - $totalWithdrawn - $pendingWithdrawal, 0);

    $lastRequest = db_one(
        'SELECT requested_at FROM withdrawal_requests WHERE agent_id = :agent_id ORDER BY requested_at DESC LIMIT 1',
        ['agent_id' => $agentId]
    );

    $cooldownDays = withdrawal_cooldown_days();
    $nextRequestAt = null;

    if ($lastRequest && !empty($lastRequest['requested_at'])) {
        $nextRequestAt = date('Y-m-d H:i:s', strtotime($lastRequest['requested_at'] . ' +' . $cooldownDays . ' days'));
    }

    $minimum = withdrawal_minimum();

    $isCooldownOver = $nextRequestAt === null || strtotime(now()) >= strtotime($nextRequestAt);
    $canRequest = $available >= $minimum && $isCooldownOver;

    return [
        'total_earned' => round($totalEarned, 2),
        'total_withdrawn' => round($totalWithdrawn, 2),
        'pending_withdrawal' => round($pendingWithdrawal, 2),
        'available_balance' => round($available, 2),
        'minimum_threshold' => round($minimum, 2),
        'cooldown_days' => $cooldownDays,
        'next_request_at' => $nextRequestAt,
        'can_request' => $canRequest,
    ];
}

function list_withdrawal_requests_for_user(array $user): array
{
    if ($user['role'] === 'agent') {
        return db_all(
            'SELECT w.*, u.full_name AS agent_name FROM withdrawal_requests w
             JOIN users u ON u.id = w.agent_id
             WHERE w.agent_id = :agent_id
             ORDER BY w.requested_at DESC',
            ['agent_id' => $user['id']]
        );
    }

    return db_all(
        'SELECT w.*, u.full_name AS agent_name FROM withdrawal_requests w
         JOIN users u ON u.id = w.agent_id
         ORDER BY w.requested_at DESC'
    );
}

function create_withdrawal_request(int $agentId, float $amount, string $note = ''): void
{
    $summary = agent_withdrawal_summary($agentId);

    if ($amount <= 0) {
        throw new RuntimeException('Withdrawal amount must be greater than zero.');
    }

    if ($amount < $summary['minimum_threshold']) {
        throw new RuntimeException('Amount is below minimum threshold of ' . money($summary['minimum_threshold']) . '.');
    }

    if ($amount > $summary['available_balance']) {
        throw new RuntimeException('Amount exceeds available balance.');
    }

    if (!$summary['can_request']) {
        if ($summary['next_request_at']) {
            throw new RuntimeException('You can request withdrawal after ' . $summary['next_request_at'] . '.');
        }

        throw new RuntimeException('Withdrawal request is currently not available.');
    }

    db_execute(
        'INSERT INTO withdrawal_requests (agent_id, amount, currency, note, status, requested_at)
         VALUES (:agent_id, :amount, :currency, :note, :status, :requested_at)',
        [
            'agent_id' => $agentId,
            'amount' => $amount,
            'currency' => default_currency(),
            'note' => trim($note),
            'status' => 'pending',
            'requested_at' => now(),
        ]
    );

    notify_roles(['admin', 'finance'], 'New withdrawal request', 'An agent requested a commission withdrawal.', url('withdrawals'));
}

function update_withdrawal_request_status(int $requestId, string $status, int $reviewedBy, string $adminNote = ''): void
{
    $allowed = ['pending', 'approved', 'paid', 'rejected'];

    if (!in_array($status, $allowed, true)) {
        throw new RuntimeException('Invalid withdrawal status.');
    }

    $request = db_one('SELECT * FROM withdrawal_requests WHERE id = :id LIMIT 1', ['id' => $requestId]);
    if (!$request) {
        throw new RuntimeException('Withdrawal request not found.');
    }

    db_execute(
        "UPDATE withdrawal_requests
         SET status = :status, admin_note = :admin_note, reviewed_at = :reviewed_at,
             paid_at = CASE WHEN :status2 = 'paid' THEN :paid_at ELSE paid_at END
         WHERE id = :id",
        [
            'status' => $status,
            'admin_note' => trim($adminNote),
            'reviewed_at' => now(),
            'status2' => $status,
            'paid_at' => now(),
            'id' => $requestId,
        ]
    );

    create_notification((int) $request['agent_id'], 'Withdrawal request update', 'Your withdrawal request status is now ' . $status . '.', url('withdrawals'));
    notify_users([$reviewedBy], 'Withdrawal action saved', 'Withdrawal request #' . $requestId . ' was updated.', url('withdrawals'));
}

function change_user_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
{
    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User account not found.');
    }

    $mustChangeOnLogin = (int) ($user['must_change_password'] ?? 0) === 1;

    if (!$mustChangeOnLogin && !password_verify($currentPassword, (string) $user['password_hash'])) {
        throw new RuntimeException('Current password is incorrect.');
    }

    if (strlen($newPassword) < 6) {
        throw new RuntimeException('New password must be at least 6 characters.');
    }

    if ($newPassword !== $confirmPassword) {
        throw new RuntimeException('New password and confirmation do not match.');
    }

    if (password_verify($newPassword, (string) $user['password_hash'])) {
        throw new RuntimeException(
            $mustChangeOnLogin
                ? 'New password must be different from your temporary password.'
                : 'New password must be different from current password.'
        );
    }

    db_execute(
        'UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id',
        [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]
    );

    // Force OTP challenge again on all devices after password change.
    db_execute('DELETE FROM trusted_devices WHERE user_id = :user_id', ['user_id' => $userId]);

    create_notification($userId, 'Password changed', 'Your account password was changed successfully.', url('profile'));
}

function support_ticket_code(): string
{
    return 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function store_support_attachment(array $file, int $ticketId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Support attachment upload failed.');
    }

    if ((int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        throw new RuntimeException('Attachment too large. Max 12MB.');
    }

    $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'pl', 'cgi', 'js'];
    $original = (string) ($file['name'] ?? 'ticket_attachment.bin');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (in_array($ext, $blocked, true)) {
        throw new RuntimeException('This file type is blocked.');
    }

    ensure_upload_directory();
    $dir = upload_directory() . '/support_tickets/ticket_' . $ticketId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original) ?: 'support_attachment.bin';
    $stored = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
    $path = $dir . '/' . $stored;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('Could not save support attachment.');
    }

    $mime = mime_content_type($path) ?: 'application/octet-stream';
    return [
        'path' => $path,
        'name' => $original,
        'mime' => $mime,
    ];
}

function get_support_ticket(int $ticketId): ?array
{
    return db_one(
        'SELECT t.*, c.full_name AS client_name, c.email AS client_email
         FROM support_tickets t
         JOIN users c ON c.id = t.client_id
         WHERE t.id = :id LIMIT 1',
        ['id' => $ticketId]
    );
}

function can_access_support_ticket(array $user, array $ticket): bool
{
    if (in_array((string) ($user['role'] ?? ''), ['admin', 'finance'], true)) {
        return true;
    }

    if (($user['role'] ?? '') === 'client') {
        return (int) $ticket['client_id'] === (int) $user['id'];
    }

    return false;
}

function create_support_ticket(int $clientId, string $subject, string $message, string $priority = 'normal', string $category = 'general', ?array $attachment = null): int
{
    $subject = trim($subject);
    $message = trim($message);
    $category = trim($category) !== '' ? trim($category) : 'general';
    $priority = strtolower(trim($priority));

    if ($subject === '') {
        throw new RuntimeException('Ticket subject is required.');
    }

    if ($message === '') {
        throw new RuntimeException('Ticket message is required.');
    }

    if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
        $priority = 'normal';
    }

    db_execute(
        'INSERT INTO support_tickets (ticket_code, client_id, subject, category, priority, status, created_at, updated_at, last_reply_at)
         VALUES (:ticket_code, :client_id, :subject, :category, :priority, :status, :created_at, :updated_at, :last_reply_at)',
        [
            'ticket_code' => support_ticket_code(),
            'client_id' => $clientId,
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
            'last_reply_at' => now(),
        ]
    );

    $ticketId = db_last_id();
    $attachmentData = null;
    if ($attachment && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $attachmentData = store_support_attachment($attachment, $ticketId);
    }

    db_execute(
        'INSERT INTO support_ticket_messages (ticket_id, sender_id, message, attachment_path, attachment_name, attachment_mime, created_at)
         VALUES (:ticket_id, :sender_id, :message, :attachment_path, :attachment_name, :attachment_mime, :created_at)',
        [
            'ticket_id' => $ticketId,
            'sender_id' => $clientId,
            'message' => $message,
            'attachment_path' => $attachmentData['path'] ?? null,
            'attachment_name' => $attachmentData['name'] ?? null,
            'attachment_mime' => $attachmentData['mime'] ?? null,
            'created_at' => now(),
        ]
    );

    notify_roles(
        ['admin', 'finance'],
        'New support ticket',
        'A client opened support ticket #' . $ticketId . ': ' . $subject,
        url('support', ['ticket_id' => $ticketId])
    );

    return $ticketId;
}

function add_support_ticket_message(int $ticketId, int $senderId, string $message = '', ?array $attachment = null): void
{
    $ticket = get_support_ticket($ticketId);
    if (!$ticket) {
        throw new RuntimeException('Support ticket not found.');
    }

    $sender = find_user_by_id($senderId);
    if (!$sender) {
        throw new RuntimeException('Sender account not found.');
    }

    if (!can_access_support_ticket($sender, $ticket)) {
        throw new RuntimeException('You do not have access to this support ticket.');
    }

    $currentStatus = (string) ($ticket['status'] ?? 'open');
    if ($sender['role'] === 'client' && in_array($currentStatus, ['resolved', 'closed'], true)) {
        throw new RuntimeException('This ticket is closed. Wait for support to reopen it before replying.');
    }

    $message = trim($message);
    $attachmentData = null;
    if ($attachment && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $attachmentData = store_support_attachment($attachment, $ticketId);
    }

    if ($message === '' && !$attachmentData) {
        throw new RuntimeException('Enter a message or attach a file.');
    }

    db_execute(
        'INSERT INTO support_ticket_messages (ticket_id, sender_id, message, attachment_path, attachment_name, attachment_mime, created_at)
         VALUES (:ticket_id, :sender_id, :message, :attachment_path, :attachment_name, :attachment_mime, :created_at)',
        [
            'ticket_id' => $ticketId,
            'sender_id' => $senderId,
            'message' => $message !== '' ? $message : null,
            'attachment_path' => $attachmentData['path'] ?? null,
            'attachment_name' => $attachmentData['name'] ?? null,
            'attachment_mime' => $attachmentData['mime'] ?? null,
            'created_at' => now(),
        ]
    );

    $newStatus = $currentStatus;
    if (in_array($sender['role'], ['admin', 'finance'], true) && $newStatus === 'open') {
        $newStatus = 'in_progress';
    }

    db_execute(
        'UPDATE support_tickets
         SET status = :status, updated_at = :updated_at, last_reply_at = :last_reply_at
         WHERE id = :id',
        [
            'status' => $newStatus,
            'updated_at' => now(),
            'last_reply_at' => now(),
            'id' => $ticketId,
        ]
    );

    if ($sender['role'] === 'client') {
        notify_roles(
            ['admin', 'finance'],
            'Support reply from client',
            'New reply on support ticket ' . (string) $ticket['ticket_code'] . '.',
            url('support', ['ticket_id' => $ticketId])
        );
    } else {
        create_notification(
            (int) $ticket['client_id'],
            'Support ticket update',
            'Support responded to your ticket ' . (string) $ticket['ticket_code'] . '.',
            url('support', ['ticket_id' => $ticketId])
        );
    }
}

function update_support_ticket_status(int $ticketId, string $status, int $updatedBy): void
{
    $ticket = get_support_ticket($ticketId);
    if (!$ticket) {
        throw new RuntimeException('Support ticket not found.');
    }

    $status = strtolower(trim($status));
    if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
        throw new RuntimeException('Invalid support ticket status.');
    }

    db_execute(
        'UPDATE support_tickets SET status = :status, updated_at = :updated_at WHERE id = :id',
        [
            'status' => $status,
            'updated_at' => now(),
            'id' => $ticketId,
        ]
    );

    notify_users([$updatedBy], 'Support ticket updated', 'Support ticket ' . (string) $ticket['ticket_code'] . ' status changed to ' . $status . '.', url('support', ['ticket_id' => $ticketId]));
    create_notification((int) $ticket['client_id'], 'Support ticket status', 'Your support ticket ' . (string) $ticket['ticket_code'] . ' is now ' . $status . '.', url('support', ['ticket_id' => $ticketId]));
}

function list_support_tickets_for_user(array $user, array $filters = []): array
{
    $role = (string) ($user['role'] ?? '');
    if (!in_array($role, ['client', 'admin', 'finance'], true)) {
        return [];
    }

    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $priority = strtolower(trim((string) ($filters['priority'] ?? '')));
    $sort = strtolower(trim((string) ($filters['sort'] ?? 'updated')));

    if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
        $status = '';
    }
    if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
        $priority = '';
    }
    if (!in_array($sort, ['updated', 'newest', 'oldest', 'priority'], true)) {
        $sort = 'updated';
    }

    $sortSql = [
        'updated' => 't.last_reply_at DESC, t.updated_at DESC',
        'newest' => 't.created_at DESC',
        'oldest' => 't.created_at ASC',
        'priority' => "FIELD(t.priority, 'urgent', 'high', 'normal', 'low') ASC, t.updated_at DESC",
    ];

    $sql = 'SELECT t.*, c.full_name AS client_name, c.email AS client_email
            FROM support_tickets t
            JOIN users c ON c.id = t.client_id
            WHERE 1=1';
    $params = [];

    if ($role === 'client') {
        $sql .= ' AND t.client_id = :client_id';
        $params['client_id'] = (int) $user['id'];
    }

    if ($status !== '') {
        $sql .= ' AND t.status = :status';
        $params['status'] = $status;
    }

    if ($priority !== '') {
        $sql .= ' AND t.priority = :priority';
        $params['priority'] = $priority;
    }

    $sql .= ' ORDER BY ' . $sortSql[$sort];
    return db_all($sql, $params);
}

function list_support_ticket_messages(int $ticketId): array
{
    return db_all(
        'SELECT m.*, u.full_name AS sender_name, u.role AS sender_role
         FROM support_ticket_messages m
         JOIN users u ON u.id = m.sender_id
         WHERE m.ticket_id = :ticket_id
         ORDER BY m.created_at ASC, m.id ASC',
        ['ticket_id' => $ticketId]
    );
}

function get_support_ticket_message(int $messageId): ?array
{
    return db_one(
        'SELECT m.*, t.client_id
         FROM support_ticket_messages m
         JOIN support_tickets t ON t.id = m.ticket_id
         WHERE m.id = :id LIMIT 1',
        ['id' => $messageId]
    );
}

function can_access_community(array $user): bool
{
    return in_array((string) ($user['role'] ?? ''), ['admin', 'agent'], true);
}

function get_community_post(int $postId): ?array
{
    return db_one(
        'SELECT p.*, u.full_name AS author_name, u.role AS author_role
         FROM community_posts p
         JOIN users u ON u.id = p.author_id
         WHERE p.id = :id LIMIT 1',
        ['id' => $postId]
    );
}

function create_community_post(int $authorId, string $title, string $body): int
{
    $title = trim($title);
    $body = trim($body);

    if ($body === '') {
        throw new RuntimeException('Post message is required.');
    }

    if ($title === '') {
        $title = substr($body, 0, 90);
        if (strlen($body) > 90) {
            $title .= '...';
        }
    }

    db_execute(
        'INSERT INTO community_posts (author_id, title, body, is_active, created_at, updated_at)
         VALUES (:author_id, :title, :body, :is_active, :created_at, :updated_at)',
        [
            'author_id' => $authorId,
            'title' => $title,
            'body' => $body,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $postId = db_last_id();
    $author = find_user_by_id($authorId);
    $authorName = $author ? (string) $author['full_name'] : 'An agent';
    notify_roles(['admin', 'agent'], 'New community post', $authorName . ' shared a new community post.', url('community'));

    return $postId;
}

function list_community_posts(array $viewer): array
{
    $isAdmin = (string) ($viewer['role'] ?? '') === 'admin';

    return db_all(
        'SELECT p.*, u.full_name AS author_name, u.role AS author_role,
                (SELECT COUNT(*) FROM community_post_likes pl WHERE pl.post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM community_comments cm WHERE cm.post_id = p.id) AS comment_count,
                (SELECT COUNT(*) FROM community_post_likes pl2 WHERE pl2.post_id = p.id AND pl2.user_id = :viewer_id) AS viewer_liked
         FROM community_posts p
         JOIN users u ON u.id = p.author_id
         WHERE p.is_active = 1 OR :is_admin = 1
         ORDER BY p.updated_at DESC, p.id DESC',
        [
            'viewer_id' => (int) $viewer['id'],
            'is_admin' => $isAdmin ? 1 : 0,
        ]
    );
}

function list_community_comments_for_posts(array $postIds): array
{
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
    if (!$postIds) {
        return [];
    }

    $placeholder = implode(',', array_fill(0, count($postIds), '?'));
    $rows = db_query(
        'SELECT c.*, u.full_name AS user_name, u.role AS user_role
         FROM community_comments c
         JOIN users u ON u.id = c.user_id
         WHERE c.post_id IN (' . $placeholder . ')
         ORDER BY c.created_at ASC, c.id ASC',
        $postIds
    )->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $postId = (int) $row['post_id'];
        if (!isset($grouped[$postId])) {
            $grouped[$postId] = [];
        }
        $grouped[$postId][] = $row;
    }

    return $grouped;
}

function add_community_post_comment(int $postId, int $userId, string $comment): void
{
    $post = get_community_post($postId);
    if (!$post) {
        throw new RuntimeException('Community post not found.');
    }

    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User account not found.');
    }

    if ((int) $post['is_active'] !== 1 && (string) ($user['role'] ?? '') !== 'admin') {
        throw new RuntimeException('This community post is not active.');
    }

    $comment = trim($comment);
    if ($comment === '') {
        throw new RuntimeException('Comment cannot be empty.');
    }

    db_execute(
        'INSERT INTO community_comments (post_id, user_id, comment, created_at)
         VALUES (:post_id, :user_id, :comment, :created_at)',
        [
            'post_id' => $postId,
            'user_id' => $userId,
            'comment' => $comment,
            'created_at' => now(),
        ]
    );

    db_execute('UPDATE community_posts SET updated_at = :updated_at WHERE id = :id', ['updated_at' => now(), 'id' => $postId]);

    if ((int) $post['author_id'] !== $userId) {
        create_notification((int) $post['author_id'], 'New community comment', 'Someone commented on your community post.', url('community'));
    }
}

function toggle_community_post_like(int $postId, int $userId): void
{
    $post = get_community_post($postId);
    if (!$post) {
        throw new RuntimeException('Community post not found.');
    }

    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User account not found.');
    }

    if ((int) $post['is_active'] !== 1 && (string) ($user['role'] ?? '') !== 'admin') {
        throw new RuntimeException('This community post is not active.');
    }

    $existing = db_one(
        'SELECT id FROM community_post_likes WHERE post_id = :post_id AND user_id = :user_id LIMIT 1',
        ['post_id' => $postId, 'user_id' => $userId]
    );

    if ($existing) {
        db_execute('DELETE FROM community_post_likes WHERE id = :id', ['id' => (int) $existing['id']]);
        return;
    }

    db_execute(
        'INSERT INTO community_post_likes (post_id, user_id, created_at)
         VALUES (:post_id, :user_id, :created_at)',
        [
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => now(),
        ]
    );

    if ((int) $post['author_id'] !== $userId) {
        create_notification((int) $post['author_id'], 'New community like', 'Someone liked your community post.', url('community'));
    }
}

function set_community_post_visibility(int $postId, bool $isActive, int $adminId): void
{
    $post = get_community_post($postId);
    if (!$post) {
        throw new RuntimeException('Community post not found.');
    }

    db_execute(
        'UPDATE community_posts SET is_active = :is_active, updated_at = :updated_at WHERE id = :id',
        [
            'is_active' => $isActive ? 1 : 0,
            'updated_at' => now(),
            'id' => $postId,
        ]
    );

    notify_users([$adminId], 'Community post updated', 'Post visibility was updated.', url('community'));
}

function delete_community_post(int $postId, int $adminId): void
{
    $post = get_community_post($postId);
    if (!$post) {
        throw new RuntimeException('Community post not found.');
    }

    db_execute('DELETE FROM community_posts WHERE id = :id', ['id' => $postId]);
    notify_users([$adminId], 'Community post deleted', 'Post was deleted successfully.', url('community'));
}
