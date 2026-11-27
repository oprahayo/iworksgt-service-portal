<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
function app_config():array{return ['db'=>['host'=>getenv('DB_HOST'),'port'=>3306,'name'=>getenv('DB_NAME'),'user'=>getenv('DB_USER'),'pass'=>getenv('DB_PASS'),'charset'=>'utf8mb4']];}
require __DIR__.'/../app/db.php';
require __DIR__.'/../app/website_inbox.php';
function now():string{return '2026-11-27 00:00:00';}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $p,array $q=[]):string{return 'index.php?'.http_build_query(['page'=>$p]+$q);}
function csrf_token():string{return 'fixture';}
function has_role(array $roles):bool{return in_array('admin',$roles,true);}
function ok(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$admin=['id'=>1,'role'=>'admin'];$agent=['id'=>2,'role'=>'agent'];$client=['id'=>3,'role'=>'client'];
db()->beginTransaction();
try{
 for($i=1;$i<=30;$i++){
  db_execute("INSERT INTO website_leads(full_name,email,phone,company_name,message,page_slug,status,created_at,updated_at) VALUES(:name,:email,'123456','Example company',:message,'contact','new',:created,:updated)",['name'=>'Inbox fixture '.$i,'email'=>'fixture'.$i.'@example.invalid','message'=>'Synthetic inbox message '.$i.' <script>alert(1)</script>','created'=>now(),'updated'=>now()]);
 }
 $leadId=db_last_id();
 $inboxQuery=iw_website_inbox_filters(['q'=>'Inbox fixture']);
 $inbox=iw_website_inbox_list($admin,$inboxQuery);
 ok($inbox['total']===30&&count($inbox['rows'])===25&&$inbox['pages']===2,'Enquiry history paginated');
 ok(count(iw_website_inbox_list($admin,['q'=>'Inbox fixture','p'=>2])['rows'])===5,'Older enquiries reachable');
 ok(iw_website_inbox_list($agent,['q'=>'Synthetic inbox message 30'])['total']===1,'Staff search full message');
 $denied=false;try{iw_website_inbox_list($client,[]);}catch(RuntimeException $e){$denied=true;}ok($denied,'Client inbox denied');
 $denied=false;try{iw_website_inbox_update($client,$leadId,'closed');}catch(RuntimeException $e){$denied=true;}ok($denied,'Client status write denied');
 $denied=false;try{iw_website_inbox_update($admin,$leadId,'invalid');}catch(InvalidArgumentException $e){$denied=true;}ok($denied,'Invalid status denied');
 iw_website_inbox_update($admin,$leadId,'reviewed');
 ok(iw_website_inbox_list($admin,['q'=>'Inbox fixture','status'=>'reviewed'])['total']===1,'Status persisted and filter works');
 $selectedLead=iw_website_inbox_get($admin,$leadId);
 ok(str_contains($selectedLead['message'],'<script>alert(1)</script>'),'Original message retained');
 ok(iw_website_inbox_get($admin,0)===null,'Invalid lead absent');
 $_GET=['page'=>'website_inbox'];
 ob_start();require __DIR__.'/../views/website-inbox.php';$html=ob_get_clean();
 ok(str_contains($html,'Original message')&&str_contains($html,'&lt;script&gt;alert(1)&lt;/script&gt;')&&!str_contains($html,'<script>alert(1)</script>'),'Message escaped in detail');
 ok(str_contains($html,'mailto:fixture30@example.invalid')&&str_contains($html,'name="csrf_token"')&&str_contains($html,'Support tickets'),'Reply and status controls available');
 $dir=__DIR__.'/../docs/visual-qa/communications';if(!is_dir($dir))mkdir($dir,0755,true);
 $wrap=static fn($body)=>'<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../../../assets/css/app.css"><link rel="stylesheet" href="../../../assets/css/communications-workspace.css"><style>body{font:14px system-ui;margin:0;background:#f1f4f8}main{padding:12px;max-width:1050px;margin:auto}.btn,.form-control,.form-select{box-sizing:border-box;max-width:100%;font:inherit}</style></head><body><main>'.$body.'</main></body></html>';
 file_put_contents($dir.'/detail.html',$wrap($html));
 $selectedLead=null;$leadId=0;ob_start();require __DIR__.'/../views/website-inbox.php';$html=ob_get_clean();file_put_contents($dir.'/list.html',$wrap($html));
 echo "PASS enquiries pagination, search, status, roles, escaping and rendering.\n";
}finally{db()->rollBack();}
