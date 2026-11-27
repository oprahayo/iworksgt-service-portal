<?php
declare(strict_types=1);

function iw_website_inbox_access(array $user):void {
    if (!in_array((string)($user['role']??''),['admin','agent'],true) || (int)($user['id']??0)<1) throw new RuntimeException('Inbox access denied.');
}
function iw_website_inbox_filters(array $input):array {
    $status=is_string($input['status']??null)?$input['status']:'';
    $q=is_string($input['q']??null)?mb_substr(trim($input['q']),0,120):'';
    $p=is_scalar($input['p']??null)?max(1,min(1000000,(int)$input['p'])):1;
    return ['status'=>in_array($status,['new','reviewed','closed'],true)?$status:'','q'=>$q,'p'=>$p];
}
function iw_website_inbox_list(array $user,array $input):array {
    iw_website_inbox_access($user);$f=iw_website_inbox_filters($input);
    $where=' WHERE 1=1';$params=[];
    if($f['status']!==''){$where.=' AND status=:status';$params['status']=$f['status'];}
    if($f['q']!==''){
        $terms=['full_name','email','phone','company_name','message','page_slug'];$clauses=[];
        foreach($terms as $i=>$column){$key='q'.$i;$clauses[]="$column LIKE :$key";$params[$key]='%'.$f['q'].'%';}
        $where.=' AND ('.implode(' OR ',$clauses).')';
    }
    $count=(int)(db_one('SELECT COUNT(*) n FROM website_leads'.$where,$params)['n']??0);
    $pages=max(1,(int)ceil($count/25));$page=min($f['p'],$pages);$offset=($page-1)*25;
    $rows=db_all('SELECT id,full_name,email,phone,company_name,LEFT(message,180) preview,page_slug,status,created_at FROM website_leads'.$where.' ORDER BY created_at DESC,id DESC LIMIT 25 OFFSET '.$offset,$params);
    $counts=['new'=>0,'reviewed'=>0,'closed'=>0];
    foreach(db_all('SELECT status,COUNT(*) n FROM website_leads GROUP BY status') as $r)if(isset($counts[$r['status']]))$counts[$r['status']]=(int)$r['n'];
    return ['rows'=>$rows,'counts'=>$counts,'total'=>$count,'page'=>$page,'pages'=>$pages,'filters'=>$f];
}
function iw_website_inbox_get(array $user,int $id):?array {
    iw_website_inbox_access($user);
    return $id>0?db_one('SELECT * FROM website_leads WHERE id=:id',['id'=>$id]):null;
}
function iw_website_inbox_update(array $user,int $id,string $status):void {
    iw_website_inbox_access($user);
    if(!in_array($status,['new','reviewed','closed'],true)||!iw_website_inbox_get($user,$id))throw new InvalidArgumentException('Select a valid enquiry and status.');
    db_execute('UPDATE website_leads SET status=:status,updated_at=:at WHERE id=:id',['status'=>$status,'at'=>now(),'id'=>$id]);
}
