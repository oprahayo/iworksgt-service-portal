<?php

declare(strict_types=1);

function reset_current_user_cache(): void
{
    $GLOBALS['__current_user_cache'] = null;
}

function current_user(): ?array
{
    if (array_key_exists('__current_user_cache', $GLOBALS) && is_array($GLOBALS['__current_user_cache'])) {
        return $GLOBALS['__current_user_cache'];
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $GLOBALS['__current_user_cache'] = null;
        return null;
    }

    $user = db_one('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $userId]);

    if ($user && (($user['status'] ?? 'active') !== 'active' || ($user['account_state'] ?? 'active') !== 'active')) {
        unset($_SESSION['user_id']);
        $user = null;
    }

    if ($user) {
        $heartbeatTs = (int) ($_SESSION['last_seen_heartbeat_ts'] ?? 0);
        if ((time() - $heartbeatTs) >= 45) {
            $seenAt = now();
            try {
                db_execute('UPDATE users SET last_seen_at = :last_seen_at WHERE id = :id', [
                    'last_seen_at' => $seenAt,
                    'id' => (int) $user['id'],
                ]);
            } catch (Throwable $e) {
                // Ignore until schema upgrade adds last_seen_at.
            }
            $_SESSION['last_seen_heartbeat_ts'] = time();
            $user['last_seen_at'] = $seenAt;
        }
    }

    $GLOBALS['__current_user_cache'] = $user ?: null;

    return $GLOBALS['__current_user_cache'];
}

function find_user_by_id(int $id): ?array
{
    return db_one('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1', ['id' => $id]);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): ?string
{
    return current_user()['role'] ?? null;
}

function has_role(array $roles): bool
{
    $role = user_role();
    return $role !== null && in_array($role, $roles, true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please login to continue.');
        redirect(portal_url('login'));
    }
}

function require_roles(array $roles): void
{
    require_login();

    if (!has_role($roles)) {
        http_response_code(403);
        exit('You do not have permission for this section.');
    }
}

function find_user_by_email(string $email): ?array
{
    return db_one('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1', ['email' => strtolower(trim($email))]);
}

function authenticate_with_password(string $email, string $password): ?array
{
    $user = find_user_by_email($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    if (($user['status'] ?? 'active') !== 'active') {
        return null;
    }

    if (($user['account_state'] ?? 'active') !== 'active') {
        return null;
    }

    return $user;
}

function login_with_password(string $email, string $password): bool
{
    $user = authenticate_with_password($email, $password);

    if (!$user) {
        return false;
    }

    $_SESSION['user_id'] = (int) $user['id'];
    reset_current_user_cache();

    return true;
}

function login_user(int $userId): void
{
    $_SESSION['user_id'] = $userId;
    try {
        db_execute('UPDATE users SET last_seen_at = :last_seen_at WHERE id = :id', [
            'last_seen_at' => now(),
            'id' => $userId,
        ]);
    } catch (Throwable $e) {
        // Ignore until schema upgrade adds last_seen_at.
    }
    reset_current_user_cache();
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    reset_current_user_cache();
}

function is_email_verified(array $user): bool
{
    return !empty($user['email_verified_at']);
}

function mark_email_verified(int $userId): void
{
    db_execute('UPDATE users SET email_verified_at = :verified_at WHERE id = :id', [
        'verified_at' => now(),
        'id' => $userId,
    ]);
}

function create_user(array $data): int
{
    $emailVerified = !empty($data['email_verified']) ? now() : null;
    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName = trim((string) ($data['last_name'] ?? ''));
    $fullName = trim((string) ($data['full_name'] ?? trim($firstName . ' ' . $lastName)));
    if ($fullName === '') {
        $fullName = 'Portal User';
    }

    db_execute(
        'INSERT INTO users (
            full_name, first_name, last_name, email, gender, date_of_birth, phone, country, state_region, city, address_line, occupation,
            password_hash, role, status, account_state, deleted_at, email_verified_at, must_change_password,
            bank_name, bank_account_name, bank_account_number, bank_swift, bank_currency, created_at
         )
         VALUES (
            :full_name, :first_name, :last_name, :email, :gender, :date_of_birth, :phone, :country, :state_region, :city, :address_line, :occupation,
            :password_hash, :role, :status, :account_state, :deleted_at, :email_verified_at, :must_change_password,
            :bank_name, :bank_account_name, :bank_account_number, :bank_swift, :bank_currency, :created_at
         )',
        [
            'full_name' => $fullName,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => strtolower(trim($data['email'])),
            'gender' => trim((string) ($data['gender'] ?? '')) ?: null,
            'date_of_birth' => trim((string) ($data['date_of_birth'] ?? '')) ?: null,
            'phone' => trim($data['phone'] ?? ''),
            'country' => trim((string) ($data['country'] ?? '')) ?: null,
            'state_region' => trim((string) ($data['state_region'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? '')) ?: null,
            'address_line' => trim((string) ($data['address_line'] ?? '')) ?: null,
            'occupation' => trim((string) ($data['occupation'] ?? '')) ?: null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'client',
            'status' => $data['status'] ?? 'active',
            'account_state' => $data['account_state'] ?? 'active',
            'deleted_at' => null,
            'email_verified_at' => $emailVerified,
            'must_change_password' => !empty($data['must_change_password']) ? 1 : 0,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_name' => $data['bank_account_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'bank_swift' => $data['bank_swift'] ?? null,
            'bank_currency' => $data['bank_currency'] ?? null,
            'created_at' => now(),
        ]
    );

    return db_last_id();
}

function otp_expiry_minutes(): int
{
    $minutes = (int) setting('otp_expiry_minutes', '10');
    return $minutes > 0 ? $minutes : 10;
}

function otp_resend_cooldown_seconds(): int
{
    $seconds = (int) setting('otp_resend_cooldown_seconds', '45');
    $seconds = max(30, $seconds);
    return min(60, $seconds);
}

function otp_queue_retry_seconds(): int
{
    $seconds = (int) setting('otp_queue_retry_seconds', '20');
    $seconds = max(10, $seconds);
    return min(300, $seconds);
}

function otp_queue_max_attempts(): int
{
    $attempts = (int) setting('otp_queue_max_attempts', '3');
    $attempts = max(1, $attempts);
    return min(10, $attempts);
}

function otp_queue_table_available(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    try {
        $row = db_one(
            'SELECT COUNT(*) AS c
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name',
            ['table_name' => 'otp_dispatch_queue']
        );
        $available = (int) ($row['c'] ?? 0) > 0;
    } catch (Throwable $e) {
        $available = false;
    }

    return $available;
}

function otp_email_payload(array $user, string $purpose, string $code): array
{
    $subject = $purpose === 'verify_email' ? 'Verify your iWorks GT account' : 'Your iWorks GT login OTP';
    $message = "Hello {$user['full_name']},\n\n";
    $message .= "Your OTP code is: {$code}\n";
    $message .= 'This code expires in ' . otp_expiry_minutes() . " minutes.\n\n";
    $message .= 'If you did not request this, ignore this email.';

    return [
        'subject' => $subject,
        'message' => $message,
    ];
}

function otp_resend_cooldown_remaining(int $userId, string $purpose): int
{
    $lastOtp = db_one(
        'SELECT created_at
         FROM otp_codes
         WHERE user_id = :user_id AND purpose = :purpose
         ORDER BY id DESC
         LIMIT 1',
        [
            'user_id' => $userId,
            'purpose' => $purpose,
        ]
    );

    if (!$lastOtp) {
        return 0;
    }

    $createdTs = strtotime((string) ($lastOtp['created_at'] ?? ''));
    if ($createdTs === false) {
        return 0;
    }

    $remaining = otp_resend_cooldown_seconds() - (time() - $createdTs);
    return max(0, $remaining);
}

function queue_otp_delivery(array $user, string $purpose, string $code): bool
{
    if (!otp_queue_table_available()) {
        return false;
    }

    $toEmail = trim((string) ($user['email'] ?? ''));
    if ($toEmail === '') {
        return false;
    }

    $payload = otp_email_payload($user, $purpose, $code);

    db_execute(
        "UPDATE otp_dispatch_queue
         SET status = 'superseded', updated_at = :updated_at, last_error = :last_error
         WHERE user_id = :user_id
           AND purpose = :purpose
           AND status IN ('queued', 'processing', 'retrying')",
        [
            'updated_at' => now(),
            'last_error' => 'Superseded by a newer OTP request.',
            'user_id' => (int) $user['id'],
            'purpose' => $purpose,
        ]
    );

    db_execute(
        'INSERT INTO otp_dispatch_queue (
            user_id, purpose, to_email, subject, message, otp_code, status,
            attempt_count, max_attempts, next_attempt_at, sent_at, last_error, created_at, updated_at
         ) VALUES (
            :user_id, :purpose, :to_email, :subject, :message, :otp_code, :status,
            :attempt_count, :max_attempts, :next_attempt_at, :sent_at, :last_error, :created_at, :updated_at
         )',
        [
            'user_id' => (int) $user['id'],
            'purpose' => $purpose,
            'to_email' => $toEmail,
            'subject' => (string) $payload['subject'],
            'message' => (string) $payload['message'],
            'otp_code' => $code,
            'status' => 'queued',
            'attempt_count' => 0,
            'max_attempts' => otp_queue_max_attempts(),
            'next_attempt_at' => now(),
            'sent_at' => null,
            'last_error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    return true;
}

function latest_otp_dispatch(int $userId, string $purpose): ?array
{
    if (!otp_queue_table_available()) {
        return null;
    }

    return db_one(
        'SELECT *
         FROM otp_dispatch_queue
         WHERE user_id = :user_id AND purpose = :purpose
         ORDER BY id DESC
         LIMIT 1',
        [
            'user_id' => $userId,
            'purpose' => $purpose,
        ]
    );
}

function process_otp_dispatch_queue(int $limit = 5): array
{
    $summary = [
        'processed' => 0,
        'sent' => 0,
        'failed' => 0,
    ];

    if (!otp_queue_table_available()) {
        return $summary;
    }

    $limit = max(1, min(50, $limit));
    $rows = db_all(
        "SELECT *
         FROM otp_dispatch_queue
         WHERE status IN ('queued', 'retrying')
           AND next_attempt_at <= :next_attempt_at
         ORDER BY id ASC
         LIMIT {$limit}",
        ['next_attempt_at' => now()]
    );

    foreach ($rows as $row) {
        $claim = db_query(
            "UPDATE otp_dispatch_queue
             SET status = 'processing',
                 attempt_count = attempt_count + 1,
                 updated_at = :updated_at
             WHERE id = :id
               AND status IN ('queued', 'retrying')",
            [
                'updated_at' => now(),
                'id' => (int) $row['id'],
            ]
        );

        if ($claim->rowCount() <= 0) {
            continue;
        }

        $queued = db_one('SELECT * FROM otp_dispatch_queue WHERE id = :id LIMIT 1', ['id' => (int) $row['id']]);
        if (!$queued) {
            continue;
        }

        $summary['processed']++;

        $sent = send_email(
            (string) ($queued['to_email'] ?? ''),
            (string) ($queued['subject'] ?? ''),
            (string) ($queued['message'] ?? '')
        );

        if ($sent) {
            db_execute(
                "UPDATE otp_dispatch_queue
                 SET status = 'sent', sent_at = :sent_at, last_error = NULL, updated_at = :updated_at
                 WHERE id = :id",
                [
                    'sent_at' => now(),
                    'updated_at' => now(),
                    'id' => (int) $queued['id'],
                ]
            );
            $summary['sent']++;
            continue;
        }

        $attemptCount = (int) ($queued['attempt_count'] ?? 0);
        $maxAttempts = max(1, (int) ($queued['max_attempts'] ?? otp_queue_max_attempts()));
        $isLastAttempt = $attemptCount >= $maxAttempts;
        $nextAttemptAt = date('Y-m-d H:i:s', time() + otp_queue_retry_seconds());

        db_execute(
            'UPDATE otp_dispatch_queue
             SET status = :status,
                 next_attempt_at = :next_attempt_at,
                 last_error = :last_error,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'status' => $isLastAttempt ? 'failed' : 'retrying',
                'next_attempt_at' => $nextAttemptAt,
                'last_error' => $isLastAttempt
                    ? 'Maximum retry attempts reached.'
                    : 'Email delivery failed. Will retry.',
                'updated_at' => now(),
                'id' => (int) $queued['id'],
            ]
        );

        if ($isLastAttempt) {
            $summary['failed']++;
        }
    }

    if (function_exists('set_setting')) {
        set_setting('otp_queue_last_run_at', now());
    }

    return $summary;
}

function otp_queue_dispatch_kick(): void
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    register_shutdown_function(static function (): void {
        try {
            if (PHP_SAPI !== 'cli' && function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            process_otp_dispatch_queue(3);
        } catch (Throwable $e) {
            // Background queue kick should not block user requests.
        }
    });
}

function otp_delivery_fallback_allowed(int $userId, string $purpose): bool
{
    $dispatch = latest_otp_dispatch($userId, $purpose);
    if (!$dispatch) {
        return false;
    }

    $status = (string) ($dispatch['status'] ?? '');
    if (in_array($status, ['sent', 'sent_fallback', 'superseded'], true)) {
        return false;
    }

    $createdAtTs = strtotime((string) ($dispatch['created_at'] ?? ''));
    if ($createdAtTs === false) {
        return false;
    }

    return (time() - $createdAtTs) >= max(45, otp_queue_retry_seconds() * 2);
}

function send_latest_otp_via_fallback(int $userId, string $purpose): bool
{
    if (!otp_queue_table_available()) {
        return false;
    }

    $dispatch = latest_otp_dispatch($userId, $purpose);
    if (!$dispatch) {
        return false;
    }

    $status = (string) ($dispatch['status'] ?? '');
    if (in_array($status, ['sent', 'sent_fallback', 'superseded'], true)) {
        return false;
    }

    $sent = send_email(
        (string) ($dispatch['to_email'] ?? ''),
        (string) ($dispatch['subject'] ?? ''),
        (string) ($dispatch['message'] ?? '')
    );

    db_execute(
        'UPDATE otp_dispatch_queue
         SET status = :status,
             sent_at = :sent_at,
             last_error = :last_error,
             updated_at = :updated_at
         WHERE id = :id',
        [
            'status' => $sent ? 'sent_fallback' : 'failed',
            'sent_at' => $sent ? now() : null,
            'last_error' => $sent ? null : 'Fallback delivery failed.',
            'updated_at' => now(),
            'id' => (int) $dispatch['id'],
        ]
    );

    return $sent;
}

function create_otp_code(int $userId, string $purpose): string
{
    if (!in_array($purpose, ['login', 'verify_email'], true)) {
        throw new RuntimeException('Invalid OTP purpose.');
    }

    $code = (string) random_int(100000, 999999);

    db_execute('DELETE FROM otp_codes WHERE user_id = :user_id AND purpose = :purpose AND consumed_at IS NULL', [
        'user_id' => $userId,
        'purpose' => $purpose,
    ]);

    db_execute(
        'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, consumed_at, created_at)
         VALUES (:user_id, :purpose, :code_hash, :expires_at, :consumed_at, :created_at)',
        [
            'user_id' => $userId,
            'purpose' => $purpose,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . otp_expiry_minutes() . ' minutes')),
            'consumed_at' => null,
            'created_at' => now(),
        ]
    );

    return $code;
}

