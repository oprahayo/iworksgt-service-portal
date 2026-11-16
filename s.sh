#!/usr/bin/env bash
# Phone-friendly iWORKS SCUML product publisher. No file upload required.
set -Eeuo pipefail
for tool in curl php bash mktemp; do command -v "$tool" >/dev/null || { echo "Missing $tool" >&2; exit 2; }; done
SHA="$(curl -fsSL --retry 2 --connect-timeout 15 --max-time 30 \
  https://api.github.com/repos/oprahayo/iworksgt-service-portal/commits/main \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true);$s=(string)($j["sha"]??"");if(!preg_match("/^[a-f0-9]{40}$/",$s))exit(2);echo $s;')"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Could not pin a verified GitHub release.' >&2; exit 3; }
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 60 \
  "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$SHA/scripts/deploy_scuml_guidance.sh" \
  -o "$TMP"
PORTAL="$HOME/public_html/portal.iworksgt.com"
PUBLIC="$HOME/public_html"
echo "Checking iWORKS SCUML service release $SHA"
bash "$TMP" --check "$SHA" "$PORTAL" "$PUBLIC"
if [[ "${1:-}" == --check ]]; then exit 0; fi
[[ -r /dev/tty ]] || { echo 'Read-only check complete; interactive terminal required to apply.' >&2; exit 4; }
echo
echo 'Before publication, ensure a separate PRIVATE full database backup is verified.'
echo 'The government registration/certificate are FREE. iWORKS offers independent advice only.'
echo 'Type PUBLISH to activate two iWORKS document-guidance services.'
read -r answer </dev/tty || exit 4
if [[ "$answer" != PUBLISH ]]; then
  echo 'Cancelled. No production files or records changed.'
  exit 0
fi
IWORKS_PRIVATE_DB_BACKUP_VERIFIED=YES bash "$TMP" --apply "$SHA" "$PORTAL" "$PUBLIC"
