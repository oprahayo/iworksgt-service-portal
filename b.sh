#!/usr/bin/env bash
# iWORKS invoice, SCUML and official social-sharing logo update — no uploads.
set -Eeuo pipefail
for cmd in curl php bash mktemp; do
  command -v "$cmd" >/dev/null || { echo "Missing $cmd" >&2; exit 2; }
done
SHA="$(curl -fsSL --retry 2 --connect-timeout 15 --max-time 30 \
  https://api.github.com/repos/oprahayo/iworksgt-service-portal/commits/main \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true);$s=(string)($j["sha"]??"");if(!preg_match("/^[0-9a-f]{40}$/",$s))exit(2);echo $s;')"
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || {
  echo 'Unable to verify release commit.' >&2; exit 3;
}
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 60 \
  "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$SHA/scripts/deploy_invoice_payment_layout.sh" \
  -o "$TMP"
PORTAL="$HOME/public_html/portal.iworksgt.com"
echo "Checking invoice, SCUML and social preview release at $SHA..."
bash "$TMP" --check "$SHA" "$PORTAL"
if [[ "${1:-}" == --check ]]; then
  echo 'Read-only check completed. No changes applied.'
  exit 0
fi
[[ -r /dev/tty ]] || {
  echo 'No interactive terminal. Release NOT applied.' >&2; exit 4;
}
echo
echo 'This update changes invoice layout, SCUML text and public share image metadata, not payments.'
echo 'Before proceeding, confirm your normal private backup is available.'
echo 'Type DEPLOY to publish the invoice, SCUML and new iWORKS social preview.'
read -r confirmation </dev/tty || exit 4
if [[ "$confirmation" != DEPLOY ]]; then
  echo 'Cancelled. No files changed.'
  exit 0
fi
bash "$TMP" --apply "$SHA" "$PORTAL"