function send_otp_email(array $user, string $purpose, string $code): void
{
    $payload = otp_email_payload($user, $purpose, $code);
    send_email((string) $user['email'], (string) $payload['subject'], (string) $payload['message']);
}

function set_pending_otp(int $userId, string $purpose): void
{
    $_SESSION['pending_otp'] = [
        'user_id' => $userId,
        'purpose' => $purpose,
        'created_at' => now(),
    ];
}

function pending_otp_context(): ?array
{
    $pending = $_SESSION['pending_otp'] ?? null;

    if (!is_array($pending) || empty($pending['user_id']) || empty($pending['purpose'])) {
        return null;
    }

    return $pending;
}

function clear_pending_otp(): void
{
    unset($_SESSION['pending_otp']);
}

function start_otp_challenge(int $userId, string $purpose): void
{
    $user = find_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $code = create_otp_code($userId, $purpose);
    $queued = queue_otp_delivery($user, $purpose, $code);
    if ($queued) {
        otp_queue_dispatch_kick();
    } else {
        // Fallback for legacy installs without queue table.
        send_otp_email($user, $purpose, $code);
    }
    set_pending_otp($userId, $purpose);
}

function verify_otp_code(int $userId, string $purpose, string $code): bool
{
    $record = db_one(
        'SELECT * FROM otp_codes
         WHERE user_id = :user_id AND purpose = :purpose AND consumed_at IS NULL
         ORDER BY id DESC LIMIT 1',
        [
            'user_id' => $userId,
            'purpose' => $purpose,
        ]
    );

    if (!$record) {
        return false;
    }

    if (strtotime((string) $record['expires_at']) < time()) {
        return false;
    }

    if (!password_verify(trim($code), (string) $record['code_hash'])) {
        return false;
    }

    db_execute('UPDATE otp_codes SET consumed_at = :consumed_at WHERE id = :id', [
        'consumed_at' => now(),
        'id' => $record['id'],
    ]);

    return true;
}

