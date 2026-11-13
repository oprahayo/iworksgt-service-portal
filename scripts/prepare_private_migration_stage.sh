#!/usr/bin/env bash
# Stage an immutable migration release privately, without touching live source.
# bash scripts/prepare_private_migration_stage.sh --check|--prepare PORTAL_ROOT COMMIT_SHA
set -Eeuo pipefail
MODE="${1:---check}"
PORTAL_INPUT="${2:-$HOME/public_html/portal.iworksgt.com}"
RELEASE="${3:-}"
[[ "$MODE" == "--check" || "$MODE" == "--prepare" ]] || {
  echo 'Use --check or --prepare' >&2
  exit 2
}
[[ "$RELEASE" =~ ^[a-f0-9]{40}$ ]] || {
  echo 'An immutable 40-character release SHA is required.' >&2
  exit 2
}
for utility in readlink mktemp tar cp chmod; do
  command -v "$utility" >/dev/null || {
    echo "Missing utility: $utility" >&2; exit 1
  }
done
PORTAL="$(readlink -f "$PORTAL_INPUT")"
HTML="$(readlink -f "$HOME/public_html")"
case "$PORTAL" in
  "$HTML"/*) ;;
  *) echo 'Portal path must be under the configured public_html.' >&2; exit 2 ;;
esac
[[ -f "$PORTAL/app/config.php" ]] || {
  echo 'Existing private portal configuration was not found.' >&2; exit 2
}
ROOT="$HOME/iworks-private-migration-stages"
STAGE="$ROOT/$RELEASE"
if [[ "$MODE" == "--check" ]]; then
  if [[ -f "$STAGE/.release-sha" && -f "$STAGE/app/config.php"
        && -f "$STAGE/scripts/migrate_legacy_schema.php" ]]; then
    echo "Private migration stage ready: $STAGE"
  else
    echo "Private migration stage not prepared for $RELEASE."
  fi
  echo 'CHECK ONLY: no site files or database records changed.'
  exit 0
fi
[[ ! -e "$STAGE" ]] || {
  echo 'Stage already exists. Inspect it privately; refusing to overwrite.' >&2
  exit 1
}
OLD_UMASK="$(umask)"
umask 077
mkdir -p "$ROOT"
chmod 0700 "$ROOT"
TMP="$(mktemp -d "$ROOT/.staging.XXXXXXXX")"
trap 'rm -rf "$TMP"; umask "$OLD_UMASK"' EXIT
mkdir -p "$TMP/app"
if [[ -n "${IWORKS_RELEASE_ARCHIVE:-}" ]]; then
  [[ -s "$IWORKS_RELEASE_ARCHIVE" ]] || exit 1
  cp "$IWORKS_RELEASE_ARCHIVE" "$TMP/release.tar.gz"
else
  command -v curl >/dev/null || { echo 'curl unavailable' >&2; exit 1; }
  curl -fsSL --retry 2 --connect-timeout 15 --max-time 150 \
    "https://codeload.github.com/oprahayo/iworksgt-service-portal/tar.gz/$RELEASE" \
    -o "$TMP/release.tar.gz"
fi
mkdir "$TMP/source"
tar -xzf "$TMP/release.tar.gz" -C "$TMP/source" --strip-components=1
for file in app/bootstrap.php scripts/migrate_legacy_schema.php \
            scripts/migrate_auth_attempt_limits.php \
            scripts/migrate_gateway_uniqueness.php \
            scripts/migrate_notification_email_outbox.php; do
  [[ -f "$TMP/source/$file" ]] || {
    echo "Required migration entrypoint missing: $file" >&2; exit 1
  }
done
# Preserve the live configuration only in a private, non-web-accessible stage.
cp "$PORTAL/app/config.php" "$TMP/source/app/config.php"
chmod 0600 "$TMP/source/app/config.php"
printf '%s\n' "$RELEASE" > "$TMP/source/.release-sha"
chmod 0600 "$TMP/source/.release-sha"
chmod 0700 "$TMP/source"
mv "$TMP/source" "$STAGE"
echo "Prepared private migration stage: $STAGE"
echo 'No database migrations or live website changes were executed.'
