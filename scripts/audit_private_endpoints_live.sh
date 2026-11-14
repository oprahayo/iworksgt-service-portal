#!/usr/bin/env bash
# Safe read-only non-secret HTTP security checks. Never collect response bodies.
# Usage: bash scripts/audit_private_endpoints_live.sh
set -Eeuo pipefail
command -v curl >/dev/null
issues=0
checks=0
probe() {
  local title="$1" url="$2" method="$3" response
  if [[ "$method" == HEAD ]]; then
    response="$(curl -sS --http1.1 -I --max-redirs 0 --connect-timeout 8 --max-time 20 -o /dev/null -w '%{http_code}' "$url")" || response="000"
  else
    response="$(curl -sS --http1.1 --max-redirs 0 --connect-timeout 8 --max-time 20 -o /dev/null -w '%{http_code}' "$url")" || response="000"
  fi
  checks=$((checks + 1))
  case "$response" in
    403|404) printf 'PASS %-37s %-4s HTTP %s\n' "$title" "$method" "$response" ;;
    *) printf 'FAIL %-37s %-4s HTTP %s; expected 403/404\n' "$title" "$method" "$response" >&2; issues=$((issues+1)) ;;
  esac
}
for base in https://iworksgt.com https://portal.iworksgt.com; do
  host="${base#https://}"
  for path in setup.php app/config.php app/config.example.php scripts/check_remediation_release.php tests/ .git/config; do
    for method in GET HEAD; do
      probe "$host/$path" "$base/$path" "$method"
    done
  done
done
printf 'Extended private endpoint checks: %d total / %d failures\n' "$checks" "$issues"
[[ "$issues" -eq 0 ]] || exit 1
