#!/usr/bin/env bash
# Read-only comparison of every Academy deployment file; never reads app/config.php.
set -Eeuo pipefail
umask 077
BASE=c20d4df85e48ee4e7ce91a9411d7ae16460a923d
RELEASE=f9bba44224b29f41e6215e828d5b3d6f2f810e41
PORTAL="$HOME/public_html/portal.iworksgt.com"
[[ -d "$PORTAL" ]] || { echo 'BLOCK portal directory not found'; exit 2; }
WORK="$(mktemp -d "$HOME/.academy-source-check.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
for ref in "$BASE" "$RELEASE"; do
  mkdir "$WORK/$ref"
  curl -fsSL --retry 2 --max-time 180 "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$ref" -o "$WORK/$ref.tar.gz"
  tar -xzf "$WORK/$ref.tar.gz" -C "$WORK/$ref" --strip-components=1
done
OLD="$WORK/$BASE"; SRC="$WORK/$RELEASE"
echo '=== Complete Academy source synchronization report ==='
for f in .htaccess academy.php app/academy.php app/auth_rate_limits.php app/domain.php assets/css/academy.css index.php scripts/migrate_academy.php scripts/render_academy_pdf.py scripts/run_scheduled_operations.php views/login.php views/partials/header.php; do
  if [[ -L "$PORTAL/$f" ]]; then
    echo "SYMLINK $f"
  elif [[ ! -f "$PORTAL/$f" ]]; then
    if [[ -f "$OLD/$f" ]]; then echo "MISSING $f"; else echo "NEW $f"; fi
  elif cmp -s "$SRC/$f" "$PORTAL/$f"; then
    echo "TARGET $f"
  elif [[ -f "$OLD/$f" ]] && cmp -s "$OLD/$f" "$PORTAL/$f"; then
    echo "BASE $f"
  else
    echo "DRIFT $f"
    sha256sum "$PORTAL/$f" | awk '{print "  live SHA256: "$1}'
  fi
done
echo '=== Portal .htaccess rewrite structure (selected directives only) ==='
if [[ -f "$PORTAL/.htaccess" && ! -L "$PORTAL/.htaccess" ]]; then
  awk 'BEGIN{IGNORECASE=1} /^[[:space:]]*(RewriteEngine|RewriteBase|RewriteRule|RewriteCond|DirectoryIndex|<\/?IfModule|<\/?FilesMatch|Require all|Options[[:space:]]+-Indexes)([[:space:]>]|$)/ { if (tolower($0) ~ /password|passwd|secret|token|api[_-]?key|authorization|cookie|bearer|https?:\/\/[^ ]+@/) print NR ": [directive omitted: potentially sensitive]"; else print NR ": " $0 }' "$PORTAL/.htaccess"
fi
echo 'Read-only report finished. No production files, database or payment settings changed.'