function trusted_device_cookie_name(): string
{
    return 'iworksgt_trusted_device';
}

function remember_device_days(): int
{
    return 30;
}

function set_trusted_device_cookie(string $token): void
{
    $expires = time() + (remember_device_days() * 86400);
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(trusted_device_cookie_name(), $token, [
        'expires' => $expires,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[trusted_device_cookie_name()] = $token;
}

function remember_trusted_device(int $userId): void
{
    $token = bin2hex(random_bytes(32));

    db_execute(
        'INSERT INTO trusted_devices (user_id, token_hash, user_agent, ip_address, expires_at, created_at, last_used_at)
         VALUES (:user_id, :token_hash, :user_agent, :ip_address, :expires_at, :created_at, :last_used_at)',
        [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 80),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . remember_device_days() . ' days')),
            'created_at' => now(),
            'last_used_at' => now(),
        ]
    );

    set_trusted_device_cookie($token);
}

function has_valid_trusted_device(int $userId): bool
{
    $token = $_COOKIE[trusted_device_cookie_name()] ?? '';
    if (!is_string($token) || trim($token) === '') {
        return false;
    }

    $record = db_one(
        'SELECT * FROM trusted_devices
         WHERE user_id = :user_id AND token_hash = :token_hash
         ORDER BY id DESC LIMIT 1',
        [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
        ]
    );

    if (!$record) {
        return false;
    }

    if (strtotime((string) $record['expires_at']) < time()) {
        return false;
    }

    db_execute('UPDATE trusted_devices SET last_used_at = :last_used_at WHERE id = :id', [
        'last_used_at' => now(),
        'id' => $record['id'],
    ]);

    return true;
}

