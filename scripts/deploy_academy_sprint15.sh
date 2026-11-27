#!/usr/bin/env bash
# Sprint 15: compact dashboard shortcuts and course content management.
set -Eeuo pipefail
umask 077
SHA="$1"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]]
BASE=7d1e7ad7e9f679729e232908fb1d8dfb8e110e73
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
WORK="$(mktemp -d "$OPS/sprint15-stage-XXXXXXXX")"
BACKUP="$(mktemp -d "$OPS/sprint15-backup-XXXXXXXX")"
echo "=== Sprint 15: dashboard shortcuts and course builder ==="
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
: > "$WORK/changes"
for f in assets/css/academy-compact.css views/academy-builder.php views/academy-student.php views/academy-admin.php academy.php; do
 [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]]
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 case "$f" in *.php) php -l "$WORK/target/$f";; esac
 if cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
 cmp -s "$ROOT/$f" "$WORK/base" || { echo "BLOCK source drift $f; complete Sprint 14 first"; exit 3; }
 echo "$f" >> "$WORK/changes"
done
for f in app/academy_admin.php app/academy_assessments.php app/academy_transcript.php views/academy-learning.php; do
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/prior"
 cmp -s "$ROOT/$f" "$WORK/prior" || { echo "BLOCK prerequisite differs: $f"; exit 3; }
done
php -r 'require $argv[1];db_one("SELECT submission_id FROM academy_assignment_files LIMIT 1");db_one("SELECT id FROM academy_admin_history LIMIT 1");echo "PASS prior Academy schema\n";' "$ROOT/app/bootstrap.php"
if [[ -s "$WORK/changes" ]]; then
 tar -czf "$BACKUP/academy-source.tgz" -C "$ROOT" -T "$WORK/changes"
 tar -tzf "$BACKUP/academy-source.tgz" >/dev/null
 STARTED=1
 while IFS= read -r f; do
  stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-dashboard.XXXXXXXX")"
  cat "$WORK/target/$f" > "$stage"
  chmod --reference="$ROOT/$f" "$stage"
  mv -f "$stage" "$ROOT/$f"
  cmp -s "$ROOT/$f" "$WORK/target/$f"
  case "$f" in *.php) php -l "$ROOT/$f";; esac
 done < "$WORK/changes"
fi
curl -fsSL --retry 2 --max-time 30 "https://portal.iworksgt.com/assets/css/academy-compact.css?v=15" -o "$WORK/live.css"
cmp -s "$WORK/live.css" "$WORK/target/assets/css/academy-compact.css" || { echo "BLOCK live stylesheet differs"; exit 3; }
for action in admin learn transcript assignment_file; do
 status="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$action")"
 case "$status" in 301|302|303|307|308|401|403) ;; *) echo "BLOCK unauthenticated route $action returned $status"; exit 3;; esac
done
STARTED=0
echo "PASS Sprint 15 installed; private backup: $BACKUP"
echo 'No enrollment, grade, certificate or payment records changed by installer.'
