<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
if(!db_one("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='academy_assessments' AND COLUMN_NAME='duration_minutes'")){
 db()->exec('ALTER TABLE academy_assessments ADD COLUMN duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0');
}
db()->exec("CREATE TABLE IF NOT EXISTS academy_exam_starts (
 assessment_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
 started_epoch BIGINT UNSIGNED NOT NULL, deadline_epoch BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(assessment_id,student_id),
 FOREIGN KEY(assessment_id) REFERENCES academy_assessments(id), FOREIGN KEY(student_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS optional timed exam schema. Existing exams remain untimed.\n";
