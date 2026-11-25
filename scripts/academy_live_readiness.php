<?php
declare(strict_types=1);
// Read-only production readiness checks; no payment, webhook registration or settings changes.
if(PHP_SAPI!=='cli'){http_response_code(404);exit(2);}
$root=realpath($argv[1]??'');$home=realpath((string)getenv('HOME'));
if(!$root||!$home||$root!==realpath($home.'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
$pending=0;
try{
    if(academy_checkout_enabled())throw new RuntimeException('Checkout must be disabled during readiness checks.');
    echo "PASS checkout disabled\n";
    $key=academy_live_paystack_key();
    echo "PASS configured Paystack key has live prefix (not displayed)\n";
    // Harmless read of live integration timeout verifies the API credentials without charging.
    $r=gateway_json_get('https://api.paystack.co/integration/payment_session_timeout',$key);
    if(($r['status']??false)!==true)throw new RuntimeException('Live API credential verification failed.');
    echo "PASS Paystack live API authenticated; no payment initialized\n";
    // Only inspect an existing endpoint; never overwrite the company's webhook.
    // The multi-endpoint API may not be enabled on this Paystack account.
    try{
        $url='https://portal.iworksgt.com/index.php?page=payment_webhook&provider=paystack';
        $next='';$matched=null;
        for($i=0;$i<10;$i++){
            $list=gateway_json_get('https://api.paystack.co/integration/webhooks/endpoints?limit=50'.($next!==''?'&next='.rawurlencode($next):''),$key);
            foreach($list['data']??[] as $item){
                if(($item['webhook_url']??'')===$url&&($item['enabled']??false)===true){$matched=$item;break;}
            }
            if($matched)break;
            $next=(string)($list['meta']['next']??'');
            if($next==='')break;
        }
        if(!$matched)throw new RuntimeException('Existing exact webhook endpoint not found.');
        $code=(string)($matched['webhook_url_code']??'');
        if(!preg_match('/^WHK_[A-Za-z0-9_-]+$/D',$code))throw new RuntimeException('Invalid endpoint code.');
        $endpoint=gateway_json_get('https://api.paystack.co/integration/webhooks/endpoints/'.rawurlencode($code),$key);
        $events=[];
        foreach(($endpoint['data']['events']??[]) as $group){if(is_array($group))foreach($group as $event)if(is_string($event))$events[]=$event;}
        if(!in_array('charge.success',$events,true))throw new RuntimeException('Endpoint subscription not verified.');
        echo "PASS Paystack live endpoint and charge.success subscription verified\n";
        // Ping is a provider-generated non-charge event, no financial side effects.
        $ping=gateway_json_post('https://api.paystack.co/integration/webhooks/endpoints/'.rawurlencode($code).'/ping',[],$key);
        if(($ping['status']??false)!==true||($ping['data']['ok']??false)!==true||(int)($ping['data']['http_status']??0)!==200)throw new RuntimeException('Provider ping did not return HTTP 200.');
        echo "PASS Paystack itself reached the live webhook and received HTTP 200\n";
    }catch(Throwable $e){
        $pending++;
        echo "PENDING Paystack endpoint API or provider ping not verified; inspect live webhook configuration/delivery history in Paystack dashboard. No endpoint changed.\n";
    }
    unset($key);
    // Confirm the existing scheduled worker is installed and recently healthy.
    $path=dirname(mail_log_path()).'/cron-queue-status.json';
    $metric=is_file($path)?json_decode((string)file_get_contents($path),true):null;
    $last=is_array($metric)?strtotime((string)($metric['last_success_at']??'')):false;
    if(is_array($metric)&&($metric['status']??'')==='ok'&&$last!==false&&$last>=time()-1800&&array_key_exists('academy_email_processed',$metric)){
        echo "PASS recent scheduled Academy email worker run\n";
    }else{
        $pending++;echo "PENDING scheduled Academy email worker: no recent successful Academy-aware queue status found\n";
    }
    echo "Readiness pending: ".$pending."\n";
    echo "No payment made, no settings changed, no live checkout activated.\n";
    exit($pending?3:0);
}catch(Throwable $e){
    unset($key);
    fwrite(STDERR,"BLOCK live readiness check (".get_class($e)."); verify the existing live gateway configuration without sharing keys.\n");
    exit(3);
}
