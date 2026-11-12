#!/usr/bin/env bash
# Restore all current Services colours and install public mobile/technical SEO fixes.
# Validates the release and backs up existing files before any replacement.
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="e9958029657f9b3fb2206754e1a9677c100464fc"
FILES=(
  'app/helpers.php'
  'app/auth.php'
  'app/bootstrap.php'
  'app/public_seo.php'
  'index.php'
  '.htaccess'
  'views/settings.php'
  'views/login.php'
  'views/register.php'
  'views/public-services-refined.php'
  'views/partials/public-header.php'
  'views/partials/public-footer.php'
  'views/partials/header.php'
  'assets/css/services-list.css'
  'assets/css/auth-compact.css'
  'assets/css/public-mobile-audit.css'
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

grep -Fq -- 'function iworks_seo_public_sitemap' "$WORK_DIR/app/public_seo.php" || fail 'Release verification failed: app/public_seo.php'
grep -Fq -- 'if ($page === '"'"'seo_sitemap'"'"')' "$WORK_DIR/index.php" || fail 'Release verification failed: index.php'
grep -Fq -- 'sitemap\.xml' "$WORK_DIR/.htaccess" || fail 'Release verification failed: .htaccess'
grep -Fq -- 'robots\.txt' "$WORK_DIR/.htaccess" || fail 'Release verification failed: .htaccess'
grep -Fq -- 'rel="canonical"' "$WORK_DIR/views/partials/public-header.php" || fail 'Release verification failed: views/partials/public-header.php'
grep -Fq -- 'public-mobile-audit.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Release verification failed: views/partials/public-header.php'
grep -Fq -- 'auth-compact.css' "$WORK_DIR/views/partials/public-header.php" || fail 'Release verification failed: views/partials/public-header.php'
grep -Fq -- 'iwEmbeddedPublicAuth' "$WORK_DIR/views/partials/public-footer.php" || fail 'Release verification failed: views/partials/public-footer.php'
grep -Fq -- 'data-layout="grid"' "$WORK_DIR/views/public-services-refined.php" || fail 'Release verification failed: views/public-services-refined.php'
grep -Fq -- '--iw-tile-start' "$WORK_DIR/assets/css/services-list.css" || fail 'Release verification failed: assets/css/services-list.css'
grep -Fq -- 'min-height:44px' "$WORK_DIR/assets/css/public-mobile-audit.css" || fail 'Release verification failed: assets/css/public-mobile-audit.css'
grep -Fq -- 'portal_url('"'"'google_start'"'"')' "$WORK_DIR/views/login.php" || fail 'Release verification failed: views/login.php'
grep -Fq -- 'portal_url('"'"'google_start'"'"')' "$WORK_DIR/views/register.php" || fail 'Release verification failed: views/register.php'
grep -Fq -- 'PHP_SAPI' "$WORK_DIR/scripts/check_google_oauth.php" || fail 'Release verification failed: scripts/check_google_oauth.php'
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
BACKUP="$BACKUP_DIR/mobile-seo-grid-$(date -u +%Y%m%dT%H%M%SZ)-$$.tar.gz"
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
printf 'SUCCESS: grid colours, mobile navigation and technical SEO installed. Backup: %s\n' "$BACKUP"
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
check_live 'SEO sitemap' 'https://portal.iworksgt.com/sitemap.xml' '<urlset'
check_live 'Robots discovery' 'https://portal.iworksgt.com/robots.txt' 'Sitemap:'
printf '\nGoogle OAuth readiness (credentials remain hidden):\n'
php "$SITE_ROOT/scripts/check_google_oauth.php" || printf 'NOTICE: Google OAuth diagnostic unavailable; inspect Admin Settings.\n'
printf '\nIMPORTANT: Validate live mobile navigation and submit the sitemap to Search Console after deployment.\n'
