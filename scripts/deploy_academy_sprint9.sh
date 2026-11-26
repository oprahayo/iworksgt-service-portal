#!/usr/bin/env bash
# Sprint 9: scoped Academy UI + transactional course import; no payment changes.
set -Eeuo pipefail
umask 077
TARGET="${1:-}"
[[ "$TARGET" =~ ^[0-9a-f]{40}$ ]] || { echo 'BLOCK immutable commit required'; exit 3; }
BASE=5ec9cc4969935cb8a0952094fc7a1391022457eb
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
HTML="$(readlink -f "$HOME/public_html")"
ROOT="$(readlink -f "$HTML/portal.iworksgt.com")"
[[ "$ROOT" == "$HTML/"* && -f "$ROOT/app/config.php" ]] || { echo 'BLOCK unexpected portal root'; exit 3; }
OPS="$HOME/iworks-release-operations"; mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"; mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another release running'; exit 3; }
WORK=""; BACKUP=""; STARTED=0
finish(){
 local rc=$?
 if [[ "$STARTED" == 1 ]]; then
   echo 'Release failed; restoring prior source.'
   tar -xzf "$BACKUP/source.tar.gz" -C "$ROOT" || echo 'WARNING manual source restore required'
 fi
 [[ -z "$WORK" ]] || rm -rf -- "$WORK"
 rmdir "$LOCK" || true
 return "$rc"
}
trap finish EXIT
WORK="$(mktemp -d "$OPS/.academy-sprint9.XXXXXXXX")"
echo '=== Academy Sprint 9: focused mobile lessons and Digital Marketing ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=30" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI not successful\n");exit(3);' "$WORK/ci.json" "$TARGET"
echo 'PASS pinned CI'
for f in academy.php app/academy.php app/academy_learning.php app/academy_assessments.php app/academy_requests.php assets/js/academy-actions.js; do
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/dependency"
 cmp -s "$ROOT/$f" "$WORK/dependency" || { echo "BLOCK prior sprint dependency differs: $f. Finish Sprint 8C first."; exit 3; }
done
: > "$WORK/changed"
for f in views/academy-admin.php views/academy-learning.php assets/css/academy.css views/partials/header.php; do
 [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]] || { echo "BLOCK missing or unsafe $f"; exit 3; }
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/target/$f"
 case "$f" in *.php) php -l "$WORK/target/$f";; esac
 if cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
 cmp -s "$ROOT/$f" "$WORK/base" || { echo "BLOCK unknown local source drift: $f"; exit 3; }
 echo "$f" >> "$WORK/changed"
done
for f in seed_academy_digital_marketing.php cpanel_finalize_db.php; do
 curl -fsSL --retry 2 "$REPO/$TARGET/scripts/$f" -o "$WORK/$f"; php -l "$WORK/$f"
done
php "$WORK/seed_academy_digital_marketing.php" --check
php -r 'require $argv[1];db_one("SELECT id,duration_minutes FROM academy_assessments LIMIT 1");db_one("SELECT course_id FROM academy_learning_courses LIMIT 1");echo "PASS existing learning schema\n";' "$ROOT/app/bootstrap.php"
for tool in mysqldump gzip openssl sha256sum; do command -v "$tool" >/dev/null || { echo "BLOCK missing $tool"; exit 3; }; done
BACKUP="$(mktemp -d "$OPS/academy-sprint9-backup-XXXXXXXX")"; chmod 0700 "$BACKUP"
php "$WORK/cpanel_finalize_db.php" client-options "$ROOT" "$WORK/mysql.cnf" "$WORK/db-name"
DB="$(cat "$WORK/db-name")"
mysqldump --defaults-extra-file="$WORK/mysql.cnf" --single-transaction --quick --skip-lock-tables --no-tablespaces --routines --events --triggers "$DB" | gzip -6 > "$WORK/database.sql.gz"
gzip -t "$WORK/database.sql.gz"
KEYDIR="$HOME/iworks-release-keys"; mkdir -p "$KEYDIR"; chmod 0700 "$KEYDIR"
KEY="$KEYDIR/mysql-backup.passphrase"
if [[ ! -e "$KEY" ]]; then openssl rand -hex 48 > "$KEY"; chmod 0600 "$KEY"; fi
[[ -f "$KEY" && -s "$KEY" && ! -L "$KEY" ]] || exit 3
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$WORK/database.sql.gz" -out "$BACKUP/database.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
sha256sum "$BACKUP/database.sql.gz.enc" > "$BACKUP/database.sql.gz.enc.sha256"
rm -f "$WORK/database.sql.gz" "$WORK/mysql.cnf"
echo "PASS encrypted database backup integrity: $BACKUP"

if [[ -s "$WORK/changed" ]]; then
 tar -czf "$BACKUP/source.tar.gz" -C "$ROOT" -T "$WORK/changed"; tar -tzf "$BACKUP/source.tar.gz" >/dev/null
 STARTED=1
 while IFS= read -r f; do
  stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-sprint9.XXXXXXXX")"
  cat "$WORK/target/$f" > "$stage"; chmod --reference="$ROOT/$f" "$stage"; mv -f "$stage" "$ROOT/$f"; cmp -s "$ROOT/$f" "$WORK/target/$f"
 done < "$WORK/changed"
fi
for route in 'student/register' 'verify'; do
 code="$(curl -sSL --max-time 30 -o "$WORK/body" -w '%{http_code}' "https://portal.iworksgt.com/$route")"
 [[ "$code" == 200 ]] || { echo "BLOCK public route $route returned $code"; exit 3; }
 if [[ "$route" == student/register ]]; then grep -qi 'Create your student account' "$WORK/body"; else grep -qi 'certificate number' "$WORK/body"; fi
done
for route in 'admin' 'learn&course=1'; do
 code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$route")"
 case "$code" in 301|302|303|401|403) ;; *) echo "BLOCK guest access check $route returned $code"; exit 3;; esac
done
curl -fsSL --max-time 30 "https://portal.iworksgt.com/assets/css/academy.css?v=18" -o "$WORK/live.css"
grep -Fq 'Sprint 9: focused reading' "$WORK/live.css"
# Last fallible step: import is transactional and refuses existing course content.
php "$WORK/seed_academy_digital_marketing.php" "$ROOT" --backup-confirmed
STARTED=0
echo 'PASS Sprint 9 source installed and course import verified. Check enrolled student and admin sessions on mobile.'
