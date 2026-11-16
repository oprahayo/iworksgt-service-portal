#!/usr/bin/env bash
# iWORKS transaction-only dashboard refresh. Usage:
# bash deploy_transaction_dashboard.sh --check|--apply FULL_COMMIT_SHA PORTAL_ROOT
# No database, user uploads, private config or payment records are modified.
set -Eeuo pipefail
[[ "$#" == 3 ]] || { echo 'Usage: --check|--apply FULL_COMMIT_SHA PORTAL_ROOT' >&2; exit 2; }
MODE="$1"; SHA="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Choose --check or --apply.' >&2; exit 2; }
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Invalid immutable release SHA.' >&2; exit 2; }
for tool in curl php tar cmp mktemp readlink grep; do command -v "$tool" >/dev/null || { echo "Missing $tool" >&2; exit 3; }; done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" ]] || {
  echo 'Expected split iWORKS portal and public roots not found.' >&2; exit 3;
}
case "$PORTAL" in "$PUBLIC"/*) ;; *) echo 'Wrong portal root; stopping.' >&2; exit 3;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" &&
   -f "$PORTAL/views/dashboard.php" && -f "$PORTAL/app/domain.php" ]] || {
  echo 'Existing iWORKS portal not found; no changes.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$SHA" \
  -o "$WORK/source.tgz"
mkdir "$WORK/release"
tar -xzf "$WORK/source.tgz" -C "$WORK/release" --strip-components=1
SOURCE="$WORK/release"
cat > "$WORK/manifest" <<'FILES'
app/transaction_dashboard.php
app/domain.php
index.php
views/dashboard.php
views/partials/header.php
views/partials/transaction-overview.php
assets/css/dashboard-transactions.css
FILES
while IFS= read -r path; do
  [[ -s "$SOURCE/$path" && ! -L "$PORTAL/$path" ]] || {
    echo "Missing or unsafe source/destination: $path" >&2; exit 3;
  }
done < "$WORK/manifest"
while IFS= read -r path; do
  case "$path" in *.php) php -l "$SOURCE/$path" >/dev/null;; esac
done < "$WORK/manifest"
php "$SOURCE/tests/transaction_dashboard_contract_test.php"
php "$SOURCE/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Mandatory security/schema/mail preflight BLOCKED. No files changed.' >&2; exit 3;
}
: > "$WORK/changed"; : > "$WORK/existing"; : > "$WORK/added"
while IFS= read -r path; do
  if [[ ! -f "$PORTAL/$path" ]] || ! cmp -s "$SOURCE/$path" "$PORTAL/$path"; then
    echo "$path" >> "$WORK/changed"
    if [[ -f "$PORTAL/$path" ]]; then echo "$path" >> "$WORK/existing"; else echo "$path" >> "$WORK/added"; fi
  fi
done < "$WORK/manifest"
echo "Transaction dashboard source files to update: $(wc -l < "$WORK/changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. Production files remain untouched.'
  exit 0
fi

umask 077
BACKUP="$HOME/iworks-deployment-backups/transactions-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 0700 "$BACKUP"
if [[ -s "$WORK/existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/existing"; fi
cp "$WORK/added" "$BACKUP/new.txt"
printf '%s\n' "$SHA" > "$BACKUP/release.txt"
chmod 0600 "$BACKUP"/*
STARTED=1
rollback() {
  code=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Transaction dashboard release failed; restoring previous files.' >&2
    [[ ! -f "$BACKUP/portal.tgz" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
    while IFS= read -r path; do rm -f "$PORTAL/$path"; done < "$BACKUP/new.txt"
  fi
  exit "$code"
}
trap rollback ERR
copy_atomic() {
  local relative="$1" dest="$PORTAL/$1" tmp
  mkdir -p "$(dirname "$dest")"
  tmp="$(mktemp "$(dirname "$dest")/.iwtransaction-XXXXXXXX")"
  cat "$SOURCE/$relative" > "$tmp"
  chmod 0644 "$tmp"
  mv -f "$tmp" "$dest"
}
while IFS= read -r path; do copy_atomic "$path"; done < "$WORK/changed"
while IFS= read -r path; do cmp -s "$SOURCE/$path" "$PORTAL/$path"; done < "$WORK/manifest"
while IFS= read -r path; do
  case "$path" in *.php) php -l "$PORTAL/$path" >/dev/null;; esac
done < "$WORK/manifest"
code="$(curl --http1.1 -sS -L --max-time 30 -o "$WORK/portal.html" -w '%{http_code}' \
    'https://portal.iworksgt.com/?page=login' || true)"
[[ "$code" == 200 ]] || { echo "Portal login returned HTTP $code; rolling back." >&2; false; }
STARTED=0
trap - ERR
echo "SUCCESS: transaction dashboard updated from $SHA"
echo "Private rollback backup: $BACKUP"
echo 'Actual transaction values are displayed when authorized staff log in.'
echo 'No customer, invoice, payment or database content modified.'
