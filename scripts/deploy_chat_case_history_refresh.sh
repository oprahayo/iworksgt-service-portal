#!/usr/bin/env bash
# One narrowly scoped iWORKS support/chat + home case-study release.
# Usage: bash deploy_chat_case_history_refresh.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# No SQL, account, payment or customer document changes. Existing schema required.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; SHA="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full immutable SHA required' >&2; exit 2; }
for tool in curl php tar cmp mktemp readlink grep; do
  command -v "$tool" >/dev/null || { echo "Missing required command: $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$ROOT" ]] || {
  echo 'Unknown portal/public document roots; no change' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal path must be under public_html' >&2; exit 3 ;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" &&
   -f "$PORTAL/views/public-home-clean.php" && -d "$PORTAL/assets" && -d "$PUBLIC/assets" ]] || {
  echo 'Expected iWORKS installation is absent; no change' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$SHA" -o "$WORK/release.tgz"
mkdir "$WORK/src"
tar -xzf "$WORK/release.tgz" -C "$WORK/src" --strip-components=1
SRC="$WORK/src"
cat > "$WORK/php" <<'EOF'
app/live_chat.php
app/launch_case_study_catalog.php
views/partials/public-assistance.php
views/partials/public-header.php
views/partials/public-footer.php
views/live-inbox.php
views/public-home-clean.php
views/public-legal-pages.php
scripts/cron_support_chat.php
scripts/publish_all_launch_case_studies.php
EOF
cat > "$WORK/assets" <<'EOF'
assets/js/public-assistance.js
assets/js/live-inbox.js
assets/js/portfolio-work.js
assets/css/public-assistance.css
assets/css/portfolio-work.css
assets/branding/case-studies/amandla-institute-formation-hosting.webp
assets/branding/case-studies/triply-travels-company-formation-travel-website-portals.webp
assets/branding/case-studies/restoration-llc-healthcare-website-design-hosting-management.webp
assets/branding/case-studies/karis-health-services-digital-healthcare-presence.webp
assets/branding/case-studies/cornerstonetl-senior-care-website-design-hosting.webp
EOF
cat "$WORK/php" "$WORK/assets" > "$WORK/portal-files"
: > "$WORK/public-files"
if ! [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then cat "$WORK/assets" > "$WORK/public-files"; fi
while IFS= read -r file; do
  [[ -s "$SRC/$file" ]] || { echo "Missing release file: $file" >&2; exit 3; }
  [[ ! -L "$PORTAL/$file" ]] || { echo "Portal symlink not overwritten: $file" >&2; exit 3; }
done < "$WORK/portal-files"
while IFS= read -r file; do
  [[ ! -L "$PUBLIC/$file" ]] || { echo "Public symlink not overwritten: $file" >&2; exit 3; }
done < "$WORK/public-files"
while IFS= read -r file; do php -l "$SRC/$file" >/dev/null; done < "$WORK/php"
php "$SRC/tests/streamlined_live_chat_history_contract_test.php"
php "$SRC/tests/public_assistance_contract_test.php"
php "$SRC/tests/home_case_showcase_contract_test.php"
php "$SRC/tests/public_clean_home_test.php"
# Previously approved security and email readiness cannot be bypassed.
php "$SRC/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Required production readiness checks failed. Nothing changed.' >&2; exit 3;
}
[[ -f "$PORTAL/scripts/migrate_live_chat_engagement.php" ]] || {
  echo 'Chat schema check script missing. Nothing changed.' >&2; exit 3;
}
php "$PORTAL/scripts/migrate_live_chat_engagement.php" --check || {
  echo 'Chat continuation/outbox schema must be ready before rollout.' >&2; exit 3;
}
for target in portal public; do
  : > "$WORK/$target-existing"
  : > "$WORK/$target-added"
  : > "$WORK/$target-changed"
done
while IFS= read -r file; do
  if [[ ! -f "$PORTAL/$file" ]] || ! cmp -s "$SRC/$file" "$PORTAL/$file"; then
    echo "$file" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$file" ]]; then echo "$file" >> "$WORK/portal-existing"; else echo "$file" >> "$WORK/portal-added"; fi
  fi
done < "$WORK/portal-files"
while IFS= read -r file; do
  if [[ ! -f "$PUBLIC/$file" ]] || ! cmp -s "$SRC/$file" "$PUBLIC/$file"; then
    echo "$file" >> "$WORK/public-changed"
    if [[ -f "$PUBLIC/$file" ]]; then echo "$file" >> "$WORK/public-existing"; else echo "$file" >> "$WORK/public-added"; fi
  fi
done < "$WORK/public-files"
echo "Portal files to change: $(wc -l < "$WORK/portal-changed")"
echo "Public assets to change: $(wc -l < "$WORK/public-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. No site files, database records or CMS media changed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/chat-history-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"; chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"; fi
if [[ -s "$WORK/public-existing" ]]; then tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/public-existing"; fi
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/public-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$SHA" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  code=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Rolling back changed source/assets from private backup' >&2
    [[ ! -s "$WORK/portal-existing" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
    [[ ! -s "$WORK/public-existing" ]] || tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true
    while IFS= read -r file; do rm -f "$PORTAL/$file"; done < "$WORK/portal-added"
    while IFS= read -r file; do rm -f "$PUBLIC/$file"; done < "$WORK/public-added"
  fi
  exit "$code"
}
trap rollback ERR
copy_atomic() {
  local source_file="$1" dest_file="$2" folder tmp
  folder="$(dirname "$dest_file")"
  mkdir -p "$folder"
  tmp="$(mktemp "$folder/.chatrelease-XXXXXXXX")"
  cat "$source_file" > "$tmp"
  chmod 0644 "$tmp"
  mv -f "$tmp" "$dest_file"
}
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PORTAL/$file"; done < "$WORK/portal-changed"
while IFS= read -r file; do copy_atomic "$SRC/$file" "$PUBLIC/$file"; done < "$WORK/public-changed"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PORTAL/$file" || false; done < "$WORK/portal-files"
while IFS= read -r file; do cmp -s "$SRC/$file" "$PUBLIC/$file" || false; done < "$WORK/public-files"
while IFS= read -r file; do php -l "$PORTAL/$file" >/dev/null; done < "$WORK/php"
status="$(curl -sS --max-time 25 -o "$WORK/home.html" -w '%{http_code}' 'https://iworksgt.com/' || true)"
[[ "$status" == 200 ]] || { echo "Homepage HTTP $status, reverting source" >&2; false; }
grep -Fq 'data-assist-country-label' "$WORK/home.html" || {
  echo 'New chat form missing from homepage; check cached / alternate public root' >&2; false;
}
if grep -Fq 'Looking for something else?' "$WORK/home.html"; then
  echo 'Duplicate service catalogue link still present; reverting' >&2; false;
fi
for photo in $(sed -n 's#^assets/branding/case-studies/##p' "$WORK/assets"); do
  status="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "https://iworksgt.com/assets/branding/case-studies/$photo" || true)"
  [[ "$status" == 200 ]] || { echo "Case-study image $photo HTTP $status" >&2; false; }
done
STARTED=0
trap - ERR
echo "SUCCESS: simplified support chat and case-study assets released. Backup: $BACKUP"
echo 'IMPORTANT: configure the private five-minute cron_support_chat.php worker to send emails and enforce retention.'
echo 'Case studies require published CMS records; use publish_all_launch_case_studies.php --check before a backup-gated publication.'
echo 'No customer DB records, orders, prices or uploaded images changed by this script.'
