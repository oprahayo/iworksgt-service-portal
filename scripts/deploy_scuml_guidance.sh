#!/usr/bin/env bash
# iWORKS SCUML document-support release; existing cPanel site only.
# Usage: bash scripts/deploy_scuml_guidance.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# No arbitrary migrations or historical invoice/order modifications.
set -Eeuo pipefail
[[ "$#" -eq 4 ]] || { echo 'Usage: --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Choose --check or --apply.' >&2; exit 2; }
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full Git SHA required.' >&2; exit 2; }
for cmd in curl php tar cmp mktemp readlink grep; do
  command -v "$cmd" >/dev/null || { echo "Missing $cmd" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"
PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$ROOT" ]] || {
  echo 'Expected existing iWORKS portal/public roots not found.' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal directory is not in public_html.' >&2; exit 3;; esac
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" && \
   -f "$PORTAL/views/public-service-detail.php" && -d "$PORTAL/assets" ]] || {
  echo 'The existing configured iWORKS portal is unavailable. No changes.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/source.tgz"
mkdir "$WORK/source"
tar -xzf "$WORK/source.tgz" -C "$WORK/source" --strip-components=1
SOURCE="$WORK/source"
cat > "$WORK/manifest" <<'FILES'
index.php
app/customer_service_policy.php
views/public-service-detail.php
views/public-services-refined.php
views/partials/public-header.php
app/public_marketing.php
assets/css/scuml-service.css
assets/branding/services/scuml-document-example.svg
scripts/publish_scuml_guidance.php
FILES
while IFS= read -r path; do
  [[ -s "$SOURCE/$path" && ! -L "$PORTAL/$path" ]] || {
    echo "Missing source or unsafe target: $path" >&2; exit 3;
  }
  case "$path" in *.php) php -l "$SOURCE/$path" >/dev/null;; esac
done < "$WORK/manifest"
php "$SOURCE/tests/scuml_guidance_contract_test.php"
php "$SOURCE/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Mandatory security/schema/mail preflight BLOCKED. No changes.' >&2; exit 3;
}
IWORKS_PORTAL_ROOT="$PORTAL" php "$SOURCE/scripts/publish_scuml_guidance.php" --check
: > "$WORK/changed"; : > "$WORK/existing"; : > "$WORK/added"
while IFS= read -r path; do
  if [[ ! -f "$PORTAL/$path" ]] || ! cmp -s "$SOURCE/$path" "$PORTAL/$path"; then
    echo "$path" >> "$WORK/changed"
    if [[ -f "$PORTAL/$path" ]]; then echo "$path" >> "$WORK/existing"; else echo "$path" >> "$WORK/added"; fi
  fi
done < "$WORK/manifest"
echo "SCUML site files to update: $(wc -l < "$WORK/changed" | tr -d ' ')"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. Neither website nor database has been changed.'
  exit 0
fi
[[ "${IWORKS_PRIVATE_DB_BACKUP_VERIFIED:-}" == YES ]] || {
  echo 'BLOCK: a recent verified private database backup must be confirmed before applying.' >&2; exit 3;
}
umask 077
BACKUP="$HOME/iworks-deployment-backups/scuml-code-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 0700 "$BACKUP"
if [[ -s "$WORK/existing" ]]; then tar -C "$PORTAL" -czf "$BACKUP/portal.tgz" -T "$WORK/existing"; fi
cp "$WORK/added" "$BACKUP/new.txt"
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 0600 "$BACKUP"/*
STARTED=1
rollback() {
  code=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Release failed: restoring previous source files.' >&2
    [[ ! -f "$BACKUP/portal.tgz" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal.tgz" || true
    while IFS= read -r path; do rm -f "$PORTAL/$path"; done < "$BACKUP/new.txt"
    echo 'Any newly published SCUML catalogue records require supervised review; existing customer records are unchanged.' >&2
  fi
  exit "$code"
}
trap rollback ERR
copy_atomic() {
  local relative="$1" dest="$PORTAL/$1" temp
  mkdir -p "$(dirname "$dest")"
  temp="$(mktemp "$(dirname "$dest")/.iw-scuml-XXXXXXXX")"
  cat "$SOURCE/$relative" > "$temp"
  chmod 0644 "$temp"
  mv -f "$temp" "$dest"
}
while IFS= read -r path; do copy_atomic "$path"; done < "$WORK/changed"
while IFS= read -r path; do cmp -s "$SOURCE/$path" "$PORTAL/$path"; done < "$WORK/manifest"
while IFS= read -r path; do
  case "$path" in *.php) php -l "$PORTAL/$path" >/dev/null;; esac
done < "$WORK/manifest"
IWORKS_PORTAL_ROOT="$PORTAL" php "$PORTAL/scripts/publish_scuml_guidance.php" --apply --backup-confirmed

for slug in scuml-document-support; do
  html="$WORK/$slug.html"
  status="$(curl --http1.1 -sS -L --max-time 40 \
    -H 'Cache-Control: no-cache' -o "$html" -w '%{http_code}' \
    "https://iworksgt.com/services/$slug?iw_scuml_release=${RELEASE:0:12}" || true)"
  [[ "$status" == 200 ]] || {
    echo "Live $slug returned HTTP $status." >&2; false;
  }
  grep -Fq 'iw-scuml-service' "$html" || {
    echo "SCUML product details not visible on $slug." >&2; false;
  }
  grep -Fq 'data:image/svg+xml;base64,' "$html" || {
    echo "SCUML example document not embedded for $slug." >&2; false;
  }
  grep -Fq 'iWORKS standard guidance fee' "$html" || {
    echo "Wrong SCUML guidance fee presentation on $slug." >&2; false;
  }
  grep -Fq 'EFCC/SCUML' "$html" || {
    echo "Official free registration disclosure missing for $slug." >&2; false;
  }
  echo "PASS SCUML product: $slug"
done
STARTED=0
trap - ERR
echo "SUCCESS: SCUML guidance services published from $RELEASE."
echo "Private source backup: $BACKUP"
echo 'Customer onboarding uses the existing portal; requirements intake will be tailored when the user supplies the checklist.'
echo 'Old Business Name and Ltd catalogue variants are hidden, not deleted; no historical orders were changed.'
echo 'Never present iWORKS as an EFCC agent.'
