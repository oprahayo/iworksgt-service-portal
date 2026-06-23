<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$lockFile = __DIR__ . '/storage/setup.lock';

function execute_sql_file(PDO $pdo, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('Cannot read database.sql file.');
    }

    $statements = [];
    $buffer = '';

    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (str_ends_with(rtrim($line), ';')) {
            $statements[] = $buffer;
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    // MySQL auto-commits many DDL statements (CREATE/ALTER), so wrapping
    // the installer SQL in a transaction causes "There is no active transaction"
    // on some cPanel environments.
    foreach ($statements as $statement) {
        if (trim($statement) === '') {
            continue;
        }

        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Fallback for legacy schema while seeding updated inserts.
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                ensure_schema_upgrades($pdo);
                $pdo->exec($statement);
                continue;
            }

            throw $e;
        }
    }
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute(['table_name' => $table]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['c'] ?? 0) > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name');
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['c'] ?? 0) > 0;
}

function column_type(PDO $pdo, string $table, string $column): ?string
{
    $stmt = $pdo->prepare('SELECT COLUMN_TYPE AS column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name LIMIT 1');
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !isset($row['column_type'])) {
        return null;
    }

    return (string) $row['column_type'];
}

function ensure_schema_upgrades(PDO $pdo): void
{
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'email_verified_at')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER status');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'must_change_password')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER email_verified_at');
    }

    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'account_state')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN account_state ENUM('active', 'suspended', 'blocked') NOT NULL DEFAULT 'active' AFTER status");
    }

    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'deleted_at')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL AFTER account_state');
    }

    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'first_name')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL AFTER full_name');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'last_name')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'gender')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN gender VARCHAR(30) NULL AFTER email');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'date_of_birth')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER gender');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'country')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN country VARCHAR(90) NULL AFTER phone');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'state_region')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN state_region VARCHAR(90) NULL AFTER country');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'city')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN city VARCHAR(90) NULL AFTER state_region');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'address_line')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN address_line VARCHAR(190) NULL AFTER city');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'occupation')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN occupation VARCHAR(120) NULL AFTER address_line');
    }
    if (table_exists($pdo, 'users') && !column_exists($pdo, 'users', 'last_seen_at')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN last_seen_at DATETIME NULL AFTER bank_currency');
    }

    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'currency')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'NGN' AFTER price");
    }

    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'tax_percent')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER commission_percent");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'category')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'general' AFTER tax_percent");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'short_description')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN short_description VARCHAR(255) NULL AFTER description");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'featured_bullets')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN featured_bullets LONGTEXT NULL AFTER short_description");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'featured_image_id')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN featured_image_id INT UNSIGNED NULL AFTER featured_bullets");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'is_public')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
    }
    if (table_exists($pdo, 'services') && !column_exists($pdo, 'services', 'is_featured')) {
        $pdo->exec("ALTER TABLE services ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_public");
    }

    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'subtotal_amount')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER status");
    }

    if (table_exists($pdo, 'jobs')) {
        $statusType = column_type($pdo, 'jobs', 'status');
        if ($statusType !== null && stripos($statusType, "'revision_requested'") === false) {
            $pdo->exec("ALTER TABLE jobs MODIFY COLUMN status ENUM('pending', 'in_progress', 'waiting_client', 'revision_requested', 'delivered', 'closed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'tax_percent')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER subtotal_amount");
    }

    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'tax_amount')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tax_percent");
    }

    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'currency')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'NGN' AFTER amount");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'pay_currency')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN pay_currency VARCHAR(10) NULL AFTER currency");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'pay_method')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'fx_rate_used')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'crypto_asset')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'crypto_rate_used')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'crypto_amount_due')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'crypto_network')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'rate_locked_at')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'payment_group')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN payment_group VARCHAR(60) NULL AFTER invoice_number");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'delivered_by')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN delivered_by INT UNSIGNED NULL AFTER delivered_at");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'accepted_by')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN accepted_by INT UNSIGNED NULL AFTER delivered_by");
    }
    if (table_exists($pdo, 'jobs') && !column_exists($pdo, 'jobs', 'accepted_at')) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN accepted_at DATETIME NULL AFTER accepted_by");
    }
    if (table_exists($pdo, 'jobs') && column_exists($pdo, 'jobs', 'pay_currency')) {
        $pdo->exec("UPDATE jobs SET pay_currency = currency WHERE pay_currency IS NULL OR pay_currency = ''");
    }
    if (table_exists($pdo, 'jobs') && column_exists($pdo, 'jobs', 'pay_method')) {
        $pdo->exec("UPDATE jobs SET pay_method = 'card' WHERE pay_method IS NULL OR pay_method = ''");
    }

    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'subtotal_amount')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER client_id");
    }

    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'tax_percent')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER subtotal_amount");
    }

    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'tax_amount')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tax_percent");
    }

    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'currency')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'NGN' AFTER amount");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'pay_currency')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN pay_currency VARCHAR(10) NULL AFTER currency");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'pay_method')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'fx_rate_used')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'crypto_asset')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'crypto_rate_used')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'crypto_amount_due')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'crypto_network')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'rate_locked_at')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network");
    }
    if (table_exists($pdo, 'invoices') && !column_exists($pdo, 'invoices', 'payment_group')) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN payment_group VARCHAR(60) NULL AFTER job_id");
    }
    if (table_exists($pdo, 'invoices') && column_exists($pdo, 'invoices', 'pay_currency')) {
        $pdo->exec("UPDATE invoices SET pay_currency = currency WHERE pay_currency IS NULL OR pay_currency = ''");
    }
    if (table_exists($pdo, 'invoices') && column_exists($pdo, 'invoices', 'pay_method')) {
        $pdo->exec("UPDATE invoices SET pay_method = 'card' WHERE pay_method IS NULL OR pay_method = ''");
    }

    if (table_exists($pdo, 'job_comments') && !column_exists($pdo, 'job_comments', 'comment_type')) {
        $pdo->exec("ALTER TABLE job_comments ADD COLUMN comment_type ENUM('user', 'system') NOT NULL DEFAULT 'user' AFTER user_id");
    }

    if (table_exists($pdo, 'payment_methods')) {
        if (!column_exists($pdo, 'payment_methods', 'public_key')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN public_key TEXT NULL AFTER details');
        }
        if (!column_exists($pdo, 'payment_methods', 'secret_key')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN secret_key TEXT NULL AFTER public_key');
        }
        if (!column_exists($pdo, 'payment_methods', 'private_key')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN private_key TEXT NULL AFTER secret_key');
        }
        if (!column_exists($pdo, 'payment_methods', 'requires_proof')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN requires_proof TINYINT(1) NOT NULL DEFAULT 1 AFTER private_key');
        }
        if (!column_exists($pdo, 'payment_methods', 'callback_url')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN callback_url VARCHAR(255) NULL AFTER private_key');
        }
        if (!column_exists($pdo, 'payment_methods', 'webhook_url')) {
            $pdo->exec('ALTER TABLE payment_methods ADD COLUMN webhook_url VARCHAR(255) NULL AFTER callback_url');
        }
    }

    if (table_exists($pdo, 'job_payments')) {
        if (!column_exists($pdo, 'job_payments', 'pay_currency')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN pay_currency VARCHAR(10) NULL AFTER amount');
        }
        if (!column_exists($pdo, 'job_payments', 'pay_method')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN pay_method VARCHAR(20) NULL AFTER pay_currency');
        }
        if (!column_exists($pdo, 'job_payments', 'fx_rate_used')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN fx_rate_used DECIMAL(16,6) NULL AFTER pay_method');
        }
        if (!column_exists($pdo, 'job_payments', 'crypto_asset')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN crypto_asset VARCHAR(12) NULL AFTER fx_rate_used');
        }
        if (!column_exists($pdo, 'job_payments', 'crypto_rate_used')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN crypto_rate_used DECIMAL(24,10) NULL AFTER crypto_asset');
        }
        if (!column_exists($pdo, 'job_payments', 'crypto_amount_due')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN crypto_amount_due DECIMAL(24,8) NULL AFTER crypto_rate_used');
        }
        if (!column_exists($pdo, 'job_payments', 'crypto_network')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN crypto_network VARCHAR(24) NULL AFTER crypto_amount_due');
        }
        if (!column_exists($pdo, 'job_payments', 'rate_locked_at')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN rate_locked_at DATETIME NULL AFTER crypto_network');
        }
        if (!column_exists($pdo, 'job_payments', 'gateway_provider')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN gateway_provider VARCHAR(40) NULL AFTER amount');
        }
        if (!column_exists($pdo, 'job_payments', 'gateway_transaction_id')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN gateway_transaction_id VARCHAR(140) NULL AFTER gateway_provider');
        }
        if (!column_exists($pdo, 'job_payments', 'gateway_session_id')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN gateway_session_id VARCHAR(190) NULL AFTER gateway_transaction_id');
        }
        if (!column_exists($pdo, 'job_payments', 'gateway_payload')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN gateway_payload LONGTEXT NULL AFTER gateway_session_id');
        }
        if (!column_exists($pdo, 'job_payments', 'proof_path')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN proof_path VARCHAR(500) NULL AFTER amount');
        }
        if (!column_exists($pdo, 'job_payments', 'rejection_reason')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN rejection_reason TEXT NULL AFTER note');
        }
        if (!column_exists($pdo, 'job_payments', 'reviewed_by')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER status');
        }
        if (!column_exists($pdo, 'job_payments', 'reviewed_at')) {
            $pdo->exec('ALTER TABLE job_payments ADD COLUMN reviewed_at DATETIME NULL AFTER created_at');
        }
    }

    if (table_exists($pdo, 'banners') && !column_exists($pdo, 'banners', 'mobile_image_path')) {
        $pdo->exec('ALTER TABLE banners ADD COLUMN mobile_image_path VARCHAR(255) NULL AFTER image_path');
    }

    if (table_exists($pdo, 'announcements')) {
        if (!column_exists($pdo, 'announcements', 'message_html')) {
            $pdo->exec('ALTER TABLE announcements ADD COLUMN message_html LONGTEXT NULL AFTER message');
        }
        if (!column_exists($pdo, 'announcements', 'attachment_path')) {
            $pdo->exec('ALTER TABLE announcements ADD COLUMN attachment_path VARCHAR(500) NULL AFTER message_html');
        }
        if (!column_exists($pdo, 'announcements', 'audience')) {
            $pdo->exec("ALTER TABLE announcements ADD COLUMN audience ENUM('all', 'client', 'agent') NOT NULL DEFAULT 'all' AFTER attachment_path");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS otp_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            purpose ENUM('login', 'verify_email') NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            consumed_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
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

    $pdo->exec(
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

    $pdo->exec(
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

    $pdo->exec(
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

    $pdo->exec(
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

    // Backward compatibility for old builds.
    $pdo->exec(
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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS website_leads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(40) NULL,
            company_name VARCHAR(190) NULL,
            message TEXT NOT NULL,
            page_slug VARCHAR(120) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_website_leads_status_created (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS trusted_devices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            user_agent VARCHAR(255) NULL,
            ip_address VARCHAR(80) NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            CONSTRAINT fk_trusted_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            message TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            require_ack TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_announcement_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS announcement_reads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            UNIQUE KEY uniq_announcement_user (announcement_id, user_id),
            CONSTRAINT fk_announcement_reads_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
            CONSTRAINT fk_announcement_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_threads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL,
            agent_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_admin_agent_chat (admin_id, agent_id),
            CONSTRAINT fk_chat_thread_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_chat_thread_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT NULL,
            attachment_path VARCHAR(500) NULL,
            attachment_name VARCHAR(255) NULL,
            attachment_mime VARCHAR(180) NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_chat_message_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
            CONSTRAINT fk_chat_message_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_typing_status (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            last_typed_at DATETIME NOT NULL,
            UNIQUE KEY uniq_chat_typing (thread_id, user_id),
            CONSTRAINT fk_chat_typing_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
            CONSTRAINT fk_chat_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_code VARCHAR(40) NOT NULL UNIQUE,
            client_id INT UNSIGNED NOT NULL,
            subject VARCHAR(220) NOT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'general',
            priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
            status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            last_reply_at DATETIME NOT NULL,
            CONSTRAINT fk_support_ticket_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS support_ticket_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT NULL,
            attachment_path VARCHAR(500) NULL,
            attachment_name VARCHAR(255) NULL,
            attachment_mime VARCHAR(180) NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_support_message_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            CONSTRAINT fk_support_message_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS community_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id INT UNSIGNED NOT NULL,
            title VARCHAR(220) NOT NULL,
            body LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_community_post_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS community_comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_community_comment_post FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
            CONSTRAINT fk_community_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS community_post_likes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_community_post_like (post_id, user_id),
            CONSTRAINT fk_community_like_post FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
            CONSTRAINT fk_community_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS intake_forms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            description TEXT NULL,
            schema_json LONGTEXT NOT NULL,
            status ENUM('active', 'draft', 'archived') NOT NULL DEFAULT 'draft',
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_intake_form_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_intake_form_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS service_intake_forms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            service_id INT UNSIGNED NOT NULL,
            intake_form_id INT UNSIGNED NOT NULL,
            is_required TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_service_intake_form (service_id, intake_form_id),
            CONSTRAINT fk_service_intake_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            CONSTRAINT fk_service_intake_form FOREIGN KEY (intake_form_id) REFERENCES intake_forms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS intake_submissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            intake_form_id INT UNSIGNED NOT NULL,
            service_id INT UNSIGNED NOT NULL,
            job_id INT UNSIGNED NOT NULL,
            client_user_id INT UNSIGNED NOT NULL,
            assigned_agent_user_id INT UNSIGNED NULL,
            form_version_hash VARCHAR(80) NOT NULL,
            schema_snapshot_json LONGTEXT NOT NULL,
            data_json LONGTEXT NULL,
            files_json LONGTEXT NULL,
            status ENUM('draft', 'submitted', 'update_requested') NOT NULL DEFAULT 'draft',
            update_request_note TEXT NULL,
            submitted_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_intake_submission_job_form (job_id, intake_form_id),
            CONSTRAINT fk_intake_submission_form FOREIGN KEY (intake_form_id) REFERENCES intake_forms(id) ON DELETE RESTRICT,
            CONSTRAINT fk_intake_submission_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
            CONSTRAINT fk_intake_submission_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            CONSTRAINT fk_intake_submission_client FOREIGN KEY (client_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_intake_submission_agent FOREIGN KEY (assigned_agent_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS audit_log_intake_submission_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            submission_id INT UNSIGNED NOT NULL,
            actor_user_id INT UNSIGNED NULL,
            event_type VARCHAR(80) NOT NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_intake_audit_submission FOREIGN KEY (submission_id) REFERENCES intake_submissions(id) ON DELETE CASCADE,
            CONSTRAINT fk_intake_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
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

    $pdo->exec(
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
}

$title = 'Portal Setup - ' . app_name();
$error = null;
$success = null;

if (file_exists($lockFile)) {
    $success = 'Setup already completed. Delete storage/setup.lock only if you intentionally need to rerun setup.';
}

if (method_is('POST') && !file_exists($lockFile)) {
    require_csrf();

    try {
        if (!is_configured()) {
            throw new RuntimeException('Edit app/config.php first: set DB host/name/user/pass and SSO secret.');
        }

        // Upgrade legacy schema first (old installs may miss new columns like `currency`).
        ensure_schema_upgrades(db());

        execute_sql_file(db(), __DIR__ . '/database.sql');

        // Run again in case new tables were created from older snapshots.
        ensure_schema_upgrades(db());

        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminName = trim($_POST['admin_name'] ?? 'Portal Admin');

        if ($adminEmail === '' || strlen($adminPassword) < 6) {
            throw new RuntimeException('Provide admin email and password (min 6 chars).');
        }

        $existingAdmin = find_user_by_email($adminEmail);
        if (!$existingAdmin) {
            create_user([
                'full_name' => $adminName,
                'email' => $adminEmail,
                'phone' => trim($_POST['admin_phone'] ?? ''),
                'password' => $adminPassword,
                'role' => 'admin',
                'email_verified' => true,
            ]);
        }

        if (!is_dir(__DIR__ . '/storage')) {
            mkdir(__DIR__ . '/storage', 0775, true);
        }

        ensure_public_upload_directory();

        file_put_contents($lockFile, 'Setup complete at ' . now());

        $success = 'Setup complete. You can now login.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

require __DIR__ . '/views/partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="brand-hero mb-3">
      <small class="kicker text-white-50">First Time Setup</small>
      <h1 class="h4 mt-2 mb-1"><?= e(app_name()) ?> Installer</h1>
      <p class="mb-0 text-white-50">This step imports tables, seeds your services catalog, and creates the first admin user.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= e($success) ?></div>
      <a class="btn btn-primary" href="<?= e(url('login')) ?>">Go to login</a>
    <?php endif; ?>

    <?php if (!file_exists($lockFile)): ?>
    <div class="card">
      <div class="card-body">
        <?php if (!is_configured()): ?>
          <div class="alert alert-warning">Edit <code>app/config.php</code> first. Replace database values and SSO secret, then refresh.</div>
        <?php endif; ?>

        <h2 class="h5">Create initial admin account</h2>
        <form method="post" action="setup.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Admin full name</label><input class="form-control" name="admin_name" required></div>
            <div class="col-md-6"><label class="form-label">Admin phone</label><input class="form-control" name="admin_phone"></div>
            <div class="col-md-6"><label class="form-label">Admin email</label><input class="form-control" name="admin_email" type="email" required></div>
            <div class="col-md-6"><label class="form-label">Admin password</label><input class="form-control" name="admin_password" type="password" minlength="6" required></div>
          </div>
          <button class="btn btn-primary mt-3" type="submit" <?= !is_configured() ? 'disabled' : '' ?>>Run setup</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/views/partials/footer.php'; ?>
