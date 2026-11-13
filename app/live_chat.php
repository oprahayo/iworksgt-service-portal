<?php
declare(strict_types=1);

/**
 * iWorks public live chat and staff inbox.
 * Persistent MySQL messages + near-realtime polling, compatible with cPanel.
 * No external geolocation service or raw visitor IP is stored.
 */
function iw_chat_ready(): bool
{
    return schema_table_exists('iw_live_threads')
        && schema_table_exists('iw_live_messages')
        && schema_table_exists('iw_live_staff_presence');
}

function iw_chat_ip_prefix(): string
{
    $raw = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!filter_var($raw, FILTER_VALIDATE_IP)) return 'Unknown';
    if (filter_var($raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $raw);
        $parts[3] = '0';
        return implode('.', $parts) . '/24';
    }
    $binary = inet_pton($raw);
    if ($binary === false) return 'Unknown';
    return inet_ntop(substr($binary, 0, 6) . str_repeat("\0", 10)) . '/48';
}

function iw_chat_country_code(): ?string
{
    // Optional country hint from CDN metadata. Never treat as verified GPS.
    // REMOTE_ADDR remains the only network address used; ignore X-Forwarded-For.
    $country = strtoupper(trim((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    return preg_match('/^[A-Z]{2}$/', $country) && !in_array($country, ['XX', 'T1'], true)
        ? $country : null;
}

function iw_chat_send_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}

function iw_chat_staff_online(): bool
{
    $row = db_one("SELECT COUNT(*) AS n FROM iw_live_staff_presence
                   WHERE last_seen_at >= DATE_SUB(NOW(), INTERVAL 70 SECOND)");
    return (int)($row['n'] ?? 0) > 0;
}

function iw_chat_public_thread(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    return db_one('SELECT * FROM iw_live_threads WHERE visitor_token_hash = :hash LIMIT 1',
        ['hash' => hash('sha256', $token)]);
}

function iw_chat_serialise_messages(array $rows): array
{
    return array_map(static fn(array $m): array => [
        'id' => (int)$m['id'], 'sender' => (string)$m['sender'],
        'body' => (string)$m['body'], 'created_at' => (string)$m['created_at'],
    ], $rows);
}

function iw_chat_messages(int $threadId, int $afterId = 0): array
{
    return iw_chat_serialise_messages(db_all(
        'SELECT id,sender,body,created_at FROM iw_live_messages
         WHERE thread_id=:thread AND id>:after ORDER BY id ASC LIMIT 120',
        ['thread' => $threadId, 'after' => max(0, $afterId)]
    ));
}

function iw_chat_public_request(): void
{
    if (!method_is('POST')) {
        iw_chat_send_json(['ok'=>false, 'message'=>'POST required'],405);
        return;
    }
    if (!verify_csrf()) {
        iw_chat_send_json(['ok'=>false, 'message'=>'Session expired. Refresh and retry.'],419);
        return;
    }
    if (!iw_chat_ready()) {
        iw_chat_send_json(['ok'=>false, 'message'=>'Live chat is being configured. Please use our Contact page.'],503);
        return;
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'start') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 150
                || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                throw new InvalidArgumentException('Enter your name and a valid email address.');
            }
            $ip = iw_chat_ip_prefix();
            $existing = db_one(
                'SELECT COUNT(*) AS n FROM iw_live_threads
                 WHERE ip_prefix=:ip AND created_at>=DATE_SUB(NOW(), INTERVAL 1 HOUR)',
                ['ip'=>$ip]
            );
            if ((int)($existing['n'] ?? 0) >= 25) {
                iw_chat_send_json(['ok'=>false, 'message'=>'Too many new chats from this network. Try again later.'],429);
                return;
            }
            $token = bin2hex(random_bytes(32));
            $ref = substr(trim((string)($_SERVER['HTTP_REFERER'] ?? '')), 0, 250);
            db_execute(
                "INSERT INTO iw_live_threads
                 (visitor_token_hash,name,email,ip_prefix,country_code,landing_page,status,visitor_last_seen_at,created_at,updated_at)
                 VALUES (:token,:name,:email,:ip,:country,:landing,'open',NOW(),NOW(),NOW())",
                ['token'=>hash('sha256', $token),'name'=>$name,'email'=>$email,
                 'ip'=>$ip,'country'=>iw_chat_country_code(),'landing'=>$ref]
            );
            iw_chat_send_json(['ok'=>true,'token'=>$token,'id'=>db_last_id(),
                'staff_online'=>iw_chat_staff_online(), 'messages'=>[]]);
            return;
        }

        $token = strtolower(trim((string)($_POST['token'] ?? '')));
        $thread = iw_chat_public_thread($token);
        if (!$thread) {
            iw_chat_send_json(['ok'=>false,'message'=>'This chat session has expired. Start a new conversation.'],404);
            return;
        }
        $threadId = (int)$thread['id'];
        if ($action === 'poll' || $action === 'send') {
            if ((string)$thread['status'] !== 'open') {
                iw_chat_send_json(['ok'=>true,'status'=>'ended','staff_online'=>iw_chat_staff_online(),
                    'messages'=>iw_chat_messages($threadId, min(2147483647,max(0,(int)($_POST['after_id'] ?? 0))))]);
                return;
            }
            db_execute('UPDATE iw_live_threads SET visitor_last_seen_at=NOW() WHERE id=:id',
                ['id'=>$threadId]);
            if ($action === 'send') {
                $body = trim((string)($_POST['message'] ?? ''));
                if ($body === '' || mb_strlen($body) > 2000) {
                    throw new InvalidArgumentException('Message must be between 1 and 2,000 characters.');
                }
                $freq = db_one("SELECT COUNT(*) AS n FROM iw_live_messages
                    WHERE thread_id=:id AND sender='visitor' AND created_at>DATE_SUB(NOW(),INTERVAL 1 MINUTE)",
                    ['id'=>$threadId]);
                if ((int)($freq['n'] ?? 0) >= 12) {
                    iw_chat_send_json(['ok'=>false,'message'=>'You are sending too quickly. Try again shortly.'],429);
                    return;
                }
                db_execute("INSERT INTO iw_live_messages (thread_id,sender,body,created_at)
                            VALUES (:id,'visitor',:body,NOW())",['id'=>$threadId,'body'=>$body]);
                db_execute('UPDATE iw_live_threads SET last_message_at=NOW(),updated_at=NOW() WHERE id=:id',
                    ['id'=>$threadId]);
                if ((int)($freq['n'] ?? 0) === 0) {
                    // The staff inbox polls regardless. Notification is a fallback, not a fake reply.
                    try {
                        notify_users(admin_user_ids(), 'New visitor live chat',
                            $thread['name'] . ' started a support conversation.',url('live_inbox'));
                    } catch (Throwable $ignored) {}
                }
            }
            $after = min(2147483647,max(0,(int)($_POST['after_id'] ?? 0)));
            iw_chat_send_json(['ok'=>true,'status'=>'open','staff_online'=>iw_chat_staff_online(),
                'messages'=>iw_chat_messages($threadId,$after)]);
            return;
        }
        if ($action === 'end') {
            $rating = (int)($_POST['rating'] ?? 0);
            db_execute("UPDATE iw_live_threads SET status='ended',
                    rating=:rating,updated_at=NOW() WHERE id=:id",
                ['id'=>$threadId,'rating'=>($rating>=1 && $rating<=5)?$rating:null]);
            iw_chat_send_json(['ok'=>true,'status'=>'ended']);
            return;
        }
        iw_chat_send_json(['ok'=>false,'message'=>'Unknown chat action.'],400);
    } catch (InvalidArgumentException $e) {
        iw_chat_send_json(['ok'=>false,'message'=>$e->getMessage()],422);
    } catch (Throwable $e) {
        error_log('[iWorks live chat] '.$e->getMessage());
        iw_chat_send_json(['ok'=>false,'message'=>'Support is temporarily unavailable. Please try again or use Contact.'],503);
    }
}

function iw_chat_staff_request(): void
{
    if (!has_role(['admin','agent'])) {
        iw_chat_send_json(['ok'=>false,'message'=>'Access denied.'],403);
        return;
    }
    if (!method_is('POST') || !verify_csrf()) {
        iw_chat_send_json(['ok'=>false,'message'=>'Invalid request. Refresh the inbox.'],419);
        return;
    }
    if (!iw_chat_ready()) {
        iw_chat_send_json(['ok'=>false,'message'=>'Live chat tables are not installed. Run the schema migration.'],503);
        return;
    }
    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    try {
        db_execute('INSERT INTO iw_live_staff_presence (user_id,last_seen_at)
                    VALUES (:id,NOW()) ON DUPLICATE KEY UPDATE last_seen_at=NOW()',
                    ['id'=>$userId]);
        $action = trim((string)($_POST['action'] ?? 'poll'));
        $threadId = max(0,(int)($_POST['thread_id'] ?? 0));
        if ($action === 'send' || $action === 'close') {
            $thread = db_one('SELECT id,status FROM iw_live_threads WHERE id=:id LIMIT 1',['id'=>$threadId]);
            if (!$thread) { iw_chat_send_json(['ok'=>false,'message'=>'Conversation not found.'],404); return; }
            if ($action === 'close') {
                db_execute("UPDATE iw_live_threads SET status='closed',updated_at=NOW()
                             WHERE id=:id",['id'=>$threadId]);
            } else {
                if ((string)$thread['status'] !== 'open') {
                    iw_chat_send_json(['ok'=>false,'message'=>'This conversation has ended.'],409);
                    return;
                }
                $body = trim((string)($_POST['message'] ?? ''));
                if ($body === '' || mb_strlen($body) > 2000) {
                    throw new InvalidArgumentException('Enter a reply of up to 2,000 characters.');
                }
                db_execute("INSERT INTO iw_live_messages (thread_id,sender,staff_user_id,body,created_at)
                            VALUES (:id,'staff',:user,:body,NOW())",
                    ['id'=>$threadId,'user'=>$userId,'body'=>$body]);
                db_execute('UPDATE iw_live_threads SET last_message_at=NOW(),updated_at=NOW(),
                            assigned_user_id=COALESCE(assigned_user_id,:user) WHERE id=:id',
                    ['id'=>$threadId,'user'=>$userId]);
            }
        }
        if ($threadId > 0 && in_array($action,['poll','send','close'],true)) {
            db_execute('UPDATE iw_live_threads SET last_staff_read_at=NOW() WHERE id=:id',
                ['id'=>$threadId]);
        }
        $threads = db_all(
            "SELECT t.id,t.name,t.email,t.ip_prefix,t.country_code,t.status,t.rating,t.landing_page,
                    t.created_at,t.last_message_at,t.assigned_user_id,
                    IF(t.visitor_last_seen_at>=DATE_SUB(NOW(),INTERVAL 70 SECOND),1,0) AS visitor_online,
                    (SELECT MAX(m.id) FROM iw_live_messages m
                     WHERE m.thread_id=t.id AND m.sender='visitor') AS last_inbound_id,
                    (SELECT LEFT(m.body,145) FROM iw_live_messages m
                     WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) AS preview,
                    (SELECT COUNT(*) FROM iw_live_messages m WHERE m.thread_id=t.id
                      AND m.sender='visitor' AND
                        (t.last_staff_read_at IS NULL OR m.created_at>t.last_staff_read_at)) AS unread
             FROM iw_live_threads t
             ORDER BY (t.status='open') DESC,t.updated_at DESC LIMIT 100"
        );
        $messages = $threadId ? iw_chat_messages($threadId,min(2147483647,max(0,(int)($_POST['after_id'] ?? 0)))) : [];
        iw_chat_send_json(['ok'=>true,'threads'=>$threads,'messages'=>$messages,
            'online_agents'=>(int)(db_one("SELECT COUNT(*) AS n FROM iw_live_staff_presence
                    WHERE last_seen_at>=DATE_SUB(NOW(),INTERVAL 70 SECOND)")['n'] ?? 0)]);
    } catch (InvalidArgumentException $e) {
        iw_chat_send_json(['ok'=>false,'message'=>$e->getMessage()],422);
    } catch (Throwable $e) {
        error_log('[iWorks staff chat] '.$e->getMessage());
        iw_chat_send_json(['ok'=>false,'message'=>'Unable to load chats. Check application logs.'],503);
    }
}