function can_view_full_bank_details(array $viewer, array $agent): bool
{
    if ((int) $viewer['id'] === (int) $agent['id']) {
        return true;
    }

    return in_array($viewer['role'], ['admin', 'finance'], true);
}

function role_label(string $role): string
{
    return ucwords(str_replace('_', ' ', $role));
}

function password_reset_expiry_minutes(): int
{
    return 60;
}

function create_password_reset_token(int $userId): string
{
    $token = bin2hex(random_bytes(32));

    db_execute(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, used_at, created_at)
         VALUES (:user_id, :token_hash, :expires_at, :used_at, :created_at)',
        [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . password_reset_expiry_minutes() . ' minutes')),
            'used_at' => null,
            'created_at' => now(),
        ]
    );

    return $token;
}

function find_password_reset_by_token(string $token): ?array
{
    return db_one(
        'SELECT r.*, u.email, u.full_name, u.status, u.account_state
         FROM password_resets r
         JOIN users u ON u.id = r.user_id
         WHERE r.token_hash = :token_hash
         ORDER BY r.id DESC
         LIMIT 1',
        ['token_hash' => hash('sha256', $token)]
    );
}

function send_password_reset_link_to_user(int $userId): bool
{
    $user = find_user_by_id($userId);
    if (!$user) {
        return false;
    }

    if (($user['status'] ?? 'active') !== 'active' || ($user['account_state'] ?? 'active') !== 'active') {
        return false;
    }

    $token = create_password_reset_token($userId);
    $link = portal_absolute_url(url('reset_password', ['token' => $token]));
    $subject = 'Reset your iWorks GT portal password';
    $message = "Hello {$user['full_name']},\n\n";
    $message .= "Use the link below to reset your password:\n{$link}\n\n";
    $message .= 'This link expires in ' . password_reset_expiry_minutes() . " minutes.\n";
    $message .= "If you did not request this, ignore this email.";

    send_email((string) $user['email'], $subject, $message);
    return true;
}

