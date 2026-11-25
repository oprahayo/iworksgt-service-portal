<?php
declare(strict_types=1);
if(!defined('IWORKS_ACADEMY_BUILDER')||!has_role(['admin'])){http_response_code(403);exit;}
function ac_build_form(int $course,string $mode,int $id=0,int $module=0):void{
    ac_form('builder');echo '<input type="hidden" name="course" value="'.$course.'"><input type="hidden" name="mode" value="'.e($mode).'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="module" value="'.$module.'">';
}
function ac_build_publish(bool $published):void{
    echo '<label class="ac-check"><input type="checkbox" name="published" value="1"'.($published?' checked':'').'> Published</label>';
}
function ac_build_lesson(int $course,int $module,array $lesson=[]):void{
    $id=(int)($lesson['id']??0);
    echo '<details class="ac-lms-editor"><summary>'.($id?e($lesson['title']).' · '.(!empty($lesson['published'])?'Published':'Draft'):'Add lesson').'</summary>';
    ac_build_form($course,'lesson',$id,$module);
    ac_input('title','Lesson title',(string)($lesson['title']??''));
    ac_input('position','Display order',(string)($lesson['position']??10),'number');
    echo '<label>Lesson text<textarea name="body" rows="6" maxlength="50000">'.e((string)($lesson['body']??'')).'</textarea></label><label>Video link (HTTPS)<input type="url" name="video_url" maxlength="1000" value="'.e((string)($lesson['video_url']??'')).'"></label><label>Learning material link (HTTPS)<input type="url" name="material_url" maxlength="1000" value="'.e((string)($lesson['material_url']??'')).'"></label>';
    ac_build_publish(!empty($lesson['published']));echo '<button>Save lesson</button></form></details>';
    if($id)ac_assessment_admin($course,$id);
}
$cid=(int)$course['id'];
$config=db_one('SELECT * FROM academy_learning_courses WHERE course_id=:course',['course'=>$cid])??[];
$modules=db_all('SELECT * FROM academy_modules WHERE course_id=:course ORDER BY position,id',['course'=>$cid]);
ac_head('Course builder');
echo '<div class="ac-admin ac-lms"><a class="ac-link" href="/academy.php?action=admin&amp;tab=courses">Back to courses</a><h1>'.e($course['title']).'</h1><p>Prepare content in draft. Publish the course, module and lesson when ready.</p>';
if($notice=flash('academy_admin_notice'))echo '<p role="status" class="ac-message">'.e($notice).'</p>';
if($error=flash('academy_admin_error'))echo '<p role="alert" class="ac-error">'.e($error).'</p>';
echo '<section class="ac-panel"><h2>Course settings</h2>';ac_build_form($cid,'course');
echo '<label>Course description<textarea name="description" rows="3" maxlength="10000">'.e((string)($config['description']??'')).'</textarea></label>';
ac_build_publish(!empty($config['published']));echo '<button>Save course settings</button></form></section>';
echo '<section class="ac-panel"><h2>Modules and lessons</h2><details class="ac-lms-editor"><summary>Add module</summary>';ac_build_form($cid,'module');ac_input('title','Module title');ac_input('position','Display order','10','number');ac_build_publish(false);echo '<button>Save module</button></form></details>';
foreach($modules as $m){
    $mid=(int)$m['id'];echo '<article class="ac-lms-module"><h3>'.e($m['title']).' <small>'.($m['published']?'Published':'Draft').'</small></h3><details class="ac-lms-editor"><summary>Edit module</summary>';
    ac_build_form($cid,'module',$mid);ac_input('title','Module title',$m['title']);ac_input('position','Display order',(string)$m['position'],'number');ac_build_publish((bool)$m['published']);echo '<button>Save module</button></form></details>';
    foreach(db_all('SELECT * FROM academy_lessons WHERE module_id=:module ORDER BY position,id',['module'=>$mid]) as $l)ac_build_lesson($cid,$mid,$l);
    ac_build_lesson($cid,$mid);echo '</article>';
}
echo '</section></div>';ac_end();
