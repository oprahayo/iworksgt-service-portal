#!/usr/bin/env bash
# cPanel cron setup; don't install before checking external schedulers.
# bash scripts/install_scheduled_jobs.sh --check PORTAL_PATH PHP_BIN
# bash scripts/install_scheduled_jobs.sh --apply PORTAL_PATH PHP_BIN --confirm-no-external-scheduler
set -Eeuo pipefail
if [[ "$#" -lt 3 ]]; then echo 'Missing mode, portal path or PHP binary.' >&2; exit 2; fi
MODE="$1"
PORTAL="$2"
PHP_BIN="$3"
[[ "$MODE" == "--check" || "$MODE" == "--apply" ]] || exit 2
[[ -f "$PORTAL/scripts/run_scheduled_operations.php" ]] || { echo 'Worker missing from portal.' >&2; exit 1; }
[[ -x "$PHP_BIN" ]] || { echo 'PHP executable not found.' >&2; exit 1; }
CRONTAB_BIN="${IWORKS_CRONTAB_BIN:-$(command -v crontab || true)}"
[[ -n "$CRONTAB_BIN" && -x "$CRONTAB_BIN" ]] || { echo 'crontab is unavailable. Use cPanel Cron Jobs UI.' >&2; exit 1; }
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
"$CRONTAB_BIN" -l > "$TMP" 2>/dev/null || :
if grep -Fq '# iworks-otp-queue-v1' "$TMP"; then echo 'OTP cron: installed'; else echo 'OTP cron: missing'; fi
if grep -Fq '# iworks-crypto-rates-v1' "$TMP"; then echo 'Crypto rates cron: installed'; else echo 'Crypto rates cron: missing'; fi
if [[ "$MODE" == "--check" ]]; then
  echo 'CHECK ONLY: inspect external cPanel schedulers before applying.'
  exit 0
fi
if [[ "$#" -lt 4 || "$4" != "--confirm-no-external-scheduler" ]]; then
  echo 'Confirm no external scheduler exists before installing to avoid duplicates.' >&2
  exit 2
fi
BACKUP="${IWORKS_CRON_BACKUP_DIR:-$HOME/iworks-private-cron-backup}"
mkdir -p "$BACKUP"
chmod 0700 "$BACKUP"
cp "$TMP" "$BACKUP/crontab-before-$(date -u +%Y%m%dT%H%M%S)-$BASHPID.txt"
chmod 0600 "$BACKUP"/*.txt
if ! grep -Fq '# iworks-otp-queue-v1' "$TMP"; then
  printf '* * * * * "%s" "%s/scripts/run_scheduled_operations.php" queue >/dev/null 2>&1 # iworks-otp-queue-v1\n' "$PHP_BIN" "$PORTAL" >> "$TMP"
fi
if ! grep -Fq '# iworks-crypto-rates-v1' "$TMP"; then
  printf '*/30 * * * * "%s" "%s/scripts/run_scheduled_operations.php" rates >/dev/null 2>&1 # iworks-crypto-rates-v1\n' "$PHP_BIN" "$PORTAL" >> "$TMP"
fi
"$CRONTAB_BIN" "$TMP"
echo 'Scheduled jobs installed. Inspect private cron status files for processing and upstream failures.'