function request_password_reset_by_email(string $email): void
{
    $user = find_user_by_email($email);
    if (!$user) {
        return;
    }

    send_password_reset_link_to_user((int) $user['id']);
}

function reset_password_with_token(string $token, string $newPassword, string $confirmPassword): void
{
    $token = trim($token);
    if ($token === '') {
        throw new RuntimeException('Invalid reset token.');
    }

    $record = find_password_reset_by_token($token);
    if (!$record) {
        throw new RuntimeException('Reset link is invalid.');
    }

    if (!empty($record['used_at'])) {
        throw new RuntimeException('Reset link has already been used.');
    }

    if (strtotime((string) $record['expires_at']) < time()) {
        throw new RuntimeException('Reset link has expired. Request a new one.');
    }

    if (strlen($newPassword) < 6) {
        throw new RuntimeException('New password must be at least 6 characters.');
    }

    if ($newPassword !== $confirmPassword) {
        throw new RuntimeException('New password and confirmation do not match.');
    }

    db_execute(
        'UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id',
        [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => (int) $record['user_id'],
        ]
    );

    db_execute('DELETE FROM trusted_devices WHERE user_id = :user_id', ['user_id' => (int) $record['user_id']]);
    db_execute('UPDATE password_resets SET used_at = :used_at WHERE id = :id', ['used_at' => now(), 'id' => (int) $record['id']]);
}

