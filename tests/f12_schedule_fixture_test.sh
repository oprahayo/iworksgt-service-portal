#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
export HOME="$TMP/home"
mkdir -p "$HOME" "$TMP/bin" "$TMP/portal/scripts"
printf '<?php // synthetic worker only\n' > "$TMP/portal/scripts/run_scheduled_operations.php"
printf '' > "$TMP/crontab"
cat > "$TMP/bin/crontab" <<'FAKE'
#!/usr/bin/env bash
if [[ "$1" == "-l" ]]; then cat "$IWORKS_TEST_CRONTAB"; else cp "$1" "$IWORKS_TEST_CRONTAB"; fi
FAKE
chmod +x "$TMP/bin/crontab"
export PATH="$TMP/bin:$PATH"
export IWORKS_TEST_CRONTAB="$TMP/crontab"
bash "$ROOT/scripts/install_scheduled_jobs.sh" --check "$TMP/portal" "$(command -v php)" > "$TMP/check.txt"
grep -q 'OTP cron: missing' "$TMP/check.txt"
if bash "$ROOT/scripts/install_scheduled_jobs.sh" --apply "$TMP/portal" "$(command -v php)" > /dev/null 2>&1; then
  echo 'Missing external-scheduler confirmation should block installation.' >&2
  exit 1
fi
bash "$ROOT/scripts/install_scheduled_jobs.sh" --apply "$TMP/portal" "$(command -v php)" --confirm-no-external-scheduler > /dev/null
bash "$ROOT/scripts/install_scheduled_jobs.sh" --apply "$TMP/portal" "$(command -v php)" --confirm-no-external-scheduler > /dev/null
[[ "$(grep -c 'iworks-otp-queue-v1' "$TMP/crontab")" == 1 ]]
[[ "$(grep -c 'iworks-crypto-rates-v1' "$TMP/crontab")" == 1 ]]
[[ "$(stat -c %a "$HOME/iworks-private-cron-backup")" == 700 ]]
[[ "$(find "$HOME/iworks-private-cron-backup" -name '*.txt' | wc -l)" -ge 1 ]]
grep -Fq 'flock($lock, LOCK_EX | LOCK_NB)' "$ROOT/scripts/run_scheduled_operations.php"
grep -Fq 'last_success_at' "$ROOT/scripts/run_scheduled_operations.php"
echo 'f12_schedule_fixture_test passed'
