<?php
declare(strict_types=1);

/**
 * Transactional email outbox for in-app notifications. Auth OTPs continue
 * to use the existing dedicated OTP delivery queue.
 */
function iworks_enqueue_notification_email(
    int $notificationId, int $userId, string $recipient,
    string $subject, string $plain, string $html = ''
): void {
    if ($notificationId <= 0 || $userId <= 0
        || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $subject = trim(str_replace(["\r","\n"], ' ', $subject));
    db_execute(
        "INSERT INTO notification_email_outbox
            (notification_id, user_id, recipient_email, subject, plain_body, html_body,
             status, attempt_count, max_attempts, next_attempt_at, created_at, updated_at)
         VALUES
            (:notification_id, :user_id, :recipient_email, :subject, :plain_body, :html_body,
             'pending', 0, 5, :next_attempt_at, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE notification_id = notification_id",
        [
            'notification_id'=>$notificationId,
            'user_id'=>$userId,
            'recipient_email'=>$recipient,
            'subject'=>$subject,
            'plain_body'=>$plain,
            'html_body'=>$html,
            'next_attempt_at'=>now(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]
    );
}

/**
 * At-least-once transport submission. A successful PHP mail() return means
 * "accepted by transport", not proof of recipient inbox delivery.
 */
function iworks_process_notification_email_outbox(int $limit = 20): array
{
    $result = ['processed'=>0,'accepted'=>0,'failed'=>0,'pending'=>0];
    $limit = max(1,min(50,$limit));
    if (!email_enabled()) {
        $result['pending'] = (int)(db_one(
            "SELECT COUNT(*) AS n FROM notification_email_outbox
             WHERE status IN ('pending','retry','sending')"
        )['n'] ?? 0);
        return $result;
    }

    $stale = date('Y-m-d H:i:s', time() - 600);
    // Reclaim crashed sending workers after ten minutes. The scheduler
    // also owns an exclusive file lock so normal runs never overlap.
    db_execute(
        "UPDATE notification_email_outbox
         SET status = CASE WHEN attempt_count >= max_attempts THEN 'failed' ELSE 'retry' END,
             next_attempt_at = :at, updated_at = :updated
         WHERE status = 'sending' AND updated_at < :stale",
        ['at'=>now(),'updated'=>now(),'stale'=>$stale]
    );
    $rows = db_all(
        "SELECT id FROM notification_email_outbox
         WHERE status IN ('pending','retry') AND next_attempt_at <= :at
         ORDER BY id ASC LIMIT {$limit}",
        ['at'=>now()]
    );

    foreach ($rows as $row) {
        $claim = db_query(
            "UPDATE notification_email_outbox SET status = 'sending',
             attempt_count = attempt_count + 1, updated_at = :at
             WHERE id = :id AND status IN ('pending','retry')",
            ['at'=>now(),'id'=>(int)$row['id']]
        );
        if ($claim->rowCount() !== 1) continue;
        $entry = db_one(
            'SELECT * FROM notification_email_outbox WHERE id = :id LIMIT 1',
            ['id'=>(int)$row['id']]
        );
        if (!$entry) continue;
        $result['processed']++;
        $ok = false;
        if (filter_var((string)$entry['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            if (trim((string)$entry['html_body']) !== '') {
                $ok = iworks_send_html_email(
                    (string)$entry['recipient_email'],
                    (string)$entry['subject'],
                    (string)$entry['plain_body'],
                    (string)$entry['html_body']
                );
            } else {
                $ok = send_email(
                    (string)$entry['recipient_email'],
                    (string)$entry['subject'],
                    (string)$entry['plain_body']
                );
            }
        }
        $attempts = (int) ($entry['attempt_count'] ?? 1);
        $final = !$ok && $attempts >= max(1,(int)($entry['max_attempts'] ?? 5));
        $state = $ok ? 'sent' : ($final ? 'failed' : 'retry');
        $backoff = min(3600, 60 * (2 ** min(5,max(0,$attempts-1))));
        db_execute(
            "UPDATE notification_email_outbox
             SET status = :status, sent_at = :sent_at,
                 next_attempt_at = :next_attempt_at, updated_at = :updated_at,
                 last_error_code = :last_error_code
             WHERE id = :id",
            [
                'status'=>$state,'sent_at'=>$ok ? now() : null,
                'next_attempt_at'=>$ok ? now() : date('Y-m-d H:i:s',time()+$backoff),
                'updated_at'=>now(),
                'last_error_code'=>$ok ? null : 'transport_unavailable',
                'id'=>(int)$entry['id'],
            ]
        );
        if ($ok) $result['accepted']++;
        if ($final) $result['failed']++;
    }
    $result['pending'] = (int) (db_one(
        "SELECT COUNT(*) AS n FROM notification_email_outbox
         WHERE status IN ('pending','retry','sending')"
    )['n'] ?? 0);
    return $result;
}
