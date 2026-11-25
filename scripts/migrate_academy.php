<?php
declare(strict_types=1);
// Apply only after a verified database backup. Never execute from HTTP.
if (PHP_SAPI !== 'cli' || !in_array('--backup-confirmed', $argv, true)) { exit("CLI only; verified backup required (--backup-confirmed).\n"); }
require __DIR__.'/../app/bootstrap.php';
db()->exec("ALTER TABLE users MODIFY role ENUM('admin','agent','client','finance','author','student') NOT NULL DEFAULT 'client'");
db()->exec("CREATE TABLE IF NOT EXISTS academy_courses (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(100) NOT NULL UNIQUE,
 title VARCHAR(160) NOT NULL, colour ENUM('orange','purple','green','blue') NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_graduates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT UNSIGNED NOT NULL,
 course_id INT UNSIGNED NOT NULL, duration VARCHAR(80) NOT NULL, approved_by INT UNSIGNED NOT NULL,
 approved_at DATETIME NOT NULL, UNIQUE KEY student_course(student_id,course_id),
 FOREIGN KEY(student_id) REFERENCES users(id), FOREIGN KEY(course_id) REFERENCES academy_courses(id),
 FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
db()->exec("CREATE TABLE IF NOT EXISTS academy_certificates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, graduate_id INT UNSIGNED NOT NULL UNIQUE,
 student_id INT UNSIGNED NOT NULL, certificate_number VARCHAR(48) NOT NULL UNIQUE,
 student_name VARCHAR(150) NOT NULL, course_title VARCHAR(160) NOT NULL, duration VARCHAR(80) NOT NULL,
 colour ENUM('orange','purple','green','blue') NOT NULL, amount_minor INT UNSIGNED NOT NULL,
 payment_reference VARCHAR(80) NOT NULL UNIQUE, checkout_url VARCHAR(512) NULL, provider_transaction VARCHAR(80) NULL UNIQUE,
 status ENUM('pending','issued','revoked') NOT NULL DEFAULT 'pending',
 issued_at DATETIME NULL, email_sent_at DATETIME NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(graduate_id) REFERENCES academy_graduates(id), FOREIGN KEY(student_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!db_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='academy_certificates' AND COLUMN_NAME='checkout_url'")) {
    db()->exec('ALTER TABLE academy_certificates ADD COLUMN checkout_url VARCHAR(512) NULL AFTER payment_reference');
}
db()->exec("CREATE TABLE IF NOT EXISTS academy_enrollments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT UNSIGNED NOT NULL,
 course_id INT UNSIGNED NOT NULL, status ENUM('requested','enrolled','completed','cancelled') NOT NULL DEFAULT 'requested',
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE KEY academy_student_course(student_id,course_id),
 FOREIGN KEY(student_id) REFERENCES users(id), FOREIGN KEY(course_id) REFERENCES academy_courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach ([
 ['digital-marketing','Digital Marketing','orange'],
 ['wordpress','WordPress & CMS Web Design','purple'],
 ['frontend-basic','Basic Coding & Frontend Web Development','purple'],
 ['frontend-advanced','Advanced Frontend Web Development','purple'],
 ['backend','Backend Web Development','blue'],
 ['fullstack','Full-Stack Web Development','green'],
 ['hosting','Web Hosting & Server Administration','green']
] as [$slug,$title,$colour]) {
 db_execute('INSERT IGNORE INTO academy_courses (slug,title,colour) VALUES (:slug,:title,:colour)', compact('slug','title','colour'));
}
echo "Academy schema ready.\n";
