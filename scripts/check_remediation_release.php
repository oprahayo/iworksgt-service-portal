<?php
declare(strict_types=1);
/**
 * Read-only preflight for immutable cPanel release. NEVER applies migrations.
 * php scripts/check_remediation_release.php PORTAL_CONFIG PORTAL_ROOT PUBLIC_ROOT
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/release_preflight.php';
if (count($argv) !== 4) {
    fwrite(STDERR, "Use: php check_remediation_release.php CONFIG PORTAL_ROOT PUBLIC_ROOT\n");
    exit(2);
}
$configPath = realpath((string)$argv[1]);
$portal = realpath((string)$argv[2]);
$public = realpath((string)$argv[3]);
if (!$configPath || !$portal || !$public
    || $configPath !== realpath($portal.'/app/config.php') || $portal === $public) {
    fwrite(STDERR, "Invalid application/config locations.\n");
    exit(2);
}
$results = [];
$mark = static function (string $label, bool $ok) use (&$results): void {
    $results[] = ['check'=>$label,'ok'=>$ok];
};
try {
    $config = require $configPath;
    $db = (array)($config['db'] ?? []);
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        (string)($db['host'] ?? ''), (int)($db['port'] ?? 3306),
        (string)($db['name'] ?? ''));
    $pdo = new PDO($dsn,(string)($db['user'] ?? ''),(string)($db['pass'] ?? ''),[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    $needed = iworks_required_release_schema();
    $tables = $pdo->query(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchAll(PDO::FETCH_COLUMN);
    $tableLookup = array_fill_keys(array_map('strtolower',$tables),true);
    foreach ($needed['tables'] as $table) {
        $mark('table:'.$table, isset($tableLookup[$table]));
    }
    $columns = $pdo->query(
        'SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE()'
    )->fetchAll();
    $columnLookup=[];
    foreach ($columns as $row) {
        $columnLookup[strtolower($row['table_name'].'.'.$row['column_name'])]=true;
    }
    foreach ($needed['columns'] as [$table,$column]) {
        $mark('column:'.$table.'.'.$column,isset($columnLookup[$table.'.'.$column]));
    }
    $indexes = $pdo->query(
        'SELECT table_name,index_name FROM information_schema.statistics WHERE table_schema = DATABASE()'
    )->fetchAll();
    $indexLookup=[];
    foreach ($indexes as $row) {
        $indexLookup[strtolower($row['table_name'].'.'.$row['index_name'])]=true;
    }
    foreach ($needed['indexes'] as [$table,$index]) {
        $mark('unique-index:'.$table.'.'.$index,
            isset($indexLookup[$table.'.'.$index]));
    }

    if (isset($tableLookup['app_settings'])) {
        $stmt = $pdo->prepare(
            "SELECT setting_key, setting_value FROM app_settings
             WHERE setting_key IN ('mail_enabled','email_notifications_enabled','mail_from_email')"
        );
        $stmt->execute();
        $settings=[];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']]=$row['setting_value'];
        }
        $mark('transport-enabled',($settings['mail_enabled'] ?? '')==='1');
        $mark('notification-enabled',($settings['email_notifications_enabled'] ?? '')==='1');
        $mark('sender-email-valid',
            filter_var((string)($settings['mail_from_email'] ?? ''),FILTER_VALIDATE_EMAIL)!==false);
    }
    foreach (['portal'=>$portal,'public'=>$public] as $name=>$root) {
        $htaccess = is_file($root.'/.htaccess') ? (string)file_get_contents($root.'/.htaccess') : '';
        $storage = is_file($root.'/storage/.htaccess') ? (string)file_get_contents($root.'/storage/.htaccess') : '';
        $mark($name.':private-http-denial',
            str_contains($htaccess,'BEGIN IWORKS PRIVATE FILE PROTECTION')
            && str_contains($htaccess,'RewriteRule ^(?:storage|app|tests|scripts)'));
        $mark($name.':storage-access-denial',
            str_contains($storage,'Require all denied'));
    }
} catch (Throwable $error) {
    // No exception/SQL/credential output, even for failed schema queries.
    fwrite(STDERR, "Release preflight could not safely verify database/configuration.\n");
    exit(1);
}
$failures=0;
foreach ($results as $result) {
    echo ($result['ok']?'PASS':'BLOCK') . ' ' . $result['check'] . "\n";
    if (!$result['ok']) $failures++;
}
if ($failures) {
    fwrite(STDERR, "Release blocked by $failures mandatory preflight finding(s).\n");
    exit(3);
}
echo "Preflight passed. Controlled mailbox, gateway sandbox and backup verification are still separate acceptance requirements.\n";
