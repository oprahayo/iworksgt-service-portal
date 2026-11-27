<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_assessment_retakes (
 assessment_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
 granted_by INT UNSIGNED NOT NULL, reason VARCHAR(500) NOT NULL,
 prior_submission MEDIUMTEXT NULL, prior_timer MEDIUMTEXT NULL,
 granted_at DATETIME NOT NULL,
 PRIMARY KEY(assessment_id,student_id),
 FOREIGN KEY(assessment_id) REFERENCES academy_assessments(id),
 FOREIGN KEY(student_id) REFERENCES users(id),
 FOREIGN KEY(granted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS additive retake audit schema. No existing attempts changed.\n";
