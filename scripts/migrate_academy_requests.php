<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_certification_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT UNSIGNED NOT NULL, course_id INT UNSIGNED NOT NULL,
 student_name VARCHAR(150) NOT NULL, student_note TEXT NOT NULL, status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
 review_note TEXT NOT NULL, submitted_at DATETIME NOT NULL, reviewed_at DATETIME NULL, reviewed_by INT UNSIGNED NULL,
 attempts INT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY student_course(student_id,course_id), KEY review_queue(status,submitted_at),
 FOREIGN KEY(student_id) REFERENCES users(id), FOREIGN KEY(course_id) REFERENCES academy_courses(id), FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS additive certification request schema ready. Existing approvals unchanged.\n";
