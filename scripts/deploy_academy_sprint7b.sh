#!/usr/bin/env bash
# Calm Academy/portal navigation patch: source-only, backed up, CI gated.
set -Eeuo pipefail
umask 077
BASE=f219399e76f120c48049d951bca467714f8d05f7
LEGACY=86be6193e4d0b04fa43452a2e9a73edf9bed4d36
CLEAN=7564d94d98242e42d42f84e4a277c30caffa173f
ONBOARD=e9011344e7a3142aa909b62ff203858add6a5f75
WORKSPACE=58148ad01a78faf3fbd4931a7fc7a2478c764404
SPRINT2=2a20714b84d2709f55cb6c6d5e3d68be5a9393d5
COMPACT=f0f16aa3b8cef7e52d0c349587f1e39c283959fb
MOBILE=d7a74ce735f6b40a695280a5e406cf6b2f44947a
SPRINT3=035353ea0991f7eb07ebea7123ff47d5fc870764
SPRINT4=47a54dbb3f53314535492ee47ec417e35939aed5
SPRINT5=7e7f48404ba8b0f2ce5fb2b7aa9cf091b25b7c0e
HOTFIX=afe3295e61dfb453113b807c32ddde49aeeb5726
SPRINT6=211dff0e3516689ee20470be8ba17c11a5f77352
DURATION=ebd4639dcd58845ed58c8d2ddda3e47a760b38d7
REQUESTS=a40576dec30ea0b45c9685528cf576bc0880ee65
POLISH=c2d086384ac245e80a1c321b77a99ebc2d34b67b
GUIDE=3f57bdfa9f23750caf7d27c17134658db04ac765
HISTORY=9d2b53761ecb60fac5dbe0a71a7ee2613458ddb0
SPRINT7A=e5df3b9ed1832fc3a331f1b9a011608d642482b0
INTERFACE=e1195903d474719c247e07d3bde7f951c4b869fe
TARGET=e7a5a3f871b96ad916b54e30957b80c3f14271dc
ROOT="$(readlink -f "$HOME/public_html/portal.iworksgt.com")"
HTML="$(readlink -f "$HOME/public_html")"
[[ "$ROOT" == "$HTML/"* && -f "$ROOT/app/config.php" ]] || { echo 'BLOCK unexpected portal root'; exit 3; }
OPS="$HOME/iworks-release-operations"; mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another release running'; exit 3; }
WORK="$(mktemp -d "$OPS/.academy-clean.XXXXXXXX")"
BACKUP=""; STARTED=0
finish(){
  local rc=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Restoring previous interface after failed check.'
    if [[ -s "$WORK/existing" ]]; then tar -xzf "$BACKUP/source.tar.gz" -C "$ROOT" || echo 'WARNING manual rollback required'; fi
    if [[ -f "$WORK/added" ]]; then while IFS= read -r f; do rm -f -- "$ROOT/$f"; done < "$WORK/added"; fi
  fi
  rm -rf "$WORK"; rmdir "$LOCK" || true
  return "$rc"
}
trap finish EXIT
echo '=== Academy Sprint 7B timed assessments, fast actions and certificate hierarchy ==='
# Wait only while this exact pinned CI is queued or running; never bypass a failure.
CI_READY=0
for attempt in $(seq 1 25); do
  curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
  CI_STATE="$(php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]){echo ($r["status"]??"")==="completed"?($r["conclusion"]??"failure"):"pending";exit;}}echo "pending";' "$WORK/ci.json" "$TARGET")"
  if [[ "$CI_STATE" == success ]]; then CI_READY=1; echo 'PASS pinned CI'; break; fi
  [[ "$CI_STATE" == pending ]] || { echo "BLOCK pinned CI: $CI_STATE; no production changes"; exit 3; }
  echo "Pinned CI still running ($attempt/25); no production changes yet."
  [[ "$attempt" == 25 ]] || sleep 15
done
[[ "$CI_READY" == 1 ]] || { echo 'BLOCK pinned CI still pending; no production changes'; exit 3; }

REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
 : > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
