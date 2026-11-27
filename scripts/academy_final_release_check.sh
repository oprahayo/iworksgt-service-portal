#!/usr/bin/env bash
# Final code hardening update and operator-controlled QA. Never enables checkout.
set -Eeuo pipefail
umask 077
ROOT="$HOME/public_html/portal.iworksgt.com"
REPO="https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal"
BASE=f9bba44224b29f41e6215e828d5b3d6f2f810e41
TARGET=6ddae2216194562c2c9ecb5da44d2ac34a83a8d0
WORK="$(mktemp -d "$HOME/.academy-final.XXXXXXXX")"
OPS="$HOME/iworks-release-operations"
mkdir -p "$OPS"; chmod 0700 "$OPS"
LOCK="$OPS/.academy-release-lock"
mkdir "$LOCK" 2>/dev/null || { rm -rf "$WORK"; echo 'BLOCK another release is in progress'; exit 3; }
CHANGED=0
BACKUP=""
finish(){
  local rc=$?
  if [[ "$CHANGED" == 1 ]]; then
    echo 'Restoring previous Academy source after patch check failure.'
    cp -p "$BACKUP/academy.php" "$ROOT/app/academy.php" || echo 'WARNING restore from private backup manually.'
  fi
  rm -rf "$WORK"; rmdir "$LOCK" || true
  return "$rc"
}
trap finish EXIT
[[ -f "$ROOT/app/config.php" && -f "$ROOT/app/academy.php" && ! -L "$ROOT/app/academy.php" ]] || { echo 'BLOCK invalid portal path'; exit 3; }
echo '=== Verified code update ==='
curl -fsSL --retry 2 "https://api.github.com/repos/oprahayo/iworksgt-service-portal/actions/runs?head_sha=$TARGET&per_page=20" -o "$WORK/ci.json"
php -r '$j=json_decode(file_get_contents($argv[1]),true);foreach($j["workflow_runs"]??[] as $r){if(($r["name"]??"")==="CI"&&($r["head_sha"]??"")===$argv[2]&&($r["conclusion"]??"")==="success"){echo "PASS pinned CI\n";exit(0);}}fwrite(STDERR,"BLOCK pinned CI not successful yet\n");exit(3);' "$WORK/ci.json" "$TARGET"
curl -fsSL --retry 2 "$REPO/$BASE/app/academy.php" -o "$WORK/previous.php"
mkdir -p "$WORK/source"
curl -fsSL --retry 2 --max-time 180 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$TARGET" -o "$WORK/source.tar.gz"
tar -xzf "$WORK/source.tar.gz" --strip-components=1 -C "$WORK/source"
SRC="$WORK/source"
cp "$ROOT/app/config.php" "$SRC/app/config.php"; chmod 0600 "$SRC/app/config.php"
if ! cmp -s "$ROOT/app/academy.php" "$WORK/previous.php" && ! cmp -s "$ROOT/app/academy.php" "$SRC/app/academy.php"; then echo 'BLOCK Academy source drift; no change'; exit 3; fi
php -l "$SRC/app/academy.php"
php "$SRC/tests/academy_smoke_test.php"
php "$SRC/tests/academy_payment_lifecycle_test.php"
php "$SRC/scripts/academy_preflight.php"
php -r 'require $argv[1];if(setting("academy_checkout_enabled","0")!=="0")exit(3);' "$ROOT/app/bootstrap.php"
if ! cmp -s "$ROOT/app/academy.php" "$SRC/app/academy.php"; then
  BACKUP="$(mktemp -d "$OPS/academy-hardening-backup-XXXXXXXX")"
  cp -p "$ROOT/app/academy.php" "$BACKUP/academy.php"
  cmp -s "$BACKUP/academy.php" "$ROOT/app/academy.php"
  CHANGED=1
  stage="$(mktemp "$ROOT/app/.academy-hardening.XXXXXXXX")"
  cat "$SRC/app/academy.php" > "$stage"; chmod --reference="$ROOT/app/academy.php" "$stage"; mv -f "$stage" "$ROOT/app/academy.php"
  cmp -s "$ROOT/app/academy.php" "$SRC/app/academy.php"
  php -l "$ROOT/app/academy.php"
  code="$(curl -sS --max-time 30 -o "$WORK/response" -w '%{http_code}' 'https://portal.iworksgt.com/verify')"
  [[ "$code" == 200 ]] && grep -qi 'certificate number' "$WORK/response"
  CHANGED=0
  echo "PASS production live-mode protection installed; backup: $BACKUP"
else
  echo 'PASS production live-mode protection already installed'
fi
echo '=== Live provider and scheduled mail readiness ==='
curl -fsSL --retry 2 "$REPO/bba8fc0c6f6b124efea732e43f948ee88b2466d5/scripts/academy_live_readiness.php" -o "$WORK/readiness.php"
php -l "$WORK/readiness.php"
READY=0
php "$WORK/readiness.php" "$ROOT" || READY=$?
echo '=== Real student and sandbox checks ==='
curl -fsSL --retry 2 "$REPO/d428cf5586c1e27b725fc28f6aa82d44123b6c74/scripts/academy_final_qa.sh" -o "$WORK/qa.sh"
bash -n "$WORK/qa.sh"
QA=0
bash "$WORK/qa.sh" || QA=$?
echo "Live readiness exit: $READY; real student/sandbox exit: $QA"
echo 'No live certification payments enabled. No production transaction initialized.'
if [[ "$READY" != 0 || "$QA" != 0 ]]; then echo 'PENDING one or more acceptance checks'; exit 3; fi
echo 'PASS live readiness and sandbox API checks; provider charge-to-certificate verification and backup restore rehearsal still required.'
