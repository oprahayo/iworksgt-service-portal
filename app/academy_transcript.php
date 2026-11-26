<?php
declare(strict_types=1);
/** Read-only, course-specific academic transcript. Student access requires an
 * active verified account and an approved enrollment or graduate record. */
function academy_transcript_data(int $viewer,int $student,int $course,bool $admin=false):?array {
 if($admin){
  academy_admin_assert($viewer);
 }elseif($viewer!==$student)return null;
 $u=db_one("SELECT id,full_name,status,account_state,email_verified_at FROM users WHERE id=:student AND role='student' AND deleted_at IS NULL",compact('student'));
 if(!$u||(!$admin&&($u['status']!=='active'||$u['account_state']!=='active'||!$u['email_verified_at'])))return null;
 $r=db_one("SELECT c.title,c.active,COALESCE(lc.published,0) learning_published,en.status,g.id graduate_id,g.duration,ce.status certificate_status FROM academy_courses c LEFT JOIN academy_learning_courses lc ON lc.course_id=c.id LEFT JOIN academy_enrollments en ON en.course_id=c.id AND en.student_id=:en_student LEFT JOIN academy_graduates g ON g.course_id=c.id AND g.student_id=:g_student LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id WHERE c.id=:course AND (en.id IS NOT NULL OR g.id IS NOT NULL)",['en_student'=>$student,'g_student'=>$student,'course'=>$course]);
 if(!$r||(!$admin&&!in_array($r['status'],['enrolled','completed'],true)&&!($r['graduate_id']&&$r['status']===null)))return null;
 $progress=($admin||($r['active']&&$r['learning_published']))?academy_admin_progress($student,$course):['total'=>0,'completed'=>0,'results'=>[]];
 $results=[];
 foreach($progress['results'] as $item){
  $result=$item['score_percent']!==null?(int)$item['score_percent'].'% - '.((int)$item['score_percent']>=(int)$item['pass_percent']?'Passed':'Not passed'):($item['submitted_at']?'Awaiting grading':'Not submitted');
  if($item['granted_at']){$prior=json_decode((string)$item['prior_submission'],true);$result.='; retake approved ('.(is_array($prior)?'original '.(int)$prior['score_percent'].'%':'original exam expired').')';}
  $results[]=['title'=>$item['title'],'kind'=>ucfirst($item['kind']),'pass'=>(int)$item['pass_percent'].'%','result'=>$result];
 }
 return ['student'=>$u['full_name'],'course'=>$r['title'],'status'=>ucfirst($r['status']??'completed'),'approval'=>$r['graduate_id']?'Approved - '.$r['duration']:'Not yet approved','certificate'=>match($r['certificate_status']){'issued'=>'Issued','revoked'=>'Revoked - not valid','pending'=>'Payment pending',default=>'Not issued'},'generated'=>gmdate('Y-m-d H:i').' UTC','completed'=>$progress['completed'],'total'=>$progress['total'],'results'=>$results];
}
function academy_transcript_pdf(array $data):string {
 $home=academy_private_home();
 $python=$home.'/.iworks-academy-renderer/bin/python';
 $script=__DIR__.'/../scripts/render_academy_transcript.py';
 $fonts=$home.'/.local/share/fonts/iworks-academy';
 $logo=__DIR__.'/../assets/branding/iworks-email-logo.png';
 if(!function_exists('proc_open')||!is_executable($python)||!is_readable($script)||!is_readable($logo))throw new RuntimeException('Transcript renderer unavailable.');
 $work=rtrim(sys_get_temp_dir(),'/').'/iwga-transcript-'.bin2hex(random_bytes(16));
 if(!mkdir($work,0700))throw new RuntimeException('Unable to prepare private transcript.');
 $input=$work.'/input.json';$output=$work.'/output.pdf';
 try{
  $json=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
  if(strlen($json)>2097152||file_put_contents($input,$json)!==strlen($json))throw new RuntimeException('Transcript too large or unavailable.');
  chmod($input,0600);
  $p=proc_open([$python,'-I',$script,$input,$output,$fonts,$logo],[0=>['pipe','r'],1=>['file','/dev/null','a'],2=>['file','/dev/null','a']],$pipes);
  if(!is_resource($p))throw new RuntimeException('Transcript renderer unavailable.');
  fclose($pipes[0]);
  if(proc_close($p)!==0||!is_file($output)||filesize($output)>8388608)throw new RuntimeException('Transcript rendering failed.');
  $pdf=file_get_contents($output);
  if(!$pdf||!str_starts_with($pdf,'%PDF-'))throw new RuntimeException('Invalid transcript.');
  return $pdf;
 }finally{@unlink($input);@unlink($output);@rmdir($work);}
}