for f in assets/js/academy-actions.js views/academy-assessments.php views/academy-builder.php views/academy-admin.php views/partials/academy-profile-dialog.php views/partials/academy-student-topbar.php assets/js/academy-profile.js app/academy_assessments.php views/academy-guide.php assets/js/academy-guide.js assets/js/academy-certificates.js views/academy-student.php views/academy-learning.php assets/css/academy.css views/partials/header.php academy.php; do
  [[ ! -L "$ROOT/$f" ]] || { echo "BLOCK unsafe $f"; exit 3; }
  mkdir -p "$WORK/target/$(dirname "$f")" "$WORK/base/$(dirname "$f")"
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/target/$f"
  case "$f" in *.php) php -l "$WORK/target/$f";; esac
  if [[ -f "$ROOT/$f" ]] && cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
  if [[ ! -e "$ROOT/$f" && ( "$f" == views/academy-student.php || "$f" == app/academy_learning.php || "$f" == views/academy-builder.php || "$f" == views/academy-learning.php || "$f" == app/academy_assessments.php || "$f" == views/academy-assessments.php || "$f" == app/academy_requests.php || "$f" == views/academy-guide.php || "$f" == assets/js/academy-guide.js || "$f" == assets/js/academy-certificates.js || "$f" == views/partials/academy-profile-dialog.php || "$f" == views/partials/academy-student-topbar.php || "$f" == assets/js/academy-profile.js || "$f" == assets/js/academy-actions.js ) ]]; then
    echo "$f" >> "$WORK/added"
  else
    [[ -f "$ROOT/$f" ]] || { echo "BLOCK missing $f"; exit 3; }
    MATCH=0
    for ref in "$INTERFACE" "$SPRINT7A" "$HISTORY" "$GUIDE" "$POLISH"; do
      if ! curl -fsSL --retry 2 "$REPO/$ref/$f" -o "$WORK/base/$f"; then continue; fi
      if cmp -s "$ROOT/$f" "$WORK/base/$f"; then MATCH=1; break; fi
    done
    [[ "$MATCH" == 1 ]] || { echo "BLOCK unexpected production source drift: $f"; exit 3; }
    echo "$f" >> "$WORK/existing"
  fi
  echo "$f" >> "$WORK/changes"
done
echo 'PASS existing source matches previously released interface'
checkout_state(){ php -r 'require $argv[1];require $argv[2];echo academy_checkout_enabled()?"1":"0";' "$ROOT/app/bootstrap.php" "$ROOT/app/academy.php"; }
BEFORE="$(checkout_state)"
[[ "$BEFORE" == 0 || "$BEFORE" == 1 ]] || { echo 'BLOCK cannot read checkout setting'; exit 3; }
echo "PASS captured existing checkout state"
# This update assumes the previously installed certification request workflow.
# Verify its unchanged dependencies before touching the source; no DB writes.
for f in app/academy_requests.php app/academy.php app/academy_learning.php; do
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/dependency"
  cmp -s "$ROOT/$f" "$WORK/dependency" || { echo "BLOCK existing Academy dependency differs: $f"; exit 3; }
done
php -r 'require $argv[1];db_one("SELECT id FROM academy_certification_requests LIMIT 1");echo "PASS certification request schema available\n";' "$ROOT/app/bootstrap.php"
# Schema is additive; never drop tables or restore over production on failure.
# A private encrypted snapshot is required before running the migration.
for tool in mysqldump gzip openssl sha256sum; do command -v "$tool" >/dev/null || { echo "BLOCK missing $tool"; exit 3; }; done
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/cpanel_finalize_db.php" -o "$WORK/cpanel_finalize_db.php"
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/migrate_academy_learning.php" -o "$WORK/migrate_learning.php"
php -l "$WORK/migrate_learning.php"
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/migrate_academy_assessments.php" -o "$WORK/migrate_assessments.php"
php -l "$WORK/migrate_assessments.php"
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/migrate_academy_requests.php" -o "$WORK/migrate_requests.php"
php -l "$WORK/migrate_requests.php"
BACKUP="$(mktemp -d "$OPS/academy-sprint7b-backup-XXXXXXXX")"; chmod 0700 "$BACKUP"
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
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/migrate_academy_timed_exams.php" -o "$WORK/migrate_timed.php"
php -l "$WORK/migrate_timed.php"
php "$WORK/migrate_timed.php" "$ROOT" --backup-confirmed
if [[ -s "$WORK/changes" ]]; then
  if [[ -s "$WORK/existing" ]]; then tar -czf "$BACKUP/source.tar.gz" -C "$ROOT" -T "$WORK/existing"; tar -tzf "$BACKUP/source.tar.gz" >/dev/null; fi
  cp "$WORK/added" "$BACKUP/added-files.txt"
  printf '%s\n' "$TARGET" > "$BACKUP/release.txt"
  STARTED=1
  while IFS= read -r f; do
    stage="$(mktemp "$(dirname "$ROOT/$f")/.academy-clean.XXXXXXXX")"
    cat "$WORK/target/$f" > "$stage"; if [[ -f "$ROOT/$f" ]]; then chmod --reference="$ROOT/$f" "$stage"; else chmod 0644 "$stage"; fi; mv -f "$stage" "$ROOT/$f"
    cmp -s "$ROOT/$f" "$WORK/target/$f"
  done < "$WORK/changes"
  echo "PASS source backup: $BACKUP"
fi
for route in student/register verify; do
  code="$(curl -sS --max-time 30 -o "$WORK/body" -w '%{http_code}' "https://portal.iworksgt.com/$route")"
  [[ "$code" == 200 ]] || { echo "BLOCK public route /$route returned $code"; exit 3; }
  if [[ "$route" == verify ]]; then grep -qi 'certificate number' "$WORK/body"; else grep -qi 'student registration' "$WORK/body"; fi
  echo "PASS /$route"
