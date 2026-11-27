<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
// Minimal deletion receipt: no name, email, phone or retained academic content.
db()->exec("CREATE TABLE IF NOT EXISTS academy_student_deletions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 deleted_student_id INT UNSIGNED NOT NULL UNIQUE,
 admin_id INT UNSIGNED NOT NULL,
 deleted_at DATETIME NOT NULL,
 KEY deletion_admin(admin_id,deleted_at),
 FOREIGN KEY(admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS Academy deletion receipts schema.\n";
