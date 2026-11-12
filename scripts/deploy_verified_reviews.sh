#!/usr/bin/env bash
# Install the reviewed job-reviews and final public-UX update.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="70f3a158ab1c64f8fb8724f9711ca695331a286f"
FILES=(
  'app/bootstrap.php'
  'app/domain.php'
  'app/job_reviews.php'
  'app/public-photo-config.php'
  'index.php'
  'assets/css/reviews.css'
  'assets/css/services-list.css'
  'assets/css/ux-finish.css'
  'assets/css/auth-clean.css'
  'assets/js/public-ux.js'
  'views/job.php'
  'views/login.php'
  'views/register.php'
  'views/public-home-clean.php'
  'views/public-about-refined.php'
  'views/public-services-refined.php'
  'views/public-service-detail.php'
  'views/partials/public-header.php'
  'views/partials/public-footer.php'
  'views/partials/public-review-carousel.php'
  'views/partials/header.php'
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

grep -Fq 'function submit_job_review' "$WORK_DIR/app/job_reviews.php" || fail 'Release preflight failed: app/job_reviews.php'
grep -Fq 'ensure_job_review_schema' "$WORK_DIR/app/bootstrap.php" || fail 'Release preflight failed: app/bootstrap.php'
grep -Fq 'submit_order_review' "$WORK_DIR/views/job.php" || fail 'Release preflight failed: views/job.php'
grep -Fq 'list_public_job_reviews' "$WORK_DIR/views/partials/public-review-carousel.php" || fail 'Release preflight failed: views/partials/public-review-carousel.php'
grep -Fq 'data-iw-service-view="grid"' "$WORK_DIR/views/public-services-refined.php" || fail 'Release preflight failed: views/public-services-refined.php'
grep -Fq 'public-review-carousel.php' "$WORK_DIR/views/public-home-clean.php" || fail 'Release preflight failed: views/public-home-clean.php'
grep -Fq 'public-review-carousel.php' "$WORK_DIR/views/public-about-refined.php" || fail 'Release preflight failed: views/public-about-refined.php'
grep -Fq 'ux-finish.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Release preflight failed: views/partials/public-header.php'
grep -Fq 'iwPublicHelpDialog' "$WORK_DIR/views/partials/public-footer.php" || fail 'Release preflight failed: views/partials/public-footer.php'
grep -Fq 'iwRegisterNext' "$WORK_DIR/views/register.php" || fail 'Release preflight failed: views/register.php'
grep -Fq 'data-iw-service-view' "$WORK_DIR/assets/js/public-ux.js" || fail 'Release preflight failed: assets/js/public-ux.js'
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
BACKUP="$BACKUP_DIR/verified-reviews-$(date -u +%Y%m%dT%H%M%SZ)-$$.tar.gz"
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
printf 'SUCCESS: Verified customer reviews and public UX installed. Backup: %s\n' "$BACKUP"
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
check_live 'Home customer reviews' 'https://portal.iworksgt.com/' 'iw-reviews-google'
check_live 'List and grid Services' 'https://portal.iworksgt.com/services' 'data-iw-service-view="grid"'
check_live 'About customer reviews' 'https://portal.iworksgt.com/about' 'iw-reviews-google'
