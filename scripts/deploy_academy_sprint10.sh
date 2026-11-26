#!/usr/bin/env bash
# Sprint 10: private assignment PDF uploads and downloads.
set -Eeuo pipefail
umask 077
SHA="$1"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]]
BASE=a81f093dfae2dc4f76848f2ef085364cab871a95
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
   echo 'Restoring prior Academy source; additive audit table is preserved.'
   tar -xzf "$BACKUP/academy-source.tgz" -C "$ROOT" || echo "WARNING manual restore required: $BACKUP"
 fi
 if [[ -n "$WORK" ]]; then rm -f "$WORK/mysql.cnf" "$WORK/database.sql.gz"; fi
 rmdir "$LOCK" || true
 return "$rc"
}
trap finish EXIT
WORK="$(mktemp -d "$OPS/sprint10-stage-XXXXXXXX")"
BACKUP="$(mktemp -d "$OPS/sprint10-backup-XXXXXXXX")"
echo "=== Sprint 10: private assignment uploads ==="
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
: > "$WORK/changes"
for f in app/academy_assessments.php academy.php views/academy-assessments.php; do
 [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]]
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 php -l "$WORK/target/$f"
 if cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
 cmp -s "$ROOT/$f" "$WORK/base" || { echo "BLOCK source drift $f; complete Sprint 9B first"; exit 3; }
 echo "$f" >> "$WORK/changes"
done
for f in scripts/migrate_academy_assignment_files.php scripts/cpanel_finalize_db.php; do
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 case "$f" in *.php) php -l "$WORK/target/$f";; *.sh) bash -n "$WORK/target/$f";; esac
done
# Verify prior timed assessment schema and make a private encrypted database backup.
php -r 'require $argv[1];db_one("SELECT assessment_id,student_id,deadline_epoch FROM academy_exam_starts LIMIT 1");db_one("SELECT id,duration_minutes FROM academy_assessments LIMIT 1");db_one("SELECT assessment_id,student_id FROM academy_assessment_retakes LIMIT 1");db_one("SELECT id FROM academy_admin_history LIMIT 1");if(!class_exists("finfo"))exit(3);echo "PASS previous Academy schema\n";' "$ROOT/app/bootstrap.php"
for tool in mysqldump gzip openssl sha256sum; do command -v "$tool" >/dev/null; done
php "$WORK/target/scripts/cpanel_finalize_db.php" client-options "$ROOT" "$WORK/mysql.cnf" "$WORK/db-name"
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
php "$WORK/target/scripts/migrate_academy_assignment_files.php" "$ROOT" --backup-confirmed
php -r 'require $argv[1];db_one("SELECT submission_id,filename,size_bytes FROM academy_assignment_files LIMIT 1");echo "PASS private assignment file schema\n";' "$ROOT/app/bootstrap.php"
if [[ -s "$WORK/changes" ]]; then
 tar -czf "$BACKUP/academy-source.tgz" -C "$ROOT" -T "$WORK/changes"
 tar -tzf "$BACKUP/academy-source.tgz" >/dev/null
 STARTED=1
 while IFS= read -r f; do
  stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-assignment.XXXXXXXX")"
  cat "$WORK/target/$f" > "$stage"
  chmod --reference="$ROOT/$f" "$stage"
  mv -f "$stage" "$ROOT/$f"
  cmp -s "$ROOT/$f" "$WORK/target/$f"
  php -l "$ROOT/$f"
 done < "$WORK/changes"
fi
for action in admin learn builder assignment_file; do
 status="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$action")"
 case "$status" in 301|302|303|307|308|401|403) ;; *) echo "BLOCK unauthenticated route $action returned $status"; exit 3;; esac
done
STARTED=0
echo "PASS Sprint 10 installed; private backup: $BACKUP"
echo 'No real assignments submitted or student records changed by the installer.'
