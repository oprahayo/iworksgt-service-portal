#!/usr/bin/env bash
# Read-only public SEO audit of every published service URL in iWorks catalog.
set -uo pipefail
BASE="${1:-https://iworksgt.com}"
BASE="${BASE%/}"
[[ "$BASE" == 'https://iworksgt.com' ]] || { echo 'Expected https://iworksgt.com'; exit 2; }
command -v curl >/dev/null || { echo 'curl required'; exit 2; }
paths=(
/ /services
/services/business-name-registration-cac-nigeria
/services/company-registration
/services/trademark
/services/website-design-development
/services/domain-web-hosting
/services/graphics-design
/services/seo-smm-digital-marketing
/services/email-list-smtp
/services/form-your-u-s-llc-in-minutes
/services/incorporated-trustees
/services/launch-a-company-package
/services/pro-training
/services/social-enterprise-ltd-by-guarantee
/services/vehicles-assets-tracking
/about /contact /blog /faq /sitemap.xml /robots.txt
)
errors=0
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
printf 'Public site SEO audit: %s\n' "$BASE"
for path in "${paths[@]}"; do
  info="$(curl --silent --show-error --location --max-redirs 6 \
    --connect-timeout 8 --max-time 35 -D "$tmp/headers" \
    -o "$tmp/body" -w '%{http_code}|%{url_effective}|%{num_redirects}' \
    "$BASE$path" 2>"$tmp/error")"
  transport=$?
  status="${info%%|*}"
  part="${info#*|}"
  effective="${part%%|*}"
  redirects="${part##*|}"
  printf '%-53s status=%s redirects=%s\n' "$path" "$status" "$redirects"
  if [[ "$transport" -ne 0 || "$status" != 200 || "$effective" != "$BASE"* ]]; then
    printf '  FAIL: ended at %s; %s\n' "$effective" "$(cat "$tmp/error")"
    errors=$((errors + 1))
    continue
  fi
  if [[ "$path" == '/sitemap.xml' ]]; then
    if ! grep -Fq '<urlset' "$tmp/body" || ! grep -Fq '<loc>https://iworksgt.com/' "$tmp/body"; then
      echo '  FAIL: sitemap is missing the expected public domain URLs'
      errors=$((errors + 1))
    fi
    if grep -Fq '<loc>https://portal.iworksgt.com/' "$tmp/body"; then
      echo '  FAIL: sitemap contains portal-domain public URLs'
      errors=$((errors + 1))
    fi
    continue
  fi
  if [[ "$path" == '/robots.txt' ]]; then
    if ! grep -Fq 'Sitemap: https://iworksgt.com/sitemap.xml' "$tmp/body"; then
      echo '  FAIL: robots file does not advertise apex sitemap'
      errors=$((errors + 1))
    fi
    continue
  fi
  if ! grep -Fq 'rel="canonical"' "$tmp/body"; then
    echo '  FAIL: missing canonical tag'
    errors=$((errors + 1))
  fi
  if ! grep -Fq 'name="viewport"' "$tmp/body"; then
    echo '  FAIL: missing mobile viewport meta'
    errors=$((errors + 1))
  fi
  if ! grep -Eq '<title>[^<]+' "$tmp/body"; then
    echo '  FAIL: missing populated HTML title'
    errors=$((errors + 1))
  fi
  if ! grep -Fq '<h1' "$tmp/body"; then
    echo '  FAIL: missing page H1'
    errors=$((errors + 1))
  fi
done
printf 'Failed checks: %d\n' "$errors"
if [[ "$errors" -ne 0 ]]; then
  echo 'Fix domain redirects and HTTP failures before requesting indexing.'
  exit 1
fi
echo 'Public URL and core on-page checks passed. Check Search Console and device performance separately.'
