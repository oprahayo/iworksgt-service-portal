<?php
declare(strict_types=1);
/**
 * Private cPanel release assistant. CLI only; no secrets or message content
 * are printed. Called by cpanel_finalize_release.sh, never through HTTP.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(2); }
$mode = $argv[1] ?? '';
$stage = realpath((string)($argv[2] ?? ''));
if (!$stage || !is_file($stage.'/app/config.php')) {
    fwrite(STDERR, "Private release stage unavailable.\n");
    exit(2);
}
if ($mode === 'client-options') {
    $optsPath = (string)($argv[3] ?? '');
    $namePath = (string)($argv[4] ?? '');
    $optsDir = realpath(dirname($optsPath));
    $nameDir = realpath(dirname($namePath));
    $publicHtml = realpath((string)(getenv('HOME') ?: '').'/public_html');
    if (!$optsDir || $optsDir !== $nameDir
        || ($publicHtml && ($optsDir === $publicHtml || str_starts_with($optsDir, $publicHtml.'/')))
        || file_exists($optsPath) || file_exists($namePath) || is_link($optsPath) || is_link($namePath)) {
        fwrite(STDERR, "Refusing insecure database backup option path.\n");
        exit(2);
    }
    $cfg = require $stage.'/app/config.php';
    $db = (array)($cfg['db'] ?? []);
    $name = (string)($db['name'] ?? '');
    $host = (string)($db['host'] ?? '');
    $user = (string)($db['user'] ?? '');
    $password = (string)($db['pass'] ?? '');
    $port = (int)($db['port'] ?? 3306);
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $name)
        || $host === '' || $user === '' || $password === '' || $port < 1 || $port > 65535) {
        fwrite(STDERR, "Existing database connection settings are invalid.\n");
        exit(2);
    }
    foreach ([$host, $user, $password] as $value) {
        if (preg_match('/[\x00-\x1f\x7f]/', $value)) {
            fwrite(STDERR, "Unsupported database configuration characters.\n");
            exit(2);
        }
    }
    $iniQuote = static fn(string $v): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $v).'"';
    $opts = "[client]\nuser=".$iniQuote($user)."\npassword=".$iniQuote($password)
          ."\nhost=".$iniQuote($host)."\nport=".$port."\n";
    umask(0077);
    $f = @fopen($optsPath, 'x');
    if (!$f) { fwrite(STDERR, "Unable to protect temporary database credentials.\n"); exit(1); }
    try {
        if (fwrite($f, $opts) !== strlen($opts)) { throw new RuntimeException('Incomplete configuration write'); }
    } catch (Throwable $e) {
        fclose($f);
        @unlink($optsPath);
        fwrite(STDERR, "Temporary database credentials write failed.\n");
        exit(1);
    }
    fclose($f);
    chmod($optsPath, 0600);
    if (file_put_contents($namePath, $name."\n", LOCK_EX) === false) {
        @unlink($optsPath);
        fwrite(STDERR, "Database identifier file write failed.\n");
        exit(1);
    }
    chmod($namePath, 0600);
    echo "Private MySQL connection prepared without displaying credentials.\n";
    exit(0);
}
if ($mode !== 'mail-probe' && $mode !== 'mail-enable') {
    fwrite(STDERR, "Use client-options, mail-probe or mail-enable.\n");
    exit(2);
}
require $stage.'/app/bootstrap.php';
$sender = trim(setting('mail_from_email', ''));
$notifications = setting('email_notifications_enabled', '0');
if (!filter_var($sender, FILTER_VALIDATE_EMAIL) || $notifications !== '1'
    || strpbrk($sender, "\r\n") !== false) {
    fwrite(STDERR, "Mail sender or notification configuration is not production-ready.\n");
    exit(3);
}
if ($mode === 'mail-probe') {
    $recipient = trim((string)($argv[3] ?? ''));
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || strpbrk($recipient, "\r\n") !== false) {
        fwrite(STDERR, "A valid controlled test recipient is required.\n");
        exit(2);
    }
    $subject = '[iWORKS RELEASE TEST] '.gmdate('Y-m-d H:i:s').' UTC';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: iWORKS GLOBAL <'.$sender.'>',
        'Reply-To: '.$sender,
    ]);
    $sent = @mail($recipient, $subject,
        "Controlled iWORKS release test. No customer action is required.\n",
        $headers, '-f'.$sender);
    echo $sent
        ? "Mail transport accepted controlled test; separate inbox confirmation is still required.\n"
        : "Mail transport rejected controlled test; no settings changed.\n";
    exit($sent ? 0 : 3);
}
if (!function_exists('set_setting')) {
    fwrite(STDERR, "Mail setting writer unavailable.\n");
    exit(2);
}
try {
    set_setting('mail_enabled', '1');
    if (setting('mail_enabled', '0') !== '1') {
        throw new RuntimeException('Post-write value mismatch');
    }
    echo "Outgoing mail setting enabled; notifications were already enabled.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Unable to enable mail. Inspect private diagnostics.\n");
    exit(1);
}
