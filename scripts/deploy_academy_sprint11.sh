#!/usr/bin/env bash
# Sprint 11: read-only academic transcripts. No database migration.
set -Eeuo pipefail
umask 077
SHA="$1"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]]
BASE=a47a590ae6749336ddbc1e17a990980841bf8d45
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
WORK="$(mktemp -d "$OPS/sprint11-stage-XXXXXXXX")"
BACKUP="$(mktemp -d "$OPS/sprint11-backup-XXXXXXXX")"
echo "=== Sprint 11: downloadable academic transcripts ==="
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$SHA&per_page=30" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success")exit(0);}fwrite(STDERR,"BLOCK pinned CI has not passed\n");exit(3);' "$WORK/ci.json" "$SHA"
: > "$WORK/changes"
for f in academy.php views/academy-learning.php views/academy-admin.php views/academy-student.php; do
 [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]]
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 php -l "$WORK/target/$f"
 if cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
 curl -fsSL --retry 2 "$REPO/$BASE/$f" -o "$WORK/base"
 cmp -s "$ROOT/$f" "$WORK/base" || { echo "BLOCK source drift $f; complete Sprint 10 first"; exit 3; }
 echo "$f" >> "$WORK/changes"
done
for f in app/academy_transcript.php scripts/render_academy_transcript.py; do
 mkdir -p "$WORK/target/$(dirname "$f")"
 curl -fsSL --retry 2 "$REPO/$SHA/$f" -o "$WORK/target/$f"
 case "$f" in *.php) php -l "$WORK/target/$f";; esac
 [[ ! -L "$ROOT/$f" ]]
 if [[ -e "$ROOT/$f" ]]; then cmp -s "$ROOT/$f" "$WORK/target/$f" || { echo "BLOCK unexpected transcript file $f"; exit 3; }; fi
done
php -r 'require $argv[1];db_one("SELECT submission_id FROM academy_assignment_files LIMIT 1");db_one("SELECT assessment_id FROM academy_assessment_retakes LIMIT 1");echo "PASS prior Academy schema\n";' "$ROOT/app/bootstrap.php"
PY="$HOME/.iworks-academy-renderer/bin/python"
FONTS="$HOME/.local/share/fonts/iworks-academy"
LOGO="$ROOT/assets/branding/iworks-email-logo.png"
[[ -x "$PY" && -r "$LOGO" && -r "$FONTS/Lato-Regular.ttf" && -r "$FONTS/Lato-Bold.ttf" ]]
printf '%s' '{"student":"ACADEMY TEST STUDENT","course":"Web Development","status":"Enrolled","approval":"Not yet approved","certificate":"Not issued","generated":"Synthetic deployment check","completed":1,"total":3,"results":[{"title":"Quiz","kind":"Quiz","pass":"60%","result":"85% - Passed"}]}' > "$WORK/fixture.json"
"$PY" -I "$WORK/target/scripts/render_academy_transcript.py" "$WORK/fixture.json" "$WORK/fixture.pdf" "$FONTS" "$LOGO"
php -r 'if(substr(file_get_contents($argv[1]),0,5)!=="%PDF-")exit(1);echo "PASS transcript PDF renderer\n";' "$WORK/fixture.pdf"
# New helper and renderer are inert until the controller is updated.
for f in app/academy_transcript.php scripts/render_academy_transcript.py; do
 if [[ ! -e "$ROOT/$f" ]]; then install -m 0644 "$WORK/target/$f" "$ROOT/$f"; fi
done
if [[ -s "$WORK/changes" ]]; then
 tar -czf "$BACKUP/academy-source.tgz" -C "$ROOT" -T "$WORK/changes"
 tar -tzf "$BACKUP/academy-source.tgz" >/dev/null
 STARTED=1
 while IFS= read -r f; do
  stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-transcript.XXXXXXXX")"
  cat "$WORK/target/$f" > "$stage"
  chmod --reference="$ROOT/$f" "$stage"
  mv -f "$stage" "$ROOT/$f"
  cmp -s "$ROOT/$f" "$WORK/target/$f"
  php -l "$ROOT/$f"
 done < "$WORK/changes"
fi
for action in admin learn transcript assignment_file; do
 status="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$action")"
 case "$status" in 301|302|303|307|308|401|403) ;; *) echo "BLOCK unauthenticated route $action returned $status"; exit 3;; esac
done
STARTED=0
echo "PASS Sprint 11 installed; private backup: $BACKUP"
echo 'No student, payment or certificate records changed by the installer.'
