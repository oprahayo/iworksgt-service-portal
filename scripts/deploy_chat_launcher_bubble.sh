#!/usr/bin/env bash
# iWORKS chat launcher icon update: two public PHP views + widget CSS only.
# bash deploy_chat_launcher_bubble.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT
# No database, authentication, customer data, uploads, payments, email or JS changes.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_chat_launcher_bubble.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Use --check or --apply' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Expected immutable 40-character commit SHA' >&2; exit 2; }
for bin in curl php tar cmp grep mktemp readlink; do
  command -v "$bin" >/dev/null || { echo "Missing command: $bin" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ "$PUBLIC" == "$ROOT" && "$PORTAL" != "$PUBLIC" && -d "$PORTAL" && -d "$PUBLIC" ]] || {
  echo 'Unexpected public/portal deployment root' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Unexpected portal path' >&2; exit 3;; esac
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" && -d "$PORTAL/assets/css" ]] || {
  echo 'Existing iWORKS portal not detected' >&2; exit 3;
}
for file in views/partials/public-assistance.php views/partials/public-header.php assets/css/public-assistance.css; do
  [[ -f "$PORTAL/$file" && ! -L "$PORTAL/$file" ]] || { echo "Expected safe existing file missing: $file" >&2; exit 3; }
done
[[ -d "$PUBLIC/assets" ]] || { echo 'Apex static assets not found' >&2; exit 3; }
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 120 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" \
  -o "$WORK/source.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/source.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
TEMPLATE="views/partials/public-assistance.php"
HEADER="views/partials/public-header.php"
CSS="assets/css/public-assistance.css"
for file in "$TEMPLATE" "$HEADER" "$CSS"; do [[ -s "$SRC/$file" ]] || exit 3; done
php -l "$SRC/$TEMPLATE" >/dev/null
php -l "$SRC/$HEADER" >/dev/null
grep -Fq 'class="iw-assist-launcher-icon"' "$SRC/$TEMPLATE" || { echo 'Reference icon is missing' >&2; exit 3; }
grep -Fq 'fill="#fff"' "$SRC/$TEMPLATE" || exit 3
grep -Fq 'l5 17-24-10' "$SRC/$TEMPLATE" || exit 3
grep -Fq 'background:transparent!important' "$SRC/$CSS" || exit 3
grep -Fq '20261114-launcher1' "$SRC/$HEADER" || exit 3
# Existing production gate is read-only and must pass before changing files.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Production safety preflight blocked update; no files changed' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/public-changed"
for file in "$TEMPLATE" "$HEADER" "$CSS"; do
  if ! cmp -s "$SRC/$file" "$PORTAL/$file"; then echo "$file" >> "$WORK/portal-changed"; fi
done
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  [[ ! -L "$PUBLIC/$CSS" ]] || { echo 'Unsafe public CSS symlink' >&2; exit 3; }
  if [[ ! -f "$PUBLIC/$CSS" ]] || ! cmp -s "$SRC/$CSS" "$PUBLIC/$CSS"; then
    echo "$CSS" >> "$WORK/public-changed"
  fi
fi
echo "Portal source changes: $(wc -l < "$WORK/portal-changed")"
echo "Apex static asset changes: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED: candidate is valid; no production changes performed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/chat-launcher-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 700 "$BACKUP"
: > "$WORK/public-existing"
: > "$WORK/public-added"
while IFS= read -r file; do
  if [[ -f "$PUBLIC/$file" ]]; then
    echo "$file" >> "$WORK/public-existing"
  else
    echo "$file" >> "$WORK/public-added"
  fi
done < "$WORK/public-changed"
if [[ -s "$WORK/portal-changed" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-changed"
fi
if [[ -s "$WORK/public-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/public-existing"
fi
cp "$WORK/public-added" "$BACKUP/new-public-files.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/pinned-source.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'Failure detected: restoring chat launcher source files from private backup' >&2
    if [[ -s "$WORK/portal-changed" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  local origin="$1" target="$2" folder temp
  folder="$(dirname "$target")"
  mkdir -p "$folder"
  temp="$(mktemp "$folder/.chat-icon.XXXXXXXX")"
  cat "$origin" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$target"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/public-changed"
for file in "$TEMPLATE" "$HEADER" "$CSS"; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Verification failed: $file" >&2; false; }
done
while IFS= read -r file; do
  cmp -s "$SRC/$file" "$PUBLIC/$file" || false
done < "$WORK/public-changed"
php -l "$PORTAL/$TEMPLATE" >/dev/null
php -l "$PORTAL/$HEADER" >/dev/null
status="$(curl -sS --max-time 20 -o "$WORK/site.html" -w '%{http_code}' 'https://iworksgt.com/')"
[[ "$status" == 200 ]] || { echo "Homepage HTTP test failed: $status" >&2; false; }
STARTED=0
trap - ERR
echo "SUCCESS: chat bubble launcher updated. Private rollback: $BACKUP"
if grep -Fq 'iw-assist-launcher-icon' "$WORK/site.html"; then
  echo 'LIVE MARKER: SVG chat launcher found in public homepage HTML.'
else
  echo 'NOTICE: public page did not include new markup yet; confirm apex PHP release and clear server/CDN cache.'
fi
echo 'No database, chat messages, sessions, payments, other templates or JS changed.'
