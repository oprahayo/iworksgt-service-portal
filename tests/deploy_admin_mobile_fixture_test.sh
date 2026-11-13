#!/usr/bin/env bash
# Offline deployment smoke test: simulates a separate cPanel portal and apex.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
export HOME="$TMP/home"
mkdir -p "$HOME/public_html/portal.iworksgt.com/app" "$HOME/public_html/portal.iworksgt.com/views/partials" "$TMP/fixture/app" "$TMP/fixture/views/partials" "$TMP/fixture/assets/css" "$TMP/fixture/assets/branding" "$TMP/fixture/scripts"
PORTAL="$HOME/public_html/portal.iworksgt.com"
PUBLIC="$HOME/public_html"

# Existing private config and data must be retained verbatim.
printf '%s\n' '<?php return ["database"=>"not a real secret"];' > "$PORTAL/app/config.php"
cp "$PORTAL/app/config.php" "$TMP/expected-config.php"
printf '%s\n' '<?php echo "old portal";' > "$PORTAL/index.php"
printf '%s\n' '<?php // old header' > "$PORTAL/views/partials/header.php"
mkdir -p "$PORTAL/storage" "$PORTAL/assets/uploads"
echo 'customer record' > "$PORTAL/storage/private.txt"
echo 'customer upload' > "$PORTAL/assets/uploads/file.txt"

cat > "$TMP/fixture/index.php" <<'PHP'
<?php
// $portalManagementPage is required to distinguish management paths.
echo 'new portal';
PHP
printf '%s\n' '<?php // iw-sitewide-cta' > "$TMP/fixture/views/partials/public-footer.php"
printf '%s\n' '<?php // iw-mobile-menu-group' > "$TMP/fixture/views/partials/public-header.php"
printf '%s\n' '<?php // new header' > "$TMP/fixture/views/partials/header.php"
printf '%s\n' '<?php // repair script' > "$TMP/fixture/scripts/repair_public_content_schema.php"
printf '%s\n' 'CREATE TABLE IF NOT EXISTS blog_posts (' > "$TMP/fixture/database.sql"
printf '%s\n' '.fixture{display:block}' > "$TMP/fixture/assets/css/fixture.css"
printf '%s\n' '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"></svg>' > "$TMP/fixture/assets/branding/iworks-desktop.svg"
printf '%s\n' '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"></svg>' > "$TMP/fixture/assets/branding/iworks-compact.svg"
tar -C "$TMP" -czf "$TMP/release.tar.gz" --transform='s#^fixture/#release-root/#' fixture

# No internet needed, and no /dev/fd process substitution used by installer.
export IWORKS_RELEASE_ARCHIVE="$TMP/release.tar.gz"
bash "$ROOT/scripts/deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC" > "$TMP/check.log"
grep -q 'CHECK ONLY' "$TMP/check.log"
grep -q 'Portal changes:' "$TMP/check.log"
[[ ! -e "$PORTAL/assets/css/fixture.css" ]]
[[ ! -e "$PUBLIC/assets/css/fixture.css" ]]
[[ ! -e "$PORTAL/assets/branding/iworks-desktop.svg" ]]
[[ ! -e "$PUBLIC/assets/branding/iworks-compact.svg" ]]
grep -q 'old portal' "$PORTAL/index.php"
# Reproduce legacy 0700 public asset directory permissions before deployment.
mkdir -p "$PORTAL/assets/branding" "$PUBLIC/assets/branding"
chmod 0700 "$PORTAL/assets" "$PORTAL/assets/branding" "$PUBLIC/assets" "$PUBLIC/assets/branding"

bash "$ROOT/scripts/deploy_admin_mobile_refresh.sh" --apply "$PORTAL" "$PUBLIC" > "$TMP/apply.log"
grep -q 'SUCCESS: source files deployed' "$TMP/apply.log"
grep -q 'new portal' "$PORTAL/index.php"
[[ -s "$PORTAL/scripts/repair_public_content_schema.php" ]]
[[ -s "$PUBLIC/assets/css/fixture.css" ]]
[[ -s "$PORTAL/assets/css/fixture.css" ]]
[[ -s "$PORTAL/assets/branding/iworks-desktop.svg" ]]
[[ -s "$PORTAL/assets/branding/iworks-compact.svg" ]]
[[ -s "$PUBLIC/assets/branding/iworks-desktop.svg" ]]
[[ -s "$PUBLIC/assets/branding/iworks-compact.svg" ]]
cmp "$PORTAL/app/config.php" "$TMP/expected-config.php"
for dir in "$PORTAL/assets" "$PORTAL/assets/branding" "$PUBLIC/assets" "$PUBLIC/assets/branding"; do
  [[ "$(stat -c %a "$dir")" == "755" ]] || { echo "Public asset directory not traversable: $dir"; exit 1; }
done
for file in "$PORTAL/assets/branding/iworks-desktop.svg" "$PUBLIC/assets/branding/iworks-compact.svg"; do
  [[ "$(stat -c %a "$file")" == "644" ]]
done
MANIFEST="$(find "$HOME/iworks-deployment-backups" -type f -name source-release-manifest.sha256 | head -1)"
[[ -n "$MANIFEST" && -s "$MANIFEST" ]]
grep -Fq 'assets/branding/iworks-desktop.svg' "$MANIFEST"
[[ "$(stat -c %a "$(dirname "$MANIFEST")")" == "700" ]]
grep -q 'customer record' "$PORTAL/storage/private.txt"
grep -q 'customer upload' "$PORTAL/assets/uploads/file.txt"

# Repeat deployment is idempotent for unchanged files.
bash "$ROOT/scripts/deploy_admin_mobile_refresh.sh" --check "$PORTAL" "$PUBLIC" > "$TMP/again.log"
grep -q 'Portal changes: 0' "$TMP/again.log"
grep -q 'Public static asset changes: 0' "$TMP/again.log"
echo 'deploy_admin_mobile_fixture_test passed'
