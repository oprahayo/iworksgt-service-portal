#!/usr/bin/env bash
# Deploy pinned iWorks application files to cPanel, without running SQL.
# Usage: bash deploy_admin_mobile_refresh.sh --check|--apply PORTAL_ROOT PUBLIC_ROOT
set -Eeuo pipefail
MODE="$1"
PORTAL_INPUT="$2"
PUBLIC_INPUT="$3"
[[ "$MODE" == "--check" || "$MODE" == "--apply" ]] || { echo 'Use --check or --apply' >&2; exit 2; }
for command in curl tar php cmp find mktemp readlink; do
  command -v "$command" >/dev/null || { echo "Missing $command" >&2; exit 1; }
done
PORTAL="$(readlink -f "$PORTAL_INPUT")"
PUBLIC="$(readlink -f "$PUBLIC_INPUT")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" ]] || { echo 'Invalid document root' >&2; exit 1; }
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Portal root must be under public_html' >&2; exit 1 ;; esac
[[ "$PORTAL" != "$PUBLIC" ]] || { echo 'Portal and public roots must be separate' >&2; exit 1; }
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" && -f "$PORTAL/views/partials/header.php" ]] || {
  echo 'Existing configured portal files not found; nothing changed.' >&2; exit 1;
}
RELEASE="0925f0375f19a0f5fe3811ee688150cba0d1850c"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$TMP/release.tar.gz"
mkdir -p "$TMP/release"
tar -xzf "$TMP/release.tar.gz" -C "$TMP/release" --strip-components=1
SRC="$TMP/release"
grep -Fq '$portalManagementPage' "$SRC/index.php"
grep -Fq 'iw-sitewide-cta' "$SRC/views/partials/public-footer.php"
grep -Fq 'iw-mobile-menu-group' "$SRC/views/partials/public-header.php"
grep -Fq 'CREATE TABLE IF NOT EXISTS blog_posts' "$SRC/database.sql"

# Copy all coherent application source files, not only a few updated templates.
# Never overwrite private config, uploaded media, storage, apex rewrites, or .htaccess.
: > "$TMP/list"
while IFS= read -r file; do
  case "$file" in
    app/config.php|storage/*|assets/uploads/*|public-root/*|.github/*|.htaccess) continue ;;
    app/*|api/*|assets/css/*|assets/js/*|assets/illustrations/*|views/*|scripts/*|tests/*|index.php|invoice.php|download.php|export.php|preview.php|sso.php|logout.php|setup.php|database.sql|cron_*.php)
      printf '%s\n' "$file" >> "$TMP/list" ;;
  esac
done < <(cd "$SRC" && find . -type f -print | sed 's#^\./##' | LC_ALL=C sort)
[[ -s "$TMP/list" ]] || { echo 'No eligible files'; exit 1; }
: > "$TMP/portal-change"
: > "$TMP/portal-existing"
: > "$TMP/portal-added"
: > "$TMP/public-change"
: > "$TMP/public-existing"
: > "$TMP/public-added"
MIRROR=1
if [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then MIRROR=0; fi
while IFS= read -r file; do
  case "$file" in
    *.php) php -l "$SRC/$file" >/dev/null ;;
    *.sh) bash -n "$SRC/$file" ;;
  esac
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$TMP/portal-change"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$TMP/portal-existing"; else echo "$file" >> "$TMP/portal-added"; fi
  fi
  if [[ "$MIRROR" -eq 1 ]]; then
    case "$file" in assets/css/*|assets/js/*|assets/illustrations/*)
      if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
        echo "$file" >> "$TMP/public-change"
        if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$TMP/public-existing"; else echo "$file" >> "$TMP/public-added"; fi
      fi ;;
    esac
  fi
done < "$TMP/list"
echo "Portal changes: $(wc -l < "$TMP/portal-change")"
echo "Public static asset changes: $(wc -l < "$TMP/public-change")"
if [[ "$MODE" == "--check" ]]; then
  echo 'CHECK ONLY: no site files or database records changed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/admin-public-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
if [[ -s "$TMP/portal-existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tar.gz" -T "$TMP/portal-existing"; fi
if [[ -s "$TMP/public-existing" ]]; then tar -C "$PUBLIC" -czf "$BACKUP/public-assets.tar.gz" -T "$TMP/public-existing"; fi
cp "$TMP/portal-added" "$BACKUP/added-portal.txt"
cp "$TMP/public-added" "$BACKUP/added-public-assets.txt"
echo "Backup: $BACKUP"
STARTED=0
restore() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'ERROR: restoring backups...' >&2
    if [[ -s "$TMP/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tar.gz" || true; fi
    if [[ -s "$TMP/public-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/public-assets.tar.gz" || true; fi
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$TMP/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$TMP/public-added"
  fi
  exit "$status"
}
trap restore ERR
STARTED=1
copy_file() {
  srcfile="$1"; destfile="$2"
  mkdir -p "$(dirname "$destfile")"
  stage="$(mktemp "$(dirname "$destfile")/.iworks-release.XXXXXXXX")"
  cat "$srcfile" > "$stage"
  chmod 0644 "$stage"
  mv -f "$stage" "$destfile"
}
while IFS= read -r file; do copy_file "$SRC/$file" "$PORTAL/$file"; done < "$TMP/portal-change"
while IFS= read -r file; do copy_file "$SRC/$file" "$PUBLIC/$file"; done < "$TMP/public-change"
php -l "$PORTAL/index.php" >/dev/null
php -l "$PORTAL/views/partials/public-header.php" >/dev/null
STARTED=0
trap - ERR
echo 'SUCCESS: source files deployed. No database migration ran.'
echo "Next check: php \"$PORTAL/scripts/repair_public_content_schema.php\" --check"
echo 'After a separate database backup, apply editorial schema repair only if needed.'
