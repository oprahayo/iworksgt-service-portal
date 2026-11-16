#!/usr/bin/env bash
# One-command iWORKS finance dashboard release for cPanel/iPhone.
# Interactive: first dry-run, then explicit DEPLOY.
set -Eeuo pipefail
for cmd in curl php bash mktemp; do command -v "$cmd" >/dev/null || { echo "Missing $cmd" >&2; exit 2; }; done
API='https://api.github.com/repos/oprahayo/iworksgt-service-portal/commits/main'
SHA="$(curl -fsSL --retry 2 --connect-timeout 15 --max-time 30 "$API" \
  | php -r '$o=json_decode(stream_get_contents(STDIN),true);$s=(string)($o["sha"]??"");if(!preg_match("/^[a-f0-9]{40}$/",$s))exit(2);echo $s;')"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Unable to pin release.' >&2; exit 3; }
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 60 \
 "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$SHA/scripts/deploy_transaction_dashboard.sh" \
 -o "$TMP"
PORTAL="$HOME/public_html/portal.iworksgt.com"
echo "Verifying transaction dashboard release: $SHA"
bash "$TMP" --check "$SHA" "$PORTAL"
if [[ "${1:-}" == --check ]]; then exit 0; fi
[[ -r /dev/tty ]] || { echo 'Interactive terminal required. Release NOT applied.' >&2; exit 4; }
echo 'CHECK PASSED. Confirm a current PRIVATE database backup is available.'
echo 'Type DEPLOY to publish the finance dashboard; anything else cancels.'
read -r answer </dev/tty || exit 4
if [[ "$answer" != DEPLOY ]]; then echo 'Cancelled. No files changed.'; exit 0; fi
bash "$TMP" --apply "$SHA" "$PORTAL"
