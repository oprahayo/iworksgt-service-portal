<?php
declare(strict_types=1);
// Simulated gateway/DB only; does not call Paystack or issue live certificates.
final class AcademyFakePdo extends PDO {
 private bool $active=false; private array $snapshot=[];
 public function __construct(){}
 public function beginTransaction():bool {$this->snapshot=$GLOBALS['cert'];$this->active=true;return true;}
 public function commit():bool {$this->active=false;return true;}
 public function rollBack():bool {$GLOBALS['cert']=$this->snapshot;$this->active=false;return true;}
 public function inTransaction():bool{return $this->active;}
}
$GLOBALS['pdo']=new AcademyFakePdo();
$GLOBALS['ref']='IWGA-'.str_repeat('a',32);
$GLOBALS['cert']=['id'=>1,'student_id'=>10,'payment_reference'=>$GLOBALS['ref'],'amount_minor'=>10200000,'status'=>'pending'];
$GLOBALS['verified']=['status'=>true,'data'=>['id'=>12345,'status'=>'success','reference'=>$GLOBALS['ref'],'currency'=>'NGN','amount'=>10200000]];
$GLOBALS['writes']=0;$GLOBALS['verifyCalls']=0;$GLOBALS['fail']=false;
function db():PDO{return $GLOBALS['pdo'];}
function now():string{return '2026-11-25 12:00:00';}
function get_payment_method_by_code(string $code):array{return ['is_active'=>1,'secret_key'=>'fixture-not-real'];}
function gateway_json_get(string $url,string $secret):array{$GLOBALS['verifyCalls']++;return $GLOBALS['verified'];}
function db_one(string $sql,array $p=[]):?array{
 if(isset($p['ref'])&&$p['ref']!==$GLOBALS['ref'])return null;
 if(isset($p['student'])&&($p['student']!==10||$GLOBALS['cert']['status']!=='issued'))return null;
 return $GLOBALS['cert'];
}
function db_execute(string $sql,array $p=[]):bool{
 if($GLOBALS['fail'])throw new RuntimeException('fixture failure');
 $GLOBALS['cert']['status']='issued';$GLOBALS['cert']['provider_transaction']=$p['tx'];$GLOBALS['writes']++;return true;
}
require __DIR__.'/../app/academy.php';
function expect(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
$good=$GLOBALS['verified'];
foreach(['amount'=>10199999,'currency'=>'USD','reference'=>'wrong','status'=>'failed','id'=>'','amount'=>'10200000'] as $key=>$value){
 $GLOBALS['verified']=$good;$GLOBALS['verified']['data'][$key]=$value;
 expect(!academy_confirm($GLOBALS['ref']),'Reject mismatched '.$key);
 expect($GLOBALS['cert']['status']==='pending'&&$GLOBALS['writes']===0,'No issuance');
}
$GLOBALS['verified']=$good;$GLOBALS['fail']=true;
try {academy_confirm($GLOBALS['ref']);throw new LogicException('Expected rollback');}
catch(RuntimeException $e){expect($e->getMessage()==='fixture failure','Correct failure');}
expect(!db()->inTransaction()&&$GLOBALS['cert']['status']==='pending','Atomic rollback');
$GLOBALS['fail']=false;
expect(academy_confirm($GLOBALS['ref']),'Verified issue');
expect($GLOBALS['writes']===1,'Exactly one issuance');
expect(academy_confirm($GLOBALS['ref']),'Replay succeeds');
expect($GLOBALS['writes']===1,'Replay does not issue again');
expect(academy_owned_certificate(1,11)===null,'Reject cross-student download');
expect(academy_owned_certificate(1,10)!==null,'Allow owner download');
$GLOBALS['cert']['status']='revoked';
expect(!academy_confirm($GLOBALS['ref']),'No reissue after revocation');
expect(academy_owned_certificate(1,10)===null,'No revoked download');
echo "Academy simulated payment lifecycle passed.\n";
