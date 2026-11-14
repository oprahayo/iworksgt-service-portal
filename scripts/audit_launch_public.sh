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
  local label="$1" url="$2" expected="$3"
  local body="$TMP/$(printf %s "$label" | tr ' /' '__').html"
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

# Check publicly visible content without logging the page body.
check_absent() {
  local label="$1" file="$2" needle="$3"
  if [[ ! -f "$file" ]]; then
    echo "FAIL $label: no response captured" >&2
    FAIL=1
  elif grep -Fqi "$needle" "$file"; then
    echo "FAIL $label: outdated or misleading content is still published" >&2
    FAIL=1
  else
    echo "PASS $label: unsupported claim absent"
  fi
}
# GET and HEAD are checked independently; discard contents without saving.
check_private() {
  local label="$1" target="$2" method code
  for method in GET HEAD; do
    if [[ "$method" == HEAD ]]; then
      if ! code="$(curl -sS --http1.1 -I --max-redirs 0 --max-time 20 \
        --connect-timeout 8 --output /dev/null --write-out '%{http_code}' "$target")"; then
        echo "FAIL $label $method: transport error" >&2; FAIL=1; continue
      fi
    else
      if ! code="$(curl -sS --http1.1 --max-redirs 0 --max-time 20 \
        --connect-timeout 8 --output /dev/null --write-out '%{http_code}' "$target")"; then
        echo "FAIL $label $method: transport error" >&2; FAIL=1; continue
      fi
    fi
    case "$code" in
      403|404) echo "PASS $label $method: HTTP $code (denied)" ;;
      *) echo "FAIL $label $method: HTTP $code, expected 403 or 404" >&2; FAIL=1 ;;
    esac
  done
}
check_svg() {
  local label="$1" target="$2" file="$TMP/svg.txt" code
  if ! code="$(curl -fsS --location --max-redirs 5 --max-time 25 \
      --output "$file" --write-out '%{http_code}' "$target")"; then
    echo "FAIL $label: asset unavailable" >&2; FAIL=1; return
  fi
  if [[ "$code" == 200 ]] && grep -Fqi '<svg' "$file"; then
    echo "PASS $label: valid SVG"
  else
    echo "FAIL $label: missing or invalid SVG" >&2; FAIL=1
  fi
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
check_page "Portal registration" "$PORTAL/index.php?page=register" "Select your country of residence"
check_page "Contact" "$PUBLIC/contact" "Send an enquiry"
check_page "Tracking" "$PUBLIC/track" "Track"
check_page "About" "$PUBLIC/about" "About"
check_page "FAQ" "$PUBLIC/faq" "questions"
check_page "Privacy" "$PUBLIC/privacy" "International customers and transfers"
check_page "Terms" "$PUBLIC/terms" "Refunds and cancellation"
check_page "Cookies" "$PUBLIC/cookies" "Cookie Policy"
check_page "Overseas CAC guide" "$PUBLIC/register-company-nigeria-from-abroad" "United Kingdom"
check_page "US LLC guide" "$PUBLIC/us-llc-formation-from-nigeria" "registered agent"
check_page "Visitor consent controls" "$PUBLIC/" "Notifications and privacy settings"
check_absent "No unverified Google rating" "$TMP/Homepage.html" "iw-reviews-google-score"
check_absent "No regulator in client ribbon" "$TMP/Homepage.html" 'alt="Corporate Affairs Commission"'
check_absent "No vendor in client ribbon" "$TMP/Homepage.html" 'alt="GoDaddy"'
check_svg "Public branded logo" "$PUBLIC/assets/branding/iworks-desktop.svg"
check_svg "Customer portal logo" "$PORTAL/assets/branding/iworks-compact.svg"
for base in "$PUBLIC" "$PORTAL"; do
  check_private "Private SQL on $base" "$base/database.sql"
  check_private "Mail log on $base" "$base/storage/mail.log"
  check_private "Setup lock on $base" "$base/storage/setup.lock"
done
echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "FAIL: Public launch checks need repair. Do not declare launch complete." >&2
  exit 1
fi
echo "PASS: Public route, trust and private-file smoke checks completed."
echo "This does not prove live payment gateway callbacks, inbox email delivery,"
echo "private database backup/restore, OTP, customer access or real mobile UX."
echo "Manual checks still required: signed-in client Support ticket thread and reply,"
echo "admin/agent live inbox with a real visitor, service-order cart and invoice,"
echo "mobile nav and appointment request with real guest email."
