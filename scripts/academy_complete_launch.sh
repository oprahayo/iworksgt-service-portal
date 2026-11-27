#!/usr/bin/env bash
# Install verified auth hotfix and optionally enable checkout. No test or live payment is initiated.
set -Eeuo pipefail
umask 077
REPO=https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal
ROOT="$(readlink -f "$HOME/public_html/portal.iworksgt.com")"
OPS="$HOME/iworks-release-operations"
[[ -f "$ROOT/app/config.php" && "$ROOT" == "$(readlink -f "$HOME/public_html")/"* ]] || exit 3
mkdir -p "$OPS"; chmod 0700 "$OPS"
WORK="$(mktemp -d "$OPS/.academy-final.XXXXXXXX")"
LOCK="$OPS/.academy-release-lock"
LOCKED=0
cleanup(){ if [[ "$LOCKED" == 1 ]]; then rmdir "$LOCK" || true; fi; rm -rf "$WORK"; }
trap cleanup EXIT
curl -fsSL --retry 2 "$REPO/2209fa2547a491ab2716aee183603916a2652710/scripts/deploy_academy_device_trust.sh" -o "$WORK/hotfix.sh"
bash -n "$WORK/hotfix.sh"
bash "$WORK/hotfix.sh"
mkdir "$LOCK" 2>/dev/null || { echo 'BLOCK another release running'; exit 3; }
LOCKED=1
TARGET=afe3295e61dfb453113b807c32ddde49aeeb5726
for f in academy.php app/auth.php app/academy.php app/academy_learning.php index.php views/academy-student.php views/academy-admin.php views/academy-register.php scripts/run_scheduled_operations.php; do
  [[ -f "$ROOT/$f" && ! -L "$ROOT/$f" ]] || { echo "BLOCK missing $f"; exit 3; }
  curl -fsSL --retry 2 "$REPO/$TARGET/$f" -o "$WORK/expected"
  cmp -s "$ROOT/$f" "$WORK/expected" || { echo "BLOCK unexpected source drift: $f"; exit 3; }
done
echo 'PASS installed payment and authentication source matches tested release'
curl -fsSL --retry 2 "$REPO/c4ccc3d616d88008082ad26dd3a9555e0065014f/scripts/academy_launch_gate.php" -o "$WORK/gate.php"
php -l "$WORK/gate.php"
if php "$WORK/gate.php" "$ROOT" status; then echo 'Checkout already enabled; no settings changed.'; exit 0; fi
# Confirm integrity of the existing Sprint 5 private encrypted backup.
BACKUP="$(find "$OPS" -maxdepth 1 -type d -name 'academy-sprint5-backup-*' -printf '%T@ %p\n' | sort -nr | head -n 1 | cut -d' ' -f2-)"
[[ -n "$BACKUP" && -f "$BACKUP/database.sql.gz.enc" && -f "$BACKUP/database.sql.gz.enc.sha256" ]] || { echo 'BLOCK Sprint 5 encrypted backup not found'; exit 3; }
(cd "$BACKUP"; sha256sum -c database.sql.gz.enc.sha256)
KEY="$HOME/iworks-release-keys/mysql-backup.passphrase"
[[ -f "$KEY" && ! -L "$KEY" && -s "$KEY" ]] || exit 3
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$BACKUP/database.sql.gz.enc" | gzip -t
echo 'PASS existing encrypted backup integrity (not an isolated restore rehearsal)'
php "$WORK/gate.php" "$ROOT" check
echo 'Existing sandbox payment: NGN 102,000, Paystack dashboard showed successful on 25 Nov 2026.'
echo 'Post-completion sandbox API verification and isolated backup restore are still outstanding.'
echo 'This controlled launch accepts the recorded dashboard evidence; the live verification rules remain unchanged.'
echo 'Type ENABLE to authorize live certificate checkout at NGN 102,000. No charge is initiated here.'
read -r confirmation </dev/tty
[[ "$confirmation" == ENABLE ]] || { echo 'Checkout remains disabled.'; exit 0; }
php "$WORK/gate.php" "$ROOT" enable
php "$WORK/gate.php" "$ROOT" status
echo 'SUCCESS: live checkout enabled. Verify first real student payment, certificate download and inbox receipt.'
