#!/usr/bin/env bash
# Deploy the reviewed iWorks public homepage and services layout release.
# Run from cPanel Terminal as the hosting account user.
set -Eeuo pipefail

REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="0b1be25d868d65c8313e7db8b526e4aede418074"
FILES=(
  'assets/css/launch-home.css'
  'assets/css/refined-services.css'
  'views/public-home-tbh.php'
  'views/public-services-refined.php'
  'views/partials/public-header.php'
  'views/public-page.php'
)

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
command -v php >/dev/null 2>&1 || fail 'PHP CLI is required.'
command -v curl >/dev/null 2>&1 || { [[ -n "${IWORKS_DEPLOY_LOCAL_SOURCE:-}" ]] || fail 'curl is required.'; }
command -v tar >/dev/null 2>&1 || fail 'tar is required.'

PUBLIC_HTML="$(readlink -f "${HOME}/public_html")"
[[ -d "$PUBLIC_HTML" ]] || fail "Cannot find $HOME/public_html"

if [[ $# -ge 1 ]]; then
  SITE_ROOT="$(readlink -f "$1")"
else
  # CageFS/cPanel terminals may not expose /dev/fd; avoid process substitution.
  CANDIDATES_FILE="$(mktemp "${TMPDIR:-/tmp}/iworks-candidates.XXXXXXXX")"
  find "$PUBLIC_HTML" -maxdepth 7 -type f -path '*/views/public-page.php' -print > "$CANDIDATES_FILE"
  mapfile -t MATCHES < "$CANDIDATES_FILE"
  rm -f "$CANDIDATES_FILE"
  [[ "${#MATCHES[@]}" -eq 1 ]] || {
    printf 'Found %s possible portal installations.\n' "${#MATCHES[@]}" >&2
    printf '  %s\n' "${MATCHES[@]:-}" >&2
    fail 'Run: bash deploy_homepage.sh /full/path/to/portal/document-root'
  }
  SITE_ROOT="$(readlink -f "$(dirname "$(dirname "${MATCHES[0]}")")")"
fi

case "$SITE_ROOT" in
  "$PUBLIC_HTML"|"$PUBLIC_HTML"/*) ;;
  *) fail 'Refusing to operate outside your public_html directory.' ;;
esac
[[ -f "$SITE_ROOT/index.php" && -f "$SITE_ROOT/app/bootstrap.php" && -f "$SITE_ROOT/views/public-page.php" && -f "$SITE_ROOT/views/partials/public-header.php" ]] || fail 'Not an iWorks portal installation. Nothing modified.'
grep -Fq 'render_public_block' "$SITE_ROOT/views/public-page.php" || fail 'Portal homepage signature not found. Nothing modified.'
printf 'iWorks portal root: %s\n' "$SITE_ROOT"

WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/iworks-deploy.XXXXXXXX")"
trap 'rm -rf "$WORK_DIR"' EXIT
for file in "${FILES[@]}"; do
  mkdir -p "$WORK_DIR/$(dirname "$file")"
  if [[ -n "${IWORKS_DEPLOY_LOCAL_SOURCE:-}" ]]; then
    [[ -f "$IWORKS_DEPLOY_LOCAL_SOURCE/$file" ]] || fail "Local source missing: $file"
    cp "$IWORKS_DEPLOY_LOCAL_SOURCE/$file" "$WORK_DIR/$file"
  else
    url="https://raw.githubusercontent.com/$REPOSITORY/$RELEASE/$file"
    curl --fail --location --silent --show-error --retry 2 --connect-timeout 15 --max-time 80 "$url" -o "$WORK_DIR/$file" || fail "Download failed: $file. No changes made."
  fi
  [[ -s "$WORK_DIR/$file" ]] || fail "Downloaded file empty: $file"
done

grep -Fq 'launch-home.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Incorrect header file.'
grep -Fq 'public-home-tbh.php' "$WORK_DIR/views/public-page.php" || fail 'Incorrect homepage integration.'
grep -Fq 'public-services-refined.php' "$WORK_DIR/views/public-page.php" || fail 'Incorrect services integration.'
grep -Fq 'refined-services.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Incorrect services stylesheet integration.'
grep -Fq 'launch-hero' "$WORK_DIR/views/public-home-tbh.php" || fail 'Incorrect homepage template.'
grep -Fq '.iworks-launch-home' "$WORK_DIR/assets/css/launch-home.css" || fail 'Incorrect homepage stylesheet.'
grep -Fq '.iworks-services-page' "$WORK_DIR/assets/css/refined-services.css" || fail 'Incorrect services stylesheet.'
php -l "$WORK_DIR/views/public-page.php" >/dev/null || fail 'Homepage integration has a PHP syntax error.'
php -l "$WORK_DIR/views/partials/public-header.php" >/dev/null || fail 'Public header has a PHP syntax error.'
php -l "$WORK_DIR/views/public-home-tbh.php" >/dev/null || fail 'Homepage template has a PHP syntax error.'
php -l "$WORK_DIR/views/public-services-refined.php" >/dev/null || fail 'Services template has a PHP syntax error.'

all_equal=1
for file in "${FILES[@]}"; do
  if [[ ! -f "$SITE_ROOT/$file" ]] || ! cmp -s "$WORK_DIR/$file" "$SITE_ROOT/$file"; then all_equal=0; fi
done
if [[ "$all_equal" -eq 1 ]]; then
  printf 'Homepage files are already at the requested release. Nothing to change.\n'
  exit 0
fi

umask 077
BACKUP_DIR="${HOME}/iworks-deployment-backups"
mkdir -p "$BACKUP_DIR"
BACKUP="$BACKUP_DIR/frontend-$(date -u +%Y%m%dT%H%M%SZ)-$.tar.gz"
PRESENT=()
ADDED=()
for file in "${FILES[@]}"; do
  if [[ -f "$SITE_ROOT/$file" ]]; then PRESENT+=("$file"); else ADDED+=("$file"); fi
done
[[ "${#PRESENT[@]}" -ge 2 ]] || fail 'Expected existing portal files not found.'
tar -C "$SITE_ROOT" -czf "$BACKUP" "${PRESENT[@]}" || fail 'Backup failed. Nothing modified.'
printf 'Backup saved: %s\n' "$BACKUP"

STARTED=0
rollback_on_error() {
  code=$?
  if [[ "$STARTED" -eq 1 ]]; then
    printf 'Installation failed; restoring backup ...\n' >&2
    tar -C "$SITE_ROOT" -xzf "$BACKUP" || printf 'WARNING: Automatic restore failed; restore %s manually.\n' "$BACKUP" >&2
    for file in "${ADDED[@]}"; do rm -f "$SITE_ROOT/$file"; done
  fi
  exit "$code"
}
trap rollback_on_error ERR

STARTED=1
# New static/template files first; update live entrypoints last.
for file in "${FILES[@]}"; do
  target="$SITE_ROOT/$file"
  mkdir -p "$(dirname "$target")"
  temp_target="$(mktemp "$(dirname "$target")/.iworks-release.XXXXXXXX")"
  cat "$WORK_DIR/$file" > "$temp_target"
  chmod 0644 "$temp_target"
  mv -f "$temp_target" "$target"
  printf 'Updated: %s\n' "$file"
done
php -l "$SITE_ROOT/views/public-page.php" >/dev/null
php -l "$SITE_ROOT/views/partials/public-header.php" >/dev/null
php -l "$SITE_ROOT/views/public-home-tbh.php" >/dev/null
php -l "$SITE_ROOT/views/public-services-refined.php" >/dev/null
STARTED=0
trap - ERR
printf 'SUCCESS: Public frontend files deployed. Backup: %s\n' "$BACKUP"

if [[ "${IWORKS_DEPLOY_SKIP_LIVE_CHECK:-0}" != '1' ]]; then
  if curl --fail --location --silent --show-error --max-time 30 'https://portal.iworksgt.com/services?iworks_release_check=1' | grep -Fq 'Find the right service'; then
    printf 'SERVICES LIVE VERIFIED: new catalogue marker found.\n'
  else
    printf 'NOTICE: Services page marker not found; check routing and CDN caching.\n'
  fi
  if curl --fail --location --silent --show-error --max-time 30 'https://portal.iworksgt.com/?iworks_release_check=1' | grep -Fq 'Business services, made simpler'; then
    printf 'HOMEPAGE LIVE VERIFIED: new homepage marker found.\n'
  else
    printf 'NOTICE: Homepage marker not found; check host routing and cache.\n'
  fi
fi
