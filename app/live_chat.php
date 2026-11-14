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

/** New engagement fields become active only after explicit backup-gated DDL. */
function iw_chat_engagement_ready(): bool
{
    return iw_chat_ready() && schema_table_exists('iw_live_email_outbox');
}

/** Host-only, HttpOnly cookie is set only after visitors choose to remember chat. */
function iw_chat_resume_cookie_name(): string
{
    return 'iw_chat_resume';
}

function iw_chat_resume_cookie_token(): string
{
    $value = (string)($_COOKIE[iw_chat_resume_cookie_name()] ?? '');
    return preg_match('/^[a-f0-9]{64}$/D', $value) ? $value : '';
}

function iw_chat_remember_cookie(string $token, bool $remember): void
{
    if (PHP_SAPI === 'cli') return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    $options = [
        'expires' => $remember ? time() + 30 * 86400 : time() - 3600,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    setcookie(iw_chat_resume_cookie_name(), $remember ? $token : '', $options);
}

/** Path only: never store URL search parameters, fragments, emails or tokens. */
function iw_chat_safe_page(string $raw): ?string
{
    $path = parse_url(trim($raw), PHP_URL_PATH);
    if (!is_string($path)) return null;
    $path = rawurldecode($path);
    if (preg_match('~^/(?:services|pricing|about|contact|faq|management|track|blog|case-studies|home)(?:/)?$~iD', $path)) {
        return strtolower(rtrim($path, '/')) ?: '/';
    }
    if (preg_match('~^/(?:service|services|blog|case-studies)/[a-z0-9-]{1,65}/?$~iD', $path)) {
        return strtolower(rtrim($path, '/'));
    }
    return $path === '/' ? '/' : null;
}

/** Staff presence always comes from fresh requests, not a long-lived cookie. */
function iw_chat_touch_visitor(array $thread, array $request): void
{
    $id = (int)$thread['id'];
    if (!iw_chat_engagement_ready()) {
        db_execute('UPDATE iw_live_threads SET visitor_last_seen_at=NOW() WHERE id=:id', ['id'=>$id]);
        return;
    }
    $seen = ($request['chat_open'] ?? '') === '1';
    $page = !empty($thread['track_page'])
        ? iw_chat_safe_page((string)($request['page_path'] ?? '')) : null;
    if ($seen) {
        db_execute("UPDATE iw_live_threads SET visitor_last_seen_at=NOW(),
            current_page=CASE WHEN track_page=1 THEN :page ELSE NULL END,
            visitor_last_read_message_id=GREATEST(visitor_last_read_message_id,
              COALESCE((SELECT MAX(id) FROM iw_live_messages
                 WHERE thread_id=:thread AND sender='staff'),0))
            WHERE id=:id", ['page'=>$page,'thread'=>$id,'id'=>$id]);
    } else {
        db_execute("UPDATE iw_live_threads SET visitor_last_seen_at=NOW(),
            current_page=CASE WHEN track_page=1 THEN :page ELSE NULL END
            WHERE id=:id", ['page'=>$page,'id'=>$id]);
    }
}

function iw_chat_unread_for_visitor(array $thread): int
{
    if (!iw_chat_engagement_ready()) return 0;
    return (int)(db_one("SELECT COUNT(*) AS n FROM iw_live_messages
        WHERE thread_id=:id AND sender='staff' AND id>:last_id",
        ['id'=>(int)$thread['id'],'last_id'=>(int)($thread['visitor_last_read_message_id'] ?? 0)])['n'] ?? 0);
}

function iw_chat_enqueue_offline_reply(array $thread, int $messageId): void
{
    if (!iw_chat_engagement_ready() || $messageId < 1
        || (int)($thread['notify_offline'] ?? 0) !== 1
        || !filter_var((string)($thread['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $last = strtotime((string)($thread['visitor_last_seen_at'] ?? '')) ?: 0;
    if ($last > time() - 70) return;
    db_execute(
        "INSERT IGNORE INTO iw_live_email_outbox
            (thread_id,message_id,recipient_email,status,attempt_count,
             next_attempt_at,created_at,updated_at)
         VALUES (:thread,:message,:recipient,'pending',0,NOW(),NOW(),NOW())",
        ['thread'=>(int)$thread['id'],'message'=>$messageId,
         'recipient'=>(string)$thread['email']]
    );
}

/** Durable at-least-once queue; a transport acceptance is not inbox delivery. */
function iw_chat_process_email_outbox(int $limit = 12): array
{
    $result = ['processed'=>0,'accepted'=>0,'failed'=>0,'pending'=>0];
    if (!iw_chat_engagement_ready()) return $result;
    if (!email_enabled()) return $result;
    $limit = max(1,min(25,$limit));
    db_execute("UPDATE iw_live_email_outbox
        SET status=IF(attempt_count>=5,'failed','retry'),
            next_attempt_at=NOW(), updated_at=NOW()
        WHERE status='sending' AND updated_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)");
    $ids = db_all("SELECT id FROM iw_live_email_outbox
        WHERE status IN ('pending','retry') AND next_attempt_at<=NOW()
        ORDER BY id LIMIT {$limit}");
    foreach ($ids as $item) {
        $claimed = db_query("UPDATE iw_live_email_outbox
            SET status='sending',attempt_count=attempt_count+1,updated_at=NOW()
            WHERE id=:id AND status IN ('pending','retry')",
            ['id'=>(int)$item['id']]);
        if ($claimed->rowCount() !== 1) continue;
        $entry = db_one("SELECT * FROM iw_live_email_outbox WHERE id=:id LIMIT 1",
            ['id'=>(int)$item['id']]);
        if (!$entry) continue;
        $result['processed']++;
        $message = "iWORKS Support has replied to your website conversation.\n\n"
            ."Open https://iworksgt.com/ in the same browser and select the support chat "
            ."to read the new reply. This email does not include private chat content.\n\n"
            ."You received this notification because you enabled offline reply emails.\n";
        $ok = send_email((string)$entry['recipient_email'],
            'New reply from iWORKS Support', $message);
        $attempt = max(1,(int)$entry['attempt_count']);
        $failed = !$ok && $attempt >= 5;
        db_execute("UPDATE iw_live_email_outbox
            SET status=:status,sent_at=:sent,
                next_attempt_at=:next_attempt,updated_at=NOW()
            WHERE id=:id",
            ['status'=>$ok?'sent':($failed?'failed':'retry'),
             'sent'=>$ok?now():null,
             'next_attempt'=>$ok?now():date('Y-m-d H:i:s',time()+min(3600,60*(2**min(4,$attempt-1)))),
             'id'=>(int)$entry['id']]
        );
        if ($ok) $result['accepted']++;
        if ($failed) $result['failed']++;
    }
    $result['pending']=(int)(db_one(
        "SELECT COUNT(*) AS n FROM iw_live_email_outbox WHERE status IN ('pending','retry','sending')"
    )['n']??0);
    return $result;
}

function iw_chat_dashboard_summary(): array
{
    $empty=['online'=>0,'open'=>0,'unanswered'=>0,'new_today'=>0,'agents'=>0,'activity'=>[],'recent'=>[]];
    if (!iw_chat_ready()) return $empty;
    $counts = db_one("SELECT
        SUM(CASE WHEN status='open' AND visitor_last_seen_at>=DATE_SUB(NOW(),INTERVAL 70 SECOND) THEN 1 ELSE 0 END) AS online,
        SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) AS open,
        SUM(CASE WHEN status='open' AND (last_staff_read_at IS NULL OR
          last_message_at>last_staff_read_at) THEN 1 ELSE 0 END) AS unanswered,
        SUM(CASE WHEN created_at>=CURDATE() THEN 1 ELSE 0 END) AS new_today
        FROM iw_live_threads") ?: [];
    $activity = db_all("SELECT DATE(created_at) AS day,COUNT(*) AS total
        FROM iw_live_messages
        WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)
        GROUP BY DATE(created_at) ORDER BY day ASC");
    $recent = db_all("SELECT id,name,landing_page,status,visitor_last_seen_at,
             country_code,last_message_at
        FROM iw_live_threads ORDER BY updated_at DESC LIMIT 5");
    return [
        'online'=>(int)($counts['online']??0),
        'open'=>(int)($counts['open']??0),
        'unanswered'=>(int)($counts['unanswered']??0),
        'new_today'=>(int)($counts['new_today']??0),
        'agents'=>(int)(db_one("SELECT COUNT(*) AS n FROM iw_live_staff_presence
            WHERE last_seen_at>=DATE_SUB(NOW(),INTERVAL 70 SECOND)")['n']??0),
        'activity'=>array_map(static fn($row)=>['day'=>(string)$row['day'],'total'=>(int)$row['total']],$activity),
        'recent'=>array_map(static fn($row)=>[
            'id'=>(int)$row['id'],'name'=>(string)$row['name'],
            'page'=>(string)($row['landing_page']??'/'),
            'status'=>(string)$row['status'],
            'country'=>(string)($row['country_code']??''),
            'online'=>(string)$row['status']==='open'
                && strtotime((string)($row['visitor_last_seen_at']??''))>=time()-70,
        ],$recent),
    ];
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
    // Reopening a long conversation should show the latest 120 entries,
    // rather than only its first messages from months ago.
    if ($afterId <= 0) {
        $rows = db_all('SELECT id,sender,body,created_at FROM iw_live_messages
            WHERE thread_id=:thread ORDER BY id DESC LIMIT 120',
            ['thread'=>$threadId]);
        return iw_chat_serialise_messages(array_reverse($rows));
    }
    return iw_chat_serialise_messages(db_all(
        'SELECT id,sender,body,created_at FROM iw_live_messages
         WHERE thread_id=:thread AND id>:after ORDER BY id ASC LIMIT 120',
        ['thread'=>$threadId,'after'=>max(0,$afterId)]
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
            if ((function_exists('mb_strlen') ? mb_strlen($name) : strlen($name)) < 2
                || (function_exists('mb_strlen') ? mb_strlen($name) : strlen($name)) > 150
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
            // Keep only public origin/path, never referer query parameters or tokens.
            $refRaw = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
            $refParts = parse_url($refRaw);
            $ref = is_array($refParts)
                ? substr((string)($refParts['path'] ?? '/'),0,250)
                : '/';
            $engagement = iw_chat_engagement_ready();
            $remember = $engagement && (string)($_POST['remember_chat'] ?? '') === '1';
            $notify = $engagement && (string)($_POST['notify_offline'] ?? '') === '1';
            $pageTracking = $engagement && (string)($_POST['track_page'] ?? '') === '1';
            $currentPage = $pageTracking ? iw_chat_safe_page((string)($_POST['page_path'] ?? '')) : null;
            if ($engagement) {
                db_execute(
                    "INSERT INTO iw_live_threads
                     (visitor_token_hash,name,email,ip_prefix,country_code,landing_page,
                      current_page,track_page,notify_offline,status,visitor_last_seen_at,created_at,updated_at)
                     VALUES (:token,:name,:email,:ip,:country,:landing,
                       :current_page,:track_page,:notify_offline,'open',NOW(),NOW(),NOW())",
                    ['token'=>hash('sha256',$token),'name'=>$name,'email'=>$email,
                     'ip'=>$ip,'country'=>iw_chat_country_code(),'landing'=>$ref,
                     'current_page'=>$currentPage,'track_page'=>(int)$pageTracking,
                     'notify_offline'=>(int)$notify]
                );
                iw_chat_remember_cookie($token,$remember);
            } else {
                db_execute(
                    "INSERT INTO iw_live_threads
                     (visitor_token_hash,name,email,ip_prefix,country_code,landing_page,status,visitor_last_seen_at,created_at,updated_at)
                     VALUES (:token,:name,:email,:ip,:country,:landing,'open',NOW(),NOW(),NOW())",
                    ['token'=>hash('sha256',$token),'name'=>$name,'email'=>$email,
                     'ip'=>$ip,'country'=>iw_chat_country_code(),'landing'=>$ref]
                );
            }
            $createdId = db_last_id();
            try {
                notify_users(admin_user_ids(), 'Visitor waiting in live chat',
                    $name . ' opened a website support conversation.',url('live_inbox'));
            } catch (Throwable $notificationFailure) {
                error_log('[iWorks live chat notification] '.$notificationFailure->getMessage());
            }
            iw_chat_send_json(['ok'=>true,'token'=>$token,'id'=>$createdId,
                'staff_online'=>iw_chat_staff_online(),'messages'=>[],
                'features'=>$engagement ? ['resume'=>true,'offline_email'=>true,'page_presence'=>true] : []]);
            return;
        }

        if ($action === 'resume') {
            if (!iw_chat_engagement_ready()) {
                iw_chat_send_json(['ok'=>true,'active'=>false]);
                return;
            }
            $savedToken = iw_chat_resume_cookie_token();
            $savedThread = $savedToken === '' ? null : iw_chat_public_thread($savedToken);
            if (!$savedThread || (string)$savedThread['status'] !== 'open') {
                if ($savedToken !== '') iw_chat_remember_cookie('',false);
                iw_chat_send_json(['ok'=>true,'active'=>false]);
                return;
            }
            iw_chat_touch_visitor($savedThread,$_POST);
            $savedThread = db_one('SELECT * FROM iw_live_threads WHERE id=:id',
                ['id'=>(int)$savedThread['id']]) ?: $savedThread;
            iw_chat_send_json(['ok'=>true,'active'=>true,
                'id'=>(int)$savedThread['id'],
                'name'=>(string)$savedThread['name'],
                'email'=>(string)$savedThread['email'],
                'status'=>'open','staff_online'=>iw_chat_staff_online(),
                'messages'=>iw_chat_messages((int)$savedThread['id']),
                'unread'=>iw_chat_unread_for_visitor($savedThread)]);
            return;
        }
        $token = strtolower(trim((string)($_POST['token'] ?? '')));
        // The HttpOnly remembered conversation is a fallback if the previous
        // tab-session token is no longer available after closing the browser.
        if ($token === '') $token = iw_chat_resume_cookie_token();
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
            iw_chat_touch_visitor($thread,$_POST);
            if ($action === 'send') {
                $body = trim((string)($_POST['message'] ?? ''));
                if ($body === '' || (function_exists('mb_strlen') ? mb_strlen($body) : strlen($body)) > 2000) {
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
            }
            $after = min(2147483647,max(0,(int)($_POST['after_id'] ?? 0)));
            // Reload the last-read watermark from the DB, since heartbeat
            // updates and this request can share the same connection.
            $fresh = db_one('SELECT * FROM iw_live_threads WHERE id=:id',['id'=>$threadId]) ?: $thread;
            iw_chat_send_json(['ok'=>true,'status'=>'open','staff_online'=>iw_chat_staff_online(),
                'messages'=>iw_chat_messages($threadId,$after),
                'unread'=>iw_chat_unread_for_visitor($fresh)]);
            return;
        }
        if ($action === 'end') {
            $rating = (int)($_POST['rating'] ?? 0);
            db_execute("UPDATE iw_live_threads SET status='ended',
                    rating=:rating,updated_at=NOW() WHERE id=:id",
                ['id'=>$threadId,'rating'=>($rating>=1 && $rating<=5)?$rating:null]);
            if (iw_chat_resume_cookie_token() === $token) iw_chat_remember_cookie('',false);
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
                if ($body === '' || (function_exists('mb_strlen') ? mb_strlen($body) : strlen($body)) > 2000) {
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
