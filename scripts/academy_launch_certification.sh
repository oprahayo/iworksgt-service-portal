#!/usr/bin/env bash
# Authorized live Academy activation; no live or sandbox payment initialization.
set -Eeuo pipefail
umask 077
TARGET=035353ea0991f7eb07ebea7123ff47d5fc870764
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
ROOT="$(readlink -f "$HOME/public_html/portal.iworksgt.com")"
HTML="$(readlink -f "$HOME/public_html")"
[[ "$ROOT" == "$HTML/"* && -f "$ROOT/app/config.php" ]] || { echo 'BLOCK unexpected portal root'; exit 3; }
for tool in curl php mysql mysqldump gzip openssl sha256sum readlink mktemp awk cmp grep uapi; do command -v "$tool" >/dev/null || { echo "BLOCK missing $tool"; exit 3; }; done
OPSDIR="$HOME/iworks-release-operations"; mkdir -p "$OPSDIR"; chmod 0700 "$OPSDIR"
LOCK="$OPSDIR/.academy-release-lock"; mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another release running'; exit 3; }
WORK="$(mktemp -d "$OPSDIR/.academy-launch.XXXXXXXX")"
RESTORE_CREATED=""; BACKUP=""
cleanup(){
  local rc=$?
  unset TEST_KEY
  if [[ -n "$RESTORE_CREATED" ]]; then
    uapi --output=json Mysql delete_database "name=$RESTORE_CREATED" >"$BACKUP/uapi-cleanup.log" 2>&1 || echo 'WARNING isolated rehearsal schema cleanup requires attention'
    php -r '$j=json_decode(file_get_contents($argv[1]),true);exit(($j["result"]["status"]??0)==1?0:1);' "$BACKUP/uapi-cleanup.log" || echo 'WARNING isolated rehearsal schema cleanup not confirmed'
  fi
  rm -rf "$WORK"; rmdir "$LOCK" || true
  return "$rc"
}
trap cleanup EXIT
echo '=== Final Academy activation: existing sandbox transaction only ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS pinned Sprint 3 CI\n";exit(0);}}exit(3);' "$WORK/ci.json" "$TARGET"
for f in assets/css/academy.css views/academy-admin.php views/academy-student.php views/partials/header.php academy.php app/academy.php scripts/run_scheduled_operations.php; do
  [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]] || { echo "BLOCK missing or unsafe $f"; exit 3; }
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/compare"
  cmp -s "$ROOT/$f" "$WORK/compare" || { echo "BLOCK unexpected production source drift: $f"; exit 3; }
done
echo 'PASS installed Sprint 3 source matches tested release'
curl -fsSL --retry 2 "$REPO/aac853ee98dc69db539490a4969898aa33341bae/scripts/academy_verify_completed_sandbox.php" -o "$WORK/verify.php"
curl -fsSL --retry 2 "$REPO/c4ccc3d616d88008082ad26dd3a9555e0065014f/scripts/academy_launch_gate.php" -o "$WORK/gate.php"
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/cpanel_finalize_db.php" -o "$WORK/cpanel_finalize_db.php"
php -l "$WORK/verify.php"; php -l "$WORK/gate.php"; php -l "$WORK/cpanel_finalize_db.php"
php "$WORK/gate.php" "$ROOT" check
echo 'Enter the same Paystack TEST secret once. No new checkout will be created.'
read -r -s -p 'Paystack test secret (hidden): ' TEST_KEY </dev/tty; echo
printf '%s\n' "$TEST_KEY" | php "$WORK/verify.php" "$ROOT"
unset TEST_KEY
echo '== Creating complete, private, encrypted database backup =='
BACKUP="$(mktemp -d "$OPSDIR/backup-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
chmod 0700 "$BACKUP"
OPTS="$WORK/mysql.cnf"
php "$WORK/cpanel_finalize_db.php" client-options "$ROOT" "$OPTS" "$WORK/db-name.txt"
DB_NAME="$(< "$WORK/db-name.txt")"
# Do not restore executable database objects into the live server under another schema.
OBJECTS="$(mysql --defaults-extra-file="$OPTS" -N -B -e "SELECT (SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='$DB_NAME')+(SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA='$DB_NAME')+(SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='$DB_NAME')")"
[[ "$OBJECTS" == 0 ]] || { echo "BLOCK isolated restore requires review of database programmable objects"; exit 3; }
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
if ! php -r '$r=json_decode(file_get_contents($argv[1]),true);exit(($r["result"]["status"]??0)==1?0:1);' \
  "$BACKUP/uapi-create.log"; then
  echo 'BLOCK: cPanel UAPI did not confirm rehearsal database creation.' >&2
  exit 3
fi
RESTORE_CREATED="$RESTORE_DB"
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
for table in users jobs invoices job_payments academy_courses academy_enrollments academy_graduates academy_certificates; do
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
echo '=== Controlled live certification launch ==='
echo 'Live webhook URL was confirmed in Paystack; actual provider delivery and issued-certificate inbox receipt remain first-payment acceptance checks.'
echo 'The scheduled payment recovery and admin verify/retry controls are ready. No payment is initiated here.'
echo 'Type ENABLE to authorize live certificate checkout at NGN 102,000 for approved students.'
read -r confirmation </dev/tty
[[ "$confirmation" == ENABLE ]] || { echo 'Cancelled; live checkout remains disabled.'; exit 0; }
php "$WORK/gate.php" "$ROOT" enable
php "$WORK/gate.php" "$ROOT" status
echo 'Student registration: https://portal.iworksgt.com/student/register'
echo 'Admin approval: https://portal.iworksgt.com/academy.php?action=admin'
echo "Private verified backup: $BACKUP"
echo 'SUCCESS: live certification checkout enabled. Monitor the first paid certificate through download and actual inbox receipt before inviting the remaining students.'
