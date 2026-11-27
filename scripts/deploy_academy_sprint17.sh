#!/usr/bin/env bash
# Sprint 17: admin hierarchy, registration and guarded student deletion.
set -Eeuo pipefail
umask 077
SHA="$1"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]]
BASE=5dc6fc16114091a455453c78de14e25fc5e715c6
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
PUBLIC="$(readlink -f "$HOME/public_html")"
ROOT="$(readlink -f "$PUBLIC/portal.iworksgt.com")"
[[ "$ROOT" == "$PUBLIC/"* && -f "$ROOT/app/config.php" ]]
OPS="$HOME/iworks-release-operations"
mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" || { echo 'BLOCK another Academy release running'; exit 3; }
WORK=""; STARTED=0; BACKUP=""
finish(){
 local rc=$?
 if [[ "$STARTED" == 1 ]]; then
   echo 'Restoring prior Academy source.'
   tar -xzf "$BACKUP/academy-source.tgz" -C "$ROOT" || echo "WARNING manual restore required: $BACKUP"
 fi
 if [[ -n "$WORK" ]]; then rm -f "$WORK/mysql.cnf" "$WORK/database.sql.gz"; fi
 rmdir "$LOCK" || true
 return "$rc"
}
trap finish EXIT
WORK="$(mktemp -d "$OPS/sprint17-stage-XXXXXXXX")"
BACKUP="$(mktemp -d "$OPS/sprint17-backup-XXXXXXXX")"
echo "=== Sprint 17: student management and registration ==="
PHP=php
for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php; do
 if [[ -x "$candidate" ]] && "$candidate" -r 'exit(extension_loaded("pdo_mysql")&&extension_loaded("mbstring")?0:1);' ; then PHP="$candidate"; break; fi
done
echo "Using PHP CLI: $PHP"

curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
"$PHP" -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
: > "$WORK/changes"
for f in assets/css/academy-compact.css app/academy_admin.php views/academy-admin.php academy.php; do
 [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]]
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 case "$f" in *.php) "$PHP" -l "$WORK/target/$f";; esac
 if cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
 cmp -s "$ROOT/$f" "$WORK/base" || { echo "BLOCK source drift $f; complete Sprint 16 first"; exit 3; }
 echo "$f" >> "$WORK/changes"
done
for f in app/academy_assessments.php app/academy_transcript.php views/academy-learning.php views/academy-builder.php views/academy-student.php; do
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/prior"
 cmp -s "$ROOT/$f" "$WORK/prior" || { echo "BLOCK prerequisite differs: $f"; exit 3; }
done
"$PHP" -r 'require $argv[1];db_one("SELECT submission_id FROM academy_assignment_files LIMIT 1");db_one("SELECT id FROM academy_admin_history LIMIT 1");echo "PASS prior Academy schema\n";' "$ROOT/app/bootstrap.php"
for f in scripts/migrate_academy_student_deletions.php scripts/cpanel_finalize_db.php; do
 mkdir -p "$WORK/target/scripts"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 "$PHP" -l "$WORK/target/$f"
done
for tool in mysqldump gzip openssl sha256sum; do command -v "$tool" >/dev/null; done
"$PHP" "$WORK/target/scripts/cpanel_finalize_db.php" client-options "$ROOT" "$WORK/mysql.cnf" "$WORK/db-name"
DB="$(cat "$WORK/db-name")"
mysqldump --defaults-extra-file="$WORK/mysql.cnf" --single-transaction --quick --skip-lock-tables --no-tablespaces --routines --events --triggers "$DB" | gzip -6 > "$WORK/database.sql.gz"
gzip -t "$WORK/database.sql.gz"
KEYDIR="$HOME/iworks-release-keys"; mkdir -p "$KEYDIR"; chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
if [[ ! -e "$KEY" ]]; then openssl rand -hex 48 > "$KEY"; chmod 0600 "$KEY"; fi
[[ -f "$KEY" && -s "$KEY" && ! -L "$KEY" ]]
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$WORK/database.sql.gz" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
sha256sum "$BACKUP/database.sql.gz.enc" > "$BACKUP/database.sql.gz.enc.sha256"
rm -f "$WORK/database.sql.gz" "$WORK/mysql.cnf"
echo "PASS encrypted database backup: $BACKUP"
"$PHP" "$WORK/target/scripts/migrate_academy_student_deletions.php" "$ROOT" --backup-confirmed
"$PHP" -r 'require $argv[1];db_one("SELECT deleted_student_id,admin_id FROM academy_student_deletions LIMIT 1");echo "PASS deletion audit schema\n";' "$ROOT/app/bootstrap.php"
if [[ -s "$WORK/changes" ]]; then
 tar -czf "$BACKUP/academy-source.tgz" -C "$ROOT" -T "$WORK/changes"
 tar -tzf "$BACKUP/academy-source.tgz" >/dev/null
 STARTED=1
 while IFS= read -r f; do
  stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-management.XXXXXXXX")"
  cat "$WORK/target/$f" > "$stage"
  chmod --reference="$ROOT/$f" "$stage"
  mv -f "$stage" "$ROOT/$f"
  cmp -s "$ROOT/$f" "$WORK/target/$f"
  case "$f" in *.php) "$PHP" -l "$ROOT/$f";; esac
 done < "$WORK/changes"
fi
curl -fsSL --retry 2 --max-time 30 "https://portal.iworksgt.com/assets/css/academy-compact.css?v=17" -o "$WORK/live.css"
cmp -s "$WORK/live.css" "$WORK/target/assets/css/academy-compact.css" || { echo "BLOCK live stylesheet differs"; exit 3; }
for action in admin learn transcript assignment_file; do
 status="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$action")"
 case "$status" in 301|302|303|307|308|401|403) ;; *) echo "BLOCK unauthenticated route $action returned $status"; exit 3;; esac
done
curl -fsSL --retry 2 --max-time 30 "https://portal.iworksgt.com/academy.php?action=register" -o "$WORK/registration.html"
grep -Fq "Create your student account" "$WORK/registration.html" || { echo "BLOCK public student registration form unavailable"; exit 3; }
echo "PASS public student registration form"
STARTED=0
echo "PASS Sprint 17 installed; private backup: $BACKUP"
echo 'No real student accounts deleted or payment records changed by installer.'
