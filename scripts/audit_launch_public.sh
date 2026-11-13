#!/usr/bin/env bash
# Read-only launch acceptance for public routes (no login or DB mutation).
# Usage: bash scripts/audit_launch_public.sh [PUBLIC_BASE] [PORTAL_BASE]
set -Eeuo pipefail
PUBLIC="${1:-https://iworksgt.com}"
PORTAL="${2:-https://portal.iworksgt.com}"
PUBLIC="${PUBLIC%/}"
PORTAL="${PORTAL%/}"
command -v curl >/dev/null || { echo 'Missing curl' >&2; exit 2; }
command -v grep >/dev/null || { echo 'Missing grep' >&2; exit 2; }
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
FAIL=0
check_page() {
  local label="$1" url="$2" expected="$3" body="$TMP/$(printf %s "$label" | tr ' /' '__').html"
  local status final
  final="$TMP/out"
  : > "$final"
  if ! status="$(curl --silent --show-error --location --max-redirs 6 --max-time 30 \
      --connect-timeout 10 --retry 1 \
      --output "$body" --write-out '%{http_code}' "$url")"; then
    echo "FAIL $label: request failed ($url)"
    FAIL=1
    return
  fi
  if [[ ! "$status" =~ ^2[0-9][0-9]$ ]]; then
    echo "FAIL $label: HTTP $status ($url)"
    FAIL=1
    return
  fi
  if grep -Eiq 'Call to undefined function|<h1[^>]*>System error|Internal Server Error|Fatal error:|Could not connect to database' "$body"; then
    echo "FAIL $label: possible application error in HTML ($url)"
    FAIL=1
    return
  fi
  if [[ -n "$expected" ]] && ! grep -Fqi "$expected" "$body"; then
    echo "FAIL $label: expected content not found: $expected"
    FAIL=1
    return
  fi
  echo "PASS $label: HTTP $status"
}
echo "iWorks public read-only launch audit"
echo "Public: $PUBLIC  Portal: $PORTAL"
check_page "Homepage" "$PUBLIC/" "Ready to launch and grow your business"
check_page "Services" "$PUBLIC/services" "service"
check_page "Unified CAC registration" "$PUBLIC/services/company-registration" "Business Registration"
check_page "Legacy CAC redirect" "$PUBLIC/services/business-name-registration-cac-nigeria" "Business Registration"
check_page "Insights" "$PUBLIC/blog" "Insights"
check_page "robots.txt" "$PUBLIC/robots.txt" "User-agent"
check_page "sitemap.xml" "$PUBLIC/sitemap.xml" "<urlset"
check_page "Portal sign-in" "$PORTAL/index.php?page=login" "Sign"
echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "FAIL: Public launch checks need repair. Do not declare launch complete." >&2
  exit 1
fi
echo "PASS: Public page checks completed."
echo "Manual checks still required: signed-in client Support ticket thread and reply,"
echo "admin/agent live inbox with a real visitor, service-order cart and invoice,"
echo "mobile nav and appointment request with real guest email."
