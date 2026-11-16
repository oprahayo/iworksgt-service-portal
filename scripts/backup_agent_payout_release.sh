#!/usr/bin/env bash
# Private cPanel backup and isolated restore rehearsal. Never deploys or pays.
set -Eeuo pipefail
[[ "${1:-}" == --prepare ]] || { echo 'Use --prepare PORTAL_PATH' >&2; exit 2; }
PORTAL=$(readlink -f "${2:?Portal path required}")
ROOT=$(cd "$(dirname "$0")/.." && pwd)
[[ -f "$PORTAL/app/config.php" && "$PORTAL" == "$HOME/public_html/portal.iworksgt.com" ]] || exit 2
for tool in php mysql mysqldump gzip openssl sha256sum uapi tar; do command -v "$tool" >/dev/null; done
umask 077
OPS="$HOME/iworks-release-operations"
mkdir -p "$OPS"
chmod 0700 "$OPS"
BACKUP=$(mktemp -d "$OPS/agent-payout-backup-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")
WORK=$(mktemp -d "$OPS/.agent-backup-XXXXXXXX")
RESTORE_DB=''
cleanup() {
  if [[ -n "$RESTORE_DB" ]]; then
    uapi --output=json Mysql delete_database "name=$RESTORE_DB" > "$BACKUP/restore-cleanup.json" 2>&1 || true
  fi
  rm -rf "$WORK"
}
trap cleanup EXIT
php "$ROOT/scripts/cpanel_finalize_db.php" client-options "$PORTAL" "$WORK/mysql.cnf" "$WORK/db-name.txt"
DB_NAME=$(< "$WORK/db-name.txt")
KEYDIR="$HOME/iworks-release-keys"
mkdir -p "$KEYDIR"
chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
[[ ! -L "$KEY" ]] || exit 3
if [[ ! -e "$KEY" ]]; then openssl rand -hex 48 > "$KEY"; fi
chmod 0600 "$KEY"
[[ -s "$KEY" ]] || exit 3
mysqldump --defaults-extra-file="$WORK/mysql.cnf" --single-transaction --quick --skip-lock-tables \
  --no-tablespaces --routines --events --triggers --default-character-set=utf8mb4 "$DB_NAME" \
  2>"$BACKUP/dump-errors.log" | gzip -6 \
  | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
# The full portal includes its configuration, uploads and current runtime switch.
# Encryption happens in the pipeline; no plaintext SQL or archive is persisted.
tar -C "$PORTAL" -czf - . 2>"$BACKUP/files-errors.log" \
  | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -out "$BACKUP/portal.tar.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/portal.tar.gz.enc" \
  | tar -tzf - > "$BACKUP/file-manifest.txt"
grep -Fxq './app/config.php' "$BACKUP/file-manifest.txt"
grep -Fxq './index.php' "$BACKUP/file-manifest.txt"
DB_PREFIX=${DB_NAME%%_*}
[[ "$DB_PREFIX" =~ ^[a-zA-Z0-9]{1,24}$ ]] || exit 3
CANDIDATE="${DB_PREFIX}_iwpay$(date -u +%H%M%S)_$RANDOM"
[[ "$CANDIDATE" =~ ^[a-zA-Z0-9_]{1,64}$ && "$CANDIDATE" != "$DB_NAME" ]] || exit 3
exists=$(mysql --defaults-extra-file="$WORK/mysql.cnf" -N -B -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$CANDIDATE'")
[[ "$exists" == 0 ]] || exit 3
SQL_USER=$(mysql --defaults-extra-file="$WORK/mysql.cnf" -N -B -e "SELECT SUBSTRING_INDEX(CURRENT_USER(),'@',1)")
[[ "$SQL_USER" =~ ^[a-zA-Z0-9_]{1,64}$ ]] || exit 3
api_ok() { php -r '$r=json_decode(file_get_contents($argv[1]),true);exit(($r["result"]["status"]??0)==1?0:1);' "$1"; }
uapi --output=json Mysql create_database "name=$CANDIDATE" > "$BACKUP/restore-create.json" 2>&1
api_ok "$BACKUP/restore-create.json"
RESTORE_DB=$CANDIDATE
uapi --output=json Mysql set_privileges_on_database "user=$SQL_USER" "database=$RESTORE_DB" \
  'privileges=ALL%20PRIVILEGES' > "$BACKUP/restore-grant.json" 2>&1
api_ok "$BACKUP/restore-grant.json"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" \
  | gzip -dc | mysql --defaults-extra-file="$WORK/mysql.cnf" "$RESTORE_DB" \
  > "$BACKUP/restore-output.log" 2> "$BACKUP/restore-errors.log"
for table in users jobs invoices job_payments commission_payouts withdrawal_requests; do
  live=$(mysql --defaults-extra-file="$WORK/mysql.cnf" -N -B -e "SELECT COUNT(*) FROM $DB_NAME.$table")
  restored=$(mysql --defaults-extra-file="$WORK/mysql.cnf" -N -B -e "SELECT COUNT(*) FROM $RESTORE_DB.$table")
  [[ "$live" == "$restored" ]] || { echo "Restore count mismatch: $table" >&2; exit 3; }
  echo "PASS restored $table ($restored rows)" | tee -a "$BACKUP/restore-verification.txt"
done
uapi --output=json Mysql delete_database "name=$RESTORE_DB" > "$BACKUP/restore-cleanup.json" 2>&1
api_ok "$BACKUP/restore-cleanup.json"
RESTORE_DB=''
(cd "$BACKUP" && sha256sum database.sql.gz.enc portal.tar.gz.enc > SHA256SUMS)
chmod 0600 "$BACKUP"/*
printf '%s\n' "$PORTAL" > "$BACKUP/portal-path.txt"
date -u +%FT%TZ > "$BACKUP/RESTORE_VERIFIED"
echo "Verified encrypted backup: $BACKUP"
echo 'Production source, schema, webhook and payout mode were not changed.'
