<?php
declare(strict_types=1);
require __DIR__.'/../app/auth.php';
function now():string{return date('Y-m-d H:i:s');}
function db_execute(string $sql,array $p=[]):bool{
 if(str_contains($sql,'INSERT INTO trusted_devices'))$GLOBALS['devices'][]=$p+['id'=>count($GLOBALS['devices']??[])+1];
 return true;
}
function db_one(string $sql,array $p=[]):?array{
 foreach($GLOBALS['devices']??[] as $r)if($r['user_id']===$p['user_id']&&$r['token_hash']===$p['token_hash'])return $r;
 return null;
}
function expect(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
$_SERVER['HTTP_HOST']='portal.iworksgt.com';
remember_trusted_device(11);remember_trusted_device(22);
expect(has_valid_trusted_device(11)&&has_valid_trusted_device(22),'Separate accounts retain trust');
expect(!has_valid_trusted_device(33),'Trust never crosses accounts');
expect(abs(strtotime($GLOBALS['devices'][0]['expires_at'])-time()-30*86400)<3,'Fixed 30 day expiry');
$token=$_COOKIE[trusted_device_cookie_name(11)];
expect($GLOBALS['devices'][0]['token_hash']===hash('sha256',$token),'Only hashed token stored');
unset($_COOKIE[trusted_device_cookie_name(11)]);
$_COOKIE[trusted_device_cookie_name()]=$token;
expect(has_valid_trusted_device(11)&&isset($_COOKIE[trusted_device_cookie_name(11)]),'Legacy cookie migrates with original expiry');
expect(has_valid_trusted_device(22),'Legacy migration cannot overwrite other account');
$GLOBALS['devices'][0]['expires_at']=date('Y-m-d H:i:s',time()-1);
expect(!has_valid_trusted_device(11),'Expired trust denied');
$GLOBALS['devices']=[];
expect(!has_valid_trusted_device(22),'Revoked trust denied');
expect(otp_recovery_cookie_secure(),'Production HTTPS-only cookie policy');
echo "PASS 30-day trusted device, account isolation, legacy migration, expiry and revocation.\n";
