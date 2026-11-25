<?php
declare(strict_types=1);
function academy_learning_text(mixed $v,int $max,bool $required=false):string{
    if(!is_string($v))throw new InvalidArgumentException('Invalid text.');
    $v=trim($v);
    if(($required&&$v==='')||mb_strlen($v)>$max)throw new InvalidArgumentException('Check the text length.');
    return $v;
}
function academy_learning_url(mixed $v):string{
    $v=academy_learning_text($v,1000);
    if($v==='')return '';
    $p=parse_url($v);
    if(!filter_var($v,FILTER_VALIDATE_URL)||!is_array($p)||strtolower($p['scheme']??'')!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass'])||preg_match('/[\x00-\x20\x7f]/',$v))throw new InvalidArgumentException('Enter a valid HTTPS link.');
    return $v;
}
function academy_learning_position(mixed $v):int{
    $n=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99999]]);
    if($n===false)throw new InvalidArgumentException('Order must be between 1 and 99999.');
    return $n;
}
function academy_learning_save(int $course,string $mode,array $data):void{
    if(!db_one('SELECT id FROM academy_courses WHERE id=:id',['id'=>$course]))throw new InvalidArgumentException('Select a valid course.');
    $published=($data['published']??'')==='1'?1:0;
    if($mode==='course'){
        $desc=academy_learning_text($data['description']??'',10000);
        db_execute('INSERT INTO academy_learning_courses(course_id,description,published,updated_at) VALUES(:id,:description,:published,:updated) ON DUPLICATE KEY UPDATE description=VALUES(description),published=VALUES(published),updated_at=VALUES(updated_at)',['id'=>$course,'description'=>$desc,'published'=>$published,'updated'=>now()]);
        return;
    }
    $title=academy_learning_text($data['title']??'',160,true);
    $position=academy_learning_position($data['position']??'10');
    $id=(int)($data['id']??0);
    if($mode==='module'){
        if($id>0){
            if(!db_one('SELECT id FROM academy_modules WHERE id=:id AND course_id=:course',['id'=>$id,'course'=>$course]))throw new InvalidArgumentException('Module unavailable.');
            db_execute('UPDATE academy_modules SET title=:title,position=:position,published=:published WHERE id=:id AND course_id=:course',compact('title','position','published','id','course'));
        }else db_execute('INSERT INTO academy_modules(course_id,title,position,published) VALUES(:course,:title,:position,:published)',compact('course','title','position','published'));
        return;
    }
    if($mode!=='lesson')throw new InvalidArgumentException('Unknown learning action.');
    $module=(int)($data['module']??0);
    if(!db_one('SELECT id FROM academy_modules WHERE id=:id AND course_id=:course',['id'=>$module,'course'=>$course]))throw new InvalidArgumentException('Module unavailable.');
    $body=academy_learning_text($data['body']??'',50000);
    $video=academy_learning_url($data['video_url']??'');
    $material=academy_learning_url($data['material_url']??'');
    if($published&&$body===''&&$video===''&&$material==='')throw new InvalidArgumentException('Add content before publishing a lesson.');
    $params=compact('module','title','position','published','body','video','material');
    if($id>0){
        if(!db_one('SELECT l.id FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id WHERE l.id=:id AND m.course_id=:course',['id'=>$id,'course'=>$course]))throw new InvalidArgumentException('Lesson unavailable.');
        // Keep the original module to preserve student progress and course ownership.
        db_execute('UPDATE academy_lessons SET title=:title,position=:position,published=:published,body=:body,video_url=:video,material_url=:material WHERE id=:id AND module_id=:module',$params+['id'=>$id]);
    }else db_execute('INSERT INTO academy_lessons(module_id,title,position,published,body,video_url,material_url) VALUES(:module,:title,:position,:published,:body,:video,:material)',$params);
}
function academy_learning_access(int $student,int $course):?array{
    return db_one("SELECT c.id,c.title,en.status,lc.description,COALESCE(lc.published,0) published FROM academy_enrollments en JOIN academy_courses c ON c.id=en.course_id LEFT JOIN academy_learning_courses lc ON lc.course_id=c.id JOIN users u ON u.id=en.student_id WHERE en.student_id=:student AND en.course_id=:course AND en.status IN ('enrolled','completed') AND c.active=1 AND u.role='student' AND u.status='active' AND u.account_state='active' AND u.deleted_at IS NULL AND u.email_verified_at IS NOT NULL",compact('student','course'));
}
function academy_learning_lessons(int $student,int $course):array{
    if(!(academy_learning_access($student,$course)['published']??false))return [];
    return db_all("SELECT l.id,l.title,m.title module_title,m.id module_id,CASE WHEN p.lesson_id IS NULL THEN 0 ELSE 1 END completed FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id JOIN academy_learning_courses lc ON lc.course_id=m.course_id AND lc.published=1 LEFT JOIN academy_lesson_progress p ON p.lesson_id=l.id AND p.student_id=:student WHERE m.course_id=:course AND m.published=1 AND l.published=1 ORDER BY m.position,m.id,l.position,l.id",compact('student','course'));
}
function academy_learning_lesson(int $student,int $course,int $lesson):?array{
    if(!(academy_learning_access($student,$course)['published']??false))return null;
    return db_one("SELECT l.*,m.title module_title FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id JOIN academy_learning_courses lc ON lc.course_id=m.course_id AND lc.published=1 WHERE m.course_id=:course AND l.id=:lesson AND m.published=1 AND l.published=1",compact('course','lesson'));
}
function academy_learning_complete(int $student,int $course,int $lesson):bool{
    // Atomic guarded insert: no cross-course, draft, or unapproved progress writes.
    $count=db_query("INSERT IGNORE INTO academy_lesson_progress(student_id,lesson_id,completed_at) SELECT en.student_id,l.id,:completed FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id JOIN academy_learning_courses lc ON lc.course_id=m.course_id JOIN academy_courses c ON c.id=m.course_id JOIN academy_enrollments en ON en.course_id=c.id JOIN users u ON u.id=en.student_id WHERE l.id=:lesson AND c.id=:course AND en.student_id=:student AND en.status IN ('enrolled','completed') AND c.active=1 AND lc.published=1 AND m.published=1 AND l.published=1 AND u.role='student' AND u.status='active' AND u.account_state='active' AND u.deleted_at IS NULL AND u.email_verified_at IS NOT NULL",['completed'=>now(),'lesson'=>$lesson,'course'=>$course,'student'=>$student])->rowCount();
    return $count===1;
}
