#!/usr/bin/env bash
# Deploy the compact iWorks login, Google OAuth safeguards and illustrated Services grid.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="400583a4199f09b3b6cd72d4c1d5f8259d197225"
FILES=(
  'app/helpers.php'
  'app/auth.php'
  'index.php'
  'views/settings.php'
  'views/login.php'
  'views/register.php'
  'views/public-services-refined.php'
  'views/partials/public-header.php'
  'views/partials/header.php'
  'assets/css/services-list.css'
  'assets/css/auth-compact.css'
  'scripts/check_google_oauth.php'
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

grep -Fq 'data-layout="grid"' "$WORK_DIR/views/public-services-refined.php" || fail 'Invalid release file: views/public-services-refined.php'
grep -Fq 'data-tone=' "$WORK_DIR/views/public-services-refined.php" || fail 'Invalid release file: views/public-services-refined.php'
grep -Fq '--iw-tile-start' "$WORK_DIR/assets/css/services-list.css" || fail 'Invalid release file: assets/css/services-list.css'
grep -Fq 'portal_url('"'"'google_start'"'"')' "$WORK_DIR/views/login.php" || fail 'Invalid release file: views/login.php'
grep -Fq 'portal_url('"'"'google_start'"'"')' "$WORK_DIR/views/register.php" || fail 'Invalid release file: views/register.php'
grep -Fq 'class="iw-auth-home"' "$WORK_DIR/views/login.php" || fail 'Invalid release file: views/login.php'
grep -Fq 'auth-compact.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Invalid release file: views/partials/public-header.php'
grep -Fq 'auth-compact.css' "$WORK_DIR/views/partials/header.php" || fail 'Invalid release file: views/partials/header.php'
grep -Fq 'function google_oauth_redirect_problems' "$WORK_DIR/app/helpers.php" || fail 'Invalid release file: app/helpers.php'
grep -Fq 'google_oauth_redirect_problems()' "$WORK_DIR/app/auth.php" || fail 'Invalid release file: app/auth.php'
grep -Fq 'PHP_SAPI' "$WORK_DIR/scripts/check_google_oauth.php" || fail 'Invalid release file: scripts/check_google_oauth.php'
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
BACKUP="$BACKUP_DIR/google-grid-$(date -u +%Y%m%dT%H%M%SZ)-$.tar.gz"
PRESENT=() ADDED=()
for file in "${FILES[@]}"; do
  if [[ -f "$SITE_ROOT/$file" ]]; then PRESENT+=("$file"); else ADDED+=("$file"); fi
done
[[ "${#PRESENT[@]}" -ge 8 ]] || fail 'Existing public files missing; stop without changes.'
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
printf 'SUCCESS: compact login, Google OAuth improvements and illustrated grid installed. Backup: %s\n' "$BACKUP"
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
check_live 'Illustrated grid default' 'https://portal.iworksgt.com/services' 'data-layout="grid"'
check_live 'Standalone home shortcut' 'https://portal.iworksgt.com/index.php?page=login' 'iw-auth-home'
printf '\nGoogle OAuth readiness (no credentials displayed):\n'
php "$SITE_ROOT/scripts/check_google_oauth.php" || printf 'NOTICE: Google OAuth diagnostic unavailable; inspect your admin settings.\n'
printf '\nTest live Google login end-to-end with a real Google account; Cloud Console approval cannot be verified here.\n'
