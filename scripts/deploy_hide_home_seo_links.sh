#!/usr/bin/env bash
# iWORKS surgical homepage correction — remove one visible SEO paragraph.
# Does not update SQL, customer files, branding, payments, chat or metadata.
# Usage: bash deploy_hide_home_seo_links.sh --check|--apply PORTAL_ROOT PUBLIC_ROOT
set -Eeuo pipefail
[[ $# -eq 3 ]] || { echo 'Usage: bash deploy_hide_home_seo_links.sh --check|--apply PORTAL_ROOT PUBLIC_ROOT' >&2; exit 2; }
MODE="$1"
[[ "$MODE" == --check || "$MODE" == --apply ]] || { echo 'Expected --check or --apply' >&2; exit 2; }
for tool in php grep cp mv cmp mkdir mktemp readlink curl chmod; do
  command -v "$tool" >/dev/null || { echo "Required tool unavailable: $tool" >&2; exit 3; }
done
PORTAL="$(readlink -f "$2")"
PUBLIC="$(readlink -f "$3")"
ROOT="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" && "$PUBLIC" == "$ROOT" ]] || {
  echo 'Unexpected iWORKS portal / apex public document roots' >&2; exit 3;
}
case "$PORTAL" in "$ROOT"/*) ;; *) echo 'Portal must be inside public_html' >&2; exit 3;; esac
PAGE="$PORTAL/views/public-home-clean.php"
PREFLIGHT="$PORTAL/scripts/check_remediation_release.php"
[[ -f "$PAGE" && ! -L "$PAGE" && -f "$PORTAL/app/config.php" && -f "$PREFLIGHT" ]] || {
  echo 'Safe, configured public home or release preflight not found' >&2; exit 3;
}
php -l "$PAGE" >/dev/null
[[ -f "$PORTAL/app/public_seo.php" && -f "$PORTAL/app/public_marketing.php" ]] || {
  echo 'SEO metadata and route configuration missing' >&2; exit 3;
}
# Confirm that a change is needed before staging. This uses the installed
# source, not a wholesale template replacement, preserving any later changes.
MATCHES="$(grep -Fc 'class="iw-home-international-links">Building across borders?' "$PAGE" || true)"
if [[ "$MATCHES" == 0 ]]; then
  echo 'ALREADY CLEAN: the SEO marketing sentence is absent from the live home template.'
  echo 'No site files were modified. Verify cached homepage HTML and clear cache if needed.'
  exit 0
fi
[[ "$MATCHES" == 1 ]] || { echo "Unexpected duplicate paragraph count: $MATCHES" >&2; exit 3; }
# Fail closed on mandatory private-file/security/schema/email checks.
php "$PREFLIGHT" "$PORTAL/app/config.php" "$PORTAL" "$PUBLIC" || {
  echo 'Release readiness checks failed. No public files were changed.' >&2; exit 3;
}
if [[ "$MODE" == --check ]]; then
  echo 'CHECK PASSED. Exactly one visible SEO line can be removed; no files changed.'
  exit 0
fi
umask 077
BACKUP="$HOME/iworks-deployment-backups/home-seo-line-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$BACKUP"
chmod 700 "$BACKUP"
cp -p "$PAGE" "$BACKUP/public-home-clean.php"
chmod 600 "$BACKUP/public-home-clean.php"
STAGED="$(mktemp "$PORTAL/views/.seo-line-XXXXXXXX")"
STARTED=0
rollback() {
  status=$?
  if [[ "$STARTED" -eq 1 ]]; then
    echo 'Error: restoring original homepage PHP from private backup' >&2
    cp -p "$BACKUP/public-home-clean.php" "$PAGE" || true
  fi
  rm -f "$STAGED" 2>/dev/null || true
  exit "$status"
}
trap rollback ERR
# Edit one known rendered line only, rather than deploying a whole stale PHP
# view over any customer-specific or later code. No hidden keyword stuffing.
php -r '
  $source=$argv[1];$dest=$argv[2];
  $lines=explode("\n",(string)file_get_contents($source));
  $out=[];$count=0;
  foreach($lines as $line){
    if(str_contains($line,"class=\"iw-home-international-links\"") &&
       str_contains($line,"Building across borders?")){
       $count++;continue;
    }
    $out[]=$line;
  }
  if($count!==1){fwrite(STDERR,"Unexpected visible SEO line count.\n");exit(2);}
  if(file_put_contents($dest,implode("\n",$out))===false)exit(3);
' "$PAGE" "$STAGED"
php -l "$STAGED" >/dev/null
chmod 644 "$STAGED"
STARTED=1
mv -f "$STAGED" "$PAGE"
php -l "$PAGE" >/dev/null
if grep -Fq 'Building across borders?' "$PAGE"; then
  echo 'The unwanted marketing sentence remained in the local view' >&2
  false
fi
# Marketing metadata and separate published routes are not altered.
[[ -s "$PORTAL/app/public_seo.php" && -s "$PORTAL/app/public_marketing.php" ]] || false
status="$(curl -sS --max-time 25 -o "$BACKUP/home-response.html" -w '%{http_code}' 'https://iworksgt.com/' || true)"
if [[ "$status" != 200 ]]; then
  echo "Public homepage returned HTTP $status; restoring prior PHP" >&2
  false
fi
chmod 600 "$BACKUP/home-response.html"
STARTED=0
trap - ERR
echo "SUCCESS: visible SEO link removed from the public home template."
if grep -Fq 'Building across borders?' "$BACKUP/home-response.html"; then
  echo 'WARNING: public response still shows old text. Clear site/CDN cache or verify routing to shared portal PHP.'
else
  echo 'LIVE MARKER PASSED: public homepage response no longer contains the unwanted sentence.'
fi
echo "Private rollback backup: $BACKUP"
echo 'International company registration pages and SEO metadata are preserved.'
