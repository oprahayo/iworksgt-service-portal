<?php
declare(strict_types=1);
// Additive LMS schema. No changes to existing enrollment, graduate or payment records.
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath($argv[1]??dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_learning_courses (
 course_id INT UNSIGNED PRIMARY KEY, description TEXT NOT NULL,
 published TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL,
 FOREIGN KEY(course_id) REFERENCES academy_courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_modules (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, course_id INT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL, position INT UNSIGNED NOT NULL DEFAULT 10,
 published TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(course_id) REFERENCES academy_courses(id), KEY course_order(course_id,position,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_lessons (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, module_id INT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL, body MEDIUMTEXT NOT NULL, video_url VARCHAR(1000) NOT NULL DEFAULT '',
 material_url VARCHAR(1000) NOT NULL DEFAULT '', position INT UNSIGNED NOT NULL DEFAULT 10,
 published TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(module_id) REFERENCES academy_modules(id), KEY module_order(module_id,position,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_lesson_progress (
 student_id INT UNSIGNED NOT NULL, lesson_id INT UNSIGNED NOT NULL, completed_at DATETIME NOT NULL,
 PRIMARY KEY(student_id,lesson_id), FOREIGN KEY(student_id) REFERENCES users(id),
 FOREIGN KEY(lesson_id) REFERENCES academy_lessons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS additive Academy learning schema ready.\n";
