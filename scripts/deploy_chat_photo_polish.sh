#!/usr/bin/env bash
# iWORKS compact chat + photo graphic accents only. No database or uploads.
# Usage: bash deploy_chat_photo_polish.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_chat_photo_polish.sh --check|--apply COMMIT_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Pin a full immutable Git commit SHA' >&2; exit 2; }
for bin in curl tar php cmp mktemp readlink grep; do
  command -v "$bin" >/dev/null || { echo "Required command missing: $bin" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PUBLIC" == "$ROOT" && "$PORTAL" != "$PUBLIC" ]] || { echo 'Unexpected document roots' >&2; exit 3; }
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal root must be under public_html' >&2; exit 3;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" && -d "$PORTAL/assets/css" && -d "$PUBLIC/assets" ]] || { echo 'Expected iWORKS installation missing' >&2; exit 3; }
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 120 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/templates" <<'EOF'
views/partials/public-assistance.php
views/partials/public-header.php
views/partials/public-footer.php
views/public-home-clean.php
views/public-about-refined.php
views/public-service-detail.php
views/public-blog-detail.php
EOF
cat > "$WORK/static" <<'EOF'
assets/css/public-assistance.css
assets/css/site-launch-polish.css
assets/js/public-assistance.js
EOF
cat "$WORK/templates" "$WORK/static" > "$WORK/portal-files"
for file in $(cat "$WORK/portal-files"); do
  [[ -s "$SRC/$file" ]] || { echo "Missing release asset: $file" >&2; exit 3; }
  [[ -f "$PORTAL/$file" && ! -L "$PORTAL/$file" ]] || { echo "Unsafe or missing live file: $file" >&2; exit 3; }
done
while IFS= read -r file; do php -l "$SRC/$file" >/dev/null; done < "$WORK/templates"
grep -Fq 'iw-assist-privacy-details' "$SRC/views/partials/public-assistance.php" || exit 3
grep -Fq 'preventScroll:true' "$SRC/assets/js/public-assistance.js" || exit 3
grep -Fq 'iw-photo-feature-hero' "$SRC/views/public-home-clean.php" || exit 3
grep -Fq 'iw-photo-feature-support' "$SRC/views/public-home-clean.php" || exit 3
grep -Fq '20261114-photo2' "$SRC/views/partials/public-header.php" || exit 3
grep -Fq '20261114-compact2' "$SRC/views/partials/public-footer.php" || exit 3
grep -Fq 'radial-gradient' "$SRC/assets/css/site-launch-polish.css" || exit 3
php "$SRC/tests/compact_chat_photo_contract_test.php"
# Existing mandatory read-only release preflight, before touching the server.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Release readiness failed; no changes performed.' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/public-changed"
: > "$WORK/portal-existing"
: > "$WORK/public-existing"
: > "$WORK/portal-added"
: > "$WORK/public-added"
while IFS= read -r file; do
  if ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    echo "$file" >> "$WORK/portal-existing"
  fi
done < "$WORK/portal-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  while IFS= read -r file; do
    [[ ! -L "$PUBLIC/$file" ]] || { echo "Unsafe apex symlink: $file" >&2; exit 3; }
    if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
      echo "$file" >> "$WORK/public-changed"
      if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/public-existing"; else echo "$file" >> "$WORK/public-added"; fi
    fi
  done < "$WORK/static"
fi
echo "Portal template/static changes: $(wc -l < "$WORK/portal-changed")"
echo "Apex static mirror changes: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED: no files were modified. Use --apply to publish.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/chat-photo-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"; fi
if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/public-existing"; fi
cp "$WORK/public-added" "$BACKUP/added-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/source-commit.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" == 1 ]]; then
    echo 'ERROR: restoring previous production files from private backup' >&2
    if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  local source="$1" dest="$2" folder tmp
  folder="$(dirname "$dest")"
  mkdir -p "$folder"
  tmp="$(mktemp "$folder/.iworks-ux.XXXXXXXX")"
  cat "$source" > "$tmp"
  chmod 0644 "$tmp"
  mv -f "$tmp" "$dest"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/public-changed"
while IFS= read -r file; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Portal mismatch: $file" >&2; false; }
done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/public-changed"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/templates"
status="$(curl -sS --max-time 20 -o "$WORK/apex.html" -w '%{http_code}' 'https://iworksgt.com/')"
[[ "$status" == 200 ]] || { echo "Homepage smoke test returned HTTP $status" >&2; false; }
STARTED=0
trap - ERR
echo "SUCCESS: compact chat and real-photo accents deployed; backup: $BACKUP"
if grep -Fq 'iw-assist-privacy-details' "$WORK/apex.html"; then
  echo 'Live homepage includes compact chat markup.'
else
  echo 'NOTICE: public HTML may be served from cache. Verify apex routing and clear site/CDN cache if needed.'
fi
echo 'Customer records, auth, payment, media uploads and chat database remained untouched.'
