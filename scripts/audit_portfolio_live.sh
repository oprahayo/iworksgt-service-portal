#!/usr/bin/env bash
# Read-only public HTTP acceptance for the five published iWORKS project pages.
# Never signs in, writes site files, downloads customer information or calls a DB.
# Usage: bash scripts/audit_portfolio_live.sh [https://iworksgt.com]
set -Eeuo pipefail

BASE="${1:-https://iworksgt.com}"
BASE="${BASE%/}"
case "$BASE" in
  https://iworksgt.com|https://www.iworksgt.com) ;;
  *) echo 'BLOCK: use the production iWORKS website only.' >&2; exit 2 ;;
esac
command -v curl >/dev/null
command -v grep >/dev/null
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
pass=0
fail=0
check() {
  local title="$1" url="$2" needle="${3:-}" output="$TMP/response"
  local status
  status="$(curl --http1.1 -sSL --connect-timeout 8 --max-time 32 -o "$output" -w '%{http_code}' "$url")" || status="000"
  if [[ "$status" == "200" ]] && { [[ -z "$needle" ]] || grep -Fq -- "$needle" "$output"; }; then
    echo "PASS $title: HTTP $status"
    pass=$((pass+1))
  else
    echo "FAIL $title: HTTP $status${needle:+ / expected page marker missing}" >&2
    fail=$((fail+1))
  fi
}
check 'Our Work gallery and filters' "$BASE/case-studies" 'data-iw-work-filters'
check 'Separate project and site links' "$BASE/case-studies" 'Visit website'
for slug in \
  amandla-institute-formation-hosting \
  triply-travels-company-formation-travel-website-portals \
  restoration-llc-healthcare-website-design-hosting-management \
  karis-health-services-digital-healthcare-presence \
  cornerstonetl-senior-care-website-design-hosting; do
    check "Case detail /$slug" "$BASE/case-studies/$slug" 'Visit client website'
    check "Genuine screenshot /$slug" "$BASE/assets/branding/case-studies/$slug.webp"
done
check 'Homepage Selected Work' "$BASE/" 'iw-home-case-actions'
echo "Portfolio acceptance: $pass PASS / $fail FAIL"
[[ "$fail" -eq 0 ]] || exit 1
