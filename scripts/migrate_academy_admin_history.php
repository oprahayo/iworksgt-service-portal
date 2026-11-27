<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!in_array('--backup-confirmed',$argv,true)){http_response_code(404);exit(2);}
$root=realpath(isset($argv[1])&&$argv[1]!=='--backup-confirmed'?$argv[1]:dirname(__DIR__));
if(!$root||!is_file($root.'/app/bootstrap.php'))exit(2);
require $root.'/app/bootstrap.php';
db()->exec("CREATE TABLE IF NOT EXISTS academy_admin_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL, admin_id INT UNSIGNED NOT NULL,
 entity_type VARCHAR(30) NOT NULL, entity_id INT UNSIGNED NOT NULL,
 old_state VARCHAR(190) NOT NULL, new_state VARCHAR(190) NOT NULL,
 reason TEXT NOT NULL, changed_at DATETIME NOT NULL,
 KEY student_history(student_id,id), FOREIGN KEY(student_id) REFERENCES users(id),
 FOREIGN KEY(admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "PASS Academy administrator history schema.\n";
