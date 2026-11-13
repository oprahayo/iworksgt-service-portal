#!/usr/bin/env bash
# Emergency F01: preserve evidence and deny public HTTP access to private files.
# Run on the cPanel account: bash scripts/contain_private_http_access.sh --check|--apply
# This script intentionally does NOT rotate sessions or touch the database.
set -Eeuo pipefail
MODE="${1:---check}"
PORTAL_INPUT="${2:-$HOME/public_html/portal.iworksgt.com}"
PUBLIC_INPUT="${3:-$HOME/public_html}"
[[ "$MODE" == "--check" || "$MODE" == "--apply" ]] || { echo 'Use --check or --apply' >&2; exit 2; }
for command in readlink mktemp cp mv chmod grep cat; do command -v "$command" >/dev/null || { echo "Missing $command" >&2; exit 1; }; done
PORTAL="$(readlink -f "$PORTAL_INPUT")"
PUBLIC="$(readlink -f "$PUBLIC_INPUT")"
HTML="$(readlink -f "$HOME/public_html")"
[[ -d "$PORTAL" && -d "$PUBLIC" && "$PORTAL" != "$PUBLIC" ]] || { echo 'Invalid split document roots' >&2; exit 1; }
case "$PORTAL" in "$HTML"/*) ;; *) echo 'Portal root is outside public_html' >&2; exit 1 ;; esac
case "$PUBLIC" in "$HTML" | "$HTML"/*) ;; *) echo 'Public root is outside public_html' >&2; exit 1 ;; esac

if [[ "$MODE" == "--check" ]]; then
  for root in "$PORTAL" "$PUBLIC"; do
    if [[ -f "$root/.htaccess" ]] && grep -Fq '# BEGIN IWORKS PRIVATE FILE PROTECTION' "$root/.htaccess"; then
      echo "Protection marker present: $root"
    else
      echo "MISSING protection marker: $root"
    fi
  done
  echo 'CHECK ONLY: no files, tokens or database records changed.'
  exit 0
fi

# Evidence remains OUTSIDE either document root and should be retained for
# incident investigation; never print or upload its contents.
umask 077
EVIDENCE="$HOME/iworks-security-evidence/f01-$(date -u +%Y%m%dT%H%M%SZ)-$$"
mkdir -p "$EVIDENCE"
chmod 0700 "$HOME/iworks-security-evidence" "$EVIDENCE"
printf 'F01 containment at %s UTC\n' "$(date -u +%FT%TZ)" > "$EVIDENCE/incident.txt"

for site in portal public; do
  if [[ "$site" == "portal" ]]; then root="$PORTAL"; else root="$PUBLIC"; fi
  if [[ -f "$root/.htaccess" ]]; then
    cp -p "$root/.htaccess" "$EVIDENCE/$site.htaccess.before"
  fi
  if [[ -f "$root/storage/.htaccess" ]]; then
    cp -p "$root/storage/.htaccess" "$EVIDENCE/$site.storage.htaccess.before"
  fi
  if [[ -f "$root/storage/mail.log" ]]; then
    cp -p "$root/storage/mail.log" "$EVIDENCE/$site.legacy-mail-evidence.log"
    chmod 0600 "$EVIDENCE/$site.legacy-mail-evidence.log"
    printf 'Legacy mail log preserved privately for %s\n' "$site" >> "$EVIDENCE/incident.txt"
  fi
done

# Keep pre-existing rewrite/site rules. A narrow deny block is prepended;
# do not replace site-specific .htaccess or disrupt valid application routing.
for site in portal public; do
  if [[ "$site" == "portal" ]]; then root="$PORTAL"; else root="$PUBLIC"; fi
  if [[ ! -f "$root/.htaccess" ]] || ! grep -Fq '# BEGIN IWORKS PRIVATE FILE PROTECTION' "$root/.htaccess"; then
    temp="$(mktemp "$root/.iworks-private.XXXXXXXX")"
    cat > "$temp" <<'HTACCESS'
# BEGIN IWORKS PRIVATE FILE PROTECTION
<IfModule mod_authz_core.c>
  <FilesMatch "(?i)^(?:database\.sql|(?:\.env(?:\..*)?|composer\.(?:json|lock)|phpunit\.xml|.*\.(?:bak|backup|sql|sqlite|log|lock|ini)))$">
    Require all denied
  </FilesMatch>
</IfModule>
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^(?:storage|app|tests|scripts)(?:/|$) - [F,L,NC]
  RewriteRule ^(?:database\.sql|\.env(?:\..*)?|.*\.(?:sql|sqlite|bak|backup|log|lock|ini))$ - [F,L,NC]
</IfModule>
# END IWORKS PRIVATE FILE PROTECTION

HTACCESS
    if [[ -f "$root/.htaccess" ]]; then cat "$root/.htaccess" >> "$temp"; fi
    chmod 0644 "$temp"
    mv -f "$temp" "$root/.htaccess"
  fi

  mkdir -p "$root/storage"
  temp="$(mktemp "$root/storage/.iworks-storage.XXXXXXXX")"
  cat > "$temp" <<'STORAGE'
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
Options -Indexes
STORAGE
  chmod 0644 "$temp"
  mv -f "$temp" "$root/storage/.htaccess"
done

# After new protections are installed, quarantine historical logs.
# Existing code may recreate a storage/mail.log until the source change
# is deployed; the access rule must still keep it inaccessible.
for site in portal public; do
  if [[ "$site" == "portal" ]]; then root="$PORTAL"; else root="$PUBLIC"; fi
  if [[ -f "$root/storage/mail.log" ]]; then
    mv -f "$root/storage/mail.log" "$EVIDENCE/$site.legacy-mail-quarantined.log"
    chmod 0600 "$EVIDENCE/$site.legacy-mail-quarantined.log"
  fi
done
echo "Containment rules installed for both document roots. Private evidence: $EVIDENCE"

# Verify safe methods without downloading/exposing message contents.
if [[ "${IWORKS_SECURITY_FIXTURE_MODE:-0}" == "1" ]]; then
  echo 'Fixture mode: remote HTTP checks were not attempted.'
  exit 0
fi
command -v curl >/dev/null || { echo 'curl required to verify HTTP denial' >&2; exit 1; }
bad=0
for host in "https://portal.iworksgt.com" "https://iworksgt.com"; do
  for path in "/storage/mail.log" "/storage/setup.lock" "/database.sql"; do
    for method in GET HEAD; do
      status="$(curl -sS --max-time 15 --max-redirs 0 -o /dev/null -w '%{http_code}' -X "$method" "$host$path")" || status=000
      case "$status" in
        401|403|404|410) echo "DENIED $method $host$path ($status)" ;;
        *) echo "FAIL $method $host$path ($status); investigate hosting rules immediately" >&2; bad=1 ;;
      esac
    done
  done
done
[[ "$bad" -eq 0 ]] || exit 1
echo 'F01 HTTP containment verified. Token invalidation and session incident review remain separate required steps.'