function send_password_reset_links_to_all_active_users(): int
{
    $rows = db_all("SELECT id FROM users WHERE status = 'active' AND account_state = 'active' AND deleted_at IS NULL");
    $count = 0;
    foreach ($rows as $row) {
        if (send_password_reset_link_to_user((int) $row['id'])) {
            $count++;
        }
    }

    return $count;
}

function google_oauth_authorization_url(): string
{
    if (!google_auth_ready()) {
        throw new RuntimeException('Google authentication is not configured in admin settings.');
    }
    $redirectIssues = google_oauth_redirect_problems();
    if ($redirectIssues !== []) {
        throw new RuntimeException('Google sign-in callback configuration needs correction: ' . implode(' ', $redirectIssues));
    }

    $state = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_state_created_at'] = time();

    $params = [
        'client_id' => google_client_id(),
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function consume_google_oauth_state(string $state): bool
{
    $state = trim($state);
    $expected = (string) ($_SESSION['google_oauth_state'] ?? '');
    $createdAt = (int) ($_SESSION['google_oauth_state_created_at'] ?? 0);
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_created_at']);

    if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
        return false;
    }

    if ($createdAt <= 0 || (time() - $createdAt) > 900) {
        return false;
    }

    return true;
}

function google_http_post_form(string $url, array $payload): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL is required for Google authentication.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false || $responseBody === '') {
        throw new RuntimeException('Google token request failed' . ($error !== '' ? ': ' . $error : '.'));
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Could not parse Google token response.');
    }

    return [
        'status_code' => $statusCode,
        'data' => $decoded,
    ];
}

