#!/usr/bin/env bash
# Public SEO/legal-only iWORKS release. Does NOT modify CMS page rows, credentials,
# payment provider settings, customer documents, migrations or portal workflows.
# Usage: bash deploy_international_legal_seo.sh --check|--apply FULL_GIT_SHA PORTAL_ROOT APEX_ROOT
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: bash deploy_international_legal_seo.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"
RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Use --check or --apply' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Require a full immutable Git SHA' >&2; exit 2; }
for tool in curl tar php grep cmp mktemp readlink; do
  command -v "$tool" >/dev/null || { echo "Missing required command $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$HTML" ]] || {
  echo 'Unexpected portal or public document root' >&2; exit 3;
}
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Portal is not in the approved public_html installation' >&2; exit 3;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" && -d "$PORTAL/views" &&
   -d "$PUBLIC/assets" && -d "$PORTAL/assets/css" ]] || {
  echo 'Current iWORKS installation could not be verified; no changes' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir -p "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/php" <<'EOF'
index.php
app/public_marketing.php
app/public_seo.php
views/partials/public-header.php
views/partials/public-footer.php
views/public-home-clean.php
views/public-legal-pages.php
views/public-international-formation.php
EOF
cat > "$WORK/css" <<'EOF'
assets/css/public-legal-international.css
EOF
cat "$WORK/php" "$WORK/css" > "$WORK/portal-files"
while IFS= read -r file; do
  [[ -s "$SRC/$file" ]] || { echo "Release file missing or empty: $file" >&2; exit 3; }
  [[ ! -L "$PORTAL/$file" ]] || { echo "Portal symlink target is unsafe: $file" >&2; exit 3; }
done < "$WORK/portal-files"
while IFS= read -r file; do php -l "$SRC/$file" >/dev/null; done < "$WORK/php"
grep -Fq 'register-company-nigeria-from-abroad' "$SRC/index.php" || exit 3
grep -Fq "public-legal-pages.php" "$SRC/index.php" || exit 3
grep -Fq 'Cookie Policy' "$SRC/views/partials/public-footer.php" || exit 3
grep -Fq 'iw-legal-copy' "$SRC/assets/css/public-legal-international.css" || exit 3
grep -Fq 'iworks_seo_canonical' "$SRC/views/partials/public-header.php" || exit 3
php "$SRC/tests/international_legal_seo_contract_test.php" || {
  echo 'Policy and SEO contract tests failed; release aborted' >&2; exit 3;
}
# Existing production protection, schema, backup and email readiness checks
# are authoritative. This command never overrides a failing release gate.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Production readiness gate failed. No production changes performed.' >&2; exit 3;
}
: > "$WORK/portal-changed"
: > "$WORK/portal-existing"
: > "$WORK/portal-added"
: > "$WORK/apex-changed"
: > "$WORK/apex-existing"
: > "$WORK/apex-added"
while IFS= read -r file; do
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then
      echo "$file" >> "$WORK/portal-existing"
    else
      echo "$file" >> "$WORK/portal-added"
    fi
  fi
done < "$WORK/portal-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  while IFS= read -r file; do
    [[ ! -L "$PUBLIC/$file" ]] || { echo "Unsafe apex symlink: $file" >&2; exit 3; }
    if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
      echo "$file" >> "$WORK/apex-changed"
      if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/apex-existing"
      else echo "$file" >> "$WORK/apex-added"; fi
    fi
  done < "$WORK/css"
fi
echo "Portal files to update: $(wc -l < "$WORK/portal-changed")"
echo "Public CSS files to update: $(wc -l < "$WORK/apex-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. No production files were modified.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/legal-seo-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"
fi
if [[ -s "$WORK/apex-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/apex-css.tgz" -T "$WORK/apex-existing"
fi
cp "$WORK/portal-added" "$BACKUP/created-portal.txt"
cp "$WORK/apex-added" "$BACKUP/created-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Error: reverting changed public SEO/legal files from private backup' >&2
    if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true; fi
    if [[ -s "$WORK/apex-existing" ]]; then tar -C "$PUBLIC" -xzf "$BACKUP/apex-css.tgz" || true; fi
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/apex-added"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  local from="$1" to="$2" folder temp
  folder="$(dirname "$to")"
  mkdir -p "$folder"
  temp="$(mktemp "$folder/.legal-seo.XXXXXXXX")"
  cat "$from" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$to"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/apex-changed"
while IFS= read -r file; do
  cmp -s "$SRC/$file" "$PORTAL/$file" || { echo "Deployment mismatch: $file" >&2; false; }
done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/apex-changed"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/php"
for route in privacy terms cookies register-company-nigeria-from-abroad us-llc-formation-from-nigeria; do
  status="$(curl -sS --max-time 25 -o "$WORK/$route.html" -w '%{http_code}' "https://iworksgt.com/$route")" || status=000
  [[ "$status" == 200 ]] || { echo "Smoke check failed: /$route HTTP $status" >&2; false; }
done
if ! grep -Fq 'Cookie Policy' "$WORK/cookies.html" ||
   ! grep -Fq 'Register a company in Nigeria' "$WORK/register-company-nigeria-from-abroad.html"; then
   echo 'Live legal/SEO content markers are missing: check URL rewrites or stale cache.' >&2
   false
fi
STARTED=0
trap - ERR
echo "SUCCESS: public legal documents and international SEO published. Rollback backup: $BACKUP"
echo 'Database, CMS drafts, uploaded documents, orders, payments and user authentication unchanged.'
echo 'Review the new pages on desktop/mobile, then request legal counsel sign-off on operational specifics.'
