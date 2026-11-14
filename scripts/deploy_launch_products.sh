#!/usr/bin/env bash
# iWORKS launch product-order code release. Does not mutate the service DB.
# Usage: bash deploy_launch_products.sh --check|--apply SHA PORTAL_ROOT PUBLIC_ROOT
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: deploy_launch_products.sh --check|--apply SHA PORTAL_ROOT PUBLIC_ROOT' >&2; exit 2; }
MODE="$1"; SHA="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full immutable SHA required' >&2; exit 2; }
for tool in curl php tar cmp mktemp readlink; do command -v "$tool" >/dev/null || exit 3; done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"; ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$ROOT" ]] || { echo 'Unexpected document roots' >&2; exit 3; }
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Unexpected portal path' >&2; exit 3 ;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" && -d "$PUBLIC/assets" ]] || { echo 'Live portal not found' >&2; exit 3; }
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$SHA" -o "$WORK/release.tgz"
mkdir "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/php" <<'EOF'
index.php
app/helpers.php
app/domain.php
app/customer_service_policy.php
app/cac_filing.php
app/launch_packages.php
views/client-service-checkout.php
views/client-profile.php
views/client-cac-pathway.php
views/client-cac-filing.php
views/services.php
views/register.php
views/public-service-detail.php
EOF
cat > "$WORK/assets" <<'EOF'
assets/css/product-detail.css
EOF
cat "$WORK/php" "$WORK/assets" > "$WORK/portal-files"
while IFS= read -r file; do
  [[ -s "$SRC/$file" ]] || { echo "Missing source: $file" >&2; exit 3; }
  [[ ! -L "$PORTAL/$file" ]] || { echo "Unsafe portal symlink: $file" >&2; exit 3; }
done < "$WORK/portal-files"
while IFS= read -r file; do php -l "$SRC/$file" >/dev/null; done < "$WORK/php"
php "$SRC/tests/launch_product_order_contract_test.php"
php "$SRC/tests/customer_catalogue_unification_test.php"
php "$SRC/tests/cac_unified_product_contract_test.php"
php "$SRC/tests/f13_cac_quote_consistency_test.php"
php "$SRC/tests/cac_registration_choice_ui_test.php"
# Essential: existing release/security/migrations/email checks are not bypassed.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Mandatory production safety gate failed; source unchanged.' >&2; exit 3;
}
: > "$WORK/portal-existing"; : > "$WORK/portal-added"; : > "$WORK/portal-changed"
: > "$WORK/apex-existing"; : > "$WORK/apex-added"; : > "$WORK/apex-changed"
while IFS= read -r file; do
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing"; else echo "$file" >> "$WORK/portal-added"; fi
  fi
done < "$WORK/portal-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  while IFS= read -r file; do
    [[ ! -L "$PUBLIC/$file" ]] || { echo "Unsafe apex symlink: $file" >&2; exit 3; }
    if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
      echo "$file" >> "$WORK/apex-changed"
      if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/apex-existing"; else echo "$file" >> "$WORK/apex-added"; fi
    fi
  done < "$WORK/assets"
fi
echo "Portal files changing: $(wc -l < "$WORK/portal-changed")"
echo "Apex static files changing: $(wc -l < "$WORK/apex-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'READ-ONLY CHECK PASSED. Nothing published or priced.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/order-launch-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
[[ ! -s "$WORK/portal-existing" ]] || tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"
[[ ! -s "$WORK/apex-existing" ]] || tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/apex-existing"
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/apex-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$SHA" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback(){
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Deployment error: restoring prior source from private backup' >&2
    [[ ! -s "$WORK/portal-existing" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
    [[ ! -s "$WORK/apex-existing" ]] || tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/apex-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic(){
  local input="$1" output="$2" dir tmp
  dir="$(dirname "$output")"; mkdir -p "$dir"
  tmp="$(mktemp "$dir/.launch-XXXXXXXX")"
  cat "$input" > "$tmp"; chmod 0644 "$tmp"; mv -f "$tmp" "$output"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/apex-changed"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PORTAL/$file" || false; done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/apex-changed"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/php"
for path in / /services /services/website-design-development; do
  status="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "https://iworksgt.com$path" || true)"
  [[ "$status" == 200 ]] || { echo "Public smoke test failed at $path: HTTP $status" >&2; false; }
done
STARTED=0
trap - ERR
echo "SUCCESS: launch ordering code published. Private source backup: $BACKUP"
echo 'No database records, catalogue prices or customer invoices have been changed by this installer.'
echo 'Next: verified private full DB backup, separate launch-price --check / --apply and real payment/email tests.'
