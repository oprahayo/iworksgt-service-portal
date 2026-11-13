<?php
declare(strict_types=1);
final class OutboxFakePDO extends PDO
{
    private bool $active=false;
    public function __construct() {}
    public function beginTransaction(): bool {$this->active=true;return true;}
    public function commit(): bool {$this->active=false;return true;}
    public function rollBack(): bool {$this->active=false;return true;}
    public function inTransaction(): bool {return $this->active;}
}
final class OutboxClaim {
    public function __construct(private int $count) {}
    public function rowCount(): int {return $this->count;}
}
$GLOBALS['fakePdo']=new OutboxFakePDO();
$GLOBALS['notifications']=[];
$GLOBALS['outbox']=[];
$GLOBALS['nextNotificationId']=200;
$GLOBALS['mailOn']=false;
$GLOBALS['mailSuccess']=false;
$GLOBALS['mailCalls']=0;
function db(): PDO {return $GLOBALS['fakePdo'];}
function now(): string {return '2026-11-13 13:00:00';}
function db_last_id(): int {return $GLOBALS['nextNotificationId'];}
function email_notifications_enabled(): bool {return true;}
function email_enabled(): bool {return $GLOBALS['mailOn'];}
function portal_absolute_url(string $path): string {return 'https://portal.iworksgt.com/'.$path;}
function find_user_by_id(int $id): ?array {return ['id'=>$id,'email'=>'synthetic@example.invalid'];}
function iworks_send_html_email(string $to,string $subject,string $plain,string $html): bool {
    $GLOBALS['mailCalls']++;
    return $GLOBALS['mailSuccess'];
}
function send_email(string $to,string $subject,string $message): bool {
    $GLOBALS['mailCalls']++;
    return $GLOBALS['mailSuccess'];
}
function db_execute(string $sql,array $params=[]): bool {
    if (str_contains($sql,'INSERT INTO notifications')) {
        $GLOBALS['notifications'][]=$params;
    } elseif (str_contains($sql,'INSERT INTO notification_email_outbox')) {
        $id=$params['notification_id'];
        if (!isset($GLOBALS['outbox'][$id])) {
            $GLOBALS['outbox'][$id]=$params+[
                'id'=>$id,'status'=>'pending','attempt_count'=>0,'max_attempts'=>5,
                'sent_at'=>null,'last_error_code'=>null
            ];
        }
    } elseif (str_contains($sql,'UPDATE notification_email_outbox')) {
        if (isset($params['status'])) {
            $id=$params['id'];
            $GLOBALS['outbox'][$id]['status']=$params['status'];
            $GLOBALS['outbox'][$id]['next_attempt_at']=$params['next_attempt_at'];
            $GLOBALS['outbox'][$id]['sent_at']=$params['sent_at'];
        }
    }
    return true;
}
function db_one(string $sql,array $params=[]): ?array {
    if (str_contains($sql,'FROM notification_email_outbox WHERE id')) {
        return $GLOBALS['outbox'][$params['id']]??null;
    }
    if (str_contains($sql,'COUNT(*)') && str_contains($sql,'notification_email_outbox')) {
        $pending=count(array_filter($GLOBALS['outbox'],
            static fn(array $entry):bool => in_array($entry['status'],['pending','retry','sending'],true)));
        return ['n'=>$pending];
    }
    return null;
}
function db_all(string $sql,array $params=[]): array {
    if (str_contains($sql,'FROM notification_email_outbox')) {
        return array_map(static fn($item)=>['id'=>$item['id']],array_filter(
            $GLOBALS['outbox'],
            static fn(array $item)=>in_array($item['status'],['pending','retry'],true)
                && $item['next_attempt_at']<='2026-11-13 13:00:00'
        ));
    }
    return [];
}
function db_query(string $sql,array $params=[]): OutboxClaim {
    $id=$params['id'];
    $entry=$GLOBALS['outbox'][$id]??null;
    if (!$entry || !in_array($entry['status'],['pending','retry'],true)) return new OutboxClaim(0);
    $GLOBALS['outbox'][$id]['status']='sending';
    $GLOBALS['outbox'][$id]['attempt_count']++;
    return new OutboxClaim(1);
}
require __DIR__.'/../app/notification_outbox.php';
require __DIR__.'/../app/domain.php';
function test_outbox(bool $pass,string $message): void {if (!$pass)throw new RuntimeException($message);}
create_notification(1,'Support ticket update','Review your support ticket','index.php?page=support',[
    'subject'=>'You have a new reply | iWORKSGLOBAL',
    'plain'=>'View and reply in your dashboard',
    'html'=>'<html><body><a href="https://portal.iworksgt.com">View and reply</a></body></html>',
]);
test_outbox(count($GLOBALS['notifications'])===1 && count($GLOBALS['outbox'])===1,
    'One dashboard event must create one durable email record.');
test_outbox($GLOBALS['mailCalls']===0,
    'No synchronous email should leave the transaction before commit.');
$entry=$GLOBALS['outbox'][200];
test_outbox(str_contains($entry['html_body'],'View and reply'),
    'Approved branded HTML must be preserved in outbox.');
iworks_enqueue_notification_email(200,1,'synthetic@example.invalid','duplicate','body');
test_outbox(count($GLOBALS['outbox'])===1,'Same notification may never enqueue twice.');
$disabled=iworks_process_notification_email_outbox();
test_outbox($disabled['pending']===1 && $GLOBALS['mailCalls']===0,
    'Disabled email transport must preserve pending notification without burning retries.');
$GLOBALS['mailOn']=true;
$failed=iworks_process_notification_email_outbox();
test_outbox($failed['processed']===1 && $failed['accepted']===0
    && $GLOBALS['outbox'][200]['status']==='retry','Failed transport must retry.');
$GLOBALS['mailSuccess']=true;
$GLOBALS['outbox'][200]['next_attempt_at']='2026-11-13 12:59:00';
$sent=iworks_process_notification_email_outbox();
test_outbox($sent['accepted']===1 && $GLOBALS['outbox'][200]['status']==='sent'
    && $GLOBALS['mailCalls']===2,'Retried notification must be submitted once after transport recovery.');
echo "f06_notification_outbox_test passed\n";
