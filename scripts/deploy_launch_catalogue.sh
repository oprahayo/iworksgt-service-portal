#!/usr/bin/env bash
# iWORKS launch catalogue / prices / self-ordering: PHP+CSS only.
# Usage: bash deploy_launch_catalogue.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# Do not run SQL migrations; do not modify customer data or historical invoices.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_launch_catalogue.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Expected --check or --apply' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Require an immutable 40-character commit SHA' >&2; exit 2; }
for tool in curl tar php cmp grep mktemp readlink chmod; do
  command -v "$tool" >/dev/null || { echo "Missing required tool: $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PUBLIC" == "$ROOT" && "$PORTAL" != "$PUBLIC" ]] || { echo 'Unexpected document root; stop.' >&2; exit 3; }
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal root not in public_html; stop.' >&2; exit 3;; esac
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" &&
   -f "$PORTAL/app/domain.php" && -d "$PORTAL/assets/css" && -d "$PUBLIC/assets" ]] || {
  echo 'Cannot verify configured iWORKS installation; stop.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 160 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" \
  -o "$WORK/src.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/src.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/phpfiles" <<'EOF'
index.php
app/bootstrap.php
app/helpers.php
app/launch_pricing.php
app/customer_service_policy.php
app/cac_filing.php
app/order_intent.php
app/domain.php
app/trademark_applications.php
views/client-cac-pathway.php
views/client-cac-filing.php
views/client-service-checkout.php
views/client-quote-request.php
views/client-dashboard.php
views/client-profile.php
views/register.php
views/client-trademark.php
views/services.php
views/public-service-detail.php
EOF
cat > "$WORK/styles" <<'EOF'
assets/css/product-detail.css
EOF
cat "$WORK/phpfiles" "$WORK/styles" > "$WORK/portal-files"
while IFS= read -r file; do
  [[ -s "$SRC/$file" ]] || { echo "Release file missing or empty: $file" >&2; exit 3; }
  [[ ! -L "$PORTAL/$file" ]] || { echo "Unsafe portal symlink: $file" >&2; exit 3; }
done < "$WORK/portal-files"
while IFS= read -r file; do php -l "$SRC/$file" >/dev/null; done < "$WORK/phpfiles"
php "$SRC/tests/launch_regional_catalogue_test.php" || {
  echo 'Regional price/order contract failed before deployment.' >&2; exit 3;
}
grep -Fq 'case '\''quote_request'\'':' "$SRC/index.php" || { echo 'Quotation workflow route absent' >&2; exit 3; }
grep -Fq 'iworks_launch_notify_new_order' "$SRC/app/launch_pricing.php" || exit 3
grep -Fq 'iworks_launch_account_currency' "$SRC/app/helpers.php" || exit 3
# Existing mandatory security, schema, backup and email-readiness preflight.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Production safety gates failed. No production files modified.' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/portal-existing"
: > "$WORK/portal-added"
: > "$WORK/apex-changed"
: > "$WORK/apex-existing"
: > "$WORK/apex-added"
while IFS= read -r file; do
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing";
    else echo "$file" >> "$WORK/portal-added"; fi
  fi
done < "$WORK/portal-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  while IFS= read -r file; do
    [[ ! -L "$PUBLIC/$file" ]] || { echo "Unsafe apex symlink: $file" >&2; exit 3; }
    if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
      echo "$file" >> "$WORK/apex-changed"
      if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/apex-existing";
      else echo "$file" >> "$WORK/apex-added"; fi
    fi
  done < "$WORK/styles"
fi
echo "Portal files to update: $(wc -l < "$WORK/portal-changed")"
echo "Apex CSS mirrors to update: $(wc -l < "$WORK/apex-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. No customer files or production source changed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/launch-catalog-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"
fi
if [[ -s "$WORK/apex-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/apex-css.tgz" -T "$WORK/apex-existing"
fi
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/apex-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/source-commit.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Release failed: restoring existing iWORKS source files from private backup' >&2
    if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/apex-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex-css.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/apex-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  local source_file="$1" target="$2" dir temp
  dir="$(dirname "$target")"
  mkdir -p "$dir"
  temp="$(mktemp "$dir/.iworks-launch.XXXXXXXX")"
  cat "$source_file" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$target"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/apex-changed"
while IFS= read -r file; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Portal verification failed: $file" >&2; false; }
done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/apex-changed"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/phpfiles"
for target in / /services; do
  code="$(curl -sS --max-time 24 -o /dev/null -w '%{http_code}' "https://iworksgt.com$target" || true)"
  [[ "$code" == 200 ]] || { echo "Live public smoke failed $target HTTP $code" >&2; false; }
done
STARTED=0
trap - ERR
echo "SUCCESS: new-order regional catalogue and quote workflow published."
echo "PRIVATE ROLLBACK: $BACKUP"
echo 'No database, customer records, old invoices, identity documents or payment secrets altered.'
echo 'MUST still test account creation/OTP, four CAC flows, both billing regions, agents and actual email/payment in staging.'
