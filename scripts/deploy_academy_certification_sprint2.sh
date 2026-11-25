#!/usr/bin/env bash
# Calm Academy/portal navigation patch: source-only, backed up, CI gated.
set -Eeuo pipefail
umask 077
BASE=f219399e76f120c48049d951bca467714f8d05f7
LEGACY=86be6193e4d0b04fa43452a2e9a73edf9bed4d36
CLEAN=7564d94d98242e42d42f84e4a277c30caffa173f
ONBOARD=e9011344e7a3142aa909b62ff203858add6a5f75
WORKSPACE=58148ad01a78faf3fbd4931a7fc7a2478c764404
TARGET=2a20714b84d2709f55cb6c6d5e3d68be5a9393d5
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
echo '=== Academy Sprint 2: payment recovery and acceptance ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS pinned CI\n";exit(0);}}fwrite(STDERR,"BLOCK pinned CI not yet successful\n");exit(3);' "$WORK/ci.json" "$TARGET"
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
 : > "$WORK/existing"; : > "$WORK/added"; : > "$WORK/changes"
for f in assets/css/academy.css views/academy-student.php views/partials/header.php academy.php app/academy.php scripts/run_scheduled_operations.php; do
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
    for ref in "$WORKSPACE" "$BASE" "$ONBOARD" "$CLEAN" "$LEGACY"; do
      curl -fsSL --retry 2 "$REPO/$ref/$f" -o "$WORK/base/$f"
      if cmp -s "$ROOT/$f" "$WORK/base/$f"; then MATCH=1; break; fi
    done
    [[ "$MATCH" == 1 ]] || { echo "BLOCK unexpected production source drift: $f"; exit 3; }
    echo "$f" >> "$WORK/existing"
  fi
  echo "$f" >> "$WORK/changes"
done
echo 'PASS existing source matches previously released interface'
php -r 'require $argv[1];require $argv[2];if(academy_checkout_enabled()){fwrite(STDERR,"BLOCK certification checkout unexpectedly enabled\n");exit(3);}echo "PASS live checkout remains disabled\n";' "$ROOT/app/bootstrap.php" "$ROOT/app/academy.php"
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
curl -fsS --max-time 30 'https://portal.iworksgt.com/assets/css/academy.css?v=5' -o "$WORK/style"
grep -Fq '.ac-student-welcome' "$WORK/style"
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
php -r 'require $argv[1];require $argv[2];if(!function_exists("academy_reconcile_pending")||academy_checkout_enabled())exit(3);echo "PASS payment recovery installed; no live checkout activation\n";' "$ROOT/app/bootstrap.php" "$ROOT/app/academy.php"
grep -Fq 'academy_reconcile_pending' "$ROOT/scripts/run_scheduled_operations.php"
STARTED=0
echo 'SUCCESS: student workspace and scheduled certificate payment recovery installed.'
echo 'No database migration, payment configuration, student records or certificate artwork changed.'
echo '=== Read-only certification readiness ==='
php -r 'require $argv[1];require $argv[2];$key=academy_live_paystack_key();$response=gateway_json_get("https://api.paystack.co/integration/payment_session_timeout",$key);unset($key);if(($response["status"]??false)!==true)exit(3);echo "PASS live Paystack API authenticated without initializing a charge\n";$p=dirname(mail_log_path())."/cron-queue-status.json";$m=is_file($p)?json_decode(file_get_contents($p),true):[];if(($m["status"]??"")==="ok"&&strtotime($m["last_success_at"]??"")>=time()-1800)echo "PASS recent scheduled queue worker\n";else echo "PENDING recent scheduled queue worker\n";if(array_key_exists("academy_payments_checked",$m??[]))echo "PASS scheduled payment recovery metric exists\n";else echo "PENDING first scheduled run of new recovery code\n";' "$ROOT/app/bootstrap.php" "$ROOT/app/academy.php"
echo 'Webhook URL was previously confirmed by the operator; provider delivery evidence is still required.'
echo '=== Final interactive acceptance ==='
curl -fsSL --retry 2 "$REPO/$TARGET/scripts/academy_final_qa.sh" -o "$WORK/final-qa.sh"
bash -n "$WORK/final-qa.sh"
if bash "$WORK/final-qa.sh"; then
  echo 'PASS real student and sandbox payment acceptance.'
else
  echo 'PENDING one or more interactive acceptance checks. Source update remains installed; checkout is disabled.'
  exit 3
fi
echo 'Remaining: actual provider delivery evidence, isolated backup restoration, and authorized live checkout activation. No charge has been made.'
