#!/usr/bin/env bash
# Private Academy host diagnostics only. No live source changes, schema migration,
# customer email, Paystack charge or gateway settings changes.
# bash academy_host_check.sh COMMIT_SHA PRIVATE_TEMPLATE_DIR [PORTAL_ROOT]
set -Eeuo pipefail
SHA="${1:-}"
ASSETS="${2:-}"
PORTAL_INPUT="${3:-$HOME/public_html/portal.iworksgt.com}"
[[ "$SHA" =~ ^[0-9a-f]{40}$ && -d "$ASSETS" ]] || { echo "Usage: $0 COMMIT_SHA PRIVATE_TEMPLATE_DIR [PORTAL_ROOT]" >&2; exit 2; }
for cmd in curl tar php sha256sum mktemp readlink fc-match; do command -v "$cmd" >/dev/null || { echo "BLOCK missing $cmd"; exit 2; }; done
PORTAL="$(readlink -f "$PORTAL_INPUT")"
ASSETS="$(readlink -f "$ASSETS")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -f "$PORTAL/app/config.php" && "$PORTAL" == "$HTML/"* ]] || { echo "BLOCK portal configuration missing"; exit 2; }
[[ "$ASSETS" != "$HTML" && "$ASSETS" != "$HTML/"* ]] || { echo "BLOCK keep signed assets outside public_html"; exit 2; }
umask 077
WORK="$(mktemp -d "$HOME/.iworks-academy-check.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
mkdir "$WORK/source"
curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$SHA" -o "$WORK/source.tar.gz"
tar -xzf "$WORK/source.tar.gz" --strip-components=1 -C "$WORK/source"
[[ -f "$WORK/source/scripts/academy_preflight.php" ]] || { echo "BLOCK invalid release archive"; exit 3; }
cp "$PORTAL/app/config.php" "$WORK/source/app/config.php"
chmod 0600 "$WORK/source/app/config.php"
mkdir -p "$WORK/source/storage/academy-templates"
for colour in orange purple green blue; do
  [[ -f "$ASSETS/iworks-$colour.svg" && ! -L "$ASSETS/iworks-$colour.svg" ]] || { echo "BLOCK missing approved $colour SVG"; exit 3; }
  cp "$ASSETS/iworks-$colour.svg" "$WORK/source/storage/academy-templates/"
done
for family in Lato 'Liberation Serif'; do
  actual="$(fc-match --format='%{family}' "$family")"
  [[ "$actual" == "$family"* ]] || { echo "BLOCK required font $family missing"; exit 3; }
done
php -l "$WORK/source/app/academy.php"
php -l "$WORK/source/academy.php"
php "$WORK/source/tests/academy_smoke_test.php"
php "$WORK/source/tests/academy_payment_lifecycle_test.php"
php "$WORK/source/scripts/academy_preflight.php"
echo 'Host checks passed. This is NOT approval to activate live payment or proof of a deployed release.'
