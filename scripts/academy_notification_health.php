<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$root=realpath($argv[1]??'');
if(!$root||$root!==realpath(getenv('HOME').'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
try{
 echo 'Certificate checkout: '.(academy_checkout_enabled()?'ENABLED':'DISABLED')."\n";
 echo 'Notification emails: '.(email_notifications_enabled()?'enabled':'disabled')."; mail transport: ".(email_enabled()?'enabled':'disabled')."\n";
 $rows=db_all("SELECT u.role,n.title,COUNT(*) total,SUM(n.is_read=0) unread FROM notifications n JOIN users u ON u.id=n.user_id WHERE n.target_url LIKE '%academy.php%' AND u.role IN ('student','admin') GROUP BY u.role,n.title ORDER BY u.role,n.title");
 // Titles for Academy events are fixed; do not expose messages, account IDs or addresses.
 $allowed=['Course application received','Enrollment accepted.','Enrollment cancelled.','Training approved','Certificate issued','Academy update','Student inquiry','Certification request received','New certification request','Certification approved','Certification declined'];
 $totals=['student'=>0,'admin'=>0];
 foreach($rows as $r){$totals[$r['role']]+=(int)$r['total'];if(in_array($r['title'],$allowed,true))echo $r['role'].' | '.$r['title'].' | total='.$r['total'].' unread='.$r['unread']."\n";}
 foreach($totals as $role=>$n)echo 'Stored Academy notifications for '.$role.': '.$n."\n";
 $out=db_all("SELECT o.status,COUNT(*) total FROM notification_email_outbox o JOIN notifications n ON n.id=o.notification_id WHERE n.target_url LIKE '%academy.php%' GROUP BY o.status");
 foreach($out as $r)echo 'Academy email outbox '.$r['status'].': '.$r['total']."\n";
 $p=dirname(mail_log_path()).'/cron-queue-status.json';$m=is_file($p)?json_decode((string)file_get_contents($p),true):null;
 echo 'Scheduled notification worker: '.(is_array($m)&&($m['status']??'')==='ok'&&strtotime((string)($m['last_success_at']??''))>=time()-1800&&array_key_exists('notification_processed',$m)?'recent successful run':'NOT VERIFIED / stale')."\n";
 echo "Read-only notification audit complete. Sent means transport acceptance, not inbox receipt. No messages sent or records changed.\n";
}catch(Throwable $e){fwrite(STDERR,"Notification audit unavailable (".get_class($e).").\n");exit(3);}
