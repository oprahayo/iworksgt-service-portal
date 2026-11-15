#!/usr/bin/env bash
# iWORKS visual fixes + Gansell / Our Solutions release (existing site only).
# Usage: bash deploy_solutions_ui_refresh.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# Scoped source/assets publisher. NO database writes, migrations or private file changes.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Choose --check or --apply.' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full immutable Git SHA required.' >&2; exit 2; }
for cmd in curl php tar cmp mktemp readlink grep; do
  command -v "$cmd" >/dev/null || { echo "Missing required command: $cmd" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PUBLIC" == "$ROOT" && "$PORTAL" != "$PUBLIC" ]] || {
  echo 'Unexpected portal/public document root; no site change.' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal root is not within public_html.' >&2; exit 3 ;; esac
[[ -f "$PORTAL/index.php" && -f "$PORTAL/app/config.php" &&
   -f "$PORTAL/views/partials/public-header.php" && -d "$PORTAL/assets" &&
   -d "$PUBLIC/assets" ]] || {
  echo 'Expected existing iWORKS installation not found; no site change.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/release.tgz"
mkdir "$WORK/source"
tar -xzf "$WORK/release.tgz" -C "$WORK/source" --strip-components=1
SOURCE="$WORK/source"

# Explicitly scoped manifest: do not overwrite config, uploaded content,
# storage, .htaccess, CMS drafts, payments, or account data.
cat > "$WORK/php-files" <<'PHP'
index.php
app/helpers.php
app/public_marketing.php
app/public_seo.php
views/partials/public-header.php
views/public-home-clean.php
views/public-solutions.php
PHP
cat > "$WORK/asset-files" <<'ASSETS'
assets/css/public-clean.css
assets/css/portfolio-work.css
assets/css/site-launch-polish.css
assets/css/solutions.css
assets/branding/case-studies/gansell-website-project.webp
ASSETS
cat "$WORK/php-files" "$WORK/asset-files" > "$WORK/portal-files"

while IFS= read -r path; do
  [[ -s "$SOURCE/$path" ]] || { echo "Missing immutable source: $path" >&2; exit 3; }
  [[ ! -L "$PORTAL/$path" ]] || { echo "Refusing portal symlink: $path" >&2; exit 3; }
done < "$WORK/portal-files"
while IFS= read -r path; do
  [[ ! -L "$PUBLIC/$path" ]] || { echo "Refusing public asset symlink: $path" >&2; exit 3; }
done < "$WORK/asset-files"

grep -Fq 'iw-hero-overlay-header-v2' "$SOURCE/assets/css/site-launch-polish.css" || {
  echo 'Wrong source: corrected transparent header marker not found.' >&2; exit 3;
}
grep -Fq 'id="iw-solutions"' "$SOURCE/views/public-solutions.php" || {
  echo 'Wrong source: Our Solutions page missing.' >&2; exit 3;
}
while IFS= read -r path; do php -l "$SOURCE/$path" >/dev/null; done < "$WORK/php-files"
php "$SOURCE/tests/solutions_visual_refinement_contract_test.php"
php "$SOURCE/tests/gansell_hero_partner_contract_test.php"
php "$SOURCE/tests/home_case_showcase_contract_test.php"
php "$SOURCE/tests/home_transparent_sticky_header_contract_test.php"

# Mandatory production security, schema and mail readiness still apply.
php "$SOURCE/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'MANDATORY RELEASE PREFLIGHT BLOCKED. No site files modified.' >&2; exit 3;
}

MIRROR=1
if [[ "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then MIRROR=0; fi
: > "$WORK/portal-existing"
: > "$WORK/portal-added"
: > "$WORK/portal-changed"
: > "$WORK/apex-existing"
: > "$WORK/apex-added"
: > "$WORK/apex-changed"
while IFS= read -r path; do
  if [[ ! -f "$PORTAL/$path" ]] || ! cmp -s "$SOURCE/$path" "$PORTAL/$path"; then
    echo "$path" >> "$WORK/portal-changed"
    if [[ -f "$PORTAL/$path" ]]; then
      echo "$path" >> "$WORK/portal-existing"
    else
      echo "$path" >> "$WORK/portal-added"
    fi
  fi
done < "$WORK/portal-files"
if [[ "$MIRROR" -eq 1 ]]; then
  while IFS= read -r path; do
    if [[ ! -f "$PUBLIC/$path" ]] || ! cmp -s "$SOURCE/$path" "$PUBLIC/$path"; then
      echo "$path" >> "$WORK/apex-changed"
      if [[ -f "$PUBLIC/$path" ]]; then
        echo "$path" >> "$WORK/apex-existing"
      else
        echo "$path" >> "$WORK/apex-added"
      fi
    fi
  done < "$WORK/asset-files"
fi
echo "Portal files to change: $(wc -l < "$WORK/portal-changed")"
echo "Public assets to mirror: $(wc -l < "$WORK/apex-changed")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. No production files, records or accounts modified.'
  exit 0
fi

# Backup outside the HTTP document root with a rollback on any post-write error.
umask 077
BACKUP="$HOME/iworks-deployment-backups/solutions-ui-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 700 "$BACKUP"
if [[ -s "$WORK/portal-existing" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/portal-existing"
fi
if [[ -s "$WORK/apex-existing" ]]; then
  tar -C "$PUBLIC" -czf "$BACKUP/apex.tgz" -T "$WORK/apex-existing"
fi
cp "$WORK/portal-added" "$BACKUP/new-portal.txt"
cp "$WORK/apex-added" "$BACKUP/new-apex.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 600 "$BACKUP"/*
STARTED=1
rollback() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Release failed after copying. Restoring existing source and assets.' >&2
    [[ ! -f "$BACKUP/portal.tgz" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
    [[ ! -f "$BACKUP/apex.tgz" ]] || tar -C "$PUBLIC" -xzf "$BACKUP/apex.tgz" || true
    while IFS= read -r path; do rm -f "$PORTAL/$path"; done < "$BACKUP/new-portal.txt"
    while IFS= read -r path; do rm -f "$PUBLIC/$path"; done < "$BACKUP/new-apex.txt"
  fi
  exit "$status"
}
trap rollback ERR
copy_atomic() {
  local path="$1" dest="$2" temp
  mkdir -p "$(dirname "$dest")"
  temp="$(mktemp "$(dirname "$dest")/.iwsol-XXXXXXXX")"
  cat "$SOURCE/$path" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$dest"
}
while IFS= read -r path; do copy_atomic "$path" "$PORTAL/$path"; done < "$WORK/portal-changed"
while IFS= read -r path; do copy_atomic "$path" "$PUBLIC/$path"; done < "$WORK/apex-changed"
while IFS= read -r path; do cmp -s "$SOURCE/$path" "$PORTAL/$path"; done < "$WORK/portal-files"
while IFS= read -r path; do cmp -s "$SOURCE/$path" "$PUBLIC/$path"; done < "$WORK/apex-changed"
while IFS= read -r path; do php -l "$PORTAL/$path" >/dev/null; done < "$WORK/php-files"
for url in 'https://iworksgt.com/' \
           'https://iworksgt.com/solutions' \
           'https://iworksgt.com/solutions/gansell'; do
  filename="$WORK/response-$(printf '%s' "$url" | sha256sum | cut -c1-10).html"
  code="$(curl --http1.1 -sS --max-time 25 -o "$filename" -w '%{http_code}' "$url" || true)"
  [[ "$code" == 200 ]] || { echo "HTTP $code on $url; rolling back." >&2; false; }
  if [[ "$url" == *'/solutions'* ]]; then
    grep -Fq 'id="iw-solutions"' "$filename" || {
      echo "Missing live Solutions marker on $url (cache or wrong web root); rolling back." >&2; false;
    }
  fi
  echo "PASS HTTP 200: $url"
done
STARTED=0
trap - ERR
echo "SUCCESS: Solutions + visual corrections published from $RELEASE"
echo "Private source rollback files: $BACKUP"
echo 'No database tables, customers, payments or private configuration changed.'
echo 'Acceptance still requires real mobile/desktop browser checks and Gansell link verification.'
