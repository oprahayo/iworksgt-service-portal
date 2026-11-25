<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath($argv[1]??'');if(!$root||$root!==realpath(getenv('HOME').'/public_html/portal.iworksgt.com'))exit(2);
require $root.'/app/bootstrap.php';
require $root.'/app/academy.php';
$fixed=0;$locked=0;$review=0;
foreach(db_all("SELECT g.id,g.student_id,g.duration,ce.id AS certificate_id FROM academy_graduates g LEFT JOIN academy_certificates ce ON ce.graduate_id=g.id ORDER BY g.id") as $r){
    $old=(string)$r['duration'];
    // Preserve the original number: never infer or rewrite a student's actual training length.
    if(!preg_match('/^\s*[0-9]{1,3}\s*$/D',$old))continue;
    if($r['certificate_id']){$locked++;continue;}
    if(academy_duration_weeks($old)===null){$review++;continue;}
    if(academy_edit_duration((int)$r['student_id'],(int)$r['id'],$old,$old))$fixed++;
    else $review++;
}
echo "PASS corrected bare-number approved durations: $fixed\n";
echo "Existing checkout/issued/revoked records preserved: $locked; other records needing review: $review\n";
echo "No new graduation approvals, certificates or charges created.\n";
