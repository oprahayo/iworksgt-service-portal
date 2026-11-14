#!/usr/bin/env bash
# iWORKS GLOBAL: one-command private, backup-gated cPanel publish.
# bash cpanel_finalize_release.sh --check|--publish PORTAL PUBLIC PINNED_OPERATOR_SHA
set -Eeuo pipefail
MODE="${1:---check}"
PORTAL_INPUT="${2:-$HOME/public_html/portal.iworksgt.com}"
PUBLIC_INPUT="${3:-$HOME/public_html}"
OPERATOR_SHA="${4:-}"
RELEASE="952b503ba4ff65c8ec1b14277c6dfd4b40433860"
[[ "$MODE" == --check || "$MODE" == --publish ]] || { echo 'Use --check or --publish.' >&2; exit 2; }
[[ "$OPERATOR_SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Pin the operator to an immutable 40-character GitHub commit.' >&2; exit 2; }
for tool in curl php mysql mysqldump gzip openssl sha256sum readlink mktemp awk cmp grep; do
  command -v "$tool" >/dev/null || { echo "Missing server command: $tool" >&2; exit 2; }
done
PORTAL="$(readlink -f "$PORTAL_INPUT")"
PUBLIC="$(readlink -f "$PUBLIC_INPUT")"
HOME_PUBLIC="$(readlink -f "$HOME/public_html")"
[[ "$PUBLIC" == "$HOME_PUBLIC" && -d "$PORTAL" && -f "$PORTAL/app/config.php" ]] \
  || { echo 'Configured portal or public document root missing.' >&2; exit 2; }
case "$PORTAL" in "$HOME_PUBLIC"/*) ;; *) echo 'Unexpected portal document root.' >&2; exit 2 ;; esac
umask 077
OPSDIR="$HOME/iworks-release-operations"
mkdir -p "$OPSDIR"
chmod 0700 "$OPSDIR"
LOCK="$OPSDIR/.release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'Release already running or stale lock. Inspect private lock first.' >&2; exit 3; }
WORK="$(mktemp -d "$OPSDIR/.operator.XXXXXXXX")"
RESTORE_CREATED=""
OPTS=""
cleanup() {
  if [[ -n "$RESTORE_CREATED" && -n "$OPTS" && -f "$OPTS" ]]; then
    # Use cPanel UAPI, not raw DROP DATABASE, so cPanel mappings remain valid.
    uapi --output=json Mysql delete_database "name=$RESTORE_CREATED" >"$WORK/uapi-cleanup.log" 2>&1 \
      || echo 'WARNING: isolated rehearsal database cleanup needs attention.' >&2
  fi
  rm -rf "$WORK"
  rmdir "$LOCK" 2>/dev/null || true
}
trap cleanup EXIT
ROOT_URL="https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$OPERATOR_SHA/scripts"
for filename in deploy_admin_mobile_refresh.sh prepare_private_migration_stage.sh cpanel_finalize_db.php; do
  curl -fsSL --retry 2 "$ROOT_URL/$filename" -o "$WORK/$filename"
done
bash -n "$WORK/deploy_admin_mobile_refresh.sh" "$WORK/prepare_private_migration_stage.sh"
php -l "$WORK/cpanel_finalize_db.php" >/dev/null
grep -Fq "RELEASE=\"$RELEASE\"" "$WORK/deploy_admin_mobile_refresh.sh" \
  || { echo 'Immutable application release mismatch.' >&2; exit 3; }
private_probes() {
  local site path method status
  for site in https://portal.iworksgt.com https://iworksgt.com; do
    for path in /storage/mail.log /storage/setup.lock /database.sql; do
      for method in GET HEAD; do
        if [[ "$method" == HEAD ]]; then
          status="$(curl --http1.1 --head -sS --max-redirs 0 --max-time 15 -o /dev/null -w '%{http_code}' "$site$path")" || status=000
        else
          status="$(curl --http1.1 -sS --max-redirs 0 --max-time 15 -o /dev/null -w '%{http_code}' "$site$path")" || status=000
        fi
        case "$status" in
          401|403|404|410) echo "PASS private $method $site$path ($status)" ;;
          *) echo "BLOCK private $method $site$path ($status). No release." >&2; return 3 ;;
        esac
      done
    done
  done
}
echo '== Private HTTP denial checks =='
private_probes
if [[ "$MODE" == --check ]]; then
  bash "$WORK/deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC"
  echo 'Read-only checks passed; backup and restore rehearsal are still required for publish.'
  exit 0
fi
echo '== Preparing isolated migration stage =='
STAGE="$HOME/iworks-private-migration-stages/$RELEASE"
if [[ -e "$STAGE" ]]; then
  [[ -f "$STAGE/.release-sha" && "$(< "$STAGE/.release-sha")" == "$RELEASE" ]] \
    || { echo 'Existing private stage has an unexpected release marker.' >&2; exit 3; }
  cmp -s "$STAGE/app/config.php" "$PORTAL/app/config.php" \
    || { echo 'Staged private configuration differs from live; manual stage inspection required.' >&2; exit 3; }
else
  bash "$WORK/prepare_private_migration_stage.sh" --prepare "$PORTAL" "$RELEASE"
fi
for f in migrate_legacy_schema.php migrate_auth_attempt_limits.php migrate_notification_email_outbox.php \
         migrate_gateway_uniqueness.php f01_expire_exposed_tokens.php; do
  [[ -f "$STAGE/scripts/$f" ]] || { echo "BLOCK missing migration $f" >&2; exit 3; }
done
echo '== Detecting duplicate payment identifiers before DDL =='
php "$STAGE/scripts/migrate_gateway_uniqueness.php" --check > "$WORK/duplicates.log" \
  || { echo 'BLOCK provider uniqueness check failed.' >&2; exit 3; }
cat "$WORK/duplicates.log"
if grep -Eq 'duplicate groups: [1-9][0-9]*' "$WORK/duplicates.log"; then
  echo 'BLOCK: duplicate provider identifiers need financial reconciliation.' >&2
  exit 3
fi
echo '== Creating complete, private, encrypted database backup =='
BACKUP="$(mktemp -d "$OPSDIR/backup-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
chmod 0700 "$BACKUP"
OPTS="$WORK/mysql.cnf"
php "$WORK/cpanel_finalize_db.php" client-options "$STAGE" "$OPTS" "$WORK/db-name.txt"
DB_NAME="$(< "$WORK/db-name.txt")"
if ! mysqldump --defaults-extra-file="$OPTS" --single-transaction --quick \
  --skip-lock-tables --no-tablespaces --routines --events --triggers \
  --default-character-set=utf8mb4 "$DB_NAME" 2>"$BACKUP/dump-errors.log" \
  | gzip -6 > "$BACKUP/database.sql.gz.incomplete"; then
  echo "BLOCK database dump failed. Private diagnostics: $BACKUP/dump-errors.log" >&2
  exit 3
fi
gzip -t "$BACKUP/database.sql.gz.incomplete"
if ! gzip -dc "$BACKUP/database.sql.gz.incomplete" | awk '
  /^CREATE TABLE / {
    if (index($0, "users")) u=1
    if (index($0, "jobs")) j=1
    if (index($0, "invoices")) i=1
    if (index($0, "job_payments")) p=1
  }
  END { exit !(u && j && i && p) }'; then
  echo 'BLOCK database dump is missing essential application schema.' >&2
  exit 3
fi
mv "$BACKUP/database.sql.gz.incomplete" "$BACKUP/database.sql.gz"
KEYDIR="$HOME/iworks-release-keys"
mkdir -p "$KEYDIR"
chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
if [[ ! -e "$KEY" ]]; then
  openssl rand -hex 48 > "$KEY"
  chmod 0600 "$KEY"
fi
[[ -f "$KEY" && -s "$KEY" && ! -L "$KEY" ]] || { echo 'BLOCK backup encryption key unavailable.' >&2; exit 3; }
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" \
  -in "$BACKUP/database.sql.gz" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" \
  -in "$BACKUP/database.sql.gz.enc" | gzip -t
sha256sum "$BACKUP/database.sql.gz.enc" > "$BACKUP/database.sql.gz.enc.sha256"
rm -f "$BACKUP/database.sql.gz"
chmod 0600 "$BACKUP"/*
echo "PASS encrypted SQL backup at $BACKUP (key stored separately in $KEYDIR)"
echo '== Rehearsing complete SQL restore in isolated database =='
# cPanel-managed create/grant/delete operations preserve host database mapping.
# Do not use raw CREATE/DROP DATABASE under a cPanel-owned database account.
command -v uapi >/dev/null || { echo 'BLOCK cPanel UAPI unavailable for isolated restore rehearsal.' >&2; exit 3; }
# Use the prefix already present on the configured production DB: hosts
# may force an 8-character prefix even when the cPanel login is longer.
DB_PREFIX="${DB_NAME%%_*}"
[[ "$DB_PREFIX" =~ ^[a-zA-Z0-9]{1,24}$ ]] || { echo 'BLOCK unexpected cPanel database prefix.' >&2; exit 3; }
RESTORE_DB="$DB_PREFIX"_iwverify"$(date -u +%H%M%S)"_$RANDOM
[[ "$RESTORE_DB" =~ ^[a-zA-Z0-9_]{1,64}$ && "$RESTORE_DB" != "$DB_NAME" ]] \
  || { echo 'BLOCK unsafe rehearsal database identifier.' >&2; exit 3; }
existing="$(mysql --defaults-extra-file="$OPTS" -N -B \
  -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$RESTORE_DB'" 2>/dev/null)" \
  || { echo 'BLOCK cannot inspect rehearsal database name.' >&2; exit 3; }
[[ "$existing" == 0 ]] || { echo 'BLOCK rehearsal database already exists.' >&2; exit 3; }
SQL_USER="$(mysql --defaults-extra-file="$OPTS" -N -B -e "SELECT SUBSTRING_INDEX(CURRENT_USER(),'@',1)")"
[[ "$SQL_USER" =~ ^[a-zA-Z0-9_]{1,64}$ ]] \
  || { echo 'BLOCK unexpected MySQL account identity.' >&2; exit 3; }
if ! uapi --output=json Mysql create_database "name=$RESTORE_DB" >"$BACKUP/uapi-create.log" 2>&1; then
  echo 'BLOCK: cPanel UAPI could not create an isolated rehearsal database. Production unchanged.' >&2
  exit 3
fi
RESTORE_CREATED="$RESTORE_DB"
if ! php -r '$r=json_decode(file_get_contents($argv[1]),true);exit(($r["result"]["status"]??0)==1?0:1);' \
  "$BACKUP/uapi-create.log"; then
  echo 'BLOCK: cPanel UAPI did not confirm rehearsal database creation.' >&2
  exit 3
fi
if ! uapi --output=json Mysql set_privileges_on_database "user=$SQL_USER" \
  "database=$RESTORE_DB" 'privileges=ALL%20PRIVILEGES' >"$BACKUP/uapi-grant.log" 2>&1; then
  echo 'BLOCK: cPanel UAPI could not grant isolated rehearsal privileges.' >&2
  exit 3
fi
if ! php -r '$r=json_decode(file_get_contents($argv[1]),true);exit(($r["result"]["status"]??0)==1?0:1);' \
  "$BACKUP/uapi-grant.log"; then
  echo 'BLOCK: rehearsal database privileges were not confirmed.' >&2
  exit 3
fi
if ! openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" \
  -in "$BACKUP/database.sql.gz.enc" | gzip -dc \
  | mysql --defaults-extra-file="$OPTS" "$RESTORE_DB" \
  >"$BACKUP/restore-output.log" 2>"$BACKUP/restore-errors.log"; then
  echo 'BLOCK: isolated restore rehearsal failed. No production migrations started.' >&2
  exit 3
fi
for table in users jobs invoices job_payments; do
  live="$(mysql --defaults-extra-file="$OPTS" -N -B -e "SELECT COUNT(*) FROM $DB_NAME.$table")"
  restored="$(mysql --defaults-extra-file="$OPTS" -N -B -e "SELECT COUNT(*) FROM $RESTORE_DB.$table")"
  [[ "$live" == "$restored" ]] || { echo "BLOCK restored row-count mismatch: $table" >&2; exit 3; }
  echo "PASS restored $table ($restored rows)"
done
uapi --output=json Mysql delete_database "name=$RESTORE_DB" >"$BACKUP/uapi-delete.log" 2>&1
php -r '$r=json_decode(file_get_contents($argv[1]),true);exit(($r["result"]["status"]??0)==1?0:1);' "$BACKUP/uapi-delete.log" \
  || { echo 'BLOCK: cPanel did not confirm cleanup of rehearsal database.' >&2; exit 3; }
RESTORE_CREATED=""
echo 'PASS backup restored successfully in isolated schema.'
echo '== Controlled migration after verified private backup =='
php "$STAGE/scripts/migrate_legacy_schema.php" --check
php "$STAGE/scripts/f01_expire_exposed_tokens.php" --check
for script in migrate_legacy_schema.php migrate_auth_attempt_limits.php \
              migrate_notification_email_outbox.php migrate_gateway_uniqueness.php; do
  php "$STAGE/scripts/$script" --apply --backup-confirmed \
    || { echo "BLOCK $script failed; private backup retained at $BACKUP." >&2; exit 3; }
done
php "$STAGE/scripts/migrate_auth_attempt_limits.php" --check
php "$STAGE/scripts/migrate_notification_email_outbox.php" --check
php "$STAGE/scripts/f01_expire_exposed_tokens.php" --apply
echo '== Testing mail transport and enabling configured notifications =='
php "$WORK/cpanel_finalize_db.php" mail-probe "$STAGE" "${IWORKS_MAIL_TEST_TO:-oprahnet@gmail.com}" \
  || { echo 'BLOCK mail transport rejected test; no application files deployed.' >&2; exit 3; }
php "$WORK/cpanel_finalize_db.php" mail-enable "$STAGE"
echo '== Mandatory database, email and private-file release preflight =='
bash "$WORK/deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC" \
  || { echo 'BLOCK final read-only preflight; no source files deployed.' >&2; exit 3; }
echo '== Publishing immutable, source-backed release =='
bash "$WORK/deploy_admin_mobile_refresh.sh" --apply "$PORTAL" "$PUBLIC"
for page in https://iworksgt.com/ https://iworksgt.com/services; do
  status="$(curl --http1.1 -sS --max-time 25 -o /dev/null -w '%{http_code}' "$page")" || status=000
  [[ "$status" == 200 ]] || { echo "POST-PUBLISH HTTP FAILURE $page ($status)" >&2; exit 3; }
  echo "PASS public page $page ($status)"
done
private_probes
grep -Fq 'data-iw-guest-cta' "$PORTAL/views/partials/public-header.php" \
  || { echo 'POST-PUBLISH signed-in CTA marker missing.' >&2; exit 3; }
echo "PUBLISHED pinned iWORKS release $RELEASE"
echo "Encrypted backup preserved: $BACKUP"
echo 'Confirm actual new email arrival, real OTP/password-reset, payment gateway sandbox, scheduler and mobile/browser acceptance before declaring full production verification.'
