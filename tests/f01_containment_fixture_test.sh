#!/usr/bin/env bash
set -Eeuo pipefail
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/public_html/portal.iworksgt.com/storage" "$TMP/public_html/storage"
printf 'DirectoryIndex index.php\nRewriteEngine On\nRewriteRule ^x$ index.php [L]\n' > "$TMP/public_html/.htaccess"
printf 'DirectoryIndex index.php\n' > "$TMP/public_html/portal.iworksgt.com/.htaccess"
printf 'synthetic fixture only\n' > "$TMP/public_html/portal.iworksgt.com/storage/mail.log"
printf 'synthetic fixture only\n' > "$TMP/public_html/storage/mail.log"
HOME="$TMP" IWORKS_SECURITY_FIXTURE_MODE=1 bash "$REPO_ROOT/scripts/contain_private_http_access.sh" --check \
  "$TMP/public_html/portal.iworksgt.com" "$TMP/public_html" >/dev/null
HOME="$TMP" IWORKS_SECURITY_FIXTURE_MODE=1 bash "$REPO_ROOT/scripts/contain_private_http_access.sh" --apply \
  "$TMP/public_html/portal.iworksgt.com" "$TMP/public_html" >/dev/null
for root in "$TMP/public_html" "$TMP/public_html/portal.iworksgt.com"; do
  grep -Fq 'BEGIN IWORKS PRIVATE FILE PROTECTION' "$root/.htaccess"
  grep -Fq 'Require all denied' "$root/storage/.htaccess"
  grep -Fq 'RewriteRule ^(?:storage|app|tests|scripts)' "$root/.htaccess"
  [[ ! -e "$root/storage/mail.log" ]]
done
grep -Fq 'RewriteRule ^x$ index.php' "$TMP/public_html/.htaccess"
[[ "$(find "$TMP/iworks-security-evidence" -type f -name '*legacy-mail-quarantined.log' | wc -l)" -eq 2 ]]
# Idempotency: a subsequent application cannot duplicate protection blocks.
HOME="$TMP" IWORKS_SECURITY_FIXTURE_MODE=1 bash "$REPO_ROOT/scripts/contain_private_http_access.sh" --apply \
  "$TMP/public_html/portal.iworksgt.com" "$TMP/public_html" >/dev/null
[[ "$(grep -c 'BEGIN IWORKS PRIVATE FILE PROTECTION' "$TMP/public_html/.htaccess")" -eq 1 ]]
[[ "$(grep -c 'BEGIN IWORKS PRIVATE FILE PROTECTION' "$TMP/public_html/portal.iworksgt.com/.htaccess")" -eq 1 ]]
# Evidence base remains private.
[[ "$(stat -c %a "$TMP/iworks-security-evidence")" == "700" ]]
# HTTP/2 servers can produce PROTOCOL_ERROR for synthetic -X HEAD.
# The verifier must use curl's true no-body HEAD with HTTP/1.1.
grep -Fq 'curl --http1.1 --head -sS' "$REPO_ROOT/scripts/contain_private_http_access.sh"
! grep -Fq -- '-X "$method"' "$REPO_ROOT/scripts/contain_private_http_access.sh"
echo 'f01_containment_fixture_test passed'
