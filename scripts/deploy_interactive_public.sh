#!/usr/bin/env bash
# Install the mobile-first services and interactive iWorks public-site release.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="f211134b09e75c1539f966c52008d2b2c1fad746"
FILES=(
  'app/bootstrap.php'
  'app/public-photo-config.php'
  'index.php'
  'assets/css/public-unified.css'
  'assets/css/public-clean.css'
  'assets/css/public-inner-clean.css'
  'assets/css/services-list.css'
  'assets/css/public-polish.css'
  'assets/css/auth-clean.css'
  'views/public-home-clean.php'
  'views/public-services-refined.php'
  'views/public-about-refined.php'
  'views/public-contact-clean.php'
  'views/public-track-clean.php'
  'views/public-service-detail.php'
  'views/login.php'
  'views/register.php'
  'views/partials/auth-embed-header.php'
  'views/partials/auth-embed-footer.php'
  'views/partials/public-footer.php'
  'views/partials/public-header.php'
  'views/partials/header.php'
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
grep -Fq '.iw-clean-home' "$WORK_DIR/assets/css/public-clean.css" || fail 'Homepage stylesheet invalid'
grep -Fq '.iw-service-index-row' "$WORK_DIR/assets/css/services-list.css" || fail 'Service directory stylesheet invalid'
grep -Fq '.iw-home-track' "$WORK_DIR/assets/css/public-polish.css" || fail 'Homepage interaction stylesheet invalid'
grep -Fq '.iw-auth-embed' "$WORK_DIR/assets/css/auth-clean.css" || fail 'Authentication stylesheet invalid'
grep -Fq 'frame-ancestors' "$WORK_DIR/app/bootstrap.php" || fail 'Authentication frame protection missing'
grep -Fq 'auth-embed-header.php' "$WORK_DIR/index.php" || fail 'Authentication router hook missing'
grep -Fq 'public-home-clean.php' "$WORK_DIR/views/public-page.php" || fail 'Homepage router missing'
grep -Fq 'public-contact-clean.php' "$WORK_DIR/views/public-page.php" || fail 'Contact route missing'
grep -Fq 'public-track-clean.php' "$WORK_DIR/views/public-page.php" || fail 'Tracking route missing'
grep -Fq 'services-list.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Service directory CSS hook missing'
grep -Fq 'public-polish.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Homepage polish CSS hook missing'
grep -Fq 'data-service-row' "$WORK_DIR/views/public-services-refined.php" || fail 'Service listing template invalid'
grep -Fq 'name="form_action" value="public_track"' "$WORK_DIR/views/public-home-clean.php" || fail 'Homepage tracking form missing'
grep -Fq 'data-iw-help-open' "$WORK_DIR/views/public-home-clean.php" || fail 'Human support dialog link missing'
grep -Fq 'name="form_action" value="website_lead"' "$WORK_DIR/views/partials/public-footer.php" || fail 'Human support message form missing'
grep -Fq 'data-iw-auth=' "$WORK_DIR/views/partials/public-header.php" || fail 'Sign-in dialog trigger missing'
grep -Fq 'target="_top"' "$WORK_DIR/views/login.php" || fail 'Login form target missing'
grep -Fq 'target="_top"' "$WORK_DIR/views/register.php" || fail 'Registration form target missing'
grep -Fq 'auth-embed-footer.php' "$WORK_DIR/index.php" || fail 'Embedded auth footer hook missing'
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
BACKUP="$BACKUP_DIR/iworks-interactive-public-$(date -u +%Y%m%dT%H%M%SZ)-$.tar.gz"
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
printf 'SUCCESS: iWorks interactive public website installed. Backup: %s\n' "$BACKUP"
check_live() {
  label="$1"
  url="$2"
  marker="$3"
  RESPONSE_FILE="$(mktemp "${TMPDIR:-/tmp}/iworks-release-check.XXXXXXXX")"
  if curl --fail --location --silent --show-error --max-time 25 "$url?verify=$(date +%s)" -o "$RESPONSE_FILE" && grep -Fq "$marker" "$RESPONSE_FILE"; then
    printf 'LIVE VERIFIED: %s\n' "$label"
  else
    printf 'NOTICE: %s not verified. Check document root and cache.\n' "$label"
  fi
  rm -f "$RESPONSE_FILE"
}
check_live 'Homepage interactive tracking' 'https://portal.iworksgt.com/' 'iw-home-track'
check_live 'Mobile-first Services directory' 'https://portal.iworksgt.com/services' 'iw-service-index-row'
check_live 'About' 'https://portal.iworksgt.com/about' 'iw-about-min'
