#!/usr/bin/env bash
# iWORKS homepage transparent hero header, with white sticky state on scroll.
# Usage: bash deploy_home_hero_overlay.sh --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT
# CSS-only release; no schema, payment, accounts, upload or application logic changes.
set -Eeuo pipefail
[[ $# -eq 4 ]] || { echo 'Usage: --check|--apply FULL_SHA PORTAL_ROOT APEX_ROOT' >&2; exit 2; }
MODE="$1"; RELEASE="$2"
[[ "$MODE" == --check || "$MODE" == --apply ]] || exit 2
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || { echo 'Full immutable 40-character SHA required.' >&2; exit 2; }
for tool in curl php tar cmp mktemp readlink grep; do
  command -v "$tool" >/dev/null || { echo "Missing required command: $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$3")"; PUBLIC="$(readlink -f "$4")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PUBLIC" == "$ROOT" && "$PORTAL" != "$PUBLIC" ]] || {
  echo 'Unexpected portal/public roots; no change.' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal must be under public_html.' >&2; exit 3 ;; esac
CSS='assets/css/site-launch-polish.css'
[[ -f "$PORTAL/app/config.php" && -f "$PORTAL/index.php" &&
   -f "$PORTAL/views/partials/public-header.php" && -f "$PORTAL/$CSS" ]] || {
  echo 'Expected iWORKS portal files missing; no change.' >&2; exit 3;
}
[[ ! -L "$PORTAL/$CSS" && ! -L "$PUBLIC/$CSS" ]] || {
  echo 'Refusing to replace symlinked CSS.' >&2; exit 3;
}
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
  "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" -o "$WORK/source.tgz"
mkdir "$WORK/source"
tar -xzf "$WORK/source.tgz" -C "$WORK/source" --strip-components=1
SOURCE="$WORK/source"
[[ -s "$SOURCE/$CSS" && -s "$SOURCE/scripts/check_remediation_release.php" ]] || {
  echo 'Incomplete pinned release; no change.' >&2; exit 3;
}
grep -Fq 'iw-hero-overlay-header-v1' "$SOURCE/$CSS" || {
  echo 'Pinned release lacks the requested hero/header patch.' >&2; exit 3;
}
php "$SOURCE/tests/home_transparent_sticky_header_contract_test.php"
php "$SOURCE/scripts/check_remediation_release.php" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Security/configuration preflight failed; no change.' >&2; exit 3;
}
MIRROR=1
if [[ -d "$PUBLIC/assets" && -d "$PORTAL/assets" && "$PUBLIC/assets" -ef "$PORTAL/assets" ]]; then
  MIRROR=0
fi
: > "$WORK/change-portal"; : > "$WORK/change-public"
if ! cmp -s "$SOURCE/$CSS" "$PORTAL/$CSS"; then echo "$CSS" > "$WORK/change-portal"; fi
if [[ "$MIRROR" -eq 1 ]] && { [[ ! -f "$PUBLIC/$CSS" ]] || ! cmp -s "$SOURCE/$CSS" "$PUBLIC/$CSS"; }; then
  echo "$CSS" > "$WORK/change-public"
fi
echo "Portal CSS changes: $(wc -l < "$WORK/change-portal")"
echo "Apex CSS mirror changes: $(wc -l < "$WORK/change-public")"
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED — no production files or database rows changed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/hero-header-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 0700 "$BACKUP"
: > "$BACKUP/apex-added"
if [[ -s "$WORK/change-portal" ]]; then
  tar -C "$PORTAL" -czf "$BACKUP/portal-css.tgz" -T "$WORK/change-portal"
fi
if [[ -s "$WORK/change-public" ]]; then
  if [[ -f "$PUBLIC/$CSS" ]]; then
    tar -C "$PUBLIC" -czf "$BACKUP/apex-css.tgz" -T "$WORK/change-public"
  else
    echo "$CSS" > "$BACKUP/apex-added"
  fi
fi
printf '%s\n' "$RELEASE" > "$BACKUP/release.txt"
chmod 0600 "$BACKUP"/*
STARTED=1
rollback() {
  code=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Release check failed: restoring backed-up CSS.' >&2
    [[ ! -s "$WORK/change-portal" ]] || tar -C "$PORTAL" -xzf "$BACKUP/portal-css.tgz" || true
    [[ ! -f "$BACKUP/apex-css.tgz" ]] || tar -C "$PUBLIC" -xzf "$BACKUP/apex-css.tgz" || true
    while IFS= read -r path; do rm -f "$PUBLIC/$path"; done < "$BACKUP/apex-added"
  fi
  exit "$code"
}
trap rollback ERR
install_css() {
  dest="$1"
  mkdir -p "$(dirname "$dest")"
  tmp="$(mktemp "$(dirname "$dest")/.iwhero-XXXXXXXX")"
  cat "$SOURCE/$CSS" > "$tmp"
  chmod 0644 "$tmp"
  mv -f "$tmp" "$dest"
}
if [[ -s "$WORK/change-portal" ]]; then install_css "$PORTAL/$CSS"; fi
if [[ -s "$WORK/change-public" ]]; then install_css "$PUBLIC/$CSS"; fi
cmp -s "$SOURCE/$CSS" "$PORTAL/$CSS"
if [[ "$MIRROR" -eq 1 ]]; then cmp -s "$SOURCE/$CSS" "$PUBLIC/$CSS"; fi
STATUS="$(curl --http1.1 -sS --max-time 25 -o "$WORK/home.html" -w '%{http_code}' 'https://iworksgt.com/' || true)"
[[ "$STATUS" == 200 ]] || { echo "Homepage HTTP $STATUS; reverting." >&2; false; }
if ! grep -Fq 'iw-hero-overlay-header-v1' "$WORK/home.html"; then
  echo 'NOTICE: the homepage may be cached, or its PHP document root may not be the installed portal.'
  echo 'CSS installed and verified; inspect the live hero manually before marking visual QA complete.'
fi
STARTED=0
trap - ERR
echo "CSS-ONLY RELEASE COMPLETE. Private rollback archive: $BACKUP"
echo 'Check the home at top and after scroll on iPhone + desktop; no database changes.'
