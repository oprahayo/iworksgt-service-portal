<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('ACADEMY_CI_DATABASE')!=='1'||getenv('DB_NAME')!=='academy_ci')exit(2);
require __DIR__.'/../app/bootstrap.php';
$course=db_one("SELECT c.id,lc.published FROM academy_courses c JOIN academy_learning_courses lc ON lc.course_id=c.id WHERE c.slug='digital-marketing'");
if(!$course||(int)$course['published']!==1)throw new RuntimeException('Published course missing');
$id=(int)$course['id'];
$modules=db_all('SELECT id,published FROM academy_modules WHERE course_id=:course',['course'=>$id]);
$lessons=db_all('SELECT l.id,l.published,l.body,l.material_url FROM academy_lessons l JOIN academy_modules m ON m.id=l.module_id WHERE m.course_id=:course',['course'=>$id]);
$assessments=db_all('SELECT a.kind,a.title,a.questions_json,a.pass_percent,a.duration_minutes,a.published FROM academy_assessments a JOIN academy_lessons l ON l.id=a.lesson_id JOIN academy_modules m ON m.id=l.module_id WHERE m.course_id=:course',['course'=>$id]);
if(count($modules)!==8||count($lessons)!==16||count($assessments)!==10)throw new RuntimeException('Course counts incorrect or duplicate import');
foreach(array_merge($modules,$lessons,$assessments) as $item)if((int)$item['published']!==1)throw new RuntimeException('Unpublished item');
foreach($lessons as $lesson)if(strlen($lesson['body'])<500||!str_starts_with($lesson['material_url'],'https://'))throw new RuntimeException('Lesson incomplete');
$counts=['quiz'=>0,'exam'=>0,'assignment'=>0];
foreach($assessments as $a){
 $counts[$a['kind']]++;
 $questions=json_decode($a['questions_json'],true,512,JSON_THROW_ON_ERROR);
 $n=count($questions);
 if($a['kind']==='quiz'&&($n!==3||(int)$a['pass_percent']!==60))throw new RuntimeException('Invalid quiz');
 if($a['kind']==='exam'&&($n!==16||(int)$a['pass_percent']!==70||(int)$a['duration_minutes']!==25||!str_contains($a['title'],'practice')))throw new RuntimeException('Invalid formative exam');
 if($a['kind']==='assignment'&&($n!==0||(int)$a['pass_percent']!==70))throw new RuntimeException('Invalid assignment');
}
if($counts!==['quiz'=>8,'exam'=>1,'assignment'=>1])throw new RuntimeException('Incorrect assessment types');
echo "PASS real MySQL Digital Marketing import, repeat safety, content and assessment integrity.\n";
