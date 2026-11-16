#!/usr/bin/env bash
# Source-only iWORKS invoice/payment review refresh.
# bash scripts/deploy_invoice_payment_layout.sh --check|--apply FULL_SHA PORTAL_ROOT
# Only the verified presentation files are modified; no migrations or payment changes.
set -Eeuo pipefail
[[ "$#" -eq 3 ]] || { echo 'Usage: --check|--apply FULL_SHA PORTAL_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Choose --check or --apply.' >&2; exit 2; }
[[ "$RELEASE" =~ ^[0-9a-f]{40}$ ]] || { echo 'Full immutable Git commit required.' >&2; exit 2; }
for cmd in curl php tar cmp mktemp readlink grep; do
  command -v "$cmd" >/dev/null || { echo "Missing command: $cmd" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" ]] || {
  echo 'Cannot locate existing public/portal roots.' >&2; exit 3;
}
case "$PORTAL" in "$PUBLIC"/*) ;; *) echo 'Wrong portal root. No changes.' >&2; exit 3;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/invoice.php" && -f "$PORTAL/views/payments.php" ]] || {
  echo 'Existing iWORKS configured portal missing; refusing to publish.' >&2; exit 3;
}
WORK="$(mktemp -d)"
APPLY_STARTED=0
BACKUP=''
cleanup_and_restore() {
  code=$?
  if [[ "$APPLY_STARTED" == 1 && "$code" -ne 0 ]]; then
    echo 'Release failed: restoring original invoice and SCUML presentation files.' >&2
    if [[ -f "$BACKUP/portal.tgz" ]]; then
      tar -xzf "$BACKUP/portal.tgz" -C "$PORTAL" || true
    fi
    if [[ -f "$BACKUP/new.txt" ]]; then
      while IFS= read -r path; do rm -f "$PORTAL/$path"; done < "$BACKUP/new.txt"
    fi
  fi
  rm -rf "$WORK"
  exit "$code"
}
trap cleanup_and_restore EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 160 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" \
  -o "$WORK/release.tgz"
mkdir "$WORK/source"
tar -xzf "$WORK/release.tgz" -C "$WORK/source" --strip-components=1
SOURCE="$WORK/source"
cat > "$WORK/manifest" <<'FILES'
invoice.php
views/partials/invoice-wave-sheet.php
views/public-service-detail.php
assets/css/invoice-wave.css
assets/css/scuml-service.css
FILES
: > "$WORK/existing"
: > "$WORK/new"
: > "$WORK/changed"
while IFS= read -r path; do
  [[ -s "$SOURCE/$path" && ! -L "$PORTAL/$path" ]] || {
    echo "Missing or unsafe file: $path" >&2; exit 3;
  }
  case "$path" in *.php) php -l "$SOURCE/$path" >/dev/null;; esac
  if [[ ! -f "$PORTAL/$path" ]] || ! cmp -s "$SOURCE/$path" "$PORTAL/$path"; then
    echo "$path" >> "$WORK/changed"
    if [[ -f "$PORTAL/$path" ]]; then
      echo "$path" >> "$WORK/existing"
    else
      echo "$path" >> "$WORK/new"
    fi
  fi
done < "$WORK/manifest"
php "$SOURCE/tests/invoice_payment_layout_contract_test.php"
php "$SOURCE/tests/scuml_single_title_contract_test.php"
php "$SOURCE/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Required production readiness preflight BLOCKED; no changes applied.' >&2
  exit 3
}
echo "Invoice/SCUML presentation files to update: $(wc -l < "$WORK/changed" | tr -d ' ')"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. Existing invoices, payment records and source files were untouched.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/invoice-mobile-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 0700 "$BACKUP"
if [[ -s "$WORK/existing" ]]; then
  tar -czf "$BACKUP/portal.tgz" -C "$PORTAL" -T "$WORK/existing"
fi
cp "$WORK/new" "$BACKUP/new.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 0600 "$BACKUP"/*
APPLY_STARTED=1
while IFS= read -r path; do
  dest="$PORTAL/$path"
  mkdir -p "$(dirname "$dest")"
  temp="$(mktemp "$(dirname "$dest")/.iw-invoice-XXXXXXXX")"
  cat "$SOURCE/$path" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$dest"
done < "$WORK/changed"
while IFS= read -r path; do
  cmp -s "$SOURCE/$path" "$PORTAL/$path" || {
    echo "Source did not match after publishing: $path" >&2
    false
  }
  case "$path" in *.php) php -l "$PORTAL/$path" >/dev/null;; esac
done < "$WORK/manifest"
status="$(curl --http1.1 -sS -L --retry 1 --max-time 35 \
  -o "$WORK/portal-login.html" -w '%{http_code}' \
  'https://portal.iworksgt.com/index.php?page=login' || true)"
[[ "$status" == 200 ]] || {
  echo "Portal login health check returned HTTP $status; rolling back." >&2
  false
}
# Verify SCUML's public page when the service is live. If the catalogue
# record is not yet published, the source-only release still succeeds and
# reports that separate product activation is required.
scuml_status="$(curl --http1.1 -sS -L --max-time 35   -o "$WORK/scuml-public.html" -w '%{http_code}'   "https://iworksgt.com/services/scuml-document-support?iw_ui_release=${RELEASE:0:12}" || true)"
if [[ "$scuml_status" == 200 ]]; then
  grep -Fq 'Apply for support' "$WORK/scuml-public.html" || {
    echo 'SCUML Apply for support button missing after update; rolling back.' >&2; false;
  }
  if grep -Fq 'Check official requirements' "$WORK/scuml-public.html"; then
    echo 'Old SCUML official requirements link still visible; rolling back.' >&2; false;
  fi
  echo 'PASS SCUML public application UI.'
else
  echo "NOTICE: SCUML public product page returned HTTP $scuml_status. Check whether the catalogue service has been activated."
fi
APPLY_STARTED=0
echo "SUCCESS: Wave-style iWORKS invoice and simpler SCUML product updated from $RELEASE."
echo "Private source rollback: $BACKUP"
echo 'No database migrations, customer jobs, invoices, payment records, or payment statuses changed.'
echo 'Open an authenticated invoice preview and a staff payment-review page to verify visual layout.'
