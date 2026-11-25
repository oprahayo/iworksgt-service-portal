<?php
declare(strict_types=1);
function academy_lesson_in_course(int $course,int $lesson):bool{
 return db_one('SELECT l.id FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id WHERE l.id=:lesson AND m.course_id=:course',compact('lesson','course'))!==null;
}
function academy_video_embed(string $url):?string{
 $p=parse_url($url);if(!is_array($p)||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port']))return null;
 $host=strtolower($p['host']??'');$path=$p['path']??'';$id='';
 if($host==='youtu.be')$id=ltrim($path,'/');
 elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com'],true)&&$path==='/watch'){parse_str($p['query']??'',$q);$id=$q['v']??'';}
 elseif(in_array($host,['youtube.com','www.youtube.com','www.youtube-nocookie.com'],true)&&preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})$~D',$path,$m))$id=$m[1];
 if(is_string($id)&&preg_match('/^[A-Za-z0-9_-]{11}$/D',$id))return 'https://www.youtube-nocookie.com/embed/'.$id;
 if(in_array($host,['vimeo.com','www.vimeo.com','player.vimeo.com'],true)&&preg_match('~^/(?:video/)?([0-9]{1,15})$~D',$path,$m))return 'https://player.vimeo.com/video/'.$m[1];
 return null;
}
function academy_material_validate(string $name,string $data):string{
 if(strlen($data)<8||strlen($data)>2*1024*1024||!str_starts_with($data,'%PDF-')||(new finfo(FILEINFO_MIME_TYPE))->buffer($data)!=='application/pdf')throw new InvalidArgumentException('Upload a PDF of at most 2 MB.');
 $name=preg_replace('/[^A-Za-z0-9._-]/','_',basename($name));
 if(!is_string($name)||!preg_match('/\.pdf$/iD',$name))throw new InvalidArgumentException('Use a .pdf filename.');
 return substr($name,0,180);
}
function academy_material_upload(int $course,int $lesson,array $file):void{
 if(!academy_lesson_in_course($course,$lesson))throw new InvalidArgumentException('Lesson unavailable.');
 if(($file['error']??null)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_uploaded_file($file['tmp_name'])||($file['size']??0)>2*1024*1024)throw new InvalidArgumentException('Choose a PDF of at most 2 MB.');
 $data=file_get_contents($file['tmp_name'],false,null,0,2*1024*1024+1);
 if(!is_string($data))throw new InvalidArgumentException('File unavailable.');
 $name=academy_material_validate((string)($file['name']??''),$data);
 db_execute('INSERT INTO academy_lesson_materials(lesson_id,filename,content,size_bytes,created_at) VALUES(:lesson,:name,:content,:size,:at)',['lesson'=>$lesson,'name'=>$name,'content'=>$data,'size'=>strlen($data),'at'=>now()]);
}
function academy_material_download(int $student,int $course,int $lesson,int $id):?array{
 if(!academy_learning_lesson($student,$course,$lesson))return null;
 return db_one('SELECT filename,content,size_bytes FROM academy_lesson_materials WHERE id=:id AND lesson_id=:lesson',compact('id','lesson'));
}
function academy_materials(int $lesson):array{
 return db_all('SELECT id,filename,size_bytes FROM academy_lesson_materials WHERE lesson_id=:lesson ORDER BY id',compact('lesson'));
}
function academy_assessment_questions(string $raw):array{
 $lines=preg_split('/\R/',trim($raw));$questions=[];
 foreach($lines as $line){
  if(trim($line)==='')continue;
  $parts=array_map('trim',explode('|',$line));
  if(count($parts)!==6||!in_array($parts[5],['1','2','3','4'],true))throw new InvalidArgumentException('Each question needs: question | option 1 | option 2 | option 3 | option 4 | correct option number.');
  foreach(array_slice($parts,0,5) as $v)academy_learning_text($v,500,true);
  $questions[]=['question'=>$parts[0],'options'=>array_slice($parts,1,4),'answer'=>(int)$parts[5]-1];
 }
 if(!$questions||count($questions)>50)throw new InvalidArgumentException('Enter between 1 and 50 questions.');
 return $questions;
}
function academy_assessment_create(int $course,int $lesson,array $d):int{
 if(!academy_lesson_in_course($course,$lesson))throw new InvalidArgumentException('Lesson unavailable.');
 $kind=$d['kind']??'';if(!in_array($kind,['quiz','exam','assignment'],true))throw new InvalidArgumentException('Select assessment type.');
 $title=academy_learning_text($d['title']??'',160,true);
 $instructions=academy_learning_text($d['instructions']??'',10000,$kind==='assignment');
 $pass=filter_var($d['pass_percent']??'60',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
 if($pass===false)throw new InvalidArgumentException('Pass mark must be 1–100.');
 $questions=$kind==='assignment'?[]:academy_assessment_questions(academy_learning_text($d['questions']??'',60000,true));
 db_execute('INSERT INTO academy_assessments(lesson_id,kind,title,instructions,questions_json,pass_percent,published,created_at) VALUES(:lesson,:kind,:title,:instructions,:questions,:pass,:published,:at)',['lesson'=>$lesson,'kind'=>$kind,'title'=>$title,'instructions'=>$instructions,'questions'=>json_encode($questions,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'pass'=>$pass,'published'=>($d['published']??'')==='1'?1:0,'at'=>now()]);
 return db_last_id();
}
function academy_assessment_publish(int $course,int $lesson,int $id,bool $published):void{
 if(!academy_lesson_in_course($course,$lesson))throw new InvalidArgumentException('Lesson unavailable.');
 db_execute('UPDATE academy_assessments SET published=:published WHERE id=:id AND lesson_id=:lesson',['published'=>$published?1:0,'id'=>$id,'lesson'=>$lesson]);
}
function academy_assessment_submit(int $student,int $course,int $lesson,int $id,array $data):bool{
 if(!academy_learning_lesson($student,$course,$lesson))throw new InvalidArgumentException('Assessment unavailable.');
 $a=db_one('SELECT * FROM academy_assessments WHERE id=:id AND lesson_id=:lesson AND published=1',compact('id','lesson'));
 if(!$a)throw new InvalidArgumentException('Assessment unavailable.');
 $score=null;
 if($a['kind']==='assignment')$response=academy_learning_text($data['response']??'',20000,true);
 else{
  $questions=json_decode($a['questions_json'],true,512,JSON_THROW_ON_ERROR);$answers=$data['answers']??null;
  if(!is_array($answers)||count($answers)!==count($questions)||!$questions)throw new InvalidArgumentException('Answer every question.');
  $correct=0;$response=[];
  foreach($questions as $i=>$q){$v=$answers[$i]??null;if(!is_string($v)||!in_array($v,['0','1','2','3'],true))throw new InvalidArgumentException('Answer every question.');$response[]=(int)$v;if((int)$v===$q['answer'])$correct++;}
  $score=(int)floor(100*$correct/count($questions));
 }
 // Unique constraint prevents duplicate attempts; the SELECT rechecks publication and enrollment atomically.
 $count=db_query("INSERT IGNORE INTO academy_assessment_submissions(assessment_id,student_id,response_json,score_percent,feedback,submitted_at,graded_at) SELECT a.id,en.student_id,:response,:score,'',:submitted,:graded FROM academy_assessments a JOIN academy_lessons l ON l.id=a.lesson_id JOIN academy_modules m ON m.id=l.module_id JOIN academy_learning_courses lc ON lc.course_id=m.course_id JOIN academy_courses c ON c.id=m.course_id JOIN academy_enrollments en ON en.course_id=c.id JOIN users u ON u.id=en.student_id WHERE a.id=:id AND l.id=:lesson AND c.id=:course AND en.student_id=:student AND a.published=1 AND l.published=1 AND m.published=1 AND lc.published=1 AND c.active=1 AND en.status IN ('enrolled','completed') AND u.role='student' AND u.status='active' AND u.account_state='active' AND u.deleted_at IS NULL AND u.email_verified_at IS NOT NULL",['response'=>json_encode($response,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'score'=>$score,'submitted'=>now(),'graded'=>$score===null?null:now(),'id'=>$id,'lesson'=>$lesson,'course'=>$course,'student'=>$student])->rowCount();
 return $count===1;
}
function academy_assignment_grade(int $admin,int $course,int $lesson,int $submission,array $data):void{
 $score=filter_var($data['score']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);
 if($score===false||!academy_lesson_in_course($course,$lesson))throw new InvalidArgumentException('Enter a valid score.');
 $feedback=academy_learning_text($data['feedback']??'',10000);
 $count=db_query("UPDATE academy_assessment_submissions s JOIN academy_assessments a ON a.id=s.assessment_id SET s.score_percent=:score,s.feedback=:feedback,s.graded_by=:admin,s.graded_at=:at WHERE s.id=:submission AND a.lesson_id=:lesson AND a.kind='assignment'",['score'=>$score,'feedback'=>$feedback,'admin'=>$admin,'at'=>now(),'submission'=>$submission,'lesson'=>$lesson])->rowCount();
}
