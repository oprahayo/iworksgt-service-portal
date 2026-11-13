CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    gender VARCHAR(30) NULL,
    date_of_birth DATE NULL,
    phone VARCHAR(40) NULL,
    country VARCHAR(90) NULL,
    state_region VARCHAR(90) NULL,
    city VARCHAR(90) NULL,
    address_line VARCHAR(190) NULL,
    occupation VARCHAR(120) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'client', 'finance', 'author') NOT NULL DEFAULT 'client',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    account_state ENUM('active', 'suspended', 'blocked') NOT NULL DEFAULT 'active',
    deleted_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    bank_name VARCHAR(120) NULL,
    bank_account_name VARCHAR(140) NULL,
    bank_account_number VARCHAR(80) NULL,
    bank_swift VARCHAR(40) NULL,
    bank_currency VARCHAR(12) NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    short_description VARCHAR(255) NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    category VARCHAR(100) NOT NULL DEFAULT 'general',
    featured_bullets LONGTEXT NULL,
    featured_image_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_code VARCHAR(40) NOT NULL UNIQUE,
    client_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'in_progress', 'waiting_client', 'revision_requested', 'delivered', 'closed', 'cancelled') NOT NULL DEFAULT 'pending',
    subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    pay_currency VARCHAR(10) NULL,
    pay_method VARCHAR(20) NULL,
    fx_rate_used DECIMAL(16,6) NULL,
    crypto_asset VARCHAR(12) NULL,
    crypto_rate_used DECIMAL(24,10) NULL,
    crypto_amount_due DECIMAL(24,8) NULL,
    crypto_network VARCHAR(24) NULL,
    rate_locked_at DATETIME NULL,
    commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid', 'paid', 'partial', 'refunded') NOT NULL DEFAULT 'unpaid',
    payment_reference VARCHAR(150) NULL,
    invoice_number VARCHAR(50) NOT NULL,
    payment_group VARCHAR(60) NULL,
    delivered_at DATETIME NULL,
    delivered_by INT UNSIGNED NULL,
    accepted_by INT UNSIGNED NULL,
    accepted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_jobs_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_jobs_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    CONSTRAINT fk_jobs_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_jobs_delivered_by FOREIGN KEY (delivered_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_jobs_accepted_by FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    feedback TEXT NULL,
    display_name VARCHAR(100) NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_job_review_job (job_id),
    KEY idx_job_review_public (is_public, created_at),
    CONSTRAINT fk_job_review_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_review_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    job_id INT UNSIGNED NOT NULL,
    payment_group VARCHAR(60) NULL,
    client_id INT UNSIGNED NOT NULL,
    subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    pay_currency VARCHAR(10) NULL,
    pay_method VARCHAR(20) NULL,
    fx_rate_used DECIMAL(16,6) NULL,
    crypto_asset VARCHAR(12) NULL,
    crypto_rate_used DECIMAL(24,10) NULL,
    crypto_amount_due DECIMAL(24,8) NULL,
    crypto_network VARCHAR(24) NULL,
    rate_locked_at DATETIME NULL,
    status ENUM('draft', 'issued', 'paid', 'void') NOT NULL DEFAULT 'issued',
    issued_at DATETIME NULL,
    due_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_invoices_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment_type ENUM('user', 'system') NOT NULL DEFAULT 'user',
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_comments_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intake_forms (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_intake_forms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    intake_form_id INT UNSIGNED NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_service_intake_form (service_id, intake_form_id),
    CONSTRAINT fk_service_intake_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_service_intake_form FOREIGN KEY (intake_form_id) REFERENCES intake_forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intake_submissions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log_intake_submission_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_intake_audit_submission FOREIGN KEY (submission_id) REFERENCES intake_submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_intake_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    mime_type VARCHAR(180) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_files_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_status_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_status_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS commission_payouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL UNIQUE,
    commission_amount DECIMAL(12,2) NOT NULL,
    status ENUM('accrued', 'approved', 'paid') NOT NULL DEFAULT 'accrued',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_payout_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    method_code VARCHAR(60) NOT NULL UNIQUE,
    display_name VARCHAR(140) NOT NULL,
    details TEXT NULL,
    public_key TEXT NULL,
    secret_key TEXT NULL,
    private_key TEXT NULL,
    callback_url VARCHAR(255) NULL,
    webhook_url VARCHAR(255) NULL,
    requires_proof TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    payment_method_id INT UNSIGNED NOT NULL,
    reference VARCHAR(180) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    pay_currency VARCHAR(10) NULL,
    pay_method VARCHAR(20) NULL,
    fx_rate_used DECIMAL(16,6) NULL,
    crypto_asset VARCHAR(12) NULL,
    crypto_rate_used DECIMAL(24,10) NULL,
    crypto_amount_due DECIMAL(24,8) NULL,
    crypto_network VARCHAR(24) NULL,
    rate_locked_at DATETIME NULL,
    gateway_provider VARCHAR(40) NULL,
    gateway_transaction_id VARCHAR(140) NULL,
    gateway_session_id VARCHAR(190) NULL,
    gateway_payload LONGTEXT NULL,
    proof_path VARCHAR(500) NULL,
    note TEXT NULL,
    rejection_reason TEXT NULL,
    status ENUM('submitted', 'confirmed', 'rejected') NOT NULL DEFAULT 'submitted',
    reviewed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    UNIQUE KEY uniq_gateway_provider_tx (gateway_provider, gateway_transaction_id),
    UNIQUE KEY uniq_gateway_provider_session (gateway_provider, gateway_session_id),
    CONSTRAINT fk_payments_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crypto_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    base_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    asset_symbol VARCHAR(12) NOT NULL,
    rate_usd_per_asset DECIMAL(24,10) NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'fallback',
    last_updated_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_crypto_rate_pair (base_currency, asset_symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crypto_rate_snapshots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_date DATE NOT NULL,
    base_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    asset_symbol VARCHAR(12) NOT NULL,
    rate_usd_per_asset DECIMAL(24,10) NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'snapshot',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_crypto_snapshot_day_asset (snapshot_date, base_currency, asset_symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    target_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_email_outbox (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    plain_body TEXT NOT NULL,
    html_body LONGTEXT NULL,
    status ENUM('pending','sending','retry','sent','failed') NOT NULL DEFAULT 'pending',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 5,
    next_attempt_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    last_error_code VARCHAR(80) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_notification_email (notification_id),
    KEY idx_notification_queue_due (status, next_attempt_at),
    CONSTRAINT fk_outbox_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_outbox_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    mobile_image_path VARCHAR(255) NULL,
    link_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    note TEXT NULL,
    admin_note TEXT NULL,
    status ENUM('pending', 'approved', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    paid_at DATETIME NULL,
    CONSTRAINT fk_withdrawal_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    purpose ENUM('login', 'verify_email') NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    consumed_at DATETIME NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_attempt_limits (
    scope VARCHAR(32) NOT NULL,
    subject_hash CHAR(64) NOT NULL,
    window_started_at INT UNSIGNED NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (scope, subject_hash, window_started_at),
    KEY idx_auth_window_age (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS otp_dispatch_queue (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id CHAR(36) NOT NULL,
    type VARCHAR(64) NOT NULL,
    content_json LONGTEXT NOT NULL,
    order_index INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_page_blocks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    KEY idx_page_blocks_page_order (page_id, order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_revisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id CHAR(36) NOT NULL,
    layout_json LONGTEXT NOT NULL,
    seo_json LONGTEXT NULL,
    label VARCHAR(190) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_page_revision_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_page_revision_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media_library (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backward compatibility for old builds.
CREATE TABLE IF NOT EXISTS media_assets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Public Insights and admin/agent editorial use this shared table.
CREATE TABLE IF NOT EXISTS blog_posts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Additional editorial modules used by the administrator's Stories & Feedback.
CREATE TABLE IF NOT EXISTS iworks_case_studies (
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
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_case_public (status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS iworks_testimonials (
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
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_testimonial_public(status,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS iworks_blog_comments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS iworks_blog_likes (
    post_id INT UNSIGNED NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(post_id,visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Persistent website live chat. Staff can reply from the protected portal inbox.
CREATE TABLE IF NOT EXISTS iw_live_threads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    visitor_token_hash CHAR(64) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(254) NOT NULL,
    ip_prefix VARCHAR(80) NOT NULL,
    country_code CHAR(2) NULL,
    landing_page VARCHAR(255) NULL,
    status ENUM('open','ended','closed') NOT NULL DEFAULT 'open',
    assigned_user_id INT UNSIGNED NULL,
    visitor_last_seen_at DATETIME NULL,
    staff_last_seen_at DATETIME NULL,
    last_message_at DATETIME NULL,
    last_staff_read_at DATETIME NULL,
    rating TINYINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_live_status (status,updated_at),
    INDEX idx_live_ip (ip_prefix,created_at),
    INDEX idx_live_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS iw_live_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    thread_id BIGINT UNSIGNED NOT NULL,
    sender ENUM('visitor','staff','system') NOT NULL,
    staff_user_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_live_messages_thread (thread_id,id),
    INDEX idx_live_messages_sender (thread_id,sender,created_at),
    CONSTRAINT fk_live_messages_thread FOREIGN KEY (thread_id)
        REFERENCES iw_live_threads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS iw_live_staff_presence (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_at DATETIME NOT NULL,
    INDEX idx_live_staff_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_leads (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS trusted_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(80) NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    CONSTRAINT fk_trusted_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    message_html LONGTEXT NULL,
    attachment_path VARCHAR(500) NULL,
    audience ENUM('all', 'client', 'agent') NOT NULL DEFAULT 'all',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    require_ack TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_announcement_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL,
    UNIQUE KEY uniq_announcement_user (announcement_id, user_id),
    CONSTRAINT fk_announcement_reads_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_announcement_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_admin_agent_chat (admin_id, agent_id),
    CONSTRAINT fk_chat_thread_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_thread_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_typing_status (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    last_typed_at DATETIME NOT NULL,
    UNIQUE KEY uniq_chat_typing (thread_id, user_id),
    CONSTRAINT fk_chat_typing_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_ticket_messages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(220) NOT NULL,
    body LONGTEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_community_post_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_community_comment_post FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_community_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_community_post_like (post_id, user_id),
    CONSTRAINT fk_community_like_post FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_community_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO services (name, slug, description, short_description, price, currency, commission_percent, tax_percent, category, featured_bullets, is_active, is_public, is_featured, created_at)
VALUES
('Company Registration', 'company-registration', 'Register your company with CAC and required compliance.', 'Register your company with CAC and required compliance.', 120000.00, 'NGN', 10.00, 7.50, 'business setup', '["CAC filing support","Director and shareholder guidance","Post-registration compliance steps"]', 1, 1, 1, NOW()),
('Incorporated Trustees', 'incorporated-trustees', 'Setup NGOs, associations, and trustees structure.', 'Setup NGOs, associations, and trustees structure.', 180000.00, 'NGN', 10.00, 7.50, 'compliance', '["Trustee filing workflow","Constitution guidance","Support document checklist"]', 1, 1, 0, NOW()),
('Social Enterprise (Ltd by Guarantee)', 'social-enterprise-ltd-by-guarantee', 'Company limited by guarantee setup and filing support.', 'Company limited by guarantee setup and filing support.', 220000.00, 'NGN', 10.00, 7.50, 'business setup', '["Entity structure review","Filing preparation","Remote submission support"]', 1, 1, 0, NOW()),
('Business Name Registration', 'business-name-registration', 'Register and reserve business name under CAC.', 'Register and reserve a business name under CAC.', 65000.00, 'NGN', 8.00, 7.50, 'business setup', '["Name reservation","CAC filing","Status tracking"]', 1, 1, 1, NOW()),
('Website Design & Development', 'website-design-development', 'Website design and development services.', 'Website design and development services.', 250000.00, 'NGN', 12.00, 7.50, 'digital growth', '["Design and build","Responsive delivery","Launch support"]', 1, 1, 1, NOW()),
('Graphics Design', 'graphics-design', 'Brand graphics, identity, social media creatives.', 'Brand graphics, identity, and social media creatives.', 70000.00, 'NGN', 8.00, 7.50, 'digital growth', '["Brand assets","Creative concepts","Remote revisions"]', 1, 1, 0, NOW()),
('SEO/SMM/Digital Marketing', 'seo-smm-digital-marketing', 'Digital growth campaigns and optimization.', 'Digital growth campaigns and optimization.', 150000.00, 'NGN', 10.00, 7.50, 'digital growth', '["SEO strategy","Campaign setup","Monthly optimisation"]', 1, 1, 0, NOW()),
('Domain & Web Hosting', 'domain-web-hosting', 'Domain registration and managed hosting.', 'Domain registration and managed hosting.', 55000.00, 'NGN', 7.00, 7.50, 'digital growth', '["Domain acquisition","Hosting setup","Renewal support"]', 1, 1, 1, NOW()),
('Email List / SMTP', 'email-list-smtp', 'Email delivery setup and SMTP support.', 'Email delivery setup and SMTP support.', 90000.00, 'NGN', 8.00, 7.50, 'digital growth', '["SMTP configuration","Deliverability support","Provider setup"]', 1, 1, 0, NOW()),
('Vehicles & Assets Tracking', 'vehicles-assets-tracking', 'Asset/vehicle tracking deployment and monitoring.', 'Asset and vehicle tracking deployment and monitoring.', 210000.00, 'NGN', 9.00, 7.50, 'operations', '["Tracking deployment","Reporting setup","Monitoring support"]', 1, 1, 0, NOW()),
('Pro Training', 'pro-training', 'Professional digital and business operations training.', 'Professional digital and business operations training.', 85000.00, 'NGN', 8.00, 7.50, 'training', '["Live coaching","Templates and SOPs","Practical walkthroughs"]', 1, 1, 0, NOW()),
('Launch a Company Package', 'launch-a-company-package', 'End-to-end launch package with CAC, TIN, banking, branding and go-live support.', 'End-to-end launch package with CAC, TIN, banking, branding and go-live support.', 350000.00, 'NGN', 12.00, 7.50, 'business setup', '["CAC registration","TIN and compliance","Brand and launch setup"]', 1, 1, 1, NOW())
ON DUPLICATE KEY UPDATE
name = VALUES(name),
short_description = VALUES(short_description),
price = VALUES(price),
currency = VALUES(currency),
commission_percent = VALUES(commission_percent),
tax_percent = VALUES(tax_percent),
category = VALUES(category),
featured_bullets = VALUES(featured_bullets),
is_active = VALUES(is_active),
is_public = VALUES(is_public),
is_featured = VALUES(is_featured);

INSERT INTO intake_forms (title, description, schema_json, status, created_by, updated_by, created_at, updated_at)
SELECT
  'Trademark Intake Form',
  'Collect trademark filing requirements and supporting documents.',
  '{"meta":{"serviceName":"Trademark Processing","jurisdiction":"Nigeria"},"steps":[{"title":"Applicant details","description":"Provide applicant information exactly as government records.","fields":[{"key":"applicant_type","label":"Applicant type","type":"radio","required":true,"options":["Individual","Company"],"helpText":"Select who owns the mark."},{"key":"applicant_full_name","label":"Applicant full name","type":"text","required":true,"placeholder":"Enter legal full name"},{"key":"email","label":"Email","type":"email","required":true},{"key":"phone","label":"Phone","type":"phone","required":true}]},{"title":"Trademark details","description":"Provide details for filing.","fields":[{"key":"mark_name","label":"Trademark name","type":"text","required":true},{"key":"trademark_class","label":"Trademark class","type":"text","required":true,"helpText":"Provide class number(s)."},{"key":"goods_services","label":"Goods/services description","type":"textarea","required":true},{"key":"logo_file","label":"Logo file","type":"file","required":false,"helpText":"Upload PNG/JPG/PDF if logo mark."}]},{"title":"Review","description":"Confirm and submit.","fields":[{"key":"consent_confirmation","label":"I confirm all details are accurate.","type":"checkbox","required":true,"options":["Confirmed"]}]}]}',
  'active',
  NULL,
  NULL,
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM intake_forms WHERE title = 'Trademark Intake Form');

INSERT INTO intake_forms (title, description, schema_json, status, created_by, updated_by, created_at, updated_at)
SELECT
  'CAC Registration Intake Form',
  'Collect company/business registration requirements and documents.',
  '{"meta":{"serviceName":"CAC Registration","jurisdiction":"Nigeria"},"steps":[{"title":"Applicant details","description":"Provide business owner details.","fields":[{"key":"applicant_full_name","label":"Applicant full name","type":"text","required":true},{"key":"email","label":"Email","type":"email","required":true},{"key":"phone","label":"Phone","type":"phone","required":true}]},{"title":"Registration details","description":"Provide business registration information.","fields":[{"key":"registration_type","label":"Registration type","type":"select","required":true,"options":["Business Name","Company Limited by Shares","NGO / Trustee"]},{"key":"proposed_name_1","label":"Proposed name option 1","type":"text","required":true},{"key":"proposed_name_2","label":"Proposed name option 2","type":"text","required":true},{"key":"business_activity","label":"Business activity","type":"textarea","required":true},{"key":"id_document","label":"Government ID","type":"file","required":true}]},{"title":"Review","description":"Confirm and submit.","fields":[{"key":"consent_confirmation","label":"I confirm all details are accurate.","type":"checkbox","required":true,"options":["Confirmed"]}]}]}',
  'active',
  NULL,
  NULL,
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM intake_forms WHERE title = 'CAC Registration Intake Form');

INSERT INTO service_intake_forms (service_id, intake_form_id, is_required, sort_order, created_at)
SELECT s.id, f.id, 1, 1, NOW()
FROM services s
JOIN intake_forms f ON f.title = 'CAC Registration Intake Form'
WHERE s.slug IN ('company-registration', 'business-name-registration')
  AND NOT EXISTS (
    SELECT 1 FROM service_intake_forms x WHERE x.service_id = s.id AND x.intake_form_id = f.id
  );

INSERT INTO service_intake_forms (service_id, intake_form_id, is_required, sort_order, created_at)
SELECT s.id, f.id, 1, 1, NOW()
FROM services s
JOIN intake_forms f ON f.title = 'Trademark Intake Form'
WHERE s.slug IN ('trademark', 'trademark-processing')
  AND NOT EXISTS (
    SELECT 1 FROM service_intake_forms x WHERE x.service_id = s.id AND x.intake_form_id = f.id
  );

INSERT INTO payment_methods (method_code, display_name, details, public_key, secret_key, private_key, callback_url, webhook_url, requires_proof, is_active, created_at)
VALUES
('stripe', 'Stripe', 'Enable Stripe and add your checkout/payment instructions.', '', '', '', '', '', 0, 1, NOW()),
('paystack', 'Paystack', 'Enable Paystack and add your transfer/checkout instructions.', '', '', '', '', '', 0, 1, NOW()),
('flutterwave', 'Flutterwave', 'Enable Flutterwave and add your transfer/checkout instructions.', '', '', '', '', '', 0, 1, NOW()),
('crypto_wallet', 'Crypto Wallet', 'List accepted coins and wallet addresses.', '', '', '', '', '', 1, 1, NOW()),
('bank_transfer', 'Bank Transfer', 'Add bank name, account name, and account number.', '', '', '', '', '', 1, 1, NOW())
ON DUPLICATE KEY UPDATE
 display_name = VALUES(display_name),
 details = VALUES(details),
 public_key = VALUES(public_key),
 secret_key = VALUES(secret_key),
 private_key = VALUES(private_key),
 callback_url = VALUES(callback_url),
 webhook_url = VALUES(webhook_url),
 requires_proof = VALUES(requires_proof);

INSERT INTO app_settings (setting_key, setting_value, updated_at)
VALUES
('brand_primary', '#ff6600', NOW()),
('brand_primary_dark', '#111111', NOW()),
('brand_accent', '#6b7280', NOW()),
('brand_name', 'iWORKS GLOBAL', NOW()),
('app_name', 'iWorks Global Business Support Portal', NOW()),
('company_legal_name', 'iWorks Global Limited', NOW()),
('business_support_summary', 'Business support tools to help entrepreneurs scale their business with our services.', NOW()),
('default_currency', 'NGN', NOW()),
('brand_footer_text', 'iWorks Global Limited', NOW()),
('withdrawal_minimum', '20000', NOW()),
('withdrawal_cooldown_days', '14', NOW()),
('fx_ngn_per_usd', '1600', NOW()),
('crypto_rates_refresh_minutes', '10', NOW()),
('logo_path', '', NOW()),
('favicon_path', '', NOW()),
('seo_description', 'Business support tools to help entrepreneurs scale their business with our services.', NOW()),
('seo_tags', 'iworksglobal, business support, company registration, remote portal', NOW()),
('social_facebook', '', NOW()),
('social_instagram', '', NOW()),
('social_x', '', NOW()),
('social_linkedin', '', NOW()),
('social_tiktok', '', NOW()),
('social_youtube', '', NOW()),
('mail_enabled', '0', NOW()),
('mail_default_disabled_v29', '1', NOW()),
('mail_from_name', 'iWorks Global Business Support Portal', NOW()),
('mail_from_email', 'no-reply@iworksgt.com', NOW()),
('email_notifications_enabled', '1', NOW()),
('otp_expiry_minutes', '10', NOW()),
('otp_resend_cooldown_seconds', '45', NOW()),
('otp_queue_retry_seconds', '20', NOW()),
('otp_queue_max_attempts', '3', NOW()),
('google_auth_enabled', '0', NOW()),
('google_client_id', '', NOW()),
('google_client_secret', '', NOW()),
('google_redirect_uri', '', NOW()),
('ai_enabled', '0', NOW()),
('ai_provider', 'openai', NOW()),
('ai_model', 'gpt-4o-mini', NOW()),
('ai_api_key', '', NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at);

INSERT INTO crypto_rates (base_currency, asset_symbol, rate_usd_per_asset, source, last_updated_at, created_at, updated_at)
VALUES
('USD', 'USDT', 1.0000000000, 'seed', NOW(), NOW(), NOW()),
('USD', 'LTC', 90.0000000000, 'seed', NOW(), NOW(), NOW()),
('USD', 'BTC', 65000.0000000000, 'seed', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
rate_usd_per_asset = VALUES(rate_usd_per_asset),
source = VALUES(source),
last_updated_at = VALUES(last_updated_at),
updated_at = VALUES(updated_at);
