#!/usr/bin/env bash
# Read-only public HTTP acceptance for the six published iWORKS project pages.
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
check 'Case studies page and filters' "$BASE/case-studies" 'data-iw-work-filters'
check 'Native case-studies heading' "$BASE/case-studies" '>Case studies</h1>'
check 'Approved iWORKS warm-white style' "$BASE/case-studies" '--iw-cases-paper:#fffaf6'
check 'Separate project and site links' "$BASE/case-studies" 'Visit website'
for slug in \
  amandla-institute-formation-hosting \
  triply-travels-company-formation-travel-website-portals \
  restoration-llc-healthcare-website-design-hosting-management \
  karis-health-services-digital-healthcare-presence \
  cornerstonetl-senior-care-website-design-hosting \
  gansell-website-project; do
    check "Case detail /$slug" "$BASE/case-studies/$slug" 'iw-work-client-website'
    check "Genuine screenshot /$slug" "$BASE/assets/branding/case-studies/$slug.webp"
done
check 'Real Gansell client page' "$BASE/case-studies/gansell-website-project" 'Gansell'
check 'Gansell external website link' "$BASE/case-studies/gansell-website-project" 'https://gansell.com/'
check 'Gansell in case study gallery' "$BASE/case-studies" 'Gansell'
check 'OrangeServer hero brand' "$BASE/" 'OrangeServer'
check 'RillaHost hero brand' "$BASE/" 'RillaHost'
check 'OrangeServer SVG asset' "$BASE/assets/branding/orangeserver-logo.svg" '#00a651'
check 'RillaHost SVG asset' "$BASE/assets/branding/rilahost-logo.svg" '#ffffff'
check 'Hosting-service logo treatment' "$BASE/" 'iw-home-brand-rilahost'
check 'Provider identification' "$BASE/" 'independent service provider'
check 'Homepage graphical carousel' "$BASE/" 'data-iw-showcase-track'
check 'Homepage window previews' "$BASE/" 'iw-home-case-window'
check 'Homepage fade actions' "$BASE/" 'iw-home-case-overlay-actions'
check 'Homepage carousel controls' "$BASE/" 'data-iw-showcase-next'
check 'Homepage matching How It Works background' "$BASE/" 'background:#fff6ee!important'
check 'Homepage drag controller' "$BASE/" 'function initShowcase()'
check 'Case Studies in About navigation' "$BASE/" 'iw-public-about-menu'
check 'Visible mobile image override' "$BASE/" 'display:block!important; /* override legacy mobile'
check 'Top-level Case Studies navigation' "$BASE/" '>Case Studies</a>'
check 'Structured client case overview' "$BASE/case-studies/triply-travels-company-formation-travel-website-portals" 'iw-case-project-facts'
check 'Expandable full project details' "$BASE/case-studies/triply-travels-company-formation-travel-website-portals" 'iw-case-project-notes'
# The rejected work-gallery presentation and headings must not return.
check_absent() {
  local title="$1" url="$2" unwanted="$3" output="$TMP/response"
  local status
  status="$(curl --http1.1 -sSL --connect-timeout 8 --max-time 32 -o "$output" -w '%{http_code}' "$url")" || status="000"
  if [[ "$status" == "200" ]] && ! grep -Fq -- "$unwanted" "$output"; then
    echo "PASS $title: unwanted element absent"
    pass=$((pass+1))
  else
    echo "FAIL $title: old UI still present or HTTP $status" >&2
    fail=$((fail+1))
  fi
}
check_absent 'No redundant Pricing and services menu' "$BASE/" 'Pricing &amp; services'
check_absent 'No old mobile hide-image CSS' "$BASE/assets/css/editorial-brand.css" '.iw-home-case-visual{display:none}'
check_absent 'No rejected Selected Work heading' "$BASE/" 'Real projects. Real progress.'
check_absent 'No dark mock browser frames' "$BASE/case-studies" 'iw-work-browser'
check_absent 'No decorative up-arrow buttons' "$BASE/case-studies" 'bi-arrow-up-right'
echo "Portfolio acceptance: $pass PASS / $fail FAIL"
[[ "$fail" -eq 0 ]] || exit 1
