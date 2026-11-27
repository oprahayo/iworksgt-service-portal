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
SPRINT5=7e7f48404ba8b0f2ce5fb2b7aa9cf091b25b7c0e
SPRINT4=47a54dbb3f53314535492ee47ec417e35939aed5
TARGET=afe3295e61dfb453113b807c32ddde49aeeb5726
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
echo '=== Academy 30-day device trust and notification health ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS pinned CI\n";exit(0);}}fwrite(STDERR,"BLOCK pinned CI not yet successful\n");exit(3);' "$WORK/ci.json" "$TARGET"
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
 : > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
for f in app/auth.php index.php views/otp.php views/login.php academy.php views/academy-register.php; do
  [[ ! -L "$ROOT/$f" ]] || { echo "BLOCK unsafe $f"; exit 3; }
  mkdir -p "$WORK/target/$(dirname "$f")" "$WORK/base/$(dirname "$f")"
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/target/$f"
  case "$f" in *.php) php -l "$WORK/target/$f";; esac
  if [[ -f "$ROOT/$f" ]] && cmp -s "$ROOT/$f" "$WORK/target/$f"; then continue; fi
  if [[ "$f" == views/academy-student.php && ! -e "$ROOT/$f" ]]; then
    echo "$f" >> "$WORK/added"
  else
    [[ -f "$ROOT/$f" ]] || { echo "BLOCK missing $f"; exit 3; }
    MATCH=0
    for ref in "$SPRINT5" "$SPRINT4" "$SPRINT3" "$MOBILE" "$COMPACT" "$SPRINT2" "$WORKSPACE" "$BASE" "$ONBOARD" "$CLEAN" "$LEGACY"; do
      curl -fsSL --retry 2 "$REPO/$ref/$f" -o "$WORK/base/$f"
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
if [[ -s "$WORK/changes" ]]; then
  BACKUP="$(mktemp -d "$OPS/academy-clean-backup-XXXXXXXX")"; chmod 0700 "$BACKUP"
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
for route in dashboard courses certificates profile; do
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
for route in 'notifications' 'realtime&scope=notifications'; do
  code="$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://portal.iworksgt.com/index.php?page=$route")"
  case "$code" in 301|302|303|401|403) echo "PASS guest access denied: $route";; *) echo "BLOCK private notification route returned $code"; exit 3;; esac
done
AFTER="$(checkout_state)"
[[ "$AFTER" == "$BEFORE" ]] || { echo 'BLOCK checkout setting changed unexpectedly'; exit 3; }
grep -Fq 'Trust this private device for 30 days' "$WORK/register"
STARTED=0
echo 'SUCCESS: 30-day account-specific trusted-device fix installed.'
if [[ "$AFTER" == 1 ]]; then echo 'LIVE CERTIFICATE CHECKOUT ENABLED (existing state preserved)'; else echo 'LIVE CERTIFICATE CHECKOUT DISABLED (existing state preserved)'; fi
echo 'No database migration, payment changes, or new payment initiated.'
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/academy_notification_health.php" -o "$WORK/notification-health.php"
php -l "$WORK/notification-health.php"
php "$WORK/notification-health.php" "$ROOT"
