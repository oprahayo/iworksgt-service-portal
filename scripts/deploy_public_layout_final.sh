#!/usr/bin/env bash
# Install the complete reviewed iWorks public site, native auth popup and layout corrections.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="4847b602e3d69ad6b987e0dad07d98c256f89455"
FILES=(
  'app/bootstrap.php'
  'app/domain.php'
  'app/job_reviews.php'
  'app/public-photo-config.php'
  'index.php'
  'assets/css/public-unified.css'
  'assets/css/public-clean.css'
  'assets/css/public-inner-clean.css'
  'assets/css/public-polish.css'
  'assets/css/reviews.css'
  'assets/css/services-list.css'
  'assets/css/ux-finish.css'
  'assets/css/auth-clean.css'
  'assets/css/public-layout-fixes.css'
  'assets/js/public-ux.js'
  'views/job.php'
  'views/login.php'
  'views/register.php'
  'views/public-home-clean.php'
  'views/public-about-refined.php'
  'views/public-services-refined.php'
  'views/public-contact-clean.php'
  'views/public-track-clean.php'
  'views/public-service-detail.php'
  'views/public-page.php'
  'views/partials/public-header.php'
  'views/partials/public-footer.php'
  'views/partials/public-review-carousel.php'
  'views/partials/header.php'
  'views/partials/auth-embed-header.php'
  'views/partials/auth-embed-footer.php'
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

grep -Fq 'public-home-clean.php' "$WORK_DIR/views/public-page.php" || fail 'Invalid release file: views/public-page.php'
grep -Fq 'iw-clean-faq-intro' "$WORK_DIR/views/public-home-clean.php" || fail 'Invalid release file: views/public-home-clean.php'
grep -Fq 'function selectView(value)' "$WORK_DIR/views/public-services-refined.php" || fail 'Invalid release file: views/public-services-refined.php'
grep -Fq 'data-iw-auth-pane="login"' "$WORK_DIR/views/partials/public-footer.php" || fail 'Invalid release file: views/partials/public-footer.php'
grep -Fq 'require __DIR__ . '"'"'/../register.php'"'"'' "$WORK_DIR/views/partials/public-footer.php" || fail 'Invalid release file: views/partials/public-footer.php'
grep -Fq 'public-layout-fixes.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Invalid release file: views/partials/public-header.php'
grep -Fq 'has-founder-photo' "$WORK_DIR/views/public-about-refined.php" || fail 'Invalid release file: views/public-about-refined.php'
grep -Fq 'iw-step-transmit' "$WORK_DIR/assets/css/public-layout-fixes.css" || fail 'Invalid release file: assets/css/public-layout-fixes.css'
grep -Fq 'iw-clean-faq-intro' "$WORK_DIR/assets/css/public-layout-fixes.css" || fail 'Invalid release file: assets/css/public-layout-fixes.css'
grep -Fq 'body.page-public .public-nav-center .nav-link' "$WORK_DIR/assets/css/ux-finish.css" || fail 'Invalid release file: assets/css/ux-finish.css'
grep -Fq 'data-iw-reviews' "$WORK_DIR/assets/js/public-ux.js" || fail 'Invalid release file: assets/js/public-ux.js'
grep -Fq 'function submit_job_review' "$WORK_DIR/app/job_reviews.php" || fail 'Invalid release file: app/job_reviews.php'
grep -Fq 'ensure_job_review_schema' "$WORK_DIR/app/bootstrap.php" || fail 'Invalid release file: app/bootstrap.php'
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
BACKUP="$BACKUP_DIR/native-auth-layout-$(date -u +%Y%m%dT%H%M%SZ)-$.tar.gz"
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
printf 'SUCCESS: iWorks native login and public layout corrections installed. Backup: %s\n' "$BACKUP"
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
check_live 'Home support photo and process' 'https://portal.iworksgt.com/' 'iw-clean-faq-intro'
check_live 'Services list/grid switch' 'https://portal.iworksgt.com/services' 'data-iw-service-view="grid"'
check_live 'About founder and reviews' 'https://portal.iworksgt.com/about' 'iw-about-min-founder'
printf 'Test Sign in, Create account and switching on a real browser after clearing cache.\n'
