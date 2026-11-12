<?php
declare(strict_types=1);

/** Editorial content is deliberately separate from jobs, invoices and customer uploads. */
function iworks_editorial_ready(string $table): bool {
    return schema_table_exists($table);
}
function iworks_editorial_media(int $id): string {
    if ($id<1) return '';
    $asset=get_media_asset($id);
    return trim((string)($asset['url']??''));
}
function iworks_editorial_slug(string $title,int $id=0): string {
    $base=substr(slugify($title),0,165);
    if($base==='')throw new RuntimeException('Provide a descriptive title.');
    $try=$base;$n=2;
    while(($row=db_one('SELECT id FROM iworks_case_studies WHERE slug=:slug LIMIT 1',['slug'=>$try]))
       && (int)$row['id']!==$id) {
        $try=substr($base,0,158).'-'.$n++;
        if($n>100)throw new RuntimeException('Unable to create a unique case-study URL.');
    }
    return $try;
}
function iworks_editorial_cases(bool $published=true,int $limit=24):array {
    if(!iworks_editorial_ready('iworks_case_studies'))return [];
    $sql='SELECT * FROM iworks_case_studies';
    if($published)$sql.=" WHERE status='published'";
    $sql.=' ORDER BY updated_at DESC,id DESC LIMIT '.max(1,min(100,$limit));
    $rows=db_all($sql);
    return array_map(static function(array $row):array {
        $row['image_url']=iworks_editorial_media((int)($row['image_id']??0));
        $row['logo_url']=iworks_editorial_media((int)($row['logo_id']??0));
        return $row;
    },$rows);
}
function iworks_editorial_case(int $id):?array {
    if($id<1||!iworks_editorial_ready('iworks_case_studies'))return null;
    $row=db_one('SELECT * FROM iworks_case_studies WHERE id=:id LIMIT 1',['id'=>$id]);
    return $row?:null;
}
function iworks_editorial_case_slug(string $slug):?array {
    if(!iworks_editorial_ready('iworks_case_studies'))return null;
    $row=db_one("SELECT * FROM iworks_case_studies WHERE slug=:slug AND status='published' LIMIT 1",['slug'=>$slug]);
    if(!$row)return null;
    $row['image_url']=iworks_editorial_media((int)($row['image_id']??0));
    $row['logo_url']=iworks_editorial_media((int)($row['logo_id']??0));
    return $row;
}
function iworks_editorial_file_id(array $files,string $field,int $adminId,int $existing=0):int {
    $file=$files[$field]??null;
    if(!is_array($file)||(int)($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return $existing;
    return store_media_asset($adminId,$file);
}
function iworks_editorial_save_case(array $input,array $files,int $adminId):int {
    if(!iworks_editorial_ready('iworks_case_studies'))throw new RuntimeException('Case studies module is not installed.');
    $id=(int)($input['id']??0);$old=$id>0?iworks_editorial_case($id):null;
    if($id>0&&!$old)throw new RuntimeException('Case study not found.');
    $title=mb_safe_trim(trim((string)($input['title']??'')),240);
    $body=sanitize_limited_html((string)($input['story_html']??''));
    if($title===''||trim(strip_tags($body))==='')throw new RuntimeException('Provide a title and a case-study narrative.');
    $status=(string)($input['status']??'draft')==='published'?'published':'draft';
    $slug=iworks_editorial_slug(trim((string)($input['slug']??''))?:$title,$id);
    $fields=[
        'slug'=>$slug,'title'=>$title,
        'client'=>mb_safe_trim(trim((string)($input['client_name']??'')),190),
        'category'=>mb_safe_trim(trim((string)($input['category']??'')),100),
        'summary'=>mb_safe_trim(trim((string)($input['summary']??'')),1200),
        'story'=>$body,'outcome'=>mb_safe_trim(trim((string)($input['outcome']??'')),240),
        'image'=>iworks_editorial_file_id($files,'cover_upload',$adminId,(int)($old['image_id']??0)),
        'logo'=>iworks_editorial_file_id($files,'logo_upload',$adminId,(int)($old['logo_id']??0)),
        'status'=>$status,'updated'=>now()
    ];
    if($id>0){
        $fields['id']=$id;
        db_execute('UPDATE iworks_case_studies SET slug=:slug,title=:title,client_name=:client,
        category=:category,summary=:summary,story_html=:story,outcome=:outcome,image_id=:image,
        logo_id=:logo,status=:status,updated_at=:updated WHERE id=:id',$fields);
        return $id;
    }
    $fields['author']=$adminId;$fields['created']=now();
    db_execute('INSERT INTO iworks_case_studies (slug,title,client_name,category,summary,
    story_html,outcome,image_id,logo_id,status,author_id,created_at,updated_at)
    VALUES (:slug,:title,:client,:category,:summary,:story,:outcome,:image,:logo,:status,:author,:created,:updated)',$fields);
    return db_last_id();
}
function iworks_editorial_delete_case(int $id):void {
    if($id>0)db_execute('DELETE FROM iworks_case_studies WHERE id=:id',['id'=>$id]);
}
function iworks_editorial_reviews(bool $published=true,int $limit=16):array {
    if(!iworks_editorial_ready('iworks_testimonials'))return [];
    $sql='SELECT * FROM iworks_testimonials';
    if($published)$sql.=" WHERE status='published' AND consent_confirmed=1";
    $sql.=' ORDER BY sort_order ASC,id DESC LIMIT '.max(1,min($limit,100));
    return array_map(static function(array $row):array {
        $row['logo_url']=iworks_editorial_media((int)($row['company_logo_id']??0));
        $row['portrait_url']=iworks_editorial_media((int)($row['portrait_id']??0));
        return $row;
    },db_all($sql));
}
function iworks_editorial_review(int $id):?array {
    if($id<1||!iworks_editorial_ready('iworks_testimonials'))return null;
    return db_one('SELECT * FROM iworks_testimonials WHERE id=:id LIMIT 1',['id'=>$id]);
}
function iworks_editorial_save_review(array $input,array $files,int $adminId):int {
    if(!iworks_editorial_ready('iworks_testimonials'))throw new RuntimeException('Feedback module is not installed.');
    $id=(int)($input['id']??0);$old=$id>0?iworks_editorial_review($id):null;
    if($id>0&&!$old)throw new RuntimeException('Feedback not found.');
    $name=mb_safe_trim(trim((string)($input['customer_name']??'')),190);
    $quote=mb_safe_trim(trim((string)($input['quote_text']??'')),1800);
    if($name===''||$quote==='')throw new RuntimeException('Customer name and genuine feedback are required.');
    $consent=!empty($input['consent_confirmed'])?1:0;
    $status=(string)($input['status']??'draft')==='published'?'published':'draft';
    if($status==='published'&&!$consent)throw new RuntimeException('Obtain customer permission before publishing a name, logo, photo or quote.');
    $fields=[
        'name'=>$name,'position'=>mb_safe_trim(trim((string)($input['position']??'')),140),
        'company'=>mb_safe_trim(trim((string)($input['company']??'')),190),
        'quote'=>$quote,'logo'=>iworks_editorial_file_id($files,'logo_upload',$adminId,(int)($old['company_logo_id']??0)),
        'portrait'=>iworks_editorial_file_id($files,'portrait_upload',$adminId,(int)($old['portrait_id']??0)),
        'status'=>$status,'consent'=>$consent,
        'sort'=>(int)($input['sort_order']??0),'updated'=>now()
    ];
    if($id>0){
        $fields['id']=$id;
        db_execute('UPDATE iworks_testimonials SET customer_name=:name,position=:position,
        company=:company,quote_text=:quote,company_logo_id=:logo,portrait_id=:portrait,
        status=:status,consent_confirmed=:consent,sort_order=:sort,updated_at=:updated WHERE id=:id',$fields);
        return $id;
    }
    $fields['created']=now();
    db_execute('INSERT INTO iworks_testimonials (customer_name,position,company,quote_text,
    company_logo_id,portrait_id,status,consent_confirmed,sort_order,created_at,updated_at)
    VALUES (:name,:position,:company,:quote,:logo,:portrait,:status,:consent,:sort,:created,:updated)',$fields);
    return db_last_id();
}
function iworks_editorial_delete_review(int $id):void {
    if($id>0)db_execute('DELETE FROM iworks_testimonials WHERE id=:id',['id'=>$id]);
}
/** Same-origin CSRF is mandatory for public reactions and comments. */
function iworks_blog_visitor_id():string {
    return hash('sha256',session_id().'|'.(string)($_SERVER['REMOTE_ADDR']??''));
}
function iworks_blog_likes(int $postId):int {
    if($postId<1||!iworks_editorial_ready('iworks_blog_likes'))return 0;
    return (int)(db_one('SELECT COUNT(*) AS c FROM iworks_blog_likes WHERE post_id=:id',['id'=>$postId])['c']??0);
}
function iworks_blog_liked(int $postId):bool {
    if($postId<1||!iworks_editorial_ready('iworks_blog_likes'))return false;
    return !!db_one('SELECT 1 AS ok FROM iworks_blog_likes WHERE post_id=:post AND visitor_hash=:v',
        ['post'=>$postId,'v'=>iworks_blog_visitor_id()]);
}
function iworks_blog_toggle_like(int $postId):void {
    if(!iworks_editorial_ready('iworks_blog_likes'))throw new RuntimeException('Reactions are temporarily unavailable.');
    $key=iworks_blog_visitor_id();
    if(iworks_blog_liked($postId)){
        db_execute('DELETE FROM iworks_blog_likes WHERE post_id=:p AND visitor_hash=:v',['p'=>$postId,'v'=>$key]);
    }else{
        db_execute('INSERT IGNORE INTO iworks_blog_likes (post_id,visitor_hash,created_at) VALUES (:p,:v,:date)',
            ['p'=>$postId,'v'=>$key,'date'=>now()]);
    }
}
function iworks_blog_comments(int $postId,bool $approved=true):array {
    if(!iworks_editorial_ready('iworks_blog_comments'))return [];
    return db_all('SELECT * FROM iworks_blog_comments WHERE post_id=:post'
        .($approved?" AND status='approved'":'').' ORDER BY created_at DESC,id DESC LIMIT 75',['post'=>$postId]);
}
function iworks_blog_comments_queue():array {
    if(!iworks_editorial_ready('iworks_blog_comments'))return [];
    return db_all("SELECT c.*,b.title AS article_title FROM iworks_blog_comments c
      JOIN blog_posts b ON b.id=c.post_id WHERE c.status='pending'
      ORDER BY c.created_at DESC LIMIT 100");
}
function iworks_blog_submit_comment(array $input,int $postId):void {
    if(!iworks_editorial_ready('iworks_blog_comments'))throw new RuntimeException('Comments are temporarily unavailable.');
    $name=mb_safe_trim(trim((string)($input['name']??'')),120);
    $email=mb_safe_trim(strtolower(trim((string)($input['email']??''))),190);
    $comment=mb_safe_trim(trim((string)($input['comment']??'')),1300);
    if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($comment)<10)
        throw new RuntimeException('Enter your name, valid email and a comment of at least 10 characters.');
    if(trim((string)($input['website']??''))!=='')throw new RuntimeException('Unable to submit comment.');
    $visitor=iworks_blog_visitor_id();
    $recent=(int)(db_one('SELECT COUNT(*) AS c FROM iworks_blog_comments WHERE visitor_hash=:v AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
        ['v'=>$visitor])['c']??0);
    if($recent>=3)throw new RuntimeException('You have submitted several comments recently. Please try later.');
    db_execute("INSERT INTO iworks_blog_comments (post_id,display_name,email,comment_text,visitor_hash,status,created_at)
       VALUES (:post,:name,:email,:comment,:visitor,'pending',:created)",
       ['post'=>$postId,'name'=>$name,'email'=>$email,'comment'=>$comment,'visitor'=>$visitor,'created'=>now()]);
}
function iworks_blog_moderate_comment(int $id,string $action):void {
    if($action==='delete'){
        db_execute('DELETE FROM iworks_blog_comments WHERE id=:id',['id'=>$id]);
    } elseif($action==='approve'){
        db_execute("UPDATE iworks_blog_comments SET status='approved' WHERE id=:id",['id'=>$id]);
    } else throw new RuntimeException('Invalid moderation action.');
}
