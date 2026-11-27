<?php
declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn($p)=>file_get_contents($root.'/'.$p);
$assert=static function(bool $ok,string $m){if(!$ok)throw new RuntimeException($m);};
$index=$read('index.php');$domain=$read('app/domain.php');$editor=$read('views/frontend-editor.php');$header=$read('views/partials/header.php');
$assert(str_contains($domain,"url('website_inbox', ['lead_id' => \$leadId])"),'New notifications must route to exact enquiry');
$assert(str_contains($index,"case 'website_inbox':")&&str_contains($index,"=== 'New website quote/enquiry'")&&str_contains($index,"redirect(url('website_inbox'));"),'Old notification routes must be recovered');
$assert(str_contains($index,"iw_website_inbox_update(\$user,\$leadId")&&str_contains($index,"require_roles(['admin','agent']);"),'Staff route and status mutation required');
$assert(!str_contains($editor,'Recent Website Enquiries')&&str_contains($editor,'iw-editor-block-fields')&&str_contains($editor,'Publication history'),'Editor must be content only and grouped');
$assert(str_contains($header,"'label' => 'Communications'"),'Shared inbox navigation required');
foreach(['views/live-inbox.php','views/messages.php','views/support.php','views/website-inbox.php'] as $p)$assert(str_contains($read($p),'communications-nav.php'),'Missing shared navigation '.$p);
echo "PASS communications routing and editor separation.\n";