done
code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' 'https://portal.iworksgt.com/academy.php?action=admin')"
case "$code" in 301|302|303|401|403) echo 'PASS guest admin access denied';; *) echo "BLOCK admin returned $code"; exit 3;; esac
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/css/academy.css?v=17' -o "$WORK/style"
grep -Fq 'Native iWorks operations visual system' "$WORK/style"
grep -Fq '.ac-dashboard-columns' "$WORK/style"
grep -Fq 'repeat(3,minmax(0,1fr))!important' "$WORK/style"
for route in dashboard courses certificates profile 'builder&course=1' 'learn&course=1' lesson_complete 'material&course=1&lesson=1&id=1' assessment_submit exam_start certificate_preview preview_image request_certificate; do
  code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/academy.php?action=$route")"
  case "$code" in 301|302|303|401|403) echo "PASS guest access denied: $route";; *) echo "BLOCK private student screen returned $code"; exit 3;; esac
done
curl -fsSL --max-time 30 'https://portal.iworksgt.com/student/register' -o "$WORK/register"
grep -Fq 'Create your student account' "$WORK/register"
grep -Fq 'auth-clean.css' "$WORK/register"
grep -Fq 'iw-auth-home' "$WORK/register"
if grep -Fq 'Continue with Google' "$WORK/register"; then
  echo 'PASS Google button visible (interactive OAuth still requires real sign-in)'
else
  echo 'PENDING Google OAuth is not configured or available in current portal settings'
fi
grep -Fq 'data-iw-password="iwAcademyPassword"' "$WORK/register"
grep -Fq 'data-iw-password="iwAcademyConfirm"' "$WORK/register"
grep -Fq 'Sprint 4: purposeful student surfaces' "$WORK/style"
grep -Fq 'academy_notice' "$ROOT/app/academy.php"
grep -Fq 'academy_inquiry' "$ROOT/app/auth_rate_limits.php"
for route in 'notifications' 'realtime&scope=notifications'; do
  code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/index.php?page=$route")"
  case "$code" in 301|302|303|401|403) echo "PASS guest access denied: $route";; *) echo "BLOCK private notification route returned $code"; exit 3;; esac
done
AFTER="$(checkout_state)"
[[ "$AFTER" == "$BEFORE" ]] || { echo 'BLOCK checkout setting changed unexpectedly'; exit 3; }
grep -Fq 'Sprint 5: course builder and learning workspace' "$WORK/style"
grep -Fq 'academy_learning_complete' "$ROOT/app/academy_learning.php"
grep -Fq 'Sprint 6: materials and assessments' "$WORK/style"
grep -Fq 'Certification request workspace: compact' "$WORK/style"
grep -Fq 'Trust this private device for 30 days' "$WORK/register"
grep -Fq 'Student guidance: lightweight contextual hints' "$WORK/style"
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/js/academy-guide.js?v=4' -o "$WORK/guide.js"
cmp -s "$WORK/guide.js" "$WORK/target/assets/js/academy-guide.js"
echo 'PASS deployed guide script matches tested version'
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/js/academy-certificates.js?v=4' -o "$WORK/certificates.js"
cmp -s "$WORK/certificates.js" "$WORK/target/assets/js/academy-certificates.js"
grep -Fq 'Certificate activity history: concise rows' "$WORK/style"
grep -Fq 'Sprint 7A: light, action-led guidance' "$WORK/style"
echo 'PASS deployed certificate history script matches tested version'
grep -Fq 'Sprint 7B interface: single-row student header' "$WORK/style"
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/js/academy-profile.js?v=1' -o "$WORK/profile.js"
cmp -s "$WORK/profile.js" "$WORK/target/assets/js/academy-profile.js"
echo 'PASS deployed profile script matches tested version'
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/js/academy-actions.js?v=1' -o "$WORK/actions.js"
cmp -s "$WORK/actions.js" "$WORK/target/assets/js/academy-actions.js"
grep -Fq 'Certificate catalogue: clear request action' "$WORK/style"
STARTED=0
echo 'SUCCESS: Sprint 7B timed exams, progressive actions and certificate layout installed.'
if [[ "$AFTER" == 1 ]]; then echo 'LIVE CERTIFICATE CHECKOUT ENABLED (existing state preserved)'; else echo 'LIVE CERTIFICATE CHECKOUT DISABLED (existing state preserved)'; fi
echo 'Additive optional exam timer schema only. Existing exams remain untimed; no existing certificate or payment-setting changes.'

curl -fsSL --retry 2 "$REPO/$TARGET/scripts/academy_notification_health.php" -o "$WORK/notification-health.php"
php "$WORK/notification-health.php" "$ROOT" || echo "WARNING notification audit unavailable; release installed."

