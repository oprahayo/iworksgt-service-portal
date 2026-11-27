<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_assignment_files (
 submission_id INT UNSIGNED PRIMARY KEY,
 filename VARCHAR(190) NOT NULL,
 content MEDIUMBLOB NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,
 uploaded_at DATETIME NOT NULL,
 FOREIGN KEY(submission_id) REFERENCES academy_assessment_submissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS private assignment file schema; existing submissions unchanged.\n";
