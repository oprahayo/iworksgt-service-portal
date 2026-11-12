#!/usr/bin/env bash
# Install the verified service-first public iWorks homepage and shared page files.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="90b0b1dd335d9938ab6edcd1df7d96f2488a2a3d"
FILES=(
  'app/public-photo-config.php'
  'assets/css/businesshub-home.css'
  'assets/css/refined-services.css'
  'assets/css/public-unified.css'
  'views/public-services-refined.php'
  'views/public-home-businesshub.php'
  'views/public-about-refined.php'
  'views/public-service-detail.php'
  'views/partials/public-header.php'
  'views/public-page.php'
)
fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
command -v curl >/dev/null || fail 'curl is required'
command -v php >/dev/null || fail 'PHP CLI is required'
command -v tar >/dev/null || fail 'tar is required'
command -v cmp >/dev/null || fail 'cmp is required'
SITE_ROOT="$(readlink -f "${1:-$HOME/public_html/portal.iworksgt.com}")"
PUBLIC_HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$SITE_ROOT" ]] || fail "Portal directory not found: $SITE_ROOT"
case "$SITE_ROOT" in "$PUBLIC_HTML"|"$PUBLIC_HTML"/*) ;; *) fail 'Refusing path outside public_html' ;; esac
[[ -f "$SITE_ROOT/index.php" && -f "$SITE_ROOT/views/public-page.php" && -f "$SITE_ROOT/views/partials/public-header.php" ]] || fail 'Not an existing iWorks portal install'
grep -Fq 'render_public_block' "$SITE_ROOT/views/public-page.php" || fail 'Unexpected installed portal version; no changes made.'
printf 'Deploying into: %s\n' "$SITE_ROOT"

WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/iworks-photo-release.XXXXXXXX")"
trap 'rm -rf "$WORK_DIR"' EXIT
for file in "${FILES[@]}"; do
  mkdir -p "$WORK_DIR/$(dirname "$file")"
  curl --fail --location --silent --show-error --retry 2 --connect-timeout 15 --max-time 80 "https://raw.githubusercontent.com/$REPOSITORY/$RELEASE/$file" -o "$WORK_DIR/$file" || fail "Download failed: $file"
  [[ -s "$WORK_DIR/$file" ]] || fail "Empty downloaded file: $file"
done

grep -Fq 'function iworks_stock_photo_url' "$WORK_DIR/app/public-photo-config.php" || fail 'Photo config invalid'
grep -Fq '.iworks-bhub' "$WORK_DIR/assets/css/businesshub-home.css" || fail 'Homepage stylesheet invalid'
grep -Fq 'iworks-services-page' "$WORK_DIR/assets/css/refined-services.css" || fail 'Services stylesheet invalid'
grep -Fq 'isc-grid' "$WORK_DIR/views/public-services-refined.php" || fail 'Services template invalid'
grep -Fq 'photo-1772588627327-1eeddcf73c11' "$WORK_DIR/app/public-photo-config.php" || fail 'Registration photo ID missing'
grep -Fq 'What would you like to register?' "$WORK_DIR/views/public-home-businesshub.php" || fail 'Homepage markup invalid'
grep -Fq 'iworks_stock_photo_url' "$WORK_DIR/views/public-about-refined.php" || fail 'About photo markup missing'
grep -Fq 'iworks_stock_photo_url' "$WORK_DIR/views/public-service-detail.php" || fail 'Service detail photo markup missing'
grep -Fq 'public-home-businesshub.php' "$WORK_DIR/views/public-page.php" || fail 'New public homepage route missing'
grep -Fq 'businesshub-home.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Homepage CSS hook missing'
for file in "${FILES[@]}"; do
  case "$file" in *.php) php -l "$WORK_DIR/$file" >/dev/null || fail "PHP syntax error in $file";; esac
done

changed=0
for file in "${FILES[@]}"; do
  if [[ ! -f "$SITE_ROOT/$file" ]] || ! cmp -s "$WORK_DIR/$file" "$SITE_ROOT/$file"; then changed=1; fi
done
if [[ "$changed" -eq 0 ]]; then printf 'ALREADY INSTALLED: no changes needed.\n'; exit 0; fi

umask 077
BACKUP_DIR="$HOME/iworks-deployment-backups"
mkdir -p "$BACKUP_DIR"
BACKUP="$BACKUP_DIR/businesshub-home-$(date -u +%Y%m%dT%H%M%SZ)-$.tar.gz"
PRESENT=() ADDED=()
for file in "${FILES[@]}"; do
  if [[ -f "$SITE_ROOT/$file" ]]; then PRESENT+=("$file"); else ADDED+=("$file"); fi
done
[[ "${#PRESENT[@]}" -ge 4 ]] || fail 'Existing public files missing; stop without changes.'
tar -C "$SITE_ROOT" -czf "$BACKUP" "${PRESENT[@]}" || fail 'Could not back up current files'
printf 'Backup saved: %s\n' "$BACKUP"
STARTED=0
rollback_on_error() {
  code=$?
  if [[ "$STARTED" == 1 ]]; then
    printf 'Update failed; restoring previous files...\n' >&2
    tar -C "$SITE_ROOT" -xzf "$BACKUP" || printf 'WARNING: backup restore failed: %s\n' "$BACKUP" >&2
    for file in "${ADDED[@]}"; do rm -f "$SITE_ROOT/$file"; done
  fi
  exit "$code"
}
trap rollback_on_error ERR
STARTED=1
for file in "${FILES[@]}"; do
  target="$SITE_ROOT/$file"
  mkdir -p "$(dirname "$target")"
  staging="$(mktemp "$(dirname "$target")/.iworks-photo.XXXXXXXX")"
  cat "$WORK_DIR/$file" > "$staging"
  chmod 0644 "$staging"
  mv -f "$staging" "$target"
  printf 'Updated: %s\n' "$file"
done
for file in "${FILES[@]}"; do
  case "$file" in *.php) php -l "$SITE_ROOT/$file" >/dev/null;; esac
done
STARTED=0
trap - ERR
printf 'SUCCESS: iWorks homepage redesign installed. Backup: %s\n' "$BACKUP"
RESPONSE_FILE="$(mktemp "${TMPDIR:-/tmp}/iworks-photocheck.XXXXXXXX")"
if curl --fail --location --silent --show-error --max-time 25 "https://portal.iworksgt.com/?home_release=$(date +%s)" -o "$RESPONSE_FILE" && grep -Fq 'What would you like to register?' "$RESPONSE_FILE"; then
  printf 'LIVE VERIFIED: New iWorks service-first homepage is visible.\n'
else
  printf 'NOTICE: Live page marker not found. Verify deployment path and clear page cache.\n'
fi
rm -f "$RESPONSE_FILE"
