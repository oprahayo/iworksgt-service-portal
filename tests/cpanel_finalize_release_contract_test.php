<?php
declare(strict_types=1);
/** One-command cPanel publication must fail closed on missing prerequisites. */
$root = dirname(__DIR__);
$operator = (string)file_get_contents($root.'/scripts/cpanel_finalize_release.sh');
$helper = (string)file_get_contents($root.'/scripts/cpanel_finalize_db.php');
$installer = (string)file_get_contents($root.'/scripts/deploy_admin_mobile_refresh.sh');
$assert = static function (bool $ok, string $why): void {
    if (!$ok) throw new RuntimeException($why);
};
$release = 'e032577d94860ef3336499e770f439f231c886de';
$assert(str_contains($operator, 'RELEASE="'.$release.'"')
    && str_contains($installer, 'RELEASE="'.$release.'"'), 'Pinned application release must match.');
$assert(str_contains($operator, '[[ "$OPERATOR_SHA" =~ ^[a-f0-9]{40}$ ]]'),
    'Operator scripts must load from a pinned immutable commit.');
$assert(str_contains($operator, 'private_probes') && str_contains($operator, '--http1.1 --head'),
    'Private-file GET/HEAD denial checks must run with HTTP/1.1.');
$backup = strpos($operator, 'mysqldump --defaults-extra-file=');
$restore = strpos($operator, 'Rehearsing complete SQL restore');
$migration = strpos($operator, 'php "$STAGE/scripts/$script" --apply --backup-confirmed');
$mail = strpos($operator, 'mail-probe');
$gate = strpos($operator, 'deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC"');
$publish = strpos($operator, 'deploy_admin_mobile_refresh.sh" --apply "$PORTAL" "$PUBLIC"');
$assert($backup !== false && $restore !== false && $migration !== false
    && $mail !== false && $gate !== false && $publish !== false
    && $backup < $restore && $restore < $migration
    && $migration < $mail && $mail < $gate && $gate < $publish,
    'Restore and all mandatory gates must precede live source publication.');
$assert(str_contains($operator, 'CREATE DATABASE $RESTORE_DB')
    && str_contains($operator, 'DROP DATABASE $RESTORE_DB')
    && str_contains($operator, 'RESTORE_CREATED'),
    'Backup restore must be rehearsed into an isolated, tracked database.');
$assert(str_contains($operator, 'openssl enc -aes-256-cbc -pbkdf2')
    && str_contains($operator, 'chmod 0700 "$KEYDIR"'),
    'Private backups must be encrypted and their key directory inaccessible to web users.');
$assert(str_contains($operator, 'duplicate groups: [1-9]')
    && str_contains($operator, 'f01_expire_exposed_tokens.php'),
    'Do not skip provider duplicate or incident token checks.');
$assert(str_contains($helper, "file_exists(")
    && str_contains($helper, 'umask(0077)')
    && str_contains($helper, 'chmod($optsPath, 0600)')
    && str_contains($helper, "PHP_SAPI !== 'cli'"),
    'DB credentials must remain private and CLI-only.');
$assert(!str_contains($operator, 'IWORKS_BYPASS') && !str_contains($operator, 'SKIP_BACKUP'),
    'Unsafe bypass modes must not exist.');
$assert(str_contains($installer, 'app/config.php|storage/*|assets/uploads/*'),
    'Existing private config and uploaded content must remain excluded from publication.');
echo "cpanel_finalize_release_contract_test passed\n";
