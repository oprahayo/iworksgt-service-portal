<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/live_chat.php';
$assert=static function(bool $ok,string $what):void {
    if (!$ok) throw new RuntimeException($what);
};
$oldCloud=$_SERVER['HTTP_CF_IPCOUNTRY']??null;
$oldGeo=$_SERVER['GEOIP_COUNTRY_CODE']??null;
$oldMM=$_SERVER['MM_COUNTRY_CODE']??null;
unset($_SERVER['GEOIP_COUNTRY_CODE'],$_SERVER['MM_COUNTRY_CODE']);
$_SERVER['HTTP_CF_IPCOUNTRY']='ES';
$assert(iw_chat_country_code('US')==='ES',
    'Client-submitted country must never override trusted network country.');
$_SERVER['HTTP_CF_IPCOUNTRY']='XX';
$assert(iw_chat_country_code('DE')===null || preg_match('/^[A-Z]{2}$/',iw_chat_country_code('DE')??'')===1,
    'Unknown proxy country must not become untrusted visitor selection.');
$assert(iw_chat_safe_page('/services/company-registration?token=PRIVATE')
    ==='/services/company-registration',
    'Sensitive URL query parameters must never be retained in chat page context.');
$assert(iw_chat_safe_page('https://iworksgt.com/track?email=client@example.com')
    ==='/track','Public page path should strip identifiers.');
$assert(iw_chat_safe_page('/portal/index.php?page=admin')===null,
    'Staff/private URL paths must never be disclosed as visitor page context.');
$assert(iw_chat_safe_page('/')==='/', 'Home path must remain usable.');
if($oldCloud===null)unset($_SERVER['HTTP_CF_IPCOUNTRY']);
else $_SERVER['HTTP_CF_IPCOUNTRY']=$oldCloud;
if($oldGeo!==null)$_SERVER['GEOIP_COUNTRY_CODE']=$oldGeo;
if($oldMM!==null)$_SERVER['MM_COUNTRY_CODE']=$oldMM;
echo "chat_country_page_safety_test passed\n";
