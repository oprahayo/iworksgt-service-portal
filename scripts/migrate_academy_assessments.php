<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_lesson_materials (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lesson_id INT UNSIGNED NOT NULL,
 filename VARCHAR(190) NOT NULL, content MEDIUMBLOB NOT NULL, size_bytes INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL, FOREIGN KEY(lesson_id) REFERENCES academy_lessons(id),
 KEY lesson_materials(lesson_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_assessments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lesson_id INT UNSIGNED NOT NULL,
 kind ENUM('quiz','exam','assignment') NOT NULL, title VARCHAR(160) NOT NULL,
 instructions TEXT NOT NULL, questions_json MEDIUMTEXT NOT NULL, pass_percent TINYINT UNSIGNED NOT NULL DEFAULT 60,
 published TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
 FOREIGN KEY(lesson_id) REFERENCES academy_lessons(id), KEY lesson_assessments(lesson_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_assessment_submissions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id INT UNSIGNED NOT NULL,
 student_id INT UNSIGNED NOT NULL, response_json MEDIUMTEXT NOT NULL,
 score_percent TINYINT UNSIGNED NULL, feedback TEXT NOT NULL,
 submitted_at DATETIME NOT NULL, graded_at DATETIME NULL, graded_by INT UNSIGNED NULL,
 FOREIGN KEY(assessment_id) REFERENCES academy_assessments(id),
 FOREIGN KEY(student_id) REFERENCES users(id), FOREIGN KEY(graded_by) REFERENCES users(id),
 UNIQUE KEY one_attempt(assessment_id,student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS Sprint 6 private materials and assessment schema ready.\n";
