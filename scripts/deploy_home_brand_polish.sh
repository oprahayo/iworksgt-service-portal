#!/usr/bin/env bash
# iWORKS homepage + staff navigation refinements, and one authentic Pexels photograph. No SQL or auth changes.
# bash deploy_home_brand_polish.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT
set -Eeuo pipefail
if [[ $# -ne 4 ]]; then
  echo 'Usage: bash deploy_home_brand_polish.sh --check|--apply COMMIT_SHA PORTAL_ROOT PUBLIC_ROOT' >&2
  exit 2
fi
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Require immutable 40-character release SHA' >&2; exit 2; }
for bin in curl php tar cmp mktemp readlink; do command -v "$bin" >/dev/null || exit 3; done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$HTML" ]] || { echo 'Unexpected public/portal roots' >&2; exit 3; }
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Unexpected portal path' >&2; exit 3 ;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" && -f "$PORTAL/views/public-home-clean.php" ]] || { echo 'Configured portal not found' >&2; exit 3; }
[[ -d "$PORTAL/assets" && -d "$PUBLIC/assets" ]] || { echo 'Static asset paths missing' >&2; exit 3; }
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/templates" <<'EOF'
app/public-photo-config.php
views/public-home-clean.php
views/partials/public-header.php
views/partials/header.php
views/blog.php
views/editorial-showcase.php
EOF
cat > "$WORK/styles" <<'EOF'
assets/css/public-clean.css
assets/css/public-layout-fixes.css
assets/css/editorial-brand.css
assets/css/site-launch-polish.css
EOF
while IFS= read -r file; do
  [[ -f "$SRC/$file" ]] || exit 3
  php -l "$SRC/$file" >/dev/null
done < "$WORK/templates"
grep -Fq "'support-agent' => 'support-agent-black.jpg'" "$SRC/app/public-photo-config.php" || exit 3
grep -Fq 'iw-home-brand-ribbon' "$SRC/views/public-home-clean.php" || exit 3
while IFS= read -r file; do [[ -s "$SRC/$file" ]] || exit 3; done < "$WORK/styles"
# Existing mandatory release preflight remains read-only and fail-closed.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || { echo 'Safety preflight failed; no production changes' >&2; exit 3; }
cat > "$WORK/photos" <<'EOF'
support-agent-black.jpg|7689752|1000
EOF
mkdir -p "$WORK/assets/photography"
while IFS='|' read -r name id width; do
  photo="$WORK/assets/photography/$name"
  curl -fsSL --retry 2 --connect-timeout 15 --max-time 100 "https://images.pexels.com/photos/$id/pexels-photo-$id.jpeg?auto=compress&cs=tinysrgb&w=$width" -o "$photo"
  php -r '$p=$argv[1];$i=@getimagesize($p);$n=@filesize($p);if(!$i||$i[2]!==IMAGETYPE_JPEG||$i[0]<600||$i[1]<300||$n<15000||$n>3500000)exit(2);' "$photo" || { echo "Corrupt/invalid source photo: $name" >&2; exit 3; }
done < "$WORK/photos"
cat "$WORK/templates" "$WORK/styles" > "$WORK/portal-paths"
while IFS='|' read -r name id width; do echo "assets/photography/$name" >> "$WORK/portal-paths"; done < "$WORK/photos"
: > "$WORK/public-paths"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  cat "$WORK/styles" >> "$WORK/public-paths"
  while IFS='|' read -r name id width; do echo "assets/photography/$name" >> "$WORK/public-paths"; done < "$WORK/photos"
fi
for group in portal public; do
  : > "$WORK/$group-existing"; : > "$WORK/$group-added"; : > "$WORK/$group-changed"
done
while IFS= read -r file; do
  source_file="$SRC/$file"
  [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  [[ ! -L "$PORTAL/$file" ]] || { echo "Unsafe symlink: $file" >&2; exit 3; }
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$source_file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing"; else echo "$file" >> "$WORK/portal-added"; fi
  fi
done < "$WORK/portal-paths"
while IFS= read -r file; do
  source_file="$SRC/$file"; [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  [[ ! -L "$PUBLIC/$file" ]] || { echo "Unsafe public symlink: $file" >&2; exit 3; }
  if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$source_file" "$PUBLIC/$file"; then
    echo "$file" >> "$WORK/public-changed"
    if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/public-existing"; else echo "$file" >> "$WORK/public-added"; fi
  fi
done < "$WORK/public-paths"
echo "Portal files: $(wc -l < "$WORK/portal-changed"); apex assets: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'PASS: source, local-photo candidates and security checks. Production unchanged.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/authentic-photos-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"; fi
if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -czf "$BACKUP/apex-assets.tgz" -T "$WORK/public-existing"; fi
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/public-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Error: restoring prior files from private backup' >&2
    if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex-assets.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  source_file="$1"; destination="$2"; folder="$(dirname "$destination")"
  mkdir -p "$folder"
  if [[ "$destination" == */assets/photography/* ]]; then chmod 755 "$folder"; fi
  staged="$(mktemp "$folder/.photo-XXXXXXXX")"
  cat "$source_file" > "$staged"; chmod 644 "$staged"; mv -f "$staged" "$destination"
}
while IFS= read -r file; do
  source_file="$SRC/$file"; [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  copy_atomic "$source_file" "$PORTAL/$file"
done < "$WORK/portal-changed"
while IFS= read -r file; do
  source_file="$SRC/$file"; [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  copy_atomic "$source_file" "$PUBLIC/$file"
done < "$WORK/public-changed"
while IFS= read -r file; do
  source_file="$SRC/$file"; [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  cmp -s "$source_file" "$PORTAL/$file" || { echo "Portal mismatch: $file" >&2; false; }
done < "$WORK/portal-paths"
while IFS= read -r file; do
  source_file="$SRC/$file"; [[ "$file" == assets/photography/* ]] && source_file="$WORK/$file"
  cmp -s "$source_file" "$PUBLIC/$file" || false
done < "$WORK/public-paths"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/templates"
for path in / /assets/photography/support-agent-black.jpg /assets/css/public-layout-fixes.css; do
  status="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "https://iworksgt.com$path")" || status=000
  [[ "$status" == 200 ]] || { echo "Smoke-test failed: $path ($status)" >&2; false; }
done
STARTED=0
trap - ERR
echo "SUCCESS: homepage refinements deployed. Private rollback: $BACKUP"
echo 'No database, customer content, uploaded CMS media, auth, payments or mail data altered.'
