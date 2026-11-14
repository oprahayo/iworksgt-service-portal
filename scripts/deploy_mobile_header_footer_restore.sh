#!/usr/bin/env bash
# Restore original two-column mobile footer and compact mobile header only.
# Usage: bash deploy_mobile_header_footer_restore.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# No database, migrations, uploads, payments, authentication, chat or data changes.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_mobile_header_footer_restore.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Choose --check or --apply' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Require an immutable 40-character Git SHA' >&2; exit 2; }
for tool in curl php tar cmp mktemp grep readlink chmod; do
  command -v "$tool" >/dev/null || { echo "Missing required command: $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$ROOT" ]] || {
  echo 'Unexpected portal/public document roots. No change.' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Unexpected portal path. No change.' >&2; exit 3;; esac
HEADER="views/partials/public-header.php"
CSS="assets/css/site-launch-polish.css"
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" && -f "$PORTAL/$HEADER" && -f "$PORTAL/$CSS" && -d "$PUBLIC/assets" ]] || {
  echo 'Current iWORKS installation is not in the expected location.' >&2; exit 3;
}
[[ ! -L "$PORTAL/$HEADER" && ! -L "$PORTAL/$CSS" ]] || {
  echo 'Cannot overwrite a symlinked portal file.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 120 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" \
  -o "$WORK/release.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
[[ -s "$SRC/$HEADER" && -s "$SRC/$CSS" ]] || { echo 'Release missing header or mobile CSS.' >&2; exit 3; }
php -l "$SRC/$HEADER" >/dev/null
php "$SRC/tests/mobile_header_footer_restore_contract_test.php" || {
  echo 'Required mobile layout contract failed. Nothing was changed.' >&2; exit 3;
}
grep -Fq 'iw-mobile-menu-cta-row' "$SRC/$HEADER" || exit 3
grep -Fq 'minmax(0, .94fr) minmax(0, 1.06fr)' "$SRC/$CSS" || exit 3
grep -Fq '20261114-mobile-footer2' "$SRC/$HEADER" || exit 3
# Preserve the mandatory private-file, schema and email safety gates.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Production release gate blocked this update; no changes made.' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/apex-changed"
: > "$WORK/apex-existing"
: > "$WORK/apex-added"
for file in "$HEADER" "$CSS"; do
  if ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
  fi
done
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  [[ ! -L "$PUBLIC/$CSS" ]] || { echo 'Cannot overwrite symlinked public CSS' >&2; exit 3; }
  if [[ ! -f "$PUBLIC/$CSS" ]] || ! cmp -s "$SRC/$CSS" "$PUBLIC/$CSS"; then
    echo "$CSS" >> "$WORK/apex-changed"
    if [[ -f "$PUBLIC/$CSS" ]]; then echo "$CSS" >> "$WORK/apex-existing"
    else echo "$CSS" >> "$WORK/apex-added"; fi
  fi
fi
echo "Portal changes: $(wc -l < "$WORK/portal-changed")"
echo "Public CSS mirror changes: $(wc -l < "$WORK/apex-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED: prior mobile footer restored in source. Production unchanged.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/mobile-nav-footer-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-changed" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-changed"
fi
if [[ -s "$WORK/apex-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/apex-css.tgz" -T "$WORK/apex-existing"
fi
cp "$WORK/apex-added" "$BACKUP/new-public-files.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
restore_on_error() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Deployment error: restoring previous mobile header/footer from private backup' >&2
    if [[ -s "$WORK/portal-changed" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/apex-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex-css.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/apex-added"
  fi
  exit "$status"
}
trap restore_on_error ERR
copy_atomic() {
  local source_file="$1" target="$2" folder staged
  folder="$(dirname "$target")"
  mkdir -p "$folder"
  staged="$(mktemp "$folder/.mobile-layout.XXXXXXXX")"
  cat "$source_file" > "$staged"
  chmod 0644 "$staged"
  mv -f "$staged" "$target"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/apex-changed"
for file in "$HEADER" "$CSS"; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Portal file verification failed: $file" >&2; false; }
done
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/apex-changed"
php -l "$PORTAL/$HEADER" >/dev/null
status="$(curl -sS --max-time 25 -o "$WORK/home.html" -w '%{http_code}' 'https://iworksgt.com/' || true)"
[[ "$status" == 200 ]] || { echo "Public homepage HTTP $status; restoring old files." >&2; false; }
STARTED=0
trap - ERR
echo "SUCCESS: mobile footer two-column layout and compact header navigation updated."
echo "Private rollback archive: $BACKUP"
if grep -Fq 'iw-mobile-menu-cta-row' "$WORK/home.html"; then
  echo 'LIVE HEADER MARKER: new consultation/get-started row is present.'
else
  echo 'NOTICE: public HTML may be cached or routed to older templates. Clear site/CDN cache and confirm site rendering.'
fi
echo 'No database, user records, payments or uploaded assets were modified.'
