#!/usr/bin/env bash
# Restore only the existing homepage hero logo ribbon. No SQL, uploads or payments.
# Usage: bash scripts/deploy_hero_logo_carousel_restore.sh --check|--apply SHA PORTAL_ROOT PUBLIC_ROOT
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: --check|--apply RELEASE_SHA PORTAL_ROOT PUBLIC_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full immutable release SHA required' >&2; exit 2; }
for bin in curl tar php cmp readlink mktemp grep; do command -v "$bin" >/dev/null || { echo "Missing: $bin" >&2; exit 3; }; done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$HTML" ]] || { echo 'Unexpected public/portal roots' >&2; exit 3; }
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Portal must reside in public_html' >&2; exit 3 ;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/views/public-home-clean.php" ]] || { echo 'Configured portal missing' >&2; exit 3; }
[[ -d "$PORTAL/assets/css" && -d "$PUBLIC/assets/css" ]] || { echo 'Static asset roots missing' >&2; exit 3; }
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/portal-files" <<'EOF'
views/public-home-clean.php
views/partials/public-header.php
assets/css/public-clean.css
EOF
: > "$WORK/public-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then echo 'assets/css/public-clean.css' > "$WORK/public-files"; fi
for f in views/public-home-clean.php views/partials/public-header.php; do
    [[ -s "$SRC/$f" ]] || exit 3
    php -l "$SRC/$f" >/dev/null
done
[[ -s "$SRC/assets/css/public-clean.css" ]] || exit 3
php "$SRC/tests/public_clean_home_test.php"
php "$SRC/tests/home_brand_refinement_contract_test.php"
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
    echo 'Mandatory release preflight failed. Production unchanged.' >&2
    exit 3
}
for target in portal public; do
    : > "$WORK/$target-existing"
    : > "$WORK/$target-added"
    : > "$WORK/$target-changed"
done
while IFS= read -r file; do
    [[ ! -L "$PORTAL/$file" ]] || { echo "Symlink refused: $file" >&2; exit 3; }
    if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
        echo "$file" >> "$WORK/portal-changed"
        if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing"; else echo "$file" >> "$WORK/portal-added"; fi
    fi
done < "$WORK/portal-files"
while IFS= read -r file; do
    [[ ! -L "$PUBLIC/$file" ]] || { echo "Symlink refused: $file" >&2; exit 3; }
    if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
        echo "$file" >> "$WORK/public-changed"
        if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/public-existing"; else echo "$file" >> "$WORK/public-added"; fi
    fi
done < "$WORK/public-files"
echo "Portal source files changing: $(wc -l < "$WORK/portal-changed")"
echo "Apex static files changing: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
    echo 'CHECK passed. No website files, database or media changed.'
    exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/hero-logo-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"; fi
if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/public-existing"; fi
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/public-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
    code=$?
    if [[ "$STARTED" -eq 1 ]]; then
        echo 'Restoring prior homepage files from private backup.' >&2
        [[ ! -s "$WORK/portal-existing" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
        [[ ! -s "$WORK/public-existing" ]] || tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true
        while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
        while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
    fi
    exit "$code"
}
trap rollback ERR
copy_atomic() {
    local source="$1" destination="$2" target
    target="$(mktemp "$(dirname "$destination")/.hero-logo-XXXXXXXX")"
    cat "$source" > "$target"
    chmod 0644 "$target"
    mv -f "$target" "$destination"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/public-changed"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PORTAL/$file" || false; done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/public-files"
php -l "$PORTAL/views/public-home-clean.php" >/dev/null
php -l "$PORTAL/views/partials/public-header.php" >/dev/null
curl -fsSL --max-time 25 "https://iworksgt.com/" -o "$WORK/live-home.html" || false
grep -Fq 'iw-home-brand-track is-scrolling' "$WORK/live-home.html" || {
    echo 'Hero carousel is still not scrolling; check existing Website Editor logo assets/IDs.' >&2
    false
}
grep -Fq 'Client projects &amp; service platforms' "$WORK/live-home.html" || {
    echo 'The restored logo grouping was not rendered; rolling back.' >&2
    false
}
STARTED=0
trap - ERR
echo "SUCCESS: original hero logo carousel restored. Private file backup: $BACKUP"
echo 'All existing uploaded logos, customer data, payments, footer and mobile menu remain untouched.'
