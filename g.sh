#!/usr/bin/env bash
# iWORKS Gansell: phone-friendly GitHub-to-cPanel release launcher.
# Run: curl -fsSL https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/main/g.sh | bash
# Checks release first; applies only after a typed interactive DEPLOY confirmation.
set -Eeuo pipefail
for program in curl php bash mktemp; do
  command -v "$program" >/dev/null || { echo "Missing $program" >&2; exit 2; }
done
PORTAL="$HOME/public_html/portal.iworksgt.com"
APEX="$HOME/public_html"
[[ -d "$PORTAL" && -d "$APEX" ]] || {
  echo 'Expected iWORKS portal/public_html folders not found. No changes.' >&2
  exit 3
}
RELEASE_JSON="$(curl -fsSL --retry 2 --connect-timeout 15 --max-time 30 \
  'https://api.github.com/repos/oprahayo/iworksgt-service-portal/commits/main')"
SHA="$(printf '%s' "$RELEASE_JSON" | php -r '
$j=json_decode(stream_get_contents(STDIN),true);
$s=(string)($j["sha"]??"");
if(!preg_match("/^[a-f0-9]{40}$/",$s))exit(2);
echo $s;
')"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || {
  echo 'Cannot pin GitHub release SHA. No changes.' >&2; exit 3;
}
echo "Verified source: oprahayo/iworksgt-service-portal@$SHA"
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 15 --max-time 60 \
  "https://raw.githubusercontent.com/oprahayo/iworksgt-service-portal/$SHA/scripts/deploy_solutions_ui_refresh.sh" \
  -o "$TMP"
echo 'Checking production readiness without changing website files...'
bash "$TMP" --check "$SHA" "$PORTAL" "$APEX"
echo 'Release check passed.'
if [[ "$#" -gt 0 && "$1" == '--check' ]]; then
  echo 'Check only. No changes applied.'
  exit 0
fi
if [[ ! -r /dev/tty ]]; then
  echo 'No interactive terminal available. Check passed; release NOT applied.' >&2
  exit 4
fi
echo
echo 'Before continuing, confirm that a PRIVATE database backup exists.'
echo 'Type DEPLOY to apply this reviewed release, or anything else to cancel.'
read -r reply < /dev/tty || exit 4
if [[ "$reply" != DEPLOY ]]; then
  echo 'Cancelled. No website changes applied.'
  exit 0
fi
bash "$TMP" --apply "$SHA" "$PORTAL" "$APEX"
echo 'Deployment command completed. Verify live Gansell page on mobile.'