function google_http_get_json(string $url, array $headers = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL is required for Google authentication.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false || $responseBody === '') {
        throw new RuntimeException('Google profile request failed' . ($error !== '' ? ': ' . $error : '.'));
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Could not parse Google profile response.');
    }

    return [
        'status_code' => $statusCode,
        'data' => $decoded,
    ];
}

function exchange_google_code_for_access_token(string $code): string
{
    $code = trim($code);
    if ($code === '') {
        throw new RuntimeException('Google authentication code is missing.');
    }

    $tokenResponse = google_http_post_form('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => google_client_id(),
        'client_secret' => google_client_secret(),
        'redirect_uri' => google_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);

    $data = $tokenResponse['data'];
    if (($tokenResponse['status_code'] ?? 0) >= 400) {
        $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google token exchange failed.');
        throw new RuntimeException($message);
    }

    $accessToken = trim((string) ($data['access_token'] ?? ''));
    if ($accessToken === '') {
        throw new RuntimeException('Google access token was not returned.');
    }

    return $accessToken;
}

function fetch_google_user_profile(string $accessToken): array
{
    $profileResponse = google_http_get_json(
        'https://openidconnect.googleapis.com/v1/userinfo',
        ['Authorization: Bearer ' . $accessToken]
    );

    $data = $profileResponse['data'];
    if (($profileResponse['status_code'] ?? 0) >= 400) {
        $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google profile fetch failed.');
        throw new RuntimeException($message);
    }

    $email = strtolower(trim((string) ($data['email'] ?? '')));
    if ($email === '') {
        throw new RuntimeException('Google account email is missing.');
    }

    $verified = (bool) ($data['email_verified'] ?? false);
    if (!$verified) {
        throw new RuntimeException('Google email is not verified. Please verify your Google account first.');
    }

    return [
        'email' => $email,
        'full_name' => trim((string) ($data['name'] ?? '')),
        'first_name' => trim((string) ($data['given_name'] ?? '')),
        'last_name' => trim((string) ($data['family_name'] ?? '')),
    ];
}

function find_or_create_google_user(array $profile): array
{
    $email = strtolower(trim((string) ($profile['email'] ?? '')));
    if ($email === '') {
        throw new RuntimeException('Google email is required.');
    }

    $user = find_user_by_email($email);
    if ($user) {
        if (($user['status'] ?? 'active') !== 'active' || ($user['account_state'] ?? 'active') !== 'active') {
            throw new RuntimeException('Your account is not active. Contact support.');
        }

        if (!is_email_verified($user)) {
            mark_email_verified((int) $user['id']);
            $user = find_user_by_id((int) $user['id']);
        }

        if (!$user) {
            throw new RuntimeException('Unable to load your account after Google verification.');
        }

        return $user;
    }

    $fullName = trim((string) ($profile['full_name'] ?? ''));
    $firstName = trim((string) ($profile['first_name'] ?? ''));
    $lastName = trim((string) ($profile['last_name'] ?? ''));

    if ($fullName === '') {
        $fullName = trim($firstName . ' ' . $lastName);
    }
    if ($fullName === '') {
        $fullName = 'Google User';
    }

    $userId = create_user([
        'full_name' => $fullName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => bin2hex(random_bytes(16)),
        'role' => 'client',
        'email_verified' => true,
    ]);

    $created = find_user_by_id($userId);
    if (!$created) {
        throw new RuntimeException('Unable to create account from Google profile.');
    }

    return $created;
}

function authenticate_with_google_code(string $code): array
{
    if (!google_auth_ready()) {
        throw new RuntimeException('Google authentication is currently disabled.');
    }

    $accessToken = exchange_google_code_for_access_token($code);
    $profile = fetch_google_user_profile($accessToken);

    return find_or_create_google_user($profile);
}
