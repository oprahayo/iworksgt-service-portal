#!/usr/bin/env bash
# iWorks two-origin installer. Safe read-only mode first; explicit --apply required.
# Usage:
# bash scripts/deploy_public_root_split.sh --check "$HOME/public_html" "$HOME/public_html/portal.iworksgt.com"
# bash scripts/deploy_public_root_split.sh --apply "$HOME/public_html" "$HOME/public_html/portal.iworksgt.com"
set -Eeuo pipefail
REPOSITORY="oprahayo/iworksgt-service-portal"
RELEASE="ffd2741a2b7a4fc605d62f32285f3e3ba109dd51"
MODE="$1"
fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
[[ "$MODE" == '--check' || "$MODE" == '--apply' ]] || fail 'Usage: --check or --apply PUBLIC_ROOT PORTAL_ROOT'
[[ $# -eq 3 ]] || fail 'Both cPanel document root paths are required'
for binary in curl php tar cmp readlink mktemp; do command -v "$binary" >/dev/null || fail "Missing prerequisite: $binary"; done
PUBLIC_ROOT="$(readlink -f "$2")"
PORTAL_ROOT="$(readlink -f "$3")"
PUBLIC_HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PUBLIC_ROOT" && -d "$PORTAL_ROOT" ]] || fail 'Both document roots must already exist'
[[ "$PUBLIC_ROOT" != "$PORTAL_ROOT" ]] || fail 'Public root and portal root must be separate'
case "$PUBLIC_ROOT" in "$PUBLIC_HTML"|"$PUBLIC_HTML"/*) ;; *) fail 'Public root must be under public_html' ;; esac
case "$PORTAL_ROOT" in "$PUBLIC_HTML"/*) ;; *) fail 'Portal root must be under public_html' ;; esac
case "$PUBLIC_ROOT/" in "$PORTAL_ROOT/"*) fail 'Public root cannot be inside portal root' ;; esac
[[ -f "$PORTAL_ROOT/app/config.php" && -f "$PORTAL_ROOT/index.php" && -d "$PORTAL_ROOT/assets" ]] || fail 'Existing production portal or assets not found'
[[ -f "$PORTAL_ROOT/views/public-page.php" ]] || fail 'Public CMS template missing from existing portal'
if [[ "$PUBLIC_ROOT" != "$(dirname "$PORTAL_ROOT")" && "$(dirname "$PUBLIC_ROOT")" != "$(dirname "$PORTAL_ROOT")" ]]; then
  fail 'Current front-controller template expects portal.iworksgt.com in public root or sibling directory'
fi
[[ "$(basename "$PORTAL_ROOT")" == 'portal.iworksgt.com' ]] || fail 'Portal directory must be portal.iworksgt.com'
printf 'PUBLIC DOMAIN: https://iworksgt.com\nPUBLIC DOCROOT: %s\n' "$PUBLIC_ROOT"
printf 'PORTAL DOMAIN: https://portal.iworksgt.com\nPORTAL DOCROOT: %s\n' "$PORTAL_ROOT"
if [[ -f "$PUBLIC_ROOT/.htaccess" ]]; then
  if grep -Eiq 'portal[.]iworksgt[.]com|Redirect(Match)?|RewriteRule' "$PUBLIC_ROOT/.htaccess"; then
    printf 'NOTICE: Existing root .htaccess contains redirect/rewrite rules; they will be backed up and replaced only in --apply.\n'
  fi
fi
printf 'Current public index: '
[[ -f "$PUBLIC_ROOT/index.php" ]] && printf 'present\n' || printf 'missing\n'
# Both --check and --apply validate all pinned release files before any site change.
# A successful --check is therefore a useful predictor of an --apply preflight.

FILES=(
  app/helpers.php app/auth.php app/bootstrap.php app/public_seo.php app/public_marketing.php app/order_intent.php app/trademark_applications.php app/stripe_card_vault.php app/client_workspace.php app/cac_filing.php app/editorial.php app/domain.php
  index.php invoice.php .htaccess views/settings.php views/login.php views/register.php views/support.php views/job.php views/intake-submission.php views/users.php views/blog.php views/frontend-editor.php views/frontend-editor-blocks.php views/dashboard.php
  views/public-page.php views/public-services-refined.php views/public-service-detail.php views/public-home-clean.php views/public-track-clean.php views/public-insights.php views/public-blog-detail.php views/public-case-studies-refined.php views/public-case-study-detail.php views/jobs.php
  views/client-dashboard.php views/client-applications.php views/client-trademark.php views/client-billing.php views/client-card-vault.php views/client-profile.php views/client-job.php views/client-cac-filing.php views/staff-cac-review.php views/editorial-showcase.php views/author-dashboard.php
  views/partials/public-header.php views/partials/public-footer.php views/partials/header.php views/partials/client-header.php
  assets/css/services-list.css assets/css/auth-compact.css assets/css/public-mobile-audit.css assets/css/client-portal.css assets/css/client-checkout.css assets/css/editorial-brand.css assets/css/admin-editorial.css
  scripts/check_google_oauth.php scripts/audit_public_seo_live.sh scripts/migrate_trademark_applications.php scripts/migrate_stripe_card_vault.php scripts/migrate_cac_filings.php scripts/migrate_editorial_content.php
)
ROOT_FILES=(index.php .htaccess)
WORK="$(mktemp -d)"
BACKUP_DIR="$HOME/iworks-deployment-backups/public-portal-split-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$WORK/portal" "$WORK/root"
STARTED=0
ADDED_PORTAL=()
ADDED_ROOT=()
ASSETS_MOVED=0
NEW_ASSETS_LINK=0
restore() {
  local status="${1:-$?}"
  trap - EXIT ERR INT TERM
  if [[ "$STARTED" == 1 && "$status" -ne 0 ]]; then
    printf 'Deployment interrupted. Restoring previous web-root files.\n' >&2
    for file in "${FILES[@]}"; do
      if [[ -f "$BACKUP_DIR/portal/$file" ]]; then
        cp -p "$BACKUP_DIR/portal/$file" "$PORTAL_ROOT/$file" || true
      else
        rm -f "$PORTAL_ROOT/$file"
      fi
    done
    for file in "${ROOT_FILES[@]}"; do
      if [[ -f "$BACKUP_DIR/root/$file" ]]; then
        cp -p "$BACKUP_DIR/root/$file" "$PUBLIC_ROOT/$file" || true
      else
        rm -f "$PUBLIC_ROOT/$file"
      fi
    done
    if [[ "$NEW_ASSETS_LINK" == 1 && -L "$PUBLIC_ROOT/assets" ]]; then rm -f "$PUBLIC_ROOT/assets"; fi
    if [[ "$ASSETS_MOVED" == 1 && -e "$BACKUP_DIR/root-assets" ]]; then
      mv "$BACKUP_DIR/root-assets" "$PUBLIC_ROOT/assets" || true
    fi
  fi
  rm -rf "$WORK"
  exit "$status"
}
# EXIT also catches an explicit fail() call after file replacement: restore all
# web files if either PHP migration fails, not just failures from set -e.
trap 'restore $?' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
for file in "${FILES[@]}"; do
  mkdir -p "$WORK/portal/$(dirname "$file")"
  curl -fsSL --retry 3 --connect-timeout 15 --max-time 75 \
    "https://raw.githubusercontent.com/$REPOSITORY/$RELEASE/$file" -o "$WORK/portal/$file" || fail "Cannot fetch $file"
  [[ -s "$WORK/portal/$file" ]] || fail "Empty release file: $file"
  case "$file" in *.php) php -l "$WORK/portal/$file" >/dev/null || fail "Invalid PHP $file";; esac
done
for file in "${ROOT_FILES[@]}"; do
  curl -fsSL --retry 3 --connect-timeout 15 --max-time 75 \
    "https://raw.githubusercontent.com/$REPOSITORY/$RELEASE/public-root/$file" -o "$WORK/root/$file" || fail "Cannot fetch public-root/$file"
  [[ -s "$WORK/root/$file" ]] || fail "Empty public-root file: $file"
done
php -l "$WORK/root/index.php" >/dev/null || fail 'Invalid root front-controller PHP'
grep -Fq -- "https://iworksgt.com" "$WORK/portal/app/helpers.php" || fail 'Missing canonical apex URL'
grep -Fq -- 'function iworks_order_intent_capture' "$WORK/portal/app/order_intent.php" || fail 'Missing pending order tracking'
grep -Fq -- 'iworks_post_login_destination' "$WORK/portal/index.php" || fail 'Missing Google sign-in order handoff'
grep -Fq -- 'iw-search-guide' "$WORK/portal/views/public-service-detail.php" || fail 'Missing service landing page guidance'
grep -Fq -- 'iworks_seo_breadcrumb_jsonld' "$WORK/portal/app/public_seo.php" || fail 'Missing SEO breadcrumb contract'
grep -Fq -- 'function trademark_checkout' "$WORK/portal/app/trademark_applications.php" || fail 'Missing trademark application checkout'
grep -Fq -- 'function stripe_vault_start_setup' "$WORK/portal/app/stripe_card_vault.php" || fail 'Missing secure provider-backed card vault'
grep -Fq -- "case 'card_vault':" "$WORK/portal/index.php" || fail 'Card vault route is missing'
grep -Fq -- 'stripe_vault_customer_id' "$WORK/portal/app/domain.php" || fail 'Missing Stripe saved-card checkout integration'
grep -Fq -- 'iw-client-services' "$WORK/portal/views/client-dashboard.php" || fail 'Missing application dashboard'
grep -Fq -- "case 'cac_filing':" "$WORK/portal/index.php" || fail 'CAC prepayment route is missing'
grep -Fq -- 'function cac_filing_checkout' "$WORK/portal/app/cac_filing.php" || fail 'CAC invoice workflow missing'
grep -Fq -- 'iw-cac-layout' "$WORK/portal/views/client-cac-filing.php" || fail 'CAC registration interface missing'
grep -Fq -- 'upload_intake_file' "$WORK/portal/index.php" || fail 'Immediate intake upload route missing'
grep -Fq -- "case 'editorial_showcase':" "$WORK/portal/index.php" || fail 'Editorial content administration missing'
grep -Fq -- "require_roles(['admin', 'author'])" "$WORK/portal/index.php" || fail 'Author role restriction missing'
grep -Fq -- 'data-iw-theme-toggle' "$WORK/portal/views/partials/public-header.php" || fail 'Public dark theme control missing'
grep -Fq -- 'iworks_editorial_reviews(true' "$WORK/portal/views/public-home-clean.php" || fail 'Testimonials publication gate missing'
grep -Fq -- 'iworks_editorial_cases(true' "$WORK/portal/views/public-case-studies-refined.php" || fail 'Public case studies missing'
grep -Fq -- 'CREATE TABLE IF NOT EXISTS iworks_blog_comments' "$WORK/portal/scripts/migrate_editorial_content.php" || fail 'Moderated commentary schema missing'
grep -Fq -- 'iworks_client_service_items' "$WORK/portal/app/client_workspace.php" || fail 'Unified customer service records missing'
grep -Fq -- 'iw-client-detail-grid' "$WORK/portal/views/client-job.php" || fail 'Client order tracking view missing'
grep -Fq -- 'iw-invoice-main-grid' "$WORK/portal/invoice.php" || fail 'Redesigned payment page missing'
grep -Fq -- 'iw-client-profile-grid' "$WORK/portal/views/client-profile.php" || fail 'Client profile missing'
grep -Fq -- '185' "$WORK/portal/app/trademark_applications.php" || fail 'Missing price default'
grep -Fq -- "portal.iworksgt.com/index.php" "$WORK/root/index.php" || fail 'Missing delegated public controller'
grep -Fq -- "RewriteRule ^sitemap" "$WORK/root/.htaccess" || fail 'Missing public sitemap rewrite'
grep -Fq -- "portal.iworksgt.com" "$WORK/portal/index.php" || true
# Download, PHP lint and release checks complete; --check stops before any
# backup, code install, asset link or database migration.
if [[ "$MODE" == '--check' ]]; then
  printf 'RELEASE VALIDATION PASSED. No DNS, .htaccess, website files or database changed.\n'
  exit 0
fi
# Back up files into a private directory outside the document roots.
umask 077
mkdir -p "$BACKUP_DIR/portal" "$BACKUP_DIR/root"
for file in "${FILES[@]}"; do
  if [[ -f "$PORTAL_ROOT/$file" ]]; then
    mkdir -p "$BACKUP_DIR/portal/$(dirname "$file")"
    cp -p "$PORTAL_ROOT/$file" "$BACKUP_DIR/portal/$file"
  else ADDED_PORTAL+=("$file"); fi
done
for file in "${ROOT_FILES[@]}"; do
  if [[ -f "$PUBLIC_ROOT/$file" ]]; then cp -p "$PUBLIC_ROOT/$file" "$BACKUP_DIR/root/$file"; else ADDED_ROOT+=("$file"); fi
done
printf 'Private backups stored in: %s\n' "$BACKUP_DIR"
STARTED=1
for file in "${FILES[@]}"; do
  mkdir -p "$PORTAL_ROOT/$(dirname "$file")"
  install -m 0644 "$WORK/portal/$file" "$PORTAL_ROOT/$file"
done
for file in "${ROOT_FILES[@]}"; do
  install -m 0644 "$WORK/root/$file" "$PUBLIC_ROOT/$file"
done
if [[ ! -L "$PUBLIC_ROOT/assets" || "$(readlink -f "$PUBLIC_ROOT/assets" || true)" != "$PORTAL_ROOT/assets" ]]; then
  if [[ -e "$PUBLIC_ROOT/assets" || -L "$PUBLIC_ROOT/assets" ]]; then
    mv "$PUBLIC_ROOT/assets" "$BACKUP_DIR/root-assets"
    ASSETS_MOVED=1
  fi
  ln -s "$PORTAL_ROOT/assets" "$PUBLIC_ROOT/assets"
  NEW_ASSETS_LINK=1
fi
for file in "${FILES[@]}"; do
  case "$file" in *.php) php -l "$PORTAL_ROOT/$file" >/dev/null;; esac
done
php -l "$PUBLIC_ROOT/index.php" >/dev/null
# Provision the dedicated, isolated draft table before enabling the new route.
# No existing jobs, payments or customer tables are altered by this migration.
php "$PORTAL_ROOT/scripts/migrate_trademark_applications.php" || fail 'Trademark schema migration failed; deployment rolled back'
php "$PORTAL_ROOT/scripts/migrate_stripe_card_vault.php" || fail 'Stripe vault mapping schema migration failed; deployment rolled back'
php "$PORTAL_ROOT/scripts/migrate_cac_filings.php" || fail 'CAC schema migration failed; deployment rolled back'
php "$PORTAL_ROOT/scripts/migrate_editorial_content.php" || fail 'Editorial author/content schema migration failed; deployment files rolled back'
STARTED=0
printf '\nLOCAL INSTALL COMPLETED (backups: %s)\n' "$BACKUP_DIR"
printf 'Important: cPanel domain forwarding or higher-level redirects may still send iworksgt.com to portal.\n'
printf 'Live domain check: '
FINAL="$(curl -kLsS -o /dev/null --max-time 25 -w '%{url_effective}' "https://iworksgt.com/?iworks_split_check=$(date +%s)" || true)"
if [[ "$FINAL" == https://iworksgt.com/* ]]; then
  printf 'apex URL retained: %s\n' "$FINAL"
else
  printf 'ACTION REQUIRED: apex URL not confirmed (result: %s). Remove cPanel Redirects/Domain Forwarding and verify domain document root.\n' "$FINAL"
fi
printf '\nGoogle OAuth configuration report (credentials hidden):\n'
php "$PORTAL_ROOT/scripts/check_google_oauth.php" || printf 'NOTICE: OAuth configuration could not be verified.\n'
printf 'Also test direct root Services, one product, robots.txt and sitemap.xml, and portal login/dashboard on desktop/mobile.\n'
