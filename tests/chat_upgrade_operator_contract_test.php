<?php
declare(strict_types=1);
/**
 * Scoped cPanel production upgrade contract: encrypted backup and successful
 * isolated restore must precede the additive chat migration and source copy.
 */
$root = dirname(__DIR__);
$script = (string)file_get_contents($root.'/scripts/cpanel_finalize_release.sh');
$installer = (string)file_get_contents($root.'/scripts/deploy_admin_mobile_refresh.sh');
$migration = (string)file_get_contents($root.'/scripts/migrate_live_chat_engagement.php');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$pin = '0f303b137e90fc9856a1df8d7791d9c08b8590fc';
$assert(str_contains($installer,'RELEASE="'.$pin.'"')
    && str_contains($script,'RELEASE="'.$pin.'"'),
    'Operator and source-only installer must use the same immutable reviewed chat source.');
$assert(str_contains($script,'--chat-upgrade')
    && str_contains($script,'[[ "$MODE" == --check || "$MODE" == --publish || "$MODE" == --chat-upgrade ]]'),
    'Scoped chat upgrade must be an explicit, recognized mode.');
$backup = strpos($script,'mysqldump --defaults-extra-file=');
$encrypted = strpos($script,'openssl enc -aes-256-cbc -pbkdf2');
$restore = strpos($script, 'uapi --output=json Mysql create_database');
$restored = strpos($script, "echo 'PASS backup restored successfully in isolated schema.'");
$chatBranch = strpos($script, 'if [[ "$MODE" == --chat-upgrade ]]; then');
$chatMigrate = strpos($script, 'migrate_live_chat_engagement.php" --apply --backup-confirmed');
$chatCheck = strpos($script, 'migrate_live_chat_engagement.php" --check', $chatMigrate === false ? 0 : $chatMigrate);
$sourceCheck = strpos($script, 'deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC"', $chatMigrate === false ? 0 : $chatMigrate);
$sourceApply = strpos($script, 'deploy_admin_mobile_refresh.sh" --apply "$PORTAL" "$PUBLIC"', $chatMigrate === false ? 0 : $chatMigrate);
$chatEnd = strpos($script, "echo '== Controlled migration after verified private backup =='", $chatBranch === false ? 0 : $chatBranch);
$assert($backup !== false && $encrypted !== false && $restore !== false
    && $restored !== false && $chatBranch !== false && $chatMigrate !== false
    && $chatCheck !== false && $sourceCheck !== false && $sourceApply !== false
    && $chatEnd !== false
    && $backup < $encrypted && $encrypted < $restore && $restore < $restored
    && $restored < $chatBranch && $chatBranch < $chatMigrate
    && $chatMigrate < $chatCheck && $chatCheck < $sourceCheck
    && $sourceCheck < $sourceApply && $sourceApply < $chatEnd,
    'Encrypted SQL export and actual isolated restore must precede scoped DDL, post-check, preflight and source release.');
$chatBlock = substr($script,$chatBranch,$chatEnd-$chatBranch);
$assert(!str_contains($chatBlock, 'f01_expire_exposed_tokens.php')
    && !str_contains($chatBlock, 'migrate_legacy_schema.php')
    && !str_contains($chatBlock, 'mail-enable'),
    'Routine chat upgrade must not rerun incident token invalidation, other migrations or email activation.');
$assert(str_contains($script, 'grep -Fq "RELEASE=\"$RELEASE\""')
    && str_contains($script, 'private_probes')
    && str_contains($chatBlock, 'DROP DATABASE') === false
    && str_contains($script, 'uapi --output=json Mysql delete_database'),
    'Retain pinned operator, private webroot probes and cPanel UAPI-managed rehearsal cleanup.');
$assert(str_contains($migration, "'--backup-confirmed'")
    && str_contains($migration, "if (PHP_SAPI !== 'cli')")
    && str_contains($migration, 'CREATE TABLE IF NOT EXISTS iw_live_email_outbox')
    && !str_contains($migration, 'DROP TABLE'),
    'Engagement DDL must be additive, CLI only and require verified backup.');
echo "chat_upgrade_operator_contract_test passed\n";
