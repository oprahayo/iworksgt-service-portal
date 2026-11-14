#!/usr/bin/env bash
# iWORKS homepage color harmony: one CSS file + public cache version.
# bash deploy_home_color_finish.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT
# Never updates database, uploads, customer records, payment, authentication or mail.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_home_color_finish.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Use --check or --apply' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Require an immutable 40-character source SHA' >&2; exit 2; }
for bin in curl php tar cmp mktemp readlink grep; do
  command -v "$bin" >/dev/null || { echo "Missing $bin" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PUBLIC" == "$HTML" && "$PORTAL" != "$PUBLIC" ]] || {
  echo 'Unexpected portal/apex document roots' >&2; exit 3;
}
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Portal must be under public_html' >&2; exit 3;; esac
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" && -f "$PORTAL/views/partials/public-header.php" ]] || {
  echo 'Live iWORKS portal not found; no changes' >&2; exit 3;
}
[[ -d "$PORTAL/assets/css" && -d "$PUBLIC/assets" ]] || {
  echo 'Existing public asset paths not found' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 120 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir -p "$WORK/source"
tar -xzf "$WORK/release.tgz" -C "$WORK/source" --strip-components=1
SRC="$WORK/source"
CSS="assets/css/site-launch-polish.css"
HEADER="views/partials/public-header.php"
[[ -s "$SRC/$CSS" && -s "$SRC/$HEADER" ]] || { echo 'Release missing required files' >&2; exit 3; }
php -l "$SRC/$HEADER" >/dev/null
grep -Fq 'rgba(241,109,24,.23)' "$SRC/$CSS" || { echo 'Expected welcoming hero gradient missing' >&2; exit 3; }
grep -Fq 'linear-gradient(125deg,#171717,#2c231e)' "$SRC/$CSS" || { echo 'FAQ must match existing launch CTA background' >&2; exit 3; }
grep -Fq 'body.page-public .iw-clean-home .iw-clean-faq' "$SRC/$CSS" || exit 3
grep -Fq 'body.page-public .iw-clean-home .iw-clean-support' "$SRC/$CSS" || exit 3
grep -Fq 'background:#fff!important;' "$SRC/$CSS" || exit 3
grep -Fq '20261114-color2' "$SRC/$HEADER" || { echo 'CSS cache revision missing' >&2; exit 3; }
# Reuse the existing read-only release safety gate. Fail closed if prerequisites are unmet.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Safety preflight failed. No source files changed.' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/public-changed"
: > "$WORK/portal-existing"
: > "$WORK/public-existing"
: > "$WORK/portal-added"
: > "$WORK/public-added"
for file in "$CSS" "$HEADER"; do
  [[ ! -L "$PORTAL/$file" ]] || { echo "Unsafe portal target symlink: $file" >&2; exit 3; }
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing"; else echo "$file" >> "$WORK/portal-added"; fi
  fi
done
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  [[ ! -L "$PUBLIC/$CSS" ]] || { echo 'Unsafe apex CSS symlink' >&2; exit 3; }
  if [[ ! -f "$PUBLIC/$CSS" ]] || ! cmp -s "$SRC/$CSS" "$PUBLIC/$CSS"; then
    echo "$CSS" >> "$WORK/public-changed"
    if [[ -f "$PUBLIC/$CSS" ]]; then echo "$CSS" >> "$WORK/public-existing"; else echo "$CSS" >> "$WORK/public-added"; fi
  fi
fi
echo "Portal changes: $(wc -l < "$WORK/portal-changed")"
echo "Public CSS mirror changes: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. Nothing changed. Backups will be created on --apply.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/home-color-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"
fi
if [[ -s "$WORK/public-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/public-css.tgz" -T "$WORK/public-existing"
fi
cp "$WORK/portal-added" "$BACKUP/added-portal.txt"
cp "$WORK/public-added" "$BACKUP/added-public.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/pinned-release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
restore_on_error() {
  status=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'ERROR: restoring homepage color files from backup' >&2
    if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/public-css.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
  fi
  exit "$status"
}
trap restore_on_error ERR
copy_atomic() {
  local source_file="$1" destination="$2" folder temp
  folder="$(dirname "$destination")"
  mkdir -p "$folder"
  temp="$(mktemp "$folder/.iworks-color.XXXXXXXX")"
  cat "$source_file" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$destination"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/public-changed"
for file in "$CSS" "$HEADER"; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Mismatch: $file" >&2; false; }
done
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/public-changed"
php -l "$PORTAL/$HEADER" >/dev/null
status="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' 'https://iworksgt.com/')"
[[ "$status" == 200 ]] || { echo "Homepage failed HTTP smoke check: $status" >&2; false; }
STARTED=0
trap - ERR
echo "SUCCESS: homepage color harmony applied. Private rollback backup: $BACKUP"
echo 'No database or CMS content modified. Please check mobile/desktop and clear CDN cache if applicable.'
